<?php

namespace Mindy\Controller;

/**
 * CController class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
use Mindy\Base\Mindy;
use Mindy\Exception\Exception;
use Mindy\Exception\HttpException;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\BehaviorAccessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Request;
use Mindy\Http\Traits\HttpErrors;


/**
 * CController manages a set of actions which deal with the corresponding user requests.
 *
 * Through the actions, CController coordinates the data flow between models and views.
 *
 * When a user requests an action 'XYZ', CController will do one of the following:
 * 1. Method-based action: call method 'actionXYZ' if it exists;
 * 2. Class-based action: create an instance of class 'XYZ' if the class is found in the action class map
 *    (specified via {@link actions()}, and execute the action;
 * 3. Call {@link missingAction()}, which by default will raise a 404 HTTP exception.
 *
 * If the user does not specify an action, CController will run the action specified by
 * {@link defaultAction}, instead.
 *
 * CController may be configured to execute filters before and after running actions.
 * Filters preprocess/postprocess the user request/response and may quit executing actions
 * if needed. They are executed in the order they are specified. If during the execution,
 * any of the filters returns true, the rest filters and the action will no longer get executed.
 *
 * Filters can be individual objects, or methods defined in the controller class.
 * They are specified by overriding {@link filters()} method. The following is an example
 * of the filter specification:
 * <pre>
 * array(
 *     'accessControl - login',
 *     'ajaxOnly + search',
 *     array(
 *         'COutputCache + list',
 *         'duration'=>300,
 *     ),
 * )
 * </pre>
 * The above example declares three filters: accessControl, ajaxOnly, COutputCache. The first two
 * are method-based filters (defined in CController), which refer to filtering methods in the controller class;
 * while the last refers to an object-based filter whose class is 'system.web.widgets.COutputCache' and
 * the 'duration' property is initialized as 300 (s).
 *
 * For method-based filters, a method named 'filterXYZ($filterChain)' in the controller class
 * will be executed, where 'XYZ' stands for the filter name as specified in {@link filters()}.
 * Note, inside the filter method, you must call <code>$filterChain->run()</code> if the action should
 * be executed. Otherwise, the filtering process would stop at this filter.
 *
 * Filters can be specified so that they are executed only when running certain actions.
 * For method-based filters, this is done by using '+' and '-' operators in the filter specification.
 * The '+' operator means the filter runs only when the specified actions are requested;
 * while the '-' operator means the filter runs only when the requested action is not among those actions.
 * For object-based filters, the '+' and '-' operators are following the class name.
 *
 * @property array $actionParams The request parameters to be used for action parameter binding.
 * @property Action $action The action currently being executed, null if no active action.
 * @property string $id ID of the controller.
 * @property string $uniqueId The controller ID that is prefixed with the module ID (if any).
 * @property string $route The route (module ID, controller ID and action ID) of the current request.
 * @property \Mindy\Http\Request $request The request component
 * @property Module $module The module that this controller belongs to. It returns null
 * if the controller does not belong to any module.
 * @property string $viewPath The directory containing the view files for this controller. Defaults to 'protected/views/ControllerID'.
 * @property Map $clips The list of clips.
 * @property string $pageTitle The page title. Defaults to the controller name and the action name.
 * @property Stack $cachingStack Stack of {@link COutputCache} objects.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web
 * @since 1.0
 *
 * CBaseController is the base class for {@link CController} and {@link CWidget}.
 *
 * It provides the common functionalities shared by controllers who need to render views.
 *
 * CBaseController also implements the support for the following features:
 * <ul>
 * <li>{@link CClipWidget Clips} : a clip is a piece of captured output that can be inserted elsewhere.</li>
 * <li>{@link CWidget Widgets} : a widget is a self-contained sub-controller with its own view and model.</li>
 * <li>{@link COutputCache Fragment cache} : fragment cache selectively caches a portion of the output.</li>
 * </ul>
 *
 * To use a widget in a view, use the following in the view:
 * <pre>
 * $this->widget('path.to.widgetClass',array('property1'=>'value1',...));
 * </pre>
 * or
 * <pre>
 * $this->beginWidget('path.to.widgetClass',array('property1'=>'value1',...));
 * // ... display other contents here
 * $this->endWidget();
 * </pre>
 *
 * To create a clip, use the following:
 * <pre>
 * $this->beginClip('clipID');
 * // ... display the clip contents
 * $this->endClip();
 * </pre>
 * Then, in a different view or place, the captured clip can be inserted as:
 * <pre>
 * echo $this->clips['clipID'];
 * </pre>
 *
 * Note that $this in the code above refers to current controller so, for example,
 * if you need to access clip from a widget where $this refers to widget itself
 * you need to do it the following way:
 *
 * <pre>
 * echo $this->getController()->clips['clipID'];
 * </pre>
 *
 * To use fragment cache, do as follows,
 * <pre>
 * if($this->beginCache('cacheID',array('property1'=>'value1',...))
 * {
 *     // ... display the content to be cached here
 *    $this->endCache();
 * }
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web
 * @since 1.0
 */
class BaseController
{
    use Configurator, BehaviorAccessors, HttpErrors;

    /**
     * Name of the hidden field storing persistent page states.
     */
    const STATE_INPUT_NAME = 'YII_PAGE_STATE';
    /**
     * @var string the name of the default action. Defaults to 'index'.
     */
    public $defaultAction = 'index';

    private $_id;
    private $_action;
    private $_module;
    /**
     * @var \Mindy\Http\Request
     */
    private $_request;

    /**
     * @param string $id id of this controller
     * @param \Mindy\Base\Module $module the module that this controller belongs to.
     * @param \Mindy\Http\Request $request
     */
    public function __construct($id, $module = null, Request $request)
    {
        $this->_id = $id;
        $this->_module = $module;
        $this->_request = $request;

        $signal = Mindy::app()->signal;
        $signal->handler($this, 'beforeAction', [$this, 'beforeAction']);
        $signal->handler($this, 'afterAction', [$this, 'afterAction']);

        $this->attachBehaviors($this->behaviors());
    }

    /**
     * @return array
     */
    public function getCsrfExempt()
    {
        return [];
    }

    /**
     * @DEPRECATED
     * @return Request
     */
    public function getR()
    {
        return $this->_request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.
     * @return boolean whether the action should be executed.
     */
    public function beforeAction($owner, $action)
    {
        return true;
    }

    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param Action $action the action just executed.
     * @param $out
     * @return string
     */
    public function afterAction($action, $out)
    {
        $app = Mindy::app();
        if ($app->hasComponent('middleware')) {
            $app->middleware->processView($this->getRequest(), $out);
            $app->middleware->processResponse($this->getRequest());
        }
        echo $out;
    }

    /**
     * Initializes the controller.
     * This method is called by the application before the controller starts to execute.
     * You may override this method to perform the needed initialization for the controller.
     */
    public function init()
    {
    }

    /**
     * Returns the filter configurations.
     *
     * By overriding this method, child classes can specify filters to be applied to actions.
     *
     * This method returns an array of filter specifications. Each array element specify a single filter.
     *
     * For a method-based filter (called inline filter), it is specified as 'FilterName[ +|- Action1, Action2, ...]',
     * where the '+' ('-') operators describe which actions should be (should not be) applied with the filter.
     *
     * For a class-based filter, it is specified as an array like the following:
     * <pre>
     * array(
     *     'FilterClass[ +|- Action1, Action2, ...]',
     *     'name1'=>'value1',
     *     'name2'=>'value2',
     *     ...
     * )
     * </pre>
     * where the name-value pairs will be used to initialize the properties of the filter.
     *
     * Note, in order to inherit filters defined in the parent class, a child class needs to
     * merge the parent filters with child filters using functions like array_merge().
     *
     * @return array a list of filter configurations.
     * @see CFilter
     */
    public function filters()
    {
        return [];
    }

    /**
     * Returns a list of external action classes.
     * Array keys are action IDs, and array values are the corresponding
     * action class in dot syntax (e.g. 'edit'=>'application.controllers.article.EditArticle')
     * or arrays representing the configuration of the actions, such as the following,
     * <pre>
     * return array(
     *     'action1'=>'path.to.Action1Class',
     *     'action2'=>array(
     *         'class'=>'path.to.Action2Class',
     *         'property1'=>'value1',
     *         'property2'=>'value2',
     *     ),
     * );
     * </pre>
     * Derived classes may override this method to declare external actions.
     *
     * Note, in order to inherit actions defined in the parent class, a child class needs to
     * merge the parent actions with child actions using functions like array_merge().
     *
     * You may import actions from an action provider
     * (such as a widget, see {@link CWidget::actions}), like the following:
     * <pre>
     * return array(
     *     ...other actions...
     *     // import actions declared in ProviderClass::actions()
     *     // the action IDs will be prefixed with 'pro.'
     *     'pro.'=>'path.to.ProviderClass',
     *     // similar as above except that the imported actions are
     *     // configured with the specified initial property values
     *     'pro2.'=>array(
     *         'class'=>'path.to.ProviderClass',
     *         'action1'=>array(
     *             'property1'=>'value1',
     *         ),
     *         'action2'=>array(
     *             'property2'=>'value2',
     *         ),
     *     ),
     * )
     * </pre>
     *
     * In the above, we differentiate action providers from other action
     * declarations by the array keys. For action providers, the array keys
     * must contain a dot. As a result, an action ID 'pro2.action1' will
     * be resolved as the 'action1' action declared in the 'ProviderClass'.
     *
     * @return array list of external action classes
     * @see createAction
     */
    public function actions()
    {
        return [];
    }

    /**
     * Returns a list of behaviors that this controller should behave as.
     * The return value should be an array of behavior configurations indexed by
     * behavior names. Each behavior configuration can be either a string specifying
     * the behavior class or an array of the following structure:
     * <pre>
     * 'behaviorName'=>array(
     *     'class'=>'path.to.BehaviorClass',
     *     'property1'=>'value1',
     *     'property2'=>'value2',
     * )
     * </pre>
     *
     * Note, the behavior classes must implement {@link IBehavior} or extend from
     * {@link CBehavior}. Behaviors declared in this method will be attached
     * to the controller when it is instantiated.
     *
     * For more details about behaviors, see {@link CComponent}.
     * @return array the behavior configurations (behavior name=>behavior configuration)
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * Returns the access rules for this controller.
     * Override this method if you use the {@link filterAccessControl accessControl} filter.
     * @return array list of access rules. See {@link CAccessControlFilter} for details about rule specification.
     */
    public function accessRules()
    {
        return [];
    }

    /**
     * Runs the named action.
     * Filters specified via {@link filters()} will be applied.
     * @param string $actionID action ID
     * @param array $params
     * @see filters
     * @see createAction
     * @see runAction
     */
    public function run($actionID, $params = [])
    {
        if (($action = $this->createAction($actionID)) !== null) {
            $signal = Mindy::app()->signal;
            $signal->send($this, 'beforeAction', $this, $action);
            ob_start();
            $this->runActionWithFilters($action, $this->filters(), $params);
            $signal->send($this, 'afterAction', $action, ob_get_clean());
        } else {
            $this->missingAction($actionID);
        }
    }

    /**
     * Runs an action with the specified filters.
     * A filter chain will be created based on the specified filters
     * and the action will be executed then.
     * @param Action $action the action to be executed.
     * @param array $filters list of filters to be applied to the action.
     * @param array $params
     * @see filters
     * @see createAction
     * @see runAction
     */
    public function runActionWithFilters($action, $filters, $params = [])
    {
        if (empty($filters)) {
            $this->runAction($action, $params);
        } else {
            $priorAction = $this->_action;
            $this->_action = $action;
            FilterChain::create($this, $action, $filters)->run($params);
            $this->_action = $priorAction;
        }
    }

    /**
     * Runs the action after passing through all filters.
     * This method is invoked by {@link runActionWithFilters} after all possible filters have been executed
     * and the action starts to run.
     * @param Action $action action to run
     * @param array $params
     */
    public function runAction($action, $params = [])
    {
        $priorAction = $this->_action;
        $this->_action = $action;
        $signal = Mindy::app()->signal;
        $results = $signal->send($this, 'beforeAction', $this, $action);
        if ($results->getLast()->value) {
            ob_start();
            if ($action->runWithParams($params) === false) {
                ob_end_clean();
                $this->invalidActionParams($action);
            } else {
                $signal->send($this, 'afterAction', $action, ob_get_clean());
            }
        }
        $this->_action = $priorAction;
    }

    /**
     * This method is invoked when the request parameters do not satisfy the requirement of the specified action.
     * The default implementation will throw a 400 HTTP exception.
     * @param Action $action the action being executed
     * @throws \Mindy\Exception\HttpException
     * @since 1.1.7
     */
    public function invalidActionParams($action)
    {
        throw new HttpException(400, Mindy::t('base', 'Your request is invalid.'));
    }

    /**
     * Creates the action instance based on the action name.
     * The action can be either an inline action or an object.
     * The latter is created by looking up the action map specified in {@link actions}.
     * @param string $actionID ID of the action. If empty, the {@link defaultAction default action} will be used.
     * @throws \Mindy\Exception\Exception
     * @return Action the action instance, null if the action does not exist.
     * @see actions
     */
    public function createAction($actionID)
    {
        if ($actionID === '') {
            $actionID = $this->defaultAction;
        }
        if (method_exists($this, 'action' . $actionID) && strcasecmp($actionID, 's')) { // we have actions method
            return new InlineAction($this, $actionID);
        } else {
            $action = $this->createActionFromMap($this->actions(), $actionID, $actionID);
            if ($action !== null && !method_exists($action, 'run')) {
                throw new Exception(Mindy::t('base', 'Action class {class} must implement the "run" method.', array('{class}' => get_class($action))));
            }
            return $action;
        }
    }

    /**
     * Creates the action instance based on the action map.
     * This method will check to see if the action ID appears in the given
     * action map. If so, the corresponding configuration will be used to
     * create the action instance.
     * @param array $actionMap the action map
     * @param string $actionID the action ID that has its prefix stripped off
     * @param string $requestActionID the originally requested action ID
     * @param array $config the action configuration that should be applied on top of the configuration specified in the map
     * @throws \Mindy\Exception\Exception
     * @return Action the action instance, null if the action does not exist.
     */
    protected function createActionFromMap($actionMap, $actionID, $requestActionID, $config = [])
    {
        if (($pos = strpos($actionID, '.')) === false && isset($actionMap[$actionID])) {
            $baseConfig = is_array($actionMap[$actionID]) ? $actionMap[$actionID] : ['class' => $actionMap[$actionID]];
            return Creator::createObject(empty($config) ? $baseConfig : array_merge($baseConfig, $config), $this, $requestActionID);
        } elseif ($pos === false) {
            return null;
        }

        // the action is defined in a provider
        $prefix = substr($actionID, 0, $pos + 1);
        if (!isset($actionMap[$prefix])) {
            return null;
        }
        $actionID = (string)substr($actionID, $pos + 1);

        $provider = $actionMap[$prefix];
        if (is_string($provider)) {
            $providerType = $provider;
        } elseif (is_array($provider) && isset($provider['class'])) {
            $providerType = $provider['class'];
            if (isset($provider[$actionID])) {
                if (is_string($provider[$actionID])) {
                    $config = array_merge(['class' => $provider[$actionID]], $config);
                } else {
                    $config = array_merge($provider[$actionID], $config);
                }
            }
        } else {
            throw new Exception(Mindy::t('base', 'Object configuration must be an array containing a "class" element.'));
        }

        $map = call_user_func([$providerType, 'actions']);
        return $this->createActionFromMap($map, $actionID, $requestActionID, $config);
    }

    /**
     * Handles the request whose action is not recognized.
     * This method is invoked when the controller cannot find the requested action.
     * The default implementation simply throws an exception.
     * @param string $actionID the missing action name
     * @throws HttpException whenever this method is invoked
     */
    public function missingAction($actionID)
    {
        throw new HttpException(404, Mindy::t('base', 'The system is unable to find the requested action "{action}".',
            ['{action}' => $actionID == '' ? $this->defaultAction : $actionID]));
    }

    /**
     * @return Action the action currently being executed, null if no active action.
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param Action $value the action currently being executed.
     */
    public function setAction($value)
    {
        $this->_action = $value;
    }

    /**
     * @return string ID of the controller
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        return $this->_module ? $this->_module->getId() . '/' . $this->_id : $this->_id;
    }

    /**
     * @return \Mindy\Base\Module the module that this controller belongs to. It returns null
     * if the controller does not belong to any module
     */
    public function getModule()
    {
        if ($this->_module === null) {
            $reflect = new \ReflectionClass(get_class($this));
            $namespace = $reflect->getNamespaceName();
            $segments = explode('\\', $namespace);
            $this->_module = Mindy::app()->getModule($segments[1]);
        }
        return $this->_module;
    }

    /**
     * Processes the request using another controller action.
     * This is like {@link redirect}, but the user browser's URL remains unchanged.
     * In most cases, you should call {@link redirect} instead of this method.
     * @param string $route the route of the new controller action. This can be an action ID, or a complete route
     * with module ID (optional in the current module), controller ID and action ID. If the former, the action is assumed
     * to be located within the current controller.
     * @param boolean $exit whether to end the application after this call. Defaults to true.
     * @since 1.1.0
     */
    public function forward($route, $exit = true)
    {
        if (strpos($route, '/') === false) {
            $this->run($route);
        } else {
            if ($route[0] !== '/' && ($module = $this->getModule()) !== null) {
                $route = $module->getId() . '/' . $route;
            }
            Mindy::app()->runController($route);
        }
        if ($exit) {
            Mindy::app()->end();
        }
    }

    /**
     * The filter method for 'postOnly' filter.
     * This filter throws an exception (HttpException with code 400) if the applied action is receiving a non-POST request.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     * @throws HttpException if the current request is not a POST request
     */
    public function filterPostOnly($filterChain)
    {
        if ($this->r->isPost) {
            $filterChain->run();
        } else {
            throw new HttpException(400, Mindy::t('base', 'Your request is invalid.'));
        }
    }

    /**
     * The filter method for 'ajaxOnly' filter.
     * This filter throws an exception (HttpException with code 400) if the applied action is receiving a non-AJAX request.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     * @throws HttpException if the current request is not an AJAX request.
     */
    public function filterAjaxOnly($filterChain)
    {
        if ($this->r->isAjax) {
            $filterChain->run();
        } else {
            throw new HttpException(400, Mindy::t('base', 'Your request is invalid.'));
        }
    }

    /**
     * The filter method for 'accessControl' filter.
     * This filter is a wrapper of {@link CAccessControlFilter}.
     * To use this filter, you must override {@link accessRules} method.
     * @param FilterChain $filterChain the filter chain that the filter is on.
     */
    public function filterAccessControl($filterChain)
    {
        // TODO refactoring
        $filter = new AccessControlFilter;
        $filter->setRules($this->accessRules());
        $filter->filter($filterChain);
    }
}
