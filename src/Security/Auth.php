<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

use Fal\Stick\App;
use Fal\Stick\Helper;

/**
 * Authentication utils
 */
final class Auth
{
    /** Credential messages */
    const CREDENTIAL_INVALID = 'Invalid credentials.';
    const CREDENTIAL_EXPIRED = 'Your credentials is expired.';

    /** Event names */
    const EVENT_LOGIN = 'auth.login';
    const EVENT_LOGOUT = 'auth.logout';
    const EVENT_LOADUSER = 'auth.loaduser';

    /** @var App */
    private $app;

    /** @var UserProviderInterface */
    private $provider;

    /** @var PasswordEncoderInterface */
    private $encoder;

    /** @var array */
    private $options;

    /** @var UserInterface */
    private $user;

    /** @var bool */
    private $userLoaded;

    /**
     * Class constructor
     *
     * @param App                      $app
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array                    $options   @see self::setOption
     */
    public function __construct(App $app, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = [])
    {
        $this->app = $app;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->setOptions($options);
        $this->getUser();
    }

    /**
     * Try login with username and password
     *
     * @param  string $username
     * @param  string $password
     * @param  string &$message
     *
     * @return bool
     */
    public function attempt(string $username, string $password, string &$message = null): bool
    {
        $result = false;
        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $message = self::CREDENTIAL_INVALID;
        } elseif ($user->isExpired()) {
            $message = self::CREDENTIAL_EXPIRED;
        } else {
            $result = true;

            $this->login($user);
        }

        return $result;
    }

    /**
     * Set current user
     *
     * @return void
     */
    public function login(UserInterface $user): void
    {
        if ($this->app->trigger(self::EVENT_LOGIN, [$user, $this])) {
            return;
        }

        $this->user = $user;
        $this->app->set('SESSION.user_login_id', $user->getId());
    }

    /**
     * Finish user session
     *
     * @return void
     */
    public function logout(): void
    {
        if ($this->app->trigger(self::EVENT_LOGOUT, [$this->getUser(), $this])) {
            return;
        }

        $this->userLoaded = false;
        $this->user = null;
        $this->app->clear('SESSION.user_login_id');
    }

    /**
     * Get logged user
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        if ($this->userLoaded || $this->user || $this->app->trigger(self::EVENT_LOADUSER, [$this])) {
            return $this->user;
        }

        $userId = $this->app->get('SESSION.user_login_id');
        $this->userLoaded = true;

        if (!$userId) {
            return null;
        }

        $this->user = $this->provider->findById($userId);

        return $this->user;
    }

    /**
     * Set user
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
     * Check user login
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return isset($this->user);
    }

    /**
     * Do guard
     *
     * @return void
     */
    public function guard(): void
    {
        $path = $this->app['REQ.PATH'];

        if ($path === $this->options['loginPath']) {
            if ($this->isLogged()) {
                $this->app->reroute($this->options['redirect']);
            }

            return;
        }

        foreach ($this->options['rules'] as $check => $roles) {
            if (preg_match('#' . $check . '#', $path) && !$this->isGranted($roles)) {
                $this->app->reroute($this->options['loginPath']);

                return;
            }
        }
    }

    /**
     * Check roles
     *
     * @param  string|array  $checkRoles
     *
     * @return bool
     */
    public function isGranted($checkRoles): bool
    {
        $user = $this->getUser();
        $userRoles = $user ? $user->getRoles() : [];

        if (!$user || !$userRoles) {
            return false;
        }

        $use = Helper::reqarr($checkRoles);

        if (count(array_intersect($use, $userRoles))) {
            return true;
        }

        $roles = [];

        foreach ($userRoles as $userRole) {
            $roles = array_merge($roles, [$userRole], $this->getHierarchy($userRole));
        }

        $roles = array_unique($roles);

        return count(array_intersect($use, $roles)) > 0;
    }

    /**
     * Get user provider
     *
     * @return UserProviderInterface
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }

    /**
     * Get password encoder
     *
     * @return PasswordEncoderInterface
     */
    public function getEncoder(): PasswordEncoderInterface
    {
        return $this->encoder;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set options
     *
     * Valid options:
     *  loginPath       string|array route/path to redirect if user anonymous
     *  redirect        string|array route/path to redirect if user has been login
     *  rules           array        path with valid roles, example: /secure => ROLE_USER,ROLE_ADMIN
     *  roleHierarchy   array        like in symfony roles, example: ROLE_ADMIN => ROLE_USER
     *
     * @param array $options
     *
     * @return Auth
     */
    public function setOptions(array $options): Auth
    {
        $this->options = $options + [
            'loginPath' => '/login',
            'redirect' => '/',
            'rules' => [],
            'roleHierarchy' => [],
        ];

        return $this;
    }

    /**
     * Get hierarchy for specific role
     *
     * @param  string $role
     *
     * @return array
     */
    private function getHierarchy($role): array
    {
        if (!array_key_exists($role, $this->options['roleHierarchy'])) {
            return [];
        }

        $roles = [];
        $children = (array) $this->options['roleHierarchy'][$role];

        foreach ($children as $child) {
            $roles = array_merge($roles, $this->getHierarchy($child));
        }
        $roles = array_merge($roles, $children);

        return $roles;
    }
}
