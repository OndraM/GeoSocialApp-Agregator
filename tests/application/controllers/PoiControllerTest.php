<?php

class POIControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();

        // default server variables
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'gsaa.local';
    }

    public function testGetNearbyActionRoutingWorks()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'get-nearby', 'controller' => 'Poi', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
    }

    public function testGetNearbyActionNotDirectlyAccessible() {
        $this->dispatch('/poi/get-nearby');

        $this->assertNotController('poi');
        $this->assertResponseCode(500);
    }

    public function testGetNearbyActionWithoutParametrsAcessibleThroughXmlHttpRequestAndReturnsEmptyJson()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $this->dispatch('/poi/get-nearby');
        $this->assertResponseCode(200);
        $this->assertHeaderContains('Content-Type', 'application/json');

        $responseBody = $this->getResponse()->getBody();
        $responseValue = Zend_Json::decode($responseBody);
        $this->assertTrue(is_array($responseValue));
        $this->assertTrue(empty($responseValue));
    }

    // TODO: tests with parameters

    // TODO: data provider for details

    // TODO: test show-detail action
    /*public function testShowDetailActionRoutingWorks()
    {
        //$this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'show-detail', 'controller' => 'Poi', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
    }*/

    /*public function testGetNearbyActionAccessibleThroughXmlHttpRequest() {
        $this->dispatch('/poi/show-detail');

        $this->assertResponseCode(500);
    }*/
}





