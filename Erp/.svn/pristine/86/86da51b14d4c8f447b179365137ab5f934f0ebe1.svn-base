<?php 

/**
 * 货币相关服务接口
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */
 
require_once('lib_soap.php');

/**
 * 货币转换
 * 
 * @param float    $amount 金额
 * @param string   $from_code 外币
 * @param string   $to_code 本币
 * @param date     $date 日期
 * 
 * @return float 失败返回false
 */
function currency_conversion($amount, $from_code, $to_code, $date) {
    $handle = soap_get_client('CurrencyConversionService', 'ROMEO');
    $args = array(
        'fromCurrencyCode' => $from_code,
        'toCurrencyCode' => $to_code,
        'currencyConversionDate' => $date,
        'amount' => $amount,
    );
    $response = $handle->getCurrencyConversionAmountByDate($args);
    return round($response->return, 2);
}

function getCurrency() {
    $handle = soap_get_client('CurrencyConversionService', 'ROMEO');
    $args = array();
    $response = $handle->getCurrency($args);
	return wrap_object_to_array($response->return->Currency);
}

function createCurrency($currencyCode, $description) {
    $handle = soap_get_client('CurrencyConversionService');
    $args = array("code" => $currencyCode, "description" => $description);
    print_r($args);
    $response = $handle->createCurrency($args);
    print_r($response);
    return $response;
}

function removeCurrency($currencyCode) {
    $handle = soap_get_client('CurrencyConversionService');
    $args = array('code' => $currencyCode);
    return $handle->removeCurrency($args);
}

function createCurrencyConversion($args) {
	$handle = soap_get_client('CurrencyConversionService');
    $args['createdUserByLogin'] = $_SESSION['admin_name'];
    return $handle->createCurrencyConversion($args);
}

function deleteCurrencyConversion($currencyConversionId) {
	$handle = soap_get_client('CurrencyConversionService');
    $args = array("currencyConversionId" => $currencyConversionId);
    return $handle->deleteCurrencyConversion($args);
}

function getCurrencyConversionByCondition($args) {
	$handle = soap_get_client('CurrencyConversionService');
	foreach (array('offset', 'limit', 'begin', 'end', 'fromCurrencyCode', 'toCurrencyCode') as $key) {
		if (!$args[$key]) {
			$args[$key] = null;
		}
	}
	
    $response = $handle->getCurrencyConversionByCondition($args);		
	// print_r($response);
	return wrap_object_to_array($response->return->resultList->CurrencyConversion);
}


/**
 * 取得币种列表
 * 
 */
function get_currencys() {
    global $db;
    $sql = "SELECT currency_code,description FROM romeo.currency";
    $currency_list = $db->getAll($sql); 
    $currencys = array();
    foreach ($currency_list as $currency) {
        $currencys[$currency['currency_code']] = $currency['description'];
    }
    return $currencys;
}	