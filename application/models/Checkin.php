<?php

class GSAA_Model_Checkin
{
    /**
     * Type of POI {fq, gw, gg, fb, ...}
     */
    public $type    = null;

    /**
     * Unique id of POI
     */
    public $id      = null;

    /**
     * Name of user
     */
    public $userName    = null;

    /**
     * Latitude of checkin
     */
    public $lat     = null;

    /**
     * Longitude of checkin
     */
    public $lng     = null;

    /**
     * URL of user avatar
     */
    public $avatar = null;

    /**
     * Timestamp of checkin
     */
    public $date = null;

    /**
     * Formatted date string
     */
    public $dateFormatted = null;

    /**
     * Name of checkin POI
     */
    public $poiName = null;

    /**
     * Optional checkin comment
     */
    public $comment = null;

    /**
     * Full name of source service
     */
    public $serviceName = null;

    /**
     * Class constructor
     * @param string $type Service type shortcut
     * @param int $timestamp Checkin timestamp
     */
    public function __construct($type, $timestamp) {
        if (empty($type)) throw new InvalidArgumentException('Checkin service not specified');
        $services = Zend_Registry::get('var')->services;
        if (!isset($services[$type])) throw new InvalidArgumentException('Checkin service not found');
        if (!is_numeric($timestamp) || (int) $timestamp != $timestamp )
            throw new InvalidArgumentException('Incorrect checkin timestamp');

        $date = new Zend_Date($timestamp);
        $this->date = $timestamp;
        $this->dateFormatted = $date->get(Zend_Date::DATETIME_MEDIUM);

        $this->type = $type;
        $this->serviceName = $services[$type]['name'];
    }


}
