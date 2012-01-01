<?php

class UserControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        parent::setUp();

    }

    public function testIndexAction()
    {
        $params = array('action' => 'index', 'controller' => 'User', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // assertions
        /*
        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
        $this->assertQueryContentContains(
            'div#view-content p',
            'View script for controller <b>' . $params['controller'] . '</b> and script/action name <b>' . $params['action'] . '</b>'
            );
         *
         */
    }

    public function testFriendsActionIsProperlyDispatched()
    {
        $this->dispatch('/user/friends');

        $this->assertModule('default');
        $this->assertController('user');
        $this->assertAction('friends');
        $this->assertResponseCode(200);
    }

    public function  testFriendsActionLoadedThroughAjaxDontContainLayout()
    {
        $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $params = array('action' => 'friends', 'controller' => 'user', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // header is not present
        $this->assertNotQueryContentContains("#header h1", "GeoSocialApp aggregator");

        // no scripts are loaded
        $this->assertQueryCount("head script", 0);
    }

    public function  testFriendsActionLoadedDirectlyContainLayout()
    {
        $params = array('action' => 'friends', 'controller' => 'user', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        // scripts are loaded
        $this->assertQuery("head script");

        // CSS loaded
        $this->assertQuery("head link");

        // header is not present
        $this->assertQueryContentContains("#header h1", "GeoSocialApp aggregator");
    }

    public function testFriendsActionProperlyRendered()
    {
        $params = array('action' => 'friends', 'controller' => 'user', 'module' => 'default');
        $urlParams = $this->urlizeOptions($params);
        $url = $this->url($urlParams);
        $this->dispatch($url);

        $this->assertQueryContentContains("h1", "Friends last checkins");
        $this->assertQueryCount("h1 p", 'Error loading recent checkins of your friends - you are either not connected to any of your account, or no one of your friends has done checkin recently.');
    }

}



