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

namespace Fal\Stick\Web\Security;

use Fal\Stick\EventDispatcher\EventDispatcherInterface;
use Fal\Stick\Util;
use Fal\Stick\Web\Cookie;
use Fal\Stick\Web\Exception\ForbiddenException;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStackInterface;
use Fal\Stick\Web\Response;
use Fal\Stick\Web\Security\Event\LoadUserEvent;
use Fal\Stick\Web\Security\Event\LoginEvent;
use Fal\Stick\Web\Security\Event\LogoutEvent;
use Fal\Stick\Web\Security\Event\VoteEvent;
use Fal\Stick\Web\Session\SessionInterface;
use Fal\Stick\Web\UrlGeneratorInterface;

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
    const ON_LOADUSER = 'auth.loaduser';
    const ON_VOTE = 'auth.vote';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var RequestStackInterface
     */
    protected $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

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
        'role_hierarchy' => array(),
        'lifetime' => '1 week',
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
     * @param EventDispatcherInterface $eventDispatcher
     * @param SessionInterface         $session
     * @param RequestStackInterface    $requestStack
     * @param UrlGeneratorInterface    $urlGenerator
     * @param UserProviderInterface    $provider
     * @param PasswordEncoderInterface $encoder
     * @param array|null               $options
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, SessionInterface $session, RequestStackInterface $requestStack, UrlGeneratorInterface $urlGenerator, UserProviderInterface $provider, PasswordEncoderInterface $encoder, array $options = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->provider = $provider;
        $this->encoder = $encoder;
        $this->setOptions($options ?? array());
    }

    /**
     * Do guard.
     *
     * Return true if request has been redirected.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function guard(Request $request): ?Response
    {
        $path = $request->getPath();

        if (in_array($path, $this->options['excludes'])) {
            return null;
        }

        if (isset($this->options['logout'][$path])) {
            $response = $this->logout();
            $redirect = $this->urlGenerator->redirect($this->options['logout'][$path]);

            foreach ($response->headers->getFlatCookies() as $cookie) {
                $redirect->headers->addCookie($cookie);
            }

            return $redirect;
        }

        foreach ($this->options['rules'] as $check => $rule) {
            $login = '/login';
            $home = '/';
            $roles = $rule;

            if (is_array($rule)) {
                $login = $rule['login'] ?? $login;
                $home = $rule['home'] ?? $home;
                $roles = $rule['roles'] ?? null;
            }

            if ($login === $path) {
                if ($this->isLogged()) {
                    return $this->urlGenerator->redirect($home);
                }

                return null;
            }

            if (preg_match('#'.$check.'#', $path) && !$this->isGranted($roles)) {
                return $this->urlGenerator->redirect($login);
            }
        }

        return null;
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
            throw new ForbiddenException('Access denied.');
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
        $granted = (bool) array_intersect(Util::split($roles), $attributes);

        $event = new VoteEvent($this, $granted, $data, $attributes);
        $this->eventDispatcher->dispatch(static::ON_VOTE, $event);

        if ($event->isPropagationStopped()) {
            return false;
        }

        return $granted;
    }

    /**
     * Attempt login with username and password.
     *
     * @param string      $username
     * @param string      $password
     * @param bool        $remember
     * @param string|null &$message
     *
     * @return Response|null
     */
    public function attempt(string $username, string $password, bool $remember = false, string &$message = null): ?Response
    {
        $user = $this->provider->findByUsername($username);

        if (!$user || !$this->encoder->verify($password, $user->getPassword())) {
            $message = static::ERROR_CREDENTIAL_INVALID;

            return null;
        }

        if ($user->isCredentialsExpired()) {
            $message = static::ERROR_CREDENTIAL_EXPIRED;

            return null;
        }

        return $this->login($user, $remember);
    }

    /**
     * Set current user and dispatch login event.
     *
     * @param UserInterface $user
     * @param bool          $remember
     *
     * @return Response
     */
    public function login(UserInterface $user, bool $remember = false): Response
    {
        $event = new LoginEvent($this, $user, $remember);
        $this->eventDispatcher->dispatch(static::ON_LOGIN, $event);

        $response = new Response('OK', 200);
        $this->user = $event->getUser();
        $this->session->set(static::SESSION_KEY, $user->getId());

        if ($remember) {
            $cookie = new Cookie(static::SESSION_KEY, $user->getId(), $this->options['lifetime']);
            $response->headers->addCookie($cookie);
        }

        return $response;
    }

    /**
     * Finish user session.
     *
     * @return Response
     */
    public function logout(): Response
    {
        $user = $this->getUser();
        $event = new LogoutEvent($this, $user);
        $this->eventDispatcher->dispatch(static::ON_LOGOUT, $event);

        $this->userLoaded = false;
        $this->userRoles = null;
        $this->user = null;
        $this->extraRoles = null;
        $this->session->clear(static::SESSION_KEY);

        $response = new Response('OK', 200);
        $response->headers->clearCookie(static::SESSION_KEY);

        return $response;
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

        $event = new LoadUserEvent($this);
        $this->eventDispatcher->dispatch(static::ON_LOADUSER, $event);

        if ($event->hasUser()) {
            $this->user = $event->getUser();
        } elseif ($userId = $this->getCookieUserId()) {
            $this->user = $this->provider->findById($userId);
            $this->extraRoles[] = 'IS_AUTHENTICATED_REMEMBERED';
        } elseif ($userId = $this->getSessionUserId()) {
            $this->user = $this->provider->findById($userId);
            $this->extraRoles[] = 'IS_AUTHENTICATED_FULLY';
        }

        $this->userRoles = null;
        $this->userLoaded = true;

        return $this->user;
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
     * Returns user cookie id.
     *
     * @return mixed
     */
    public function getCookieUserId()
    {
        return $this->requestStack->getCurrentRequest()->cookies->get(static::SESSION_KEY);
    }

    /**
     * Returns user session id.
     *
     * @return mixed
     */
    public function getSessionUserId()
    {
        return $this->session->get(static::SESSION_KEY);
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
            if (in_array($option, array('excludes', 'logout', 'role_hierarchy', 'rules')) && !is_array($value)) {
                $value = (array) $value;
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

        if (array_key_exists($role, $this->options['role_hierarchy'])) {
            $children = Util::split($this->options['role_hierarchy'][$role]);

            foreach ($children as $child) {
                $roles = array_merge($roles, $this->getRoleHierarchy($child));
            }

            return array_merge($roles, $children);
        }

        return $roles;
    }
}
