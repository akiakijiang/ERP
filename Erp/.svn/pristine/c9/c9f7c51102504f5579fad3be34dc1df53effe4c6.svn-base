<?php
/**
 * ECSHOP supplier_action
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
	
	if ($_POST) {
		#p($_POST);die();
	}
	
	$provider_name = $_REQUEST["provider_name"] ? strval($_REQUEST["provider_name"]) : "";
	$provider_code = $_REQUEST["provider_code"] ? strval($_REQUEST["provider_code"]) : "";
	$order_type = $_REQUEST["order_type"] ? strval($_REQUEST["order_type"]) : "B2C";
	$provider_type = $_REQUEST["provider_type"];
	$provider_category = $_REQUEST["provider_category"];
	$address = $_REQUEST["address"] ? strval($_REQUEST["address"]) : "";
	$hot_brand = $_REQUEST["hot_brand"] ? strval($_REQUEST["hot_brand"]) : "";
	$contact_person = $_REQUEST["contact_person"] ? strval($_REQUEST["contact_person"]) : "";
	$phone = $_REQUEST["phone"] ? strval($_REQUEST["phone"]) : "";
	$email = $_REQUEST["email"] ? strval($_REQUEST["email"]) : "";
	if(!empty($_REQUEST["provider_currency"])) {
		$currency = $_REQUEST["provider_currency"];
	} else {
		$currency = "RMB";
	}	
	
	$provider_bank = $_REQUEST["provider_bank"];
	$bank_account = $_REQUEST["bank_account"];
	$bank_address = $_REQUEST["bank_address"];
	$swift = $_REQUEST["swift"];
	$provider_describe = $_REQUEST["provider_describe"];
	$attribution = $_REQUEST["attribution"];
	$contract_number = $_REQUEST["contract_number"];
	$validity_date = $_REQUEST["validity_date"];
	$stop = $_REQUEST["stop"];
	
	$other_guarantee = $_REQUEST["other_guarantee"] ? strval($_REQUEST["other_guarantee"]) : "";

	
	$contact_person = $_REQUEST["contact_person"] ? strval($_REQUEST["contact_person"]) : "";
	$phone = $_REQUEST["phone"] ? strval($_REQUEST["phone"]) : "";
	$email = $_REQUEST["email"] ? strval($_REQUEST["email"]) : "";
	
	// 联系人
	$contact_person_id = $_REQUEST["contact_person_id"];
	$contact_person_name = $_REQUEST["contact_person_name"];
	$contact_person_sex = $_REQUEST["contact_person_sex"];
	$contact_person_phone_no = $_REQUEST["contact_person_phone_no"];
	$contact_person_email = $_REQUEST["contact_person_email"];
	$contact_person_qq = $_REQUEST["contact_person_qq"];
	$contact_person_msn = $_REQUEST["contact_person_msn"];
	$contact_person_birthday = $_REQUEST["contact_person_birthday"];
	$contact_person_marriage_status = $_REQUEST["contact_person_marriage_status"];
	$contact_person_education = $_REQUEST["contact_person_education"];
	$contact_person_work_time = $_REQUEST["contact_person_work_time"];
	$contact_person_taboo = $_REQUEST["contact_person_taboo"];
	$contact_person_notes = $_REQUEST["contact_person_notes"];

	// 联系方式
	$provider_address_id = $_REQUEST['provider_address_id'];
	$provider_address_name = $_REQUEST["provider_address_name"];
	$product_address = $_REQUEST["product_address"];
	$product_person = $_REQUEST["product_person"];
	$product_phone = $_REQUEST["product_phone"];
	$provider_id = $_REQUEST["provider_id"];

	$action	= $_REQUEST["action"] ? strval($_REQUEST["action"]) : ""; 
	$action_note = $_REQUEST["action_note"] ? strval($_REQUEST["action_note"]) : ""; 
	
	$provider_id = $_REQUEST['provider_id'];
	
	$operator = $_SESSION['admin_name'];
	if ($operator === null) {
		die("请登陆");
	}
	
	if ($action == "add") {
		$providerSQL = "insert into ".$ecs->table('provider')."(provider_name, provider_code, provider_order_type, provider_type,  address, hot_brand, contact_person, phone, email, other_guarantee, provider_status, apply_time,currency,provider_bank,bank_account,bank_address,swift,provider_describe,attribution,contract_number,validity_date,stop) 
			values('$provider_name', '$provider_code', '$order_type', $provider_type, '$address', '$hot_brand', '$contact_person', '$phone', '$email', '$other_guarantee', 1, now(),'$currency','$provider_bank','$bank_account','$bank_address','$swift','$provider_describe','$attribution','$contract_number','$validity_date','$stop')";
		$db->query($providerSQL);
		$provider_id = $db->insert_id();
        require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
		$SupplierRebateAccount = new stdClass();
		$SupplierRebateAccount->supplierId = $provider_id;
		__SupplierRebateCall('createSupplierRebateAccount', $SupplierRebateAccount);
		
		// 联系方式
		$address_length = count($product_address);
		for ($i = 0; $i < $address_length; $i++) {
			if ($provider_address_name[$i] != "" && $product_address[$i] != "" && $product_person[$i] != "" && $product_phone[$i] != "") {
				$addressSQL = "insert into " . $ecs->table('provider_address') . "(provider_address_name, provider_id, product_address, product_person, product_phone) 
					values('$provider_address_name[$i]', '$provider_id', '$product_address[$i]', '$product_person[$i]', '$product_phone[$i]')";
				$db->query($addressSQL);
			}
		}
		
		// 商品类别
		$category_length = count($provider_category);
		for ($i = 0; $i < $category_length; $i++) {
			$categorySQL = "insert into " . $ecs->table('provider_category') . "(provider_id, cat_id, provider_category_status) 
				values($provider_id, $provider_category[$i], 1)";
			$db->query($categorySQL);
			$provider_category_id = $db->insert_id();
			$category_action_sql = "insert into " . $ecs->table('provider_category_action') . "(provider_category_id, provider_category_action_user, provider_category_action_time, provider_category_status) 
				values($provider_category_id, '$operator' ,now(), 1)";
			$db->query($category_action_sql);
		}
		
		// 联系人
		$contact_people_length = count($contact_person_name);
		for ($i = 0; $i < $contact_people_length; $i++) {
			if ($contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "insert into " . $ecs->table('provider_contact_person') . "(provider_id, name, sex, phone_no, email, qq, msn, birthday, marriage_status, education, work_time, taboo, notes) 
					values('$provider_id', '$contact_person_name[$i]', '$contact_person_sex[$i]', 
					'$contact_person_phone_no[$i]', '$contact_person_email[$i]', '$contact_person_qq[$i]', 
					'$contact_person_msn[$i]', '$contact_person_birthday[$i]', '$contact_person_marriage_status[$i]',
					'$contact_person_education[$i]', '$contact_person_work_time[$i]', '$contact_person_taboo[$i]', '$contact_person_notes[$i]'
					)";
				$db->query($contact_person_sql);
			}
		}
		
		$provider_action_sql = "insert into " . $ecs->table('provider_action') . "(provider_id, provider_action_user, provider_action_time, provider_action_note, provider_status) 
			values($provider_id, '$operator', now(), '添加供应商：$provider_name' , 1)";
		$db->query($provider_action_sql);
		
		echo "<script type=\"text/javascript\">alert('添加成功');location.href='supplier-info.php';</script>";
	} else if ($action == "mod") {
		
		// {{{ 修改分类 by ychen
		$querySQL = "select cat_id from " . $ecs->table('provider_category') . " where ".$ecs->table('provider_category').".provider_id = '$provider_id' and provider_category_status = 1";
		$rst = $db->query($querySQL);
		$category_array = array();		//数据库中的类别
		while ($data = $db->fetch_array($rst)) {
			$category_array[] = $data['cat_id'];
		}

		for ($i = 0; $i < count($provider_category); $i++) {
			if (($index = array_search($provider_category[$i], $category_array)) === false) {
				$insert_sql = "insert into " . $ecs->table('provider_category') . "(provider_id, cat_id, provider_category_status) values($provider_id, $provider_category[$i], 1)";
				
				$db->query($insert_sql);
				$provider_category_id = $db->insert_id();
				$category_action_sql = "insert into " . $ecs->table('provider_category_action') . "(provider_category_id, provider_category_action_user, provider_category_action_time, provider_category_status) 
					values($provider_category_id, '$operator', now(), 1)";
				$db->query($category_action_sql);
			} else {
				unset($category_array[$index]);
			}
		}
		
		foreach ($category_array as $key => $val) {
			$val = (int)$val;
		
			$query_sql = "select provider_category_id from " . $ecs->table('provider_category') . " where cat_id = $val and provider_id = $provider_id and provider_category_status = 1";
			
			$rst = $db->query($query_sql);
			$data = $db->fetch_array($rst);
			$provider_category_id = $data['provider_category_id'];
			
			if ($provider_category_id !== null) {
				$delete_sql = "update " . $ecs->table('provider_category') . " set provider_category_status = 2 where provider_category_id = $provider_category_id";
				
				$db->query($delete_sql);
				$category_action_sql = "insert into " . $ecs->table('provider_category_action') . "
					(provider_category_id, provider_category_action_user, provider_category_action_time, provider_category_status, provider_category_action_note) 
					values($provider_category_id, '$operator', now(), 2, '$action_note')";
				$db->query($category_action_sql);
			}
		}
		
		// }}}
		// {{{ 修改供应商信息
		
		$sql_u = "UPDATE ".$ecs->table('provider')." SET 
			provider_name = '$provider_name',
			provider_code = '$provider_code',
			provider_order_type = '$order_type',
			provider_type = '$provider_type',
			address = '$address',
			hot_brand = '$hot_brand',
			provider_bank = '$provider_bank',
			bank_account = '$bank_account',
			contact_person = '$contact_person',
			phone = '$phone',
			email = '$email',
			other_guarantee = '$other_guarantee',
			currency = '$currency',
			bank_address = '$bank_address',
			swift = '$swift',
			provider_describe = '$provider_describe',
			attribution = '$attribution',
			contract_number = '$contract_number',
			validity_date = '$validity_date',
			stop = '$stop'
			WHERE provider_id = '$provider_id' ";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			#echo "<script type=\"text/javascript\">alert('编辑失败');document.write(\"$sql_u\");location.href='buyer_supplier-manage.php';</script>";
			#die();
		}
		
		// }}}
		// {{{ 修改取货地址
		
		$address_length = count($product_address);
		for ($i = 0; $i < $address_length; $i++) {
			if ($provider_address_id[$i] && $provider_address_name[$i] != "" && $product_address[$i] != "" && $product_person[$i] != "" && $product_phone[$i] != "") {
				$addressSQL = "UPDATE " . $ecs->table('provider_address') . " 
					SET provider_address_name = '$provider_address_name[$i]', product_address = '$product_address[$i]', product_person = '$product_person[$i]', product_phone = '$product_phone[$i]'
					WHERE provider_address_id = '$provider_address_id[$i]' ";
				$db->query($addressSQL);
			}elseif ($provider_address_name[$i] != "" && $product_address[$i] != "" && $product_person[$i] != "" && $product_phone[$i] != "") {
				$addressSQL = "insert into " . $ecs->table('provider_address') . "(provider_address_name, provider_id, product_address, product_person, product_phone) 
					values('$provider_address_name[$i]', '$provider_id', '$product_address[$i]', '$product_person[$i]', '$product_phone[$i]')";
				$db->query($addressSQL);
			}
		}
		
		// }}}
		// {{{ 修改联系人
		
		$contact_person_id = $_REQUEST['contact_person_id'];
		$contact_person_length = count($contact_person_name);
		for ($i = 0; $i < $contact_person_length; $i++) {
			if ($contact_person_id[$i] && $contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "UPDATE " . $ecs->table('provider_contact_person') . " 
					SET name = '$contact_person_name[$i]', sex = '$contact_person_sex[$i]', phone_no = '$contact_person_phone_no[$i]',
					email = '$contact_person_email[$i]', qq = '$contact_person_qq[$i]', msn = '$contact_person_msn[$i]', 
					birthday = '$contact_person_birthday[$i]', marriage_status = '$contact_person_marriage_status[$i]', education = '$contact_person_education[$i]',
					work_time = '$contact_person_work_time[$i]', taboo = '$contact_person_taboo[$i]', notes = '$contact_person_notes[$i]'
					WHERE provider_contact_person_id = '$contact_person_id[$i]' ";
				$db->query($contact_person_sql);
			}elseif ($contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "insert into " . $ecs->table('provider_contact_person') . "(provider_id, name, sex, phone_no, email, qq, msn, birthday, marriage_status, education, work_time, taboo, notes) 
					values('$provider_id', '$contact_person_name[$i]', '$contact_person_sex[$i]', 
					'$contact_person_phone_no[$i]', '$contact_person_email[$i]', '$contact_person_qq[$i]', 
					'$contact_person_msn[$i]', '$contact_person_birthday[$i]', '$contact_person_marriage_status[$i]',
					'$contact_person_education[$i]', '$contact_person_work_time[$i]', '$contact_person_taboo[$i]', '$contact_person_notes[$i]'
					)";
				$db->query($contact_person_sql);
			}
		}
		// }}}
		$actionSQL = "insert into " . $ecs->table('provider_action') . "(provider_id, provider_action_user, provider_action_time, provider_action_note, provider_status) 
			values($provider_id, '$operator', now(), '修改供应商：$provider_name', 1)";
		$db->query($actionSQL);
		
		echo "<script type=\"text/javascript\">alert('编辑成功');location.href='buyer_supplier-manage.php';</script>";
		
	} else if ($action == "delete") {
		$statusSQL = "update " . $ecs->table('provider') . " set provider_status = 4 where provider_id = $provider_id";
		$actionSQL = "insert into " . $ecs->table('provider_action') . "(provider_id, provider_action_user, provider_action_time, provider_action_note, provider_status) 
			values($provider_id, '$operator', now(), '删除供应商：$action_note', 4)";
		$db->query($statusSQL);
		$db->query($actionSQL);
		echo "<script type=\"text/javascript\">alert('删除成功');location.href='buyer_supplier-manage.php';</script>";
	}
?>