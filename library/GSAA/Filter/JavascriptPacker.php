<?php
/**
 * Pack a given Javascript code
 *
 * @author  Ondřej Machulda <ondrej.machulda@gmail.com>
 *
 */

class GSAA_Filter_JavascriptPacker implements Zend_Filter_Interface
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        Zend_Loader::loadFile("jsmin.php", null, true);
    }

    /**
     * Pack (minify) content of $value
     *
     * @param type $value
     * @return type
     */
    public function filter($value)
    {
        return JSMin::minify($value);
    }
}
