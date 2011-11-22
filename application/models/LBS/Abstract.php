<?php

abstract class GSAA_Model_LBS_Abstract
{
    /*
     * Url of service itself
     */
    const SERVICE_URL = null;
    /*
     * Two-letter service shortcut (like "fq" for foursquare etc)
     */
    const TYPE = null;
    /*
     * Default radius for requesting venues if none set
     */
    const RADIUS = 2500;
    /* 
     * Maximum radius for requesting venues
     */
    const RADIUS_MAX = 50000;
    /*
     * Limit for requesting places
     */
    const LIMIT = 30;
    /*
     * Limit for requesting places when no filter is applied
     */
    const LIMIT_WITHOUT_FILTER = 10;


    /**
     * Constructor.
     * 
     * @return void
     */

    public function __construct() {        
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
     * Abstract function to get nearby venues.
     * 
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @param string $category Category id
     * @return array Array with venues
     */
    abstract public function getNearbyVenues($x, $y, $radius, $term = null, $category = null);
    
    /**
     * Calculate distance between two coordinates. 
     * 
     * @param double $lat1 First latitude
     * @param double $long1 First longitude
     * @param double $lat2 Second latitude
     * @param double $long2 Second longitude
     * @return int Distance in meters. 
     */
    final public static function getDistance($lat1, $long1, $lat2, $long2) {
        return round(   6378
                        * M_PI
                        * sqrt(
                            ($lat2-$lat1)
                                * ($lat2-$lat1)
                            + cos(deg2rad($lat2))
                                * cos(deg2rad($lat1)) 
                                * ($long2-$long1) 
                                * ($long2-$long1))
                        / 180
                        * 1000);        
    }
    
}

