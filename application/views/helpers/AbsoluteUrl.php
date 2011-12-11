<?php
/**
 * Get absolute url
 *
 * Source: http://f-pig.blogspot.com/2007/10/try-zend-framework-vol5-always-absolute.html
 */

class GSAA_View_Helper_AbsoluteUrl extends Zend_View_Helper_Url
{

    /**
     * Generates an absolute url given the name of a route.
     *
     * @access public
     *
     * @param  array $urlOptions Options passed to the assemble method of the Route object.
     * @param  mixed $name The name of a Route to use. If null it will use the current Route
     * @param  bool $reset Whether or not to reset the route defaults with those provided
     * @param  bool $encode Tells to encode URL parts on output
     * @return string Absolute url for the link href attribute.
     */
  public function absoluteUrl(array $urlOptions = array(), $name = null, $reset = false, $encode = true)
  {
    $server = Zend_Controller_Front::getInstance()->getRequest()->getServer();
    $url = parent::url($urlOptions, $name, $reset, $encode);
    $protocol = explode('/', $server['SERVER_PROTOCOL']);
    return strtolower(trim($protocol[0])) . '://' . $server['HTTP_HOST'] . $url;
  }

}