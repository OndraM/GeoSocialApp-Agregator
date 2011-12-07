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
        
        /*$ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('FIXME', 'json')
                    ->initContext();
         * 
         */
    }
    
    public function friendsAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout->disableLayout();
        }
        $this->view->cLat = $this->_getParam('cLat');
        $this->view->cLng = $this->_getParam('cLng');
        $friends_raw = array();
        
        foreach($this->_serviceModels as $model) {
            if (method_exists($model, 'getFriendsActivity')) {
                $friends_raw = array_merge($friends_raw,
                    $model->getFriendsActivity());
            }
        }
        foreach ($friends_raw as &$friend) {
            $date = new Zend_Date($friend['date']);
            $friend['id'] = 'id-' + substr(md5(uniqid()), 0, 8);
            $friend['dateFormatted'] = $date->get(Zend_Date::DATETIME_MEDIUM);
        }
        // TODO: merge friends (when same user found, get only latest chckin)
        $this->view->friends = $friends_raw;
        $this->view->services = Zend_Registry::get('var')->services;

    }
    
    public function checkinAction() {
        // TODO checkin in specified POIs
    }

    public function testAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        /*foreach($this->_serviceModels as $model) {
            $user = $model->getUserInfo();
            d($user, $model::TYPE);
        }*/
        $model = $this->_serviceModels['fb'];
        d($model->getFriendsActivity());
        
    }


}

