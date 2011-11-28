<?php

class GSAA_Model_POI
{
    /* 
     * Type of POI {fq, gw, gg, fb}
     */
    public $type    = null;
    
    /*
     * Unique id of POI 
     */
    public $id      = null;
    
    /*
     * Name of POI
     */
    public $name    = null;
    
    /*
     * Latitude
     */
    public $lat     = null;
    
    /*
     * Longitude
     */
    public $lng     = null;
    
    /*
     * Distance in meters from search coords (optional).
     * Not available in POI detail.
     */
    public $distance = null;
    
    /*
     * Address (optional).
     */
    public $address = null;
    
    /*
     * Full URL of POI detail in its service (optional).
     * May not be available in compact POI.
     */
    public $url     = null;
    
    /*
     * Phone (optional).
     * Available only in POI detail.
     */
    public $phone = null;
    
    /*
     * Associative array of links (optional).
     * Available only in POI detail.
     * Key - Description
     * Value - link url
     */
    public $links    = array();
    
    /*
     * Array of POI photos array (optional).
     * Available only in POI detail.
     * Structure:
     *      array(
     *          id,
     *          url
     *      )
     * 
     */
    public $photos    = array();
    
    /**
     * Get unique priority of the service.
     * Zero is the lowest possible priority.
     * 
     * @return int
     */    
    public function getPriority() {
        return Zend_Registry::get('var')->services[$this->type]['priority'];
    }

        
    /*
     * TODO: stats
     */
    
    /*
     * TODO: categories
     */    
    
}
