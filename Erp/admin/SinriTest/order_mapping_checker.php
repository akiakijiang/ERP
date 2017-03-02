<?php
	define('IN_ECS', true);
	require_once('../includes/init.php');
	include_once('../config.vars.php');
	header("content-type:text/html;charset=utf-8");

	$res = "";
	if(isset($_POST['act'])){
		if($_POST['act'] == 'order_mapping_checker'){
			if(isset($_POST['orderstr'])){
					$res = ckeck_findCatholicRecords($_POST['orderstr']);
			}
		}
	}

	function ckeck_findCatholicRecords($order_str,$shipping_time_limit_days=2,$shipping_time_limit_days_offset=0){
		global $db;
		global $_CFG;
		$result = "";
		$erp_outer_sn = false;
		if(empty($order_str)){
			$result = "输入为空";
			return $result;
		}
		$count = 0;
		//判断输入订单号类别
		while(!$erp_outer_sn){
			if($count == 0){
				//taobao_order_sn，distribution_purchase_order_sn类
				$order_sql = "SELECT mp.outer_order_sn as taobao_order_sn,oi.order_id
						  FROM ecshop.ecs_order_mapping mp
						  LEFT JOIN  ecshop.ecs_order_info oi ON oi.taobao_order_sn = mp.outer_order_sn
						  WHERE mp.outer_order_sn='{$order_str}'";
			}else if($count == 1){	
					$order_sql = "SELECT oi.taobao_order_sn,oi.order_id FROM ecshop.ecs_order_info oi WHERE oi.order_sn='{$order_str}'
								  UNION
								  SELECT oi.taobao_order_sn,oi.order_id FROM ecshop.ecs_order_info oi WHERE oi.order_id='{$order_str}'
								  UNION
								  SELECT oi.taobao_order_sn,oi.order_id FROM ecshop.ecs_order_info oi WHERE oi.taobao_order_sn='{$order_str}'
								  ";
			}else{
				$result = "不存在此订单号！";
				return $result;
			}
			$erp_outer_sn = $db ->getRow($order_sql);
			$count ++;
		}

		// echo $erp_outer_sn['order_id'];
		if(empty($erp_outer_sn['taobao_order_sn'])||$erp_outer_sn['taobao_order_sn'] == ""){
			$result = "此订单不存在外部订单号！";
			return $result;
		}
		
		if($count == 1){
			if(!empty($erp_outer_sn['order_id'])){
				$result = "此订单号为taobao_order_sn：";
			}else{
				$result = "此订单号为distribution_purchase_order_sn：";
				$count = $count + 2;
			}
		}else if($count == 2){
			if($erp_outer_sn['order_id'] == $order_str){
				$result = "此订单号为order_id：";
			}else if($erp_outer_sn['taobao_order_sn'] == $order_str){
				$result = "此订单号为taobao_order_sn：";
				$count = $count + 2;
			}else{
				$result = "此订单号为order_sn：";
			}
		}

		$result .= "<span class = 'underline'>".$order_str."</span>（提示）<br>";
		if($count == 2){
			$result .= "(对应erp外部订单号taobao_order_sn为：<span class = 'underline'>".$erp_outer_sn['taobao_order_sn']."</span>)<br>";
		}
		// return $result;
		// var_dump($erp_outer_sn['taobao_order_sn']);
		if($count == 3){
			$mapping_sql = "ON oi.distribution_purchase_order_sn = mp.outer_order_sn";
			$order_sql_ = "WHERE mp.outer_order_sn = '{$erp_outer_sn['taobao_order_sn']}'";
		}else{
			$mapping_sql = "ON oi.taobao_order_sn = mp.outer_order_sn";
			$order_sql_ = "WHERE oi.taobao_order_sn = '{$erp_outer_sn['taobao_order_sn']}'"; 
		}
		//判断各种条件是否符合
		$sql = "SELECT 	
						oi.order_id,
						oi.order_status,
						oi.pay_status,
						oi.shipping_status,
						oi.order_time,
						oi.shipping_time,
						oi.source_type,
						oi.taobao_order_sn,
						oi.distribution_purchase_order_sn,
						mp.mapping_id,
						mp.outer_order_sn,
						mp.shipping_status AS mp_shipping_status,
						mp.tracking_numbers,
						mp.memo,
						s.shipment_id,
						s.tracking_number,
						s.status,
						UNIX_TIMESTAMP() AS UNIX_TIMESTAMP
				FROM
					ecshop.ecs_order_info oi 
				LEFT JOIN romeo.order_shipment os on os.order_id=convert(oi.order_id using utf8)
				LEFT JOIN romeo.shipment s on s.shipment_id=os.shipment_id	 
				LEFT JOIN   ecshop.ecs_order_mapping mp {$mapping_sql}
				{$order_sql_}
				";
		$check_vars = $db -> getRow($sql);
	
		 // var_dump($check_vars);
		if($check_vars){
			$if_success = 1;
			if(!empty($check_vars['order_id'])){
				$result .="该订单order_id:".$check_vars['order_id']."（提示）<br>";
				if($count != 3){
					!empty($check_vars['distribution_purchase_order_sn'])?
					$result .="对应distribution_purchase_order_sn:<span class = 'underline'>"
					.$check_vars['distribution_purchase_order_sn']."</span>（提示）<br>"
					:$result .="该订单无对应distribution_purchase_order_sn（提示）<br>";
				}else if($count == 3){
					!empty($check_vars['taobao_order_sn'])?
					$result .="对应taobao_order_sn:<span class = 'underline'>"
					.$check_vars['taobao_order_sn']."</span>（提示）<br>"
					:$result .="该订单无对应taobao_order_sn（提示）<br>";
				}

				$result .="<span>";
				if($check_vars['order_status'] != 1){

					$result .=
					"订单不是已确认状态！此订单为：".
					$_CFG['adminvars']['order_status'][$check_vars['order_status']]."状态<br>";
					$if_success = 0;
				}
				if($check_vars['pay_status'] != 2){
					$result .=
					"订单不是已付款状态！此订单为：".
					$_CFG['adminvars']['pay_status'][$check_vars['pay_status']]."状态<br>";
					$if_success = 0;
				}
				if($check_vars['shipping_status'] != 1){
					$result .=
					"订单不是已发货状态！此订单为：".
					$_CFG['adminvars']['shipping_status'][$check_vars['shipping_status']]."状态<br>";
					$if_success = 0;
				}
				if($check_vars['order_time'] < '2015-09-15 00:00:00'){
					$result .=
					"订单创建时间小于2015-09-15 00:00:00！此订单创建时间为：".$check_vars['order_time']."<br>";
					$if_success = 0;
				}
				if($check_vars['source_type'] != 'taobao'&&$check_vars['source_type'] != '360buy'&&
						$check_vars['source_type'] != '360buy_overseas'){
						$result .=
						"订单来源类型错误！此订单为：";
						!empty($check_vars['source_type'])?$result .=$check_vars['source_type']."<br>"
						:$result .="订单来源类型未设置！<br>";
						$if_success = 0;
				}
				$result .= "</span>";
				if(!empty($check_vars['shipping_time'])){
					if($check_vars['shipping_time'] <= (($check_vars['UNIX_TIMESTAMP'] - 3600 * 24 * shipping_time_limit_days))
						||$check_vars['shipping_time'] > (($check_vars['UNIX_TIMESTAMP'] - 3600 * 24 * shipping_time_limit_days_offset))){
						$result .= "<span>";
						$result .=
						"shipping_time不在设定范围内！此订单shipping_time为：".date('Y-m-d H:i:s',$check_vars['shipping_time']).
						"<br>(规定范围为：".date('Y-m-d H:i:s',(($check_vars['UNIX_TIMESTAMP'] - 3600 * 24 * shipping_time_limit_days)))
						."到".date('Y-m-d H:i:s',(($check_vars['UNIX_TIMESTAMP'] - 3600 * 24 * shipping_time_limit_days_offset))).")<br>";
						$result .= "</span>";
						$if_success = 0;
					}	
				}else{
					$result .= "<span>";
					$result .="shipping_time未设置！<br>";
					$result .= "</span>";
					$if_success = 0;
			}
			}else{
				$result .= "<span>";
				$result .=
				"找不到与此外部订单号相对应的erp订单号(order_id)！<br>";
				$result .= "</span>";
				$if_success = 0;
			}
			

			if(!empty($check_vars['mapping_id'])){
				$result .="该订单mapping_id:".$check_vars['mapping_id']."（提示）<br>"
				."该订单outer_order_sn:<span class = 'underline'>".$check_vars['outer_order_sn']."</span>（提示）<br>";
				$result .= "<span>";
				if($check_vars['mp_shipping_status'] != ''&&$check_vars['mp_shipping_status'] != 'SELLER_CONSIGNED_PART'&&
				!empty($check_vars['mp_shipping_status'])){
					$result .=
					"外部订单发货状态错误！此订单状态为：".$check_vars['mp_shipping_status']."<br>";
					$if_success = 0;
				}
				$result .= "</span>";
				if(!empty($check_vars['tracking_numbers'])){
					$result .= 
					"该外部订单的tracking_numbers字段信息为：".$check_vars['tracking_numbers']."(提示信息)<br>";
				}
				if(!empty($check_vars['memo'])){
					$result .= 
					"该外部订单的memo字段信息为：".$check_vars['memo']."(提示信息)<br>";
				}
			}else{
					//IF erp_outer_sn is X-N, process X;ELSE throw away.
					$subtagindex=strpos($check_vars['taobao_order_sn'],'-');
					$pure_taobao_order_sn='';
	        		if($subtagindex!==false){
	        			$pure_taobao_order_sn=substr($check_vars['taobao_order_sn'], 0,$subtagindex);
	        		}
	        		if(!empty($check_vars['distribution_purchase_order_sn'])){
	        			$pure_taobao_order_sn=$check_vars['distribution_purchase_order_sn'];
	        		}
	        		if(!empty($pure_taobao_order_sn)){
	        			$sql_pure = "SELECT
											mp.shipping_status AS mp_shipping_status,
											mp.mapping_id,
											mp.tracking_numbers,
											mp.outer_order_sn,
											mp.memo
										FROM
											ecshop.ecs_order_mapping mp
										WHERE
											mp.outer_order_sn = '{$pure_taobao_order_sn}'
										";
							
							$pure_res = $db -> getRow($sql_pure);
							// var_dump($pure_res);
							if($pure_res){
								if(!empty($pure_res['mapping_id'])){
									$result .="该订单mapping_id:".$pure_res['mapping_id']."（提示）<br>"
									."该订单outer_order_sn:<span class = 'underline'>".$pure_res['outer_order_sn']."</span>（提示）<br>";
									$result .= "<span>";
									if($pure_res['mp_shipping_status'] != ''&&$pure_res['mp_shipping_status'] != 'SELLER_CONSIGNED_PART'&&
									!empty($pure_res['mp_shipping_status'])){
									$result .=
									"外部订单发货状态错误！此订单状态为：".$pure_res['mp_shipping_status']."<br>";
									$if_success = 0;
									}
									$result .= "</span>";
									if(!empty($pure_res['tracking_numbers'])){
										$result .= 
										"该订单的tracking_numbers字段信息为：".$pure_res['tracking_numbers']."(提示信息)<br>";
									}
									if(!empty($pure_res['memo'])){
										$result .= 
										"该订单的memo字段信息为：".$pure_res['memo']."(提示信息)<br>";
									}
							}else{
								$result .= "<span>";
								$result .= 
								"taobao_order_sn去掉‘-’换成distribution_purchase_order_sn后该订单号仍与order_mapping匹配不到！<br>";
								$result .= "</span>";
								$if_success = 0;
							}
						}else{
							$result .= "<span>";
							$result .= 
							"taobao_order_sn去掉‘-’或换成distribution_purchase_order_sn后仍取不到订单对应order_mapping信息！<br>";
							$result .= "</span>";
							$if_success = 0;
						}
	        		}else{
	        			$result .= "<span>";
	        			$result .= 
						"该订单号与order_mapping匹配不到！<br>";
						$result .= "</span>";
						$if_success = 0;
	        		}
	        	}

			if(!empty($check_vars['shipment_id'])){
				$result .="发货单shipment_id:".$check_vars['shipment_id']."（提示）<br>";
				$result .= "<span>";
				if($check_vars['tracking_number'] == ''||empty($check_vars['tracking_number'])){
					$result .=
					"发货面单号为空！<br>";
					$if_success = 0;
				}
				if($check_vars['status'] == 'SHIPMENT_CANCELLED'){
					$result .=
					"发货为取消SHIPMENT_CANCELLED状态！<br>";
					$if_success = 0;
				}
				$result .= "</span>";
			}else{
				$result .= "<span>";
				$result .=
					"发货单还未创建！<br>";
					$result .= "</span>";
					$if_success = 0;
			}

			if($if_success == 1){
				$result .= "<div><span style = 'color:red'>可以继续进行！</span></div><br>";
			}else{
				$result .= "<div><span style = 'color:red'>有条件不满足,不能继续进行！</span><br>
							(除提示信息外,以上均为不满足条件)</div><br>";
			}
			return $result;
		}else{
			$result .= "<span>";
			$result .= "未取到任何相关订单的值(order_id,shipment_id等)！<br>";
			$result .= "</span>";
			return $result;
		}
	}

?>

<!DOCTYPE html>
<html>
<head>
	<title>
		根据订单号查询外部订单号发货是否能继续进行
	</title>
<style type="text/css">
	div {
		margin: 15px;
	}
	.input {
		height: 25px;
		line-height: 23px;
		width: 200px;
		padding-right: 16px;
		border:solid 1px #DCDCDC;
	}
	.button {
		display:inline-block;
		border: none;
	    outline: none;
	    background: #88bc40;
	    background-image: -moz-linear-gradient(top, #a2ca51, #88bc40);
	    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0,#a2ca51), color-stop(1, #88bc40));
	    border-bottom: 1px solid #729f36;
	    color: #fff;
	    width: 45px;
	    height: 30px;
	    line-height: 25px;
	    cursor: pointer;
	    border-radius: 4px;
	    text-align: center;
	}
	span{
		color:#FF00FF ;
	}
	.underline{
		color:black;
		text-decoration:underline
	}
	</style>
</head>
<body style='font-family: 微软雅黑;'>
<center>
<p style="font-family:幼圆;font-size:28px;margin-top:140px;font-weight:bold;">根据订单号查询外部订单号发货是否能继续进行</p>
<div>
<form method = "post">
	<input type = "hidden" name = "act" value = "order_mapping_checker"> 
    订单号：<input class="input" id = "ostr" type = "text"  name = "orderstr" value = "<?php echo $_POST['orderstr']?>">
    <input type = "submit" class = "button" name = "submit" value = "查询">
    <input type = "button" class = "button" name = "reset" value = "清空" onclick="document.getElementById('ostr').value = '';"><br>
</form>

<div>
	<?php echo $res;?>
</div>
</center>

</body>
</html>