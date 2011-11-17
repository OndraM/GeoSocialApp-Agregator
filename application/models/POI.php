<?php

class GSAA_Model_POI
{
    // Type of POI {fq, gw, gg, fb}
    public $type    = null;
    // Id of POI 
    public $id      = null;
    // Name of POI
    public $name    = null;
    // Location array (will be converted to object in constructor)
    public $location = array(
        // Latitude
        'lat'       => null,
        // Longitude
        'lng'       => null,
        // Distance in meters from search coords (if available)
        'distance'  => null,
        // Address (if available)
        'address'   => null,
        // Postal code (if available)
        'postalCode'=> null,
        // City (if available)
        'city'      => null,
        // Country (if available)
        'country'   => null
    );
    // Full URL of POI detail in its service
    public $url     = null;
    
    // TODO: stats
    
    // TODO: categories
    
    /**
     * Constructor.
     * 
     * @return void
     */

    public function __construct() {        
        $this->location = (object) $this->location;
    }
    
    
}
