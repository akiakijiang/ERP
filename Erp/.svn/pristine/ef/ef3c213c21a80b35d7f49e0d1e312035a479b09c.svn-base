<?php
/**
 * OUKOO支付方式 中国工商银行
 */
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}
include_once ROOT_PATH. 'includes/lib_payment.php';
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/icbc.php';

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
    	
//	/* 代码 */
//	$modules[$i]['code']    = basename(__FILE__, '.php');
//
//	/* 描述对应的语言项 */
//	$modules[$i]['desc']    = 'icbc_desc';
//
//	/* 是否支持货到付款 */
//	$modules[$i]['is_cod']  = '0';
//
//	/* 支付费用 */
//	$modules[$i]['pay_fee'] = '0';
//
//	/* 作者 */
//	$modules[$i]['author']  = 'Oukoo';
//
//	/* 网址 */
//	$modules[$i]['website'] = 'http://www.icbc.com.cn';
//
//	/* 版本号 */
//	$modules[$i]['version'] = '1.0.0';
//
//	/* 配置信息 */
//	$modules[$i]['config'] = array(
//		array('name' => 'interfaceName', 'type' => 'text', 'value' => 'ICBC_PERBANK_B2C'),
//		array('name' => 'interfaceVersion', 'type' => 'text', 'value' => '1.0.0.0'),
//		array('name' => 'curType', 'type' => 'text', 'value' => '001'),
//		array('name' => 'merID', 'type' => 'text', 'value' => '1001EC20000495'),
//		array('name' => 'merAcct', 'type' => 'text', 'value' => '1001194919006740107'),
//		array('name' => 'merURL', 'type' => 'text', 'value' => 'http://testshop.ouku.com/respond.php?code=icbc'),
//		array('name' => 'verifyJoinFlag', 'type' => 'text', 'value' => '0'),
//		array('name' => 'notifyType', 'type' => 'text', 'value' => 'HS'),
//		array('name' => 'resultType', 'type' => 'text', 'value' => '0'),
//	);

	return;
}

class icbc
{
	protected $ICBCPaymentServiceClient = null;
	/**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
	function __construct()
	{
		// {{{ rpc
		global $payment_rpc_host, $payment_rpc_path, $payment_rpc_port;
		include_once 'payment_rpc_config.php';
		include_once 'RpcApi/payment/icbc/ICBCPaymentServiceClient.php';

		$this->ICBCPaymentServiceClient = new ICBCPaymentServiceClient(new RpcContext(HOST, PATH, PORT));
		// }}}
	}

	/**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
	function get_code($order, $payment)
	{
		#pp($GLOBALS['modules']);
		#$action = 'https://210.82.37.103/servlet/ICBCINBSEBusinessServlet';
		#$action = 'https://mybank.icbc.com.cn/servlet/ICBCINBSEBusinessServlet';
		$action = "https://B2C.icbc.com.cn/servlet/ICBCINBSEBusinessServlet";

		$interfaceName = 'ICBC_PERBANK_B2C'; // *
		$interfaceVersion = '1.0.0.0'; // *
		$orderid = $order['order_sn']; // *
		$amount = intval($order['order_amount']*100); // *
		$curType = '001'; // *
		#$merID = '1001EC20001111'; // *
		$merID = '1001EC20000495'; // *
		#$merAcct = '1001242709300067248'; // *
		$merAcct = '1001194919006740107'; // *
		#$merURL = 'http://testshop.ouku.com/respond.php?code=icbc'; // *
		$merURL = $payment['merURL'];
		$carriageAmt = 0; // *
		$orderDate = date("YmdHis");
		#$orderDate = date("YmdHis", time()+60*60*24*34); // * 23 26

		# 20071210 == 20080102
		// echo (strtotime(20080125 )-strtotime(20071214 ))/(60*60*24);die();

		$verifyJoinFlag = 0; // *
		$notifyType = 'HS'; // *
		$resultType = 0; // *

		// 6222 0010 0112 0657433
		// 6222001001120657433
		// 180 07/07

		// 9558803700106081732

		$merSignMsg = '';
		#echo RETURNURL;
		#接口名称的值+接口版本号的值+商城代码的值+商城账号的值+通知地址的值+结果发送类型的值+订单号的值+订单金额的值+支付币种的值+通知类型的值+交易日期时间的值+校验联名标志的值
		$srcStr = $interfaceName.$interfaceVersion.$merID.$merAcct.$merURL.$notifyType.$orderid.$amount.$curType.$resultType.$orderDate.$verifyJoinFlag;
		#$srcStr = "interfaceName={$interfaceName} &interfaceVersion={$interfaceVersion}&merID={$merID}&merAcct={$merAcct}&merURL={$merURL}&notifyType={$notifyType}&orderid={$orderid}&amount={$amount}&curType={$curType}&resultType={$resultType}&orderDate={$orderDate}&verifyJoinFlag={$verifyJoinFlag}";

		$merSignMsg = $this->ICBCPaymentServiceClient->sign($srcStr);
		$merSignMsg = preg_replace("/\s*/", "", $merSignMsg);
		if (!$merSignMsg)
		{
			return false;
		}
		$verifySign = $this->ICBCPaymentServiceClient->verifySign($merSignMsg, $srcStr);
		#vv($verifySign);

		$merCert = $this->ICBCPaymentServiceClient->getPublicCert();

		if (!$merCert)
		{
			return false;
		}

		$forms = "
			<form action='$action' method='post' target='_blank'>
			<input type='hidden' name='interfaceName' value='$interfaceName'>
			<input type='hidden' name='interfaceVersion' value='$interfaceVersion'>
			<input type='hidden' name='orderid' value='$orderid'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='curType' value='$curType'>
			<input type='hidden' name='merID' value='$merID'>
			<input type='hidden' name='merAcct' value='$merAcct'>
			<input type='hidden' name='verifyJoinFlag' value='$verifyJoinFlag'>
			<input type='hidden' name='notifyType' value='$notifyType'>
			<input type='hidden' name='merURL' value='$merURL'>
			<input type='hidden' name='resultType' value='$resultType'>
			<input type='hidden' name='carriageAmt' value='$carriageAmt'>
			<input type='hidden' name='merHint' value=''>
			<input type='hidden' name='orderDate' value='$orderDate'>
			<input type='hidden' name='merSignMsg' value='$merSignMsg'>
			<input type='hidden' name='merCert' value='$merCert'>
			<input type='hidden' name='remark1' value='remark1'>
			<input type='hidden' name='remark2' value='remark2'>
			<input type='submit' class='icbcButton' value='[使用工商银行网上支付]'> 
			</form>
			";
		
        global $db, $ecs;
        $ouku_request = mysql_escape_string($forms);
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '{$ouku_request}' WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);			
		return $forms;
	}

	/**
	     * 响应操作
	     */
	function respond()
	{
		$payment = get_payment(basename(__FILE__, '.php'));

		/* 察看是否启用这个支付方式 */
		if (!$payment['enabled'])
		{
			return false;
		}

		$sSucceed =='N';

		#$sSucceed	=	trim($_GET['Succeed']);

		$interfaceName = trim($_POST['interfaceName']);
		$interfaceVersion = trim($_POST['interfaceVersion']);
		$orderid = trim($_POST['orderid']);
		$TranSerialNo = trim($_POST['TranSerialNo']);
		$amount = trim($_POST['amount']);
		$curType = trim($_POST['curType']);
		$merID = trim($_POST['merID']);
		$merAcct = trim($_POST['merAcct']);
		$verifyJoinFlag = trim($_POST['verifyJoinFlag']);
		$JoinFlag = trim($_POST['JoinFlag']);
		$UserNum = trim($_POST['UserNum']);
		$resultType = trim($_POST['resultType']);
		$orderDate = trim($_POST['orderDate']);
		$notifyDate = trim($_POST['notifyDate']);
		$tranStat = trim($_POST['tranStat']);
		$comment = trim($_POST['comment']);
		$remark1 = trim($_POST['remark1']);
		$remark2 = trim($_POST['remark2']);

		$signMsg = trim($_POST['signMsg']);

		#interfaceName=值&interfaceVersion=值&orderid=值&TranSerialNo=值&amount=值&curType=值&merID=值&merAcct=值&verifyJoinFlag=值&JoinFlag=值&UserNum=值&resultType=值&orderDate=值&notifyDate=值&tranStat=值&comment=值&remark1=值&remark2=值

		$srcStr = "interfaceName=$interfaceName&interfaceVersion=$interfaceVersion&orderid=$orderid&TranSerialNo=$TranSerialNo&amount=$amount&curType=$curType&merID=$merID&merAcct=$merAcct&verifyJoinFlag=$verifyJoinFlag&JoinFlag=$JoinFlag&UserNum=$UserNum&resultType=$resultType&orderDate=$orderDate&notifyDate=$notifyDate&tranStat=$tranStat&comment=$comment&remark1=$remark1&remark2=$remark2";

		#$returnInfo = parse_str($_POST);

		// {{{
		$merSignMsg = $this->ICBCPaymentServiceClient->sign($srcStr);
		//@file_put_contents(ROOT_PATH.'pay.icbc.txt', "\r\n---------------------------------\r\n"."merSignMsg: ".$merSignMsg, FILE_APPEND);

		$verifySign = $this->ICBCPaymentServiceClient->verifySign($signMsg, $srcStr);
		//@file_put_contents(ROOT_PATH.'pay.icbc.txt', "\r\n---------------------------------\r\n"."verifySign: ".$verifySign, FILE_APPEND);
		// }}}
		
		/*
		// 6222 0010 0112 0657433
		// 6222001001120657433
		// 180 07/07
		// 9558803700106081732
		interfaceName=ICBC_PERBANK_B2C&interfaceVersion=1.0.0.0&orderid=1057530009&TranSerialNo=HFG000094963764&amount=1&curType=001&merID=1001EC20000495&merAcct=1001194919006740107&verifyJoinFlag=0&JoinFlag=&UserNum=&resultType=0&orderDate=20071228174943&notifyDate=20071228175314&tranStat=1&comment=&remark1=&remark2=&signMsg=ARwRByT0yIUJDLmmg7Vojez45UxgXvj0dBuMAUlCpVASHsI%2BUNEoRbKwnLRSd6HPj%2BzcMnLGWpg0rKdTRO6Hi0Pf3SnpixZWFLkS8v7FOfeFRblRbz9ydv2%2F%2BlHQHkrfAIfl0K%2BIQN1ZPAnjnYk%2FxASBClJzUSP6Vn%2Ffzu4nZ4M%3D
		*/
		
		$checkData = $interfaceName == $payment['interfaceName'] && $interfaceVersion == $payment['interfaceVersion']
			&& $curType == $payment['curType'] && $merID == $payment['merID'] && $merAcct == $payment['merAcct']
			&& $verifyJoinFlag == $payment['verifyJoinFlag'] && $resultType == $payment['resultType'];

		if ($tranStat == 1 /*&& $verifySign == 0*/ && $checkData)
		{
			// 支付成功
			$sSucceed = 'Y';
		}

		if ($sSucceed =='Y')
		{
			// {{{
			$sql =	'select oi.order_id , pl.log_id , oi.order_amount  from ' . $GLOBALS['ecs']->table('order_info') .
		'as oi , '.$GLOBALS['ecs']->table('pay_log').' as pl where oi.order_id = pl.order_id  and oi.order_sn = '."'".$orderid."'";
			$payInfo = $GLOBALS['db']->getRow($sql);
			// }}}

			// 支付的是否是有效的订单
			if ($payInfo)
			{
				/* 检查支付的金额是否相符 */
				if ($amount == $payInfo['order_amount']*100)
				{
					/* 改变订单状态 */
					order_paid($payInfo['log_id']);
					return true;
				}
			}
		}

		return false;
	}
}




?>