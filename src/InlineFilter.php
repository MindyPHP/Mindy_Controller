<?php

namespace Mindy\Controller;

/**
 * CInlineFilter class file.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
use Exception;

use Mindy\Base\Mindy;

/**
 * CInlineFilter represents a filter defined as a controller method.
 *
 * CInlineFilter executes the 'filterXYZ($action)' method defined
 * in the controller, where the name 'XYZ' can be retrieved from the {@link name} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web.filters
 * @since 1.0
 */
class InlineFilter extends Filter
{
    /**
     * @var string name of the filter. It stands for 'XYZ' in the filter method name 'filterXYZ'.
     */
    public $name;

    /**
     * Creates an inline filter instance.
     * The creation is based on a string describing the inline method name
     * and action names that the filter shall or shall not apply to.
     * @param \Mindy\Controller\BaseController $controller the controller who hosts the filter methods
     * @param string $filterName the filter name
     * @return InlineFilter the created instance
     * @throws Exception if the filter method does not exist
     */
    public static function create($controller, $filterName)
    {
        if (method_exists($controller, 'filter' . $filterName)) {
            $filter = new InlineFilter;
            $filter->name = $filterName;
            return $filter;
        } else {
            throw new Exception('Filter "' . $filterName . '" is invalid. Controller "{' . get_class($controller) . '}" does not have the filter method "filter{filter}"');
        }
    }

    /**
     * Performs the filtering.
     * This method calls the filter method defined in the controller class.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     */
    public function filter($filterChain)
    {
        $method = 'filter' . $this->name;
        $filterChain->controller->$method($filterChain);
    }
}
