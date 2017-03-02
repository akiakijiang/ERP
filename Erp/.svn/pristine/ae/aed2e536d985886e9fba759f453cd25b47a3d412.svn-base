<?php

/**
 * 超级工具页面
 */
define('IN_ECS', true);
set_time_limit(3600);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');

if(!in_array($_SESSION['admin_name'],array(
	'yjchen','ychen','wjzhu','lchen',
	'mjzhou','qdi','hbai','jwang','qyyao',
	'zjli','ytchen','ljni','stsun',
	'yxie','xjye','hzhang1','bjlian','lyjing'
))){
	die('没有权限');
}

require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/includes/lib_taobao.php');
include_once(ROOT_PATH . 'includes/lib_passport.php');

$flag = true;
// 淘宝店列表
$taobao_shop_list = Helper_Array::toHashmap((array)get_taobao_application_list(),'taobao_shop_conf_id','nick');

// 新旧状态
$status_id_list = array('INV_STTS_AVAILABLE'=>'全新','INV_STTS_USED'=>'二手');

// 权限组列表
include_once('../languages/' .$_CFG['lang']. '/admin/priv_action.php');
$sql = "SELECT action_id, parent_id, action_code FROM " .$ecs->table('admin_action'). " WHERE is_shield=0 and parent_id=0";
$action_list=$db->getAllRefby($sql, array('action_code'), $ref_files, $ref_values);
$action_group_list=array();
foreach($action_list as $action_row)
	$action_group_list[$action_row['action_id']]=isset($_LANG[$action_row['action_code']])?$_LANG[$action_row['action_code']]:$action_row['action_code'];

// 去掉空白
if(!empty($_POST))
	Helper_Array::removeEmpty($_POST);

// 修改订单状态新
if($_REQUEST['act']=='edit_order_status_new')
{
	do
	{
		if(empty($_POST['order_sn']))
		{
			$smarty->assign('message', '填写订单号');
			break;
		}
		
		$order=order_info(null,$_POST['order_sn']);
		if($order)
		{
			$update=array();
			$update['order_status']=$_REQUEST['order_status'];
			$update['pay_status']=$_REQUEST['pay_status'];
			$update['shipping_status']=$_REQUEST['shipping_status'];
			$_REQUEST['action_note'] = '【超级工具】'.$_REQUEST['action_note'];
			// 更新
			if(!empty($update))
			{
				$sql="update ecs_order_info set";
				if(isset($update['order_status']))
					$sql.=" order_status=". $update['order_status'];
				if(isset($update['shipping_status']))
					$sql.=" ,shipping_status=". $update['shipping_status'];
				if(isset($update['pay_status']))
					$sql.=" ,pay_status=". $update['pay_status'];
				$sql.=" where order_id = ". $order['order_id']." limit 1";
				// Qlog::log('$sql:'.$sql);
				$result=$db->query($sql);
				// 有备注则添加备注
				if($result && !empty($_REQUEST['action_note']))
				{
					// 备注下
					order_action
					(
						$order['order_sn'],
						isset($update['order_status']) ? $update['order_status'] : $order['order_status'],
						isset($update['shipping_status']) ? $update['shipping_status'] : $order['shipping_status'],
						isset($update['pay_status']) ? $update['pay_status'] : $order['pay_status'],
						$_REQUEST['action_note']
					);
				}
				
				if($result)
					$smarty->assign('message', '修改成功，请勿重复提交');
				else
					$smarty->assign('message', '悲剧了，数据库执行不成功');
			}
			else 
			{
				$smarty->assign('请选择要修改的订单状态');
			}
		}
		else 
		{
			$smarty->assign('message','没有此订单');
			break;
		}
	}while (false);
}

// 修改订单状态
if($_REQUEST['act']=='edit_order_status')
{
	do
	{
		if(empty($_POST['order_sn']))
		{
			$smarty->assign('message', '填写订单号');
			break;
		}
		
		$order=order_info(null,$_POST['order_sn']);
		if($order)
		{
			$update=array();
			if($_REQUEST['order_status'] !=0)
				$update['order_status']=$_REQUEST['order_status'];
			if($_REQUEST['pay_status'] !=0)
				$update['pay_status']=$_REQUEST['pay_status'];
			if($_REQUEST['shipping_status'] !=0 )
				$update['shipping_status']=$_REQUEST['shipping_status'];
			
			// 更新
			if(!empty($update))
			{
				$sql="update ecs_order_info set";
				if(isset($update['order_status']))
					$sql.=" order_status=". $update['order_status'];
				if(isset($update['shipping_status']))
					$sql.=" shipping_status=". $update['shipping_status'];
				if(isset($update['pay_status']))
					$sql.=" pay_status=". $update['pay_status'];
				$sql.=" where order_id = ". $order['order_id'];
				$result=$db->query($sql);
				// 有备注则添加备注
				if($result && !empty($_REQUEST['action_note']))
				{
					// 备注下
					order_action
					(
						$order['order_sn'],
						isset($update['order_status']) ? $update['order_status'] : $order['order_status'],
						isset($update['shipping_status']) ? $update['shipping_status'] : $order['shipping_status'],
						isset($update['pay_status']) ? $update['pay_status'] : $order['pay_status'],
						$_REQUEST['action_note']
					);
				}
				
				if($result)
					$smarty->assign('message', '修改成功，请勿重复提交');
				else
					$smarty->assign('message', '悲剧了，数据库执行不成功');
			}
			else 
			{
				$smarty->assign('请选择要修改的订单状态');
			}
		}
		else 
		{
			$smarty->assign('message','没有此订单');
			break;
		}
	}while (false);
}
else if($_REQUEST['act']=='gt_return_map'){
	do{
		if(empty($_POST['order_id']))
		{
			$smarty->assign('message', '填写订单号');
			break;
		}else{
			 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
			 purchaseReturnMapForTools($_POST['order_id']);
		}
	}while(false);
}
// 添加淘宝小二
else if($_REQUEST['act']=='edit_taobao_sales')
{
	do
	{
		if(empty($_POST['nickname']))
		{
			$smarty->assign('message', '需要填写昵称');
			break;
		}
		
		if(empty($_POST['taobao_shop_conf_id']))
		{
			$smarty->assign('message', '需要选择店铺');
			break;
		}
		else
			$taobao_shop_conf_id=$_POST['taobao_shop_conf_id'];
		
		$sql="select taobao_sales_id from taobao_sales where taobao_shop_id = %d and nickname = '%s'";
		$taobao_sales=$db->getRow(sprintf($sql,$taobao_shop_conf_id,$_REQUEST['nickname']));
		// 存在则更新
		if($taobao_sales)
		{
			$sql="update taobao_sales set enabled='%s' where taobao_sales_id = %d";
			$result=$db->query(sprintf($sql,$_REQUEST['enabled'],$taobao_sales['taobao_sales_id']));
			if($result)
				$smarty->assign('message', '更新成功');
			else
				$smarty->assign('message', '悲剧了，更新失败');
		}
		// 不存在添加记录
		else 
		{
			$sql="insert into taobao_sales (taobao_shop_id,nickname,enabled,created) values (%d, '%s', '%s', NOW())";
			$result=$db->query(sprintf($sql,$taobao_shop_conf_id,$_REQUEST['nickname'],$_REQUEST['enabled']));
			if($result)
				$smarty->assign('message', '添加成功，请注意勿重复提交');
			else
				$smarty->assign('message', '悲剧了，数据库执行不成功');
		}
	}
	while(false);
}
// 权限
else if($_REQUEST['act']=='edit_admin_action')
{
	do
	{
		if(empty($_POST['action_code']))
		{
			$smarty->assign('message', '需要填写权限代码');
			break;
		}
		
		if(empty($_POST['parent_id']))
		{
			$smarty->assign('message', '需要选择组别');
			break;
		}
		
		$sql="select action_id from ecs_admin_action where action_code = '%s'";
		$row=$db->getRow(sprintf($sql,$_POST['action_code']));
		// 存在则更新
		if($row)
		{
			$sql="update ecs_admin_action set is_shield=%d where action_id = %d";
			$result=$db->query(sprintf($sql,$_POST['is_shield'],$row['action_id']));
			if($result)
				$smarty->assign('message', '更新成功');
			else
				$smarty->assign('message', '悲剧了，更新失败');
		}
		// 不存在添加记录
		else 
		{
			$sql="insert into ecs_admin_action (parent_id,action_code,is_shield) values (%d, '%s', %d)";
			$result=$db->query(sprintf($sql,$_POST['parent_id'],$_POST['action_code'],$_POST['is_shield']));
			if($result)
				$smarty->assign('message', '添加成功，请注意勿重复提交');
			else
				$smarty->assign('message', '悲剧了，数据库执行不成功');
		}
	}
	while(false);	
}
/*
// 注册用户 -- RPC关联
else if($_REQUEST['act']=='user_register')
{
	do
	{	
		if(empty($_POST['username']))
		{
			$smarty->assign('message', '请填写用户名');
			break;
		}
		
		if(empty($_POST['password']))
		{
			$smarty->assign('message', '请填写密码');
			break;
		}
		
		if(empty($_POST['email']))
		{
			$smarty->assign('message', '请填写邮件地址');
			break;
		}
		
		if(empty($_POST['realname']))
		{
			$smarty->assign('message', '请填写真实姓名');
			break;
		}

		#$appkey=reset($application_key);
		$appkey="5b4b488ae337e982205fb0fec6034089";  // erp
		
		// 验证用户名
		$context=$sso_client->verifyUserName($_POST['username'],$appkey);
		if($context!==0)
		{
			if($context==26)
				$smarty->assign('message','这个用户名已经存在');
			else if($context==1)
				$smarty->assign('message','用户名长度不对,5-18个英文字母及数字或2-6个中文字');
			else
				$smarty->assign('message','用户名不合要求');
			break;
		}
		
		// 验证密码
		$context=$sso_client->verifyPassword($_POST['password'],$appkey);
		if($context!==0)
		{
			if($context==3)
				$smarty->assign('message','密码长度不对,密码只允许5-18字符');
			else
				$smarty->assign('message','用户密码不合要求');
			break;
		}
		
		// 验证邮箱地址
		$context=$sso_client->verifyEmail($_POST['email'],$appkey);
		if($context!==0)
		{
			if($context==28)
				$smarty->assign('message','这个email已经存在');
			else
				$smarty->assign('message','email不合要求');
			break;
		}
		
		include_once(ROOT_PATH . "RpcApi/universal/user/OKUser.php");
		$OKUser=new OKUser();
    	$OKUser->setUserName($_POST['username']);
    	$OKUser->setPassword($_POST['password']);
    	$OKUser->setEmail($_POST['email']);
    	$OKUser->setCreatedBy($_SESSION['admin_user']);
    	$OKUser->setCreatedIP(getRealIp());
    	$context=$sso_client->createUser($OKUser,$appkey);
    	if($context && $context->errorCode==0)
    	{
    		$userId=$context->userId;
			$sql="insert into ecs_users (userId, email,user_name,password,reg_time,user_realname) values ('%s','%s','%s','%s','".time()."','%s')";
			$db->query(sprintf($sql,$userId,$_POST['email'],$_POST['username'],md5($_POST['password']),$_POST['realname']));
			$smarty->assign('message', '用户添加成功');
    	}
		else
		{
			$smarty->assign('message', '悲剧了，用户添加失败，错误代码：' .$context->errorCode);
		}
	}while(false);
}
*/
// 修改发票抬头
else if($_REQUEST['act']=='edit_inv_payee')
{
	do
	{	
		if(empty($_POST['order_sn']))
		{
			$smarty->assign('message', '请填写订单号');
			break;
		}
		
		if(empty($_POST['inv_payee']))
		{
			$smarty->assign('message', '请填写发票抬头');
			break;
		}
		
		$order=$db->getRow(sprintf("select order_id,order_sn from ecs_order_info where order_sn = '%s' limit 1", $_POST['order_sn']));
		if(!$order)
		{
			$smarty->assign('message', '找不到订单号:'.$_POST['order_sn']);
			break;
		}
		
		// 更新发票抬头
		$result=$db->query(sprintf("update ecs_order_info set need_invoice='Y', inv_payee='%s' where order_id= '%d' limit 1",$_POST['inv_payee'],$order['order_id']));
		$smarty->assign('message', $result?'更新成功了':'更新失败了，请重试');
		
	}while(false);
}
// Dumex订单重新导单
else if($_REQUEST['act']=='dumex_taobao_order_sn_update')
{
	do
	{	
		// 多美滋订单编号
		if(empty($_POST['dumex_taobao_order_sn']))
		{
			$smarty->assign('message', '多美滋订单编号 还没有填上呢，，');
			break;
		}
		// 备注
		if(empty($_POST['dumex_action_note']))
		{
			$smarty->assign('message', '多美滋订单更新 要填写备注的，，');
			break;
		}
		
		$order = $db->getRow(sprintf("select order_id, order_sn, order_status, shipping_status, pay_status from ecs_order_info where taobao_order_sn = '%s' limit 1", $_POST['dumex_taobao_order_sn']));
		if(!$order)
		{
			$smarty->assign('message', '找不到订单号:'.$_POST['dumex_taobao_order_sn']);
			break;
		}
		
		$sql = "update ecshop.ecs_order_info 
                   set taobao_order_sn = CONCAT(taobao_order_sn,'-F'), distribution_purchase_order_sn = CONCAT(distribution_purchase_order_sn,'-F')
                where taobao_order_sn = '%s' limit 1 ;" ;
        
                       
		// 订单更新
		$result = $db->query(sprintf($sql, $_POST['dumex_taobao_order_sn']));
		if ($result) {
			// 留下备注
		    order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $_REQUEST['dumex_action_note']);     
		}
		
		$smarty->assign('message', $result?'订单更新成功了':'更新失败了，请重试');
		
	}while(false);
}
// 维护商品条码
else if($_REQUEST['act']=='edit_goods_barcode')
{
	do
	{
		if(empty($_POST['goods_id'])){
			$smarty->assign('message', '请填写商品ID');
			break;
		}
		
		if(empty($_POST['bar_code'])){
			$smarty->assign('message', '请填写商品条码');
			break;
		}
		
		$goodsItem=$db->getRow(sprintf("select * from ecs_goods where goods_id = '%d' limit 1", $_POST['goods_id']));
		if(!$goodsItem){
			$smarty->assign('message', '找不到商品:'.$_POST['goods_id']);
			break;
		}
		
		// 更新商品条码
		$result=$db->query(sprintf("update ecs_goods  set barcode = '%s' where goods_id = '%d'", $_POST['bar_code'], $goodsItem['goods_id']));
		$smarty->assign('message', $result?'更新成功了':'更新失败了，请重试');
		
	}while(false);
	
}
// 维护发货单状态
else if($_REQUEST['act']=='edit_shipment_status')
{
	do
	{
		if(empty($_POST['shipment_id'])){
			$smarty->assign('message', '请填写发货单号');
			break;
		}
		
		$shipment=$db->getRow(sprintf("select shipment_id from romeo.shipment where shipment_id = '%d' limit 1", $_POST['shipment_id']));
		if(!$shipment){
			$smarty->assign('message', '找不到发货单:'.$_POST['shipment_id']);
			break;
		}
		
		// 更新商品条码
		$result=$db->query(sprintf("update romeo.shipment set status = 'SHIPMENT_INPUT' where shipment_id = '%d'", $shipment['shipment_id']));
		$smarty->assign('message', $result?'更新成功了':'更新失败了，请重试');
		
	}while(false);
}
// 维护发货单号
else if($_REQUEST['act']=='edit_shipment_tracking')
{
	do
	{
		if(empty($_POST['shipment_id'])){
			$smarty->assign('message', '请填写发货单号');
			break;
		}
		
		if(empty($_POST['tracking_number'])){
			$smarty->assign('message', '请填写面单号');
			break;
		}
		
		$shipment=$db->getRow(sprintf("select shipment_id from romeo.shipment where shipment_id = '%d' limit 1", $_POST['shipment_id']));
		if(!$shipment){
			$smarty->assign('message', '找不到发货单:'.$_POST['shipment_id']);
			break;
		}
		
		// 更新商品条码
		$result=$db->query(sprintf("update romeo.shipment set tracking_number = '%s' where shipment_id = '%d'", $_POST['tracking_number'], $shipment['shipment_id']));
		$smarty->assign('message', $result?'更新成功了':'更新失败了，请重试');
		
	}while(false);
}
else if ($_REQUEST['act'] == 'create_shipment') {
die('会和command冲突，停止功能');
	$sql = "
		SELECT order_sn
		FROM ecs_order_info oi
		LEFT JOIN romeo.order_shipment os ON CONVERT( oi.order_id
		USING utf8 ) = os.order_id
		LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
		WHERE s.shipment_id IS NULL and oi.order_type_id in ('SHIP_ONLY', 'SALE', 'RMA_EXCHANGE')
		AND oi.order_time >=  '2015-06-10' 
	";
$order_sn_list = 	$db->getCol($sql);

foreach ($order_sn_list as $order_sn) {
    require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
    $sql = "select o.order_id, o.shipping_id, o.party_id, s.default_carrier_id as carrier_id
              from ecshop.ecs_order_info o
                   left join ecshop.ecs_shipping s on o.shipping_id = s.shipping_id
             where o.order_sn = '{$order_sn}'
          ";
    $order = $db->getRow($sql);
    if (!$order) {
        $smarty->assign('message', "找不到订单 {$order_sn}");
    } else {
        try {
//            $handle=soap_get_client('ShipmentService');
//            $handle->createShipmentForOrder(array(
//                'orderId'=>$order['order_id'],
//                'carrierId'=>$order['carrier_id'],
//                'shipmentTypeId'=>$order['shipping_id'],
//                'partyId'=>$order['party_id'],
//                'createdByUserLogin'=>$_SESSION['admin_name'],
//            ));
            $smarty->assign('message', "订单 {$order_sn} shipment 创建成功");
        } 
        catch (Exception $e) {
            $smarty->assign('message', "订单 {$order_sn} shipment 创建失败");
            print_r($e);
        }
    }
  }
}
else if ($_REQUEST['act'] == 'reserve_order_stock') {
	do {
		$order_sn = trim($_REQUEST['order_sn']);
		if(empty($order_sn)) {
			$message .= "请输入订单号";
			break;
		}
		
//		if(empty($party_info['party_id'])) {
//			$message .= "根据订单号找不到对应的组织";
//			break;
//		}
//		else if($party_info['IN_STORAGE_MODE']==3){
//			$message .= "批次号维护业务组不可以通过超级工具预定！如果着急请执行调度ReserveOrderInventory  ReserveByPartyIdNew --party_id={$party_info['party_id']} ";
//			break;
//		}

        $sqle =  "SELECT oi.order_id
			         from ecshop.ecs_order_info oi
			         left join romeo.order_inv_reserved re ON oi.order_id = re.order_id
			         where oi.order_sn = '{$order_sn}'
			         and (re.status ='N' OR re.status is null)
			         and not exists (
			             select 1 from
			             ecshop.ecs_order_goods og
			             left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			             where og.order_id = oi.order_id and pm.product_id is null
			             limit 1
			         )
					 and not exists (
						select 
						o.order_id ,sum(og.goods_number) as goodnum ,og.goods_id,og.style_id,ifnull(im.available_to_reserved,0) as available,
						ifnull(r.reserve_number,0) as reserve,og.status_id
						from
						ecshop.ecs_order_info o
						inner join ecshop.ecs_order_goods og ON o.order_id = og.order_id
						inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
						left join romeo.inventory_summary im ON pm.product_id = im.product_id and o.facility_id = im.facility_id
											and im.status_id = og.status_id
						left join romeo.order_inv_reserved_detail ird ON convert(og.rec_id using utf8) = ird.order_item_id
						left join ecshop.ecs_goods_inventory_reserved r ON pm.ecs_goods_id = r.goods_id and pm.ecs_style_id = r.style_id
											and r.facility_id = o.facility_id and r.status = 'OK'
						where o.order_id = oi.order_id
						and (ird.status ='N' or ird.status is null)
						group by goods_id,style_id
						having goodnum > available - if(og.status_id ='INV_STTS_AVAILABLE', reserve	,0)		
						limit 1
					 )  "; 
        $order_id = $db->getOne($sqle);
		if(empty($order_id)) {
			$message .= "该订单库存商品不足够预定或者订单状态不对";
			break;
		}
		
		require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
		
		try {
		    $handle=soap_get_client('InventoryService');
		    $handle->createOrderReserveByOrderId(array('orderId'=>$order_id));
		    
		    $sql = "
				SELECT p.party_id,p.IN_STORAGE_MODE,oi.order_id,og.rec_id,oi.order_type_id FROM ecshop.ecs_order_info oi  
				inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
				inner join romeo.party p on oi.party_id = p.party_id 
				WHERE order_sn = '{$order_sn}'    
		    ";
		
			$party_info = $db->getAll($sql);
		    
		    foreach ( $party_info as $order_goods_id ){
				if($order_goods_id['IN_STORAGE_MODE']==3 && ($order_goods_id['order_type_id']=='RMA_EXCHANGE' ||$order_goods_id['order_type_id']=='SHIP_ONLY' || $order_goods_id['order_type_id']=='SALE' )){

					
					$result =$handle->reserveOrderInventoryWithInventoryItemByOrderGoodsId(array('orderGoodsId'=>$order_goods_id['rec_id']));
				    
				} else {
					
					$result =$handle->reserveOrderInventoryByOrderGoodsId(array('orderGoodsId'=>$order_goods_id['rec_id']));
				}
			}
//		    $result = $handle->reserveOrderInventoryByPartyId(array("partyId" => $party_info['party_id']));
            $handle->updateOrderReserveStatusByOrderId(array('orderId'=>$order_id));
		    $message .= "请查看该订单是否预定成功,未成功请检查库存和订单状态！";
		} 
		catch (Exception $e) {
		    $message .= "订单 {$order_sn} 预定失败：".$e->getMessage;
		}
	  
	}while(false);
//	 $message = "超级工具中订单预定功能暂时已废弃";
    $smarty->assign('message', $message);
}
else if ($_REQUEST['act'] == 'cancel_order') {
    $order_sn = $_REQUEST['order_sn'];
    require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
    $sql = "
    	SELECT 	o.order_id 
    	FROM 	ecshop.ecs_order_info o 
    	WHERE 	o.order_sn = '{$order_sn}' 
    ";
    $order = $db->getRow($sql);
    if (!$order) {
        $smarty->assign('message', "找不到订单 {$order_sn}");
    } else {
    	$sql = "
    		SELECT 	* 
    		FROM 	romeo.order_inv_reserved
			WHERE 	order_id = '{$order['order_id']}'
		";
    	$order_inv_reserved = $db->getRow($sql);
    	if(!$order_inv_reserved) {
    		$smarty->assign('message', "该订单 {$order_sn}未预定库存");
    	} else {
    		try {
				$handle=soap_get_client("InventoryService");
				$handle->cancelOrderInventoryReservation(array('orderId'=>$order['order_id']));
				$smarty->assign('message', "该订单 {$order_sn}预定的库存成功取消");
			}
			catch(Exception $e){
				$smarty->assign('message', "该订单 {$order_sn}预定的库存取消失败");
            	print_r($e);
			}
    	}
    } 
}
else if ($_REQUEST['act'] == 'cancel_picklist') {
    $order_sn = $_REQUEST['order_sn'];
    require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
    $sql = "
    	SELECT 		o.order_id, s.picklist_id 
    	FROM 		ecshop.ecs_order_info o 
    	INNER JOIN	romeo.order_inv_reserved oir on oir.order_id = o.order_id
    	INNER JOIN  romeo.order_shipment os on os.order_id = o.order_id
    	INNER JOIN  romeo.shipment s on s.shipment_id = os.shipment_id
    	INNER JOIN  romeo.picklist p on p.picklist_id = s.picklist_id
    	WHERE 		o.order_sn = '{$order_sn}' 
    	AND			o.order_status = '1'
    	AND			o.pay_status = '2'
    	AND			o.shipping_status = '0'
    	AND			s.status = 'SHIPMENT_INPUT'
    	AND			s.picklist_id != ''
    	AND			p.status = 'PICKLIST_ASSIGNED';
    ";
    $order = $db->getRow($sql);
    if (!$order) {
        $smarty->assign('message', "订单 {$order_sn}未满足取消批拣条件：已确认, 已付款, 待配货, 已预定, 已批拣, 已分派");
    } else {
    	try {
			$handle = soap_get_client('PicklistService');
        	$handle->cancelPicklist(array(
            	'picklistId'=>$order['picklist_id'],
            	'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
        	));
			$smarty->assign('message', "该订单 {$order_sn}从批拣单{$order['picklist_id']}中取消批拣成功");
		}
		catch(Exception $e){
			$smarty->assign('message', "该订单 {$order_sn}从批拣单{$order['picklist_id']}中取消批拣失败");
            print_r($e);
		}
    } 
}
/***
// 订单调整
else if($_REQUEST['act']=='order_variance')
{
	do
	{
		if(empty($_POST['order_sn'])){
			$smarty->assign('message', '请填写需要调整的订单号');
			break;
		}
		
		$varianceAmount ;
		if(empty($_POST['variance_amount'])){
			$smarty->assign('message', '请填写调整金额');
			break;
		}else{
			$varianceAmount = floatval($_POST['variance_amount']);
		}
		
		$orderItem=$db->getRow(sprintf("select order_id from ecshop.ecs_order_info where order_sn = '%s' limit 1", $_POST['order_sn']));
		if(!$orderItem){
			$smarty->assign('message', 'ERP系统中 找不到该订单:'.$_POST['order_sn']);
			break;
		}else{
			$smarty->assign('message', $orderItem['order_id'].' session_name : '.$_SESSION['admin_name']);
			$varianceItem = $db->getRow(sprintf("select variance_id from romeo.finance_variance_amount where order_id = '%d' limit 1", $orderItem['order_id']));
			$result ;
			if(!$varianceItem){
			    // Insert	
				$strSQL = "INSERT INTO romeo.finance_variance_amount (`ORDER_ID`, `AMOUNT`, `NOTE`, `STATUS`, `VARIANCED_STAMP`, `CREATED_BY_USER_LOGIN`, `CREATED_TX_STAMP`, `CREATED_STAMP`, `LAST_UPDATED_TX_STAMP`, `LAST_UPDATED_STAMP`) VALUES "
				          ."('%d', '%.4f', '%s', 'OK', now(), '%s', now(), now(), now(), now());";
				$result = $db->query(sprintf($strSQL, $orderItem['order_id'], $varianceAmount, $_POST['variance_note'], $_SESSION['admin_name']));
				
			}else{
			    // update
			    if($_POST['variance_note']){
			        $strSQL = "update romeo.finance_variance_amount set amount = '%.4f', status = 'OK', created_by_user_login = '%s'" .
			        		  ", last_updated_tx_stamp = now(), last_updated_stamp = now(), note = '%s' " .
			        		  " where variance_id = '%s'";
			        		  
			        $result = $db->query(sprintf($strSQL, $varianceAmount, $_SESSION['admin_name'], $_POST['variance_note'], $varianceItem['variance_id']));
			    }else{
			    	$strSQL = "update romeo.finance_variance_amount set amount = '%.4f', status = 'OK', created_by_user_login = '%s'" .
			        		  ", last_updated_tx_stamp = now(), last_updated_stamp = now() " .
			        		  " where variance_id = '%s'";
			        		  
			        $result = $db->query(sprintf($strSQL, $varianceAmount, $_SESSION['admin_name'], $varianceItem['variance_id']));
			    }
			    
			}
			
			$smarty->assign('message', $result?'订单调整项更新成功了':'订单调整项更新失败了，请重试');
			break;
		}
		
	}while(false);
	
}
***/

// 订单调整
//else if($_REQUEST['act']=='modify_product_name')
//{
//	do
//	{
//		if(empty($_POST['product_goodsId'])){
//			$smarty->assign('message', '商品编码要填写的，，');
//			break;
//		}
//		$goodsIds = $_REQUEST['product_goodsId'];
//		
//		global $soapclient;
//		
//	    $sql = "
//	        SELECT 
//	             g.goods_id, g.goods_name, gs.goods_color, s.color, s.style_id
//	        FROM 
//	            {$ecs->table('goods')} AS g
//	            LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = g.goods_id
//	            LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
//	        WHERE g.goods_id " . db_create_in($goodsIds);
//	    $goodsList = $db->getAll($sql);
//	    
//	    if (!$goodsList) { 
//	        $smarty->assign('message', '商品编码要填写的，，');
//			break;
//        }
//
//	    // 取得ROMEO中的产品对应关系
//	    foreach ($goodsList as $goods) {
//
//            // 取得productId
//	        $param = new HashMap();
//	        $v[0] = new GenericValue();
//	        $v[1] = new GenericValue();
//	        $v[2] = new GenericValue();
//	        
//	        // 转化成prodcut_name
//            $goods_name =  $goods['goods_name'];
//            $style_id = 0 ;
//            if(!empty($goods['style_id'])){
//                $style_id = $goods['style_id'] ;
//                if(!empty($goods['goods_color'])){
//                	$goods_name = $goods_name . ' ' . $goods['goods_color'];
//                }else if(!empty($goods['color'])){
//                	$goods_name = $goods_name . ' ' . $goods['color'];
//                }
//            }
//	        
//	        
//	        $param->put("goodsId", $v[0]->setStringValue($goods['goods_id'])->getObject());
//	        $param->put("styleId", $v[1]->setStringValue($style_id)->getObject());
//	        $param->put("productName", $v[2]->setStringValue($goods_name)->getObject());
//	        
//	        // var_dump($v);die();
//	     
//	        require_once('RomeoApi/lib_inventory.php');
//	        $response = $soapclient->getProductIdByGoodsIdStyleId(array('arg0'=>$param->getObject()));
//	    }
//		
//      }while(false);
//	
//}


// 调用romeo接口 
else if($_REQUEST['act']=='auto_comfirm_order')
{
	do
	{
//		require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
//		
//		
//		$goods_id = '38495' ;
//		$style_id = '0';
//		$input_number = 1;
//		$value = '';
//		$order_type = 'B2C';
//		$order_id = 494149 ;
//		$fromStatusId = '';
//		$toStatusId = 'INV_STTS_INSPECT' ;
//		$purchase_paid_amount = '60.00' ;
//		$order_goods_id = '765289' ;
//		$facility_id = '30246773' ;
		
		
//        createAcceptInventoryTransaction('ITT_INSPECT',
//                                     array('goods_id'=>$goods_id, 'style_id'=>$style_id), 
//                                     $input_number, 
//                                     $value, 
//                                     $order_type, 
//                                     $order_id, 
//                                     $fromStatusId, 
//                                     $toStatusId, 
//                                     $purchase_paid_amount, 
//                                     $order_goods_id,
//                                     $facility_id);
    
//        $fromStatusId = 'INV_STTS_INSPECT';
//        $toStatusId = 'INV_STTS_AVAILABLE'; //入库的东西是新的
//        createTransferInventoryTransaction('ITT_PURCHASE',
//                                        array('goods_id'=>$goods_id, 'style_id'=>$style_id),
//                                        $input_number, 
//                                        $value, 
//                                        $order_type, 
//                                        $order_id, 
//                                        $order_id, 
//                                        $fromStatusId, 
//                                        $toStatusId, 
//                                        $order_goods_id,
//                                        $facility_id,
//                                        $facility_id);
//        
//        
//        
//		$smarty->assign('message', 'OKOK ............');
//		
//		require_once(ROOT_PATH. "RomeoApi/lib_dispatchlist.php");
//		$dispatchListId = '35734265' ;
//		$attributes = array('goodsStyle_cup_size' => 'Cup B');
//		
//		$result = createDispatchListAttribute($dispatchListId, $attributes);
//		
//		var_dump($result);


 $taocan_list = array(
    '61' => array(
            '24539621M' => array(
                       'goods_number' => 3,
                       'goods_price' => 128.00,
                     ),
            '24539621G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '28' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539570M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
          ),
    '201' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539571M' => array(
                       'goods_number' => 3,
                       'goods_price' => 54.00,
                     ),
          ),
    '27' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539570M' => array(
                       'goods_number' => 2,
                       'goods_price' => 179.00,
                     ),
          ),
    '15' => array(
            '24600818M' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),
            '24539573G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '16' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539575G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '17' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539619M' => array(
                       'goods_number' => 1,
                       'goods_price' => 33.00,
                     ),
            '24539575G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '18' => array(
            '24539619G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539617M' => array(
                       'goods_number' => 1,
                       'goods_price' => 189.00,
                     ),
          ),
    '224' => array(
            '24539574M' => array(
                       'goods_number' => 3,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),          
    '223' => array(
            '24539574M' => array(
                       'goods_number' => 6,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 2,
                       'goods_price' => 0.00,
                     ),
          ), 
    '222' => array(
            '24600818M' => array(
                       'goods_number' => 3,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '221' => array(
            '24600818M' => array(
                       'goods_number' => 6,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 2,
                       'goods_price' => 0.00,
                     ),
          ), 
    '182' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '181' => array(
            '24600818M' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '162' => array(
            '24539574M' => array(
                       'goods_number' => 2,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '161' => array(
            '24600818M' => array(
                       'goods_number' => 2,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '144' => array(
            '24539574M' => array(
                       'goods_number' => 3,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '143' => array(
            '24600818M' => array(
                       'goods_number' => 3,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '121' => array(
            '24539620M' => array(
                       'goods_number' => 3,
                       'goods_price' => 248.00,
                     ),
            '24539620G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '21' => array(
            '24539621' => array(
                       'goods_number' => 1,
                       'goods_price' => 128.00,
                     ),    
          ),
    '22' => array(
            '24502600' => array(
                       'goods_number' => 1,
                       'goods_price' => 98.00,
                     ),    
          ),
    '23' => array(
            '24539620' => array(
                       'goods_number' => 1,
                       'goods_price' => 248.00,
                     ),    
          ),
    '25' => array(
            '24539571' => array(
                       'goods_number' => 1,
                       'goods_price' => 54.00,
                     ),    
          ),
    '24' => array(
            '24539570' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),    
          ),
    '4' => array(
            '24539573' => array(
                       'goods_number' => 1,
                       'goods_price' => 82.00,
                     ),    
          ),
    '9' => array(
            '24600818' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),    
          ),
    '5' => array(
            '24539575' => array(
                       'goods_number' => 1,
                       'goods_price' => 69.00,
                     ),    
          ),
    '10' => array(
            '24539574' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),    
          ),
    '7' => array(
            '24539609' => array(
                       'goods_number' => 1,
                       'goods_price' => 58.00,
                     ),    
          ),
    '6' => array(
            '24539602' => array(
                       'goods_number' => 1,
                       'goods_price' => 159.00,
                     ),    
          ),
    '8' => array(
            '24539619' => array(
                       'goods_number' => 1,
                       'goods_price' => 66.00,
                     ),    
          ),
    '11' => array(
            '24539617' => array(
                       'goods_number' => 1,
                       'goods_price' => 189.00,
                     ),    
          ),
   ) ;
$taocan_map = array(
            '61' => '61_HA买3听送1听', 
            '28' => '28_DMP新用户买1罐送1盒', 
            '201' => '201_DMP新老用户买3盒送1盒', 
            '27' => '27_DMP老用户买2罐送1盒', 
            '15' => '15_DG2买900g送400g', 
            '16' => '16_DG3买900g送400g', 
            '17' => '17_DG3买900g送400g半价AI0350g', 
            '18' => '18_AIO买900g送350g', 
            '224' => '224_DG3买3*900g送900g', 
            '223' => '223_DG3买6*900g送2*900g', 
            '222' => '222_DG2买3*900g送900g', 
            '221' => '221_DG2买6*900g送2*900g', 
            '182' => '182_DG3买900g送900g',
            '181' => '181_DG2买900g送900g', 
            '162' => '162_DG3买2*900g送900g', 
            '161' => '161_DG2买2*900g送900g', 
            '144' => '144_DG3买3*900g送900g带游乐券', 
            '143' => '143_DG2买3*900g送900g带游乐券', 
            '121' => '121_PEPTI买3听送1听', 
            '21' => '21_HA-听', 
            '22' => '22_LF-听', 
            '23' => '23_PEPTI-听', 
            '25' => '25_妈妈奶粉-DMP-盒', 
            '24' => '24_妈妈奶粉-DMP-罐', 
            '4' => '4_金盾-DG2-盒', 
            '9' => '9_金盾-DG2-罐', 
            '5' => '5_金盾-DG3-盒', 
            '10' => '10_金盾-DG3-罐', 
            '7' => '7_金盾-DG4-盒', 
            '6' => '6_金盾-DG4-罐', 
            '8' => '8_优衡多-AIO-盒', 
            '11' => '11_优衡多-AIO-罐', 
);    
               
         
         require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
         
         $groupSQL = "INSERT INTO `ecshop`.`distribution_group_goods` (`code`, `name`, `amount`, `valid_from`, `updated`, `created`, `party_id`) VALUES ('%s', '%s', %d, NOW(), NOW(), NOW(), 65540); " ;
         $itemSQL = "INSERT INTO `ecshop`.`distribution_group_goods_item` (`group_id`, `goods_id`, `style_id`, `goods_name`, `goods_number`, `price`, `shipping_fee`) VALUES (%d, %d, %d, '%s', %d, %d, 0.0000);" ;
         $updateSQL = "update `ecshop`.`distribution_group_goods` set amount = %d where code = '%s' " ;

//         foreach ($taocan_list as $key => $item) {
//             $group_amount = 0 ;
//             $result = $db->query(sprintf($groupSQL, $key, $taocan_map[$key], $group_amount));
//             
//             $rows = $db->getRow(sprintf("select * from ecshop.distribution_group_goods where code = '%s' ", $key)) ; 
//             if (empty($rows)) {
//                 continue ;
//             }
//                 
//             foreach ($item as $group_id => $goods_item) {
//                 $productMapping = getGoodsIdStyleIdByProductId(intval($group_id)) ;        
//                 $goods_name = $productMapping['goods_name'];
//                 $goods_id = intval($productMapping['goods_id']) ;
//                 $style_id = intval($productMapping['style_id']) ;
//                 
//                 $db->query(sprintf($itemSQL, $rows['group_id'], $goods_id, $style_id, $goods_name, $goods_item['goods_number'], $goods_item['goods_price']));
//                 
//                 $group_amount = $group_amount + intval($goods_item['goods_number']) * floatval($goods_item['goods_price']) ;
//             }
//             
//             $db->query(sprintf($updateSQL, $group_amount, $key)) ;
//             
//         }


        
      }while(false);
    
}

else if ($_REQUEST ['act'] == 'input_old_inventory') {
	require_once(ROOT_PATH . 'admin/function.php');
	$result = create_deliver_old_inventory(
					$_REQUEST['order_id'],
					$_REQUEST['goods_id'],
					$_REQUEST['style_id'],
					$_REQUEST['facility_id'],
					$_REQUEST['serialNo']);
	if($result['success']){
		$smarty->assign ( 'result', $result['success'] );
	}
}

// 一键修复
else if($_REQUEST ['act'] == 'batch_modify_qoh_atp'){
    require_once(ROOT_PATH.'admin/includes/lib_modify_data.php');
    autoModifyAtpAndQoh();
}

//新老库存维护（原因：-t取消追回bug，新老库存均没入库（先数据修掉全部未入库再用工具改）; 修复：全部入库） ljzhou 2013-10-12
else if ($_REQUEST ['act'] == 'edit_old_new_t') {
	require_once(ROOT_PATH . 'admin/includes/lib_order.php');
	$order_id = trim($_REQUEST['t_order_id']);
	if(empty($order_id)) {
		$smarty->assign ( 'message', 'order_id 为空：'.$order_id );
		break;
	}
	$result = generate_return_all_back_order($order_id, 'cancel');
	if($result){
		$smarty->assign ( 'message', '修改成功：'.$result );
	}
}else if($_REQUEST ['act'] == 'export_atp_excel'){
	$sql = "select im.product_id,im.facility_id,  im.inventory_summary_id,  im.stock_quantity,im.available_to_reserved,
         sum(if(ird.status = 'Y',ird.reserved_quantity,0)) as reserved,( im.available_to_reserved + sum(if(ird.status = 'Y',ird.reserved_quantity,0)) -  im.stock_quantity) as diff
	     from romeo.inventory_summary im
	     left join romeo.order_inv_reserved_detail ird 
	               ON im.product_id = ird.product_id and im.facility_id = ird.facility_id and  im.status_id = ird.status_id
		 left join ecshop.ecs_order_info oi ON ird.order_id = oi.order_id
	     where im.status_id = 'INV_STTS_AVAILABLE' 
           -- and im.last_updated_stamp > '2013-11-6'
           -- 排除发货完但是还没还原预定的订单
		 and oi.shipping_status not in(1,2,3,8,9,11,12)
	     group by im.product_id, im.facility_id 
       having diff <>0";
     $lists = $db->getAll ($sql);
     $i = 1; 
     export_sheet($i,$lists);
 }
 else if($_REQUEST ['act'] == 'repair_order_new_inventory'){
 	 $order_id = trim($_REQUEST['order_id']);

     $sql = "select order_id
              from ecshop.ecs_order_info 
             where order_id = '{$order_id}' and order_status != '4' and shipping_status != '3'
          ";
     $order = $db->getRow($sql);
     if(empty($order)){
     	$smarty->assign ( 'message', "订单号{$order_id}不存在或已产生售后");
     }else{
     	require_once(ROOT_PATH.'RomeoApi/lib_inventory.php');
     	$result = oneKeyOrderPick($order_id);
     	if($result['success']){
     		$smarty->assign ( 'message', "订单号{$order_id}出库成功");
     	}else{
     		$smarty->assign ( 'message', "订单号{$order_id}失败，{$result['error']}");
     	}
     }
 }
  else if($_REQUEST ['act'] == 'del_inv_item_detail'){
 	 $inventoryItemDetailId = trim($_REQUEST['inventory_item_detail_id']);
     $sql = "select order_id
              from romeo.inventory_item_detail  
             where inventory_item_detail_id = '{$inventoryItemDetailId}'
          ";
     $order = $db->getRow($sql);
     if(empty($order)){
     	$smarty->assign ( 'message', "$inventoryItemDetailId{$inventoryItemDetailId}不存在");
     }else{
     	require_once(ROOT_PATH.'RomeoApi/lib_inventory.php');
     	$result = delInvItemDetail($inventoryItemDetailId);
     	if($result['success']){
     		$smarty->assign ( 'message', "$inventoryItemDetailId{$inventoryItemDetailId}删除成功");
     	}else{
     		$smarty->assign ( 'message', "$inventoryItemDetailId{$inventoryItemDetailId}删除失败，{$result['error']}");
     	}
     }
 }
 else if($_REQUEST ['act'] == 'del_old_inv'){
 	$order_id = trim($_REQUEST['order_id']);
     $sql = "select order_id
              from ecshop.ecs_order_info 
             where order_id = '{$order_id}'
          ";
     $order = $db->getRow($sql);
     if(empty($order)){
     	$smarty->assign ( 'message', "订单号{$order_id}不存在或已产生售后");
     }else{
     	$result = delOldInv($order_id);
     	if($result['success']){
     		$smarty->assign ( 'message', "订单号{$order_id}出库成功");
     	}else{
     		$smarty->assign ( 'message', "订单号{$order_id}失败，{$result['error']}");
     	}
     }
 }
 else if($_REQUEST ['act'] == 'delete_oukoo_erp_record'){
 	$order_id = trim($_REQUEST['order_id']);
 	do{
 		//1.判断是-T，2.老库存已经入库，新库存没有入库，3.并且老库存入库数!=goods_number
	 	$sql = "select order_id  from  `ecshop`.`ecs_order_info` where order_id = '{$order_id}' and order_type_id = 'RMA_RETURN'";
	 	 // QLog::log ( '---query-order-sql--'.$sql);
	 	$order = $db->getOne($sql);
	 	if(empty($order)){
	 		$smarty->assign ( 'message', "订单号{$order_id} 删除失败,-t订单号不存在");
	 		break;
	 	}
 		$sql =" select order_id from romeo.inventory_item_detail where order_id = '{$order_id}' ";
 		// QLog::log('---query_inventory--'.$sql);
	    $inventory= $db->getCol($sql);
	    if(empty($inventory)){
	    	$sql = "SELECT rec_id  from `ecshop`.`ecs_order_goods`  WHERE order_id = '{$order_id}' ";
            // QLog::log('---query_goods_id--'.$sql);
            $order_goods_id = $db->getCol($sql);
	    	//删除动作
	    	$sql = "DELETE  from  `ecshop`.`ecs_order_info` WHERE order_id = '{$order_id}' AND order_type_id = 'RMA_RETURN'";
	    	$db->query($sql);
	    	$sql = "DELETE  from  `ecshop`.`ecs_order_goods` WHERE order_id = '{$order_id}' ";
	    	$db->query($sql);
	    	$sql = "DELETE  from  `ecshop`.`ecs_oukoo_erp` WHERE order_goods_id  ". db_create_in ($order_goods_id);
	    	// QLog::log('---DELETE--'.$sql);
	    	$db->query($sql);
	    	$smarty->assign ( 'message', "订单号{$order_id} 成功删除老库存记录");
	    }else{
	    	$smarty->assign ( 'message', "订单号{$order_id} 新库存有记录，删除失败");
	    }

 	}while(false);
 	
 }
 else if($_REQUEST ['act'] == 'update_location_party'){
 	$order_id = trim($_REQUEST['order_id']);
 	do{
 		require_once (ROOT_PATH . 'includes/debug/lib_log.php');
		require_once (ROOT_PATH . 'admin/function.php');

		$location_barcode = trim($_POST ['location_barcode']);
		$party_id = trim($_POST ['party_id']);

		if (empty ( $location_barcode ) || empty ( $party_id )) {
			$smarty->assign ( 'message', '请填写location_barcode 和 party_id' );
			break;
		}

 		$sql =" select location_barcode from romeo.location where location_barcode = '{$location_barcode}' limit 1";
 		// QLog::log('---select location_party $sql--'.$sql);
	    $location_barcode= $db->getOne($sql);
	    if(!empty($location_barcode)){
	    	//删除动作
	    	$sql = "update romeo.location set party_id = '{$party_id}' where location_barcode = '{$location_barcode}' limit 1";
	    	// QLog::log('---update location_party $sql--'.$sql);
	    	
	    	$db->query($sql);
	    	
	    	$smarty->assign ( 'message', "修改成功");
	    }else{
	    	$smarty->assign ( 'message', "容器条码不存在");
	    }

 	}while(false);
 	
 }
 else if($_REQUEST ['act'] == 'reset_order_status'){//将订单状态由批拣中回推到待配货，方便客户操作
 	$order_id = isset($_REQUEST['order_id'])?$_REQUEST['order_id']:false;
 	$reset_order_status_message = '';
 	if($order_id){
 		$sql = "select shipment_id from romeo.shipment where primary_order_id = '{$order_id}'";
 		$shipment_id = $db->getOne($sql);
 		if($shipment_id){
 			$sql = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
 			$db->query($sql);
 			$sql = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}'";
 			$db->query($sql);
 			$sql = "update ecshop.ecs_order_info set shipping_status = '0' where order_id = '{$order_id}'";
 			$db->query($sql);
 			$reset_order_status_message = "orderId为{$order_id}的订单已经修改";
 		}else{
 			$reset_order_status_message = "orderId为{$order_id}没有对应的shipment，请核查";
 		}
 	}else{
 		$reset_order_status_message = '没有输入订单号';
 	}
 	$smarty->assign('reset_order_status_message',$reset_order_status_message);
 }else if($_REQUEST ['act'] == 'shelve_goods'){
	$party_id = isset($_REQUEST['party_id'])?$_REQUEST['party_id']:false;
	$facility_id = isset($_REQUEST['facility_id'])?$_REQUEST['facility_id']:false;
	$barcode = isset($_REQUEST['barcode'])?$_REQUEST['barcode']:false;
	$location_barcode = isset($_REQUEST['location_barcode'])?$_REQUEST['location_barcode']:false;
	
	if($party_id && $facility_id && $barcode && $location_barcode){
		$sql = "select product_id
			from ecshop.ecs_goods_style egs
			inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
			inner join romeo.product_mapping pm on pm.ecs_goods_id = egs.goods_id and pm.ecs_style_id = egs.style_id
			where egs.barcode = '{$barcode}'
			and eg.goods_party_id = '{$party_id}'";
		$product_id = $db->getOne($sql);
		if(!$product_id){
			$sql = "select product_id
				from ecshop.ecs_goods eg 
				inner join romeo.product_mapping pm on pm.ecs_goods_id = eg.goods_id 
				where eg.barcode = '{$barcode}'
				and eg.goods_party_id = '{$party_id}'		
			";
			$product_id = $db->getOne($sql);
		}
		
		if($product_id){
			$location_id = $db->getOne("select location_id from romeo.location where location_barcode = '{$location_barcode}'");
			if($location_id){
				$sql = "
					insert into romeo.inventory_location values(null,'{$location_barcode}','0','{$barcode}','{$product_id}',10000,10000,'1970-01-01','{$party_id}','{$facility_id}','INV_STTS_AVAILABLE','{$_SESSION['user_name']}',now(),now(),'{$location_id}');
				";
				$result = $db->query($sql);
				if($result){
					$shelve_goods_msg = "数据库执行成功,已经成功的上架";
					$smarty->assign('party_id',$party_id);
					$smarty->assign('facility_id',$facility_id);
				}else{
					$shelve_goods_msg = "数据库执行不成功";
				}			
			}else{
				$shelve_goods_msg = "根据barcode为：".$location_barcode ."找不到记录";			
			}
		}else{
			$shelve_goods_msg = "根据location_barcode为：".$barcode ."在业务组：".$party_id."找不到商品";
		}
	}else{
		$shelve_goods_msg = '没有输入对用的party_id或者facility_id或者barcode或者location_barcode';
	}	
	$smarty->assign('shelve_goods_msg',$shelve_goods_msg);
 }
 
$smarty->assign('order_status_list',$_CFG['adminvars']['order_status']);  // 订单状态
$smarty->assign('pay_status_list',$_CFG['adminvars']['pay_status']);  // 支付状态
$smarty->assign('shipping_status_list',$_CFG['adminvars']['shipping_status']);  // 物流状态
$smarty->assign('taobao_shop_list', $taobao_shop_list);  // 淘宝店列表
$smarty->assign('action_group_list', $action_group_list);  // 权限组列表
$smarty->assign('status_id_list', $status_id_list);  // 新旧

$smarty->display('toolkit.htm');

function export_sheet($i,$lists){
	set_include_path ( get_include_path () . PATH_SEPARATOR . './includes/Classes/' );
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	$excel = new PHPExcel();
	if ($i == 1) {
		$sheet = $excel->getActiveSheet ();
	} else {
		$sheet = '$sheet' . $i;
		$sheet = $excel->createSheet ();
	}
    $excel->getProperties()->setTitle('atp不等数据');
    $sheet->setCellValue('A1', "product_id");
	$sheet->setCellValue('B1', "facility_id");
	$sheet->setCellValue('C1', "inventory_summary_id");
	$sheet->setCellValue('D1', "stock_quantity");
	$sheet->setCellValue('E1', "available_to_reserved");
	$sheet->setCellValue('F1', "reserved");
	$sheet->setCellValue('G1', "diff");
	$i = 2;
    foreach ($lists as $list) {
        $sheet->setCellValueExplicit("A{$i}", $list['product_id'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B{$i}", $list['facility_id'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("C{$i}", $list['inventory_summary_id'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("D{$i}", $list['stock_quantity'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("E{$i}", $list['available_to_reserved'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("F{$i}", $list['reserved'], PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("G{$i}", $list['diff'], PHPExcel_Cell_DataType::TYPE_STRING);
        $i++;
     }
     if (! headers_sent ()){
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment; filename="atp不等式.xlsx"' );
		header ( 'Cache-Control: max-age=0' );
		$output = PHPExcel_IOFactory::createWriter ( $excel, 'Excel2007' );
		$output->setOffice2003Compatibility ( true );
		$output->save ( 'php://output' );
	}
}
function delOldInv($orderId){
	global $db;
	$sql = "
 		select  
 			oi.order_id,oi.facility_id,pm.ecs_goods_id,pm.ecs_style_id,
 			ifnull(count(e.out_sn),0) as sum_goods_number
		from  ecshop.ecs_order_info oi  
		inner join ecshop.ecs_order_goods og  
			on oi.order_id=og.order_id  
		inner join romeo.product_mapping pm  
			on og.goods_id=pm.ecs_goods_id and og.style_id=pm.ecs_style_id  
	 	left join ecshop.ecs_oukoo_erp AS e
	     	on e.order_goods_id = og.rec_id 
		where oi.order_id = '{$orderId}' AND e.out_sn = '' AND e.in_sn = ''
		group by pm.product_id
 	";
 	$orders = $db->getAll ($sql);
 	foreach($orders as $order){
	 		$sum_number = $order['sum_goods_number'];
	 		if($sum_number > 0){
	 		 	if(!create_deliver_old_inventory_tools($order['order_id'],$order['ecs_goods_id'],
	 				$order['ecs_style_id'],$order['facility_id'],null,$sum_number))
	 			{
	 				$result['success'] = false;
	 				$result['error'] = "老库存数量不够: [order_id = ".$order['order_id']."][ecs_goods_id = ".$order['ecs_goods_id']."][ecs_style_id = ".$order['ecs_style_id']."][facility_id = ".$order['facility_id']."]";
	 				return $result;
	 			}
	 		}
	 }
	 $result['success'] = true;
	 return $result;
}
function create_deliver_old_inventory_tools($order_id,$goods_id,$style_id,$facility_id,$serialNo,$number){
 	global $db;
 	if(empty($number)){
 		$number = 1;
 	}
    //from 
	 $sql = "
		SELECT 
        	og.goods_id, og.style_id, e.in_sn,
        	e.purchase_paid_type, e.erp_goods_sn, e.purchase_paid_amount, 
        	e.order_type, e.provider_id, e.is_new, e.facility_id
		FROM ecshop.ecs_oukoo_erp AS e
        	LEFT JOIN ecshop.ecs_order_goods AS og ON og.rec_id = e.order_goods_id
		WHERE e.in_sn <> '' AND e.out_sn = ''
        	AND e.facility_id = '{$facility_id}'
        	AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND e.erp_goods_sn <> ''
        	AND e.is_new = 'NEW'
        	AND NOT EXISTS (SELECT 1 FROM ecshop.ecs_oukoo_erp WHERE out_sn = e.in_sn) 
		LIMIT {$number}
        	";
	$ines = $db->getAll($sql);
	if(empty($ines)){
		print_r($sql);
		return false;
	}
	//to 
	$sql = "
		SELECT e.erp_id
		FROM ecshop.ecs_oukoo_erp AS e
		LEFT JOIN ecshop.ecs_order_goods AS og  ON og.rec_id = e.order_goods_id 
		WHERE og.order_id = '{$order_id}'
			AND e.out_sn = '' AND e.in_sn = ''
			AND og.goods_id ='{$goods_id}' AND og.style_id = '{$style_id}'
		LIMIT {$number}
			";
	$erp_ids = $db->getAll($sql);
	if(count($erp_ids) != count($ines)){
		print_r($erp_ids);
		print_r($ines);
		return false;
	}
	deliver_old_inventory_real_tools($erp_ids,$ines);
	return true;	
 }
function deliver_old_inventory_real_tools($erp_ids,$ines){
	global $db;
	for($i= 0;$i< count($erp_ids); $i++){
		$ine = $ines[$i];
		$erp_id = $erp_ids[$i]['erp_id'];
		$sql = "
			UPDATE ecshop.ecs_oukoo_erp
			SET purchase_paid_type   = '{$ine['purchase_paid_type']}',
	        	erp_goods_sn         = '{$ine['erp_goods_sn']}',
	        	purchase_paid_amount = '{$ine['purchase_paid_amount']}',
	        	order_type           = '{$ine['order_type']}',
	        	provider_id          = '{$ine['provider_id']}',
	        	is_new               = '{$ine['is_new']}',
	        	in_time              = IF(in_time > '1900-1-1', in_time, NOW()), 
	        	out_sn               = '{$ine['in_sn']}',
	        	facility_id          = '{$ine['facility_id']}', 
	        	last_update_time     = NOW(), 
	        	action_user          = '{$_SESSION['admin_name']}'
			WHERE erp_id = '{$erp_id}' 
	    		";
		$db->query($sql);
	}
}

function get_gt_info($order_sn) {
	global $db;
	$sql = "select oi.order_id,sr.supplier_return_id,sr.party_id,
	oi.facility_id,og.status_id,pm.product_id,og.goods_number,sr.inventory_item_type_id,sr.purchase_unit_price
	from  romeo.supplier_return_request sr 
	inner join romeo.supplier_return_request_gt gt ON sr.supplier_return_id = gt.supplier_return_id
	inner join ecshop.ecs_order_info oi ON gt.SUPPLIER_RETURN_GT_SN = oi.order_sn
	inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
	inner join romeo.product_mapping pm on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
	where oi.order_sn = '{$order_sn}' limit 1";
	// Qlog::log('get_gt_info:'.$sql);
	$gt_info = $db->getRow($sql);
	if(empty($gt_info)) {
		return null;
	}
	return $gt_info;
}


 
 