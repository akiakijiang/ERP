<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string or inserting $etc into the middle.
 * @link http://smarty.php.net/manual/en/language.modifier.truncate.php
 *          truncate (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param integer
 * @param string
 * @param boolean
 * @param boolean
 * @return string
 */
function smarty_modifier_truncate($string, $length = 80, $etc = '...',
                                  $break_words = false, $middle = false)
{
    if ($length == 0)
        return '';

    if (strlen($string) > $length) {
        $length -= strlen($etc);
        /*if (!$break_words && !$middle) {
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
        }*/
        if(!$middle) {
			return sub_str($string, 0, $length,false). $etc;  // modify by wj 2006-10-08 using sub_str instead of substr
        } else {
			return sub_str($string, 0, $length/2, false) . $etc . sub_str($string, strlen($string)-$length/2, strlen($string), false); // modify by wj 2006-10-08 using sub_str instead of substr
        }
    } else {
        return $string;
    }
}

/* vim: set expandtab: */

?>
