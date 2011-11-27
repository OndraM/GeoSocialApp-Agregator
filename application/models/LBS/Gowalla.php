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
     * @return array Array of GSAA_Model_POI
     */
    public function getNearbyVenues($lat, $long, $radius, $term = null, $category = null) {
        $endpoint = '/spots';
        if ($radius > self::RADIUS_MAX) {
            $radius = self::RADIUS_MAX;
        }
        $limit = self::LIMIT_WITHOUT_FILTER;
        if ($term || $category) {
            $limit = self::LIMIT;
        }
        
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
        
        // cut unwanted part of result (longer than $limit)
        if ($result['total_results'] > $limit) {
            array_splice($result['spots'], $limit);
        }        
        
        // Load venues into array of GSAA_Model_POI        
        $pois = array();
        foreach ($result['spots'] as $entry) {
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            if ($this->getDistance($lat, $long, $entry['lat'], $entry['lng']) > $radius) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $urlExploded    = explode('/',  $entry['url']);
            $poi->id        = $urlExploded[2];
            $poi->url       = self::PUBLIC_URL . $entry['url'];
            $poi->lat       = $entry['lat'];
            $poi->lng       = $entry['lng'];
            if (isset($entry['location']['address']))
                $poi->address    = $entry['address']['locality'];
            
            $poi->distance = $this->getDistance($lat, $long, $poi->lat, $poi->lng);
            
            $pois[] = $poi;
        }
        return $pois;
    }
    
    /**
     * Get full detail of venue.
     * 
     * @param string $id Venue ID
     * @return GSAA_Model_POI
     */
    public function getDetail($id) {
        $endpoint = '/spots';
        
        $client = $this->_constructClient($endpoint . '/' . $id);
        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return array();
        }
        $entry = Zend_Json::decode($response->getBody());       
        
        $poi = new GSAA_Model_POI();
        
        $poi->type      = self::TYPE;
        $poi->name      = $entry['name'];
        $urlExploded    = explode('/',  $entry['url']);
        $poi->id        = $urlExploded[2];
        $poi->url       = self::PUBLIC_URL . $entry['url'];
        $poi->lat       = $entry['lat'];
        $poi->lng       = $entry['lng'];
        if (isset($entry['location']['address']))
            $poi->address    = $entry['address']['locality'];

        if (isset($entry['phone_number']))
            $poi->phone = $entry['phone_number'];
        
        if (isset($entry['description']))
            $poi->description = trim($entry['description']);
        
        /*
         * Links
         */
        if (isset($entry['websites'])) {
            foreach ($entry['websites'] as $websiteIndex => $website)
            $poi->links["Website" . ($websiteIndex == 0 ? '' : $websiteIndex+1)] = $website;
        }
        if (isset($entry['twitter_username'])) // twitter account
            $poi->links["Twitter"] = "http://twitter.com/" . $entry['twitter_username'];

        
        /**
         * TODO photos? See photos_count
         */
        
        /*
         * Add tips (aka highlights)
         */
        $poi->tips = array();
        // TODO
        
        return $poi;
        
    }
    
    /**
     * Construct Zend_Http_Client object.
     * 
     * @param string $endpoint
     * @param array $queryParams 
     * @param array $clientConfig
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