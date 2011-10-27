<?php

abstract class GSAA_Model_LBS_Abstract
{
    const SERVICE_URL = null;

    
    abstract public function getNearbyVenues($x, $y, $term, $category);
}

