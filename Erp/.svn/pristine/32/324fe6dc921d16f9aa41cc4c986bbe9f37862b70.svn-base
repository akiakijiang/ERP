<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'includes/helper/array.php');
// include_once (ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
Yii :: import('application.commands.LockedCommand', true);
/*
 * Created on 2013-2-23
 *  
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class AutoConfirmOrdersCommand extends CConsoleCommand {

	private $master; // Master数据库    
	private $can_merge_order_ids; 
	private $no_need_merge_partys; 

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {
		//生成出库指示数据
		$this->run(array (
			'Validate'
		));
	}

	public function actionTest() {
		global $db;
		$this->log('All Green!');
//		   $partys = "select party_id from romeo.party where party_group ='<!--6-->跨境'"; 
//           $no_need_merge_partys = $db->getCol($partys);
//           var_dump($no_need_merge_partys);
//           if(in_array(65638,$no_need_merge_partys)){
//           	echo 'HHHHHHHHHHHHHHHHHHHHHHHHHHHH';
//           }else{
//           	echo 'FFFFFFFFFFFFFFFFFFFFFFFFFFFF';
//           }
	}
    

	/*  扫描需要自动确认的订单
	 */
    function scanOrdersForConfirmStep($distri_id,$oi_facility_id,$party_id,$last_order_id_sql,$num = 200,$days = 10){
    	global $db;
    	$postscript_switch=1;//为1的时候，检查客户留言有些奇怪的字就呵呵。
    	$reserve_switch=0;//为1的时候，限制库存一定要有，2016.5.5去掉库存限制 
		$check_has_confirm_action=1;//为1的时候，在OA记录里不能有已确认记录
    	
    	//HERE CHANGE SWITCHES WITH CONDITIONS
    	//SUCH AS
    	// if($party_id=='65558'){
    	// 	$postscript_switch=0;
    	// 	$reserve_switch=0;
    	// }

    	$sql = "SELECT oi.order_id,oi.order_sn,oi.order_status,oi.pay_status,oi.shipping_status,
    	           oi.order_amount,oi.goods_amount,oi.shipping_fee,oi.bonus,oi.pack_fee ,
    	           oi.invoice_status,oi.shortage_status 
    	          from ecshop.ecs_order_info as oi use index(order_info_multi_index)					
				  where  oi.order_time > date_sub(NOW(), INTERVAL $days day)  
				  and oi.party_id = {$party_id}
				  and oi.order_status = 0 
				  and oi.shipping_status = 0 
				  and oi.pay_status = 2  
				  {$last_order_id_sql}
				  and oi.order_type_id = 'SALE' 
				  {$distri_id}
				  {$oi_facility_id}
				  and
					 /*过滤掉客户留言的订单*/		
					(
						$postscript_switch = 0 
						or oi.party_id = 65553
						or	
						(
							(oi.postscript is null or oi.postscript = '')
							or
							(
								/*在山的那边海的那边有一个亨氏唯品会*/
								oi.pay_id=249 and TRIM(oi.postscript) = '送货时间不限'
							)
						)
					)
				  and
					/*过滤掉港澳台地址的订单*/	
					    oi.province not in(3689 ,3688, 3784,0)
					    and oi.city <> 0
				   and 
				   /*过滤小二留言的订单*/		
				   (
						$postscript_switch = 0 
						or oi.distributor_id=2758 -- 聚惠优购无视小二留言的身份证号 By Sinri 20160219
						or	(
							not EXISTS 
							(
							SELECT 1 from ecshop.order_attribute oa where oa.order_id = oi.order_id and attr_name = 'TAOBAO_SELLER_MEMO' limit 1
							)
						)
					)
					/*过滤掉库存不足的订单    已经将这一段逻辑 注释 之后不考虑库存不足的情况 
						and 			
						(
							$reserve_switch = 0 or
							not EXISTS 
							(
								SELECT 1 from ecshop.ecs_order_info eoi1
								left join ecshop.ecs_order_goods as og ON eoi1.order_id = og.order_id
								left join romeo.product_mapping as pm ON pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
								left join romeo.inventory_summary as ris ON pm.product_id = ris.product_id and eoi1.facility_id = ris.facility_id and og.status_id = ris.status_id
								LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = og.goods_id and gir.style_id = og.style_id and gir.facility_id = eoi1.FACILITY_ID and gir.party_id = eoi1.party_id
								where oi.order_id = eoi1.order_id and (ris.AVAILABLE_TO_RESERVED is null or ris.AVAILABLE_TO_RESERVED-if(gir.reserve_number is NULL or gir.status != 'OK',0,gir.reserve_number) < og.goods_number) 
								limit 1
							)
						)	*/														
					/*过滤掉淘宝后台已发起退款申请的订单*/
					and
						(
							not EXISTS 
							(
								select 1 from ecshop.sync_taobao_refund as str where str.tid = oi.taobao_order_sn limit 1
							)
						)
					/*过滤掉已发起退款申请的订单*/
					and
						(
						    not EXISTS 
							(
								select 1 from romeo.refund as re where re.order_id = convert(oi.order_id using utf8) limit 1
							)	
						)
					/*过滤掉套餐价格和订单金额不等的订单*/
					and oi.goods_amount =
	                        (
	                             select sum(eog.goods_price * eog.goods_number) from ecshop.ecs_order_goods as eog where eog.order_id = oi.order_id  
								 group by eog.order_id limit 1	 
	                        )	                   
					and oi.order_amount = oi.goods_amount + oi.shipping_fee + oi.bonus + oi.pack_fee
					/*过滤掉已被确认又被人打回未确认的订单*/
					and (
						$check_has_confirm_action = 0
						or not EXISTS (select * from ecshop.ecs_order_action oa where oa.order_id=oi.order_id and oa.order_status=1)
					)
					limit {$num} "; 

	    $start = microtime(true); 
	    $this->log($sql);
	    $orders = $this->getMaster()->createCommand($sql)->queryAll(); 
	    $sql_end = microtime(true);
	    $this->log("  step sql query time ----- ".($sql_end - $start)); 
        $this->log("  the number of orders ----- ".count($orders)); 
	    $this_order_can = true; 
	    $order_count = 0;
	    $order_can = 0;
	    $order_succ = 0; 
	    $last_order_id = ''; 
	    if(!empty($orders)){
	    	$order_count = count($orders);
	    	foreach ($orders as $key => $order) {
	    		$this_order_can = true; 
	    		$last_order_id = $order['order_id']; 
               
                
                // 判断该订单是否存在合并可能 
                 if(intval( $party_id )  != 65638 && $this_order_can ){
                 	// 是否存在合并可能 返回 true 表示不需要合并订单 
                 	$not_merge = $this->not_merge($order,$party_id); 
                 	$this->log( $order['order_id']."  not_merge -- ".($not_merge == true)); 
                 	if(!$not_merge){
                 		$this_order_can = false; 
                 	}
                 	
                 	$testFacilitySql ="select is_out_ship from ecshop.ecs_order_info oi
                 			           inner join romeo.facility f on f.facility_id = oi.facility_id
                 			           where oi.order_id = {$order['order_id']} limit 1";
                    $facility_status = $this->getMaster()->createCommand($testFacilitySql)->queryScalar();
                    if($facility_status == 'Y'){
                    	$this_order_can = true; 
                    }
                    
                 	
                 }
                 
                  // 乐其跨境 
                 if( intval( $party_id )  == 65638 ){
	                 if($this_order_can){
	                 	// 乐其跨境 订单金额大于 50 在一个表中做了特殊标记 
	                 	$haiguan = $this->isOrderHaiGuan($order['order_id']); 
	                 	if($haiguan){
	                 		$this_order_can = false; 
	                 	}else{
	                 		if($order['order_status'] == '0' && $order['shipping_status'] == '0' && $order['pay_status'] == '2')
	                 		{
	                 			$start = microtime(true);
		                 		/*在乐其跨境下组织下面的店铺未确认待发货已付款的订单，并且税额>50的不需要自动确认，需要拆分
						        * by hzhang1 2015-08-12*/
								$sql = "select eog.order_id,eoi.order_sn,
										cast(case eoi.source_type when 'taobao' then _utf8'tmall' 
											 else _utf8'ntmall' end as char(64)) as source_type,
										eoi.order_time,eoi.order_amount,eoi.taobao_order_sn,d.name,d.distributor_id,md.type,if(sum(eog.goods_price*eog.goods_number*ifnull(bgt.tax_rate,0.1))>50,sum(eog.goods_price*eog.goods_number*ifnull(bgt.tax_rate,0.1)),0) as total
									from ecshop.ecs_order_info eoi
									left JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
									left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id))= bgt.outer_id
									left join ecshop.distributor d on eoi.distributor_id = d.distributor_id
									left join ecshop.main_distributor md on md.main_distributor_id = d.main_distributor_id
									where eoi.order_id ={$order['order_id']} and eoi.party_id = 65638 and eoi.order_status = 0 and eoi.shipping_status = 0 and eoi.pay_status = 2
									group BY eoi.order_id";
								$order_list = $this->getMaster()->createCommand($sql)->queryAll();
								if (!empty ($order_list)) {
									foreach ($order_list as $order_) {
										if($order_['total'] > 50){
											$order_id=$order_['order_id'];
											$sql = "select goods_amount as total,bonus from ecshop.ecs_order_info where order_id = '{$order_id}'";
											$order_bonus = $db->getRow($sql);
											$this->log($order_bonus['total']);
											$sql = "select * from ecshop.ecs_order_goods where order_id = '{$order_id}'";
											$order_goods = $db->getAll($sql);
											$total_rate = 0;
											foreach($order_goods as $goods){
												$good_price_bonus=$goods['goods_price']+$order_bonus['bonus']*$goods['goods_price']/$order_bonus['total'];
												
												$sql ="select ifnull(tax_rate,0.1) as rate from ecshop.ecs_order_goods eog
														left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id)) = bgt.outer_id 
														where eog.rec_id = '{$goods['rec_id']}' limit 1";
												$rate = $db->getRow($sql);
												$total_rate += $goods['goods_number']*$good_price_bonus*$rate['rate'];
											}
											
											if($total_rate > 50){
												$sql = "insert into ecshop.haiguan_order_split(order_id,order_sn,parent_order_id,order_time,goods_amount,taobao_order_sn,name,type,distributor_id,created_stamp,last_updated_stamp,source_type) 
														values('{$order_['order_id']}','{$order_['order_sn']}',0,'{$order_['order_time']}','{$order_['order_amount']}','{$order_['taobao_order_sn']}','{$order_['name']}','{$order_['type']}','{$order_['distributor_id']}',now(),now(),'{$order_['source_type']}')
														ON DUPLICATE KEY UPDATE order_id='{$order_['order_id']}',order_sn='{$order_['order_sn']}',order_time='{$order_['order_time']}',goods_amount='{$order_['order_amount']}',taobao_order_sn='{$order_['taobao_order_sn']}',name='{$order_['name']}',type='{$order_['type']}',distributor_id='{$order_['distributor_id']}',last_updated_stamp=now(),source_type='{$order_['source_type']}'";
												$db->query($sql);
												$this->log($order['order_id']." need to split,insert into the table.");
												$this_order_can = false; 
											}else{
												$this->log($order['order_id'].'step');
											}
										}else{
											$this->log($order['order_id']." don't need to split,continue.");
										}
									}
								}
								echo "[". date('c'). "]  haiguan_order_split spending time：".(microtime(true)-$start)."\n";
							}
	                 	}
	                 	$this->log( $order['order_id']."  is haiguan split -- ".($haiguan == true));  
                	 }
                 }//乐其跨境下拆单逻辑


                 // 通过以上的条件 这该订单可以自动确认
                 if($this_order_can){
                 	   $auto = $this->confirmOrder($order);
                 	if($auto){
                 		$order_succ = $order_succ + 1; 
                 	}
                 	$order_can = $order_can + 1; 
                 }

	    	}
	    }
	    $end = microtime(true);
	    $this->log("  step time ----- ".($end - $sql_end));  
	    return array(
	    	"order_count"=>$order_count,
	    	"order_can" => $order_can,
	    	"order_succ" => $order_succ,
	    	"last_order_id" => $last_order_id
	    	);
    }

    /*
       判断订单的商品库存是否足够
       返回  true  false 
     */
    function isOrderInventoryEnough($order_id){
    	$sql = " SELECT 1 from ecshop.ecs_order_info eoi1
				left join ecshop.ecs_order_goods as og ON eoi1.order_id = og.order_id
				left join romeo.product_mapping as pm ON pm.ecs_goods_id = og.goods_id 
					and pm.ecs_style_id = og.style_id
				left join romeo.inventory_summary as ris ON pm.product_id = ris.product_id 
					and eoi1.facility_id = ris.facility_id and og.status_id = ris.status_id
				LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = og.goods_id 
					and gir.style_id = og.style_id and gir.facility_id = eoi1.FACILITY_ID 
					and gir.party_id = eoi1.party_id
			    where eoi1.order_id = '{$order_id}' and 
			    	(ris.AVAILABLE_TO_RESERVED is null or ris.AVAILABLE_TO_RESERVED-if(gir.reserve_number is NULL or gir.status != 'OK',0,gir.reserve_number) < og.goods_number) 
				limit 1 ";						
	    $not_enough = $this->getMaster()->createCommand($sql)->queryAll();
	    if( empty($not_enough) ){
	    	return true;
	    }
	    return false;
    }

    /**过滤掉淘宝后台已发起退款申请的订单
       过滤掉已发起退款申请的订单 
       返回 true 表示 无退款申请 
    */ 
    function isOrderNoRefund($order_id,$taobao_order_sn){
    	if(trim($taobao_order_sn) == ''){
    		$taobao_refund = 0 ; 
    	}else{
    		$sql = " select 1 from ecshop.sync_taobao_refund as str where str.tid = '{$taobao_order_sn}'  limit 1 "; 
    		$taobao_refund = $this->getMaster()->createCommand($sql)->queryAll(); 
    	}
  
    	if(empty($taobao_refund)){
    		$sql = " select 1 from romeo.refund as re where re.order_id = '{$order_id}' limit 1 "; 
    		$refund = $this->getMaster()->createCommand($sql)->queryAll();  
    		if(empty($refund)){
    			return true; 
    		}
    	}
		return false; 									 
    }

    /**是否是乐其跨境 做了特殊标记的订单 
       返回 true 是做了特殊标记的订单 该订单不需要自动确认 
    */ 
    function isOrderHaiGuan($order_id){
    	/*过滤掉已经存入haiguan_order_split的订单*/
		$sql = "select 1 from  ecshop.haiguan_order_split where order_id = '{$order_id}' limit 1"; 
		$hai = $this->getMaster()->createCommand($sql)->queryAll();  
		if(!empty($hai)){
			return true; 
		}								 
    	return false; 
    }

    /*  订单是否存在合并的可能 
     *  返回 true 不需要合并订单 
     *  跨境业务组 不需要合并订单  party_group 跨境
     */
    function not_merge($order_info,$party_id){
    	if(!empty($this->no_need_merge_partys)){
    		if(in_array($party_id, $this->no_need_merge_partys)){
    			return true; 
    		}
    	}
    	if(!empty($this->can_merge_order_ids)){
    		if(in_array($order_info['order_id'], $this->can_merge_order_ids)){
    			return false; 
    		}
    	}
		return true; 
    }

	/**
	*  自动确认订单 
	*/
	public function actionConfirmOrders($group=0,$party_id=-1,$appkey='',$days = 10) {
		ini_set('max_execution_time', '0');

//		$lock_name = "party_";
//		$lock_file_name = $this->get_file_lock_path($lock_name, 'lock_for_order_batch_validate');
		global $db;
		
//		$fp = fopen($lock_file_name, 'a');
//		$would_block = false;
//		$locked = false;
//		if (!flock($fp, LOCK_EX | LOCK_NB, $would_block)) {
//			$this->log("前一批批量确认订单未结束");
//			fclose($fp);
//			return;
//		}
		try {
			$start = microtime(true);
			$count = 0;			
			$success_count = 0;
			$fail_count = 0;
			$this->log("Auto confirm orders begin ");
			$distributorList=array();
			$lock_name = '业务组'.$party_id;
			if($party_id==-1 && $appkey== ''){
				if($group==0){
				    $lock_name = 'all_party';
				}elseif ( $group==1 ) {
			        $lock_name = 'party_group_one';
		        }elseif ( $group==2 ) {
			        $lock_name = 'party_group_two';
		        }elseif ( $group==3 ) {
			        $lock_name = 'party_group_three';
		        }elseif ( $group==4 ) {
			        $lock_name = 'party_group_four';				
		        }elseif ( $group==5 ) {
			        $lock_name = 'party_group_five';				
		        }
		        $distributorList = $this->getDistributorList($group);
			}else if($appkey== '' && $party_id != -1){
				 $sql = "select party_id,auto_confirm_facility_ids, GROUP_CONCAT(convert(distributor_id using utf8)) as
	           			distributors from ecshop.distributor where status = 'NORMAL' and is_auto_confirm = 1 and party_id = ".$party_id."  group by
	           		    party_id,auto_confirm_facility_ids";
				 $distributorList = Yii::app()->getDb()->createCommand($sql)->queryAll();
			}else if($appkey!= ''){
				 $lock_name = "appkey: ".$appkey;
				 $sql = " SELECT dis.party_id,auto_confirm_facility_ids,dis.distributor_id as distributors
				 		  from ecshop.distributor as dis
                          INNER JOIN ecshop.taobao_shop_conf as tsc on tsc.distributor_id = dis.distributor_id
                          where tsc.application_key = '".$appkey."'";
				 $distributorList = Yii::app()->getDb()->createCommand($sql)->queryAll();
			}  
			$this->log("execute：".$lock_name.":".count($distributorList)." ".date('Y-m-d H:i:s') )	;		
			foreach ($distributorList as $distributor) {				
				try { 
					$auto_confirm_facility_ids = $distributor['auto_confirm_facility_ids']; 
					$oi_facility_id = '';
					$distributors = $distributor['distributors'];
					$distri_id ='';
			        $party_id = $distributor['party_id']; 
			        $ystart=microtime(true);
			       
			       
				
					if( $auto_confirm_facility_ids == 'ALL'){

					}else{
						// 自动确认发货仓库限制 
						$facility_ids = explode(",", $auto_confirm_facility_ids); 
						$facility_ids = implode("','",$facility_ids);
						$facility_ids = "('".$facility_ids."')"; 
						$oi_facility_id = " AND oi.facility_id in {$facility_ids} "; 
					}

                
					$this->log($distributor['party_id'] . "  ready go ");
					$party_start = microtime(true);
					$party_count = 0;
					$party_success_count = 0;
					$party_fail_count = 0;
				    //  每个店铺开始前判断下该业务组的所有可能合并的订单 
				    $sql = " select 
						        count(*) as num,  GROUP_CONCAT(convert(oi.order_id using utf8)) as order_id_str
						    from 
						        ecshop.ecs_order_info oi use index(order_time,order_info_multi_index)
						    where
						        oi.order_time  > date_sub(NOW(), INTERVAL 24 HOUR)   
						        and oi.party_id = {$party_id} 
						        and oi.order_status in (0,1) 
						        and oi.shipping_status=0 
                                and oi.order_type_id in('SALE','SHIP_ONLY','RMA_EXCHANGE')  
						    group by 
						        oi.mobile,oi.tel,oi.consignee,oi.province,oi.city,oi.district,oi.address,oi.nick_name
						    having 
						        num > 1 "; 
				    $merge_sql_start = microtime(true); 
					$party_merge_order_ids = $db->getAll($sql);
                    $merge_sql_end = microtime(true); 
                    $this->log("merge sql time:".($merge_sql_end - $merge_sql_start));
					$this->can_merge_order_ids = array(); 
					if(!empty($party_merge_order_ids)){
						foreach ($party_merge_order_ids as  $value) {
							$tmp_arr = explode(",",$value['order_id_str']); 
							if(!empty($tmp_arr)){
								foreach ($tmp_arr as $one) {
									array_push($this->can_merge_order_ids,$one); 
								}
							}
						}
					}	 
					$this->log("may merge orders: ".json_encode($this->can_merge_order_ids)); 
                    // end 每个店铺开始前扫描出该业务组所有可能合并订单 
                    
                    //查询不需要考虑合并逻辑的业务组  暂时是跨境不需要考虑合并
                    $this->can_merge_order_ids = array();
                    $partys = "select party_id from romeo.party where party_group ='<!--6-->跨境'"; 
                    $this->no_need_merge_partys = $db->getCol($partys);
                    
                    
                    //获取分销商   因为group_concat函数默认长度为1024，所以弃用上面的查询结果                 
                    if($appkey != ''){
			            $distri_id = explode(",", $distributors); 
					    $distri_id = implode("','",$distri_id);
					    $distri_id ="('".$distri_id."')";
					    $distri_id = " AND oi.distributor_id in {$distri_id} "; 
                    }else{
					    $sqlDis_ids = "select distributor_id
			                           from ecshop.distributor
			                           where status = 'NORMAL' and is_auto_confirm = 1
			                           and party_id = {$party_id} and status = 'NORMAL' and is_auto_confirm = 1  
			                           and auto_confirm_facility_ids = '{$auto_confirm_facility_ids}';
			                           "; 
			            $this->log(" sqlDis_ids:".$sqlDis_ids);
			            $dis_ids = Yii::app()->getDb()->createCommand($sqlDis_ids)->queryAll();
			            $distribute_ids =  implode(",",Helper_Array::getCols($dis_ids,'distributor_id'));
						$distri_id = " AND oi.distributor_id in (".$distribute_ids.") ";
						$this->log("  distri_id -----H ".$distri_id);
                    }
					$last_order_id_sql = ''; 
					$STEP_NUM = 300; // 单次查询的订单数  xjye 从260修改为300 2015-10-28
					for($i = 0 ;$i < 10;$i++){
						$r = $this->scanOrdersForConfirmStep($distri_id,$oi_facility_id,$party_id,$last_order_id_sql,$STEP_NUM,$days); 
						$party_success_count  = $party_success_count + $r['order_succ']; 
						$party_count = $party_count + $r['order_can']; 
						$party_fail_count = $party_fail_count +($r['order_can'] - $r['order_succ']);  
					    $last_order_id = $r['last_order_id']; 
					    if($last_order_id !=''){
					    	$last_order_id_sql = " and oi.order_id > {$last_order_id} "; 
					    }
						if($r['order_count'] < ($STEP_NUM - 20 ) ){ 
							break; 
						}
					}
					$count = $count + $party_count; 
					$success_count = $success_count + $party_success_count;
					$fail_count = $fail_count + $party_fail_count; 
				    $this->log(" this party ".$party_id." success_count: " . $party_success_count . " fail_count: " . $party_fail_count." 执行时间：".(microtime(true)-$ystart));
				} catch( Exception $e){
					$this->log(" this distributor error ");
					var_dump($e->getMessage());
				}
			}
			$this->log($lock_name." OrderBatchValidate end time：" . (microtime(true) - $start) . " count: " . $count . " success_count: " . $success_count . " fail_count: " . $fail_count);
		} catch (Exception $e) {
			$this->log("OrderBatchValidate error ");
			var_dump($e->getMessage());
		}
//		flock($fp, LOCK_UN);
//		fclose($fp);

	}

	/**
	平成二十六年十一月十一日
	自动确认订单功能按照大本营要求
	关闭合并订单可能性检测 = 注释掉一坨条件
	开放套餐金额检测，只打上标记
	**/
	public function actionConfirmOrdersFor1111() {
		ini_set('max_execution_time', '0');
		$lock_name = "party_";
		$lock_file_name = $this->get_file_lock_path($lock_name, 'lock_for_order_batch_validate');

		$fp = fopen($lock_file_name, 'a');
		$would_block = false;
		$locked = false;
		if (!flock($fp, LOCK_EX | LOCK_NB, $would_block)) {
			$this->log("前一批批量确认订单未结束");
			fclose($fp);
			return;
		}
		try {
			$start = microtime(true);
			$count = 0;
			$success_count = 0;
			$fail_count = 0;
			$this->log("Auto confirm orders begin ");
			foreach ($this->getDistributorList() as $distributor) {

				$auto_confirm_facility_ids = $distributor['auto_confirm_facility_ids']; 
				$oi_facility_id = '';
				if( $auto_confirm_facility_ids == 'ALL'){

				}else{
					// 自动确认发货仓库限制 
					$facility_ids = explode(",", $auto_confirm_facility_ids); 
					$facility_ids = implode("','",$facility_ids);
					$facility_ids = "('".$facility_ids."')"; 
					$oi_facility_id = " AND oi.facility_id in {$facility_ids} "; 
				}


				$this->log($distributor['distributor_id'] . " " . $distributor['name'] . " ready go ");
				$this->log("facility_id".$oi_facility_id); 
				$party_start = microtime(true);
				$party_count = 0;
				$party_success_count = 0;
				$party_fail_count = 0;

				$query_sql = "select oi.* , IF (
					 	(oi.goods_amount = sum(eog.goods_price * eog.goods_number) and	oi.order_amount = oi.goods_amount + oi.shipping_fee + oi.bonus + oi.pack_fee),
					 	1,0
					) as sinri_set_price_correct
					from ecshop.ecs_order_info as oi
											LEFT JOIN ecshop.ecs_order_goods as eog on eog.order_id = oi.order_id		
											where oi.order_type_id = 'SALE' 
											and oi.order_status = 0 
											and oi.pay_status = 2  
											and oi.distributor_id = " . $distributor['distributor_id']  .
				" and oi.order_time > date_sub(NOW(), INTERVAL 1 day)
											and
											/*过滤掉客户留言的订单*/		
											(
												(oi.postscript is null or oi.postscript = '')
												or
												(
													/*在山的那边海的那边有一个亨氏唯品会*/
													oi.pay_id=249 and TRIM(oi.postscript) = '送货时间不限'
												)
											)
											and
											/*过滤掉港澳台地址的订单*/	
											oi.province not in(3689 ,3688, 3784,0)
											and oi.city <> 0
											/*过滤掉小二留言的订单*/	
											and 			
											(
												not EXISTS 
												(
													SELECT 1 from ecshop.order_attribute oa where oa.order_id = oi.order_id and attr_name = 'TAOBAO_SELLER_MEMO' limit 1
												)
											)
											/*过滤掉库存不足的订单  2015.4.13去掉库存限制，恢复限制2015-4-23*/
											and 			
											(
												not EXISTS 
												(
													SELECT 1 from ecshop.ecs_order_info eoi1
													left join ecshop.ecs_order_goods as og ON eoi1.order_id = og.order_id
													left join romeo.product_mapping as pm ON pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
													left join romeo.inventory_summary as ris ON pm.product_id = ris.product_id and eoi1.facility_id = ris.facility_id and og.status_id = ris.status_id
													where oi.order_id = eoi1.order_id and (ris.AVAILABLE_TO_RESERVED is null or ris.AVAILABLE_TO_RESERVED < og.goods_number) 
													limit 1
												)
											)	
											/*已被扑杀：过滤掉存在可合并的订单*/
											/*过滤掉淘宝后台已发起退款申请的订单*/
											and
											(
												not EXISTS 
												(
													select 1 from ecshop.sync_taobao_refund as str where str.tid = oi.taobao_order_sn limit 1
												)
											)
											/*过滤掉已发起退款申请的订单*/
											and
											(
												not EXISTS 
												(
													select 1 from romeo.refund as re where re.order_id = convert(oi.order_id using utf8) limit 1
												)	
											)	
											group by oi.order_id limit 1000 
											/*已经扑杀：过滤掉套餐价格和订单金额不等的订单*/
										";
				$orders = $this->getMaster()->createCommand($query_sql)->queryAll();
				// print_r($orders);
				$party_query_time = (microtime(true) - $party_start);
				$party_count = count($orders);
				$this->log('query_cost = ' . $party_query_time);
				$this->log('order_count = ' . $party_count);
				if (!empty ($orders)) {
					foreach ($orders as $order) {
						$this->confirmOrder($order,true);
						$party_success_count++;
					}
				}
				$this->log($distributor['distributor_id']  . " " . $distributor['name']. " game over ! time: " . (microtime(true) - $party_start) . " party_query_time: " . $party_query_time . " party_count: " . $party_count . " party_success_count: " . $party_success_count . " party_fail_count: " . $party_fail_count);
				$count += $party_count;
				$success_count += $party_success_count;
				$fail_count += $party_fail_count;

			}
			$this->log($lock_name." OrderBatchValidate end time：" . (microtime(true) - $start) . " count: " . $count . " success_count: " . $success_count . " fail_count: " . $fail_count);
		} catch (Exception $e) {
			$this->log("OrderBatchValidate error ");
			var_dump($e->getMessage());
		}
		flock($fp, LOCK_UN);
		fclose($fp);

	}

	/**
	 * 获得文件锁路径
	 *
	 * @param string $file_name
	 * @return string
	 */
	protected function get_file_lock_path($file_name = '', $namespace = null) {
		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
		}
		if ($namespace == null) {
			preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
			$namespace = $matches[1];
		}
		return ROOT_PATH . "admin/filelock/{$namespace}.{$file_name}";
	}

	/**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */
	protected function getMaster() {
		if (!$this->master) {
			$this->master = Yii :: app()->getDb();
			$this->master->setActive(true);
		}
		return $this->master;
	}

	private function log($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}

	/**
	* 取得启用自动确认订单的组织列表
	* 
	* @return array
	*/
	protected function getPartyList() {
		static $list;
		if (!isset ($list)) {
			$sql = "select distinct p.party_id, p.name
								from romeo.party as p
								where p.status = 'ok' and p.is_auto_confirm = 1";
			$list = $this->getMaster()->createCommand($sql)->queryAll();
		}
		return $list;
	}
	
	/**
	* 取得启用自动确认订单的店铺列表
	* xjye 修改Distributor的查询按party_id,auto_confirm_facility_ids分组 2015-10-28
	* @return array
	*/
	protected function getDistributorList($group) {
		  static $list;
          if(!isset($list))
          {
           	$sql = "select party_id,auto_confirm_facility_ids, GROUP_CONCAT(convert(distributor_id using utf8)) as
           			distributors from ecshop.distributor where status = 'NORMAL' and is_auto_confirm = 1  ";
           		               
            if($group == 0 ){   	// 全部业务组  不做处理
            }
            elseif ($group == 1) {      //金佰利
                echo " 金佰利\n";
            	$sql .= " and party_id in ('65558') ";
            }
            elseif ($group == 2) {     //雀巢  
                echo " 雀巢 \n";
            	$sql .= " and party_id in ('65553') ";
            }
            elseif ($group == 3) {   	//惠氏 康贝 亨氏 安满 金宝贝
                echo " 康贝、中粮、百事、桂格 \n";
            	$sql .= " and party_id in ('65586','65625','65608','65632') ";
            }
            elseif ($group == 4) {      //除了康贝,雀巢,保乐力加,金佰利,金宝贝的其它组织
               echo "除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织\n";
            	$sql .= "  and party_id not in ('65558','65553','65586','65625','65608','65632','65638') ";
            }
            elseif ($group == 5) {      //乐其跨境
               echo "乐其跨境\n";
               $sql .= " and party_id in ('65638') ";

            }
            else {            	// 非法参数
            	echo 'invad $group='.$group."\n";
            	$sql .= " and  1=0 ";
            }
            $sql .=" group by party_id,auto_confirm_facility_ids";
            $this->log($sql);
            $list = Yii::app()->getDb()->createCommand($sql)->queryAll();
        }
        return $list;
	}
	
	
	function confirmOrder($order,$For1111_SetPriceCheckJustTag=false) {
//		$this->log('order_id:' . $order['order_id']);
		global $db;

		// check before confirming
		// 1. 获取订单商品及当前可预订量列表
//		$sql = "SELECT eoi.facility_id, pm.product_id, eog.goods_id, eog.style_id, eog.status_id, eog.goods_name, sum(eog.goods_number) as goods_number, s.AVAILABLE_TO_RESERVED
//				FROM ecshop.ecs_order_info eoi
//				LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
//				LEFT JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
//				LEFT JOIN romeo.inventory_summary s on s.PRODUCT_ID = pm.PRODUCT_ID and s.STATUS_ID = eog.status_id and s.FACILITY_ID = eoi.facility_id
//				where eoi.order_id = '{$order['order_id']}' and eog.status_id = ''
//				GROUP BY eog.goods_id, eog.style_id";
//		$product_list = array();
//		$product_ids = $product_list1=array();
//		$db->getAllRefBy($sql, array('product_id'), $product_ids, $product_list1);
//		foreach ($product_list1['product_id'] as $product_id => $product_list1_with_status) {
//			# code...
//			foreach ($product_list1_with_status as $product) {
//				# code...
//				$product_list[$product_id][$product['status_id']] = $product;
//			}
//		}
//		// 2. 获取订单商品列表中现有已确认未预定订单的预定量gap
//		$product_ids_str = $product_ids['product_id'];
//		$sql = "SELECT pm.product_id, eog.status_id, eog.goods_name, sum(eog.goods_number) as reserved_gap
//				from ecshop.ecs_order_info eoi
//				LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
//				LEFT JOIN romeo.order_inv_reserved_detail oird on oird.order_id = eoi.order_id and oird.order_item_id = eog.rec_id
//				LEFT JOIN romeo.product_mapping pm on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id
//				where eoi.order_type_id='SALE' and eoi.order_status = 1 
//					and eoi.party_id = '{$order['party_id']}' and eoi.facility_id = '{$order['facility_id']}'
//					and pm.product_id ".db_create_in($product_ids_str)." 
//					and (oird.status is null or oird.status='N')
//				GROUP BY pm.product_id, eog.status_id";
//		$gap_data = array();
//		$db->getAllRefBy($sql, array('product_id'), $product_ids, $gap_data);
//		// 3. check
//		if(isset($gap_data['product_id'])){
//			foreach ($gap_data['product_id'] as $product_id => $gap_data_with_status) {
//				# code...
//				foreach ($gap_data_with_status as $gap_data) {
//					# code...
//					if(isset($product_list[$product_id][$gap_data['status_id']])
//						&& $product_list[$product_id][$gap_data['status_id']]['goods_number'] < $product_list[$product_id][$gap_data['status_id']]['AVAILABLE_TO_RESERVED'] - $gap_data['reserved_gap']){
//						continue;
//					}else{
//						$log = "商品[".$gap_data['goods_name']."]可预订量不足.</br>
//								当前可预订量[".$product_list[$product_id][$gap_data['status_id']]['AVAILABLE_TO_RESERVED']."], 即将被其他订单预定量[".$gap_data['reserved_gap']."], 当前订单[".$order['order_id']."]所需量[".$product_list[$product_id][$gap_data['status_id']]['goods_number']."]";
//						$this->log($log);
//						$this->sendMail('AutoConfirm', $log);
//						return false;
//					}
//				}
//			}
//		}

//		/*
//		 * 如果业务组是【乐其跨境，并且订单生成时间与当前时间是在5分钟之内的，就不进行自动确认】
//		 * by hzhang1 2015-10-28
//		 * */
//		$now = date("Y-m-d H:i:s", time());
//		if($order['party_id']=="65638" && (strtotime($now) - strtotime($order['order_time']))< 300 ){
//			return false;
//		}
		//确认
		$sql = "UPDATE ecshop.ecs_order_info 
			set order_status = 1,confirm_time = unix_timestamp(now()) 
			where order_id = " . $order['order_id']. " and order_status = 0 and pay_status = 2  ";
		$confirm_affected_row_count=$db->exec($sql);
		if($confirm_affected_row_count>0){
			$For1111_SetPriceCheckJustTag_desc = ""; 

			if( $For1111_SetPriceCheckJustTag ){
				$For1111_SetPriceCheckJustTag_desc= ( $order['sinri_set_price_correct']==0 )?"2014双十一！套餐金额不等标记":"2014双十一！";
			}else{

			}
			
			$sql = "INSERT into ecshop.ecs_order_action 
						(order_id, action_user, order_status, shipping_status, pay_status, action_time, action_note,invoice_status,shortage_status,note_type)
					values
						({$order['order_id']}, 'system', 1, {$order['shipping_status']}, {$order['pay_status']}, now(), '自动确认订单-{$For1111_SetPriceCheckJustTag_desc}', {$order['invoice_status']}, {$order['shortage_status']}, '')
					";
			$db->query($sql);

			// update_order_mixed_status($order['order_id'], array (
			// 	'order_status' => 'confirmed'
			// ), 'system', '自动确认订单');
			
			$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order['order_id']}, 'AUTO_CONFIRM_ORDERS', '11')");
			$this->log("order_id {$order['order_id']} SUCCESS");

			if($For1111_SetPriceCheckJustTag){
				if($order['sinri_set_price_correct']==0){
					/* 双十一自动确认订单打标，这里都是套餐金额有问题的订单 */
					$db->query("INSERT into ecshop.order_attribute (order_id, attr_name, attr_value) 
						values ({$order['order_id']}, 'AUTO_CONFIRM_ORDERS_SET_PRICE_DIFFER', '1')");
					$this->log("order_id {$order['order_id']} TAGGED in [order_attribute] with AUTO_CONFIRM_ORDERS_SET_PRICE_DIFFER=1 SUCCESS");
				}
			}

			return true;
		}else{
			return false;
		}
	}


    protected function sendMail($subject, $body, $path=null, $file_name=null) {
        require_once(ROOT_PATH. 'includes/helper/mail.php');
        $mail=Helper_Mail::smtp();
        $mail->IsSMTP();                 // 启用SMTP
        $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
        $mail->SMTPAuth = true;         //启用smtp认证
        $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
        $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
        $mail->CharSet='UTF-8';
        $mail->Subject=$subject;
        $mail->SetFrom('erp@leqee.com', '乐其网络科技');
        $mail->AddAddress('cywang@leqee.com', '王聪颖');
        $mail->Body = date("Y-m-d H:i:s") . " " . $body;
        if($path != null && $file_name != null){
            $mail->AddAttachment($path, $file_name);
        }
        try {
            if ($mail->Send()) {
                LogRecord('mail send sucess ');
            } else {
                LogRecord('mail send fail ');
            }
        } catch(Exception $e) {
            // 屏蔽PHP邮箱 版本错误
            //Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475  Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475 
        }
    }
    
      /**
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params)
	{
		if(strnatcasecmp($action,'index')==0)
			return true;
		
		// 加锁
		$lockName="commands.".$this->getName().".".$action.$params[0].$params[1].$params[2];
		if(($lock=Yii::app()->getComponent('lock'))!==null && $lock->acquire($lockName,60*60))
		{
			// 记录命令的最后一次执行的开始时间
			$key='commands.'.$this->getName().'.'.strtolower($action).':start';
			Yii::app()->setGlobalState($key,microtime(true));
			return true;	
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] 命令{$action}正在被执行，或上次执行异常导致独占锁没有被释放，请稍候再试。\n";
			return false;
		}
	}
	
	/**
	 * 执行完毕后执行
	 * 
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action,$params)
	{	
		if(strnatcasecmp($action,'index')==0)
			return;

		// 记录命令的最后一次执行的完毕时间
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		Yii::app()->setGlobalState($key,microtime(true));
		
		// 释放锁
		$lockName="commands.".$this->getName().".".$action.$params[0];
		$lock=Yii::app()->getComponent('lock');
		$lock->release($lockName);
	}
}
?>
