<?php

class GSAA_Model_LBS_Gowalla extends GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = 'http://api.gowalla.com';
    const PUBLIC_URL = 'https://gowalla.com';
    const OAUTH_URL = 'https://gowalla.com/api/oauth/new';
    const OAUTH_CALLBACK = 'https://gowalla.com/api/oauth/token';
    const OAUTH_CHECK = 'http://api.gowalla.com/users/me';
    const CLIENT_ID = '6f695b01d109467b9515c1d7457377bc';
    const CLIENT_SECRET = 'b31113a8961d41b19040a2d4b4fc01a1';
    const LIMIT = 30;
    const TYPE = 'gw';

    /**
     * Number of usersCount, when POI is considered as "top quality" (quality = 5).
     * May increase in time (depending on increase of gowalla users).
     */
    const TOP_QUALITY_USERSCOUNT_SCALE = 50;

    public function init() {
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
        $endpoint = '/spots';
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
                                        array(  'lat'            => $lat,
                                                'lng'           => $long,
                                                'q'              => $term,
                                                'radius'        => $radius
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
            if (GSAA_POI_Distance::getDistance($lat, $long, $entry['lat'], $entry['lng']) > $radius) {
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

            $poi->distance = GSAA_POI_Distance::getDistance($lat, $long, $poi->lat, $poi->lng);

            $poi->quality = $this->_calculateQuality($poi, $entry['users_count']);

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
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }

        // error in response
        if ($response->isError()) return;

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
            $poi->links[] = array("Website" => (strncmp($website, 'http', 4) == 0 ? '' : 'http://') . $website);
        }
        if (isset($entry['twitter_username'])) // twitter account
            $poi->links[] = array("Twitter" => "http://twitter.com/" . $entry['twitter_username']);

        /*
         * Categories
         */
        foreach ($entry['spot_categories'] as $category) {
            $tmpCategory = array();
            $tmpCategory['name']    = $category['name'];
            $urlExploded            = explode('/',  $category['url']);
            $tmpCategory['id']      = $urlExploded[2];

            $clientCategory = $this->_constructClient('/categories/' . $tmpCategory['id']);
            try {
                $responseCategory = $clientCategory->request();

                // error in response
                if ($responseCategory->isError()) return;

                $resultCategory = Zend_Json::decode($responseCategory->getBody());
                $tmpCategory['icon'] = $resultCategory['small_image_url'];
            } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                // dont add category
                continue;
            }
            $poi->categories[] = $tmpCategory;
        }

        /**
         * Add photos
         */
        if ($entry['photos_count'] > 0) {
            $clientPhotos = $this->_constructClient($endpoint . '/' . $id . '/photos');
            try {
                $responsePhotos = $clientPhotos->request();

                // error in response
                if ($responsePhotos->isError()) return;

                $resultPhotos = Zend_Json::decode($responsePhotos->getBody());
                $entryPhotos = $resultPhotos['activity'];

                if (count($entryPhotos) > 0) {
                    foreach ($entryPhotos as $photo) {
                        if ($photo['type']!='photo') continue;
                        $tmpDate = new Zend_Date(substr($photo['created_at'], 0, -1) . '+00:00', Zend_Date::W3C);
                        $tmpPhoto = array(
                            'url'   => $photo['photo_urls']['high_res_320x480'],
                            'thumbnail' => $photo['photo_urls']['square_100'],
                            'id'    => md5($photo['checkin_url']), // create "static" url
                            'date'  => $tmpDate->get(Zend_Date::TIMESTAMP),
                            'title' => trim($photo['message'])
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
        }

        /*
         * Add tips (aka highlights)
         */
        $clientTips = $this->_constructClient($endpoint . '/' . $id . '/highlights');
        try {
            $responseTips = $clientTips->request();

            // error in response
            if ($responseTips->isError()) return;

            $resultTips = Zend_Json::decode($responseTips->getBody());
            $entryTips = $resultTips['highlights'];

            if (count($entryTips) > 0) {
                foreach ($entryTips as $tip) {
                    if (empty($tip['comment'])) continue; // skip highlights without text
                    $tmpDate = new Zend_Date(substr($tip['updated_at'], 0, -1) . '+00:00', Zend_Date::W3C);
                    $tmpTip = array(
                        'id'    => md5($tip['updated_at']), // create "static" url
                        'text'  => $tip['comment'],
                        'date'  => $tmpDate->get(Zend_Date::TIMESTAMP)
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
                                        )),
                    'scope'         => 'read-write'
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
            'scope'         => 'read-write',
            'code'          => $code
        );
        $client->setUri(self::OAUTH_CALLBACK);
        $client->setParameterGet($queryParams);

        try {
            $response = $client->request('POST');
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
            $response = $client->request('HEAD');
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
        $client = $this->_constructClient('/checkins',
                                            array(
                                                'spot_id'       => $poiId,
                                                'comment'       => $comment
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
        $responseMessage = trim($result['detail_text']);
        return $responseMessage;
    }

    /**
     * Get details of signed in user.
     *
     * @return array Array of user details
     */
    public function getUserInfo() {
        $client = $this->_constructClient('/users/me');
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }

        // error in response
        if ($response->isError()) return;

        $entry = Zend_Json::decode($response->getBody());
        //$entry = $result['response']['user'];
        $urlExploded    = explode('/',  $entry['url']);
        $user = array(
            'name'      => $entry['first_name'] . ' ' . $entry['last_name'],
            'id'        => $urlExploded[2],
            'avatar'    => $entry['image_url']
        );
        return $user;
    }

    /**
     * Get latest checkins of my friends
     *
     * @return array Array of friends latest checkins in GSAA_Model_Checkin
     */
    public function getFriendsActivity() {
        $user = $this->getUserInfo();
        if (!$user) {
            return array();
        }
        $client = $this->_constructClient('/users/' . $user['id'] .'/friends');
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return array();
        }

        // error in response
        if ($response->isError()) return array();

        $result = Zend_Json::decode($response->getBody());

        $friendsActivity = array();
        $entry = $result['users'];

        foreach ($entry as $friend) {
            // request friend details
                $urlExploded    = explode('/',  $friend['url']);
                $clientFriend = $this->_constructClient('/users/' . $urlExploded[2]);
                try {
                    $responseFriend = $clientFriend->request();
                } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                    continue;
                }
                // error in response
                if ($responseFriend->isError()) continue;

                $resultFriend = Zend_Json::decode($responseFriend->getBody());
                $resultFriend = current($resultFriend['last_checkins']);
                if (!isset($resultFriend)) continue;
                if (!isset($resultFriend['spot'])) continue;
                if ($resultFriend['type'] != 'checkin') continue;
                $tmpDate = new Zend_Date(substr($resultFriend['created_at'], 0, -1) . '+00:00', Zend_Date::W3C);

            // request checkin details
                $clientCheckin = $this->_constructClient($resultFriend['url']);
                try {
                    $responseCheckin = $clientCheckin->request();
                } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                    continue;
                }
                // error in response
                if ($responseCheckin->isError()) continue;

                $resultCheckin = Zend_Json::decode($responseCheckin->getBody());
                if (!isset($resultCheckin)) continue;
                if (!isset($resultCheckin['spot'])) continue;
                if ($resultCheckin['type'] != 'checkin') continue;

            // fill GSAA_Model_Checkin
                $checkin = new GSAA_Model_Checkin(self::TYPE, $tmpDate->get(Zend_Date::TIMESTAMP));
                $checkin->userName  = (isset($friend['first_name']) ? $friend['first_name'] : '')
                                        . (isset($friend['last_name']) ? ' ' . $friend['last_name'] : '');
                $checkin->avatar    = $friend['image_url'];
                $checkin->poiName   = $resultFriend['spot']['name'];
                $checkin->lat       = $resultCheckin['spot']['lat'];
                $checkin->lng       = $resultCheckin['spot']['lng'];
                $checkin->comment   = (isset($resultFriend['message']) ? $resultFriend['message'] : '');
                $checkin->id        = 'id-' . substr(md5(uniqid()), 0, 8);

                $friendsActivity[] = $checkin;
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

        if (!empty($this->_oauthToken)) $queryParams['oauth_token'] = $this->_oauthToken;

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