<?php
	define('IN_ECS', true);
	require('includes/init.php');
	require_once('config.vars.php');
	
	admin_priv('purchase_uploadGoods');
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles/css/css.css" rel="stylesheet" type="text/css">
<link href="styles/css/css_2007.9.8.css" rel="stylesheet" type="text/css">
<link href="styles/css/odiv.css" rel="stylesheet" type="text/css">

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
.Table3_Bo ul{
	list-style:none;
}
.Table3_Bo li{
	margin:10px 0;
}
.Table3_Bo h2{
	font-size:12px;
}
</style>
</head>

<body>
<div class="Caption">
  采购管理模块 — 商品批量上传查看
</div>
<div style="float:left;width:950px;text-align:left">
  <div style="float:left"><img src="image/Menu_bot.gif" width=950 height=3></div>
  <div class="Table3_Bo" style="width:949px;*width:950px;margin-left:1px;*margin-left:0;" id="Table1">
  <form method="POST" action="upload_action.php" enctype="multipart/form-data" onsubmit="return check()" id="upload_form">
<ul>
<!--
<li><input type="radio" name="type" value="<?php echo PROVIDER_UPLOAD_PRICE ?>" >价格更新</li>
<li><input type="radio" name="type" value="<?php echo PROVIDER_UPLOAD_PRODUCT ?>">产品更新</li>
<li><input type="radio" name="type" value="<?php echo PROVIDER_UPLOAD_WHOLE ?>">整体下架更新</li>
-->
<input type="hidden" name="type" value="<?php echo PROVIDER_UPLOAD_PRODUCT ?>" checked="checked">
<li>上传批量CSV文件：<input type="file" name="file"></li>
<li><input type="submit" value="确定" /></li>
</ul>
</form>
<h2>注意事项</h2>
<ul>
<li>1. 价格更新：CSV文件中的旧产品只更新价格，即使存在着冲突的情况。若CSV文件中包含有新产品信息，则自动添加入库；</li>
<li>2. 产品更新：对产品进行更新，若有冲突情况则到产品对比页面进行修改；</li>
<li>3. 整体下架更新：针对某个特定供应商的所有商品，先进行整体下架，然后再进行更新；</li>
<li>4. 上传csv文件的命名规则为：供应商_商品分类_上传方式_时间.csv。 </li>
</ul>
  </div>
	</div>
	
<script type="text/javascript">
function check() {
	return true;
	/*
	type = document.getElementById("upload_form").type;
	for (i = 0; i < type.length; i++) {
		if (type[i].checked) {
			return true;
		}
	}
	alert("请选择类别");
	return false;
	*/
}
</script>
</body>
</html>