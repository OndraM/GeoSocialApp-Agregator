<?php

class GSAA_Model_AggregatedPOI
{
    
    /*
     * Array of GSAA_Model_POI
     */
    protected $_pois    = array();
    
    /*
     * Flag to indicate whether POIS were already sorted
     */
    protected $_sorted = false;
        
    /* 
     * Get array of aggregated POI types {fq, gw, gg, fb}
     */    
    public function getTypes() {
        $this->_sortPois();
        $return = array();
        foreach ($this->getPois() as $poi) {
            $return[] = $poi->type;
        }
        return $return;
    }
    
    /*
     * Get aggregated id of POI
     */
    public function getId() {
        $this->_sortPois();
        // return ID of primary POI
        return reset($this->getPois())->id;
    }

    /* 
     * Get name of aggregated POI
     */
    public function getName() {
        $this->_sortPois();
        // return name of primary POI
        return reset($this->getPois())->name;
    }
    
    /*
     * Get latitude of aggregated POI
     */
    public function getLat() {
        $this->_sortPois();
        // TODO: mayby get average location?
        return reset($this->getPois())->lat;
    }

    /*
     *  Get longitude of aggregated POI
     */
    public function getLng() {
        $this->_sortPois();
        // TODO: mayby get average location?
        return reset($this->getPois())->lng;
    }

    /*
     *  Get distance of aggregated lat & lng in meters from search coords (if available)
     */
    public function getDistance() {
        $this->_sortPois();
        return reset($this->getPois())->distance;
    }

    /*
     * Get aggregated address (if available), or find address on POI location
     */
    public function getAddress() {
        $this->_sortPois();
        // TODO - get best address, if none of primary POI, try onother ones, 
        // if none of them has address, maybe search on Google :)?
        return reset($this->getPois())->address;
    }
    
    /*
     * Get URL of aggregated detail (maybe unnecesarry)
     */
    public function getUrl() {
        $this->_sortPois();
        // TODO - combine url
        return;
    }

    /*
     * Get current array of GSAA_Model_POI
     */
    public function getPois() {
        $this->_sortPois();
        return $this->_pois;
    }
    
    /*
     * Add POI into aggregated one.
     */
    public function addPoi(GSAA_Model_POI $poi) {
        $this->_pois[] = $poi;
        $this->_sorted = false; // indicate POIs are not sorted
    }
    
    /*
     * Sort POIs in $this->_pois array
     */
    protected function _sortPois() {        
        if ($this->_sorted) return; // POIs are already sorted
        $tmp = array();
        // cycle throught all POIs, put in array indexed by priority
        foreach ($this->_pois as $poi) {
            $tmp[$poi->getPriority()] = $poi;
        }
        // sort array by priority
        krsort($tmp);
        
        // remove current POIs
        unset($this->_pois);
        $this->_pois = array();
        // put pois in array one-by-one, sorted by priority
        foreach($tmp as $poi) $this->_pois[] = $poi;
        $this->_sorted = true;
    }


    
    
    
}
