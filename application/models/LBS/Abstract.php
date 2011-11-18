<?php

abstract class GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = null;
    const TYPE = null;
    const RADIUS = 2500;


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
     * Abstract funtion to get nearby venues.
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
    protected function _getDistance($lat1, $long1, $lat2, $long2) {
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

