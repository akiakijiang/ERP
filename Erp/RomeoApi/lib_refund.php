<?php

/**
 * 退款服务功能库
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

require_once('lib_soap.php');
require_once('lib_cache.php');

// 退款状态
define('RFND_STTS_INIT',     'RFND_STTS_INIT');     // 已生成
define('RFND_STTS_IN_CHECK', 'RFND_STTS_IN_CHECK'); // 处理中
define('RFND_STTS_CHECK_OK', 'RFND_STTS_CHECK_OK'); // 已退款
define('RFND_STTS_EXECUTED', 'RFND_STTS_EXECUTED'); // 已完成
define('RFND_STTS_CANCELED', 'RFND_STTS_CANCELED'); // 已取消

/**
 * 退款申请列表
 * 
 * @param array $conditions 查询条件
 * @param boolean $last_update_timestamp 是否只返回最后更新时间
 * 
 * @return array
 */
function refund_get_all_by_conditions($conditions = array(), $last_update_timestamp = false)
{ 
    global  $_CFG;
    $handle = refund_get_soap_client();
    $args = _refund_helper_conditions($conditions);

    // 只返回最新的更新时间戳
    if ($last_update_timestamp)
    {
        try
        {
            unset($args['offset'], $args['limit']);
            if (is_array($args['orderId']))
                return $handle->getLastUpdateTimeByOrderIdsCondition($args)->return;
            else
                return $handle->getLastUpdateTimeByCondition($args)->return;
        }
        catch (SoapFault$e)
        {
            return 0;
        }
    }
    try
    {
        if (is_array($args['orderId']) && count($args['orderId'])!=0){ // order_id 是数组
            $result = $handle->getRefundByOrderIdsCondition($args);
		}else if(!is_array($args['orderId'])){
			$result = $handle->getRefundByCondition($args); 
		}else{
			trigger_error("没有查到订单信息，可能是没有对应仓库权限");
			$result = false;
		}
            
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP查询退款记录列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        $result = false;
    }
    $list = array();
    if (isset($result) && isset($result->return->result->Refund))
    {
        $_status_list = refund_status_list(); // 状态mapping
        $_payment_type_list = refund_payment_type_list(); // 退款支付类型mapping
        $_refund_type_list = refund_type_list(); // 退款申请类型mapping

        if (is_object($result->return->result->Refund))
            $list[0] = $result->return->result->Refund;
        else if (is_array($result->return->result->Refund))
            $list = $result->return->result->Refund;
        //var_dump($list);

        $oIds = array();
        $radix = 0;
        $giveUpApproveOrderIds = array(); 
        foreach ($list as $key => $item)
        {
            if ($item->status == RFND_STTS_INIT)
            {  
                if( !empty($item->checkDate2) ) {
                    $giveUpApproveOrderIds[] = $item->orderId; 
                }
                // 取得时间间隔
                $time_desc = refund_helper_time_format(strtotime($item->createdStamp), $radix);
                if ($radix > 1) // 小时的级别
                    $list[$key]->timeDescription = $time_desc;
            }
            
            $list[$key]->statusName = $_status_list[$item->status];
            $list[$key]->refundPaymentTypeName = $_payment_type_list[$item->refundPaymentTypeId][1];
            $list[$key]->refundTypeName = $_refund_type_list[$item->refundTypeId];
            if($item->status == RFND_STTS_INIT && !empty($item->checkDate2) && $args['status_orig']==RFND_STTS_INIT){
            unset($list[$key]);            
            }
            else if($item->status == RFND_STTS_INIT && empty($item->checkDate2) && $args['status_orig']==RFND_STTS_INIT2){
            unset($list[$key]);
            }
            else{
            $oIds[] = $item->orderId;
            }
        }
        //var_dump($giveUpApproveOrderIds);
        if(!empty($giveUpApproveOrderIds)){
        //从数据库中取得财务弃审时的备注信息
        $sql = "SELECT order_id,action_note,action_time
              from ecshop.ecs_order_action where  ".db_create_in($giveUpApproveOrderIds, 'order_id')." ORDER BY action_time desc "; 
        $disapprove_order_actions = $GLOBALS['db']->getAll($sql); 
        $giveUpApproveRemarks = array();
        foreach ($disapprove_order_actions as $key => $value) {
            $order_id = $value['order_id']; 
            if(!isset($giveUpApproveRemarks[$order_id])){
                $action_note = $value['action_note'];
                if( strpos($action_note,'弃审') ){
                    $giveUpApproveRemarks[$order_id] = $action_note; 
                }
            }
        }
        }
        else{$giveUpApproveRemarks="";}

        //取得每条退款单记录对应的订单详情
        $sql = "
			SELECT 
				o.order_id, o.order_sn, o.order_time, o.consignee, o.pay_name as order_pay_name, 
				p.pay_code, p.pay_name, u.userId, u.user_name, o.email, oa.attr_value as important_day
			FROM {$GLOBALS['ecs']->table('order_info')} AS o 
				LEFT JOIN {$GLOBALS['ecs']->table('payment')} p ON p.pay_id = o.pay_id
				LEFT JOIN {$GLOBALS['ecs']->table('users')} u ON u.user_id = o.user_id
				LEFT JOIN ecshop.order_attribute oa ON oa.order_id = o.order_id AND attr_name = 'important_day' 
			 WHERE " . db_create_in($oIds, 'o.order_id') . "
		";
       // var_dump(db_create_in($oIds, 'o.order_id'));
       // var_dump($sql);
        $result = $GLOBALS['db']->query($sql);
        //var_dump($result);
        $orders = array();
        if ($result)
        {
            while ($row = $GLOBALS['db']->fetchRow($result))
            {
                $orders[$row['order_id']] = (object)$row;
            }
        }
        // jjs 退款原因
        $sql = "select * from romeo.refund_detail_reason where reason like 'jjs%' and visible = 'Y'";
        $refund_detail_reason_jjs_ids = array();
        $_tmp = $GLOBALS['db']->getAll($sql);
        foreach ($_tmp as $t) {
        	$refund_detail_reason_jjs_ids[$t['REFUND_DETAIL_REASON_ID']] = $t['REASON'];
        }
        //var_dump($list);
        // 组装数据
        foreach ($list as $key => $item)
        {   
            $list[$key]->orderInfo = $orders[$item->orderId];
            if(isset($giveUpApproveRemarks[$item->orderId])){
                 $list[$key]->giveUpRemark = $giveUpApproveRemarks[$item->orderId]; 
            }
            // {{{ 退款原因@FIXME
            $sql = "SELECT GROUP_CONCAT(rd.note separator '<br>') AS note 
            		FROM romeo.refund r
            			LEFT JOIN romeo.refund_detail rd ON rd.refund_id = r.refund_id
            		WHERE r.refund_id = '{$item->refundId}'";
            $list[$key]->refundReason = $GLOBALS['db']->getOne($sql);
            // }}}
            // {{{ cms订单号
            $sql = "SELECT oi.taobao_order_sn, oi.order_sn, oi.order_amount as order_amount_exchange, 
            			(select attr_value from ecshop.order_attribute oa where oa.order_id = oi.order_id and oa.attr_name = 'order_amount') 
            				as order_amount, 
            			(select attr_value from ecshop.order_attribute oa where oa.order_id = oi.order_id and oa.attr_name = 'goods_amount') 
            				as goods_amount 
            		FROM ecshop.ecs_order_info oi
            		WHERE oi.order_id = '" . intval($item->orderId) . "'";
            $row = $GLOBALS['db']->getRow($sql);
            $list[$key]->orderInfo->order_sn = $row['order_sn'];
            $list[$key]->orderInfo->taobao_order_sn = $row['taobao_order_sn'];
            $list[$key]->orderInfo->order_amount = $row['order_amount'];
	        $list[$key]->orderInfo->order_amount_exchange = $row['order_amount_exchange'];
            $list[$key]->orderInfo->goods_amount = $row['goods_amount'];
            // }}}
            // {{{ 退款汇总
            $sql = "SELECT refund_detail_reason_id FROM romeo.refund_detail WHERE refund_id = '{$item->refundId}'";
            $list[$key]->refund_detail_reason_id = $GLOBALS['db']->getOne($sql);
            // }}}
            // {{{ 收到退货再退款的需要特殊处理一下
            if (stripos($list[$key]->orderInfo->order_sn, '-t') !== false) {
            	$sql = "SELECT oi.taobao_order_sn, oi.order_sn, oi.order_amount as order_amount_exchange, 
	            			(select attr_value from ecshop.order_attribute oa where oa.order_id = oi.order_id and oa.attr_name = 'order_amount') 
	            				as order_amount, 
	            			(select attr_value from ecshop.order_attribute oa where oa.order_id = oi.order_id and oa.attr_name = 'goods_amount') 
	            				as goods_amount 
	            		FROM ecshop.order_relation `or`
	            			LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = `or`.parent_order_id
	            		WHERE `or`.order_id = '" . intval($item->orderId) . "'";
	            $row = $GLOBALS['db']->getRow($sql);
	            $list[$key]->orderInfo->order_sn = $row['order_sn'];
	            $list[$key]->orderInfo->taobao_order_sn = $row['taobao_order_sn'];
	            $list[$key]->orderInfo->order_amount = $row['order_amount'];
	            $list[$key]->orderInfo->order_amount_exchange = $row['order_amount_exchange'];
	            $list[$key]->orderInfo->goods_amount = $row['goods_amount'];
            }
            // }}}
        }
        // {{{ 汇总, for jjshouse
        $summary = array();
        foreach ($list as $key => $item) {
        	$summary[$item->refund_detail_reason_id]['order_amount'] += $item->orderInfo->order_amount;
        	$summary[$item->refund_detail_reason_id]['goods_amount'] += $item->orderInfo->goods_amount;
        	$summary[$item->refund_detail_reason_id]['order_sn'][] = $item->orderInfo->taobao_order_sn;
        	$summary[$item->refund_detail_reason_id]['reason']= $refund_detail_reason_jjs_ids[$item->refund_detail_reason_id];
        }
        foreach ($summary as $key => $item) {
        	$summary[$key]['order_sn'] = join("<br>", $summary[$key]['order_sn']);
        }
        $GLOBALS['smarty']->assign("summary", $summary);
        // }}}
    }

    return $list;
}

/**
 * 按条件查询退款单总数
 * 
 * @param array $conditions
 * 
 * @return int
 */
function refund_get_count_by_conditions($conditions)
{
    try
    {
        $handle = refund_get_soap_client();
        $args = _refund_helper_conditions($conditions);
        $args['offset'] = 0; // offset
        $args['limit'] = 0; // limit
        if (is_array($args['orderId'])) // order_id 是数组
            $result = $handle->getRefundByOrderIdsCondition($args);
        else
            $retult = $handle->getRefundByCondition($args);
        return $result->return->count;
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP查询退款记录总数失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return 0;
    }
}

/**
 * 返回比refund_get_all_by_conditions方法更详细的退款单列表，包括所有的mapping
 * 
 * @param array $conditions 查询条件
 * @param boolean $last_update_timestamp 是否只返回最后更新时间
 * 
 * @return array
 */
function refund_get_all_with_detail_by_conditions($conditions)
{
    $handle = refund_get_soap_client();
    $args = _refund_helper_conditions($conditions);

    // 查询出列表
    try
    {
        $result = $handle->getRefundWithDetailsByCondition($args);
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP查询退款记录及详情失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return array();
    }

    $count = $result->return->count; // 总记录数
    $list = array(); // 列表
    if (isset($result->return->result->RefundInfo))
    {
        if (is_object($result->return->result->RefundInfo))
            $list[0] = $result->return->result->RefundInfo;
        else if (is_array($result->return->result->RefundInfo))
            $list = $result->return->result->RefundInfo;
    }



    // 取得每条退款单记录对应的订单详情
    if (!empty($list))
    {
        $oIds = array();
        foreach ($list as $k => $v)
        {
            if (is_object($v->refundDetail->RefundDetail))
            {
                $list[$k]->refundDetail->RefundDetail = array($v->refundDetail->RefundDetail);
            }
            else if (is_array($v->refundDetail->RefundDetail))
            {

            }
            else
            {
                $list[$k]->refundDetail->RefundDetail = array();
            }

            $oIds[] = $list[$k]->orderId;
        }

        $sql = "
			SELECT o.order_id, o.order_sn, o.order_time, o.consignee, o.pay_name as order_pay_name, p.pay_code, p.pay_name, u.userId, u.user_name
			FROM {$GLOBALS['ecs']->table('order_info')} AS o LEFT JOIN {$GLOBALS['ecs']->table('payment')} p ON p.pay_id = o.pay_id
				LEFT JOIN {$GLOBALS['ecs']->table('users')} u ON u.user_id = o.user_id
			WHERE " . db_create_in($oIds, 'o.order_id') . "
		";
        $GLOBALS['db']->getAllRefby($sql, array('order_id'), $ref_value, $ref, false);
        // 组装数据
        foreach ($list as $k => $v)
        {
            $list[$k]->orderInfo = (object)$ref['order_id'][$v->orderId][0];
        }
    }

    return $list;
}

/**
 * 通过条件主键获得退款单信息
 * 
 * @param int $pkv 退款明细项id
 * 
 * @return object 失败返回FALSE
 */
function refund_get_one_by_conditions($conditions)
{
    $handle = refund_get_soap_client();
    $obj = false;

    // 设置了主键则通过主键取得
    if (isset($conditions['refund_id']))
    {
        try
        {
            $result = $handle->getRefundInfoById(array('arg0' => $conditions['refund_id']));
            $obj = isset($result->return) ? $result->return : false;
        }
        catch (SoapFault$e)
        {
            trigger_error("SOAP获取退款记录失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        }
    }
    // 通过条件查询取得
    else
    {
        try
        {
            $args = _refund_helper_conditions($conditions);
            $args['limit'] = 1; // limit
            if (is_array($args['orderId'])) $args['orderId'] = reset($args['orderId']);  // order_id
            $result = $handle->getRefundByCondition($args);
        }
        catch (SoapFault$e)
        {
            trigger_error("SOAP查询退款记录失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        }
        if (isset($result) && isset($result->return->result->Refund))
            $obj = $result->return->result->Refund;
    }
    return $obj;
}

/**
 * 通过主键获得退款单的详细信息，包括商品明细和其他明细
 * 
 * @param int $pkv 退款明细项id
 * 
 * @return object 失败返回FALSE
 */
function refund_get_detail_by_pk($pkv)
{
    $handle = refund_get_soap_client();
    try
    {
        $result = $handle->getRefundInfoById(array('arg0' => $pkv));
        $obj = isset($result->return) ? $result->return : false;

        if ($obj)
        {
            // 取得退款商品明细
            $goods = $handle->getRefundGoodsDetailByRefundId(array('arg0' => $obj->refundId));
            if (isset($goods->return->RefundDetail))
            {
                if (is_object($goods->return->RefundDetail))
                    $obj->goodsDetail[0] = $goods->return->RefundDetail;
                else if (is_array($goods->return->RefundDetail))
                    $obj->goodsDetail = $goods->return->RefundDetail;

                // 该订单商品的mapping
                $_order_goods = order_goods($obj->orderId);
                $_order_goods_map = array();
                foreach ($_order_goods as $g)
                {
                    $_order_goods_map[$g['rec_id']] = (object)$g;
                }

                // 取得商品详细信息
                foreach ($obj->goodsDetail as $key => $goods)
                {
                    $obj->goodsDetail[$key]->orderGoods = $_order_goods_map[$goods->orderGoodsId];
                }
            }

            // 取得退款其他明细
            $others = $handle->getRefundOthersDetailByRefundId(array('arg0' => $obj->refundId));
            if (isset($others->return->RefundDetail))
            {
                if (is_object($others->return->RefundDetail))
                    $obj->othersDetail[0] = $others->return->RefundDetail;
                else if (is_array($others->return->RefundDetail))
                    $obj->othersDetail = $others->return->RefundDetail;
            }
        }
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP获取退款记录详细信息失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        $obj = false;
    }

    return $obj;
}

/**
 * 通过主键获得退款明细类型
 * 
 * @param int $pkv 退款明细项id
 * 
 * @return object
 */
function refund_get_refund_detail_type($pkv)
{
    $handle = refund_get_soap_client();
    try
    {
        $result = $handle->getRefundDetailTypeById(array('arg0' => $pkv));
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP通过主键获取退款明细类型失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
    return isset($result->return) ? $result->return : null;
}

/**
 * 创建OR更新一条退款记录
 * 
 * @param array 
 * 
 * 输入的参数为一个多维数组，包括单头和详细条目，如
 *	array
 *	(
 *		'info'   =>  array(退款信息),
 *		'detail' =>  array
 *		(
 *			'goods'  => array(退款商品明细),
 *			'others' => array(退款其他明细),
 *		) 
 *	)
 * 
 * @return int (true) | false 成功返回退款单号，失败返回FALSE
 */
function refund_save($data,$claim_note)
{
    // 组合数据, 为soap提供可用的数据格式    
    if (isset($data['payment']))
    {
        $data['info'] = array_merge($data['info'], $data['payment']);
        unset($data['payment']); // $data['payment'] 为用户提交的账号信息
    }
    if (!isset($data['info']['created_by_user_login']))
    {
        $data['info']['created_by_user_login'] = $_SESSION['admin_name'];
    }
    // 删除原来的键，用于json
    if (!empty($data['detail']['goods']))
    {
        sort($data['detail']['goods']);
    }
    // 删除原来的键，用于json
    if (!empty($data['detail']['others']))
    {
        sort($data['detail']['others']);
    }

    $handle = refund_get_soap_client();
    // 如果传入了主键值则更新
    if (isset($data['refund_id']) && !empty($data['refund_id']))
    {
        $refund_id = $data['refund_id'];
        unset($data['refund_id']);
        try
        {
             //去掉退款申请列表中客户姓名中带\   by jrpei 2011-7-6
            $result = $handle->updateRefundById(array('arg0' => $refund_id, 'arg1' => json_encode(stripslashes_deep($data))));
            $sql1 = "select compensation_id
            from ecshop.claims_settlement
            where order_id = '{$data['info']['order_id']}' AND refund_id = '{$refund_id}' AND compensation_type = 'REFUND' ";
            $compensation = $GLOBALS['db']->getAll($sql1);
            if(count($compensation) < count($data['responsible_party'])){
                for($i = 0;$i < (count($data['responsible_party']) - count($compensation));$i++){
                    $sql = "INSERT INTO ecshop.claims_settlement(order_id, refund_id, responsible_party, compensation_type, compensation_amount, freight, is_claim, is_delete, note, created_stamp, last_updated_stamp)
                    VALUES({$data['info']['order_id']}, '{$refund_id}', '{$data['responsible_party'][count($data['responsible_party'])-$i-1]}', 'REFUND', {$data['compensation_amount'][count($data['responsible_party'])-$i-1]}, 0, 0, 0, '{$claim_note}', now(), now())";
                    $GLOBALS['db']->query($sql);
                }
            }
            if(count($compensation) > count($data['responsible_party'])){
                for($i = 0;$i < (count($compensation) - count($data['responsible_party']));$i++){
                   $sql = "delete from ecshop.claims_settlement
                           where order_id = '{$data['info']['order_id']}' AND refund_id = '{$refund_id}' AND compensation_type = 'REFUND' AND compensation_id = '{$compensation[count($compensation)-$i-1]['compensation_id']}' ";
                   $GLOBALS['db']->query($sql);
                }
            }
            //退款申请修改理赔信息
            for($i = 0;$i < count($data['responsible_party']);$i++){
			     $sql = "UPDATE ecshop.claims_settlement 
            		SET responsible_party = '{$data['responsible_party'][$i]}', compensation_amount = '{$data['compensation_amount'][$i]}', last_updated_stamp = now() , note = '{$claim_note}'
					WHERE order_id = '{$data['info']['order_id']}' AND refund_id = '{$refund_id}' AND compensation_type = 'REFUND' AND compensation_id = '{$compensation[$i]['compensation_id']}' ";
			     $GLOBALS['db']->query($sql);
		      }
        }
        catch (SoapFault$e)
        {
            trigger_error("SOAP更新退款记录失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
            return false;
        }
    }
    // 添加
    else
    {
        try
        {
            $result = $handle->createRefund(array('arg0' => json_encode(stripslashes_deep($data))));
            // 退款申请成功后添加log到订单
            refund_add_order_action($data['info']['order_id'], '新建退款申请', $data['info']['created_by_user_login']);
            if ($data['taobao_refund_id']) {
                refund_update_taobao_info($data['taobao_refund_id'], $result->return);
            }
            
            //退款申请添加到理赔表
            for($i = 0;$i < count($data['responsible_party']);$i++){
	           $sql = "INSERT INTO ecshop.claims_settlement(order_id, refund_id, responsible_party, compensation_type, compensation_amount, freight, is_claim, is_delete, note, created_stamp, last_updated_stamp) 
						VALUES({$data['info']['order_id']}, '{$result->return}', '{$data['responsible_party'][$i]}', 'REFUND', {$data['compensation_amount'][$i]}, 0, 0, 0, '{$claim_note}', now(), now())";
	           $GLOBALS['db']->query($sql);
            }
        }
        catch (SoapFault$e)
        {
            trigger_error("SOAP 新建退款记录失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
            return false;
        }

        return $result->return;
    }

    return true;
}

/**
 * 退款审核
 * 
 * @param array
 * 
 * @return boolean
 */
function refund_check($input)
{
    $handle = refund_get_soap_client();

    try
    {
        $args = array();
        $args['arg0'] = $input['refund_id'];
        $args['arg1'] = $input['user'];
        $args['arg2'] = $input['level'];
        $args['arg3'] = $input['note'];

        $handle->approveRefund($args);

        // 退款审核信息log追加到订单
        $dep = refund_check_list();
        refund_add_order_action($input['order_id'], $dep[$input['level']] . "退款审核通过，备注：{$input['note']}", $_SESSION['admin_name']);
        
        //物流自动审核
        if($input['level']==1){
    		$input['level']=2;
    		$input['user']='system';
    		$input['note']='物流自动审核';
    		refund_check($input);
    		
    		//天猫待配货整单退款 类型 系统自动审核和执行退款
	        $refund_type_id = $GLOBALS['db']->getOne("select REFUND_TYPE_ID from romeo.refund where refund_id = ".$input['refund_id']." LIMIT 1");
	        if($refund_type_id == 8){
	        	$input['level']=4;
	    		$input['user']='system';
	    		$input['note']='售后主管自动审核';
	    		refund_check($input);
	    		
	    		$input['user']='system';
	        	$input['note']='系统自动退款';
	        	refund_execute($input);
	        }
        }
        
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP 退款审核失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * 退款确认
 * 
 * @param array
 * 
 * @return boolean
 */
function refund_execute($input)
{
    $handle = refund_get_soap_client();

    try
    {
        $args = array();
        $args['arg0'] = $input['refund_id'];
        $args['arg1'] = $input['user'];
        $args['arg2'] = $input['note'];

        $handle->executeRefund($args);

        // 退款审核信息log追加到订单
        refund_add_order_action($input['order_id'], "确认退款", $_SESSION['admin_name']);
        
        //天猫整单先款后货取消类型退款 系统自动把付款状态改成未付款
        $sql = "select refund_type_id from romeo.refund where refund_id = '{$input['refund_id']}' LIMIT 1";
        $refund_type_id = $GLOBALS['db']->getOne($sql);
        if($refund_type_id == 8){
        	$sql="UPDATE ecshop.ecs_order_info set pay_status = 0 where order_id = {$input['order_id']} LIMIT 1";
        	$GLOBALS['db']->query($sql);
        	refund_add_order_action($input['order_id'], "修改付款状态：已付款->未付款", $_SESSION['admin_name']);
        }
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP 退款确认失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * 退款弃审
 * 
 * @return boolean
 */
function refund_giveup($input)
{
    $handle = refund_get_soap_client();

    try
    {
        $handle->giveUpApprovedRefund(array('arg0' => $input['refund_id']));

        // 财务弃审log追加到订单
        refund_add_order_action($input['order_id'], "财务弃审，备注：{$input['note']}", $_SESSION['admin_name']);
        
        //天猫待配货退款 修改订单付款状态 未付款->已付款
        $refund_type_id = $GLOBALS['db']->getOne("select refund_type_id from romeo.refund where refund_id = '{$input['refund_id']}' LIMIT 1");
        if($refund_type_id == 8){
	        $GLOBALS['db']->query("update ecshop.ecs_order_info set pay_status = 2 where order_id = {$input['order_id']} LIMIT 1");
	    	refund_add_order_action($input['order_id'], "财务弃审，修改付款状态：未付款->已付款", $_SESSION['admin_name']);
        }
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP弃审失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * 取消退款
 * 
 * @return boolean
 */
function refund_cancel($input)
{
    $handle = refund_get_soap_client();

    try
    {
        $args = array();
        $args['arg0'] = $input['refund_id'];
        $args['arg1'] = $input['user'];
        $args['arg2'] = $input['note'];

        $handle->cancelRefund($args);

        // 取消log追加到订单
        refund_add_order_action($input['order_id'], "取消退款，备注：{$input['note']}", $_SESSION['admin_name']);
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP取消退款失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * 返回一个退款状态的列表
 * 
 * @return array
 */
function refund_status_list()
{
    return array
    (
        RFND_STTS_INIT     => '已生成', 
        RFND_STTS_IN_CHECK => '处理中',
        RFND_STTS_CHECK_OK => '已退款', 
        RFND_STTS_EXECUTED => '已完成', 
        RFND_STTS_CANCELED => '已取消',
        RFND_STTS_INIT2     => '已弃审', 
    );
}

/**
 * 审核列表 , 'level' => '部门名'
 * 
 * @return array
 */
function refund_check_list()
{
    return array('1' => '客服', '2' => '物流', '3' => '财务', '4' => '售后主管');
}

/**
 * 退款明细列表
 * 
 * @rterun array
 */
function refund_detail_type_list()
{
    $handle = refund_get_soap_client();
    try
    {
        $result = $handle->getAllRefundDetailType();
        $data = isset($result->return->RefundDetailType) ? $result->return->RefundDetailType : false;
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP获取退款明细列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
        $data = false;
    }

    $list = array();
    if ($data)
    {
        foreach ($data as $item)
        {
            if ($item->visible == 'Y' )
                $list[$item->refundDetailTypeId] = $item->description;
        }
    }

    return $list;
}

/**
 * 退款明细原因列表
 * 
 * @param string $category GOODS|OTHERS
 * 
 * @rterun array
 */
function refund_detail_reason_list($category = 'GOODS')
{
    $handle = refund_get_soap_client();
    try
    {
        $result = $handle->getAllRefundDetailReason(array('arg0' => $category));
        $data = isset($result->return->RefundDetailReason) ? $result->return->RefundDetailReason : false;
    }
    catch (SoapFault$e)
    {
        trigger_error("SOAP获取退款明细原因列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
        $data = false;
    }

    $list = array();
    if ($data)
    {
        foreach ($data as $item)
        {
            if ($item->visible == 'Y')
                $list[$item->refundDetailReasonId] = $item->reason;
        }
    }

    return $list;
}

/**
 * 返回支持的退款类型列表
 * 
 * @rterun array
 */
function refund_type_list()
{
    static $refund_type_list;
    
    if(!isset($refund_type_list)){
        $cache=RomeoApi_Cache::instance();
        if(($refund_type_list=$cache->get(__FUNCTION__))===false){
            try{
                $handle=refund_get_soap_client();
                $result=$handle->getAllRefundType();
            }
            catch(SoapFault $e){
                trigger_error("SOAP获取退款类型列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
            }
            
            $refund_type_list=array();
            if(isset($result->return->RefundType)){
                foreach($result->return->RefundType as $item){
                    $refund_type_list[$item->refundTypeId] = $item->refundTypeName;         
                }
            }
            $cache->set(__FUNCTION__,$refund_type_list);
        }
    }
	
    return $refund_type_list;
}

/**
 * 返回支持的退款方式列表
 * 
 * @return array
 */
function refund_payment_type_list()
{
    static $refund_payment_type_list;
	
    if(!isset($refund_payment_type_list)){
        $cache=RomeoApi_Cache::instance();
        if(($refund_payment_type_list=$cache->get(__FUNCTION__))===false){
            try{
                $handle=refund_get_soap_client();
                $response=$handle->getAllRefundPaymentType();
            }
            catch(SoapFault $e){
                trigger_error("SOAP获取退款支付方式列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
            }
            
            $refund_payment_type_list=array();
            if(isset($response->return->RefundPaymentType)){
                foreach($response->return->RefundPaymentType as $item){
                    $refund_payment_type_list[$item->refundPaymentTypeId]=array($item->code, $item->refundPaymentTypeName);
                }
                $cache->set(__FUNCTION__,$refund_payment_type_list);
            }
        }
    }
	
    return $refund_payment_type_list;
}

/**
 * 通过支付code取得支付方式
 * 
 * @param string $code 支付方式
 * @param string $default 默认支付方式，如果按支付code找不到支付方式，则用该支付方式
 * 
 * @return obj
 */
function refund_get_payment_type_by_code($code, $default = 'OTHERS')
{
    static $refund_payment_type_list;
    
    if (!isset($refund_payment_type_list))
    {
        $handle = refund_get_soap_client();
        try
        {
            $result = $handle->getAllRefundPaymentType();
            $refund_payment_type_list = isset($result->return->RefundPaymentType) ? $result->return->RefundPaymentType : false ;
        }
        catch (SoapFault$e)
        {
            trigger_error("SOAP获取退款支付方式列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
            $refund_payment_type_list = false;
        }   
    }
    
    if ($refund_payment_type_list)
    {
        $code = strtoupper($code);
        foreach ($refund_payment_type_list as $item) {
            if ($item->code == $code) { return $item; }
            if ($item->code == $default) { $default = $item; }
        }
        if (is_object($default)) { return $default; }
    }
    
    return false;   
}

/**
 * 取得soap客户端
 * 
 * @return object SoapClient
 */
function refund_get_soap_client()
{
    try
    {
        return soap_get_client('RefundService', 'ROMEO');
    }
    catch (Exception $e)
    {
        trigger_error("SOAP连接失败，所有的操作可能受限，请和管理员联系: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
    }
}

/**
 * 取得该订单的商品列表
 * 
 * @param int $order_id 订单id
 * @param string $order_type 订单类型
 * 
 * @return array
 */
function refund_order_goods_list($order_id, $order_type = NULL)
{
	global $db;
	// 查询订单类型
	if ($order_type == NULL) {
		$sql = "SELECT order_type_id FROM ecshop.ecs_order_info WHERE order_id = {$order_id}";
		$order_type = $db->getOne($sql, true);
	} 
	
	// 查询订单商品
	$sql = "
		SELECT 
			og.rec_id, og.goods_id, og.style_id, og.goods_name, og.market_price, og.goods_number, 
			og.goods_price, og.is_real, og.is_gift, og.goods_price * og.goods_number AS subtotal
		FROM
			ecshop.ecs_order_goods AS og 
		WHERE
			og.order_id = '{$order_id}'
	";	
	
	$order_goods = $db->getAll($sql);
	if (!$order_goods) { return array(); }
	
	if (!function_exists('getProductId'))
		require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
		
	foreach($order_goods as $key=>$order_good){
		$order_goods[$key]['productId'] = getProductId($order_good['goods_id'], $order_good['style_id']);
	}
	
    return $order_goods;
}

/**
 * 列出订单中不能退款的商品（和商品数量）
 *
 * @param int $order_id
 * @param string $order_type 订单类型
 * 
 * @return mixed  如果有不在库存的商品，则返回不在库存中的商品的列表，否则返回FALSE
 */
function refund_order_disabled_goods_list($order_id, $order_type = NULL)
{  
	global $db;
	// 查询订单类型
	if ($order_type == NULL) {
		$sql = "SELECT order_type_id FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_id = {$order_id}";
		$order_type = $GLOBALS['db']->getOne($sql, true);
	} 
	
	// 查询订单商品
	$sql = "
		SELECT og.rec_id, og.goods_id, og.style_id, og.goods_name, og.goods_number
		FROM ecshop.ecs_order_goods AS og 
		WHERE og.order_id = '{$order_id}'
	";	
	$order_goods = $db->getAll($sql);
	if (!$order_goods) { return FALSE; }
	
	// 根据订单情况来取得
	$goods_list = array();
	
	// 退货订单, 检查是否都已经入库了, 需要列出还未入库的商品
	if ($order_type == 'RMA_RETURN') {
		foreach($order_goods as $order_good){
			$sql = "SELECT SUM(quantity_on_hand_diff) FROM romeo.inventory_item_detail WHERE order_goods_id = '{$order_good['rec_id']}' AND quantity_on_hand_diff > 0";
			$in_number = $db->getOne($sql);
			if($order_good['goods_number'] > $in_number){
				$goods_list[$order_good['rec_id']]['goods_number'] = $order_good['goods_number'] - $in_number;
				$goods_list[$order_good['rec_id']]['goods_name'] = $order_good['goods_name'];	
			}
		}
	}
	
	// 正常销售订单或-h订单，检查是否还有在库中的, 需要列出已经出库的商品
	if ($order_type == 'SALE' || $order_type == 'RMA_EXCHANGE' ) {
		foreach($order_goods as $order_good){
			$sql = "SELECT ABS(SUM(quantity_on_hand_diff)) FROM romeo.inventory_item_detail WHERE order_goods_id = '{$order_good['rec_id']}'";
			$out_number = $db->getOne($sql);
			if($out_number > 0){
				$goods_list[$order_good['rec_id']]['goods_number'] = $out_number;
				$goods_list[$order_good['rec_id']]['goods_name'] = $order_good['goods_name'];
			}
		}
	}
	
	return empty($goods_list) ? FALSE : $goods_list;
}

/**
 * 通过订单各种状态判断该订单是否可以退款
 * 
 * @param array $order 订单信息 
 * 
 * @return boolean
 */
function refund_order_enabled($order)
{
    // 如果是指定退款类型，则通过特定的业务逻辑来处理
    if (isset($_REQUEST['type']))
    {
        // 商品退换货或杂项退款
        if ($_REQUEST['type'] == 'RET_EXT' || $_REQUEST['type'] == 'OTHERS') 
        {
            return true;
        }
        
        if ($_REQUEST['type'] == 'REFUND_TAOBAO') {
            if (!empty($_REQUEST['taobao_refund_id'])) {
                return true;
            } else {
                return false;
            }
        }
        // 退运费
        if ($_REQUEST['type'] == 'REFUND_SHIPPING_FEE' && is_numeric($_REQUEST['service_id']))
        {
            $sql = "SELECT * FROM service WHERE service_id = '{$_REQUEST['service_id']}' LIMIT 1 ";
            $service = $GLOBALS['db']->getRow($sql);
            if ($service['order_id'] == $order['order_id']
                && ($service['service_type'] == 1 || $service['service_type'] == 2)
                && $service['service_status'] == 3
                ) 
            {
                return true;
            }
        }
    }
    
    // 如果没有指定退款类型，则判断该订单是否满足退款条件
    if ($order['pay_status'] != 4 && ($order['pay_status'] == 2 || (float)$order['real_paid'] >0) 
        && !in_array($order['shipping_status'], array('1', '2', '3')))  // 订单已付款，未发货, 未退款
    {
        if ($order['order_status'] == 2 || // 订单已取消
            $order['order_status'] == 4 || // 订单已拒收
            $order['order_status'] == 1 && $order['real_paid'] > $order['order_amount']) // 支付金额 > 订单总额
        {
        	//若是取消 已付款 已出库，待发货的操作必须在追回物品之后才能进行退款申请
        	if(($order['order_status']==2 && $order['pay_status']==2 && ($order['shipping_status']==8 || $order['shipping_status']==12))|| $order['order_status']==1){
        		return false;
        	}
            return true;
        }
    }

    return false;
}

/**
 * 用于接收并判断从各个模块抛过来的退款方式 
 * 如果没有抛过来的$_REQUEST['type'], 则通过订单各种状态判断该订单的退款类型
 *
 * @param array $order 订单信息 
 * 
 * @return int
 */
function refund_order_type($order = null)
{
    if (isset($_REQUEST['type']))
    {
        /* 该退款类型已废除
        if ($_REQUEST['type'] == 'BONUS')
            return 4;  // 订单商品保价
        */

        if ($_REQUEST['type'] == 'RET_EXT')
            return 5;  // 商品退款货
        
        if ($_REQUEST['type'] == 'REFUND_SHIPPING_FEE' || $_REQUEST['type'] == 'OTHERS') 
            return 6;  // 退运费或杂项退款
            
        if ($_REQUEST['type'] == 'REFUND_TAOBAO') {
            return 7;
        }
    }

    if ($order['pay_status'] == '2' && $order['order_status'] == '2')
        return 1;  // 先款后货取消订单

    if ($order['pay_status'] == '2' && $order['order_status'] == '1' && ($order['real_paid'] > $order['order_amount']))
        return 2;  // 先款后货订单修改

    if ($order['pay_status'] == '2' && $order['order_status'] == '4')
        return 3; // 订单拒收

    return 6;  // 其他
}

/**
 * 取得该订单的退款方式信息
 * 
 * @param int $order_id 订单id
 * 
 * @return array
 */
function refund_order_payment_type($order_id)
{
    $payment = array();

    // 取得付款方式名
    $result = $GLOBALS['db']->getRow(" 
    	SELECT o.email, o.pay_name as order_pay_name, p.pay_code, p.pay_name, p.pay_id
    	FROM {$GLOBALS['ecs']->table('order_info')} o
    	LEFT JOIN {$GLOBALS['ecs']->table('payment')} p ON p.pay_id = o.pay_id 
    	WHERE o.`order_id` = '{$order_id}' AND p.pay_code IS NOT NULL LIMIT 1
    ");

    // 尝试通过log来分析用户的支付账号
    if ($result['pay_code'])
    {
        $log = $GLOBALS['db']->getOne("
    		SELECT request_data FROM {$GLOBALS['ecs']->table('pay_log')} 
    		WHERE request_data != '' AND order_id = {$order_id} ORDER BY log_id, is_paid DESC LIMIT 1
    	");
        switch ($result['pay_code'])
        {
            case 'alipay':
                if (preg_match('/buyer_email=(.+)/', $log, $matches))
                {
                    $result['pay_account']['account_user_login'] = $matches[1];
                }
                break;

        }
    }
    // 特殊处理淘宝店铺的退款
    if (in_array($result['pay_id'], array(65, 73, 74))) {
        $sql = "SELECT buyer_nick FROM taobao_refund WHERE order_id = {$order_id} ";
        $bank_account_no = $GLOBALS['db']->getOne($sql);
        $result['pay_account']['account_user_login'] = $bank_account_no;
        $result['pay_account']['alipay_account_user_login'] = $bank_account_no;
        $result['is_taobao_refund'] = true;
        $result['pay_code'] = 'TAOBAO';
        $refundPaymentType = refund_get_payment_type_by_code($result['pay_code']);
        $result['payment_type_id'] = (string)$refundPaymentType->refundPaymentTypeId;
    }

    return $result;
}

/**
 * 错误处理句柄
 * 
 * @param int $errno 错误级别
 * @param string $errstr 错误消息
 * @param string $errfile 出错的文件
 * @param string $errline 出错行
 */
function refund_error_handler($errno, $errstr, $errfile, $errline)
{
    global $smarty;

    // 现在做的只是将错误信息赋值给模版
    switch ($errno)
    {
        case E_USER_ERROR:
        case E_USER_WARNING:
        case E_USER_NOTICE:
            $smarty->assign('message', $errstr);
            break;
    }

    return true;
}

/**
 * 助手函数，用来格式化时间，返回描述性语句。比如“一分钟前”
 * 
 * @param string 时间戳 
 * @param string 基数，返回用于做一些特殊的应用。大写的字母：3（天）， 2(小时)，1(分)，0（秒）
 * 
 * @return array
 */
function refund_helper_time_format($timestamp, &$radix)
{
    $description = '';
    $now = time();
    $diff = $now - $timestamp;
    if ($diff < 60)
    {
        $radix = 0;
        $description = "{$diff}秒钟前";
    }

    if ($diff >= 60 && $diff < 3600)
    {
        $radix = 1;
        $_f = floor($diff / 60);
        $description = "{$_f}分钟前";
    }

    if ($diff >= 3600 && $diff < 86400)
    {
        $radix = 2;
        $_f = floor($diff / 3600);
        $description = "{$_f}小时前";
    }

    if ($diff >= 86400)
    {
        $radix = 3;
        $_f = floor($diff / 86400);
        $description = "{$_f}天前";
    }

    return $description;
}

/**
 * 助手函数，用来确定这个级别是否有审核权限，不推荐使用
 * 
 * @param int $level  部门级别, 1代码客服，2代表物流，3代表财务，4代表售后主管
 * 
 * @return boolean 
 */
function refund_helper_check_priv($level)
{
    static $user_privs; // 存储当前用户权限列表

    if (is_null($user_privs))
    {
        if (!empty($_SESSION['action_list']))
            $user_privs = array_filter(array_map('trim', explode(',', $_SESSION['action_list'])), 'strlen');
        else
            $user_privs = array();
    }

    $privs = array
    (
        '1'  => 'kf_refund_check', // 客服审核权限
        '2'  => 'wl_refund_check', // 物流审核权限
        '3'  => 'cw_refund_check', // 财务审核权限
        '4'  => 'shzg_refund_check', //售后主管审核权限
        'ok' => 'refund_execute', // 执行退款权限
        'ok1' => 'shzg_refund_execute' // 售后主管执行退款权限
    );

    if (isset($privs[$level]))
    {
        if (in_array('all', $user_privs))
            return true;
        if (in_array($privs[$level], $user_privs))
            return true;
        return false;
    }

    return call_user_func_array('admin_priv', $privs);
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
function db_create_in_zjli($item_list, $field_name = '')
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

/**
 * 助手函数， 用来构造查询条件
 * 
 * @access private
 * 
 * @return array 
 */
function _refund_helper_conditions($conditions)
{
    $args = array();
    $args['start'] = isset($conditions['start']) && strtotime($conditions['start'])!== false ? $conditions['start'] : date("Y-m-d", strtotime('-3 month')); // 期初时间
    $args['end'] = isset($conditions['end']) && strtotime($conditions['end']) !== false ? $conditions['end'] : date("Y-m-d", strtotime('1 day')); // 期末时间
    $args['orderId'] = is_numeric($conditions['order_id'])  // 订单id 
                        ? $conditions['order_id'] 
                        : (is_array($conditions['order_id']) && !empty($conditions['order_id']) ? $conditions['order_id'] : null); 
    if(check_admin_user_priv($_SESSION['admin_name'], 'wl_refund_check')){
    	if($args['orderId']!=null || $args['orderId'] != ""){
			$sql_order = " and r.ORDER_ID IN (". implode(",",$args['orderId']).")";
		}
		
		if($args['start'] != ''){
			$sql_order .= " and r.CREATED_STAMP >= '{$args['start']}'";
		}
		if($args['end'] != ''){
			$sql_order .= " and r.CREATED_STAMP < '{$args['end']}'";
		}
// 		$sql = " SELECT r.ORDER_ID from romeo.refund  r " .
// 			" where r.facility_id in (".$conditions['facility_id'].") " .$sql_order;

		$conditions['facility_id'] = str_replace("'","",$conditions['facility_id']);
		$sql = " SELECT r.ORDER_ID from romeo.refund  r " .
				" where r.party_id = '{$_SESSION['party_id']}' and r.facility_id " . db_create_in_zjli($conditions['facility_id']) . " " . $sql_order;
        $order_ids = $GLOBALS['db']->getCol($sql);
		$args['orderId'] = $order_ids;
    }  
    $args['userId'] = is_numeric($conditions['user_id']) ? $conditions['user_id'] : null; // 用户id
    $args['status'] = isset($conditions['status']) ? $conditions['status'] : null; // 退款单状态
    $args['refundPaymentTypeId'] = is_numeric($conditions['refund_payment_type_id']) ? $conditions['refund_payment_type_id'] : null; // 退款方式
    $args['currentchecker'] = is_numeric($conditions['current_checker']) ? $conditions['current_checker'] : null; // 待审核级别
    $args['partyId'] = is_numeric($conditions['party_id']) ? $conditions['party_id'] : intval($_SESSION['party_id']);
    $args['offset'] = is_numeric($conditions['offset']) ? $conditions['offset'] : 0; // offset
    $args['limit'] = is_numeric($conditions['limit']) ? $conditions['limit'] : 500; // limit
    $sql_admin = "select user_id from ecshop.ecs_admin_user where user_name = '".$_SESSION['admin_name']."'";
    $args['adminId']=$GLOBALS['db']->getOne($sql_admin);
    $args['status_orig'] = isset($conditions['status_orig']) ? $conditions['status_orig'] : null; // 原始状态
    return $args;
}

/**
 * 添加订单备注
 *
 * @param int $order_id
 * @param string $order_action
 * @param string $action_user
 */
function refund_add_order_action($order_id, $order_action, $action_user)
{
    if (!function_exists('order_info'))
        require_once (ROOT_PATH . 'includes/lib_order.php');

    $order = order_info($order_id);

    if (!function_exists('order_action'))
        require_once (ROOT_PATH . 'includes/lib_common.php');

    order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $order_action, $action_user);
}

/**
 * 更新淘宝退款，添加romeo退款号
 *
 * @param int $taobao_refund_id
 * @param string $refund_id
 */
function refund_update_taobao_info($taobao_refund_id, $refund_id) {
    global $db;
    $sql = "UPDATE taobao_refund 
        SET romeo_refund_id = '{$refund_id}'
        WHERE taobao_refund_id = '{$taobao_refund_id}'
        LIMIT 1";
    return $db->query($sql);
}

/**
 * 有退款问题返回 false；没有返回 true
 * @param string $order_id
 */
function check_refund_status($order_id) {
    global $db;
    $sql = "
    	select status 
		from ecshop.sync_taobao_refund estr
		inner join ecshop.ecs_order_info eoi on eoi.taobao_order_sn = estr.tid
        WHERE eoi.order_id = '{$order_id}' and estr.status !='CLOSED' AND estr.status != 'SUCCESS'
    ";
    $status = $db -> getOne($sql);
    if (!empty($status)) { 
    	return false; 
    }
    
	if (is_full_refund($order_id)) {
    	return false;
    }
    
    return true;
}
/**
 * 是否部分全部退款成功
 *
 * @param string $order_id
 */
function is_full_refund($order_id){
	global $db;
	//退款金额
	$sql = "
		select sum(estr.refund_fee) as total_refund_fee
		from ecshop.sync_taobao_refund estr
		inner join ecshop.ecs_order_info eoi on eoi.taobao_order_sn = estr.tid
		WHERE eoi.order_id = '{$order_id}' and estr.status ='SUCCESS' 
		group by eoi.order_id
    ";
    $refund_fee = $db->getOne($sql);
    //实付金额
    $sql = "
    	select order_amount 
    	from ecs_order_info
        WHERE order_id = '{$order_id}' ";
    $pay_fee = $db->getOne($sql);
    //部分退款成功
    if (!empty($refund_fee) && !empty($pay_fee)) { 
    	if(($pay_fee - $refund_fee) <= 0){
    		return true;
    	}
    }
    return false;
}

/**
 * 有退款问题返回 false；没有返回 true
 * @param string $shipment_id
 */
function check_refund_status_by_shipment_id($shipment_id){
	global $db;
	//退款金额
	$sql = "SELECT primary_order_id
		FROM romeo.shipment 
		WHERE SHIPMENT_ID = '{$shipment_id}' ";
	$order_id_list=$db->getAll($sql);
	if(!empty($order_id_list)){
		foreach($order_id_list as $order_id){
			if(!check_refund_status($order_id['primary_order_id'])){
				return false;
			}
		}
	}
	return true;
}

/**
 * 有退款问题返回 false；没有返回 true
 * @param string $order_sn
 */
function check_refund_status_by_order_sn($order_sn){
	global $db;
	//退款金额
	$sql = "SELECT order_id
		FROM  ecshop.ecs_order_info
		WHERE order_sn = '{$order_sn}'";
	$order_id_list=$db->getAll($sql);
	if(!empty($order_id_list)){
		foreach($order_id_list as $order_id){
			if(!check_refund_status($order_id['order_id'])){
				return false;
			}
		}
	}
	return true;
}

/**
 * 有退款问题返回 false；没有返回 true
 * @param string $tracking_number
 */
function check_refund_status_by_tracking_number($tracking_number){
	global $db;
	
	$sql = "SELECT primary_order_id
		FROM romeo.shipment
		WHERE tracking_number = '{$tracking_number}' ";
	$order_id_list=$db->getAll($sql);
	if(!empty($order_id_list)){
		foreach($order_id_list as $order_id){
			if(!check_refund_status($order_id['primary_order_id'])){
				return false;
			}
		}
	}
	return true;
}


/**
 *查询退款理赔售后类型
 *
 */
function get_claims_note($order_id, $refund_id){
    $sql = "select note from ecshop.claims_settlement WHERE order_id = '{$order_id}' AND refund_id = '{$refund_id}' AND compensation_type = 'REFUND' LIMIT 1 ";
    $re= $GLOBALS['db']->getAll($sql);
    $claims_note = $re[0]['note'];
    return $claims_note;
}

/**
*查询退款理赔信息
*
*/
function get_claims_settlement($order_id, $refund_id){
	global $_CFG;
	$sql = "select responsible_party, compensation_amount from ecshop.claims_settlement WHERE order_id = '{$order_id}' AND refund_id = '{$refund_id}' AND compensation_type = 'REFUND' ";
	$responsible = $GLOBALS['db']->getAll($sql);
	foreach($responsible as &$res){
	    $res['responsible_party'] = $res['responsible_party'];
	    $res['responsible_party_name'] = $_CFG['adminvars']['responsible_party'][$res['responsible_party']];
	    $res['compensation_amount'] = $res['compensation_amount'];
	}
	return $responsible;
}