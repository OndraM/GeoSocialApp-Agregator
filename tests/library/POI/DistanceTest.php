<?php
class DistanceTest extends PHPUnit_Framework_TestCase
{
    protected $poi;

    public function setUp()
    {
        parent::setUp();
        $this->poi = new GSAA_POI_Distance();
    }

    protected function tearDown() {
        unset($this->poi);
        parent::tearDown();
    }

    public function testBasicLongitudeCalculationWorks() {
        $poi = $this->poi;
        $distance = $poi::getDistance(0, 0, 0, 1);
        $distanceOfOneLongitudeDegree = round(6378000 * 2 * M_PI / 360);
        $this->assertEquals($distanceOfOneLongitudeDegree, $distance);
    }
    public function testBasicLatitudeCalculationWorks() {
        $poi = $this->poi;
        $distance = $poi::getDistance(0, 0, 1, 0);
        $distanceOfOneLatitudeDegree = round(6378000 * 2 * M_PI / 360);
        $this->assertEquals($distanceOfOneLatitudeDegree, $distance);
    }
}