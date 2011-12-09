<?php
/**
 * Controller for requesting POIs and sets of POIs
 */

class PoiController extends Zend_Controller_Action
{

    protected $_serviceModels = array();

    public function init()
    {
        foreach (Zend_Registry::get('var')->services as $serviceId => $service) {
            $classname = $service['model'];
            $this->_serviceModels[$serviceId] = new $classname();
        }
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');        
        $ajaxContext->addActionContext('get-nearby', 'json')
                    ->initContext();
    }

    /**
     * Get nearby venues.
     * Result is in view variables => returned as JSON.
     */
    public function getNearbyAction()
    {
        $lat = (double) $this->_getParam('lat');
        $long = (double) $this->_getParam('long');
        $radius = (int) $this->_getParam('radius');
        $term = (string) $this->_getParam('term');
        
        // lat and long params are mandatory
        if (!is_numeric($lat) || !is_numeric($long)) {
            return;
        }
        
        $poisRaw = array();
        
        foreach ($this->_serviceModels as $modelId => $model) { // iterate through availabe models
            if ((boolean) $this->_getParam($modelId)) { // use service
                $poisRaw = array_merge(
                        $poisRaw,
                        $model->getNearbyVenues($lat, $long, $radius, $term));
            }
        }

        if (count($poisRaw) > 0) {
            //$this->view->pois = $pois;
            $pois = $this->_mergePois($poisRaw);
            $this->view->pois = array();
            $i = 0;
            foreach ($pois as $poi) {
                $this->view->pois[$i]['name']       = $poi->getField('name');
                $this->view->pois[$i]['id']         = $poi->getField('id');
                $this->view->pois[$i]['url']        = $poi->getDetailUrl();
                $this->view->pois[$i]['types']      = $poi->getTypes();
                $this->view->pois[$i]['lat']        = $poi->getField('lat');
                $this->view->pois[$i]['lng']        = $poi->getField('lng');
                $this->view->pois[$i]['distance']   = $poi->getField('distance');
                $this->view->pois[$i]['address']    = $poi->getField('address', false);
                $this->view->pois[$i]['phone']      = $poi->getField('phone');
                $this->view->pois[$i]['quality']   = $poi->getField('quality');
                $this->view->pois[$i]['pois']       = $poi->getPois();
                $i++;
            }
            
        }
        
    }

    /**
     * Load and render detail of specific venue.
     * If called using XmlHttpRequest, layout is not used.
     */
    public function showDetailAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout->disableLayout();
        } 
        
        $aggregatedPOI = new GSAA_Model_AggregatedPOI();
        foreach ($this->_request->getParams() as $index => $value) {
            if (array_key_exists($value, $this->_serviceModels)) { // only parameters representing POIs
                $poi = $this->_serviceModels[$value]->getDetail($index);
                if (!$poi) continue;
                $aggregatedPOI->addPoi($poi);
            }
        }
        if (count($aggregatedPOI->getPois()) < 1) {
            throw new Zend_Controller_Action_Exception('No POI specified or available.', 404);
        }
        
        $this->view->serviceParams      = $aggregatedPOI->getTypes(true);
        $this->view->pois               = $aggregatedPOI->getPois();
        $this->view->title              = $aggregatedPOI->getField('name');
        $this->view->values             = array();
        $this->view->values['address']  = $aggregatedPOI->getFieldAll('address');
        $this->view->values['phone']    = $aggregatedPOI->getFieldAll('phone');
        $this->view->values['links']    = $aggregatedPOI->getFieldAll('links');
        $this->view->values['photos']   = $aggregatedPOI->getFieldAll('photos');
        $this->view->values['tips']     = $aggregatedPOI->getFieldAll('tips');
        $this->view->values['notes']    = $aggregatedPOI->getFieldAll('notes');
        $this->view->values['categories'] = $aggregatedPOI->getFieldAll('categories');
        if (count($this->view->values['address']) == 0) {
            $addressGeocode = $aggregatedPOI->getField('address', true); // geocode address
            if ($addressGeocode) {
                $this->view->values['address_geocode']  = $addressGeocode;
            }
        }
        $this->view->services = Zend_Registry::get('var')->services;
    }

    /**
     * Action only for testing and development purposes.
     */    
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
        $poisRaw = array();
        foreach ($this->_serviceModels as $modelId => $model) { // iterate through availabe models
            if ((boolean) $service[$modelId] ) { // use service
                $poisRaw = array_merge(
                        $poisRaw,
                        $model->getNearbyVenues($lat, $long, $radius, $term));
            }
        }      
        
        $pois = $this->_mergePois($poisRaw);
                
        print_r($pois);
    }
    
    
    /**
     * Merge array of GSAA_Model_POI
     * 
     * @param array $poisRaw
     * @return array Array of GSAA_Model_AggregatedPOI
     */    
    protected function _mergePois(array $poisRaw) {
        $pois = array(); // Array of GSAA_Model_AggregatedPOI
        // iterate through array of pois
        for ($x = 0; $x < count($poisRaw); $x++) {
            if (is_null($poisRaw[$x])) continue; // skip already merged items
            $agPoi = new GSAA_Model_AggregatedPOI();
            $agPoi->addPoi($poisRaw[$x]); // copy entire POI
            
            $poiXName = Zend_Filter::filterStatic($poisRaw[$x]->name, 'StringToLower');
            $poiXName = Zend_Filter::filterStatic($poiXName, 'ASCII', array(), array('GSAA_Filter'));
            for ($y = 0; $y < count($poisRaw); $y++) {
                if (is_null($poisRaw[$y])) continue; // skip already merged items
                if ($x == $y) continue; // skip the same POI
                $poiYName = Zend_Filter::filterStatic($poisRaw[$y]->name, 'StringToLower');
                $poiYName = Zend_Filter::filterStatic($poiYName, 'ASCII', array(), array('GSAA_Filter'));
                
                $similarPercentBasic = 0;
                $similarPercentAlpha = 0;
                /*
                 * TODO: other text matching improvements suggestions (see also issue #45):
                 * - maybe remove some chars
                 * - divide name on parts dividers like | and ()
                 * - remove common prefixes like "Restaurace" (but then be more strict on distance)
                 * - try different word order
                 */
                
                similar_text($poiXName, $poiYName, $similarPercentBasic);
                similar_text( Zend_Filter::filterStatic($poiXName, 'Alnum'),
                              Zend_Filter::filterStatic($poiYName, 'Alnum'), $similarPercentAlpha);
                $distance = $this->_serviceModels[$poisRaw[$x]->type]->getDistance(
                                $poisRaw[$x]->lat,
                                $poisRaw[$x]->lng,
                                $poisRaw[$y]->lat,
                                $poisRaw[$y]->lng);

                /*echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $y . ": " . $poisRaw[$y]->name . " | "
                        . 'similar_text_basic: ' . round($similarPercentBasic, 1) . " | "
                        . 'similar_text_alpha: ' . round($similarPercentAlpha, 1) . " | "
                        . 'distance: '
                        . $distance
                        . "<br />\n";*/
                if (($similarPercentBasic > 75
                         || $similarPercentAlpha > 82.5)
                    && $distance < 150) { // Merge objects
                    
                    $agPoi->addPoi($poisRaw[$y]); // copy entire POI
                    $poisRaw[$y] = null; // remove content from array, so that the POI wont be merged again
                    // TODO: is it wise, just to find similarities between the first one? Would by better to find all similar pairs and sorty similarity
                } 
            }
            $pois[] = $agPoi;
        }
        return $pois;
    }

}



