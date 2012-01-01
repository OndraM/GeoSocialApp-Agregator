<?php
require_once 'application/models/LBS/LbsCommonModel.php';

class LbsFoursquareModelTest extends LbsCommonModel
{
    protected function setUp() {
        parent::setUp();
        $this->_model = new GSAA_Model_LBS_Foursquare();
        $this->_modelName = 'GSAA_Model_LBS_Foursquare';
        $this->_testPoi = '4b46db8bf964a520f12826e3'; // ČVUT FEL (KN), Prague
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

    public static function poisProvider() {
        $array = array(
            array('4b46db8bf964a520f12826e3'),  // Starbucks, Wenceslas square, Prague
            array('4adf3f57f964a520ad7821e3'),  // Houses of Parliament, London
            array('43695300f964a5208c291fe3')   // Empire State Building, NY
        );
        return $array;
    }

}