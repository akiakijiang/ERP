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
	
	admin_priv('others_carrier');
	
	$sql = "select * from " . $ecs->table('carrier');
	$rst = $db->query($sql);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles/css/css.css" rel="stylesheet" type="text/css">
<link href="styles/css/css_2007.9.8.css" rel="stylesheet" type="text/css">
<link href="styles/css/odiv.css" rel="stylesheet" type="text/css">
<style type="text/css">

.Button4,.Button3{
text-decoration:none;
color:#000;
margin:5px;
}
.Menu_li2{
width:32.5%;
}
.Menu_li3{
width:32.5%;
}
.Table3_Bo {
float:none;
clear:both;
}
#Table1 th{
width:16%;
}
#Table1 td{
width:16%;
}
#Table1 td.idcolumn {
width:8%;
}
#Table1 td.addresscolumn {
width:24%;
}
</style>
<script src="js/js/jquery.pack.js" type="text/javascript" ></script>
<script src="js/js/div.jquery.js" type="text/javascript" ></script>
<script src="js/common.js" type="text/javascript" ></script>
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
$("#itemDiv2").cDiv("#Close2","#itemDiv2",".edit");
$("#itemDiv1").cDiv("#Close1","#itemDiv1",".newz");  
$(".edit").click(function(){
var Input = $("#itemDiv2 input");
Input[0].value = $(this).parent().parent().children(":eq(1)").text();
Input[1].value = $(this).parent().parent().children(":eq(2)").text();
Input[2].value = $(this).parent().parent().children(":eq(3)").text();
Input[3].value = $(this).parent().parent().children(":eq(4)").text();
Input[4].value = $(this).parent().parent().children(":eq(0)").text(); // id
});
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
$(divclass).css("top",(document.documentElement.scrollTop + (document.documentElement.clientHeight/4)+"px"));
$(divclass).css("left", (document.documentElement.clientWidth/3+"px"));
}
window.onscroll=onloadss;
window.onresize=onloadss;
window.onload=onloadss;
</script>
<title>承运商管理模块</title>

</head>

<body>
<div class="Caption">
  承运商管理模块
</div>
<div style="float:left;width:950px;text-align:left; clear:both;">
 
  <div style="float:left"><img src="images/image/Menu_bot.gif" width=950 height=3></div>
  <div class="Table3_Bo" style="width:949px;*width:950px;margin-left:1px;*margin-left:0;" id="Table1">
	<table cellpadding="0" cellspacing="0">
	<thead>
	<tr>
	<th style="width:50px">编号</th>
	<th>名称</th>
	<th>地址</th>
    <th>联系电话</th>
	<th>网站</th>
	<th>操作</th>
	</tr>
	</thead>
	<tbody>
	<?php
		while ($data = $db->fetch_object($rst)) {
			echo "<tr>";
			echo "<td class=\"idcolumn\">" . $data->carrier_id . "</td>";
			echo "<td>" . $data->name . "</td>";
			echo "<td class=\"addresscolumn\">" . $data->address . "</td>";
			echo "<td>" . $data->phone_no . "</td>";
			echo "<td>" . $data->web_site . "</td>";
			echo '<td><a href="#" style="margin-left:30px" class="Button4 edit">编辑</a><a href="#" class="Button4" onclick="deletedata(' . $data->carrier_id . ')">删除</a></td>';
			echo "</tr>";
		}
	?>
	<tr>
    <td colspan="6" style="text-align:right;padding-right:20px;"> <a href="#" class="Button4 newz" style="float:right; margin-top:-1px;">新增</a></td>
	</tr>
	</tbody>
	</table>
  </div>
</div>
<div id="oDiv" onDblClick="dblclick()"></div>
<div class="itemDiv" id="itemDiv2"><form method="POST" action="carrier_action.php" id="editform" ><h2>承运商信息编辑</h2><ul><li><strong>名称：</strong><input type="text" name="name" /></li><li><strong>地址：</strong><input type="text" name="address" /></li><li><strong>联系电话：</strong><input type="text" name="phone_no" /></li><li><strong>网站：</strong><input type="text" name="web_site" /></li></ul><p><a href="#" class="Button4" onclick="check(document.getElementById('editform'))">保存</a><a href="#" class="Button4" onclick="document.getElementById('editform').reset()">重置</a><a href="#" class="Button4" id="Close2">取消</a></p><input type="hidden" name="id" /><input type="hidden" value="edit" name="action" /></form></div>

<div class="itemDiv" id="itemDiv1"><form method="POST" action="carrier_action.php" id="newzform" ><h2>承运商信息添加</h2><ul><li><strong>名称：</strong><input type="text" name="name" /></li><li><strong>地址：</strong><input type="text" name="address" /></li><li><strong>联系电话：</strong><input type="text" name="phone_no" /></li><li><strong>网站：</strong><input type="text" name="web_site" /></li></ul><p><a href="#" class="Button4" onclick="check(document.getElementById('newzform'))">保存</a><a href="#" class="Button4" onclick="document.getElementById('newzform').reset()">重置</a><a href="#" class="Button4" id="Close1">取消</a></p><input type="hidden" value="add" name="action" /></form></div>

<script type="text/javascript">
function Show(a)
{
for(var i=1;i<=7;i++)
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
function conf(){
confirm("你确定要删除吗");
}
function clear(dataform) {
	dataform.name.value = '';
	dataform.address.value = '';
	dataform.phone_no.value= '';
	dataform.web_site.value= '';	
}

function check(dataform) {
	if (dataform.name.value == null || dataform.name.value == "") {
		alert("请输入名称");
		dataform.name.focus();
		return;
	}
	if (dataform.address.value == null || dataform.address.value == "") {
		alert("请输入地址");
		dataform.address.focus();
		return;
	}
	
	if (dataform.phone_no.value == null || dataform.phone_no.value == "") {
		alert("请输入电话号码");
		dataform.phone_no.focus();
		return;
	}
	dataform.submit();
	clear(dataform);
}

function deletedata(id) {
	if (confirm("你确定要删除吗")) {
		document.getElementById("deleteform").id.value = id;
		document.getElementById("deleteform").submit();
	}
}
</script>
<form id="deleteform" method="POST" action="carrier_action.php">
<input name="id" type="hidden" />
<input name="action" type="hidden" value="delete" />
</form>
</body>
</html>
