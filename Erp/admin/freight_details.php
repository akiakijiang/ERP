<?php
/**
 * 物流对账计算方法（标注excel的匀为导入数据）
 * =======================================================
 * EMS快递(快递费率  = 2%)
 * -------返款明细-------------
 *  实收费用 = 0
 *    手续费  = 服务费 = 5   结算费 = 0   快递费 = 退回邮费  = 10
 *  实收费用! = 0
 *    服务费 = 10    退回邮费  = 0    结算费 = 实收费用 *快递费率
 *    手续费  = 服务费 + 实收费用 * 快递费率
 *
 * 总结：返款明细中只计算手续费
 *       ERP 手续费 = 服务费 + 实收费用 *快递费率  (ERP计算) +退回邮费
 *       excel 手续费 = 服务费 + 结算费    +退回邮费 = 合计费用 (excel导入的快递费)
 *       手续费差值  = excel 手续费 - ERP 手续费
 * --------网络邮费----------
 *  重量单位为g
 *  ERP重量  = (重量(excel) /1000 进一取整)
 *  重量比较  ERP重量*1000 ！= 重量 (excel)  提示重量有误
 *  ERP快递费  = 首重快递费 + (ERP重量 - 1)*快递费率
 *  excel快递费 = 邮资
 *  快递费差值  = excel快递费 - ERP快递费
 * =================================================
 * 宅急送快递(快递费率 = 1.5%)
 * -------返款明细-----------
 *  计算手续费
 *    如果（应收代收 * 快递费率）小于4，  ERP手续费 = 4 否则   ERP手续费  = 应收代收 * 快递费率
 * --------返款明细和运费明细---------------
 *  计费重量
 *   如果为整数       ERP计费重量  = excel计费重量
 *   如果不为整数   ERP计费重量  = excel计费重量进0.5
 *  快递费用
 *   ERP快递费用 = 首重快递费 + (ERP计费重量 - 1) *续重快递费
 *
 */


define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/includes/freight/cls_abstract_sheet.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv('carriage_detail_manage');
$act = $_REQUEST['act'];
$shipping_id = $_REQUEST['shipping'];
$action = $_REQUEST['action'];
$type = intval($_REQUEST['type']);
$message = '';

switch ($act) {
    case 'export-f':
        //返单导入
        //目前只有宅急送进行返单导入，返单导入的excel模板与快递费的excel模板相同
        $type = 2;
        $file_name = file_upload();
        $sheet = AbstractSheet::createSheet($shipping_id, $type);
        //excel的读取
        $result = $sheet->read_excel($file_name);
        if ($result !== true) {
            //输出错误
            $message = $result;
            break;
        }
        $sheet->fetch_order_list($shipping_id);
        $order_list = $sheet->import_return_tracking_number();
        $message = "以下运单号没有查找到对应的订单，导入失败：" . $order_list;
        break;
    case 'upload':
    	QLog::log('freight_details-upload:begin');
        //测试
        $file_name = file_upload();
        QLog::log('freight_details-upload:file_upload end');
        
        $sheet = AbstractSheet::createSheet($shipping_id, $type);
        QLog::log('freight_details-upload:createSheet end');
        
        //excel的读取
        $result = $sheet->read_excel($file_name);
        QLog::log('freight_details-upload:read_excel end');
        
        if ($result !== true) {
            //输出错误
            $message = $result;
            break;
        }
        QLog::log('freight_details-upload:fetch_order_list begin');
        $sheet->fetch_order_list($shipping_id);
        QLog::log('freight_details-upload:fetch_order_list end');
        
        if ($type == 2) {
        	$sheet->check_office_shipment($shipping_id);
        	 QLog::log('freight_details-upload:check_office_shipment end');
        }
        QLog::log('freight_details-upload:check_data begin');
        $result = $sheet->check_data();
        QLog::log('freight_details-upload:check_data end');
        
        $sheet->export_error_excel('费用明细');
        QLog::log('freight_details-upload:export_error_excel end');
        QLog::log('freight_details-upload:end');
        
        break;
    case 'export':
        //导入
        admin_priv('carriage_detail_export');
        $file_name = file_upload();
        $sheet = AbstractSheet::createSheet($shipping_id, $type);
        //excel的读取
        $result = $sheet->read_excel($file_name);
        if ($result !== true) {
            //输出错误
            $message = $result;
            break;
        }
        $sheet->fetch_order_list($shipping_id);
        if ($type == 2) {
            $sheet->check_office_shipment($shipping_id);
        }
        $result = $sheet->check_data();
        if ($result) {
            $sheet->update_fee();
            $message = '数据更新成功';
        } else {
            $message = '存在异常数据';
        }  
        break;     
}
unset($sheet);
$smarty->assign('message', $message);
unset($message);
$smarty->assign('shipping_lists',getShippingTypes());
//$smarty->assign('available_facility',get_available_facility($_SESSION['party_id']));
$smarty->display("oukooext/freight_details.htm");
/**
 * 接收上传的excel文件，返回文件的路径和文件名
 * 
 */
function file_upload () {
    $uploader = new Helper_Uploader();
    $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
    if (!$uploader->existsFile('excel')) {
        $message = '没有选择上传文件，或者文件上传失败';
        sys_msg($message);
    }
    $file = $uploader->file('excel');
    //获取文件路径及文件后缀
    $file_name = $file->filepath() . "." .$file->extname();
    unset($uploader);
    unset($file);
    return $file_name;
}