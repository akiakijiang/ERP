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
	require("function.php");
	require("pagination.php");
	
	//admin_priv('purchase_uploadLog');
	
	$csv_suffix = '.out.csv';
	
	$provider_uploaded_id = intval($_GET['provider_uploaded_id']);
	if ($provider_uploaded_id) {
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

		header('Content-type: application/csv');
		header('Content-Disposition: attachment; filename="'.$provider_uploaded_id.$csv_suffix.'"');

		readfile(UPLOAD_DIR.$provider_uploaded_id.$csv_suffix);
		die();
	}
	
	$condition = get_upload_condition(false);
	
	$baseSQL = "select * from " . $ecs->table('provider_uploaded') . " $condition order by provider_uploaded_id desc";
	$pagination = new Pagination();
	$pagination->set_sql($baseSQL, $db);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles/css/css.css" rel="stylesheet" type="text/css">
<link href="styles/css/css_2007.9.8.css" rel="stylesheet" type="text/css">
<link href="styles/css/odiv.css" rel="stylesheet" type="text/css">

<!-- Loading Calendar JavaScript files -->
<script type="text/javascript" src="js/style/zapatec/utils/zapatec.js"></script>
<script type="text/javascript" src="js/style/zapatec/zpcal/src/calendar.js"></script>
<script type="text/javascript" src="js/style/zapatec/zpcal/lang/calendar-en.js"></script>
<link rel="stylesheet" href="js/style/zapatec/zpcal/themes/winter.css" />

<title> 采购管理模块 — 商品批量上传查看</title>
<style type="text/css">
.Button4,.Button3{
text-decoration:none;
color:#000;
margin:5px;
}
.Menu_li2{
width:19%;
}
.Menu_li3{
width:19%;
}
.Table3_Bo {
float:none;
clear:both;
}
.Table3_Bo table td{
width:auto;
}
.Table3_Bo table th.w1{
width:5%;
}
.Table3_Bo table th.w2{
width:15%;
}
.Table3_Bo table table{
border:0;
}
.Table3_Bo table table td{
border:0;
}
.Table3_Bo table table{
border:0;
}
.Table3_Bo table input{
width:60px;
margin:0 5px;
}
</style>
</head>

<body>
<div class="Caption">
  采购管理模块 — 商品批量上传查看
</div>
<div style="float:left;width:950px;text-align:left">
 <div class="Hr1"><img src="images/image/hr1.gif" width=948 height=2></div>
  <div class="Search_Bo" style="width:948px">
  	<form id="filter_form" action="buyer_production-batch-view.php" method="GET">
    <span style="margin-top:14px;margin-left:11px;float:left;width:40px">文件名</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="file_name" value="<?php echo $_REQUEST["file_name"] ?>"></span>
    <span style="margin-top:14px;margin-left:11px;float:left;width:49px">上传时间</span>
	<span style="margin-top:10px;margin-left:11px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="start_date" value="<?php echo $_REQUEST["start_date"] ?>"  id="startCalendar" ></span>
	<span style="margin-top:13px;float:left;width:20px"><button id="startTrigger" style="border:0"><img src="images/image/date.gif" align="absmiddle"></button></span>
	<span style="margin-top:14px;float:left;width:20px">到</span>
	<span style="margin-top:10px;margin-left:11px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="end_date" value="<?php echo $_REQUEST["end_date"] ?>"  id="endCalendar" ></span>
	<span style="margin-top:13px;float:left;width:20px"><button id="endTrigger" style="border:0"><img src="images/image/date.gif" align="absmiddle"></button></span>
	<span style="margin-top:14px;float:left;width:50px">上传方式</span>
	<span style="margin-top:10px;margin-left:11px;float:left;width:110px">
		<select style="height:20px;width:100px" name="type">
			<option value="-1" >所有</option>
			<option value="0" <?php if ($_REQUEST["type"] !== null && $_REQUEST["type"] == 0) echo "selected=\"selected\""; ?>>价格更新</option>
			<option value="1" <?php if ($_REQUEST["type"] !== null && $_REQUEST["type"] == 1) echo "selected=\"selected\""; ?>>产品更新</option>
			<option value="2" <?php if ($_REQUEST["type"] !== null && $_REQUEST["type"] == 2) echo "selected=\"selected\""; ?>>整体下架更新</option>
		</select></span>
	<span class="Button4" style="margin-top:9px" onclick="document.getElementById('filter_form').submit()">搜索</span>
	</form>
  </div>
  

  <div style="float:left"><img src="images/image/Menu_bot.gif" width=950 height=3></div>
  <div class="Table3_Bo" style="width:949px;*width:950px;margin-left:1px;*margin-left:0;" id="Table1">
<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
	<th width="9%">编号</th>
	<th width="11%">上传者</th>
	<th width="14%">上传时间</th>
	<th width="27%">文件名</th>
	<th width="12%">上传方式</th>
	<th width="12%">状态</th>
	<th width="15%">冲突情况</th>
	</tr>
	</thead>
	<tbody>
	<?php
		$output = "";
		$dataArray = array();
		while ($data = $db->fetch_object($pagination->result)) {
			$data->type = get_provider_upload_type($data->type);
			$data->status = get_provider_upload_status($data->status);
			$dataArray[] = $data;
		}
		foreach ($dataArray as $data) {
			$output .= "<tr>";
			$output .= "<td>$data->provider_uploaded_id</td>";
			$output .= "<td>$data->operator</td>";
			$output .= "<td>$data->operate_datetime</td>";
			$output .= "<td>$data->file_name</td>";
			$output .= "<td>$data->type</td>";
			$output .= "<td>$data->status</td>";
			if (file_exists(UPLOAD_DIR.$data->provider_uploaded_id.$csv_suffix)) {
				$output .= "<td align=\"center\"><a href=\"?page={$_GET['page']}&provider_uploaded_id=$data->provider_uploaded_id\" class=\"Button3\" target=\"_blank\">查看</a></td>";
			} else {
				$output .= "<td></td>";
			}
			$output .= "</tr>";
		}
		echo $output;
	?>

	<tr>
	<td colspan="7" style="text-align:right;padding-right:20px;">总计<?php echo $pagination->total_count?>个记录 分为<?php echo $pagination->page_count?>页 当前第<?php echo $pagination->page_number?>页 <?php echo $pagination->get_forward_view("首页", "上一页", "下一页", "末页"); ?>
<select id="oselect" onchange="change()">
	<?php
		$output = "";
		for($i = 1; $i <= $pagination->page_count; $i++) {
			if ($i == $pagination->page_number) {
				$output .= "<option selected=\"selected\" value=\"". $pagination->get_request_url($i) ."\">$i</option>";
			} else {
				$output .= "<option value=\"". $pagination->get_request_url($i) ."\">$i</option>";
			}
		}
		echo $output;
	?>
</select>
	</tr>
	
	</tbody>
	</table>
	<a href='http://www.zapatec.com/products/prod1'> Javascript Calendar</a>
	</div>
	</div>
	
<script type="text/javascript">//<![CDATA[
      Zapatec.Calendar.setup({
        weekNumbers       : false,
        electric          : false,
        inputField        : "startCalendar",
        button            : "startTrigger",
        ifFormat          : "%Y-%m-%d",
        daFormat          : "%Y-%m-%d"
      });
      Zapatec.Calendar.setup({
        weekNumbers       : false,
        electric          : false,
        inputField        : "endCalendar",
        button            : "endTrigger",
        ifFormat          : "%Y-%m-%d",
        daFormat          : "%Y-%m-%d"
      });
    //]]>
</script>
</body>
</html>