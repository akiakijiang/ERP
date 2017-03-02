<?php

/**
 * OUKU 网银在线插件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/bill99.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    $code = basename(__FILE__, '.php');
    $sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_code = '{$code}'";
    $payments = $db->getAll($sql);
    
    foreach ($payments as $key => $payment) {
    	$modules[$payment['pay_id']] = $payment;
    	$modules[$payment['pay_id']]['author'] = 'oukoo';
    }
    
//    /* 代码 */
//    $modules[$i]['code']    = basename(__FILE__, '.php');
//
//    /* 描述对应的语言项 */
//    $modules[$i]['desc']    = 'chinabank_desc';
//
//    /* 是否支持货到付款 */
//    $modules[$i]['is_cod']  = '0';
//
//    /* 支付费用 */
//    $modules[$i]['pay_fee'] = '2.5%';
//
//    /* 作者 */
//    $modules[$i]['author']  = 'Oukoo';
//
//    /* 网址 */
//    $modules[$i]['website'] = 'http://www.chinabank.com.cn';
//
//    /* 版本号 */
//    $modules[$i]['version'] = '1.0.1';
//
//    /* 配置信息 */
//    $modules[$i]['config'] = array(
//        array('name' => 'chinabank_account', 'type' => 'text', 'value' => ''),
//        array('name' => 'chinabank_key',     'type' => 'text', 'value' => ''),
//    );

    return;
}

/**
 * 类
 */
class bill99
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function bill99()
    // {
    // }

    function __construct()
    {
        $this->bill99();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
      if ($payment['bill99_direct'] || $payment['bill99_bankId']) {
        $payType = "10";
        $bankId = $payment['bill99_bankId'];
      } else {
        $payType = "00";
        $bankId = '';
      }
        $parameter = array( 
          
          //字符集.固定选择值。可为空。
          ///只能选择1、2、3.
          ///1代表UTF-8, 2代表GBK, 3代表gb2312
          ///默认值为1
          'inputCharset' => "1",
          
          //接受支付结果的页面地址.与[bgUrl]不能同时为空。必须是绝对地址。
          ///如果[bgUrl]为空，快钱将支付结果Post到[pageUrl]对应的地址。
          ///如果[bgUrl]不为空，并且[bgUrl]页面指定的<redirecturl>地址不为空，则转向到<redirecturl>对应的地址
          'pageUrl' => RETURNURL.'?code=bill99&',
//          'pageUrl' => 'http://localhost/shop/ouku_return.php?code=bill99&',
          
          //服务器接受支付结果的后台地址.与[pageUrl]不能同时为空。必须是绝对地址。
          ///快钱通过服务器连接的方式将交易结果发送到[bgUrl]对应的页面地址，在商户处理完成后输出的<result>如果为1，页面会转向到<redirecturl>对应的地址。
          ///如果快钱未接收到<redirecturl>对应的地址，快钱将把支付结果post到[pageUrl]对应的页面。
          //$bgUrl =>"http://www.yoursite.com/receive.php",
          	
          //网关版本.固定值
          ///快钱会根据版本号来调用对应的接口处理程序。
          ///本代码版本号固定为v2.0
          'version' => "v2.0",
          
          //语言种类.固定选择值。
          ///只能选择1、2、3
          ///1代表中文；2代表英文
          ///默认值为1
          'language' => "1",
          
          //签名类型.固定值
          ///1代表MD5签名
          ///当前版本固定为1
          'signType' => "1",	
          
          //人民币网关账户号
          ///请登录快钱系统获取用户编号，用户编号后加01即为人民币网关账户号。
          'merchantAcctId' => trim($payment['bill99_account']),
          
          //支付人姓名
          ///可为中文或英文字符
          'payerName' => $order['consignee'],
          
          //支付人联系方式类型.固定选择值
          ///只能选择1
          ///1代表Email
          'payerContactType' => "1",	
          
          //支付人联系方式
          ///只能选择Email或手机号
//          'payerContact' => $order['email'],
          
          //商户订单号
          ///由字母、数字、或[-][_]组成
          'orderId' => $order["order_sn"],		
          
          //订单金额
          ///以分为单位，必须是整型数字
          ///比方2，代表0.02元
          'orderAmount' => $order['order_amount'] * 100,
          	
          //订单提交时间
          ///14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
          ///如；20080101010101
          'orderTime' =>date('YmdHis'),
          
          //商品名称
          ///可为中文或英文字符
          'productName' => $order["order_sn"]."_".$order['log_id'],
          
          //商品数量
          ///可为空，非空时必须为数字
          'productNum' => "1",
          
          //商品代码
          ///可为字符或者数字
//          'productId' => "",
//          
//          //商品描述
//          'productDesc' =>"",
          	
          //扩展字段1
          ///在支付结束后原样返回给商户
          //$ext1 =>"",

          //扩展字段2
          ///在支付结束后原样返回给商户
          //  $ext2 =>"",
          	
          //支付方式.固定选择值
          ///只能选择00、10、11、12、13、14
          ///00：组合支付（网关支付页面显示快钱支持的各种支付方式，推荐使用）10：银行卡支付（网关支付页面只显示银行卡支付）.11：电话银行支付（网关支付页面只显示电话支付）.12：快钱账户支付（网关支付页面只显示快钱账户支付）.13：线下支付（网关支付页面只显示线下支付方式）.14：B2B支付（网关支付页面只显示B2B支付，但需要向快钱申请开通才能使用）
          'payType' => $payType,
          
          
          //银行代码
          ///实现直接跳转到银行页面去支付,只在payType =>10时才需设置参数
          ///具体代码参见 接口文档银行代码列表
          'bankId' => $bankId,
          
          //同一订单禁止重复提交标志
          ///固定选择值： 1、0
          ///1代表同一订单号只允许提交1次；0表示同一订单号在没有支付成功的前提下可重复提交多次。默认为0建议实物购物车结算类商户采用0；虚拟产品类商户采用1
          'redoFlag' => "0",
        );

        foreach ($parameter AS $key => $val)
        {
//          if ($key != 'payerContact') {
//          	$param .= "<input type=\"hidden\" name=\"{$key}\" value=\"" .urlencode($val). "\" />";
//          } else {
//            $param .= "<input type=\"hidden\" name=\"{$key}\" value=\"" .($val). "\" />";
//          }
            if ($val != '') {
            	$param .= "<input type=\"hidden\" name=\"{$key}\" value=\"" .($val). "\" />";
              $sign  .= "$key=$val&";
            }
        }
        $sign .= "key=".trim($payment['bill99_key']);
        $signMsg = strtoupper(md5($sign));
        $bank_name = $payment['bill99_bankname'];
        $class = $bankId ? strtolower($bankId)."_bank" : 'bill99';
        $def_url = "<form name=\"kqPay\" method=\"post\" action=\"https://www.99bill.com/gateway/recvMerchantInfoAction.htm\">
        $param
        <input type=\"hidden\" name=\"signMsg\" value=\"" .urlencode($signMsg). "\" />
        <input type=\"submit\" name=\"submit\" value=\"立即到{$bank_name}支付\" class='{$class}'>
        </form>";
        
        global $db, $ecs;
        $ouku_request = $def_url;
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '" . mysql_escape_string($ouku_request) ."' WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);
        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment        = get_payment(basename(__FILE__, '.php'));
        
        
        
        $parameter_array = array('version', 'language', 'signType', 'payType', 'bankId', 
        'orderId', 'orderTime', 'orderAmount', 'dealId', 'bankDealId', 'dealTime',
        'payAmount', 'fee', 'payResult', 'errCode');
        $sign = "merchantAcctId=".$payment['bill99_account']."&";
        foreach ($parameter_array as $par) {
          if($_REQUEST[$par]) {
            $sign .= "$par=".$_REQUEST[$par]."&";
          }
        }

        /**
         * 重新计算md5的值
         */
        $sign            .= "key=".$payment['bill99_key'];
        $md5string = strtoupper(md5($sign));
        /* 检查秘钥是否正确 */
        if ($_REQUEST['signMsg'] == $md5string)
        {
          global $db, $ecs; //根据订单sn获得log_id

//          $log_id = intval($_REQUEST['orderId']);         
          $order_sn = $_REQUEST['orderId'];
          $sql = "SELECT p.log_id FROM {$GLOBALS['ecs']->table('pay_log')} p ". 
                 " INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id ".
                 " WHERE o.order_sn = '{$order_sn}' ORDER BY log_id DESC LIMIT 1 ";
          $log_id = $db->getOne($sql);

          /* 改变订单状态 */
          if ($_REQUEST['payResult'] == 10) {
            order_paid($log_id);
            return true;
          }
        }
        return false;
    }
}

?>