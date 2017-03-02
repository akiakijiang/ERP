<?php
define('IN_ECS', true);
require_once('../lib_bw_shop.php');
require_once('../../includes/init.php');


$BWShopAgent = new BWShopAgent();
$mock = $BWShopAgent->shopList();
foreach ($mock as $key => $value) {
	if(is_array($value)){
		$shop_name[] = $value['shop_name'];
	}
}
?>



<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title>订单录入详情</title>
	<style type="text/css">
	.form_main{
		font-size: 18px !important;
		width: 630px;
		padding: 20px;
		border-radius: 10px;
		background: rgba(34,45,65,.2);
		margin: 8% auto;
		box-shadow: 4px 4px 3px black;
	}
	.form_main form{
		margin-left: 20px;
	}
	.form_main form:first-child{
		padding-bottom: 40px;
		border-bottom: 1px solid black;
		margin-bottom: -30px;
	}
	.form_main input{
		font-size: 18px;
		/*border-radius: 2px;*/
	}
	input[type="submit"] {
		-webkit-appearance: button;
		cursor: pointer;
	}
	.margin_div{
		margin: 15px auto;
	}
	.btn{
		display: inline-block;
		padding: 6px 12px;
		margin-bottom: 0;
		font-size: 18px;
		font-weight: normal;
		line-height: 1.42857143;
		text-align: center;
		white-space: nowrap;
		vertical-align: middle;
		-ms-touch-action: manipulation;
		touch-action: manipulation;
		cursor: pointer;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
		color: #fff;
		background-color: #337ab7;
		border-color: #2e6da4;
		background-image: none;
		border: 1px solid transparent;
		border-radius: 4px;
	}
	.input-control {
		/*display: block;*/
		width: 77%;
		height: 34px;
		padding: 6px 12px;
		font-size: 14px;
		line-height: 1.42857143;
		color: #555;
		background-color: #fff;
		background-image: none;
		border: 1px solid #ccc;
		border-radius: 5px;
		-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
		box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
		-webkit-transition: border-color ease-in-out .41s, -webkit-box-shadow ease-in-out .15s;
		-o-transition: border-color ease-in-out .41s, box-shadow ease-in-out .15s;
		transition: border-color ease-in-out .41s, box-shadow ease-in-out .15s;
	}
	.input-control:focus {
		border-color: #66afe9;
		border-radius: 5px;
		outline: 0;
		-webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, .6);
		box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, .6);

	}
	.user_margin{
		display: inline-block;
		width: 90px;
	}
	.public_hidden_span,.private_hidden_span,.upload_hidden_span{
		opacity: 0;
	}
	.show_span{
		opacity: 1;
		color: red;

	}
	.text{
		text-decoration: none;
		border:1px solid rgba(102, 175, 233, .6);
		border-radius: 5px;
		padding:5px;
	}
	</style>
</head>
<body>
	<div class="form_main">
		<h3>保稅倉訂單導入界面</h3>
		<p>
			下载模板，根据模板内容进行填写,然后上传。
		</p>
		<p>
			【悲報】订单号已經不需要加前缀。
		</p>
		<p>
			【悲報】全部有内容的单元格必须是文本格式，日期、公式等会亮瞎系统。
		</p>
		<a href="excel/books_order.xlsx"><input type="button" class="btn" value="订单信息输入模板"></a>
		&nbsp;
		<a href="excel/order_import_readme.xlsx"><input type="button" class="btn" value="模板字段简介"></a>
		&nbsp;
		接口文档：
		<a href="http://testbwshop.leqee.com/doc/API_DOC.pages" class="text">pages</a>
		<a href="http://testbwshop.leqee.com/doc/API_DOC.pdf" class="text">pdf</a>
		<br><hr>
		<form method="post" enctype="multipart/form-data" action="download.php?act=upload_data">
			<div class="margin_div">
				<div class="user_margin">客户:</div>
				<span>
					<select id="shop_name" name="shop_name" class="input-control">
						<option value="">未选择</option>
						<?php 
						foreach ($shop_name as $key) {
							echo "<option value = ".$key.">".$key."</option>";
						}
						?>
					</select>
				</span>
			</div>
			<div class="margin_div">
				<span><input type="file" name="upload_excel" id="file_upload"></span>
				<span class="upload_hidden_span" id="fail_upload_file">*文件未上传</span>
				<div style="margin-top: 15px;">
					務必在上傳前檢查檔案內容和分銷對象選擇的正確性。
					<input type="submit" id="excel_submit" class="btn" value="上传excel文件">
				</div>
			</div>
			<input type="hidden" id="data" name="data">
			<input type="hidden" id="url" name="url">
		</form>
	</div>
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script>
	$(document).ready(function(){
		$("#excel_submit").on("click",function(){
			if($("#shop_name").val() == ""){
				return false;
			}else{
				if(confirm("确认选择"+$("#shop_name").val()+"吗？")){
				}else{
					return false;
				}
			}
			if($("#file_upload").val()==""){
				$(".upload_hidden_span").addClass("show_span");
				return false;
			}
			var filepath=$("#file_upload").val();
			var extStart=filepath.lastIndexOf(".");
			var ext=filepath.substring(extStart+1,filepath.length);
			if(ext != "xlsx"){
				$("#fail_upload_file").html("请选择正确的xlsx格式文件");
				return false;
			}
		});
	});
	</script>
</body>
</html>