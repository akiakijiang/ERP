<?php
/**
All Hail Sinri Edogawa!
此页为乐其仓库改造部队的邪恶之大鲵奉聪颖幕府之命所建。
用于根据BPSN打印面单。

@AUTHOR ljni@i9i8.com
@UPDATED 20130814

@PARAM 
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
	$BPSN=$_REQUEST['BPSN'];
} else $BPSN = null;

if(isset($_REQUEST['act'])){
	if ($_REQUEST['act']=="query"){
		
	} else if ($_REQUEST['act']=="batch_print"){
		$TNS=array();
		$SID=array();
		$OID=array();
		if(isset($_REQUEST['TNS']) && $_REQUEST['TNS']!='0'){
			$TNS=$_REQUEST['TNS'];
		} 
		if(isset($_REQUEST['SID']) && $_REQUEST['SID']!='0'){
			$SID=$_REQUEST['SID'];
		} 
		if(isset($_REQUEST['OID']) && $_REQUEST['OID']!='0'){
			$OID=$_REQUEST['OID'];
		} 
		$update_done=update_shipment_tracking_number($SID,$TNS);
	} else if ($_REQUEST['act']=="print"){
		$TNS=array();
		$SID=array();
		$OID=array();
		if(isset($_REQUEST['SSID']) && $_REQUEST['SSID']!='0'){
			$SID=array($_REQUEST['SSID']);
		} 
		if(isset($_REQUEST['SOID']) && $_REQUEST['SOID']!='0'){
			$OID=array($_REQUEST['SOID']);
		} 
		if(isset($_REQUEST['STN']) && $_REQUEST['STN']!='0'){
			$TNS=array($_REQUEST['STN']);
		} 
		$update_done=update_shipment_tracking_number($SID,$TNS);
	}
}




?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<TITLE>SINRI BATCH Carrier Bills DEALING PAGE</TITLE>
		<script type="text/javascript" src="js/js_wms/sinri_print_iframe.js"></script>
		<script type="text/javascript" src="js/js_wms/tracking_number_add_rule.js"></script>
		<script type="text/javascript" src="js/js_wms/tracking_number_check.js"></script>
		<script type="text/javascript" src="misc/jquery.js"></script>
		
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
				-- text-align:center;
			}
			
		</style>
		<script type="text/javascript">
		
			function BTN_SINCE(start,endbefore){
				var this_TN=document.getElementById("TN_"+start).value;
				var origin_TN = this_TN;
				
		        // 判断末尾字母
				var ato_index=this_TN.search(/[A-z]+$/);
				if(ato_index<0) {
					var ato='';
					//this_TN=this_TN.substr(0,ato_index);
				}
				else {
					var ato=this_TN.substr(ato_index,this_TN.length-ato_index);
					this_TN=this_TN.substr(0,ato_index);
				}
				
				// 开头数字
				var mae=this_TN.substr(0,this_TN.length-9);
				// 中间的9个数字，用于处理批量变化
				var usiro=this_TN.substr(-9,9);
				for(var i=start+1;i<endbefore;i++){
					var box=document.getElementById("TN_"+i);
					if(box){
					    var tail = auto_add_tracking_number(usiro,i-start);//由于快递规则不一样，递增时需要特殊判断
						box.value=mae+tail+ato;
					}
				}
			}
			//批量打印之前(根据聪颖的提议只单独对第一个运单号检查合法性)
	        function batch_check_trackingnumber(){
	            $('#button').attr("disabled","true");
	            var check = true;
	            var carrier_id = $("#carrierId").val();
		        var trackingNumber=$("#TN_0").val();
				//检查运单号规则
		        if(!check_tracking_number(carrier_id,trackingNumber)) {
		            check = false;
		            $("#button").removeAttr("disabled");
		        }
		        if(check){
		        	document.getElementById('mainact').value='batch_print';
				    document.mainform.submit();
				    $("#button").removeAttr("disabled");
		        }
	        }
	        //单个打印之前检查运单号合法性
	        function check_trackingnumber(NO,sid,oid){
	            var carrier_id = $("#carrierId").val();
	            var TN_NO = 'TN_'+NO;
	            var check = true;
	            var trackingNumber=$("#TN_"+NO).val();
	            //检查运单号规则
		        if(!check_tracking_number(carrier_id,trackingNumber)) {
		            check = false;
		        }
	            if(check){
		        	document.getElementById(TN_NO).blur();
					document.getElementById('mainact').value='print';
					document.getElementById('sel_ssid').value= sid;
					document.getElementById('sel_soid').value= oid;
					document.getElementById('sel_tn').value=document.getElementById(TN_NO).value;
					document.mainform.submit();
		        }
	        }
		</script>
	</head>
	<body>
		<DIV>
			<h1>批量拣货单对应的快递单打印</h1>
			<p>
				本页面已经支持20单以上的打印
			</p>
			<p>
				<form action="Deal_CarrierBill_PrintV2.php" method="POST">
					<input type="hidden" name="act" value="query">
					批拣单号：<input type="text" name="BPSN" value="<?php echo $BPSN; ?>">
					<input type="submit" value="查询"> 
				</form>
			</p>
			<hr>
			<DIV>
				<?php
					if(isset($BPSN)){
						$shipments=getShipmentsfromBPSN($BPSN);
				?>	<form name="mainform" action="Deal_CarrierBill_PrintV2.php" method="POST">
						<input type="hidden" id="sel_ssid" name="SSID"> 
						<input type="hidden" id="sel_soid" name="SOID"> 
						<input type="hidden" id="sel_tn" name="STN"> 
						<table>
							<tr>
								<th>序号</th>
								<th>发货单号</th>
								<th>主订单号</th>
								<th>快递名称</th>
								<th>快递面单号录入和更新</th>
								<th>操作</th>
							</tr>
				<?php 
						foreach ($shipments as $no => $shipment) {
				?>
							<tr>
								<td><input type="hidden" id="carrierId" value="<?php echo $shipments[$no]['carrier_id'] ?>">
									<?php echo ($no+1); ?></td>
				<?php
								foreach ($shipment as $key => $value) {
									if($key=='shipment_id') {
										echo "<td>$value<input type='hidden' name='SID[]' value=\"$value\"></td>";
										$sid=$value;
									}
									else if ($key=='main_order_id'){
										echo "<td>$value<input type='checked' name='OID[]' value=\"$value\"></td>";
										$oid=$value;
									}
									else if($key=='carrier_name') { 
										echo "<td>$value</td>";
									}
									else if($key=='shipping_id') { 
										echo "<input type='hidden' id='shipping_id_{$no}' name='shipping_ID[]' value=\"$value\">";
									}
									else if($key=='tracking_number') {
				?>
										<td>
											<input type="text"
												id="TN_<?php echo $no; ?>"
												name="TNS[]"
												value="<?php echo $value; ?>"
											>
											<input type="button" id="BTN_<?php echo $no;?>" value="以此开始向下批量" onclick="BTN_SINCE(<?php echo $no; ?>,<?php echo sizeof($shipments); ?>);">
										</td>
				<?php
									}
								}
				?>
								<td>
									<input type="button" value="更新快递单记录并重新打印快递单" onclick="
										check_trackingnumber(<?php echo $no; ?>,'<?php echo $sid; ?>','<?php echo $oid; ?>');
									">
								</td>
							</tr>
				<?php
						}
				?>
							<tr>
				<?php
								if(sizeof($shipments)){
				?>
									<td colspan=6>
										<input type="hidden" name="BPSN" value="<?php echo $BPSN; ?>">
										<input type="hidden" id="mainact" name="act" value="batch_print">
										Let's go to print them all! <input type="button" id="button"  value="批量打印" onclick="
											 batch_check_trackingnumber();
										">
									</td>
				<?php
							} else {
				?>
									<td colspan='6'>没有能打的面单</td>
				<?php
							}
				?>
							</tr>
						</table>
					</form>
				<?php
					}
				?>
			</DIV>
		</DIV>
		<DIV style="display:none;">
			DISPALY FOR TESTING:<?php echo $_REQUEST['act']." STN=".$_REQUEST['STN']; ?><br>
			<?php print_r($_REQUEST); ?><br>
			TNS<br>
			<?php echo "TNS=$TNS<br>"; print_r($TNS); ?>
			<br>
			<?php echo "one_tn=$one_tn<br>"; print_r($one_tn); ?>
			<br>
			SID<br>
			<?php print_r($SID); ?>
			<br>
			OID<br>
			<?php print_r($OID); ?>
			<br>
			update_done=
			<?php print_r($update_done); ?>
			<br>
			<?php echo "$one_sid $one_oid $one_tn"; ?>
			<?php
				for ($i=0; $i < sizeof($SID); $i++) { 
					echo "[".$SID[$i].":".$OID[$i]."=>".$TNS[$i]."]<br>";
				}
			?>
			<form name="KyrieEleison" id="KyrieEleison" action="print_shipping_orders.php" method='POST' target="print_frame">
				<input type="hidden" name="print" value="1">
				<textarea name='order_id'><?php 
					echo implode(',', $OID);
				?></textarea>
				<input type="submit">
			</form>
		</DIV>
		<?php
			if(isset($update_done) && $update_done) {
		?>
			<script type="text/javascript">
			document.KyrieEleison.submit();
			</script>
			<iframe name="print_frame" id="print_frame" width="100%" height="100%" frameborder="1" src="<?php 
				//echo "print_shipping_orders.php?print=1&order_id=".join(',',$OID);
			?>" ></iframe>
		<?php 
			}
		?>
	</body>
</html>