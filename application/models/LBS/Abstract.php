<?php

abstract class GSAA_Model_LBS_Abstract
{
    /**
     * Two-letter service shortcut (like "fq" for foursquare etc)
     */
    const TYPE = null;
    /**
     * Url of service itself
     */
    const SERVICE_URL = null;
    /**
     * URL of user access confirmation
     */
    const OAUTH_URL = null;
    /**
     * URL of OAuth callback to get access token
     */
    const OAUTH_CALLBACK = null;
    /**
     * URL where to check token is still valid
     */
    const OAUTH_CHECK = null;
    /**
     * Default radius for requesting POIs if none set
     */
    const RADIUS = 2500;
    /**
     * Maximum radius for requesting POIs
     */
    const RADIUS_MAX = 50000;
    /**
     * Limit for requesting POIs
     */
    const LIMIT = 30;
    /**
     * Limit for requesting POIs when no filter is applied
     */
    const LIMIT_WITHOUT_FILTER = 10;

    /*
     * OAuth token of this service
     */
    protected $_oauthToken;

    /*
     * Array of available services
     */
    protected $_services;
    /**
     * Constructor.
     *
     * @return void
     */

    public function __construct() {
        $session = new Zend_Session_Namespace('GSAA');
        // check if token in session is set and valid
        if (isset($session->services) && isset($session->services[static::TYPE])) {
            if ($this->checkToken($session->services[static::TYPE])) {
                $this->_oauthToken = $session->services[static::TYPE];
            }
        }
        $this->_services = Zend_Registry::get('var')->services;
        $this->init();
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Abstract function to get nearby POIs.
     *
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @return array Array of GSAA_Model_POI
     */
    abstract public function getNearbyPois($x, $y, $radius, $term = null);


    /**
     * Abstract function to get full detail of POI.
     *
     * @param string $id POI ID
     * @return GSAA_Model_POI POI detail object
     */
    abstract public function getDetail($id);

    /**
     * Abstract funtion to request OAuth access token.
     *
     * @param string $code OAuth code we got from service.
     * @return string Token, or null if we didn't obtain a proper token
     */
    abstract public function requestToken($code);

    /**
     *  Abstract funtion to check if token is still valid in service.
     *
     * @param string $token OAuth token
     * @return bool Whether token is still valid in service
     */
    abstract public function checkToken($token);

    /**
     * Abstract funtion to get details of signed in user.
     *
     * @return array Array of user details
     */
    abstract public function getUserInfo();

    public static function getAbsoluteUrl(array $urlParams = array()) {
        $url = Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getResource('view')
                ->absoluteUrl($urlParams, null, true);
        return $url;
    }

}

