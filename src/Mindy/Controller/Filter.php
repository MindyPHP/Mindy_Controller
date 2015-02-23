<?php

namespace Mindy\Controller;

use Mindy\Base\Interfaces\IFilter;
use Mindy\Helper\Traits\BehaviorAccessors;
use Mindy\Helper\Traits\Configurator;

/**
 * CFilter is the base class for all filters.
 *
 * A filter can be applied before and after an action is executed.
 * It can modify the context that the action is to run or decorate the result that the
 * action generates.
 *
 * Override {@link preFilter()} to specify the filtering logic that should be applied
 * before the action, and {@link postFilter()} for filtering logic after the action.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Mindy\Controller
 * @since 1.0
 */
class Filter implements IFilter
{
    use Configurator, BehaviorAccessors;

    /**
     * Performs the filtering.
     * The default implementation is to invoke {@link preFilter}
     * and {@link postFilter} which are meant to be overridden
     * child classes. If a child class needs to override this method,
     * make sure it calls <code>$filterChain->run()</code>
     * if the action should be executed.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     * @param array $params
     */
    public function filter($filterChain, $params = [])
    {
        if ($this->preFilter($filterChain)) {
            $filterChain->run($params);
            $this->postFilter($filterChain);
        }
    }

    /**
     * Initializes the filter.
     * This method is invoked after the filter properties are initialized
     * and before {@link preFilter} is called.
     * You may override this method to include some initialization logic.
     * @since 1.1.4
     */
    public function init()
    {
    }

    /**
     * Performs the pre-action filtering.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     * @return boolean whether the filtering process should continue and the action
     * should be executed.
     */
    protected function preFilter($filterChain)
    {
        return true;
    }

    /**
     * Performs the post-action filtering.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     */
    protected function postFilter($filterChain)
    {
    }
}
