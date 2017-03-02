<?php
define('IN_ECS', true);
require_once(__DIR__.'/../includes/init.php');

global $db;
global $_CFG;

function getRequest($name,$default=null){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

function listCatholicOrderMapping($params=array(),$limit=20,$offset=0){
	global $db;
	$cond="";
	foreach ($params as $key => $value) {
		if(!empty($value)) {$cond.=" AND {$key}='{$value}' ";}
	}
	$sql="SELECT
			mapping_id,
			outer_order_sn,
			platform,
			shipping_status,
			application_key,
			tracking_numbers,
			created_time,
			update_time,
			memo
		FROM
			ecshop.ecs_order_mapping
		WHERE
			1 {$cond}
		ORDER BY
			mapping_id DESC
		LIMIT {$limit} OFFSET {$offset}
	";
	return $db->getAll($sql);
}

function getShopNameWithAppKey($application_key){
	global $db;
	$sql="SELECT
		nick
	FROM
		ecshop.taobao_shop_conf
	WHERE
		application_key = '{$application_key}'
	";
	return $db->getOne($sql);
}

function getPlatformList(){
	global $db;
	$sql="SELECT DISTINCT platform FROM ecshop.ecs_order_mapping";
	return $db->getCol($sql);
}

$act=getRequest('act','');
$list=array();
$page=intval(getRequest('page',1));
$page=max($page,1);

$params=array();

if($act=='list'){
	$params['outer_order_sn']=getRequest('outer_order_sn',null);
	$params['platform']=getRequest('platform',null);

	$limit=getRequest('limit',20);
	// $offset=getRequest('offset',0);
	$offset=($page-1)*$limit;


	$list=listCatholicOrderMapping($params,$limit,$offset);
}

$platform_list=getPlatformList();

?>
<!DOCTYPE html>
<html>
<head>
	<title>普适内外订单映射</title>
	<style type="text/css">
	table {
		width:100%;
	}
	table, td, th {
		border-collapse: collapse;
		border: 1px solid gray;
		padding:1px 5px;
		font-size: 0.8em;
	}
	</style>
</head>
<body>
	<h1>统一发货同步监控</h1>
	
	<div style="margin:10px">
		<form><!--target="result_iframe"-->
			<input type='hidden' name='act' value='list'>
			外部订单号 
			<input type='text' name='outer_order_sn' value='<?php echo $params['outer_order_sn']; ?>'>
			平台
			<!--  <input type='text' name='platform' value='<?php echo $params['platform']; ?>'> -->
			<select name='platform'>
				<?php foreach ($platform_list as $platform_code) {
					echo "<option value='{$platform_code}' ".($platform_code==$params['platform']?"selected='selected'":'').">{$platform_code}</option>";
				} ?>
				<!-- <option value="%">只要不是分销的 ╮(╯_╰)╭</option> -->
			</select>			
			<input type='submit'>
			&nbsp;&nbsp;&nbsp;
			页码 
			<button onclick="document.getElementById('page').value=0+parseInt(document.getElementById('page').value)-1;">Prev</button>
			<input type='text' id='page' name='page' value='<?php echo $page; ?>' style="width:40px;text-align:center">
			<button onclick="document.getElementById('page').value=0+parseInt(document.getElementById('page').value)+1;">Next</button>
		</form>
	</div>
	<hr>
	<!-- <iframe name="result_iframe" style="width:95%;height:400px;border: 1px solid green;"></iframe> -->
	<table>
		<thead>
			<tr>
				<th>Mapping ID</th>
				<th>平台</th>
				<th>外部订单号</th>
				<th>发货状态</th>
				<th>app key</th>
				<th>已同步运单号</th>
				<th>创建更新时间</th>
				<th>备注</th>
				<th>ERP订单ID</th>
				<th>ERP订单号</th>
				<th>ERP订单TBSN</th>
				<th>ERP订单面单号</th>
				<th>ERP发货</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$color=0;
			foreach ($list as $line) {
				$color++;
				
				if($line['platform']=='fenxiao'){
					$sql="SELECT 
						    -- mp.outer_order_sn,
						    -- foi.tc_order_id,
						    -- foi.fenxiao_id,
						    -- oi.taobao_order_sn,
						    -- oi.distribution_purchase_order_sn
						    oi.order_id,oi.order_sn,mp.taobao_order_sn,oi.shipping_time,oi.shipping_status
						FROM
						    ecshop.ecs_order_mapping mp
						        inner JOIN
						    ecshop.sync_taobao_fenxiao_order_info foi ON mp.outer_order_sn = foi.fenxiao_id 
						        inner JOIN
						    ecshop.ecs_order_info oi ON foi.tc_order_id = oi.taobao_order_sn and foi.create_timestamp>'2015-09-11 00:00:00' and foi.last_update_timestamp>'2015-09-11 00:00:00'
						where mp.platform='fenxiao'
						and mp.outer_order_sn='{$line['outer_order_sn']}'";
				}else{
					$sql="SELECT order_id,order_sn,taobao_order_sn,shipping_time,shipping_status
						FROM ecshop.ecs_order_info 
						WHERE taobao_order_sn like '{$line['outer_order_sn']}%'
					";
				}
				$orders=$db->getAll($sql);
				foreach ($orders as $key => $value) {
					$sql="SELECT
							tracking_number
						FROM
							romeo.order_shipment os
						INNER JOIN romeo.shipment s ON os.SHIPMENT_ID = s.SHIPMENT_ID
						WHERE
							os.ORDER_ID = '{$value['order_id']}'
						AND s.`STATUS` != 'SHIPMENT_CANCELLED'
						";
					$orders[$key]['tracking_numbers']=$db->getCol($sql);
				}
				echo "<!-- LINE".PHP_EOL;
				print_r($line);
				echo PHP_EOL."-->".PHP_EOL;
			?>
			<tr style="background-color:<?php echo ($color%2?'#EEEEEE':'#FFFFFF'); ?>">
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo $line['mapping_id']; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo $line['platform']; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo $line['outer_order_sn']; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo $line['shipping_status']; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo getShopNameWithAppKey($line['application_key'])."<!-- ".$line['application_key']." -->"; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo (empty($line['tracking_numbers'])?'':implode('<br>', explode(',',$line['tracking_numbers']))); ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>">From <?php echo $line['created_time']; ?><br>To <?php echo $line['update_time']; ?></td>
				<td rowspan="<?php echo (count($orders)>0?count($orders):1); ?>"><?php echo $line['memo']; ?></td>

				<td rowspan="1"><?php echo $orders[0]['order_id']; ?></td>
				<td rowspan="1"><?php echo $orders[0]['order_sn']; ?></td>
				<td rowspan="1"><?php echo $orders[0]['taobao_order_sn']; ?></td>
				<td rowspan="1"><?php echo (empty($orders[0]['tracking_numbers'])?'':implode('<br>',$orders[0]['tracking_numbers'])); ?></td>
				<td rowspan="1">
					<?php echo $_CFG['adminvars']['shipping_status'][$orders[0]['shipping_status']].' / '.date('Y-m-d H:i:s',$orders[0]['shipping_time']); ?>
				</td>
			</tr>
			<?php
				for ($i=1; $i < count($orders); $i++) { 
			?>
			<tr style="background-color:<?php echo ($color%2?'#EEEEEE':'#FFFFFF'); ?>">
				<td rowspan="1"><?php echo $orders[$i]['order_id']; ?></td>
				<td rowspan="1"><?php echo $orders[$i]['order_sn']; ?></td>
				<td rowspan="1"><?php echo $orders[$i]['taobao_order_sn']; ?></td>
				<td rowspan="1"><?php echo (empty($orders[$i]['tracking_numbers'])?'':implode('<br>',$orders[$i]['tracking_numbers'])); ?></td>
				<td rowspan="1">
					<?php echo $_CFG['adminvars']['shipping_status'][$orders[$i]['shipping_status']].' / '.date('Y-m-d H:i:s',$orders[$i]['shipping_time']); ?>
				</td>
			</tr>
			<?php
				}
			} ?>
		</tbody>
	</table>
	<hr>
	<div style="text-align:center">All Hail Sinri Edogawa!</div>
</body>
</html>