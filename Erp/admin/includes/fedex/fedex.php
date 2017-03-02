<?php

// Copyright 2009, FedEx Corporation. All rights reserved.
// Version 2.0.1

if (ini_get("date.timezone") == "") {
	date_default_timezone_set("Asia/ShangHai");
}

require_once dirname(__FILE__) . '/fedex-common.php';
ini_set("soap.wsdl_cache_enabled", "0");

/** Fedex验证送货地址
 * @param unknown_type $path_to_wsdl
 * @param unknown_type $data
 *  $data 格式：
	 array(
			'AddressId' => 'WTC', //可选
			'Address' => array(
				'StreetLines' => array(
					'10 FedEx Parkway'
				), 
				'City'=>'',//可选
				'StateOrProvinceCode'=>'',//可选
				'CountryCode'=>'',//可选
				'PostalCode' => '38017', //可选
				'CompanyName' => 'FedEx Services'//可选
			)
		)

 * return Array
			(
			    [code] => 0
			    [data] => Array
			        (
			            [AddressId] => WTC
			            [ProposedAddressDetails] => Array
			                (
			                    [Score] => 86
			                    [Changes] => Array
			                        (
			                            [0] => MODIFIED_TO_ACHIEVE_MATCH
			                            [1] => NORMALIZED
			                        )
			
			                    [ResidentialStatus] => BUSINESS
			                    [DeliveryPointValidation] => CONFIRMED
			                    [Address] => Array
			                        (
			                            [StreetLines] => 10 FED EX PKWY
			                            [City] => COLLIERVILLE
			                            [StateOrProvinceCode] => TN
			                            [PostalCode] => 38017-8711
			                            [CountryCode] => US
			                        )
			
			                    [ParsedAddress] => Array
			                        (
			                            [ParsedStreetLine] => Array
			                                (
			                                    [Elements] => Array
			                                        (
			                                            [0] => Array
			                                                (
			                                                    [Name] => houseNumber
			                                                    [Value] => 10
			                                                    [Changes] => NO_CHANGES
			                                                )
			
			                                            [1] => Array
			                                                (
			                                                    [Name] => streetName
			                                                    [Value] => FED EX
			                                                    [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                                )
			
			                                            [2] => Array
			                                                (
			                                                    [Name] => streetSuffix
			                                                    [Value] => PKWY
			                                                    [Changes] => NORMALIZED
			                                                )
			
			                                        )
			
			                                )
			
			                            [ParsedCity] => Array
			                                (
			                                    [Elements] => Array
			                                        (
			                                            [Name] => city
			                                            [Value] => COLLIERVILLE
			                                            [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                        )
			
			                                )
			
			                            [ParsedStateOrProvinceCode] => Array
			                                (
			                                    [Elements] => Array
			                                        (
			                                            [Name] => stateProvince
			                                            [Value] => TN
			                                            [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                        )
			
			                                )
			
			                            [ParsedPostalCode] => Array
			                                (
			                                    [Elements] => Array
			                                        (
			                                            [0] => Array
			                                                (
			                                                    [Name] => postalBase
			                                                    [Value] => 38017
			                                                    [Changes] => NO_CHANGES
			                                                )
			
			                                            [1] => Array
			                                                (
			                                                    [Name] => postalAddOn
			                                                    [Value] => 8711
			                                                    [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                                )
			
			                                            [2] => Array
			                                                (
			                                                    [Name] => postalDPV
			                                                    [Value] => 10
			                                                    [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                                )
			
			                                        )
			
			                                )
			
			                            [ParsedCountryCode] => Array
			                                (
			                                    [Elements] => Array
			                                        (
			                                            [Name] => country
			                                            [Value] => US
			                                            [Changes] => MODIFIED_TO_ACHIEVE_MATCH
			                                        )
			
			                                )
			
			                        )
			
			                    [RemovedNonAddressData] => 
			                )
			
			        )
			
			)
 */

function addressValidation($path_to_wsdl, $data)
{

	$request['WebAuthenticationDetail'] = array(
		'UserCredential' => array(
			'Key' => getProperty('key'), 
			'Password' => getProperty('password')
		)
	);
	$request['ClientDetail'] = array(
		'AccountNumber' => getProperty('shipaccount'), 
		'MeterNumber' => getProperty('meter')
	);
	$request['TransactionDetail'] = array(
		'CustomerTransactionId' => ' *** Address Validation Request v2 using PHP ***'
	);
	$request['Version'] = array(
		'ServiceId' => 'aval', 
		'Major' => '2', 
		'Intermediate' => '0', 
		'Minor' => '0'
	);
	$request['RequestTimestamp'] = date('c');
	
	$request['Options'] = array(
		'CheckResidentialStatus' => 1, 
		'MaximumNumberOfMatches' => 10,  // 最大10
		'StreetAccuracy' => 'MEDIUM',  // 可选参数EXACT, TIGHT, MEDIUM, LOOSE.
		                              // 准确，严密，中，松散。文档推荐使用 MEDIUM
		'DirectionalAccuracy' => 'MEDIUM',  // 可选参数EXACT, TIGHT, MEDIUM, LOOSE.
		                                   // 准确，严密，中，松散。
		'CompanyNameAccuracy' => 'MEDIUM',  // 可选参数EXACT, TIGHT, MEDIUM, LOOSE.
		                                   // 准确，严密，中，松散。
		'ConvertToUpperCase' => 1, 
		'RecognizeAlternateCityNames' => 1, 
		'ReturnParsedElements' => 1
	);
	
	$request['AddressesToValidate'][] = $data;
		
	try
	{
		$client = new SoapClient($path_to_wsdl, array(
			'trace' => 1
		));
	
		if (setEndpoint('changeEndpoint'))
		{
			$newLocation = $client->__setLocation(setEndpoint('endpoint'));
		}
		
		$response = $client->addressValidation($request);
		
		if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR')
		{
			$arr['code'] = 0;
			$result = json_decode(json_encode($response->AddressResults),true);
			$arr['data'] = $result;
			//printSuccess($client, $response);
			return $arr;
		}
		else
		{
			//printError($client, $response);
			return array('code' => 1, 'data' => $response->Notifications->Message);
		}
		//writeToLog($client); // Write to log file
	}
	catch (SoapFault $exception)
	{
		//printFault($exception, $client);
		return array('code' => 2, 'data' => "Code:{$exception->faultcode},String:{$exception->faultstring}");
	}
}


/**
 * Fedex打印送货单
 * @param string $path_to_wsdl
 * @param array $data
 * @return 
	 array(
		 [code] => 0
		 [data] => array (
			 [Image] => 数据
			 [MasterTrackingId] => Array
		                (
		                    [TrackingIdType] => FEDEX
		                    [FormId] => 0430
		                    [TrackingNumber] => 794798560776
		                )
	
	        )
	)
 */

function shipClient($path_to_wsdl, $params, $PackageLineItems = array())
{
	
	// {{{
	/*
	$data['PackageCount'] = 2;
	$data['TotalWeight'] = array(
			'Value' => 103.0,
			'Units' => 'LB'
	);
	
	$data['LabelSpecification'] = array(
			'LabelFormatType' => 'COMMON2D',  // valid values COMMON2D,
			// LABEL_DATA_ONLY,
			// FEDEX_FREIGHT_STRAIGHT_BILL_OF_LADING,
			// VICS_BILL_OF_LADING
			'ImageType' => 'ZPLII',  // valid values DPL, EPL2, PDF, ZPLII and PNG
			// STOCK_4X6 STOCK_4X6.75_LEADING_DOC_TAB
			// STOCK_4X6.75_TRAILING_DOC_TAB STOCK_4X8
			// STOCK_4X9_LEADING_DOC_TAB
			// STOCK_4X9_TRAILING_DOC_TAB
			'LabelStockType' => 'STOCK_4X6.75_LEADING_DOC_TAB',//PAPER_4X6
			'LabelPrintingOrientation' => 'TOP_EDGE_OF_TEXT_FIRST'
	);*/
	
	$data['LabelSpecification'] = array(
			'LabelFormatType' => 'COMMON2D',  // valid values COMMON2D,
			// LABEL_DATA_ONLY,
			// FEDEX_FREIGHT_STRAIGHT_BILL_OF_LADING,
			// VICS_BILL_OF_LADING
			'ImageType' => 'PDF',  // valid values DPL, EPL2, PDF, ZPLII and PNG
			// STOCK_4X6 STOCK_4X6.75_LEADING_DOC_TAB
			// STOCK_4X6.75_TRAILING_DOC_TAB STOCK_4X8
			// STOCK_4X9_LEADING_DOC_TAB
			// STOCK_4X9_TRAILING_DOC_TAB
			'LabelStockType' => 'PAPER_4X6',//PAPER_4X6
			'LabelPrintingOrientation' => 'TOP_EDGE_OF_TEXT_FIRST'
	);
	
	$data['ShippingChargesPayment'] = array(
			'PaymentType' => 'SENDER',  // valid values RECIPIENT, SENDER and
			// THIRD_PARTY
			'Payor' => array(
					'AccountNumber' => getProperty('billaccount'),
					'CountryCode' => 'CN'
			)
	);
	
	$data['Shipper'] = array(
			'Contact' => array(
					'PersonName' => 'CHRIS GUAN',
					'CompanyName' => 'CHRIS GUAN',
					'PhoneNumber' => '+85281972796'
			),
			'Address' => array(
					'StreetLines' => array(//最多2行
							'Unit152B,No.1355, Jinji Lake Avenue',//每行最多35字符,剩余自动截断
							'Suzhou Industrial Park'//每行最多35字符,剩余自动截断
					),
					'City' => 'SUZHOU',
					'StateOrProvinceCode' => 'JS',
					'PostalCode' => '215021',
					'CountryCode' => 'CN'
			)
	);
	/*
	$data['Recipient'] = array(
			'Contact' => array(
					'PersonName' => 'guan 2',
					'CompanyName' => 'g Company Name',
					'PhoneNumber' => '1234567890'
			),
			'Address' => array(
					'StreetLines' => array(//最多2行
							'Address Line 1g',//每行最多35字符,剩余自动截断
							'abcdef'//每行最多35字符,剩余自动截断
					),
					'City' => 'Richmond',
					'StateOrProvinceCode' => 'BC',
					'PostalCode' => 'V7C4V4',
					'CountryCode' => 'CA',
					'Residential' => false
			)
	);*/
	
	$data['CustomerClearanceDetail'] = array(
			'DutiesPayment' => array(
					'PaymentType' => 'RECIPIENT',  // valid values RECIPIENT, SENDER and THIRD_PARTY
					/* R 后面不需要任何内容
					'Payor' => array(
							'AccountNumber' => getProperty('dutyaccount'),
							'CountryCode' => 'CN'
					)*/
			),
			'DocumentContent' => 'NON_DOCUMENTS',/*
			'CustomsValue' => array(
					'Currency' => 'USD',
					'Amount' => 101.0
			),
			'Commodities' => array(
					'0' => array(
							'NumberOfPieces' => 1,
							'Description' => 'Books',
							'CountryOfManufacture' => 'US',
							'Weight' => array(
									'Units' => 'LB',
									'Value' => 1.0
							),
							'Quantity' => 4,
							'QuantityUnits' => 'EA',
							'UnitPrice' => array(
									'Currency' => 'USD',
									'Amount' => 102.000000
							),
							'CustomsValue' => array(
									'Currency' => 'USD',
									'Amount' => 400.000000
							)
					)
			),*/
			'ExportDetail' => array(
					'B13AFilingOption' => 'NOT_REQUIRED'
			)
	);
	// }}}
	
	$data = array_merge_recursive($data, $params);
	
	$request = array();
	
	$request['WebAuthenticationDetail'] = array(
		'UserCredential' => array(
			'Key' => getProperty('key'), 
			'Password' => getProperty('password')
		)
	);
	$request['ClientDetail'] = array(
		'AccountNumber' => getProperty('shipaccount'), 
		'MeterNumber' => getProperty('meter')
	);
	$request['TransactionDetail'] = array(
		'CustomerTransactionId' => '*** Express International Shipping Request v10 using PHP ***'
	);
	$request['Version'] = array(
		'ServiceId' => 'ship', 
		'Major' => '10', 
		'Intermediate' => '0', 
		'Minor' => '0'
	);
	
	$data['LabelSpecification']['CustomerSpecifiedDetail'] = array(
			'DocTabContent' => array(
				'DocTabContentType' => 'ZONE001', 
				'Zone001' => array(
					'0' => array(
						'ZoneNumber' => 1, 
						'Header' => 'Comp', 
						'DataField' => 'REQUEST/SHIPMENT/Shipper/Contact/CompanyName'
					), 
					'1' => array(
						'ZoneNumber' => 2, 
						'Header' => 'Ref', 
						'DataField' => 'REQUEST/SHIPMENT/CustomerReferences/CustomerReferenceType'
					), 
					'2' => array(
						'ZoneNumber' => 3, 
						'Header' => 'Sender', 
						'DataField' => 'REQUEST/SHIPMENT/Shipper/Contact/PersonName'
					), 
					'3' => array(
						'ZoneNumber' => 4, 
						'Header' => 'Name', 
						'DataField' => 'REQUEST/SHIPMENT/Recipient/Contact/PersonName'
					), 
					'4' => array(
						'ZoneNumber' => 5, 
						'Header' => 'Country', 
						'DataField' => 'REQUEST/SHIPMENT/Recipient/Address/CountryCode'
					), 
					'5' => array(
						'ZoneNumber' => 6, 
						'Header' => 'MAWB#', 
						'DataField' => 'REPLY/SHIPMENT/MasterTrackingId/TrackingNumber'
					), 
					'6' => array(
						'ZoneNumber' => 7, 
						'Header' => 'EIN#', 
						'DataField' => ''
					), 
					'7' => array(
						'ZoneNumber' => 8, 
						'Header' => 'TtlVal', 
						'DataField' => 'REQUEST/SHIPMENT/CustomsClearanceDetail/CustomsValue/Amount'
					), 
					'8' => array(
						'ZoneNumber' => 9, 
						'Header' => 'TtlWgt', 
						'DataField' => 'REQUEST/SHIPMENT/TotalWeight/Value'
					), 
					'9' => array(
						'ZoneNumber' => 10, 
						'Header' => 'Date', 
						'DataField' => 'REQUEST/SHIPMENT/ShipTimestamp'
					), 
					'10' => array(
						'ZoneNumber' => 11, 
						'Header' => 'ttlPkg', 
						'DataField' => 'REQUEST/SHIPMENT/PackageCount'
					), 
					'11' => array(
						'ZoneNumber' => 12, 
						'Header' => 'DimWgt', 
						'DataField' => 'REPLY/PACKAGE/RATE/ACTUAL/DimWeight/Value'
					)
				)
			),
		'CustomContent' => array(
			'TextEntries' => array(
				'Position' => array(
					'X' => 1, 
					'Y' => 2
				), 
				'Format' => 'Svcs:',
				'ThermalFontId' => 12, 
				'DataFields' => 'REQUEST/SHIPMENT/ServiceType'
			)
		)
	);
	
	$Shipper = $data['Shipper'];
	$Recipient = $data['Recipient'];
	$ShippingChargesPayment = $data['ShippingChargesPayment'];
	$CustomerClearanceDetail = $data['CustomerClearanceDetail'];
	$LabelSpecification = $data['LabelSpecification'];
	$PackageCount  = $data['PackageCount'];
	
	$PackageLineItem = $data['PackageLineItem'];
	
	$MasterTrackingId = isset($data['MasterTrackingId']) ? $data['MasterTrackingId'] : '';
	
	$request['RequestedShipment'] = array(
		'ShipTimestamp' => date('c'), 
		'DropoffType' => 'REGULAR_PICKUP',  // valid values REGULAR_PICKUP,
		                                   // REQUEST_COURIER, DROP_BOX,
		                                   // BUSINESS_SERVICE_CENTER and STATION
		'ServiceType' => 'INTERNATIONAL_ECONOMY',  // valid values
		                                          // INTERNATIONAL_ECONOMY
		                                          // INTERNATIONAL_ECONOMY_FREIGHT
		                                          // STANDARD_OVERNIGHT,
		                                          // PRIORITY_OVERNIGHT, FEDEX_GROUND,
		                                          // ...
		'PackagingType' => 'YOUR_PACKAGING',  // valid values FEDEX_BOX, FEDEX_PAK,
		                                     // FEDEX_TUBE, YOUR_PACKAGING, ...
		'TotalWeight' => $data['TotalWeight'], 
		'Shipper' => $Shipper, 
		'Recipient' => $Recipient, 
		'ShippingChargesPayment' => $ShippingChargesPayment, 
		'CustomsClearanceDetail' => $CustomerClearanceDetail, 
		'LabelSpecification' => $LabelSpecification, 
		'CustomerSpecifiedDetail' => array(
			'MaskedData' => 'SHIPPER_ACCOUNT_NUMBER'
		), 
		'RateRequestTypes' => array(
			'ACCOUNT'
		),  // valid values ACCOUNT and LIST
		                                        // 'MasterTrackingId' =>
		                                        // array('TrackingIdType' =>
		                                        // 'FEDEX','FormId' =>
		                                        // '0430','TrackingNumber'=>'794798511469'),
		'PackageCount' => $PackageCount, 
		'RequestedPackageLineItems' => array(
			'0' => $PackageLineItem
		), 
		'CustomerReferences' => array(
			'0' => array(
				'CustomerReferenceType' => 'CUSTOMER_REFERENCE', 
				'Value' => 'TC007_07_PT1_ST01_PK01_SNDUS_RCPCA_POS'
			)
		)
	);
	
	if ($PackageLineItem['SequenceNumber'] > 1)
	{
		$request['RequestedShipment']['MasterTrackingId'] = $MasterTrackingId;
	}
	
	//print_r($request);
	
	try
	{
		$client = new SoapClient($path_to_wsdl, array(
			'trace' => 1
		)); // Refer to
	
		if (setEndpoint('changeEndpoint'))
		{
			$newLocation = $client->__setLocation(setEndpoint('endpoint'));
		}
		
		$response = $client->processShipment($request); // FedEx web service
		                                                // invocation
		
		if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR')
		{
			//printSuccess($client, $response);
			$img = $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image;
			$arr['TrackingIdType'] = @$response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType;
			$arr['FormId'] = @$response->CompletedShipmentDetail->MasterTrackingId->FormId;
			$arr['TrackingNumber'] = @$response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
			
			$result['Image'] = $img;
			$result['MasterTrackingId'] = $arr;
			
			return array('code' => 0, 'data' => $result);
		}
		else
		{
			printError($client, $response);
			return array('code' => 1, 'data' => $response->Notifications->Message);
		}
		//writeToLog($client); // Write to log file
	}
	catch (SoapFault $exception)
	{
		printFault($exception, $client);
		return array('code' => 2, 'data' => "Code:{$exception->faultcode},String:{$exception->faultstring}");
	}
}

/*
return;

////////////////////打印配送单例子////////////////////////////

// {{{
$data['PackageCount'] = 2;
$data['TotalWeight'] = array(
		'Value' => 103.0,
		'Units' => 'LB'
);

$data['Recipient'] = array(
	'Contact' => array(
			'PersonName' => 'guan 2',
			'CompanyName' => 'g Company Name',
			'PhoneNumber' => '1234567890'
	),
	'Address' => array(
		'StreetLines' => array(//最多2行
				'Address Line 1g',//每行最多35字符,剩余自动截断
				'abcdef'//每行最多35字符,剩余自动截断
		),
		'City' => 'Richmond',
		'StateOrProvinceCode' => 'BC',
		'PostalCode' => 'V7C4V4',
		'CountryCode' => 'CA',
		'Residential' => false
	)
);
$data['CustomerClearanceDetail'] = array(
		
		'CustomsValue' => array(
				'Currency' => 'USD',
				'Amount' => 101.0
		),
		'Commodities' => array(
				'0' => array(
						'NumberOfPieces' => 1,
						'Description' => 'Books',
						'CountryOfManufacture' => 'US',
						'Weight' => array(
								'Units' => 'LB',
								'Value' => 1.0
						),
						'Quantity' => 4,
						'QuantityUnits' => 'EA',
						'UnitPrice' => array(
								'Currency' => 'USD',
								'Amount' => 102.000000
						),
						'CustomsValue' => array(
								'Currency' => 'USD',
								'Amount' => 400.000000
						)
				)
		)
);
// }}}


$shipclient_wsdl = dirname(__FILE__) . '/ShipService_v10.wsdl';

$result = shipClient($shipclient_wsdl, $data, $PackageLineItems);


die();

for($SequenceNumber = 1; $SequenceNumber <= $data['PackageCount']; $SequenceNumber++)
{
	$data['PackageLineItem'] = $PackageLineItems[$SequenceNumber - 1];
	$result = shipClient($shipclient_wsdl, $data);
	
	$data['MasterTrackingId'] = $result['data']['MasterTrackingId'];
	
	if($result['code'] == 1)
	{
		print_r($result);
	}
	
	$fp = fopen('shipexpresslabel' . $SequenceNumber . '.pdf', 'wb');
	fwrite($fp, $result['data']['Image']);
	fclose($fp);
	
	echo 'Label <a href="./shipexpresslabel' . $SequenceNumber . '.pdf" target="_blank">shipexpresslabel' . $SequenceNumber . '.pdf</a><br>';
}

*/

////////////////////打印配送单例子 - end////////////////////////////

////////////////////验证送货地址例子////////////////////////////
/*
$data = array(
	'AddressId' => 'WTC',  // 可选
	'Address' => array(
		// 最多包含四个child
		'StreetLines' => array(//最多2个child
			'10 FedEx Parkway' // 最长 35个字符
		, 
		// 'City' => 'SH', // 可选 最长 35个字符
		// 'StateOrProvinceCode' => '', // 可选 最长 14个字符
		// 'CountryCode' => '', // 可选 最长 2个字符
		'PostalCode' => '38017',  // 可选 最长 16个字符
		'CompanyName' => 'FedEx Services' // 可选
	
	
);

$address_validation_wsdl = "wsdl/ShipService_v10.wsdl";
$address_data = addressValidation($address_validation_wsdl, $data);
$address_data['data']['ProposedAddressDetails']['DeliveryPointValidation'] // CONFIRMED UNCONFIRMED UNAVAILABLE
if ($address_data['data']['ProposedAddressDetails']['DeliveryPointValidation'] == 'UNAVAILABLE')
{
	return;
}
echo $address_data['data']['ProposedAddressDetails']['DeliveryPointValidation']; 
 */
////////////////////验证送货地址例子 - end////////////////////////////
?>