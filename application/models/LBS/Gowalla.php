<?php

class GSAA_Model_LBS_Gowalla extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'http://api.gowalla.com';
    const PUBLIC_URL = 'https://gowalla.com';
    const CLIENT_ID = '6f695b01d109467b9515c1d7457377bc';
    const CLIENT_SECRET = 'b31113a8961d41b19040a2d4b4fc01a1';
    const LIMIT = 30;
    const TYPE = 'gw';
        
    public function init() {
        // TODO: set client properties?
        ;
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
        $endpoint = '/spots';
        
        $client = $this->_constructClient($endpoint,
                                        array(  'lat'            => $lat,
                                                'lng'           => $long,
                                                'q'              => $term,
                                                // 'categoryId'    => $category // TODO category mapping
                                                'radius'        => ($radius > 0 ? $radius : self::RADIUS)
                                            ));
        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return array();
        }
        $result = Zend_Json::decode($response->getBody());       
        
        if ($result['total_results'] == 0) {
            return array();
        }
        
        // cut unwanted part of result (longer than self::LIMIT)
        if ($result['total_results'] > self::LIMIT) {
            array_splice($result['spots'], self::LIMIT);
        }
        
        
        // Load venues into array of GSAA_Model_POI        
        $pois = array();
        foreach ($result['spots'] as $entry) {
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            if ($this->_getDistance($lat, $long, $entry['lat'], $entry['lng']) > $radius) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $urlExploded    = explode('/',  $entry['url']);
            $poi->id        = $urlExploded[2];
            $poi->url       = self::PUBLIC_URL . $entry['url'];
            $poi->lat     = $entry['lat'];
            $poi->lng     = $entry['lng'];
            if (isset($entry['location']['address']))
                $poi->address    = $entry['address']['locality'];
            
            $poi->distance = $this->_getDistance($lat, $long, $poi->lat, $poi->lng);
            
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
                
        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);
        $client->setHeaders(array(
                    'X-Gowalla-API-Key' => self::CLIENT_ID,
                    'Accept'            => 'application/json'
                ));
        
        
        return $client;
    }
}