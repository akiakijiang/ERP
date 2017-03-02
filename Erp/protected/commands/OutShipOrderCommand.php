<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH.'RomeoApi/lib_RMATrack.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
include_once ROOT_PATH . 'admin/function.php';
require_once(ROOT_PATH.'includes/debug/lib_log.php');
 

class OutShipOrderCommand extends CConsoleCommand {
	
    private $master; // Master数据库    
    private $lock;
    private $batch_length = 100;
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
    	$this->log("begin");
        $this->run(array('DoTask'));
        
        $this->run(array('CreateBatchPick'));
        $this->log("end");
    }
    
    
    public function actionDoTask($party_id = null) {
    	$this->log("begin DoTask");
    	$start = microtime(true);
        $sql = "
        	select t.task_id, t.party_id, t.outer_id, t.facility_id, t.shipping_id, t.out_ship_number, t.goods_number, 
        	t.start_time, t.end_time, t.create_user, t.province, t.city
        	from ecshop.ecs_out_ship_order_task t 
        	where t.status = 'INIT' 
        ";
        if ($party_id) {
        	$sql .= " and t.party_id = '{$party_id}'";
        }
        $tasks = $this->getMaster()->createCommand ($sql)->queryAll();
        $total = 0;
        if (! $tasks || empty($tasks)) {
        	$this->log("tasks empty");
        } else {
	        foreach ($tasks as $task) {
	        	$this->log("begin task: {$task['task_id']} party_id: {$task['party_id']}");
	        	
	        	$sql = "select task_id from ecshop.ecs_out_ship_order_task where task_id = '{$task['task_id']}' and status = 'INIT' limit 1 ";
	        	$t_id = $this->getMaster()->createCommand ($sql)->queryScalar();
	        	$sql = "select task_id from ecshop.ecs_out_ship_order where task_id = '{$task['task_id']}' limit 1 ";
	        	$to_id = $this->getMaster()->createCommand ($sql)->queryScalar();
	        	//检查锁
	        	if ($this->isLocked($task['outer_id'].'_'.$task['facility_id'].'_'.$task['shipping_id'])) {
	        		$this->log("task: {$task['task_id']}, outer_id: {$task['outer_id']} facility_id: {$task['facility_id']} shipping_id: {$task['shipping_id']} locked!" );
	        	} else if (! $t_id) {
	        		$this->log("task: {$task['task_id']}, party: {$task['party_id']} not init!" );
	        		flock($this->lock, LOCK_UN);
	        		fclose($this->lock);
	        	} else if ($to_id){
	        		$this->log("task: {$task['task_id']}, party: {$task['party_id']} error!" );
	        		$this->updateTask('ERROR', $task['task_id']);
	        		flock($this->lock, LOCK_UN);
	        		fclose($this->lock);
	        	} else {
	        	
	        	$this->updateTask('EXCUTE', $task['task_id']);
	        	if($task['facility_id']!= '149849257'){
	        		$orders = $this->getOrders($task);
	        	}else{
	        		$orders[0]['order_id'] = $task['outer_id'];
	        	}
	        	
	        	$i = 0;
	        	if (! $orders) {
	        		$this->log("task: {$task['task_id']} orders empty");
	        	} else {
	        		foreach ($orders as $order) {
	        			$db = $this->getMaster();
	        			$transaction = $db->beginTransaction();
	        			
	        			//打标前再判断一遍订单状态
						$sql = "
							select eoi.order_id
							from ecshop.ecs_order_info eoi 
							where eoi.order_id = {$order['order_id']}
							and eoi.order_status = 1 and eoi.pay_status = 2
							and eoi.facility_id = '{$task['facility_id']}' and eoi.shipping_id = '{$task['shipping_id']}'
							limit 1 for update
						";
						
						$o =  $db->createCommand ($sql)->queryScalar();
	        			if ($o){
	        				//打标
			        		$sql = "
				        		insert into ecshop.ecs_out_ship_order
									(task_id, party_id, order_id, create_user, create_time)
								values 
									('{$task['task_id']}', '{$task['party_id']}', '{$order['order_id']}', '{$task['create_user']}', now())
							";
							$db->createCommand ($sql)->execute();
							
							//记录action
							$sql = "select shipping_status, invoice_status, shortage_status, note_type from ecshop.ecs_order_action where order_id = {$order['order_id']} order by action_time desc limit 1 ";
							$action = $db->createCommand ($sql)->queryRow();
							$sql = "
								insert into ecshop.ecs_order_action 
									(order_id, order_status, pay_status, shipping_status, action_time, action_user, action_note, invoice_status, shortage_status, note_type)
								values 
									('{$order['order_id']}', 1, 2, '{$action['shipping_status']}', now(), '{$task['create_user']}', '外包仓发货，客服暂不处理。系统自动走预定、出库、发货流程。', '{$action['invoice_status']}', '{$action['shortage_status']}', '{$action['note_type']}')
								";
							$db->createCommand ($sql)->execute();
							
							$this->log("task: {$task['task_id']} order_id {$order['order_id']} create success");
							$i++;
	        			}
	        			$transaction->commit();
	        		}
	        	}
	        	$total += $i;
				$this->log("task: {$task['task_id']} create order count: {$i}");
	        	$this->updateTask('COMPLETE', $task['task_id']);
		        flock($this->lock, LOCK_UN);
	        	fclose($this->lock);
		        }
		        $this->log("end task: {$task['task_id']} party_id: {$task['party_id']}\n");
        	}
        }
        
        $this->log("total time :".(microtime(true)-$start) . "total task count: " . count($tasks) . " total order count: {$total}");
        $this->log("end DoTask");
    }
    
    function getOrders($task) {
    	$condition = "eoi.party_id = {$task['party_id']} and eoi.order_type_id = 'SALE' 
					and eoi.order_status = 1 and eoi.pay_status = 2 and eoi.shipping_status = 0
					and eoi.order_time >= '{$task['start_time']}'
					and eoi.order_time < '{$task['end_time']}'
					and eoi.facility_id = '{$task['facility_id']}'
					and eoi.shipping_id = '{$task['shipping_id']}'
					and outo.order_id is null 
					";
		if($task['province'] != ''){
			$condition .= " and eoi.province in ({$task['province']})";
		}
		if($task['city'] != ''){
			$condition .= " and eoi.city in ({$task['city']})";
		}
					
    	if(strpos($task['outer_id'], 'TC-') !== false){
			$TCsql="SELECT GROUP_CONCAT(CONCAT(dggi.goods_id, '_',dggi.style_id, '_',dggi.goods_number) order by goods_id, style_id, goods_number)  tc_group_concat
					FROM ecshop.distribution_group_goods dgg
					INNER JOIN ecshop.distribution_group_goods_item dggi ON dgg.group_id = dggi.group_id
					WHERE dgg.`code` = '{$task['outer_id']}'
					group by dgg.group_id";
			$tc_group_concat=$this->getMaster()->createCommand($TCsql)->queryScalar();
			
			$sql="select eoi.order_id
					from ecshop.ecs_order_info eoi force INDEX(order_info_multi_index)
					inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id AND eog.group_code = '{$task['outer_id']}' 
					inner join romeo.order_inv_reserved oir on eoi.order_id = oir.order_id and oir.status = 'Y'
					LEFT JOIN ecshop.ecs_order_goods eog1 ON eoi.order_id = eog1.order_id AND eog1.group_code != '{$task['outer_id']}'
					left join ecshop.ecs_out_ship_order outo on eoi.order_id = outo.order_id
					where {$condition}
					AND eog.group_number = 1 AND eog1.rec_id IS NULL
					group by eoi.order_id 
					having group_concat(concat(eog.goods_id, '_', eog.style_id, '_', eog.goods_number) order by eog.goods_id, eog.style_id, eog.goods_number) = '{$tc_group_concat}'
					limit {$task['out_ship_number']}
				";
		}else{ //单品的group_code为空
			$goods = explode('_', $task['outer_id']);
			$goods_id = $goods[0];
			$style_id = isset($goods[1])?$goods[1]:0;
			
			$sql="select eoi.order_id
				from ecshop.ecs_order_info eoi force INDEX(order_info_multi_index)
				inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id AND eog.goods_id = {$goods_id} AND eog.style_id = {$style_id} 
				inner join romeo.order_inv_reserved oir on eoi.order_id = oir.order_id and oir.status = 'Y'
				LEFT JOIN ecshop.ecs_order_goods eog1 ON eoi.order_id = eog1.order_id AND (eog1.goods_id != {$goods_id} OR eog1.style_id != {$style_id})
				left join ecshop.ecs_out_ship_order outo on eoi.order_id = outo.order_id
				where {$condition}
				 AND eog.goods_number = 1 AND eog.group_code = '' AND eog1.rec_id IS NULL 
				group by eoi.order_id
				having sum(eog.goods_number) = 1
				limit {$task['out_ship_number']}
				";
		}
//    	$this->log($sql);
		return $this->getMaster()->createCommand ($sql)->queryAll();
    }
    
    function updateTask($status, $task_id) {
    	$sql = "update ecshop.ecs_out_ship_order_task set status = '{$status}' where task_id = '{$task_id}' limit 1 ";
    	$this->getMaster()->createCommand ($sql)->execute();
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
    
    // 检查该业务是否已锁住
    private function isLocked ($party_id) {
    	$lock_file_name = $this->get_file_lock_path($party_id, 'outShipOrder');
		$this->lock = fopen($lock_file_name, "w+");
		$would_block = false;
		if(flock($this->lock, LOCK_EX|LOCK_NB, $would_block)){
    		return false;
		}else{
	    	fclose($this->lock);
	    	return true;
	    }
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
	public function actionCreateBatchPick(){
		$sql = "select t.task_id ,count(DISTINCT s.SHIPMENT_ID) as shipment_count,count(DISTINCT outo.order_id) as order_count 
			from ecshop.ecs_out_ship_order_task t
			inner join romeo.facility_shipping fs on fs.shipping_id = t.shipping_id and fs.facility_id = t.facility_id
			inner join ecshop.ecs_out_ship_order outo on t.task_id = outo.task_id
			left join romeo.order_shipment os on os.order_id = convert(outo.order_id using utf8)
			left join romeo.shipment s on s.shipment_id = os.shipment_id and s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
			left join romeo.out_batch_pick_mapping bpm on os.shipment_id = bpm.shipment_id 
			where bpm.shipment_id is null and fs.is_delete = 0  and outo.create_time >date_sub(NOW(),interval 7 day)
			GROUP BY t.task_id
			HAVING shipment_count = order_count
			order by t.task_id 
			limit 20 ";
		$task_ids = $this->getMaster()->createCommand ($sql)->queryAll();
		foreach($task_ids as $task_id){
			$this->bind_task_to_bpsn($task_id['task_id']);
		}
	}
	private function bind_task_to_bpsn($task_id){
		$this->log("bind_task_to_bpsn '{$task_id}' begin");
		$sql = "select party_id,facility_id,shipping_id from ecshop.ecs_out_ship_order_task where task_id = '{$task_id}' limit 1 ";
		$task_info = $this->getMaster()->createCommand ($sql)->queryRow();
		if($task_info['party_id'] == '65609' && $task_info['facility_id'] == '149849257'){
			$facility_id = $task_info['facility_id'];
			$shipping_id = $task_info['shipping_id'];
			$party_id = $task_info['party_id'];
			$goods_name = '亨氏云擎项目商品-虚拟发货';
		}else{
			//查找商品名称，仓库，快递
			$sql = "select if(eog.group_code is null or eog.group_code='',eog.goods_name,eog.group_name) as title,t.facility_id,t.shipping_id,t.party_id
				from ecshop.ecs_out_ship_order_task t
				inner join ecshop.ecs_out_ship_order  oso on oso.task_id = t.task_id
				inner join ecshop.ecs_order_goods eog on eog.order_id = oso.order_id
				where t.task_id = '{$task_id}' limit 1 ";
			$bpsn_datas = $this->getMaster()->createCommand ($sql)->queryRow();
			$facility_id = $bpsn_datas['facility_id'];
			$shipping_id = $bpsn_datas['shipping_id'];
			$party_id = $bpsn_datas['party_id'];
			$goods_name = $bpsn_datas['title'];
		}
		//
		$sql = "select os.shipment_id 
			from ecshop.ecs_out_ship_order_task t
			inner join ecshop.ecs_out_ship_order outo on t.task_id = outo.task_id
			inner join romeo.order_shipment os on os.order_id = convert(outo.order_id using utf8)  
			inner join romeo.shipment s on s.shipment_id = os.shipment_id and s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
			LEFT JOIN romeo.out_batch_pick_mapping bpm on s.shipment_id = bpm.shipment_id
			where t.task_id = '{$task_id}' and bpm.shipment_id is null";
		$total_shipment_list = $this->getMaster()->createCommand ($sql)->queryAll();
		$shipment_ids = array();
		foreach($total_shipment_list as $total_shipment_id){
			$shipment_ids[] = $total_shipment_id['shipment_id'];
			if(count($shipment_ids) === $this->batch_length){
				$this->bind_shipments_to_bpsn($facility_id,$shipping_id,$shipment_ids,$goods_name,$party_id);
				$shipment_ids = array();
			}
		}
		if(!empty($shipment_ids)){
			$this->bind_shipments_to_bpsn($facility_id,$shipping_id,$shipment_ids,$goods_name,$party_id);
		}
		$this->log("bind_task_to_bpsn '{$task_id}' end");
	}
	private function bind_shipments_to_bpsn($facility_id,$shipping_id,$shipment_ids,$goods_name,$party_id){
		$this->log("bind_shipments_to_bpsn  begin");
		if(empty($shipment_ids)){
			$this->log("bind_shipments_to_bpsn shipment_ids is empty");
			return;
		}
		$transaction = $this->getMaster()->beginTransaction();
		$batch_pick_sn = $this->get_batch_pick_sn(); 
		$this->log('batch_pick_sn:'.$batch_pick_sn);
		if(empty($batch_pick_sn)){
			$this->log('create batch_pick_sn failed!');
			$transaction->rollback();
		}else{
			$sql1 = "insert into romeo.out_batch_pick (batch_pick_sn,facility_id,shipping_id,created_stamp,goods_name,party_id,print_note)
				values('{$batch_pick_sn}','{$facility_id}','{$shipping_id}',now(),'{$goods_name}','{$party_id}','打印日志：')";
			$sql2 = "select batch_pick_id from romeo.out_batch_pick where batch_pick_sn = '{$batch_pick_sn}'";
			if($this->getMaster()->createCommand ($sql1)->execute()){
				$batch_pick_id = $this->getMaster()->createCommand ($sql2)->queryScalar();
				if(empty($batch_pick_id)){
					$this->log("bind_shipments_to_bpsn failed where get batch_pick_id ");
					$transaction->rollback();
				}else{
					$sql3 = "insert into romeo.out_batch_pick_mapping (batch_pick_id,shipment_id,created_stamp) values ";
                    $single_data = array();
                    foreach($shipment_ids as $shipment_id){
                            $single_data[] = "('{$batch_pick_id}','{$shipment_id}',now())";
                    }
                    $sql3 = $sql3.join(',',$single_data).";";

					$this->log('bind_shipments_to_bpsn sql3: '.$sql3.PHP_EOL);

					if($this->getMaster()->createCommand ($sql3)->execute()){
						$transaction->commit();
						$this->log("bind_shipments_to_bpsn end");
					}else{
						$this->log("bind_shipments_to_bpsn failed where insert out_batch_pick_mapping ");
						$transaction->rollback();
					}
				}
			}else{
				$this->log("bind_shipments_to_bpsn failed where insert out_batch_pick ");
				$transaction->rollback();
			}
		}
	}
	private function get_batch_pick_sn(){
		$cur_day_str = date ( "ymd");
		$sql = "select max(batch_pick_sn) from romeo.out_batch_pick where batch_pick_sn >= '{$cur_day_str}' ";
		$batch_pick_sn = $this->getMaster()->createCommand ($sql)->queryScalar();
		if(empty($batch_pick_sn)){
			$max_number = 1;
		}else{
			$max_number = (int)substr($batch_pick_sn,-4);
			$max_number++;
		}
		return $cur_day_str.'-'.sprintf("%04d", $max_number);
	}

    private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    
    private function getGoodsName($outer_id) {
		$goods_name = "";
		if (strstr($outer_id,'TC')) {
			$sql = "select dgg.name from ecshop.distribution_group_goods dgg where dgg.code = '{$task['outer_id']}' limit 1 ";
			$goods_name = $this->getMaster()->createCommand ($sql)->queryScalar();
		} else if (strstr($outer_id,'_')){
			$ary = preg_split('/_/', $outer_id);
			$sql = "select g.goods_name from ecshop.ecs_goods g where g.goods_id = '{$ary[0]}' limit 1 ";
			$goods_name = $this->getMaster()->createCommand ($sql)->queryScalar();
			if ($ary[1]) {
				$sql = "select s.color from ecshop.ecs_style s where s.style_id = '{$ary[1]}' limit 1";
				$goods_name .= " " . $this->getMaster()->createCommand ($sql)->queryScalar();
			}
		} else {
			$sql = "select g.goods_name from ecshop.ecs_goods g where g.goods_id = '{$outer_id}' limit 1 ";
			$goods_name = $this->getMaster()->createCommand ($sql)->queryScalar();
		}
		return $goods_name;
	
	}
}	
	

