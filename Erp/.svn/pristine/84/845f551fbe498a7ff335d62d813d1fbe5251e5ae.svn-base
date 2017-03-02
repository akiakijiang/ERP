<?php
/**
 * ECSHOP
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: Zandy $
 * $Date: 2007-05-15 16:52:17 +0800 (星期二, 15 五月 2007) $
 * $Id$
 * @see : ecshop\admin\includes\inc_menu.php
 * @see : ecshop\languages\zh_cn\admin\common.php
 * @see : ecshop\languages\zh_cn\admin\priv_action.php
*/
	define('IN_ECS', true);
	require('includes/init.php');
	
	$id = $_REQUEST['id'];	
	$action	= $_REQUEST["action"] ? strval($_REQUEST["action"]) : "";
	$name = $_REQUEST["name"] ? strval($_REQUEST["name"]) : "";
	$address = $_REQUEST["address"] ? strval($_REQUEST["address"]) : "";
	$web_site = $_REQUEST["web_site"] ? strval($_REQUEST["web_site"]) : "";
	$phone_no = $_REQUEST["phone_no"] ? strval($_REQUEST["phone_no"]) : "";
	
	if ($action == "add") {
		$sql = "insert into " . $ecs->table('carrier') . "(address, phone_no, web_site, name) values('$address', '$phone_no', '$web_site', '$name');";		
	} elseif ($action == "edit") {
		$sql = "update " . $ecs->table('carrier') . " set address = '$address', phone_no = '$phone_no', web_site = '$web_site', name = '$name' where carrier_id = $id";
	} elseif ($action == "delete") {
		$sql = "delete from " . $ecs->table('carrier') . " where carrier_id = $id";
	} else {
		die("action error: $action");
	}
	
	$db->query($sql);
	
	Header("Location: carrier.php"); 
	
	/*-- Create table ecs_carrier 
create table ecs_carrier (
  carrier_id int auto_increment primary key,
  address  text not null,
  phone_no varchar(32) not null,
  web_site varchar(100),
  name varchar(100)
);*/
?>