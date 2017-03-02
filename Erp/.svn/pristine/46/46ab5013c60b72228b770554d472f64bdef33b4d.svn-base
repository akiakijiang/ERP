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
function smarty_modifier_delparam()
{
	$func_get_args = func_get_args();
	if (sizeof($func_get_args) < 2)
	{
		return '';
	}
	#pp($func_get_args);
	$qs = $func_get_args[0];
	if ($qs === null)
	{
		$qs = $_SERVER['QUERY_STRING'];
	}
	#unset($func_get_args[0]);
	$qss = explode("&", $qs);
	$arr = array();
	foreach ($qss as $v)
	{
		$a = explode("=", $v);
		'' != $a[0] && $arr[$a[0]] = $a[1];
	}

	#$func_get_args = $func_get_args[1];
	unset($func_get_args[0]);

	foreach ($arr as $k => $v)
	{
		foreach ($func_get_args as $func_get_arg)
		{
			if (preg_match('/^'.$func_get_arg.'$/', $k))
			{
				unset($arr[$k]);
			}
		}
	}

	$b = array();
	foreach ($arr as $k => $v)
	{
	'' != $k && $b[] = "$k=$v";
	}
	$r = join("&", $b);
	return $r;
}

/* vim: set expandtab: */

?>
