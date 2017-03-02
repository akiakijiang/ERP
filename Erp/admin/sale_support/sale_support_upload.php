<?php
define('IN_ECS', true);
require('../includes/init.php');
require("../function.php");
if(isset($_POST["sub"]) && $_REQUEST['act'] == "upload"){
	global $db;
	//图片保存目录
	$path = "../upload/";
	//允许上传的文件类型
	$type = array ("jpg","gif","bmp","jpeg","png");
	$message_id = $_REQUEST['message_id'];
	if($_FILES['file']['error']>0){
		$tip = "上传文件有误：".$_FILES['file']['error'];
		header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
	}
	if(!in_array(strtolower(fileext($_FILES['file']['name'])),$type)){
		$text = implode(",",$type);
		$tip = "您只能上传以下类型的文件：".$text;
		header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
	}
	if(!is_numeric($message_id)){
		$tip = "message_id错误:".$message_id;
		header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
	}
	//以时间来命名图片，防止重名
	$name = $message_id;
	$name .= time();
	$name .= rand(1,10);
	$name .= strrchr($_FILES['file']['name'],".");
	$tmp_name = $_FILES['file']['tmp_name'];
	
	$file_name = $path.$name;
	//如果文件路径不存在，就创建一个
	if(!is_dir($path)){
		mkdir($path);
	}
	//为该路径附权限
	@chmod($path,0777);
	if(file_exists($file_name)){
		$tip = "执行冲突，请稍后再试";
		header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
	}else{
		if(move_uploaded_file($tmp_name,$file_name)){
			$pic_desc = trim($_REQUEST['desc']);
			$pic_desc = addslashes($pic_desc);
			$sql = "
			   insert into ecshop.sale_support_message_pic
			   (sale_support_message_id,pic_name,path,pic_status,pic_desc)
			   values
			   ('{$message_id}','{$name}','{$file_name}','OK','{$pic_desc}')
			";
			$db -> query($sql);
			$tip = "上传图片成功";
			header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
		}else{
			$tip = "上传图片失败";
			header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
		}
	}
}

//获取文件后缀名函数
function fileext($filename)
{
	return substr(strrchr($filename, '.'),1);
}
?>