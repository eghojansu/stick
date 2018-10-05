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

namespace Fal\Stick;

use Fal\Stick\Html\Form;
use Fal\Stick\Sql\Mapper;
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
        'routeArgs' => array(),
        'createdMessageKey' => 'SESSION.alerts.success',
        'updatedMessageKey' => 'SESSION.alerts.info',
        'deletedMessageKey' => 'SESSION.alerts.warning',
        'wrapperName' => 'crud',
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
        $this->app
            ->throws(empty($this->options['segments']), 'Please provide route segments.')
            ->throws(empty($this->options['mapper']), 'Please provide mapper class or table name.')
        ;

        $state = $this->options['state'] ?? $this->options['segments'][0] ?? null;

        if ('index' === $state) {
            $state = static::STATE_LISTING;
        }

        $out = true;
        $enabled = $this->options['states'][$state] ?? false;
        $this->page = (int) ($this->options['page'] ?? $this->app->get('QUERY.'.static::QUERY_PAGE) ?? 1);

        if ($enabled) {
            $handle = 'state'.$state;
            $view = $this->options['views'][$state] ?? null;

            $this->state = $state;
            $this->prepareFields();
            $out = $this->$handle();
        } else {
            $view = $this->options['views'][static::STATE_FORBIDDEN] ?? null;
        }

        $this->app->throws(empty($view), 'No view for state: "'.$state.'".');

        $data = array(
            'state' => $state,
            'fields' => $this->fields,
            'page' => $this->page,
            'query_page' => static::QUERY_PAGE,
            'query_keyword' => static::QUERY_KEYWORD,
            'searchable' => $this->options['searchable'],
        );
        $complement = array(
            'title' => $this->options['title'] ?? 'Manage '.$this->app->titleCase($this->getMapper()->table()),
            'subtitle' => $this->options['subtitle'] ?? $this->app->titleCase($state),
        );

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
        $this->options['states'] = array_fill_keys($this->app->arr($states), false) + $this->options['states'];

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

                if ($this->options['formBuild'] && is_callable($this->options['formBuild'])) {
                    call_user_func_array($this->options['formBuild'], array($this->form));
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
                $throw = !is_array($value);
                $message = 'Option "'.$option.'" expect array value.';
                $this->app->throws($throw, $message, 'UnexpectedValueException');

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
     * Prepare fields.
     */
    protected function prepareFields(): void
    {
        $fields = (array) ($this->options['fields'][$this->state] ?? $this->getMapper()->schema());
        $keys = array_unique(array_merge((array) $this->options['fieldOrders'], array_keys($fields)));
        $this->fields = array_fill_keys($keys, array());

        foreach ($fields as $field => $def) {
            $label = $this->app->trans($field, null, $this->app->titleCase($field));
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
        $filters = array();

        foreach ($keyword ? $this->app->arr($this->options['searchable']) : array() as $field) {
            $filters[$field] = $this->app->endswith($field, '~') ? '%'.$keyword.'%' : $keyword;
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

        $this->app->throws(count($ids) !== count($keys), 'Insufficient primary keys!');

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
            $this->app->get('ALIAS'),
            $this->options['routeArgs'] + ($args ?? array('index')),
            ((array) $query) + array(static::QUERY_PAGE => $this->page),
        );
    }

    /**
     * Perform state listing.
     *
     * @return bool
     */
    protected function stateListing(): bool
    {
        $keyword = (string) $this->app->get('QUERY.'.static::QUERY_KEYWORD);
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
        $this->app->throws(!$this->getForm(), 'Please provide form.');

        if ($this->form->isSubmitted() && $this->form->valid()) {
            $this->mapper->save();
            $this->app
                ->set($this->options['createdMessageKey'], $this->message('crud_created'))
                ->reroute($this->rerouteTarget())
            ;

            return false;
        }

        $this->data['form'] = $this->form;

        return true;
    }

    /**
     * Perform state update.
     *
     * @return bool
     */
    protected function stateUpdate(): bool
    {
        $this->app->throws(!$this->getForm(), 'Please provide form.');

        $this->stateView();
        $this->form->setData($this->mapper->toArray());

        if ($this->form->isSubmitted() && $this->form->valid()) {
            $this->mapper->save();
            $this->app
                ->set($this->options['updatedMessageKey'], $this->message('crud_updated'))
                ->reroute($this->rerouteTarget())
            ;

            return false;
        }

        $this->data['form'] = $this->form;

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
            $this->mapper->delete();
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
