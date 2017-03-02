<?php
define('IN_ECS', true);
require_once('../includes/init.php');
admin_priv('taobao_goods_upload');
party_priv('65574');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'admin/distribution.inc.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');

$sql = "select application_key from ecshop.taobao_shop_conf where party_id = {$_SESSION['party_id']} and status = 'OK' limit 1";
$application_key = $db->getOne($sql);
if (!$application_key) {
    die('该组织没有淘宝店铺应用');
}

$act = $_REQUEST['act'];
switch  ($act) {
    case 'upload' :
    $config = array(
        'taobao商品' => array(
            'outer_id' => '商家编码', 
            'cid' => '类目',
            'num' => '商品数量',
            'price' => '商品价格',
            'type' => '发布类型',
            'stuff_status' => '新旧程度',
            'title' => '宝贝标题', 
            'desc' => '宝贝描述',
            'location.state' => '省份',
            'location.city' => '城市',
            'approve_status' => '商品状态',
            'props' => '商品属性',
            'color' => '颜色属性ID',
            'color_value' => '颜色属性值',
            'sku_propertie' => 'sku商品属性', 
            'sku_quantitie' => 'sku数量', 
            'sku_price' => 'sku单价', 
            'sku_outer_id' => 'sku商家编码', 
            'property_alias' => '属性值别名',
            'freight_payer' => '运费承担方式',
            'valid_thru' => '有效期', 
            'has_invoice' => '是否有发票',
            'has_warranty' => '是否有保修',
            'postage_id' => '运费模板ID',
            'auction_point' => '商品的积分返点比例', 
            'model' => '型号', 
            'goods_no_id' => '货号ID',
            'goods_no' => '货号',
        )
    );
    
    
    /* 文件上传并读取 */
    @set_time_limit(300);
    $uploader = new Helper_Uploader();
    $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
    
    if (!$uploader->existsFile('excel')) {
        $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
        break;
    }
    
    // 取得要上传的文件句柄
    $file = $uploader->file('excel');
               
    // 检查上传文件
    if (!$file->isValid('xls, xlsx', $max_size)) {
        $smarty->assign('message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
        break;
    }
    
    // 读取excel
    $rowset = excel_read($file->filepath(), $config, $file->extname(), $failed);
    if (!empty($failed)) {
        $smarty->assign('message', reset($failed));
        break;
    }
    
    // 检查数据
    if (empty($rowset) || empty($rowset['taobao商品'])) {
        $smarty->assign('message', 'excel文件中没有数据,请检查文件');
        break;
    }
    
    $goods_list = $rowset['taobao商品'];
    
    // 检查数据中是否有空内容
    $empty_col = false;
    foreach (array_keys($config['taobao商品']) as $val) {
        $in_val = Helper_Array::getCols($goods_list, $val);
        $in_len = count($in_val);
        Helper_Array::removeEmpty($in_val);
        if (empty($in_val) || $in_len > count($in_val)) {
            $empty_col = true;
            $smarty->assign('message', "文件中存在空的{$config['taobao商品'][$val]}，请确保有数据的行都是完整的");
            break;              
        }         
    }            
    if($empty_col) break;
    
    //检查数据的合法性
    //同一个商品，多个sku中，每一行sku的商品信息都应该一样
    $temp = array();
    $is_success = true;
    foreach($goods_list as $goods) {
        if(! is_numeric(trim($goods['outer_id']))) {
            $smarty->assign('message', "商家编码: " . trim($goods['outer_id']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
        
        if(! is_numeric(trim($goods['cid']))) {
            $smarty->assign('message', "类目: " . trim($goods['cid']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
        
        if(! is_numeric(trim($goods['num']))) {
            $smarty->assign('message', "商品数量: " . trim($goods['num']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
            
        if(! is_numeric(trim($goods['price']))) {
            $smarty->assign('message', "商品价格: " . trim($goods['price']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }        
        
        if(trim($goods['type']) != 'fixed' && trim($goods['type']) != 'auction') {
            $smarty->assign('message', "发布类型: " . trim($goods['type']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
            
        if(trim($goods['stuff_status']) != 'new' && trim($goods['stuff_status']) != 'second' && trim($goods['stuff_status']) != 'unused') {
            $smarty->assign('message', "新旧程度: " . trim($goods['stuff_status']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
        
        if(trim($goods['location.state']) != '上海' || trim($goods['location.city']) != '上海') {
            $smarty->assign('message', "省份、城市只能是上海,请检查修改");
            $is_success = false;
            break;
        }     
        
        if(trim($goods['approve_status']) != 'onsale' && trim($goods['approve_status']) != 'instock') {
            $smarty->assign('message', "商品状态: " . trim($goods['approve_status']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
        
        $props = trim($goods['props']);
        $props_ary = preg_split('/;/', $props);
        $props_ary2 = preg_split('/:/', $props);
        if (count($props_ary) + 1 != count($props_ary2)) {
            $smarty->assign('message', "商品属性: " . $props . " 格式不合法,请检查修改");
            $is_success = false;
            break;
        }
        $flag = true;
        foreach ($props_ary as $prop) {
            $p = preg_split('/:/', $prop);
            if(count($p) != 2 || !is_numeric(trim($p[0])) || !is_numeric(trim($p[1]))) {
                $smarty->assign('message', "商品属性: " . $props . " 格式不合法,请检查修改");
                $is_success = false;
                $flag = false; 
                break;
            };
        }
        if (!$flag) break;
        
        if(! is_numeric(trim($goods['color']))) {
            $smarty->assign('message', "颜色属性ID " . trim($goods['color']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
            
        if(! is_numeric(trim($goods['color_value']))) {
            $smarty->assign('message', "颜色属性值 " . trim($goods['color_value']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }        
        $props = trim($goods['sku_propertie']);
        $props_ary = preg_split('/;/', $props);
        $props_ary2 = preg_split('/:/', $props);
        if (count($props_ary) + 1 != count($props_ary2)) {
            $smarty->assign('message', "sku商品属性: " . $props . " 格式不合法,请检查修改");
            $is_success = false;
            break;
        }
        $flag = true;
        foreach ($props_ary as $prop) {
            $p = preg_split('/:/', $prop);
            if(count($p) != 2 || !is_numeric(trim($p[0])) || !is_numeric(trim($p[1]))) {
                $smarty->assign('message', "sku商品属性: " . $props . " 格式不合法,请检查修改");
                $is_success = false;
                $flag = false; 
                break;
            };
        }
        if (!$flag) break;
        
        if(! is_numeric(trim($goods['sku_quantitie']))) {
            $smarty->assign('message', "sku数量: " . trim($goods['sku_quantitie']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
        
        if(! is_numeric(trim($goods['sku_price']))) {
            $smarty->assign('message', "sku单价: " . trim($goods['sku_price']) . " 不是数字,请检查修改");
            $is_success = false;
            break;
        }
        
        $props = trim($goods['sku_outer_id']);
        $props_ary = preg_split('/_/', $props);
        if (count($props_ary) != 2 || !is_numeric(trim($props_ary[0])) || !is_numeric(trim($props_ary[1])) || trim($props_ary[0]) != trim($goods['outer_id'])) {
            $smarty->assign('message', "sku商家编码: " . trim($goods['sku_outer_id']) . " 格式不合法,请检查修改");
            $is_success = false;
            break;
        }
        
        $alias = trim($goods['property_alias']);
        if ($alias != "NONE") {
            $alias_ary = preg_split('/;/', $alias);
            $alias_ary2 = preg_split('/:/', $alias);
            if (count($alias_ary) != count($alias_ary2) / 3 ) {
                $smarty->assign('message', "属性值别名: " . $alias . " 格式不合法,请检查修改");
                $is_success = false;
                break;
            }
            $flag = true;
            foreach ($alias_ary as $alias) {
                $p = preg_split('/:/', $alias);
                if(count($p) != 3) {
                    $smarty->assign('message', "属性值别名: " . $alias . " 格式不合法,请检查修改");
                    $is_success = false;
                    $flag = false; 
                    break;
                };
                
                $propery = trim($p[0]) . ":" . trim($p[1]);
                if (!in_array($propery, preg_split('/;/', trim($goods['props']))) && !in_array($propery, preg_split('/;/', trim($goods['sku_propertie'])))) {
                    $smarty->assign('message', "属性值别名: " . $alias . " 对应的属性不存在");
                    $is_success = false;
                    $flag = false; 
                    break;
                }
            }
            if (!$flag) break;
            
        }
        
        if(trim($goods['freight_payer']) != 'seller' && trim($goods['freight_payer']) != 'buyer') {
            $smarty->assign('message', "运费承担方式: " . trim($goods['freight_payer']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
        
        if(trim($goods['valid_thru']) != '7' && trim($goods['valid_thru']) != '14') {
            $smarty->assign('message', "有效期:  " . trim($goods['valid_thru']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
            
        if(trim($goods['has_invoice']) != 'Y' && trim($goods['has_invoice']) != 'N') {
            $smarty->assign('message', "是否有发票:  " . trim($goods['has_invoice']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
                
        if(trim($goods['has_warranty']) != 'Y' && trim($goods['has_warranty']) != 'N') {
            $smarty->assign('message', "是否有保修:  " . trim($goods['has_warranty']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
            
        if(! is_numeric(trim($goods['postage_id']))) {
            $smarty->assign('message', "运费模板ID:  " . trim($goods['postage_id']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
        
            
        if(! is_numeric(trim($goods['auction_point'])) || trim($goods['auction_point']) <= 0 || trim($goods['auction_point']) > 500) {
            $smarty->assign('message', "商品的积分返点比例:  " . trim($goods['auction_point']) . " 不合法,请检查修改");
            $is_success = false;
            break;
        }
        
        unset($goods['sku_propertie']);
        unset($goods['sku_quantitie']);
        unset($goods['sku_price']);
        unset($goods['sku_outer_id']);
        unset($goods['property_alias']);
        $data = "";
        foreach ($goods as $d) {
            $data .= $d;
        } 
        $temp[] = $data;
    }
    if (!$is_success) break;
    if (count(array_unique(Helper_Array::getCols($goods_list, 'outer_id'))) != count(array_unique($temp))) {
        pp(array_unique(Helper_Array::getCols($goods_list, 'outer_id')));
        pp(array_unique($temp));
        $smarty->assign('message', "文件中存在商品多个sku中的商品信息不等,请仔细检查");
        break;
    }
    
    $goods_list = Helper_Array::groupBy($goods_list, 'outer_id');
    foreach ($goods_list as $sku_list) {
        $quantitie = 0;
        $sku_quantitie = 0;
        $outer_id = $sku_list[0]['outer_id'];
        if (count(Helper_Array::getCols($sku_list, 'sku_propertie')) != count(array_unique(Helper_Array::getCols($sku_list, 'sku_propertie')))) {
            $smarty->assign('message', "商家编码: " . $outer_id . " 的sku商品属性重复了,请仔细检查");
            $is_success = false;
            break;
        }
        foreach ($sku_list as $sku) {
            $quantitie = $sku['num'] ;
            $sku_quantitie += $sku['sku_quantitie'];
        }
        if ($quantitie != $sku_quantitie) {
            $smarty->assign('message', "商家编码: " . $outer_id . " 的sku数量之和不等于商品数量,请仔细检查");
            $is_success = false;
            break;
        }
    }
    if (! $is_success) break;
    

    // excel检查通过，接下来上传淘宝
    $productService = soap_get_client('TaobaoProductService'); 
    $sql = "select ifnull(max(batch_id), 0) + 1 as batch_id, now() as batch_time from ecshop.ecs_taobao_goods_upload_history limit 1 ";
    $batch = $db->getRow($sql);
    $list = array();
    foreach ($goods_list as $key => $sku_list) {
        $props = trim($sku_list[0]['props']) . ";" . trim($sku_list[0]['sku_propertie']);
        $sku_properties = trim($sku_list[0]['color']) . ":" . trim($sku_list[0]['color_value']) . ";" . trim($sku_list[0]['sku_propertie']); 
        $sku_quantities = trim($sku_list[0]['sku_quantitie']); 
        $sku_prices = trim($sku_list[0]['sku_price']);
        $sku_outer_ids = trim($sku_list[0]['sku_outer_id']); 
        $property_aliass = null;
        if (trim($sku_list[0]['property_alias']) != "NONE") {
            $property_aliass = trim($sku_list[0]['property_alias']);
        }
        $idx = 0;
        foreach ($sku_list as $sku) {
            if ($idx > 0) {
                $props .= ";" . $sku['sku_propertie'];
                $sku_properties .= "," . $sku['color'] . ":" . $sku['color_value'] . ";" . $sku['sku_propertie'];
                $sku_quantities .= "," . $sku['sku_quantitie'];
                $sku_prices .= "," . $sku['sku_price'];
                $sku_outer_ids .= "," . $sku['sku_outer_id'];
                if (trim($sku['property_alias']) != "NONE") {
                    if ($property_aliass) {
                        $property_aliass .= ";" . trim($sku['property_alias']);
                    } else {
                        $property_aliass = trim($sku['property_alias']);
                    }
                }
            }
            $idx ++;
        }
        $customer_props = "20000:GYMBOREE/金宝贝";
        $input_pids = "20000";
        $input_str = "GYMBOREE/金宝贝";
        if (trim($sku_list[0]['model']) != "NONE")  {
            $customer_props .= ":20640283:" . trim($sku_list[0]['model']);
            $input_str .=  ";型号;" . trim($sku_list[0]['model']);
        }
        
        if (trim($sku_list[0]['goods_no_id']) != "NONE" && trim($sku_list[0]['goods_no']) != "NONE")  {
            $customer_props .= ";" . trim($sku_list[0]['goods_no_id']) . ":" . trim($sku_list[0]['goods_no']);
            $input_pids .= "," . trim($sku_list[0]['goods_no_id']);
            $input_str .= "," . trim($sku_list[0]['goods_no']);
        } elseif (trim($sku_list[0]['goods_no_id']) == "NONE" && trim($sku_list[0]['goods_no']) != "NONE"){
            $customer_props .= ";25396636:" . trim($sku_list[0]['goods_no']);
            $input_str .= ";货号;" . trim($sku_list[0]['goods_no']);
        }
        

        //判断商家编码是否已有且仅有一个，如果是，调用update，没有则调用add, 如果有多个，则不上传
        $product_id = null;
        $num_iid = null;
        $is_exists = false;
        $is_success = true;
        $error_code = null;
        $error_msg = null;
        $error_method = null;
        $sku_ids = null;
        $repeat=1;
        
        $c = new stdClass();
        $c->outerId = trim($sku_list[0]['outer_id']);
        $c->applicationKey = $application_key;
        

        //根据商家编码查询商品,获取product_id
        do {
            try {
                $result = $productService->getTaobaoItemByOuterId($c);
                if (isset($result) && isset($result->return)) {
                    if (isset($result->return->TaobaoItem) && is_array($result->return->TaobaoItem)) {
                        $is_success =false;
                        $error_method = "通过商家编码获得商品和产品";
                        $error_msg = "一个商家编码存在多个商品";
                        break;
                    } elseif (isset($result->return->TaobaoItem)){
                        $is_exists = true;
                        $is_success = true;;
                        $product_id = $result->return->TaobaoItem->productId;
                        $num_iid = $result->return->TaobaoItem->numIid;
                        break;
                    } elseif (! isset($result->return->TaobaoItem)) {
                        $is_exists = false;
                        $is_success = true;
                        break;
                    } 
                } else {
                    $is_success = false;
                    $error_method = "通过商家编码获得商品和产品";
                    $error_msg = "接口没有返回值";
                    break;
                }
            } catch (Exception $e) {
                $is_success =false;
                $error_method = "通过商家编码获得商品和产品";
                $error_msg = "php调romeo抛异常: " . $e->getMessage();
                break;
            }
            $repeat++;
            sleep(3);
        }while($repeat>0 && $repeat<5);

        
        
        //商家编码查询不到商品时,添加产品
        if (! $is_exists && $is_success) {
            $c = new stdClass();
            $product = array (
                "cid" => trim($sku_list[0]['cid']),
                "props" => $props,
                "customerProps" => $customer_props,
                "outerId" => trim($sku_list[0]['outer_id']),
                "price" => 0.00,
                "imageUrl" => "/var/www/gymboree/image/gymboree.jpg",
                "name" => trim($sku_list[0]['title']),
                "desc" => trim($sku_list[0]['desc']), 
                "isMajor" => "true",
            );
            $c->taobaoProduct = $product;
            $c->applicationKey = $application_key;
            $repeat = 1;
            do {
                try {
                    $result = $productService->uploadTaobaoProduct($c);
                    if (isset($result) && isset($result->return)) {
                        $error_code = $result->return->code;
                        $msg = $result->return->msg;
                        if ($error_code == "1" && $msg == "OK") {
                            $is_success = true;
                            $product_id = $result->return->result->productId;
                            break;
                        } elseif ($error_code == "2") {
                            $is_success = false;
                            $error_method = "新增产品";
                            $error_msg = "romeo exception:" . $msg;
                            break;
                        } else {
                            if ($error_code == "540" && $msg == "Remote service error: 本类目此关键属性的产品已存在, 不可重复添加") {
                                $sql = "select product_id from ecshop.ecs_taobao_goods_upload_history 
                                        where cid = %d and customer_props = '%s' and product_id <> 0 and product_id is not null 
                                        order by batch_time desc limit 1 ";
                                $sql = sprintf($sql, trim($sku_list[0]['cid']), $customer_props);
                                $temp = $db->getOne($sql);
                                if (! empty($temp)) {
                                    $is_success = true;
                                    $product_id = $temp;
                                    break;
                                }
                            }
                            $is_success = false;
                            $error_method = "新增产品";
                            $error_msg = "taobaoApi error_msg:" . $msg ;
                        }
                    } else {
                        $is_success = false;
                        $error_method = "新增产品";
                        $error_msg = "接口没有返回值";
                        break;
                    }
                 } catch (Exception $e) {
                     $is_success =false;
                     $error_method = "新增产品";
                    $error_msg = "php调romeo抛异常: " . $e->getMessage();
                    break;
                }
                if ($error_code != "530") {
                    break;
                }
                sleep(3);
                $repeat++;
            }while($repeat>0 && $repeat<5);
        }
        
        //增加、修改商品
        if ($is_success) {
            $c = new stdClass();
            $c->applicationKey = $application_key;
            $item = array (
                "productId" => $product_id,
                "outerId" => trim($sku_list[0]['outer_id']),
                "cid" => trim($sku_list[0]['cid']),
                "num" => trim($sku_list[0]['num']), 
                "price" => trim($sku_list[0]['price']),
                "title" => trim($sku_list[0]['title']),
                "desc" => trim($sku_list[0]['desc']),
                "stuffStatus" => trim($sku_list[0]['stuff_status']),
                "approveStatus" => trim($sku_list[0]['approve_status']),
                "props" => $props,
                "inputPids" => $input_pids, 
                "inputStr" => $input_str,
                "freightPayer" => trim($sku_list[0]['freight_payer']),
                "validThru" => trim($sku_list[0]['valid_thru']), 
                "hasInvoice" => (trim($sku_list[0]['has_invoice']) == 'Y' ? true : false),
                "hasWarranty" => (trim($sku_list[0]['has_warranty']) == 'Y' ? "true" : "false"),
                "postageId" => trim($sku_list[0]['postage_id']),
                "auctionPoint" => trim($sku_list[0]['auction_point']),
                "location" => array (
                    "state" => trim($sku_list[0]['location.state']),
                    "city" => trim($sku_list[0]['location.city']),
                ),
            );
            if ($property_aliass) {
                $item['propertyAlias'] = $property_aliass;
            }
            $repeat = 1;
            do {
                try {
                    if ($is_exists) {
                        $item['numIid'] = $num_iid; 
                        $c->taobaoItem = $item;
                        $result = $productService->setTaobaoItem3($c);
                    } else {
                        $item['type'] = trim($sku_list[0]['type']);
                        $c->taobaoItem = $item;
                        $result = $productService->uploadTaobaoItem($c);
                    }
                    if (isset($result) && isset($result->return)) {
                        $error_code = $result->return->code;
                        $msg = $result->return->msg;
                        if ($error_code == "1" && $msg == "OK") {
                            $is_success = true;
                            $num_iid = $result->return->result->numIid;
                            break;
                        } elseif ($error_code == "2") {
                            $is_success = false;
                            $error_method = ($is_exists ? "修改商品" : "新增商品");
                            $error_msg = "romeo exception:" . $msg;
                            break;
                        } else {
                            $is_success = false;
                            $error_method = ($is_exists ? "修改商品" : "新增商品");
                            $error_msg = "taobaoApi :" . $msg ;
                        }
                    } else {
                        $is_success = false;
                        $error_method = ($is_exists ? "修改商品" : "新增商品");
                        $error_msg = "接口没有返回值";
                        break;
                    }
                 } catch (Exception $e) {
                     $is_success =false;
                     $error_method = ($is_exists ? "修改商品" : "新增商品");
                    $error_msg = "php调romeo抛异常: " . $e->getMessage();
                    break;
                }
                if ($error_code != "530") {
                    break;
                }
                $repeat++;
                sleep(3);
            }while($repeat>0 && $repeat<5);
        }
        
        //添加sku
        if ($is_success && !$is_exists && $num_iid) {
            $sku_properties_ary = preg_split("/,/", $sku_properties);
            $sku_outer_ids_ary = preg_split("/,/", $sku_outer_ids);
            $sku_prices_ary = preg_split("/,/", $sku_prices);
            $sku_quantities_ary = preg_split("/,/", $sku_quantities);
            foreach ($sku_outer_ids_ary as $index => $outer_id) {
                $repeat = 1;
                do {
                    try {
                        $c = new stdClass();
                            $sku_req = array (
                                "numIid" => $num_iid, 
                                "outerId" => $outer_id,
                                "properties" => $sku_properties_ary[$index], 
                                "price" => $sku_prices_ary[$index],
                                "quantity" => $sku_quantities_ary[$index]
                            );
                        $c->taobaoSku = $sku_req;
                        $c->applicationKey = $application_key;
                        
                        $result = $productService->setTaobaoSku3($c);
                        if (isset($result) && isset($result->return)) {
                            $error_code = $result->return->code;
                            $msg = $result->return->msg;
                            if ($error_code == "1" && $msg == "OK") {
                                $is_success = true;
                                if (empty($sku_ids)) {
                                    $sku_ids = $result->return->result->skuId; 
                                } else {
                                    $sku_ids .= "," . $result->return->result->skuId;
                                }
                                break;
                            } elseif ($error_code == "2") {
                                $is_success = false;
                                $error_method = "添加sku";
                                $error_msg = "romeo exception:" . $msg;
                                break;
                            } else {
                                $is_success = false;
                                $error_method = "添加sku";
                                $error_msg = "taobaoApi error_msg:" . $msg ;
                            }
                        } else {
                            $is_success = false;
                            $error_method = "添加sku";
                            $error_msg = "接口没有返回值";
                        }
                    } catch(Exception $e) {
                        $is_success =false;
                         $error_method = "添加sku";
                        $error_msg = "php调romeo抛异常: " . $e->getMessage();
                        break;
                    }
                if ($error_code != "530" && $error_msg != "接口没有返回值") {
                    break;
                }
                $repeat++;
                sleep(3);
                }while($repeat>0 && $repeat<5);
                if (! $is_success) {
                    break;
                }
            }
        }
        
        //记录上传历史
        $error_msg = str_replace('\'', '', $error_msg);
        $error_msg = str_replace('\"', '', $error_msg);
        if (empty($num_iid)) $num_iid = 0;
        $sql = "insert into ecshop.ecs_taobao_goods_upload_history (
                    batch_id, batch_time, party_id, goods_no, outer_id, cid, product_id, num_iid, 
                    num, price, type, stuff_status, title, 
                    `desc`, location_state, location_city, approve_status, props,
                    input_pids, input_str, property_alias, freight_payer, valid_thru, has_invoice,
                    has_warranty, postage_id, auction_point, sku_properties, sku_quantities, sku_ids,
                    sku_prices, sku_outer_ids, customer_props, user_name, is_success,
                    is_exists, error_code, error_method, error_msg, created_stamp, last_update_stamp)
                values (
                    %d, '%s', '%s', '%s', %d, %d, %d, %s,
                    %d, %d, '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', %d, '%s',
                    '%s', %d, '%s', '%s', '%s', '%s', 
                    '%s', '%s', '%s', '%s', '%s', 
                    '%s', ";
        $sql = sprintf($sql, $batch['batch_id'], $batch['batch_time'], $_SESSION['party_id'], trim($sku_list[0]['goods_no']), trim($sku_list[0]['outer_id']), trim($sku_list[0]['cid']), $product_id, $num_iid, 
                        trim($sku_list[0]['num']), trim($sku_list[0]['price']), trim($sku_list[0]['type']), trim($sku_list[0]['stuff_status']), trim($sku_list[0]['title']), 
                        trim($sku_list[0]['desc']), trim($sku_list[0]['location.state']), trim($sku_list[0]['location.city']), trim($sku_list[0]['approve_status']), $props,
                        $input_pids, $input_str, $property_aliass, trim($sku_list[0]['freight_payer']), trim($sku_list[0]['valid_thru']), trim($sku_list[0]['has_invoice']),
                        trim($sku_list[0]['has_warranty']), trim($sku_list[0]['postage_id']), trim($sku_list[0]['auction_point']), $sku_properties, $sku_quantities, $sku_ids,
                        $sku_prices, $sku_outer_ids, $customer_props, $_SESSION['admin_name'], ($is_success ? 'Y' : 'N'), ($is_exists ? 'Y' : 'N'));
        $sql = $sql . ($is_success ? 'null' : ("'" . $error_code . "'")) . ", " . ($is_success ? 'null' : ("'" . $error_method . "'")) . ", " . ($is_success ? 'null' : ("'" . $error_msg . "'")) . ", now(), now());";
        try {
            $db->query($sql);
        } catch(Exception $e) {
            QLog::log("数据库记录上传历史异常: " . $e->getMessage());
            QLog::log("异常SQL: " . $sql);
        }
    }
    $sql = "select goods_no, outer_id, num_iid, is_exists, is_success, error_code, error_method, error_msg 
            from ecshop.ecs_taobao_goods_upload_history
            where batch_id = '{$batch['batch_id']}'";
    $list = $db->getAll($sql);
    $smarty->assign('list', $list);
}

$smarty->display('taobao/taobao_goods_upload.htm');

