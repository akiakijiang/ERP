<?php

if (isset($_COOKIE['OKTID']) && strlen((string)$_COOKIE['OKTID']) == 32)
{
	$OKTID = $_COOKIE['OKTID'];
}
else
{
	$OKTID = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].microtime(true).mt_rand());
}

$OKTID_time = 60*60*24*7;

setcookie('OKTID', $OKTID, time() + $OKTID_time, '/', COOKIE_DOMAIN);

function clearUserInfo() {
	foreach ($_COOKIE as $k => $v) {
		if ($k != 'OKTID')
			setcookie($k, '', 1, '/', COOKIE_DOMAIN);
	}
	$GLOBALS['sess']->destroy_session();
}
?>