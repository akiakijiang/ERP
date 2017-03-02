<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_order.php';
require_once __DIR__.'/lib_bw_shop.php';
require_once __DIR__.'/../config.vars.php';

global $_CFG;

$page=1;
$size=100;
$total=0;

$apply_status='ALL';
$shipping_status='ALL';
$shop_id='ALL';

$tab='LIST';

$search_type='BW_ORDER_ID';
$search_value='';

if(isset($_REQUEST['ajax']) && !empty($_REQUEST['ajax'])){
	if($_REQUEST['ajax']=='search'){
		$search_type=$_REQUEST['search_type'];
		$search_value=$_REQUEST['search_value'];
		$bw_order_list=array();
		if($search_type=='BW_ORDER_ID'){
			$bw_order_list[]=BWOrderAgent::getBWOrderInfoByBwOrderId($search_value);
		}elseif($search_type=='BW_ORDER_SN'){
			$bw_order_list=BWOrderAgent::getBWOrderInfoListByBwOrderSnAndShopId($search_value);
		}elseif($search_type=='ERP_ORDER_ID'){
			$bw_order_list[]=BWOrderAgent::getBWOrderInfoByErpOrderId($search_value);
		}elseif($search_type=='ERP_ORDER_SN'){
			$bw_order_list[]=BWOrderAgent::getBWOrderInfoByErpOrderSn($search_value);
		}
		// print_r($bw_order_list);die();
		$page_count=count($bw_order_list);

		$tab='SEARCH';
	}
}else{
	if(isset($_REQUEST['page'])){
		$page=$_REQUEST['page'];
	}
	if(isset($_REQUEST['size'])){
		$size=$_REQUEST['size'];
	}

	$params=array();

	if(isset($_REQUEST['apply_status'])){
		$apply_status=$_REQUEST['apply_status'];
		if($apply_status!='ALL'){
			$params['apply_status']=$apply_status;
		}
	}

	if(isset($_REQUEST['shipping_status'])){
		$shipping_status=$_REQUEST['shipping_status'];
		if($shipping_status!='ALL'){
			$params['shipping_status']=$shipping_status;
		}
	}
	if(isset($_REQUEST['shop_id'])){
		$shop_id=$_REQUEST['shop_id'];
		if($shop_id!='ALL'){
			$params['shop_id']=$shop_id;
		}
	}

	$bw_order_list=BWOrderAgent::getBWOrderList($size,($page-1)*$size,$total,$params);

	$page_count= ceil($total / $size);
}

$apply_status_array=array(
	"ALL"=>"全部推送状态",
	"INIT"=>"未推送",
	"READY"=>"推送队列中",
	"ACCEPT"=>"已推送",
	"CANCEL"=>"已撤单拦截",
	"REFUSED"=>"保税仓拒收",
);
$shipping_status_array=array(
	"ALL"=>"全部清关状态",
	"00"=>"未申报(包括预校验失败者)",
	"01"=>"库存不足",
	"11"=>"已报国检",
	"12"=>"国检放行",
	"13"=>"国检审核未过",
	"14"=>"国检抽检",
	"21"=>"已报海关",
	"22"=>"海关单证放行",
	"23"=>"海关单证审核未过",
	"24"=>"海关货物放行",
	"25"=>"海关查验未过",
	"99"=>"已关闭",
);

$shop_id_array=array("ALL"=>"全部店铺",);
$shop_list=BWShopAgent::shopList();
foreach ($shop_list as $shop) {
	$shop_id_array[$shop['shop_id']]=$shop['shop_name'];
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>BWSHOP ORDER MONITOR</title>
	<link rel="stylesheet" type="text/css" href="bootstrap-combined.min.css">
	<link rel="stylesheet" href="font-awesome.min.css">

	<script src="jquery.min.js"></script>
	<!-- // <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script> -->
	<script src="bootstrap.min.js"></script>

	<script type="text/javascript">
	$(function () { $("[data-toggle='tooltip']").tooltip(); });
	</script>
	<style type="text/css">
	.tooltip-inner {
		text-align: left;
		white-space:pre-wrap;
	}
	.black {
		color: black;
	}
	.red {
		color: red;
	}
	.orange {
		color: orange;
	}
	.green {
		color: green;
	}
	.blue {
		color: blue;
	}

	#os_select,#ps_select,#ss_select{
		padding: 1px;
	}
	</style>
	<script type="text/javascript">
	function result_filter(os,ps,ss){
		var selector="[tr_type='result_row']";
		$("tr"+selector).css('display','none');
		if(os!='ALL'){
			selector=selector+"[erp_order_status='"+os+"']";
		}
		if(ps!='ALL'){
			selector=selector+"[erp_pay_status='"+ps+"']";
		}
		if(ss!='ALL'){
			selector=selector+"[erp_shipping_status='"+ss+"']";
		}
		$("tr"+selector).css('display','table-row');
	}
	function erp_filter(){
		var os=$("#os_select").val();
		var ps='ALL'; //$("#ps_select").val();
		var ss=$("#ss_select").val();
		result_filter(os,ps,ss);
	}
	</script>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div  class="col-xs-2">
				<img src="http://to-a.ru/wBYpCm/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Hozei Souko No Kansi Sisutemu</h1>
					<p>Hozei Souko no tyuumon nomi wo tenji site ageru wa~</p>
				</div>
			</div>
			<?php if(false && $_SESSION['party_id']!=65638){ ?>
			<div class="col-xs-12">
				<div class="alert alert-info">
					<!-- <button type="button" class="close" data-dismiss="alert">×</button> -->
					<h4>
						提示!
					</h4> <strong>警告!</strong> 切换到保税仓对应的乐其跨境组织方可完全显示ERP相关内容！
				</div>
			</div>
			<?php } ?>
			<div class="col-xs-12">
				<div class="tabbable" id="tabs-209510">
					<ul class="nav nav-tabs">
						<li <?php if($tab=='LIST') { ?>class="active"<?php } ?>>
							<!-- <a href="#panel-597553" data-toggle="tab">LIST</a> -->
							<a href="bw_order_monitor.php?page=<?php echo ($page); ?>" title="按照先后次序分页显示订单列表">订单列表</a>
						</li>
						<li <?php if($tab=='SEARCH') { ?>class="active"<?php } ?>>
							<a href="#panel-874023" data-toggle="tab" title="按照给定条件搜索订单">搜索</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane <?php if($tab=='LIST') { ?>active<?php } ?>" id="panel-597553">
							<form class="form-search form-inline">
								<select name="apply_status">
									<?php 
									foreach ($apply_status_array as $key => $value) {
										echo "<option value='{$key}'".($key==$apply_status?" selected='selected' ":"").">{$value}</option>";
									}
									?>
								</select>
								<select name="shipping_status">
									<?php 
									foreach ($shipping_status_array as $key => $value) {
										echo "<option value='{$key}'".($key==$shipping_status?" selected='selected' ":"").">{$value}</option>";
									}
									?>
								</select>
								<select name="shop_id">
									<?php 
									foreach ($shop_id_array as $key => $value) {
										echo "<option value='{$key}'".($key==$shop_id?" selected='selected' ":"").">{$value}</option>";
									}
									?>
								</select>
								第<input type="text"  name="page" style="width:60px" value="<?php echo $page; ?>">页/共<?php echo $page_count; ?>页
								<!-- <select name="page" style="width:100px">
									<?php for ($i=0; $i < $page_count; $i++) {  ?>
									<option value="<?php echo ($i+1); ?>" <?php if(($i+1)==$page){echo "selected='selected'";} ?>>
										第<?php echo ($i+1); ?>页
									</option>
									<?php } ?>
								</select> -->
								<!-- <select name="size" style="width:100px">
									<option value="20">20条/页</option>
									<option value="50">50条/页</option>
									<option value="100">100条/页</option>
									<option value="500">500条/页</option>
								</select> -->
								<button type="submit" class="btn">过滤</button>
							</form>
							<!-- <form class="form-search form-inline">
								<select name="page" style="width:60px">
									<?php for ($i=0; $i < $page_count; $i++) {  ?>
									<option value="<?php echo ($i+1); ?>" <?php if(($i+1)==$page){echo "selected='selected'";} ?>><?php echo ($i+1); ?></option>
									<?php } ?>
								</select>
								<select name="size" style="width:60px">
									<option value="20">20</option>
									<option value="20">50</option>
									<option value="20">100</option>
									<option value="20">500</option>
								</select>
								<input type="hidden" name="apply_status" value="<?php echo $apply_status; ?>">
								<input type="hidden" name="shipping_status" value="<?php echo $shipping_status; ?>">
								<button>Go</button>
							</form> -->
						</div>
						<div class="tab-pane <?php if($tab=='SEARCH') { ?>active<?php } ?>" id="panel-874023">
							<form class="form-search form-inline">
								<input type="hidden" name="ajax" value="search">
								<select id='search_type' name="search_type">
									<option value="BW_ORDER_ID" <?php if($search_type=='BW_ORDER_ID') { ?>selected="selected"<?php } ?>>
										BW订单号
									</option>
									<option value="BW_ORDER_SN" <?php if($search_type=='BW_ORDER_SN') { ?>selected="selected"<?php } ?>>
										分销商订单号
									</option>
									<option value="ERP_ORDER_ID" <?php if($search_type=='ERP_ORDER_ID') { ?>selected="selected"<?php } ?>>
										ERP订单ID
									</option>
									<option value="ERP_ORDER_SN" <?php if($search_type=='ERP_ORDER_SN') { ?>selected="selected"<?php } ?>>
										ERP订单号
									</option>
								</select>
								<input class="input-medium search-query" type="text" id="search_value" name="search_value" value="<?php echo $search_value; ?>"/> 
								<button type="submit" class="btn">查找</button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-12">
				<!-- <h3>LIST</h3> -->
				<table class="table table-bordered table-hover table-condensed">
					<thead>
						<tr tr_type='head_row'>
							<th>
								BW订单号
							</th>
							<th>
								对正正订单号
							</th>
							<th>
								分销商订单号
							</th>
							<th>
								分销商
							</th>
							<th colspan="1">
								ERP对接
							</th>
							<th>
								<select id="os_select" onchange="erp_filter()" style="width:100px;">
									<option value='ALL'>随意</option>
									<?php foreach ($_CFG['adminvars']['order_status'] as $key => $value) {
									?>
									<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
									<?php
									} ?>
								</select>
							</th>
								<!-- <br><select id="ps_select" onchange="erp_filter()">
									<option value='ALL'>随意</option>
									<?php foreach ($_CFG['adminvars']['psy_status'] as $key => $value) {
									?>
									<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
									<?php
									} ?>
								</select> -->
							<th colspan="1">
								<select id="ss_select" onchange="erp_filter()"  style="width:100px;">
									<option value='ALL'>随意</option>
									<?php foreach ($_CFG['adminvars']['shipping_status'] as $key => $value) {
									?>
									<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
									<?php
									} ?>
								</select>
								<!-- <button onclick="result_filter(0,0,0)">FILTER</button> -->
							</th>
							<th>
								撤单
							</th>
							<th>
								退货
							</th>
							<th>
								正正推送
							</th>
							<th>
								海关取引
							</th>
							<th>
								快递
							</th>
							<th>
								面单
							</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($bw_order_list as $bw_order) { ?>
						<tr tr_type='result_row'
							erp_order_status='<?php echo $bw_order['erp_order_status']; ?>' 
							erp_pay_status='<?php echo $bw_order['erp_pay_status']; ?>'
							erp_shipping_status='<?php echo $bw_order['erp_shipping_status']; ?>'
						>
							<td>
								<a href="bw_order_preview.php?bw_order_id=<?php echo $bw_order['order_id']; ?>" target="new_blank">
									<?php echo $bw_order['order_id']; ?>
								</a>
							</td>
							<td>
								<a href="bw_order_preview.php?bw_order_id=<?php echo $bw_order['order_id']; ?>" target="new_blank">
									<?php echo $bw_order['order_sn']; ?>
								</a>
							</td>
							<td>
								<a href="bw_order_preview.php?bw_order_id=<?php echo $bw_order['order_id']; ?>" target="new_blank">
									<?php echo $bw_order['outer_order_sn']; ?>
								</a>
							</td>
							<td>
								<?php echo "<!--".$bw_order['shop_id']."-->".$bw_order['distributor_name'].""; ?>
							</td>
							<td>
								<a href="../order_edit.php?order_id=<?php echo $bw_order['erp_order_id']; ?>"><?php echo $bw_order['erp_order_sn']; ?></a>
								<?php 
									/*
									if(!empty($bw_order['erp_order_id']) && $_SESSION['party_id']==65638 ){
										$erp_oi=order_info($bw_order['erp_order_id']);
										echo $erp_oi['formated_order_status'].",".
											$erp_oi['formated_pay_status'].",".
											$erp_oi['formated_shipping_status'];
									}
									*/
								?>
							</td>
							<td>
								<?php
									$color = 'black';
									$str='';
									if(!empty($bw_order['erp_order_sn'])){
										$str = $_CFG['adminvars']['order_status'][$bw_order['erp_order_status']];
										if($bw_order['erp_order_status']==0){
											$color='orange';
										}elseif($bw_order['erp_order_status']==1){
											$color='green';
										}else{
											$color='red';
										}
									}
								?>
								<span class="<?php echo $color; ?>">
									<?php echo $str ?>
								</span>
							</td>
							<!--
							<td>
								<?php
									if(!empty($bw_order['erp_order_sn'])){
										echo $_CFG['adminvars']['pay_status'][$bw_order['erp_pay_status']];
									}
								?>
							</td>
							-->
							<td>
								<?php
									$color = 'black';
									$str='';
									if(!empty($bw_order['erp_order_sn'])){
										$str = $_CFG['adminvars']['shipping_status'][$bw_order['erp_shipping_status']];
										if($bw_order['erp_shipping_status']==0){
											$color='orange';
										}elseif($bw_order['erp_shipping_status']==1){
											$color='green';
										}else{
											$color='red';
										}
									}
								?>
								<span class="<?php echo $color; ?>">
									<?php echo $str ?>
								</span>
							</td>
							<td >
								<?php 
								$ocs = BWOrderAgent::getBWOrderCancelStatus($bw_order['order_id']); 
								if($ocs == 'N'){
									$color="black";
									$text="未退款";
								}elseif($ocs == 'Y'){
									$color="orange";
									$text= "窗口受理";
								}elseif($ocs == 'F'){
									$color="green";
									$text= "ERP已受理";
								}else{
									$color="red";
									$text= "╮(╯_╰)╭";
								}
								echo "<span class='{$color}'>{$text}</span>";
								?>
							</td>
							<td>
								<?php 
								$ors = BWOrderAgent::getBWOrderReturnStatus($bw_order['order_id']); 
								if($ors == 'N'){
									$color="black";
									$text="未退货";
								}elseif($ors == 'Y'){
									$color="orange";
									$text= "窗口受理";
								}elseif($ors == 'F'){
									$color="green";
									$text= "ERP已受理";
								}else{
									$color="red";
									$text= "╮(╯_╰)╭";
								}
								echo "<span class='{$color}'>{$text}</span>";
								?>
								
							</td>
							<td>
								<?php 
									$ar_msg='╮(╯_╰)╭';
									$ar = json_decode($bw_order['apply_response']); 
									if($ar){
										$ar_msg=$ar->result_msg;
									}
									if($bw_order['apply_status']=='INIT'){ 
										$color="black";
									}
									elseif($bw_order['apply_status']=='READY'){ 
										$color="orange";
									}
									elseif($bw_order['apply_status']=='ACCEPT'){ 
										$color="green";
									}
									elseif($bw_order['apply_status']=='DENIED'){ 
										$color="red";
									}
									elseif($bw_order['apply_status']=='CANCEL'){ 
										$color="blue";
									}
									else{ 
										$color="red";
									}
								?>
								<span data-toggle="tooltip" title="<?php echo $ar_msg; ?>" class="<?php echo $color; ?>">
									<?php echo BWOrderAgent::explainApplyStatus($bw_order['apply_status']); ?>
								</span>
							</td>
							<td>
								<?php 
									$ar_msg='╮(╯_╰)╭';
									$ar = json_decode($bw_order['custom_history'],true); 
									$checkFlg='Unknown';
									$checkMsg='Unknown';
									if($ar){
										$checkFlg=$ar['mft']['CheckFlg'];
										$checkMsg=$ar['mft']['CheckMsg'];
										$ar_msg="接口调用: ".$ar['result_msg'].PHP_EOL.
											"数据预校验: ".$ar['mft']['CheckMsg'].PHP_EOL.
											"清关状态: ".$ar['mft']['Result'].PHP_EOL.
											"清关审核历史: ".PHP_EOL;
										$history=$ar['mft']['history'];
										foreach ($history as $event) {
											$ar_msg.="时间：".$event['CreateTime'].PHP_EOL;
											$ar_msg.="状态变更为 ".BWOrderAgent::explainShippingStatus($event['Status']).PHP_EOL;
										}
									}
									if($bw_order['apply_status']=='ACCEPT'){
										if($bw_order['shipping_status']=='24'){
											if(empty($bw_order['tracking_number'])){
												$color='blue';
											}else{
												$color='green';
											}
										}elseif($bw_order['shipping_status']=='01'){
											//库存不足
											$color='red';
										}elseif($bw_order['shipping_status']=='13'){
											//死在国检
											$color='red';
										}elseif($bw_order['shipping_status']=='23'){
											//死在海关单证
											$color='red';
										}elseif($bw_order['shipping_status']=='25'){
											//死在海关
											$color='red';
										}elseif($bw_order['shipping_status']=='99'){
											//取消
											$color='blue';
										}else{
											$color='orange';
										}
									}else{
										$color='black';
									}
									if($checkFlg=='0'){
										$color='red';
									}
								?>
								<span data-toggle="tooltip" title="<?php echo $ar_msg; ?>" data-placement="right" class="<?php echo $color; ?>">
									<?php 
										if($checkFlg=='0'){
											echo $checkMsg;
										}else{
											echo BWOrderAgent::explainShippingStatus($bw_order['shipping_status']); 
										}
									?>
								</span>
							</td>
							<td>
								<?php echo $bw_order['shipping_name']; ?>
							</td>
							<td>
								<?php echo $bw_order['tracking_number']; ?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<div class="pagination pagination-small pagination-right">
					<ul>
						<li>
						<?php if(1<$page){ ?>
							<a href="bw_order_monitor.php?page=<?php echo ($page-1); ?>&apply_status=<?php echo $apply_status; ?>&shipping_status=<?php echo $shipping_status; ?>">上一页</a>
						<?php }else{ ?>
							<a href="#">没有上一页了</a>
						<?php } ?>
						</li>
						<?php for ($i=max($page-5,0); $i < min($page_count,$page+5); $i++) {  ?>
						<li>
							<a href="bw_order_monitor.php?page=<?php echo ($i+1); ?>&apply_status=<?php echo $apply_status; ?>&shipping_status=<?php echo $shipping_status; ?>" style="<?php 
							if(($i+1)==$page){
								echo "color: black;";
							}
						?>"><?php echo ($i+1); ?></a>
						</li>
						<?php } ?>
						<li>
						<?php if($page_count>$page){ ?>
							<a href="bw_order_monitor.php?page=<?php echo ($page+1); ?>&apply_status=<?php echo $apply_status; ?>&shipping_status=<?php echo $shipping_status; ?>">下一页</a>
						<?php }else{ ?>
							<a href="#">没有下一页了</a>
						<?php } ?>
						</li>
					</ul>
				</div>
			</div>
			<div class="col-xs-12">
				<ul class="breadcrumb">
				<li>
					<a href="#">此处应有导航→_→</a> <span class="divider">/</span>
				</li>
				<li>
					<a href="#">然而什么都没有←_←</a> <span class="divider">/</span>
				</li>
				<li class="active">
					某保税仓库的监视系统 by Evil Giant Salamander <span class="divider">/</span>
				</li>
				<li>
					<a href="import" target="new_blank">传说中的订单批量导入</a> <span class="divider">/</span>
				</li>
			</ul>
			</div>
		</div>
	</div>
</body>
</html>