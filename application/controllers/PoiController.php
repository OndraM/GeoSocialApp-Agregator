<?php

/**
 * Controller for requesting POIs and POIs sets using AJAX
 */

class PoiController extends Zend_Controller_Action
{

    protected $_serviceModels = array();

    public function init()
    {
        foreach(Zend_Registry::get('var')->services as $serviceId => $service) {
            $classname = $service['model'];
            $this->_serviceModels[$serviceId] = new $classname();
        }
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        
        $ajaxContext->addActionContext('get-nearby', 'json')
                    ->initContext();
    }

    public function getNearbyAction()
    {
        $lat = (double) $this->_getParam('lat');
        $long = (double) $this->_getParam('long');
        $radius = (int) $this->_getParam('radius');
        $term = (string) $this->_getParam('term');
        
        // lat and long params are mandatory
        if (empty($lat) || empty($long) || !is_numeric($lat) || !is_numeric($long)) {
            return;
        }
        
        $pois = array();
        
        foreach ($this->_serviceModels as $modelId => $model) { // iterate through availabe models
            if ((boolean) $this->_getParam($modelId)) { // use service
                $pois = array_merge(
                        $pois,
                        $model->getNearbyVenues($lat, $long, $radius, $term));
            }
        }

        if (count($pois) > 0) {
            $this->view->pois = $pois;
        }
        
        // overwrite context setting for testing purposes // TODO
        //$response = $this->getResponse();
        //$response->setHeader('Content-Type', 'text/html');
    }
    
    public function testAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $lat = (double) $this->_getParam('lat');
        $long = (double) $this->_getParam('long');
        $radius = (int) $this->_getParam('radius');
        $term = (string) $this->_getParam('term');
        $service['fq'] = (boolean) $this->_getParam('fq');
        $service['gw'] = (boolean) $this->_getParam('gw');
        $service['gg'] = (boolean) $this->_getParam('gg');
        $service['fb'] = (boolean) $this->_getParam('fb');
        
        //$model = new GSAA_Model_LBS_GooglePlaces();        
        //print_r($model->getNearbyVenues($lat, $long, $radius, $term));
        $pois_raw = array();
        foreach ($this->_serviceModels as $modelId => $model) { // iterate through availabe models
            if ((boolean) $service[$modelId] ) { // use service
                $pois_raw = array_merge(
                        $pois_raw,
                        $model->getNearbyVenues($lat, $long, $radius, $term));
            }
        }      
        
        for ($x = 0; $x < count($pois_raw); $x++) {
            //d($pois_raw[$x], $x);
            echo $x . ": " . $pois_raw[$x]->name . "<br />\n";
            for ($y = 0; $y < count($pois_raw); $y++) {
                if ($x == $y) continue; // skip the same POI
                $similair_percent = 0;
                similar_text($pois_raw[$x]->name, $pois_raw[$y]->name, $similair_percent);
                if ($similair_percent > 75) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $y . ": " . $pois_raw[$y]->name . " | "
                        . 'similair_text: ' . round($similair_percent, 1) . " | "
                        . 'distance: '
                        . $this->_serviceModels[$pois_raw[$x]->type]->getDistance(
                                $pois_raw[$x]->lat,
                                $pois_raw[$x]->lng,
                                $pois_raw[$y]->lat,
                                $pois_raw[$y]->lng)
                        . "<br />\n";
                }
            }
        }
        
        
        //d($pois);
    }


}



