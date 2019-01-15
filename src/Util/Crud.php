<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 15, 2019 11:38
 */

namespace Fal\Stick\Util;

use Fal\Stick\Core;
use Fal\Stick\Form\Form;
use Fal\Stick\Security\Auth;
use Fal\Stick\Database\Sql\Mapper;
use Fal\Stick\Template\Template;

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
     * @var Core
     */
    protected $_core;

    /**
     * @var Template
     */
    protected $_template;

    /**
     * @var Auth
     */
    protected $_auth;

    /**
     * @var array
     */
    protected $_data = array(
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
    protected $_options = array(
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
        'created_message_key' => 'SESSION.alerts.success',
        'updated_message_key' => 'SESSION.alerts.info',
        'deleted_message_key' => 'SESSION.alerts.warning',
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
        'create_new_session_key' => 'SESSION.crud_create_new',
    );

    /**
     * @var array
     */
    protected $_functions = array();

    /**
     * Class constructor.
     *
     * @param Core       $app
     * @param Template   $template
     * @param array|null $views
     */
    public function __construct(Core $fw, Template $template, array $views = null)
    {
        $states = array(
            static::STATE_LISTING,
            static::STATE_VIEW,
            static::STATE_CREATE,
            static::STATE_UPDATE,
            static::STATE_DELETE,
        );
        $nullStates = array_fill_keys($states, null);

        $this->_core = $fw;
        $this->_template = $template;

        $this->_options['states'] = array_fill_keys($states, true);
        $this->_options['views'] = $nullStates;
        $this->_options['fields'] = $nullStates;
        $this->_options['roles'] = $nullStates;

        empty($views) || $this->views($view);
    }

    /**
     * Returns true if data exists.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * Returns data value.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Sets data value.
     *
     * @param mixed $key
     * @param mixed $val
     *
     * @return Crud
     */
    public function set($key, $val)
    {
        $this->_data[$key] = $val;

        return $this;
    }

    /**
     * Clear data.
     *
     * @param mixed $key
     *
     * @return Crud
     */
    public function clear($key)
    {
        unset($this->_data[$key]);

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
    public function addFunction($name, callable $cb)
    {
        $this->_functions[$name] = $cb;

        return $this;
    }

    /**
     * Get auth instance.
     *
     * @return Auth
     *
     * @throws LogicException if auth instance not registered yet
     */
    public function getAuth()
    {
        if (!$this->_auth) {
            $this->_auth = $this->_core->service('Fal\\Stick\\Security\\Auth');
        }

        return $this->_auth;
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
    public function call($name, array $args = null, $default = null)
    {
        $cb = isset($this->_functions[$name]) ? $this->_functions[$name] : null;

        return $cb ? $this->_core->call($cb, $args) : $default;
    }

    /**
     * Enable state.
     *
     * @param string|array $states
     *
     * @return Crud
     */
    public function enable($states)
    {
        foreach ($this->_core->split($states) as $state) {
            $this->_options['states'][$state] = true;
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
    public function disable($states)
    {
        foreach ($this->_core->split($states) as $state) {
            $this->_options['states'][$state] = false;
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
    public function field($states, $fields)
    {
        foreach ($this->_core->split($states) as $state) {
            $this->_options['fields'][$state] = $fields;
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
    public function view($state, $view)
    {
        $this->_options['views'][$state] = $view;

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
    public function role($state, $roles)
    {
        $this->getAuth();
        $this->_options['roles'][$state] = $roles;

        return $this;
    }

    /**
     * Massive role sets.
     *
     * @param array $roles
     *
     * @return Crud
     */
    public function roles(array $roles)
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
    public function option($name)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function options()
    {
        return $this->_options;
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function data()
    {
        return $this->_data;
    }

    /**
     * Returns crud link.
     *
     * @param mixed $path
     * @param mixed $query
     *
     * @return string
     */
    public function path($path = 'index', $query = null)
    {
        if (empty($this->_data['route'])) {
            throw new \LogicException('No route defined.');
        }

        $paths = is_string($path) ? explode('/', $path) : $path;
        $args = array_merge((array) $this->_options['route_args'], $paths);

        return $this->_core->path($this->_data['route'], $args, $query);
    }

    /**
     * Returns true if state roles not exists or role is granted.
     *
     * @param string $state
     *
     * @return bool
     */
    public function isGranted($state)
    {
        $enabled = isset($this->_options['states'][$state]) ? $this->_options['states'][$state] : false;
        $roles = $this->_core->split(isset($this->_options['roles'][$state]) ? $this->_options['roles'][$state] : null);

        if (!$enabled || !$roles) {
            return true;
        }

        return $this->getAuth()->isGranted($roles);
    }

    /**
     * Do render.
     *
     * @return string|null
     */
    public function render()
    {
        $this->init();

        $state = $this->_data['state'];
        $enabled = isset($this->_options['states'][$state]) ? $this->_options['states'][$state] : false;
        $roles = isset($this->_options['roles'][$state]) ? $this->_options['roles'][$state] : null;
        $var = $this->_options['varname'];

        if ($enabled && (!$roles || $this->getAuth()->isGranted($roles))) {
            $handle = 'state'.$state;
            $view = isset($this->_options['views'][$state]) ? $this->_options['views'][$state] : null;

            $this->prepareFields();
            $this->trigger('on_init');

            $out = $this->$handle();
        } else {
            $view = isset($this->_options['views'][static::STATE_FORBIDDEN]) ? $this->_options['views'][static::STATE_FORBIDDEN] : null;
            $out = true;
        }

        if (empty($view)) {
            throw new \LogicException(sprintf('No view for state: "%s".', $state));
        }

        return $out ? $this->_template->render($view, array($var => $this)) : null;
    }

    /**
     * Prepare crud.
     */
    protected function init()
    {
        $route = isset($this->_options['route']) ? $this->_options['route'] : $this->_core->get('ALIAS');

        if (empty($route)) {
            throw new \LogicException('No route defined.');
        }

        if (empty($this->_options['mapper'])) {
            throw new \LogicException('No mapper provided.');
        }

        $this->loadMapper();
        $this->loadForm();

        $segments = $this->_core->split(isset($this->_options['segments']) ? $this->_options['segments'] : array(), '/');

        if (empty($segments) || 'index' === $segments[0]) {
            $state = self::STATE_LISTING;
        } else {
            $state = $segments[0];
        }

        $this->_data['state'] = $state;
        $this->_data['route'] = $route;
        $this->_data['segments'] = $segments;
        $this->_data['keyword'] = isset($this->_options['keyword']) ? $this->_options['keyword'] : $this->_core->get('GET.'.$this->_options['keyword_query_name']);
        $this->_data['page'] = (int) (isset($this->_options['page']) ? $this->_options['page'] : ($this->_core->get('GET.'.$this->_options['page_query_name']) ?: 1));
        $this->_data['searchable'] = $this->_options['searchable'];
        $this->_data['route_args'] = $this->_options['route_args'];
        $this->_data['page_query_name'] = $this->_options['page_query_name'];
        $this->_data['keyword_query_name'] = $this->_options['keyword_query_name'];
        $this->_data['title'] = $this->_options['title'];
        $this->_data['subtitle'] = $this->_options['subtitle'];

        if (empty($this->_data['title'])) {
            $this->_data['title'] = 'Manage '.$this->titleize($this->_data['mapper']->table());
        }

        if (empty($this->_data['subtitle'])) {
            $this->_data['subtitle'] = $this->titleize($state);
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
    protected function goBack($key, $messageKey)
    {
        $var = $this->_options[$key];
        $createNewKey = $this->_options['create_new_session_key'];
        $message = strtr(isset($this->_options[$messageKey.'_message']) ? $this->_options[$messageKey.'_message'] : '', array(
            '%table%' => $this->_data['mapper']->table(),
            '%id%' => implode(', ', $this->_data['mapper']->keys()),
        ));

        if ($this->_options['create_new'] && $this->_data['form']->create_new && 'create' === $this->_data['state']) {
            $createNew = true;
            $target = null;
        } else {
            $createNew = false;
            $target = array(
                $this->_data['route'],
                array_merge((array) $this->_options['route_args'], array('index')),
                array_filter(array(
                    $this->_options['page_query_name'] => $this->_data['page'],
                    $this->_options['keyword_query_name'] => $this->_data['keyword'],
                ), 'is_scalar'),
            );
        }

        $this->_core->set($var, $message);
        $this->_core->set($createNewKey, $createNew);
        $this->_core->reroute($target);

        return false;
    }

    /**
     * Load mapper.
     */
    protected function loadMapper()
    {
        $map = $this->_options['mapper'];

        if ($map instanceof Mapper) {
            $this->_data['mapper'] = $map;
        } elseif (class_exists($map)) {
            $this->_data['mapper'] = $this->_core->service($map);
        } else {
            $this->_data['mapper'] = $this->_core->createInstance(Mapper::class, array('arguments' => array($map)));
        }
    }

    /**
     * Load form.
     */
    protected function loadForm()
    {
        $form = $this->_options['form'];

        if ($form instanceof Form) {
            $this->_data['form'] = $form;
        } elseif ($form && class_exists($form)) {
            $this->_data['form'] = $this->_core->service($form);
        } else {
            $this->_data['form'] = $this->_core->createInstance(Form::class);
            $this->trigger('on_form_build', array($this->_data['form']));
        }
    }

    /**
     * Titleize name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function titleize($name)
    {
        return ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Prepare fields, ensure field has label and name member.
     */
    protected function prepareFields()
    {
        $fields = isset($this->_options['fields'][$this->_data['state']]) ? $this->_options['fields'][$this->_data['state']] : null;

        if ($fields) {
            if (is_string($fields)) {
                $fields = array_fill_keys($this->_core->split($fields), null);
            }
        } else {
            $fields = $this->_data['mapper']->schema();
        }

        $orders = $this->_core->split($this->_options['field_orders']);
        $keys = array_unique(array_merge($orders, array_keys($fields)));
        $this->_data['fields'] = array_fill_keys($keys, array());

        foreach ($fields as $name => $field) {
            $default = array(
                'name' => $name,
                'label' => $this->_core->trans($name, null, $this->titleize($name)),
            );
            $this->_data['fields'][$name] = ((array) $field) + $default;
        }
    }

    /**
     * Prepare listing filters.
     *
     * @return array
     */
    protected function prepareFilters()
    {
        $keyword = $this->_data['keyword'];
        $filters = $this->_options['filters'];
        $search = &$filters[];

        foreach ($keyword ? $this->_core->split($this->_options['searchable'], ',') : array() as $field) {
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
    protected function prepareItemFilters()
    {
        $ids = array_slice($this->_data['segments'], $this->_options['sid_start'], $this->_options['sid_end']);
        $keys = $this->_data['mapper']->keys(false);

        if (count($ids) !== count($keys)) {
            throw new \LogicException('Insufficient primary keys!');
        }

        return $this->_options['filters'] + array_combine($keys, $ids);
    }

    /**
     * Trigger internal event.
     *
     * @param string     $eventName
     * @param array|null $args
     *
     * @return mixed
     */
    protected function trigger($eventName, array $args = null)
    {
        $cb = isset($this->_options[$eventName]) ? $this->_options[$eventName] : null;

        return is_callable($cb) ? $this->_core->call($cb, $args) : null;
    }

    /**
     * Prepare form.
     *
     * @return Form
     */
    protected function prepareForm()
    {
        $values = array_filter($this->_data['mapper']->toArray(), 'is_scalar');
        $initial = $this->_data['mapper']->toArray('initial');
        $data = ((array) $this->trigger('on_prepare_data')) + $values + $initial;
        $options = $this->_options['form_options'];

        if (is_callable($options)) {
            $options = $this->_core->call($options);
        }

        $this->_data['form']->setData($data)->prepare((array) $options);

        if ($this->_options['create_new'] && 'create' === $this->_data['state'] && !$this->_data['form']->getField('create_new')) {
            $this->_data['form']->addField('create_new', 'checkbox', array(
                'checked' => $this->_core->get('SESSION.'.$this->_options['create_new_session_key']),
            ), array(
                'label' => $this->_options['create_new_label'],
            ));
        }

        return $this->_data['form'];
    }

    /**
     * Perform state listing.
     *
     * @return bool
     */
    protected function stateListing()
    {
        $this->_data['data'] = $this->_data['mapper']->paginate($this->_data['page'], $this->prepareFilters(), $this->_options['listing_options']);

        return true;
    }

    /**
     * Perform state view.
     *
     * @return bool
     */
    protected function stateView()
    {
        $this->_data['mapper']->load($this->prepareItemFilters());
        $this->trigger('on_load');

        if ($this->_data['mapper']->dry()) {
            $this->_core->error(404);

            return false;
        }

        return true;
    }

    /**
     * Perform state create.
     *
     * @return bool
     */
    protected function stateCreate()
    {
        $form = $this->prepareForm();

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_create');
            $this->_data['mapper']->fromArray($data + $form->getData())->save();
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
    protected function stateUpdate()
    {
        $this->stateView();

        $form = $this->prepareForm();

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_update');
            $this->_data['mapper']->fromArray($data + $form->getData())->save();
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
    protected function stateDelete()
    {
        $this->stateView();

        if ('POST' === $this->_core->get('VERB')) {
            $this->trigger('before_delete');
            $this->_data['mapper']->delete();
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
            $name = $this->_core->snakeCase($option);

            if (array_key_exists($name, $this->_options)) {
                $value = $args[0];

                if (is_array($this->_options[$name])) {
                    if (!is_array($value)) {
                        throw new \UnexpectedValueException(sprintf('Option "%s" expect array value.', $name));
                    }

                    $this->_options[$name] = array_replace($this->_options[$name], $value);
                } else {
                    $this->_options[$name] = $value;
                }
            }
        }

        return $this;
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
}
