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
function smarty_modifier_subcnstr($string, $length = 80, $etc = '...',
                                  $break_words = false, $middle = false)
{
    if ($length == 0)
        return '';

    $str = trim($string);
    
    $newstr = "";
    $a = array();
    $len = 0;
    for ($i=0; $i<$length; $i++) {
    	$b = sub_str($string, $i, 1, false);
    	$a[] = $b;
    	$len += strlen($b) > 1 ? 1 : (strlen($b) ? 0.5 : 0);
    	if ($len >= $length/2) {
    		$a[] = $etc;
    		break;
    	}
    }
    $newstr = join("", $a);

    return $newstr;
}

/* vim: set expandtab: */

?>
