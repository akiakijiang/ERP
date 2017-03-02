<?php
/**
All Hail Sinri Edogawa!
此页为乐其仓库改造部队的邪恶之大鲵奉聪颖幕府之命所建。
用于根据BPSN打印发货单

@AUTHOR ljni@i9i8.com
@UPDATED 20130814

@PARAM 
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!=0){
	$BPSN=$_REQUEST['BPSN'];
} else {
	$BPSN=null;
}
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<TITLE>SINRI BATCH Shipment DEALING PAGE</TITLE>
		<script type="text/javascript" src="js/js_wms/sinri_print_iframe.js"></script>
		<style type="text/css" media="all">
			h2{
				font-size:12pt;
				clear:both;
				margin:10px 0;
			}
			h3{
				font-size:10pt;
				clear:both;
				margin:8px 0;
			}
			ul{
				list-style:none;
				font-size:10pt;
				width:49%;
				float:left;
			}
			ul li{
				height:40px;
				line-height:40px;
			}
			
			table{
				font-size:10pt;
				text-align:center;
				border:1px solid #000;
				border-collapse:collapse;
				margin:20px 0 20px 2px;
			}
			table td{
			    padding:5px 10px;
			}
			table td,table th{
				border:1px solid #000;
				height:25px;
				line-height:25px;
			}
			p{
				font-size:10pt;
				clear:both;
				text-align:center;
			}
			
		</style>
	</head>
	<body>
		<DIV>
			<h1>查询批拣发货单列表</h1>
			<h2>本页面已经支持20单以上的打印</h2>
			<form action="Deal_Shipment_PrintV2.php" method="post">
				请输入批拣单号（Batch Pick Serial Number）： <input type="text" name='BPSN' value='<?php echo $BPSN; ?>'>
				<input type="submit">
			</form>
		</DIV>
		<hr style="margin:20px;">
		<?php 
			if(isset($BPSN)) { 
				//Seek shipment_id[] from BPSN
				$shipment_ids=getShipmentIDsfromBPSN($BPSN);
				if(sizeof($shipment_ids)){
		?>
		<DIV>
			<h2>该批拣单号（Batch Pick Serial Number）【<?php echo $BPSN; ?>】 发货单列表</h2>
			<table style="border">
				<tr>
					<td>发货单号</td>
					<td>操作</td>
				</tr>
				<?php 
						foreach ($shipment_ids as $key => $value) {
				?>
				<tr>
				<?php
							echo "<td>".$value."</td>";
				?>
				<td>
					<a href="javascript:void(0);" onclick="print_dispatch(<?php echo $value; ?>);">打印此发货单</a>
				</td>
				</tr>
				<?php
						}
				?>
			</table>
		</DIV>
		<hr style="margin:50px;">
		<div style="text-align:center;">
			<h1> = = = 打印预览 = = = </h1>
		</div>
		<div style="display: none;">
			<form name="KyrieEleison" id="KyrieEleison" action="shipment_print_for_batch_pick_new.php" method='POST' target="print_frame">
				<input type="hidden" name="print" value="1">
				<textarea name='shipment_id'><?php 
					echo implode(',', $shipment_ids);
				?></textarea>
				<input type="submit">
			</form>
		</div>
		<iframe name="print_frame" width="100%" height="100%" frameborder="1" src="<?php
			//echo "shipment_print_for_batch_pick.php?print=1&shipment_id=".join(',',$shipment_ids);
		?>" ></iframe>
		<script type="text/javascript">
			document.KyrieEleison.submit();
		</script>
		<?php 
				} else {
					echo "<DIV>";
					echo "<h1>残念！不存在的批拣单号或未有发货单绑定记录！</h1>";
					//echo "<p>这什么批拣单号，不要用伪造的来耍人，ERP系统虽然傻但是程序员不傻。</p>";
					echo "</DIV>";
				}
			}
		?>
	</body>
</html>