<?php
require_once 'application/models/LBS/LbsCommonModel.php';

class LbsFacebookModelTest extends LbsCommonModel
{
    protected function setUp() {
        parent::setUp();
        $this->_model = new GSAA_Model_LBS_Facebook();
        $this->_modelName = 'GSAA_Model_LBS_Facebook';
        $this->_testVenue = '109610852438723'; // Mosaic House, Prague
    }
    protected function tearDown() {
        $this->_model = null;
        parent::tearDown();
    }

    public static function coordsProvider() {
        $array = array(
            array('50.079776', '14.429713'),    // St. Wenceslas statue, Prague
            array(51.500613, -0.126819),        // Parliament square, London
        );
        return $array;
    }

    public static function poisProvider() {
        $array = array(
            array('114791421933654'),   // Restaurace Kozlova Almara, Prague
            array('6597757578'),        // Royal Opera House, London
        );
        return $array;
    }

    protected function _testTips($poi) {
        $this->assertAttributeInternalType('array', 'tips', $poi);
    }
    protected function _testCategories($poi) {
        $this->assertAttributeInternalType('array', 'categories', $poi);
    }

}