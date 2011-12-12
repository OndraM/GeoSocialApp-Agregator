<?php

class GSAA_Model_POI
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
     * Name of POI
     */
    public $name    = null;

    /**
     * Latitude
     */
    public $lat     = null;

    /**
     * Longitude
     */
    public $lng     = null;

    /**
     * Distance in meters from search coords (optional).
     * Not available in POI detail.
     */
    public $distance = null;

    /**
     * Address (optional).
     */
    public $address = null;

    /**
     * Full URL of POI detail in its service (optional).
     * May not be available in compact POI.
     */
    public $url     = null;

    /**
     * Phone (optional).
     * Available only in POI detail.
     */
    public $phone = null;

    /**
     * Associative array of links (optional).
     * Available only in POI detail.
     * Key - Description
     * Value - link url
     */
    public $links    = array();

    /**
     * Array of POI tips (optional).
     * Available only in POI detail.
     * Structure:
     *      array(
     *          id,         // unique ID for tip
     *          text,       // text of tip
     *          date        // date of tip
     *      )
     *
     */
    public $tips    = array();

    /**
     * Array of POI photos array (optional).
     * Available only in POI detail.
     * Structure:
     *      array(
     *          id,         // unique ID for photo
     *          url,        // url to full photo
     *          thumbnail,  // url to photo thumnail (100px square)
     *          title,      // title of photo (optional)
     *          date        // date of photo (optional)
     *      )
     *
     */
    public $photos    = array();

    /**
     * Array of notes (optional).
     * Structure:
     *      array[] = note
     */
    public $notes    = array();

    /**
     * Array of categories (optional).
     * Available only in POI detail.
     * Structure:
     *      array(
     *          id,     // catgoery ID
     *          name,   // category name
     *          icon    // category icon URL (32x32px preffered)
     *      )
     */
    public $categories    = array();

    /**
     * Relevance (quality) of POI.
     * @var double Quality of POI, -5.0 is worst, 5.0 is best. 0 is default.
     */
    public $quality    = 0;


    /**
     * Priority of POI service
     * @var int
     */
    protected $_priority;

    /**
     * Class constructor
     * @param string $type Service type shortcut
     */
    public function __construct($type) {
        if (empty($type)) throw new InvalidArgumentException('POI type not specified');
        $services = Zend_Registry::get('var')->services;
        if (!isset($services[$type])) throw new InvalidArgumentException('POI type not found');

        $this->type = $type;
        $this->_priority = Zend_Registry::get('var')->services[$this->type]['priority'];
    }

    /**
     * Get unique priority of the service.
     * Zero is the lowest possible priority.
     *
     * @return int
     */
    public function getPriority() {
        return $this->_priority;
    }


}
