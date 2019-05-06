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
use Fal\Stick\Util\Option;

/**
 * Authentication helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Auth
{
    // session key
    const SESSION_KEY = 'user_login_id';

    // Events
    const EVENT_VOTE = 'auth.vote';
    const EVENT_LOGIN = 'auth.login';
    const EVENT_LOGOUT = 'auth.logout';
    const EVENT_LOADUSER = 'auth.loaduser';
    const EVENT_IMPERSONATE_ON = 'auth.impersonate_on';
    const EVENT_IMPERSONATE_OFF = 'auth.impersonate_off';

    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var UserProviderInterface
     */
    public $provider;

    /**
     * @var PasswordEncoderInterface
     */
    public $encoder;

    /**
     * @var Option
     */
    public $options;

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
     * @var string
     */
    protected $error;

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
        $this->options = (new Option())
            ->add('excludes', array(), 'array|string', true)
            ->add('rules', array(), 'array', true)
            ->add('role_hierarchy', array(), 'array', true)
            ->add('remember_session', true, 'bool', true)
            ->add('remember_cookie', false, 'bool', true)
            ->add('remember_lifetime', '1 week', 'string|int|DateTime', true)
            ->add('basic_realm', $fw->currentUrl(true), 'string', true)
            ->resolve($options ?? array());
    }

    /**
     * HTTP basic auth mechanism.
     *
     * Returns true if need authenticate.
     *
     * @return bool
     */
    public function basic(): bool
    {
        $username = 'SERVER.PHP_AUTH_USER';
        $password = 'SERVER.PHP_AUTH_PW';

        if ($header = $this->fw->get('HEADERS.Authorization') ?? $this->fw->get('SERVER.REDIRECT_HTTP_AUTHORIZATION')) {
            list($this->fw->$username, $this->fw->$password) = explode(':', base64_decode(substr($header, 6)));
        }

        if ($this->login(true, $username, $password)) {
            return false;
        }

        $this->fw->hset('WWW-Authenticate', 'Basic realm="'.$this->options['basic_realm'].'"');
        $this->fw->error(401);

        return true;
    }

    /**
     * Guard with JWT Token.
     *
     * @param Jwt     $jwt
     * @param Closure $toUser
     *
     * @return bool
     */
    public function jwt(Jwt $jwt, \Closure $toUser): bool
    {
        if ($header = $this->fw->get('HEADERS.Authorization') ?? $this->fw->get('SERVER.REDIRECT_HTTP_AUTHORIZATION')) {
            $token = substr($header, 7);
            $user = $toUser($jwt->decode($token));
            $this->user || $this->setUser($user);
        }

        return $this->guard();
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
        $path = $this->fw->get('PATH');

        if ($this->options['excludes'] && in_array($path, $this->fw->split($this->options['excludes']))) {
            return false;
        }

        foreach ($this->options['rules'] as $check => $rule) {
            if (!is_array($rule)) {
                $rule = array('roles' => $rule);
            }

            if (empty($rule['roles'])) {
                throw new \LogicException(sprintf('No roles for rule: %s.', $check));
            }

            $rule += array('login' => '/login', 'home' => '/');

            if ($rule['login'] === $path && $this->isLogged()) {
                // if not granted, logout automatically
                $this->isGranted($rule['roles']) || $this->logout();
                // reroute to home
                $this->fw->reroute($rule['home']);

                return true;
            }

            if (preg_match('#'.$check.'#', $path) && !$this->isGranted($rule['roles'])) {
                $this->fw->reroute($rule['login']);

                return true;
            }
        }

        return false;
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
        $dispatch = $this->fw->dispatch(self::EVENT_VOTE, $this, $granted, $data, $attributes);

        if ($dispatch && false === $dispatch[0]) {
            return false;
        }

        return $granted;
    }

    /**
     * Is granted complement.
     *
     * @param mixed $roles
     * @param mixed $data
     *
     * @return bool
     */
    public function isNotGranted($roles, $data = null): bool
    {
        return !$this->isGranted($roles, $data);
    }

    /**
     * Returns latest error.
     *
     * @return string|null
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * Attempt login with username key and password key from framework hive.
     *
     * @param bool   $doLogin
     * @param string $usernameKey
     * @param string $passwordKey
     *
     * @return bool
     */
    public function login(bool $doLogin, string $usernameKey = null, string $passwordKey = null): bool
    {
        if (!$doLogin) {
            return false;
        }

        $username = $this->fw->get($usernameKey ?? 'POST.username');
        $password = $this->fw->get($passwordKey ?? 'POST.password');

        if (!$username || !$password) {
            $this->error = 'Empty credentials.';

            return false;
        }

        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $this->error = 'Invalid credentials.';

            return false;
        }

        if ($user->isCredentialsExpired()) {
            $this->error = 'Your credentials is expired.';

            return false;
        }

        if ($this->options['remember_session']) {
            $this->fw->set('SESSION.'.self::SESSION_KEY, $user->getId());
        }

        if ($this->options['remember_cookie']) {
            $this->fw->cookie(self::SESSION_KEY, $user->getId(), array(
                'expires' => $this->options['remember_lifetime'],
            ));
        }

        $this->fw->dispatch(self::EVENT_LOGIN, $this, $user);
        $this->setUser($user);

        return true;
    }

    /**
     * Finish user session.
     *
     * @return Auth
     */
    public function logout(): Auth
    {
        $dispatch = $this->fw->dispatch(self::EVENT_LOGOUT, $this);

        if ($dispatch && false === $dispatch[0]) {
            return $this;
        }

        $this->userLoaded = false;
        $this->userRoles = null;
        $this->user = null;
        $this->extraRoles = null;
        $this->fw->mrem(array(
            'SESSION.impersonate_'.self::SESSION_KEY,
            'SESSION.'.self::SESSION_KEY,
            'COOKIE.'.self::SESSION_KEY,
        ));

        return $this;
    }

    /**
     * Impersonate as user.
     *
     * @param string $username
     *
     * @return bool
     */
    public function impersonateOn(string $username): bool
    {
        $user = $this->provider->findByUsername($username);

        if (!$user) {
            $this->error = 'User not found.';

            return false;
        }

        if ($user->isCredentialsExpired()) {
            $this->error = 'User credentials is expired.';

            return false;
        }

        $this->fw->set('SESSION.impersonate_'.self::SESSION_KEY, $user->getId());

        $this->fw->dispatch(self::EVENT_IMPERSONATE_ON, $this, $user);
        $this->setUser($user);

        return true;
    }

    /**
     * Finish impersonating.
     *
     * @return Auth
     */
    public function impersonateOff(): Auth
    {
        $dispatch = $this->fw->dispatch(self::EVENT_IMPERSONATE_OFF, $this);

        $this->userLoaded = false;
        $this->userRoles = null;
        $this->user = null;
        $this->extraRoles = null;
        $this->fw->rem('SESSION.impersonate_'.self::SESSION_KEY);

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

        // user already set by custom user loader?
        $dispatch = $this->fw->dispatch(self::EVENT_LOADUSER, $this);

        if (($dispatch && false === $dispatch[0]) || !$this->user) {
            if ($userId = $this->fw->get('SESSION.impersonate_'.self::SESSION_KEY)) {
                $this->setUser($this->provider->findById($userId));
                $this->extraRoles[] = 'ROLE_PREVIOUS_ADMIN';
            } elseif ($userId = $this->fw->get('COOKIE.'.self::SESSION_KEY)) {
                $this->setUser($this->provider->findById($userId));
                $this->extraRoles[] = 'IS_AUTHENTICATED_REMEMBERED';
            } elseif ($userId = $this->fw->get('SESSION.'.self::SESSION_KEY)) {
                $this->setUser($this->provider->findById($userId));
                $this->extraRoles[] = 'IS_AUTHENTICATED_FULLY';
            }
        }

        return $this->user;
    }

    /**
     * Sets user.
     *
     * @param UserInterface $user
     *
     * @return Auth
     */
    public function setUser(UserInterface $user): Auth
    {
        $this->user = $user;
        $this->userRoles = null;
        $this->userLoaded = true;

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

            $this->userRoles = array_unique($userRoles);
        }

        return $this->userRoles;
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
     * Returns hierarchy for specific role.
     *
     * @param string $role
     *
     * @return array
     */
    protected function getRoleHierarchy(string $role): array
    {
        $roles = array($role);

        if (array_key_exists($role, $this->options['role_hierarchy'])) {
            $children = $this->fw->split($this->options['role_hierarchy'][$role]);

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
