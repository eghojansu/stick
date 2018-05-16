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

final class Auth
{
    /** Credential message */
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
    private $option;

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
     * @param array                    $option
     */
    public function __construct(App $app, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $option = [])
    {
        $this->app = $app;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->setOption($option);
        $this->getUser();
    }

    /**
     * Attempt login
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
     * Login user
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

        if ($path === $this->option['loginPath']) {
            if ($this->isLogged()) {
                $this->app->reroute($this->option['redirect']);
            }

            return;
        }

        foreach ($this->option['rules'] as $check => $roles) {
            if (preg_match('#' . $check . '#', $path) && !$this->isGranted($roles)) {
                $this->app->reroute($this->option['loginPath']);

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
     * Get provider
     *
     * @return UserProviderInterface
     */
    public function getProvider(): UserProviderInterface
    {
        return $this->provider;
    }

    /**
     * Get encoder
     *
     * @return PasswordEncoderInterface
     */
    public function getEncoder(): PasswordEncoderInterface
    {
        return $this->encoder;
    }

    /**
     * Get option
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->option;
    }

    /**
     * Set option
     *
     * @param array $option
     * @return Auth
     */
    public function setOption(array $option): Auth
    {
        $this->option = $option + [
            'loginPath' => '/login',
            'redirect' => '/',
            'rules' => [],
            'roleHierarchy' => [],
        ];

        return $this;
    }

    /**
     * Get hierarchy for role
     *
     * @param  string $role
     *
     * @return array
     */
    private function getHierarchy($role): array
    {
        if (!array_key_exists($role, $this->option['roleHierarchy'])) {
            return [];
        }

        $roles = [];
        $children = (array) $this->option['roleHierarchy'][$role];

        foreach ($children as $child) {
            $roles = array_merge($roles, $this->getHierarchy($child));
        }
        $roles = array_merge($roles, $children);

        return $roles;
    }
}
