<?php
	define('IN_ECS', true);
	require_once('includes/init.php');
	require_once('function.php');
	require_once('includes/lib_sinri_DealPrint.php');

	if (isset($_REQUEST['sn']) && $_REQUEST['sn']){
		$sn=$_REQUEST['sn'];
	} else {
		$sn=false;
	}
	if ($sn && isset($_REQUEST['sugu_print']) && $_REQUEST['sugu_print']){
		$sp=$_REQUEST['sugu_print'];
	} else $sp=0;
?>
<!-- 
	All Hail Sinri Edogawa!
	公元2013年夏，浙江南岸浪人大鲵自乐其幕府奉命建之。
	@UPDATED 20130813
	@AUTHOR ljni@i9i8.com
-->
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<TITLE>乐其新仓库 批拣单第【<?php echo $sn; ?>】号</TITLE>
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
			if ($sp==1 && $sn) {
		?>
		<DIV style="text-align:center;">
			<table style="border:0px;">
				<tr>
					<td>
						<h1>
							乐其新仓库 批拣单
						</h1>
						<h2>
							第【<?php echo $sn; ?>】号
						</h2>
						<h2>
							責任者：__________ 
						</h2>
						<h2>
							生成时间：
							<?php 
								echo Date('Y-m-d (w) H:i');
							?>
						</h2>
					</td>
					<td>
						<img src="code_img.php?barcode=<?php echo $sn; ?>&height=60">
					</td>
				</tr>
			</table>
		</DIV>
		<DIV>
			<?php
				$result=get_batch_pick_path_merged($sn);
				$total=sizeof($result);	
			    echo $total;			
			?>		
			<table border=1>	
				<tr>
					<th>共<?php echo $total; ?>条</th>
					<th>货架</th>
					<th>货物条码</th>					
					<th>货物名称</th>
					<th>数量</th>
					<!-- <th>下架去处</th> -->
					<th>记录</th>
				</tr>
				<?php						
						$location_count_i=0;
						$last_location_barcode=0;
						foreach ($result as $location_barcode => $location_info) {
							# code...
							$location_count_i++;
							echo "<tr>";
							if($location_barcode != $last_location_barcode)
							{
								echo "<td rowspan=".count($location_info['goods_list']).">".$location_count_i."</td>";
								echo "<td rowspan=".count($location_info['goods_list']).">".$location_barcode."</td>";
							}else{
								echo "<td rowspan=".count($location_info['goods_list']).">".$location_count_i."</td>";
								echo "<td rowspan=".count($location_info['goods_list'])."></td>";
							}
							$last_location_barcode = $location_barcode;
							foreach ($location_info['goods_list'] as $product_id => $goods_info) {
								# code...
								echo "<td>".$goods_info['barcode']."</td>";
								echo "<td>".$goods_info['goodsName']."</td>";
								echo "<td>".$goods_info['goodsNumber']."</td>";
								/*
								echo "<td style='text-align:left'>";
								foreach ($goods_info['validity_batch_sn'] as $grid_id => $validity_sns) {
									# code...
									echo "[第".$grid_id."格]".$validity_sns[0]['shipment_id']."</br>";
								    foreach ($validity_sns as $validity_sn) {
								    	if($validity_sn['validity'] !='1970-01-01') {
								    		echo ' 生产日期:'.$validity_sn['validity'];
								    	}
								    	if($validity_sn['batch_sn']) {
								    		echo ' 批次:'.$validity_sn['batch_sn'];
								    	}
								    	echo ' 数量:'.$validity_sn['quantity'];
								    	echo "</br>";
								    }
								    
								}
		    		
								echo "</td>";
								*/
								echo "<td></td>";
								echo "</tr>";
							}
						}
				?>
			</table>		
		</DIV>
		<DIV style="text-align:center;">
			乐其新仓库 批拣单第【<?php echo $sn; ?>】号 共<?php echo "$total"; ?>条任务 打印完毕!
		</DIV>
		<?php
			} else {
		?>
		<fieldset style="-moz-border-radius:6px;padding:10px;margin:10px;">
			<legend><span style="font-weight:bold; font-size:18px; color:#2A1FFF;">&nbsp;打印批拣单&nbsp;</span></legend>
			<DIV>
				<p>
					<form action="print_batch_pick.php" method="GET">
					如果需要根据批拣单号重新打印批拣单，请在此查询并重新打印:
						<input type="text" name="sn" value="<?php echo $sn; ?>">
						<input type="hidden" name="sugu_print" value="2">
						<input type="submit" value="  打 印  ">
					</form>
				</p>
			</DIV>
			<DIV>
				<?php
					if(isset($_REQUEST['count'])){
						$count=$_REQUEST['count'];
					}else {
						$count=25;
					}
				?>
				<hr style="margin:5px;">
				<h3 style="text-align:left;">
					最近<?php echo "$count";?>条批拣记录
				</h3>
				<?php
					
					$list=list_recent_BPs(null);
					//print_r($list);
					echo "<table border=1>";
					echo "<tr>";
					echo "<th>批拣单号</th>";
					//echo "<th>施設番号</th>";
					echo "<th>拣否</th>";
					echo "<th>执行人</th>";
					echo "<th>发起时间</th>";
					echo "<th>最后更新</th>";
					echo "</tr>";
					foreach ($list as $key => $line) {
						echo "<tr>";
						foreach ($line as $name => $value) {
							echo "<td>";
							if($name=='batch_pick_sn'){
								echo "<a href=\"batch_pick_detail.php?batch_pick_sn=$value\" target=\"new\">$value</a>";
								echo " &nbsp; ";
								echo "<a href=\"SinriTest/sinri_wms_batch_pick_debug.php?BPSN=$value\" target=\"_blank\">批拣信息监控</a>";
							} else {
								echo $value;
							}
							echo "</td>";
						}
						echo "</tr>";
					}
					echo "</table>";
				?>
			</DIV>
			<DIV>
				<?php
				if($sp==2){
					echo "<h1>打印预览</h1>";
					echo "
						<iframe name=\"print_frame\" width=\"100%\" height=\"100%\" frameborder=\"1\" 
						src=\"print_batch_pick.php?sugu_print=1&sn=$sn\" ></iframe>
						";
					}
				?>
			</DIV>
		</fieldset>
		<?php 
			}
		?>
	</body>
</html>