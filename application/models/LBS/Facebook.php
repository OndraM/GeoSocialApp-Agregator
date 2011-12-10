<?php

class GSAA_Model_LBS_Facebook extends GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = 'https://graph.facebook.com';
    const PUBLIC_URL = 'https://facebook.com';
    const OAUTH_URL = 'https://www.facebook.com/dialog/oauth';
    const OAUTH_CALLBACK = 'https://graph.facebook.com/oauth/access_token';
    const OAUTH_CHECK = 'https://graph.facebook.com/me/';
    const CLIENT_ID = '110157905740540';
    const CLIENT_SECRET = 'fc86a9bb2486f28f3e3866a5d8ff67ef';
    const ACCESS_TOKEN = '110157905740540|Pq1zptubbe1gq5L8hAL3aBswvPs';
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
     * @return array Array of GSAA_Model_POI
     */
    public function getNearbyVenues($lat, $long, $radius, $term = null) {
        $endpoint = '/search';
        if ($radius > self::RADIUS_MAX) {
            $radius = self::RADIUS_MAX;
        }
        $limit = self::LIMIT_WITHOUT_FILTER;
        if ($term) {
            $limit = self::LIMIT;
        }

        $queryParams = array('type'     => 'place',
                             'center'   => "$lat,$long",
                             'limit'    => $limit,
                             'distance' => ($radius > 0 ? $radius : self::RADIUS),
                             'access_token' => self::ACCESS_TOKEN // even if user has his own token, overwrite it with the app token here
                             );
        if ($term) {
            $queryParams['q'] = Zend_Filter::filterStatic($term, 'ASCII', array(), array('GSAA_Filter'));
        }
        $client = $this->_constructClient($endpoint, $queryParams);

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

        // Load venues into array of GSAA_Model_POI
        $pois = array();
        foreach ($result['data'] as $entry) {
            // skip venues that are not in radius x2 (avoid showing venues that are too far)
            // in fact the distance parametr do the same on FB side (and actually works), so this is kind of redundant check
            if (GSAA_POI_Distance::getDistance($lat, $long,
                                    $entry['location']['latitude'], $entry['location']['longitude']) > $radius*2) {
                continue;
            }
            $poi = new GSAA_Model_POI();
            $poi->type      = self::TYPE;
            $poi->name      = $entry['name'];
            $poi->id        = $entry['id'];
            $poi->url       = self::PUBLIC_URL . "/" . $entry['id'];
            $poi->lat       = $entry['location']['latitude'];
            $poi->lng       = $entry['location']['longitude'];
            $poi->distance = GSAA_POI_Distance::getDistance($lat, $long, $poi->lat, $poi->lng);

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
     * Get full detail of venue.
     *
     * @param string $id Venue ID
     * @return GSAA_Model_POI
     */
    public function getDetail($id) {
        $endpoint = '';
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
        $poi->id        = $entry['id'];
        $poi->url       = $entry['link'];
        $poi->lat       = $entry['location']['latitude'];
        $poi->lng       = $entry['location']['longitude'];
        if (isset($entry['location']['street']))
                $poi->address = $entry['location']['street'];
        if (isset($entry['location']['zip']))
            $poi->address .= (!empty($poi->address) ? ', ' : '')
                        . $entry['location']['zip'];
        if (isset($entry['location']['city']))
            $poi->address .= (!empty($poi->address) ? ', ' : '')
                        . $entry['location']['city'];

        if (isset($entry['phone']))
            $poi->phone = $entry['phone'];

        if (isset($entry['website']))
            $poi->links[] = array("Website" => (strncmp($entry['website'], 'http', 4) == 0 ? '' : 'http://') . $entry['website']);

        /**
         * Add photos (Facebook profile photos)
         */
        $clientPhotos = $this->_constructClient($endpoint . '/' . $id . '/' . 'photos');
        try {
            $responsePhotos = $clientPhotos->request();

            // error in response
            if ($responsePhotos->isError()) return;

            $resultPhotos = Zend_Json::decode($responsePhotos->getBody());
            $entryPhotos = $resultPhotos['data'];
            if (count($entryPhotos) > 0) {
                foreach ($entryPhotos as $photo) {
                    $thumbUrl = null;
                    // find apropriate thumbnail size
                    foreach ($photo['images'] as $sizes) {
                        if ($sizes['height'] < 150) { // firt thumbnail smaller the 150px
                            $thumbUrl = $sizes['source'];
                            break;
                        }
                    }
                    $tmpDate = new Zend_Date(substr($photo['created_time'], 0, -2) . ':' . substr($photo['created_time'], -2), Zend_Date::W3C);
                    $tmpPhoto = array(
                        'url'   => $photo['source'],
                        'thumbnail' => $thumbUrl,
                        'id'    => $photo['id'],
                        'date'  => $tmpDate->get(Zend_Date::TIMESTAMP),
                        'title' => (isset($photo['name']) ? $photo['name'] : '')
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
                    'redirect_uri'  => 'http://gsaa.local/oauth/callback/service/' . self::TYPE, // TODO: variable path?
                    'scope'         => 'user_about_me,user_checkins,friends_checkins,publish_checkins,offline_access'
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
            'redirect_uri'  => rawurldecode('http://gsaa.local/oauth/callback/service/' . self::TYPE), // TODO: get absolute url dynamically
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

        parse_str($response->getBody(), $result);
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
     * @return array Response array // TODO
     */
    public function doCheckin($poiId, $comment = '') {
        // at first, get detail of POI, so we load its lat and lng
        $poiDetail = $this->getDetail($poiId);

        $client = $this->_constructClient('/me/checkins/',
                                            array(
                                                'place'       => $poiId,
                                                'message'     => $comment,
                                                'coordinates' => Zend_Json::encode(array(
                                                    'latitude'  => $poiDetail->lat,
                                                    'longitude' => $poiDetail->lng
                                                ))
                                            )
        );
        try {
            $response = $client->request('POST');
            d($response);
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }

        // error in response
        if ($response->isError()) return;

        $result = Zend_Json::decode($response->getBody());
        $responseMessage = '';
        if ($result) {
            $responseMessage = 'Check-in successful';
        }
        return $responseMessage;
    }

    /**
     * Get details of signed in user.
     *
     * @return array Array of user details
     */
    public function getUserInfo() {
        $client = $this->_constructClient('/me');
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return;
        }
        // error in response
        if ($response->isError()) return;

        $entry = Zend_Json::decode($response->getBody());
        $user = array(
            'name'      => $entry['name'],
            'id'        => $entry['id'],
            'avatar'    => self::SERVICE_URL . '/' . $entry['username'] . '/picture'
        );
        return $user;
    }

    /**
     * Get latest checkins of my friends
     *
     * @return array Array of friends latest checkins in GSAA_Model_Checkin
     */
    public function getFriendsActivity() {
        $client = $this->_constructClient('/search', array('type' => 'checkin'));
        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
            return array();
        }

        // error in response
        if ($response->isError()) return array();

        $result = Zend_Json::decode($response->getBody());

        $friendsActivity = array();
        $entry = $result['data'];

        foreach ($entry as $friend) {
            if (!isset($friend['place'])) continue;

            $tmpDate = new Zend_Date(substr($friend['created_time'], 0, -2) . ':' . substr($friend['created_time'], -2), Zend_Date::W3C);

            // fill GSAA_Model_Checkin
                $checkin = new GSAA_Model_Checkin(self::TYPE, $tmpDate->get(Zend_Date::TIMESTAMP));
                $checkin->userName  = $friend['from']['name'];
                $checkin->avatar    = self::SERVICE_URL . '/' . $friend['from']['id'] . '/picture';
                $checkin->poiName   = $friend['place']['name'];
                $checkin->lat       = $friend['place']['location']['latitude'];
                $checkin->lng       = $friend['place']['location']['longitude'];
                $checkin->comment   = (isset($friend['message']) ? $friend['message'] : '');
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

        // when no ouath_token is set, and user has his own, try to use it
        if (!empty($this->_oauthToken) && !isset($queryParams['oauth_token'])) {
            $queryParams['access_token'] = $this->_oauthToken;
        } else { // otherwise use APP token
            $queryParams['access_token'] = self::ACCESS_TOKEN;
        }

        // set client options
        $client->setUri(self::SERVICE_URL . $endpoint);
        $client->setParameterGet($queryParams);
        $client->setConfig($clientConfig);

        return $client;
    }

}