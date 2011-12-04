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
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
        
        $services = Zend_Registry::get('var')->services;
        
        $service = $this->_getParam('service');
        $code = $this->_getParam('code');        
        
        if (array_key_exists($service, $services)) {
            $client = new Zend_Http_Client();
            $queryParams = array(
                'client_id'     => $services[$service]['model']::CLIENT_ID,
                'client_secret' => $services[$service]['model']::CLIENT_SECRET,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => rawurldecode('http://gsaa.local/oauth/callback/service/' . $service),
                'code'          => $code
            );
            $client->setUri($services[$service]['model']::OAUTH_CALLBACK);
            $client->setParameterGet($queryParams);
            
            try {
                $response = $client->request();
            } catch (Zend_Http_Client_Exception $e) {  // timeout or host not accessible
                return;
            }   

            // error in response
            if ($response->isError()) {
                // TODO: error handling!
                return;
            }
            
            $result = Zend_Json::decode($response->getBody());
            
            $token = $result['access_token'];
            $this->session->services[$service] = $token;
            $this->view->status = true;
           

        }
    }
    
    public function isAuthenticatedAction()
    {
        /*$this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();*/
        
        $service = $this->_getParam('service');
        $this->view->status = false;
        if (isset($this->session->services[$service])) {
            // TODO: check token is stil valid
            $this->view->status = true;
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

