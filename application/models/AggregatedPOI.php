<?php

class GSAA_Model_AggregatedPOI
{
    
    /**
     * Array of GSAA_Model_POI
     */
    protected $_pois    = array();
    
    /**
     * Flag to indicate whether POIS are in sorted state
     */
    protected $_sorted = false;
        
    /** 
     * Get array of aggregated POI types {fq, gw, gg, fb}
     * Note each can eventually occur more than once.
     * 
     * @return Array Array of aggreagted POI types. 
     */    
    public function getTypes($indexById = false) {
        $this->_sortPois();
        $return = array();
        foreach ($this->getPois() as $poi) {
            if ($indexById) {
                $return[($poi->type == 'gg') ? $poi->reference : $poi->id] = $poi->type;
            } else {
                $return[] = $poi->type;
            }
        }
        return $return;
    }
    
    /**
     * Get URL of aggregated detail     
     */
    public function getDetailUrl() {
        $this->_sortPois();
        
        $urlParams = array('controller' => 'poi', 'action' => 'show-detail');
        foreach ($this->getPois() as $poi) {
            // id => type order is there on purpose (to allow multiple pois from one service)
            $urlParams[($poi->type == 'gg') ? $poi->reference : $poi->id] = $poi->type;
        }
        // little bit MVC break, but we really need to get the url view heleper...
        $url = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view')->url($urlParams);
        return $url;
    }

    /**
     * Get single value of specified field.
     * If field is not present in POI with highest priority, try to get value from next POI and so on...
     * 
     * @param string $field Name of POI field
     * @param mixed $params Specific params. If url, bool whether if none address is found, should it be reverse geocoded
     * @return mixed 
     */
    public function getField($field, $params = null) {
        $this->_sortPois();
        if ($field == 'address') return $this->_getAddress($params  );
        
        foreach ($this->getPois() as $poi) {
            if (isset($poi->$field) && !is_null($poi->$field)) {
                return $poi->$field;
            }
        }
        return;
    }
    /**
     * Get all values if specified field.
     * 
     * @param string $field Name of POI field
     * @return array Array of values, values are array indexed by service from which value origins.
     */
    public function getFieldAll($field) {
        $this->_sortPois();
        
        $array = array();
        foreach ($this->getPois() as $poi) {
            if (isset($poi->$field) && !is_null($poi->$field)) {
                if (is_array($poi->$field)) { // in case value consist of arrays => need to rearrange
                    foreach ($poi->$field as $fieldArray) {
                        $array[] = array($poi->type => $fieldArray);
                    }
                } else {
                    $array[] = array($poi->type => $poi->$field);
                }
            }
        }
        return $array;
    }

    /**
     * Get current array of GSAA_Model_POI
     * 
     * @return array Array of GSAA_Model_POI
     */
    public function getPois() {
        $this->_sortPois();
        return $this->_pois;
    }
    
    /**
     * Add POI into aggregated one.
     * 
     * @param GSAA_Model_POI POI to add
     */
    public function addPoi(GSAA_Model_POI $poi) {
        $this->_pois[] = $poi;
        $this->_sorted = false; // indicate POIs are not in sorted state
    }
    
    /**
     * Get aggregated address (if available), or find address on POI location (TODO!)
     
     * @param bool If none adres found, should we do reverse geocoding and try to found it
     * @return type 
     */
    protected function _getAddress($find = false) {
        $this->_sortPois();
        foreach ($this->getPois() as $poi) {
            if (isset($poi->address) && !empty($poi->address)) {
                return $poi->address;
            }
        }
        // none address found
        if ($find) {
            return; // TODO: reverse geocoding (see issue #81)
        }
    }
        
    /**
     * Sort POIs in $this->_pois array.
     * The purpose why this is not called automatically after each addPoi() is performance.
     * We only call this after all POIs area added and some content is requested.
     */
    protected function _sortPois() {        
        if ($this->_sorted) return; // POIs are already sorted
        $tmp = array();
        // cycle throught all POIs, put them in array indexed by priority
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
