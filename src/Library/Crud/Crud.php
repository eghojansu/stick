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

namespace Fal\Stick\Library\Crud;

use Fal\Stick\Fw;
use Fal\Stick\HttpException;
use Fal\Stick\Library\Html\Form;
use Fal\Stick\Library\Security\Auth;
use Fal\Stick\Library\Sql\Mapper;
use Fal\Stick\Library\Str;
use Fal\Stick\Library\Template\Template;

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
     * @var Form
     */
    protected $form;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var array
     */
    protected $hive = array(
        'state' => null,
        'route' => null,
        'page' => null,
        'keyword' => null,
        'fields' => array(),
    );

    /**
     * @var array
     */
    protected $options = array(
        'title' => null,
        'subtitle' => null,
        'form' => null,
        'form_build' => null,
        'form_options' => null,
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
        'wrapper_name' => 'crud',
        'on_prepare_data' => null,
        'on_display' => null,
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
    );

    /**
     * @var array
     */
    protected $data = array();

    /**
     * Reroute back target.
     *
     * @var array
     */
    protected $back;

    /**
     * Class constructor.
     *
     * @param Fw       $app
     * @param Template $template
     * @param Auth     $auth
     */
    public function __construct(Fw $fw, Template $template, Auth $auth)
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
        $this->auth = $auth;

        $this->options['states'] = array_fill_keys($states, true);
        $this->options['views'] = $nullStates;
        $this->options['fields'] = $nullStates;
        $this->options['roles'] = $nullStates;
    }

    /**
     * Do render.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->hive['route'] = $this->options['route'] ?? $this->fw->get('ALIAS');

        if (empty($this->hive['route'])) {
            throw new \LogicException('No route defined.');
        }

        if (empty($this->options['segments'])) {
            throw new \LogicException('No segments provided.');
        }

        if (empty($this->options['mapper'])) {
            throw new \LogicException('No mapper provided.');
        }

        $map = array('index' => self::STATE_LISTING);
        $state = $this->options['state'] ?? $this->options['segments'][0] ?? null;
        $mState = $map[$state] ?? $state;

        $wrapperName = $this->options['wrapper_name'];
        $pageName = $this->options['page_query_name'];
        $keywordName = $this->options['keyword_query_name'];
        $enabled = $this->options['states'][$mState] ?? false;
        $roles = $this->options['roles'][$mState] ?? null;

        $this->hive['state'] = $mState;
        $this->hive['keyword'] = $this->options['keyword'] ?? $this->fw->get('GET.'.$keywordName) ?? null;
        $this->hive['page'] = (int) ($this->options['page'] ?? $this->fw->get('GET.'.$pageName) ?? 1);
        $this->back = array(
            $this->hive['route'],
            array_merge((array) $this->options['route_args'], array('index')),
            array_filter(array(
                $pageName => $this->hive['page'],
                $keywordName => $this->hive['keyword'],
            ), 'is_scalar'),
        );

        if ($enabled && (!$roles || $this->auth->isGranted($roles))) {
            $handle = 'state'.$mState;
            $view = $this->options['views'][$mState] ?? null;

            $this->prepareFields();
            $out = $this->$handle();
        } else {
            $view = $this->options['views'][static::STATE_FORBIDDEN] ?? null;
            $out = true;
        }

        if (empty($view)) {
            throw new \LogicException('No view for state: "'.$mState.'".');
        }

        if (!$out) {
            return null;
        }

        $pick = array('searchable', 'route_args', 'page_query_name', 'keyword_query_name');
        $data = array_intersect_key($this->options, array_flip($pick));
        $complement = array(
            'title' => $this->options['title'] ?? 'Manage '.Str::titleCase($this->mapper()->table()),
            'subtitle' => $this->options['subtitle'] ?? Str::titleCase($mState),
        );
        $crudData = new CrudData(...array(
            $this->fw,
            $this->auth,
            $this->hive['route'],
            $this->options['roles'],
            $this->hive + $data + $complement + $this->data,
            $this->options['on_display'],
        ));

        return $this->template->render($view, array($wrapperName => $crudData));
    }

    /**
     * Disable state.
     *
     * @param string|array $states
     *
     * @return Crud
     */
    public function disabled($states): Crud
    {
        $this->options['states'] = array_fill_keys($this->arr($states), false) + $this->options['states'];

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
    public function fields($states, $fields): Crud
    {
        $this->options['fields'] = array_fill_keys($this->arr($states), $fields) + $this->options['fields'];

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
     * Set segments.
     *
     * @param string|array $segments
     *
     * @return Crud
     */
    public function segments($segments): Crud
    {
        $this->options['segments'] = is_string($segments) ? explode('/', $segments) : $segments;

        return $this;
    }

    /**
     * Returns option value.
     *
     * @param string|null $name
     *
     * @return mixed
     */
    public function option(string $name = null)
    {
        return $name ? $this->options[$name] ?? null : $this->options;
    }

    /**
     * Sets or get data value.
     *
     * Null value is ignored.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    public function data(string $name, $value = null)
    {
        if (null === $value) {
            return $this->data[$name] ?? null;
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Returns hive value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function hive(string $name)
    {
        return $this->hive[$name] ?? null;
    }

    /**
     * Sets or get CRUD mapper instance.
     *
     * @param mixed $mapper
     *
     * @return Mapper|Crud|null
     */
    public function mapper($mapper = null)
    {
        if (null !== $mapper) {
            $this->options['mapper'] = $mapper;

            return $this;
        }

        $map = $this->options['mapper'];

        if (!$this->mapper && $map) {
            if ($map instanceof Mapper) {
                $this->mapper = $map;
            } elseif (class_exists($map)) {
                $this->mapper = $this->fw->service($map);
            } else {
                $this->mapper = $this->fw->instance(Mapper::class, array($map));
            }
        }

        return $this->mapper;
    }

    /**
     * Returns form instance.
     *
     * @param mixed $form
     *
     * @return Form|Crud
     */
    public function form($form = null)
    {
        if (null !== $form) {
            $this->options['form'] = $form;

            return $this;
        }

        if (!$this->form) {
            $form = $this->options['form'];

            if ($form instanceof Form) {
                $this->form = $form;
            } elseif ($form && class_exists($form)) {
                $this->form = $this->fw->service($form);
            } else {
                $this->form = $this->fw->instance(Form::class);

                if (is_callable($this->options['form_build'])) {
                    $this->fw->call($this->options['form_build'], array($this->form));
                }
            }
        }

        return $this->form;
    }

    /**
     * Prepare fields, ensure field has label and name member.
     */
    protected function prepareFields(): void
    {
        $fields = $this->options['fields'][$this->hive['state']] ?? null;

        if ($fields) {
            if (is_string($fields)) {
                $fields = array_fill_keys($this->arr($fields), null);
            }
        } else {
            $fields = $this->mapper()->schema();
        }

        $orders = $this->arr($this->options['field_orders']);
        $keys = array_unique(array_merge($orders, array_keys($fields)));
        $this->hive['fields'] = array_fill_keys($keys, array());

        foreach ($fields as $name => $field) {
            $default = array(
                'name' => $name,
                'label' => $this->fw->trans($name, null, Str::titleCase($name)),
            );
            $this->hive['fields'][$name] = ((array) $field) + $default;
        }
    }

    /**
     * Prepare listing filters.
     *
     * @return array
     */
    protected function prepareFilters(): array
    {
        $keyword = $this->hive['keyword'];
        $filters = $this->options['filters'];

        foreach ($keyword ? $this->arr($this->options['searchable']) : array() as $field) {
            $filters[$field] = Str::endswith($field, '~') ? '%'.$keyword.'%' : $keyword;
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
        $ids = array_slice($this->options['segments'], $this->options['sid_start'], $this->options['sid_end']);
        $keys = $this->mapper->keys();

        if (count($ids) !== count($keys)) {
            throw new \LogicException('Insufficient primary keys!');
        }

        $filters = array_combine(array_keys($keys), $ids);

        return $this->options['filters'] + $filters;
    }

    /**
     * Returns translated message key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function message(string $key): string
    {
        return strtr($this->options[$key.'_message'] ?? '', array(
            '%table%' => $this->mapper()->table(),
            '%id%' => implode(', ', $this->mapper->keys()),
        ));
    }

    /**
     * Trigger internal event.
     *
     * @param string $eventName
     *
     * @return mixed
     */
    protected function trigger(string $eventName)
    {
        if (is_callable($this->options[$eventName])) {
            return $this->fw->call($this->options[$eventName]);
        }

        return null;
    }

    /**
     * Resolve form options.
     *
     * @return array
     */
    protected function resolveFormOptions(): array
    {
        $option = $this->options['form_options'];

        if (is_callable($option)) {
            return (array) $this->fw->call($option);
        }

        return (array) $option;
    }

    /**
     * Prepare data before assign to update.
     *
     * @return array
     */
    protected function prepareData(): array
    {
        $data = $this->mapper->toArray();

        if (is_callable($this->options['on_prepare_data'])) {
            $data = (array) $this->fw->call($this->options['on_prepare_data']) + $data;
        }

        return $data;
    }

    /**
     * Normalize fields definitions.
     *
     * @param mixed $val
     *
     * @return array
     */
    protected function arr($val): array
    {
        return is_array($val) ? $val : array_filter(array_map('trim', explode(',', (string) $val)));
    }

    /**
     * Perform state listing.
     *
     * @return bool
     */
    protected function stateListing(): bool
    {
        $this->data['data'] = $this->mapper()->paginate(...array(
            $this->hive['page'],
            $this->prepareFilters(),
            $this->options['listing_options'],
        ));

        return true;
    }

    /**
     * Perform state view.
     *
     * @return bool
     */
    protected function stateView(): bool
    {
        $this->mapper()->load($this->prepareItemFilters());
        $this->trigger('on_load');

        if ($this->mapper->dry()) {
            throw new HttpException(null, 404);
        }

        $this->data['item'] = $this->mapper;

        return true;
    }

    /**
     * Perform state create.
     *
     * @return bool
     */
    protected function stateCreate(): bool
    {
        $form = $this->form()->build($this->resolveFormOptions());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_create');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('after_create');

            $this->fw
                ->set($this->options['created_message_key'], $this->message('created'))
                ->reroute($this->back)
            ;

            return false;
        }

        $this->data['form'] = $form;

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

        $form = $this->form()->build($this->resolveFormOptions(), $this->prepareData());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_update');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('after_update');

            $this->fw
                ->set($this->options['updated_message_key'], $this->message('updated'))
                ->reroute($this->back)
            ;

            return false;
        }

        $this->data['form'] = $form;

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

        if ('POST' === $this->fw->get('VERB')) {
            $this->trigger('before_delete');
            $this->mapper->delete();
            $this->trigger('after_delete');

            $this->fw
                ->set($this->options['deleted_message_key'], $this->message('deleted'))
                ->reroute($this->back)
            ;

            return false;
        }

        return true;
    }

    /**
     * Setting option via method call.
     *
     * @param string $option
     * @param array  $args
     *
     * @return Crud
     */
    public function __call($option, $args)
    {
        if ($args) {
            $name = Str::snakeCase($option);

            if (array_key_exists($name, $this->options)) {
                $value = $args[0];

                if (is_array($this->options[$name])) {
                    if (!is_array($value)) {
                        throw new \UnexpectedValueException('Option "'.$name.'" expect array value.');
                    }

                    $this->options[$name] = array_replace($this->options[$name], $value);
                } else {
                    $this->options[$name] = $value;
                }
            }
        }

        return $this;
    }
}
