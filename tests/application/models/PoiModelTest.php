<?php
class PoiModelTest extends PHPUnit_Framework_TestCase
{

    protected function setUp() {
        parent::setUp();
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->bootstrap->bootstrap();
    }
    protected function tearDown() {
        parent::tearDown();
    }

    public function poiTypesProvider() {
        $bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $var = $bootstrap->getOption('var');
        $return = array();

        foreach ($var['services'] as $serviceType => $serviceArray) {
            $return[] = array($serviceType, $serviceArray['priority']);
        }
        return $return;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyConstructorFails() {
        new GSAA_Model_POI('');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorFailsWithNotExistingType() {
        new GSAA_Model_POI('foobar');
        new GSAA_Model_POI('bazbar');
    }

    /**
     * @dataProvider poiTypesProvider
     */
    public function testInitWorks($serviceType, $priority) {
        $poi = new GSAA_Model_POI($serviceType);
        $this->assertInstanceOf('GSAA_Model_POI', $poi);
    }

    /**
     * @dataProvider poiTypesProvider
     */
    public function testPriorityEquals($serviceType, $priority) {
        $poi = new GSAA_Model_POI($serviceType);
        $this->assertEquals($priority, $poi->getPriority());
    }

}