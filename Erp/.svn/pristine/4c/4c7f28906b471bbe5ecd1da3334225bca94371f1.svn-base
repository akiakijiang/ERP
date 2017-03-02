<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('postsale_function.php');

if($_SESSION['admin_name']=="ljni"){
  $show_dev=true;
} else{
  $show_dev=false;
}

$type=isset($_REQUEST['type'])?$_REQUEST['type']:'';
if($show_dev){
  $sql=isset($_REQUEST['sql'])?stripslashes($_REQUEST['sql']):'';
  $sql_hell=isset($_REQUEST['sql_hell'])?stripslashes($_REQUEST['sql_hell']):'';
}
if($type!=''){
  //echo $type;
  if(!empty($sql)){
    global $db;
    $result=$db->getAll($sql);
    if($type=='json'){
      $json=json_encode($result);
      echo $json;
    }else if($type=='csv'){
      echo "'0'";
      foreach ($result[0] as $key => $value) {
        echo ",'".$key."'";
      }
      echo "\r\n";
      foreach ($result as $line_no => $line) {
        echo "'".($line_no+1)."'";
        foreach ($line as $key => $value) {
          echo ",'".$value."'";
        }
        echo "\r\n";
      }
    }
  }
  die();
}

$full_power_permit=false;
$AdmiraltyCode=isset($_REQUEST['AdmiraltyCode'])?$_REQUEST['AdmiraltyCode']:'';
if($AdmiraltyCode=="All Hail Sinri Edogawa!"){
  $full_power_permit=true;
}

$order_id=isset($_REQUEST['order_id'])?$_REQUEST['order_id']:'';
$order_sn=isset($_REQUEST['order_sn'])?$_REQUEST['order_sn']:'';
$service_id=isset($_REQUEST['service_id'])?$_REQUEST['service_id']:'';
$refund_id=isset($_REQUEST['refund_id'])?$_REQUEST['refund_id']:'';
$trade_id=isset($_REQUEST['trade_id'])?$_REQUEST['trade_id']:'';
$shipment_id=isset($_REQUEST['shipment_id'])?$_REQUEST['shipment_id']:'';

$facility_id=isset($_REQUEST['facility_id'])?$_REQUEST['facility_id']:'';
$facility_name=isset($_REQUEST['facility_name'])?$_REQUEST['facility_name']:'';
$party_id=isset($_REQUEST['party_id'])?$_REQUEST['party_id']:'';
$party_name=isset($_REQUEST['party_name'])?$_REQUEST['party_name']:'';
$distributor_id=isset($_REQUEST['distributor_id'])?$_REQUEST['distributor_id']:'';
$tracking_number=isset($_REQUEST['tracking_number'])?$_REQUEST['tracking_number']:'';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Kasugauara</title>
		<style type="text/css">
			table, td {
				border: 1px solid gray;
				border-collapse:collapse;
				font-size: 13px;
				text-align: center;
				/* padding: 5px; */
			}
			th {
				background-color: #2899D6;/* #6CB8FF; */
				color: #EEEEEE;
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 2px;
				text-align: center;
			}
			div.waku{
  				/* padding: 5px;*/
  				margin: 5px;
  			}

      pre:hover {
        text-decoration: underline;
      }
		</style>
  	</head>
  	<body>
  	<h1>ようこそテストセンターへ
  		<!--
  		Kyrie eleison
		Christe eleison
		Kyrie eleison
		-->
		Κύριε ἐλέησον
		Χριστὲ ἐλέησον
		Κύριε ἐλέησον
	</h1>
  	<div style="
  		background-color: lightgreen;
  		padding:10px;
  	">
  		<form method="post">
  			ORDER_ID=<input type="text" name="order_id" value="<?php echo "$order_id"; ?>">
  			ORDER_SN=<input type="text" name="order_sn" value="<?php echo "$order_sn"; ?>">
  			TAOBAO TRADE_ID=<input type="text" name="trade_id" value="<?php echo "$trade_id"; ?>">
  			<br>
  			SERVICE_ID=<input type="text" name="service_id" value="<?php echo "$service_id"; ?>">
  			REFUND_ID=<input type="text" name="refund_id" value="<?php echo "$refund_id"; ?>">
  			<br>
  			SHIPMENT_ID=<input type="text" name="shipment_id" value="<?php echo "$shipment_id"; ?>">
  			<br>
  			FACILITY_ID=<input type="text" name="facility_id" value="<?php echo "$facility_id"; ?>">
  			FACILITY_NAME=<input type="text" name="facility_name" value="<?php echo "$facility_name"; ?>">
        <br>
  			PARTY_ID=<input type="text" name="party_id" value="<?php echo "$party_id"; ?>">
  			PARTY_NAME=<input type="text" name="party_name" value="<?php echo "$party_name"; ?>">
        <br>
  			DISTRIBUTOR_ID=<input type="text" name="distributor_id" value="<?php echo "$distributor_id"; ?>">
        <br>
        TRACKING_NUMBER=<input type="text" name="tracking_number" value="<?php echo "$tracking_number"; ?>">
        <br>
        オール　オープン　エンフォースメント　アドミラリティ　コード <input type='text' name='AdmiraltyCode' value="">
        <br>
  			<input type="submit">
        <?php if($show_dev){ ?>
  			DutyId:<input type="text" id="sql_open" onchange="
  				if(document.getElementById('sql_open').value=='kyrie eleison'){
  					alert('Rise up, LORD, and let thine enemies be scattered; and let them that hate thee flee before thee!');
  					document.getElementById('sql_area').style.display='block';
  				} else if(document.getElementById('sql_open').value=='amen'){
  					alert('Return, O LORD, unto the many thousands of Israel!');
  					document.getElementById('sql_area').style.display='none';
  					document.getElementById('sql_hell_area').style.display='none';
  				} else if(document.getElementById('sql_open').value=='christe eleison'){
  					alert('Glória in excélsis Deo et in terra pax homínibus bonae voluntátis. Laudámus te, benedícimus te, adorámus te, glorificámus te, grátias ágimus tibi propter magnam glóriam tuam, Dómine Deus, Rex cæléstis, Deus Pater omnípotens. Dómine Fili Unigénite, Iesu Christe, Dómine Deus, Agnus Dei, Fílius Patris, qui tollis peccáta mundi, miserére nobis; qui tollis peccáta mundi, súscipe deprecatiónem nostram. Qui sedes ad déxteram Patris, miserére nobis. Quóniam tu solus Sanctus, tu solus Dóminus, tu solus Altíssimus, Iesu Christe, cum Sancto Spíritu: in glória Dei Patris. Amen.');
  					document.getElementById('sql_hell_area').style.display='block';
  				} 
  			">
  			<div id="sql_area" style="<?php if(!empty($sql))echo "display: block;";else echo "display: none;"; ?>">
  				Heaven:<textarea name='sql' cols="120" rows="8"><?php echo $sql;?></textarea>
  			</div>
  			<div id="sql_hell_area" style="<?php if(!empty($sql_hell))echo "display: block;";else echo "display: none;"; ?>">
  				Hell:<textarea name='sql_hell' cols="120" rows="8"><?php echo $sql_hell;?></textarea>
  			</div>
        <?php } ?>
  		</form>
  	</div>
  	<hr>
  	<div style="min-height:500px;">
  	<?php
  	if(!empty($_REQUEST['order_sn'])){
  		echo "<h1>ORDER_SN=$order_sn</h1>";
  		displaySQL("SELECT
						*
					FROM
						ecshop.ecs_order_info
					WHERE
						order_sn = '$order_sn';
  		");
      global $db;
      $order_id_by_sn=$db->getOne("SELECT
            order_id
          FROM
            ecshop.ecs_order_info
          WHERE
            order_sn = '$order_sn';");
  	}
  	?>
  	<?php
    if($order_id==''){
      $order_id=$order_id_by_sn;
    }
  	if($order_id!=''){
  		echo "<h1>ORDER_ID=$order_id</h1>";
  		displaySQL("SELECT
						*
					FROM
						ecshop.ecs_order_info
					WHERE
						order_id = '$order_id';
  		");
  		displaySQL("SELECT
						*
					FROM
						ecshop.ecs_order_action
					WHERE
						order_id = '$order_id';
  		");
  		displaySQL("SELECT
						*
					FROM
						ecshop.service
					WHERE
						order_id = '$order_id';
		");
		displaySQL("SELECT
						*
					FROM
						romeo.refund
					WHERE
						order_id = '$order_id';
		");
		// displaySQL("SELECT
		// 				*
		// 			FROM
		// 				ecshop.order_mixed_status_note
		// 			WHERE
		// 				order_id='$order_id';
		// ");
		// displaySQL("SELECT
		// 				*
		// 			FROM
		// 				ecshop.order_mixed_status_history
		// 			WHERE
		// 				order_id='$order_id';
		// ");
		displaySQL("SELECT
						*
					FROM
						romeo.order_shipment os
					WHERE
						os.ORDER_ID = '$order_id';
		");
	}
  	?>

  	<?php
  	if(!empty($_REQUEST['shipment_id'])){
  		echo "<h1>SHIPMENT_ID=$shipment_id</h1>";
  		displaySQL("SELECT
						*
					FROM
						romeo.batch_pick_mapping bpm
					WHERE
						bpm.shipment_id = '$shipment_id';
  		");
  		displaySQL("SELECT * FROM romeo.order_shipment WHERE SHIPMENT_ID='$shipment_id';");
  		displaySQL("SELECT * FROM romeo.batch_pick_mapping bpm WHERE bpm.shipment_id='$shipment_id';");
      displaySQL("SELECT * FROM romeo.shipment WHERE SHIPMENT_ID='$shipment_id';");
  	}
  	?>
  	
  	<?php
  	if(!empty($_REQUEST['service_id'])){
  		echo "<h1>SERVICE_ID=$service_id</h1>";
  		displaySQL("SELECT
						*
					FROM
						ecshop.service
					WHERE
						service_id = '$service_id';
  		 ");
  	}
  	?>
  	
  	<?php
  	if(!empty($_REQUEST['refund_id'])){
  		echo "<h1>REFUND_ID=$refund_id</h1>";
  		displaySQL("SELECT
						*
					FROM
						romeo.refund
					WHERE
						refund_id = '$refund_id';
		");
  	}
  	?>

  	<?php
  	if(!empty($_REQUEST['trade_id'])){
  		echo "<h1>TRADE_ID=$trade_id</h1>";
  		displaySQL("SELECT
						*
					FROM
						ecshop.sync_taobao_refund
					WHERE
						tid like '%$trade_id%';
  		");
  		displaySQL("SELECT
						*
					FROM
						ecshop.ecs_order_info
					WHERE
						taobao_order_sn like '$trade_id%';
  		");
  	}
  	?>

  	<?php
  	if(!empty($_REQUEST['facility_id'])){
  		echo "<h1>FACILITY_ID=$facility_id</h1>";
  		displaySQL("SELECT * FROM romeo.facility where FACILITY_ID='$facility_id';");
  	}
  	if(!empty($_REQUEST['facility_name'])){
  		echo "<h1>FACILITY_NAME=$facility_name</h1>";
  		displaySQL("SELECT * FROM romeo.facility where FACILITY_NAME like '%$facility_name%';");
  	}
  	if(!empty($_REQUEST['party_id'])){
  		echo "<h1>PARTY_ID=$party_id</h1>";
  		displaySQL("SELECT * FROM romeo.party where PARTY_ID='$party_id';");
  	}
  	if(!empty($_REQUEST['party_name'])){
  		echo "<h1>PARTY_NAME=$party_name</h1>";
  		displaySQL("SELECT * FROM romeo.party where NAME like '%$party_name%';");
  	}
  	if(!empty($_REQUEST['distributor_id'])){
  		echo "<h1>DISTRIBUTOR_ID=$distributor_id</h1>";
  		//displaySQL("SELECT * FROM ecshop.distributor where distributor_id='$distributor_id';");
  		displaySQL("SELECT
                *
            FROM
            ecshop.distributor d
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                d.distributor_id = '$distributor_id';");
  	}
  	?>

    <?php
    if(!empty($_REQUEST['tracking_number'])){
      echo "<h1>TRACKING_NUMBER=$tracking_number</h1>";
      displaySQL("SELECT * FROM romeo.shipment WHERE tracking_number='{$tracking_number}';");
      if($full_power_permit)displaySQL("SELECT * FROM ecshop.ecs_order_action WHERE action_note like '%{$tracking_number}%' and action_time>'2014-06-01 00:00:00'");
      displaySQL("SELECT * FROM ecshop.ecs_carrier_bill WHERE bill_no = '{$tracking_number}';");
    }
    ?>

  	<?php if($show_dev){
  	/**
  	MAP GUN
  	**/
  		if(!empty($sql)){
  			echo "<h1>All Hail Sinri Edogawa!</h1>";
  			echo "Carried out the following sql:<br> <br><i>".$sql."</i><br> <br>";
  			global $db;
  			$r=$db->getAll($sql);
  			//pp($r);
  			if($r){
  				echo "Result is as following:<br><table border='1'>";
  				if($r && is_array($r)){
					$keys=array_keys($r[0]);
					echo "<tr>";
					foreach ($keys as $no => $key) {
						echo "<th>$key</th>";
					}
					echo "</tr>";
  				}
  				foreach ($r as $i => $line) {
  					if($line){
  						echo "<tr>";
	  					foreach ($line as $key => $value) {
                if(is_null($value)){
                  echo "<td><i>NULL</i></td>";
                }else{
	  						  echo "<td>$value</td>";
                }
	  					}
	  					echo "</tr>";
	  				}
  				}
  				echo "</table>";
  			} else{
  				echo "<p>No Result</p>";
  			}
  		}

  		if(!empty($sql_hell)){
  			echo "<h1>Gloria in excelsis Deo</h1>";
  			echo "<p>Carried out the following sql:<br> <br><i>".$sql_hell."</i></p>";
  			global $db;
    		$sql_0="START transaction;";
    		$sql_2="COMMIT;";
    		$sql_3="ROLLBACK";
    		$db->query($sql_0);
        $r=$db->query($sql_hell);
        if($r){
            $db->query($sql_2);
            echo "<h2>COMMIT</h2>";
        }else{
            $db->query($sql_3);
            echo "<h2>ROLLBACK</h2>";
        }
  		}
    }
  	?>
  	<hr>
  		<p style="text-align: center;">
  		ALL HAIL SINRI EDOGAWA 2014 LEQEE ERP MAP GUN
  		</p>
  	</div>
  	</body>
</html>

<?php

function displaySQL($sql){
	global $db;
	$r=$db->getAll($sql);
	if($r){
		echo "<div class='waku'>";
		echo "<p>".$sql."</p>";
		echo "<table border='1'>";
		echo "<tr>";
		foreach ($r[0] as $key => $value) {
			echo "<th>$key</th>";
		}
		echo "</tr>";
		
		foreach ($r as $rid => $rline) {
			echo "<tr>";
			foreach ($rline as $key => $value) {
				echo "<td><pre>$value</pre></td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "</div>";
	}else{
		echo "<div><p>".$sql."</p><p>No results</p></div>";
	}
}

?>