<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_order.php';
require_once __DIR__.'/lib_bw_shop.php';
require_once __DIR__.'/lib_bw_party.php';
require_once __DIR__.'/../config.vars.php';

global $db;

function getRequest($name,$default=null){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

$plan=getRequest('plan');
$shop_id=getRequest('shop_id',0);
$days=getRequest('days',15);

$shop_id_array=array("0"=>"全部店铺",);
$shop_list=BWShopAgent::shopList();
foreach ($shop_list as $shop) {
	$shop_id_array[$shop['shop_id']]=$shop['shop_name'];
}

$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
$bw_facility_id=BWPartyAgent::getBWFacilityId();

$sql_base="SELECT boi.*,
					eoi.order_id erp_order_id,
					eoi.order_sn erp_order_sn,
					d.distributor_id,
					d.`name` distributor_name,
					eoi.order_status erp_order_status,
					eoi.pay_status erp_pay_status,
					eoi.shipping_status erp_shipping_status,
					eoi.order_time erp_order_time 
	FROM ecshop.ecs_order_info eoi 
force index (order_info_multi_index)
-- use index (party_id)
	INNER join ecshop.bw_order_info boi on eoi.taobao_order_sn=boi.outer_order_sn
	LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
	LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
	WHERE
	eoi.party_id in ({$bw_parties_sql}) 
	and eoi.facility_id='{$bw_facility_id}'
	and eoi.order_time>SUBDATE(now(),INTERVAL {$days} day)
";

if($shop_id!=0){
	$sql_base.=" and boi.shop_id=".intval($shop_id)." ";
}

$plans=array();
$plans['PLAN_0']="已推送未申报的已确认订单";
$plans['PLAN_1']="保税仓拒收的已确认订单";
$plans['PLAN_2']="申报关闭的已确认订单";
$plans['PLAN_3']="海关货物放行然而还是待配货的订单";
$plans['PLAN_4']="海关货物放行然而已取消的订单";

if($plan=='PLAN_0'){
	// $plans['PLAN_0']="已推送未申报的已确认订单";
	$sql_base.=" and eoi.order_status=1 and boi.apply_status='ACCEPT' and boi.shipping_status='00' and (boi.tracking_number is not null or boi.tracking_number is null) ";
}elseif($plan=='PLAN_1'){
	// $plans['PLAN_1']="保税仓拒收的已确认订单";
	$sql_base.=" and eoi.order_status=1 and boi.apply_status='REFUSED' ";
}elseif($plan=='PLAN_2'){
	// $plans['PLAN_2']="申报关闭的已确认订单";
	$sql_base.=" and eoi.order_status=1 and boi.apply_status='ACCEPT' and boi.shipping_status='99'  and (boi.tracking_number is not null or boi.tracking_number is null) ";
}elseif($plan=='PLAN_3'){
	// $plans['PLAN_3']="海关货物放行然而还是待配货的订单";
	$sql_base.=" and eoi.order_type_id='SALE' and eoi.order_status=1 and eoi.shipping_status=0 and boi.apply_status='ACCEPT' and boi.shipping_status='24' and (boi.tracking_number is not null or boi.tracking_number is null) ";
}elseif($plan=='PLAN_4'){
	// $plans['PLAN_4']="海关货物放行然而已取消的订单";
	$sql_base.=" and eoi.order_type_id='SALE' and eoi.order_status=2 and boi.apply_status='ACCEPT' and boi.shipping_status='24' and (boi.tracking_number is not null or boi.tracking_number is null) ";
}else{
	$sql_base='';
}
// print_r( $sql_base);
if(!empty($sql_base)){
	$list=$db->getAll($sql_base);
}else{
	$list=null;
}
// var_dump($list);

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
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div  class="col-xs-2">
				<img src="http://to-a.ru/wBYpCm/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Hozei Souko No Radar Syouzyun Sisutemu</h1>
					<p>
						查询最近<?php echo $days; ?>天内特殊状态的订单！让我们伸出触手！一口气看完！没有磨叽的分页！
						<!-- <form class="form-inline"> -->
							<select id="shop_id_select" name="shop_id" onchange="onSelectShopIdChanged()">
								<?php 
								foreach ($shop_id_array as $key => $value) {
									echo "<option value='{$key}'".($key==$shop_id?" selected='selected' ":"").">{$value}</option>";
								}
								?>
							</select>
						<!-- </form> -->
						<script type="text/javascript">
						function onSelectShopIdChanged(){
							/*console.log(this.form);this.form.submit();*/
							location.href="bw_order_sight.php?days=<?php echo $days; ?>&shop_id="+$("#shop_id_select").val()+"&plan=<?php echo $plan; ?>";
						}
						</script>
					</p>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="col-xs-11 well">
				
				<ul class="unstyled inline">
				<?php foreach ($plans as $key => $value) {
					if($key!=$plan){
						$badge_type='badge-success';
					}else{
						$badge_type='';
					}
				?>
					<li>
						<a href="bw_order_sight.php?days=<?php echo $days; ?>&shop_id=<?php echo $shop_id; ?>&plan=<?php echo $key; ?>"><span class="label <?php echo $badge_type; ?>"><?php echo $value; ?></span></a>
					</li>
				<?php
				}
				?>
				</ul>
			</div>
			<div class="col-xs-11">
			<?php if(!empty($list)) { ?>
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
							<th colspan="3">
								ERP对接
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
					<?php foreach ($list as $bw_order) { ?>
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
								<br>
								<?php echo $bw_order['erp_order_time']; ?>
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
									<?php echo $str; ?>									
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
									<?php echo $str; ?>
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
			<?php }else{
			?>
				<p>然而并没有订单</p>
			<?php
			}
			?>
			</div>
		</div>
	</div>
</body>
</html>
