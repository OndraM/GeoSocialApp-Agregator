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
        $service_fq = (boolean) $this->_getParam('fq');
        $service_gw = (boolean) $this->_getParam('gw');
        $service_gg = (boolean) $this->_getParam('gg');
        
        d($service_gg);

        
        $model = new GSAA_Model_LBS_GooglePlaces();
        
        print_r($model->getNearbyVenues($lat, $long, $radius, $term));
    }


}



