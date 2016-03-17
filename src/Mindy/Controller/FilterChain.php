<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 10/06/14.06.2014 13:47
 */

namespace Mindy\Controller;

use Mindy\Exception\Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Creator;
use Mindy\Utils\BaseList;

/**
 * FilterChain class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * FilterChain represents a list of filters being applied to an action.
 *
 * FilterChain executes the filter list by {@link run()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web.filters
 * @since 1.0
 */
class FilterChain extends BaseList
{
    /**
     * @var \Mindy\Controller\BaseController the controller who executes the action.
     */
    public $controller;
    /**
     * @var Action the action being filtered by this chain.
     */
    public $action;
    /**
     * @var integer the index of the filter that is to be executed when calling {@link run()}.
     */
    public $filterIndex = 0;


    /**
     * Constructor.
     * @param \Mindy\Controller\BaseController $controller the controller who executes the action.
     * @param Action $action the action being filtered by this chain.
     */
    public function __construct($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }

    /**
     * FilterChain factory method.
     * This method creates a FilterChain instance.
     * @param \Mindy\Controller\BaseController $controller the controller who executes the action.
     * @param Action $action the action being filtered by this chain.
     * @param array $filters list of filters to be applied to the action.
     * @throws \Mindy\Exception\Exception
     * @return FilterChain
     */
    public static function create($controller, $action, $filters)
    {
        $chain = new FilterChain($controller, $action);

        $actionID = $action->getId();
        foreach ($filters as $filter) {
            if (is_string($filter)) { // filterName [+|- action1 action2]
                if (($pos = strpos($filter, '+')) !== false || ($pos = strpos($filter, '-')) !== false) {
                    $matched = preg_match("/\b{$actionID}\b/i", substr($filter, $pos + 1)) > 0;
                    if (($filter[$pos] === '+') === $matched) {
                        $filter = InlineFilter::create($controller, trim(substr($filter, 0, $pos)));
                    }
                } else {
                    $filter = InlineFilter::create($controller, $filter);
                }
            } elseif (is_array($filter)) {
                // array('path.to.class [+|- action1, action2]','param1'=>'value1',...)
                if (!isset($filter['class'])) {
                    throw new Exception(Mindy::t('base', 'The first element in a filter configuration must be the filter class.'));
                }
                $filterClass = $filter['class'];
                unset($filter['class']);
                if (($pos = strpos($filterClass, '+')) !== false || ($pos = strpos($filterClass, '-')) !== false) {
                    $matched = preg_match("/\b{$actionID}\b/i", substr($filterClass, $pos + 1)) > 0;
                    if (($filterClass[$pos] === '+') === $matched) {
                        $filterClass = trim(substr($filterClass, 0, $pos));
                    } else {
                        continue;
                    }
                }
                $filter['class'] = $filterClass;
                $filter = Creator::createObject($filter);
            }

            if (is_object($filter)) {
                $filter->init();
                $chain->add($filter);
            }
        }
        return $chain;
    }

    /**
     * Inserts an item at the specified position.
     * This method overrides the parent implementation by adding
     * additional check for the item to be added. In particular,
     * only objects implementing {@link IFilter} can be added to the list.
     * @param integer $index the specified position.
     * @param mixed $item new item
     * @throws Exception If the index specified exceeds the bound or the list is read-only, or the item is not an {@link IFilter} instance.
     */
    public function insertAt($index, $item)
    {
        if ($item instanceof IFilter) {
            parent::insertAt($index, $item);
        } else {
            throw new Exception(Mindy::t('base', 'FilterChain can only take objects implementing the IFilter interface.'));
        }
    }

    /**
     * Executes the filter indexed at {@link filterIndex}.
     * After this method is called, {@link filterIndex} will be automatically incremented by one.
     * This method is usually invoked in filters so that the filtering process
     * can continue and the action can be executed.
     */
    public function run($params = [])
    {
        if ($this->offsetExists($this->filterIndex)) {
            $filter = $this->itemAt($this->filterIndex++);
            Mindy::app()->logger->info('Running filter ' . ($filter instanceof InlineFilter ? get_class($this->controller) . '.filter' . $filter->name . '()' : get_class($filter) . '.filter()'), [], 'system.web.filters.FilterChain');
            $filter->filter($this, $params);
        } else {
            $this->controller->runAction($this->action, $params);
        }
    }
}
