<?php

class UserController extends Zend_Controller_Action
{

    protected $_serviceModels = array();
    
    public function init()
    {
        $this->session = new Zend_Session_Namespace('GSAA');
        if (!isset($this->session->services)) {
            $this->session->services = array();
        }
        
        foreach (Zend_Registry::get('var')->services as $serviceId => $service) {
            $classname = $service['model'];
            $this->_serviceModels[$serviceId] = new $classname();
        }
        
        $ajaxContext = $this->_helper->getHelper('AjaxContext');        
        $ajaxContext->addActionContext('FIXME', 'json') // TODO: puut friends action here
                    ->initContext();
    }
    
    public function friendsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $friends_raw = array();
        
        foreach($this->_serviceModels as $model) {
            if (method_exists($model, 'getFriendsActivity')) {
                $friends_raw = array_merge($friends_raw,
                    $model->getFriendsActivity());
            }
        }
        d($friends_raw);

        // TODO: merge friends
        // TODO: put in view variables
    }
    
    public function checkinAction() {
        // TODO checkin in specified POIs
    }

    public function testAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        foreach($this->_serviceModels as $model) {
            $user = $model->getUserInfo();
            d($user, $model::TYPE);
        }
        
    }


}

