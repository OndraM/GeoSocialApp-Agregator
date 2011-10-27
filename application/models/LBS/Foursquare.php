<?php

class GSAA_Model_LBS_Foursquare extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'https://api.foursquare.com/v2/';
    const CLIENT_ID = 'QJ52TX1UJUBCPJ3DMOWS52I5MK5WJTDD3ZGCDFFWHWISUQ3K';
    const CLIENT_SECRET = 'XFCVWF3HNGWVQWZJQC32ZMYBUHTGNKFR4IKJUHMYJNE2ZFDW';
    
    // Date of 4SQ API is verified to be up-to-date.
    const DATEVERIFIED = '20111027';
    
    public function init() {
        // TODO: set client properties?
        ;
    }

    /**
     * Abstract funtion to get nearby venues.
     * 
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param string $term Search term
     * @param string $category Category id (TODO)
     * @return array Array with venues
     */
    public function getNearbyVenues($lat, $long, $term = null, $category = null) {
        $endpoint = 'venues/search';
        
        $client = $this->_constructClient($endpoint,
                                        array(  'll'            => "$lat,$long",
                                                'query'         => $term,
                                                // 'categoryId'    => $category // TODO category mapping
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
        return $result['response'];
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