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

use Fal\Stick\Fw;

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
    const EVENT_LOGIN = 'auth.login';
    const EVENT_LOGOUT = 'auth.logout';
    const EVENT_LOAD_USER = 'auth.load_user';

    /**
     * @var Fw
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
    protected $options;

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
    protected $extraRoles;

    /**
     * Class constructor.
     *
     * @param Fw                       $fw
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array|null               $options
     */
    public function __construct(Fw $fw, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->fw = $fw;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->options = ((array) $options) + array(
            'redirect' => '/',
            'login' => '/login',
            'logout' => null,
            'excludes' => null,
            'rules' => array(),
            'roleHierarchy' => array(),
            'lifetime' => 1541932314, /* 1 week */
        );
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
     * Set current user and trigger login event.
     *
     * @param UserInterface $user
     * @param bool|null     $remember
     *
     * @return Auth
     */
    public function login(UserInterface $user, bool $remember = null): Auth
    {
        $this->fw->trigger(self::EVENT_LOGIN, array($user));

        $this->user = $user;
        $this->fw['SESSION'][self::SESSION_KEY] = $user->getId();

        if ($remember) {
            $this->fw['COOKIE'][self::SESSION_KEY] = array($user->getId(), $this->options['lifetime']);
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
        $this->fw->trigger(self::EVENT_LOGOUT, array($user));

        $this->userLoaded = false;
        $this->user = null;
        unset($this->fw['SESSION'][self::SESSION_KEY], $this->fw['COOKIE'][self::SESSION_KEY], $this->extraRoles);

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

        if (($user = $this->fw->trigger(self::EVENT_LOAD_USER)) && $user instanceof UserInterface) {
            $this->user = $user;
        }

        if ($userId = $this->getCookieUserId()) {
            $this->user = $this->provider->findById($userId);
            $this->extraRoles[] = 'IS_AUTHENTICATED_REMEMBERED';
        } elseif ($userId = $this->getSessionUserId()) {
            $this->user = $this->provider->findById($userId);
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
     * Returns user cookie id.
     *
     * @return mixed
     */
    public function getCookieUserId()
    {
        $id = $this->fw['COOKIE'][self::SESSION_KEY] ?? null;

        return is_array($id) ? array_shift($id) : $id;
    }

    /**
     * Returns user session id.
     *
     * @return mixed
     */
    public function getSessionUserId()
    {
        return $this->fw['SESSION'][self::SESSION_KEY] ?? null;
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
     * Do guard.
     *
     * Return true if request has been redirected.
     *
     * @return bool
     */
    public function guard(): bool
    {
        $path = $this->fw['PATH'];

        if (in_array($path, (array) $this->options['excludes'])) {
            return false;
        }

        if ($this->options['logout'] === $path) {
            $this->logout();
            $this->fw->reroute($this->options['redirect']);

            return true;
        }

        if ($this->options['login'] === $path) {
            if ($this->isLogged()) {
                $this->fw->reroute($this->options['redirect']);

                return true;
            }

            return false;
        }

        foreach ($this->options['rules'] as $check => $roles) {
            if (preg_match('#'.$check.'#', $path) && !$this->isGranted(...((array) $roles))) {
                $this->fw->reroute($this->options['login']);

                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if user has checked roles.
     *
     * @param string ...$checkRoles
     *
     * @return bool
     */
    public function isGranted(string ...$checkRoles): bool
    {
        $user = $this->getUser();
        $userRoles = $user ? $user->getRoles() : array();
        $mCheckRoles = $checkRoles;

        if (array_intersect($mCheckRoles, $userRoles)) {
            return true;
        }

        $roles = array();

        foreach ($userRoles as $role) {
            $roles = array_merge($roles, $this->getRoleHierarchy($role));
        }

        $roles = array_merge($roles, (array) $this->extraRoles, array('IS_AUTHENTICATED_ANONYMOUSLY'));

        return (bool) array_intersect($mCheckRoles, $roles);
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
            $children = (array) $this->options['roleHierarchy'][$role];

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
