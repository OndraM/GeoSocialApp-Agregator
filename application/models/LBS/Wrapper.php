<?php
/*
 * Class to provide unified access to all LBS model classes
 */

class GSAA_Model_LBS_Wrapper
{
    /**
     * Init model for each active LBS services
     *
     * @return array Array of GSAA_Model_LBS_Abstract
     */
    protected static function _initServiceModel() {
        $serviceModels = array();
        foreach (Zend_Registry::get('var')->services as $serviceId => $service) {
            $classname = $service['model'];
            $serviceModels[$serviceId] = new $classname();
        }
        return $serviceModels;
    }

    /**
     * Load nearby POIs from all services into one array
     *
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @return array Array of all nearby POIs
     */
    public static function loadNearbyPois($lat, $long, $radius, $term) {
        $serviceModels = self::_initServiceModel();
        $pois = array();

        foreach ($serviceModels as $modelId => $model) {    // iterate through availabe LBS models
            if ((boolean) $this->_getParam($modelId)) {     // use service
                $pois = array_merge(
                        $pois,
                        $model->getNearbyVenues($lat, $long, $radius, $term));
            }
        }
        return $pois;
    }

    /**
     * Load POIs specified by their ID into one AggregatedPOI model.
     *
     * @param array $pois Array of pois in form (poiId => poiType).
     * @return GSAA_Model_AggregatedPOI
     *
     */
    public static function loadPoiDetails($pois) {
        $serviceModels = self::_initServiceModel();
        $aggregatedPOI = new GSAA_Model_AggregatedPOI();

        foreach ($pois as $index => $value) {                   // iterate through all params
            if (array_key_exists($value, $serviceModels)) {     // only parameters representing POIs
                $poi = $serviceModels[$value]->getDetail($index);
                if (!$poi) continue;
                $aggregatedPOI->addPoi($poi);
            }
        }
        if (count($aggregatedPOI->getPois()) < 1) {
            throw new Zend_Controller_Action_Exception('No POI specified or available.', 404);
        }
        return $aggregatedPOI;
    }

    /**
     * Load last activity of friends from availabe LBS services
     *
     * @param array Array of services to request friends
     * @return Array of friends latest checkins in GSAA_Model_Checkin
     */
    public static function loadFriendsActivity($services) {
        $serviceModels = self::_initServiceModel();
        $friendsActivity = array();

        foreach ($services as $service => $token) {     // iterate through active services
            if (method_exists($serviceModels[$service], 'getFriendsActivity')) {
                $friendsActivity = array_merge($friendsActivity,
                    $serviceModels[$service]->getFriendsActivity());
            }
        }
        return $friendsActivity;
    }

}