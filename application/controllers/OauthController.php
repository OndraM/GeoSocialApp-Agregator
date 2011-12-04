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
    
    public function isAuthenticatedAction()
    {
        
        $services = Zend_Registry::get('var')->services;
        
        $service = $this->_getParam('service');
        $this->view->status = false;
        if (isset($this->session->services[$service])) {
            $client = new Zend_Http_Client();
            $queryParams = array(
                'oauth_token'   => $this->session->services[$service],
            );
            $client->setUri($services[$service]['model']::SERVICE_URL . '/users/self/checkins');
            $client->setParameterGet($queryParams);
            
            try {
                $response = $client->request();
            } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                // $this->view->status = false; // false by default
                return;
            }   
            if ($response->isSuccessful()) {
                $this->view->status = true;                
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

