<?php
require_once ROOT_PATH . 'admin/includes/express/cls_factorage_sheet.php';

class EMSFactorageSheet extends FactorageSheet{
    //设置excel模板
    protected $tpls = array (
        'date' => '交寄日期',
        'tracking_number' => '邮件号码',
        'goods_amount' => '实收费用',
        'service_fee' => '服务费',
        'back_service_fee' => '退回邮费',
        'final_fee' => '合计费用',
        'address' => '收件人地址',
    );
    /**
     * ems手续费计算
     * 合计费用 = 服务费  + 实收费用  * 快递费率  + 退回邮费
     * 费率：percent  2%
     * erp计算费用：calculated_fee
     */
    public function calculate_fee($order) {
        $facility_id = facility_convert($order['facility_id']);
        if($facility_id == '19568549' && $order['province'] == 10){
            $calculated_fee = $order['excel_row']['service_fee'] + $order['excel_row']['goods_amount'] * 0.005 + $order['excel_row']['back_service_fee'];
        }else{
            $calculated_fee = $order['excel_row']['service_fee'] + $order['excel_row']['goods_amount'] * 0.02 + $order['excel_row']['back_service_fee'];
        }
        $calculated_fee = $this->get_real_value($calculated_fee);
        return $calculated_fee;
    }
}