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

namespace Fal\Stick\Library;

use Fal\Stick\App;
use Fal\Stick\Util;
use Fal\Stick\HttpException;
use Fal\Stick\Library\Html\Form;
use Fal\Stick\Library\Sql\Mapper;
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

    const QUERY_KEYWORD = 'keyword';
    const QUERY_PAGE = 'page';

    /**
     * @var App
     */
    protected $app;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var array
     */
    protected static $restricted = array(
        'states',
    );

    /**
     * @var array
     */
    protected $options = array(
        'title' => null,
        'subtitle' => null,
        'form' => null,
        'formBuild' => null,
        'formOptions' => null,
        'fieldOrders' => null,
        'fieldLabels' => null,
        'mapper' => null,
        'state' => null,
        'filters' => array(),
        'listingOptions' => null,
        'searchable' => null,
        'segments' => null,
        'sidStart' => 1,
        'sidEnd' => 1,
        'page' => null,
        'route' => null,
        'routeArgs' => array(),
        'createdMessageKey' => 'SESSION.alerts.success',
        'updatedMessageKey' => 'SESSION.alerts.info',
        'deletedMessageKey' => 'SESSION.alerts.warning',
        'wrapperName' => 'crud',
        'onPrepareData' => null,
        'onDisplay' => null,
        'onLoad' => null,
        'beforeCreate' => null,
        'afterCreate' => null,
        'beforeUpdate' => null,
        'afterUpdate' => null,
        'beforeDelete' => null,
        'afterDelete' => null,
        'states' => null,
        'views' => null,
        'fields' => null,
    );

    /**
     * @var array
     */
    protected $data = array();

    /**
     * Class constructor.
     *
     * @param App      $app
     * @param Template $template
     */
    public function __construct(App $app, Template $template)
    {
        $states = array(
            static::STATE_LISTING,
            static::STATE_VIEW,
            static::STATE_CREATE,
            static::STATE_UPDATE,
            static::STATE_DELETE,
        );
        $this->app = $app;
        $this->template = $template;

        $this->options['states'] = array_fill_keys($states, true);
        $this->options['views'] = $this->options['fields'] = array_fill_keys($states, null);
    }

    /**
     * Do render.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->route = $this->options['route'] ?? $this->app->get('ALIAS');

        if (empty($this->route)) {
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

        $out = true;
        $enabled = $this->options['states'][$state] ?? false;
        $this->page = (int) ($this->options['page'] ?? $this->app->get('GET.'.static::QUERY_PAGE) ?? 1);

        if ($enabled) {
            $handle = 'state'.$state;
            $view = $this->options['views'][$state] ?? null;

            $this->state = $state;
            $this->prepareFields();
            $out = $this->$handle();
        } else {
            $view = $this->options['views'][static::STATE_FORBIDDEN] ?? null;
        }

        if (empty($view)) {
            throw new \LogicException('No view for state: "'.$state.'".');
        }

        $onDisplay = $this->options['onDisplay'];
        $data = array(
            'route' => $this->route,
            'routeArgs' => $this->options['routeArgs'],
            'state' => $state,
            'fields' => $this->fields,
            'page' => $this->page,
            'query_page' => static::QUERY_PAGE,
            'query_keyword' => static::QUERY_KEYWORD,
            'searchable' => $this->options['searchable'],
        );
        $complement = array(
            'title' => $this->options['title'] ?? 'Manage '.Util::titleCase($this->getMapper()->table()),
            'subtitle' => $this->options['subtitle'] ?? Util::titleCase($state),
        );
        $this->template->addFunction('crudLink', function ($args = 'index', $query = null) {
            return $this->app->path($this->route, Util::arr($args), $query);
        });
        $this->template->addFunction('crudDisplay', function(string $field, Mapper $item) use ($onDisplay) {
            return is_callable($onDisplay) ? $this->app->call($onDisplay, array($field, $item)) : $item->get($field);
        });

        return $out ? $this->template->render($view, array(
            $this->options['wrapperName'] => $data + $this->data + $complement,
        )) : null;
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

                if (is_callable($this->options['formBuild'])) {
                    $this->app->call($this->options['formBuild']);
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
        if (!in_array($option, static::$restricted) && array_key_exists($option, $this->options)) {
            if (is_array($this->options[$option])) {
                if (!is_array($value)) {
                    throw new \UnexpectedValueException('Option "'.$option.'" expect array value.');
                }

                $this->options[$option] = array_replace($this->options[$option], $value);
            } else {
                $this->options[$option] = $value;
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
     * Returns state.
     *
     * @return string
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Returns bool if state equals with current state.
     *
     * @param string $state
     *
     * @return bool
     */
    public function isState(string $state): bool
    {
        return $this->state === $state;
    }

    /**
     * Prepare fields.
     */
    protected function prepareFields(): void
    {
        $fields = (array) ($this->options['fields'][$this->state] ?? $this->getMapper()->schema());
        $keys = array_unique(array_merge((array) $this->options['fieldOrders'], array_keys($fields)));
        $this->fields = array_fill_keys($keys, array());

        foreach ($fields as $field => $def) {
            $label = $this->app->trans($field, null, Util::titleCase($field));
            $this->fields[$field] = ((array) $def) + array('label' => $label);
        }
    }

    /**
     * Prepare listing filters.
     *
     * @param string $keyword
     *
     * @return array
     */
    protected function prepareFilters(string $keyword): array
    {
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
        $ids = array_slice($this->options['segments'], $this->options['sidStart'], $this->options['sidEnd']);
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
        return $this->app->trans($key, array(
            'table' => $this->getMapper()->table(),
            'id' => implode(', ', $this->mapper->keys()),
        ));
    }

    /**
     * Returns rerouted target.
     *
     * @param array|null $args
     * @param array|null $query
     *
     * @return array
     */
    protected function rerouteTarget(array $args = null, array $query = null): array
    {
        return array(
            $this->route,
            $this->options['routeArgs'] + ($args ?? array('index')),
            ((array) $query) + array(static::QUERY_PAGE => $this->page),
        );
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
        $option = $this->options['formOptions'];

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

        if (is_callable($this->options['onPrepareData'])) {
            $data = (array) $this->app->call($this->options['onPrepareData']) + $data;
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
        $keyword = (string) $this->app->get('GET.'.static::QUERY_KEYWORD);
        $filters = $this->prepareFilters($keyword);

        $this->data['keyword'] = $keyword;
        $this->data['data'] = $this->getMapper()->paginate($this->page, $filters, $this->options['listingOptions']);

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
        $this->trigger('onLoad');

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
        $form = $this->getForm();
        $form->build($this->resolveFormOptions());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('beforeCreate');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('afterCreate');

            $this->app
                ->set($this->options['createdMessageKey'], $this->message('crud_created'))
                ->reroute($this->rerouteTarget())
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
        $form = $this->getForm();

        $this->stateView();
        $form->build($this->resolveFormOptions(), $this->prepareData());

        if ($form->isSubmitted() && $form->valid()) {
            $data = (array) $this->trigger('beforeUpdate');
            $this->mapper->fromArray($data + $form->getData())->save();
            $this->trigger('afterUpdate');

            $this->app
                ->set($this->options['updatedMessageKey'], $this->message('crud_updated'))
                ->reroute($this->rerouteTarget())
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
            $this->trigger('beforeDelete');
            $this->mapper->delete();
            $this->trigger('afterDelete');

            $this->app
                ->set($this->options['deletedMessageKey'], $this->message('crud_deleted'))
                ->reroute($this->rerouteTarget())
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
