<?php

class OauthControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();

        // default server variables
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'gsaa.local';
    }

    public function testCallbackActionPrintErrorWithInvalidToken()
    {
        $params = array('action' => 'callback', 'controller' => 'oauth', 'module' => 'default',
                        'service' => 'fq',
                        'code'   => 'INVALID'
            );
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        print_r($this->getResponse()->outputBody());
        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);

        $this->assertQueryCount('#document #content p', 2);
    }

    public function testIsAuthorizedNotDirectlyAccessible()
    {
        $params = array('action' => 'is-authorized', 'controller' => 'oauth', 'module' => 'default',
                        'service' => 'fq'
            );
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertController('error');
        $this->assertResponseCode(500);
    }
    public function testIsAuthorizedReturnsFalse()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'is-authorized', 'controller' => 'oauth', 'module' => 'default',
                        'service' => 'fq'
            );
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);

        $responseBody = $this->getResponse()->getBody();
        $responseValue = Zend_Json::decode($responseBody);
        $this->assertTrue(is_array($responseValue));
        $this->assertEquals($responseValue['status'], false);

    }

    public function testDestroyActionIsDispatchedFalse()
    {
        $params = array('action' => 'destroy', 'controller' => 'oauth', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
    }


}



