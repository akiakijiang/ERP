<?php
/**
 * ECSHOP order_action
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
	include_once 'function.php';
	
	$action	= $_REQUEST["action"] ? strval($_REQUEST["action"]) : "";
	$order_id	= $_REQUEST["order_id"] ? intval($_REQUEST["order_id"]) : "";
	$inv_payee_new	= $_REQUEST["order_id"] ? strval($_REQUEST["inv_payee_new"]) : "";
	$action_note	= $_REQUEST["action_note"] ? strval($_REQUEST["action_note"]) : "";
	$type = $_REQUEST["type"];
	
	if ($action === "") {
		die("action error: $action");
	}
	if ($order_id === "" && !$_REQUEST['order_sn']) {
		die("order_id - error: $order_id");
	}
	#p($_REQUEST);die();
	
	if ($action == 'ajax') {
		$do = $_REQUEST['do'];
		if ($do == 'getFinanceInvoiceInfo') {
			$order_id = $_REQUEST['order_id'];
			$sql = "SELECT * FROM ".$ecs->table('order_info')." a
				LEFT JOIN ".$ecs->table('order_action')." b ON a.order_id = b.order_id 
				WHERE a.order_id = '$order_id' AND b.order_status = 0 AND b.shipping_status = 0 AND b.pay_status = 0 AND b.invoice_status = 1 
				ORDER BY action_id DESC LIMIT 1 ";
			$getRow1 = $db->getRow($sql);
			
			$sql = "SELECT * FROM ".$ecs->table('order_info')." a
				LEFT JOIN ".$ecs->table('order_action')." b ON a.order_id = b.order_id 
				WHERE a.order_id = '$order_id' AND b.order_status = 0 AND b.shipping_status = 0 AND b.pay_status = 0 AND b.invoice_status = 2 
				ORDER BY action_id DESC LIMIT 1 ";
			$getRow2 = $db->getRow($sql);
			
			die(join("\t\t--\t\t", array($getRow1['inv_payee'], $getRow1['action_note'], $getRow2['action_note'])));
		}elseif ($do == 'gonoModInvoice') {
			// @see line 34
			$order_sn = $_REQUEST['order_sn'];
			$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
			$fetch = $db->fetch_array($query);
			$order_id = $fetch['order_id'];

			$sql_u = "UPDATE ".$ecs->table('order_info')." SET invoice_status = 1 WHERE order_id = '$order_id' AND invoice_status = 2 LIMIT 1 ";
			$db->query($sql_u);
			$affected_rows = $db->affected_rows();
			if (!$affected_rows) {
				die("error");
			}
			$action_note = "发票继续修改";
			$_info = array('order_id' => $order_id, 'invoice_status' => 2, 'action_note' => $action_note);
			orderActionLog($_info);
			die("ok");
		}
	}

	$query_sql = "select order_status, shipping_status, pay_status, invoice_status from " . $ecs->table('order_info') . " where order_id = '$order_id'";
	$query_rst = $db->query($query_sql);
	$query = $db->fetch_object($query_rst);
	$order_status = $query->order_status;
	$shipping_status = $query->shipping_status;
	$pay_status = $query->pay_status;
	$invoice_status = $query->invoice_status;

	$time = time();
	$action_user = $_SESSION['admin_name'];
	$action_time = date("Y-m-d H:i:s");
	
	if ($action == "modify_invoice") {
		$action_note = "财务建议：<br>拟修改发票抬头为: ".$inv_payee_new."<hr>财务备注：".$action_note;
		
		$action_sql = "insert into " . $ecs->table('order_action') . "(order_id, invoice_status, action_time, action_note, action_user) values($order_id, 1, '$action_time', '$action_note', '$action_user')";
		$order_sql = "update " . $ecs->table('order_info') . " set invoice_status = 1 where order_id = '$order_id' or parent_order_id = '$order_id'";
		$db->query($action_sql);
		$db->query($order_sql);
		Header("Location: financial_manage.php?type=f,s_0"); 
	} else if ($action == "confirm_invoice") {
		
		$action_sql = "insert into " . $ecs->table('order_action') . "(order_id, order_status, invoice_status,  action_time, action_user, action_note) values($order_id, 1, 3, '$action_time', '$action_user', '$action_note')";
		$order_sql = "update " . $ecs->table('order_info') . " set invoice_status = 3, order_status = 1, confirm_time = '$time' where order_id = '$order_id' or parent_order_id = '$order_id'";
		if (isset($_REQUEST['inv_payee_confirm']) && $_REQUEST['inv_payee_confirm']) {
			$inv_payee = $_REQUEST['inv_payee_confirm'];
			$order_sql = "update " . $ecs->table('order_info') . " set invoice_status = 3, order_status = 1, confirm_time = '$time', inv_payee = '$inv_payee' where order_id = '$order_id' or parent_order_id = '$order_id'";
		}
		$db->query($action_sql);
		$db->query($order_sql);
		Header("Location: financial_manage.php?type=$type"); 
	} else if ($action == "confirm_pay") {
		$action_sql = "insert into " . $ecs->table('order_action') . "(order_id, pay_status, action_time, action_note, action_user) values($order_id, 2, '$action_time', '$action_note', '$action_user')";
		$order_sql = "update " . $ecs->table('order_info') . " set pay_status = 2, pay_time = $time where order_id = '$order_id' or parent_order_id = '$order_id'";
		$db->query($action_sql);
		$db->query($order_sql);
		Header("Location: financial_manage.php?type=$type"); 
	} else if($action == "confirm_refund") {
		$action_sql = "insert into " . $ecs->table('order_action') . "(order_id, pay_status, action_time, action_note, action_user) values($order_id, 4, '$action_time', '$action_note', '$action_user')";
		$order_sql = "update " . $ecs->table('order_info') . " set pay_status = 4 where order_id = '$order_id' or parent_order_id = '$order_id'";
		$db->query($action_sql);
		$db->query($order_sql);		
		Header("Location: financial_manage.php?type=dt,s_0"); 
	} else {
		die("action error: $action");
	}
?>