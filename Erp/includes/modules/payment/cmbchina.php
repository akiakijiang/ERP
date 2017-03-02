<?php
/**
 * OUKU支付方式 招商银行
 */
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/cmbchina.php';

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
//	$modules[$i]['desc']    = 'cmb_desc';
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
//	$modules[$i]['website'] = 'http://netpay.cmbchina.com/';
//
//	/* 版本号 */
//	$modules[$i]['version'] = '1.0.1';
//
//	/* 配置信息 */
//	$modules[$i]['config'] = array(
//	array('name' => 'cmb_BranchID', 'type' => 'text', 'value' => ''),
//	array('name' => 'cmb_CoNo',     'type' => 'text', 'value' => ''),
//	//array('name' => 'cmb_Date',    'type' => 'text', 'value' => ''),
//	//array('name' => 'cmb_testBillNo',    'type' => 'text', 'value' => ''),
//	);

	return;
}

class	cmbchina
{
	/**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
	// function cmbchina()
	// {
	// }

	function __construct()
	{
		$this->cmbchina();
	}

	/**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
	function get_code($order, $payment)
	{
		$key	=	'ouku';
		
		$MD5KEY =	$order['order_sn'].number_format($order['order_amount'], 2, ".", "").$key;

		$parameter = array(
		'MerchantUrl'       =>  RETURNURL.'?code=cmbchina&',
		//'MerchantUrl'       =>  'http://testshop.ouku.com/ouku_return.php?code=cmbchina&',
		/* 业务参数 */
		'BillNo'           	=> $order['order_sn'],
		'Amount'          	=> $order['order_amount'],
		'Date'      		=> date("Ymd"),
		'MerchantPara'     	=> md5($MD5KEY),
		);

		$param = '';
		$sign  = '';

		foreach ($parameter AS $key => $val)
		{
			$param .= '<input type="HIDDEN"   name="'.$key.'"  value="' .$val. '" >'."\r\n";
			$sign  .= "$key=$val&";
		}

		$sign  = substr($sign, 0, -1);

		$tPay	=	'PrepayC2';
		$BrandId	=	'0021';
		$OUKUNUMBER	=	'000005';
		/* 我有把参数也写进入了 */
		$button = '
		<form  method="post" action="https://netpay.cmbchina.com/netpayment/BaseHttp.dll?'.$tPay.'" target="_blank">';
		$button .= $param;
		$button .= '<input type="HIDDEN" name="BranchID" value="'.$BrandId.'">'."\r\n";
		$button .= '<input type="HIDDEN" name="CoNo" value="'.$OUKUNUMBER.'">'."\r\n";
		$button .= '<input type=submit class="cmbButton" value="[使用招商网上银行支付]"></form>';
		
        global $db, $ecs;
        $ouku_request = mysql_escape_string($button);
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '{$ouku_request}' WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);		
		return $button;
}

/**
     * 响应操作
     */
function respond()
{
	$key	=	'ouku';
	$payment  = get_payment($_REQUEST['code']);

	/* 察看是否启用这个支付方式 */
	if(!$payment['enabled']){
		return false;
	}

	$sSucceed	=	trim($_REQUEST['Succeed']);

	if($sSucceed	=='Y'){
		$order_sn		=	trim($_REQUEST['BillNo']);
		$order_total	=	trim($_REQUEST['Amount']);
		$MerchantPara 	= 	trim($_REQUEST['MerchantPara']);
		$sMd5Respond	=	md5($order_sn.number_format($order_total, 2, ".", "").$key);
		//echo($MerchantPara.':'.$sMd5Respond.'<br>');
		//print_r($GLOBALS['ecs']);
		$sql =	'select oi.order_id , pl.log_id  from ' . $GLOBALS['ecs']->table('order_info') .
		' oi , '.$GLOBALS['ecs']->table('pay_log').' as pl where oi.order_id = pl.order_id  and oi.order_sn = '."'".$order_sn."'";

		//echo($sql);
		$payInfo = $GLOBALS['db']->getRow($sql);
		//print_r($payInfo);
		//exit;
		if($payInfo){
			/* 改变订单状态 */
			/* 检查支付的金额是否相符 */
			if ($MerchantPara	!=	$sMd5Respond)
			{
				return false;
			}else{
				/* 改变订单状态 */
				order_paid($payInfo['log_id']);
				return true;
			}
		}
	}
}
}

?>