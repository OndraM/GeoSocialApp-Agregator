<?php
class AbsoluteUrlTest extends PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp() {
        parent::setUp();
        $request = new Zend_Controller_Request_Http();
        $this->front = Zend_Controller_Front::getInstance();
        $this->front->resetInstance();
        $this->front->setRequest($request);
        $this->front->getRouter()->addDefaultRoutes();

        $view = new Zend_View();
        $this->helper = new GSAA_View_Helper_AbsoluteUrl();
        $this->helper->setView($view);

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'gsaa.test';
    }

    protected function tearDown() {
        unset($this->helper);
        parent::tearDown();
    }

    public function testRenderWorks() {
        $this->assertEquals('http://gsaa.test/', $this->helper->absoluteUrl());
    }

    public function testRenderWorksWithDifferentHosts() {
        $_SERVER['HTTP_HOST'] = 'google.com';
        $this->assertEquals('http://google.com/', $this->helper->absoluteUrl());

        $_SERVER['HTTP_HOST'] = 'www.xn--tistaticetti-wkcff.eu';
        $this->assertEquals('http://www.xn--tistaticetti-wkcff.eu/', $this->helper->absoluteUrl());
    }
    public function testRenderWorksWithDifferentProtocols() {
        $_SERVER['HTTP_HOST'] = 'test';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTPS/1.1';
        $this->assertEquals('https://test/', $this->helper->absoluteUrl());

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        $this->assertEquals('http://test/', $this->helper->absoluteUrl());
    }


}

