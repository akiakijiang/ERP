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
 * $Author: ychen
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
	
	admin_priv('purchase_provider');
	
	$condition = get_condition_provider(true);
	
	$baseSQL = "select * from " . $ecs->table('provider') . " where provider_status <> 4 $condition order by provider_id desc";
	$pagination = new Pagination();
	$pagination->set_sql($baseSQL, $db);
	$row = array();
	$row["provider_category"] = provider_category_add("aleft");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles/css/css.css" rel="stylesheet" type="text/css">
<link href="styles/css/css_2007.9.8.css" rel="stylesheet" type="text/css">
<link href="styles/css/odiv.css" rel="stylesheet" type="text/css">
<script src="js/js/jquery.pack.js" type="text/javascript" ></script>
<script src="js/js/div.jquery.js" type="text/javascript" ></script>
<script type="text/javascript">
$(function(){
var yScroll = document.documentElement.scrollHeight;
var windowHeight = document.documentElement.clientHeight;
if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else {
		pageHeight = yScroll;
	}
$("#oDiv").height(pageHeight);
$("#oDiv").css("visibility","hidden");
$("#itemDiv1").cDiv("#Close1","#itemDiv1",".look");
$("#itemDiv2").cDiv("#Close2","#itemDiv2",".edit");
$("#itemDiv3").cDiv("#Close3","#itemDiv3",".cancel");        
});

var dblclick = function(){
$("#oDiv").css("visibility","hidden");
$(".itemDiv").css("visibility","hidden");
$(":input").css("visibility","visible");
$(".itemDiv :input").css("visibility","hidden");
}
function onloadss(){
ss(".itemDiv");
}
function ss(divclass){
$(divclass).css("top",(document.documentElement.scrollTop + (document.documentElement.clientHeight/5)+"px"));
$(divclass).css("left", (document.documentElement.clientWidth/3+"px"));
}

function examine(name, code, cagetory, hot, type, address, person, phone, email) {
	 $("#itemDiv1 li:eq(0) span").text(name);
	 $("#itemDiv1 li:eq(1) span").text(code);
	 $("#itemDiv1 li:eq(2) span").text(cagetory);
	 $("#itemDiv1 li:eq(3) span").text(hot);
	 $("#itemDiv1 li:eq(4) span").text(type);
	 $("#itemDiv1 li:eq(5) span").text(address);
	 $("#itemDiv1 li:eq(6) span").text(person);
	 $("#itemDiv1 li:eq(7) span").text(phone);
	 $("#itemDiv1 li:eq(8) span").text(email);
}

function delete_provider(name, code, person, phone, provider_id) {
	 $("#itemDiv3 li:eq(0) span").text(name);
	 $("#itemDiv3 li:eq(1) span").text(code);
	 $("#itemDiv3 li:eq(2) span").text(person);
	 $("#itemDiv3 li:eq(3) span").text(phone);
	 $("#itemDiv3 li:eq(5) input").val(provider_id);
}

function edit_provider(name, code, category, provider_id) {
	 $("#itemDiv2 li:eq(0) span").text(name);
	 $("#itemDiv2 li:eq(1) span").text(code);
	 $("#itemDiv2 li:eq(4) input").val(provider_id);
	 checkboxes = document.getElementsByName("provider_category[]");
	 
	 for (j = 0; j < checkboxes.length; j++) {
	 	checkboxes[j].checked = "";
 	 }	 
	 
	 for (i = 0; i < category.length; i++) {
	 	for (j = 0; j < checkboxes.length; j++) {
	 		if (checkboxes[j].value == category[i]) {
	 			checkboxes[j].checked = "checked";
	 		}
	 	}
	 }
}

window.onscroll=onloadss;
window.onresize=onloadss;
window.onload=onloadss;
</script>
<title> 采购管理模块 — 供应商管理</title>
<style type="text/css">
.Button4,.Button3{
text-decoration:none;
color:#000;
margin:5px;
}
.Button3{
clear:both;
}
.Menu_li2{
width:9%;
}
.Menu_li3{
width:9%;
}
.Table3_Bo {
float:none;
clear:both;
}
.aleft{
	white-space:nowrap;
}
.aleft input{
	float:none;
}
</style>
</head>

<body>
<div class="Caption">
  采购管理模块 — 供应商管理
</div>
<div style="float:left;width:950px;text-align:left">
 <div class="Hr1"><img src="images/image/hr1.gif" width=948 height=2></div>
  <div class="Search_Bo" style="width:948px">
  <form method="GET" action="buyer_supplier-manage.php" id="filter_form" >
    <span style="margin-top:14px;margin-left:11px;float:left;width:40px">供应商</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="provider_name" value="<?php echo $_REQUEST["provider_name"] ?>"></span>
	<span style="margin-top:14px;margin-left:11px;float:left;width:50px">代码</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="provider_code" value="<?php echo $_REQUEST["provider_code"] ?>"></span>
	<span style="margin-top:14px;margin-left:11px;float:left;width:40px">联系人</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="contact_person" value="<?php echo $_REQUEST["contact_person"] ?>"></span>
	
	<span style="margin-top:14px;float:left;width:50px">渠道类型</span>
	<span style="margin-top:10px;margin-left:11px;float:left;width:110px" class="selectt">
		<select style="height:20px;width:100px" name="provider_type">
			<option value="-1">所有</option>
			<option value="0" <?php if ($_REQUEST["provider_type"] !== null && $_REQUEST["provider_type"] == 0) echo "selected=\"selected\""; ?>>制造商</option>
			<option value="1" <?php if ($_REQUEST["provider_type"] !== null && $_REQUEST["provider_type"] == 1) echo "selected=\"selected\""; ?>>全国代理</option>
			<option value="2" <?php if ($_REQUEST["provider_type"] !== null && $_REQUEST["provider_type"] == 2) echo "selected=\"selected\""; ?>>省级代理</option>
			<option value="3" <?php if ($_REQUEST["provider_type"] !== null && $_REQUEST["provider_type"] == 3) echo "selected=\"selected\""; ?>>区域代理</option>
			<option value="4" <?php if ($_REQUEST["provider_type"] !== null && $_REQUEST["provider_type"] == 4) echo "selected=\"selected\""; ?>>其他</option>
		</select>
	</span>
	<span class="Button4" style="margin-top:9px" onClick="document.getElementById('filter_form').submit()">搜索</span>
  </form>
  </div>

  <div style="float:left"><img src="images/image/Menu_bot.gif" width=950 height=3></div>
  <div class="Table3_Bo" style="width:949px;*width:950px;margin-left:1px;*margin-left:0;">
	<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
	<th>编号</th>
	<th>公司名</th>
	<th>公司代码</th>
	<th>交易类型</th>
    <th>经营商品类别</th>
	<th>渠道类型</th>
	<th>联系人</th>
	<th>联系电话</th>
    <th>备注</th>
	<th>操作</th>
	</tr>
	</thead>
	<tbody>
	<?php
		$output = "";
		while ($data = $db->fetch_object($pagination->result)) {
			$categorySQL = "select * from ecs_category, ecs_provider_category where ecs_provider_category.provider_id = $data->provider_id and ecs_provider_category.cat_id = ecs_category.cat_id and provider_category_status = 1";
			$category_rst = $db->query($categorySQL) or "无法执行SQL语句: $categorySQL";
			
			$category_array = array();			
			$category_id_array = array();
			
			$output .= "<td>$data->provider_id</td>";
			$output .= "<td>$data->provider_name</td>";
			$output .= "<td>$data->provider_code</td>";
			$output .= "<td>$data->provider_order_type</td>";
			
			while ($category = $db->fetch_object($category_rst)) {
				$category_array[] = $category->cat_name;
				$category_id_array[] = $category->cat_id;
 			}
 			$category_id_array = "[". implode(", ", $category_id_array) . "]";
 			
			$category = implode(", ", $category_array);
			$type = get_provider_type($data->provider_type);
			$output .= "<td>$category</td>";
			$output .= "<td>$type</td>";
			$output .= "<td>$data->contact_person</td>";
			$output .= "<td>$data->phone</td>";
			$output .= "<td></td>";
			$output .= "<td><a target=\"_blank\" class=\"Button4\" href='supplier-info.php?provider_id=".$data->provider_id."&action=view'>查看</a><a href=\"#\" class=\"Button4 cancel\" onclick=\"delete_provider('$data->provider_name', '$data->provider_code', '$data->contact_person', '$data->phone', '$data->provider_id')\">删除</a><a href='supplier-info.php?provider_id=".$data->provider_id."' class=\"Button3\">修改资料</a></td>";
			$output .= "</tr>";
		}
		echo $output;
	?>

	<tr>
	<td colspan="10" style="text-align:right;padding-right:20px;"><a href="supplier-info.php" target="_blank">增加供应商</a> 总计<?php echo $pagination->total_count?>个记录 分为<?php echo $pagination->page_count?>页 当前第<?php echo $pagination->page_number?>页 <?php echo $pagination->get_forward_view("首页", "上一页", "下一页", "末页"); ?>
<select id="oselect" onChange="change()">
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
  </div>
</div>
<div id="oDiv" onDblClick="dblclick()"></div>
<div class="itemDiv" id="itemDiv1">
<h2>供应商信息</h2>
<ul>
<li><strong>公司名：</strong><span class="aleft"></span></li>
<li><strong>公司代码：</strong><span class="aleft"></span></li>
<li><strong>商品类别：</strong><span class="aleft"></span></li>
<li><strong>热销品牌：</strong><span class="aleft"></span></li>
<li><strong>渠道类型：</strong><span class="aleft"></span></li>
<li><strong>公司地址：</strong><span class="aleft"></span></li>
<!--
<li><strong>邮编：</strong><span class="aleft">201210</span></li>
<li><strong>法人代表：</strong><span class="aleft">王某</span></li>
-->
<li><strong>联系人：</strong><span class="aleft"></span></li>
<!--
<li><strong>职务：</strong><span class="aleft">总经理</span></li>
-->
<li><strong>联系电话：</strong><span class="aleft"></span></li>
<!--
<li><strong>手机：</strong><span class="aleft">13355555555</span></li>
<li><strong>传真：</strong><span class="aleft">02155555555</span></li>
-->
<li><strong>Email：</strong><span class="aleft"></span></li>
</ul>
<p><a href="#" class="Button4" id="Close1">关闭</a></p>
</div>
<!-- -->
<div class="itemDiv" id="itemDiv2" >
<h2>修改供应商信息</h2>
<form id="edit_form" action="supplier_action.php" method="POST">
<ul>
<li><strong>公司名：</strong><span class="aleft"></span></li>
<li><strong>公司代码：</strong><span class="aleft"></span></li>
<li><strong>商品类别：</strong><?php echo $row["provider_category"]?></li>
<li><strong>备注：</strong><input type="text" /></li>
<li style="display:none"><input type="hidden" name="provider_id"/></li>
<li style="display:none"><input type="hidden" name="action" value="edit"/></li>
</ul>
<p><a href="#" class="Button4" onClick="document.getElementById('edit_form').submit()">确定</a><a href="#" class="Button4" id="Close2">取消</a></p>
</form>
</div>
<!-- -->
<div class="itemDiv" id="itemDiv3">
<h2>删除供应商信息</h2>
<form action="supplier_action.php" method="POST" id="delete_form">
<ul>
<li><strong>公司名：</strong><span class="aleft"></span></li>
<li><strong>公司代码：</strong><span class="aleft"></span></li>
<li><strong>联系人：</strong><span class="aleft"></span></li>
<li><strong>联系电话：</strong><span class="aleft"></span></li>
<li><strong>备注：</strong><textarea name="action_note"></textarea></li>
<li style="display:none"><input type="hidden" name="provider_id"/></li>
<li style="display:none"><input type="hidden" name="action" value="delete"/></li>
</ul>
</form>
<p><a href="#" class="Button4" onClick="document.getElementById('delete_form').submit()">确定</a><a href="#" class="Button4" id="Close3">取消</a></p>
</div>

<script type="text/javascript">
 
function change() {
	_select = document.getElementById("oselect");
	if (_select != null) {
		value = _select.options[_select.selectedIndex].value;
	}
	location.href=value;
}
</script>
</body>
</html>
