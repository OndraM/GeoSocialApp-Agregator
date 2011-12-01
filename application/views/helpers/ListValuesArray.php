<?php
/**
 * Helper for listing specified field of pois
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */


class GSAA_View_Helper_ListValuesArray extends Zend_View_Helper_Abstract
{

    /**
     *
     * @param array $values 
     * @param string $field Field type
     * @return string 
     */
    public function listValuesArray($valuesArray, $field)
    {
        $return = "";
        if (count($valuesArray) < 1) return "";
        
        foreach ($valuesArray as $values) {
            if (count($values) < 1) return "";
            
            foreach ($values as $type => $value) {
                if ($field == 'links') {
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($type)
                            . "<a href=\"" . $this->view->escape($value) . "\""
                            . " class=\"external\""
                            . ">"
                            . $this->view->escape($value)
                            . "</a>"
                            . "</li>\n";
                } elseif ($field == 'tips') {
                    $date = new Zend_Date($value['date']);
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($type)
                            . $this->view->escape($value['text'])
                            . " ("
                            . $date->get(Zend_Date::DATETIME_MEDIUM)
                            .")"
                            . "</li>\n";
                } else {
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($type)
                            . $this->view->escape($value)
                            . "</li>\n";
                }
            }
        }
        return "<ul>\n" . $return . "</ul>\n";
    }

}