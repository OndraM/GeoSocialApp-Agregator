<?php

class GSAA_Model_POI
{
    /* 
     * Type of POI {fq, gw, gg, fb}
     */
    public $type    = null;
    
    /*
     * Id of POI 
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
     * Distance in meters from search coords
     */
    public $distance = null;
    
    /*
     * Address (if available)
     */
    public $address = null;
    
    /*
     * Full URL of POI detail in its service (if available)
     */
    public $url     = null;
    
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
