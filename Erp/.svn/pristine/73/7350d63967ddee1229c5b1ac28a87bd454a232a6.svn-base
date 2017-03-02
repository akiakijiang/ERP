<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class OthersFreightSheet extends FreightSheet{
	
    /**
     * 计算快递费(宅急便，跨越速运，德邦直接导入不进行测试)
     */
    public function calculate_fee($order) {
        return $order;
    }
     /**
     * 费用检查(测试过程中--计算规则未维护，所以不需要测试)
     */
    protected function check_fee() {
    	return true;
    }
     /**
     * 检查费用是否异常(宅急便，跨越速运，德邦直接导入不进行费用检查--计算规则未维护)
     */
    public function check_freight_set() {
    	return true;
    }
    	
}
