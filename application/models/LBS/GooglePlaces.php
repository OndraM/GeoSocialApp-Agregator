<?php

class GSAA_Model_LBS_GooglePlaces extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'https://maps.googleapis.com/maps/api/place';
    const PUBLIC_URL = 'FIXME';
    const CLIENT_ID = '702732417915.apps.googleusercontent.com';
    const CLIENT_SECRET = 'j90OM1fvYso4p0haoZbZPUoY';
    const CLIENT_KEY = 'AIzaSyAtSx0_q5JPDtU0GPzlgSi5ZkRvJ1Jmy24';    
    const LIMIT = 30; // Though Google Places return just 20 POIs at once...
    const TYPE = 'gg';
    
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
        $endpoint = '/search/json';
        if ($radius > self::RADIUS_MAX) {
            $radius = self::RADIUS_MAX;
        }
        $limit = self::LIMIT_WITHOUT_FILTER;
        if ($term || $category) {
            $limit = self::LIMIT;
        }
        
        $client = $this->_constructClient($endpoint,
                                        array(  'location'      => "$lat,$long",
                                                'sensor'        => 'false',
                                                'name'          => $term,
                                                // 'categoryId'    => $category // TODO category mapping
                                                'types'         => 'establishment',
                                                'radius'        => ($radius > 0 ? $radius : self::RADIUS)                                                
                                            ));

        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return array();
        }
        $result = Zend_Json::decode($response->getBody());
        
        // returned an error
        if ($result['status'] != 'OK') {
            return array();
        };
        
        // cut unwanted part of result (longer than $limit)
        if (count($result['results']) > $limit) {
            array_splice($result['results'], $limit);
        }
                
        // Load venues into array of GSAA_Model_POI        
        $pois = array();
        foreach ($result['results'] as $entry) {
            // replaced by 'types'         => 'establishment'
            /*if (in_array('political', $entry['types'])) { // dont include political venues
                //continue;
            }*/
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            if ($this->getDistance($lat, $long,
                                    $entry['geometry']['location']['lat'], $entry['geometry']['location']['lng']) > $radius*2) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $poi->id        = $entry['id'];
            //$poi->url       = self::PUBLIC_URL . "venue/" . $entry['id'];
            $poi->reference = $entry['reference'];
            $poi->lat     = $entry['geometry']['location']['lat'];
            $poi->lng     = $entry['geometry']['location']['lng'];
            if (isset($entry['vicinity'])) {
                $poi->address = $entry['vicinity'];
            }
            $poi->distance = $this->getDistance($lat, $long, $poi->lat, $poi->lng);
            
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
        $queryParams['key'] = self::CLIENT_KEY;
        
        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);
        
        
        return $client;
    }
}