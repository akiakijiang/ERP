<?php
/*
 * Created on 2013-10-10
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once ('includes/debug/lib_log.php');
require_once('includes/lib_sinri_DataBasic.php');

if(isset($_REQUEST['BPSN'])){
    $BPSN=$_REQUEST['BPSN'];
} else {
    die("Give the parameter BPSN!");
}
$BPSN_MAPPINGS=get_BPSN_mapping($BPSN);
 foreach ($BPSN_MAPPINGS as $no => $BPSN_MAPPING) {
    	$BPSN_MAPPINGS[$no]['shipping_status_name'] = get_shipping_status($BPSN_MAPPING['shipping_status']);
    }
//pp($BPSN_MAPPINGS);exit();
$smarty->assign('BPSN_MAPPINGS', $BPSN_MAPPINGS);
$smarty->display('oukooext/wms_batch_pick_detail.htm');
?>
