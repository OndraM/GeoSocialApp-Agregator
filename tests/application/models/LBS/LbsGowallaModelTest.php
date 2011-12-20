<?php
require_once 'application/models/LBS/LbsCommonModel.php';

class LbsGowallaModelTest extends LbsCommonModel
{
    protected function setUp() {
        parent::setUp();
        $this->_model = new GSAA_Model_LBS_Gowalla();
        $this->_modelName = 'GSAA_Model_LBS_Gowalla';
        $this->_testVenue = '1213044'; // Mosaic House, Prague
    }
    protected function tearDown() {
        $this->_model = null;
        parent::tearDown();
    }

    public static function coordsProvider() {
        $array = array(
            array('50.079776', '14.429713'),    // St. Wenceslas statue, Prague
            //array(51.500613, -0.126819),        // Parliament square, London
        );
        return $array;
    }

    public static function poisProvider() {
        $array = array(
            array('1107156'),   // Friends Coffee House, Prague
            //array('22089'),     // Houses of Parliament, London
            //array('12313')      // Empire State Building, NY
        );
        return $array;
    }

}