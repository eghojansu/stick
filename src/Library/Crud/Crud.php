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

use Fal\Stick\App;
use Fal\Stick\HttpException;
use Fal\Stick\Library\Html\Form;
use Fal\Stick\Library\Security\Auth;
use Fal\Stick\Library\Sql\Mapper;
use Fal\Stick\Library\Template\Template;
use Fal\Stick\Util;

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
     * @var App
     */
    protected $app;

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
    protected static $restricted = array(
        'states',
    );

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
        'route_args' => array(),
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
     * @param App      $app
     * @param Template $template
     * @param Auth     $auth
     */
    public function __construct(App $app, Template $template, Auth $auth)
    {
        $states = array(
            static::STATE_LISTING,
            static::STATE_VIEW,
            static::STATE_CREATE,
            static::STATE_UPDATE,
            static::STATE_DELETE,
        );
        $nullStates = array_fill_keys($states, null);

        $this->app = $app;
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
        $this->hive['route'] = $this->options['route'] ?? $this->app->get('ALIAS');

        if (empty($this->hive['route'])) {
            throw new \LogicException('No route defined.');
        }

        if (empty($this->options['segments'])) {
            throw new \LogicException('No segments provided.');
        }

        if (empty($this->options['mapper'])) {
            throw new \LogicException('No mapper provided.');
        }

        $state = $this->options['state'] ?? $this->options['segments'][0] ?? null;

        if ('index' === $state) {
            $state = static::STATE_LISTING;
        }

        $wrapperName = $this->options['wrapper_name'];
        $pageName = $this->options['page_query_name'];
        $keywordName = $this->options['keyword_query_name'];
        $enabled = $this->options['states'][$state] ?? false;
        $roles = $this->options['roles'][$state] ?? null;

        $this->hive['state'] = $state;
        $this->hive['keyword'] = $this->options['keyword'] ?? $this->app->get('GET.'.$keywordName) ?? null;
        $this->hive['page'] = (int) ($this->options['page'] ?? $this->app->get('GET.'.$pageName) ?? 1);
        $this->back = array(
            $this->hive['route'],
            array_merge($this->options['route_args'], array('index')),
            array_filter(array(
                $pageName => $this->hive['page'],
                $keywordName => $this->hive['keyword'],
            ), 'is_scalar'),
        );

        if ($enabled && (!$roles || $this->auth->isGranted($roles))) {
            $handle = 'state'.$state;
            $view = $this->options['views'][$state] ?? null;

            $this->prepareFields();
            $out = $this->$handle();
        } else {
            $view = $this->options['views'][static::STATE_FORBIDDEN] ?? null;
            $out = true;
        }

        if (empty($view)) {
            throw new \LogicException('No view for state: "'.$state.'".');
        }

        if (!$out) {
            return null;
        }

        $pick = array('searchable', 'route_args', 'page_query_name', 'keyword_query_name');
        $data = array_intersect_key($this->options, array_flip($pick));
        $complement = array(
            'title' => $this->options['title'] ?? 'Manage '.Util::titleCase($this->getMapper()->table()),
            'subtitle' => $this->options['subtitle'] ?? Util::titleCase($state),
        );
        $crudData = new CrudData(...array(
            $this->app,
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
        $this->options['states'] = array_fill_keys(Util::arr($states), false) + $this->options['states'];

        return $this;
    }

    /**
     * Returns CRUD mapper instance.
     *
     * @return Mapper|null
     */
    public function getMapper(): ?Mapper
    {
        $map = $this->options['mapper'];

        if (!$this->mapper && $map) {
            if ($map instanceof Mapper) {
                $this->mapper = $map;
            } elseif (class_exists($map)) {
                $this->mapper = $this->app->service($map);
            } else {
                $this->mapper = $this->app->instance(Mapper::class, array($map));
            }
        }

        return $this->mapper;
    }

    /**
     * Returns form instance.
     *
     * @return Form
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $form = $this->options['form'];

            if ($form instanceof Form) {
                $this->form = $form;
            } elseif ($form && class_exists($form)) {
                $this->form = $this->app->service($form);
            } else {
                $this->form = $this->app->instance(Form::class);

                if (is_callable($this->options['form_build'])) {
                    $this->app->call($this->options['form_build'], array($this->form));
                }
            }
        }

        return $this->form;
    }

    /**
     * Returns option value.
     *
     * @param string|null $name
     *
     * @return mixed
     */
    public function getOption(string $name = null)
    {
        return $name ? $this->options[$name] ?? null : $this->options;
    }

    /**
     * Sets option.
     *
     * @param string $option
     * @param mixed  $value
     *
     * @return Crud
     */
    public function setOption(string $option, $value = null): Crud
    {
        $name = Util::snakeCase($option);

        if (!in_array($name, static::$restricted) && array_key_exists($name, $this->options)) {
            if (is_array($this->options[$name])) {
                if (!is_array($value)) {
                    throw new \UnexpectedValueException('Option "'.$name.'" expect array value.');
                }

                $this->options[$name] = array_replace($this->options[$name], $value);
            } else {
                $this->options[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * Returns data value.
     *
     * @param string|null $name
     *
     * @return mixed
     */
    public function getData(string $name = null)
    {
        return $name ? $this->data[$name] ?? null : $this->data;
    }

    /**
     * Sets data.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Crud
     */
    public function setData(string $name, $value): Crud
    {
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
    public function getHive(string $name)
    {
        return $this->hive[$name] ?? null;
    }

    /**
     * Prepare fields, ensure field has label and name member.
     */
    protected function prepareFields(): void
    {
        $fields = (array) ($this->options['fields'][$this->hive['state']] ?? $this->getMapper()->schema());
        $keys = array_unique(array_merge((array) $this->options['field_orders'], array_keys($fields)));
        $this->hive['fields'] = array_fill_keys($keys, array());

        foreach ($fields as $name => $field) {
            $label = $this->app->trans($name, null, Util::titleCase($name));
            $default = compact('label', 'name');
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

        foreach ($keyword ? Util::arr($this->options['searchable']) : array() as $field) {
            $filters[$field] = Util::endswith($field, '~') ? '%'.$keyword.'%' : $keyword;
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
            '%table%' => $this->getMapper()->table(),
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
            return $this->app->call($this->options[$eventName]);
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
            return (array) $this->app->call($option);
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
            $data = (array) $this->app->call($this->options['on_prepare_data']) + $data;
        }

        return $data;
    }

    /**
     * Perform state listing.
     *
     * @return bool
     */
    protected function stateListing(): bool
    {
        $this->data['data'] = $this->getMapper()->paginate(...array(
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
        $this->getMapper()->load($this->prepareItemFilters());
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
        $form = $this->getForm()->build($this->resolveFormOptions());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_create');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('after_create');

            $this->app
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

        $form = $this->getForm()->build($this->resolveFormOptions(), $this->prepareData());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('before_update');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('after_update');

            $this->app
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

        if ('POST' === $this->app->get('VERB')) {
            $this->trigger('before_delete');
            $this->mapper->delete();
            $this->trigger('after_delete');

            $this->app
                ->set($this->options['deleted_message_key'], $this->message('deleted'))
                ->reroute($this->back)
            ;

            return false;
        }

        return true;
    }

    /**
     * Proxy to setOption.
     *
     * @param string $method
     * @param array  $args
     *
     * @return Crud
     */
    public function __call($method, $args)
    {
        if ($args) {
            $this->setOption($method, $args[0]);
        }

        return $this;
    }
}
