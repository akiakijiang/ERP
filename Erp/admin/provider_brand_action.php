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
 * $Author: ychen $
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
	$provider_type = $_REQUEST["provider_type"];
	$brand = $_REQUEST["brand"] ? strval($_REQUEST["brand"]) : "";
	$company_address = $_REQUEST["company_address"] ? strval($_REQUEST["company_address"]) : "";
	
	// 联系人
	$provider_brand_person_id = $_REQUEST["provider_brand_person_id"];
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


	$action	= $_REQUEST["action"] ? strval($_REQUEST["action"]) : ""; 
	$action_note = $_REQUEST["action_note"] ? strval($_REQUEST["action_note"]) : ""; 
	
	$provider_brand_id = $_REQUEST['provider_brand_id'];
	
	$operator = $_SESSION['admin_name'];
	if ($operator === null) {
		die("请登陆");
	}
	
	if ($action == "add") {
		$provider_band_sql = "insert into " . $ecs->table('provider_brand_manage') . "(provider_name, provider_type, brand, company_address) 
			values('$provider_name', '$provider_type', '$brand', '$company_address')";
		
		$db->query($provider_band_sql);
		$provider_brand_id = $db->insert_id();
		
		// 联系人
		$contact_people_length = count($contact_person_name);
		for ($i = 0; $i < $contact_people_length; $i++) {
			if ($contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "insert into " . $ecs->table('provider_brand_person') . "(provider_brand_id, name, sex, phone_no, email, qq, msn, birthday, marriage_status, education, work_time, taboo, notes) 
					values('$provider_brand_id', '$contact_person_name[$i]', '$contact_person_sex[$i]', 
					'$contact_person_phone_no[$i]', '$contact_person_email[$i]', '$contact_person_qq[$i]', 
					'$contact_person_msn[$i]', '$contact_person_birthday[$i]', '$contact_person_marriage_status[$i]',
					'$contact_person_education[$i]', '$contact_person_work_time[$i]', '$contact_person_taboo[$i]', '$contact_person_notes[$i]'
					)";
				$db->query($contact_person_sql);
			}
		}
		
		echo "<script type=\"text/javascript\">alert('添加成功');location.href='provider_brand_info.php';</script>";
	} else if ($action == "mod") {
		
		// {{{ 修改供应商信息
		
		$sql_u = "UPDATE ".$ecs->table('provider_brand_manage')." SET 
			provider_name = '$provider_name',
			provider_type = '$provider_type',
			brand = '$brand',
			company_address = '$company_address'
			WHERE provider_brand_id = '$provider_brand_id' ";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			#echo "<script type=\"text/javascript\">alert('编辑失败');document.write(\"$sql_u\");location.href='buyer_supplier-manage.php';</script>";
			#die();
		}
		
		// }}}
		// {{{ 修改联系人
		
		$contact_person_length = count($contact_person_name);
		for ($i = 0; $i < $contact_person_length; $i++) {
			if ($provider_brand_person_id[$i] && $contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "UPDATE " . $ecs->table('provider_brand_person') . " 
					SET name = '$contact_person_name[$i]', sex = '$contact_person_sex[$i]', phone_no = '$contact_person_phone_no[$i]',
					email = '$contact_person_email[$i]', qq = '$contact_person_qq[$i]', msn = '$contact_person_msn[$i]', 
					birthday = '$contact_person_birthday[$i]', marriage_status = '$contact_person_marriage_status[$i]', education = '$contact_person_education[$i]',
					work_time = '$contact_person_work_time[$i]', taboo = '$contact_person_taboo[$i]', notes = '$contact_person_notes[$i]'
					WHERE provider_brand_person_id = '$provider_brand_person_id[$i]' ";
				$db->query($contact_person_sql);
			}elseif ($contact_person_name[$i] != "" && $contact_person_sex[$i] != "" && $contact_person_phone_no[$i] != "") {
				$contact_person_sql = "insert into " . $ecs->table('provider_brand_person') . "(provider_brand_id, name, sex, phone_no, email, qq, msn, birthday, marriage_status, education, work_time, taboo, notes) 
					values('$provider_brand_id', '$contact_person_name[$i]', '$contact_person_sex[$i]', 
					'$contact_person_phone_no[$i]', '$contact_person_email[$i]', '$contact_person_qq[$i]', 
					'$contact_person_msn[$i]', '$contact_person_birthday[$i]', '$contact_person_marriage_status[$i]',
					'$contact_person_education[$i]', '$contact_person_work_time[$i]', '$contact_person_taboo[$i]', '$contact_person_notes[$i]'
					)";
				$db->query($contact_person_sql);
			}
		}
		// }}}
		
		echo "<script type=\"text/javascript\">alert('编辑成功');location.href='provider_brand_manage.php';</script>";
		
	} else if ($action == "delete") {
		$statusSQL = "update " . $ecs->table('provider_brand_manage') . " set status = 1 where provider_brand_id = $provider_brand_id";
		$db->query($statusSQL);
		echo "<script type=\"text/javascript\">alert('删除成功');location.href='provider_brand_manage.php';</script>";
	}
?>