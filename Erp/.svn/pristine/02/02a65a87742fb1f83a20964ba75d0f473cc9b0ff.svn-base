<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/lib_main.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-11-03
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpAutoGenerateBPSNCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	private $master; // Slave数据库

	// 自动推荐
	public function actionAutoGenerateBPSN($party_id, $product_key_size, $max_shipment_size) {
		ini_set('max_execution_time', '0');
		
		$lock_name = "party_{$party_id}";
	    $lock_file_name = $this->get_file_lock_path($lock_name, 'lock_for_generate_bpsn');
	    
		$fp = fopen($lock_file_name,'a');
		$would_block = false;
		$locked = false;
		if(!flock($fp,LOCK_EX|LOCK_NB,$would_block))
		{
			echo("前一批拣单未生成\n");
		}
		else
		{
			$this->AutoGenerate($party_id, $product_key_size, $max_shipment_size);
			flock($fp,LOCK_UN);
		}
		fclose($fp);
		return;
	}
		
	public function AutoGenerate($party_id, $product_key_size, $max_shipment_size) {
		$sql = "
		    select 
		        oi.party_id,oi.facility_id,oi.shipping_id,es.default_carrier_id
		    from
		        ecshop.ecs_order_info oi
		        inner join ecshop.ecs_shipping es ON oi.shipping_id = es.shipping_id
		        inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
		        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
		        inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
		    where
		        r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
		        and oi.party_id = $party_id and oi.shipping_status = '0' and oi.order_status = '1'
		        group by oi.party_id,oi.facility_id,s.carrier_id order by oi.party_id desc";        
		        
		$party_facility = $this->getSlave()->createCommand($sql)->queryRow();
		print_r("[query_party_acility]\n");
		print_r("	".$sql."\n");
		
		if($party_facility)
		{
			$carrier_id = $party_facility['default_carrier_id'];
			$facility_id = $party_facility['facility_id'];
			print_r('	party_facility:');
			print_r($party_facility);
			
			$start=microtime(true);
			$shipments_counts_of_specific_product_key = array();
			$hot_goods_condition = array();
			$this->GetHotGoodsInfo($shipments_counts_of_specific_product_key, $hot_goods_condition,
				$party_id, $carrier_id, $facility_id,
				$product_key_size, $max_shipment_size);
			$query_hot_goods_condition_end = microtime(true);
			print_r("GetHotGoodsInfo lasts(s): ".($query_hot_goods_condition_end - $start)."\n");

			$shipment_list = $this->ChooseSpecificShipments(
				$hot_goods_condition, $party_id, $carrier_id, $facility_id,
				$max_shipment_size);
			$specific_goods_choose_end = microtime(true);
			print_r("ChooseSpecificShipments lasts(s): ".($specific_goods_choose_end - $query_hot_goods_condition_end)."\n");

			if ($shipment_list) {
			    // 对发货单按组合商品数量排序
				$sort_nums = array();
				$sort_product = array();
				$shipment_ids = array();
				foreach($shipment_list as $key=>$shipment) {
					if(empty($shipments_counts_of_specific_product_key[$shipment['product_key']])) {
						$shipments_counts_of_specific_product_key[$shipment['product_key']] = 0;
					}
					$shipment_list[$key]['sort_num'] = $shipments_counts_of_specific_product_key[$shipment['product_key']];
					$sort_nums[] = $shipments_counts_of_specific_product_key[$shipment['product_key']];
					$sort_product[] = $shipment['product_key'];
					if(in_array($shipment['SHIPMENT_ID'], $shipment_ids))
						die('repeated shipment_id: '.$shipment['SHIPMENT_ID']);
					$shipment_ids[] = $shipment['SHIPMENT_ID'];
				}
				// 数量多的排前面
				array_multisort( $sort_nums, SORT_DESC, $sort_product, SORT_ASC, $shipment_list);
				$array_multisort_end = microtime(true);
				print_r("array_multisort lasts(s): ".($array_multisort_end - $specific_goods_choose_end)."\n");
							    
			    $available_orders = array();
			    $available_shipment_ids = array();
			    $this->CheckReserveStatus($shipment_ids, $available_orders, $available_shipment_ids);
				$filter_orders_end = microtime(true);
				print_r("CheckOrdersResStatus lasts(s): ".($filter_orders_end - $array_multisort_end)."\n");

			    
			    $sorted_products = $this->SortProducts($party_id, $facility_id, $available_shipment_ids);
				$sort_products_end = microtime(true);
				print_r("SortProducts lasts(s): ".($sort_products_end - $filter_orders_end)."\n");
				
				$this->AutoGroundingLackedGoods($party_id, $facility_id, $available_shipment_ids);
				$auto_grounding_lacked_goods_end = microtime(true);
				print_r("AutoGroundingLackedGoods lasts(s): ".($auto_grounding_lacked_goods_end - $sort_products_end)."\n");

				$shipment_ids_for_check = array_unique($shipment_ids);
			   	if(count($shipment_ids) != count($shipment_ids_for_check))
			   	{
			   		print_r($shipment_ids);
			   		die('repeated shipment_id');
			   	}
			   	$lacked_goods_barcodes = array();
			   	if($this->check_if_enough_on_location_for_shipments($shipment_ids, $lacked_goods_barcodes))
			   		die('error found in check_if_enough_on_location_for_shipments');
			   	
			    $bpsn = $this->record_shipments_to_batch_pick($shipment_ids, "cronjob");
				$record_shipments_to_batch_pick_end = microtime(true);
				print_r("record_shipments_to_batch_pick lasts(s): ".($record_shipments_to_batch_pick_end - $sort_products_end)."\n");
			    if($bpsn['bpsn'] != 0) {
			    	$this->get_batch_pick_path_merged($bpsn['bpsn']);
			    	print_r("生成bpsn: ".$bpsn['bpsn']."\n");
					$get_batch_pick_path_merged_end = microtime(true);
					print_r("get_batch_pick_path_merged lasts(s): ".($get_batch_pick_path_merged_end - $record_shipments_to_batch_pick_end)."\n");
			    } else {
		    		print_r('生成批捡单失败'."\n");
		       		//print_r('shipment_listV5_for_1111 batch_pick basic_info:'.$tongji.' get_batch_pick_path_merged $bpsn:'.$bpsn['bpsn'].' cost_time:'.$cost_time2);
		    	}
			}
    		print_r("本次生成总耗时 ".(microtime(true)-$start)."s"."\n");
		}
		else
		{
    		print_r("找不到party_facility\n");
		}
	}
	
		/**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */
	protected function getSlave() {
		if (!$this->slave) {
			if (($this->slave = Yii :: app()->getComponent('slave')) === null)
				$this->slave = Yii :: app()->getDb();
			$this->slave->setActive(true);
		}
		return $this->slave;
	}
	    /**
     * 取得master数据库连接	
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app ()->getDb ();
            $this->master->setActive ( true );
        }
        return $this->master;
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
		return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
	}
	
	/**
	 * 创建像这样的查询: "IN('a','b')";
	 *
	 * @access   public
	 * @param    mix      $item_list      列表数组或字符串
	 * @param    string   $field_name     字段名称
	 * @author   Xuan Yan
	 *
	 * @return   void
	 */
	protected function db_create_in($item_list, $field_name = '')
	{
	    if (empty($item_list))
	    {
	        return $field_name . " IN ('') ";
	    }
	    else
	    {
	        if (!is_array($item_list))
	        {
	            $item_list = explode(',', $item_list);
	        }
	        $item_list = array_unique($item_list);
	        $item_list_tmp = '';
	        foreach ($item_list AS $item)
	        {
	            $item = trim($item);
	            if ($item !== '')
	            {
	                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
	            }
	        }
	        if (empty($item_list_tmp))
	        {
	            return $field_name . " IN ('') ";
	        }
	        else
	        {
	            return $field_name . ' IN (' . $item_list_tmp . ') ';
	        }
	    }
	}
	
	protected function GetHotGoodsInfo(&$shipments_counts_of_specific_product_key, &$hot_goods_condition,
		$party_id, $carrier_id, $facility_id, $product_key_size, $max_shipment_size)
	{
		// 热门商品筛选 或者 具体的某个组合（单品）
		$sql = "select res.product_key,res.product_nums,count(res.shipment_id) as shipment_nums
			from (
			select os.shipment_id,count(DISTINCT(pm.product_id)) as product_nums,group_concat(DISTINCT(pm.product_id) order by pm.product_id) as product_key
			from ecshop.ecs_order_info oi
			inner join ecshop.ecs_order_goods og ON oi.order_id =og.order_id
			inner join romeo.product_mapping pm ON og.goods_id =pm.ecs_goods_id and og.style_id= pm.ecs_style_id
			inner join romeo.order_shipment os ON convert(oi.order_id using utf8)=os.order_id
			inner join romeo.shipment s ON os.shipment_id = s.shipment_id
			inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
			where 
			        r.STATUS = 'Y' and oi.shipping_status = '0' and oi.order_status = '1' 
			        and s.carrier_id='{$carrier_id}' and oi.facility_id = '$facility_id'
			        -- 未批捡
			        and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = os.shipment_id limit 1)
		        	and r.PARTY_ID = $party_id 
			group by os.shipment_id
			) as res 
			group by res.product_key
			having res.product_nums >= 1 
			order by shipment_nums desc
			limit $product_key_size
		";
		print_r('GetHotGoodsInfo sql:'.$sql."\n");
		$product_keys = $this->getSlave()->createCommand($sql)->queryAll();
		$hot_product_keys = array();
		$shipment_sum = 0;
		foreach($product_keys as $key=>$product_key) {
			if($shipment_sum > $max_shipment_size) {
				break;
			}
			$shipment_sum  += $product_key['shipment_nums'];
			$hot_product_keys[] = $product_key['product_key'];
			$shipments_counts_of_specific_product_key[$product_key['product_key']] = $product_key['shipment_nums'];// 对product_key的优先级进行赋值
		}
		if(!empty($hot_product_keys)) {
			$hot_goods_condition = " having product_key ".$this->db_create_in($hot_product_keys);
		}
		print_r('$hot_goods_condition: '.$hot_goods_condition."\n");
		
		return $hot_goods_condition;
	}
	
	protected function ChooseSpecificShipments($hot_goods, $party_id, $carrier_id, $facility_id, $max_shipment_size)
	{
		// 开始具体的筛选
		$sql_from = "
		    select 
		        s.SHIPMENT_ID,s.CARRIER_ID,s.PARTY_ID,s.PRIMARY_ORDER_ID, 
		        count(distinct(pm.product_id)) as sku_num, 
		        -- group_concat(distinct(pm.product_id) order by pm.product_id) as sku_num,
		        group_concat(distinct(pm.product_id) order by pm.product_id) as product_key,
		        if(oi.handle_time = 0, oi.order_time, FROM_UNIXTIME(oi.handle_time)) handle_time
		    from
		        ecshop.ecs_order_info oi
		        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
		        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
		        inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
		        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
		        inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
		    where
		        r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
		        and oi.shipping_status = '0' and oi.order_status = '1'
		        and s.carrier_id='{$carrier_id}' and oi.facility_id = '$facility_id' and oi.party_id = '$party_id'
		        -- 订单处理时间已到
		        and (oi.handle_time = 0 or oi.handle_time < UNIX_TIMESTAMP())
		        -- 未批捡
		        and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = s.shipment_id limit 1)
		        -- 所有订单都预定了
	        	and not exists (select 1 from romeo.order_shipment os2 left join romeo.order_inv_reserved r2 ON r2.order_id=cast(os2.order_id as UNSIGNED)
                            where os2.shipment_id=s.shipment_id and r2.status !='Y' limit 1)
		        group by 
		        s.SHIPMENT_ID
		    -- 在热门商品里面
		    $hot_goods
		    limit $max_shipment_size
		";
		print_r("ChooseSpecificShipments SQL: ".$sql_from."\n");
		$ref_fields=$ref_rowset=array();
		$list=$this->getSlave()->createCommand($sql_from)->queryAll();
		return $list;
	}

	protected function CheckReserveStatus($shipment_ids, &$available_orders, &$available_shipment_ids)
	{
		// 取得Shipment对应的订单列表
	    // 判断Shipment对应的订单，是不是每个订单都已经预订上了
	    // 如果Shipemt中有一个订单没有预定库存（合并发货），说明这个Shipment不能发货
	    // 合并发货订单会有多条记录
	        
		$sql="
        	select 
            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.facility_id,o.shipping_id,o.shipping_name,
            o.order_time,o.order_sn,o.consignee,o.distributor_id,
        	IF( o.order_type_id = 'SALE', 
        		(	SELECT 	a.action_time
					FROM 	ecshop.ecs_order_action a
					WHERE 	a.order_id =  o.order_id
					AND 	a.order_status = '1'
					AND NOT EXISTS (
						SELECT 		b.action_time
						FROM 		ecshop.ecs_order_action b
						WHERE 		b.order_id = o.order_id
						AND 		b.order_status =  '2'
						AND 		b.action_time > a.action_time
						ORDER BY 	b.action_time DESC 
						LIMIT 1
					)
					ORDER BY a.action_time ASC 
					LIMIT 1
        		), 
        		o.order_time ) AS confirm_time,
            r.STATUS as RESERVED_STATUS, r.RESERVED_TIME AS reserved_time, m.SHIPMENT_ID
        from
            romeo.order_shipment m
            left join romeo.order_inv_reserved r on r.ORDER_ID = m.ORDER_ID
            left join ecshop.ecs_order_info as o on o.order_id = m.ORDER_ID
        where
            m.SHIPMENT_ID ".$this->db_create_in($shipment_ids);
	        
	    
	    print_r('CheckReserveStatus SQL: '.$sql."\n");
		$available_orders = $this->getSlave()->createCommand($sql)->queryAll();	    
		
		$available_shipment_ids = array();
	    foreach($available_orders as $key => $item)
	    {
	    	if(!in_array($item['SHIPMENT_ID'], $available_shipment_ids))
	    		$available_shipment_ids[] = $item['SHIPMENT_ID'];
	    }
	}
	
	protected function SortProducts($party_id, $facility_id, $shipment_ids)
	{
				    	
	    // 如果需要统计商品数量排行
	    $sql  = "select res.goods_name,res.product_key,count(res.shipment_id) as shipment_count
				from
				(select 
				        group_concat(distinct(g.goods_name) separator '</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') as goods_name,s.shipment_id,group_concat(distinct(pm.product_id) order by pm.product_id) as product_key
				    from
				        ecshop.ecs_order_info oi
				        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
				        left  join ecshop.ecs_goods g ON og.goods_id = g.goods_id
				        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				        inner join romeo.order_shipment m on m.ORDER_ID=oi.ORDER_ID
				        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
				    where oi.party_id = $party_id and s.shipment_id ".$this->db_create_in($shipment_ids).
				" group by s.shipment_id 
				) as res
	            group by res.product_key
	            order by shipment_count desc";
		print_r('SortProducts SQL: '.$sql."\n");
		return $this->getSlave()->createCommand($sql)->queryAll();
	}

	/**
	 * 根据查询条件过滤订单列表
	 *
	 * @param array $list 订单列表
	 */
	protected function shipment_list_filter(& $list, $PARTY_ID_IN_REQUEST,$facility_ids/*, $filter = array()*/) {
	    if (empty($list) || empty($filter)) return;
	
		$error_shipments=array();
		// 批量处理好，得到单个shipment_id容器预定数量不够的
		$check_shipment_ids = array();
	    foreach($list as $key=>$value) {
	    	$check_shipment_ids[] = $list[$key]['SHIPMENT_ID'];
	    }
	    $esid=$this->check_shipments_tariruka($check_shipment_ids,$PARTY_ID_IN_REQUEST,$facility_ids);
	    $error_shipments = $esid;
	
	    /*foreach ($list as $key => $item) {
	        $flag = true;
	
	        if(in_array($item['SHIPMENT_ID'],$esid)) {
	        	$flag=false;
	        	$error_shipments[] = $item['SHIPMENT_ID'];
	        }
	
	        if ($flag && isset($filter['party_id'])) {
	        	$flag = $flag && ($item['PARTY_ID'] == $filter['party_id']);
	        }
			if ($flag && isset($filter['facility_ids'])) {
				$flag_f=false;
				foreach($filter['facility_ids'] as $ffi){
					$flag_f=$flag_f || ($item['FACILITY_ID'] == $ffi);
				}
				$flag=$flag && $flag_f;
			}
	        if ($flag && isset($filter['carrier_id'])) {
	            $flag = $flag && ($item['CARRIER_ID'] == $filter['carrier_id']);
	        }
			if ($flag && isset($filter['distributor_ids'])) {
				$flag_d=false;
				foreach($filter['distributor_ids'] as $fdi){
					$flag_d = $flag_d || ($item['DISTRIBUTOR_ID'] ==  $fdi);
				}
				$flag=$flag && $flag_d;
			}
			
	        if (!$flag) {
	            unset($list[$key]);
	        }
	    }*/
	    
	    return $error_shipments;
	}
		
		
		
		
	protected function check_if_enough_on_location_for_shipments($shipment_id_array, &$lacked_goods_info_list){
		$shipment_ids_string = join(',', $shipment_id_array);
		$sql = "SELECT barcode, gb.product_id
				FROM
					(select  if(ISNULL(egs.barcode) or egs.barcode = '',eg.barcode, egs.barcode) as barcode, p.product_id, eog.goods_number as order_goods_number
						from romeo.shipment s
						LEFT JOIN romeo.order_shipment os on s.shipment_id = os.SHIPMENT_ID
						LEFT JOIN ecshop.ecs_order_goods eog on os.order_id = eog.order_id
						LEFT JOIN ecshop.ecs_goods eg on eg.goods_id = eog.goods_id
						LEFT JOIN ecshop.ecs_goods_style egs on egs.goods_id = eog.goods_id and egs.style_id = eog.style_id and egs.is_delete=0
						LEFT JOIN romeo.product p on p.ecs_goods_id = eg.goods_id and p.ecs_style_id = egs.style_id
						WHERE s.shipment_id in($shipment_ids_string)) gb
				LEFT JOIN romeo.inventory_location il on gb.barcode = il.goods_barcode
				WHERE inventory_location_id is NULL or order_goods_number > il.goods_number 
				GROUP BY barcode, gb.product_id";
		
		$lacked_goods_info_list = $this->getMaster()->createCommand($sql)->queryAll();
		print_r("check_if_enough_on_location_for_shipments SQL: ".$sql);
		print_r($lacked_goods_info_list);
		return count($lacked_goods_info_list);
	}
	/**
	给一批Shipment，检查其是否已经被批拣登记。
	if $shipment_id_array is not all free, return 0; or false;
	shipment[]中没有被登记的shipment_id，则生成一个batch_pick_sn，于batch_pick表立项,并将相应batch_pick_sn和shipment_id写入batch_pick_mapping
	接口定义中间件实现 ljni@i9i8.com
	监工幕府的WebService接口，已经按照返回值示例填满，但是还没有在ROMEO里测过
	CHECKED ON 20130911
	USED IN
	Deal_Batch_Pick.php
	**/
	protected function record_shipments_to_batch_pick($shipment_id_array,$AU){
	  $p=array();
	  $p['actionUser']=$AU;
	  $p['shipmentIds']=array();
	  foreach ($shipment_id_array as $key => $value) {
	     $p['shipmentIds'][]=$value;
	  }
	  if(isset($handle))unset($handle);
	  $client = Yii :: app()->getComponent('romeo')->InventoryService;
	  $result=$client->createBatchPickMapping($p);
	  if(isset($result->return->entry)){
	    $entries=$result->return->entry;
	    foreach ($entries as $no => $entry) {
	      if ($entry->key=="batchPickSn"){
	        $sn=$entry->value->stringValue;
	        if(isset($sn)){
	          return array('bpsn'=>$sn);
	        }
	      } else if ($entry->key=="error"){
	        $msg1=$entry->value->stringValue;
	      } else if($entry->key=="goodsNotEnoughList"){
	        $msg2=$entry->value->stringValue;
	      }
	    }
	  }
	  print_r("record_shipments_to_batch_pick($shipment_id_array,$AU) FAIL $msg1 CAUSED BY LACKING $msg2"."\n");
	  return array('bpsn'=>0,'error'=>$msg1.'<br>'.$msg2);
	}
	
	/**
	专用于打印的批拣路径生成获取,按商品汇总
	CHECKED ON 20131026
	USED IN
	print_batch_pick.php
	**/
	protected function get_batch_pick_path_merged($batch_pick_sn) {
	  if(isset($handle))unset($handle);
	  
	  $client = Yii :: app()->getComponent('romeo')->InventoryService;
	  $result=$client->getDynamicBatchPickPathAndReserve(array('batchPickSn' => $batch_pick_sn));
	  print_r("get_batch_pick_path_merged($batch_pick_sn) called"."\n");
	
	  //get data
	  if(isset($result->return->entry)){
	      $entries=$result->return->entry;
	      foreach ($entries as $no => $entry) {
	        if ($entry->key=="itemList"){
	          $json=$entry->value->stringValue;
	          break;
	        }
	      }
	    }
	
	
	  $jsoned=json_decode($json,true);
	  
	  $res=array();
	  $record_count=0;
	  $BP_records_new = array();
	  foreach ($jsoned as $lb => $lb_box) {
	  	$location_barcode = $lb_box['locationBarcode'];
	  	if(!array_key_exists($location_barcode,$BP_records_new))
	  	{  		
	  		$BP_records_new[$location_barcode]['location_barcode'] = $location_barcode;
		    //$BP_records_new[$location_barcode]['row_span'] = 0;
	  		$BP_records_new[$location_barcode]['goods_list'] = array();
	  	}
	  	
	  	$goods_info_list = $lb_box['goodsInfo'];
	    //遍历goods_info_list
	    foreach ($goods_info_list as $goods_info) {
	    	$product_id = $goods_info['productId'];
	    	if(!array_key_exists($product_id,$BP_records_new[$location_barcode]['goods_list']))
	    	{
		      //1. basic info
		        //$BP_records_new[$location_barcode]['goods_list'][$product_id]['row_span'] = 0;
		        $BP_records_new[$location_barcode]['goods_list'][$product_id]['productId'] = $product_id;
		        $BP_records_new[$location_barcode]['goods_list'][$product_id]['barcode'] = $goods_info['barcode'];
	    	    $BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsName'] = $goods_info['goodsName'];
	    	    $BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsNumber'] = 0;
	    		$BP_records_new[$location_barcode]['goods_list'][$product_id]['grids'] = array();
	    	}
		  		
		  	//2. shipment
	      	foreach ($goods_info['shipmentNum'] as $shipment) {
	        	# code...
	        	$BP_records_new[$location_barcode]['goods_list'][$product_id]['grids'][$shipment['gridId']]
	        		= array('shipment_id' => $shipment['shipmentId'], 
	        		'number' => $shipment['gridNum']);
	       		$BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsNumber'] += $shipment['gridNum'];
	       		//$BP_records_new[$location_barcode]['row_span']++;
	       		//$BP_records_new[$location_barcode]['goods_list'][$product_id]['row_span']++;
	       		$record_count++;
	      	}
	      	ksort($BP_records_new[$location_barcode]['goods_list'][$product_id]['grids']);
	    }
	  }
	  return $BP_records_new;
	}

	protected function AutoGroundingLackedGoods($party_id, $facility_id, $shipment_ids)
	{
		
		$lacked_goods_info_list = array();
		if($this->check_if_enough_on_location_for_shipments($shipment_ids, $lacked_goods_info_list))
		{
			foreach($lacked_goods_info_list as $goods_info) {
				$this->auto_grouding_location($party_id,$facility_id,$goods_info['product_id'],$goods_info['barcode'],'2J-C-02-61');
			}
		}
	}

	/**
	蔵に予定された品物はまことに棚に用意されていてそして十分足りるかと判断すること。
	見よ、この世の罪びとを一掃し裁きを下さるものは、その方の足音はもう聞こえる。
	ljni@i9i8.com
	20130826
	CHECKED ON 20130911
	USED IN
	shipment_listV5.php
	**/
	protected function check_shipments_tariruka($shipment_ids,$PARTY_ID,$facility_ids){
	  $result=$this->get_shipments_takiruka_info_single($shipment_ids,$PARTY_ID,$facility_ids);
	  $err_sids=array();
	  foreach ($result as $pi => $tarinu_sinamono) {
	    foreach ($tarinu_sinamono as $key => $value) {
	      if($key=='SIDS'){
	        $sids = preg_split("/[\s,]+/", $value);
	        foreach ($sids as $k => $sid) {
	          $err_sids[]=$sid;
	        }
	      }
	    }
	  }
	  return $err_sids;
	}
	
	/**
	 * 精确到单个shipment_id级别
	品物の予定要請を出した配送リストの対応する品物が不足かどうか、ここでチェックしよう。
	返すのは、品物番号に組み合わせた情報リストです。もちろん、不足に困った配送リストの番号も提供いたします。
	ljni@i9i8.com
	20130826
	CHECKED ON 20130911
	USED BY check_shipments_tariruka
	IN
	shipment_listV5.php
	**/
	protected function get_shipments_takiruka_info_single($shipment_ids,$PARTY_ID,$facility_ids){
	  
	  $facility_ids_string = join(',', $facility_ids);
	  $shipment_ids_string = join(',', $shipment_ids);
	  	  
	  $sql="SELECT
	          if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) as barcode,
	          g.goods_name,
	          sum(og.goods_number) ordergoodsnumber,
	          ifnull(
	            (
	              SELECT
	                sum(available_to_reserved)
	              FROM
	                romeo.inventory_location il
	              LEFT JOIN romeo.location AS loc ON loc.location_barcode = il.location_barcode
	              WHERE
	                il.status_id = 'INV_STTS_AVAILABLE'
	              AND loc.location_type = 'IL_LOCATION'
	              AND il.product_id = pm.product_id  
	              ".
	              ((isset($PARTY_ID) && $PARTY_ID!=null)?"AND il.party_id='$PARTY_ID'":"")
	              ." 
	              AND il.facility_id IN ($facility_ids_string)
	            ),
	            0
	          ) locationgoodsnumber,
	          group_concat(s.shipment_id) AS SIDS
	        FROM
	          romeo.shipment s
	        LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
	        LEFT JOIN ecshop.ecs_order_goods og ON os.order_id = og.order_id
	        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id
	        AND og.style_id = pm.ecs_style_id
	        LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
	        LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
	        WHERE
	          s.shipment_id IN ('$shipment_ids_string')
	        group by pm.product_id,s.shipment_id
	    having ordergoodsnumber > locationgoodsnumber;";
	  print_r("get_shipments_takiruka_info_single sql: ".$sql);
	  $result= $this->getSlave()->createCommand($sql)->queryAll();
	  return $result;
	}

	/**
	品物の予定要請を出した配送リストの対応する品物が不足かどうか、ここでチェックしよう。
	返すのは、品物番号に組み合わせた情報リストです。もちろん、不足に困った配送リストの番号も提供いたします。
	ljni@i9i8.com
	20130826
	CHECKED ON 20130911
	USED BY check_shipments_tariruka
	IN
	shipment_listV5.php
	**/
	protected function get_shipments_takiruka_info($shipment_ids,$PARTY_ID,$facility_id){
	  global $db;
	  $sql0="SELECT
	          if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) as barcode,
	          g.goods_name,
	          sum(og.goods_number) ordergoodsnumber,
	          ifnull(
	            (
	              SELECT
	                sum(available_to_reserved)
	              FROM
	                romeo.inventory_location il
	              LEFT JOIN romeo.location AS loc ON loc.location_barcode = il.location_barcode
	              WHERE
	                il.status_id = 'INV_STTS_AVAILABLE'
	              AND loc.location_type = 'IL_LOCATION'
	              AND il.product_id = pm.product_id  
	              ".
	              ((isset($PARTY_ID) && $PARTY_ID!=null)?"AND il.party_id='$PARTY_ID'":"")
	              ." 
	              AND il.facility_id = '$facility_id'
	            ),
	            0
	          ) locationgoodsnumber,
	          pm.product_id,
	          group_concat(s.shipment_id) AS SIDS
	        FROM
	          romeo.shipment s
	        LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
	        LEFT JOIN ecshop.ecs_order_goods og ON os.order_id = og.order_id
	        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id
	        AND og.style_id = pm.ecs_style_id
	        LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
	        LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
	        WHERE
	          s.shipment_id IN (";
	  $sids=array();
	  foreach ($shipment_ids as $key => $value) {
	    $sids[]="'".$value."'";
	  }
	  $sql1=join(',',$sids);
	  $sql2=")
	        group by pm.product_id
	    having ordergoodsnumber > locationgoodsnumber;";
	  $sql=$sql0.$sql1.$sql2;
	  $result=$this->getSlave()->createCommand($sql)->queryAll();
	  return $result;
	}
	
	/**
	 * 上库位特殊功能，慎用
	 * ljzhou 2013-10-30
	 */
	protected function auto_grouding_location($party_id,$facility_id,$product_id,$goods_barcode,$location_barcode) {
		$sql = "INSERT INTO `romeo`.`inventory_location` (location_barcode,is_serial,goods_barcode,product_id,
		goods_number,available_to_reserved,validity,party_id,facility_id,status_id,action_user,created_stamp,
		last_updated_stamp)values ('$location_barcode','0', '$goods_barcode', '$product_id','50000','50000','1970-01-01 00:00:00',
	    $party_id,$facility_id,'INV_STTS_AVAILABLE','system',now(),now())";
	    print_r('auto_grouding_location:'.$sql);
	  	$this->getMaster()->createCommand($sql)->query();
	  	
	  	//check
	  	$sql = "select * from romeo.inventory_location where goods_barcode = '$goods_barcode'";
	  	$result=$this->getSlave()->createCommand($sql)->queryAll();
	  	print_r('grouding_result');
	  	print_r($result);
	}


	
	  	/*$sql = "select oi.party_id,oi.facility_id,pm.product_id,bpm.shipment_id, " .
	  			"sum(og.goods_number) as sum_goods_number, " .
	  			"ifnull(gs.barcode,g.barcode) as barcode,g.goods_name, " .
	  			"ifnull((select  sum(ilr.reserved_quantity) " .
	  			"			from romeo.inventory_location_reserve ilr" .
	  			"			inner join romeo.inventory_location il on ilr.inventory_location_id=il.inventory_location_id " .
	  			"			where ilr.batch_pick_sn='131107-0412' and il.product_id=pm.product_id and ilr.shipment_id=bpm.shipment_id) ,0)" .
	  			" 	as already_reserved_quantity " .
	  			"from romeo.batch_pick bp " .
	  			"inner join romeo.batch_pick_mapping bpm on bp.batch_pick_sn = bpm.batch_pick_sn " .
	  			"inner join romeo.order_shipment os on bpm.shipment_id = os.shipment_id " .
	  			"inner join ecshop.ecs_order_info oi on os.order_id=oi.order_id " .
	  			"inner join ecshop.ecs_order_goods og on oi.order_id=og.order_id " .
	  			"inner join romeo.product_mapping pm on og.goods_id=pm.ecs_goods_id and og.style_id=pm.ecs_style_id " .
	  			"inner join ecshop.ecs_goods g on og.goods_id = g.goods_id " .
	  			"left join ecshop.ecs_goods_style gs on og.style_id=gs.style_id and og.goods_id=gs.goods_id " .
	  			"where bp.batch_pick_sn = '131107-0412' " .
	  			"group by oi.party_id,oi.facility_id,pm.product_id,bpm.shipment_id " .
	  			"order by oi.party_id,oi.facility_id,pm.product_id,sum_goods_number";*/
}

?>
