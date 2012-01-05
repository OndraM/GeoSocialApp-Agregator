<?php
/**
 * Wrapper class to provide unified access to all LBS model classes at once
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
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
     * @param array  $services Array of services to search in form ($serviceType = on)
     * @param double $lat Latitude
     * @param double $long Longitude
     * @param int    $radius Radius to search
     * @param string $term Search term
     * @return array Array of all nearby POIs
     */
    public static function loadNearbyPois($services, $lat, $long, $radius, $term) {
        $serviceModels = self::_initServiceModel();
        $pois = array();

        foreach ($services as $serviceType => $serviceState) {      // iterate through all params
            if (array_key_exists($serviceType, $serviceModels)) {   // only parameters representing POIs
                if ($serviceState) {                                // use service
                    $pois = array_merge(
                            $pois,
                            $serviceModels[$serviceType]->getNearbyPois($lat, $long, $radius, $term));
                }
            }
        }
        foreach ($serviceModels as $modelId => $model) {    // iterate through availabe LBS models

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

        foreach ($pois as $poiId => $poiType) {                   // iterate through all params
            if (array_key_exists($poiType, $serviceModels)) {     // only parameters representing POIs
                $poi = $serviceModels[$poiType]->getDetail($poiId);
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