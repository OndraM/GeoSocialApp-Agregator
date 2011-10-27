<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    
    protected function _initEnvironment() {
        mb_internal_encoding('utf-8');
        date_default_timezone_set('Europe/Prague');

        ini_set('session.gc_maxlifetime', 60*60*24*60);     // 60 days
        ini_set('session.cookie_lifetime', 60*60*24*180);   // 180 days
    }
    
    protected function _initDoctype() {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');
    }
    
    /**
     * Init global variables from config file
     */
    
    public function _initVar()
    {
        Zend_Registry::set('var', (object) $this->getOption('var'));
    }



}

