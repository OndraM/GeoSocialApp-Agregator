<?php

/**
 * Controller for requesting POIs and POIs sets using AJAX
 */

class PoiController extends Zend_Controller_Action
{

    protected $_foursquareModel = null;

    public function init()
    {
        $this->_foursquareModel = new GSAA_Model_LBS_Foursquare();
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        
        $ajaxContext->addActionContext('get-nearby', 'json')
                    ->initContext();
    }

    public function getNearbyAction()
    {
        $lat = $this->_getParam('lat');
        $long = $this->_getParam('long');
        $term = $this->_getParam('term');
        
        // lat and long params are mandatory
        if (empty($lat) || empty($long) || !is_numeric($lat) || !is_numeric($long)) {
            return;
        }
        
        $venues = $this->_foursquareModel->getNearbyVenues($lat, $long, $term);
        
        if (count($venues) > 0) {
            $this->view->venues = $venues['venues'];
        }
        
        // overwrite context setting for testing purposes // TODO
        //$response = $this->getResponse();
        //$response->setHeader('Content-Type', 'text/html');
    }


}



