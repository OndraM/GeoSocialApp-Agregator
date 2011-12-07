<?php

class GSAA_Model_Checkin
{
    /**
     * Type of POI {fq, gw, gg, fb}
     */
    public $type    = null;
    
    /**
     * Unique id of POI 
     */
    public $id      = null;
    
    /**
     * Name of user
     */
    public $userName    = null;
    
    /**
     * Latitude of checkin
     */
    public $lat     = null;
    
    /**
     * Longitude of checkin
     */
    public $lng     = null;

    /**
     * URL of user avatar
     */
    public $avatar = null;

    /**
     * Timestamp of checkin
     */
    public $date = null;

    /**
     * Name of checkin POI
     */
    public $poiName = null;

    /**
     * Optional checkin comment
     */
    public $comment = null;

    /**
     * Full name of source service
     */
    public $serviceName = null;

    public function __construct($type) {
        $services = Zend_Registry::get('var')->services;
        if (!isset($services[$type])) throw new Exception('Checkin service not found');
        
        $this->type = $type;
        $this->serviceName = $services[$type]['name'];        
    }
     
    
}
