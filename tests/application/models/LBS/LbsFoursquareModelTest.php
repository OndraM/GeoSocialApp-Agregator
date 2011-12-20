<?php
class LbsFoursquareModelTest extends PHPUnit_Framework_TestCase
{
    protected $_model;

    protected function setUp() {
        parent::setUp();
        //$this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        //$this->bootstrap->bootstrap();

        Zend_Session::$_unitTestEnabled = true;
        $this->_model = new GSAA_Model_LBS_Foursquare();
    }
    protected function tearDown() {
        $this->_model = null;
        parent::tearDown();
    }

    public static function coordsProvider() {
        $array = array(
            array('50.079776', '14.429713'),    // St. Wenceslas statue, Prague
            array(51.503365, -0.127754),        // 10 Downing St, London
        );
        return $array;
    }


    public function testGetNearbyVenuesWorks() {
        $result = $this->_model->getNearbyVenues('50.079776', '14.429713', GSAA_Model_LBS_Foursquare::RADIUS);
        $this->assertInternalType('array', $result);

        $this->assertTrue(count($result) > 0);

        foreach ($result as $entry) {
           $this->assertInstanceOf('GSAA_Model_POI', $entry);
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
        $radiusTolerance = GSAA_Model_LBS_Foursquare::RADIUS * 2;
        $result = $this->_model->getNearbyVenues($lat, $lng, 0);

        foreach ($result as $entry) {
            $this->assertTrue($entry->distance < $radiusTolerance, 'Expected venue distance < ' . $radiusTolerance .', but received ' . $entry->distance . ' instead');
        }
    }

    /**
     * @dataProvider coordsProvider
     */
    public function testWhenOverkillRadiusIsGivenItIsLimitedToTheMaximumOne($lat, $lng) {
        $radiusTolerance = GSAA_Model_LBS_Foursquare::RADIUS_MAX * 2;
        $result = $this->_model->getNearbyVenues($lat, $lng, 999999);

        foreach ($result as $entry) {
            $this->assertTrue($entry->distance < $radiusTolerance, 'Expected venue distance < ' . $radiusTolerance .', but received ' . $entry->distance . ' instead');
        }
    }

    public function testWhenSearchingWithoutAndWithoutFilterLimitIsAdjusted() {
        $result = $this->_model->getNearbyVenues(40.706935, -74.010487, 0);
        $this->assertTrue(count($result) <= GSAA_Model_LBS_Foursquare::LIMIT_WITHOUT_FILTER);

        $result = $this->_model->getNearbyVenues(40.706935, -74.010487, 0, 'New York');
        $this->assertTrue(count($result) <= GSAA_Model_LBS_Foursquare::LIMIT);

    }
}