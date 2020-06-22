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

namespace Ekok\Stick\Security;

use Ekok\Stick\Fw;

/**
 * Authentication.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Auth
{
    const SESSION_KEY = 'usid';

    const EVENT_VOTE = 'auth.vote';
    const EVENT_LOGIN = 'auth.login';
    const EVENT_LOGOUT = 'auth.logout';
    const EVENT_USER_LOAD = 'auth.user_load';
    const EVENT_IMPERSONATE = 'auth.impersonate';
    const EVENT_ORIGINATE = 'auth.originate';

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
     * @var UserInterface
     */
    protected $user;

    /**
     * @var array
     */
    protected $userRoles;

    /**
     * @var array
     */
    protected $extraRoles = array();

    /**
     * @var string
     */
    protected $error;

    /**
     * @var array
     */
    protected $check;

    /**
     * @var array
     */
    protected $options = array(
        'excludes' => array(),
        'rules' => array(),
        'role_hierarchy' => array(),
        'remember_session' => true,
        'remember_cookie' => false,
        'remember_lifetime' => '1 week',
    );

    /**
     * Class constructor.
     */
    public function __construct(Fw $fw, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->fw = $fw;
        $this->provider = $provider;
        $this->encoder = $encoder;

        if ($options) {
            $this->setOptions($options);
        }
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Auth
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * HTTP basic auth mechanism.
     */
    public function basic(): bool
    {
        if (!$this->requireAuthentication()) {
            return true;
        }

        $auth = $this->getAuthorization('Basic %s');

        if ($auth) {
            list($username, $password) = explode(':', base64_decode($auth));

            if ($this->login($username, $password) && $this->authenticate()) {
                return true;
            }
        }

        $message = $this->error ?? 'Unauthorized access.';

        $this->fw->error(401, $message);
        $this->fw->set('HEADER.WWW-Authenticate', "Basic realm=\"{$message}\"");

        return false;
    }

    /**
     * Guard with JWT Token.
     */
    public function jwt(Jwt $jwt): bool
    {
        if (!$this->requireAuthentication()) {
            return true;
        }

        $auth = $this->getAuthorization('Bearer %s');

        if ($auth) {
            try {
                $raw = $jwt->decode($auth);
                $user = $this->provider->fromArray($raw);

                if ($user && $this->setUser($user)->authenticate()) {
                    return true;
                }
            } catch (\Throwable $e) {
                $message = $e->getMessage();
            }
        }

        $this->fw->error(203, $message ?? 'Unauthorized access.');

        return false;
    }

    /**
     * Do guard.
     *
     * Return true if request has been redirected.
     */
    public function guard(): bool
    {
        if (!$this->requireAuthentication()) {
            return true;
        }

        $target = $this->check['logged'] ? $this->check['home'] : $this->check['login'];

        $this->fw->reroute($target);

        return false;
    }

    /**
     * Returns true if user has roles.
     *
     * @param mixed $roles
     * @param mixed $data
     */
    public function isGranted($roles, $data = null): bool
    {
        $attributes = $this->getUserRoles();
        $granted = (bool) array_intersect($this->fw->split($roles), $attributes);
        $arguments = array($this, $granted, $data, $attributes);

        if ($this->fw->dispatch(static::EVENT_VOTE, $arguments, $continue)) {
            return $continue;
        }

        return $granted;
    }

    /**
     * Throw exception if not granted.
     *
     * @param mixed $roles
     * @param mixed $data
     */
    public function denyAccessUnlessGranted($roles, $data = null)
    {
        if (false === $this->isGranted($roles, $data)) {
            throw new \LogicException('Access denied.');
        }
    }

    /**
     * Returns latest error.
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * Attempt login with username key and password key from framework hive.
     */
    public function login(?string $username, ?string $password): bool
    {
        if (!$username || !$password) {
            $this->error = 'Empty credentials.';

            return false;
        }

        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $this->error = 'Invalid credentials.';

            return false;
        }

        if ($user->isExpired()) {
            $this->error = 'Credentials is expired.';

            return false;
        }

        if ($user->isDisabled()) {
            $this->error = 'Credentials is disabled.';

            return false;
        }

        $this->setUser($user);

        if ($this->options['remember_session']) {
            $this->fw->set('SESSION.'.static::SESSION_KEY, $user->getId());
        }

        if ($this->options['remember_cookie']) {
            $original = $this->fw->get('JAR.lifetime');

            $this->fw->set('JAR.lifetime', $this->options['remember_lifetime']);
            $this->fw->set('COOKIE.'.static::SESSION_KEY, $user->getId());
            $this->fw->set('JAR.lifetime', $original);
        }

        $this->fw->dispatch(static::EVENT_LOGIN, array($this, $user));

        return true;
    }

    /**
     * Finish user session.
     */
    public function logout(): Auth
    {
        if ($this->fw->dispatch(static::EVENT_LOGOUT, array($this), $stop) && $stop) {
            return $this;
        }

        $this->user = null;
        $this->userRoles = null;
        $this->extraRoles = array();
        $this->fw->remAll(array(
            'SESSION.impersonate_'.static::SESSION_KEY,
            'SESSION.'.static::SESSION_KEY,
            'COOKIE.'.static::SESSION_KEY,
        ));

        return $this;
    }

    /**
     * Impersonate as user.
     */
    public function impersonate(string $username): bool
    {
        $user = $this->provider->findByUsername($username);

        if (!$user) {
            $this->error = 'User not found.';

            return false;
        }

        $this->setUser($user);

        $this->fw->set('SESSION.as_'.static::SESSION_KEY, $user->getId());
        $this->fw->dispatch(static::EVENT_IMPERSONATE, array($this, $user));

        return true;
    }

    /**
     * Finish impersonating.
     */
    public function originate(): Auth
    {
        $this->fw->dispatch(static::EVENT_ORIGINATE, array($this));

        $this->user = null;
        $this->userRoles = null;
        $this->extraRoles = array();
        $this->fw->rem('SESSION.as_'.static::SESSION_KEY);

        return $this;
    }

    /**
     * Returns true if user is logged in.
     */
    public function hasUser(): bool
    {
        return null !== $this->getUser();
    }

    /**
     * Get logged user.
     */
    public function getUser(): ?UserInterface
    {
        if ($this->user) {
            return $this->user;
        }

        // user already set by custom user loader?
        if ($this->fw->dispatch(static::EVENT_USER_LOAD, array($this)) && $this->user) {
            return $this->user;
        }

        if ($id = $this->fw->get('SESSION.as_'.static::SESSION_KEY)) {
            $this->setUser($this->provider->findById($id), array('ROLE_PREVIOUS_ADMIN'));
        } elseif ($id = $this->fw->get('COOKIE.'.static::SESSION_KEY)) {
            $this->setUser($this->provider->findById($id), array('IS_AUTHENTICATED_REMEMBERED'));
        } elseif ($id = $this->fw->get('SESSION.'.static::SESSION_KEY)) {
            $this->setUser($this->provider->findById($id), array('IS_AUTHENTICATED_FULLY'));
        }

        return $this->user;
    }

    public function getOriginalUser(): ?UserInterface
    {
        $id = $this->fw->get('COOKIE.'.static::SESSION_KEY) ?: $this->fw->get('SESSION.'.static::SESSION_KEY);

        return $this->provider->findById($id);
    }

    /**
     * Sets user.
     */
    public function setUser(UserInterface $user, array $extraRoles = null): Auth
    {
        $this->user = $user;
        $this->userRoles = null;

        if ($user && $extraRoles) {
            array_push($this->extraRoles, ...$extraRoles);
        }

        return $this;
    }

    /**
     * Returns user roles.
     */
    public function getUserRoles(): array
    {
        if (null === $this->userRoles) {
            $userRoles = $this->extraRoles;

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
     * Returns hierarchy for specific role.
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

    protected function authenticate(): bool
    {
        return $this->isGranted($this->check['roles']);
    }

    protected function requireAuthentication(): bool
    {
        if ($this->options['excludes'] && in_array($this->fw->get('PATH'), $this->options['excludes'])) {
            return false;
        }

        foreach ($this->options['rules'] ?? array() as $pattern => $rule) {
            if ($check = $this->checkRule($pattern, $rule)) {
                $this->check = $check;

                return true;
            }
        }

        return false;
    }

    protected function checkRule(string $pattern, $rule): ?array
    {
        $path = $this->fw->get('PATH');
        $use = is_array($rule) ? $rule : array('roles' => $rule);
        $login = $use['login'] ?? '/login';
        $home = $use['home'] ?? '/';
        $roles = $use['roles'];

        if (
            ($logged = $path === $login && $this->getUser())
            || preg_match('#'.$pattern.'#', $path) && !$this->isGranted($roles)
        ) {
            return array(
                'home' => $home,
                'logged' => $logged,
                'login' => $login,
                'pattern' => $pattern,
                'roles' => $roles,
            );
        }

        return null;
    }

    protected function getAuthorization(string $format): ?string
    {
        sscanf($this->fw->get('SERVER.HTTP_AUTHORIZATION') ?? '', $format, $authorization);

        return $authorization;
    }
}
