<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_factorage_sheet.php';

class ZhaiJiSongFactorageSheet extends FactorageSheet{
    //设置excel模板
    protected $tpls = array (
        'date' => '开单时间',
        'tracking_number' => '工作单号',
        //'weight' => '计费重量',
        'goods_amount' => '应收代收',
        'final_fee' => '手续费',
        'consignee' => '收货人',
        'address' => '到达地',
    );
    /**
     * 宅急送手续费计算
     * 
     * 费率：percent  东莞1.5% 上海1.3%
     * 如果（应收代收 * 快递费率）小于4，  ERP手续费 = 4 否则   ERP手续费  = 应收代收 * 快递费率
     * erp计算费用：calculated_fee
     * 四舍五如保留两位
     */
    public function calculate_fee($order) {
        //对费用结果保留两位小数
        $facility_id = facility_convert($order['facility_id']);
        if($facility_id == '19568549'){
            //如果为上海仓 费率为1.3%
            $calculated_fee = max(($order['excel_row']['goods_amount'] * 0.013), 4);
        }else{
            $calculated_fee = max(($order['excel_row']['goods_amount'] * 0.015), 4);
        }
        $calculated_fee = $this->get_real_value($calculated_fee);
        return $calculated_fee;
    }
}