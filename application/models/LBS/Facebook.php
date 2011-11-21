<?php

class GSAA_Model_LBS_Facebook extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'https://graph.facebook.com';
    const PUBLIC_URL = 'https://facebook.com';
    const CLIENT_ID = '110157905740540';
    const CLIENT_SECRET = 'fc86a9bb2486f28f3e3866a5d8ff67ef';
    const ACCESS_TOKEN = 'AAABkMCLXQvwBABj8w2rr4bo7jLBgvntfZCio1idZCGJbDzFqw8PdpXYdn4JvrYl51rOcpjymGWM4Sd9KACFAsZAR5ZCpT8gZD';
    const LIMIT = 30;
    const TYPE = 'fb';
    
    public function init() {
        /*Zend_Loader::loadFile("facebook.php", null, true);
        $config = array(
            'appId'   => self::CLIENT_ID,
            'secret'  => self::CLIENT_SECRET
        );
        $this->facebook = new Facebook($config);*/
    }

    /**
     * Function to get nearby venues.
     * 
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @param string $category Category id (TODO)
     * @return array Array with venues
     */
    public function getNearbyVenues($lat, $long, $radius, $term = null, $category = null) {
        $endpoint = '/search';
        if ($radius > self::RADIUS_MAX) {
            $radius = self::RADIUS_MAX;
        }
        
        $queryParams = array('type'     => 'place',
                             'center'   => "$lat,$long",                            
                             // 'categoryId'    => $category // TODO category mapping
                             'limit'    => self::LIMIT,
                             'distance' => ($radius > 0 ? $radius : self::RADIUS)
                             );
        if ($term) {
            $queryParams['q'] = $term;
        }
        $client = $this->_constructClient($endpoint, $queryParams);

        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return array();
        }
        $result = Zend_Json::decode($response->getBody());
        
        // Load venues into array of GSAA_Model_POI        
        $pois = array();
        foreach ($result['data'] as $entry) {
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            // in fact the distance parametr do the same on FB side (and actually works), so this is kind of redundant check
            if ($this->_getDistance($lat, $long,
                                    $entry['location']['latitude'], $entry['location']['longitude']) > $radius*2) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $poi->id        = $entry['id'];
            $poi->url       = self::PUBLIC_URL . "/" . $entry['id'];
            $poi->lat     = $entry['location']['latitude'];
            $poi->lng     = $entry['location']['longitude'];
            $poi->distance = $this->_getDistance($lat, $long, $poi->lat, $poi->lng);

            if (isset($entry['location']['street']))
                $poi->address = $entry['location']['street'];
            if (isset($entry['location']['city']))
                $poi->address .= (!empty($poi->address) ? ', ' : '')
                            . $entry['location']['city'];
            
            $pois[] = $poi;
        }

        return $pois;
    }
    
    /**
     * Construct Zend_Http_Client object.
     * 
     * @param type $endpoint
     * @param type $queryParams 
     * @param type $clientConfig
     * @return Zend_Http_Client
     */
    
    protected function _constructClient($endpoint, $queryParams = array(), $clientConfig = array()) {
        $client = new Zend_Http_Client();
        
        // add predefined params
        $queryParams['access_token'] = self::ACCESS_TOKEN;
        
        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);        
        
        return $client;
    }
    
}