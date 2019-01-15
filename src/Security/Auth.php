<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

use Fal\Stick\Core;

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
    const ON_LOGIN = 'auth.login';
    const ON_LOGOUT = 'auth.logout';
    const ON_LOAD_USER = 'auth.load_user';
    const ON_VOTE = 'auth.vote';

    /**
     * @var Core
     */
    protected $core;

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
    protected $options = [
        'excludes'      => [],
        'logout'        => [],
        'rules'         => [],
        'roleHierarchy' => [],
        'lifetime'      => 1541932314, /* 1 week */
    ];

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
     * @param Core                     $core
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array|null               $options
     */
    public function __construct(Core $core, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->core = $core;
        $this->provider = $provider;
        $this->encoder = $encoder;
        empty($options) || $this->setOptions($options);
    }

    /**
     * Do guard.
     *
     * Return true if request has been redirected.
     *
     * @return bool
     */
    public function guard()
    {
        $path = urldecode($this->core->get('PATH'));

        if (in_array($path, $this->options['excludes'])) {
            return false;
        }

        if (isset($this->options['logout'][$path])) {
            $this->logout();
            $this->core->reroute($this->options['logout'][$path]);

            return true;
        }

        foreach ($this->options['rules'] as $check => $rule) {
            $login = isset($rule['login']) ? $rule['login'] : '/login';
            $home = isset($rule['home']) ? $rule['home'] : '/';
            $roles = isset($rule['roles']) ? $rule['roles'] : null;

            if ($login === $path) {
                if ($this->isLogged()) {
                    $this->core->reroute($home);

                    return true;
                }

                return false;
            }

            if (preg_match('#'.$check.'#', $path) && !$this->isGranted($roles)) {
                $this->core->reroute($login);

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
    public function isGranted($roles, $data = null)
    {
        $attributes = $this->getUserRoles();
        $granted = (bool) array_intersect($this->core->split($roles), $attributes);

        if ($granted) {
            $event = new Event\VoteEvent($this, $data, $attributes);
            $this->core->dispatch(static::ON_VOTE, $event);

            return $event->isGranted();
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
    public function attempt($username, $password, $remember = null, &$message = null)
    {
        $result = false;
        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $message = static::ERROR_CREDENTIAL_INVALID;
        } elseif ($user->isCredentialsExpired()) {
            $message = static::ERROR_CREDENTIAL_EXPIRED;
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
    public function login(UserInterface $user, $remember = null)
    {
        $event = new Event\LoginEvent($this, $user);
        $this->core->dispatch(static::ON_LOGIN, $event);

        $this->user = $event->getUser();
        $this->core->set('SESSION.'.static::SESSION_KEY, $user->getId());

        if ($remember) {
            $this->core->cookie(static::SESSION_KEY, $user->getId(), $this->options['lifetime']);
        }

        return $this;
    }

    /**
     * Finish user session.
     *
     * @return Auth
     */
    public function logout()
    {
        $event = new Event\LogoutEvent($this);
        $this->core->dispatch(static::ON_LOGOUT, $event);

        $this->userLoaded = false;
        $this->userRoles = null;
        $this->user = null;
        $this->extraRoles = null;
        $this->core->clear('SESSION.'.static::SESSION_KEY);
        $this->core->cookieRemove(static::SESSION_KEY);

        return $this;
    }

    /**
     * Get logged user.
     *
     * @return UserInterface|null
     */
    public function getUser()
    {
        if ($this->userLoaded || $this->user) {
            return $this->user;
        }

        $event = new Event\LoadUserEvent($this);
        $this->core->dispatch(static::ON_LOAD_USER, $event);

        if ($event->isPropagationStopped()) {
            $this->user = $event->getUser();
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
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Returns user roles.
     *
     * @return array
     */
    public function getUserRoles()
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
        $id = $this->core->get('COOKIE.'.static::SESSION_KEY);

        return is_array($id) ? array_shift($id) : $id;
    }

    /**
     * Returns user session id.
     *
     * @return mixed
     */
    public function getSessionUserId()
    {
        return $this->core->get('SESSION.'.static::SESSION_KEY);
    }

    /**
     * Returns true if user is logged in.
     *
     * @return bool
     */
    public function isLogged()
    {
        return null !== $this->getUser();
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions()
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
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            if (in_array($option, ['excludes', 'logout', 'roleHierarchy', 'rules']) && !is_array($value)) {
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
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Returns password encoder.
     *
     * @return PasswordEncoderInterface
     */
    public function getEncoder()
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
    protected function getRoleHierarchy($role)
    {
        $roles = [$role];

        if (array_key_exists($role, $this->options['roleHierarchy'])) {
            $children = $this->core->split($this->options['roleHierarchy'][$role]);

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
