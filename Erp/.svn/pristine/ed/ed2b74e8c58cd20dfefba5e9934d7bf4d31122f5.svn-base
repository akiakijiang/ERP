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

/* 涉及文件操作，屏蔽 by ychen 2009/07/31 */
die();

	define('IN_ECS', true);
	require('includes/init.php');
	
	require('config.vars.php');
	
	$upload_dir = UPLOAD_DIR;
	
	$file_name = $_FILES['file']['name'];
	$suffix = substr($file_name, strrpos($file_name, "."));
	
	$type = $_REQUEST["type"];
	$size = $_FILES['file']['size'];

	$is_success = true;
	
	$operator = $_SESSION['admin_name'];
	
	if ($operator === null) {
		$is_success = false;
	} else {
		if (is_uploaded_file($_FILES['file']['tmp_name'])) {
			$insert_sql = "insert into " . $ecs->table('provider_uploaded') . "(status, type, length, operate_datetime, operator, file_name) values(0 , $type, $size, now(), '$operator' ,'$file_name')";
			$db->query($insert_sql);
			$provider_uploaded_id = $db->insert_id();
			$upload_file = $upload_dir . $provider_uploaded_id . "$suffix" ;
			
			if (!is_dir($upload_dir)) {
				if (!mkdir($upload_dir)) {
					$is_success = false;			
				}
			}
		}
		
		if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
			$is_success = true;
			
		} else {
			$delete_sql = "delete from " . $ecs->table('provider_uploaded') . " where provider_uploaded_id = $provider_uploaded_id";
			$db->query($delete_sql);
			$is_success = false;
		}
	}
	
	if ($is_success) {
		echo "<script type=\"text/javascript\">alert('上传成功');location.href='buyer_production-batch-view.php';</script>";
	} else {
		echo "<script type=\"text/javascript\">alert('上传失败');location.href='buyer_production-batch.php';</script>";
	}
?>