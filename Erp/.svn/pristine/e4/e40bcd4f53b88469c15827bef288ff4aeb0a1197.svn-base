<?php
/**
RF枪病单流程

有限状态机 详见文档
文档被熊吃了

@author ljni@i9i8.com 2013/09/03
**/

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require("ajax.php");
require_once('includes/lib_sinri_DealPrint.php');
//$path = get_batch_pick_path(1);
$state=0;
if(isset($_REQUEST['state'])){
	$state=$_REQUEST['state'];
}
/*
if ($state==1){
	if(isset($_REQUEST['SSID'])){
		$BPSN=$_REQUEST['SSID'];
	}
}
*/
?>
<html>
	<head>
		<TITLE>SINRI RF SICKNESS PICKER</TITLE>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link href="styles/default.css" rel="stylesheet" type="text/css">

		<link rel="stylesheet" href="styles/rf_scan.css" />
		
		<SCRIPT TYPE="text/javascript">
			function getKey(e){ 
				e = e || window.event; 
				var keycode = e.which ? e.which : e.keyCode; 
				alert(keycode);
				if(keycode == 13 || keycode == 108){ //如果按下ENTER键 
					//在这里设置你想绑定的事件 
					//alert("ENTER");
					when_enter();
				} else if(keycode==17){
					when_ctrl();
				}
			} 

			// 把keyup事件绑定到document中 
			function listenKey() { 
				if (document.addEventListener) { 
					document.addEventListener("keyup",getKey,false); 
				} else if (document.attachEvent) { 
					document.attachEvent("onkeyup",getKey); 
				} else { 
					document.onkeyup = getKey; 
				} 
			} 
		</SCRIPT>
	</head>
	<BODY>
		<!--	ALL HAIL SINRI EDOGAWA -->
		<p>病单补拣系统</p>
		<?php
		if($state==0){
			/**
			Initial Interface Asking for SSID
			**/
			$_SESSION['SSID']=null;
			$_SESSION['location_barcode']=null;
			$_SESSION['Barcode']=null;
			$_SESSION['SN']=null;
			?>
			<DIV class="box">
				<form action="RF_sick_shipment.php" name="form1" method="post">
					<TABLE>
						<tr>
							<td colspan=2>未成功拣货的发货单</td>
						</tr>
						<tr>
							<td>发货单号</td>
							<td><input type="text" name="SSID" id="FOCUS_START_0"></td>
						</tr>
						<TR>
							<td><input type="hidden" name="state" value="1"></td>
							<td><input type="button" value="递交" onclick="document.form1.submit();"></td>
						</TR>
					</TABLE>
				</form>
			</DIV>
			<?php
		} else if ($state==1){
			/**
			Ask the Location
			**/
			if(isset($_REQUEST['SSID'])){
				$SSID=$_REQUEST['SSID'];
			} else $SSID=0;
			//echo $SSID;
			$found=getSicknessBySIDinALL($SSID);
			//pp($found);
			if(isset($found) && sizeof($found)){
				$_SESSION['SSID']=$SSID;
				?>
				<DIV class="box">
					<form action="RF_sick_shipment.php" name="form1" method="post">
						<TABLE>
							<tr>
								<td>发货单号</td>
								<td><?php echo $SSID; ?></td>
							</tr>
							<tr>
								<td>取货库位</td>
								<td><input type="text" name="location_barcode" id="FOCUS_START_0"></td>
							</tr>
							<TR>
								<td><input type="hidden" name="state" value="2"></td>
								<td><input type="button" value="递交" onclick="document.form1.submit();"></td>
							</TR>
						</TABLE>
					</form>
				</DIV>
				<div>
					<?php
					echo "<a href=\"RF_sick_shipment.php?state=4\">
						<input type=\"button\" value=\"结束此发货单\"></a><br>";
					?>
				</div>
				<?php			
			} else {
				?>
				<DIV class="box">
					<form action="RF_sick_shipment.php" name="form1" method="post">
						<TABLE>
							<tr>
								<td colspan=2>未成功拣货的发货单</td>
							</tr>
							<tr>
								<td colspan=2>该发货单不需要进行补拣处理</td>
							</tr>
							<tr>
								<td>发货单号</td>
								<td><input type="text" name="SSID" id="FOCUS_START_0"></td>
							</tr>
							<TR>
								<td><input type="hidden" name="state" value="1"></td>
								<td><input type="button" value="递交" onclick="document.form1.submit();"></td>
							</TR>
						</TABLE>
					</form>
				</DIV>
				<?php
			}
		} else if ($state==2){
			/**
			Ask for BorSN
			**/
			$result=Sinri_CheckLocationForSickness($_REQUEST['location_barcode']);
			if(isset($_REQUEST['location_barcode']) && $result){
				$location_barcode=$_REQUEST['location_barcode'];
			} else $location_barcode="NoSuchArea";
			if($result){
				$_SESSION['location_barcode']=$location_barcode;
				?>
				<DIV class="box">
					<form action="RF_sick_shipment.php" name="form1" method="post">
						<TABLE>
							<tr>
								<td>发货单号</td>
								<td><?php echo $_SESSION['SSID']; ?></td>
							</tr>
							<tr>
								<td>目标位置</td>
								<td><?php echo $_SESSION['location_barcode']; ?></td>
							</tr>
							<tr>
								<td>条码或串号</td>
								<td><input type="text" name="BorSN" id="FOCUS_START_0"></td>
							</tr>
							<TR>
								<td><input type="hidden" name="state" value="201"></td>
								<td><input type="button" value="递交" onclick="document.form1.submit();"></td>
							</TR>
						</TABLE>
					</form>
				</DIV>
				<div>
					<?php
					echo "<a href=\"RF_sick_shipment.php?state=1&SSID=".$_SESSION['SSID']."\">
						<input type=\"button\" value=\"为此发货单去下一个库位\"></a><br>";
					echo "<a href=\"RF_sick_shipment.php?state=4\">
						<input type=\"button\" value=\"结束此发货单\"></a><br>";
					?>
				</div>
				<?php
			} else {
				echo "奇怪的位置。。";
			    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=1&SSID=".$_SESSION['SSID']."\">";
			}
		} else if ($state==201){
			/**
			When Barcode Ask for Number, When SN
			**/
			if(isset($_REQUEST['BorSN'])){
				$BorSN=$_REQUEST['BorSN'];
			} else $BorSN="NoSuchActor";
			$check=check_string_type($_SESSION['party_id'],$BorSN);
			if($check['success']){
				if($check['type']=="SN"){
					if(Sinri_CheckSickGoods($_SESSION['SSID'],$check['goods_id'])){
						$_SESSION['SN']=$BorSN;
						echo "似乎是个串号。。";
				    	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=202&type=SN\">";
				    } else {
				    	echo "大泥棒inSN。。";
				    	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">";
				    }
				} else if ($check['type']=="barcode"){
					if(Sinri_CheckSickGoods($_SESSION['SSID'],$check['goods_id'])){
						$_SESSION['Barcode']=$BorSN;
						?>
						<DIV class="box">
							<form action="RF_sick_shipment.php" name="form1" method="post">
								<TABLE>
									<tr>
										<td>发货单号</td>
										<td><?php echo $_SESSION['SSID']; ?></td>
									</tr>
									<tr>
										<td>目标位置</td>
										<td><?php echo $_SESSION['location_barcode']; ?></td>
									</tr>
									<tr>
										<td>条码</td>
										<td><?php echo $_SESSION['Barcode']; ?></td>
									</tr>
									<tr>
										<td>数量</td>
										<td><input type="text" name="number" id="FOCUS_START_0"></td>
									</tr>
									<TR>
									<td>
										<input type="hidden" name="state" value="202">
										<input type="hidden" name="type" value="barcode">
									</td>
									<td><input type="button" value="递交" onclick="document.form1.submit();"></td>
								</TR>
								</TABLE>
							</form>
						</DIV>
						<div>
							<?php
							echo "<a href=\"RF_sick_shipment.php?state=1&SSID=".$_SESSION['SSID']."\">
								<input type=\"button\" value=\"为此发货单去下一个库位\"></a><br>";
							echo "<a href=\"RF_sick_shipment.php?state=4\">
								<input type=\"button\" value=\"结束此发货单\"></a><br>";
							?>
						</div>
						<?php
					} else {
				    	echo "大泥棒inBarcode。。";
				    	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">";
				    }
				} else {
					echo "奇怪的数字们。。";
			    	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">";
				}
			} else {
				echo "你打算用既不是串号又不是条码的数字来忽悠我！";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">";
			}
		} else if($state==202){
			/**
			Result and next
			**/
			if(isset($_REQUEST['type'])){
				if($_REQUEST['type']=="SN"){
					$SSID=$_SESSION['SSID'];
					$location_barcode=$_SESSION['location_barcode'];
					$SN=$_SESSION['SN'];
					/**
					OUT!
					**/

					$msg=$result;

				} else if($_REQUEST['type']=="barcode"){
					$SSID=$_SESSION['SSID'];
					$location_barcode=$_SESSION['location_barcode'];
					$Barcode=$_SESSION['Barcode'];
					$number=$_REQUEST['number'];
					/**
					OUT!
					**/
					$msg=$result;				
			?>
			<DIV class="box">
				<TABLE>
					<tr>
						<td>发货单号</td>
						<td><?php echo $_SESSION['SSID']; ?></td>
					</tr>
					<tr>
						<td>目标位置</td>
						<td><?php echo $_SESSION['location_barcode']; ?></td>
					</tr>
					<?php if($_REQUEST['type']=="barcode") { ?>
					<tr>
						<td>条码</td>
						<td><?php echo $_SESSION['Barcode']; ?></td>
					</tr>
					<tr>
						<td>数量</td>
						<td><?php echo $number; ?></td>
					</tr>
					<?php } else if($_REQUEST['type']=="SN") { ?>
					<tr>
						<td>串号</td>
						<td><?php echo $_SESSION['SN']; ?></td>
					</tr>
					<?php } ?>
				</TABLE>
				<div>
					<?php echo $msg; ?>
				</div>
			
				<div>
					<?php
					if(!tryCloseSickness($_SESSION['SSID'])){
						echo "此发货单还有货要出库!<br>";
						echo "<a href=\"RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">
								<input type=\"button\" value=\"此库位还有货要出库\"></a><br>";
						echo "<a href=\"RF_sick_shipment.php?state=1&SSID=".$_SESSION['SSID']."\">
							<input type=\"button\" value=\"为此发货单去下一个库位\"></a><br>";
						echo "<a href=\"RF_sick_shipment.php?state=4\">
							<input type=\"button\" value=\"中止此发货单\"></a><br>";
					} else {
						echo "<a href=\"RF_sick_shipment.php?state=4\">
							<input type=\"button\" value=\"全部拣完！结束此发货单\"></a><br>";
					}
					?>
				</div>
			</DIV>
			<?php
				} else {
					echo "奇怪的递交。。";
			    	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=2&location_barcode=".$_SESSION['location_barcode']."\">";
			    	//die();
				}
			} else {
				echo "这是一件极其严重的蓄谋的入侵事件";
			    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php\">";
			    //die();
			}
			?>
			<?php
		} else if($state==3){
			if(isset($_SESSION['SSID'])){
				$ssid=$_SESSION['SSID'];
				$ss_info=Sinri_GetInfoForSickness($ssid);
			}
			?>
			<DIV class="box">
				发货单：<?php echo $ssid; ?>
				<TABLE>
					<?php
						if(isset($ss_info) && sizeof($ss_info)){
							foreach ($ss_info as $key => $value) {
								echo "<tr><td>名称</td><td>".$value['goods_name']."</td></tr>";
								echo "<tr><td>条码</td><td>".$value['goods_barcode']."</td></tr>";
								echo "<tr><td>缺数</td><td>".$value['lack_number']."</td></tr>";
							}
						}
					?>
				</TABLE>
			</DIV>
			<?php
		} else if($state==4){
			$result=terminal_sick_shipment($_SESSION['SSID']);
			if($result){
				echo "病单".$_SESSION['SSID']."成功终结";
			    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php\">";
			} else{
				echo "病单".$_SESSION['SSID']."终结失败";
			    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=RF_sick_shipment.php?state=1&SSID=".$_SESSION['SSID']."\">";
			}
		}
		if(isset($_SESSION['SSID']) && $state!=3){
		?>
		<p>
			<a href="RF_sick_shipment.php?state=3" target="_blank">当前发货单缺货详情</a>
		</p>
		<?php } ?>
		<SCRIPT TYPE="text/javascript">
			var tar=document.getElementById('FOCUS_START_0');
			tar.focus();
		</SCRIPT>
	</BODY>
</html>