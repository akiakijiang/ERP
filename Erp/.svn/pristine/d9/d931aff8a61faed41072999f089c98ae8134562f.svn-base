<!-- 
	All Hail Sinri Edogawa!
	公元2013年夏，浙江南岸浪人大鲵自乐其幕府奉命建之。
	@UPDATED 20130813
	@AUTHOR ljni@i9i8.com
-->
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<?php

			define('IN_ECS', true);
			require_once('includes/init.php');
			require_once('function.php');
			require_once('includes/lib_sinri_DealPrint.php');

			if(isset($_REQUEST['shipment_id'])){
				$ssid=$_REQUEST['shipment_id'];
				$ss_info=Sinri_GetInfoForSickness($ssid);
			}

			if ($ssid && isset($_REQUEST['sugu_print']) && $_REQUEST['sugu_print']){
				$sp=$_REQUEST['sugu_print'];
			} else $sp=0;
		?>
		<TITLE>病单</TITLE>
		<style type="text/css" media="all">
			*{
				margin:0;
				padding:0;
			}
			h2{
				font-size:12pt;
				clear:both;
				margin:10px 0;
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
				width:99.7%;
				font-size:10pt;
				text-align:center;
				border:1px solid #000;
				border-collapse:collapse;
				margin:20px 0 20px 2px;
			}
			table td{
			    padding:5px 5px;
			    border:1px solid #000;
			}
			table tr,table th{
				border:1px solid #000;
				height:20px;
				line-height:15px;
			}
			p{
				font-size:10pt;
				clear:both;
				text-align:center;
			}
			.left{
				float:left;
				margin-left:40px;
				display:inline;
			}
			.right{
				float:right;
				margin-right:20px;
			}	
			h1{
				text-align:center;
				font-size:15pt;
			}
			li.c{
				clear:both;
				width:100%;
			}	
			.postscript {
			    font-size:15px;
			    font-family: "黑体";
			    font-weight:bold;
			}
			.top{
			    margin-top:140px;
			    margin-left:0px;
			}
		</style>
	</head>
	<body <?php if($sp==1) echo "onload=\"window.print()\""; ?>>
		<?php
		if($sp==1 || $sp==2){
			?>
			<div style="text-align:center">
				<?php if($sp==1){ ?>
				<img src="code_img.php?barcode=<?php echo $ssid; ?>&height=60">
				&nbsp;
				<?php } ?>
				发货单【<?php echo $ssid; ?>】问题项清单
				&nbsp;
				生成时间: <?php echo date("D Y-m-d G:i:s T"); ?>				
			</div>
			<?php
		}
		if($ss_info && sizeof($ss_info)>0){
			echo "<table>";
			echo "<tr>";
			echo "<th>病单项登记号</th>";
			echo "<th>所属发货单号</th>";
			echo "<th>所属批拣单号</th>";
			echo "<th>是否串号处理</th>";
			echo "<th>货物条码</th>";
			echo "<th>产品号</th>";
			echo "<th>货物名称</th>";
			echo "<th>当前缺数</th>";
			echo "<th>组织</th>";
			echo "<th>仓库</th>";
			echo "</tr>";
			foreach ($ss_info as $ssno => $ss_line) {
				echo "<tr>";
				echo "<td";
				if($sp==1){ echo " rowspan=2";}
				echo ">".$ss_line['sick_shipment_id'].":";
				if($ss_line['status_id']=='Y'){
					echo "已完结";
				} else if($ss_line['status_id']=='S'){
					echo "已开始";
				} else if($ss_line['status_id']=='N'){
					echo "未开始";
				}
				echo "</td>";
				echo "<td>".$ss_line['shipment_id']."</td>";
				echo "<td>".$ss_line['batch_pick_sn']."</td>";
				echo "<td>".($ss_line['is_serial']==1?"串号控制":"非串号控制")."</td>";
				echo "<td>".$ss_line['goods_barcode']."</td>";
				echo "<td>".$ss_line['product_id']."</td>";
				echo "<td>".$ss_line['goods_name']."</td>";
				echo "<td>".$ss_line['lack_number']."</td>";
				echo "<td>".Sinri_GetPartyName($ss_line['party_id'])."[".$ss_line['party_id']."]"."</td>";
				echo "<td>".Sinri_GetFacilityName($ss_line['facility_id'])."[".$ss_line['facility_id']."]"."</td>";
				echo "</tr>";
				if($sp==1){
					echo "<tr><td colspan='10'>";
					$lbs=Sinri_GetSicknessMedicine($ss_line['facility_id'],$ss_line['party_id'],$ss_line['product_id']);
					if(sizeof($lbs)>0){
						foreach ($lbs as $key => $value) {
							echo "$value [___]  或  ";
						}
					}
					echo "任意能找到的库位";
					echo "</td></tr>";
				}
			}
			echo "</table>";
		}
		?>
	</body>
</html>