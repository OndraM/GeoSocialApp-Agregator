<?php

class GSAA_Model_LBS_Foursquare extends GSAA_Model_LBS_Abstract 
{
    const SERVICE_URL = 'https://api.foursquare.com/v2';
    const PUBLIC_URL = 'https://foursquare.com';
    const CLIENT_ID = 'QJ52TX1UJUBCPJ3DMOWS52I5MK5WJTDD3ZGCDFFWHWISUQ3K';
    const CLIENT_SECRET = 'XFCVWF3HNGWVQWZJQC32ZMYBUHTGNKFR4IKJUHMYJNE2ZFDW';
    const LIMIT = 30;
    const RADIUS_MAX = 100000;
    const TYPE = 'fq';
    
    
    // Date of 4SQ API is verified to be up-to-date.
    const DATEVERIFIED = '20111027';
    
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
        $endpoint = '/venues/search';
        if ($radius > self::RADIUS_MAX) {
            $radius = self::RADIUS_MAX;
        }
        $limit = self::LIMIT_WITHOUT_FILTER;
        if ($term || $category) {
            $limit = self::LIMIT;
        }

        $client = $this->_constructClient($endpoint,
                                        array(  'll'            => "$lat,$long",
                                                'query'         => $term,
                                                // 'categoryId'    => $category // TODO category mapping
                                                'limit'         => $limit,
                                                'radius'        => ($radius > 0 ? $radius : self::RADIUS)
                                            ));

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return array();
        }        
        
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
            if ($this->getDistance($lat, $long,
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
                $poi->distance = $this->getDistance($lat, $long, $poi->lat, $poi->lng);
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
     * Get full detail of venue.
     * 
     * @param string $id Venue ID
     * @return GSAA_Model_POI
     */
    public function getDetail($id) {
        $endpoint = '/venues';
        $client = $this->_constructClient($endpoint . '/' . $id);
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }
        
        // error in response
        if ($response->isError()) return;
        
        $result = Zend_Json::decode($response->getBody());        
        
        // foursquare returned an error
        if ($result['meta']['code'] != 200) return;
        
        $entry = $result['response']['venue'];
        
        $poi = new GSAA_Model_POI();
        
        $poi->type      = self::TYPE;
        $poi->name      = $entry['name'];
        $poi->id        = $entry['id'];
        $poi->url       = $entry['canonicalUrl'];
        $poi->lat       = $entry['location']['lat'];
        $poi->lng       = $entry['location']['lng'];
        
        if (isset($entry['location']['address']))
            $poi->address = $entry['location']['address'];
        if (isset($entry['location']['crossStreet']))
            $poi->address .= (!empty($poi->address) ? ' ' : '')
                        . '(' . $entry['location']['crossStreet'] . ')';
        if (isset($entry['location']['postalCode']))
            $poi->address .= (!empty($poi->address) ? ', ' : '')
                        . $entry['location']['postalCode'];
        if (isset($entry['location']['city']))
            $poi->address .= (!empty($poi->address) ? ', ' : '')
                        . $entry['location']['city'];        
        
        if (isset($entry['contact']['formattedPhone']))
            $poi->phone = $entry['contact']['formattedPhone'];
        
        if (isset($entry['description']))
            $poi->description = trim($entry['description']);
        
        /*
         * Links
         */
        if (isset($entry['url'])) // Website
            $poi->links["Website"] = (strncmp($entry['url'], 'http', 4) == 0 ? '' : 'http://') . $entry['url'];
        if (isset($entry['contact']['twitter'])) // twitter account
            $poi->links["Twitter"] = "http://twitter.com/" . $entry['contact']['twitter'];
        

        /*
         * Add photos
         */
        $clientPhotos = $this->_constructClient($endpoint . '/' . $id . '/photos',
                                                array('group' => 'venue'));
        try {
            $responsePhotos = $clientPhotos->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }
        
        // error in response
        if ($responsePhotos->isError()) return;
        
        $resultPhotos = Zend_Json::decode($responsePhotos->getBody());        
        // foursquare returned an error
        if ($resultPhotos['meta']['code'] != 200) return;
        
        $entryPhotos = $resultPhotos['response']['photos'];
        
        if (count($entryPhotos['items']) > 0) {
            foreach ($entryPhotos['items'] as $photo) {
                $thumbUrl = null;
                // find apropriate thumbnail size 
                foreach ($photo['sizes']['items'] as $sizes) {
                    if ($sizes['height'] == 100) {
                        $thumbUrl = $sizes['url'];
                        break;
                    }
                }
                $tmpPhoto = array(
                    'url'   => $photo['url'],
                    'thumbnail' => $thumbUrl,
                    'id'    => $photo['id'],
                    'date'  => $photo['createdAt'],
                    'title' => '' // TODO? But $photo['tip']['text'] is never present in venue photos...
                );
                // check whether image really exists - do HEAD request for each of them
                $tmpClient = new Zend_Http_Client($tmpPhoto['thumbnail']);
                try {
                    if ($tmpClient->request('HEAD')->isSuccessful()) $poi->photos[] = $tmpPhoto;
                } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                    // don't add
                }
                
            }
        }
        
        /*
         * Add tips
         */
        $poi->tips = array();
        $clientTips = $this->_constructClient($endpoint . '/' . $id . '/tips',
                                              array('sort' => 'popular',
                                                    'limit' => 100
                                                  ));
        $responseTips = $clientTips->request();
        
        // error in response
        if ($responseTips->isError()) return;
        
        $resultTips = Zend_Json::decode($responseTips->getBody());        
        // foursquare returned an error
        if ($resultTips['meta']['code'] != 200) return;
        
        $entryTips = $resultTips['response']['tips'];        
       
        if (count($entryTips['items']) > 0) {
            foreach ($entryTips['items'] as $tip) {
                $tmpTip = array(
                    'id'    => $tip['id'],
                    'text'  => $tip['text'],
                    'date'  => $tip['createdAt']
                );
                $poi->tips[] = $tmpTip;
            }
        }
        
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