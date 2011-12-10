<?php
/**
 * Helper for getting OAuth url of specific service
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */

class GSAA_View_Helper_GetServiceAuthUrl extends Zend_View_Helper_Abstract
{

    /**
     * Get the url.
     *
     * @param string $modelName the full model name
     * @return string
     */
    public function getServiceAuthUrl($modelName)
    {
        $return = '';
        if (method_exists($modelName, 'getAuthUrl'))
            $return = $modelName::getAuthUrl();
        return $return;
    }

}
