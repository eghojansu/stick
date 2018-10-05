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

use Fal\Stick\App;

/**
 * Authentication utils.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Auth
{
    const SESSION_KEY = 'SESSION.user_login_id';

    // Error messages
    const ERROR_CREDENTIAL_INVALID = 'Invalid credentials.';
    const ERROR_CREDENTIAL_EXPIRED = 'Your credentials is expired.';

    // Supported events
    const EVENT_LOGIN = 'auth_login';
    const EVENT_LOGOUT = 'auth_logout';
    const EVENT_LOAD_USER = 'auth_load_user';

    /**
     * @var App
     */
    protected $app;

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
     * Class constructor.
     *
     * @param App                      $app
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array                    $options
     */
    public function __construct(App $app, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->app = $app;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->setOptions((array) $options);
    }

    /**
     * Attempt login with username and password.
     *
     * @param string $username
     * @param string $password
     * @param string &$message
     *
     * @return bool
     */
    public function attempt(string $username, string $password, string &$message = null): bool
    {
        $result = false;
        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $message = self::ERROR_CREDENTIAL_INVALID;
        } elseif ($user->isCredentialsExpired()) {
            $message = self::ERROR_CREDENTIAL_EXPIRED;
        } else {
            $result = true;

            $this->login($user);
        }

        return $result;
    }

    /**
     * Set current user and trigger login event.
     *
     * @param UserInterface $user
     *
     * @return Auth
     */
    public function login(UserInterface $user): Auth
    {
        $this->app->trigger(self::EVENT_LOGIN, array($this, $user));

        $this->user = $user;
        $this->app->set(self::SESSION_KEY, $user->getId());

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
        $this->app->trigger(self::EVENT_LOGOUT, array($this, $user));

        $this->userLoaded = false;
        $this->user = null;
        $this->app->clear(self::SESSION_KEY);

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

        if (!$this->app->trigger(self::EVENT_LOAD_USER, array($this)) && $userId = $this->getSessionUserId()) {
            $this->user = $this->provider->findById($userId);
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
     * Returns user session id.
     *
     * @return string|null
     */
    public function getSessionUserId(): ?string
    {
        return $this->app->get(self::SESSION_KEY);
    }

    /**
     * Check user login.
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return null !== $this->getUser();
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
        $path = $this->app->get('PATH');

        if ($path === $this->options['loginPath']) {
            if ($this->isLogged()) {
                $this->app->reroute($this->options['redirect']);

                return true;
            }

            return false;
        }

        foreach ($this->options['rules'] as $check => $roles) {
            if (preg_match('#'.$check.'#', $path) && !$this->isGranted($roles)) {
                $this->app->reroute($this->options['loginPath']);

                return true;
            }
        }

        return false;
    }

    /**
     * Check roles against current user roles.
     *
     * @param string|array $checkRoles
     *
     * @return bool
     */
    public function isGranted($checkRoles): bool
    {
        $user = $this->getUser();

        if (!$user || !($userRoles = $user->getRoles())) {
            return false;
        }

        $use = $this->app->arr($checkRoles);

        if (array_intersect($use, $userRoles)) {
            return true;
        }

        $roles = array();

        foreach ($userRoles as $userRole) {
            $roles = array_merge($roles, array($userRole), $this->getRoleHierarchy($userRole));
        }

        return (bool) array_intersect($use, $roles);
    }

    /**
     * Get user provider.
     *
     * @return UserProviderInterface
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }

    /**
     * Get password encoder.
     *
     * @return PasswordEncoderInterface
     */
    public function getEncoder(): PasswordEncoderInterface
    {
        return $this->encoder;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set options.
     *
     * Valid options:
     *
     *     loginPath       string|array route/path to redirect if user anonymous
     *     redirect        string|array route/path to redirect if user has been login
     *     rules           array        path with valid roles, example: ['/secure' => 'ROLE_USER,ROLE_ADMIN']
     *     roleHierarchy   array        like in symfony roles, example: ['ROLE_ADMIN' => 'ROLE_USER']
     *
     * @param array $options
     *
     * @return Auth
     */
    public function setOptions(array $options): Auth
    {
        $this->options = $options + array(
            'loginPath' => '/login',
            'redirect' => '/',
            'rules' => array(),
            'roleHierarchy' => array(),
        );

        return $this;
    }

    /**
     * Get hierarchy for specific role.
     *
     * @param string $role
     *
     * @return array
     */
    protected function getRoleHierarchy($role): array
    {
        $roles = array();

        if (array_key_exists($role, $this->options['roleHierarchy'])) {
            $children = $this->app->arr($this->options['roleHierarchy'][$role]);

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
