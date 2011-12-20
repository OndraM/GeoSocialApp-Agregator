<?php
class LbsFoursquareModelTest extends PHPUnit_Framework_TestCase
{
    protected $_model;
    protected $_modelName;

    protected function setUp() {
        parent::setUp();
        $this->appliaction = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->appliaction->bootstrap();

        $front = Zend_Controller_Front::getInstance();
        $request = new Zend_Controller_Request_Http();
        $front = Zend_Controller_Front::getInstance();
        $front->resetInstance();
        $front->setRequest($request);
        $front->getRouter()->addDefaultRoutes();
        $front->setParam('bootstrap', $this->appliaction->getBootstrap());
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'gsaa.test';

        Zend_Session::$_unitTestEnabled = true;
        $this->_model = new GSAA_Model_LBS_Foursquare();
        $this->_modelName = 'GSAA_Model_LBS_Foursquare';
    }
    protected function tearDown() {
        $this->_model = null;
        parent::tearDown();
    }

    public static function coordsProvider() {
        $array = array(
            array('50.079776', '14.429713'),    // St. Wenceslas statue, Prague
            //array(51.503365, -0.127754),        // 10 Downing St, London
        );
        return $array;
    }

    public static function poisProvider() {
        $array = array(
            array('4b46db8bf964a520f12826e3'),  // Starbucks, Wenceslas square, Prague
            //array('4adf3f57f964a520ad7821e3'),  // Houses of Parliament, London
            //array('43695300f964a5208c291fe3')   // Empire State Building, NY
        );
        return $array;
    }


    public function testGetNearbyVenuesWorks() {
        $modelName = $this->_modelName;
        $result = $this->_model->getNearbyVenues('50.079776', '14.429713', $modelName::RADIUS);
        $this->assertInternalType('array', $result);

        $this->assertTrue(count($result) > 0);

        foreach ($result as $poi) {
           $this->assertInstanceOf('GSAA_Model_POI', $poi);
           $this->assertAttributeEquals($modelName::TYPE, 'type', $poi);
            $this->assertAttributeNotEmpty('id', $poi);
            $this->assertAttributeNotEmpty('name', $poi);
            $this->assertAttributeNotEmpty('lat', $poi);
            $this->assertAttributeNotEmpty('lng', $poi);
            $this->assertAttributeNotEquals(null, 'distance', $poi);
            $this->assertAttributeInternalType('float', 'quality', $poi);
        }
    }

    /**
     * @dataProvider coordsProvider
     */
    public function testWhenRadiusIsGivenVenuesAreInsideBounds($lat, $lng) {
        $radiusArray = array(10, 200, 5000);

        foreach ($radiusArray as $radius) {
            $radiusTolerance = $radius * 2;
            $result = $this->_model->getNearbyVenues($lat, $lng, $radius);

            foreach ($result as $entry) {
                $this->assertTrue($entry->distance < $radiusTolerance, 'Expected venue distance < ' . $radiusTolerance .', but received ' . $entry->distance . ' instead');
            }
        }
    }

    /**
     * @dataProvider coordsProvider
     */
    public function testWhenZeroRadiusIsGivenTheDefaultOneIsUsed($lat, $lng) {
        $modelName = $this->_modelName;
        $radiusTolerance = $modelName::RADIUS * 2;
        $result = $this->_model->getNearbyVenues($lat, $lng, 0);

        foreach ($result as $entry) {
            $this->assertTrue($entry->distance < $radiusTolerance, 'Expected venue distance < ' . $radiusTolerance .', but received ' . $entry->distance . ' instead');
        }
    }

    /**
     * @dataProvider coordsProvider
     */
    public function testWhenOverkillRadiusIsGivenItIsLimitedToTheMaximumOne($lat, $lng) {
        $modelName = $this->_modelName;
        $radiusTolerance = $modelName::RADIUS_MAX * 2;
        $result = $this->_model->getNearbyVenues($lat, $lng, 999999);

        foreach ($result as $entry) {
            $this->assertTrue($entry->distance < $radiusTolerance, 'Expected venue distance < ' . $radiusTolerance .', but received ' . $entry->distance . ' instead');
        }
    }

    public function testWhenSearchingWithoutAndWithoutFilterLimitIsAdjusted() {
        $modelName = $this->_modelName;
        $result = $this->_model->getNearbyVenues(40.706935, -74.010487, 0);
        $this->assertTrue(count($result) <= $modelName::LIMIT_WITHOUT_FILTER);

        $result = $this->_model->getNearbyVenues(40.706935, -74.010487, 0, 'New York');
        $this->assertTrue(count($result) <= $modelName::LIMIT);

    }

    public function testGetVenueDetailWorks() {
        $result = $this->_model->getDetail('4b6fff72f964a520c8022de3');
        $this->assertInstanceOf('GSAA_Model_POI', $result);
    }

    /**
     * @dataProvider poisProvider
     */
    public function testAllVenueDetailDataRetreivedFine($id) {
        $modelName = $this->_modelName;
        $poi = $this->_model->getDetail($id);
        $this->assertInstanceOf('GSAA_Model_POI', $poi);

        $this->assertAttributeEquals($modelName::TYPE, 'type', $poi);
        $this->assertAttributeNotEmpty('id', $poi);
        $this->assertAttributeNotEmpty('name', $poi);
        $this->assertAttributeNotEmpty('lat', $poi);
        $this->assertAttributeNotEmpty('lng', $poi);
        $this->assertAttributeEquals(null, 'distance', $poi);

        $this->assertAttributeInternalType('array', 'links', $poi);
        $this->assertAttributeNotEmpty('links', $poi);

        $this->assertAttributeInternalType('array', 'tips', $poi);
        $this->assertAttributeNotEmpty('tips', $poi);

        $this->assertAttributeInternalType('array', 'photos', $poi);
        $this->assertAttributeNotEmpty('photos', $poi);

        $this->assertAttributeInternalType('array', 'notes', $poi);

        $this->assertAttributeInternalType('array', 'categories', $poi);
        $this->assertAttributeNotEmpty('categories', $poi);

    }

    public function testRequestTokenFailsWithBadCode() {
        $result = $this->_model->requestToken('');
        $this->assertInternalType('null', $result);

        $result = $this->_model->requestToken('bAdCoDe');
        $this->assertInternalType('null', $result);
    }

    public function testCheckTokenReturnsFalseWithBadToken() {
        $result = $this->_model->checkToken('');
        $this->assertSame(false, $result);

        $result = $this->_model->checkToken('BaDtOkEn');
        $this->assertSame(false, $result);
    }

    public function testAuthUrlIsValidUrl() {
        $result = $this->_model->getAuthUrl();
        $modelName = $this->_modelName;
        $this->assertRegExp('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $result);
        $this->assertContains($modelName::OAUTH_URL, $result);
    }

    public function testCheckinFailsWithoutUser() {
        $result = $this->_model->doCheckin('4b46db8bf964a520f12826e3');
        $this->assertInternalType('null', $result);
    }
    public function testGetUserInfoFailsWithoutUser() {
        $result = $this->_model->getUserInfo();
        $this->assertInternalType('null', $result);
    }
    public function testGetFriendsActivityReturnsEmptyArrayWithoutUser() {
        $result = $this->_model->getFriendsActivity();
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }
}