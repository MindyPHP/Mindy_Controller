<?php

namespace Mindy\Controller\Tests;

use Exception;
use Mindy\Controller\BaseController;

class Controller extends BaseController
{
    public function actionIndex()
    {
        return func_get_args();
    }

    public function actionView($name = 'foo')
    {
        return func_get_args();
    }
}

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testAction()
    {
        $c = new Controller('1');
        $out = $c->run('index');
        $this->assertEquals([], $out);

        $out = $c->run('index', ['name' => 1]);
        $this->assertEquals([], $out);

        $out = $c->run('view');
        $this->assertEquals(['foo'], $out);

        $out = $c->run('view', ['name' => 'bar']);
        $this->assertEquals(['bar'], $out);

        $out = $c->run('view', ['path' => 'bar']);
        $this->assertEquals(['foo'], $out);
    }

    public function testMissingAction()
    {
        $c = new Controller('1');
        $this->setExpectedException(Exception::class);
        $c->run('unknown');
    }
}