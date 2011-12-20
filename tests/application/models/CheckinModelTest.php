<?php
class CheckinModelTest extends PHPUnit_Framework_TestCase
{

    protected function setUp() {
        parent::setUp();
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->bootstrap->bootstrap();
    }
    protected function tearDown() {
        parent::tearDown();
    }

    public static function poiTypesProvider() {
        $bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $var = $bootstrap->getOption('var');
        $return = array();

        foreach ($var['services'] as $serviceType => $serviceArray) {
            $return[] = array($serviceType);
        }
        return $return;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyConstructorFails(){
        new GSAA_Model_Checkin('', 0);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorFailsWithNotExistingType(){
        new GSAA_Model_POI('foobar', 0);
        new GSAA_Model_POI('bazbar', 0);
    }

    /**
     * @dataProvider poiTypesProvider
     * @expectedException InvalidArgumentException
     */
    public function testConstructorFailsWithWrongStringTimestamp($serviceType) {
        new GSAA_Model_Checkin($serviceType, 'foo');
    }
    /**
     * @dataProvider poiTypesProvider
     * @expectedException InvalidArgumentException
     */
    public function testConstructorFailsWithWrongEmptyStringTimestamp($serviceType) {
        new GSAA_Model_Checkin($serviceType, '');
    }

    /**
     * @dataProvider poiTypesProvider
     */
    public function testInitWorks($serviceType) {
        $checkin = new GSAA_Model_Checkin($serviceType, Zend_Date::now()->getTimestamp());
        $this->assertInstanceOf('GSAA_Model_Checkin', $checkin);
    }

}