<?php

/**
 * ECSHOP 支付接口函数库
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     scott ye <scott.yell@gmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: wj $
 * $Date: 2007-04-30 16:33:26 +0800 (星期一, 30 四月 2007) $
 * $Id: lib_payment.php 8446 2007-04-30 08:33:26Z wj $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 取得返回信息地址
 * @param   string  $code   支付方式代码
 */
function return_url($code)
{
    return $GLOBALS['ecs']->url() . 'respond.php?code=' . $code;
}

/**
 *  取得某支付方式信息
 *  @param  string  $code   支付方式代码
 */
function get_payment($code)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment').
           " WHERE pay_code = '$code' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

/**
 * 检查支付的金额是否与订单相符
 *
 * @access  public
 * @param   string   $log_id      支付编号
 * @param   float    $money       支付接口返回的金额
 * @return  true
 */
function check_money($log_id, $money)
{
    $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('pay_log') .
              " WHERE log_id = '$log_id'";
    $amount = $GLOBALS['db']->getOne($sql);

    if ($money == $amount)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 检查支付的金额是否与订单相符
 *
 * @access  public
 * @param   string   $order_sn      支付编号
 * @param   float    $money       支付接口返回的金额
 * @return  true
 */
function check_money_by_sn($order_sn, $money)
{
    $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('order_info') .
              " WHERE order_sn = '$order_sn'";
    $amount = $GLOBALS['db']->getOne($sql);
    if ($money == $amount)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 修改订单的支付状态
 *
 * @access  public
 * @param   string  $log_id     支付编号
 * @param   integer $pay_status 状态
 * @param   string  $note       备注
 * @return  void
 */
function order_paid($log_id, $pay_status = PS_PAYED, $note = '')
{

    /* 取得支付编号 */
    $log_id = intval($log_id);
    if ($log_id > 0)
    {
        /* 取得要修改的支付记录信息 */
        $sql = "SELECT is_paid,order_type,order_amount,order_id,log_id FROM " . $GLOBALS['ecs']->table('pay_log') .
                " WHERE log_id = '$log_id'";
        $pay_log = $GLOBALS['db']->getRow($sql);

        if ($pay_log && ($pay_log['is_paid'] == 0)) {
        	/* 获取post数据 */
        	$data = "--------------------------------------REQUEST------------------------------";
        	foreach($_REQUEST as $k => $v) {
        		if (is_array($v)) {
        			$data = $data."\n".$k."=[";
        			$started = false;
        			foreach($v as $i=>$j) {
        				if ($stated) $data = $data.",";
        				$started = true;
        				$data = $data.$j;
        			}
        			$data = $data."]";
        		} else {
	        		$data = $data."\n".$k."=".$v;
        		}
        	}
        	/* 获得服务器数据 */
        	$data = $data."\n\n--------------------------------------SERVER------------------------------";
        	foreach($_SERVER as $k => $v) {
        		if (is_array($v)) {
        			$data = $data."\n".$k."=[";
        			$started = false;
        			foreach($v as $i=>$j) {
        				if ($stated) $data = $data.",";
        				$started = true;
        				$data = $data.$j;
        			}
        			$data = $data."]";
        		} else {
	        		$data = $data."\n".$k."=".$v;
        		}
        	}
//        	$data = str_replace("'","''",$data);
        	$data = $GLOBALS['db']->escape_string($data);
            
        
            /* 修改此次支付操作的状态为已付款 */
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('pay_log') .
                    " SET is_paid = '1', request_data='$data' WHERE log_id = '$log_id'";
            $GLOBALS['db']->query($sql);

			#@file_put_contents(ROOT_PATH.'pay.icbc.txt', "\r\n---------------------------------\r\n"."sql: ".$sql, FILE_APPEND);
            /* 根据记录类型做相应处理 */
            if ($pay_log['order_type'] == PAY_ORDER) {
                /* 取得订单信息 */
                $sql = 'SELECT order_id, order_sn, consignee, address, tel, shipping_id, parent_order_id, pay_name, order_amount, mobile' .
                        ' FROM ' . $GLOBALS['ecs']->table('order_info') .
                       " WHERE order_id = '$pay_log[order_id]' or parent_order_id='$pay_log[order_id]' ";
                $orders 	=	$GLOBALS['db']->getAll($sql);
				foreach ($orders as $key =>$order) {
					order_paid0($order, $pay_status, $note);
				}
            }
            elseif ($pay_log['order_type'] == PAY_SURPLUS)
            {
                /* 更新会员预付款的到款状态 */
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') .
                       " SET paid_time = '" .time(). "', is_paid = 1" .
                       " WHERE id = '$pay_log[order_id]' LIMIT 1";
                $GLOBALS['db']->query($sql);

                /* 取得添加预付款的用户以及金额 */
                $sql = "SELECT user_id, amount FROM " . $GLOBALS['ecs']->table('user_account') .
                        " WHERE id = '$pay_log[order_id]'";
                $arr = $GLOBALS['db']->getRow($sql);

                /* 修改会员帐户金额 */
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') .
                        " SET user_money = user_money + '$arr[amount]'" .
                        " WHERE user_id = '$arr[user_id]' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }
        }
        else
        {
            /* 取得已发货的虚拟商品信息 */
            $post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);

            /* 有已发货的虚拟商品 */
            if (!empty($post_virtual_goods))
            {
                $msg = '';
                /* 检查两次刷新时间有无超过12小时 */
                $sql = 'SELECT pay_time, order_sn FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$pay_log[order_id]'";
                $row = $GLOBALS['db']->getRow($sql);
                $intval_time = time() - $row['pay_time'];
                if ($intval_time > 0 && $intval_time < 3600 * 12)
                {
                    /* 将语言项注册为全局 */
                    global $_LANG;

                    /* 取得虚拟商品所需要插件支持 */
                    $extension_code = array_unique(array_keys($post_virtual_goods));
                    foreach ($extension_code AS $code)
                    {
                        if (file_exists(ROOT_PATH . 'plugins/'.$code.'/'.$code.'_inc.php'))
                        {
                            include_once(ROOT_PATH . 'plugins/'.$code.'/'.$code.'_inc.php');
                            /* 存在语言项包含语言项 */
                            if (file_exists(ROOT_PATH . 'plugins/'.$code.'/languages/common_'.$GLOBALS['_CFG']['lang'].'.php'))
                            {
                                include_once(ROOT_PATH . 'plugins/'.$code.'/languages/common_'.$GLOBALS['_CFG']['lang'].'.php');
                            }
                            $$code = new $code();
                        }
                        else
                        {
                            $msg .= '<tr><td colspan="4">'.sprintf($GLOBALS['_LANG']['plugins_not_found'],$code) . '</td></tr>';
                        }
                    }

                    foreach ($post_virtual_goods AS $code => $goods_list)
                    {
                        foreach ($goods_list as $goods)
                        {
                            $msg .= $$code->result($row['order_sn'], $goods);
                        }
                    }

                    $msg = '<br /><table align="center">' . $msg . '</table>';
                }
                else
                {
                    $msg = '<div>' .  $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
                }

                $GLOBALS['_LANG']['pay_success'] .= $msg;
            }

           /* 取得未发货虚拟商品 */
           $virtual_goods = get_virtual_goods($pay_log['order_id'], false);
           if (!empty($virtual_goods))
           {
               $GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
           }
        }
    }
}



/**
 * 修改订单的支付状态
 * @access  public
 * @param   string  $log_id     支付编号
 * @param   integer $pay_status 状态
 * @param   string  $note       备注
 * @return  void
 */
function order_paid0($order, $pay_status = PS_PAYED, $note = '') {
  $order_id = $order['order_id'];
  $order_sn = $order['order_sn'];

  /* 修改订单状态为已付款 */
  $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
            " SET order_status = '" . OS_CONFIRMED . "', " .
            " confirm_time = '" . time() . "', " .
            " pay_status = '$pay_status', " .
            " pay_time = '".time()."', " .
            " money_paid = order_amount " .
            "WHERE order_id = '$order_id'";
  $result = $GLOBALS['db']->query($sql);
  
  //在数据库插入操作记录
  if ($result) {
      global $userInfo;
      if($userInfo)
      {
          $username = $userInfo['username'];
      }
      else
      {
          $username = "unknow";
      }
	  $action_time = date("Y-m-d H:i:s");
      $sql = sprintf("INSERT INTO %s (order_id, action_user, order_status,pay_status,action_time, action_note) 
          VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",$GLOBALS['ecs']->table('order_action'),
          $order_id, $username, OS_CONFIRMED, $pay_status, $action_time, $note ? $note : '用户支付操作'
      );
      $result = $GLOBALS['db']->query($sql);
  }
 
  if ($order['parent_order_id'] == 0 && $GLOBALS['is_message']) {
	  $sql = "SELECT goods_name FROM {$GLOBALS['ecs']->table('order_goods')} WHERE order_id = '$order_id'";
	  $goods_names = $GLOBALS['db']->getCol($sql);
	  $goods_names_str = join("，", $goods_names);
	  $order_amount = $order['order_amount'] + 0;	// 去除小数点
	  /*
//	  $msg = "你好，您在欧酷使用{$order['pay_name']}支付方式购买共计{$order['order_amount']}元的{$goods_names_str}支付成功，欧酷网正在为你备货";
	  $msg = "您好！您在欧酷网的订单（订单号：$order_sn）已经支付成功，我们将在48小时内为您发货，请耐心等待。";
	  send_message($msg, $order['mobile']);
	  */
  }
  
  /* 如果需要，发短信 */
  /*
  if ($row["parent_order_id"] == '0' && $GLOBALS['_CFG']['sms_order_payed'] == '1' && $GLOBALS['_CFG']['sms_shop_mobile'] != '') {
    include_once(ROOT_PATH.'includes/cls_sms.php');
    $sms = new sms();
    $sms->send($GLOBALS['_CFG']['sms_shop_mobile'],
    sprintf($GLOBALS['_LANG']['order_payed_sms'], $order_sn, $order['consignee'], $order['tel']), 0);
  }
  */

  /* 对虚拟商品的支持 */
  $virtual_goods = get_virtual_goods($order_id);
  if (!empty($virtual_goods)) {
    $msg = '';
    if (virtual_goods_ship($virtual_goods, $msg, $order_sn, true)) {
      $GLOBALS['_LANG']['pay_success'] .= '<br /><table align="center">'.$msg.'</table>';
    } else {
      $GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">'.$msg.'</div>'.$GLOBALS['_LANG']['virtual_goods_ship_fail'];
    }
    /* 如果订单没有配送方式，自动完成发货操作 */
    if ($order['shipping_id'] == -1) {
      /* 将订单标识为已发货状态，并记录发货记录 */
      $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                " SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . time() . "'" .
                " WHERE order_id = '$order_id'";
      $GLOBALS['db']->query($sql);
      /* 记录订单操作记录 */
      order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
    }
  }

}

?>
