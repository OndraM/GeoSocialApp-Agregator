<?php
class AggregatedPoiModelTest extends PHPUnit_Framework_TestCase
{
    protected $_aPoi;
    protected static $_aPoiPersistent = 0;

    protected function setUp() {
        parent::setUp();
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->bootstrap->bootstrap();

        // kep content amongst tests
        if (!self::$_aPoiPersistent) {
            self::$_aPoiPersistent = new GSAA_Model_AggregatedPOI();
        }

        $this->_aPoi = new GSAA_Model_AggregatedPOI();
    }
    protected function tearDown() {
        $this->_aPoi = null;
        parent::tearDown();
    }

    public static function poiProvider() {
        $bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $bootstrap->bootstrap();
        //require_once (APPLICATION_PATH . '/models/POI.php');
        $pois =
            array(
                'fb' =>
                    array(
                        'id'    => '123456',
                        'name'  => 'Venue from facebook',
                        'lat'   => 12.34,
                        'lng'   => 56.78,
                        'address' => 'Address from facebook',
                        'links' => array(array('First FB Web' => 'http://first.com'),
                                         array('Second FB Web' => 'http://second.com'))
                    ),
                'fq' =>
                    array(
                        'id'    => '345678',
                        'name'  => 'Venue from foursquare',
                        'lat'   => 11.11,
                        'lng'   => 22.22,
                        'address' => 'Address from Foursquare',
                        'links' => array(array('Twitter' => 'http://twitter.com/foursquare'))
                    ),
                'gg' =>
                    array(
                        'id'    => '678901',
                        'reference' => 'ASDFGHJKLASDFGHJ',
                        'name'  => 'Venue from Google Places',
                        'lat'   => 11.11,
                        'lng'   => 22.22
                    ),
                );
        $return = array();
        foreach ($pois as $poiType => $poiData) {
            $tmpPoi = new GSAA_Model_POI($poiType);
            $tmpPoi->id         = $poiData['id'];
            if (isset($poiData['reference']))
                $tmpPoi->reference     = $poiData['reference'];
            $tmpPoi->name       = $poiData['name'];
            $tmpPoi->lat        = $poiData['lat'];
            $tmpPoi->lng        = $poiData['lng'];
            if (isset($poiData['links']))
                $tmpPoi->links      = $poiData['links'];
            if (isset($poiData['address']))
                $tmpPoi->address      = $poiData['address'];

            $return[] = array($tmpPoi);
        }
        return $return;
    }

    /**
     * @dataProvider poiProvider
     */
    public function testAddingSinglePoiAndRetrievingItsDataWorks($poi) {
        $this->_aPoi->addPoi($poi);
        $this->assertEquals(1, count($this->_aPoi->getPois()));
        $this->assertEquals($poi->id, $this->_aPoi->getField('id'));
        $this->assertEquals($poi->name, $this->_aPoi->getField('name'));
        $this->assertEquals($poi->lat, $this->_aPoi->getField('lat'));
        $this->assertEquals($poi->lng, $this->_aPoi->getField('lng'));
        $this->assertEquals($poi->address, $this->_aPoi->getField('address'));
    }
    /**
     * @dataProvider poiProvider
     */
    public function testAddingMultiplePoisWorks($poi) {
        $poisCount = count(self::$_aPoiPersistent->getPois());
        self::$_aPoiPersistent->addPoi($poi);

        $this->assertEquals($poisCount+1, count(self::$_aPoiPersistent->getPois()));

        // default types indexing works
        $this->assertTrue(in_array($poi->type, self::$_aPoiPersistent->getTypes()));

        // type indexed by id (or by reference) is ok
        $this->assertTrue(in_array($poi->type, self::$_aPoiPersistent->getTypes(true)));
        $this->assertTrue(array_key_exists($poi->id, self::$_aPoiPersistent->getTypes(true))
                            || array_key_exists($poi->reference, self::$_aPoiPersistent->getTypes(true)));

        // POI contains data of current POI
        $found = false;
        foreach (self::$_aPoiPersistent->getFieldAll('id') as $id) {
            if (key($id) == $poi->type && current($id) == $poi->id) $found = true;
        }
        $this->assertTrue($found);

        // POI contains array data of current POI
        foreach ($poi->links as $link) {
            $foundArray = false;
            foreach (self::$_aPoiPersistent->getFieldAll('links') as $linksArray) {
                if (isset($linksArray[$poi->type])
                        && current(current($linksArray)) ==  current($link)
                        && key(current($linksArray)) == key($link)) {
                    $foundArray = true;
                }
            }
            $this->assertTrue($foundArray); // each value must be found
        }
    }

    /**
     * @depends testAddingMultiplePoisWorks
     */
    public function testPoisAreSortedByPriority() {
        $previous = null;
        foreach (self::$_aPoiPersistent->getPois() as $poi) {
            // previous priority must always be larger than priority of current POI
            if ($previous)
                $this->assertTrue($previous > $poi->getPriority());
            $previous = $poi->getPriority();
        }
    }

    public function testEmptyAggregatedPoiIsEmpty() {
        $this->assertEquals(0, count($this->_aPoi->getPois()));

        $this->assertSame(array(), $this->_aPoi->getTypes());

        $this->assertSame(null, $this->_aPoi->getField('id'));
        $this->assertSame(array(), $this->_aPoi->getFieldAll('id'));
    }

    public function testGetDetailUrlParamsWorks() {
        $fbPoi = new GSAA_Model_POI('fb');
        $fbPoi->id = 'idOfFbPoi';
        $this->_aPoi->addPoi($fbPoi);

        $ggPoi = new GSAA_Model_POI('gg');
        $ggPoi->id = 'idOfGgPoi';
        $ggPoi->reference = 'referenceOfGgPoi';
        $this->_aPoi->addPoi($ggPoi);

        $fqPoi = new GSAA_Model_POI('fq');
        $fqPoi->id = 'idOfFqPoi';
        $this->_aPoi->addPoi($fqPoi);

        $detailUrlParams = $this->_aPoi->getDetailUrlParams();
        $this->assertTrue(array_key_exists($fbPoi->id, $detailUrlParams));
        $this->assertEquals($detailUrlParams[$fbPoi->id], $fbPoi->type);

        $this->assertTrue(array_key_exists($ggPoi->reference, $detailUrlParams));
        $this->assertEquals($detailUrlParams[$ggPoi->reference], $ggPoi->type);

        $this->assertTrue(array_key_exists($fqPoi->id, $detailUrlParams));
        $this->assertEquals($detailUrlParams[$fqPoi->id], $fqPoi->type);
    }

    public function testReverseGeocodingRequireLatAndLng() {
        $this->_aPoi->addPoi(new GSAA_Model_POI('fq'));
        $addressgeocoded = $this->_aPoi->getField('address', true);
        $this->assertSame(null, $addressgeocoded);
    }

    public function testReverseGeocodingWorks() {
        $poi = new GSAA_Model_POI('fq');
        $poi->lat = 51.50331;
        $poi->lng = -0.127641;
        $this->_aPoi->addPoi($poi);

        $addressgeocoded = $this->_aPoi->getField('address', true);
        $this->assertEquals('10 Downing St, Westminster, SW1A 2, Velká Británie', $addressgeocoded);
    }

    public function testReverseGeocodingWithNotExistingAddressReturnsNull() {
        $poi = new GSAA_Model_POI('fq');
        $poi->lat = 0;
        $poi->lng = 0;
        $this->_aPoi->addPoi($poi);

        $addressgeocoded = $this->_aPoi->getField('address', true);
        $this->assertSame(null, $addressgeocoded);
    }

    public function testQualityCalculationWorks() {
        $this->_aPoi->addPoi(new GSAA_Model_POI('fb')); // default quality (= 0)

        $poi1 = new GSAA_Model_POI('fq');
        $poi1->quality = 5;
        $this->_aPoi->addPoi($poi1);

        $poi2 = new GSAA_Model_POI('gg');
        $poi2->quality = -2;
        $this->_aPoi->addPoi($poi2);

        $quality = $this->_aPoi->getField('quality');
        $this->assertEquals(1, $quality); // 0 + 5 + (-2) = 3 / 3 = 1
    }

}