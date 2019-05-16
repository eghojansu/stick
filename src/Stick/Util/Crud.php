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

namespace Fal\Stick\Util;

use Fal\Stick\Fw;
use Fal\Stick\Security\Auth;
use Fal\Stick\Template\Environment;

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
     * @var Option
     */
    public $options;

    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var Environment
     */
    public $env;

    /**
     * @var Auth
     */
    public $auth;

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
    protected $functions = array();

    /**
     * Class constructor.
     *
     * @param Fw          $fw
     * @param Environment $env
     * @param Auth        $auth
     */
    public function __construct(Fw $fw, Environment $env, Auth $auth)
    {
        $this->fw = $fw;
        $this->env = $env;
        $this->auth = $auth;
        $this->options = (new Option())
            ->add('append_query', false)
            ->add('create_new', false)
            ->add('create_new_label', null, 'string')
            ->add('create_new_session_key', 'SESSION.crud_create_new')
            ->add('created_message', 'Data has been created.')
            ->add('created_message_key', 'SESSION.alerts.success')
            ->add('deleted_message', 'Data has been deleted.')
            ->add('deleted_message_key', 'SESSION.alerts.warning')
            ->add('field_orders', null, 'string|array')
            ->add('fields', array(
                self::STATE_LISTING => null,
                self::STATE_VIEW => null,
                self::STATE_CREATE => null,
                self::STATE_UPDATE => null,
                self::STATE_DELETE => null,
            ))
            ->add('filters', array())
            ->add('form', null, 'Fal\\Stick\\Form\\Form')
            ->add('form_options', array())
            ->add('ignores', null, 'string|array')
            ->add('keyword', null, 'string')
            ->add('keyword_name', 'keyword')
            ->add('listing_options', null, 'array')
            ->add('mapper', null, 'Fal\\Stick\\Db\\Pdo\\Mapper')
            ->add('on_after_create', null, 'callable|string')
            ->add('on_after_delete', null, 'callable|string')
            ->add('on_after_update', null, 'callable|string')
            ->add('on_before_create', null, 'callable|string')
            ->add('on_before_delete', null, 'callable|string')
            ->add('on_before_update', null, 'callable|string')
            ->add('on_init', null, 'callable|string')
            ->add('on_load_form', null, 'callable|string')
            ->add('on_load_mapper', null, 'callable|string')
            ->add('on_response', null, 'callable|string')
            ->add('page', null, 'integer')
            ->add('page_name', 'page')
            ->add('roles', array(
                self::STATE_LISTING => null,
                self::STATE_VIEW => null,
                self::STATE_CREATE => null,
                self::STATE_UPDATE => null,
                self::STATE_DELETE => null,
            ))
            ->add('route_name', null, 'string')
            ->add('route_params', null, 'array')
            ->add('route_param_name', null, 'string')
            ->add('searchable', null, 'string|array')
            ->add('segment_start', 0)
            ->add('segments', null, 'string|array')
            ->add('sid_count', 1)
            ->add('state', null, 'string')
            ->add('states', array(
                self::STATE_LISTING => true,
                self::STATE_VIEW => true,
                self::STATE_CREATE => true,
                self::STATE_UPDATE => true,
                self::STATE_DELETE => true,
            ))
            ->add('subtitle', null, 'string')
            ->add('title', null, 'string')
            ->add('updated_message', 'Data has been updated.')
            ->add('updated_message_key', 'SESSION.alerts.info')
            ->add('var_name', 'crud')
            ->add('views', array(
                self::STATE_LISTING => null,
                self::STATE_VIEW => null,
                self::STATE_CREATE => null,
                self::STATE_UPDATE => null,
                self::STATE_DELETE => null,
            ));
    }

    /**
     * Returns data member.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function &__get($key)
    {
        $ref = &$this->get($key);

        return $ref;
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
        $name = $this->options->has($option) ? $option : $this->fw->snakeCase($option);

        if (!$arguments) {
            return $this->options->get($name);
        }

        $this->options->set($name, $arguments[0]);

        return $this;
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
        foreach ($this->fw->split($states) as $state) {
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
        foreach ($this->fw->split($states) as $state) {
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
        foreach ($this->fw->split($states) as $state) {
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
    public function has(string $key): bool
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
    public function &get(string $key)
    {
        if (!$this->has($key)) {
            $this->data[$key] = null;
        }

        return $this->data[$key];
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
    public function rem(string $key): Crud
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
            if (null === $arguments) {
                $arguments = array();
            }

            return $this->fw->call($this->functions[$name], ...$arguments);
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
     * Returns crud link.
     *
     * @param mixed $segments
     * @param mixed $query
     *
     * @return string
     */
    public function path($segments = null, $query = null): string
    {
        list($route, $parameters) = $this->prepareRoute($this->fw->split($segments ?? array('index'), '/'), $query);

        return $this->fw->path($route, $parameters);
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
        list($route, $parameters) = $this->prepareRoute($this->fw->split($segments ?? array('index'), '/'), $query);

        if (0 > $start = $this->options['segment_start'] - abs($cut)) {
            throw new \LogicException('Running out of segments.');
        }

        $params = &$parameters[$this->data['route_param_name']];
        $params = array_slice($params, 0, $start, true) + array_slice($params, $start + abs($cut), null, true);

        return $this->fw->path($route, $parameters);
    }

    /**
     * Returns redirect response.
     *
     * @param mixed $segments
     * @param mixed $query
     */
    public function redirect($segments = null, $query = null): void
    {
        $this->fw->reroute($this->prepareRoute($this->fw->split($segments ?? array('index'), '/'), $query));
    }

    /**
     * Do render.
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->dry) {
            $this->initialize();
            $this->dry = false;
            $this->dispatch('init');
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
        if (!$this->options['mapper']) {
            throw new \LogicException('Mapper is not provided.');
        }

        if (!$this->options['form']) {
            throw new \LogicException('Form is not provided.');
        }

        $name = $this->options->get('route_param_name');
        $start = $this->options['segment_start'];
        $segments = $this->fw->split($this->options->get('segments'), '/');
        $route = $this->options->get('route_name') ?? $this->fw->get('ALIAS');

        if (!$segments && $arguments = $this->fw->get('PARAMS')) {
            $segments = $this->fw->split(end($arguments), '/');

            if (!$name) {
                $name = key($arguments);
            }
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

        $this->data['mapper'] = $this->options['mapper'];
        $this->data['form'] = $this->options['form'];
        $this->data['state'] = $state;
        $this->data['route_name'] = $route;
        $this->data['route_param_name'] = $name;
        $this->data['route_params'] = $this->options['route_params'];
        $this->data['segments'] = $segments;
        $this->data['route_segment_prefix'] = array_slice($segments, 0, $start);
        $this->data['keyword'] = $this->options['keyword'] ?? $this->fw->get('GET.'.$this->options['keyword_name']);
        $this->data['keyword_name'] = $this->options['keyword_name'];
        $this->data['page'] = intval($this->options['page'] ?? $this->fw->get('GET.'.$this->options['page_name']) ?? 1);
        $this->data['page_name'] = $this->options['page_name'];
        $this->data['title'] = $this->options['title'] ?? 'Manage '.$this->fw->titleCase($this->data['mapper']->table());
        $this->data['subtitle'] = $this->options['subtitle'] ?? $this->fw->titleCase($state);
        $this->data['fields'] = array();

        // fix fields
        $fields = $this->options['fields'][$state] ?? array_fill_keys($this->data['mapper']->schema->getFields(), null);

        if (is_string($fields)) {
            $fields = array_fill_keys($this->fw->split($fields), null);
        }

        $orders = $this->fw->split($this->options['field_orders']);
        $keys = array_unique(array_merge($orders, array_keys($fields)));

        foreach ($keys as $key) {
            $field = $fields[$key] ?? null;

            if (!isset($field['name'])) {
                $field['name'] = $key;
            }

            if (!isset($field['label'])) {
                $field['label'] = $this->fw->trans($key, null, true);
            }

            if (!isset($field['type']) && $this->data['mapper']->schema->has($key)) {
                $field['type'] = $this->data['mapper']->schema[$key]['type'];
            }

            $this->data['fields'][$key] = $field;
        }
    }

    /**
     * Create response for state.
     *
     * @param string $state
     *
     * @return string
     */
    protected function createResponse(string $state): string
    {
        $view = $this->options['views'][$state] ?? null;

        if (empty($view)) {
            throw new \LogicException(sprintf('No view for state: %s.', $state));
        }

        return $this->env->render($view, array($this->options['var_name'] => $this));
    }

    /**
     * Do go back and set message.
     *
     * @param string $key
     */
    protected function goBack(string $key): void
    {
        $messageKey = $key.'_message';
        $sessionKey = $messageKey.'_key';
        $createNew = 'create' === $this->data['state'] && $this->options['create_new'] && $this->data['form']->has('create_new') && $this->data['form']['create_new'];

        $this->fw->set($this->options['create_new_session_key'], $createNew);

        if (isset($this->options[$messageKey])) {
            $this->fw->set($this->options[$sessionKey], strtr($this->options[$messageKey], array(
                '%name%' => $this->data['mapper']->table(),
                '%id%' => implode(', ', $this->data['mapper']->schema->getKeys()),
            )));
        }

        $this->redirect($createNew ? 'create' : 'index');
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

        $parameters += $this->options['append_query'] ? ($this->fw->get('GET') ?? array()) : array();

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

        foreach ($keyword ? $this->fw->split($this->options['searchable'], ',') : array() as $field) {
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
        $keys = $this->data['mapper']->schema->getKeys();

        if (count($ids) !== count($keys)) {
            throw new \LogicException('Insufficient primary keys!');
        }

        return $this->options['filters'] + array_combine($keys, $ids);
    }

    /**
     * Trigger internal event.
     *
     * @param string     $eventName
     * @param array|null $arguments
     *
     * @return mixed
     */
    protected function dispatch(string $eventName, array $arguments = null)
    {
        if ($handler = $this->options->get('on_'.$eventName)) {
            return $this->fw->call($handler, $this, ...($arguments ?? array()));
        }

        return null;
    }

    /**
     * Prepare form.
     */
    protected function loadForm(): void
    {
        $data = $this->data['mapper']->toArray();

        if (is_array($update = $this->dispatch('load_form', array($data)))) {
            $data = $update;
        }

        $this->data['form']
            ->setData($data)
            ->setOptions($this->options['form_options'])
            ->setIgnores($this->options['ignores'])
            ->isSubmitted();

        if ('create' === $this->data['state'] && $this->options['create_new'] && !$this->data['form']->has('create_new')) {
            $this->data['form']->set('create_new', 'checkbox', array(
                'label' => $this->options['create_new_label'],
                'attr' => array('checked' => $this->fw->get($this->options['create_new_session_key']) ?? true),
            ));
        }
    }

    /**
     * Load mapper.
     *
     * @return bool
     */
    protected function loadMapper(): bool
    {
        $this->data['mapper']->findOne($this->prepareItemFilters());
        $this->dispatch('load_mapper', array($this->data['mapper']));

        if ($this->data['mapper']->dry()) {
            $this->fw->error(404);

            return false;
        }

        return true;
    }

    /**
     * Perform state listing.
     *
     * @return string
     */
    protected function stateListing(): string
    {
        $this->data['data'] = $this->data['mapper']->paginate($this->data['page'], $this->prepareFilters(), $this->options['listing_options']);

        return $this->createResponse(static::STATE_LISTING);
    }

    /**
     * Perform state view.
     *
     * @return string
     */
    protected function stateView(): string
    {
        return $this->loadMapper() ? $this->createResponse(static::STATE_VIEW) : '';
    }

    /**
     * Perform state create.
     *
     * @return string
     */
    protected function stateCreate(): string
    {
        $this->loadForm();

        if ($this->data['form']->isSubmitted() && $this->data['form']->valid()) {
            $data = (array) $this->dispatch('before_create');
            $this->data['mapper']->fromArray($data + $this->data['form']->getValidatedData());
            $result = $this->data['mapper']->save();
            $this->dispatch('after_create', array($result));
            $this->goBack('created');

            return '';
        }

        return $this->createResponse(static::STATE_CREATE);
    }

    /**
     * Perform state update.
     *
     * @return string
     */
    protected function stateUpdate(): string
    {
        if (!$this->loadMapper()) {
            return '';
        }

        $this->loadForm();

        if ($this->data['form']->isSubmitted() && $this->data['form']->valid()) {
            $data = (array) $this->dispatch('before_update');
            $this->data['mapper']->fromArray($data + $this->data['form']->getValidatedData());
            $result = $this->data['mapper']->save();
            $this->dispatch('after_update', array($result));
            $this->goBack('updated');

            return '';
        }

        return $this->createResponse(static::STATE_UPDATE);
    }

    /**
     * Perform state delete.
     *
     * @return string
     */
    protected function stateDelete(): string
    {
        if (!$this->loadMapper()) {
            return '';
        }

        if ('POST' === $this->fw->get('VERB')) {
            $this->dispatch('before_delete');
            $result = $this->data['mapper']->delete();
            $this->dispatch('after_delete', array($result));
            $this->goBack('deleted');

            return '';
        }

        return $this->createResponse(static::STATE_DELETE);
    }

    /**
     * Render forbidden view.
     *
     * @return string
     */
    protected function stateForbidden(): string
    {
        return $this->createResponse(static::STATE_FORBIDDEN);
    }
}
