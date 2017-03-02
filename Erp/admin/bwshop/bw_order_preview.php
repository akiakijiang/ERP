<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_order.php';

$bw_order_id=$_REQUEST['bw_order_id'];

global $db;

$editable=false;
if(in_array($_SESSION['admin_name'],array('ljni','xrlao'))){
	$editable=true;
	if($_REQUEST['act']=='update_time'){
		$order_time=$_REQUEST['order_time'];
		$pay_time=$_REQUEST['pay_time'];
		$update_note='';
		if(empty($order_time) || empty($pay_time)){
			$update_note='时间不能为空';
		}else{
			$apply_status=$_REQUEST['apply_status'];
			if(!empty($apply_status)){
				$apply_status_sql=",apply_status='{$apply_status}'";
			}else{
				$apply_status_sql='';
			}
			$sql="UPDATE ecshop.bw_order_info 
			set order_time='{$order_time}',pay_time='{$pay_time}' {$apply_status_sql}
			where order_id='{$bw_order_id}'
			";
			$afx=$db->exec($sql);
			$update_note='更新影响了'.$afx.'行';
		}
	}
}

$bw_order_info=BWOrderAgent::getBWOrderInfoByBwOrderId($bw_order_id);
$bw_order_goods=BWOrderAgent::getBWOrderGoods($bw_order_id);
$bw_order_cancel_line=BWOrderAgent::getBWOrderCancelLine($bw_order_id);
$bw_order_return_line=BWOrderAgent::getBWOrderReturnLine($bw_order_id);

function stringizeForDd($v,$k="╮(╯_╰)╭"){
	if(empty($v) && $v!==0){
		return $k;
	}else{
		return $v;
	}
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
	</style>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div class="col-xs-2">
				<img src="http://to-a.ru/WEQY7W/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Hozei Souko No Tyuumon</h1>
					<p>
						虾米订单: <span class="label"><?php echo $bw_order_id; ?></span>
						| <span class="label"><?php echo $bw_order_info['order_sn']; ?></span>
						<?php if(!empty($bw_order_info['erp_order_id'])){ ?>
						ERP订单: <a href="../order_edit.php?order_id=<?php echo $bw_order_info['erp_order_id']; ?>" target="new_blank">
						<!-- <span class="label"><?php echo $bw_order_info['erp_order_id']; ?></span>|  -->
						<span class="label"><?php echo $bw_order_info['erp_order_sn']; ?></span>
						</a>
						<?php } else { ?>
						ERP订单：<span class="label">然而并没有</span>
						<?php } ?>
						虾米分销商: <span class="label"><?php echo $bw_order_info['shop_id']; ?></span>
						ERP分销商: <span class="label"><?php echo $bw_order_info['distributor_id']; ?></span>
						| <span class="label"><?php echo $bw_order_info['distributor_name']; ?></span>
					</p>
				</div>
			</div>
			<?php if(empty($bw_order_info)){ ?>
			<div class="col-xs-12">
				<div class="alert alert-error">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<h3>
						咕嘿嘿
					</h3>
					<p>
						<strong>╮(╯_╰)╭</strong> 然而并没有什么订单。
					</p>
				</div>
			</div>
			<?php } else { ?>
			<div class="col-xs-12">
				<div class="row-fluid">
					<div class="col-xs-4">
						<h3>虾米订单信息</h3>
						<h4>价格信息</h4>
						<dl class="dl-horizontal">
							<dt>商品金额</dt>
							<dd><?php echo stringizeForDd($bw_order_info['amount']); ?></dd>
							<dt>邮费</dt>
							<dd><?php echo stringizeForDd($bw_order_info['post_fee']); ?></dd>
							<dt>应收总价</dt>
							<dd><?php echo stringizeForDd($bw_order_info['goods_amount']); ?></dd>
							<dt>实际收费</dt>
							<dd><?php echo stringizeForDd($bw_order_info['payment']); ?></dd>
							<dt>支付流水</dt>
							<dd><?php echo stringizeForDd($bw_order_info['trade_trans_no']); ?></dd>
							<dt>支付代码</dt>
							<dd><?php echo stringizeForDd($bw_order_info['payment_code'])." | ".BWOrderAgent::explainPaymentCode($bw_order_info['payment_code']); ?></dd>
						</dl>
						<h4>发票信息</h4>
						<dl class="dl-horizontal">
							<dt>REMARK</dt>
							<dd><?php echo stringizeForDd($bw_order_info['remark']); ?></dd>
							<dt>抬头</dt>
							<dd><?php echo stringizeForDd($bw_order_info['title']); ?></dd>
						</dl>
						<h4>时间信息</h4>
						<dl class="dl-horizontal">
							<dt>订单时间</dt>
							<dd><?php echo stringizeForDd($bw_order_info['order_time']); ?></dd>
							<dt>支付时间</dt>
							<dd><?php echo stringizeForDd($bw_order_info['pay_time']); ?></dd>
							<dt>混进BWSHOP的时间</dt>
							<dd><?php echo stringizeForDd($bw_order_info['create_time']); ?></dd>
						</dl>
						<p>如果遇到【订单付款时间小于订单创建时间！】这样的坑事，找劳祥睿在这个页面改时间就可以了。</p>
						<?php if($editable){ ?>
						
						<dl class="dl-horizontal">
						<form class='inline-form' action="bw_order_preview.php?bw_order_id=<?php echo $bw_order_id; ?>" method='POST'>
							<input type='hidden' name='act' value='update_time'>
							<dt>订单时间更新：</dt>
							<dd>
								<input type='text' name='order_time' value='<?php echo stringizeForDd($bw_order_info['order_time'],''); ?>'>
							</dd>
							<dt>支付时间更新：</dt>
							<dd>
								<input type='text' name='pay_time' value='<?php echo stringizeForDd($bw_order_info['pay_time'],''); ?>'>
							</dd>
							<dt>需要强行重推？</dt>
							<dd><input type='checkbox' name='apply_status' value='INIT'>改成未推送</dd>
							<p><?php echo $update_note; ?></p>
							<dt></dt>
							<dd>
								<button class='btn'>更新</button>
							</dd>
							
						</form>
						</dl>
						<?php } ?>
						<h4>身份信息</h4>
						<dl class="dl-horizontal">
							<dt>身份证号码</dt>
							<dd><?php echo stringizeForDd($bw_order_info['mibun_number']); ?></dd>
							<dt>氏名</dt>
							<dd><?php echo stringizeForDd($bw_order_info['name']); ?></dd>
							<dt>电邮</dt>
							<dd><?php echo stringizeForDd($bw_order_info['email']); ?></dd>
							<dt>电话</dt>
							<dd><?php echo stringizeForDd($bw_order_info['phone']); ?></dd>
							<dt>账号</dt>
							<dd><?php echo stringizeForDd($bw_order_info['account']); ?></dd>
						</dl>
						<h4>运送信息</h4>
						<dl class="dl-horizontal">
							<dt>运单</dt>
							<dd><?php echo stringizeForDd($bw_order_info['tracking_number']); ?></dd>
							<!-- <dt>运送</dt>
							<dd><?php echo stringizeForDd($bw_order_info['shipping_id']); ?></dd> -->
							<dt>快递</dt>
							<dd><?php echo stringizeForDd($bw_order_info['shipping_name']); ?></dd>
							<dt>收件人</dt>
							<dd><?php echo stringizeForDd($bw_order_info['consignee']); ?></dd>
							<dt>联系方式</dt>
							<dd><?php echo stringizeForDd($bw_order_info['receiver_phone']); ?></dd>
							<dt>省份</dt>
							<dd><?php echo stringizeForDd($bw_order_info['province']); ?></dd>
							<dt>城市</dt>
							<dd><?php echo stringizeForDd($bw_order_info['city']); ?></dd>
							<dt>区县</dt>
							<dd><?php echo stringizeForDd($bw_order_info['district']); ?></dd>
							<dt>地址</dt>
							<dd><?php echo stringizeForDd($bw_order_info['address']); ?></dd>
						</dl>
					</div>
					<div class="col-xs-7">
						<div class="row-fluid">
							<div class="col-xs-12">
								<h3>
									正正保税仓推送 
									<span class="label">
									<?php 
									echo BWOrderAgent::explainApplyStatus($bw_order_info['apply_status']); 
									?>
									</span>
								</h3>
									<?php
									if($bw_order_info['apply_status']=='REFUSED'){
										$ar=json_decode($bw_order_info['apply_response'],true);
									?>
								<p>
									正正订单创建接口返回备注 
								</p>
								<blockquote>
									<p>
										<?php echo $ar['result_msg']; ?>
									</p>
								</blockquote>
									<?php
									}
									?>
							</div>
							<div class="col-xs-12">
								<h3>
									海关通关信息
									<span class="label">
									<?php 
									echo BWOrderAgent::explainShippingStatus($bw_order_info['shipping_status']); 
									?>
									</span>
									+
									<span class="label">
									<?php
									if(!empty($bw_order_info['tracking_number'])){
										echo "已贴面单";
									}else{
										echo "未贴面单";
									}
									?>
									</span>
									=
									<span class="label">
									<?php
									if($bw_order_info['shipping_status']=='24' && !empty($bw_order_info['tracking_number'])){
										echo "已发货";
									}else{
										echo "未发货";
									}
									?>
									</span>
								</h3>
									<?php
									$ar_msg='╮(╯_╰)╭';
									$ar = json_decode($bw_order_info['custom_history'],true); 
									if($ar){
										$ar_msg="接口调用: ".$ar['result_msg']."<br>".
											"数据预校验: ".$ar['mft']['CheckMsg']."<br>".
											"清关状态: ".$ar['mft']['Result']."<br>".
											"清关审核历史: "."<br>";
										$history=$ar['mft']['history'];
										foreach ($history as $event) {
											$ar_msg.="".$event['CreateTime']." | ";
											$ar_msg.="".BWOrderAgent::explainShippingStatus($event['Status'])."<br>";
										}
									}
									?>
								<blockquote>
									<p>
										<?php echo $ar_msg; ?>
									</p>
								</blockquote>
							</div>
							<div class="col-xs-12">
								<h3>虾米订单商品列表</h3>
								<table class="table table-bordered table-hover table-condensed">
									<thead>
										<tr>
											<th>
												ERP GOODS ID
											</th>
											<th>
												ERP GOODS STYLE ID
											</th>
											<th>
												商品名称
											</th>
											<th>
												数量
											</th>
											<th>
												总价
											</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($bw_order_goods as $bog) { ?>
										<tr>
											<td>
												<?php echo $bog['product_id']; ?>
											</td>
											<td>
												<?php echo $bog['outer_id']; ?>
											</td>
											<td>
												<?php echo $bog['goods_name']; ?>
											</td>
											<td>
												<?php echo $bog['quantity']; ?>
											</td>
											<td>
												<?php echo $bog['amount']; ?>
											</td>
										</tr>
									<?php } ?>
									</tbody>
								</table>
							</div>
							
						</div>
					</div>
				</div>
				<div class="row-fluid">
					<div class="col-xs-7">
						<h3>虾米订单撤单</h3>
						<p>
							<?php
								if(empty($bw_order_cancel_line) || $bw_order_cancel_line['refund_status']=='N'){
									echo "没有相应撤单请求。";
								}else{
									echo "于".$bw_order_cancel_line['create_time']."发起撤单。";
									if($bw_order_cancel_line['refund_status']=='Y'){
										echo "尚未生成ERP退款申请。";
										if($bw_order_info['apply_status']=='CANCEL'){
											echo "似乎已经成功拦截向正正的推送。";
										}else{
											echo "似乎没有成功拦截向正正的推送。";
										}
									}elseif($bw_order_cancel_line['refund_status']=='F'){
										echo "已经生成ERP退款申请。";
										$sql="SELECT * FROM romeo.refund WHERE ORDER_ID='{$bw_order_info['erp_order_id']}'";
										$refunds=$db->getAll($sql);
										foreach ($refunds as $r) {
							?>
								<a href="../refund_view.php?refund_id=<?php echo $r['REFUND_ID']; ?>">
									<span class="label"><?php echo $r['REFUND_ID']; ?></span>
								</a> &nbsp;
							<?php
										}
									} 
								}
							?>
						</p>
					</div>
					<div class="col-xs-7">
						<h3>虾米订单退货</h3>
						<p>
							<?php
								if(empty($bw_order_return_line) || $bw_order_return_line['return_status']=='N'){
									echo "没有相应退货请求。";
								}else{
									echo "于".$bw_order_return_line['create_time']."发起退货。";
							?>
						</p>
						<table class="table table-bordered table-hover table-condensed">
							<thead>
								<tr>
									<th>
										虾米商品编号
									</th>
									<th>
										ERP GOODS ID
									</th>
									<th>
										商品名称
									</th>
									<th>
										数量
									</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$sql="SELECT * FROM bw_order_goods_return WHERE return_id='{$bw_order_return_line['return_id']}'";
									$orgs=$db->getAll($sql);
									foreach ($orgs as $org) {
								?>
								<tr>
									<td>
										<?php echo $org['product_id']; ?>
									</td>
									<td>
										<?php echo $org['outer_id']; ?>
									</td>
									<td>
										<?php echo $org['goods_name']; ?>
									</td>
									<td>
										<?php echo $org['quantity']; ?>
									</td>
								</tr>
								<?php
									}
								?>
							</tbody>
						</table>
						<p>
							<?php

									if($bw_order_return_line['return_status']=='Y'){
										echo "尚未生成ERP退货申请。";
									}elseif($bw_order_return_line['return_status']=='F'){
										echo "已经生成ERP退货申请。";

										$sql="SELECT * FROM ecshop.service WHERE order_id='{$bw_order_info['erp_order_id']}'";
										$services=$db->getAll($sql);
										foreach ($services as $s) {
							?>
								<a href="../sale_serviceV3.php?start=&end=&act=search&service_type=2&search_text=<?php echo $bw_order_info['erp_order_sn']; ?>">
									<span class="label"><?php echo $s['service_id']; ?></span>
								</a> &nbsp;
							<?php
										}
									}
								}
							?>
						</p>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</body>
</html>