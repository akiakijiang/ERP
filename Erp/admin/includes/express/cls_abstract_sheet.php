<?php
/**
 * 运费抽象类
 *
 */
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel.php');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel/IOFactory.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_inter_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_ems_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_shunfeng_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_yuantong_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_zhaijisong_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_shentong_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_huitong_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_zhongtong_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_yunda_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_xiaobao_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_factorage_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_ems_factorage_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_zhaijisong_factorage_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_eyoubao_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_emseco_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_shunfengluyun_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_DHL_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_FEDEX_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_UPS_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_InterEMS_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_InterYouzheng_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_tiantian_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_yousucod_freight_sheet.php');
require_once(ROOT_PATH . 'admin/includes/express/cls_others_freight_sheet.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
// class runtime
// {
// 	var $StartTime = 0;
// 	var $StopTime = 0;
	 
// 	function get_microtime()
// 	{
// 		list($usec, $sec) = explode(' ', microtime());
// 		return ((float)$usec + (float)$sec);
// 	}
	 
// 	function start()
// 	{
// 		$this->StartTime = $this->get_microtime();
// 	}
	 
// 	function stop()
// 	{
// 		$this->StopTime = $this->get_microtime();
// 	}
	 
// 	function spent()
// 	{
// 		return round(($this->StopTime - $this->StartTime) * 1000, 1);
// 	}
	 
// }
abstract class AbstractSheet{
    //快递方式
    protected $shipping_id;

    //读取excel中名字的模板 一维数组
    protected $tpls = array(
        'date' => '日期',
        'tracking_number' => '运单号码',
        'weight' => '计费重量',
        'final_fee' => '应收费用',
        'excel_insurance' => '保价费',
    	'remark' =>'备注',
    );

    // sheet的内容
    protected $content = array();

    // 订单列表，key为运单号
    protected  $order_list = array();

    // 没有查到订单的运单号列表
    protected $missing_order_list = array();

    // 没有设置首重、续重费用，或者地区设置不对的列表
    protected $missing_carriage_list = array();

    // 费用计算错误的列表
    protected $error_fee_list = array();

    //更新费用列表
    protected $shipment_list = array();
    
    //快递选择方式和实际订单方式不一致表
    protected $error_match_list = array();
    
    //快递公司称重和仓库称重不一致列表
    protected  $error_weight = array();
    
    //更新办公件费用列表
    protected $office_shipment_list = array();
    
    //保价费多收列表
    protected $error_insurance_list = array();
    
    //快递方式
    const XIAOBAO = 119;
    const ZHAIJISONG_COD = 11;
    const EMS_COD = 36;
    const EMS = 47;
    const YUANTONG = 85;
    const SHUNFENG = 44;
    const TIANTIAN = 123;
    const HUITONG = 99;
    const YUNDA = 100;
    const SHENTONG_COD = 102;
    const EYOUBAO = 107;
    const ZHAIJISONG = 12;
    const SHENTONG = 89;
    const ZHONGTONG = 115;
    const EMSECO = 118;
    const SHUNFENGLUYUN = 117;
    const DHL = 95;
    const FEDEX = 112;
    const UPS = 103;
    const InterEMS = 93;
    const InterYouzheng = 92;
    const YOUSU_COD = 125;
    //估算重量加500g
    const ESTIMATE = 500;
    //费用类型
    const FACTORAGE = '1';
    const FREIGHT = '2';

    public static function createSheet($shipping_id, $type) {
        switch ($shipping_id) {
        	case self::FEDEX :
        		if($type == self::FREIGHT){
        			return new FEDEXFreightSheet($shipping_id);
        		}
        	case self::InterYouzheng :
        		if($type == self::FREIGHT){
        			return new InterYouzhengFreightSheet($shipping_id);
        		}
        	case self::InterEMS :
        		if($type == self::FREIGHT){
        			return new InterEMSFreightSheet($shipping_id);
        		}
        	case self::UPS :
        		if($type == self::FREIGHT){
        			return new UPSFreightSheet($shipping_id);
        		}
        	case self::DHL :
        		if($type == self::FREIGHT){
        			return new DHLFreightSheet($shipping_id);
        		}
        	case self::ZHONGTONG :
        		if($type == self::FREIGHT){
        			return new ZhongtongFreightSheet($shipping_id);
        		}
            case self::ZHAIJISONG_COD :
                if ($type == self::FREIGHT) {
                    return new ZhaiJiSongFreightSheet($shipping_id);
                } elseif ($type == self::FACTORAGE) {
                    return new ZhaiJiSongFactorageSheet($shipping_id);
                }
                break;
            case self::EMS_COD :
                if ($type == self::FREIGHT) {
                    return new EMSFreightSheet($shipping_id);
                } elseif ($type == self::FACTORAGE) {
                    return new EMSFactorageSheet($shipping_id);
                }
                break;
            case self::EMS :
                if ($type == self::FREIGHT) {
                    //EMS 非cod
                    return new EMSFreightSheet($shipping_id);
                }
                break;
            case self::EMSECO:
            	if($type == self::FREIGHT){
            		return new EmsEcoFreightSheet($shipping_id);
            	}
            	break;
            case self::SHUNFENGLUYUN:
            	if($type == self::FREIGHT){
            		return new ShunFengLuYunFreightSheet($shipping_id);
            	}
            	break;
            case self::SHUNFENG :
                if ($type == self::FREIGHT) {
                    return new ShunFengFreightSheet($shipping_id);
                }
                break;
            case self::TIANTIAN:
                if($type == self::FREIGHT){
                	 return new TianTianFreightSheet($shipping_id);
                }
            case self::YUANTONG :
                if ($type == self::FREIGHT) {
                    return new YuanTongFreightSheet($shipping_id);
                }
                break;
            case self::YUNDA :
                if ($type == self::FREIGHT) {
                    return new YunDaFreightSheet($shipping_id);
                }
                break;
            case self::HUITONG :
                if ($type == self::FREIGHT) {
                    return new HuiTongFreightSheet($shipping_id);
                }
                break;
            case self::EYOUBAO :
                if ($type == self::FREIGHT) {
                    return new EYOUBAOFreightSheet($shipping_id);
                }
                break;
            case self::ZHAIJISONG :
                if ($type == self::FREIGHT) {
                    return new ZhaiJiSongFreightSheet($shipping_id);
                }
                break;
            case self::SHENTONG_COD :
                if ($type == self::FREIGHT) {
                    return new ShenTongFreightSheet($shipping_id);
                }
                break;
            case self::SHENTONG :
                if ($type == self::FREIGHT) {
                    return new ShenTongFreightSheet($shipping_id);
                }
                break;
            case self::XIAOBAO:
            	if($type == self::FREIGHT){
            		return new XiaoBaoFreightSheet($shipping_id);
            	}
            	break;
           case self::YOUSU_COD:
            	if($type == self::FREIGHT){
            		return new YouSuCODFreightSheet($shipping_id);
            	}
            	break;
           default :
           		if($type == self::FREIGHT){
            		return new OthersFreightSheet($shipping_id);
            	}
            	break;
        }
    }

    protected function __construct($shipping_id){
        $this->shipping_id = $shipping_id;
    }


    /**
     * 检查excel的title格式是否正确，将title与tpls做比较
     *
     * @return boolean 正确返回true，否则返回false
     */
    //abstract public function check_format();
    protected function check_format(){
    	if('export' == $_REQUEST['act']){
    		$this->tpls = array(
        		'date' => '日期',
        		'tracking_number' => '运单号码',
        		'weight' => '计费重量',
        		'final_fee' => '应收费用',
        		'excel_insurance' => '保价费',
    			'remark' =>'备注',
    		);
    	}
        if (empty($this->tpls) || empty($this->content)) {
            return false;
        }
        foreach ($this->content as $sheet) {
            foreach ($sheet as $k => $row) {
                foreach ($row as $key => $col){
                    foreach ($this->tpls as $var_name => $col_name) {
						//提取想要的字段的数据。写入一条order记录中
                        if ($col_name == $key) {
                            $order[$var_name]  = $col;
                        }
                    }
                }
                //对运单号进行判断
                if (empty($order['tracking_number'])) {
                    continue;
                }
                
                //excel存在费用类型,专门针对顺风快递
                if (isset($order['fee_type'])) {                	
                    if ($order['fee_type'] == '保价' && $order['weight'] == 0) {
                        $order_list[$order['tracking_number']]['excel_insurance'] = $order['final_fee'];
                        $order_list[$order['tracking_number']]['tracking_number'] = $order['tracking_number'];
                        continue;
                    }
                }
                //一个运单号出现两条记录针对有保价费的订单woca
                //将保价费和运费记录合并为一条记录来分析。只有$order['fee_type']为'运费'的记录才会执行下面的判断
                //如果运费记录在保价费记录后面，$order_list[$order['tracking_number']]中只记录了保价费，没有其他记录
                if (!empty($order_list[$order['tracking_number']])) {
                    $order_list[$order['tracking_number']] = array_merge($order, $order_list[$order['tracking_number']]);
                    //array_merge中如果键名有重复，则后面的数据会覆盖之前的。
                } else {
                    $order_list[$order['tracking_number']] = $order;
                }
            }
        }
        unset($this->content);
        $this->content = $order_list;
        if (empty($this->content)) {
            return false;
        }
        return true;
    }

    /**
     * 计算运费或者手续费
     *
     * @param array $order 订单信息
     *
     * @return 费用
     */
    abstract protected function calculate_fee($order);


    /**
     * 读取excel内容，将内容写入content变量
     * 默认读取第一个sheet的内容，如果sheet_name不为，则搜索对应的sheet_name
     *
     * @param string $file 上传文件的路径
     * @param string $sheet_name 读取sheet的名字
     */
    public function read_excel($file, $sheet_name = null) { 
        // todo 获取sheet的内容，赋值给$this->content
//    	set_time_limit(0);
        $ext = pathinfo($file, PATHINFO_EXTENSION); //文件的扩展名；函数以数组的形式返回文件路径的信息。
//         die();
        try {
//        	$m1 = memory_get_usage();
            $ext == 'xlsx' ?
            $reader = PHPExcel_IOFactory::createReader('Excel2007') : $reader = PHPExcel_IOFactory::createReader('Excel5') ;
            $reader->setReadDataOnly(true);  // 设置为只读
            list($file,) = preg_split ('/\./', $file);//通过一个正则表达式分隔给定字符串。只获取数组中的第一个值。这里的效果就是去掉文件名的后缀
//             $run_time = new runtime();
//             $run_time->start();
//             $m2 = memory_get_usage();
//             var_dump($m2-$m1);
//             var_dump($m1);
//             var_dump($m2);
            $excel = $reader->load($file);//略慢，可以接受
// 			$run_time->stop();
// 			var_dump( "Script running time:".$run_time->spent()."ms");
//             die();
            
            unset($file);
            unset($reader);
            if ($sheet_name == null) {
                $sheet_name = 0;
                $sheet[$sheet_name] = $excel->getSheet(0);
            } else {
                $sheet[$sheet_name] = $excel->getSheetByName($sheet_name);
            }
            unset($excel);
            if (is_null($sheet[$sheet_name])) {
                return '该excel文件中未找到表' . $sheet_name;
            }
        }catch (Exception $e) {
            return  '读取文件内容失败，请检查该文件格式。详细错误消息:'. $e->getMessage();
        }
        if (empty($this->tpls)) {                //要读取excel表的字段名字
            return '请定义excel模板';
        }
        $i = 0;
//         $run_time = new runtime();
//         $run_time->start();
       
        foreach ($sheet[$sheet_name]->getRowIterator() as $rowIterator) {
            $cellIterator = $rowIterator->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);//读入除了不为空的值
            if ($i == 0) {//i=0的时候，读入的是字段名，下面的才是数据
                $j = 0;
                foreach ($cellIterator as $cell) {
                    $field = trim($cell->getValue());
                    // 空列不取数据
                    if (!empty($field)) {
                        $fields[$j] = $field;
                    }
                    $j++;
                }
                if (count($fields) != count(array_unique($fields))) {
                    return $sheet_name . "表中存在重复的列名";
                }
            } else {
                $j = 0;
                $row = array();
                $empty = true;  // 是否是空行
                foreach ($cellIterator as $cell) {
                    $field = trim($cell->getValue());
                    if (isset($fields[$j])) {
                        $row[$fields[$j]] = $field;
                        if (!empty($field)) {
                            $empty = $empty && false;
                        }
                    }
                    $j++;
                }
                if (!$empty) {
                    $rowset[$sheet_name][] = $row;  // 过滤空行
                }
            }
            unset($cellIterator);
            $i++;
        }
//         $run_time->stop();
//         var_dump( "Script running time:".$run_time->spent()."ms");
//         die();
        $this->content = $rowset;
        if (!$this->check_format()) {
            return "格式不正确";
        } else {
            return true;
        }
    }

    /**
     * 获取所有的订单信息
     */
    public function fetch_order_list($shipping_id) {
        foreach ($this->content as $row) {
            $order = $this->get_order_by_tracking_number($row['tracking_number']);
            if ($order === false) {
                $this->missing_order_list[$row['tracking_number']] = $row;
            } elseif ($order['shipment_type_id'] != $shipping_id) {
                $order['excel_row'] = $row;
                $this->error_match_list[$row['tracking_number']] = $order;
            }else{
//             现在应收费用中不包括保价费了
                $order['excel_row'] = $row;
                $this->order_list[$row['tracking_number']] = $order;
            }
        }
    }
    /**
     * 获取仓库称重，若没有称重则用估算重量
     * @param unknown_type $order
     * @return unknown|number
     */
    public function get_shipping_weight( & $order){
        if($order['shipping_leqee_weight'] != null && $order['shipping_leqee_weight'] != 0 ){
            return $order['shipping_leqee_weight'];
        }elseif ($order['shipping_leqee_weight'] == null || $order['shipping_leqee_weight'] == 0){
            global $db;
            $sql = "
            	SELECT 		SUM(og.goods_number*g.goods_weight) weight
		        FROM 		ecshop.ecs_order_goods og
		        LEFT JOIN 	ecshop.ecs_goods g on g.goods_id = og.goods_id
		        WHERE 		og.order_id = '{$order['order_id']}' 
            	GROUP BY 	og.ORDER_ID 
				LIMIT 1
            ";
            $weight = $db->getOne($sql);
            $order['estimate_weight'] = $weight + self::ESTIMATE ;
            //电商服务北京仓使用预估重量
            if($order['facility_id'] == '79256821'){
            	 $order['shipping_leqee_weight'] = $order['estimate_weight'];
            } 
            return $order['estimate_weight'];
        }
    }
    /**
     * 检查异常数据
     *
     * @return 如果有异常数据返回false，如果没有异常数据返回true
     */
    public function check_data() {
    	if('export' != $_REQUEST['act']){
    		$this->check_fee();
    	}
    	 //$this->check_freight_set();
        //如果是手续费不需要检查missing_carriage_list
        if (empty($this->missing_order_list)
        //empty($this->missing_carriage_list) &&
        && empty($this->error_fee_list) && empty($this->error_match_list)
        ) { 
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据运单号获取订单信息
     *
     * @param mix 返回订单信息，如果没有查到返回false
     */
    protected function get_order_by_tracking_number($tracking_number) {
        // todo 所有数据库中可能会用到的信息都在这里查询
        // 需要获取快递地区对应的首重、续重
        global $db;
        $sql = "
            SELECT o.zipcode, from_unixtime(o.shipping_time) as shipping_time, o.order_sn, o.order_id, o.party_id, s.tracking_number, s.last_update_stamp as invoice_shipping_time,o.taobao_order_sn, s.shipment_id, ep.pay_name, o.district, o.city, o.province,o.country,
                r1.region_name as district_name, r2.region_name as city_name, r3.region_name as province_name, o.consignee, 
                es.default_carrier_id as carrier_id, o.facility_id, o.shipping_id, p.name, s.shipping_category, s.shipment_type_id,
                f.facility_name, o.order_amount,s.shipping_leqee_weight,es.shipping_name
            FROM romeo.shipment s 
            LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
            LEFT JOIN ecshop.ecs_order_info o ON os.order_id = o.order_id
            LEFT JOIN romeo.party p ON p.party_id = convert(o.party_id using utf8)
            LEFT JOIN ecshop.ecs_payment ep ON ep.pay_id = o.pay_id
            LEFT JOIN ecshop.ecs_shipping es ON es.shipping_id = s.shipment_type_id
            LEFT JOIN romeo.facility f ON o.facility_id = f.facility_id
            LEFT JOIN ecshop.ecs_region as r1 ON r1.region_id = o.district
            LEFT JOIN ecshop.ecs_region as r2 ON r2.region_id = o.city
            LEFT JOIN ecshop.ecs_region as r3 ON r3.region_id = o.province 
            WHERE s.TRACKING_NUMBER = '{$tracking_number}'
        ";
        $order = $db->getRow($sql);
        if (!empty($order)) {
            return $order;
        } else {
            return false;
        }
    }

    /**
     * 检查所有的运费设置是否有异常，或者地区是否有异常，将这些异常存入$this->missing_carriage_list中
     * 只有快递费进行运费设置
     */
    //    protected function check_freight_set() {
    //        foreach ($this->order_list as $order) {
    //            if ($order['first_fee'] == 0 || $order['continued_fee'] == 0) {
    //                $this->missing_carriage_list[] = $order;
    //            }
    //        }
    //     }

    /**
     * 检查费用是否一致
     * excel读取到的快递费和手续费均为excel_row final_fee
     */
    abstract protected function check_fee();
 


    /**
     * 将异常数据输出
     * @param string $file_name 导出excel文件名
     */
    abstract function export_error_excel($file_name);


    /**
     * 导入返单件，供重载
     *tracking_number为返回运单号
     */
    protected function import_return_tracking_number() {
        foreach ($this->content as $order) {
            if (empty($order['order_info']['order_id'])) {
                continue;
            }
            //目前只针对宅急送返回运单导入
            $weight = $order['weight'] * 1000;
            $shipment_item = new stdClass();
            $shipment_item->orderId = $order['order_info']['order_id'];                //按照反单号原来的运单号查找到的order_id
            $shipment_item->partyId = $order['order_info']['party_id'];
            $shipment_item->shipmentTypeId = $order['order_info']['shipment_type_id'];      //快递方式
            $shipment_item->carrierId = $order['order_info']['carrier_id'];            //ecs_shipping中default_carrier_id
            $shipment_item->trackingNumber = $order['tracking_number'];                //返回运单号
            $shipment_item->shippingCategory = 'SHIPPING_RETURN';
            $shipment_item->shippingCost = 0;
            $shipment_item->shippingLeqeeWeight = 0;
            $shipment_item->shippingOutWeight = $weight;
            $shipment_item->shippingNote = '返回运单导入';
            $shipment_item->createdByUserLogin = $_SESSION['admin_name'];
            $soap_client = soap_get_client('ShipmentService');
            $result = $soap_client->createShipment_v2($shipment_item);
        }
    }

    /**
     * 更新数据
     * @param string $act
     */
    protected function update_fee() {
        $orderSoapclient = soap_get_client('ShipmentService');
        $webParam =  new stdClass();
        $webParam -> shipmentList = $this->shipment_list;
        $matchOrderResult = $orderSoapclient->updateShippingCostBatch($webParam);
    }
    /**
     * 保留两位小数
     * @param float $value
     */
    protected function get_real_value($value) {
        return sprintf("%01.2f ", round($value, 2));
    }
}