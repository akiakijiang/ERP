<?php
/**
 * 中通热敏打印
 * 
 * @author last modified by qdi
 */
define('IN_ECS', true);
require_once('includes/lib_express_arata.php');

global $slave_db;

$arata_list=array(
	'115'=>array('SH'=>'上海/嘉善TP','JX'=>'嘉兴',/*'BJ'=>'北京',*/'CD'=>'成都','SZSG'=>'苏州',/*'JXSG'=>'嘉兴水果','SHSG'=>'上海水果','CDSG'=>'成都水果','WHSG'=>'武汉水果','BJSG'=>'北京水果','SHZSG'=>'深圳水果'*/),
	'89'=>array('DG'=>'东莞'/*,'BJ'=>'北京'*/),
	'99'=>array('SH'=>'上海/嘉善TP','BJ'=>'北京/北京水果外包','JXSG'=>'嘉兴水果外包','SHSG'=>'上海水果','WLBJC'=>'万霖北京仓',/*'WHSG'=>'武汉水果','BJSG'=>'北京水果','KBFX'=>'康贝奉贤'*/),
//	'85'=>array('WH'=>'武汉'),  圆通改用单个拉取方式
	'12'=>array('SHSG'=>'上海水果'),
);

$jd_cod_list = array(
	'2836'=>'ASC京东旗舰店',
);


$admin_list=array('wjzhu','ytchen','ljni','jche','wlxu1','ybliu','sjgu','hhuang','mjzhou','rjsu','wbzhang','hluo1','hyzhang1','llli');
$isAdmin=false;
if(in_array($_SESSION['admin_name'],$admin_list)){
	$isAdmin=true;
}

$isBJC=false;
if(in_array($_SESSION['admin_name'],array('Bjc1'))){
	$isBJC=true;
}

$is_check_waste=false;
if(isset($_REQUEST['is_check_waste']) && $_REQUEST['is_check_waste']==1){
	$is_check_waste=true;
}


//var_dump(apply_zto_thermal(100));
if($isAdmin && $_REQUEST['act'] == 'apply'){
	$site=$_REQUEST['site'];
	$kazu_str=$_REQUEST['kazu'];
	if($site != 'JDCOD'){
		if(empty($kazu_str)){
			$kazu=500;
		}else{
			$kazu=intval($kazu_str);
			if($kazu<=0)$kazu=500;
		}
	
		if($_REQUEST['type']==115){
			zto_mail_applys($site,$kazu);
		}elseif ($_REQUEST['type']==89) {
			apply_sto_arata_tracking_number($site,$kazu);
		}elseif ($_REQUEST['type']==99) {
			ht_applyNewBillCode($site,$kazu);
		}elseif ($_REQUEST['type']==85){
			yto_applyNewBillCode($site,$kazu);
		}
	}else{
		jd_applyBillCode($_REQUEST['type'],100);
	}
}else if($isAdmin && $_REQUEST['act'] == 'update'){
	$distributor_id=$_REQUEST['distributor_id'];
	$is_delete=$_REQUEST['is_delete'];
	global $slave_db;
	$sql = "update romeo.distributor_shipping set is_delete = {$is_delete} where distributor_id = '{$distributor_id}' and shipping_id = 146 ";
	$slave_db->query($sql);
}

// $arata_count_result=arata_count();
// $jd_count_result=jd_arata_count();

$arata_count_result=arata_group_count();
$jd_count_result=jd_arata_group_count();

// print_r($arata_count_result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			table.detail_table {
				border: 1px solid gray;
				border-collapse:collapse;
			}
			table.detail_table td {
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 4px;
				text-align: center;
			}
			table.detail_table th {
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 4px;
				font-size: 15px;
				text-align: center;
			}
		</style>
		<title>热敏面单资源管理</title>
	</head>
	<body>
		<code>本页面使用缓存数据以提高效率。与实时数据有若干分钟的偏差。</code>
		<h1>热敏资源管理</h1>
		<span style="color:red;">除显示的快递组合，其他面单不足，直接与网点联系</span>
		<div>
		<div>
			<table class='detail_table'>
				<tr>
					<th>运送方式</th>
					<th>大本营所在地</th>
					<th>没有使用的面单数</th>
					<th>准备使用的面单数</th>
					<th>绑定待回传的面单数</th>
					<th>已经回传的面单数</th>
					<th>回传异常的面单数</th>
					<th>浪费数</th>
					<th>如果自动面单申请没有正常运作，可以手动申请以应急</th>
				</tr>
				<?php
				$bjc_set=array('89-北京');
				foreach ($arata_count_result as $shipping_id => $shipping_list) {
					foreach ($shipping_list as $site_code => $site_list) {
						echo "<tr>";
						echo "<td>";
						echo $slave_db->getOne("select shipping_name from ecshop.ecs_shipping where shipping_id=".$shipping_id);
						// if($shipping_id==115)echo "中通"; elseif ($shipping_id==89) echo "申通";
						echo "</td>";
						// $sum=$site_list['N']+$site_list['Y']+$site_list['F'];
						echo "<td>".$site_list['BranchName']."</td>";
						echo "<td>".$site_list['N']."</td>";
						echo "<td>".$site_list['R']."</td>";
						echo "<td>".$site_list['Y']."</td>";
						echo "<td>".$site_list['F']."</td>";
						echo "<td>".$site_list['E']."</td>";
						echo "<td>";
						if($is_check_waste){
							echo wastedMailNos($shipping_id,$site_code);
						}else{
							echo "不明";
						}
						echo "</td>";
						echo "<td>";
						if(($isAdmin || ($isBJC && in_array($shipping_id.'-'.$site_list['BranchName'], $bjc_set)))&& !($shipping_id =='115' && $site_list['BranchName']=='嘉兴') && !in_array($shipping_id,array('85','12'))){
				?>
							<form action="thermal_manage.php" method="POST">
						     	<input type="hidden" name="act" value="apply">
						     	<input type="hidden" name='type' value='<?php echo $shipping_id; ?>'>
						      	<input type='hidden' name='site' value='<?php echo $site_code; ?>'>
						      	自定数量（可不填）
						      	<input type='text' name='kazu' value=''>
						      	→_→
						      	<input type="submit" value="手动申请"> 
						    </form>
				<?php
						}
						echo "</td>";
						echo "</tr>";
					}
				}
				?>
			</table>
			<p>
				手动申请一旦开始，耗时较长，请耐心等待。如没有增加，请联系采购人员/快递网点购买号段~
			</p>
		</div>
		<div>
			如果需要针对特定组织-仓库-运送方式更改其面单打印方式，请
			<a href="ArataCarrierBillSwitch.php">
				进入切换页面
			</a>
		 	。
		</div>
		</div>
		
		<br/>
		<h2>京东货到付款店铺热敏资源设置</h2>
		<span style="color:red;">京东运单有效期只有三个月，请在使用完成后适量拉取，每次最多100单</span>
		<div>
		<div>
			<table class='detail_table'>
				<tr>
					<th>京东店铺</th>
					<th>没有使用的面单数</th>
					<th>已绑定的面单数</th>
					<th>浪费数</th>
					<th>如果自动面单申请没有正常运作，可以手动申请以应急</th>
				</tr>
				<?php
				foreach ($jd_count_result as $distributor_id => $site_list) {
						echo "<tr>";
						// $sum=$site_list['N']+$site_list['Y']+$site_list['F'];
						echo "<td>".$site_list['DistributorName']."</td>";
						echo "</td>";
						echo "<td>".$site_list['N']."</td>";
						echo "<td>".$site_list['Y']."</td>";
						echo "<td>";
						echo wastedJdBillNos($distributor_id);
						echo "</td>";
						echo "<td>";
						if($isAdmin){
				?>
							<form action="thermal_manage.php" method="POST">
						     	<input type="hidden" name="act" value="apply">
						     	<input type="hidden" name='type' value='<?php echo $distributor_id; ?>'>
						      	<input type='hidden' name='site' value='JDCOD'>
						      	平台限制100单
						      	<input type='text' name='kazu' disabled="disabled" value='100'>
						      	→_→
						      	<input type="submit" value="手动申请"> 
						    </form>
				<?php
						}
						echo "</td>";
						echo "</tr>";
				}
				?>
			</table>
			<p>
				手动申请一旦开始，耗时较长，请耐心等待。如没有增加，请联系ERP查看调度错误提示，谢谢~
			</p>
		</div>
		</div>
	</body>
</html>

<?php

function arata_count(){
	global $arata_list;
	global $slave_db;
	$result=array();
	foreach ($arata_list as $shipping_id => $shipping_list) {
		foreach ($shipping_list as $Branch => $BranchName) {
			$sql = "select count(tracking_number) as count,status from ecshop.thermal_express_mailnos where shipping_id = {$shipping_id} and branch='{$Branch}' group by status";
			$numbers = $slave_db->getAll($sql);
			$not_used_count = 0;
			$using_count = 0;
			$used_count = 0;
			$error_count = 0;
			foreach($numbers as $number){
				$status = $number['status'];
				$count = $number['count'];
				if($status == 'N'){//init
					$not_used_count = $count;
				}elseif($status == 'Y'){//ing
					$using_count = $count;
				}elseif($status == 'F'){//finish
					$used_count = $count;
				}elseif($status == 'E'){//error
					$used_count = $count;
				}
			}
			$result[$shipping_id][$Branch]=array('BranchName'=>$BranchName,'N'=>$not_used_count,'Y'=>$using_count,'F'=>$used_count,'E'=>$error_count);
		}
	}
	return $result;
}

function arata_group_count(){
	global $arata_list;
	global $slave_db;
	// global $db;
	$sql="SELECT
			shipping_id,
			branch,
			count(tracking_number)AS count,
			STATUS
		FROM
			ecshop.thermal_express_mailnos
		WHERE 1
		GROUP BY 
			shipping_id,
			branch,
			STATUS
	";
	$group_list=$slave_db->getAll($sql);
	// $group_list=$db->getAll($sql);
	$result=array();
	foreach ($arata_list as $shipping_id => $sh_g) {
		foreach ($sh_g as $branch => $BranchName) {
			$result[$shipping_id][$branch]=array('BranchName'=>$BranchName,'N'=>0,'Y'=>0,'F'=>0,'E'=>0);
		}		
	}
	foreach ($group_list as $group) {
		if(isset($arata_list[$group['shipping_id']][$group['branch']])){
			// $result[$group['shipping_id']][$group['branch']]['BranchName']=$arata_list[$group['shipping_id']][$group['branch']];
			$result[$group['shipping_id']][$group['branch']][$group['STATUS']]=$group['count'];
		}
	}
	// print_r($result);
	return $result;
}

function jd_arata_count(){
	global $jd_cod_list;
	global $slave_db;
	$result=array();
	foreach ($jd_cod_list as $distributor_id => $distributor_name) {
		$sql = "select count(tracking_number) as count,status from ecshop.jd_bill_code where distributor_id = {$distributor_id} group by status";
		$numbers = $slave_db->getAll($sql);
		$not_used_count = 0;
		$using_count = 0;
		$used_count = 0;
		foreach($numbers as $number){
			$status = $number['status'];
			$count = $number['count'];
			if($status == 'N'){//init
				$not_used_count = $count;
			}elseif($status == 'Y'){//ing
				$using_count = $count;
			}
		}
		$result[$distributor_id]=array('DistributorName'=>$distributor_name,'N'=>$not_used_count,'Y'=>$using_count);
	}
	return $result;
}

function jd_arata_group_count(){
	global $jd_cod_list;
	global $slave_db;

	$result=array();

	$sql="SELECT
			distributor_id,
			count(tracking_number)AS count,
			STATUS
		FROM
			ecshop.jd_bill_code
		WHERE
			distributor_id in ('2836')
		GROUP BY
			distributor_id,
			STATUS
	";
	$group_list=$slave_db->getAll($sql);
	foreach ($jd_cod_list as $distributor_id => $distributor_name) {
		$result[$distributor_id]=array('DistributorName'=>$distributor_name,'N'=>0,'Y'=>0);
	}
	foreach ($group_list as $line) {
		$result[$line['distributor_id']][$line['STATUS']]=$line['count'];
	}
	return $result;
}
/**
-- the count for read-used numbers  
select count(1) FROM  ecshop.thermal_express_mailnos tem  
INNER JOIN romeo.shipment s on s.TRACKING_NUMBER=tem.tracking_number and s.SHIPMENT_TYPE_ID=tem.shipping_id  
where tem.`status`!='N'  and s.`STATUS` !='SHIPMENT_CANCELLED';  
-- the count for real-wasted numbers  
select count(1) FROM  ecshop.thermal_express_mailnos tem  
LEFT JOIN romeo.shipment s on s.TRACKING_NUMBER=tem.tracking_number and s.SHIPMENT_TYPE_ID=tem.shipping_id  
WHERE tem.`status`!='N'  AND (s.TRACKING_NUMBER is NULL OR s.`STATUS` ='SHIPMENT_CANCELLED')
**/
function wastedMailNos($shipping_id,$branch){
	global $slave_db;
	$sql="SELECT count(1) FROM  ecshop.thermal_express_mailnos tem  
	LEFT JOIN romeo.shipment s on s.TRACKING_NUMBER=tem.tracking_number and s.SHIPMENT_TYPE_ID=tem.shipping_id  
	WHERE tem.`status`!='N'  AND (s.TRACKING_NUMBER is NULL OR s.`STATUS` ='SHIPMENT_CANCELLED') 
	and tem.shipping_id={$shipping_id} 
	and tem.branch='{$branch}' ";
	return $slave_db->getOne($sql);
}

function wastedJdBillNos($distributor_id){
	global $slave_db;
	$sql="SELECT count(1) FROM  ecshop.jd_bill_code jbc  
	LEFT JOIN romeo.shipment s on s.TRACKING_NUMBER=jbc.tracking_number 
	WHERE jbc.`status`!='N'  AND (s.TRACKING_NUMBER is NULL OR s.`STATUS` ='SHIPMENT_CANCELLED') 
	and jbc.distributor_id={$distributor_id}  ";
	return $slave_db->getOne($sql);
}
?>
