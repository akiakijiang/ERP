<?php
/**
 * 退换货库
 */
define('IN_ECS', true);

require('includes/init.php');
require_once('includes/lib_service.php');
require_once('function.php');
require_once('config.vars.php');

admin_priv('cg_back_goods_check');
// 查询售后档案相关数据
require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
$rma_track = getTrackByTrackId($_REQUEST['trackId'])->resultList->Track;
$rma_track->trackAttribute = getTrackAttributeArrayByTrackId($_REQUEST['trackId']);

$service_type_name = $_CFG['adminvars']['service_type_mapping'][$_REQUEST['service_type']];

$smarty->assign('service', array('rma_tracks' => array($rma_track)));
$smarty->assign('types_array', getTrackAttributeTypeOptions());
$smarty->assign('print_mode', true);
$smarty->assign('service_type_name', $service_type_name);
$smarty->display('v3/print_rma_track.htm');