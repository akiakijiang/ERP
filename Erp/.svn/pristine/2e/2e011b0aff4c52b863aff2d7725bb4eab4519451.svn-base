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
	
	$baseSQL = "select * from " . $ecs->table('provider_brand_manage') . " where status <> 1 $condition order by provider_brand_id desc";
	
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

function delete_provider_brand(name, type, brand, company_address, contact_people_name, provider_brand_id) {
	 $("#itemDiv3 li:eq(0) span").text(name);
	 $("#itemDiv3 li:eq(1) span").text(type);
	 $("#itemDiv3 li:eq(2) span").text(brand);
	 $("#itemDiv3 li:eq(3) span").text(company_address);
	 $("#itemDiv3 li:eq(4) span").text(contact_people_name);
	 $("#itemDiv3 li:eq(6) input").val(provider_brand_id);
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
  <form method="GET" action="#" id="filter_form" >
    <span style="margin-top:14px;margin-left:11px;float:left;width:40px">公司名</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="provider_name" value="<?php echo $_REQUEST["provider_name"] ?>"></span>
	<span style="margin-top:14px;margin-left:11px;float:left;width:50px">品牌</span>
	<span style="margin-top:10px;margin-left:5px;float:left;width:110px"><input type="text" style="height:14px;width:100px" name="brand" value="<?php echo $_REQUEST["brand"] ?>"></span>
	
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
	<span class="Button4" style="margin-top:9px" onclick="document.getElementById('filter_form').submit()">搜索</span>
  </form>
  </div>
   <ul class="Menu_ul" style="width:948px">
      <li class="Menu_li3" id="M1" onClick="Show('1')">所有</li>
      <li class="Menu_li2" id="M2" onClick="Show('2')">手机</li>
	  <li class="Menu_li2" id="M3" onClick="Show('3')">数码相机</li>
	  <li class="Menu_li2" id="M4" onClick="Show('4')">MP3|MP4</li>
	  <li class="Menu_li2" id="M5" onClick="Show('5')">笔记本</li>
	  <li class="Menu_li2" id="M6" onClick="Show('6')">健康|美容电子</li>
	  <li class="Menu_li2" id="M7" onClick="Show('7')">车载电子</li>
	  <li class="Menu_li2" id="M8" onClick="Show('8')">电玩|电教</li>
	  <li class="Menu_li2" id="M9" onClick="Show('9')">新奇电子</li>
	  <li class="Menu_li2" id="M10" onClick="Show('10')">已删除</li>
  </ul>

  <div style="float:left"><img src="images/image/Menu_bot.gif" width=950 height=3></div>
  <div class="Table3_Bo" style="width:949px;*width:950px;margin-left:1px;*margin-left:0;" id="Table1">
	<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
	<th>编号</th>
	<th>公司名</th>
	<th>供应渠道</th>
	<th>品牌</th>
	<th>公司地址</th>
	<th>联系人</th>
	<th>操作</th>
	</tr>
	</thead>
	<tbody>
	<?php
		$output = "";
		while ($data = $db->fetch_object($pagination->result)) {
			$contact_people_sql = "select name from " . $ecs->table('provider_brand_person') . " where provider_brand_id = " . $data->provider_brand_id;
			
			$contact_people_rst = $db->query($contact_people_sql) or "无法执行SQL语句: $contact_people_sql";
			
			$contact_people_name = Array();
			while ($contact_people = $db->fetch_object($contact_people_rst)) {
				$contact_people_name[] = $contact_people->name;
			}
			
			
			$contact_people_name = join(",", $contact_people_name);
			$type = get_provider_type($data->provider_type);
			
			$output .= "<td>$data->provider_brand_id</td>";
			$output .= "<td>$data->provider_name</td>";
			$output .= "<td>$type</td>";
			$output .= "<td>$data->brand</td>";
			$output .= "<td>$data->company_address</td>";
			$output .= "<td>$contact_people_name</td>";
			$output .= "<td><a target=\"_blank\" class=\"Button4\" href='provider_brand_info.php?provider_brand_id=".$data->provider_brand_id."&action=view'>查看</a><a href=\"#\" class=\"Button4 cancel\" onclick=\"delete_provider_brand('$data->provider_name', '$type', '$data->brand', '$data->company_address', '$contact_people_name', '$data->provider_brand_id')\">删除</a><a href='provider_brand_info.php?provider_brand_id=".$data->provider_brand_id."' class=\"Button3\">修改资料</a></td>";
			$output .= "</tr>";
		}
		echo $output;
	?>

	<tr>
	<td colspan="9" style="text-align:right;padding-right:20px;"><a href="supplier-info.php" target="_blank">增加供应商</a> 总计<?php echo $pagination->total_count?>个记录 分为<?php echo $pagination->page_count?>页 当前第<?php echo $pagination->page_number?>页 <?php echo $pagination->get_forward_view("首页", "上一页", "下一页", "末页"); ?>
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
<li><strong>商品类别：</strong><span class="aleft"><input type="checkbox" name="provider_category[]" value="1"/>手机大全</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="119"/>数码相机</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="166"/>数码摄像机</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="179"/>mp3</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="260"/>mp4</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="336"/>车载电子</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="341"/>电玩 电教</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="414"/>笔记本</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="454"/>家用电子</span><input type="checkbox" name="provider_category[]" value="1142"/>美容护理</span><span class="aleft"><input type="checkbox" name="provider_category[]" value="837"/>配件</span></li>
<li><strong>备注：</strong><input type="text" /></li>
<li style="display:none"><input type="hidden" name="provider_brand_id"/></li>
<li style="display:none"><input type="hidden" name="action" value="edit"/></li>
</ul>
<p><a href="#" class="Button4" onclick="document.getElementById('edit_form').submit()">确定</a><a href="#" class="Button4" id="Close2">取消</a></p>
</form>
</div>
<!-- -->
<div class="itemDiv" id="itemDiv3">
<h2>删除供应商信息</h2>
<form action="provider_brand_action.php" method="POST" id="delete_form">
<ul>
<li><strong>公司名：</strong><span class="aleft"></span></li>
<li><strong>供应渠道：</strong><span class="aleft"></span></li>
<li><strong>品牌：</strong><span class="aleft"></span></li>
<li><strong>公司地址：</strong><span class="aleft"></span></li>
<li><strong>联系人：</strong><span class="aleft"></span></li>
<li><strong>备注：</strong><textarea name="action_note"></textarea></li>
<li style="display:none"><input type="hidden" name="provider_brand_id"/></li>
<li style="display:none"><input type="hidden" name="action" value="delete"/></li>
</ul>
</form>
<p><a href="#" class="Button4" onclick="document.getElementById('delete_form').submit()">确定</a><a href="#" class="Button4" id="Close3">取消</a></p>
</div>

<script type="text/javascript">
function Show(a)
{
	for(var i=1;i<=10;i++)
	{
		if(a == i)
		{
			document.getElementById("M"+i).className= "Menu_li3";
			document.getElementById("Table"+i).style.display = "";
		}
		else
		{
			document.getElementById("M"+i).className= "Menu_li2";
			document.getElementById("Table"+i).style.display = "none";
		}
	}
}


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
