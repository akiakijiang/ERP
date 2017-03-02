<?php
/**
RF枪收货~ MonoHaire
奉聪颖幕府命御制
@author ljni@i9i8.com 2013/08/28
**/

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require("ajax.php");
require_once('includes/lib_sinri_DealPrint.php');
$state=0;
if(isset($_REQUEST['state'])){
	$state=$_REQUEST['state'];
}
if ($state==1){
	if(isset($_REQUEST['MHSN'])){
		$MHSN=$_REQUEST['MHSN'];
		$_SESSION['MHSN']=$MHSN;
	}
} else if ($state==200){
	if(isset($_REQUEST['HAKOID'])){
		$HAKOID=$_REQUEST['HAKOID'];
		$_SESSION['HAKOID']=$HAKOID;
	}
} else if ($state==201){
	if(isset($_REQUEST['goods_barcode'])){
		$goods_barcode=$_REQUEST['goods_barcode'];
		$_SESSION['goods_barcode']=$goods_barcode;
	}
} 

?>
<html>
	<head>
		<TITLE>SINRI RF MONO HAIRE</TITLE>
		<link href="styles/default.css" rel="stylesheet" type="text/css">
		<STYLE TYPE="text/css">
		.Input90 {
			width: 120px;
		}
		div.box {
			max-width:190px;
			background-color: lightgreen;
		}
		td{
			font-size: 12px;
		}
		</STYLE>
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
	<body>
		<DIV>收货入库基本版</DIV>
		<?php
		if($state==0){
			$_REQUEST['MHSN']=null;
		?>
		<DIV class="box">
			<form action="MonoHaire.php" method="post">
				<input type="hidden" name="state" value="1">
				要收货的单号MHSN:<br>
				<input type="text" name="MHSN">
				<br>
				<input type="submit">
			</form>
		</DIV>
		<?php
		} else if($state==1){
			//Here Check MHSN! default as true
		?>
		<DIV class="box">
			<form action="MonoHaire.php" method="post">
				<input type="hidden" name="state" value="200">
				单号MHSN:<span style="color:red;"><?php echo $_SESSION['MHSN']; ?></span><br>
				收货容器HakoID:<br>
				<input type="text" name="HAKOID">
				<br>
				<input type="submit">
			</form>
		</DIV>
		<?php
		} else if($state==200){
		?>
		<DIV class="box">
			<form action="MonoHaire.php" method="post">
				<input type="hidden" name="state" value="201">
				单号MHSN:<span style="color:red;"><?php echo $_SESSION['MHSN']; ?></span><br>
				收货容器HAKOID:<span style="color:red;"><?php echo $_SESSION['HAKOID']; ?></span><br>
				商品条码:<br>
				<input type="text" name="goods_barcode">
				<br>
				<input type="submit">
			</form>
		</DIV>
		<?php
		} else if($state==201){
			$res=MonoHaire_getGoodsInfo($_SESSION['MHSN'],$_SESSION['goods_barcode']);
			if(!$res['success']){
				echo "ERROR! ".$res['error'];
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoHaire.php?state=200\">";
			}else{
				$isMW=MonoHaire_isNeedWarranty($_SESSION['MHSN'],$_SESSION['goods_barcode']);
		?>
		<DIV class="box">
			<form action="MonoHaire.php" name="Form201" method="post">
				<input type="hidden" name="state" id="stateIn201" value="202">
				单号MHSN:<span style="color:red;"><?php echo $_SESSION['MHSN']; ?></span><br>
				收货容器HAKOID:<span style="color:red;"><?php echo $_SESSION['HAKOID']; ?></span><br>
				所收货 <span style="color:red;"><?php echo $_SESSION['goods_barcode']; ?></span><br>
				这货：<br>
				名称: <span style="color:red;">
					<?php echo $res['goods_name']." [ID:".$res['goods_id']."]"; ?><br>
				</span>
				<br>
				<?php if($res['is_serial']) { ?>
					SN:<br>
					<input type="text" name="sn">
					<br>
				<?php } else {
				?>
					数量:
					<input type="text" name="number">
					<br>
				<?php	
				} ?>
				<?php if($isMW){ ?>
					生产日期:<br>
					<input type="text" name="va">
					<br>
				<?php } ?>
				<center>
					<hr>
					<?php 
					if($res['is_serial']) {
					?>
						<input type="button" value="入库并进入下一个SN" onclick="document.getElementById('stateIn201').value='205'; document.Form201.submit();">
						<hr>
					<?php
					}
					?>
					<input type="button" value="入库并进入下一个条码" onclick="document.getElementById('stateIn201').value='202'; document.Form201.submit();">
					<hr>
					<input type="button" value="入库并进入下一个收货容器" onclick="document.getElementById('stateIn201').value='203'; document.Form201.submit();">
					<hr>
					<input type="button" value="入库并进入下一个单" onclick="document.getElementById('stateIn201').value='204'; document.Form201.submit();">
				</center>
			</form>
		</DIV>
		<?php
			} 
		}else if($state>=202 && $state<=205){
			$MHSN=$_SESSION['MHSN'];
			$location_barcode=$_SESSION['HAKOID'];
			$goods_barcode=$_SESSION['goods_barcode'];
			$goods_number=$_REQUEST['number'];
			$serial_number=$_REQUEST['sn'];
			$validity=$_REQUEST['va'];
			$validity_type='start_validity';
			$res=MonoHaire_getGoodsInfo($_SESSION['MHSN'],$_SESSION['goods_barcode']);
			if($res['success']){
				$isSN=$res['is_serial'];
				$isMW=MonoHaire_isNeedWarranty($_SESSION['MHSN'],$_SESSION['goods_barcode']);
				if($isSN && $isMW){
					$done=MonoHaire_Hairu_SN_MW($MHSN,$location_barcode,$goods_barcode,$serial_number,$validity,$validity_type);
				} else if($isSN && !$isMW){
					$done=MonoHaire_Hairu_SN($MHSN,$location_barcode,$goods_barcode,$serial_number);
				} else if(!$isSN && $isMW){
					$done=MonoHaire_Hairu_NSN_MW($MHSN,$location_barcode,$goods_barcode,$goods_number,$validity,$validity_type);
				} else if(!$isSN && !$isMW){
					$done=MonoHaire_Hairu_NSN($MHSN,$location_barcode,$goods_barcode,$goods_number);
				} 
				if (!$done['success']){
					echo "ERROR ".$done['error']." Go back to check...";
					die();
				}
			}
			if ($state==202){				
				echo "Back to goods_barcode";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoHaire.php?state=200\">";
			} else if ($state==203){
				echo "Back to HAKOID";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoHaire.php?state=1\">";
			} else if ($state==204){
				echo "Back to MHSN";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoHaire.php?state=0\">";
			} else if ($state==205){
				echo "Back to MHSN";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoHaire.php?state=201\">";
			}
		}
		?>

	</body>
</html>