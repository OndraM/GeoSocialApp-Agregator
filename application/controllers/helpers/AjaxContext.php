<?php
/**
 * Removes need to specify format parameter in url when providing only one ajax context.
*/
class GSAA_Controller_Action_Helper_AjaxContext
    extends Zend_Controller_Action_Helper_AjaxContext
{
    /**
     * Automatically initialise the first and only context
     */
    public function initContext($format = null) {
        $request = $this->getRequest();
        $action = $request->getActionName();
        $context = $this->getActionContexts($action);

        //check in case multiple contexts DO exist
        if(count($context) === 1) {
            $format = $context[0];

        }
        return parent::initContext($format);
    }
}