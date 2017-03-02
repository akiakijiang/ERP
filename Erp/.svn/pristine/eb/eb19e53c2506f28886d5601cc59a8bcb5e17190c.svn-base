<?php
define('IN_ECS', true);
require_once ('includes/init.php');
require (ROOT_PATH . 'admin/function.php');

$admin_list=array('wjzhu','ytchen','ljni','jche','wlxu1','ybliu','sjgu','hhuang','mjzhou','rjsu','wbzhang','hluo1','hyzhang1','llli');

//pp($_SESSION);

if(!in_array($_SESSION['admin_name'],$admin_list)){
	die("CALL HE JIANCHENG. リア充爆発しろ。");
}

$act=$_REQUEST['act'];
$party=$_REQUEST['party_id'];
$facility=$_REQUEST['facility_id'];
$shipping=$_REQUEST['shipping_id'];

if(isset($act) && isset($facility) && isset($shipping)){
	$msg="收到请求 仓库[".$facility."]利用运送方式[".$shipping."]时的打印计划为[".$act."]。";
	if($act=="BeginArata" || $act=="StopArata"){
		if($facility=='ALLHAILSINRIEDOGAWA' || $shipping=='ALLHAILSINRIEDOGAWA'){
			$msg.="<br>仓库、运送方式，这三项必须准确选择。";
		}else{
			$result=batchHandleChanges($party,$facility,$shipping,$act);
			$msg.="<br>运行结果如下：<br>".$result;
		}
	}else{
		$msg.="<br>打印计划变更请求无效，请不要妄图利用HTML调试器来黑我服务器。";
	}
}

?>



<?php
global $db;

$PFS_List=getPartyFacilityShippingForArata();

$parties=getParties();
$facilities=getFacilities();
$shippings=getShippings();

?>
<html>
<head>
	<title>热敏/传统面单打印设定</title>
	<style type="text/css">
		table, td {
			border: 1px solid gray;
			border-collapse:collapse;
			font-size: 13px;
			text-align: center;
			padding: 5px;
		}
		th {
			background-color: #2899D6;/* #6CB8FF; */
			color: #EEEEEE;
			border: 1px solid gray;
			border-collapse:collapse;
			padding: 2px;
			text-align: center;
			padding: 5px;
		}
	</style>
</head>
<body>
	<h1>热敏/传统面单打印设定</h1>
	<hr>
	<h2>更改设定</h2>
	<div>
		<form method="post">
			<p>
			<!--
				组织：
				<select name="party_id">
					<option value="ALLHAILSINRIEDOGAWA" selected="selected">未选择</option>
					<optgroup label="全部">
						<option value="all">全部组织</option>
					</optgroup>
					<optgroup label="字母排序">
					<?php
					foreach ($parties as $key => $value) {
						echo "<option value='{$value['party_id']}'>{$value['name']}</option>";
					}
					?>
					</optgroup>
				</select>
				|
			-->
				仓库：
				<select name="facility_id">
					<option value="ALLHAILSINRIEDOGAWA" selected="selected">未选择</option>
					<optgroup label="物理仓">
						<option value="shanghai">上海</option>
						<option value="jiaxing">嘉兴</option>
						<option value="chengdu">成都</option>
						<option value="dongguan">东莞</option>
						<option value="beijing">北京</option>
						<option value="wuhan">武汉</option>
					</optgroup>
					<optgroup label="逻辑仓">
					<?php
					foreach ($facilities as $key => $value) {
						echo "<option value='{$value['facility_id']}'>{$value['facility_name']}</option>";
					}
					?>
					</optgroup>
				</select>
				|
				运送：
				<select name="shipping_id">
					<option value="ALLHAILSINRIEDOGAWA" selected="selected">未选择</option>
					<?php
					foreach ($shippings as $key => $value) {
						echo "<option value='{$value['shipping_id']}'>{$value['shipping_name']}</option>";
					}
					?>
				</select>
				|
				<select name="act">
					<option value="BeginArata">开始利用热敏打印面单</option>
					<option value="StopArata">恢复使用传统打印面单</option>
				</select>
				<input type="submit">
			</p>
			<?php
			if($msg && $msg!=""){
				echo "<p>".$msg."</p>";
			}
			?>
		</form>
	</div>
	<hr>
	<div>
		<table>
			<tr>
				<td colspan='4'>
					系统中已经设置过的组合
				</td>
			</tr>
			<tr>
			<!--
				<th>业务组织[P]</th>
			-->
				<th>仓库[F]</th>
				<th>运送方式[S]</th>
				<th>传统/热敏</th>
			</tr>
			<?php
			foreach ($PFS_List as $line_no => $PFS_line) {
				echo "<tr>";
				//echo "<td>".$PFS_line['PARTY_NAME']."</td>";
				echo "<td>".$PFS_line['FACILITY_NAME']."</td>";
				echo "<td>".$PFS_line['shipping_name']."</td>";
				echo "<td>".($PFS_line['isNotUseArataCarrierBill']?'传统':'热敏')."</td>";
				echo "</tr>";
			}
			?>
		</table>
	</div>
	
</body>
</html>
<?php
function getPartyFacilityShippingForArata(){
	global $db;
	$sql="SELECT 
            -- pfs.party_id, 
            -- p.`NAME` PARTY_NAME, 
            -- pfs.facility_id, 
            f.FACILITY_NAME, 
            -- pfs.shipping_id, 
            es.shipping_name, 
            pfs.is_delete AS isNotUseArataCarrierBill 
        FROM 
            romeo.facility_shipping pfs 
        -- LEFT JOIN romeo.party p ON pfs.party_id = p.PARTY_ID 
        LEFT JOIN romeo.facility f ON pfs.facility_id = f.FACILITY_ID 
        LEFT JOIN ecshop.ecs_shipping es ON pfs.shipping_id = es.shipping_id";
    $result=$db->getAll($sql);
 	return $result;
}
function setPartyFacilityShippingArataOnOff($party_id='',$facility_id,$shipping_id,$isToOn){
	global $db;
	$sql_search="SELECT count(1) 
		FROM romeo.facility_shipping 
		WHERE facility_id='{$facility_id}' 
			and shipping_id={$shipping_id}";
	$count_existed=$db->getOne($sql_search);
	if($count_existed>0){
		//UPDATE
		$newValue=($isToOn?'0':'1');
		$sql_update="UPDATE romeo.facility_shipping 
			SET is_delete = {$newValue} 
			WHERE  facility_id='{$facility_id}' 
				and shipping_id={$shipping_id}";
		//pp($sql_update);
		return $db->query($sql_update);
	}else{
		if($isToOn){
			//INSERT
			$sql_insert="INSERT INTO romeo.`facility_shipping` 
				VALUES ('{$facility_id}',{$shipping_id},'system',now(),now(),0)";
			//pp($sql_insert);
			return $db->query($sql_insert);
		}else{
			//Keep it empty in DB
			//pp('no sql');
			return true;
		}
	}
}
function getParties(){
	global $db;
	$sql="SELECT party_id,name FROM romeo.party WHERE SYSTEM_MODE=2 order by convert(name USING gbk) COLLATE gbk_chinese_ci";
	$r=$db->getAll($sql);
	return $r;
}
function getFacilities(){
	global $db;
	$sql="SELECT facility_id,facility_name FROM romeo.facility WHERE IS_CLOSED='N' order by convert(facility_name USING gbk) COLLATE gbk_chinese_ci";
	$r=$db->getAll($sql);
	return $r;
}
function getShippings(){
	global $db;
	$sql="SELECT shipping_id,shipping_name FROM ecshop.ecs_shipping order by convert(shipping_name USING gbk) COLLATE gbk_chinese_ci";
	$r=$db->getAll($sql);
	return $r;
}

function batchHandleChanges($party,$facility,$shipping,$act){
	$lines=array();
	$party_id='ALL';
	
		if($facility=='shanghai'){
			// 电商服务上海仓(19568549)
			// 电商服务上海仓_2（原电商服务杭州仓）(22143847)
			// 乐其上海仓_2（原乐其杭州仓）(22143846)
			// 康贝分销上海仓(81569822)
			// 通用商品上海仓(83077348)
			// 贝亲青浦仓(24196974)
			$lines['19568549']=array();
			$lines['22143847']=array();
			$lines['22143846']=array();
			$lines['81569822']=array();
			$lines['83077348']=array();
			$lines['24196974']=array();
			$lines['119603093']=array();//康贝外包仓
			$lines['119603091']=array();//双11上海外包仓
			$lines['149849259']=array();//惠氏分销上海仓
		}else if ($facility=='dongguan'){
			// 电商服务东莞仓(19568548)
			// 乐其东莞仓(3580047)
			// 东莞乐贝仓(49858449)
			// 电商服务东莞仓2(76065524)
			// 通用商品东莞仓(83077349)
			$lines['19568548']=array();
			$lines['3580047']=array();
			$lines['49858449']=array();
			$lines['76065524']=array();
			$lines['83077349']=array();
		}else if ($facility=='jiaxing'){
			//哥伦布嘉兴仓
			$lines['149849256']=array();
		}else if ($facility=='chengdu'){
			//电商服务成都仓
			$lines['137059428']=array();
		}else if ($facility=='wuhan'){
			//电商服务武汉仓
			$lines['137059427']=array();
		}else if($facility=='beijing'){
			// 乐其北京仓(42741887)
			// 电商服务北京仓(79256821)
			// 通用商品北京仓(83077350)
			$lines['42741887']=array();
			$lines['79256821']=array();
			$lines['83077350']=array();
			$lines['119603092']=array();//北京中通外包仓
		}else if(isset($facility) && $facility!='ALLHAILSINRIEDOGAWA'){
			$lines[$facility]=array();
		}else{
			return "仓库参数被拒绝。";
		}
	

	$msg="";
		foreach ($lines as $facility_id => $pf_lines) {
			if(isset($shipping) && $shipping!='ALLHAILSINRIEDOGAWA'){
				if($act=='BeginArata'){
					$r=setPartyFacilityShippingArataOnOff($party_id,$facility_id,$shipping,true);
					if(!$r){
						$msg.="仓库 $facility_id 对运送方式 $shipping 切换成热敏失败<br>";
					}
				}else if($act=='StopArata'){
					$r=setPartyFacilityShippingArataOnOff($party_id,$facility_id,$shipping,false);
					if(!$r){
						$msg.="仓库 $facility_id 对运送方式 $shipping 切换成传统失败<br>";
					}
				}else{
					$msg.="仓库 $facility_id 对运送方式 $shipping 切换任务非法被拒绝。<br>";
				}
			}else{
				$msg.= "仓库 $facility_id 因运送方式 $shipping 非法被拒绝。<br>";
			}
		}
	
	return $msg."执行完毕";
}
?>