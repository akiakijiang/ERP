<?php
/**
All Hail Sinri Edogawa!
此页为乐其仓库改造部队的邪恶之大鲵奉聪颖幕府之命所建。
用于接收一批发货单号，为其调用建立批量拣货的BPSN，并就其BPSN进行打印拣货单。

@AUTHOR ljni@i9i8.com
@UPDATED 20130814

@PARAM checked ARRAY of checkboxes 来自shipment_listV5.php
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('includes/lib_print_action.php');
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<TITLE>SINRI BATCH PICK DEALING PAGE</TITLE>
		<script type="text/javascript" src="js/js_wms/sinri_print_iframe.js"></script>
	</head>
	<body>
		<?php
			if(isset($_REQUEST['checkbox_shipments'])){
				$shipment_ids=$_REQUEST['checkbox_shipments'];
				$bpsn=record_shipments_to_batch_pick($shipment_ids,$_SESSION['admin_name']);
			} else {
				echo "输入为空";
				echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=shipment_listV5.php\">";
			}
		?>
		
		<?php

			if(isset($bpsn['bpsn']) && $bpsn['bpsn']!=0){
				//打印批拣单
				LibPrintAction::addPrintRecord('BATCH_PICK',$bpsn['bpsn']);
		?>
		<DIV>
			开始打印批拣单【<?php echo $bpsn['bpsn']; ?>】。<br>
			包含的发货单有：
			<?php
				foreach ($shipment_ids as $key => $value) {
					echo $value." ";
				}
			?>
			<br>
			预览：
		</DIV>
		<hr>
		<?php
		echo "<iframe name=\"print_frame\" width=\"100%\" height=\"100%\" frameborder=\"1\" src=\"
			print_batch_pick.php?sugu_print=1&sn=".$bpsn['bpsn']."\"></iframe>";
		?>
		<?php
			} else {
				echo "<div><h1>本次批拣请求处理异常，请按照下方指示操作。</h1>";
				echo $bpsn['error'];
				echo "</div>";
				?>
				<h3>
					<a href="shipment_listV5.php">返回</a>
				</h3>
				<?php
			}
		?>
	</body>
</html>