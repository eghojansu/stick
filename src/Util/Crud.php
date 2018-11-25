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
use Fal\Stick\Html\Form;
use Fal\Stick\HttpException;
use Fal\Stick\Security\Auth;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Template\Template;

/**
 * Logic for handling CRUD.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Crud implements \ArrayAccess
{
    const STATE_LISTING = 'listing';
    const STATE_VIEW = 'view';
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';
    const STATE_DELETE = 'delete';
    const STATE_FORBIDDEN = 'forbidden';

    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var Auth
     */
    protected $auth;

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
        'sid_start' => 1,
        'sid_end' => 1,
        'page' => null,
        'page_query_name' => 'page',
        'keyword' => null,
        'keyword_query_name' => 'keyword',
        'route' => null,
        'route_args' => null,
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
        'before_create' => null,
        'after_create' => null,
        'before_update' => null,
        'after_update' => null,
        'before_delete' => null,
        'after_delete' => null,
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
    protected $funcs = array();

    /**
     * Class constructor.
     *
     * @param Fw       $app
     * @param Template $template
     */
    public function __construct(Fw $fw, Template $template)
    {
        $states = array(
            static::STATE_LISTING,
            static::STATE_VIEW,
            static::STATE_CREATE,
            static::STATE_UPDATE,
            static::STATE_DELETE,
        );
        $nullStates = array_fill_keys($states, null);

        $this->fw = $fw;
        $this->template = $template;

        $this->options['states'] = array_fill_keys($states, true);
        $this->options['views'] = $nullStates;
        $this->options['fields'] = $nullStates;
        $this->options['roles'] = $nullStates;
    }

    /**
     * Returns true if data exists.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function exists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns data value.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function &get($key)
    {
        if (!$this->exists($key)) {
            $this->data[$key] = null;
        }

        return $this->data[$key];
    }

    /**
     * Sets data value.
     *
     * @param mixed $key
     * @param mixed $val
     *
     * @return Crud
     */
    public function set($key, $val): Crud
    {
        $this->data[$key] = $val;

        return $this;
    }

    /**
     * Clear data.
     *
     * @param mixed $key
     *
     * @return Crud
     */
    public function clear($key): Crud
    {
        unset($this->data[$key]);

        return $this;
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
     * Get auth instance.
     *
     * @return Auth
     *
     * @throws LogicException if auth instance not registered yet
     */
    public function getAuth(): Auth
    {
        if (!$this->auth) {
            throw new \LogicException('Fal\\Stick\\Security\\Auth is not registered.');
        }

        return $this->auth;
    }

    /**
     * Sets auth instance.
     *
     * @param Auth $auth
     *
     * @return Crud
     */
    public function setAuth(Auth $auth): Crud
    {
        $this->auth = $auth;

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

        return $cb ? $this->fw->call($cb, $args) : $default;
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
        $this->getAuth();
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
     * Returns data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Returns crud link.
     *
     * @param mixed $path
     * @param mixed $query
     *
     * @return string
     */
    public function path($path = 'index', $query = null): string
    {
        if (empty($this->data['route'])) {
            throw new \LogicException('No route defined.');
        }

        $paths = is_string($path) ? explode('/', $path) : $path;
        $args = array_merge((array) $this->options['route_args'], $paths);

        return $this->fw->path($this->data['route'], $args, $query);
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
        $roles = $this->fw->split($this->options['roles'][$state] ?? null);

        return $enabled && (!$roles || $this->getAuth()->isGranted(...$roles));
    }

    /**
     * Do render.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->init();

        $state = $this->data['state'];
        $enabled = $this->options['states'][$state] ?? false;
        $roles = $this->options['roles'][$state] ?? null;
        $var = $this->options['varname'];

        if ($enabled && (!$roles || $this->getAuth()->isGranted($roles))) {
            $handle = 'state'.$state;
            $view = $this->options['views'][$state] ?? null;

            $this->prepareFields();
            $this->trigger('on_init');

            $out = $this->$handle();
        } else {
            $view = $this->options['views'][static::STATE_FORBIDDEN] ?? null;
            $out = true;
        }

        if (empty($view)) {
            throw new \LogicException(sprintf('No view for state: "%s".', $state));
        }

        return $out ? $this->template->render($view, array($var => $this)) : null;
    }

    /**
     * Prepare crud.
     */
    protected function init(): void
    {
        $route = $this->options['route'] ?? $this->fw['ALIAS'];

        if (empty($route)) {
            throw new \LogicException('No route defined.');
        }

        if (empty($this->options['mapper'])) {
            throw new \LogicException('No mapper provided.');
        }

        $this->loadMapper();
        $this->loadForm();

        $segments = $this->fw->split($this->options['segments'] ?? array(), '/');

        if (empty($segments) || 'index' === $segments[0]) {
            $state = self::STATE_LISTING;
        } else {
            $state = $segments[0];
        }

        $this->data['state'] = $state;
        $this->data['route'] = $route;
        $this->data['segments'] = $segments;
        $this->data['keyword'] = $this->options['keyword'] ?? $this->fw['GET'][$this->options['keyword_query_name']] ?? null;
        $this->data['page'] = (int) ($this->options['page'] ?? $this->fw['GET'][$this->options['page_query_name']] ?? 1);
        $this->data['searchable'] = $this->options['searchable'];
        $this->data['route_args'] = $this->options['route_args'];
        $this->data['page_query_name'] = $this->options['page_query_name'];
        $this->data['keyword_query_name'] = $this->options['keyword_query_name'];
        $this->data['title'] = $this->options['title'];
        $this->data['subtitle'] = $this->options['subtitle'];

        if (empty($this->data['title'])) {
            $this->data['title'] = 'Manage '.$this->titleize($this->data['mapper']->table());
        }

        if (empty($this->data['subtitle'])) {
            $this->data['subtitle'] = $this->titleize($state);
        }
    }

    /**
     * Do go back and set message.
     *
     * @param string $key
     * @param string $messageKey
     *
     * @return bool
     */
    protected function goBack(string $key, string $messageKey): bool
    {
        $var = $this->options[$key];
        $createNewKey = $this->options['create_new_session_key'];
        $message = strtr($this->options[$messageKey.'_message'] ?? '', array(
            '%table%' => $this->data['mapper']->table(),
            '%id%' => implode(', ', $this->data['mapper']->keys()),
        ));

        if ($this->options['create_new'] && $this->data['form']['create_new'] && 'create' === $this->data['state']) {
            $createNew = true;
            $target = null;
        } else {
            $createNew = false;
            $target = array(
                $this->data['route'],
                array_merge((array) $this->options['route_args'], array('index')),
                array_filter(array(
                    $this->options['page_query_name'] => $this->data['page'],
                    $this->options['keyword_query_name'] => $this->data['keyword'],
                ), 'is_scalar'),
            );
        }

        $this->fw[$var] = $message;
        $this->fw['SESSION'][$createNewKey] = $createNew;
        $this->fw->reroute($target);

        return false;
    }

    /**
     * Load mapper.
     */
    protected function loadMapper(): void
    {
        $map = $this->options['mapper'];

        if ($map instanceof Mapper) {
            $this->data['mapper'] = $map;
        } elseif (class_exists($map)) {
            $this->data['mapper'] = $this->fw->service($map);
        } else {
            $this->data['mapper'] = $this->fw->instance(Mapper::class, array('args' => array($map)));
        }
    }

    /**
     * Load form.
     */
    protected function loadForm(): void
    {
        $form = $this->options['form'];

        if ($form instanceof Form) {
            $this->data['form'] = $form;
        } elseif ($form && class_exists($form)) {
            $this->data['form'] = $this->fw->service($form);
        } else {
            $this->data['form'] = $this->fw->instance(Form::class);
            $this->trigger('on_form_build', array($this->data['form']));
        }
    }

    /**
     * Titleize name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function titleize(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Prepare fields, ensure field has label and name member.
     */
    protected function prepareFields(): void
    {
        $fields = $this->options['fields'][$this->data['state']] ?? null;

        if ($fields) {
            if (is_string($fields)) {
                $fields = array_fill_keys($this->fw->split($fields), null);
            }
        } else {
            $fields = $this->data['mapper']->schema();
        }

        $orders = $this->fw->split($this->options['field_orders']);
        $keys = array_unique(array_merge($orders, array_keys($fields)));
        $this->data['fields'] = array_fill_keys($keys, array());

        foreach ($fields as $name => $field) {
            $default = array(
                'name' => $name,
                'label' => $this->fw->trans($name, null, $this->titleize($name)),
            );
            $this->data['fields'][$name] = ((array) $field) + $default;
        }
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

        foreach ($keyword ? $this->fw->split($this->options['searchable']) : array() as $field) {
            $filters[$field] = '~' === substr($field, -1) ? '%'.$keyword.'%' : $keyword;
        }

        return $filters;
    }

    /**
     * Prepare item filters.
     *
     * @return array
     */
    protected function prepareItemFilters(): array
    {
        $ids = array_slice($this->data['segments'], $this->options['sid_start'], $this->options['sid_end']);
        $keys = $this->data['mapper']->keys(false);

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

        return is_callable($cb) ? $this->fw->call($cb, $args) : null;
    }

    /**
     * Prepare form.
     *
     * @return Form
     */
    protected function prepareForm(): Form
    {
        $values = array_filter($this->data['mapper']->toArray(), 'is_scalar');
        $initial = $this->data['mapper']->toArray('initial');
        $data = ((array) $this->trigger('on_prepare_data')) + $values + $initial;
        $options = $this->options['form_options'];

        if (is_callable($options)) {
            $options = $this->fw->call($options);
        }

        $this->data['form']->setData($data)->build((array) $options);

        if ($this->options['create_new'] && 'create' === $this->data['state'] && !$this->data['form']->getField('create_new')) {
            $this->data['form']->add('create_new', 'checkbox', array(
                'checked' => $this->fw['SESSION'][$this->options['create_new_session_key']],
            ), array(
                'label' => $this->options['create_new_label'],
            ));
        }

        return $this->data['form'];
    }

    /**
     * Perform state listing.
     *
     * @return bool
     */
    protected function stateListing(): bool
    {
        $this->data['data'] = $this->data['mapper']->paginate($this->data['page'], $this->prepareFilters(), $this->options['listing_options']);

        return true;
    }

    /**
     * Perform state view.
     *
     * @return bool
     */
    protected function stateView(): bool
    {
        $this->data['mapper']->load($this->prepareItemFilters());
        $this->trigger('on_load');

        if ($this->data['mapper']->dry()) {
            throw new HttpException(null, 404);
        }

        return true;
    }

    /**
     * Perform state create.
     *
     * @return bool
     */
    protected function stateCreate(): bool
    {
        $form = $this->prepareForm();

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_create');
            $this->data['mapper']->fromArray($data + $form->getData())->save();
            $this->trigger('after_create');

            return $this->goBack('created_message_key', 'created');
        }

        return true;
    }

    /**
     * Perform state update.
     *
     * @return bool
     */
    protected function stateUpdate(): bool
    {
        $this->stateView();

        $form = $this->prepareForm();

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_update');
            $this->data['mapper']->fromArray($data + $form->getData())->save();
            $this->trigger('after_update');

            return $this->goBack('updated_message_key', 'updated');
        }

        return true;
    }

    /**
     * Perform state delete.
     *
     * @return bool
     */
    protected function stateDelete(): bool
    {
        $this->stateView();

        if ('POST' === $this->fw['VERB']) {
            $this->trigger('before_delete');
            $this->data['mapper']->delete();
            $this->trigger('after_delete');

            return $this->goBack('deleted_message_key', 'deleted');
        }

        return true;
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
            $name = $this->fw->snakeCase($option);

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
     * Convenience method for checking data.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Convenience method for retrieving data.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $ref = &$this->get($offset);

        return $ref;
    }

    /**
     * Convenience method for assigning data.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method for removing data.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}
