<?php
/**
 * Helper for generating service icon
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */


class GSAA_View_Helper_ServiceIcon extends Zend_View_Helper_Abstract
{

    /**
     *
     * @param type $service
     * @param type $align
     * @return string 
     */
    public function serviceIcon($service, $align = 'left')
    {
        $return = "<img src=\""
        . $this->view->baseUrl() . "/images/icon-{$service}.png\""
        . " alt=\"{$service}\""
        . " class=\"icon-{$align}\" />";        
        return $return;

    }

}
