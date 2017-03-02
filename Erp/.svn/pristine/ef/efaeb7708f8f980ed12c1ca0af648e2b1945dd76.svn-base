<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty strip_tags modifier plugin
 *
 * Type:     modifier<br>
 * Name:     strip_tags<br>
 * Purpose:  strip html tags from text
 * @link http://smarty.php.net/manual/en/language.modifier.strip.tags.php
 *          strip_tags (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param boolean
 * @return string
 */
function smarty_modifier_strip_tags_lrln($string, $replace_with_space = true)
{
	$r = '';
    if ($replace_with_space)
        $r = preg_replace('!<[^>]*?>!', ' ', $string);
    else
        $r = strip_tags($string);
	$r = str_replace(array("\r", "\n"), array(" ", " "), $r);
	return $r;
}

/* vim: set expandtab: */

?>
