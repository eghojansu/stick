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

namespace Fal\Stick\Web\Helper;

use Fal\Stick\Container\ContainerInterface;
use Fal\Stick\Database\Mapper;
use Fal\Stick\Template\TemplateInterface;
use Fal\Stick\Translation\TranslatorInterface;
use Fal\Stick\Util;
use Fal\Stick\Web\Exception\NotFoundException;
use Fal\Stick\Web\Form\Form;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStackInterface;
use Fal\Stick\Web\Response;
use Fal\Stick\Web\Router\RouterInterface;
use Fal\Stick\Web\Security\Auth;
use Fal\Stick\Web\Session\SessionInterface;
use Fal\Stick\Web\UrlGeneratorInterface;

/**
 * Logic for handling CRUD.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Crud
{
    const STATE_LISTING = 'listing';
    const STATE_VIEW = 'view';
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';
    const STATE_DELETE = 'delete';
    const STATE_FORBIDDEN = 'forbidden';

    /**
     * @var array
     */
    protected static $optionsTypes = array(
        'append_query' => 'boolean',
        'create_new' => 'boolean',
        'create_new_label' => 'string',
        'create_new_session_key' => 'string',
        'created_message' => 'string',
        'created_message_key' => 'string',
        'deleted_message' => 'string',
        'deleted_message_key' => 'string',
        'field_orders' => 'string|array',
        'fields' => 'array',
        'filters' => 'array',
        'form' => 'string|object',
        'form_options' => 'array',
        'keyword' => 'string',
        'keyword_name' => 'string',
        'listing_options' => 'array',
        'mapper' => 'string|object',
        'on_after_create' => 'callable',
        'on_after_delete' => 'callable',
        'on_after_update' => 'callable',
        'on_before_create' => 'callable',
        'on_before_delete' => 'callable',
        'on_before_update' => 'callable',
        'on_form_build' => 'callable',
        'on_init' => 'callable',
        'on_load_form' => 'callable',
        'on_load_mapper' => 'callable',
        'on_response' => 'callable',
        'page' => 'integer',
        'page_name' => 'string',
        'roles' => 'array',
        'route_name' => 'string',
        'route_params' => 'array',
        'route_param_name' => 'string',
        'searchable' => 'string|array',
        'segment_start' => 'integer',
        'segments' => 'string|array',
        'sid_count' => 'integer',
        'state' => 'string',
        'states' => 'array',
        'subtitle' => 'string',
        'title' => 'string',
        'updated_message' => 'string',
        'updated_message_key' => 'string',
        'var_name' => 'string',
        'views' => 'array',
    );

    /**
     * @var ContainerInterface
     */
    protected $container;

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
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TemplateInterface
     */
    protected $template;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $dry = true;

    /**
     * @var array
     */
    protected $data = array(
        'fields' => null,
        'form' => null,
        'keyword' => null,
        'keyword_name' => null,
        'mapper' => null,
        'page' => null,
        'page_name' => null,
        'route_name' => null,
        'route_params' => null,
        'route_param_name' => null,
        'route_segment_prefix' => null,
        'segments' => null,
        'state' => null,
        'subtitle' => null,
        'title' => null,
    );

    /**
     * @var array
     */
    protected $options = array(
        'append_query' => false,
        'create_new' => false,
        'create_new_label' => null,
        'create_new_session_key' => 'crud_create_new',
        'created_message' => 'Data has been created.',
        'created_message_key' => 'alerts_success',
        'deleted_message' => 'Data has been deleted.',
        'deleted_message_key' => 'alerts_warning',
        'field_orders' => null,
        'fields' => array(
            self::STATE_LISTING => null,
            self::STATE_VIEW => null,
            self::STATE_CREATE => null,
            self::STATE_UPDATE => null,
            self::STATE_DELETE => null,
        ),
        'filters' => array(),
        'form' => null,
        'form_options' => array(),
        'keyword' => null,
        'keyword_name' => 'keyword',
        'listing_options' => null,
        'mapper' => null,
        'on_after_create' => null,
        'on_after_delete' => null,
        'on_after_update' => null,
        'on_before_create' => null,
        'on_before_delete' => null,
        'on_before_update' => null,
        'on_form_build' => null,
        'on_init' => null,
        'on_load_form' => null,
        'on_load_mapper' => null,
        'on_response' => null,
        'page' => null,
        'page_name' => 'page',
        'roles' => array(
            self::STATE_LISTING => null,
            self::STATE_VIEW => null,
            self::STATE_CREATE => null,
            self::STATE_UPDATE => null,
            self::STATE_DELETE => null,
        ),
        'route_name' => null,
        'route_params' => null,
        'route_param_name' => null,
        'searchable' => null,
        'segment_start' => 0,
        'segments' => null,
        'sid_count' => 1,
        'state' => null,
        'states' => array(
            self::STATE_LISTING => true,
            self::STATE_VIEW => true,
            self::STATE_CREATE => true,
            self::STATE_UPDATE => true,
            self::STATE_DELETE => true,
        ),
        'subtitle' => null,
        'title' => null,
        'updated_message' => 'Data has been updated.',
        'updated_message_key' => 'alerts_info',
        'var_name' => 'crud',
        'views' => array(
            self::STATE_LISTING => null,
            self::STATE_VIEW => null,
            self::STATE_CREATE => null,
            self::STATE_UPDATE => null,
            self::STATE_DELETE => null,
        ),
    );

    /**
     * @var array
     */
    protected $functions = array();

    /**
     * Class constructor.
     *
     * @param ContainerInterface    $container
     * @param SessionInterface      $session
     * @param RequestStackInterface $requestStack
     * @param UrlGeneratorInterface $urlGenerator
     * @param RouterInterface       $router
     * @param TranslatorInterface   $translator
     * @param TemplateInterface     $template
     * @param Auth                  $auth
     */
    public function __construct(ContainerInterface $container, SessionInterface $session, RequestStackInterface $requestStack, UrlGeneratorInterface $urlGenerator, RouterInterface $router, TranslatorInterface $translator, TemplateInterface $template, Auth $auth)
    {
        $this->container = $container;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
        $this->translator = $translator;
        $this->template = $template;
        $this->auth = $auth;
    }

    /**
     * Returns data member.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Sets/gets option via method call.
     *
     * @param string $option
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($option, $arguments)
    {
        $name = isset($this->options[$option]) ? $option : Util::snakeCase($option);
        $types = static::$optionsTypes[$name] ?? null;

        if (!$types) {
            throw new \LogicException(sprintf('Option "%s" is not available.', $option));
        }

        if (!$arguments) {
            return $this->options[$name];
        }

        $value = $arguments[0];

        if (false !== strpos($types, 'array') && is_array($value)) {
            $this->options[$name] = $this->options[$name] ? array_replace($this->options[$name], $value) : $value;
        } elseif (false !== strpos($types, 'string') && is_scalar($value)) {
            $this->options[$name] = (string) $value;
        } elseif (false !== strpos($types, 'integer') && is_scalar($value)) {
            $this->options[$name] = (int) $value;
        } elseif (false !== strpos($types, 'boolean') && is_bool($value)) {
            $this->options[$name] = $value;
        } elseif (false !== strpos($types, 'object') && is_object($value)) {
            $this->options[$name] = $value;
        } elseif (false !== strpos($types, 'callable') && (is_callable($value) || (is_string($value) && is_callable($tmp = $this->container->grab($value))))) {
            $this->options[$name] = $tmp ?? $value;
        } else {
            throw new \UnexpectedValueException(sprintf('Option "%s" expect %s value, given %s type.', $name, str_replace('|', ' or ', $types), gettype($value)));
        }

        return $this;
    }

    /**
     * Returns options.
     *
     * @return mixed
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Enable state.
     *
     * @param string|array $states
     *
     * @return Crud
     */
    public function enable($states): Crud
    {
        foreach (Util::split($states) as $state) {
            $this->options['states'][$state] = true;
        }

        return $this;
    }

    /**
     * Disable state.
     *
     * @param string|array $states
     *
     * @return Crud
     */
    public function disable($states): Crud
    {
        foreach (Util::split($states) as $state) {
            $this->options['states'][$state] = false;
        }

        return $this;
    }

    /**
     * Sets fields for state.
     *
     * @param string|array $states
     * @param mixed        $fields
     *
     * @return Crud
     */
    public function field($states, $fields): Crud
    {
        foreach (Util::split($states) as $state) {
            $this->options['fields'][$state] = $fields;
        }

        return $this;
    }

    /**
     * Sets view for state.
     *
     * @param string $state
     * @param string $view
     *
     * @return Crud
     */
    public function view(string $state, string $view): Crud
    {
        $this->options['views'][$state] = $view;

        return $this;
    }

    /**
     * Sets roles for state.
     *
     * @param string $state
     * @param string $roles
     *
     * @return Crud
     */
    public function role(string $state, string $roles): Crud
    {
        $this->options['roles'][$state] = $roles;

        return $this;
    }

    /**
     * Returns true if data exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns data value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Sets data value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Crud
     */
    public function set(string $key, $val): Crud
    {
        $this->data[$key] = $val;

        return $this;
    }

    /**
     * Clear data.
     *
     * @param string $key
     *
     * @return Crud
     */
    public function clear(string $key): Crud
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Register function.
     *
     * @param string   $name
     * @param callable $cb
     *
     * @return Crud
     */
    public function addFunction(string $name, callable $cb): Crud
    {
        $this->functions[$name] = $cb;

        return $this;
    }

    /**
     * Call registered functions.
     *
     * @param string     $name
     * @param array|null $arguments
     * @param mixed      $default
     *
     * @return mixed
     */
    public function call(string $name, array $arguments = null, $default = null)
    {
        if (isset($this->functions[$name])) {
            return $this->container->call($this->functions[$name], $arguments);
        }

        return $default;
    }

    /**
     * Returns true if state roles not exists or role is granted.
     *
     * @param string $state
     *
     * @return bool
     */
    public function isGranted(string $state): bool
    {
        $enabled = $this->options['states'][$state] ?? false;
        $roles = $this->options['roles'][$state] ?? null;

        return $enabled && (!$roles || $this->auth->isGranted($roles));
    }

    /**
     * Assign request.
     *
     * @param Request $request
     *
     * @return Crud
     */
    public function handle(Request $request): Crud
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Returns crud link.
     *
     * @param mixed $segments
     * @param mixed $query
     *
     * @return string
     */
    public function path($segments = null, $query = null): string
    {
        list($route, $parameters) = $this->prepareRoute(Util::split($segments ?? array('index'), '/'), $query);

        return $this->urlGenerator->generate($route, $parameters);
    }

    /**
     * Returns crud link (cut n-segments before inserted segments).
     *
     * @param int   $cut
     * @param mixed $segments
     * @param mixed $query
     *
     * @return string
     */
    public function backPath(int $cut = 0, $segments = null, $query = null): string
    {
        list($route, $parameters) = $this->prepareRoute(Util::split($segments ?? array('index'), '/'), $query);

        if (0 > $start = $this->options['segment_start'] - abs($cut)) {
            throw new \LogicException('Running out of segments.');
        }

        $params = &$parameters[$this->data['route_param_name']];
        $params = array_slice($params, 0, $start, true) + array_slice($params, $start + abs($cut), null, true);

        return $this->urlGenerator->generate($route, $parameters);
    }

    /**
     * Returns redirect response.
     *
     * @param mixed $segments
     * @param mixed $query
     *
     * @return Response
     */
    public function redirect($segments = null, $query = null): Response
    {
        return $this->urlGenerator->redirect($this->prepareRoute(Util::split($segments ?? array('index'), '/'), $query));
    }

    /**
     * Do render.
     *
     * @return Response
     */
    public function render(): Response
    {
        if ($this->dry) {
            $this->initialize();
            $this->dry = false;
            $this->trigger('on_init');
        }

        if ($this->isGranted($this->data['state'])) {
            $handle = 'state'.$this->data['state'];

            return $this->$handle();
        }

        return $this->stateForbidden();
    }

    /**
     * Prepare crud.
     */
    protected function initialize(): void
    {
        if (empty($this->options['mapper'])) {
            throw new \LogicException('Mapper is not provided.');
        }

        $route = null;
        $segments = array();
        $name = null;
        $start = $this->options['segment_start'];

        if ($match = $this->router->getRouteMatch()) {
            $arguments = $match->getArguments();

            $route = $match->getAlias();
            $segments = Util::split(end($arguments), '/');
            $name = key($arguments);
        }

        if ($this->options['route_name']) {
            $route = $this->options['route_name'];
        }

        if ($this->options['route_param_name']) {
            $name = $this->options['route_param_name'];
        }

        if ($this->options['segments']) {
            $segments = Util::split($this->options['segments'], '/');
        }

        if (empty($route)) {
            throw new \LogicException('Route is not defined.');
        }

        if (empty($name)) {
            throw new \LogicException('Route parameter name is not provided.');
        }

        if (empty($segments) || !isset($segments[$start]) || 'index' === $segments[$start]) {
            $state = static::STATE_LISTING;
        } else {
            $state = $segments[$start];
        }

        if (!$this->request) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        $this->data['mapper'] = $this->initializeMapper();
        $this->data['form'] = $this->initializeForm();
        $this->data['state'] = $state;
        $this->data['route_name'] = $route;
        $this->data['route_param_name'] = $name;
        $this->data['route_params'] = $this->options['route_params'];
        $this->data['segments'] = $segments;
        $this->data['route_segment_prefix'] = array_slice($segments, 0, $start);
        $this->data['keyword'] = $this->options['keyword'] ?? $this->request->query->get($this->options['keyword_name']);
        $this->data['keyword_name'] = $this->options['keyword_name'];
        $this->data['page'] = (int) ($this->options['page'] ?? $this->request->query->get($this->options['page_name']) ?? 1);
        $this->data['page_name'] = $this->options['page_name'];
        $this->data['title'] = $this->options['title'];
        $this->data['subtitle'] = $this->options['subtitle'];
        $this->data['fields'] = array();

        if (empty($this->data['title'])) {
            $this->data['title'] = 'Manage '.Util::titleCase($this->data['mapper']->getName());
        }

        if (empty($this->data['subtitle'])) {
            $this->data['subtitle'] = Util::titleCase($state);
        }

        // fix fields
        $fields = $this->options['fields'][$state] ?? array_fill_keys(array_keys($this->data['mapper']->getSchema()->getFields()), null);

        if (is_string($fields)) {
            $fields = array_fill_keys(Util::split($fields), null);
        }

        $orders = Util::split($this->options['field_orders']);
        $keys = array_unique(array_merge($orders, array_keys($fields)));

        foreach ($keys as $key) {
            $field = $fields[$key] ?? null;

            if (!isset($field['name'])) {
                $field['name'] = $key;
            }

            if (!isset($field['label'])) {
                $field['label'] = $this->translator->transAdv($key) ?? Util::titleCase($key);
            }

            $this->data['fields'][$key] = $field;
        }
    }

    /**
     * Load mapper.
     */
    protected function initializeMapper(): Mapper
    {
        $map = $this->options['mapper'];

        if ($map instanceof Mapper) {
            return $map;
        }

        if (class_exists($map)) {
            return $this->container->get($map);
        }

        return $this->container->get('Fal\\Stick\\Database\\Mapper', array('name' => $map));
    }

    /**
     * Load form.
     */
    protected function initializeForm(): Form
    {
        $form = $this->options['form'];

        if ($form instanceof Form) {
            return $form;
        }

        if ($form && class_exists($form)) {
            return $this->container->get($form);
        }

        $form = $this->container->get('Fal\\Stick\\Web\\Form\\Form');
        $this->trigger('on_form_build', array($form));

        return $form;
    }

    /**
     * Create response for state.
     *
     * @param string $state
     *
     * @return Response
     */
    protected function createResponse(string $state): Response
    {
        $view = $this->options['views'][$state] ?? null;

        if (empty($view)) {
            throw new \LogicException(sprintf('No view for state: "%s".', $state));
        }

        $content = $this->template->render($view, array($this->options['var_name'] => $this));

        if ($this->options['on_response']) {
            $response = $this->trigger('on_response', array($content));

            if (!$response instanceof Response) {
                throw new \LogicException('Response should be instance of Fal\\Stick\\Web\\Response.');
            }
        } else {
            $response = Response::create($content);
        }

        return $response;
    }

    /**
     * Do go back and set message.
     *
     * @param string $key
     *
     * @return Response
     */
    protected function goBack(string $key): Response
    {
        $messageKey = $key.'_message';
        $sessionKey = $messageKey.'_key';
        $createNew = 'create' === $this->data['state'] && $this->options['create_new'] && $this->data['form']->hasField('create_new') && $this->data['form']['create_new'];

        $this->session->set($this->options['create_new_session_key'], $createNew);

        if (isset($this->options[$messageKey])) {
            $this->session->set($this->options[$sessionKey], strtr($this->options[$messageKey], array(
                '%name%' => $this->data['mapper']->getName(),
                '%id%' => implode(', ', $this->data['mapper']->getSchema()->getKeys()),
            )));
        }

        return $this->redirect($createNew ? 'create' : 'index');
    }

    /**
     * Returns route arguments.
     *
     * @param array $segments
     * @param mixed $query
     *
     * @return array
     */
    protected function prepareRoute(array $segments, $query = null): array
    {
        if ($this->dry) {
            throw new \LogicException('Please call render first!');
        }

        $parameters = $this->data['route_params'];
        $parameters[$this->data['route_param_name']] = array_merge($this->data['route_segment_prefix'], $segments);

        $parameters += array_filter(array(
            $this->data['page_name'] => $this->data['page'],
            $this->data['keyword_name'] => $this->data['keyword'],
        ), 'is_scalar');

        if ($query) {
            if (is_string($query)) {
                parse_str($query, $query);
            }

            $parameters += $query;
        }

        $parameters += $this->options['append_query'] ? $this->request->query->all() : array();

        return array($this->data['route_name'], $parameters);
    }

    /**
     * Prepare listing filters.
     *
     * @return array
     */
    protected function prepareFilters(): array
    {
        $keyword = $this->data['keyword'];
        $filters = $this->options['filters'];
        $search = &$filters[];

        foreach ($keyword ? Util::split($this->options['searchable'], ',') : array() as $field) {
            $search[$field] = '~' === substr($field, -1) ? '%'.$keyword.'%' : $keyword;
        }

        unset($search);

        return $filters;
    }

    /**
     * Prepare item filters.
     *
     * @return array
     */
    protected function prepareItemFilters(): array
    {
        $ids = array_slice($this->data['segments'], $this->options['segment_start'] + 1, $this->options['sid_count']);
        $keys = $this->data['mapper']->getSchema()->getKeys();

        if (count($ids) !== count($keys)) {
            throw new \LogicException('Insufficient primary keys!');
        }

        return $this->options['filters'] + array_combine($keys, $ids);
    }

    /**
     * Trigger internal event.
     *
     * @param string $eventName
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function trigger(string $eventName, array $arguments = array())
    {
        if (isset($this->options[$eventName])) {
            array_unshift($arguments, $this);

            return $this->container->call($this->options[$eventName], $arguments);
        }

        return null;
    }

    /**
     * Prepare form.
     */
    protected function loadForm(): void
    {
        $data = $this->data['mapper']->getSchema()->toArray();

        if (is_array($update = $this->trigger('on_load_form', array($data)))) {
            $data = $update;
        }

        $this->data['form']->handle($this->request, $data, $this->options['form_options']);

        if ('create' === $this->data['state'] && $this->options['create_new'] && !$this->data['form']->hasField('create_new')) {
            $this->data['form']->addField('create_new', 'checkbox', array(
                'label' => $this->options['create_new_label'],
                'attr' => array('checked' => $this->session->get($this->options['create_new_session_key']) ?? true),
            ));
        }
    }

    /**
     * Load mapper.
     */
    protected function loadMapper(): void
    {
        $this->data['mapper']->load($this->prepareItemFilters());
        $this->trigger('on_load_mapper', array($this->data['mapper']));

        if (!$this->data['mapper']->valid()) {
            throw new NotFoundException();
        }
    }

    /**
     * Perform state listing.
     *
     * @return Response
     */
    protected function stateListing(): Response
    {
        $this->data['data'] = $this->data['mapper']->paginate($this->data['page'], $this->prepareFilters(), $this->options['listing_options']);

        return $this->createResponse(static::STATE_LISTING);
    }

    /**
     * Perform state view.
     *
     * @return Response
     */
    protected function stateView(): Response
    {
        $this->loadMapper();

        return $this->createResponse(static::STATE_VIEW);
    }

    /**
     * Perform state create.
     *
     * @return Response
     */
    protected function stateCreate(): Response
    {
        $this->loadForm();

        if ($this->data['form']->isSubmitted() && $this->data['form']->valid()) {
            $data = (array) $this->trigger('on_before_create');
            $this->data['mapper']->getSchema()->fromArray($data + $this->data['form']->getValidatedData());
            $result = $this->data['mapper']->save();
            $response = $this->trigger('on_after_create', array($result));

            return $response instanceof Response ? $response : $this->goBack('created');
        }

        return $this->createResponse(static::STATE_CREATE);
    }

    /**
     * Perform state update.
     *
     * @return Response
     */
    protected function stateUpdate(): Response
    {
        $this->loadMapper();
        $this->loadForm();

        if ($this->data['form']->isSubmitted() && $this->data['form']->valid()) {
            $data = (array) $this->trigger('on_before_update');
            $this->data['mapper']->getSchema()->fromArray($data + $this->data['form']->getValidatedData());
            $result = $this->data['mapper']->save();
            $response = $this->trigger('on_after_update', array($result));

            return $response instanceof Response ? $response : $this->goBack('updated');
        }

        return $this->createResponse(static::STATE_UPDATE);
    }

    /**
     * Perform state delete.
     *
     * @return Response
     */
    protected function stateDelete(): Response
    {
        $this->loadMapper();

        if ($this->request->isMethod('POST')) {
            $this->trigger('on_before_delete');
            $result = $this->data['mapper']->delete();
            $response = $this->trigger('on_after_delete', array($result));

            return $response instanceof Response ? $response : $this->goBack('deleted');
        }

        return $this->createResponse(static::STATE_DELETE);
    }

    /**
     * Render forbidden view.
     *
     * @return Response
     */
    protected function stateForbidden(): Response
    {
        return $this->createResponse(static::STATE_FORBIDDEN);
    }
}
