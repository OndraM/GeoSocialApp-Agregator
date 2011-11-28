<?php
/**
 * Helper for listing specified field of pois
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */


class GSAA_View_Helper_ListPoisField extends Zend_View_Helper_Abstract
{

    /**
     *
     * @param array $pois
     * @param string $field
     * @return string 
     */
    public function listPoisField($pois, $field)
    {
        $return = "";
        foreach ($pois as $poi) {
            if ($field == 'links') {
                foreach ($poi->$field as $arrI => $arrV) {
                    $return .= "\t<li>"
                            . $this->view->serviceIcon($poi->type)
                            . "<a href=\"" . $this->view->escape($arrV) . "\""
                            . " target=\"_blank\""
                            . " class=\"external\""
                            . ">"
                            . $this->view->escape($arrI)
                            . "</a>"
                            . "</li>\n";
                }
            } elseif ($field == 'tips') {
                if (isset($poi->$field)) {
                    foreach ($poi->$field as $arrV) {
                        $date = new Zend_Date($arrV['date']);
                        $return .= "\t<li>"
                                . $this->view->serviceIcon($poi->type)
                                . $this->view->escape($arrV['text'])
                                . " ("
                                . $date->get(Zend_Date::DATETIME_MEDIUM)
                                .")"
                                . "</li>\n";
                    }
                }
            } elseif (!empty($poi->$field)) {
                $return .= "\t<li>"
                        . $this->view->serviceIcon($poi->type)
                        . $this->view->escape($poi->$field)
                        . "</li>\n";
            }
        }
        if (empty($return)) {
            return "";
        }
        return "<ul>\n" . $return . "</ul>\n";
    }

}
