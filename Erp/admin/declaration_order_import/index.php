<?php
define('IN_ECS', true);
require_once('../includes/init.php');
//require_once('../lib_bw_shop.php');
//require_once('../../includes/init.php');

global $db;
$sql = "SELECT
		hap.*, hap.nick shop_name,
		p.`NAME` party_name
	FROM
		ecshop.haiguan_api_params hap
		INNER JOIN ecshop.distributor d ON hap.application_key = d.distributor_id -- 只限分销
	LEFT JOIN romeo.party p ON hap.party_id = p.PARTY_ID
	LEFT JOIN ecshop.taobao_shop_conf tsc ON hap.application_key = tsc.application_key
	WHERE
		tsc.application_key is null and hap.party_id = '{$_SESSION['party_id']}'";
		

$list=$db->getAll($sql);
foreach ($list as $key => $value) {
	if(is_array($value)){
		$shop_name[$value['application_key']] = $value['shop_name'];
	}
}


global $db;
$sql_facility = "SELECT
		facility_id,facility_name
	FROM
		romeo.facility
	WHERE
		is_closed = 'N' and facility_type in ('BONDED_BAODA','BONDED_JIALI','BONDED_BAISHI')";
		

$facility_list=$db->getAll($sql_facility);
foreach ($facility_list as $key => $value) {
	if(is_array($value)){
		$facility_name[$value['facility_id']] = $value['facility_name'];
	}
}


?>



<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title>订单录入详情</title>
	<style type="text/css">
	body{
		padding:0px;
		margin:0px;
		font-family: Arial,"微软雅黑",sans-serif;
//		background: #0095cd;
		background: #ededed; 
//		background: -webkit-gradient(linear, left top, left bottom, from(#00adee), to(#0078a5)); 
//		background: -moz-linear-gradient(top, #00adee, #0078a5); 
//		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00adee', endColorstr='#0078a5');  
	}
	.form_main{
		font-size: 18px !important;
		width: 630px;
		padding: 20px;
		border-radius: 10px;
		color: #606060; 
		border: solid 1px #b7b7b7; 
		background: #fff; 
//		background: #ededed; 
		margin: 8% auto;
		box-shadow: 4px 4px 3px grey;
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
		margin-bottom: 10px;;
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
		color: #d9eef7; 
		border: solid 1px #0076a3; 
		background-color: #0095cd; 
		background: -webkit-gradient(linear, left top, left bottom, from(#00adee), to(#0078a5)); 
		background: -moz-linear-gradient(top, #00adee, #0078a5); 
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00adee', endColorstr='#0078a5'); 

	}
	.btn:hover { 
		background: #007ead; 
		background: -webkit-gradient(linear, left top, left bottom, from(#0095cc), to(#00678e)); 
		background: -moz-linear-gradient(top, #0095cc, #00678e); 
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#0095cc', endColorstr='#00678e'); 
	} 
	.btn:active { 
		color: #80bed6; 
		background: -webkit-gradient(linear, left top, left bottom, from(#0078a5), to(#00adee)); 
		background: -moz-linear-gradient(top, #0078a5, #00adee); 
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#0078a5', endColorstr='#00adee'); 
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
	.user_margin,.facility_margin{
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
		color:#0095cd; 
	}
	.text:hover{
		color:#fff; 
		background:#0095cd; 
		box-shadow: 2px 2px 1px gray;
	}
	.text:active{
		color:#0095cd; 
		background:#fff; 
		border-radius: 5px;
	}
	.word{
		font-size:16px;
		font-weight:bold;
	}
	</style>
</head>
<body>
	<div class="form_main">
		<h3>申報系統分銷訂單導入界面</h3>
		<p>
			下载模板，根据模板内容进行填写,然后上传。
		</p>
		<p>
			<font color="red">【注意】</font>全部有内容的单元格必须是文本格式，日期、公式等会亮瞎系统！！！
		</p>
		<p>
		【 省市区参考】
           <select style="width:100px;margin-top:3.5%" name="order_province"><option value="">-请选择 -</option>
           <?php
           foreach (get_regions(1, $GLOBALS['_CFG']['shop_country']) as $key => $item) { 
           echo "<option value=". $item['region_id'].">". $item['region_name'] ."</option> ";
           }
           ?>
           </select>

           	 &nbsp;&nbsp;
           <select name="order_city" style="display:none;width:100px;margin-top:3.5%"><option>-请选择-</option></select> &nbsp;&nbsp;
           <select name="order_district" style="display:none;width:100px;margin-top:3.5%"><option>-请选择-</option></select>

		</p>
		&nbsp;
		<a href="excel/books_order.xlsx"><input type="button" class="btn" value="订单信息输入模板"></a>
		&nbsp;
		<a href="excel/order_import_readme.xlsx"><input type="button" class="btn" value="模板字段简介"></a>
		&nbsp;&nbsp;&nbsp;
		<span class="word">支付方式和快递方式文档：</span>
		<a href="word/mapping.pdf" class="text">pdf</a>
		<br><hr>
		<form method="post" enctype="multipart/form-data" action="download.php?act=upload_data">
			<div class="margin_div">
				<div class="user_margin">客户:</div>
				<span>
					<select id="shop_name" name="shop_name" class="input-control">
						<option value="">未选择</option>
						<?php 
						foreach ($shop_name as $key => $item) {
							
							echo "<option value = ".$key.">".$item."</option>";
						}
						?>
					</select>
				</span>
				<br />
				<br />
				<div class="facility_margin">仓库:</div>
				<span>
					<select id="facility_name" name="facility_name" class="input-control">
						<option value="">未选择</option>
						<?php 
						foreach ($facility_name as $key => $item) {
							
							echo "<option value = ".$key.">".$item."</option>";
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
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script>
	$(document).ready(function(){
		
		// 绑定 选择省会改变地区的事件
		Region.init();
		
		$("#excel_submit").on("click",function(){
			if($("#shop_name").val() == "" || $("#facility_name").val() == "" ){
				return false;
			}else{
				if(confirm("确认选择"+$("#shop_name").find("option:selected").text()+","+$("#facility_name").find("option:selected").text()+"吗？")){
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
	<?php
	echo "var WEB_ROOT ='". $WEB_ROOT ."'";
	?>
	/**
	 * 改变地域的下拉框
	 */
	var Region = {
		/** 
		 * WEB控件ID
		 */	
		regions : [
				{name: 'order_province', data:{type:2} }, // 省
				{name: 'order_city',     data:{type:3} }, // 市
				{name: 'order_district'}                  // 区
		],

		expr : "select[name='#']",
			
		/**
		 * 初始化，即绑定事件
		 */
		init : function() 
		{
			var n0 = this.expr.replace('#', this.regions[0].name);
			$(n0).bind('change', this.regions[0].data, this.change_region_list);	
			$(this.expr.replace('#', this.regions[1].name)).bind('change', this.regions[1].data, this.change_region_list);	

		},
		
		/**
		 * 改变下拉框
		 */
		change_region_list : function(event) 
		{
				var $p = $(this);  // 父级元素
				var pn = $p.attr('name');
				var ln = Region.regions.length;
				var last = false;
				var next = 0;
				// alert("111");
				// 如果到了最后一级则返回
				for (var i=0; i<ln; i++) {
				if (pn == Region.regions[i].name) {
					next = i + 1;
					if (i == ln -1) { last = true; }
				}
			}
			
			if (!last) {
				$c = $(Region.expr.replace('#', Region.regions[next].name));	 // 子级元素
				
				for (var i=next; i<ln; i++) {
					$(Region.expr.replace('#', Region.regions[i].name)).hide();	
				}
			}
		
			$c.unbind();
			$.ajax({
				type: 'POST',
				url: WEB_ROOT + 'admin/ajax.php?act=get_regions',  // 查询地址
				data: 'type=' + event.data.type + '&parent=' + $p.val(),
				dataType: 'json',
				error: function() {alert('查询地域失败'); },
				success: function(data) {
					if (data.regions && data.regions != '') {
						r = data.regions;
						var list = '<option value="0">-请选择-</option>';
						for (var i = 0; i < r.length; i++) {
							list += '<option value="' + r[i].region_id + '">' + r[i].region_name + '</option>';		
						}
						$c.html(list).fadeIn();
						if (next + 1 < ln) {
							$c.bind('change', Region.regions[next].data, Region.change_region_list);
						}
					}
				}
			});	
		}
	};
	</script>
</body>
</html>