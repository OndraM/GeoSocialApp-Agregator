<?php

class GSAA_Model_LBS_Foursquare extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'https://api.foursquare.com/v2';
    const PUBLIC_URL = 'https://foursquare.com';
    const CLIENT_ID = 'QJ52TX1UJUBCPJ3DMOWS52I5MK5WJTDD3ZGCDFFWHWISUQ3K';
    const CLIENT_SECRET = 'XFCVWF3HNGWVQWZJQC32ZMYBUHTGNKFR4IKJUHMYJNE2ZFDW';
    const LIMIT = 30;
    const TYPE = 'fq';
    
    // Date of 4SQ API is verified to be up-to-date.
    const DATEVERIFIED = '20111027';
    
    public function init() {
        // TODO: set client properties?
        ;
    }

    /**
     * Funtion to get nearby venues.
     * 
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @param string $category Category id (TODO)
     * @return array Array with venues
     */
    public function getNearbyVenues($lat, $long, $radius, $term = null, $category = null) {
        $endpoint = '/venues/search';
        
        $client = $this->_constructClient($endpoint,
                                        array(  'll'            => "$lat,$long",
                                                'query'         => $term,
                                                // 'categoryId'    => $category // TODO category mapping
                                                'limit'         => self::LIMIT,
                                                'radius'        => ($radius > 0 ? $radius : self::RADIUS)
                                            ));

        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return array();
        }
        $result = Zend_Json::decode($response->getBody());
        
        // foursquare returned an error
        if ($result['meta']['code'] != 200) {
            // TODO: log $result['meta']['errorType'] and $result['meta']['errorDetail']
            return array();
        };
        
        // if code was 200 some non fatal error occured
        if (!empty($result['meta']['errorType'])) {
            // TODO: log $result['meta']['errorType'] and $result['meta']['errorDetail']
        }
        
        // Load venues into array of GSAA_Model_POI        
        $pois = array();
        foreach ($result['response']['venues'] as $entry) {
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            if ($this->_getDistance($lat, $long,
                                    $entry['location']['lat'], $entry['location']['lng']) > $radius*2) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $poi->id        = $entry['id'];
            $poi->url       = self::PUBLIC_URL . "venue/" . $entry['id'];
            $poi->lat     = $entry['location']['lat'];
            $poi->lng     = $entry['location']['lng'];
            if (isset($entry['location']['distance'])) {
                $poi->distance = $entry['location']['distance'];
            } else {
                $poi->distance = $this->_getDistance($lat, $long, $poi->lat, $poi->lng);
            }
            if (isset($entry['location']['address']))
                $poi->address = $entry['location']['address'];
            //if (isset($entry['location']['postalCode']))
            //  $poi->postalCode = $entry['location']['postalCode'];
            if (isset($entry['location']['city']))
                $poi->address .= (!empty($poi->address) ? ', ' : '')
                            . $entry['location']['city'];
            //if (isset($entry['location']['country']))
            //    $poi->country = $entry['location']['country'];
            
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
        $queryParams['client_id'] = self::CLIENT_ID;
        $queryParams['client_secret'] = self::CLIENT_SECRET;
        $queryParams['v'] = self::DATEVERIFIED;
        
        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);
        
        
        return $client;
    }
}