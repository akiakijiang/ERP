<?php 
/**
 * 采购商品状态导入
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
party_priv(PARTY_OUKU_SHOES);
admin_priv('cg_generate_c_order');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('parse', 'done', 'import')) 
    ? $_REQUEST['act'] 
    : null ;
$info =  // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ; 


// 导入配置   
$config = array(
    array(
        'sku' => 'SKU',
        'size' => 'SIZE',
        'quantity' => 'STOCKQTY',
    ),
);

// 供应商列表
$provider_list = array(
    '156' => '上海跨世',
    '155' => '浙江亦可',
    '163' => '泗洪国威',
);

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {
    
    switch ($act) {
        
        case 'import' :
            @set_time_limit(300);
            
            // 检查供应商
            $provider_id = $_REQUEST['provider_id'];
            if (!$provider_id || !array_key_exists($provider_id, $provider_list)) {
                $smarty->assign('message', '没有选择供应商');
                break;
            }
            
            // 查询已经导入过的供应商库存表
            $sql = "SELECT provider_id FROM provider_inventory_item WHERE executed = 'N' GROUP BY provider_id";
            $imported = $db->getCol($sql);
            if ($imported && in_array($provider_id, $imported)) {
                $smarty->assign('message', '该供应商的库存表已经导入过了');
                break;
            }
            
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
            $ret = excel_read($file->filepath(), $config, $file->extname(), $failed);
            if (!empty($failed)) {
                $smarty->assign('message', reset($failed));
                break;
            }

            /* 检查数据  */
            $rowset = @reset($ret);
            // 订单数据读取失败
            if (empty($rowset)) {
                $smarty->assign('message', 'excel文件中没有数据,请检查文件');
                break;
            }
            
            foreach ($rowset as $row) {
                if (!is_numeric($row['quantity'])) { $row['quantity'] = 0; }
                $row['provider_id'] = $provider_id;
                $row['executed'] = 'N';
                $segment[] = '('. implode(',', array_map(array(& $db, 'qstr'), $row)) .')';
            }
            
            if (!empty($segment)) {
                $sql = "INSERT INTO provider_inventory_item (sku, size, quantity, provider_id, executed) VALUES ". implode(',', $segment);
                $result = $db->query($sql, 'SILENT');
            }
            
            if ($result) {
                $smarty->assign('message', "导入成功, 导入记录：" . count($segment). "条" );
            } else {
                $smarty->assign('message', "数据保存失败，错误原因：" . $db->error());
            }
            
            $file->unlink();
            break;


        /**
         * 更新商品状态
         */            
        case 'done' :
            
            // 查询出供应商库存
            $rowset = $db->getAll("
                SELECT sku, size, SUM(quantity) AS quantity FROM provider_inventory_item 
                WHERE executed = 'N' GROUP BY sku, size
            ");
            if (empty($rowset)) {
                $smarty->assign('message', '没有库存表数据，不能执行更新');
                break;
            }

            // 已导入的供应商
            $imported_provider = $db->getCol("SELECT provider_id FROM provider_inventory_item WHERE executed = 'N' GROUP BY provider_id");
            $missing = array_diff(array_keys($provider_list), $imported_provider);
            if (!empty($missing)) {
                $message = '有部分供应商的库存表未导入，不能执行更新, 未导入库存表的供应商有：';
                foreach ($missing as $pi) {
                    $message .= $provider_list[$pi] .',';
                }
                $smarty->assign('message', $message);
                break;
            }
            
            // 将供应商数据按sku和size分组
            $import_group = array();
            foreach ($rowset as & $row) {
                $import_group[$row['sku']][$row['size']] = $row;
            }

            // 取得已上架鞋子并所有尺寸
            $sql = "
                SELECT
                    g.goods_id, g.goods_name, g.sku, g.sale_status as goods_sale_status, 
                    s.style_id, s.value AS size, gs.goods_style_id, gs.sale_status
                FROM
                    {$ecs->table('goods')} AS g
                    LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = g.goods_id AND gs.sale_status IN ('normal', 'shortage')
                    LEFT JOIN {$ecs->table('style')} AS s ON s.style_id = gs.style_id
                WHERE
                    g.goods_party_id = ". PARTY_OUKU_SHOES . " AND g.is_on_sale = 1";
            $ref_fields = $ref_rowset = array();
            $goods_list = $db->getAllRefby($sql, array('sku', 'goods_id'), $ref_fields, $ref_rowset, false);
            if (!$goods_list) {
                $smarty->assign('message', '我们还没有上架商品');
                break;
            }

            // 数据库执行结果
            $result = true;
            // 更新一个尺寸缺货的sql
            $size_shortage_sql  = "UPDATE {$ecs->table('goods_style')} SET sale_status = 'shortage' WHERE goods_style_id = '%d' LIMIT 1";
            // 更新一个货号缺货的sql
            $goods_shortage_sql = "UPDATE {$ecs->table('goods')} SET last_update = UNIX_TIMESTAMP(), sale_status = 'shortage' WHERE goods_id = '%d' LIMIT 1";
            // 更新一个尺寸在售的sql
            $size_normal_sql  = "UPDATE {$ecs->table('goods_style')} SET sale_status = 'normal' WHERE goods_style_id = '%d' LIMIT 1";
            // 更新一个货号在售的sql
            $goods_normal_sql = "UPDATE {$ecs->table('goods')} SET last_update = UNIX_TIMESTAMP(), sale_status = 'normal' WHERE goods_id = '%d' LIMIT 1";

            $storage_list = getStorage('INV_STTS_AVAILABLE');  // 库存数

            foreach ($ref_rowset['sku'] as $sku => $group) {
                $goods = reset($group);
         
                // 这个货号的所有尺寸是否都缺货                    
                $shortage_sku = true;
                    
                // 货号不在 库存表中
                if (!array_key_exists($sku, $import_group)) {
                    // 判断商品是否还有系统库存
                    foreach ($group as $g) {
                        // 库存
                        $idx = $g['goods_id'] .'_'. $g['style_id']; 
                        $qoh = isset($storage_list[$idx]['qohTotal']) ? $storage_list[$idx]['qohTotal'] : 0 ;
                        if ($qoh < 1) {
                            // 尺寸标为缺货
                            if ($g['sale_status'] == 'normal') {
                                $result = $result && $db->query(sprintf($size_shortage_sql, $g['goods_style_id']));
                                $report['shortage_size_list'][] = $g; 
                            }
                        } else {
                            $shortage_sku = false;
                            
                            // 尺寸标为在售
                            if ($g['sale_status'] == 'shortage') {
                                $result = $result && $db->query(sprintf($size_normal_sql, $g['goods_style_id']));
                            }
                            
                            // 货号标为在售
                            if ($goods['goods_sale_status'] == 'shortage' ) {
                                $result = $result && $db->query(sprintf($goods_normal_sql, $goods['goods_id']));
                                $goods['goods_sale_status'] = 'normal';
                            }
                        }
                    }
                    
                }
                // 库存表中有该货号
                else {
                    // 判断尺寸在库存表中是否存在
                    foreach ($group as $g) {
                        $idx = $g['goods_id'] .'_'. $g['style_id'];
                        // 系统库存 数
                        $qoh = isset($storage_list[$idx]['qohTotal']) ? $storage_list[$idx]['qohTotal'] : 0 ;
                        // 供应商库存数
                        $quantity =  // 如果该商品尺寸不在库存表中，供应商库存数按0计算
                            array_key_exists($g['size'], $import_group[$sku]) ? $import_group[$sku][$g['size']]['quantity'] : 0 ;
                        
                        if ($qoh < 1 && $quantity < 1) {
                            if ($g['sale_status'] == 'normal') {
                                $result = $result && $db->query(sprintf($size_shortage_sql, $g['goods_style_id']));
                                $report['shortage_size_list'][] = $g;
                            }
                        } else {
                            $shortage_sku = false;
                            
                            // 更新尺寸为在售
                            if ($g['sale_status'] == 'shortage') {
                                $result = $result && $db->query(sprintf($size_normal_sql, $g['goods_style_id']));
                                $report['onsale_size_list'][] = $g;
                            }
                            
                            // 更新货号为在售
                            if ($goods['goods_sale_status'] == 'shortage') {
                                $result = $result && $db->query(sprintf($goods_normal_sql, $goods['goods_id']));
                                $goods['goods_sale_status'] = 'normal';
                            }
                        }
                    }
                    
                    // 商品中没有但库存表商品有的尺寸，提示添加
                    $size1 = Helper_Array::getCols($group, 'size');
                    $size2 = Helper_Array::getCols($import_group[$sku], 'size');
                    $diff = array_diff($size2, $size1);
                    if (!empty($diff)) {
                        $diff_size= $goods;
                        $diff_size['errmsg'] = "该货号({$goods['goods_id']}#)缺少这些尺寸，请确定是否要添加：" . implode(', ', $diff);
                        $report['diff_size_list'][] = $diff_size;
                    }
                }
  
                // 该货号所有尺寸都缺货
                if ($shortage_sku && $goods['goods_sale_status'] == 'normal') {
                    $result = $result && $db->query(sprintf($goods_shortage_sql, $goods['goods_id']));
                    $report['shortage_sku_list'][] = $goods;
                }
            }
            
            if ($result) {
                $db->query("UPDATE provider_inventory_item SET executed = 'Y' WHERE executed = 'N'");
                $smarty->assign('message', '商品状态更新完毕, 请查看报告');
            } else {
                $smarty->assign('message', '商品状态更新完毕, 存在错误信息：' . $db->error());
            }
            
            // 更新报告
            $smarty->assign('report', $report);
            break;
    }
}


/**
 * 显示
 */
$smarty->assign('provider_list', $provider_list);
$smarty->display('oukooext/purchase_goods_status_import.htm');

?>
