<?php

class GSAA_Model_LBS_Foursquare extends GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = 'https://api.foursquare.com/v2';
    const PUBLIC_URL = 'https://foursquare.com';
    const OAUTH_URL = 'https://foursquare.com/oauth2/authenticate';
    const OAUTH_CALLBACK = 'https://foursquare.com/oauth2/access_token';
    const OAUTH_CHECK = 'https://api.foursquare.com/v2/users/self';
    const CLIENT_ID = 'QJ52TX1UJUBCPJ3DMOWS52I5MK5WJTDD3ZGCDFFWHWISUQ3K';
    const CLIENT_SECRET = 'XFCVWF3HNGWVQWZJQC32ZMYBUHTGNKFR4IKJUHMYJNE2ZFDW';
    const LIMIT = 30;
    const RADIUS_MAX = 100000;
    const TYPE = 'fq';

    /**
     * Number of usersCount, when POI is considered as "top quality" (quality = 5).
     * May increase in time (depending on increase of foursquare users).
     */
    const TOP_QUALITY_USERSCOUNT_SCALE = 250;
    /**
     *  Date of 4SQ API is verified to be up-to-date.
     */
    const DATEVERIFIED = '20111027';

    public function init() {
        // TODO: set client properties?
    }

    /**
     * Function to get nearby venues.
     *
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @return array Array of GSAA_Model_POI
     */
    public function getNearbyVenues($lat, $long, $radius, $term = null) {
        $endpoint = '/venues/search';
        if ($radius > self::RADIUS_MAX) {       // limit maximum radius
            $radius = self::RADIUS_MAX;
        } elseif ($radius == 0) {               // when no radius is send
            $radius = self::RADIUS;
        }
        $limit = self::LIMIT_WITHOUT_FILTER;    // limit number of POIs when no search is being executed
        if ($term) {
            $limit = self::LIMIT;               // limit of POIs when searching
        }

        $client = $this->_constructClient($endpoint,
                                        array(  'll'            => "$lat,$long",
                                                'query'         => $term,
                                                'intent'        => 'checkin', // other possible values: browse, match
                                                'limit'         => $limit,
                                                'radius'        => $radius,
                                                'oauth_token'   => '' // even if user has his own token, overwrite it
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
            if (GSAA_POI_Distance::getDistance($lat, $long,
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
                $poi->distance = GSAA_POI_Distance::getDistance($lat, $long, $poi->lat, $poi->lng);
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

            $poi->quality = $this->_calculateQuality($poi, $entry['stats']['usersCount']);

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
            $poi->links[] = array("Website" => (strncmp($entry['url'], 'http', 4) == 0 ? '' : 'http://') . $entry['url']);
        if (isset($entry['contact']['twitter'])) // twitter account
            $poi->links[] = array("Twitter" => "http://twitter.com/" . $entry['contact']['twitter']);

        /*
         * Categories
         */
        foreach ($entry['categories'] as $category) {
            $poi->categories[] = array(
                'id'    => $category['id'],
                'name'  => $category['name'],
                'icon'  => $category['icon']['prefix'] . $category['icon']['sizes'][0] . $category['icon']['name']
            );
        }

        /*
         * Add photos
         */
        $clientPhotos = $this->_constructClient($endpoint . '/' . $id . '/photos',
                                                array('group' => 'venue'));
        try {
            $responsePhotos = $clientPhotos->request();

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
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            // keep photos empty
        }

        /*
         * Add tips
         */
        $clientTips = $this->_constructClient($endpoint . '/' . $id . '/tips',
                                              array('sort' => 'popular',
                                                    'limit' => 100
                                                  ));
        try {
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
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            // keep tips empty
        }

        return $poi;
    }

    /**
     * Get URL to OAuth authorize page for this service
     *
     * @return string Url to OAuth authorize page
     */
    public static function getAuthUrl() {
        $queryString = http_build_query(
            array(  'client_id'     => self::CLIENT_ID,
                    'response_type' => 'code',
                    'redirect_uri'  => self::getAbsoluteUrl(array(
                                            'controller'    => 'oauth',
                                            'action'        => 'callback',
                                            'service'       => self::TYPE
                                        ))
        ));
        $url = self::OAUTH_URL . '?' . $queryString;
        return $url;
    }

    /**
     * Request OAuth access token.
     *
     * @param string $code OAuth code we got from service.
     * @return string Token, or null if we didn't obtain a proper token
     */
    public function requestToken($code) {
        $client = new Zend_Http_Client();
        $queryParams = array(
            'client_id'     => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => self::getAbsoluteUrl(array(
                                        'controller'    => 'oauth',
                                        'action'        => 'callback',
                                        'service'       => self::TYPE
                                        )),
            'code'          => $code
        );
        $client->setUri(self::OAUTH_CALLBACK);
        $client->setParameterGet($queryParams);

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }

        // error in response
        if ($response->isError()) {
            return;
        }

        $result = Zend_Json::decode($response->getBody());
        if (isset($result['access_token'])) {
            $token = $result['access_token'];
            return $token;
        }
        return;
    }

    /**
     *  Check if token is still valid in service
     *
     * @param string $token OAuth token
     * @return bool Whether token is still valid in service
     */
    public function checkToken($token) {
        $client = new Zend_Http_Client();
        $queryParams = array(
            'oauth_token'   => $token,
        );
        $client->setUri(self::OAUTH_CHECK);
        $client->setParameterGet($queryParams);
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return false;
        }
        if ($response->isSuccessful()) {
            return true;
        }
        return false;
    }

    /**
     * Execute checkin on specified POI.
     *
     * @param string $poiId ID of POI
     * @param string $comment Check-in comment
     * @return array Response message
     */
    public function doCheckin($poiId, $comment = '') {
        $client = $this->_constructClient('/checkins/add',
                                            array(
                                                'venueId' => $poiId,
                                                'shout'   => $comment
                                            )
        );
        try {
            $response = $client->request('POST');
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }

        // error in response
        if ($response->isError()) return;

        $result = Zend_Json::decode($response->getBody());
        // foursquare returned an error
        if ($result['meta']['code'] != 200) return;

        $responseMessage = '';
        foreach ($result['notifications'] as $notification) {
            if ($notification['type'] == 'message') {
                $responseMessage = $notification['item']['message'];
            }
        }
        return $responseMessage;
    }

    /**
     * Get details of signed in user.
     *
     * @return array Array of user details
     */
    public function getUserInfo() {
        $endpoint = '/users';
        $client = $this->_constructClient($endpoint . '/self');
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

        $entry = $result['response']['user'];
        $user = array(
            'name'      => $entry['firstName'] . ' ' . $entry['lastName'],
            'id'        => $entry['id'],
            'avatar'    => $entry['photo']
        );
        return $user;
    }

    /**
     * Get latest checkins of my friends
     *
     * @return array Array of friends latest checkins in GSAA_Model_Checkin
     */
    public function getFriendsActivity() {
        $client = $this->_constructClient('/checkins/recent');
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return array();
        }

        // error in response
        if ($response->isError()) return array();

        $result = Zend_Json::decode($response->getBody());
        // foursquare returned an error
        if ($result['meta']['code'] != 200) return array();

        $friendsActivity = array();
        $entry = $result['response']['recent'];

        foreach ($entry as $friend) {
            if (!isset($friend['venue'])) continue; // may be shout without venue -> skip than
            if ($friend['user']['relationship'] != 'friend') continue; // skip follwings

            // fill GSAA_Model_Checkin
                $checkin = new GSAA_Model_Checkin(self::TYPE, $friend['createdAt']);
                $checkin->userName  = (isset($friend['user']['firstName']) ? $friend['user']['firstName'] : '')
                                      . (isset($friend['user']['lastName']) ? ' ' . $friend['user']['lastName'] : '');
                $checkin->avatar    = $friend['user']['photo'];
                $checkin->poiName   = $friend['venue']['name'];
                $checkin->lat       = $friend['venue']['location']['lat'];
                $checkin->lng       = $friend['venue']['location']['lng'];
                $checkin->comment   = (isset($friend['shout']) ? $friend['shout'] : '');
                $checkin->id        = 'id-' . substr(md5(uniqid()), 0, 8);

                $friendsActivity[]  = $checkin;
        }
        return $friendsActivity;
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

        // when ouath_token is not empty, and was not reset to false, use it
        if (isset($queryParams['oauth_token']) && $queryParams['oauth_token'] == "") {
            // leave it empty
        } elseif (!empty($this->_oauthToken)) { // if token is set, use it
            $queryParams['oauth_token'] = $this->_oauthToken;
        }

        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);


        return $client;
    }

    /**
     * Calculate POI quality
     *
     * @param GSAA_Model_POI $poi POI which quality should be calculated
     * @param int $usersCount
     * @return double Quality of POI (-5.0 - 5.0)
     */
    protected function _calculateQuality($poi, $usersCount) {
        // usersCount   >= TOP_QUALITY_USERSCOUNT_SCALE => quality = 5;
        // usescount    = 0 => quality = -5
        // Values between are lineary distributed.
        $return = (($usersCount / self::TOP_QUALITY_USERSCOUNT_SCALE) * 10) - 5;
        if ($return > 5) {
            $return = 5;
        }
        return round($return, 2);
    }
}