<?php

/**
 * Controller for requesting POIs and POIs sets using AJAX
 */

class PoiController extends Zend_Controller_Action
{

    protected $_foursquareModel;
    protected $_gowallaModel;

    public function init()
    {
        $this->_foursquareModel = new GSAA_Model_LBS_Foursquare();
        $this->_gowallaModel = new GSAA_Model_LBS_Gowalla();
        $this->_googePlacesModel = new GSAA_Model_LBS_GooglePlaces();
        
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
        
        $poisFoursquare = $this->_foursquareModel->getNearbyVenues($lat, $long, $radius, $term);
        $poisGowalla    = $this->_gowallaModel->getNearbyVenues($lat, $long, $radius, $term);
        $poisGooglePlaces = $this->_googePlacesModel->getNearbyVenues($lat, $long, $radius, $term);
        
        
        $pois = array_merge($poisFoursquare, $poisGowalla, $poisGooglePlaces);
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
        
        $model = new GSAA_Model_LBS_GooglePlaces();
        
        print_r($model->getNearbyVenues($lat, $long, $radius, $term));
    }


}



