<?php

class IndexControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();

        // default server variables
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'gsaa.local';
    }

    public function testIndexActionRoutingWorks()
    {
        $params = array('action' => 'index', 'controller' => 'Index', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
    }

    public function testIndexPageProperlyDispatched() {
        $this->dispatch('/');

        $this->assertNotController('error');

        $this->assertNotRedirect();

        $this->assertModule('default');
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertResponseCode(200);
    }

    public function testIndexPageProperlyRendered() {
        $this->dispatch('/');

        // header is present
        $this->assertQueryContentContains("#header h1", "GeoSocialApp aggregator");

        // both address form and search fomr are present
        $this->assertQueryCount('form', 2);

        // connect buttons are there
        $this->assertQueryCount('div#oauth-wrapper div', 3);
        $this->assertQuery("div#oauth-wrapper div a img");


    }

}
