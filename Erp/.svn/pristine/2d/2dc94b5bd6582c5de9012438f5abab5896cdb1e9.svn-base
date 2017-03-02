<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH . PATH_SEPARATOR . ROOT_PATH . 'RomeoApi/');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/includes/lib_order.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'admin/includes/lib_common.php';
require_once ROOT_PATH . 'admin/includes/lib_order_mixed_status.php';

$GLOBALS['shopapi_client'] = $shopapi_client;
$_SESSION['admin_name'] = 'webService';
Yii::import('application.commands.LockedCommand', true);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);


/**
 * @author wjzhu@i9i8.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

class AutoConfirmDomesticOrderCommand extends LockedCommand {	
	private $master; 					// Master数据库    
	private static $_limit = 100;		// 每次检查多少条订单

	public function actionIndex()
	{
		$this->run ( array ('AutoConfirmDomesticOrder') );
	}
	
	/**
     * 自动确认内贸订单
     */
    public function actionAutoConfirmDomesticOrder() {
    	$start = microtime(true);
        $include_list  = array (
        		/*
            'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
            'd1ac25f28f324361a9a1ea634d52dfc0', //怀轩名品专营店
            '239133b81b0b4f0ca086fba086fec6d5', //贝亲官方旗舰店
            '11b038f042054e27bbb427dfce973307', //多美滋官方旗舰店
            'ee0daa3431074905faf68cddf9869895', //accessorize旗舰店
            'ee6a834daa61d3a7d8c7011e482d3de5', //金奇仕官方旗舰店
            'f38958a9b99df8f806646dc393fdaff4', //阳光豆坊旗舰店
            '62f6bb9e07d14157b8fa75824400981f', //雀巢官方旗舰店
            '753980cc6efb478f8ee22a0ff1113538', //gallo官方旗舰店
            '589e7a67c0f94fb686a9287aaa9107db', //yukiwenzi-分销
            'fe1441b38d4742008bd9929291927e9e', //好奇官方旗舰店
            'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利官方旗舰店
            'dccd25640ed712229d50e48f2170f7fd', //ecco爱步官方旗舰店
            '9f6ca417106894739e99ebcbf511e82f', //每伴旗舰店
            'd2c716db4c9444ebad50aa63d9ac342e', //皇冠巧克力
          	'6ecd27fb75354272ba07f08a2507fa40', //蒙牛母婴旗舰店
			'85b1cf4b507b497e844c639733788480', //安满官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a', //blackmores官方旗舰店
            '87b6a6a6ced1499c90073197670b54ce', //玛氏宠物旗舰店
            '573d454e82ff408297d56fbe1145cfb9'  //金宝贝
            */
        	'2693c26ac15c44c2917c26f372cd7089',	//玺乐官方旗舰店
        	'53b45e07cc2b40278dd471dc0d4e8122',	//优瑞斯旗舰店
        	'995e1a1b43eb4e2ba151f22ea45314ff',	//康漫旗舰店
        	'ea6678dce2fb102ab1b4001d0907b75a',	//星星堡旗舰店
         	'4f21d297e9d2494f9092314cb9f185f1',	//佳贝艾特官方旗舰店
        );    
        
        foreach ( $this->getTaobaoShopList () as $taobaoShop ) {      
            if (! in_array($taobaoShop['application_key'], $include_list)) {
                continue;
            }
            
            $party_start = microtime(true);
        	// 已付款、未确认、未发货
			$sql = "
				SELECT 	order_id, 
						order_sn, 
						taobao_order_sn
				FROM 	ecshop.ecs_order_info a 
				WHERE 	order_status = 0 
				AND 	pay_status = 2 
				AND 	shipping_status = 0 
				AND 	order_type_id = 'SALE' 
				AND 	party_id = '{$taobaoShop['party_id']}'
				AND 	NOT EXISTS (
							SELECT 	1 	
							FROM 	ecshop.auto_confirm_order b 
							WHERE 	b.order_sn = a.order_sn 
							AND 	confirmable = 'NO' 
							LIMIT 	1
						)
				LIMIT " . self::$_limit . "
			";
		

            $order_list = $this->getMaster()->createCommand ($sql)->queryAll();
            $order_count = 0;
	        foreach ($order_list as $order)
			{	
				$order_id = $order['order_id'];
				$confirmable = $this->isAutoConfirmable($order_id);
				if ($confirmable) {
					$this->autoConfirmIt($order_id);
					$sql = "
							INSERT IGNORE INTO ecshop.auto_confirm_order 
								(order_id, order_sn, taobao_order_sn, confirmable, ctime)
							VALUES 
								('$order_id', '{$order['order_sn']}', '{$order['taobao_order_sn']}', 'YES', NOW())
							ON DUPLICATE KEY UPDATE confirmable = 'YES'
						";
					$this->getMaster()->createCommand($sql)->execute();
					$order_count++;
				} else {
					$sql = "
							INSERT IGNORE INTO ecshop.auto_confirm_order 
								(order_id, order_sn, taobao_order_sn, confirmable, ctime)
							VALUES 
								('$order_id', '{$order['order_sn']}', '{$order['taobao_order_sn']}', 'NO', NOW())
							ON DUPLICATE KEY UPDATE confirmable = 'NO'
						";
					$this->getMaster()->createCommand($sql)->execute();
				}
			}
			
			$total_count  = $total_count + $order_count;
			
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 自动确认订单数：" . $order_count ." 耗时：".(microtime(true)-$party_start)."\n";
        }
        
        echo "[". date('c'). "] 自动确认订单：" . $total_count . " 总计耗时：" . (microtime(true)-$start) . "\n";
    }
	
	/**
     * 根据order_id判断订单是否满足自动确认的条件:
     * 1. 没有留言&订单同步快递显示全境可达
     * 2. 二次购买，记录顾客地址，直接使用上次快递
     * 3. 收货地址为江浙沪，且地址里面没有“村”的，且没有留言的
     * 4. 库存
     * 5. 各个业务组织确认订单时候的关注点
     * @return true
     */
	protected function isAutoConfirmable($order_id)
	{
		$isAutoConfirmable = false;
		$order = getOrderInfo($order_id);
		
		//优瑞斯  玺乐  康漫:品牌商来操作发货的,ERP只是记录客服的绩效
		if(in_array($order['party_id'], array('65573', '65575', '65577', '65580', '65587'))) {
			$isAutoConfirmable = true;
		}
		return $isAutoConfirmable;
	}
	
	/**
     * 根据order_id自动确认订单
     * 
     * @return true
     */
	protected function autoConfirmIt($order_id)
	{
		global $shopapi_client;
		$api_order = $shopapi_client->getOrderById($order_id);
		$api_order->orderStatus = 1;
		$action_user = $_SESSION['admin_name'];
		$api_order->actionUser = $action_user;
		$api_order->actionNote = "自动确认订单";
		$result = $shopapi_client->updateOrder($api_order);
		update_order_mixed_status($order_id, array('order_status' => 'confirmed'), 'worker', "自动确认订单");
		return true;
	}
	
	/**
     * 取得启用的淘宝店铺的列表
     * 
     * @return array
     */
    protected function getTaobaoShopList() {
        static $list;
        if (! isset ( $list )) {
            $sql = "select * from taobao_shop_conf where status='OK'";
            $list = $this->getMaster ()->createCommand ( $sql )->queryAll ();
            $command = $this->getMaster ()->createCommand ( "select * from taobao_api_params where taobao_api_params_id=:id" );
            foreach ( $list as $key => $item )
                $list [$key] ['params'] = $command->bindValue ( ':id', $item ['taobao_api_params_id'] )->queryRow ();
        }
        return $list;
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
}