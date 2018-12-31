<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Security;

use Fal\Stick\Core;
use Fal\Stick\HttpException;

/**
 * Authentication helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Auth
{
    const SESSION_KEY = 'user_login_id';

    // Error messages
    const ERROR_CREDENTIAL_INVALID = 'Invalid credentials.';
    const ERROR_CREDENTIAL_EXPIRED = 'Your credentials is expired.';

    // Supported events
    const EVENT_LOGIN = 'auth_login';
    const EVENT_LOGOUT = 'auth_logout';
    const EVENT_LOAD_USER = 'auth_load_user';
    const EVENT_VOTE = 'auth_vote';

    /**
     * @var Core
     */
    protected $fw;

    /**
     * @var UserProviderInterface
     */
    protected $provider;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var array
     */
    protected $options = array(
        'excludes' => array(),
        'logout' => array(),
        'rules' => array(),
        'roleHierarchy' => array(),
        'lifetime' => 1541932314, /* 1 week */
    );

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var bool
     */
    protected $userLoaded;

    /**
     * @var array
     */
    protected $userRoles;

    /**
     * @var array
     */
    protected $extraRoles;

    /**
     * Class constructor.
     *
     * @param Core                       $fw
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array|null               $options
     */
    public function __construct(Core $fw, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->fw = $fw;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->setOptions($options ?? array());
    }

    /**
     * Do guard.
     *
     * Return true if request has been redirected.
     *
     * @return bool
     */
    public function guard(): bool
    {
        $path = urldecode($this->fw->get('PATH'));

        if (in_array($path, $this->options['excludes'])) {
            return false;
        }

        if (isset($this->options['logout'][$path])) {
            $this->logout();
            $this->fw->reroute($this->options['logout'][$path]);

            return true;
        }

        foreach ($this->options['rules'] as $check => $rule) {
            $login = $rule['login'] ?? '/login';
            $home = $rule['home'] ?? '/';
            $roles = $rule['roles'] ?? null;

            if ($login === $path) {
                if ($this->isLogged()) {
                    $this->fw->reroute($home);

                    return true;
                }

                return false;
            }

            if (preg_match('#'.$check.'#', $path) && !$this->isGranted($roles)) {
                $this->fw->reroute($login);

                return true;
            }
        }

        return false;
    }

    /**
     * Throw exception if not granted.
     *
     * @param mixed $roles
     * @param mixed $data
     */
    public function denyAccessUnlessGranted($roles, $data = null): void
    {
        if (!$this->isGranted($roles, $data)) {
            throw new HttpException('Access denied.', 403);
        }
    }

    /**
     * Returns true if user has roles.
     *
     * @param mixed $roles
     * @param mixed $data
     *
     * @return bool
     */
    public function isGranted($roles, $data = null): bool
    {
        $attributes = $this->getUserRoles();
        $granted = (bool) array_intersect($this->fw->split($roles), $attributes);

        if ($granted && false === $this->fw->dispatch(self::EVENT_VOTE, array($data, $attributes))) {
            return false;
        }

        return $granted;
    }

    /**
     * Attempt login with username and password.
     *
     * @param string      $username
     * @param string      $password
     * @param bool|null   $remember
     * @param string|null &$message
     *
     * @return bool
     */
    public function attempt(string $username, string $password, bool $remember = null, string &$message = null): bool
    {
        $result = false;
        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $message = self::ERROR_CREDENTIAL_INVALID;
        } elseif ($user->isCredentialsExpired()) {
            $message = self::ERROR_CREDENTIAL_EXPIRED;
        } else {
            $result = true;

            $this->login($user, $remember);
        }

        return $result;
    }

    /**
     * Set current user and dispatch login event.
     *
     * @param UserInterface $user
     * @param bool|null     $remember
     *
     * @return Auth
     */
    public function login(UserInterface $user, bool $remember = null): Auth
    {
        $this->fw->dispatch(self::EVENT_LOGIN, array($user));

        $this->user = $user;
        $this->fw->set('SESSION.'.self::SESSION_KEY, $user->getId());

        if ($remember) {
            $this->fw->set('COOKIE.'.self::SESSION_KEY, array($user->getId(), $this->options['lifetime']));
        }

        return $this;
    }

    /**
     * Finish user session.
     *
     * @return Auth
     */
    public function logout(): Auth
    {
        $user = $this->getUser();
        $this->fw->dispatch(self::EVENT_LOGOUT, array($user));

        $this->userLoaded = false;
        $this->userRoles = null;
        $this->user = null;
        $this->extraRoles = null;
        $this->fw->allClear('SESSION.'.self::SESSION_KEY.',COOKIE.'.self::SESSION_KEY);

        return $this;
    }

    /**
     * Get logged user.
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        if ($this->userLoaded || $this->user) {
            return $this->user;
        }

        if (($user = $this->fw->dispatch(self::EVENT_LOAD_USER)) && $user instanceof UserInterface) {
            $this->user = $user;
            $this->userRoles = null;
        }

        if ($userId = $this->getCookieUserId()) {
            $this->user = $this->provider->findById($userId);
            $this->userRoles = null;
            $this->extraRoles[] = 'IS_AUTHENTICATED_REMEMBERED';
        } elseif ($userId = $this->getSessionUserId()) {
            $this->user = $this->provider->findById($userId);
            $this->userRoles = null;
            $this->extraRoles[] = 'IS_AUTHENTICATED_FULLY';
        }

        $this->userLoaded = true;

        return $this->user;
    }

    /**
     * Directly sets user.
     *
     * @param UserInterface|null $user
     *
     * @return Auth
     */
    public function setUser(UserInterface $user = null): Auth
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Returns user roles.
     *
     * @return array
     */
    public function getUserRoles(): array
    {
        if (null === $this->userRoles) {
            $userRoles = $this->extraRoles;
            $userRoles[] = 'IS_AUTHENTICATED_ANONYMOUSLY';

            if ($user = $this->getUser()) {
                foreach ($user->getRoles() as $role) {
                    if ($role) {
                        array_push($userRoles, ...$this->getRoleHierarchy($role));
                    }
                }
            }

            $this->userRoles = $userRoles;
        }

        return $this->userRoles;
    }

    /**
     * Returns user cookie id.
     *
     * @return mixed
     */
    public function getCookieUserId()
    {
        $id = $this->fw->get('COOKIE.'.self::SESSION_KEY);

        return is_array($id) ? array_shift($id) : $id;
    }

    /**
     * Returns user session id.
     *
     * @return mixed
     */
    public function getSessionUserId()
    {
        return $this->fw->get('SESSION.'.self::SESSION_KEY);
    }

    /**
     * Returns true if user is logged in.
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return null !== $this->getUser();
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Sets options.
     *
     * @param array $options
     *
     * @return Auth
     */
    public function setOptions(array $options): Auth
    {
        foreach ($options as $option => $value) {
            if (in_array($option, array('excludes', 'logout', 'roleHierarchy', 'rules')) && !is_array($value)) {
                $value = (array) $value;
            } elseif ('lifetime' === $option && !is_int($value)) {
                $value = $this->options[$option];
            }

            $this->options[$option] = $value;
        }

        return $this;
    }

    /**
     * Returns user provider.
     *
     * @return UserProviderInterface
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }

    /**
     * Returns password encoder.
     *
     * @return PasswordEncoderInterface
     */
    public function getEncoder(): PasswordEncoderInterface
    {
        return $this->encoder;
    }

    /**
     * Returns hierarchy for specific role.
     *
     * @param string $role
     *
     * @return array
     */
    protected function getRoleHierarchy(string $role): array
    {
        $roles = array($role);

        if (array_key_exists($role, $this->options['roleHierarchy'])) {
            $children = $this->fw->split($this->options['roleHierarchy'][$role]);

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
