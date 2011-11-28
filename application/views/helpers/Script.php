<?php
/**
 * Helper for adding javascript snippet to view
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */


class GSAA_View_Helper_Script extends Zend_View_Helper_Abstract
{

    /**
     * Add and pack Javascript code to view script, to be executed on document DOM load.
     * Require jQuery being loaded first.
     * 
     * In case $inPlace is false, script is apended to header (default). 
     * Otherwise its returned as string.
     *
     * @param string $script
     * @param bool $inPlace
     * @param bool $pack
     * @return string 
     */
    public function script($script, $inPlace = false, $pack = true)
    {
        // $script empty
        if (empty($script)) {
            return;
        }

        // remove some nonprinatble characters 1c-1f (cause unexpected string literal error)
        $script = preg_replace('/[\x1c-\x1f]/', '', $script);

        $return = "";
        $output = "$(document).ready(function(){\n"
                . $script
                . "});";

        if ($pack) { // pack script
            $filter = new GSAA_Filter_JavascriptPacker();
            $output = $filter->filter($output);
        }
        
        if (!$inPlace) { // append script into head
            $this->view->headScript()->appendScript($output . "\n");
            return;
        }
        // return script 
        $return .= "<script type=\"text/javascript\">\n"
                . "\t//<![CDATA[\n";
        $return .= $output;
        $return .= "\n"
                . "\t//]]>\n"
                . "</script>\n";
        
        return $return;

    }

}
