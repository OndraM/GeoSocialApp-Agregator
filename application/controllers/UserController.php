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
        $friendsRaw = array();
        
        foreach($this->_serviceModels as $model) {
            if (method_exists($model, 'getFriendsActivity')) {
                $friendsRaw = array_merge($friendsRaw,
                    $model->getFriendsActivity());
            }
        }

        if (count($friendsRaw) > 0) {
            $friendsCheckins = $this->_mergeFriendsCheckins($friendsRaw);
        }

        $this->view->friends = $friendsCheckins;
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
        $model = $this->_serviceModels['fq'];
        d($model->getFriendsActivity());
        
    }

    /**
     * Merge checkins from the same friends and get only the most recent for each unique friend.
     *
     * @param array $friendsRaw Array of GSAA_Model_Checkin
     * @return array Array of GSAA_Model_Checkin, with only the most recent checkin of each friend
     */

    protected function _mergeFriendsCheckins($friendsRaw) {
        $friendsCheckins = array();
        for ($x = 0; $x < count($friendsRaw); $x++) {
            $sameFriendCheckins = array(); // array for checkins of the same friend
            if (is_null($friendsRaw[$x])) continue; // skip already merged friends

            $friendXName = Zend_Filter::filterStatic($friendsRaw[$x]->userName, 'StringToLower');
            $friendXName = Zend_Filter::filterStatic($friendXName, 'ASCII', array(), array('GSAA_Filter'));
            for ($y = 0; $y < count($friendsRaw); $y++) {
                if (is_null($friendsRaw[$y])) continue; // skip already merged items
                if ($x == $y) continue; // skip the same POI
                $friendYName = Zend_Filter::filterStatic($friendsRaw[$y]->userName, 'StringToLower');
                $friendYName = Zend_Filter::filterStatic($friendYName, 'ASCII', array(), array('GSAA_Filter'));

                $similarPercent = 0;
                similar_text($friendXName, $friendYName, $similarPercent);

                if ($similarPercent > 90) {
                    $sameFriendCheckins[] = $friendsRaw[$y]; // add all same persons checkins (even more then one)
                    $friendsRaw[$y] = null; // set it to null, so it won't be checked again
                }
            }
            // when more checkins from same person is present, find the most recet
            if (count($sameFriendCheckins) > 0) { // some checkins mateched
                $sameFriendCheckins[] = $friendsRaw[$x]; // add the parent match
                $friendsRaw[$x] = null; // set it to null, so it won't be checked again
                $dates = array();
                foreach ($sameFriendCheckins as $index => $value) {
                    $dates[$index]  = $value->date; // put dates in special array
                }
                array_multisort($dates, SORT_DESC, $sameFriendCheckins); // sort by dates from the most recet
                $friendsCheckins[] = $sameFriendCheckins[0]; // add only the most recent checkin
            } else { // person is there only once => just add it to final array
                $friendsCheckins[] = $friendsRaw[$x];
            }
        }
        return $friendsCheckins;
    }


}

