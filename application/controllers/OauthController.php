<?php

class OauthController extends Zend_Controller_Action
{

    public function init()
    {
        $this->session = new Zend_Session_Namespace('GSAA');
        if (!isset($this->session->services)) {
            $this->session->services = array();
        }
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');        
        $ajaxContext->addActionContext('is-authenticated', 'json')
                    ->initContext();
    }

    public function callbackAction()
    {
        //$this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
        
        $services = Zend_Registry::get('var')->services;
        
        $service = $this->_getParam('service');
        $code = $this->_getParam('code');        
        
        if (array_key_exists($service, $services)) {
            $model = new $services[$service]['model']();
            
            $token = $model->requestToken($code);
            
            if ($token) {            
                $this->session->services[$service] = $token;
                $this->view->status = true;
            } else { // token not obtained
                $this->view->status = false;
            }
        }
    }
    
    /**
     * Check whether user is authenticated and token is valid.
     */
    
    public function isAuthenticatedAction()
    {
        $services = Zend_Registry::get('var')->services;
        
        $service = $this->_getParam('service');
        $this->view->status = false;
        if (isset($this->session->services[$service])) {
            $model = new $services[$service]['model']();
            $this->view->status = $model->checkToken($this->session->services[$service]);
            if (!$this->view->status) { // clear token from session, as it is not valid
                unset($this->session->services[$service]);
            }
        }
    }    
    
    /**
     * Destroy session
     */
    public function destroyAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $this->session->unsetAll();
    }


}

