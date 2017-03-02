<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<?php
			define('IN_ECS', true);
			require_once('includes/init.php');
			require_once('function.php');
			require_once('includes/lib_sinri_DealPrint.php');

			$facility_list = Sinri_GetUserFacilityInfo();
			if (empty($facility_list)) {
				die('没有仓库权限');
			}
			//当前所在组织
			$party_id = $_SESSION ['party_id'];
			$party_name=Sinri_GetPartyName($party_id);

			if(isset($_REQUEST['facility_ids'])){
				$facility_ids = $_REQUEST['facility_ids'];
			}

			if ($ssid && isset($_REQUEST['sugu_print']) && $_REQUEST['sugu_print']){
				$sp=$_REQUEST['sugu_print'];
			} else $sp=0;
		?>
		<TITLE>未完全拣货发货单查询和处理</TITLE>
	</head>
	<body>
		<div>
			<h1>未完全拣货发货单查询和处理</h1>
		</div>
		<div>
			<?php
			/*
			<p>
				当前的组织：<i><?php echo $party_name; ?>。</i>
				许可的仓库列表：<i><?php foreach ($facility_list as $key => $value) {
					echo $value." ";
				} ?></i>
			</p>	
			*/
			?>		
			<p>
				<form action="query_sick_shipment.php" method="post">
					<h2>可选的仓库</h2>
					<table>
					<?php
					$i=1;
					foreach ($facility_list as $key => $value) {
						if($i%5==1) echo"<tr>";
						echo "<td>";
						echo "<input type=\"checkbox\" id=\"FL".$value['facility_id']."\" name=\"facility_ids[]\" value=\"".$value['facility_id']."\"> ".$value['facility_name']." &nbsp;";
						echo "</td>";
						if($i%5==0) echo"</tr>";
						$i=($i+1);
					}
					if($i%5!=1){
						echo"</tr>";
					}
					?>
					<?php if (isset($facility_ids) && sizeof($facility_ids)>0){ ?>
					<script type="text/javascript">
						<?php
						foreach ($facility_ids as $key => $value) {
							echo "
								document.getElementById('FL".$value."').checked=true;
							";
						}
						?>
					</script>
					<?php } ?>
					</table>
					<h3>
						<input type="hidden" name="act" value="search">
						问题发货单的处理应为专人负责，请确认。<input type="submit">
					</h3>
				</form>
			</p>
		</div>
		<div style="display:none;background-color:lightgreen;">
			<?php
			pp($_REQUEST);
			?>
		</div>
		<hr>
		<div>
			<?php if(isset($_REQUEST['act']) && $_REQUEST['act']=='search' && sizeof($facility_ids)>0) { ?>
			<?php
			$result=getSickSIDs($facility_ids,$party_id);
			//pp($result);
			?>
			<?php
			if(sizeof($result)==0){
				echo "<h1>没有病单，喜大普奔</h1>";
			} else{
				?>
				<table border="1" style="width:95%;text-align:center;">
					<tr>
						<th>发货单号</th>
						<th>批拣原籍</th>
						<th>所属仓库</th>
						<th>送查时间</th>
						<th>处理操作</th>
					</tr>
					<?php
					foreach ($result as $key => $sid) {
						$info=Sinri_GetInfoForSickness($sid);
						echo "<tr>";
						if(sizeof($info)==0){
							echo "<td colspan=5>造反了</td>";
						} else {
							echo "<td><a href=\"print_sick_shipments.php?shipment_id=".$sid."&sugu_print=2\" target=\"_blank\">".$info[0]['shipment_id']."</a></td>";
							echo "<td>".$info[0]['batch_pick_sn']."</td>";
							echo "<td>".Sinri_GetFacilityName($info[0]['facility_id'])."</td>";
							echo "<td>".$info[0]['created_stamp']."</td>";
							echo "<td><a href=\"print_sick_shipments.php?shipment_id=".$sid."&sugu_print=1\" target=\"_blank\">重检打印</a></td>";
						}
						echo "</tr>";
					}
					?>
				</table>
				<?php
			}
			?>
			<?php } else {
				echo "请指定仓库 (╯‵□′)╯︵┻━┻";
			}
			?>
		</div>
	</body>
</html>