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


//$path = get_batch_pick_path(1);
$state=0;
if(isset($_REQUEST['state'])){
	$state=$_REQUEST['state'];
}
if ($state==1){
	if(isset($_REQUEST['HAKOID'])){
		$HAKOID=$_REQUEST['HAKOID'];
		$_SESSION['HAKOID']=$HAKOID;
	}
} else if ($state==200){
	if(isset($_REQUEST['BorSN'])){
		$BorSN=$_REQUEST['BorSN'];
		$_SESSION['BorSN']=$BorSN;
	}
	if(isset($_REQUEST['number'])){
		$number=$_REQUEST['number'];
		$_SESSION['number']=$number;
	}
} 

?>
<html>
	<head>
		<TITLE>SINRI RF MONO OSUWARI</TITLE>
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
		<DIV>收货上架基本版</DIV>
		<?php
		if($state==0){
		?>
		<DIV class="box">
			<form action="MonoOsuwari.php" method="post">
				<input type="hidden" name="state" value="1">
				上架容器HakoID:<br>
				<input type="text" name="HAKOID">
				<br>
				<input type="submit">
			</form>
		</DIV>
		<?php
		} else if ($state==1){
		?>
		<DIV class="box">
			<form action="MonoOsuwari.php" method="post">
				<input type="hidden" name="state" value="200">
				上架容器HAKOID:<span style="color:red;"><?php echo $_SESSION['HAKOID']; ?></span><br>
				货物条码或者串号:<br>
				<input type="text" name="BorSN"><br>
				数量:<br>
				<input type="text" name="number">
				<br>
				<input type="submit">
			</form>
		</DIV>
		<?php
		} else if ($state==200){
			//CHECK IS SERIAL
   	    	$result = check_string_type($_SESSION['party_id'], $_SESSION['BorSN']);
	        if($result['success']) {
	        	if($result['type'] == 'SN') {
		       	  	$res_barcode = get_goods_barcode($_SESSION['BorSN']);
		       	  	$_SESSION['goods_barcode'] = $res_barcode['goods_barcode'];
		       	  	$_SESSION['serial_number']=$_SESSION['BorSN'];
		       		$_SESSION['IS_SERIAL']=true;
		       		echo "SERIAL";
					echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=201\">";
	        	}
	        	else if($result['type'] == 'barcode')
	        	{
		        	$_SESSION['goods_barcode']=$_SESSION['BorSN'];
		        	$_SESSION['IS_SERIAL']=false;
	    	    	echo "NON-SERIAL";
					echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=202\">";
	        	}
	        	else
	        	{
		        	pp('不可能...');
		        	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=1\">";
	        	}
	        } else {
	        	pp('字符串输入有误，请check');
	        	、、pp($result['info']);
	        	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=1\">";
	        }
			?>
			<?php
		} else if ($state==201){//SERIAL
			$res=get_grouding_not_in_number($_SESSION['HAKOID'],$_SESSION['goods_barcode']);
			if($res['success']){
				$not_in_msg="未上架".$res['not_in_number'];
			} else {
				$not_in_msg=$res['error'];
			}
			$res_place=recommend_grouding_location($_SESSION['HAKOID'],$_SESSION['goods_barcode'],'INV_STTS_AVAILABLE');
			if($res_place['success']){
				$to_place=$res_place['location_barcode'];
			} else {
				$to_place=$res_place['error'];
			}
		?>
		<DIV class="box">
			<form action="MonoOsuwari.php" name="Form2" method="post">
				类型检查: SERIAL<br>
				<?php echo $not_in_msg; ?>
				<br>
				<input type="hidden" name="state" id="state201" value="301">
				位置确认:<br>
				推荐库位 [<?php echo $to_place; ?>]<br>
				实际库位：<input type="text" name="TOKORO">
				<hr>
				串号SN:<br>
				<?php
				for ($i=0; $i < $_SESSION['number']; $i++) { 
					?>
					<input type="text" name="SNs[]"><br>
					<?php
				}
				?>
				<hr>
				<center>
					<input type="button" value="递交并找下一个" onclick="document.getElementById('state201').value=301;document.Form2.submit();">
					<hr>
					<input type="button" value="Mono Owari" onclick="document.getElementById('state201').value=302;document.Form2.submit();">
					<hr>
					<input type="button" value="HAKO Owari" onclick="document.getElementById('state201').value=303;document.Form2.submit();">
				</center>
			</form>
		</DIV>
		<?php
		} else if ($state==202){//NON-SERIAL
			$res=get_grouding_not_in_number($_SESSION['HAKOID'],$_SESSION['goods_barcode']);
			if($res['success']){
				$not_in_msg="未上架".$res['not_in_number'];
			} else {
				$not_in_msg=$res['error'];
			}
			$res_place=recommend_grouding_location($_SESSION['HAKOID'],$_SESSION['goods_barcode'],'INV_STTS_AVAILABLE');
			if($res_place['success']){
				$to_place=$res_place['location_barcode'];
			} else {
				$to_place=$res_place['error'];
			}
		?>
		<DIV class="box">
			<form action="MonoOsuwari.php" name="Form2" method="post">
				Check Info: NON-SERIAL<br>
				<?php echo $not_in_msg; ?>
				<br>
				<input type="hidden" name="state" id="state201" value="301">
				Confirm destination:<br>
				Should be [<?php echo $to_place; ?>]<br>
				<input type="text" name="TOKORO">
				<hr>
				Number Up:<br>
				<?php
				echo "<input type=\"text\" name=\"number\" value=\"".$_SESSION['number']."\">";
				?>
				<hr>
				<center>
				<!--
					<input type="button" value="Tokoro Owari" onclick="document.getElementById('state201').value=301;document.Form2.submit();">
					<hr>
				-->
					<input type="button" value="递交并结束此物品" onclick="document.getElementById('state201').value=302;document.Form2.submit();">
					<hr>
					<input type="button" value="递交结束这一个容器" onclick="document.getElementById('state201').value=303;document.Form2.submit();">
				</center>
			</form>
		</DIV>
		<?php	
		} else if($state>=301 && $state<=303){
			//common_location_transaction($from_location_barcode,$to_location_barcode,$goods_barcode,$serial_number,$goods_number)
			$done=0;
			$msg="";
			if($_SESSION['IS_SERIAL']){
				$sns=$_REQUEST['SNs'];
				//pp($_SESSION);
				//pp($_REQUEST);
				foreach ($sns as $key => $value) {
					//echo "SN=common_location_transaction(".$_SESSION['HAKOID'].",".$_REQUEST['TOKORO'].",".$_SESSION['goods_barcode'].",$value,1);";
					$res=common_location_transaction($_SESSION['HAKOID'],$_REQUEST['TOKORO'],$_SESSION['goods_barcode'],$value,1);
					if($res['success']){
						$done=$done+1;
					} else {
						pp("SN[$value] 上架悲剧了。(".$res['error'].")");
						$msg=$msg."SN[$value] 上架悲剧了。(".$res['error'].")";
					}
				}
				if($done==$_SESSION['number']){
					pp("SN OUT ALL DONE");
					$msg=$msg."递交的都入库了。";
				}
			} else {
				//echo "NSN=common_location_transaction(".$_REQUEST['HAKOID'].",".$_REQUEST['TOKORO'].$_SESSION['goods_barcode'].",$value,".$_REQUEST['number'].");";
				$res=common_location_transaction($_SESSION['HAKOID'],$_REQUEST['TOKORO'],$_SESSION['goods_barcode'],"",$_REQUEST['number']);
				if($res['success']){
					pp("DONE");
					$msg=$msg."非串号入库完了。";
				} else {
					pp("上架悲剧了。(".$res['error'].")");
					$msg=$msg."上架悲剧了。(".$res['error'].")";
				}
			}
			pp("Rising Up!");
			echo "<script lang=\"text/javascript\">
				alert($msg);
			</script>";
			//TODO uping
			if ($state==301){				
				echo "Back to Tokoro";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=200\">";
			} else if ($state==302){
				echo "Back to Mono";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=1\">";
			} else if ($state==303){
				echo "Back to HAKOID";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=MonoOsuwari.php?state=0\">";
			}
		}
		?>
	</body>
</html>