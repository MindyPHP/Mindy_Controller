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
 * @date 09/06/14.06.2014 17:44
 */

namespace Mindy\Controller;

/**
 * IFilter is the interface that must be implemented by action filters.
 *
 * @package Mindy\Base
 * @since 1.0
 */
interface IFilter
{
    /**
     * Performs the filtering.
     * This method should be implemented to perform actual filtering.
     * If the filter wants to continue the action execution, it should call
     * <code>$filterChain->run()</code>.
     * @param \Mindy\Controller\FilterChain $filterChain the filter chain that the filter is on.
     */
    public function filter($filterChain);
}
