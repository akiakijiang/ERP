<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_issue_orders.php';
require_once __DIR__.'/../config.vars.php';

global $_CFG;

$page=1;
$size=20;
$total=0;
$start_days_ago=7;
$end_days_ago=0;

if(isset($_REQUEST['page'])){
	$page=$_REQUEST['page'];
}
if(isset($_REQUEST['size'])){
	$size=$_REQUEST['size'];
}

if(isset($_REQUEST['start_days_ago'])){
	$start_days_ago=$_REQUEST['start_days_ago'];
}
if(isset($_REQUEST['end_days_ago'])){
	$end_days_ago=$_REQUEST['end_days_ago'];
}

$issue_order_list=BWIssueOrderAgent::getE2BIssueOrders($start_days_ago,$end_days_ago,$size,($page-1)*$size,$total);

$page_count= ceil($total / $size);

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
	</style>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div  class="col-xs-2">
				<img src="http://to-a.ru/7CXI21/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>ERP TO BWSHOP :: ISSUE ORDERS</h1>
					<p>ERP orders in certain period having not transfered to BWSHOP</p>
				</div>
			</div>
			<div class="col-xs-12">
				<p>只有以下藩属的订单会被搜救：<span class="label"><?php echo implode('</span>、<span class="label">', BWIssueOrderAgent::getE2BSyncDistributorListNames()); ?></span>。其他的自(zi)行(sheng)录(zi)单(mie)。</p>
				<form class="form-search form-inline">
					查找此时间范围内的未转化订单
					<input class="input-small" type="text" id="start_days_ago" name="start_days_ago" value="<?php echo $start_days_ago; ?>"/>
					天前到
					<input class="input-small" type="text" id="end_days_ago" name="end_days_ago" value="<?php echo $end_days_ago; ?>"/> 
					天前（0天前表示当前）
					<button type="submit" class="btn">查找</button>
				</form>
			</div>
			<div class="col-xs-11">
				<!-- <h3>LIST</h3> -->
				<table class="table table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>No.</th>
							<th>SHOP</th>
							<th>ERP ORDER ID</th>
							<th>ERP ORDER SN</th>
							<th>TAOBAO ORDER SN</th>
							<!-- <th>ORDER STATUS</th>
							<th>PAY STATUS</th>
							<th>SHIPPING STATUS</th> -->
							<th>STATUS</th>
							<th>ORDER TIME</th>
							<th>PAY TIME</th>
							<th>CONFIRM TIME</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($issue_order_list as $index => $issue_order) {
						?>
						<tr>
							<td>
								<?php echo ($index+1); ?>
							</td>
							<td>
								<?php echo $issue_order['distributor_name']; ?>
							</td>
							<td>
								<a href="../order_edit.php?order_id=<?php echo $issue_order['order_id']; ?>">
									<?php echo $issue_order['order_id']; ?>
								</a>
							</td>
							<td>
								<a href="../order_edit.php?order_id=<?php echo $issue_order['order_id']; ?>">
									<?php echo $issue_order['order_sn']; ?>
								</a>
							</td>
							<td>
								<?php echo $issue_order['taobao_order_sn']; ?>
							</td>
							<td>
								<?php echo $_CFG['adminvars']['order_status'][$issue_order['order_status']]; ?>
							<!-- </td>
							<td> -->
								<?php echo $_CFG['adminvars']['pay_status'][$issue_order['pay_status']]; ?>
							<!-- </td>
							<td> -->
								<?php echo $_CFG['adminvars']['shipping_status'][$issue_order['shipping_status']]; ?>
							</td>
							<td>
								<?php echo $issue_order['order_time']; ?>
							</td>
							<td>
								<?php echo $issue_order['pay_time']; ?>
							</td>
							<td>
								<?php echo $issue_order['confirm_time']; ?>
							</td>
						</tr>
						<?php
						}
						?>
					</tbody>
				</table>
				<div class="pagination pagination-small pagination-right">
					<ul>
						<li>
						<?php if(1<$page){ ?>
							<a href="<?php echo "bw_issue_orders.php?page=".($page-1)."&start_days_ago=".$start_days_ago."&end_days_ago=".$end_days_ago; ?>">上一页</a>
						<?php }else{ ?>
							<a href="#">没有上一页了</a>
						<?php } ?>
						</li>
						<?php for ($i=max($page-5,0); $i < min($page_count,$page+5); $i++) {  ?>
						<li>
							<a href="<?php echo "bw_issue_orders.php?page=".($i+1)."&start_days_ago=".$start_days_ago."&end_days_ago=".$end_days_ago; ?>" style="<?php 
							if(($i+1)==$page){
								echo "color: black;";
							}
						?>"><?php echo ($i+1); ?></a>
						</li>
						<?php } ?>
						<li>
						<?php if($page_count>$page){ ?>
							<a href="<?php echo "bw_issue_orders.php?page=".($page+1)."&start_days_ago=".$start_days_ago."&end_days_ago=".$end_days_ago; ?>">下一页</a>
						<?php }else{ ?>
							<a href="#">没有下一页了</a>
						<?php } ?>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</body>
</html>