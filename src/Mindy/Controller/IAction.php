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
 * IAction is the interface that must be implemented by controller actions.
 *
 * @package Mindy\Base
 * @since 1.0
 */
interface IAction
{
    /**
     * @return string id of the action
     */
    public function getId();

    /**
     * @return BaseController the controller instance
     */
    public function getController();
}
