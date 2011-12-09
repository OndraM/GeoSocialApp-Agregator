<?php
/**
 * Class to calculate distance
 */

class GSAA_POI_Distance {
    /**
     * Calculate distance between two coordinates.
     *
     * @param double $lat1 First latitude
     * @param double $long1 First longitude
     * @param double $lat2 Second latitude
     * @param double $long2 Second longitude
     * @return int Distance in meters.
     */
    public static function getDistance($lat1, $long1, $lat2, $long2) {
        return round(   6378
                        * M_PI
                        * sqrt(
                            ($lat2-$lat1)
                                * ($lat2-$lat1)
                            + cos(deg2rad($lat2))
                                * cos(deg2rad($lat1))
                                * ($long2-$long1)
                                * ($long2-$long1))
                        / 180
                        * 1000);
    }
}
