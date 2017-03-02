<?php
/**
 * 销售发票公用库
 *  
 * 
 */

/**
 * 将小写金额转成大写金额
 *
 * @param float $input
 * @return string
 */
function num2capital($input) {
    $input =  (float) $input;
    
    //零元特殊处理
    if ($input == 0) {
        return '零元整';
    }
    
    // 负数转成整数，最后加上 负 
    $negative = false;
    if ($input < 0) {
        $negative = true;
        $input  = abs($input);
    }

    $cap = '';
    
    // 中文数字映射
    $num_array = array(
    0 => '零', 1 => '壹', 2 => '贰',3 => '叁',4 => '肆', 
    5 => '伍', 6 => '陆', 7 => '柒',8 => '捌',9 => '玖');
    
    //单位映射
    $cap_array = array('圆','拾','佰','仟','万','拾万','佰万','仟万','亿');
    
    //先获得整数部分的大写
    $num = intval($input);
    
    $i = 0;
    $last_temp = 1;         // 保存上一位的数字
    $new_num = 0;
    while ($num >= 1) {     // 得到各位数字
        $temp = $num % 10;
        $num = intval($num / 10);
        $new_num += ($temp * pow(10, $i)); //保存值，用于决定中间是否加零
        
        if ($temp > 0) {
            $cap = $num_array[$temp].$cap_array[$i] . $cap;
        } else {
            if ($i == 0) {
                $cap = $cap_array[$i];
            }
            if ($last_temp != 0 && $new_num > 0) {
                $cap = '零'.$cap;
            }
        }
        
        $last_temp = $temp;
        $i++;
    }
    
    $last_temp = $new_num % 10; //取得个位数。方便后面的判断是否要加零
    
    $input_str = (string) $input;   
    if (strpos($input_str, '.') === false) {
        $cap .= "整";
    } else { //如果有小数点，转换小数部分的显示 不能使用减法得到小数部分，浮点运算有误差
        $decimal1 = substr($input_str, strpos($input_str, '.') + 1, 1);
        $decimal2 = substr($input_str, strpos($input_str, '.') + 2, 1);
        
        $decimal1 = intval($decimal1);
        $decimal2 = intval($decimal2);
        
        if ($decimal1 > 0) {  //角
            if ($last_temp == 0) { // 如果个位为零，则在前面加零
                $cap .= "零";
            }
            $cap .= $num_array[$decimal1].'角';
        }
    
        if ($decimal2 > 0) {  //分
            if ($decimal1 == 0 && $new_num > 0) {
                $cap .= "零";
            }
            $cap .= $num_array[$decimal2].'分';
        } else {
            if ($decimal1 > 0) { //没有分加上整
                $cap .= "整";
            }
        }
    }
    
    // 如果是负数加上 负
    if ($negative)  {
        $cap = '负'.$cap;
    }
    return $cap;
}

/**
 * 判断两个时间的月是否相等
 *
 * @param string $day1
 * @param string $day2
 * @return boolean
 */
function month_equal($day1, $day2 = null) {    
    $month1 = date("Y-m", strtotime($day1));
    if ($day2 == null) {
        $month2 = date('Y-m');
    } else {
    	$month2 = date('Y-m', strtotime($day2));
    }
    return ($month1 == $month2);
}

/**
 * 判断day1的月小于day2
 *
 * @param string $day1
 * @param string $day2
 * @return boolean
 */
function month_less($day1, $day2 = null) {    
    $month1 = date("Y-m", strtotime($day1));
    if ($day2 == null) {
        $month2 = date('Y-m');
    } else {
    	$month2 = date('Y-m', strtotime($day2));
    }
    return ($month1 < $month2);
}

/**
 * 生成发票抬头
 *
 * @param array $invoice_header
 * @return int                  //  返回的主键
 */
function generate_sales_invoice_header ($invoice_header) {
    global $db, $_CFG;
    
    $invoice_header = array_merge(
        //基本的信息
        array(
            'created_user'  => $_SESSION['admin_name'],
            'created_stamp' => date("Y-m-d H:i:s"),
            'updated_stamp' => date("Y-m-d H:i:s"),  
            'red_flag'      => 'N',
            'invoice_date'  => date('Y-m-d'),
        ),     
        $invoice_header
    );
    if (!empty($_CFG['invoice_date']) && trim($_CFG['invoice_date']) != "") {
    	$invoice_header['description'] .= " 实际打印时间为：" . date("Y-m-d H:i:s");
    	$invoice_header['invoice_date'] = trim($_CFG['invoice_date']);
    }
    $db->autoExecute('sales_invoice', $invoice_header);
    $sales_invoice_id = $db->insert_id();
    return $sales_invoice_id;
}

/**
 * 根据发票抬头，明细生成发票
 *
 * @param array $invoice_header // 发票抬头
 * @param array $invoice_lines  // 发票明细
 * @return bool
 */
function generate_sales_invoice ($invoice_header, $invoice_lines) {
    global $db;
    $invoice_line_size = 13;         // 每张发票明细的条数
    
    $index = 0;
    $erp_ids = array();
    $erp_goods_sns = array();
    
    // 去除unit_price = 0的条目
    $gift_goods_num = 0;
    foreach ($invoice_lines as $key => $invoice_line) {
        if ($invoice_line['unit_price'] == 0) {
            $gift_goods_num += ($invoice_line['quantity']);
            unset($invoice_lines[$key]);
        }
    }
    
    if (empty($invoice_lines)) {
        return "开票项目金额都为0，无法开具发票";
    }

    $goods_amount = 0;              //商品金额计数器
    $invoice_amount = 0;            //发票金额计数器
    $temp_invoice_lines = array();  //临时的发票行记录变量

    $tax_rate = $invoice_header['tax_rate'];    //获的税率

    $base_line = array();           // 发票行的默认信息
    $base_line['created_user'] = $_SESSION['admin_name'];
    $base_line['created_stamp'] = date("Y-m-d H:i:s");
    $base_line['updated_stamp'] = date("Y-m-d H:i:s");
    
    $db->start_transaction();        //开始事务
    foreach ($invoice_lines as $invoice_line) {
        $invoice_line['erp_ids'] = trim($invoice_line['erp_ids']);
        if ($gift_goods_num > 0) {
            $invoice_line['item_model'] .= " 套餐(含{$gift_goods_num}件赠品)";
            $gift_goods_num = 0;
        }
        $temp_total = ($invoice_line['quantity'] * $invoice_line['unit_price']);
        $invoice_amount += $temp_total;
        if ($invoice_line['item_type'] == 'GOODS') {
            $erp_ids[] = $invoice_line['erp_ids'];
            $goods_amount += $temp_total;   //记录订单商品的金额，如果没有订单商品最后不打印发票
        }
        $temp_invoice_lines[] = $invoice_line;
        
        $index ++;
        
        //如果数量到了一张发票，生成一张发票
        if ($index >= $invoice_line_size) {
            if ($goods_amount > 0) {
                $invoice_net_amount = $invoice_amount / (1 + $tax_rate);
                $invoice_tax = $invoice_amount - $invoice_net_amount;
                $invoice_header['total_amount'] = $invoice_amount;
                $invoice_header['total_net_amount'] = $invoice_net_amount;
                $invoice_header['total_tax'] = $invoice_tax;
                $invoice_header['erp_ids'] = join(',', $erp_ids);
                $invoice_header['print_status'] = 'PENDING';
                $invoice_header['status'] = 'INIT';
                $invoice_header['red_flag'] = 'N';
                // 生成发票头
                $sales_invoice_id = generate_sales_invoice_header($invoice_header);

                foreach ($temp_invoice_lines as $temp_line) { //生成发票明细项
                    $temp_line = array_merge($base_line, $temp_line);
                    $temp_line['sales_invoice_id'] = $sales_invoice_id;
                    $temp_line['unit_net_price'] = $temp_line['unit_price'] / (1 + $tax_rate);
                    $temp_line['unit_tax'] = $temp_line['unit_price'] - 
                                                    $temp_line['unit_net_price'];                    
                    $db->autoExecute('sales_invoice_item', $temp_line);
                }
            }
            
            $index = 0;
            $goods_amount = 0;
            $invoice_amount = 0;
            $erp_ids = array();
            $temp_invoice_lines = array();
        }
    }
    
    //如果还剩下商品要打印的
    if ($index > 0 && $goods_amount > 0) {
        $invoice_net_amount = $invoice_amount / (1 + $tax_rate);
        $invoice_tax = $invoice_amount - $invoice_net_amount;
        
        $invoice_header['total_amount'] = $invoice_amount;
        $invoice_header['total_net_amount'] = $invoice_net_amount;
        $invoice_header['total_tax'] = $invoice_tax;
        $invoice_header['erp_ids'] = join(',', $erp_ids);
        $invoice_header['print_status'] = 'PENDING';
        $invoice_header['status'] = 'INIT';
        $invoice_header['red_flag'] = 'N';
        // 生成发票头
        $sales_invoice_id = generate_sales_invoice_header($invoice_header);
        
        foreach ($temp_invoice_lines as $temp_line) { //生成发票明细项
            $temp_line = array_merge($base_line, $temp_line); //加入基础信息
            $temp_line['sales_invoice_id'] = $sales_invoice_id;
            $temp_line['unit_net_price'] = $temp_line['unit_price'] / (1 + $tax_rate);
            $temp_line['unit_tax'] = $temp_line['unit_price'] - $temp_line['unit_net_price'];
            $db->autoExecute('sales_invoice_item', $temp_line);
        }
    }
    
    $db->commit();       //结束事务
    return true;
}

/**
 * 取消发票打印，操作人员发起了打印发票的请求，但还未打印，需要取消请求
 *
 * @param int $sales_invoice_id
 * @return mixed
 */
function cancel_sales_invoice($sales_invoice_id) {
    global  $db;
    $sales_invoice_id = intval($sales_invoice_id);
    
    $sql = "select * from sales_invoice where sales_invoice_id = '{$sales_invoice_id}' ";
    $sales_invoice = $db->getRow($sql);

    if (!$sales_invoice) {
        return '找不到发票';
    }
    
    if ($sales_invoice['print_status'] != 'PENDING') {
        return '该发票无法取消';
    }
    
    if (!empty($sales_invoice['invoice_no'])) {
        return '该发票已打印，无法取消';
    }
    
    if ($sales_invoice['tax_returned'] == 'Y') {
        return '无法取消已报税发票';
    }
    $db->start_transaction();        //开始事务    
    $sql = "update sales_invoice set print_status = 'CANCELED' 
            where sales_invoice_id = '{$sales_invoice_id}'";
    $db->query($sql);
    $db->commit();       //结束事务
    return true;
}

/**
 * 发票作废
 * 当月未报税发票可以作废
 *
 * @param string $invoice_no        // 发票号码
 * @param string $return_order_sn   // 退货订单
 * @param string $reason            // 发票作废的原因
 * @param string $invoice_issuer    // 发票开具方
 * @return mixed
 */
function discard_sales_invoice(
$invoice_no, $return_order_sn = '', $reason = '', $invoice_issuer = 'OUKU'
) {
    global $db;
    
    $sql = "select * from sales_invoice 
            where invoice_no = '{$invoice_no}' and invoice_issuer = '{$invoice_issuer}' ";
    $sales_invoice = $db->getRow($sql);
    
    //限制只能作废发票时间大于等于当月的发票
    if (month_less($sales_invoice['invoice_date'])) {
        return '不是大于等于当月的发票无法作废，请等财务报税后红冲发票';
    }
    
    if (!$sales_invoice) {
        return '找不到该发票号';
    }
    
    if ($sales_invoice['tax_returned'] == 'Y') {
        return '无法作废已报税发票';
    }
    
    if ($sales_invoice['print_status'] != 'PRINTED') {
        return '该发票无法作废';
    }
    
    $db->start_transaction();        //开始事务
    $sql = "update sales_invoice 
            set print_status = 'DISCARDED', return_order_sn = '{$return_order_sn}',
            description = CONCAT_WS('\n', description, '{$reason}') 
            where invoice_no = '{$invoice_no}' and invoice_issuer = '{$invoice_issuer}' ";
    $db->query($sql);
    $db->commit();       //结束事务
    return true;
}

/**
 * 发票报税
 *
 * @param array $invoice_nos        // 发票号码
 * @param string $invoice_issuer    // 发票开具方
 * @return mixed
 */
function tax_return_sales_invoice($invoice_nos, $invoice_issuer = 'OUKU') {
    global $db;
    
    if (count($invoice_nos) == 0) {
        return "没有发票号";
    }
    
    $sql = "select invoice_no, tax_returned
            from sales_invoice where invoice_issuer = '{$invoice_issuer}' and "
            .db_create_in($invoice_nos, 'invoice_no');
    $invoices = $db->getAll($sql);
    
    if (count($invoices) != count($invoice_nos)) {
        return '输入中有些发票号不存在';
    }
    
    $query_invoice_nos = array();
    foreach ($invoices as $invoice) {
        if ($invoice['tax_returned'] == 'Y') {
            return $invoice['invoice_no'] . " 已经报税了 ";
        }
        $query_invoice_nos[] = $invoice['invoice_no'];
    }
    
    $diff = array_diff($invoice_nos, $query_invoice_nos);
    
    if (!empty($diff)) {
        return "以下发票号无法查询到：".join($diff, ',');
    }
    
    $db->start_transaction();
    $sql = " update sales_invoice set tax_returned = 'Y',status = 'COMPLETED' 
            where  " .db_create_in($invoice_nos, 'invoice_no');    
    $db->query($sql);

    $db->commit();
    return true;
}

/**
 * 回写发票号
 *
 * @param int $sales_invoice_id
 * @param string $invoice_no
 * @param string $invoice_issuer    // 发票开具方
 * @return mixed
 */
function update_sales_invoice_no($sales_invoice_id, $party_id, $facility_id, $invoice_no, $invoice_issuer = 'OUKU') {
    global $db;
    $sales_invoice_id = intval($sales_invoice_id);   
    
    $sql = "select si.*,sii.order_id 
    			from  ecshop.sales_invoice si 
				inner join ecshop.sales_invoice_item sii 
					on si.sales_invoice_id = sii.sales_invoice_id 
            where si.sales_invoice_id = {$sales_invoice_id}
            and si.print_status = 'PENDING' 
            and ".party_sql("si.party_id", $party_id)."
            and ".facility_sql('si.facility_id', $facility_id);
    $sales_invoice = $db->getRow($sql);
    if (empty($sales_invoice)) {
        return '发票请求不存在，请确认你目前所处组织分仓是否正确';
    }
    
    if (!empty($sales_invoice['invoice_no'])) {
        return '已存在发票号';
    }
    
    //检查是否存在相同的发票号
    $sql = "select sales_invoice_id from sales_invoice where invoice_no = '{$invoice_no}' ";
    if ($db->getOne($sql)) {
        return "已存在相同的发票号 {$invoice_no} ";
    }
    
    $db->start_transaction();
    $sql = "UPDATE sales_invoice ".
           "SET invoice_issuer = '{$invoice_issuer}', updated_stamp = '".date('Y-m-d H:i:s')."', tax_returned = 'N',
                print_status = 'PRINTED', status = 'CONFIRMED', invoice_no = '{$invoice_no}' ".
           "WHERE sales_invoice_id = {$sales_invoice_id} ";
    $db->query($sql);
    $affected_rows = $db->affected_rows();
    $db->commit();
    
    if ($affected_rows == 1) {
        if ($sales_invoice['order_id']) {
            $sql = "update romeo.order_shipping_invoice set shipping_invoice = '{$invoice_no}'
                    where order_id =  {$sales_invoice['order_id']} ";
            $db->query($sql);
        }
        return true;
    } else {
    	return '更新失败';
    }
}

/**
 * 红冲发票
 * 报税后的发票可以红冲，跨月只能红冲
 *
 * @param string $invoice_no 原发票
 * @param int $return_order_sn  相关订单号
 * @param string $reason 红冲的原因
 * @param string $invoice_issuer 发票开具方
 * @return mixed
 */
function write_off_sales_invoice($invoice_no, $return_order_sn, $reason, $invoice_issuer = 'OUKU') {
    global  $db, $_CFG;
    $time_stamp = date("Y-m-d H:i:s");
    
    $sql = "select sales_invoice_id, party_id, facility_id, type, invoice_issuer, tax_returned,
            partner_id, partner_name, total_amount, total_net_amount, total_tax, tax_rate,
            shipping_name, pay_name, description, print_status, root_order_sn
            from sales_invoice 
            where invoice_no = '{$invoice_no}' and invoice_issuer = '{$invoice_issuer}' ";
    $sales_invoice = $db->getRow($sql);
    if (!$sales_invoice) {
        return '不存在该发票号';
    }
    
    if ($sales_invoice['print_status'] != 'PRINTED') {
        return '该发票无法红冲';
    }

    //发票没有报税，且大于等于单月，不可以红冲只能作废
    if (($sales_invoice['tax_returned'] == 'N')
        && !month_less($sales_invoice['invoice_date'])
        ) {
        return '该发票还未报税，请直接作废';
    }
    
    
    $sql = "select 1 from sales_invoice 
            where related_invoice_id = {$sales_invoice['sales_invoice_id']} and  
                  print_status NOT IN ('DISCARDED', 'CANCELED')";
    if ($db->getOne($sql)) {
        return "该发票已有对应的红冲发票 {$related_invoice_no} ";
    }
    
    $db->start_transaction();           // 开始事务
    
    $write_off_sales_invoice = $sales_invoice;
    $write_off_sales_invoice['related_invoice_id'] = $sales_invoice['sales_invoice_id'];
    unset($write_off_sales_invoice['sales_invoice_id']);// 红冲发票的sales_invoice_id删除
    unset($write_off_sales_invoice['tax_returned']);    // 红冲发票的是否报税暂时置空
    unset($write_off_sales_invoice['invoice_issuer']);  // 发票开具方置空
    
    $write_off_sales_invoice['red_flag'] = 'Y';
    $write_off_sales_invoice['print_status'] = 'PENDING';
    $write_off_sales_invoice['status'] = 'INIT';
    $write_off_sales_invoice['total_amount'] = -1 * abs($write_off_sales_invoice['total_amount']);
    $write_off_sales_invoice['total_net_amount'] = -1 * abs($write_off_sales_invoice['total_net_amount']);
    $write_off_sales_invoice['total_tax'] = -1 * abs($write_off_sales_invoice['total_tax']);
    $write_off_sales_invoice['created_user'] = $_SESSION['admin_name'];
    $write_off_sales_invoice['created_stamp'] = $time_stamp;
    $write_off_sales_invoice['updated_stamp'] = $time_stamp;
    $write_off_sales_invoice['return_order_sn'] = $return_order_sn;
    $write_off_sales_invoice['invoice_date'] = date('Y-m-d');
    $write_off_sales_invoice['description'] = "生成红冲发票的原因：".$reason;
    if (!empty($_CFG['invoice_date']) && trim($_CFG['invoice_date']) != "") {
    	$write_off_sales_invoice['description'] .= " 实际打印时间为：" . date("Y-m-d H:i:s");
    	$write_off_sales_invoice['invoice_date'] = trim($_CFG['invoice_date']);
    }
    
    $db->autoExecute('sales_invoice', $write_off_sales_invoice);
    $sales_invoice_id = $db->insert_id();
    
    // 基础信息
    $base_line['created_user'] = $_SESSION['admin_name'];
    $base_line['created_stamp'] = $time_stamp;
    $base_line['updated_stamp'] = $time_stamp;

    
    // 将原订单的明细复制到红冲发票的明细
    $sql = "select * from sales_invoice_item 
            where sales_invoice_id = {$sales_invoice['sales_invoice_id']} ";
    $invoice_items = $db->getAll($sql);
    foreach ($invoice_items as &$invoice_item) {
        unset($invoice_item['sales_invoice_item_id']);
        $invoice_item['sales_invoice_id'] = $sales_invoice_id;
        //$invoice_item['order_id'] = $order_id;
        $invoice_item['quantity'] = -1 * abs($invoice_item['quantity']);
        $invoice_item['unit_net_price'] = -1 * abs($invoice_item['unit_net_price']);
        $invoice_item['unit_tax'] = -1 * abs($invoice_item['unit_tax']);
        $invoice_item = array_merge($base_line, $invoice_item);
        $db->autoExecute('sales_invoice_item', $invoice_item);
    }
    
    $db->commit();                      // 结束事务
    return true;
}



/**
 * 获得商品对应的发票明细
 *
 * @param array $goods_list     商品的数组
 * @param int $gift_goods_num   赠品的数目
 * @param float $goods_amount   商品金额总和计数器
 * @param array $main_lines     主要商品的明细
 * @param array $fittings_lines 配件的明细
 * @param string $type 调用的类型 自动打印发票(auto) 手动开票(manual)
 */
function _get_sales_invoice_goods_related(
    $goods_list, $gift_goods_num, &$goods_amount, &$main_lines, &$fittings_lines, $type
) {
    require_once("includes/lib_goods.php");
    $goods_amount = 0;   
    foreach ($goods_list as $goods) {
        
        // 处理goods_name，将 brandname 去掉
        if (strpos($goods['goods_name'], $goods['brand_name']) === 0) {
            $goods['goods_name'] = trim(substr($goods['goods_name'], strlen($goods['brand_name'])));
        }
        
        // 发票明细的基本信息： 税务名称 规格 单位 数量 单价
        $item_name = $goods['brand_name'];
        $item_model = $goods['goods_name'];
        $uom = '个';
        $quantity = $goods['goods_number'];
        $unit_price = $goods['goods_price'];
                       
        
        
        // 如果是鞋子的商品，税务名称和规格做特殊处理
        if ($goods['goods_party_id'] == PARTY_OUKU_SHOES) {
            if ($goods['top_cat_id'] == 1515) {
                $item_name = '运动鞋';
                $uom = '双';
            } else {
                $item_name = '运动装备';
            }
            $item_model = $goods['sku'];
        }
        
        // 处理串号
        $inventoryItemType = get_goods_item_type($goods['goods_id']);
        if ($inventoryItemType == 'SERIALIZED') {
            $serial_number = $goods['erp_goods_sns'];
        } else {
            $serial_number = '';
        }
        

        // 自动开票时如果有0元商品，在后面加一个 * 件赠品套餐
        // 手动开票的处理在用户选择相关条目提交后处理
        if ($type == 'auto' && $gift_goods_num > 0) {
            $item_model .= (" 套餐(含{$gift_goods_num}件赠品)");
            $gift_goods_num = 0;
        }
        
        $line = array(
            'goods_id' => $goods['goods_id'],
            'style_id' => $goods['style_id'],
            'order_goods_id' => $goods['order_goods_id'],
            'item_name' => $item_name,
            'item_model' => $item_model,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'uom' => $uom,
            'serial_number' => $serial_number,
            'item_type' => 'GOODS',
            'order_id' => $goods['order_id'],
        );
        
        // 手动开票时增加一个标识，记录条目是否已开过发票了，没有数据库映射        
        if ($type == 'manual') {
            $line['printed'] = $goods['printed'];             
        }
        
        // 如果是鞋子的商品就算成主商品了
        if ($goods['goods_party_id'] == PARTY_OUKU_SHOES) {
            $main_lines[] = $line;
        } else {
            if ($goods['top_cat_id'] != 597) {
                $main_lines[] = $line;
            } else {
                $fittings_lines[] = $line;
            }
        }
        
        $goods_amount += ($unit_price * $quantity);
    }
}


/**
 * 获得发票的其他明细
 * 快递等费用必须单独传递过来，原因是手动开票时会计入相关退回等抵扣
 *
 * @param int $order_id 订单id
 * @param float $shipping_fee 快递费用
 * @param float $pack_fee 包装费
 * @param float $bonus 红包
 */
function _get_sales_invoice_other_lines($order_id, $shipping_fee, $pack_fee, $bonus) {
    $order_id = intval($order_id);
    $other_lines = array();
    
    //运费
    if ($shipping_fee > 0) {
        $other_lines[] = array(
            'item_name' => '运费',
            'item_model' => '',            
            'unit_price' => $shipping_fee,
            'quantity' => 1,
            'uom' => '',
            'item_type' => 'FEE',
            'order_id' => $order_id,
        );
    }
    
    //包装费
    if ($pack_fee > 0) {
       $other_lines[] = array(
            'item_name' => '包装费',
            'item_model' => '',
            'unit_price' => $pack_fee,
            'quantity' => 1,
            'uom' => '次',
            'item_type' => 'FEE',
            'order_id' => $order_id,
        );
    }
    
    
    //红包
    if ($bonus < 0) {
        $other_lines[] = array(
            'item_name' => '折扣',
            'item_model' => '',
            'unit_price' => $bonus,
            'quantity' => 1,
            'uom' => '',
            'item_type' => 'DISCOUNT',
            'order_id' => $order_id,
        );
    }   
    
    return $other_lines;
}

/**
 * 根据订单id获得更多发票明细项
 *
 * @param int $order_id
 * @return unknown
 */
function _get_sales_invoice_additional_lines($order_id) {
    $other_lines = array();

    // 获得淘宝支付使用的积分
    require_once("includes/lib_order.php");
    
    $point_fee_array = get_order_attribute($order_id, 'TAOBAO_POINT_FEE');
    $point_fee = floatval($point_fee_array['attr_value']);
    if ($point_fee != 0) {
        // 单位是分，需要除以100
        $point_fee = ($point_fee / 100);
        $point_fee = -1 * abs($point_fee);
    }
    
    // 积分支付
    if ($point_fee < 0) {
        $other_lines[] = array(
            'item_name' => '折扣',
            'item_model' => '',
            'unit_price' => $point_fee,
            'quantity' => 1,
            'uom' => '',
            'item_type' => 'DISCOUNT',  
            'order_id' => $order_id, 
        );
    }
    
    // 支付手续费
    $pay_proxy_fee_array = get_order_attribute($order_id, 'PAY_PROXY_FEE');
    $pay_proxy_fee = floatval($pay_proxy_fee_array['attr_value']);
     
    if ($pay_proxy_fee > 0) {
        $other_lines[] = array(
            'item_name' => '手续费',
            'item_model' => '',
            'unit_price' => $pay_proxy_fee,
            'quantity' => 1,
            'uom' => '元',
            'item_type' => 'FEE',  
            'order_id' => $order_id, 
        );
    }
    return $other_lines;
}





