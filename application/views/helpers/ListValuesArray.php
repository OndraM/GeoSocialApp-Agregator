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
        foreach ($valuesArray as $type => $values) {
            if (count($values) < 1) return "";
            
            if ($field == 'links') {
                foreach ($values as $arrI => $arrV) {
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($type)
                            . "<a href=\"" . $this->view->escape($arrV) . "\""
                            . " class=\"external\""
                            . ">"
                            . $this->view->escape($arrI)
                            . "</a>"
                            . "</li>\n";
                }
            } elseif ($field == 'tips') {
                foreach ($values as $arrV) {
                    $date = new Zend_Date($arrV['date']);
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($type)
                            . $this->view->escape($arrV['text'])
                            . " ("
                            . $date->get(Zend_Date::DATETIME_MEDIUM)
                            .")"
                            . "</li>\n";
                }
            } else {
                $return .= "\t<li>"
                        . $this->view->serviceIcon($type)
                        . $this->view->escape($values)
                        . "</li>\n";
            }
        }
        return "<ul>\n" . $return . "</ul>\n";
    }

}
