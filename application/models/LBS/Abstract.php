<?php

abstract class GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = null;
    
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
     * Abstract funtion to get nearby venues
     */
    abstract public function getNearbyVenues($x, $y, $term = null, $category = null);
    
}

