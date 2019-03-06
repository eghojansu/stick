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
        'state' => null,
        'route' => null,
        'page' => null,
        'keyword' => null,
        'fields' => array(),
        'segments' => array(),
        'segment_start' => null,
        'segments_prefix' => null,
        'form' => null,
        'mapper' => null,
    );

    /**
     * @var array
     */
    protected $options = array(
        'title' => null,
        'subtitle' => null,
        'form' => null,
        'form_options' => null,
        'on_form_build' => null,
        'field_orders' => null,
        'field_labels' => null,
        'mapper' => null,
        'state' => null,
        'filters' => array(),
        'listing_options' => null,
        'searchable' => null,
        'segments' => null,
        'segment_start' => 0,
        'sid_count' => 1,
        'page' => null,
        'page_query_name' => 'page',
        'append_query' => false,
        'keyword' => null,
        'keyword_query_name' => 'keyword',
        'route' => null,
        'route_args' => null,
        'route_param_name' => null,
        'created_message' => 'Data has been created.',
        'updated_message' => 'Data has been updated.',
        'deleted_message' => 'Data has been deleted.',
        'created_message_key' => 'alerts_success',
        'updated_message_key' => 'alerts_info',
        'deleted_message_key' => 'alerts_warning',
        'varname' => 'crud',
        'on_init' => null,
        'on_prepare_data' => null,
        'on_load' => null,
        'on_response' => null,
        'on_before_create' => null,
        'on_after_create' => null,
        'on_before_update' => null,
        'on_after_update' => null,
        'on_before_delete' => null,
        'on_after_delete' => null,
        'states' => null,
        'views' => null,
        'fields' => null,
        'roles' => null,
        'create_new' => false,
        'create_new_label' => null,
        'create_new_session_key' => 'crud_create_new',
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
        $states = array(
            static::STATE_LISTING,
            static::STATE_VIEW,
            static::STATE_CREATE,
            static::STATE_UPDATE,
            static::STATE_DELETE,
        );

        $this->container = $container;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
        $this->translator = $translator;
        $this->template = $template;
        $this->auth = $auth;

        $this->options['states'] = array_fill_keys($states, true);
        $this->options['views'] = $this->options['fields'] = $this->options['roles'] = array_fill_keys($states, null);
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
     * Setting option via method call.
     *
     * @param string $option
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($option, $args)
    {
        if ($args) {
            $name = Util::snakeCase($option);

            if (array_key_exists($name, $this->options)) {
                $value = $args[0];

                if (is_array($this->options[$name])) {
                    if (!is_array($value)) {
                        throw new \UnexpectedValueException(sprintf('Option "%s" expect array value.', $name));
                    }

                    $this->options[$name] = array_replace($this->options[$name], $value);
                } else {
                    $this->options[$name] = $value;
                }
            }
        }

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
     * @param array|null $args
     * @param mixed      $default
     *
     * @return mixed
     */
    public function call(string $name, array $args = null, $default = null)
    {
        $cb = $this->functions[$name] ?? null;

        return $cb ? $this->container->call($cb, $args) : $default;
    }

    /**
     * Returns option value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function option(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function options(): array
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
     * Massive role sets.
     *
     * @param array $roles
     *
     * @return Crud
     */
    public function roles(array $roles): Crud
    {
        foreach ($roles as $state => $role) {
            $this->role($state, $role);
        }

        return $this;
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
        if ($this->dry) {
            throw new \LogicException('Please call render first!');
        }

        return $this->urlGenerator->generate(...$this->prepareRoute(Util::split($segments ?? array('index'), '/'), $query));
    }

    /**
     * Do render.
     *
     * @return Response
     */
    public function render(): Response
    {
        if ($this->dry) {
            $this->init();
            $this->dry = false;
        }

        if ($this->isGranted($this->data['state'])) {
            $handle = $this->prepareState();

            return $this->$handle();
        }

        return $this->stateForbidden();
    }

    /**
     * Prepare crud.
     */
    protected function init(): void
    {
        if (empty($this->options['mapper'])) {
            throw new \LogicException('Mapper is not provided.');
        }

        $match = $this->router->getRouteMatch();
        $route = $this->options['route'];
        $start = $this->options['segment_start'];

        if (empty($route) && $match) {
            $route = $match->getAlias();
        }

        if (empty($route)) {
            throw new \LogicException('Route is not defined.');
        }

        if (isset($this->options['segments'])) {
            $segments = Util::split($this->options['segments'], '/');
        } elseif ($match) {
            $arguments = $match->getArguments();
            $segments = end($arguments);
        } else {
            $segments = array();
        }

        // invalid segments
        if (!is_array($segments)) {
            throw new \LogicException('Segments is not provided.');
        }

        if (empty($segments) || !isset($segments[$start]) || 'index' === $segments[$start]) {
            $state = static::STATE_LISTING;
        } else {
            $state = $segments[$start];
        }

        // parameter name
        if (empty($this->options['route_param_name']) && $match) {
            $arguments = $match->getArguments();
            end($arguments);

            $this->options['route_param_name'] = key($arguments);
        }

        if (empty($this->options['route_param_name'])) {
            throw new \LogicException('Route parameter name is not provided.');
        }

        if (!$this->request) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        $this->data['mapper'] = $this->prepareMapper();
        $this->data['form'] = $this->prepareForm();
        $this->data['state'] = $state;
        $this->data['route'] = $route;
        $this->data['segments'] = $segments;
        $this->data['segment_start'] = $start;
        $this->data['segments_prefix'] = array_slice($segments, 0, $start);
        $this->data['keyword'] = $this->options['keyword'] ?? $this->request->query->get($this->options['keyword_query_name']);
        $this->data['page'] = (int) ($this->options['page'] ?? $this->request->query->get($this->options['page_query_name']) ?? 1);
        $this->data['searchable'] = $this->options['searchable'];
        $this->data['route_args'] = $this->options['route_args'];
        $this->data['page_query_name'] = $this->options['page_query_name'];
        $this->data['keyword_query_name'] = $this->options['keyword_query_name'];
        $this->data['title'] = $this->options['title'];
        $this->data['subtitle'] = $this->options['subtitle'];

        if (empty($this->data['title'])) {
            $this->data['title'] = 'Manage '.Util::titleCase($this->data['mapper']->getName());
        }

        if (empty($this->data['subtitle'])) {
            $this->data['subtitle'] = Util::titleCase($state);
        }
    }

    /**
     * Prepare fields, ensure field has label and name member.
     *
     * @return string
     */
    protected function prepareState(): string
    {
        $state = $this->data['state'];
        $fields = $this->options['fields'][$state] ?? array_fill_keys(array_keys($this->data['mapper']->getSchema()->getFields()), null);
        $orders = Util::split($this->options['field_orders']);

        if (is_string($fields)) {
            $fields = array_fill_keys(Util::split($fields), null);
        }

        $keys = array_unique(array_merge($orders, array_keys($fields)));
        $this->data['fields'] = array_fill_keys($keys, array());

        foreach ($this->data['fields'] as $name => &$field) {
            if (!isset($field['name'])) {
                $field['name'] = $name;
            }

            if (!isset($field['label'])) {
                $field['label'] = $this->translator->transAdv($name) ?? Util::titleCase($name);
            }

            unset($field);
        }

        $this->trigger('on_init');

        return 'state'.$state;
    }

    /**
     * Load mapper.
     */
    protected function prepareMapper(): Mapper
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
    protected function prepareForm(): Form
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
     * Returns route arguments.
     *
     * @param  array  $segments
     * @param  mixed $query
     *
     * @return array
     */
    protected function prepareRoute(array $segments, $query = null): array
    {
        $parameters = $this->options['route_args'];
        $parameters[$this->options['route_param_name']] = array_merge($this->data['segments_prefix'], $segments);

        $parameters += array_filter(array(
            $this->options['page_query_name'] => $this->data['page'],
            $this->options['keyword_query_name'] => $this->data['keyword'],
        ), 'is_scalar');

        if ($query) {
            if (is_string($query)) {
                parse_str($query, $query);
            }

            $parameters += $query;
        }

        $parameters += $this->options['append_query'] ? $this->request->query->all() : array();

        return array($this->data['route'], $parameters);
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

        $content = $this->template->render($view, array($this->options['varname'] => $this));

        if (isset($this->options['on_response'])) {
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
     * @param string $messageKey
     *
     * @return Response
     */
    protected function goBack(string $key, string $messageKey): Response
    {
        $createNew = $this->options['create_new'] && $this->data['form']->hasField('create_new') && 'create' === $this->data['state'] && $this->data['form']['create_new'];

        if ($createNew) {
            $target = $this->request->getUri();
        } else {
            $target = $this->prepareRoute(array('index'));
        }

        if (isset($this->options[$mkey = $messageKey.'_message'])) {
            $this->session->set($this->options[$key], strtr($this->options[$mkey], array(
                '%name%' => $this->data['mapper']->getName(),
                '%id%' => implode(', ', $this->data['mapper']->getSchema()->getKeys()),
            )));
        }

        $this->session->set($this->options['create_new_session_key'], $createNew);

        return $this->urlGenerator->redirect($target);
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
     * @param string     $eventName
     * @param array|null $args
     *
     * @return mixed
     */
    protected function trigger(string $eventName, array $args = null)
    {
        $cb = $this->options[$eventName] ?? null;

        return is_callable($cb) ? $this->container->call($cb, $args) : null;
    }

    /**
     * Prepare form.
     */
    protected function loadForm(): void
    {
        $data = $this->data['mapper']->getSchema()->toArray();

        if (is_array($update = $this->trigger('on_prepare_data', array($data)))) {
            $data = $update;
        }

        if (is_callable($options = $this->options['form_options'])) {
            $options = $this->container->call($options);
        }

        $this->data['form']->handle($this->request, $data, (array) $options);

        if ($this->options['create_new'] && 'create' === $this->data['state'] && !$this->data['form']->hasField('create_new')) {
            $this->data['form']->addField('create_new', 'checkbox', array(
                'label' => $this->options['create_new_label'],
                'attr' => array(
                    'checked' => $this->session->get($this->options['create_new_session_key']) ?? true,
                ),
            ));
        }
    }

    /**
     * Load mapper.
     */
    protected function loadMapper(): void
    {
        $this->trigger('on_load', array($this->data['mapper']->load($this->prepareItemFilters())));

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
            $this->data['mapper']->save();
            $this->trigger('on_after_create');

            return $this->goBack('created_message_key', 'created');
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
            $this->data['mapper']->save();
            $this->trigger('on_after_update');

            return $this->goBack('updated_message_key', 'updated');
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
            $this->data['mapper']->delete();
            $this->trigger('on_after_delete');

            return $this->goBack('deleted_message_key', 'deleted');
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
