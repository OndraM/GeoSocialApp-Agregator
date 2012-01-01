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

    public function testGetNearbyActionWorks()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $this->dispatch('/poi/get-nearby?lat=50.076738&long=14.41803&radius=500&fq=on&gg=on&fb=on&gw=on&term=');
        $this->assertResponseCode(200);
        $this->assertHeaderContains('Content-Type', 'application/json');

        $responseBody = $this->getResponse()->getBody();
        $responseValue = Zend_Json::decode($responseBody);
        $this->assertTrue(is_array($responseValue));
        $this->assertTrue(!empty($responseValue));

        $this->assertTrue(!empty($responseValue['pois']));
    }

    public function testShowDetailIsProperlyDispatched()
    {
        $this->dispatch('/poi/show-detail/4adcdaa3f964a520904e21e3/fq/CnRnAAAA_IujrZtopAsuWOS-lIeQTreN2H8pho_QtpFnX8S1Wtr489-_RiM0KPsT0715uJSWpjCqS7X6QDMC2KmEapomYnMGcMSnsidtxG19rzMcuZojTkWAi3qUgWn-Af8GJjEMNTx3kR4sIv_xaRlT0uHP5xIQnRYs1bZjJ3_g9HF44DOmnBoU2uloTM5Xbz1kRo-eJumD0nY70Js/gg/141286919270246/fb/6572209/gw');

        $this->assertModule('default');
        $this->assertController('poi');
        $this->assertAction('show-detail');
        $this->assertResponseCode(200);
    }
    public function testShowDetailLoadedThroughAjaxIsProperlyDispatched()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'show-detail', 'controller' => 'poi', 'module' => 'default',
                        '4adcdaa3f964a520904e21e3' => 'fq',
                        'CnRnAAAA_IujrZtopAsuWOS-lIeQTreN2H8pho_QtpFnX8S1Wtr489-_RiM0KPsT0715uJSWpjCqS7X6QDMC2KmEapomYnMGcMSnsidtxG19rzMcuZojTkWAi3qUgWn-Af8GJjEMNTx3kR4sIv_xaRlT0uHP5xIQnRYs1bZjJ3_g9HF44DOmnBoU2uloTM5Xbz1kRo-eJumD0nY70Js' => 'gg',
                        '141286919270246'   => 'fb',
                        '6572209'   => 'gw');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
        $this->assertResponseCode(200);
    }

    public function testShowDetailLoadedThroughAjaxDontContainLayout()
    {

        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'show-detail', 'controller' => 'poi', 'module' => 'default',
                        '4adcdaa3f964a520904e21e3' => 'fq',
                        'CnRnAAAA_IujrZtopAsuWOS-lIeQTreN2H8pho_QtpFnX8S1Wtr489-_RiM0KPsT0715uJSWpjCqS7X6QDMC2KmEapomYnMGcMSnsidtxG19rzMcuZojTkWAi3qUgWn-Af8GJjEMNTx3kR4sIv_xaRlT0uHP5xIQnRYs1bZjJ3_g9HF44DOmnBoU2uloTM5Xbz1kRo-eJumD0nY70Js' => 'gg',
                        '141286919270246'   => 'fb',
                        '6572209'   => 'gw');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // header is not present
        $this->assertNotQueryContentContains("#header h1", "GeoSocialApp aggregator");

        // no scripts are loaded
        $this->assertQueryCount("head script", 0);

    }

    public function testShowDetailProperlyRendered()
    {
        $params = array('action' => 'show-detail', 'controller' => 'poi', 'module' => 'default',
                        '4adcdaa3f964a520904e21e3' => 'fq',
                        'CnRnAAAA_IujrZtopAsuWOS-lIeQTreN2H8pho_QtpFnX8S1Wtr489-_RiM0KPsT0715uJSWpjCqS7X6QDMC2KmEapomYnMGcMSnsidtxG19rzMcuZojTkWAi3qUgWn-Af8GJjEMNTx3kR4sIv_xaRlT0uHP5xIQnRYs1bZjJ3_g9HF44DOmnBoU2uloTM5Xbz1kRo-eJumD0nY70Js' => 'gg',
                        '141286919270246'   => 'fb',
                        '6572209'   => 'gw');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // scripts are loaded
        $this->assertQuery("head script");

        // CSS loaded
        $this->assertQuery("head link");

        // header is not present
        $this->assertQueryContentContains("#header h1", "GeoSocialApp aggregator");

        $this->assertQueryContentContains("#venue-source", "Sources of venue data");
        $this->assertQueryCount("#venue-source-list li", 4);
    }

    public function testShowDetailGeocodeAddress()
    {
        $params = array('action' => 'show-detail', 'controller' => 'poi', 'module' => 'default',
                        '121562'   => 'gw');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // address section is included
        $this->assertQueryContentContains("h2#venue-address", 'Address');
    }
}





