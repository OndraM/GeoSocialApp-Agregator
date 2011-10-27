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
        // TODO: load params (getParam);
        $lat = 50.076738;
        $long = 14.41803;
        
        //echo "<pre>";
        //echo Zend_Json::prettyPrint($this->_foursquareModel->getNearbyVenues($lat, $long));
        //echo Zend_Json::encode($this->_foursquareModel->getNearbyVenues($lat, $long));
        //echo "</pre>";
        $venues = $this->_foursquareModel->getNearbyVenues($lat, $long);
        $this->view->venues = $venues['venues'];
        
        // overwrite context setting for testing purposes // TODO
        //$response = $this->getResponse();
        //$response->setHeader('Content-Type', 'text/html');
    }


}



