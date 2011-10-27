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
     * @param double $x Latitude
     * @param double $y Longitude
     * @param string $term Search term
     * @param string $category Category id (TODO)
     * @return string JSON
     */
    public function getNearbyVenues($x, $y, $term = null, $category = null) {
        $endpoint = 'venues/search';
        
        $client = $this->_constructClient($endpoint, array('ll' => "$x,$y"));

        $response = $client->request();
        
        // error in response
        if ($response->isError()) {
            return $this->_getEmpty();
        }
        $result = Zend_Json::decode($response->getBody());
        
        // foursquare returned an error
        if ($result['meta']['code'] != 200) {
            // TODO: log $result['meta']['errorType'] and $result['meta']['errorDetail']
            return $this->_getEmpty();
        };
        
        // if code was 200 some non fatal error occured
        if (!empty($result['meta']['errorType'])) {
            // TODO: log $result['meta']['errorType'] and $result['meta']['errorDetail']
        }
        //echo Zend_Json::prettyPrint(Zend_Json::encode($result['response']));
        
        return Zend_Json::encode($result['response']);
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
        
        // set predefined params
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