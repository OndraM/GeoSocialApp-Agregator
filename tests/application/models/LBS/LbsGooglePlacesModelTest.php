<?php
require_once 'application/models/LBS/LbsCommonModel.php';

class LbsGooglePlacesModelTest extends LbsCommonModel
{
    protected function setUp() {
        parent::setUp();
        $this->_model = new GSAA_Model_LBS_GooglePlaces();
        $this->_modelName = 'GSAA_Model_LBS_GooglePlaces';
        $this->_testVenue = 'CnRwAAAARIae4C4G0iGNHTwbJD89RX9uOPnCo5i7IuqhgTtDEqzXqTOHOz3DDwkm4tmFhSNcpfmNsYq0aOHzLSAOZ63GgqqaFGd6lte3kgopW22x2fiaIb2V-Df27we_bTZKRL7fr2kwv6S3GRzfEmMOSl9UJxIQNTRktNxRbPxb0vhiQsA91BoUuLdXWkqWjBzYibPkks0dUtB13KY'; // Restaurace Kozlova Almara, Prague
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
            array('CnRvAAAAYPLLkzLY9xwGJmB9rFyzZHaE3mveJwu8sqSX96fss7iI6kRZLovwX2-ZHycsDTQNvrWuM7YWHU9aWxBvH06lwhV4OkEtHhJEsuioEW_9kWBV6qz9RGmyPXv9JkMQZF99GsRpVajjUrUS6eYHVyEE3hIQ54fiFdwEmdXYr4DR9ErSbBoUbv-qGdfZ1ClPAo-aBatZtaMw9fQ'),   // NYSE, New York
            array('CnRoAAAAiDlxboy--BDZZa48y_73YaDj8qTt42Z1b3KafzwbJ30b6uCa4r0z8bzzQa2469uuAOt4dXiWWsVuCs9Nwbw_QOnkGaxh7xXuiitpoA6KVpgpo-kU9yQNcU7zNHH0JoKVPHZXX7Ks-r5hZyhnkSfhKRIQN4NZaX19ib5s8Kn-f4awUhoUw4cjk7o44tHsbTX5sMSwGozBqGE'),   // Buckingham Palace, London
        );
        return $array;
    }

    protected function _testTips($poi) {
        $this->assertAttributeInternalType('array', 'tips', $poi);
    }
    protected function _testPhotos($poi) {
        $this->assertAttributeInternalType('array', 'photos', $poi);
    }
    protected function _testCategories($poi) {
        $this->assertAttributeInternalType('array', 'categories', $poi);
    }

}