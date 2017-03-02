<?php
require_once('config.vars.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'admin/includes/lib_goods.php');

define('ZHAIJISONG_COD',11 );
define('EMS_COD',36 );
define('EMS', 47);
define('YUANTONG',85);
define('SHUNFENG', 44);
define('HUITONG', 99);
define('YUNDA', 100);
define('SHENTONG_COD', 102);
define('EYOUBAO', 107);
define('ZHAIJISONG', 12);
define('SHENTONG', 89);
define('ZHONGTONG', 115);
define('EMS_LAND', 118);
define('SHUNFENG_LAND', 117);
define('XIAOBAO', 119);
define('TIANTIAN',123);


/**
 * 通过user_id得到user
 *
 * @param unknown_type $user_id
 */
function getUserById($user_id) {
    global $db, $ecs;
    $sql = "
        SELECT * FROM {$ecs->table('users')} WHERE user_id = '$user_id';
    ";
    return $db->getRow($sql);
}

/**
 * ONLY USED IN `admin/includes/rpc/shopapi/OrderServiceClient.php`
 * @param  [type] $bill_id [description]
 * @return [type]          [description]
 */
function getCarrierBillById($bill_id) {
    global $db, $ecs;
    $sql = "
        SELECT * FROM {$ecs->table('carrier_bill')} WHERE bill_id = '{$bill_id}';
    ";
    return $db->getRow($sql);
}

function getCarrierById($carrier_id) {
    global $db, $ecs;
    $sql = "
        SELECT * FROM {$ecs->table('carrier')} WHERE carrier_id = '{$carrier_id}';
    ";
    return $db->getRow($sql);
}

function getCarrierName($carrier_id) {
    global $db, $ecs;
    $sql = "
        SELECT name FROM {$ecs->table('carrier')} WHERE carrier_id = '{$carrier_id}';
    ";
    $carrier_name = $db->getOne($sql);
    return $carrier_name;
}

function getActionListByOrderId($order_id, $condition = '') {
    global $db, $ecs;
    $sql = "
        SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order_id}' {$condition} ORDER BY action_id
    ";
    return $db->getAll($sql);
}

/**
 * get order goods
 * @author Zandy<yzhang@oukoo.com>
 * @param mixed $order_id empty or int
 * @param mixed $order_sn empty or string
 * @return array or false
 */
function getOrderGoods($order_id = '', $order_sn = '')
{
    global $db, $ecs;
    ///增加读取goods的top_cat_id 和 cat_id added by zwsun 2009年7月9日12:12:43
    if (!empty($order_id))
    {
        $sql = "select og.*,  g.top_cat_id, g.cat_id, og.goods_number * og.goods_price as total_price from ".$ecs->table('order_goods')." og
                LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
                where og.order_id = '$order_id' ";
    }
    elseif (!empty($order_sn))
    {
        $sql = "select b.*, g.top_cat_id, g.cat_id, b.goods_number * b.goods_price as total_price from ".$ecs->table('order_info')." a
                left join ".$ecs->table('order_goods')." b on a.order_id = b.order_id
                left join {$ecs->table('goods')} g on b.goods_id = g.goods_id
                where a.order_sn = '$order_sn' ";
    }
    else
    {
        return false;
    }
    $getAll = $db->getAll($sql);
    return $getAll;
}

function get_order_status($status_num){
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['order_status'])) {
        return $_CFG['adminvars']['order_status'][$status_num];
    }
    return '';
}

function get_pay_status($status_num) {
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['pay_status'])) {
        return $_CFG['adminvars']['pay_status'][$status_num];
    }
    return '';
}

function get_shipping_status($status_num) {
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['shipping_status'])) {
        return $_CFG['adminvars']['shipping_status'][$status_num];
    }
    return '';
}

function get_invoice_status($status_num) {
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['invoice_status'])) {
        return $_CFG['adminvars']['invoice_status'][$status_num];
    }
    return '';
}

function get_goods_status($status_num) {
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['goods_status'])) {
        return $_CFG['adminvars']['goods_status'][$status_num];
    }
    return '';
}

function get_provider_upload_status($status_num) {
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['provider_upload_status'])) {
        return $_CFG['adminvars']['provider_upload_status'][$status_num];
    }
    return '';
}

function get_provider_type($type_num) {
    global $_CFG;
    if (array_key_exists($type_num, $_CFG['adminvars']['provider_type'])) {
        return $_CFG['adminvars']['provider_type'][$type_num];
    }
    return '';
}

function get_provider_upload_type($type_num) {
    global $_CFG;
    if (array_key_exists($type_num, $_CFG['adminvars']['provider_upload_type'])) {
        return $_CFG['adminvars']['provider_upload_type'][$type_num];
    }
    return '';
}

/**
 * 商品销售状态
 *
 * @param string $status_num
 * @return string
 */
function get_goods_sale_status($status_num){
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['goods_sale_status'])) {
        return $_CFG['adminvars']['goods_sale_status'][$status_num];
    }
    return '';
}
/**
 * 商品库存状态
 *
 * @param string $status_num
 * @return string
 */
function get_goods_storage_status($status_num){
    global $_CFG;
    if (array_key_exists($status_num, $_CFG['adminvars']['goods_storage_status'])) {
        return $_CFG['adminvars']['goods_storage_status'][$status_num];
    }
    return '';
}

function get_condition_provider($has_where) {
    $result = "";
    $provider_name = $_REQUEST["provider_name"];
    $provider_code = $_REQUEST["provider_code"];
    $contact_person = $_REQUEST["contact_person"];
    $provider_type = $_REQUEST["provider_type"];
    $brand = $_REQUEST["brand"];
    $cat_name = $_REQUEST["cat_name"];

    if ($provider_code !== null && $provider_code !== "") {
        if (!$has_where) {
            $result .= " where provider_code = '$provider_code'";
            $has_where = true;
        } else {
            $result .= " and provider_code = '$provider_code'";
        }
    }
    if ($contact_person !== null && $contact_person !== "") {
        if (!$has_where) {
            $result .= " where contact_person = '$contact_person'";
            $has_where = true;
        } else {
            $result .= " and contact_person = '$contact_person'";
        }
    }
    if ($provider_name !== null && $provider_name !== "") {
        if (!$has_where) {
            $result .= " where provider_name like '%$provider_name%'";
            $has_where = true;
        } else {
            $result .= " and provider_name like '%$provider_name%'";
        }
    }

    if ($provider_type !== null && $provider_type !== "" && $provider_type != -1) {
        if (!$has_where) {
            $result .= " where provider_type = $provider_type";
            $has_where = true;
        } else {
            $result .= " and provider_type = $provider_type";
        }
    }

    if ($brand !== null && $brand !== "") {
        if (!$has_where) {
            $result .= " where brand = '$brand'";
            $has_where = true;
        } else {
            $result .= " and brand = '$brand'";
        }
    }

    if ($cat_name !== null && $cat_name !== "" && $cat_name != -1) {
        if (!$has_where) {
            $result .= " where exists (select cat_id from ecs_provider_category where ecs_provider_category.provider_id = ecs_provider.provider_id and cat_id in ($cat_name))";
            $has_where = true;
        } else {
            $result .= " and exists (select cat_id from ecs_provider_category where ecs_provider_category.provider_id = ecs_provider.provider_id and cat_id in ($cat_name))";
        }
    }


    return $result;
}

function get_upload_condition() {
    $result = "";
    $file_name = $_REQUEST["file_name"];
    $type = $_REQUEST["type"];
    $start_date = $_REQUEST["start_date"];
    $end_date = $_REQUEST["end_date"];

    if ($type !== null && $type !== "" && $type != -1) {
        if (!$has_where) {
            $result .= " where type = $type";
            $has_where = true;
        } else {
            $result .= " and type = $type";
        }
    }
    if ($file_name !== null && $file_name !== "") {
        if (!$has_where) {
            $result .= " where file_name = '$file_name'";
            $has_where = true;
        } else {
            $result .= " and file_name = '$file_name'";
        }
    }

    if ($start_date !== null && $start_date !== "") {
        if (!$has_where) {
            $result .= " where operate_datetime >= '$start_date'";
            $has_where = true;
        } else {
            $result .= " and operate_datetime >= '$start_date'";
        }
    }
    if ($end_date !== null && $end_date !== "") {
        if (!$has_where) {
            $result .= " where operate_datetime <= '$end_date'";
            $has_where = true;
        } else {
            $result .= " and operate_datetime <= '$end_date'";
        }
    }
    return $result;
}


/**
 * service action log
 *
 * @author : zwsun
 * @param  : array $info
 * @return  : array log id
 */
function serviceActionLog($info) {
    global $ecs, $db;

    if (!is_array($info)) {
        return 0;
    }

    if (empty($info['order_sn']) && empty($info['order_id'])) {
        return 0;
    }
    if ($info['order_sn']) {
        $order_id = $db->getOne(" SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '{$info['order_sn']}' ");
        if ($order_id) {
            $info['order_id'] = $order_id;
        }
    } elseif ($info['order_id']) {
        $order_sn = $db->getOne(" SELECT order_sn FROM {$ecs->table('order_info')} WHERE order_id = '{$info['order_id']}' ");
        if ($order_sn) {
            $info['order_sn'] = $order_sn;
        }
    }

    if ((!$info['order_id']) || (!$info['order_sn'])) {
        return 0;
    }

    $set = array();
    $info['action_user'] = $_SESSION['admin_name'];
    $info['action_time'] = date("Y-m-d H:i:s");
    foreach ($info as $k => $v) {
        if ( in_array($k, array('order_id', 'order_sn', 'order_goods_id', 'type', 'status', 'status_desc', 'action_note', 'action_user', 'action_time')) ) {
            $set[] = "$k = '$v'";
        }
    }

    $set = join(", ", $set);
    $sql_i = "INSERT INTO ".$ecs->table('service_action')." SET $set ";
    $db->query($sql_i);
    $action_id = $db->insert_id();
    return $action_id;

}

/**
 * by Zandy
 * start
 */

/**
 * order action log
 *
 * @author : Zandy
 * @param  : array $info
 * @return  : array log id
 */
function orderActionLog($info) {
    global $ecs, $db;
    $set = array();
    if (!is_array($info)) {
        return false;
    }
    if(empty($info['action_user'])){
        $info['action_user'] = $_SESSION['admin_name'];
    }
    $info['action_time'] = date("Y-m-d H:i:s");
    foreach ($info as $k => $v) {
        $set[] = "$k = '$v'";
    }
    $set = join(", ", $set);
    $sql_i = "INSERT INTO ".$ecs->table('order_action')." SET $set ";
    $db->query($sql_i);
    $action_id = $db->insert_id();
    //    return $sql_id;
    return $action_id;
}
/**
 * order goods action log
 *
 * @author : Zandy
 * @param  : array $info
 * @return  : array log id
 */
function orderGoodsActionLog($info) {
    global $ecs, $db, $action_time, $action_user;
    $set = array();
    if (!is_array($info)) {
        return false;
    }
    // {{{
    $order_id = $info['order_id'];
    $goods_id = $info['goods_id'];
    $query = "SELECT rec_id FROM ".$ecs->table('order_goods')." WHERE order_id = '$order_id' AND goods_id = '$goods_id'";
    $rec_id = $db->getOne($query);
    #v($info, $query, $rec_id);
    if (!$rec_id) {
        return false;
    }
    $info['order_goods_id'] = $rec_id;
    unset($info['order_id'], $info['goods_id']);
    // }}}
    $info['action_user'] = $action_user;
    $info['action_time'] = $action_time;
    foreach ($info as $k => $v) {
        $set[] = "$k = '$v'";
    }
    $set = join(", ", $set);
    $sql_i = "INSERT INTO ".$ecs->table('order_goods_action')." SET $set ";
    $db->query($sql_i);
    $action_id = $db->insert_id();
    return $action_id;
}

/**
 * 根据供应商id获取供应商名
 *
 * @param int $provider_id 供应商id
 * @return string 供应商名
 */
function get_provider_name($provider_id) {
    global $db, $ecs;

    $sql = "SELECT provider_name FROM {$ecs->table('provider')} p WHERE p.provider_id = '{$provider_id}'";
    $provider_name = $db->getOne($sql);

    return $provider_name;
}

/**
 * 根据供应商id获取供应商代码
 *
 * @param int $provider_id 供应商id
 * @return string 供应商代码
 */
function get_provider_code($provider_id) {
    global $db, $ecs;

    $sql = "SELECT provider_code FROM {$ecs->table('provider')} p WHERE p.provider_id = '{$provider_id}'";
    $provider_code = $db->getOne($sql);

    return $provider_code;
}

/**
 * 根据供应商名字获取供应商id
 *
 * @param string $provider_name 供应商名
 * @return string 供应商id
 */
function get_provider_id_by_name($provider_name) {
    global $db, $ecs;
    $sql = "SELECT provider_id FROM {$ecs->table('provider')} WHERE provider_name = '{$provider_name}' and provider_status = 1";
    $provider_id = $db->getOne($sql);

    return $provider_id;
}

/**
 * 根据仓库名字获取id
 *
 * @param string $facility_name 仓库名
 * @return string 仓库id
 */
function get_facility_id_by_name($facility_name) {
    global $db;
    $sql = "SELECT facility_id FROM romeo.facility WHERE facility_name = '{$facility_name}' and is_closed = 'N' ";
    $facility_id = $db->getOne($sql);

    return $facility_id;
}

/**
 * 金宝贝仓库
 */
function get_gymboree_warehouse_id($facility_name){
    global $db;
    $facility_name = trim($facility_name);
    $sql = "
        select fchrWarehouseID
        from ecshop.brand_gymboree_warehouse
        where fchrWhName = '{$facility_name}'
    ";
    $result = $db -> getOne($sql);
    return $result;
}

/**
 * get provider by order_id or order_sn
 *
 * @author : Zandy
 * @param : int $s
 * @return : string providers as tring
 */
function getProvider($order_id) {
    if (!preg_match("/[\d]+/", $order_id)) {
        return false;
    }
    global $db, $ecs;
    $sql = "SELECT * FROM ".$ecs->table('order_goods')." a
        LEFT JOIN ".$ecs->table('goods')." b ON b.goods_id = a.goods_id
        LEFT JOIN ".$ecs->table('provider')." c ON c.provider_id = b.provider_id
        WHERE 1 ";
    $sqladd = "AND a.order_id = '$order_id'";
    $sql .= $sqladd;
    $getAll = $db->getAll($sql);
    $r = array();
    foreach ($getAll as $k => $v) {
        $r[] = $v['provider_name'];
    }
    return join(", ", $r);
}
/**
 * get provider by order_id or order_sn
 *
 * @author : Zandy
 * @param : int $s
 * @return : string providers as tring
 */
function getUsernameById($user_id) {
    if (!preg_match("/[\d]+/", $user_id)) {
        return false;
    }
    global $db, $ecs;
    $sql = "SELECT * FROM ".$ecs->table('users')."
        WHERE 1 ";
    $sqladd = "AND user_id = '$user_id'";
    $sql .= $sqladd;
    $getRow = $db->getRow($sql);
    return $getRow['user_name'];
}
/**
 * get provider by order_id or order_sn
 *
 * @author : Zandy
 * @param : int $s
 * @return : string providers as tring
 */
function getGoodsStatusByOrderId($order_id) {
    if (!preg_match("/[\d]+/", $order_id)) {
        return false;
    }
    global $db, $ecs;
    $sql = "SELECT * FROM ".$ecs->table('order_goods')." a
        WHERE 1 ";
    $sqladd = "AND a.order_id = '$order_id'";
    $sql .= $sqladd;
    $getAll = $db->getAll($sql);
    $r = array();
    foreach ($getAll as $k => $v) {
        $v['goods_status'] > 0 && $r[] = $v['goods_status'];
    }
    return $r;
}

function getOrderType($order_id) {
    global $db, $ecs;
    $sql = "SELECT (select count(*) from {$ecs->table('order_info')} as oi where oi.parent_order_id = a.order_id) AS sub_count, biaoju_store_id, parent_order_id FROM {$ecs->table('order_info')} AS a WHERE a.order_id = '$order_id'";
    $order = $db->getRow($sql);
    if ($order['sub_count'] == 0 && $order['biaoju_store_id'] == 0)
    return 'b2c';
    elseif ($order['sub_count'] == 0 && $order['biaoju_store_id'] > 0)
    return 'c2c';
    elseif ($order['sub_count'] > 0)
    return 'parent';

    return null;
}
/**
 * sql safe filter
 *
 * @author : Zandy
 * @param : string $s
 * @return : string
 */
function sqlSafe($s) {
    $s = trim($s);
    $s = htmlspecialchars($s);
    $s = str_replace("'", "&#039;", $s);
    $s = str_replace("'", "&#039;", $s);
    return $s;
}
/**
 * 无货等待到无货，超过 7 天自动触发
 */
function wuhuodengdaidaowuhuo($secs = 604800) {
    global $db, $ecs;

    $action_user = $_SESSION['admin_name'];
    $action_time = date("Y-m-d H:i:s");
    $action_note = '系统自动触发，订单商品状态从无货等待到无货';

    $query = "SELECT * FROM ".$ecs->table('order_info')." a
        LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id
        WHERE a.order_status = 6 AND a.pay_status in (0, 2) AND a.shipping_status in (0, 4) AND a.invoice_status = 3
            AND b.goods_status = 22 ";
    $getAll = $db->getAll($query);
    foreach ($getAll as $k => $v) {
        $query2 = "SELECT * FROM ".$ecs->table('order_goods_action')." WHERE order_goods_id = '".$v['rec_id']."' AND goods_status = 22 ORDER BY action_id DESC limit 1 ";
        $getRow = $db->getRow($query2);
        if ($getRow['action_time'] == '0000-00-00 00:00:00' || empty($getRow['action_time'])) {
            continue;
        }
        $do_action_time = strtotime($getRow['action_time']);
        if (strtotime($action_time) - $do_action_time >= $secs) {
            $db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '21' WHERE rec_id = '".$v['rec_id']."' LIMIT 1 ");
            $_info = array('goods_id' => $v['goods_id'], 'order_id' => $v['order_id'], 'goods_status' => 21, 'action_note' => $action_note);
            $aid = orderGoodsActionLog($_info);
            #p($action_time, $getRow['action_time'], $secs);
        }
    }
}

/**
 * 生成发货单号
 */
function genInvoiceNO() {
    return "F".date("ymdHis");
}

/**
 * 根据 order_sn 取得 order_id
 *
 * @param int $order_sn
 * @return int  order_id
 */
function getOrderIdBySN($order_sn) {
    global $db, $ecs;

    $sql1 = "select order_id from " . $ecs->table('order_info') . " WHERE order_sn = '$order_sn'";
    $getRow = $db->getRow($sql1);
    $order_id = intval($getRow['order_id']);
    return $order_id;
}

/**
 * 根据 taobao_order_sn 取得 order_id
 *
 * @param int $taobao_order_sn
 * @return int  order_id
 */
function getOrderIdByTaobaoSN($taobao_order_sn) {
    global $db, $ecs;

    $sql1 = "select order_id from " . $ecs->table('order_info') . " WHERE taobao_order_sn = '$taobao_order_sn'";
    $getRow = $db->getRow($sql1);
    $order_id = intval($getRow['order_id']);
    return $order_id;
}

/**
 * @author : Zandy
 * 供应商编辑
 */
function provider_category_mod($category) {
    global $_CFG;
    $provider_category = array();
    foreach ($_CFG['adminvars']['provider_category'] as $k => $v) {
        $provider_category[$k]['name'] = $v;
    }
    foreach ($provider_category as $k => $v) {
        if (in_array($k, $category)) {
            $provider_category[$k]['checked'] = true;
        }
    }
    $s = '';
    foreach ($provider_category as $k => $v) {
        $s .= '<span class="checkb"><input type="checkbox" name="provider_category[]" value="'.$k.'"'.($v['checked'] ? ' checked="true"' : '').' />'.$v['name'].'</span>';
    }
    return $s;
}

/**
 * @author : Zandy
 * 供应商添加
 */
function provider_category_add($style) {
    global $_CFG;
    foreach ($_CFG['adminvars']['provider_category'] as $k => $v) {
        $s .= '<span class="'.$style.'"><input type="checkbox" name="provider_category[]" value="'.$k.'" />'.$v.'</span>';
    }
    return $s;
}

/**
 * @author : Zandy
 * 供应商编辑
 */
function provider_type_mod($type) {
    global $_CFG;
    $$provider_type = array();
    foreach ($_CFG['adminvars']['provider_type'] as $k => $v) {
        $provider_type[$k]['name'] = $v;
    }
    foreach ($provider_type as $k => $v) {
        if ($k == $type) {
            $provider_type[$k]['selected'] = true;
        }
    }
    $s = '';
    foreach ($provider_type as $k => $v) {
        $s .= '<option value="'.$k.'"'.($v['selected'] ? ' selected' : '').'>'.$v['name'].'</option>';
    }
    return $s;
}

/**
 * @author : Zandy
 * 供应商添加
 */
function provider_type_add() {
    global $_CFG;
    foreach ($_CFG['adminvars']['provider_type'] as $k => $v) {
        $s .= '<option value="'.$k.'">'.$v.'</option>';
    }
    return $s;
}

/**
 * pagination
 *
 * @author : Zandy
 * @param  : int $total
 * @param  : int $offset
 * @param  : int $page
 * @param  : int $url
 * @param  : int $back
 * @param  : int $label
 * @return  : string $string
 */
function Pager($total, $offset = 9, $page = null, $url = null, $back = 3, $label = 'page'){
    // lianxiwoo@hotmail | gmail | sohu | 163.com
    $page = null == $page ? 1 : $page;
    $page = $page > 1 ? $page : (int) @$_GET[$label];
    $page = $page < 1 ? 1 : $page;

    $pages = ceil($total/$offset);
    $pages = $pages > 0 ? $pages : 1;

    $page = $page > $pages ? $pages : $page;

    $url = null == $url ? $_SERVER['REQUEST_URI'] : $url;
    $url = preg_replace("/([?|&])$label\=[0-9]*/", "\\1", $url);
    $url = str_replace(array("&&", "?&"), array('&', '?'), $url);

    $url .= strstr($url, '?')
    ? (substr($url, -1) == '?' ? '' : (substr($url, -1) == '&' ? '' : '&'))
    : '?';

    $ppp = '';
    #$ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">&#171</a> ';
    $ppp .= '<ul class="page"><li style="display:inline-block;"><a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">首页</a></li>';
    //$ppp .= '<a href="'.$url.$label.'=1" target="" title="Last Page 1">'.(1==$page ? '<b>[1]</b>' : '[1]').'</a>';
    if ($pages <= ($back*2 + 1))
    {
        for ($i=1; $i<=$pages; $i++)
        {
            //            $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'"'.($page==$i ? ' style="font-weight: bold; color: #FF00FF;"' : '').' class="Pager">['.$i.']</a>';
            if ($page == $i) {
                $ppp .= '<li style="display:inline-block;"><a class="Pager currentPage">'.$i.'</a></li>';
            } else {
                $ppp .= '<li style="display:inline-block;"><a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">'.$i.'</a></li>';
            }
        }
    }else{
        $b = $back + 2;
        if ($page <= $b)
        {
            $fromfrom = 1;
            $toto = $back * 2 + 1;
        }elseif ($page > $pages - $b){
            $c = $back*2;
            $fromfrom = $pages - $c;
            $toto = $pages;
        }else{
            $fromfrom = $page - $back;
            $toto = $page + $back;
        }
        for ($i=$fromfrom; $i<=$toto; $i++)
        {
            //            $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'"'.($page==$i ? ' style="font-weight: bold; color: #FF00FF;"' : '').' class="Pager">['.$i.']</a>';
            if ($page == $i) {
                $ppp .='<li style="display:inline-block;"><a class="Pager currentPage">'.$i.'</li></a>';
            } else {
                $ppp .= '<li style="display:inline-block;"><a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">'.$i.'</a></li>';
            }
        }
    }
    //$ppp .= '<a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'">'.($pages==$page ? '<b>['.$page.']</b>' : '['.$pages.']').'</a>';
    #$ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">&#187</a>';
    $ppp .= '<li style="display:inline-block;"><a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">尾页</a></li>';
    $ppp .= '<li style="display:inline-block;"><span class="page">第<input type="text" class="pagerInput" name="page" value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){location.href=\''.$url.$label.'=\' + this.value;}else{this.value = '.$page.';}" title=" 跳转 ">页<span></li>';
    $ppp .= '<li style="display:inline-block;"><span class="page">页数/记录数 :  '.$pages.'/'.$total.'</span></li></ul>';
    return $ppp;
}

/**
 * pagination
 *
 * @author : ytchen 2015.11.26 新版，其他参数通过Onclick="Pager(i)"传递
 * @param  : int $total
 * @param  : int $offset
 * @param  : int $page
 * @param  : int $back
 */
function pager_post_parameter($total, $offset = 9, $page = null, $back = 3){
    $page = null == $page ? 1 : $page;
    $page = $page > 1 ? $page : 1;
    $page = $page < 1 ? 1 : $page;

    $pages = ceil($total/$offset);
    $pages = $pages > 0 ? $pages : 1;

    $page = $page > $pages ? $pages : $page;

    $ppp = '';
    $ppp .= '<a href="#" target="" title="First Page 1" Onclick="Pager(1)">[首页]</a> ';
    if ($pages <= ($back*2 + 1))
    {
        for ($i=1; $i<=$pages; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= '<a href="#" target="" title="Page '.$i.'" Onclick="Pager('.$i.')">['.$i.']</a>';
            }
        }
    }else{
        $b = $back + 2;
        if ($page <= $b)
        {
            $fromfrom = 1;
            $toto = $back * 2 + 1;
        }elseif ($page > $pages - $b){
            $c = $back*2;
            $fromfrom = $pages - $c;
            $toto = $pages;
        }else{
            $fromfrom = $page - $back;
            $toto = $page + $back;
        }
        for ($i=$fromfrom; $i<=$toto; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= ' <a href="#" target="" title="Page '.$i.'" Onclick="Pager('.$i.')">['.$i.']</a> ';
            }
        }
    }
    $ppp .= ' <a href="#" target="" title="Last Page '.$pages.'" Onclick="Pager('.$pages.')">[尾页]</a>';
    $ppp .= ' <input type="text" class="pagerInput" name="page"  value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){Pager(this.value)}" title=" 跳转 ">';
    $ppp .= ' ( 页数/记录数 :  '.$pages.'/'.$total.')';
    return $ppp;
}

/**
 * 读取供应商列表
 *
 * @param object $db 数据库连接对象
 * @return array 供应商列表
 * by ychen at 2007/1/22
 */
function getCarriers() {
    global $db, $ecs;

    // 返回启用的承运商
    $carrier_sql =
        " SELECT * FROM {$ecs->table('carrier')} AS c
        INNER JOIN {$ecs->table('shipping')} AS s ON s.default_carrier_id = c.carrier_id
        WHERE s.enabled = 1";
    $carrier_res = $db->query($carrier_sql);
    $carriers = Array();
    while ($row = $db->fetchRow($carrier_res)) {
        if (!party_check($row['party_id'], $_SESSION['party_id'])) {
            continue;
        }
        $carriers[$row['carrier_id']] = $row;
    }
    return $carriers;
}

function getProviders() {
    global $db, $ecs;
    $sql = "SELECT * FROM {$ecs->table('provider')} WHERE provider_status = 1 ORDER BY `order` DESC, provider_id";
    $rs = $db->query($sql);
    $providers = Array();
    while ($row = $db->fetchRow($rs))
    {
        $providers[$row['provider_id']] = $row;
    }
    return $providers;
}


/**
 * 取得 金宝贝入库通知单ID,仓库名字 ljzhou 2013.04.11
 *
 * @return array
 */
function getInoutVouchIdNames() {
    global $db, $ecs;
    $sql = "
        select bgi.fchrInOutVouchID,concat(bgi.filename,',仓库为：',bgw.fchrWhName) as file_wh_name
        from ecshop.brand_gymboree_inoutvouch bgi
        inner join ecshop.brand_gymboree_warehouse bgw on bgw.fchrwarehouseID = bgi.warehouse_id
        order by bgi.create_timeStamp
    ";
    $inoutVouchIdNames = $db->getAll($sql);
    return $inoutVouchIdNames;
}

function getProvidersByCat($cat_id) {
    global $db, $ecs;
    $cat_id = (int) $cat_id;
    $sql = "select *
            from ecshop.ecs_provider_category pc
                inner join ecshop.ecs_provider p on pc.provider_id = p.provider_id
            where pc.cat_id = $cat_id and p.provider_status = 1
            ORDER BY p.`order` DESC, p.provider_id
        ";
    $rs = $db->query($sql);
    $providers = Array();
    while ($row = $db->fetchRow($rs))
    {
        $providers[$row['provider_id']] = $row;
    }
    return $providers;
}

function getPayments() {
    global $db, $ecs;
    $sql = "SELECT *, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name FROM {$ecs->table('payment')} WHERE enabled = 1 OR enabled_backend = 'Y' ORDER BY pay_order ";
    $rs = $db->query($sql);
    $payments = Array();
    while ($row = $db->fetchRow($rs))
    {
        $payments[$row['pay_id']] = $row;
    }
    return $payments;
}

function getPurchasePaidTypes() {
    return array(
    1 => '银行付款',
    2 => '现金',
    //        3 => '网银',
//    4 => '支票',
    );
}

function getShippingTypes($condition = "") {
    global $db, $ecs;
    $sql = "SELECT * FROM {$ecs->table('shipping')} WHERE enabled = 1 $condition ORDER BY shipping_code, support_cod";
    $rs = $db->query($sql);
    $shippingTypes = Array();
    while ($row = $db->fetchRow($rs)) {
//        if (!party_check($row['party_id'], $_SESSION['party_id'])) {
//            continue;
//        }
        // if ($row['support_cod'] && !$row['support_no_cod']) {
        //     $row['shipping_name'] .= "(货到付款)";
        // }
        $shippingTypes[$row['shipping_id']] = $row;
    }
    
    return $shippingTypes;
}

/*
 * 得到快递列表  key-value 形式  ljzhou 2013.05.21
 */
 function getShippingKeyValueList () {
    global $db, $ecs;
    $sql = "SELECT * FROM {$ecs->table('shipping')} WHERE enabled = 1 ORDER BY shipping_code, support_cod";
    $rs = $db->query($sql);
    $shippingTypes = Array();
    while ($row = $db->fetchRow($rs)) {
        if (!party_check($row['party_id'], $_SESSION['party_id'])) {
            continue;
        }

        $shippingTypes[$row['shipping_id']] = $row['shipping_name'];
    }
    return $shippingTypes;
 }


/**
 *
 * 得到第三方仓库的快递方式 ljzhou 2013.04.22
 */
function get_third_ShippingList() {
    global $db, $ecs;
    // 第三方仓库只展示汇通 快递
    $sql = "SELECT * FROM {$ecs->table('shipping')} WHERE enabled = 1 and shipping_id = 99 limit 1";
    $rs = $db->query($sql);
    $shippingTypes = Array();
    while ($row = $db->fetchRow($rs)) {
        if (!party_check($row['party_id'], $_SESSION['party_id'])) {
            continue;
        }

        $shippingTypes[$row['shipping_id']] = $row;
    }
    return $shippingTypes;
}


/**
 * 搜索订单时候的根据条件添加sql语句
 *
 * @return string 添加的where子句部分
 * by ychen at 2007/1/22
 */
function getOrderSearchCondition() {
    global $ecs;

    $search_type = trim($_REQUEST['search_type']);
    $search_text = trim($_REQUEST['search_text']);

    $startCalendar = sqlSafe($_REQUEST['startCalendar']);
    $endCalendar = sqlSafe($_REQUEST['endCalendar']);
    $shipping_id = sqlSafe($_REQUEST['shipping_id']);
    $order_status = sqlSafe($_REQUEST['order_status']);
    $shipping_status = sqlSafe($_REQUEST['shipping_status']);
    $pay_status = sqlSafe($_REQUEST['pay_status']);
    $facility_id = sqlSafe($_REQUEST['facility_id']);
    $goods_name = sqlSafe($_REQUEST['goods_name']);
    $order_type = $_REQUEST['order_type'];
    //订单来源改为订单类型
    //$outer_type = trim($_REQUEST['outer_type']);
    $distribution_type = trim($_REQUEST['distribution_type']);
    $red_notice = intval($_REQUEST['red_notice']);
    $pay_id = $_REQUEST['pay_id'];

    if ($search_text != '') {
        $date = strtotime('-3 Months');
        $date = date('Y-m-d',$date);
        switch ($search_type) {
            case "user_name":
                $sqladd .= " AND info.nick_name like '{$search_text}%' AND info.order_time > '{$date}' ";
                break;

//            case "bill_no":
//              $sqladd .= " AND b.bill_no = '{$search_text}'  ";
//                break;
            case "tracking_number":
                $sqladd .= " AND sp.tracking_number = '{$search_text}' ";
                break;

            case "tel_mobile":
                $sqladd .= " AND (info.tel LIKE '{$search_text}%' OR info.mobile LIKE '{$search_text}%' ) AND info.order_time > '{$date}'";
                break;
            case "inv_payee" :
                $sqladd .= " AND info.inv_payee LIKE '$search_text%' ";
                break;
            case "taobao_order_sn":
                $sqladd .= " AND info.taobao_order_sn LIKE '$search_text%' ";
                break;
            case "email" :
                $sqladd .= " AND info.email LIKE '{$search_text}%' ";
                break;
            case "consignee" :
                $sqladd .= " AND info.consignee LIKE '{$search_text}%' ";
                break;
            default:
                // 如果是订单号
                if (preg_match('/^[0-9\.\-tbch]+$/', $search_text)) {
                    $sqladd .= " AND info.order_sn LIKE '$search_text%' ";
                } else {
                    // 否则搜索收货人,去掉收货人中间的空格
                    $search_text = str_replace(" ", "", $search_text);
                    $sqladd .= " AND info.consignee LIKE '$search_text%' ";
                }
                break;
        }
    }

    if (strtotime($startCalendar) > 0) {
        $startCalendar = date("Y-m-d H:i:s", strtotime($startCalendar));
        $sqladd .= " AND info.order_time >= '$startCalendar'";
    }
    if (strtotime($endCalendar) > 0) {
        $endCalendar = date("Y-m-d H:i:s", strtotime($endCalendar));
        $sqladd .= " AND info.order_time <= '$endCalendar'";
    }
    if ($shipping_id) {
        $sqladd .= " AND shipping_id = '$shipping_id'";
    }
    if ($facility_id) {
        $sqladd .= " AND info.facility_id = '$facility_id'";
    }
    
    if ($order_status != -1 && $order_status !== null) {
        $sqladd .= " AND order_status = '$order_status'";
    }
    //
//    if ($shipping_status != -1 && $shipping_status !== null &&  $shipping_status!='transit_step') {
    if ($shipping_status != -1 && $shipping_status !== null){
        // 已确认，待预定
        if($shipping_status == 15) {
            $sqladd .=  " AND info.order_status = 1 AND info.shipping_status = 0 
            AND not exists (select 1 from romeo.order_inv_reserved r 
            where r.order_id = info.order_id and r.status in('Y','F') limit 1) ";
        }
        else 
        {
            $sqladd .= " AND info.shipping_status = '$shipping_status'";
        }
    }
//    else if($shipping_status != -1 && $shipping_status=='transit_step'){
//      //有走件信息，已发货 -- ERROR!!!数据来源不确定ecshop.ecs_taobao_logistics_trace_detail还是ecshop.sync_taobao_logistics_trace_list！！！
//      $sqladd .= " AND info.shipping_status='1' AND EXISTS(SELECT * FROM ecshop.ecs_taobao_logistics_trace_detail WHERE logistics_trace_id = t.logistics_trace_id)";
//    }
    if ($pay_status != -1 && $pay_status !== null) {
        $sqladd .= " AND pay_status = '$pay_status'";
    }
    if ($pay_id != -1 && $pay_id !== null) {
        $sqladd .= " AND pay_id = '$pay_id'";
    }

//    if ($outer_type !== null && $outer_type != -1) {
//        if ($outer_type == 'ouku') {
//            $sqladd .= " AND NOT EXISTS (SELECT 1 FROM order_attribute WHERE order_id = info.order_id AND attr_name = 'OUTER_TYPE')";
//        } else {
//            $sqladd .= " AND EXISTS (SELECT 1 FROM order_attribute WHERE order_id = info.order_id AND attr_name = 'OUTER_TYPE' AND attr_value = '{$outer_type}' )";
//        }
//    }
//  订单来源改为订单类型
    if ($distribution_type !== null && $distribution_type != -1) {
        $sqladd .= " AND md.type = '{$distribution_type}' ";
    }

    switch ($order_type) {
        case 'b2c':
            $sqladd .= " AND biaoju_store_id in (0, 7)";
            break;
        case 'dx' :
        case 'c2c':
            $sqladd .= " AND biaoju_store_id <> 0";
            break;
        case 'parent':
            $sqladd .= " AND (select count(*) from {$ecs->table('order_info')} as oi where oi.parent_order_id = info.order_id) > 0";
            break;
        case 'all':
        default:
    }

    switch ($red_notice) {
        case 1:
            $sqladd .= " AND order_status = 1 AND shipping_status IN (0) AND pay_status = 2 AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} a WHERE a.order_status = 1 AND a.order_id = info.order_id AND UNIX_TIMESTAMP(a.action_time) < UNIX_TIMESTAMP(DATE_ADD(now(), INTERVAL -24 HOUR)))";
            break;
        case 2:
            $sqladd .= " AND order_status = 1 AND shipping_status = 0 AND (pay_status = 2 or pay_id = 1) AND  info.reserved_time != 0";
            //红色警报下已预订未发货选项
            break;
        case 3:
            $sqladd .= " AND order_status = 1 AND shipping_status = 0 AND (pay_status = 2 or pay_id = 1) AND info.reserved_time = 0";
            //红色警报下未预订订单选项
            break;
        case 4:
            $sqladd .=" AND info.pay_id = 1 AND exists (SELECT 1 FROM ecshop.ecs_order_action eoa WHERE eoa.order_id = info.order_id AND eoa.action_note IN ('N','n') AND eoa.note_type = 'message')";
            break;
        case 5:
            $sqladd .=" AND info.pay_id = 1 AND exists (SELECT 1 FROM ecshop.ecs_order_action eoa WHERE eoa.order_id = info.order_id AND eoa.action_note IN ('Y','y') AND eoa.note_type = 'message')";
            break;
        case 6:
            $sqladd .=" AND info.pay_id = 1 AND exists (SELECT 1 FROM ecshop.ecs_order_action eoa WHERE eoa.order_id = info.order_id AND eoa.action_note NOT IN ('N','n','Y','y','订单短信已发送') AND eoa.note_type = 'message')";
            break;
        default:
    }
    return $sqladd;
}

function getOrderInfoByOrderSN($order_sn) {
    global $db, $ecs;
    $order_id = getOrderIdBySN($order_sn);
    return getOrderInfo($order_id);
}

/**
 * 根据ordewr_id查询order_sn
 *
 * @param int $order_id
 * @return string $order_sn
 */
function getOrderSnByOrderId($order_id) {
    global $db, $ecs;
    static $orderMapping = array();
    $order_id = intval($order_id);
    if(!$order_id) return false;
    if(!$orderMapping[$order_id]) {
        $sql = "SELECT order_sn FROM {$ecs->table('order_info')} WHERE order_id = '$order_id' ";
        $order_sn = $db->getOne($sql);
        $orderMapping[$order_id] = $order_sn;
    }
    return $orderMapping[$order_id];
}

function getOrderInfo($order_id) {
    global $ecs, $db;
    // $orderSQL = "
    //     SELECT info.*, b.bill_no, b.weight, b.carrier_id, c.name AS carrier_name, u.user_name, u.rank_points,
    //         u.user_rank, u.email user_email, sh.midway_address,
    //         (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee,
    //         (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + bonus + integral_money) AS final_fee,
    //         (SELECT COUNT(*) FROM {$ecs->table('order_info')} AS sub WHERE info.order_id = sub.parent_order_id) AS sub_count
    //         ,(select attr_value from ecshop.order_attribute oa where oa.order_id = info.order_id and attr_name = 'language_id' limit 1) as language_id
    //     FROM {$ecs->table('order_info')} AS info
    //     LEFT JOIN {$ecs->table('users')} AS u ON u.user_id = info.user_id
    //     LEFT JOIN {$ecs->table('carrier_bill')} AS b ON b.bill_id = info.carrier_bill_id
    //     LEFT JOIN {$ecs->table('carrier')} AS c ON b.carrier_id = c.carrier_id
    //     LEFT JOIN {$ecs->table('shipping')} AS sh ON sh.shipping_id = info.shipping_id
    //     WHERE info.order_id = $order_id";
    $orderSQL="SELECT
        info.*, s.tracking_number,
        s.tracking_number AS bill_no,
        s.SHIPPING_LEQEE_WEIGHT AS weight,
        c.carrier_id,
        c. NAME AS carrier_name,
        u.user_name,
        u.rank_points,
        u.user_rank,
        u.email user_email,
        sh.midway_address,
        (
            goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee
        ) AS total_fee,
        (
            goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + bonus + integral_money
        ) AS final_fee,
        (
            SELECT
                COUNT(*)
            FROM
                ecshop.ecs_order_info AS sub
            WHERE
                info.order_id = sub.parent_order_id
        ) AS sub_count,
        (
            SELECT
                attr_value
            FROM
                ecshop.order_attribute oa
            WHERE
                oa.order_id = info.order_id
            AND attr_name = 'language_id'
            LIMIT 1
        ) AS language_id
    FROM
        ecshop.ecs_order_info AS info
    LEFT JOIN ecshop.ecs_users AS u ON u.user_id = info.user_id
    LEFT JOIN ecshop.ecs_shipping AS sh ON sh.shipping_id = info.shipping_id
    LEFT JOIN ecshop.ecs_carrier c ON sh.default_carrier_id = c.carrier_id
    LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (info.order_id USING utf8)
    LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
    WHERE
        info.order_id = $order_id
    AND s.STATUS != 'SHIPMENT_CANCELLED'
    GROUP BY
        order_id
    ";
    $order = $db->getRow($orderSQL);

    $order['order_status_name'] = get_order_status($order['order_status']);
    $order['pay_status_name'] = get_pay_status($order['pay_status']);
    $order['shipping_status_name'] = get_shipping_status($order['shipping_status']);
    $order['invoice_status_name'] = get_invoice_status($order['invoice_status']);

    // 查询省、城市
    $countrySQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['country']}'";
    $order['country_name'] = $db->getOne($countrySQL);

    $provinceSQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['province']}'";
    $order['province_name'] = $db->getOne($provinceSQL);

    $citySQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['city']}'";
    $order['city_name'] = $db->getOne($citySQL);

    $districtSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
    $order['district_name'] = $db->getOne($districtSQL);

    $actionSQL = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '$order_id' ORDER BY action_time";
    $actions = $db->getAll($actionSQL);
    foreach ($actions AS $actionKey => $action) {
        $actions[$actionKey]['order_status_name'] = get_order_status($action['order_status']);
        $actions[$actionKey]['pay_status_name'] = get_pay_status($action['pay_status']);
        $actions[$actionKey]['shipping_status_name'] = get_shipping_status($action['shipping_status']);
        $actions[$actionKey]['invoice_status_name'] = get_invoice_status($action['invoice_status']);

        // 确认时间
        if ($order['confirm_time'] == 0 && $action['order_status'] == OS_CONFIRMED) {
            $order['confirm_time'] = $action['action_time'];
        }
        // 发货时间
        if ($order['shipping_time'] == 0 && $action['shipping_status'] == SS_SHIPPED) {
            $order['shipping_time'] = $action['action_time'];
        }
        // 付款时间
        /*if ($order['pay_time'] == 0 && $action['pay_status'] == PS_PAYED) {
        $order['pay_time'] = $action['action_time'];
        }*/

    }
    $order['actions'] = $actions;


    // 查询商品
    $goodsSQL = "
        SELECT
            gs.*, g.*, g.goods_number * g.goods_price AS total_price,
            ifnull(gg.goods_volume, 0) as goods_volume, ifnull(gg.goods_weight, 0) as goods_weight
        FROM {$ecs->table('order_goods')} g
            LEFT JOIN {$ecs->table('goods_style')} gs ON g.goods_id = gs.goods_id AND g.style_id = gs.style_id
            LEFT JOIN {$ecs->table('goods')} gg ON g.goods_id = gg.goods_id
        WHERE g.order_id = '$order_id'
    ";
    $goodsList = $db->getAll($goodsSQL);

    foreach ($goodsList AS $goodsKey => $goods) {

        $actionSQL = "SELECT * FROM {$ecs->table('order_goods_action')} WHERE order_goods_id = {$goods['rec_id']} ORDER BY action_time";
        $actions = $db->getAll($actionSQL);
        $catSql = "SELECT uniq_sku, top_cat_id, func_get_goods_category_name(top_cat_id, cat_id) as category FROM {$ecs->table('goods')} WHERE goods_id = '{$goods['goods_id']}'";
        $g = $db->getRow($catSql);
        $goodsList[$goodsKey]['top_cat_id'] = $g['top_cat_id'];
        $goodsList[$goodsKey]['category'] = $g['category'];
        $goodsList[$goodsKey]['uniq_sku'] = $g['uniq_sku'];
        foreach ($actions AS $actionKey => $action) {
            $actions[$actionKey]['goods_status_name'] = get_goods_status($action['goods_status']);
        }
        $goodsList[$goodsKey]['actions'] = $actions;

        $sql = "SELECT ii.inventory_item_acct_type_id AS order_type, osi.* 
                FROM romeo.inventory_item_detail iid
                INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
                LEFT JOIN romeo.order_shipping_invoice osi ON osi.order_id = iid.order_id
                WHERE iid.order_goods_id = '{$goods['rec_id']}'";
        $erps = $db->getAll($sql);
        $shipping_invoices = array();
        $order_types = array();
        
        foreach ($erps as $erp) {
            $shipping_invoices[] = $erp['shipping_invoice'];
            $order_types[] = $erp['order_type'];
        }
        $goodsList[$goodsKey]['shipping_invoices'] = $shipping_invoices;
        $goodsList[$goodsKey]['order_types'] = $order_types;
        $goodsList[$goodsKey]['erp_info'] = $erps;
    }
    $order['goods_list'] = $goodsList;

    return $order;
}

function getNoServiceOrderInfo($order_id) {
    global $ecs, $db;
    // $orderSQL = "
    //     SELECT info.*, b.bill_no, b.weight, b.carrier_id, c.name AS carrier_name, u.user_name, u.rank_points,
    //         u.user_rank, u.email user_email, sh.midway_address,
    //         (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee,
    //         (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + bonus + integral_money) AS final_fee,
    //         (SELECT COUNT(*) FROM {$ecs->table('order_info')} AS sub WHERE info.order_id = sub.parent_order_id) AS sub_count
    //         ,(select attr_value from ecshop.order_attribute oa where oa.order_id = info.order_id and attr_name = 'language_id' limit 1) as language_id
    //     FROM {$ecs->table('order_info')} AS info
    //     LEFT JOIN {$ecs->table('users')} AS u ON u.user_id = info.user_id
    //     LEFT JOIN {$ecs->table('carrier_bill')} AS b ON b.bill_id = info.carrier_bill_id
    //     LEFT JOIN {$ecs->table('carrier')} AS c ON b.carrier_id = c.carrier_id
    //     LEFT JOIN {$ecs->table('shipping')} AS sh ON sh.shipping_id = info.shipping_id
    //     WHERE info.order_id = $order_id";
    $orderSQL="SELECT
        info.*, s.tracking_number,
        s.tracking_number AS bill_no,
        s.SHIPPING_LEQEE_WEIGHT AS weight,
        c.carrier_id,
        c. NAME AS carrier_name,
        u.user_name,
        u.rank_points,
        u.user_rank,
        u.email user_email,
        sh.midway_address,
        (
            goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee
        ) AS total_fee,
        (
            goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + bonus + integral_money
        ) AS final_fee,
        (
            SELECT
                COUNT(*)
            FROM
                ecshop.ecs_order_info AS sub
            WHERE
                info.order_id = sub.parent_order_id
        ) AS sub_count,
        (
            SELECT
                attr_value
            FROM
                ecshop.order_attribute oa
            WHERE
                oa.order_id = info.order_id
            AND attr_name = 'language_id'
            LIMIT 1
        ) AS language_id
    FROM
        ecshop.ecs_order_info AS info
    LEFT JOIN ecshop.ecs_users AS u ON u.user_id = info.user_id
    LEFT JOIN ecshop.ecs_shipping AS sh ON sh.shipping_id = info.shipping_id
    LEFT JOIN ecshop.ecs_carrier c ON sh.default_carrier_id = c.carrier_id
    LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (info.order_id USING utf8)
    LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
    WHERE
        info.order_id = $order_id
    AND s. STATUS != 'SHIPMENT_CANCELLED'
    GROUP BY
        order_id
    ";
    $order = $db->getRow($orderSQL);

    $order['order_status_name'] = get_order_status($order['order_status']);
    $order['pay_status_name'] = get_pay_status($order['pay_status']);
    $order['shipping_status_name'] = get_shipping_status($order['shipping_status']);
    $order['invoice_status_name'] = get_invoice_status($order['invoice_status']);

    // 查询省、城市
    $countrySQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['country']}'";
    $order['country_name'] = $db->getOne($countrySQL);

    $provinceSQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['province']}'";
    $order['province_name'] = $db->getOne($provinceSQL);

    $citySQL = "select region_name from {$ecs->table('region')} where region_id = '{$order['city']}'";
    $order['city_name'] = $db->getOne($citySQL);

    $districtSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
    $order['district_name'] = $db->getOne($districtSQL);

    $actionSQL = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '$order_id' ORDER BY action_time";
    $actions = $db->getAll($actionSQL);
    foreach ($actions AS $actionKey => $action) {
        $actions[$actionKey]['order_status_name'] = get_order_status($action['order_status']);
        $actions[$actionKey]['pay_status_name'] = get_pay_status($action['pay_status']);
        $actions[$actionKey]['shipping_status_name'] = get_shipping_status($action['shipping_status']);
        $actions[$actionKey]['invoice_status_name'] = get_invoice_status($action['invoice_status']);

        // 确认时间
        if ($order['confirm_time'] == 0 && $action['order_status'] == OS_CONFIRMED) {
            $order['confirm_time'] = $action['action_time'];
        }
        // 发货时间
        if ($order['shipping_time'] == 0 && $action['shipping_status'] == SS_SHIPPED) {
            $order['shipping_time'] = $action['action_time'];
        }
        // 付款时间
        /*if ($order['pay_time'] == 0 && $action['pay_status'] == PS_PAYED) {
        $order['pay_time'] = $action['action_time'];
        }*/

    }
    $order['actions'] = $actions;


    // 查询商品
    $goodsSQL = "
        SELECT
            gs.*, g.*, g.goods_number * g.goods_price AS total_price,
            ifnull(gg.goods_volume, 0) as goods_volume, ifnull(gg.goods_weight, 0) as goods_weight
        FROM {$ecs->table('order_goods')} g
            LEFT JOIN {$ecs->table('goods_style')} gs ON g.goods_id = gs.goods_id AND g.style_id = gs.style_id
            LEFT JOIN {$ecs->table('goods')} gg ON g.goods_id = gg.goods_id
        WHERE g.order_id = '$order_id'
    ";
    $goodsList = $db->getAll($goodsSQL);

    $order['goods_list'] = $goodsList;

    return $order;
}

/**
 * 得到订单的基础信息
 */
function getBasicOrderInfo($order_id) {
    global $ecs, $db;
    $orderSQL = "
        SELECT info.order_id,info.party_id
        FROM {$ecs->table('order_info')} AS info
        WHERE info.order_id = $order_id";
    $order = $db->getRow($orderSQL);

    // 查询商品
    $goodsSQL = "
        SELECT
            gs.*, g.*, g.goods_number * g.goods_price AS total_price,
            ifnull(gg.goods_volume, 0) as goods_volume, ifnull(gg.goods_weight, 0) as goods_weight
        FROM {$ecs->table('order_goods')} g
            LEFT JOIN {$ecs->table('goods_style')} gs ON g.goods_id = gs.goods_id AND g.style_id = gs.style_id
            LEFT JOIN {$ecs->table('goods')} gg ON g.goods_id = gg.goods_id
        WHERE g.order_id = '$order_id'
    ";
    $goodsList = $db->getAll($goodsSQL);
    $order['goods_list'] = $goodsList;

    return $order;
}

function getOrderInfoWithSubOrder($order_id) {
    global $ecs, $db;
    if ($order_id <= 0) {
        return null;
    }
    $SQL = "SELECT order_id, order_sn FROM {$ecs->table('order_info')} AS info WHERE info.parent_order_id = '$order_id'";
    $subOrder = $db->getAll($SQL);
    if (count($subOrder) > 0) {
        foreach ($subOrder AS $key => $subOrder) {
            $sub_orders[] = getOrderInfo($subOrder['order_id']);
        }
        $order = getOrderInfo($order_id);
        $order['sub_orders'] = $sub_orders;
    } else {
        $order = getOrderInfo($order_id);
    }
    return $order;
}

function checkHasSerialNumber($serial_numbers) {
    global $ecs, $db;
    if(empty($serial_numbers)) {
        return false;
    }
    $sql = "
        SELECT 1 from romeo.inventory_item where serial_number='{$serial_numbers}' and quantity_on_hand_total > 0 limit 1;
    ";
    $serial_number = $db->getOne($sql);
    if(empty($serial_number)) {
        return false;
    }

    return true;
}

/**
 * 得到商家列表
 *
 * @param boolean $is_OK 判断是否要加status='OK'过虑
 * @return array 商家列表
 */
function getStores($is_OK = false) {
    global $db, $ecs;
    $condition = '';
    if ($is_OK) {
        $condition .= "AND status = 'OK'";
    }
    $sql = "SELECT * FROM bj_store WHERE 1 $condition ORDER BY status";
    $stores = $db->getAll($sql);
    return $stores;
}

function get_shipping_order_type_name($type) {
    switch ($type) {
        case 'zjs':
            return '宅急送';
        case 'zjs-x':
            return '宅急送（货到付款）';
        case 'ems':
            return 'EMS';
        case 'fedex':
            return '联邦快递';
    }
    return '';
}


/**
 * 添加历史记录
 *
 * @param array $histories 历史记录
 */
function add_history($histories, $action_table_name) {
    global $db;
    foreach ($histories as $key => $history) {
        $sql = "INSERT INTO $action_table_name(reference_key, table_name, field_name, origin_value, set_value, execute_sql, execute_type, action_user, action_time) VALUES('{$history['reference_key']}', '{$history['table_name']}', '{$history['field_name']}', '{$history['origin_value']}', '{$history['set_value']}', '{$history['execute_sql']}', '{$history['execute_type']}', '{$history['action_user']}', '{$history['action_time']}')";
        $db->query($sql);
    }
}

/**
 * 获得开始结束时间
 *
 * @param string $start
 * @param string $end
 * @param string $span
 */
function check_dates(&$start, &$end, $span = "1 WEEK", $default = "-1 WEEK") {
    if ($start == '') {
        $start = $_REQUEST['start'] ? $_REQUEST['start'] : date("Y-m-d", strtotime($default));
    }
    if ($end == '') {
        $end = $_REQUEST['end'] ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));
    }
    if (!strtotime($start) || $start > date("Y-m-d")) {
        $start = date("Y-m-d", strtotime($default));
    }
    if (!strtotime($end) || $end > date("Y-m-d")) {
        $end = date("Y-m-d");
    }
    if (strtotime($span, strtotime($start)) < strtotime($end)) {
        $end = date("Y-m-d", strtotime($span, strtotime($start)));
    }
}
/**
 * 根据起始时间和结束时间返回每天的日期数组，格式默认为“年-月-日”
 *
 * @param string $start 开始时间
 * @param string $end 结束时间
 * @param string $format 返回时间的格式
 * @return array 日期数组
 */
function get_dates($start, $end, $span = "-1 week", $format = 'Y-m-d') {
    $dates = array();
    if ($start == null && $end == null) {
        $end = date("Y-m-d");
        $start = date("Y-m-d", strtotime($span, strtotime($end)));
    } else if ($start == null) {
        $start = date("Y-m-d", strtotime($span, strtotime($end)));
    } else if ($end == null) {
        $end = date("Y-m-d");
    }
    $temp = $end;
    while (strtotime($temp) >= strtotime($start)) {
        $dates[] = $temp;
        $temp = date($format, strtotime("-1 day", strtotime($temp)));
    }
    return $dates;
}

function get_weeks($start, $end, $span = "-6 day", $format = 'Y-m-d') {
    $weeks = array();
    if ($start == null && $end == null) {
        if (date("w") == 0) {
            $end = date("Y-m-d", strtotime("this Sunday"));
            $start = date("Y-m-d", strtotime($span, strtotime("this Sunday")));
        } else {
            $end = date("Y-m-d", strtotime("last Sunday"));
            $start = date("Y-m-d", strtotime($span, strtotime("last Sunday")));
        }
    } else if ($start == null) {
        $start = date("Y-m-d", strtotime($span, strtotime($end)));
    } else if ($end == null) {
        if (date("w") == 0) {
            $end = date("Y-m-d", strtotime("this Sunday"));
        } else {
            $end = date("Y-m-d", strtotime("last Sunday"));
        }
    }
    /*
    $start = date("Y-m-d", strtotime("this Monday", strtotime($start)));
    if (date("w", strtotime($end)) == 0) {
    $end = date("Y-m-d", strtotime("this Sunday", strtotime($end)));
    } else {
    $end = date("Y-m-d", strtotime("last Sunday", strtotime($end)));
    }

    $temp = $end;

    while (strtotime($temp) > strtotime($start)) {
    $week['start'] = date($format, strtotime("-6 day", strtotime($temp)));
    $week['end'] = $temp;

    $weeks[] = $week;
    $temp = date($format, strtotime("-7 day", strtotime($temp)));
    }
    */
    $weeks = array();
    $week['start'] = $start;
    $week['end'] = $end;
    $weeks[] = $week;
    return $weeks;
}

/**
 * 根据UUID取得用户名
 *
 * @param string $uuid 32位的uuid
 * @return string 32位uuid对应的用户名
 */
function getUserName($uuid) {
    global $db, $ecs;
    $sql = "SELECT user_name FROM {$ecs->table('users')} WHERE userId = '$uuid'";
    $user_name = $db->getOne($sql);
    return $user_name;
}

/**
 * 根据用户名返回UUID
 *
 * @param string 用户名
 * @return string $uuid 用户名对应的32位的uuid
 */
function getUUID($user_name) {
    global $db, $ecs;
    $sql = "SELECT userId FROM {$ecs->table('users')} WHERE user_name = '$user_name'";
    $uuid = $db->getOne($sql);
    return $uuid;
}

function erp_price_format($price) {
    if ($price === null) {
        return '';
    } else {
        return number_format($price, 2);
    }
}

function invoice_price_format($price) {
    if ($price === null) {
        return '';
    } else {
        return number_format($price, 6);
    }
}

/**
 * 从REQUEST取得参数，返回一个数组列表
 *
 * @param string $param 参数名
 * @return array 该参数对应的值，支持数组;如果没有该参数则返回null
 */
function get_param_array($param) {
    $params = $_REQUEST[$param];
    if ($params === null) {
        return null;
    }
    if (!is_array($params)) {
        $params = array($params);
    }
    return $params;
}

/**
 * 根据传入的类别id取得类别名
 *
 * @param mix $cat_ids 单个类别id或者类别id数组
 * @return mix 返回的类别名，如果传入单个id则返回单个类别名，如果传入数组则返回对应的数组类别名
 */

function get_cat_names($cat_ids = null) {
    global $db, $ecs;
    $cat_names = array();

    if (is_array($cat_ids)) {
        foreach ($cat_ids as $cat_id) {
            $sql = "SELECT cat_name FROM {$ecs->table('category')} WHERE cat_id = '$cat_id'";
            $cat_names[] = $db->getOne($sql);
        }
        return $cat_names;
    } else if ($cat_ids != null) {
        $sql = "SELECT cat_name FROM {$ecs->table('category')} WHERE cat_id = '$cat_ids'";
        return $db->getOne($sql);
    } else {
        $sql = "SELECT cat_name, cat_id FROM {$ecs->table('category')} WHERE sort_order <= 100 ORDER BY parent_id DESC";
        $cats = $db->getAll($sql);
        foreach ($cats as $key => $cat) {
            $cat_names[$cat['cat_id']] = $cat['cat_name'];
        }
        return $cat_names;
    }
}

/**
 * 根据传入的商家id取得商家店名
 *
 * @param mix $store_ids 单个店家id或者店家的id数组
 * @return mix 返回店家名，如果传入单个店家id则返回单个店家名，如果传入店家id数组则返回店家名数组
 */
function get_store_names($store_ids) {
    global $db, $ecs;

    if (is_array($store_ids)) {
        $store_names = array();
        foreach ($store_ids as $store_id) {
            if ($store_id == 0) {
                $store_names[] = "欧酷";
            } else {
                $sql = "SELECT name FROM bj_store WHERE store_id = '$store_id'";
                $store_names[] = $db->getOne($sql);
            }
        }
        return $cat_names;
    } else {
        if ($store_ids == 0) {
            return "欧酷";
        }
        $sql = "SELECT name FROM bj_store WHERE store_id = '$store_ids'";
        return $db->getOne($sql);
    }
}

/**
 * 根据传入的省份id取得省份名
 *
 * @param mix $provinces 单个省份id或者省份的id数组
 * @return mix 返回省份名，如果传入单个省份id则返回单个省份名，如果传入省份id数组则返回省份名数组
 */
function get_region_names($provinces) {
    global $db, $ecs;
    if (is_array($provinces)) {
        $province_names = array();
        foreach ($provinces as $province) {
            $sql = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '$province'";
            $province_names[] = $db->getOne($sql);
        }
        return $province_names;
    } else {
        $sql = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '$provinces'";
        return $db->getOne($sql);
    }
}
/* 一行一行读取CSV 文件*/
function fgetcsv_reg(& $handle, $length = null, $d = ',', $e = '"') {
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof=false;
    while ($eof != true) {
        $_line .= (empty ($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
        if ($itemcnt % 2 == 0)
        $eof = true;
    }
    $_csv_line = preg_replace('/(?:\r\n|[\r\n])?$/', $d, trim($_line));
    $_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];
    for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++) {
        $_csv_data[$_csv_i] = preg_replace('/^' . $e . '(.*)' . $e . '$/s', '$1', $_csv_data[$_csv_i]);
        $_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
    }
    return empty ($_line) ? false : $_csv_data;
}

/**
 * 根据goods_id和style_id获取生成订单时候的商品名称
 *
 * @param int $goods_id
 * @param int $style_id
 * @return string 生成订单时的商品名
 */
function get_order_goods_name($goods_id, $style_id = 0) {
    global $db, $ecs;

    if ($goods_id <= 0)
    return "";

    $sql = "SELECT goods_name FROM {$ecs->table('goods')} WHERE goods_id = '{$goods_id}'";
    $goods_name = $db->getOne($sql);

    if ($style_id > 0) {
        $sql = "SELECT IF (gs.goods_color = '', s.color, gs.goods_color) AS color FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s WHERE gs.goods_id = '{$goods_id}' AND s.style_id = '{$style_id}' AND gs.style_id = s.style_id ";
        $color = $db->getOne($sql);
        $goods_name .= " {$color}";
    }
    return $goods_name;
}

// 设置工作时间是：早上9点半到晚上5点半
function calculate_diff_time($start, $end) {
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    $work_start_time = strtotime("+9 hour 30 minute", strtotime("today", strtotime($start)));
    $work_end_time = strtotime("+17 hour 30 minute", strtotime("today", strtotime($end)));

    $dif_time = ($end_time>$work_end_time?$work_end_time:$end_time) -
    ($start_time>$work_start_time?$start_time:$work_start_time);
    if ($dif_time <= 0)
    return 0;

    $start_time_array = getdate($start_time);
    $end_time_array = getdate($end_time);
    $dif_time -= ($end_time_array['mday'] - $start_time_array['mday']) * 16 * 60 * 60;

    if ($dif_time < 0)
    $dif_time = 0;

    return $dif_time;
}


function get_goods_history_price($goods_id, $style_id, $day) {
    global $ecs, $db;
    if ($style_id > 0 ) {
        $sql = "SELECT s.* FROM PRICE_TRACKER.SHOP_PRICE  s
            INNER JOIN {$ecs->table('goods_style')} gs ON s.goods_style_id = gs.goods_style_id AND s.goods_id = gs.goods_id
            WHERE s.APPROVED_DATETIME < '{$day}'
            AND gs.goods_id = '{$goods_id}' AND gs.style_id = '{$style_id}'
            AND s.STATUS = 'OK'
            ORDER BY s.APPROVED_DATETIME DESC
            LIMIT 1
            ";
    } else {
        $sql = "SELECT s.*  FROM PRICE_TRACKER.SHOP_PRICE  s
            WHERE s.APPROVED_DATETIME < '{$day}'
            AND s.goods_id = '{$goods_id}'
            AND s.STATUS = 'OK'
            ORDER BY s.APPROVED_DATETIME DESC
            LIMIT 1
            ";
    }
    return $db->getRow($sql);
}


function last_month_end() {
    $a = time();
    $b = mktime(date("H", $a), date("i", $a), date("s", $a), date("m", $a)-1, date("d", $a), date("Y", $a)); // 上个月，int
    $month = date("m", $b);
    return date("Y",$a)."-".$month."-".date("t", $month);
}

function last_month_start() {
    $a = time();
    $b = mktime(date("H", $a), date("i", $a), date("s", $a), date("m", $a)-1, date("d", $a), date("Y", $a)); // 上个月，int
    $month = date("m", $b);
    return date("Y",$a)."-".$month."-01";
}

/**
 * 通过商品ID获得所以购买此商品的订单
 *
 * @param int $goods_id
 * @return array $order_ids
 */
function get_order_list($goods_id) {
    global $ecs, $db;
    $sql = "SELECT order_id FROM {$ecs->table('order_goods')} WHERE goods_id = '$goods_id'";
    $order_ids = $db->getCol($sql);
    return $order_ids;
}
/**
 * 设置订单商品状态
 *
 * @param int $order_id
 * @param int $goods_id
 * @param int $storage_status
 */
function set_goods_status($order_id, $goods_id, $storage_status) {
    global $db, $ecs;
    $sql = "UPDATE {$ecs->table('order_goods')} SET goods_status = '$storage_status' WHERE order_id = $order_id AND goods_id = $goods_id ";
    $db->query($sql);
}

/**
 * 设置搜索关键字
 *
 * @param string $search_text
 * @param array $fields
 * @return string
 */
function parseSearchText($search_text, $fields = array()) {
    $condition = '';
    if (empty($fields)) {
        if (preg_match('/^[0-9A-Za-z\-\.]+$/', $search_text)) {
            if (strpos($search_text, '.')) {
                $condition .= " AND (purchase_paid_amount = '$search_text' OR order_amount = '$search_text')";
            } else {
                $condition .= " AND (order_sn LIKE '%$search_text%' OR goods_name LIKE '%$search_text%' OR purchase_invoice LIKE '%$search_text%' OR cheque LIKE '%$search_text%')";
            }
        } else {
            $condition .= " AND (goods_name LIKE '%$search_text%' OR consignee LIKE '%$search_text%' OR action_user LIKE '%$search_text%')";
        }
    } else {
        foreach ($fields as $key => $field) {
            $fields[$key] = " {$field} LIKE '%{$search_text}%' ";
        }
        $condition .= " AND " . implode(" OR ", $fields);
    }

    return $condition;
}



/**
 * 获得商品的编码
 *
 * @param int $goods_id
 * @param  $style_id
 * @return string
 */
function encode_goods_id($goods_id, $style_id) {
    $goods_id = intval($goods_id);
    $style_id = intval($style_id);
    global $db;
    static $encode_array = array();
    $key = "{$goods_id}_{$style_id}";

    if (!isset($encode_array[$key])) {
        $encode_array[$key] = encode_goods_id_1($goods_id, $style_id);
        /*
        $sql = "SELECT goods_party_id, internal_sku
                FROM ecs_goods g
                LEFT JOIN ecs_goods_style gs ON g.goods_id = gs.goods_id
                WHERE g.goods_id = '{$goods_id}' AND gs.style_id = '{$style_id}'
                ";

        $goods = $db->getRow($sql);
        $goods_party_id = $goods['goods_party_id'];

        if ($goods_party_id == PARTY_OUKU_SHOES) {
            // 如果是鞋子的，sku从数据库中读取,然后去掉前面的三位
            $encode_array[$key] = substr($goods['internal_sku'], 3);
        } else {
            $encode_array[$key] = encode_goods_id_1($goods_id, $style_id);
        }
        */
    }

    return $encode_array[$key];
}

/**
 * 对产品编码进行解码
 *
 * @param string $code
 * @return mixed array
 */
function decode_goods_id($code) {
    global $db;
    static $decode_array = array();

    if (!isset($decode_array[$code])) {
        if(strpos($code, '#') !== false) {
            //如果code中间有#的，认为是欧酷配件
            $decode_array[$code] = decode_goods_id_1($code);
        } else {
            // 鞋子的要去掉前面的三位去匹配
            $sql = "SELECT goods_id, style_id
                    FROM ecs_goods_style
                    WHERE SUBSTRING(internal_sku, 4) = '{$code}' ";
            $style = $db->getRow($sql);
            if (!$style) {
                $decode_array[$code] = false;
            } else {
                $decode_array[$code] = array($style['goods_id'], $style['style_id']);
            }
        }
    }
    return  $decode_array[$code];
}


/**
 * 对goods_id style_id进行编码 FOR 欧酷配件
 * 编码规则：{$goods_id}#{$style_id}{$check}
 * $check = sum(数字) % 10
 *
 * @param int $goods_id
 * @param  $style_id
 * @return string
 */
function encode_goods_id_1($goods_id, $style_id) {
    $goods_id = intval($goods_id);
    $style_id = intval($style_id);

    $goods_id_str = (string) $goods_id;
    $style_id_str = (string) $style_id;
    $total = 0;
    for ($i = 0; $i < strlen($goods_id_str); $i++) {
        //    print $goods_id_str{$i}."a\n";
        $total += $goods_id_str{$i};
    }
    for ($i = 0; $i < strlen($style_id_str); $i++) {
        //    print $goods_id_str{$i}."a\n";
        $total += $style_id_str{$i};
    }
    $check =$total % 10;
    $code = $goods_id_str."#".$style_id_str.$check;
    return $code;
}

/**
 * 对欧酷的配件产品编码进行解码
 *
 * @param string $code
 * @return mixed array
 */
function decode_goods_id_1($code) {
    $temp = explode("#", $code);
    if(count($temp) != 2) {
        return false;
    } else {
        $goods_id_str = (string) $temp[0];
        $style_id_str = (string) $temp[1];
        if(strlen($style_id_str) < 2) return false;
        $total = 0;
        for ($i = 0; $i < strlen($goods_id_str); $i++) {
            //    print $goods_id_str{$i}."a\n";
            $total += $goods_id_str{$i};
        }
        for ($i = 0; $i < strlen($style_id_str) - 1; $i++) {
            //    print $goods_id_str{$i}."a\n";
            $total += $style_id_str{$i};
        }
        if($style_id_str{$i} == ($total % 10)) {
            return array(intval($goods_id_str), intval($temp[1] / 10));
        } else {
            return false;
        }

    }
}

/**
 * 检查订单是否在黑名单中
 *
 * @param array $order 订单信息
 * @return array 是否属于黑名单订单 黑名单提示信息
 */
function check_order_blacklist($order) {
    global $_CFG;
    //检测黑名单订单
    $is_in_blacklist = false;
    $blacklist_tips = '';
    $blacklist_lines = explode("\n", $_CFG['order_blacklist']);
    foreach ($blacklist_lines as $line) {
        $f_r = explode("=",trim($line));
        $field = $f_r[0];
        if (trim($field)) {
            $f_r[1] = trim($f_r[1]);
            if($order[$field] && $f_r[1]) {
                $value = "/(.*)(".trim($f_r[1]).")(.*)/i";
                if(preg_match($value, $order[$field], $matches)) {
                    $is_in_blacklist = true;
                    $blacklist_tips = $matches[2];
                    break;
                }
            }
        }
    }

    return array('is_in_blacklist' => $is_in_blacklist, 'blacklist_tips' => $blacklist_tips);
}

/**
 * 裁剪字符
 *
 * @param string $string
 * @param int $length
 * @param string $dot
 * @return string
 */
function cutstr($string, $length, $dot = ' ...') {
    $charset = 'utf-8';

    if(strlen($string) <= $length) {
        return $string;
    }

    $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);

    $strcut = '';
    if(strtolower($charset) == 'utf-8') {

        $n = $tn = $noc = 0;
        while($n < strlen($string)) {

            $t = ord($string[$n]);
            if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1; $n++; $noc++;
            } elseif(194 <= $t && $t <= 223) {
                $tn = 2; $n += 2; $noc += 2;
            } elseif(224 <= $t && $t < 239) {
                $tn = 3; $n += 3; $noc += 2;
            } elseif(240 <= $t && $t <= 247) {
                $tn = 4; $n += 4; $noc += 2;
            } elseif(248 <= $t && $t <= 251) {
                $tn = 5; $n += 5; $noc += 2;
            } elseif($t == 252 || $t == 253) {
                $tn = 6; $n += 6; $noc += 2;
            } else {
                $n++;
            }

            if($noc >= $length) {
                break;
            }

        }
        if($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);

    } else {
        for($i = 0; $i < $length - strlen($dot) - 1; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
        }
    }

    $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

    return $strcut.$dot;
}

/**
 * 判断商品大的分类
 *
 * @access  public
 * @param   int     $top_cat_id 顶级分类
 * @param   int     $cat_id 分类
 * @return  int     $real_cat_id 真实的分类 1 手机， 2 手机配件， 3 小家电， 4 DVD
 */
function get_real_cat_id($top_cat_id, $cat_id = 0) {
    if($top_cat_id == 1) return 1;
    if($top_cat_id == 597) return 2;
    if(!in_array($top_cat_id, array(1, 597, 1109)) && $cat_id != 1157 ) return 3;
    if($cat_id == 1157) return 4;
}

/**
 * 给出商品的分类名
 *
 * @access  public
 * @param   int     $real_cat_id 分类
 * @return  string     $real_cat_id 真实的分类名
 */
function get_real_cat_name($real_cat_id) {
    static $cat = array(1 => '手机', 2 => '手机配件', 3 => '小家电', 4 => 'DVD', );
    $cat_id = intval($cat_id);
    return $cat[$real_cat_id];
}


/**
 * 返回订单最核心的信息，之前的getorderinfo返回的信息太多。
 *  
 * @param string $order_sn
 * @return array
 */
function get_core_order_info($order_sn, $order_id = null) {
    global $db, $ecs;

    $cond = party_sql('o.party_id') .' AND ';

    if (trim($order_sn) != '') {
        $cond = "o.order_sn = '{$order_sn}' ";
    } else {
        $order_id = intval($order_id);
        $cond = "o.order_id = '{$order_id}' ";
    }
    $sql = "SELECT o.*, p.pay_name, p.pay_code, p.is_cod, s.shipping_name
            FROM {$ecs->table('order_info')} o
            LEFT JOIN {$ecs->table('payment')} p ON o.pay_id = p.pay_id
            LEFT JOIN {$ecs->table('shipping')} s ON o.shipping_id = s.shipping_id
            WHERE {$cond} LIMIT 1
            ";
    $order = $db->getRow($sql);

    if(!$order) return false;
    $sql = "SELECT og.*, g.top_cat_id, g.cat_id, gs.internal_sku,si.shipping_invoice,pm.product_id,if(gs.barcode is null or gs.barcode='',g.barcode,gs.barcode) as barcode
            FROM {$ecs->table('order_goods')} AS og
              LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
              LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
              LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
              LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
              WHERE og.order_id = '{$order['order_id']}' 
            GROUP BY og.rec_id";
    $order_goods = $db->getAll($sql);
    $order['order_goods'] =$order_goods;
    
    $total_goods_number = 0;
    if(!empty($order_goods)) {
        foreach($order_goods as $order_good) {
            $total_goods_number += $order_good['goods_number'];
        }
    }

    $order['total_goods_number'] = $total_goods_number;
        
    $sql = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order['order_id']}' ORDER BY action_time ";
    $order_action = $db->getAll($sql);
    $order['order_action'] = $order_action;
    $order_info_str = @serialize($order);
    $order['order_info_md5'] = md5($order_info_str);
    return $order;
}
/**
 * 返回订单复核需要的信息，之前的getorderinfo返回的信息太多。
 *  
 * @param string $order_sn
 * @return array
 */
function get_core_order_info_base($order_sn, $order_id = null) {
    global $db, $ecs;

    if (trim($order_sn) != '') {
        $cond = "o.order_sn = '{$order_sn}' ";
    } else {
        $order_id = intval($order_id);
        $cond = "o.order_id = '{$order_id}' ";
    }
    $sql = "SELECT o.order_id,o.facility_id,o.order_sn,o.consignee,o.province,o.city,o.district,o.address, p.pay_name, p.pay_code, p.is_cod, s.shipping_name,rs.tracking_number 
            FROM {$ecs->table('order_info')} o
            LEFT JOIN {$ecs->table('payment')} p ON o.pay_id = p.pay_id
            LEFT JOIN {$ecs->table('shipping')} s ON o.shipping_id = s.shipping_id 
            LEFT JOIN romeo.order_shipment ros ON ros.order_id = convert(o.order_id using utf8)
            LEFT JOIN romeo.shipment rs ON rs.shipment_id = ros.shipment_id
            WHERE {$cond} LIMIT 1
            ";
    $order = $db->getRow($sql);

    if(!$order) return false;
    
    $sql =" SELECT og.rec_id,og.goods_name,og.goods_number,-sum(iid.quantity_on_hand_diff) as out_number,og.goods_id,og.style_id,og.status_id, 
                g.top_cat_id, g.cat_id, gs.internal_sku,si.shipping_invoice
            FROM {$ecs->table('order_goods')} AS og
              inner join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
              LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
              LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
              LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
              WHERE og.order_id = '{$order['order_id']}' 
            GROUP BY og.rec_id";
    $order_goods = $db->getAll($sql);
    foreach($order_goods as $key => $good){
         $name = str_replace(array("\r\n", "\r", "\n","\\","\""), " ", $good['goods_name']); 
         $name = str_replace("'","\'",$name);
         $order_goods[$key]['goods_name']= $name;
    }

    $order['order_goods'] =$order_goods;
    return $order;
} 

/**
 * 获取历史最低价
 *
 * @param int $goods_id
 * @param int  $style_id
 * @param int  $goods_style_id
 * @param string $day
 * @return double $price
 */
function get_goods_history_min_price($goods_id, $style_id, $goods_style_id, $day) {
    global $db, $ecs;
    $sql = "SELECT min_price FROM {$ecs->table('history_goods_price')}
             WHERE goods_id = '{$goods_id}' AND goods_style_id = '{$goods_style_id}'
              AND price_date = '{$day}'
            LIMIT 1 ";
    $price = $db->getOne($sql);
    if ($price == null) {
        $price = get_goods_history_min_price_tracker($goods_id, $style_id, $goods_style_id, $day);
    }
    return $price;
}

/**
 * 获取当天最低价
 *
 * @param int $goods_id
 * @param int  $style_id
 * @param int  $goods_style_id
 * @param string $day
 * @return double $price
 */
function get_goods_history_min_price_tracker($goods_id, $style_id, $goods_style_id, $day) {
    global $db, $ecs;
    $sql = "SELECT MIN(PRICE) FROM PRICE_TRACKER.SHOP_PRICE
             WHERE STATUS = 'OK' AND DATE(APPROVED_DATETIME) = '{$day}' AND GOODS_ID = '{$goods_id}' AND goods_style_id = '{$goods_style_id}'
             GROUP BY GOODS_ID
             LIMIT 1 ";
    $price = $db->getOne($sql);
    if (!$price) {
        $shop_price = get_goods_history_price($goods_id, $style_id, $day.' 23:59:59');
        $price = $shop_price['PRICE'];
    }
    return $price;
}

/**
 * 通过shwo_message.php页面来实现弹出消息$info后跳转到$url页面
 *
 * @param string $info
 * @param string $back
 */
function alert_back($info, $url) {
    $info = urlencode($info);
    $url = urlencode($url);
    $back = WEB_ROOT . "admin/show_message.php?info={$info}&back={$url}";
    header("location: $back");
    return;
}

/**
 * 检验手机串号
 *
 * @param str $imei
 * @return boolean
 */
function chkimei($imei){
    $sum = 0; $mul = 2; $l = 14;
    for ($i = 0; $i < $l; $i++) {
        $digit = intval(sub_str($imei,$l-$i-1,1));
        $tp = intval($digit)*$mul;
        if ($tp >= 10){
            $sum += ($tp % 10) +1;
        }else{
            $sum += $tp;
        }
        if ($mul == 1){
            $mul++;
        }else{
            $mul--;
        }
    }
    $chk = ((10 - ($sum % 10)) % 10);
    if ($chk !=intval(sub_str($imei,-1,1))){
        return false;
    }else{
        return true;
    }
}

/**
 * 判断是否可以显示给当前用户供价
 *
 * @param string $goods_category_name 商品的分类名，通过func_get_goods_category_name取得
 *
 * @return boolean
 */
function view_provide_price($goods_category_name)
{
    // 电教品需要有“cg_edu_check_cost”权限
    // DVD产品需要有“cg_dvd_check_cost”权限
    // 一般商品需要有“cg_normal_check_cost”权限
    // 拥有finance_order（财务->订单管理）权限的人自动拥有所有查看供价的权限
    if (!check_admin_priv('finance_order') && (
        ($goods_category_name == '电教产品' && !check_admin_priv('cg_edu_check_cost')) ||
        ($goods_category_name == 'DVD' && !check_admin_priv('cg_dvd_check_cost')) ||
        ($goods_category_name != '电教产品' && $goods_category_name != 'DVD' && !check_admin_priv('cg_normal_check_cost'))
    )) {
        return false;
    }

    return true;
}

/**
 * 读取excel文件中的内容
 *
 * @param string $file   文件
 * @param array  $config 配置, 定义内容 :  array( 'sheet' => array('字段') )
 * @param string $ext    扩展名
 * @param array  $failed 错误提示
 *
 * @return false|array
 */
function excel_read($file, $config, $ext = null, & $failed = null, $ignoreNullCell=false)
{
    if (empty($config) || !is_array($config)) {
        $failed[] = '错误的excel配置信息';
        return false;
    }

    // 载入excel扩展
    set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    if ($ext == null) { $ext = pathinfo($file, PATHINFO_EXTENSION); }

    // 循环取得指定的表
    $sheets = array();
    try {
        $ext == 'xlsx' ?
            $reader = PHPExcel_IOFactory::createReader('Excel2007') :
            $reader = PHPExcel_IOFactory::createReader('Excel5') ;
        $reader->setReadDataOnly(true);  // 设置为只读

        $excel = $reader->load($file);
        foreach ($config as $name => $cells) {
            $sheets[$name] = is_int($name) ? $excel->getSheet($name) : $excel->getSheetByName($name) ;
            if (is_null($sheets[$name])) {
                $failed[] = '该excel文件中找不到['. $name .']表';
                break;
            }
        }
    } catch (Exception $e) {
        $failed[] = '读取文件内容失败，请检查该文件格式。详细错误消息:'. $e->getMessage();
    }

    if ($failed) { return false; }

    foreach ($sheets as $name => $sheet) {
        $cells = array_flip($config[$name]);  // 表格列集
        $rowset[$name] = $fields = array();
        $i = 0;

        foreach ($sheets[$name]->getRowIterator() as $rowIterator) {
            $cellIterator = $rowIterator->getCellIterator();
            //Added by Sinri to open filter for null-cell to make it faster while huge null rows comes. 20150819
            if(!$ignoreNullCell){
                $cellIterator->setIterateOnlyExistingCells(false);
            }
            
            // 取得列名
            if ($i == 0) {
                $j = 0;
                foreach ($cellIterator as $cell) {
                    $v = trim($cell->getValue());
                    if (!empty($v)) { $fields[$j] = $v; }  // 空列不取数据
                    $j++;
                }
                // 检查检索出的字段和我们需要的字段是否有出入
                $diff = array_diff(array_keys($cells), $fields);
                if (!empty($diff)) {
                    $failed[] = "[{$name}]表中不存在列:" . implode(',', $diff);
                    break;
                }
                if (count($fields) != count(array_unique($fields))) {
                    $failed[] = "[{$name}]表中存在重复的列名";
                }
            }
            // 取得列值
            else {
                $row = array();
                $empty = true;  // 是否是空行
                $j = 0;
                foreach ($cellIterator as $cell) {
                    // $v = trim($cell->getValue());
                    // Sinri commented the above and add the below for Input V. 20150819
                    
                    if (!is_null($cell)) {
                        $v = trim($cell->getValue());
                    }else{
                        $v='';
                    }

                    if(is_numeric($v) && ceil($v) != $v){
                        $v = number_format($v, 6, '.', '');
                    }
                    if (isset($fields[$j]) && isset($cells[$fields[$j]])) {
                        $row[$cells[$fields[$j]]] = $v;
                        if (!empty($v)) { $empty = $empty && false; }
                    }
                    $j++;
                }

                if (!$empty) $rowset[$name][] = $row;  // 过滤空行
            }
            $i++;
        }
    }

    return $rowset;
}

/**
 * excel导出模板
 * ljzhou 2014-8-20
 */
function excel_export_model($title, $filename, $data, $type, $sheetname="")
{
    set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    if (!empty($data)) {
        $sheet->fromArray($data, null, '1', $type);
    }
    if(!empty($sheetname)) {
        $sheet->setTitle($sheetname);
    } else {
        $sheet->setTitle('批量导入');
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
}

/**
 * 得到直辖市的id
 */
function get_zhixiashi_region_ids() {
    $zhixiashi = array('2','3','10','23');
    
    return $zhixiashi;
}
/**
 * 得到组织的仓库列表 和 用户的仓库列表 的交集
 */
function get_user_party_facility_ids() {
    require_once(ROOT_PATH . 'admin/includes/lib_main.php');
    
    $facility_ids = array();
    $user_facilitys = get_user_facility();
    if(!empty($user_facilitys)) {
       $facility_ids = array_intersect_assoc(get_available_facility(),$user_facilitys);
    } else {
       $facility_ids = get_available_facility();
    }
    
    return $facility_ids;
}   

/**
 * 取得所有 carrier
 * @author Zandy
 * @date 2010.12
 */
function get_carrier_list() {
    $db = $GLOBALS['db'];
    $sql = "SELECT * FROM ecshop.ecs_carrier WHERE 1";
    return $db->getAll($sql);
}






/**
 * 返回两个时间的时间差
 * @param int $base
 * @param string $datetime
 *
 * @return string $diff_str
 */
function datetime_diff($base, $datetime)
{
    $datetime = strtotime($datetime);

    $interval = $datetime - $base;
    return convert_interval($interval);
}

/**
 * 返回两个时间的时间差
 * @param int $interval
 *
 * @return string $diff_str
 */
function convert_interval($interval) {
    $diff_str = '';

    $map = array(
        '86400' => ' days',
        '3600' => ' hours',
        '60' => ' mins',
        '1' => ' secs',
    );

    $level = 0;
    $_interval = abs(intval($interval));
    foreach ($map as $time_interval => $desc) {
        if ($_interval >= $time_interval) {
            $diff_str .= " " .floor($_interval / $time_interval) . $desc;
            $_interval = $_interval % $time_interval;

            $level ++;
            if ($level >= 2) {
                break;
            }
        }

    }

    return $interval < 0 ? '-' . $diff_str  : '+ ' . $diff_str;
}


/**
 * 获得订单的相关状态，并且合并相同状态
 * @param int $order_id
 * @return array $all_status
 */
function getOrderActionNote($order_id, $sql_add = '') {
    global $db;

    $order_id = intval($order_id);
    $sql = "select * from ecs_order_action where order_id = {$order_id} $sql_add ";
    $actions = $db->getAll($sql);

    $n = 0;
    $pre_status = '';
    // 相同的order status 放在一起，方便好看
    foreach ($actions as $key => &$v) {
        $last_action = $v['action_note'];
        $order_shipping_pay = $v['order_status'].'_'.$v['pay_status'].'_'.$v['shipping_status'];

        // 同样的 order_status pay_status shipping_status 先后出现，是不能连在一起的
        if ($pre_status != $order_shipping_pay) {
            $n ++;
        }

        $v['order_status_name'] = get_order_status($v['order_status']);
        $v['shipping_status_name'] = get_shipping_status($v['shipping_status']);
        $v['pay_status_name'] = get_pay_status($v['pay_status']);

        $all_status[$order_shipping_pay."_".$n][] = $v;

        $pre_status = $order_shipping_pay;
    }

    return $all_status;
}
function getOrderActionImportantNote($order_id)
{
    global $db;

    $order_id = intval($order_id);
    $sql = "select action_note from ecs_order_action where order_id = {$order_id} and `note_type` = 'SHIPPING' ";
    $actions = $db->getAll($sql);
    $r = array();
    if ($actions) {
        foreach ($actions as $v) {
            $r[] = $v['action_note'];
        }
    }

    return join(" ", $r);
}

/**
 * 获得请求页面的完整的url
 */
function getScriptUrl()
{
    // 获得脚本地址

    $rewriteUrl = $_SERVER['SCRIPT_URI'];

    // nginx 转发过来的SCRIPT_URI不正确  by zwsun

    if (substr($_SERVER['HTTP_HOST'], -4) == ':443') {
        $rewriteUrl = str_replace("http://" . $_SERVER['HTTP_HOST'], "https://" . substr($_SERVER['HTTP_HOST'], 0, -4), $rewriteUrl);
    }

    if (!$rewriteUrl) {
        $isHttps = isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on');
        if ($isHttps) {
            $rewriteUrl = "https://";
        } else {
            $rewriteUrl = "http://";
        }

        $rewriteUrl .= $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if (($port != 80 && !$isHttps) || ($port != 443 && $isHttps))
            $rewriteUrl .= ':' . $port;

        $scriptUrl = $_SERVER["SCRIPT_URL"];
        $scriptUrl || ($scriptUrl = $_SERVER["SCRIPT_NAME"]);
        $scriptUrl || ($scriptUrl = $_SERVER["PHP_SELF"]);
        $rewriteUrl = $rewriteUrl . $scriptUrl;
    }

    return $rewriteUrl;
}

/**
 * 取得指定在售商品列表
 *
 * @param string $keyword 关键字
 * @param int $limit 限定记录数
 *
 * @return array
 */
function get_goods_list_like($keyword = '', $limit = 100)
{
    $conditions = '';

    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND g.goods_name LIKE '%{$keyword}%'";
    }

    $sql = "
        SELECT
            g.goods_id, g.cat_id, gs.style_id,
            CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
        FROM
            {$GLOBALS['ecs']->table('goods')} AS g
            LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON gs.goods_id = g.goods_id
            left join {$GLOBALS['ecs']->table('style')} as s on gs.style_id = s.style_id
        WHERE
            ( g.is_on_sale = 1 AND g.is_delete = 0 ) AND ". party_sql('g.goods_party_id') ." {$conditions}
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}

/*
 * 取得批次号列表   (精确搜索，可更改为模糊)
 */
function get_batch_sns_like($keyword = '', $limit = 100)
{
    $conditions = '';

    if (trim($keyword)) {
        $conditions .= " AND batch_sn = '{$keyword}'";
    }

    $sql = "
        SELECT
           distinct batch_sn
        FROM
            romeo.inventory_item ii
            inner join romeo.product_mapping pm ON ii.product_id = pm.product_id
            inner join ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
        WHERE
            batch_sn is not null and ". party_sql('g.goods_party_id') ." {$conditions} 
        LIMIT {$limit}
    ";
//    echo ($sql);
    return $GLOBALS['db']->getAll($sql);
}

function get_providers_list_like($keyword = '', $limit = 100){

    $conditions = '';

    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND provider_name LIKE '%{$keyword}%'";
    }
    // 第三方供应商
    require_once(ROOT_PATH . 'admin/includes/lib_main.php');
    if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
        $sql = "select provider_id, provider_name from ecshop.ecs_provider WHERE provider_status = 1 {$conditions} and provider_id = '246' limit 1" ;
    } else {
        $sql = "select provider_id, provider_name from ecshop.ecs_provider WHERE provider_status = 1 {$conditions} ORDER BY `order` DESC, provider_id" ;
    }

    return $GLOBALS['db']->getAll($sql);
}


/*
 * 根据订单商品的质量 计算对应的快递费用
 */
 function get_order_shipping_fee($order) {

     $orderItemList = $order['goods_list'];
     if (empty($orderItemList)) {
         return ;
     }
     // 先过滤掉不用系统选择快递的业务
     if ('16' == $order['party_id']) {
         return ;
     }
     // 地址不全的订单 先不计算
     if ($order['province'] == '0' && $order['district'] == '0') {
         return ;
     }

     global $db;
     $goodsWeight = 0.00 ;
     $goodsVolume = 0.00 ;
     $shippingRegionId = 0 ;
     $arrivedMap = array('ALL' => '全境到达', 'PARTLY' => '部分可达', 'NONE' => '不可达');
     foreach ($orderItemList as $item) {
         // 如果订单中商品有质量没有维护的，就不再作筛选
         if ($item['goods_weight'] == 0) {
             return ;
         }

         $goodsWeight += $item['goods_weight'] * $item['goods_number'];
         $goodsVolume += $item['goods_volume'] * $item['goods_number'];
     }

     if ($order['district'] != '0') {
         $shippingRegionId = $order['district'] ;
     } elseif ($order['district'] == '0' && $order['city'] != '0') {
         $shippingRegionId = $order['city'] ;
     } else {
         $shippingRegionId = $order['province'] ;
     }

     // 同一个仓库，逻辑仓归一个计算
     $facilityId = facility_convert($order['facility_id']);
     // 外加上外包装材料质量0.5KG
     $package_weight = get_package_weight($order);
    $goodsWeight = $goodsWeight + $package_weight ;
    // 比较体积（cm3）、与重量（g）  取其大值   体积/6000 => 重量
    // 由于每个订单的包装前重量小于包装后重量重量，筛选时，可以在包装前重量中加0.5KG（多美滋纸箱为0.5KG）作为判断条件
    if (($goodsVolume / 6000) >= $goodsWeight) {
        $goodsWeight = $goodsVolume / 6000 ;
    }
    //获取所有仓库
    $facility_sql = "select facility_id from romeo.facility";
    $facility_list = $db->getAll($facility_sql);
    $sql = "select s.shipping_name, r.region_id, r.arrived, a.shipping_id,  c.first_weight, c.first_fee, c.continued_fee,ass.facility_id,f.facility_name
     from ecshop.ecs_area_region r
       left join ecshop.ecs_shipping_area a on r.shipping_area_id = a.shipping_area_id
       left join ecshop.ecs_carriage c on a.shipping_id = c.carrier_id
       left join ecshop.ecs_shipping s on a.shipping_id = s.shipping_id
       left join ecshop.ecs_party_assign_shipping ass on s.shipping_id = ass.shipping_id
       left join romeo.facility f on f.facility_id = ass.facility_id
     where r.region_id = %d
       and c.facility_id = '%s' and c.region_id = %d
       and s.support_cod = 0 and s.support_no_cod = 1
       and ass.party_id = %d and ass.facility_id = '%s' and ass.enabled = 1 " ;

       $shippingFee = 0.0 ;
       $shippingList = array() ;
       //获取所有选择最优快递的仓库的快递费
       if($order['party_id'] == 65558 ){
            foreach ($facility_list as $facility){
                $shippingInfo= $db->getAll(sprintf($sql,
                    $shippingRegionId, facility_convert($facility['facility_id']), $shippingRegionId, $order['party_id'], $facility['facility_id']));
                if(count($shippingInfo) > 0 ){
                    $shippingInfos[] = $shippingInfo;
                }
            }
       }else{
            $shippingInfo= $db->getAll(sprintf($sql,
                    $shippingRegionId, $facilityId, $shippingRegionId, $order['party_id'], $order['facility_id']));
            $shippingInfos[] = $shippingInfo;
       }

    if($shippingInfos != null){
        foreach ($shippingInfos as $shippingInfo) {
            foreach ($shippingInfo as $shipping){
                $shippingItem = array() ;
                $shippingItem['shipping_name'] = $shipping['shipping_name'] ;
                $shippingItem['arrived'] = $arrivedMap[$shipping['arrived']] ;
                $shippingItem['facility_name'] = $shipping['facility_name'];
                $shippingItem['facility_id'] = $shipping['facility_id'];
                $shippingItem['shipping_id'] = $shipping['shipping_id'];
                // 计算快递费用
                $shipping_weight = get_weight($goodsWeight, $shipping);
                $shippingFee = $shipping['first_fee'] + $shipping_weight * $shipping['continued_fee'];
                $shippingItem['shipping_fee'] = $shippingFee ;
                $shippingItem['goods_forcast_weight'] = $goodsWeight;
                $shippingList[] = $shippingItem ;
            }

        }
    }
    //排序快递参考列表
    for( $i = 0 ; $i < count($shippingList) ; $i++){
        for($j = 0 ; $j < count($shippingList) - $i-1; $j++) {
            if((double)$shippingList[$j]['shipping_fee'] > (double)$shippingList[$j+1]['shipping_fee']){

                $temp = $shippingList[$j] ;
                $shippingList[$j] = $shippingList[$j+1];
                $shippingList[$j+1] = $temp;
            }
        }

    }
    return $shippingList ;
 }

 /*
 * 展示快递：根据订单商品的质量 计算对应的快递费用    ljzhou 2013.04.25
 */
 function get_order_show_shipping_fee($order) {
     $orderItemList = $order['goods_list'];
     if (empty($orderItemList)) {
         return ;
     }
     // 先过滤掉不用系统选择快递的业务
     if ('16' == $order['party_id']) {
         return ;
     }
     // 地址不全的订单 先不计算
     if ($order['province'] == '0' && $order['district'] == '0') {
         return ;
     }
     global $db;
     $goodsWeight = 0.00 ;
     $goodsVolume = 0.00 ;
     $shippingRegionId = 0 ;
     $arrivedMap = array('ALL' => '全境到达', 'PARTLY' => '部分可达', 'NONE' => '不可达');
     foreach ($orderItemList as $item) {
         // 如果订单中商品有质量没有维护的，就不再作筛选
         if ($item['goods_weight'] == 0) {
             return ;
         }

         $goodsWeight += $item['goods_weight'] * $item['goods_number'];
         $goodsVolume += $item['goods_volume'] * $item['goods_number'];
     }

     if ($order['district'] != '0') {
         $shippingRegionId = $order['district'] ;
     } elseif ($order['district'] == '0' && $order['city'] != '0') {
         $shippingRegionId = $order['city'] ;
     } else {
         $shippingRegionId = $order['province'] ;
     }

     // 同一个仓库，逻辑仓归一个计算
     $facilityId = facility_convert($order['facility_id']);
     // 外加上外包装材料质量0.5KG
     $package_weight = get_package_weight($order);
     $goodsWeight = $goodsWeight + $package_weight ;
    // 比较体积（cm3）、与重量（g）  取其大值   体积/6000 => 重量
    // 由于每个订单的包装前重量小于包装后重量重量，筛选时，可以在包装前重量中加0.5KG（多美滋纸箱为0.5KG）作为判断条件
    if (($goodsVolume / 6000) >= $goodsWeight) {
        $goodsWeight = $goodsVolume / 6000 ;
    }
    // 获取该组织的展示快递
    $show_shipping_list =  get_show_shipping_list ($order['party_id']);
    if(empty($show_shipping_list)) {
        return null;
    }
    //获取所有仓库
    $facility_sql = "select facility_id from romeo.facility";
    $facility_all_list = $db->getAll($facility_sql);
    // 获取映射后的仓库
    $facility_list = get_real_facility_list($facility_all_list);
    $sql = "select s.shipping_name, r.region_id, r.arrived, a.shipping_id,  c.first_weight, c.first_fee, c.continued_fee, c.facility_id, f.facility_name
       from
       ecshop.ecs_party_assign_show_shipping ss
       left join ecshop.ecs_carriage c on ss.shipping_id = c.carrier_id
       left join ecshop.ecs_shipping_area a on a.shipping_id = ss.shipping_id
       left join ecshop.ecs_area_region r on r.shipping_area_id = a.shipping_area_id
       left join ecshop.ecs_shipping s on ss.shipping_id = s.shipping_id
       left join romeo.facility f on f.facility_id = c.facility_id
       where r.region_id = %d
       and c.facility_id = '%s' and c.region_id = %d
       and s.support_cod = 0 and s.support_no_cod = 1 and ss.party_id = %d
       group by a.shipping_id";

       $shippingFee = 0.0 ;
       $shippingList = array() ;
       //获取所有选择最优快递的仓库的快递费
       if($order['party_id'] == 65558 ){
            foreach ($facility_list as $facility){
                $shippingInfo= $db->getAll(sprintf($sql,
                    $shippingRegionId, $facility, $shippingRegionId, $order['party_id']));
                if(count($shippingInfo) > 0 ){
                    $shippingInfos[] = $shippingInfo;
                }
            }
       }else{
            $shippingInfo= $db->getAll(sprintf($sql,
                    $shippingRegionId, $facilityId, $shippingRegionId, $shippingRegionId, $order['party_id']));

            $shippingInfos[] = $shippingInfo;
       }
    if($shippingInfos != null){
        foreach ($shippingInfos as $shippingInfo) {
            foreach ($shippingInfo as $shipping){
                $shippingItem = array() ;
                $shippingItem['shipping_name'] = $shipping['shipping_name'] ;
                $shippingItem['arrived'] = $arrivedMap[$shipping['arrived']] ;
                $shippingItem['facility_name'] = $shipping['facility_name'];
                $shippingItem['facility_id'] = $shipping['facility_id'];
                $shippingItem['shipping_id'] = $shipping['shipping_id'];
                // 计算快递费用

                $shipping_weight = get_weight($goodsWeight, $shipping);


                $shippingFee = $shipping['first_fee'] + $shipping_weight * $shipping['continued_fee'];
                $shippingItem['shipping_fee'] = $shippingFee ;
                $shippingItem['goods_forcast_weight'] = $goodsWeight;
                $shippingList[] = $shippingItem ;
            }

        }
    }
    //排序快递参考列表
    for( $i = 0 ; $i < count($shippingList) ; $i++){
        for($j = 0 ; $j < count($shippingList) - $i-1; $j++) {
            if((double)$shippingList[$j]['shipping_fee'] > (double)$shippingList[$j+1]['shipping_fee']){

                $temp = $shippingList[$j] ;
                $shippingList[$j] = $shippingList[$j+1];
                $shippingList[$j+1] = $temp;
            }
        }

    }
    return $shippingList ;
 }

 /**
  * 根据订单取得订单重量（不含耗材）
  * ljzhou 2012.10.23
  */
 function get_order_weight($order) {
     $orderItemList = $order['goods_list'];
     if (empty($orderItemList)) {
         return 0;
     }

     $goodsWeight = 0.00 ;
     $goodsVolume = 0.00 ;
     foreach ($orderItemList as $item) {
         // 如果订单中商品有质量没有维护的，就不再作筛选
         if ($item['goods_weight'] == 0) {
             QLog::log("weight_check_log_error:get_order_weight:".$item['goods_id']." ".$item['goods_name']."重量没有维护 ");
             return 0;
         }
         $goodsWeight += $item['goods_weight'] * $item['goods_number'];
         $goodsVolume += $item['goods_volume'] * $item['goods_number'];
     }

    // 比较体积（cm3）、与重量（g）  取其大值   体积/6000 => 重量
    if (($goodsVolume / 6000) >= $goodsWeight) {
        $goodsWeight = $goodsVolume / 6000 ;
    }
    return $goodsWeight;
 }

  /**
  * 根据组织取得称重误差
  * ljzhou 2012.10.23
  */
 function get_error_weight($party_id) {
    $default_error_weight = 100000;
    // 需要填充物的组织，默认600g
    $filler_need_config = array (65553, 65539, 65569, 65568, 65556 ); // 雀巢、贝亲、安满、欧世蒙牛、皇冠

    // 不需要填充物的组织，默认300g
    $filler_not_need_config = array (16, 65558, 65559, 65572, 65574, 65562, 65546, 65552, 65555, 65551 ); // 乐其电教、金佰利、每伴、玛氏、金宝贝、ECCO、ACC、阳光豆坊、GALLO、保乐力加

    $party_id = intval ( $party_id );
    if (in_array ( $party_id, $filler_need_config )) {
        $default_error_weight = 100000;
    } elseif (in_array ( $party_id, $filler_not_need_config )) {
        $default_error_weight = 100000;
    }

    return $default_error_weight;
 }

 /**
  * 判断一个运单对应 多个订单,并返回订单信息
  * ljzhou 2012.12.06
  */
 function get_shipment_orders($tracking_number) {
    global $db;
    $sql = "SELECT os.order_id
            FROM romeo.shipment s
            LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
            WHERE s.tracking_number = '{$tracking_number}'
            GROUP BY os.order_id";
    $orderId_list = $db->getCol ( $sql );
    return $orderId_list;
 }

 /**
  * 判断一个订单对应 多个运单,并返回运单信息
  * ljzhou 2012.12.06
  */
 function get_order_shipments($tracking_number) {
    global $db;
    $sql = "SELECT ss.primary_order_id,ifnull(ss.SHIPPING_LEQEE_WEIGHT,0.0000) as SHIPPING_LEQEE_WEIGHT
                FROM romeo.shipment s
                LEFT JOIN romeo.order_shipment os ON s.primary_order_id = os.order_id
                LEFT JOIN romeo.shipment ss ON os.shipment_id = ss.shipment_id
                WHERE s.tracking_number = '{$tracking_number}'
                GROUP BY ss.shipment_id";
    $shipment_list = $db->getAll ( $sql );
    return $shipment_list;
 }

 /**
  * 判断一个运单对应 多个订单
  */
 function check_shipment_orders($tracking_number) {
    global $db;
    $sql = "SELECT os.order_id
            FROM romeo.shipment s
            LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
            WHERE s.tracking_number = '{$tracking_number}'
            GROUP BY os.order_id";
    $orderId_list = $db->getCol ( $sql );
    if(count($orderId_list) > 1) {
        return true;
    }
    return false;
 }

 /**
  * 判断一个订单对应 多个运单
  */
 function check_order_shipments($tracking_number) {
    global $db;
    $sql = "SELECT ss.primary_order_id
                FROM romeo.shipment s
                LEFT JOIN romeo.order_shipment os ON s.primary_order_id = os.order_id
                LEFT JOIN romeo.shipment ss ON os.shipment_id = ss.shipment_id
                WHERE s.tracking_number = '{$tracking_number}'
                GROUP BY ss.shipment_id";
    $shipment_list = $db->getCol ( $sql );
    if(count($shipment_list) > 1) {
        return true;
    }
    return false;
 }
/**
 * 取每个仓库对应的每个快递方式的续重
 * */
 function get_weight($weight, $item){
    $weight = $weight/1000;
    $item['facility_id'] = facility_convert($item['facility_id']);
    switch ($item['shipping_id']){
        case ZHONGTONG:
            $weight = floor(($weight)*10)/10;
            if($item['first_weight'] == 0.5){
                $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/ 1.0));
            }
            else{
                $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/$item['first_weight']));
            }
            break;
        case HUITONG:
            $shipping_weight = max ( 0, ceil($weight) - $item['first_weight'] );
            break;
        case YUNDA:
            $shipping_weight = max(0, ceil($weight*2)/2 -$item['first_weight']);
            break;
        case EYOUBAO:
            $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/$item['first_weight']));
            break;
        case EMS_LAND:
            $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/$item['first_weight']));
            break;
        case YUANTONG:
            if('19568548' == $item['facility_id']){
                $shipping_weight = max (0,ceil($weight * 2)/2 - $item['first_weight']);
            }else{
                $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/$item['first_weight']));
            }
            break;
        case ZHAIJISONG:
            $shipping_weight = max(0, ceil($weight*2)/2 -$item['first_weight']);
            break;
        case SHENTONG:
            if('19568549' == $item['facility_id']||
                 '12768420'==$item['facility_id']||
                 '19568548' == $item['facility_id']){
                $shipping_weight = max (0,ceil($weight * 2)/2 - $item['first_weight']);
            }
            else{
                $shipping_weight = max(0, ((ceil($weight) - $item['first_weight'])/$item['first_weight']));
            }
            break;
        case TIANTIAN:
            $shipping_weight = max(0, ceil($weight*2)/2 -$item['first_weight']);
            break;
        case EMS://特殊的，与其他快递不同
            $shipping_weight = max ( 0, ceil ( ($weight - $item['first_weight'] ) / $item['first_weight'] ) );
            break;
        case SHUNFENG:
        case SHUNFENG_LAND:
            $shipping_weight = max (0,ceil($weight * 2)/2 - $item['first_weight']);
            break;
        case XIAOBAO:
            if($weight<=1.3){
                $weight = 1;
            }
            elseif($weight<=2.3){
                $weight= 2;
            }
            elseif($weight<=3.3){
                $weight= 3;
            }
            else{
                $weight = 10000;
            }
            $shipping_weight = max(0,($weight -$item['first_weight']) / $item['first_weight']);
            break;
    }
    return $shipping_weight;
 }
 function get_package_weight($order){
    global $db;
    $package_weight = 500;
    if( '65571'== $order['party_id']){//blackmores
        $package_weight = 240;
    }
    elseif ('65555'== $order['party_id']){//gallo
        $package_weight = 263;
    }
    elseif ('65539'== $order['party_id']){//贝亲
        $package_weight = 350;
    }
    elseif ('65569'== $order['party_id']){//安满
        $package_weight = 340;
    }
    elseif ('65547'== $order['party_id']){//金其仕
        $package_weight = 350;
    }
    elseif ('65556'== $order['party_id']){//皇冠
        $package_weight = 300;
    }
    elseif ('65552'== $order['party_id']){//阳光豆坊
        $package_weight = 500;
    }
    elseif ('16'== $order['party_id']){//电教
        $num1= 0;//学习机，复读机，词典的数量
        $num3= 0;//点读机,学习电脑数量(不包括T1)
        $num4= 0;//点读机T1数量

            $sql = "select goods_id, goods_name, goods_number
                        from ecshop.ecs_order_goods
                        where order_id = '{$order['order_id']}'
            ";
            $res_list = $db->getAll($sql);
            foreach ($res_list as $key=>$res){
                $exist1 = explode("学习机",$res['goods_name']);
                $exist2 = explode("复读机",$res['goods_name']);
                $exist3 = explode("词典",$res['goods_name']);
                $exist4 = explode("点读机",$res['goods_name']);
                $exist5 = explode("学习电脑",$res['goods_name']);
                if(count($exist1)>1||count($exist2)>1||count($exist3)>1){
                    $num1 += $res['goods_number'];
                }
                elseif(count($exist4)>1||count($exist5)>1){
                    if($res['goods_id']=='134200'||$res['goods_id']=='166068'){
                        $num4 +=$res['goods_number'];//T1
                    }
                    else{
                        $num3 += $res['goods_number'];
                    }

                }

            }
            if($num1<4&&$num1>0&&$num3 == 0&&$num4 == 0){//1-3台复读机，电子词典，学习机用最小号纸箱，耗材重量：0.25KG?
                $package_weight = 250;
            }
            elseif($num1==4&&$num3 == 0&&$num4 == 0){//任意4台复读机，电子词典，学习机 用最中号纸箱，耗材重量：0.45KG
                $package_weight = 450;
            }
            elseif($num1<7&&$num1>4&&$num3 == 0&&$num4 == 0){//任意5-6台复读机，电子词典，学习机用大号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif($num1>6&&$num3 == 0&&$num4 == 0){//超过6台复读机，电子词典，学习机用工厂装机器纸箱，耗材重量0.77KG
                $package_weight = 770;
            }
            elseif($num1==0&&$num4==0&&$num3<4&&$num3>0){//1－3台点读机（除点读机T1），学习电脑用中号纸箱，耗材重量：0.45KG
                $package_weight = 450;
            }
            elseif($num1==0&&$num4==0&&$num3==4){//任意4台点读机（除点读机T1），学习电脑用大号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif($num1==0&&$num4==0&&$num3>4){//大于4台点读机（除点读机T1），学习电脑用工厂装机器纸箱，耗材重量：1.1KG
                $package_weight = 1100;
            }
            elseif($num1==0&&$num3==0&&$num4<4&&$num4>0){//1-3台点读机T1用大号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif($num1==0&&$num3==0&&$num4>3){//大于3台点读机T1用工厂装机器纸箱，耗材重量：大于1.1KG
                $package_weight = 1100;
            }
            elseif($num1<3&&$num1>0&&$num4==0&&$num3==1){//1-2台复读机，电子词典，学习机＋1台点读机（除点读机T1）用中号纸箱，耗材重量：0.45KG
                $package_weight = 450;
            }
            elseif($num1<3&&$num1>0&&($num4==1||$num3==1)){//1-2台复读机，电子词典，学习机＋1台点读机（包含点读机T1）用中号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif($num1<3&&$num1>0&&($num4==2||$num3==2)){//1-2台复读机，电子词典，学习机＋2台点读机（包括点读机T1）用大号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif($num1<5&&$num1>2&&($num4==1||$num3==1)){//3-4台复读机，电子词典，学习机＋1台点读机（包括点读机T1）用大号纸箱，耗材重量：0.6KG
                $package_weight = 600;
            }
            elseif ($num1>4&&($num4==2||$num3==2)){//大于3-4台复读机，电子词典，学习机＋2台点读机（包括点读机T1）用工厂装机纸箱，耗材重量：1.1KG
                $package_weight = 1100;
            }
    }
    elseif ('65568'== $order['party_id']){//蒙牛
        $num_menniu = 0;
        $packed = array('140064','140061','120602','120604','120603','120571','120570','137649','120573','120572','120581','120585','120583','120582');
        $canned = array('120566','120569','120568','120605','120607','120606','120578','120574','120580','120579','120586','120601','120600','120591');
        $sql = "select goods_id, goods_name, goods_number
        from ecshop.ecs_order_goods
        where order_id = '{$order['order_id']}'
        ";
        $res_list = $db->getAll($sql);
        foreach ($res_list as $key =>$res){
            if(in_array($res['goods_id'],$packed)){
                $num_menniu += $res['goods_number']/2;
            }
            elseif(in_array($res['goods_id'],$canned)){
                $num_menniu += $res['goods_number'];
            }
        }
        if($num_menniu<=4){
            $package_weight =300;
        }
        elseif($num_menniu>4&&$num_menniu<=6){
            $package_weight = 500;
        }
        elseif($num_menniu>6){
            $package_weight = 700;
        }
    }
    elseif ('65553'== $order['party_id']){//雀巢
        $package_weight =500;
    }
    elseif ('65540'== $order['party_id']){//多美滋
        $num_duomeizi = 0;
        $sql = "select goods_id, goods_name, goods_number
        from ecshop.ecs_order_goods
        where order_id = '{$order['order_id']}'
        ";
        $res_list = $db->getAll($sql);
        foreach ($res_list as $key =>$res){
            $num_duomeizi += $res['goods_number'];
        }
        $package_weight = $num_duomeizi*180;
    }
    elseif ('65546'== $order['party_id']){//acc
            $big_bag = array('93805','93808','98674','98675','98676','98677','98678','98679','98681','98682','98685','98686','98693','98696','98698','98700','98703','98705','98708','98712',
                                        '98716','109413','116859','116895','116897','167300','167308','167310','167312','167313','167314','167315','167319','167320','167321','167322','167323','167324','167325','167329','167332',
                                        '167336','169502','169503','169504','169505','169506','169507');
            $num_bag = 0;
            $sql = "select go.goods_id, go.goods_name, go.goods_number, g.cat_id
                        from ecshop.ecs_order_goods go
                        left join ecshop.ecs_goods g on g.goods_id = go.goods_id
                        where order_id = '{$order['order_id']}'
            ";
            $res_list = $db->getAll($sql);

            foreach ($res_list as $key =>$res){
                if(in_array($res['goods_id'],$big_bag)){
                    $package_weight = 700;

                    break;
                }
                elseif($res['cat_id'] == '2322'){
                    $num_bag += $res['goods_number'];
                }
            }

            if($package_weight==500){//没有大包

                if($num_bag>2){
                    $package_weight = 700;
                }
                else{
                    $package_weight = 200;
                }
            }

    }
    elseif ('65558'== $order['party_id']){//金佰利
        $num_jinbaili = 0;
        $e_business_bag = array('78023','78024','78025','78026','78059','78060','78063');//商品名称包含“电商箱装”的商品
        $e_num = 0;//商品名称包含“电商箱装”的商品的数量
        $g_num = 0;//商品名称包含“好奇,高洁丝”,"礼盒"的商品的数量
        $sql = "select goods_id, goods_name, goods_number
        from ecshop.ecs_order_goods
        where order_id = '{$order['order_id']}'
        ";
        $res_list = $db->getAll($sql);
        foreach ($res_list as $key=>$res){
            if(in_array($res['goods_id'],$e_business_bag)){
                $e_num+=$res['goods_number'];
                $num_jinbaili+=$res['goods_number'];
            }
            elseif((strpos($res['goods_name'],"好奇")||strpos($res['goods_name'],"高洁丝"))&&!strpos($res['goods_name'],"礼盒")){
                $g_num+=$res['goods_number'];
                $num_jinbaili+=$res['goods_number'];
            }
            else{
                $num_jinbaili+=$res['goods_number'];
            }
        }
        if($e_num==2&&$num_jinbaili==2){//当商品数量=2，且? 商品名称包含“电商箱装”，耗材重量x=20g；这类商品是以两包为一个单位卖出的
            $package_weight = 20;
        }
        elseif($g_num==1&&$num_jinbaili==1){
            $package_weight = 20;
        }
        else{
            $package_weight =500;
        }
    }
    elseif ('65559'== $order['party_id']){//每伴
        $package_weight =500;
    }
    return $package_weight;
 }
 function facility_convert($facilityId) {
     $facility_mapping = array (
                 '12768420' =>  '12768420',    //  怀轩上海仓
                 '19568548' =>  '19568548',    //  电商服务东莞仓
                 '3580047'  =>  '19568548',    //  乐其东莞仓
                 '49858449' =>  '19568548',    //  东莞乐贝仓
                 '19568549' =>  '19568549',    //  电商服务上海仓
                 '137059426'  =>  '19568549',       //上海精品仓
                 '3633071'  =>  '19568549',    //  乐其上海仓
                 '22143846' =>  '19568549',    //  乐其杭州仓
                 '22143847' =>  '19568549',    //  电商服务杭州仓
                 '24196974' =>  '19568549',    //  贝亲青浦仓
                 '42741887' =>  '42741887',    //  乐其北京仓
                 '76065524' =>'19568548',  //电商服务东莞仓2
                 '76161272 '=>'76161272',  //上海伊藤忠
             ) ;

     if (array_key_exists($facilityId, $facility_mapping)) {
         return $facility_mapping[$facilityId] ;
     } else {
         return $facilityId ;
     }

 }


function get_estimated_weight($order_id) {
     global $db;
     $ESTIMATE = 500;
     $sql = "
        SELECT      SUM(og.goods_number*g.goods_weight) weight
        FROM        ecshop.ecs_order_goods og
        LEFT JOIN   ecshop.ecs_goods g on g.goods_id = og.goods_id
        WHERE       og.order_id = '{$order_id}'
        GROUP BY    og.ORDER_ID
        LIMIT 1
     ";

     $weight = $db->getOne($sql);
     $weight = $weight + $ESTIMATE;
     return $weight;
}

function get_weighing_weight_by_shipment_id($shipment_id) {
     global $db;
     $ESTIMATE = 500;
     $sql = "
        SELECT      oi.order_id
        FROM        ecshop.ecs_order_info oi
        LEFT JOIN   romeo.order_shipment os on CAST( os.order_id AS UNSIGNED ) = oi.order_id
        WHERE       os.shipment_id = '{$shipment_id}'
     ";

     $weight = $db->getOne($sql);
}

function get_weighing_weight_by_order_id($order_id) {

}


/**
 * 配件入库
 *
 * @param string $order_id
 * @param string $input_number
 * @param boolean $is_print
 * @param array $info
 */
function check_in_fittings($order_id, $input_number = 0, $is_print=true,$serial_numbers=null) {
    global $db, $ecs, $inventory_status,$is_command;

    $info = array();

    $sql = "
        SELECT  boi.provider_id, boi.order_type,boi.facility_id,
        og.goods_id, og.style_id, og.goods_name,og.goods_number,
        oi.purchase_paid_amount, og.rec_id,
        (select ifnull(sum(iid.quantity_on_hand_diff),0) from romeo.inventory_item_detail iid WHERE convert(og.order_id using utf8) = iid.order_id) as in_number
        FROM ecshop.ecs_order_goods og
        LEFT JOIN romeo.purchase_order_info oi ON og.order_id = oi.order_id
        LEFT JOIN ecshop.ecs_batch_order_mapping om ON og.order_id = om.order_id
        LEFT JOIN ecshop.ecs_batch_order_info boi ON om.batch_order_id = boi.batch_order_id
        WHERE
            og.order_id = '{$order_id}'
        GROUP BY og.order_id
    ";
    $item_info = $db->getRow($sql);
    $count = $item_info['goods_number'] - $item_info['in_number'];
    if ($count == 0 || $count < intval($input_number)) {
        $info['res'] = 'fail';
        $info['back'] = '此次入库数量超过未入库数量';
        return $info;
    }
    //如果原有记录没有串号，新记录有串号：入库（正式库）
    $fromStatusId = '';
    $toStatusId = 'INV_STTS_AVAILABLE';
    // $inventory_status为commond专用，可入二手
    if($inventory_status == 'INV_STTS_USED') {
        $toStatusId = $inventory_status;
    }
    
    $goods_id = $item_info['goods_id'];
    $style_id = $item_info['style_id'];
    $goods_name = $item_info['goods_name'];
    $facility_id = $item_info['facility_id'];
    $value = '';
    $order_type = $item_info['order_type'];
    $purchase_paid_amount = $item_info['purchase_paid_amount'];
    $provider_id = $item_info['provider_id'];
    $order_goods_id = $item_info['rec_id'];
    if (!checkProvider($provider_id) || ($order_type != 'B2C' && $order_type != 'C2C' && $order_type != 'DX') ) {
        $info['res'] = 'fail';
        $info['back'] = "供应商或者订单类型信息不正确，无法入库，请联系采购！";
        return $info;
    }

    // by zwsun 2011年7月4日 统一放在一个service中调用,主要是为了更新工单时间
    if (!function_exists("createPurchaseAcceptAndTransfer")) {
        include_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
    }
    if($is_command) {
        $result = createPurchaseAcceptAndTransferCommand(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                $input_number,
                                $serial_numbers,
                                $order_type,
                                $order_id,
                                $fromStatusId,
                                $toStatusId,
                                $purchase_paid_amount,
                                $order_goods_id,
                                $facility_id,
                                $provider_id);
    } else {
        $result = createPurchaseAcceptAndTransfer(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                $input_number,
                                $serial_numbers,
                                $order_type,
                                $order_id,
                                $fromStatusId,
                                $toStatusId,
                                $purchase_paid_amount,
                                $order_goods_id,
                                $facility_id,
                                $provider_id);
    }

    if (!$result) {
        $info['res'] = 'fail';
        $info['back'] = '新库存入库失败，请联系erp组';
        return $info;
    }

    if ($is_print) {
        $label='';
        if(empty($serial_numbers)) {
            $label = addslashes($goods_name);
        }
        $code = encode_goods_id($goods_id, $style_id);
        $printer_id = $_REQUEST['printer_id'];
        if (!function_exists("print_product_code")) {
            include_once(ROOT_PATH . 'admin/includes/lib_product_code.php');
        }
        if (print_product_code($order_id, $code, $input_number, $goods_id, $printer_id, $label)) {
            $info['product_code_info'] = "该产品已打印条码";
        } else {
            $info['product_code_info'] = "打印条码数量为0";
        }

    }
    $info['res'] = "success";
    return $info;
}

/**
 * 配件入库
 *
 * @param string $order_id
 * @param string $input_number
 * @param boolean $is_print
 * @param array $info
 */
function check_in_fittings_v2($order_id,$rec_id, $input_number = 0, $is_print=true,$serial_numbers=null) {
    global $db, $ecs, $inventory_status,$is_command;

    $info = array();

    $sql = "
        SELECT  boi.provider_id, boi.order_type,boi.facility_id,
        og.goods_id, og.style_id, og.goods_name,og.goods_number,
        oi.purchase_paid_amount, og.rec_id,
        (select ifnull(sum(iid.quantity_on_hand_diff),0) from romeo.inventory_item_detail iid WHERE convert(og.order_id using utf8) = iid.order_id and convert(og.rec_id using utf8) = iid.order_goods_id ) as in_number
        FROM ecshop.ecs_order_goods og
        LEFT JOIN romeo.purchase_order_info oi ON og.order_id = oi.order_id
        LEFT JOIN ecshop.ecs_batch_order_mapping om ON og.order_id = om.order_id
        LEFT JOIN ecshop.ecs_batch_order_info boi ON om.batch_order_id = boi.batch_order_id
        WHERE
            og.order_id = {$order_id} and og.rec_id = {$rec_id}
        GROUP BY og.rec_id
	";
    $item_info = $db->getRow($sql);
    $count = $item_info['goods_number'] - $item_info['in_number'];
    if ($count == 0 || $count < intval($input_number)) {
        $info['res'] = 'fail';
        $info['back'] = '此次入库数量超过未入库数量'.$count.",".$input_number;
        return $info;
    }
    //如果原有记录没有串号，新记录有串号：入库（正式库）
    $fromStatusId = '';
    $toStatusId = 'INV_STTS_AVAILABLE';
    // $inventory_status为commond专用，可入二手
    if($inventory_status == 'INV_STTS_USED') {
    	$toStatusId = $inventory_status;
    }
    
    $goods_id = $item_info['goods_id'];
    $style_id = $item_info['style_id'];
    $goods_name = $item_info['goods_name'];
    $facility_id = $item_info['facility_id'];
    $value = '';
    $order_type = $item_info['order_type'];
    $purchase_paid_amount = $item_info['purchase_paid_amount'];
    $provider_id = $item_info['provider_id'];
    $order_goods_id = $item_info['rec_id'];
    if (!checkProvider($provider_id) || ($order_type != 'B2C' && $order_type != 'C2C' && $order_type != 'DX') ) {
        $info['res'] = 'fail';
        $info['back'] = "供应商或者订单类型信息不正确，无法入库，请联系采购！";
        return $info;
    }

    // by zwsun 2011年7月4日 统一放在一个service中调用,主要是为了更新工单时间
    if (!function_exists("createPurchaseAcceptAndTransfer")) {
    	include_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
    }
    if($is_command) {
	    $result = createPurchaseAcceptAndTransferCommand(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                $input_number,
                                $serial_numbers,
                                $order_type,
                                $order_id,
                                $fromStatusId,
                                $toStatusId,
                                $purchase_paid_amount,
                                $order_goods_id,
                                $facility_id,
                                $provider_id);
    } else {
	    $result = createPurchaseAcceptAndTransfer(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                $input_number,
                                $serial_numbers,
                                $order_type,
                                $order_id,
                                $fromStatusId,
                                $toStatusId,
                                $purchase_paid_amount,
                                $order_goods_id,
                                $facility_id,
                                $provider_id);
    }

    if (!$result) {
        $info['res'] = 'fail';
        $info['back'] = '新库存入库失败，请联系erp组';
        return $info;
    }

    if ($is_print) {
    	$label='';
    	if(empty($serial_numbers)) {
    		$label = addslashes($goods_name);
    	}
        $code = encode_goods_id($goods_id, $style_id);
        $printer_id = $_REQUEST['printer_id'];
        if (!function_exists("print_product_code")) {
        	include_once(ROOT_PATH . 'admin/includes/lib_product_code.php');
        }
        if (print_product_code($order_id, $code, $input_number, $goods_id, $printer_id, $label)) {
            $info['product_code_info'] = "该产品已打印条码";
        } else {
            $info['product_code_info'] = "打印条码数量为0";
        }

    }
    $info['res'] = "success";
    return $info;
}

function create_accept_location($order_sn,$location_barcode,$goods_barcode,$serial_number,$input_number,$validity, $validity_type='start_validity') {
    global $db, $ecs;

    $result = array();
    $product_result = get_product_id_by_barcode($goods_barcode);
    if($product_result['success']){
        $product_info = $product_result['res'];
        $product_id = $product_info['product_id'];
        $goods_id   = $product_info['ecs_goods_id'];
    }else{
        $result['success'] = false;
        $result['error'] = $product_result['error'];
        QLog::log($product_result['error']);
        QLog::log('location_barcode:'.$location_barcode);
        QLog::log('goods_barcode:'.$goods_barcode);
        return $result;
    }
    $order_result = get_order_id_type($order_sn);
    if($order_result['success']){
        $order_info = $order_result['res'];
        $order_id   = $order_info['order_id'];
        $order_type = $order_info['order_type_id'];
        $facility_id = $order_info['facility_id'];
        $party_id   = $order_info['party_id'];
    }else{
        $result['success'] = false;
        $result['error'] = 'orderSn error';
        QLog::log('orderSn error:'.$order_sn);
        return $result;
    }
    $action_type = 'ITT_SO_UNKNOWN';
    if(!empty($_CFG['adminvars']['order_type_transaction_type_map_out'][$order_type])){
        $action_type = $_CFG['adminvars']['order_type_transaction_type_map_out'][$order_type];
    }
    $serial_Nos = array();
    $serial_Nos[] = $serial_number;
    $location_status_id = 'INV_STTS_AVAILABLE';
    include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    $res = createAcceptLocation($product_id,
                                $location_barcode,
                                $goods_barcode,
                                $input_number,
                                $validity,
                                $facility_id,
                                $location_status_id,//
                                $action_type,
                                $order_id,
                                $serial_Nos,
                                $party_id,
                                $goods_id);
    if($res){
        if('OK' ==  $res['status']){
            $result['success'] = true;
        }else{
            $result['success'] = false;
            $result['error'] = $res['error'];
        }
    }else{
        $result['success'] = false;
    }
    return $result;
}
/**
 * 收货入库并且容器转换
 * 
 * @param $batch_order_sn
 * @param $location_barcode
 * @param $goods_barcode
 * @param $serial_number
 * @param $input_number
 * @param $validity
 * @param $validity_type
 */
function purchase_accept_and_location_transaction($batch_order_sn,$location_barcode,$goods_barcode,$serial_number,
                                                 $input_number,$validity, $validity_type='start_validity') {
    global $db, $ecs;
    $result = array();
    $sqls = array();
     Qlog::log('收货入库并且容器转换:'.'$batch_order_sn='.$batch_order_sn.'$location_barcode='.$location_barcode.'$goods_barcode='.$goods_barcode.'$serial_number='.$serial_number.'$input_number='.$input_number.'$validity='.$validity);
    //判断串号是否存在
    if(!empty($serial_number)){
        $serial_number_exist = check_serial_number_exist($serial_number);
        if(!$serial_number_exist['success']){
            $result['error'] = $serial_number_exist['error'];
            return $result;
        }
    }
    
    $order_no_in_goods_numbers = get_order_no_in_goods_numbers($batch_order_sn,$goods_barcode);
    if(empty($order_no_in_goods_numbers)) {
        $result['error'] = "未发现有未入库的商品batch_order_sn:".$batch_order_sn.' $goods_barcode:'.$goods_barcode;
        return $result;
    }
    
    $not_in_goods_number_all = 0;
    foreach($order_no_in_goods_numbers as $order_no_in_goods_number) {
        $not_in_goods_number_all += $order_no_in_goods_number['goods_number'];
    }
    
    if ($not_in_goods_number_all == 0 || $not_in_goods_number_all < $input_number) {
        $result['success'] = false;
        $result['error'] = '此次入库数量:'.$input_number.'超过未入库数量:'.$not_in_goods_number_all;
        return $result;
    }
    
    foreach($order_no_in_goods_numbers as $order_no_in_goods_number) {
        $order_id = $order_no_in_goods_number['order_id'];
        $goods_number = $order_no_in_goods_number['not_in_number'];
        if($input_number <= 0) {
            break;
        }
        if($goods_number > $input_number) {
            $goods_number = $input_number;
        }
        Qlog::log('$order_no_in_goods_numbers:'.'$order_id='.$order_id.'$order_goods_number='.$order_no_in_goods_number['goods_number'].'$location_barcode='.$location_barcode.'$goods_barcode='.$goods_barcode.'$serial_number='.$serial_number.'input_number:='.$input_number.'$goods_number='.$goods_number.'$validity='.$validity);
        
        $one_order_result = one_order_purchase_accept_and_location_transaction($order_id,$location_barcode,$goods_barcode,$serial_number,$goods_number,$validity, $validity_type='start_validity','');
        if(!$one_order_result['success']){
             $result['success'] = false;
             $result['error'] = $one_order_result['error'];
             return $result;
        }
        
        $input_number = $input_number - $goods_number;
    }

    $result['success'] = true;
    return $result;
}

/**
 * 收货入库并且容器转换
 * @param $batch_order_sn
 * @param $location_barcode
 * @param $goods_barcode
 * @param $serial_number
 * @param $input_number
 * @param $validity
 * @param $validity_type
 * 
 * update 2016.01.21
 */
function purchase_accept_and_location_transaction_v2($batch_order_sn,$location_barcode,$goods_barcode,$serial_number,
                                                 $input_number,$validity, $validity_type='start_validity') {
    global $db, $ecs;
    $result = array();
    $sqls = array();
     Qlog::log('收货入库并且容器转换:'.'$batch_order_sn='.$batch_order_sn.'$location_barcode='.$location_barcode.'$goods_barcode='.$goods_barcode.'$serial_number='.$serial_number.'$input_number='.$input_number.'$validity='.$validity);
    //判断串号是否存在
    if(!empty($serial_number)){
    	$serial_number_exist = check_serial_number_exist($serial_number);
    	if(!$serial_number_exist['success']){
    		$result['error'] = $serial_number_exist['error'];
    		return $result;
    	}
    }
    
    $order_no_in_goods_numbers = get_order_no_in_goods_numbers_v2($batch_order_sn,$goods_barcode);
    if(empty($order_no_in_goods_numbers)) {
    	$result['error'] = "未发现有未入库的商品batch_order_sn:".$batch_order_sn.' $goods_barcode:'.$goods_barcode;
    	return $result;
    }
    
    $not_in_goods_number_all = $order_no_in_goods_numbers['goods_number'];
    
    if ($not_in_goods_number_all == 0 || $not_in_goods_number_all < $input_number) {
        $result['success'] = false;
        $result['error'] = '此次入库数量:'.$input_number.'超过未入库数量:'.$not_in_goods_number_all;
        return $result;
    }
    
	$order_id = $order_no_in_goods_numbers['order_id'];
	$goods_number = $order_no_in_goods_numbers['not_in_number'];
	$rec_id = $order_no_in_goods_numbers['rec_id'];
	if($input_number <= 0) {
		break;
	}
	if($goods_number > $input_number) {
		$goods_number = $input_number;
	}
	Qlog::log('$order_no_in_goods_numbers:'.'$order_id='.$order_id.'$order_goods_number='.$order_no_in_goods_numbers['goods_number'].'$location_barcode='.$location_barcode.'$goods_barcode='.$goods_barcode.'$serial_number='.$serial_number.'input_number:='.$input_number.'$goods_number='.$goods_number.'$validity='.$validity);
    
	$one_order_result = one_order_purchase_accept_and_location_transaction_V2($order_id,$location_barcode,$goods_barcode,$serial_number,$goods_number,$validity,$rec_id, $validity_type='start_validity','');
	if(!$one_order_result['success']){
		 $result['success'] = false;
	     $result['error'] = $one_order_result['error'];
	     return $result;
	}
	
	$input_number = $input_number - $goods_number;

    $result['success'] = true;
    return $result;
}

/**
 * 单个采购订单收货入库并且容器转换,兼容生产日期和商品批次号
 * @param $order_id
 * @param $location_barcode
 * @param $goods_barcode
 * @param $serial_number
 * @param $goods_number
 * @param $validity
 * @param $validity_type
 */

function one_order_purchase_accept_and_location_transaction($order_id,$location_barcode,$goods_barcode,$serial_number,
                                                           $goods_number,$validity, $validity_type='start_validity',$batch_sn='') {
    global $db; 
    $result = array();
    //验证调拨的 -dt订单已经出库
    $sqlcheck = "select srr.status from ecshop.supplier_transfer_mapping stm 
				LEFT JOIN  ecshop.ecs_order_info oi on oi.order_id = stm.dt_order_id
				LEFT JOIN  romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
				LEFT JOIN romeo.supplier_return_request srr on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
				where stm.dc_order_id = {$order_id}";
    $dt_info = $db->getOne($sqlcheck);
    
    if($dt_info == 'CREATED' || $dt_info == 'CANCELLATION'){
    	$result['success'] = false;
        $result['error'] = '该调拨的采购订单 因为调拨出库未完成 不能操作';
        return $result;
    }
    
    $sql = "
        SELECT boi.provider_id, boi.order_type, oi.facility_id, oi.party_id,oi.order_sn,
        og.goods_id, og.style_id, og.goods_name,og.goods_number,og.rec_id,
        po.purchase_paid_amount,om.is_cancelled,om.is_over_c,om.is_in_storage,
        ifnull((select sum(if(iid.quantity_on_hand_diff>0,iid.quantity_on_hand_diff,0)) from romeo.inventory_item_detail iid
        where iid.order_id = convert(oi.order_id using utf8) group by oi.order_id),0) as in_num
        FROM `ecshop`.`ecs_order_info` oi
            LEFT JOIN `ecshop`.`ecs_order_goods` og ON og.order_id = oi.order_id
            LEFT JOIN `ecshop`.`ecs_batch_order_mapping` om ON om.order_id = oi.order_id
            LEFT JOIN `ecshop`.`ecs_batch_order_info` boi ON om.batch_order_id = boi.batch_order_id
            LEFT JOIN romeo.purchase_order_info po ON po.order_id = oi.order_id
        WHERE
            oi.order_id = '{$order_id}'
        LIMIT 1
    ";
    //Qlog::log('purchase_accept_and_location_transaction:'.$sql);
    
    $item_info = $db->getRow($sql);
    Qlog::log('purchase_accept_and_location_transaction: goods_number:'.$item_info['goods_number'].' in_num:'. $item_info['in_num']);
    if($item_info['is_cancelled']=='Y') {
        $result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经废除不能入库';
        return $result;
    }
    if($item_info['is_over_c']=='Y') {
        $result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经完结不能入库';
        return $result;
    }
    if($item_info['is_in_storage']=='Y') {
        $result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经入库结束不能入库';
        return $result;
    }
    
    $count = $item_info['goods_number'] - $item_info['in_num'];
    if ($count == 0 || $count < intval($goods_number)) {
        $result['success'] = false;
        $result['error'] = '此次入库数量'.$goods_number.'超过未入库数量:'.$count;
        return $result;
    }

    $fromStatusId = '';
    $toStatusId = 'INV_STTS_AVAILABLE';

    $goods_id = $item_info['goods_id'];
    $style_id = $item_info['style_id'];
    if(!empty($serial_number)) {
        $serialNos = array($serial_number);
    } else {
        $serialNos = null;
    }
    $goods_name = $item_info['goods_name'];
    $value = '';
    $order_type = $item_info['order_type'];
    $purchase_paid_amount = $item_info['purchase_paid_amount'];
    Qlog::log('purchase_accept_and_location_transaction purchase_paid_amount:'.$purchase_paid_amount);
    $order_goods_id = $item_info['rec_id'];
    $party_id = $item_info['party_id'];
    $facility_id = $item_info['facility_id'];
    $validity = $validity;
    $locationStatusId = 'INV_STTS_AVAILABLE';
    $actionType = 'RECEIVE';

    //同一件商品在同一个库位上要求有唯一的生产日期、批次号
    $res_diff = is_diff_validity_batch_sn($location_barcode,$goods_barcode,$facility_id,$party_id,$validity,$batch_sn);
    if(!$res_diff['success']){
         $result['success'] = false;
         $result['error'] = $res_diff['error'];
         return $result;
    }
    
    // 得到provider_id
    $provider_id = $item_info['provider_id'];
    
    Qlog::log('$order_type:'.$order_type.' provider_id:'.$provider_id);
    if (!checkProvider($provider_id) || ($order_type != 'B2C' && $order_type != 'C2C' && $order_type != 'DX') ) {
        $result['success'] = false;
        $result['error'] = "供应商或者订单类型信息不正确，无法入库，请联系采购！";
        return $result;
    }
    if (!function_exists("createPurchaseAcceptAndTransfer")) {
        include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    }
    $res = createPurchaseAcceptAndLocationTransaction(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                    $goods_number,
                                    $serialNos,
                                    $order_type,
                                    $order_id,
                                    $fromStatusId,
                                    $toStatusId,
                                    $purchase_paid_amount,
                                    $order_goods_id,
                                    $facility_id,
                                    $location_barcode,
                                    $goods_barcode,
                                    $validity,
                                    $batch_sn,
                                    $locationStatusId,
                                    $actionType,
                                    $provider_id);
    if($res['status'] == 'OK') {
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = $res['error'];
        return $result;
    }

    // 更新订单映射关系
    $res = check_update_batch_order($order_id);
    if(!$res['success']) {
        $result['success'] = false;
        $result['error'] = "收货入库更新订单映射关系错误:order_id:".$order_id.' 错误：'.$res['error'];
        return $result;
    }
    
    return $result;
}

/**
 * 单个采购订单收货入库并且容器转换,兼容生产日期和商品批次号
 * @param $order_id
 * @param $location_barcode
 * @param $goods_barcode
 * @param $serial_number
 * @param $goods_number
 * @param $validity
 * @param $validity_type
 * 
 * update 2016.01.20
 */

function one_order_purchase_accept_and_location_transaction_V2($order_id,$location_barcode,$goods_barcode,$serial_number,
                                                           $goods_number,$validity, $rec_id,$validity_type='start_validity',$batch_sn='') {
	global $db;	
	$result = array();
	$sql = "
        SELECT boi.provider_id, boi.order_type, oi.facility_id, oi.party_id,oi.order_sn,
        og.goods_id, og.style_id, og.goods_name,og.goods_number,og.rec_id,
        po.purchase_paid_amount,om.is_cancelled,om.is_over_c,om.is_in_storage,
        ifnull((select sum(if(iid.quantity_on_hand_diff>0,iid.quantity_on_hand_diff,0)) from romeo.inventory_item_detail iid
        where iid.order_id = convert(oi.order_id using utf8)  and iid.order_goods_id = og.rec_id group by og.rec_id),0) as in_num
        FROM `ecshop`.`ecs_order_info` oi
            LEFT JOIN `ecshop`.`ecs_order_goods` og ON og.order_id = oi.order_id
            LEFT JOIN `ecshop`.`ecs_batch_order_mapping` om ON om.order_id = oi.order_id
            LEFT JOIN `ecshop`.`ecs_batch_order_info` boi ON om.batch_order_id = boi.batch_order_id
            LEFT JOIN romeo.purchase_order_info po ON po.order_id = oi.order_id and po.order_goods_id = og.rec_id
        WHERE
            og.rec_id = {$rec_id} and og.order_id = {$order_id}
        LIMIT 1
	";
//	Qlog::log('purchase_accept_and_location_transaction_v2:'.$sql);
	
    $item_info = $db->getRow($sql);
//    Qlog::log('purchase_accept_and_location_transaction: goods_number:'.$item_info['goods_number'].' in_num:'. $item_info['in_num']);
    if($item_info['is_cancelled']=='Y') {
    	$result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经废除不能入库';
        return $result;
    }
    if($item_info['is_over_c']=='Y') {
    	$result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经完结不能入库';
        return $result;
    }
    if($item_info['is_in_storage']=='Y') {
    	$result['success'] = false;
        $result['error'] = '订单：'.$item_info['order_sn'].' 已经入库结束不能入库';
        return $result;
    }
    
    $count = $item_info['goods_number'] - $item_info['in_num'];
    if ($count == 0 || $count < intval($goods_number)) {
        $result['success'] = false;
        $result['error'] = '此次入库数量'.$goods_number.'超过未入库数量:'.$count;
        return $result;
    }

    $fromStatusId = '';
    $toStatusId = 'INV_STTS_AVAILABLE';

    $goods_id = $item_info['goods_id'];
    $style_id = $item_info['style_id'];
    if(!empty($serial_number)) {
	 	$serialNos = array($serial_number);
	} else {
	 	$serialNos = null;
	}
    $goods_name = $item_info['goods_name'];
    $value = '';
    $order_type = $item_info['order_type'];
    $purchase_paid_amount = $item_info['purchase_paid_amount'];
    $order_goods_id = $item_info['rec_id'];
    $party_id = $item_info['party_id'];
    $facility_id = $item_info['facility_id'];
    $validity = $validity;
    $locationStatusId = 'INV_STTS_AVAILABLE';
    $actionType = 'RECEIVE';

    //同一件商品在同一个库位上要求有唯一的生产日期、批次号
	$res_diff = is_diff_validity_batch_sn($location_barcode,$goods_barcode,$facility_id,$party_id,$validity,$batch_sn);
	if(!$res_diff['success']){
		 $result['success'] = false;
	     $result['error'] = $res_diff['error'];
	     return $result;
	}
    
    // 得到provider_id
    $provider_id = $item_info['provider_id'];
    
//    Qlog::log('$order_type:'.$order_type.' provider_id:'.$provider_id);
    if (!checkProvider($provider_id) || ($order_type != 'B2C' && $order_type != 'C2C' && $order_type != 'DX') ) {
        $result['success'] = false;
        $result['error'] = "供应商或者订单类型信息不正确，无法入库，请联系采购！";
        return $result;
    }
    if (!function_exists("createPurchaseAcceptAndTransfer")) {
    	include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    }
    $res = createPurchaseAcceptAndLocationTransaction(array('goods_id'=>$goods_id, 'style_id'=>$style_id),
                                    $goods_number,
                                    $serialNos,
                                    $order_type,
                                    $order_id,
                                    $fromStatusId,
                                    $toStatusId,
                                    $purchase_paid_amount,
                                    $order_goods_id,
                                    $facility_id,
                                    $location_barcode,
                                    $goods_barcode,
                                    $validity,
                                    $batch_sn,
                                    $locationStatusId,
                                    $actionType,
                                    $provider_id);
    if($res['status'] == 'OK') {
    	$result['success'] = true;
    } else {
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    	return $result;
    }

    // 更新订单映射关系
    $res = check_update_batch_order($order_id);
    if(!$res['success']) {
    	$result['success'] = false;
        $result['error'] = "收货入库更新订单映射关系错误:order_id:".$order_id.' 错误：'.$res['error'];
        return $result;
    }
    
    return $result;
}

/**
 * 避免同一件商品维护不一样的生产日期收货入库时使用相同的上架容器条码 zxcheng 2013-08-29
 * @param
 */
 function is_diff_validity_batch_sn($location_barcode,$goods_barcode,$facility_id,$party_id,$validity,$batch_sn){
    global $db;
    $result = array();

    $sql = "SELECT validity,batch_sn from romeo.inventory_location
                            WHERE location_barcode = '{$location_barcode}'  AND
                                  goods_barcode = '{$goods_barcode}'  AND
                                  facility_id ='{$facility_id}'  AND
                                  party_id  = '{$party_id}'  AND
                                  goods_number > 0
                                 ";
    $validity_sn = $db->getRow($sql);
    if(!empty($validity_sn)){
        if($validity_sn['validity'] == $validity){
            $result['success'] = true;
        }else{
            $result['success'] = false;
            $result['error'] = $location_barcode.":该容器已经存在生产日期不同、条码相同的商品，请更换上架容器！";
        }
        if($validity_sn['batch_sn'] == $batch_sn){
            $result['success'] = true;
        }else{
            $result['success'] = false;
            $result['error'] = $location_barcode.":该容器已经存在批次号不同、条码相同的商品，请更换上架容器！";
        }
    }else{
        $result['success'] = true;
    }
    return $result;
 }

/**
 * 判断串号是否存在 cywang 2013-09-13
 * @param $serial_number
 */
 function check_serial_number_exist($serial_number){
    $result['success'] = false;
    global $db;
    $existed = false;
    // 几个记录串号的表
    // 1. romeo.inventory_item
    $sql = "SELECT 1
            FROM romeo.inventory_item
            WHERE serial_number = '{$serial_number}' limit 1";
    $res = $db->getOne($sql);
    if($res){
        Qlog::log('inventory_item内存在串号'.$serial_number.'的记录');
        // 判断入库和出库的次数是否相等，不等则说明商品已经在库存中，不能再入库
        $sql = "SELECT SUM(ifnull(iid.quantity_on_hand_diff,0)) as diff
                FROM romeo.inventory_item ii
                LEFT JOIN romeo.inventory_item_detail iid ON ii.inventory_item_id = iid.inventory_item_id
                WHERE ii.serial_number = '{$serial_number}' and ii.status_id = 'INV_STTS_AVAILABLE'
               ";

         $diff = $db->getOne($sql);
         if($diff == 0){
            Qlog::log('inventory_item内出入库次数相等');
         }else{
            Qlog::log('inventory_item内出入库次数不等');
            $existed = true;
         }
    }
    else{
        Qlog::log('inventory_item内不存在串号'.$serial_number.'的记录');
    }


    // 3. romeo.location_barcode_serial_mapping
    $sql = "SELECT 1
            FROM romeo.location_barcode_serial_mapping
            WHERE serial_number = '{$serial_number}' limit 1";
    $res = $db->getOne($sql);
    if($res){
        Qlog::log('location_barcode_serial_mapping内存在串号'.$serial_number.'的记录');
        $existed = true;
    }
    else
    {
        Qlog::log('location_barcode_serial_mapping内不存在串号'.$serial_number.'的记录');
    }

    //
    $result['success'] = true;
    $result['serial_has_in'] = $existed;
    if($existed){
        $result['error'] = '该串号已经入库，请检查！串号：'.$serial_number.' diff:'.$diff;
        Qlog::log('该串号已经入库，请检查！串号：'.$serial_number);
    }
    return $result;
 }
/**
 * 根据串号得到商品条码
 * ljzhou 2013.08.22
 */
 function get_goods_barcode($serial_number){
    global $db;
    $sql = "SELECT if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) as goods_barcode
            FROM romeo.inventory_item ii
            LEFT JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
            LEFT JOIN ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
            LEFT JOIN ecshop.ecs_goods_style gs ON pm.ecs_goods_id = gs.goods_id and pm.ecs_style_id = gs.style_id
            WHERE ii.serial_number = '{$serial_number}' and ii.status_id = 'INV_STTS_AVAILABLE'
           ";
     //Qlog::log('get_goods_barcode sql:'.$sql);
     $goods_barcode = $db->getOne($sql);
     if(!empty($goods_barcode)){
        $result['success'] = true;
        $result['goods_barcode'] = $goods_barcode;
     }else{
        $result['success'] = false;
        $result['error'] = '根据串号找不到对应的条码：'.$serial_number;
        Qlog::log('根据串号找不到对应的条码：'.$serial_number);
        return $result;
     }
     return $result;
 }

/**
 * 如果普通入库该订单入库完了，需要更新批量入库的映射表
 * 该函数目前仅用于command中，操作人为system，权限root
 */
function update_batch_order($order_id, $from) {
    global $db, $ecs;
    $info = array();
    if (! $order_id) {
        $info['res'] = 'fail';
        $info['back'] = 'order_id is null ';
        return $info;
    }

    $sql = "select order_sn from {$ecs->table('order_info')} where order_id = {$order_id} limit 1";
    $order_sn = $db->getOne($sql);

    if (! $order_sn) {
        $info['res'] = 'fail';
        $info['back'] = 'order_id: {$order_id} order_sn is null ';
        return $info;
    }

    // 检查该订单是否入库完
    if ( check_order_all_in_storage ( $order_id )) {
        $sql = "update {$ecs->table('batch_order_mapping')} set is_in_storage = 'Y' where order_id = {$order_id} limit 1";
        $db->query ( $sql );
        echo ( "update batch_order_mapping(by {$from})order_sn:" . $order_sn ."\n");
        $sql = "select batch_order_sn from {$ecs->table('batch_order_info')} boi
                left join {$ecs->table('batch_order_mapping')} om ON boi.batch_order_id = om.batch_order_id
                where om.order_id = {$order_id} limit 1";
        $batch_order_sn = $db->getOne ( $sql );
        if ( !empty ( $batch_order_sn )) {
            if (check_all_in ( $batch_order_sn )) {
                $sql = "update {$ecs->table('batch_order_info')} set is_in_storage= 'Y',in_time = now(),in_storage_user = 'system' where is_cancelled = 'N' and batch_order_sn = '{$batch_order_sn}' limit 1";
                $db->query ( $sql );
                echo ( "batch_order_sn all_in_storage(by common {$from})batch_order_sn:" . $batch_order_sn."\n" );
            }
        }
    }

    $info['res'] = 'success';
    return $info;

}

/**
 * 根据订单号更新批次订单映射信息
 * ljzhou 2013-08-14
 */
function check_update_batch_order($order_id) {
    global $db, $ecs;
    $result = array();
    if (! $order_id) {
        $result['success'] = false;
        $result['error'] = 'order_id is null ';
        return $result;
    }

    $sql = "select order_sn from {$ecs->table('order_info')} where order_id = {$order_id} limit 1";
    $order_sn = $db->getOne($sql);

    if (! $order_sn) {
        $result['success'] = false;
        $result['error'] = 'order_id: {$order_id} order_sn is null ';
        return $result;
    }
    Qlog::log('purchase_accept_and_location_transaction check_order_all_in_storage start:order_id'.$order_id);

    // 检查该订单是否入库完
    if ( check_order_all_in_storage ( $order_id )) {
        Qlog::log('check_order_all_in_storage come in');
        $sql = "update {$ecs->table('batch_order_mapping')} set is_in_storage = 'Y' where order_id = {$order_id} limit 1";
        $db->query ( $sql );

        $batch_order_sn = get_batch_order_sn_by_order_id($order_id);
        Qlog::log('purchase_accept_and_location_transaction check_order_all_in_storage start:order_id'.$order_id.' $batch_order_sn:'.$batch_order_sn);

        if ( !empty ( $batch_order_sn )) {
            if (check_all_in ( $batch_order_sn )) {
                $sql = "update {$ecs->table('batch_order_info')} set is_in_storage= 'Y',in_time = now(),in_storage_user = '{$_SESSION['user_name']}' where is_cancelled = 'N' and batch_order_sn = '{$batch_order_sn}' limit 1";
                $db->query ( $sql );
            }
        }
    }

    $result['success'] = true;
    return $result;

}

/**
 * 根据订单号得到批次号
 * ljzhou 2013.08.15
 */
 function get_batch_order_sn_by_order_id($order_id) {
    global $db, $ecs;
    $sql = "select batch_order_sn from {$ecs->table('batch_order_info')} boi
            left join {$ecs->table('batch_order_mapping')} om ON boi.batch_order_id = om.batch_order_id
            where om.order_id = {$order_id} limit 1";
    $batch_order_sn = $db->getOne ( $sql );
    if(empty($batch_order_sn)) {
        return false;
    }
    return $batch_order_sn;
 }

/**
 *  批次采购订单入库是否全部入库完了
 *  @param string $batch_order_sn
 */
function check_all_in($batch_order_sn) {
    global $db, $ecs;
    $sql = "select 1
             from {$ecs->table('batch_order_info')}  boi
             left join {$ecs->table('batch_order_mapping')} om ON boi.batch_order_id = om.batch_order_id
             where
             boi.batch_order_sn = '{$batch_order_sn}'
             and boi.is_cancelled = 'N' and boi.is_over_c = 'N'
             and om.is_cancelled = 'N' and om.is_over_c = 'N' and om.is_in_storage = 'N'
             limit 1
          ";
    $result = $db->getOne($sql);
    if(!empty($result)) {
        return false;
    }
    return true;
}

/**
 *  通过新库存检查该订单是否入库完
 *  @param string $order_id
 */
function check_order_all_in_storage($order_id) {
    global $db, $ecs;
	$sql = "select (select sum(goods_number) from ecshop.ecs_order_goods where order_id = og.order_id) as goods_total,ifnull(sum(iid.quantity_on_hand_diff),0) as in_total
	       from ecshop.ecs_order_goods og
	       left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
	       where og.order_id = '{$order_id}'  group by og.order_id
	       ";

	$res = $db->getRow($sql);

	if(($res['goods_total'] - $res['in_total']) > 0 ) {
		return false;
	} else {
		return true;
	}

}


/**
 * 检查是否出库完成
 * @param string $order_id
 */
function check_batch_out_storage_status($order_id) {
    $product_no_out_numbers = get_order_no_out_goods_numbers($order_id);
    if(empty($product_no_out_numbers)) {
        return false;
    }
    foreach($product_no_out_numbers as $product_id=>$no_out_number) {
        if($no_out_number > 0) {
            return false;
        }
    }
    return true;
}

/**
 * 得到-gt申请状态
 */
 function get_supplier_return_status($supplier_return_id) {
    global $db;
    $sql = "select status from romeo.supplier_return_request
            where supplier_return_id = '{$supplier_return_id}'
            limit 1
            ";
    return $db->getOne($sql);
 }

 /**
 * 得到-gt数量
 */
 function get_supplier_return_amount($supplier_return_id) {
    global $db;
    $sql = "select r.return_order_amount, sum(i.amount) as amount
            from romeo.supplier_return_request r
            inner join romeo.supplier_return_request_item i on r.supplier_return_id = i.supplier_return_id
            where r.supplier_return_id = '{$supplier_return_id}'
            group by r.supplier_return_id
            ";
    return $db->getRow($sql);
 }

/**
 * 通过供应商id来查询供应商是否存在
 */
function checkProvider($id = 0)
{
    global $db, $ecs;
    static $provider_list;
    if (!$provider_list) {
        $provider_list = $db->getCol("SELECT provider_id FROM {$ecs->table('provider')} WHERE provider_status = 1");
    }
    return in_array($id, $provider_list);
}

/**
 * 自动售后收货、步骤包括：创建售后详细档案、入退货库、验货、入库
 */
function auto_service($service_id) {
    require_once('includes/debug/lib_log.php');
    QLog::log('auto_service,serivce_id:'.$service_id);
    global $db;
    $datetime = date("Y-m-d H:i:s", time());
    $info = array();
    if (! $service_id) {
        $info['res'] = 'fail';
        $info['back'] = 'service_id is null';
        return $info;
    }

    //获得售后档案，编写售后档案详细信息
    if(isRMATrackNeeded()){
        $rma_tracks = getTrackByServiceIdArray(array($service_id));
        if (count($rma_tracks) > 0) {
            foreach ($rma_tracks as $track) {
                // 获得相关属性
                $track->trackAttribute = getTrackAttributeArrayByTrackId($track->trackId);
                if ($track->trackAttribute == null)  {
                    if (! createTrackAttr($track->trackId)) {
                        print date("Y-m-d H:i:s") . " service_id: {$service_id} track_id: {$track->trackId} createTrackAttr fail \n";
                    }
                }
            }
        }
    }
    
    //物流收到货，更新售后服务的状态
    $sql = "UPDATE ecshop.service SET back_shipping_status = 12 WHERE service_id = '{$service_id}' LIMIT 1 ";
    $db->query($sql);
    
    // 初始化变量
    $serialNums = array();  // 串号控制商品的串号
    $goodsType = array();   // 商品的全新、二手情况
    
    // 处理数据：构造数组，记录商品的新旧情况
    // 查询该service中包含的商品信息，包括【商品条码】等信息
    $sql = "SELECT sog.*, og.*, IFNULL(gs.barcode, g.barcode) as barcode FROM service_order_goods sog
          INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
          LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id
          LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
          WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $back_goods_list = $db->getAll($sql);
    $sql = "select back_order_id,party_id from ecshop.service where service_id = '{$service_id}' ";
    $party_order_id = $db->getRow($sql);
    $goodIndex = -1;  // 标识商品序列号，供前端进行操作，从0开始计数
    foreach($back_goods_list as $goods_key => $goods){
        $goodIndex++;
        
        if($party_order_id['party_id'] == '65574'){
            $back_order_id = $party_order_id['back_order_id'];
            // 获取每一种商品的全新、二手数量
            $sql = "select ifnull(sum(Available_qty),0) as goods_number_new,  ifnull(sum(Defective_qty),0)  as goods_number_used
                     -- Total_qty as goods_number_new,0 as goods_number_used
                    from  ecshop.brand_gymboree_return_order_confirm roc  
                         inner join ecshop.brand_gymboree_return_order_confirm_detail rocd on rocd.Receipt_ID = roc.Receipt_ID 
                    where roc.transfer_status='WMS_RETURN' and rocd.Receipt_ID = '{$back_order_id}' and rocd.Item='{$goods['barcode']}'  and roc.Receipt_ID = '{$back_order_id}'
                            group by rocd.Item  ";
            $new_old_count = $db->getRow($sql);
        } else if($party_order_id['party_id']=='65614' || $party_order_id['party_id']=='65558' || $party_order_id['party_id']=='65553'){
            //百威英博、金佰利、雀巢 退货的全部入全新库
            $new_old_count['goods_number_new']=$goods['amount'];
            $new_old_count['goods_number_used']=0;
        }
        else{
            // 获取每一种商品的全新、二手数量
            $sql = "select sum(if(ad.goods_type = '良品', ad.goods_number, 0)) as goods_number_new,
                           sum(if(ad.goods_type = '不良品', ad.goods_number, 0)) as goods_number_used
                    from ecshop.ecs_indicate i
                         inner join ecshop.ecs_indicate_detail d on i.indicate_id = d.indicate_id
                         inner join ecshop.ecs_actual_detail ad on d.indicate_detail_id = ad.indicate_detail_id
                    where i.service_id = '{$service_id}'
                          and ad.actual_detail_status = 'RECEIVED'
                          and d.order_goods_id='{$goods['rec_id']}'";
            $new_old_count = $db->getRow($sql);
        }
        $goods_number_new = $new_old_count['goods_number_new'];
        $goods_number_used = $new_old_count['goods_number_used'];
        

        // 若实绩退回的商品数量 > 申请售后服务的商品数量 ，跳出函数
        if($party_order_id['party_id'] == '65574') {  //金宝贝退货实绩回传无rec_id字段  只能group by bacode判断
            $sql_gymboree_back_goods = "SELECT sum(sog.amount) as amount,  IFNULL(gs.barcode, g.barcode) as barcode FROM service_order_goods sog
                                          INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
                                          LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id
                                          LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
                                          WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' and (gs.barcode = '{$goods['barcode']}' or g.barcode = '{$goods['barcode']}')
                                          group by barcode";
            $gymboree_back_goods = $db -> getRow($sql_gymboree_back_goods); 
            if($goods_number_new + $goods_number_used > $gymboree_back_goods['amount'] ) {
                $info['res'] = 'fail';
                $info['back'] = 'actual back amount larger than amount in service!';
                return $info;
            }                
        } else {
            // 若实绩退回的商品数量 > 申请售后服务的商品数量 ，跳出函数
            if($goods_number_new + $goods_number_used > $goods['amount'] ){ 
                $info['res'] = 'fail';
                $info['back'] = 'actual back amount larger than amount in service!';
                return $info;
            } 
        }
        
        // 如果售后申请的商品数量为零，则跳出此次循环
        if($goods['amount'] == 0){ 
            continue;
        }
        
        // 根据商品是否串号控制进行操作
        require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        $is_serial = getInventoryItemType($goods['goods_id']);   // 查询商品是否串号控制
        if($is_serial == 'SERIALIZED') {  // 串号控制商品
            $serial_numbers = get_out_serial_numbers($goods['rec_id']);   // 得到已经出库的串号
            
            foreach($serial_numbers as $serial_number){
                // 判断该商品是否已经在库存里面，避免相同串号的商品重复入库
                $sql = "SELECT COUNT(*) FROM romeo.inventory_item
                    WHERE serial_number ='{$serial_number}'
                    AND quantity_on_hand_total > 0
                    AND inventory_item_type_id='SERIALIZED'
                    AND status_id IN ('INV_STTS_AVAILABLE', 'INV_STTS_USED')";
                $stockNum = $db->getOne($sql);
                if($stockNum > 0){
                    continue;
                }
                
                if($goods_number_new > 0){
                    $serialNums[$goodIndex][] = $serial_number;
                    $goodsType[$serial_number][0] = 'new';
                    $goods_number_new--;
                }elseif($goods_number_used > 0){
                    $serialNums[$goodIndex][] = $serial_number;
                    $goodsType[$serial_number][0] = 'old';
                    $goods_number_used--;
                }else{
                    break;
                }
            }
        }else{   // 非串号控制商品
            while($goods_number_new > 0 || $goods_number_used > 0){
                if($goods_number_new > 0){
                    $goodsType[$goods['barcode']][] = 'new';
                    $goods_number_new--;
                }elseif($goods_number_used > 0){
                    $goodsType[$goods['barcode']][] = 'old';
                    $goods_number_used--;
                }
            }
        }
    }
    // 验货入库（废弃老库存）
    $goodsNum = back_change_in_stock($service_id,$serialNums,$goodsType);
    if ($goodsNum > 0) {
        $log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货入库成功";
        $sql = " UPDATE ecshop.service SET inner_check_status = 32,
                    service_status = '".SERVICE_STATUS_OK."', 
                    service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."'
                    WHERE service_id = '{$service_id}' LIMIT 1 ";
        $db->query($sql);
        
        //更新受理时间
        if(isRMATrackNeeded()){
            $result = getTrackByServiceId($service_id);
            if ($result->total > 0) {
                $tracks = wrap_object_to_array($result->resultList->Track);
                foreach ($tracks as $track) {
                    $track->receivedDate = date("Y-m-d H:i:s");
                    $track->receivedUser = $_SESSION['admin_name'];
                    updateTrack($track);
                }
            }
        }
    } else {
        $log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货通过，入库失败";
    }
    
    // 记录日志service_log
    $sql = " SELECT * FROM ecshop.service WHERE service_id = '{$service_id}' LIMIT 1 ";
    $service = $db->getRow($sql);
    $service['log_note'] = $log_note;
    $service['log_type'] = 'LOGISTIC';
    service_log($service);
    
    $info['res'] = 'success';
    return $info;
}


/**
 * 自动售后收货、步骤包括：创建售后详细档案、入退货库、验货、入库
 * 兼容串号
 * ljzhou 2014-12-29
 */
function actual_service($service_id) {
    require_once('includes/debug/lib_log.php');
    global $db;
    $datetime = date("Y-m-d H:i:s", time());
    $_SESSION['admin_name'] = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
    
    $info = array();
    if (! $service_id) {
        $info['res'] = 'fail';
        $info['back'] = 'service_id is null';
        return $info;
    }

    //获得售后档案，编写售后档案详细信息
    if(isRMATrackNeeded()){
        $rma_tracks = getTrackByServiceIdArray(array($service_id));
        if (count($rma_tracks) > 0) {
            foreach ($rma_tracks as $track) {
                // 获得相关属性
                $track->trackAttribute = getTrackAttributeArrayByTrackId($track->trackId);
                if ($track->trackAttribute == null)  {
                    if (! createTrackAttr($track->trackId)) {
                        print date("Y-m-d H:i:s") . " service_id: {$service_id} track_id: {$track->trackId} createTrackAttr fail \n";
                    }
                }
            }
        }
    }
    
    //物流收到货，更新售后服务的状态
    $sql = "UPDATE ecshop.service SET back_shipping_status = 12 WHERE service_id = '{$service_id}' LIMIT 1 ";
    $db->query($sql);
    
    // 初始化变量
    $serialNums = array();  // 串号控制商品的串号
    $goodsType = array();   // 商品的全新、二手情况
    
    // 处理数据：构造数组，记录商品的新旧情况
    // 查询该service中包含的商品信息，包括【商品条码】等信息
    $sql = "SELECT sog.*, og.*, IFNULL(gs.barcode, g.barcode) as barcode FROM service_order_goods sog
          INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
          LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id
          LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
          WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $back_goods_list = $db->getAll($sql);
    //var_dump('$back_goods_list');var_dump($back_goods_list);
    $goodIndex = -1;  // 标识商品序列号，供前端进行操作，从0开始计数
    foreach($back_goods_list as $goods_key => $goods){
        $goodIndex++;
        
        // 获取每一种商品的全新、二手数量
        $sql = "select ifnull(sum(ad.normal_quantity),0) as goods_number_new,
                      ifnull(sum(ad.defective_quantity),0) as goods_number_used
                from ecshop.express_best_indicate i
                     inner join ecshop.express_best_indicate_detail d on i.order_id = d.order_id
                     inner join ecshop.express_best_actual_detail ad on d.order_goods_id = ad.order_goods_id
                where i.service_id = '{$service_id}'
                      and i.indicate_status = 'RECEIVED'
                      and d.goods_id='{$goods['goods_id']}' and d.style_id='{$goods['style_id']}' ";
        //var_dump($sql);
        $new_old_count = $db->getRow($sql);
        //var_dump('$new_old_count');var_dump($new_old_count);
        $goods_number_new = $new_old_count['goods_number_new'];
        $goods_number_used = $new_old_count['goods_number_used'];
        
        // 若实绩退回的商品数量 > 申请售后服务的商品数量 ，跳出函数
//      if($goods_number_new + $goods_number_used > $goods['amount']){ 
//          $info['res'] = 'fail';
//          $info['back'] = 'actual back amount larger than amount in service!';
//          return $info;
//      } 
        
        // 如果售后申请的商品数量为零，则跳出此次循环
        if($goods['amount'] == 0){ 
            continue;
        }
        
        // 根据商品是否串号控制进行操作
        require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        $is_serial = getInventoryItemType($goods['goods_id']);   // 查询商品是否串号控制
        if($is_serial == 'SERIALIZED') {  // 串号控制商品
            $serial_numbers = get_out_serial_numbers($goods['rec_id']);   // 得到已经出库的串号
            
            foreach($serial_numbers as $serial_number){
                // 判断该商品是否已经在库存里面，避免相同串号的商品重复入库
                $sql = "SELECT COUNT(*) FROM romeo.inventory_item
                    WHERE serial_number ='{$serial_number}'
                    AND quantity_on_hand_total > 0
                    AND inventory_item_type_id='SERIALIZED'
                    AND status_id IN ('INV_STTS_AVAILABLE', 'INV_STTS_USED')";
                $stockNum = $db->getOne($sql);
                if($stockNum > 0){
                    continue;
                }
                
                if($goods_number_new > 0){
                    $serialNums[$goodIndex][] = $serial_number;
                    $goodsType[$serial_number][0] = 'new';
                    $goods_number_new--;
                }elseif($goods_number_used > 0){
                    $serialNums[$goodIndex][] = $serial_number;
                    $goodsType[$serial_number][0] = 'old';
                    $goods_number_used--;
                }else{
                    break;
                }
            }
        }else{   // 非串号控制商品
            while($goods_number_new > 0 || $goods_number_used > 0){
                if($goods_number_new > 0){
                    $goodsType[$goods['barcode']][] = 'new';
                    $goods_number_new--;
                }elseif($goods_number_used > 0){
                    $goodsType[$goods['barcode']][] = 'old';
                    $goods_number_used--;
                }
            }
        }
    }
    //var_dump('$serialNums');var_dump($serialNums);var_dump('$goodsType');var_dump($goodsType);//die();
    // 验货入库（废弃老库存）
    $goodsNum = back_change_in_stock($service_id,$serialNums,$goodsType);
    if ($goodsNum > 0) {
        $log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货入库成功";
        $sql = " UPDATE ecshop.service SET inner_check_status = 32,
                    service_status = '".SERVICE_STATUS_OK."', 
                    service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."'
                    WHERE service_id = '{$service_id}' LIMIT 1 ";
        $db->query($sql);
        
        //更新受理时间
        if(isRMATrackNeeded()){
            $result = getTrackByServiceId($service_id);
            if ($result->total > 0) {
                $tracks = wrap_object_to_array($result->resultList->Track);
                foreach ($tracks as $track) {
                    $track->receivedDate = date("Y-m-d H:i:s");
                    $track->receivedUser = $_SESSION['admin_name'];
                    updateTrack($track);
                }
            }
        }
    } else {
        $log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货通过，入库失败";
    }
    
    // 记录日志service_log
    $sql = " SELECT * FROM ecshop.service WHERE service_id = '{$service_id}' LIMIT 1 ";
    $service = $db->getRow($sql);
    $service['log_note'] = $log_note;
    $service['log_type'] = 'LOGISTIC';
    service_log($service);
    
    $info['res'] = 'success';
    return $info;
}


/**
 * 创建售后档案详细，标配齐全，参照rma_track_service_action.php
 */
function createTrackAttr($trackId) {
    global $rma_track_attribute_type;

    if (! $trackId) {
    return false;
    }

    $attributes = getTrackAttributeByTrackId($trackId);
    if ($attributes->total !== 0) {
    return false;
    }

    $string_type = array("VALUE", "TEXT");
    $null_type = array("CHOICE", "CHECK");

    $attribute_array = array();
    foreach ($rma_track_attribute_type as $type) {
        $ta = new stdClass();
        $ta->trackId = $trackId;
        $ta->trackAttributeTypeId = $type[0];
        $ta->name = $type[1];
        if ($type[0] == 'ALL_RETURNED') {
             $ta->value = 'Y';
        } elseif (in_array($type[2], $string_type)) {
            $ta->value = "";
        } else {
            $ta->value = null;
        }
        $attribute_array[] = $ta;
    }
    $attribute = new stdClass();
    $attribute->TrackAttribute = $attribute_array;
    return createTrackAttribute($attribute);
}

/**
 * 自动采购入库commond
 */
function actual_inventory_in($order_id, $input_number = 0, $is_print=true, $inventory_status = 'INV_STTS_AVAILABLE', $facility_id, $from) {
    global $db, $ecs;

    $is_command = true;
    $info = check_in_fittings($order_id, $input_number, false,null);
    
    if ($info['res'] != 'success') {
        return $info;
    }

    // 如果普通入库该订单入库完了，需要更新批量入库的映射表
    $info = update_batch_order($order_id, $from);
    return $info;
}

/**
 * 自动采购入库commond
 */
function actual_inventory_in_common($order_id, $input_number = 0, $is_print=true, $inventory_status = 'INV_STTS_AVAILABLE', $facility_id, $from,$serial_numbers=null) {
    global $db, $ecs;

    $is_command = true;
    $info = check_in_fittings($order_id, $input_number, false,$serial_numbers);
    
    if ($info['res'] != 'success') {
        return $info;
    }

    // 如果普通入库该订单入库完了，需要更新批量入库的映射表
    $info = update_batch_order($order_id, $from);
    return $info;
}

/**
 * 自动出库一个商品,兼容串号版
 */
function check_out_goods_common($item){
    global $db;
    if ($item['goods_number'] <= 0) {
        $info['msg'] = "fail";
        $info['back'] = "实绩商品数量有误goods_number:".$item['goods_number'];
        return $info;
    }
    $sql = "
        select og.goods_number+ifnull(sum(iid.quantity_on_hand_diff),0)
        from ecshop.ecs_order_goods og
        left join romeo.inventory_item_detail iid  ON iid.order_goods_id = convert(og.rec_id using utf8) 
        where
            og.rec_id = '{$item['order_goods_id']}'
        group by og.rec_id
    ";
    $count = $db->getOne($sql);
    if (empty($count) || ($count < (int)$item['goods_number']  && $count!=0)) {
        $info['msg'] = 'fail';
        $info['back'] = '此次order_goods_id:'.$item['order_goods_id'].' 出库数量'.$item['goods_number'].'超过未出库数量:'.$count;
        echo "[" . date ( 'c' ) . "] " . $info['back'] ."\n";
        return $info;
    }else if($count!=0){
        echo "[" . date ( 'c' ) . "] " . "check_out_goods_common check_out_item_status begin  item:" ."\n";
         $info = check_out_item_status_common($item);
         if ($info['msg'] != 'success') {
            $info['msg'] = 'fail';
            $info['back'] = '此次order_goods_id:'.$item['order_goods_id'].' 自动出库错误';
            echo "[" . date ( 'c' ) . "] " . $info['back'] ."\n";
            return $info;
         }
    }
    $info['msg'] = "success";
    $info['back'] = "出库成功";
    return $info;
}
/**
 * 自动出库一个商品,兼容串号版
 */
function check_out_item_status_common($item){
    global $db, $_CFG, $ecs;
    require_once('includes/debug/lib_log.php');

    require_once ROOT_PATH.'/RomeoApi/lib_inventory.php';

    $formStatusId = isset($item['status_id'])?$item['status_id']:'INV_STTS_AVAILABLE';

    // 新百伦自动出库打日志
    if($item['party_id'] == 65585) {
        QLog::log('newbalance check_out_item_status order_id:'.$item['order_id'].' order_goods_id:'.$item['order_goods_id'].' goods_id:'.$item['goods_id'].' style_id:'.$item['style_id']);
    }
    
    $goods_type = getInventoryItemType($item['goods_id']);
    
    if( $goods_type == 'SERIALIZED') {
        foreach($item['serial_numbers'] as $serial_number) {
            $inventoryTransactionResult = createTransferInventoryTransaction(
                'ITT_SALE', array('goods_id'=>$item['goods_id'], 'style_id'=>$item['style_id']), 1,
                $serial_number, 'B2C', null, $item['order_id'],
                $formStatusId, 'INV_STTS_DELIVER', $item['order_goods_id'],
                $item['facility_id'], $item['facility_id']
            );
        }
    } else {
        $inventoryTransactionResult = createTransferInventoryTransaction(
            'ITT_SALE', array('goods_id'=>$item['goods_id'], 'style_id'=>$item['style_id']), $item['goods_number'],
            null, 'B2C', null, $item['order_id'],
            $formStatusId, 'INV_STTS_DELIVER', $item['order_goods_id'],
            $item['facility_id'], $item['facility_id']
        );
    }

    if ($inventoryTransactionResult) {
        $info['msg'] = 'success';
        $info['back'] = "出库成功";
    } else {
        $info['msg'] = 'fail';
        $info['back'] = "出库失败";
    }

    return $info;
}


/**
 * 自动出库command
 */
function actual_inventory_out($order_list){
    global $db;
    Qlog::log(" actual_inventory_out begin \n");
    if (!empty($order_list)) {
        foreach ($order_list as $item ) {
            $msg = null;
            //已配货待出库
            //先检查订单状态是否已配货否则先进行配货
            $sql = "select shipping_status from ecshop.ecs_order_info where order_id = {$item['order_id']} limit 1";
            $shipping_status = $db->getOne($sql);
            if ($shipping_status == 0) {
                $shipping_9 = update_shipping_status($item['order_id'], 9);
                if ($shipping_9['msg'] != 'success') {
                    Qlog::log($shipping_9['back']);
                }
            }
            $info = check_out_goods($item);
            if ($info['msg'] != 'success') {
                $msg = "check_out_goods: {$info['back']}";
                Qlog::log("[ERROR]actual_inventory_out: {$item['order_id']} error: {$msg}\n");
            }
            if (check_batch_out_storage_status($item['order_id'])) {
                //已出库待发货
                $shipping_8 = update_shipping_status($item['order_id'], 8);
                if ($shipping_8['msg'] == "success") {
                    //已发货
                    $shipping_result = update_shipping_status($item['order_id'], 1);
                    Qlog::log($shipping_result['back']);
                } else {
                    Qlog::log($shipping_8['back']);
                }
            }
            
        }
        Qlog::log("actual_inventory_out end");
    } 
}
/**
 * 自动-gt出库
 */
function actual_supplier_return ($partyId, $supRetReqId, $returnOrderAmount, $admin_name = 'system', $hid_serial_number = null) {
    global $db;
    $info = array();

    if(empty($partyId)){
        $info['res'] = 'fail';
        $info['back'] = 'partyId is empty';
        return $info;
    }

    if(empty($supRetReqId)){
        $info['res'] = 'fail';
        $info['back'] = 'supRetReqId is empty';
        return $info;
    }

    if (! $returnOrderAmount) {
        $info['res'] = 'fail';
        $info['back'] = 'returnOrderAmount is empty';
        return $info;
    }
    $sql = "
       select rr.storage_amount,sum(og.goods_number) excute_number
       from romeo.supplier_return_request rr
       inner join romeo.supplier_return_request_gt rrg on rrg.supplier_return_id = rr.supplier_return_id
       inner join ecshop.ecs_order_info oi on oi.order_sn = rrg.supplier_return_gt_sn 
       inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
       where rr.supplier_return_id = '{$supRetReqId}' and oi.order_id is not null 
       group by rr.supplier_return_id having excute_number <= rr.storage_amount
    ";
    $result = $db->getRow($sql);
    
    //如果非空的话，证明已经有足够的数量被执行了
    if(!empty($result)){
        $info['back'] = $supRetReqId . '已经有足够的商品退货了';
        $info['res'] = 'fail';
        return $info;
    }
    require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
    // 根据requestId获取预定记录
    $ret_req = new stdClass();
    $ret_req -> supplierReturnRequestId = $supRetReqId;
    $ret_req -> partyId = $partyId;
     
    $supplier_return_request = get_supplier_return_request($ret_req); 
    if(empty($supplier_return_request)){
        $message .=  'get_supplier_return_request调用失败';
        $info['back'] = 'get_supplier_return_request调用失败';
        $info['res'] = 'fail';
        return $info; 
    }else{
        $supRetReq = $supplier_return_request[$supRetReqId];    
        //进行串号处理
        if ($supRetReq['inventoryItemTypeId'] == 'SERIALIZED'){
            // 串号商品
            $return_serials = $hid_serial_number;

            $supRetReqItems = get_supplier_return_request_item($supRetReq['supplierReturnRequestId']);
            
            // 提取指定退还
            $request_serials = array();
            foreach($supRetReqItems as $item){
                $request_serials[] = $item['serialNumber'];
            }
            $is_right = true;
            foreach($return_serials as $item){
                if(!in_array($item, $request_serials)){
                    $message .= $item.'不是指定的退还串号.';
                    $is_right = false;
                }
            }
            if($is_right){
                // 库存出库
                $message .= deliver_supplier_return_order_inventory_sn ($supRetReq,  $return_serials) ;
            }
        }else if($supRetReq['inventoryItemTypeId'] == 'NON-SERIALIZED'){
            // 批量出库逻辑
            $out_num = $returnOrderAmount;
            $message .= deliver_supplier_return_order_inventory ($supRetReq, $out_num);
        }
    }
    print date("Y-m-d H:i:s") . " " . $message . "\r\n";
    if(strstr($message,'出库成功')!=false){
        $info['res'] = 'success';
    }else{
        $info['back'] = $message;
        $info['res'] = 'fail';
    }
    return $info;


}

/**
 * 函数名 php_ftp_download
 * 功能   从ftp服务器上下载文件
 * 入口参数
 * filename 欲下载的文件名，含路径
 * ADD BY qxu
 */
function php_ftp_download($filename) {
  $phpftp_host = "172.16.2.22";    // 服务器地址
  $phpftp_port = 21;            // 服务器端口
  $phpftp_user = "qxu";        // 用户名
  $phpftp_passwd = "123456";        // 口令
  $ftp_path = dirname($filename) . "/";    // 获取路径
  $select_file = basename($filename);    // 获取文件名

  $ftp = ftp_connect($phpftp_host,$phpftp_port);    // 连接ftp服务器
  if($ftp) {
    if(ftp_login($ftp, $phpftp_user, $phpftp_passwd)) {    // 登录
      if(@ftp_chdir($ftp,$ftp_path)) {            // 进入指定路径
            $tempFile    = getcwd()."/temp/".$select_file;
        }
        ftp_get($ftp, $tempFile, $select_file, FTP_BINARY); // 下载指定的文件到指定目录
      }
  }
  ftp_quit($ftp);
}

/**
 *  功能   遍历文件夹里的所有文件，返回文件名的路径数组
 *  入口参数   欲遍历的文件夹路径
 *  //  修改方案，不需要遍历读取
 *  ADD BY qxu
 */
function listDir($path){
    global $db;
    $array = array();

    $handle=opendir($path);
    while (false !== ($file = readdir($handle))){
        if (is_file($path.'/'.$file) && strpos($file,"InOutVouch") !== false) {
            $file_name = str_replace(".xml","",$file);
            $sql = "
                select filename,fchrInOutVouchID,is_send
                from ecshop.brand_gymboree_inoutvouch
                where filename = '{$file_name}'
            ";
            $row = $db->getRow($sql);
            if ($row){
                if($row['is_send'] == 'true'){
                    continue;
                }
                $file_name = "<已下单>".$file_name;
            }

            $xml = simplexml_load_file($path.'/'.$file);
            $warehouse = "".$xml->InOutVouch->Row->fchrWarehouseID;
            $sql = "
                select fchrWhName
                from ecshop.brand_gymboree_warehouse
                where fchrWarehouseID = '{$warehouse}'
            ";
            $warehouse = $db->getOne($sql);
            $file_name .= "仓库为：".$warehouse;
            $array_item = array(
                "item_value"=>str_replace(".xml","",$file),
                "item"=>$file_name
            );
            array_push($array,$array_item);
        }
    }
    closedir($handle);
    return $array;
}


/**
 * 根据任意订单号得到合并订单的全部订单号
 * ljzhou 2012-12-19
 */
function get_merge_order_ids($order_id) {
    global $db;
    $sql = "select oss.order_id
            from romeo.shipment s
            inner join romeo.order_shipment os ON s.primary_order_id = os.order_id
            inner join romeo.order_shipment oss ON os.shipment_id = oss.shipment_id
            where s.primary_order_id = '{$order_id}'
         ";
    $result = $db->getCol($sql);
    if(empty($result)) {
        return false;
    }
    return $result;
}

/**
 * 根据orderId数组得到order_sn数组
 * ljzhou 2012-12-19
 */
function get_order_sns($order_ids) {
    global $db;
    $sql = "select order_sn from ecshop.ecs_order_info where order_id in("."{$order_ids}".")";
    $result = $db->getCol($sql);
    if(empty($result)) {
        return false;
    }
    return $result;
}
/**
 * 根据order_sn数组得到order_id,type,facility_id等
 * qdi
 */
function get_order_id_type($order_sn) {
    global $db;
    $result = array();
    $sql = "select order_id,order_type_id,facility_id,party_id from ecshop.ecs_order_info where order_sn = '{$order_sn}' limit 1";
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到容器转换的基本参数为空！";
        //Qlog::log('get_location_trans_params:'.$sql);
    }
    return $result;
}

/**
 * 根据-gt订单号得到一些基础参数
 * ljzhou 2012-12-26
 */
function get_basic_by_order_sn($order_sn) {
    global $db;
    $sql = "
            select
                  s.supplier_return_id,s.original_supplier_id,s.purchase_unit_price,s.inventory_item_type_id,s.status_id,
                  s.status,og.goods_id,og.style_id,pm.product_id,oi.facility_id,oi.order_id,oi.order_sn,og.goods_name
            from
                  ecshop.ecs_order_info oi
                  inner join romeo.supplier_return_request_gt sr ON oi.order_sn = sr.supplier_return_gt_sn
                  inner join romeo.supplier_return_request s ON sr.supplier_return_id = s.supplier_return_id
                  inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                  inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            where
                  oi.order_sn = '{$order_sn}'
            limit 1
     ";
    $result = $db->getRow($sql);
    if(empty($result)) {
        return false;
    }
    return $result;
}

/**
 * 根据order_sn,product_id得到某个订单中某商品的订单商品数
 * ljzhou 2013-11-13
 */
function get_order_goods_number($order_sn,$product_id) {
    global $db;
    $sql = "
            select
                  og.goods_number
            from ecshop.ecs_order_info oi
                  inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                  inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            where
                  oi.order_sn = '{$order_sn}' and pm.product_id = '{$product_id}'
            limit 1
    ";
    $result = $db->getOne($sql);
    if(empty($result)) {
        return 0;
    }
    return $result;
}


/**
 * 根据order_sn,product_id得到某个订单中某商品新库存的出库数
 * ljzhou 2012-12-26
 */
function get_inventory_out_count($order_sn,$product_id) {
    global $db;
    $sql = "
            select
                  -sum(if(iid.quantity_on_hand_diff is not null and iid.quantity_on_hand_diff < 0,iid.quantity_on_hand_diff,0)) as out_num
            from ecshop.ecs_order_info oi
                  left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                  left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                  left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            where
                  oi.order_sn = '{$order_sn}' and pm.product_id = '{$product_id}'
            group by og.rec_id
    ";
    $result = $db->getOne($sql);
    if(empty($result)) {
        return 0;
    }
    return $result;
}


/**
 * 根据order_sn得到某个订单中某商品新库存的入库数
 * ljzhou 2013-05-02
 */
function get_inventory_in_count($order_sn) {
    global $db;
    $sql = "
            select
                  sum(if(iid.quantity_on_hand_diff is not null and iid.quantity_on_hand_diff > 0,iid.quantity_on_hand_diff,0)) as in_num
            from ecshop.ecs_order_info oi
                  left join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8) = iid.order_id
                  left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            where
                  oi.order_sn = '{$order_sn}' and ii.status_id = 'INV_STTS_AVAILABLE'
            group by oi.order_sn
    ";
//  QLog::log ( "get_inventory_in_count sql:".$sql);

    $result = $db->getOne($sql);
    if(empty($result)) {
        return 0;
    }
    return $result;
}

/**
 * 根据order_sn判断是否为采购订单
 * ljzhou 2013-05-02
 */
function check_is_purchase($order_sn) {
    global $db;
    $sql = "
            select
                  1
            from ecshop.ecs_order_info oi
            where
                  oi.order_sn = '{$order_sn}' and oi.order_type_id = 'PURCHASE'
            limit 1
    ";

    $result = $db->getOne($sql);
    if(empty($result)) {
        return false;
    }
    return true;
}


/**
 * 根据order_sn判断采购订单ecs_order_goods是否并发
 * ljzhou 2013-05-02
 */
function check_c_order_goods($order_sn) {
    global $db;
    $sql = "
            select
                  count(distinct(og.rec_id)) as num
            from ecshop.ecs_order_info oi
            left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            where
                  oi.order_sn = '{$order_sn}'
            group by oi.order_sn
            having num > 1
    ";

    $result = $db->getOne($sql);
    if(!empty($result)) {
        return false;
    }
    return true;
}


/**
 * 根据order_sn得到某个订单中某商品新库存的入库数
 * ljzhou 2013-05-02
 */
function get_c_inventory_error_ids($order_sn,$more_in_count) {
    global $db;
    $sql = "
            select
                  iid.inventory_item_detail_id,iid.inventory_item_id,ii.quantity_on_hand_total
            from ecshop.ecs_order_info oi
                  left join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8) = iid.order_id
                  left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            where
                  oi.order_sn = '{$order_sn}' and ii.status_id = 'INV_STTS_AVAILABLE' and ii.quantity_on_hand_total > 0
                  -- 没有出库记录，否则不能删inventory_item
                  and not exists (select 1 from romeo.inventory_item_detail iid2 where iid2.inventory_item_id = ii.inventory_item_id and iid2.QUANTITY_ON_HAND_DIFF < 0 limit 1)
            group by iid.inventory_item_id
            order by ii.quantity_on_hand_total desc
    ";
    $inventory_nums = $db->getAll($sql);
    if(empty($inventory_nums)) {
        return false;
    }

    return $inventory_nums;

}


/**
 * 根据全新状态的inventory_item_id得到采购订单inventory_item_detail对应的3条记录
 * ljzhou 2013-05-02
 */
function get_c_inventory_relate_ids($order_sn,$inventory_item_id) {
    global $db;
    $sql = "
            select
                  iid.inventory_item_id as item_id_1,
                  iid3.inventory_item_id as item_id_3,
                  iid.inventory_item_detail_id as detail_id_1,
                  iid2.inventory_item_detail_id as detail_id_2,
                  iid3.inventory_item_detail_id as detail_id_3,
                  iid.inventory_transaction_id as transaction_id_1,
                  iid3.inventory_transaction_id as transaction_id_3
            from ecshop.ecs_order_info oi
                  left join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8) = iid.order_id
                  left join romeo.inventory_item_detail iid2 ON iid.inventory_transaction_id = iid2.inventory_transaction_id
                  left join romeo.inventory_item_detail iid3 ON iid2.inventory_item_id = iid3.inventory_item_id and iid2.order_id = iid3.order_id
            where
                  oi.order_sn = '{$order_sn}' and iid.inventory_item_id = '{$inventory_item_id}'
            limit 1
    ";
    $inventory_relate_ids = $db->getRow($sql);
    if(empty($inventory_relate_ids)) {
        return false;
    }

    return $inventory_relate_ids;
}


/**
 * 根据order_sn,product_id得到某个订单中某商品的新库存表的关键id
 * ljzhou 2012-12-26
 */
function get_inventory_error_ids($order_sn,$product_id) {
    global $db;
    $sql = "
            select
                  iid.inventory_item_detail_id,iid.inventory_transaction_id,ii.inventory_item_id,im.inventory_summary_id
            from ecshop.ecs_order_info oi
                  left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                  left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                  left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
                  left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                  left join romeo.inventory_summary im ON ii.product_id = im.product_id and ii.facility_id = im.facility_id and ii.status_id = im.status_id
            where
                  oi.order_sn = '{$order_sn}' and pm.product_id = '{$product_id}' and iid.quantity_on_hand_diff < 0
            order by iid.inventory_item_detail_id
            limit 1
    ";
    $result = $db->getRow($sql);
    if(empty($result)) {
        return false;
    }
    return $result;
}
/**
 * 自动出库一个商品
 */
function check_out_goods($item){
    global $db;
    if ($item['goods_number'] <= 0) {
        $info['msg'] = "fail";
        $info['back'] = "实绩商品数量有误goods_number:".$item['goods_number'];
        return $info;
    }
    $sql = "
        select og.goods_number+ifnull(sum(iid.quantity_on_hand_diff),0)
        from ecshop.ecs_order_goods og
        left join romeo.inventory_item_detail iid  ON iid.order_goods_id = convert(og.rec_id using utf8) 
        where
            og.rec_id = '{$item['order_goods_id']}'
        group by og.rec_id
    ";
    $count = $db->getOne($sql);
    if (empty($count) || ($count < (int)$item['goods_number'] && $count!=0)) {
        $info['msg'] = 'fail';
        $info['back'] = '此次order_goods_id:'.$item['order_goods_id'].' 出库数量'.$item['goods_number'].'超过未出库数量:'.$count;
        echo "[" . date ( 'c' ) . "] " . $info['back'] ."\n";
        return $info;
    }else if($count!=0){
        echo "[" . date ( 'c' ) . "] " . "AutoDeliveryConsumable check_out_item_status begin  item:" ."\n";
        $info = check_out_item_status($item);
        if ($info['msg'] != 'success') {
            $info['msg'] = 'fail';
            $info['back'] = '此次order_goods_id:'.$item['order_goods_id'].' 自动出库错误';
            echo "[" . date ( 'c' ) . "] " . $info['back'] ."\n";
            return $info;
        }
    }
    $info['msg'] = "success";
    $info['back'] = "出库成功";
    return $info;
}
/**
 * 自动出库一个商品
 */
function check_out_item_status($item){
    global $db, $_CFG, $ecs;
    require_once('includes/debug/lib_log.php');

    require_once ROOT_PATH.'/RomeoApi/lib_inventory.php';

    $formStatusId = 'INV_STTS_AVAILABLE';

    // 新百伦自动出库打日志
    if($item['party_id'] == 65585) {
        QLog::log('newbalance check_out_item_status order_id:'.$item['order_id'].' order_goods_id:'.$item['order_goods_id'].' goods_id:'.$item['goods_id'].' style_id:'.$item['style_id']);
    }
    // 新库存出库成功后才更新老库存
    $inventoryTransactionResult = createTransferInventoryTransaction(
        'ITT_SALE', array('goods_id'=>$item['goods_id'], 'style_id'=>$item['style_id']), $item['goods_number'],
        null, 'B2C', null, $item['order_id'],
        $formStatusId, 'INV_STTS_DELIVER', $item['order_goods_id'],
        $item['facility_id'], $item['facility_id']
    );

    if ($inventoryTransactionResult) {
        $info['msg'] = 'success';
        $info['back'] = "出库成功";
    } else {
        $info['msg'] = 'fail';
        $info['back'] = "出库失败";
    }

    return $info;
}

/**
 * 修改订单状态 已配货，待出库  9
 * 修改订单状态 已出库 待发货  8
 * 已发货 1
 */
function update_shipping_status_common($order_id, $to_shipping_status) {
    global $db, $ecs;
    if (empty($order_id)) {
        $info['msg'] = 'fail';
        $info['back'] = "order_id: is empty";
        return $info;
    }
    if (empty($to_shipping_status)) {
        $info['msg'] = 'fail';
        $info['back'] = "to_shipping_status: is empty";
        return $info;
    }
    // require_once ROOT_PATH."admin/includes/lib_order_mixed_status.php";
    $status = $db->getRow("select order_status, shipping_status, pay_status
                            from ecshop.ecs_order_info where order_id = {$order_id} limit 1");
    switch ($to_shipping_status) {
        case 9:
            //已配货，待出库
            if ($status['shipping_status'] == 0 || $status['shipping_status'] == 10) {
                $sql = "UPDATE ecshop.ecs_order_info SET shipping_status=9 WHERE order_id='%d' LIMIT 1";
                $result = $db->exec(sprintf($sql, $order_id));
                if ($result) {
                    // 记录订单操作历史
                    $sql = "
                         INSERT INTO ecshop.ecs_order_action
                         (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES
                         ('{$status['order_id']}', '{$status['order_status']}', 9, '{$status['pay_status']}', NOW(), '%s', 'system')
                     ";
                    if ($status['shipping_status'] == 10) {
                        $db->exec(sprintf($sql, '重新配货出库'));
                        // update_order_mixed_status($order_id, array('warehouse_status'=>'re-picked'), 'worker');
                    } elseif ($status['shipping_status'] == 0) {
                        $db->exec(sprintf($sql, '配货出库'));
                        // update_order_mixed_status($order_id, array('warehouse_status'=>'picked'), 'worker');
                    }
                    $info['msg'] = 'success';
                    $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} success";
                } else {
                    $info['msg'] = 'fail';
                    $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} fail";
                }
            } else {
                $info['msg'] = 'fail';
                $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} is not 0 or 10";
            }
            break;
        case 8:
            //已出库待发货
            if ($status['shipping_status'] == 9) {

                //查找合并订单，并修改订单状态
                $merge_order_sql = "
                    select s2.shipment_id, group_concat(s2.order_id) orderids
                    from romeo.order_shipment s1
                        inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id
                        inner join romeo.shipment s on s2.shipment_id = s.shipment_id
                    where s1.order_id = '%s'
                        and s.shipping_category = 'SHIPPING_SEND'
                        and s.status != 'SHIPMENT_CANCELLED'
                    group by s2.shipment_id" ;
                $merge_orders = $db->getRow(sprintf($merge_order_sql, $order_id));
                $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status='8', shipping_time=UNIX_TIMESTAMP() WHERE order_id in %s ;";
                $res_order = $db->query(sprintf($sql, '('.$merge_orders['orderids'].')'));
                if ($res_order) {
                    $sql = "select order_id, order_status, pay_status, shipping_status
                            from ecshop.ecs_order_info where order_id in %s ;";
                    $order_list = $db->getAll(sprintf($sql,  '('.$merge_orders['orderids'].')'));
                    foreach ($order_list as $order_item) {
                         // 记录订单备注
                        $sql = "
                            INSERT INTO {$ecs->table('order_action')}
                            (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES
                            ('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '已出库，待发货', 'system')
                        ";
                        $db->query($sql);

                        // 记录订单状态
                        // update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'delivered'), 'worker');
                    }
                    $info['msg'] = 'success';
                    $info['back'] = "order_id: {$order_id} 已出库待发货";
                } else {
                    $info['msg'] = 'fail';
                    $info['back'] = "order_info shipping_status:8 error";
                }
            }
            break;
        case 1:
            if ($status['shipping_status'] == 8) {
                require_once ROOT_PATH . '/RomeoApi/lib_inventory.php';
                require_once ROOT_PATH . 'RomeoApi/lib_soap.php';
                //查找是否为合并订单
                $merge_order_sql = "
                    select s2.shipment_id, group_concat(s2.order_id) orderids
                    from romeo.order_shipment s1
                        inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id
                        inner join romeo.shipment s on s2.shipment_id = s.shipment_id
                    where s1.order_id = '%s'
                        and s.shipping_category = 'SHIPPING_SEND'
                        and s.status != 'SHIPMENT_CANCELLED'
                    group by s2.shipment_id" ;
                $merge_orders = $db->getRow(sprintf($merge_order_sql, $order_id));
                $order_ids = preg_split('/,/',  $merge_orders['orderids'], PREG_SPLIT_NO_EMPTY );
                foreach ($order_ids as $order_id) {
                    $order_info = $db->getRow("select facility_id, order_status, shipping_status, pay_status, order_id
                            from ecshop.ecs_order_info where order_id = {$order_id} limit 1");

                     // 更改订单状态
                    $db->exec(sprintf("UPDATE ecshop.ecs_order_info SET shipping_time=UNIX_TIMESTAMP(), shipping_status=1 WHERE order_id='%d'",$order_id));
                    // 记录订单备注
                    $sql = "
                        INSERT INTO {$ecs->table('order_action')}
                        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES
                        ('{$order_id}', '{$order_info['order_status']}', '1', '{$order_info['pay_status']}', NOW(), '操作发货', 'system')
                    ";
                    $db->query($sql);
                    // update_order_mixed_status($order_id, array('shipping_status' => 'shipped'), 'worker');
                    $handle = soap_get_client('ShipmentService');
                    $response = $handle->getShipmentByOrderId(array('orderId' => $order_info['order_id']));
                    if (!empty($response->return->Shipment)) {
                        $shipment = $response->return->Shipment;
                        try {
                            $handle=soap_get_client('ShipmentService');
                            $handle->updateShipment(array(
                                'shipmentId'=>$shipment->shipmentId,
                                'status'=>'SHIPMENT_SHIPPED',
                                'lastModifiedByUserLogin'=>'system',
                            ));
                        } catch (Exception $e) {
                            $info['msg'] = 'fail';
                            $info['back'] = "order_id: {$order_id} shipment SHIPMENT_SHIPPED error";
                            break;
                        }
                    } else {
                        echo date('c'). " order_id: ".$order_info['order_id'] ." shipment_id is not exists \n";
                    }
                }
                lock_release('shipment_pick-'.$shipment->shipmentId);
                $info['msg'] = 'success';
                $info['back'] = "order_id: {$order_id} 已发货";
            } else {
                $info['msg'] = 'fail';
                $info['back'] = "order_id: {$order_id} shipping_status:{$status['shipping_status']} is not valid";
            }
            break;
        default:
            $info['msg'] = 'fail';
            $info['back'] = "to_shipping_status: is not valid";
            break;
    }
    return $info;
}



/**
 * 修改订单状态 已配货，待出库  9
 * 修改订单状态 已出库 待发货  8
 * 已发货 1
 */
function update_shipping_status($order_id, $to_shipping_status) {
    global $db, $ecs;
    if (empty($order_id)) {
        $info['msg'] = 'fail';
        $info['back'] = "order_id: is empty";
        return $info;
    }
    if (empty($to_shipping_status)) {
        $info['msg'] = 'fail';
        $info['back'] = "to_shipping_status: is empty";
        return $info;
    }
    // require_once ROOT_PATH."admin/includes/lib_order_mixed_status.php";
    $status = $db->getRow("select order_status, shipping_status, pay_status
                            from ecshop.ecs_order_info where order_id = {$order_id} limit 1");
    switch ($to_shipping_status) {
        case 9:
            //已配货，待出库
            if ($status['shipping_status'] == 0 || $status['shipping_status'] == 10) {
                $sql = "UPDATE ecshop.ecs_order_info SET shipping_status=9 WHERE order_id='%d' LIMIT 1";
                $result = $db->exec(sprintf($sql, $order_id));
                if ($result) {
                    // 记录订单操作历史
                    $sql = "
                         INSERT INTO ecshop.ecs_order_action
                         (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES
                         ('{$status['order_id']}', '{$status['order_status']}', 9, '{$status['pay_status']}', NOW(), '%s', 'system')
                     ";
                    if ($status['shipping_status'] == 10) {
                        $db->exec(sprintf($sql, '重新配货出库'));
                        // update_order_mixed_status($order_id, array('warehouse_status'=>'re-picked'), 'worker');
                    } elseif ($status['shipping_status'] == 0) {
                        $db->exec(sprintf($sql, '配货出库'));
                        // update_order_mixed_status($order_id, array('warehouse_status'=>'picked'), 'worker');
                    }
                    $info['msg'] = 'success';
                    $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} success";
                } else {
                    $info['msg'] = 'fail';
                    $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} fail";
                }
            } else {
                $info['msg'] = 'fail';
                $info['back'] = "order_id: {$order_id} shipping_status: {$to_shipping_status} is not 0 or 10";
            }
            break;
        case 8:
            //已出库待发货
            if ($status['shipping_status'] == 9) {
                $party_id = $db->getOne("select party_id from ecshop.ecs_order_info where order_id = '{$order_id}'");
                if(in_array($party_id,array('65585','65597'))){
                    $info = NB_shipping($order_id);
                }else{
                    $info_sql = "SELECT oi.shipping_id,s.tracking_number from romeo.shipment s" .
                        " LEFT JOIN romeo.order_shipment os on os.SHIPMENT_ID = s.SHIPMENT_ID " .
                        " LEFT JOIN ecshop.ecs_order_info oi on oi.order_id = os.ORDER_ID " .
                        " where os.ORDER_ID = '{$order_id}'";
                    $info = $db->getRow($info_sql);
                }
                $res = update_shipping ($order_id, $info['shipping_id'], $info['tracking_number']);
                if ($res['msg'] == 'success' && !empty($info['tracking_number'])) {
                    //查找合并订单，并修改订单状态
                    $merge_order_sql = "
                        select s2.shipment_id, group_concat(s2.order_id) orderids
                        from romeo.order_shipment s1
                            inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id
                            inner join romeo.shipment s on s2.shipment_id = s.shipment_id
                        where s1.order_id = '%s'
                            and s.shipping_category = 'SHIPPING_SEND'
                            and s.status != 'SHIPMENT_CANCELLED'
                        group by s2.shipment_id" ;
                    $merge_orders = $db->getRow(sprintf($merge_order_sql, $order_id));
                    $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status='8', shipping_time=UNIX_TIMESTAMP() WHERE order_id in %s ;";
                    $res_order = $db->query(sprintf($sql, '('.$merge_orders['orderids'].')'));
                    if ($res_order) {
                        $sql = "select order_id, order_status, pay_status, shipping_status
                                from ecshop.ecs_order_info where order_id in %s ;";
                        $order_list = $db->getAll(sprintf($sql,  '('.$merge_orders['orderids'].')'));
                        foreach ($order_list as $order_item) {
                             // 记录订单备注
                            $sql = "
                                INSERT INTO {$ecs->table('order_action')}
                                (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES
                                ('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '已出库，待发货', 'system')
                            ";
                            $db->query($sql);

                            // 记录订单状态
                            // update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'delivered'), 'worker');
                        }
                        $info['msg'] = 'success';
                        $info['back'] = "order_id: {$order_id} 已出库待发货";
                    } else {
                        $info['msg'] = 'fail';
                        $info['back'] = "order_info shipping_status:8 error";
                    }
                } else {
                    $info = $res;
                }
            }
            break;
        case 1:
            if ($status['shipping_status'] == 8) {
                require_once ROOT_PATH . '/RomeoApi/lib_inventory.php';
                require_once ROOT_PATH . 'RomeoApi/lib_soap.php';
                //查找是否为合并订单
                $merge_order_sql = "
                    select s2.shipment_id, group_concat(s2.order_id) orderids
                    from romeo.order_shipment s1
                        inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id
                        inner join romeo.shipment s on s2.shipment_id = s.shipment_id
                    where s1.order_id = '%s'
                        and s.shipping_category = 'SHIPPING_SEND'
                        and s.status != 'SHIPMENT_CANCELLED'
                    group by s2.shipment_id" ;
                $merge_orders = $db->getRow(sprintf($merge_order_sql, $order_id));
                $order_ids = preg_split('/,/',  $merge_orders['orderids'], PREG_SPLIT_NO_EMPTY );
                foreach ($order_ids as $order_id) {
//                  $order_info = get_core_order_info('', $order_id);
                    $order_info = $db->getRow("select facility_id, order_status, shipping_status, pay_status, order_id
                            from ecshop.ecs_order_info where order_id = {$order_id} limit 1");

                     // 更改订单状态
                    $db->exec(sprintf("UPDATE ecshop.ecs_order_info SET shipping_time=UNIX_TIMESTAMP(), shipping_status=1 WHERE order_id='%d'",$order_id));
                    // 记录订单状态
                    orderActionLog(array('order_id'=>$order_id, 'order_status'=>$order_info['order_status'], 'shipping_status'=>1, 'pay_status'=>$order_info['pay_status'], 'action_note'=>'操作发货'));
                    // update_order_mixed_status($order_id, array('shipping_status' => 'shipped'), 'worker');
                    $handle = soap_get_client('ShipmentService');
                    $response = $handle->getShipmentByOrderId(array('orderId' => $order_info['order_id']));
                    if (!empty($response->return->Shipment)) {
                        $shipment = $response->return->Shipment;
                        try {
                            $handle=soap_get_client('ShipmentService');
                            $handle->updateShipment(array(
                                'shipmentId'=>$shipment->shipmentId,
                                'status'=>'SHIPMENT_SHIPPED',
                                'lastModifiedByUserLogin'=>'system',
                            ));
                        } catch (Exception $e) {
                            $info['msg'] = 'fail';
                            $info['back'] = "order_id: {$order_id} shipment SHIPMENT_SHIPPED error";
                            break;
                        }
                    } else {
                        echo date('c'). " order_id: ".$order_info['order_id'] ." shipment_id is not exists \n";
                    }
                }
                lock_release('shipment_pick-'.$shipment->shipmentId);
                $info['msg'] = 'success';
                $info['back'] = "order_id: {$order_id} 已发货";
            } else {
                $info['msg'] = 'fail';
                $info['back'] = "order_id: {$order_id} shipping_status:{$status['shipping_status']} is not valid";
            }
            break;
        default:
            $info['msg'] = 'fail';
            $info['back'] = "to_shipping_status: is not valid";
            break;
    }
    return $info;
}

/**
 * 检查快递单号
 * 返回erp $info 否则 返回false
 */
function NB_shipping ($order_id) {
    if (empty($order_id)) {
        $info['msg'] = 'fail';
        $info['back'] = "NB_shipping: order_id is not valid";
        return $info;
    }
    $sql = "
        select s.shipping_name, s.shipping_id, a.carrier_name, a.tracking_number
        from ecshop.ecs_indicate i
        left join ecshop.ecs_actual a on i.indicate_id = a.indicate_id
        left join ecshop.ecs_shipping s on i.shipping_id = s.shipping_id
        where order_id = '{$order_id}' and actual_status = 'FINISHED'
    ";
    global $db;
    $result = $db->getRow($sql);
    // todo newBalance传入快递方式
    $list = array(
        "EMS快递" => '47',
        "顺丰快递" => '44',
        "申通快递" => '89',
        "圆通快递" => '85',
        "顺丰（陆运）" => '117',
        "韵达快递" => '100',
        "全一快递" => '124',
        "德邦物流" => '120',
        "中通快递" => '115',
        "汇通快递" => '99',
    );
    $info['shipping_id'] = "";
    if ($result['shipping_name'] != $result['carrier_name']) {
        if (trim($result['shipping_name']) == "顺丰（陆运）" && trim($result['carrier_name']) == "顺丰快递") {
            $info['shipping_id'] = $list[$result['shipping_name']];
        } else {
            if (isset($list[$result['carrier_name']])) {
                $info['shipping_id'] = $list[$result['carrier_name']];
            }
        }
    }
    $info['tracking_number'] = $result['tracking_number'];
    return $info;
}
/**
 * 修改快递方式 如果快递单号为空则不修改
 */
function update_shipping ($order_id, $shipping_id, $tracking_number = null) {
    require_once ROOT_PATH. "includes/helper/array.php";
    if (empty($order_id)) {
        $info['msg'] = 'fail';
        $info['back'] = "update_shipping order_id is empty";
        return $info;
    }
    if (empty($shipping_id) && empty($tracking_number)) {
        $info['msg'] = 'fail';
        $info['back'] = "update_shipping shipping_id&tracking_number is empty";
        return $info;
    }
    global $db;
    $request = array();
    $action_note = $shipping_cond = "";
    // 获取原订单相关信息 做check
    $order_sql = "
        select oi.order_id, oi.order_sn, oi.pay_id, p.pay_code, p.is_cod, oi.shipping_id, oi.shipping_name, s.support_cod,
                s.support_no_cod, oi.order_status, oi.shipping_status, oi.pay_status, oi.party_id
        from ecshop.ecs_order_info oi
            left join ecshop.ecs_payment p on oi.pay_id = p.pay_id
            left join ecshop.ecs_shipping s on oi.shipping_id = s.shipping_id
        where oi.order_id = '{$order_id}' limit 1; ";
    $order_info = $db->getRow($order_sql);
    if (!empty($shipping_id)) {
        // 先判断是否支持一种付款方式
        $shipping_sql = "select shipping_id, shipping_name, support_cod, support_no_cod, default_carrier_id
                         from ecshop.ecs_shipping where shipping_id = %d limit 1" ;
        $shipping = $db->getRow(sprintf($shipping_sql, $shipping_id));
        // 如果支持的支付方式不统一， 则要返回
        if($order_info['support_cod'] != $shipping['support_cod'] || $order_info['support_no_cod'] != $shipping['support_no_cod']){
            $info['msg'] = 'fail';
            $info['back'] = '修改的快递公司收款方式，与原订单中收款方式不一样';
            return $info;
        }
        $request = array(
            'carrierId' => $shipping['default_carrier_id'],
            'shipmentTypeId' => $shipping_id);

        $shipping_cond .= "
            oi.shipping_id = ".intval($shipping['shipping_id'])." ,
            oi.shipping_name = '{$shipping['shipping_name']}'
        ";
        $shippings = $db->getAll("SELECT * FROM ecshop.ecs_shipping
                WHERE enabled = 1 ORDER BY shipping_code, support_cod");
        $shippings = Helper_Array::toHashmap((array)$shippings, 'shipping_id');
        $origin_shippingname = $shippings[$order_info['shipping_id']]['shipping_name'];
        $new_shippingname = $shippings[$shipping_id]['shipping_name'];
        $action_note .= "物流修改快递 : 从 $origin_shippingname 修改为  $new_shippingname ";
    }

    // 查看合并订单
    $merge_order_sql = "
        select s2.shipment_id, group_concat(s2.order_id) orderids
        from romeo.order_shipment s1
            inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id
            inner join romeo.shipment s on s2.shipment_id = s.shipment_id
        where s1.order_id = '%s'
            and s.shipping_category = 'SHIPPING_SEND'
            and s.status != 'SHIPMENT_CANCELLED'
        group by s2.shipment_id" ;
    $merge_orders = $db->getRow(sprintf($merge_order_sql, $order_info['order_id']));
    $cond = "";
    if (!empty($tracking_number)) {
//      $cond .= " b.bill_no = '{$tracking_number}' ";
        $request['trackingNumber'] = $tracking_number;
        $action_note .= "快递单号：".$tracking_number;
    }
    $request =  array_merge($request, array(
        'shipmentId' => $merge_orders['shipment_id'],
        'lastModifiedByUserLogin'=>'system',
    ));
    if (!empty($shipping_cond)) {
//      $shipping_cond .= " b.carrier_id = {$shipping['default_carrier_id']} ";
        if (!empty($cond)) {
            $shipping_cond .= ", ";
        }

    }
    // 更新快递
                // killed by Sinri 20160105
                // $sql = "update ecshop.ecs_order_info oi, ecshop.ecs_carrier_bill b
                // set ". $shipping_cond . $cond ."
                // where oi.carrier_bill_id = b.bill_id and oi.order_id in %s ;";
                $sql = "UPDATE ecshop.ecs_order_info oi 
                SET {$shipping_cond}
                WHERE oi.order_id in %s";
                $db->exec(sprintf($sql, '('.$merge_orders['orderids'].')'));

    include_once(ROOT_PATH. "RomeoApi/lib_inventory.php");

    try {
        $handle=soap_get_client('ShipmentService');
        $handle->updateShipment($request);
    } catch (Exception $e) {
        $info['msg'] = 'fail';
        $info['back'] = "order_id: {$order_id} ShipmentService error";
        return $info;
    }

    // 记录保存到
    $orderids =  preg_split('/,/',  $merge_orders['orderids'], PREG_SPLIT_NO_EMPTY );
    $order_attribute_sql = "insert into ecshop.order_attribute (order_id, attr_name, attr_value) values (%d, 'SHIPMENT', '%s'); " ;
    $order_action_sql = "insert into ecshop.ecs_order_action (order_id, action_user, order_status, shipping_status, pay_status, action_time, action_note) values (%d, '%s', %d, %d, %d, now(), '%s')";

    foreach($orderids as $item){
        if (!empty($tracking_number)) {
            $db->query(sprintf($order_attribute_sql, intval($item), $tracking_number)) ;
        }
        $db->query(sprintf($order_action_sql,  intval($item), system,
                               intval($order_info['order_status']), intval($order_info['shipping_status']), intval($order_info['pay_status']), $action_note));
    }
    $info['msg'] = 'success';
    $info['back'] = "order_id: {$order_id} update_shipping success";
    return $info;
}

/**
 * 追加面单,兼容追加和更新面单
 *  
 */
function add_order_shipment($shipment_id,$tracking_number,$add_type='add',$pack_weight=null) {
    global $db,$ecs;
    do {
        $_SESSION['admin_name']=isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
        
        if(empty($tracking_number)) {
            $message .= "该发货单（shipmentId:{$shipment_id}）没有对应的面单号;";
            break;  
        }
    
        // 如果传递了发货单号则查询相关信息
        $handle = soap_get_client('ShipmentService');
        $response = $handle->getShipment(array('shipmentId' => $shipment_id));
        $shipment = is_object($response->return) ? $response->return : null;
        if (!$shipment){
            $message .= "该发货单（shipmentId:{$shipment_id}）不存在;";
            break;   
        } 
        
        //没有预订的就不让发货
        $sql =" SELECT oir.status, oir.order_id
                FROM
                    romeo.order_inv_reserved oir
                LEFT JOIN romeo.order_shipment os ON oir.order_id = os.order_id
                WHERE
                    os.shipment_id = '{$shipment_id}'";
        $status = $db->getAll($sql);
        $status = $status[0];
        if ($status['status'] == "N" || $status == null) { 
            $message .= "该发货单（shipmentId:{$shipment->shipmentId}）对应的订单未预订成功（orderId:{$status['order_id']}）;";
            break;   
        }
        
        // 取得发货单的主订单信息
        // 取得发货单的所有订单信息
        // 如果是没有合并发货的订单，查找其发货单信息
        $order = null;
        $order_list = array();
        $shipment_list = array($shipment);
        $order_shipment_list = array();
        
        // 取得发货单的所有订单信息
        $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
        if (is_array($response2->return->OrderShipment)) {
            $order_shipment_list = $response2->return->OrderShipment;
        } elseif (is_object($response2->return->OrderShipment)){
            $order_shipment = $response2->return->OrderShipment;
            $order_shipment_list[] = $order_shipment;
        } else{
            $message .= "该发货单（shipmentId:{$shipment->shipmentId}）异常，找不到对应的主订单;";
            break;   
        }
        
        $i = 0;
        foreach($order_shipment_list as $orderShipment) {
            $order_list[$i] = get_core_order_info('', $orderShipment->orderId);
            if ($shipment->primaryOrderId == $orderShipment->orderId){
                $order = $order_list[$i];
                $response3 = $handle->getShipmentByOrderId(array('orderId' => $order['order_id']));
                if (is_array($response3->return->Shipment)) {
                    foreach($response3->return->Shipment as $_shipment){
                        if ($_shipment->primaryOrderId != $shipment->primaryOrderId){
                            $shipment_list[] = $_shipment;
                        } elseif ($_shipment->shipmentId != $shipment->shipmentId) {
                            $shipment_list[] = $_shipment;
                        }
                    }
                }
            }
            $i++;
        }
    
        // 复核过才能添加
        if (in_array($order['shipping_status'],array(1,2,8,9))) {
            $trackingNumberFrom=$trackingNumberTo=array();
            $trackingNumberChanged=true;
            $trackingNumber = $tracking_number;
            
            // 添加面单号（分开多个面单发货）
            foreach($shipment_list as $shipment_item){
                $trackingNumberFrom[]=$shipment_item->trackingNumber;
                $trackingNumberTo[]=$shipment_item->trackingNumber;
            }
            $trackingNumberTo[] = $trackingNumber;
            
            try{
                $handle=soap_get_client('ShipmentService' );
                var_dump($add_type);
                if($add_type == 'add') {
                    $handle=soap_get_client('ShipmentService' ,'ERPSYNC');
                    $object=$handle->createShipment(array(
                        'orderId' => $order['order_id'],
                        'partyId' => $shipment->partyId,
                        'shipmentTypeId'=>$shipment->shipmentTypeId,
                        'carrierId'=>$shipment->carrierId,
                        'trackingNumber'=>$trackingNumber,
                        'shippingLeqeeWeight'=>$pack_weight,
                        'createdByUserLogin'=>$_SESSION['admin_name']
                    ));
                    
                    $handle->createOrderShipment(array(
                        'orderId'   =>$order['order_id'],
                        'shipmentId'=>$object->return,
                    ));
                } else if($add_type == 'update') {
                    $handle->updateShipment(array(
                        'shipmentId'=>$shipment->shipmentId,
                        'trackingNumber'=>$trackingNumber,
                        'shippingLeqeeWeight'=>$pack_weight,
                        'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                    )); 
                } else {
                     $message .= "该发货单（shipmentId:{$shipment->shipmentId}）不支持的修改类型:".$add_type;
                     break;   
                }
                    
            }
            catch (Exception $e){
                 $message .= "该发货单（shipmentId:{$shipment->shipmentId}）更新或追加异常:".$e->getMessage();
                 break;   
            }
    
            
            // 面单发生更改则更新
            if($trackingNumberChanged){
                // 修改提示语 ljzhou 2012.12.6
                $modify = array_diff ( $trackingNumberFrom, $trackingNumberTo );
                $add = array_diff ( $trackingNumberTo, $trackingNumberFrom );
                if (count ( $modify ) > 0) {
                    $note = "批量追加：快递面单从" . implode ( ',', $modify ) . "更改为" . implode ( ',', $add );
                } else {
                    $note = "批量追加：增加了快递面单" . implode ( ',', $add );
                }
                // 更新运单的运单号信息
                foreach($order_list as $order_item) {
                                                                                // killed by Sinri 20160105
                    // $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'",implode(',',$trackingNumberTo),$order_item['carrier_bill_id']));
                    // 记录订单备注
                    if(!isset($_SESSION['admin_name'])) {
                        $_SESSION['admin_name']='cronjob';
                    }
                    $sql = "
                        INSERT INTO {$ecs->table('order_action')} 
                        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                        ('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
                    ";
                    $db->query($sql);
                }
                
            }
            else{
                $message .= "该发货单（shipmentId:{$shipment->shipmentId}）面单号没有改变;";
                break;   
            }
        }
        // 不是可以扫面单的状态了
        else {
            $message .= "该发货单未复核，必须复核过才能添加面单：".$shipment_id.";";
            break;   
        }
    } while(false);
    
    $result['res'] = 'success';
    if($message) {
        $result['res'] = 'fail';
    }
    $result['message'] = $message;
    
    return $result;
        
}
/**
 * 添加单个耗材商品
 */
function auto_add_goods($info) {
    if (empty($info)){
        $result['msg'] = "add goods param is empty";
        $result['code'] = "error";
        return $result;
    }
    if (empty($info['order_id'])) {
        $result['msg'] = "add goods order_id is empty";
        $result['code'] = "error";
        return $result;
    }
    if (empty($info['goods_id'])) {
        $result['msg'] = "add goods goods_id is empty";
        $result['code'] = "error";
        return $result;
    }
    global $db;
    // 已经添加了则不需添加
    $sql = "select 1 from ecshop.ecs_order_goods where order_id = '{$info['order_id']}' and goods_id = '{$info['goods_id']}' limit 1";
    $has_add = $db->getOne($sql);
    if(!empty($has_add)) {
        require_once (ROOT_PATH . 'includes/debug/lib_log.php');
        QLog::log('auto_add_goods already exists:order_id = '.$info['order_id'].' goods_id = '.$info['goods_id']);
        $result['msg'] = "order_id:".$info['order_id']." goods_id:".$info['goods_id']." already exists";
        $result['code'] = "success";
        return $result;
    }

    //检查是否有库存，无库存时不需要添加
    $sql = 
        "SELECT
            ifnull(sum(ii.quantity_on_hand_total),0) as stock_quantity
        FROM romeo.inventory_item ii
        INNER JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
        INNER JOIN ecshop.ecs_goods AS g ON g.goods_id = pm.ecs_goods_id
        WHERE ii.facility_id = '{$info['facility_id']}' and pm.ecs_goods_id = '{$info['goods_id']}' and pm.ecs_style_id = 0
        AND ii.quantity_on_hand_total > 0 AND ii.status_id = 'INV_STTS_AVAILABLE'
    ";
    //Qlog::log('$stock_quantity:sql:'.$sql);
    $stock_quantity = $db->getOne($sql);
    if ($stock_quantity < $info['goods_number']) {
        $result['msg'] = "库存不足,只有{$stock_quantity}个，实际上需要{$info['goods_number']}";
        $result['code'] = "error";
        return $result;
    }
        $order_goods = new stdClass();
        $order_goods->orderId = $info['order_id'];

        $original_goodsPrice = $order_goods->goodsPrice;
        $original_goodsNumber = $order_goods->goodsNumber;
        $original_customized = $order_goods->customized;
        $original_styleId = $order_goods->styleId;
        $original_goodsId = $order_goods->goodsId;
        $original_goodsName = $order_goods->goodsName;

        $order_goods->goodsId = $info['goods_id'];
        $order_goods->goodsPrice = $info['goods_price'];
        $order_goods->goodsNumber = $info['goods_number'];
        $order_goods->styleId = $info['style_id'];
        $order_goods->goodsName = get_order_goods_name($order_goods->goodsId, $order_goods->styleId);

        require_once ROOT_PATH .'admin/includes/lib_goods.php';

        $goods_info = get_goods_style_info($order_goods->goodsId, $order_goods->styleId);


        if ($original_goodsId != $order_goods->goodsId && $goods_info['is_remains'] && $goods_info['goods_number'] < $order_goods->goodsNumber) {
            $result['msg'] = "{$order_goods->goodsName} 库存不足，请修改数量";
            $result['code'] = "error";
            return $result;
        }

        $status_id_list = array(
            'INV_STTS_AVAILABLE'=>'新品',
            'INV_STTS_USED'     =>'二手'
        );

        // 性能考虑，改为从romeo调 ljzhou 2013.03.26
        $handle = soap_get_client ( "OrderService" );
        $res = $handle->addOrderGoodsNew ( array ('orderItem' => $order_goods ) );
        if(!$res) {
            echo "addOrderGoods false:".$order_goods;
            $result['msg'] = "addOrderGoods false";
            $result['code'] = "error";
            return $result;
        }
        // include_once(ROOT_PATH .'admin/includes/lib_order_mixed_status.php');
        // update_order_mixed_status_note($info['order_id'], 'worker', $order_goods->actionNote);
        // Does it need insert order action record?

        $result['msg'] = "order_id:".$info['order_id']." goods_id:".$info['goods_id']." add success";
        $result['code'] = "success";
        return $result;
}


/**
 * 添加耗材，并自动出库(相当于ajax_pick_up) ljzhou 2013.03.25
 */
function consumable_pick_up($barcode,$tracking_number,$facility_id) {
    $tysp_party_id = get_tysp_party_id();
    $flag = strpos($tracking_number, ",");
    if ($flag !== false) {
        $tracking_number = explode(",", $tracking_number);
    } else {
        $tracking_number= array($tracking_number);
    }

    if (empty($tracking_number)) {
        return "tracking_number is empty";
    }
    require_once (ROOT_PATH . 'includes/debug/lib_log.php');
    global $db;
    $goods = $db->getRow("select g.goods_id, g.goods_name
        from romeo.shipment s
        left join ecshop.ecs_goods g on (CONVERT(g.goods_party_id USING utf8 ) = s.party_id ) OR g.goods_party_id = $tysp_party_id
        where g.barcode = '{$barcode}' and g.is_on_sale = 1 and g.is_delete = 0 and s.tracking_number ". db_create_in ($tracking_number) ."
        limit 1");
    $goods_name = $goods['goods_name'];
    $goods_id = $goods['goods_id'];
    $sql = "select group_concat(distinct os.order_id SEPARATOR ',') as order_list, s.party_id, s.tracking_number
        from romeo.shipment s
        left join romeo.order_shipment os on s.shipment_id = os.shipment_id
        where s.tracking_number ". db_create_in ($tracking_number). "
        group by s.shipment_id
        order by order_id asc
    ";
    $list = $db->getAll($sql);
    $tracking_number_return = "";
    $tracking_number_error = "";
    echo "[" . date ( 'c' ) . "] " . "AutoDeliveryConsumable  consumable_pick_up: " .  " barcode：" . $barcode . "\n" . " list:";
    var_dump($list);
    foreach($list as $shipment){
        $party_id = $shipment['party_id'];
        $flag = strpos($shipment['order_list'], ",");
        $order_list = array();
        if ($flag !== false) {
            $order_list = explode(",",$shipment['order_list']);
        } else {
            $order_list[] = $shipment['order_list'];
        }
        var_dump($order_list);
        //合并订单 只对一个商品添加耗材，其他商品添加操作记录
        foreach($order_list as $key=>$order_id){
            if ($key == 0) {
                $info = array(
                    'order_id' => $order_id,
                    'goods_id' => $goods_id,
                    'goods_price' => '0.00',
                    'goods_number' => "1",
                    'style_id' => "0",
                    'party_id' => $party_id,
                    'customized' => 'not-applicable',
                    'facility_id' => $facility_id,
                );
                echo "[" . date ( 'c' ) . "] " . "AutoDeliveryConsumable  consumable_pick_up-order_list: " .  " order_id：" . $order_id ."\n";

                $result = auto_add_goods($info);
                if ($result['code'] != 'success') {
                    $tracking_number_error .= $shipment['tracking_number'].",";
                    QLog::log ("order_id: ".$order_id." 添加耗材:".$result['msg']);
                    break 1;
                } else {
                    //自动出库 查询没有出库的耗材商品
                    $sql = "
                        select o.order_id, pm.product_id
                        from ecshop.ecs_order_info o
                        inner join ecshop.ecs_order_goods og on o.order_id = og.order_id
                        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                        inner join ecshop.ecs_goods g on og.goods_id = g.goods_id
                        inner join ecshop.ecs_category c on g.cat_id = c.cat_id
                        where o.order_id = '{$order_id}' and c.cat_name = '耗材商品'
                        and not exists(select 1 from romeo.inventory_item_detail iid where convert(og.rec_id using utf8) = iid.order_goods_id limit 1)
                        group by og.goods_id,og.style_id
                    ";
                    $order_product = $db->getRow($sql);
                    if(empty($order_product)) {
                        $tracking_number_error .= $shipment['tracking_number'].",";
                        break 1;
                    }

                    $is_error = true;
                    echo "[" . date ( 'c' ) . "] " . "AutoDeliveryConsumable  consumable_pick_up-order_list: " .  " goods_list：" ."\n";
                    var_dump($order_product);
                    $order_list_out = array();
                    $real_out_goods_numbers = array();
                    $order_list_out[] = get_core_order_info('', $order_product['order_id']);
                    $real_out_goods_numbers[$order_product['product_id']] = 1;
                    require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
                    $result = stock_delivery($order_list_out,$real_out_goods_numbers,null);
                    if(!empty($result['error'])) {
                        QLog::log ("order_id: ".$order_id." 耗材出库: 失败 " . $result['error']);
                        $tracking_number_error .= $shipment['tracking_number'].",";
                        break 1;
                    } else {
                        $tracking_number_return .= $shipment['tracking_number'].",";
                    }
                }
            } else {
                $sql = "
                    SELECT order_sn, order_status, pay_status, shipping_status, invoice_status,
                        order_type_id, party_id, facility_id, pay_id, shipping_id
                    FROM ecshop.ecs_order_info WHERE order_id = '{$order_id}'
                    limit 1
                ";
                $row = $db->getRow($sql);
                $order_goods->actionNote = "系统添加耗材商品：". $goods_name . " 数量：1";
                $order_action_status['order_id'] = $order_id;
                $order_action_status['order_status'] = $row['order_status'];
                $order_action_status['pay_status'] = $row['pay_status'];
                $order_action_status['shipping_status'] = $row['shipping_status'];
                $order_action_status['invoice_status'] = $row['invoice_status'];
                $order_action_status['action_user'] = 'system';
                $order_action_status['action_time'] = date("Y-m-d H:i:s", time());
                $order_action_status['action_note'] = $order_goods->actionNote;
                orderActionLog($order_action_status);
                // require_once (ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
                // update_order_mixed_status_note($order_id, 'worker', $order_goods->actionNote);
            }
        }
    }
    $number_return['tracking_number'] = $tracking_number_return;
    $number_return['tracking_number_error'] = $tracking_number_error;
    return $number_return;
}

/**
 *
 * 根据条码，仓库得到全新库存的库存数(有通用耗材情况，所以商品状态为未删除) ljzhou 2013.03.25
 * @param unknown_type $barcode
 * @param unknown_type $facility
 */
function get_available_storage($barcode, $facility) {
    global $db;
    $sql = 
        "SELECT
            ifnull(sum(ii.quantity_on_hand_total),0) as stock_quantity
        FROM romeo.inventory_item ii
        INNER JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
        INNER JOIN ecshop.ecs_goods AS g ON g.goods_id = pm.ecs_goods_id
        WHERE ii.facility_id = '{$facility}' AND g.barcode = '{$barcode}'
        AND ii.quantity_on_hand_total > 0 AND ii.status_id = 'INV_STTS_AVAILABLE' AND g.is_delete = '0'
    ";

    $storage_amount = $db->getOne ( $sql );

    return $storage_amount;
}

/**
 *
 * 更新条码运单映射表 ljzhou 2013.03.25
 * @param unknown_type $barcode
 * @param unknown_type $tracking_number
 */
function update_barcode_tracking_mapping($barcode, $tracking_number) {
    global $db;
    $sql = "update ecshop.ecs_barcode_tracking_mapping set is_pick_up = 'Y',last_updated_stamp = now() where barcode = '{$barcode}' and tracking_number = '{$tracking_number}' limit 1";
    $db->query($sql);
}


/**
 *
 * 移库位，条码对应商品 的库位有则更新库位，无则直接添加到库位 ljzhou 2013.04.17
 * @param unknown_type $party_id
 * @param unknown_type $barcode
 * @param unknown_type $to_location
 * @param unknown_type $facility_id
 */
function change_facility_location($party_id, $barcode, $to_location, $facility_id) {
    $message = array();

    global $db;
    $sql = "select
                 g.goods_id,ifnull(gs.style_id,0) as style_id
            from ecshop.ecs_goods g
            left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id
            where
                 g.goods_party_id = '{$party_id}' and (g.barcode = '{$barcode}' OR gs.barcode = '{$barcode}')
            limit 1
    ";
    $item = $db->getRow($sql);
    if(empty($item)) {
        $message['error'] .= " 该条码找不到对应商品：".$barcode;
        return false;
    }

    $sql = "select
                *
            from romeo.product_facility_location
            where
            goods_id='{$item['goods_id']}' and style_id='{$item['style_id']}'
            limit 1
     ";
    $origin_location = $db->getRow($sql);

    if($origin_location['LOCATION_SEQ_ID'] == $to_location) {
       return true;
    }

    if (! empty ( $origin_location )) {
        $sql = "update romeo.product_facility_location set LOCATION_SEQ_ID ='{$to_location}',LAST_UPDATED_STAMP=now(),LAST_UPDATED_TX_STAMP=now() where LOCATION_SEQ_ID ='{$origin_location['LOCATION_SEQ_ID']}' and PRODUCT_ID = '{$origin_location['PRODUCT_ID']}' and FACILITY_ID = '{$origin_location['FACILITY_ID']}' limit 1";
        $db->query ( $sql );
    } else {
        require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
        $product_id = getProductId($item['goods_id'],$item['style_id']);
        $sql = "INSERT INTO romeo.product_facility_location (LOCATION_SEQ_ID,PRODUCT_ID,FACILITY_ID,CREATED_STAMP,GOODS_ID,STYLE_ID,LAST_UPDATED_STAMP,CREATED_TX_STAMP,LAST_UPDATED_TX_STAMP)
         VALUES ('{$to_location}', '{$product_id}', '{$facility_id}',now(), '{$item['goods_id']}', '{$item['style_id']}',now(),now(),now())";
        $db->query ( $sql );
    }

    return true;

}

 /*
 * 判断当前组织是否为节点组织  ljzhou 2013.04.23
 */
 function check_leaf_party ($party_id) {
    global $db ;
    $sql = "
        select
              1
        from romeo.party
        where party_id = '{$party_id}' and IS_LEAF = 'Y'
    ";
    $is_leaf = $db->getOne($sql) ;
    if(empty($is_leaf)) {
        return false;
    }
    return true;
 }

/**
 * 得到组织需要同步的 店铺id
 */
function get_party_distributors($party_id,&$default_distributor_id) {
    global $db ;
    $sql = "select s.nick,s.distributor_id from ecshop.taobao_shop_conf s where party_id = '{$party_id}' ";
    $distributor_nicks = $db->getAll($sql);
    if(empty($distributor_nicks)) return null;
    
    $result = array();
    foreach($distributor_nicks as $distributor_nick) {
        $result[$distributor_nick['distributor_id']] = $distributor_nick['nick'];
    }
    
    $default_distributor_id = $distributor_nicks[0]['distributor_id'];
    
    return $result;
}

 /*
 * 得到组织同步时的默认仓库列表 ljzhou 2013.04.23
 */
 function get_default_facility_list ($party_id) {
    global $db ;

    $sql = "select facility_id from ecs_party_assign_facility where party_id = '{$party_id}' ";

    $default_facility_list = $db->getCol($sql) ;

    return $default_facility_list ;
 }

 /*
 * 得到店铺同步时的默认仓库列表
 */
 function get_distributor_default_facility_list ($distributor_id) {
    global $db ;

    $sql = "select facility_id from ecs_distributor_assign_facility where distributor_id = '{$distributor_id}' ";

    $default_facility_list = $db->getCol($sql) ;

    return $default_facility_list ;
 }


 /*
 * 得到组织展示快递列表 ljzhou 2013.04.23
 */
 function get_show_shipping_list ($party_id) {
    global $db ;

    $sql = "select shipping_id from ecs_party_assign_show_shipping where party_id = '{$party_id}' ";

    $show_shipping_list = $db->getCol($sql) ;

    return $show_shipping_list ;
 }


 /*
 * 得到省份列表 ljzhou 2013.04.23
 */
 function get_region_list () {
    global $db ;
    $sql = "select region_id,region_name from ecshop.ecs_region where parent_id = 1";
    $region_list = $db->getAll($sql);
    $list = array();
    foreach ($region_list as $region) {
        $list[$region['region_id']] = $region['region_name'];
    }
    return $list;
 }

  /*
 *直销： 得到组织地区默认仓库列表 ljzhou 2013.04.23
 */
 function get_region_facility_list ($party_id,$region_id) {
    global $db ;
    $sql = "select facility_id from ecshop.ecs_party_region_assign_facility where party_id = {$party_id} and region_id = {$region_id}";
    $region_facility_list = $db->getCol($sql) ;

    return $region_facility_list;
 }
   /*
 *得到店铺地区默认仓库列表 ljzhou 2013.04.23
 */
 function get_distributor_region_facility_list ($distributor_id,$region_id) {
    global $db ;
    $sql = "select facility_id from ecshop.ecs_distributor_region_assign_facility where distributor_id = {$distributor_id} and region_id = {$region_id}";
    $region_facility_list = $db->getCol($sql) ;

    return $region_facility_list;
 }
 
 /*
 * 分销：得到组织地区默认仓库列表 zxcheng 2013.09.04
 */
 function get_dtb_region_facility_list ($party_id,$region_id) {
    global $db ;
    $sql = "select facility_id from ecshop.ecs_distribution_facility where party_id = {$party_id}
                    and region_id = {$region_id}
                    and store_type = 'taobao'
                    and type = 'fenxiao'
                    ";
    //Qlog::log('get_dtb_region_facility_list='.$sql);
    $region_facility_list = $db->getCol($sql) ;

    return $region_facility_list;
 }
 /*
 * 分销：得到组织地区默认快递列表 zxcheng 2013.09.04
 */
 function get_dtb_region_shipping_list ($party_id,$region_id) {
    global $db ;
    $sql = "select shipping_id from ecshop.ecs_distribution_shipping where party_id = {$party_id}
                    and region_id = {$region_id}
                    and store_type = 'taobao'
                    and type = 'fenxiao'
                    ";
    $region_shipping_list = $db->getCol($sql) ;

    return $region_shipping_list;
 }
 /*
  * 分销：得到商城拍下配送方式列表
  */
 function get_pat_shipping_list(){
    global $db;
    $sql = "SELECT pat_id,pat_shipping_name FROM ecshop.ecs_pat_shipping";
    $pat_shipping_list = $db->getAll($sql);
    $list = array();
    foreach ($pat_shipping_list as $pat_shipping) {
        $list[$pat_shipping['pat_id']] = $pat_shipping['pat_shipping_name'];
    }
    return $list;
 }
 /*
 * 分销：得到商城拍下快递列表 zxcheng 2013.09.04
 */
 function get_dtb_pat_shipping_list ($party_id,$pat_id) {
    global $db ;
    $sql = "select shipping_id from ecshop.ecs_store_pat_shipping where party_id = {$party_id}
            and pat_id = {$pat_id}
            and type = 'fenxiao'
            and store_type = 'taobao'";
    $pat_shipping_list = $db->getCol($sql) ;

    return $pat_shipping_list;
 }
 /*
 * 直销：得到组织地区默认快递列表 ljzhou 2013.04.23
 */
 function get_region_shipping_list ($party_id,$region_id) {
    global $db ;
    $sql = "select shipping_id from ecshop.ecs_party_region_assign_shipping where party_id = {$party_id} and region_id = {$region_id}";
    $region_shipping_list = $db->getCol($sql) ;

    return $region_shipping_list;
 }

 /*
 * 直销：得到店铺地区默认快递列表 ljzhou 
 */
 function get_distributor_region_shipping_list ($distributor_id,$region_id) {
    global $db ;
    $sql = "select shipping_id from ecshop.ecs_distributor_region_assign_shipping where distributor_id = {$distributor_id} and region_id = {$region_id}";
    $region_shipping_list = $db->getCol($sql) ;

    return $region_shipping_list;
 }
 
 /*
 * 直销：得到店铺默认快递列表 qyyao 
 */
 function get_distributor_default_shipping_list ($distributor_id) {
    global $db ;
    $sql = "select shipping_id from ecshop.taobao_shop_conf where distributor_id = {$distributor_id} ";
    $default_shipping_list = $db->getCol($sql) ;

    return $default_shipping_list;
 }
 
 /*
 * 直销：得到店铺默认仓库列表 qyyao 
 */
 function get_distributor_default_facility_list2 ($distributor_id) {
    global $db ;
    $sql = "select facility_id from ecshop.taobao_shop_conf where distributor_id = {$distributor_id} ";
    $default_facility_list = $db->getCol($sql) ;

    return $default_facility_list;
 }



 /*
 *直销： 得到组织地区默认快递展示列表（所以不分地区） ljzhou 2013.04.23
 */
 function get_party_region_assign_shipping_list ($party_id) {
    global $db ;
    $sql = "
        select
              rs.shipping_id,rs.region_id,es.shipping_name,r.region_name 
              , rp.region_id parent_region_id, rpp.region_id grand_parent_region_id , r.region_name 
        , rp.region_name parent_region_name, rpp.region_name grand_parent_region_name , 
    if( rpp.region_id > 1 , rpp.region_id , IF(rpp.region_id =1 , rp.region_id , r.region_id )) province , 
    if( rpp.region_id > 1 , rp.region_id , IF(rpp.region_id = 1 , r.region_id , 0 )) city , 
    if( rpp.region_id > 1 , r.region_id , 0 ) district  
        from ecshop.ecs_party_region_assign_shipping rs
        left join ecshop.ecs_shipping es ON rs.shipping_id = es.shipping_id
        left join ecshop.ecs_region r ON rs.region_id = r.region_id
        left JOIN ecshop.ecs_region rp on rp.region_id = r.parent_id
        LEFT JOIN ecshop.ecs_region rpp on rpp.region_id = rp.parent_id
        where rs.party_id = {$party_id}
        order by  province , city ,district
    ";
    $party_region_assign_shipping_list = $db->getAll($sql) ;

    return $party_region_assign_shipping_list;
 }
 
 /*
 *得到店铺默认快递展示列表
 */
 function get_distributor_default_assign_shipping_list ($distributor_id) {
    global $db ;
    $sql = "
        select
              tsc.shipping_id,es.shipping_name
        from ecshop.taobao_shop_conf tsc
        left join ecshop.ecs_shipping es ON tsc.shipping_id = es.shipping_id
        where tsc.distributor_id = {$distributor_id}
        order by tsc.shipping_id
    ";
    $distributor_default_assign_shipping_list = $db->getAll($sql) ;

    return $distributor_default_assign_shipping_list;
 }
 /*
 *得到店铺默认仓库展示列表
 */
 function get_distributor_default_assign_facility_list ($distributor_id) {
    global $db ;
    $sql = "
        select
              tsc.facility_id,f.facility_name
        from ecshop.taobao_shop_conf tsc
        left join romeo.facility f ON tsc.facility_id = f.facility_id
        where tsc.distributor_id = {$distributor_id}
        order by tsc.facility_id
    ";
    $distributor_default_assign_facility_list = $db->getAll($sql) ;

    return $distributor_default_assign_facility_list;
 }
 
 /*
 *得到店铺区域默认快递展示列表
 */
 function get_distributor_region_assign_shipping_list ($distributor_id) {
    global $db ;
//  $sql = "
//      select
//            rs.shipping_id,rs.region_id,es.shipping_name,r.region_name
//      from ecshop.ecs_distributor_region_assign_shipping rs
//      left join ecshop.ecs_shipping es ON rs.shipping_id = es.shipping_id
//      left join ecshop.ecs_region r ON rs.region_id = r.region_id
//      where rs.distributor_id = {$distributor_id}
//      order by rs.shipping_id,rs.region_id
//  ";
    $sql = "
        select
              rs.shipping_id,rs.region_id, es.shipping_name, rp.region_id parent_region_id, rpp.region_id grand_parent_region_id , r.region_name 
        , rp.region_name parent_region_name, rpp.region_name grand_parent_region_name , 
    if( rpp.region_id > 1 , rpp.region_id , IF(rpp.region_id =1 , rp.region_id , r.region_id )) province , 
    if( rpp.region_id > 1 , rp.region_id , IF(rpp.region_id = 1 , r.region_id , 0 )) city , 
    if( rpp.region_id > 1 , r.region_id , 0 ) district 
        from ecshop.ecs_distributor_region_assign_shipping rs
        left join ecshop.ecs_shipping es ON rs.shipping_id = es.shipping_id
        left join ecshop.ecs_region r ON rs.region_id = r.region_id
        left JOIN ecshop.ecs_region rp on rp.region_id = r.parent_id
        LEFT JOIN ecshop.ecs_region rpp on rpp.region_id = rp.parent_id
        where rs.distributor_id =  {$distributor_id}
        order by  province , city ,district
    ";
    $distributor_region_assign_shipping_list = $db->getAll($sql) ;

    return $distributor_region_assign_shipping_list;
 }
 
 /*
 * 分销 ：得到组织地区默认快递展示列表（所以不分地区） zxcheng 2013.09.4
 */
 function get_distribution_shipping_list ($party_id) {
    global $db ;
    $sql = "
        select
              ds.shipping_id,ds.region_id,es.shipping_name,r.region_name
        from ecshop.ecs_distribution_shipping ds
        left join ecshop.ecs_shipping es ON ds.shipping_id = es.shipping_id
        left join ecshop.ecs_region r ON ds.region_id = r.region_id
        where ds.party_id = {$party_id}
              and ds.store_type = 'taobao'
              and ds.type = 'fenxiao'
        order by ds.shipping_id,ds.region_id
    ";
    //Qlog::log('get_distribution_shipping_list='.$sql);
    $distribution_shipping_list = $db->getAll($sql) ;

    return $distribution_shipping_list;
 }
 /*
 * 分销：得到组织地区默认仓库展示列表（所以不分地区） zxcheng 2013.09.04
 */
 function get_distribution_facility_list ($party_id) {
    global $db ;
    $sql = "
        select
              df.facility_id,df.region_id,f.facility_name,r.region_name
        from ecshop.ecs_distribution_facility df
        left join ecshop.ecs_region r ON df.region_id = r.region_id
        left join romeo.facility f ON df.facility_id = f.facility_id
        where df.party_id = {$party_id}
                and df.store_type = 'taobao'
                and df.type = 'fenxiao'
        order by df.facility_id,df.region_id
    ";
    //Qlog::log('get_distribution_facility_list='.$sql);
    $distribution_facility_list = $db->getAll($sql) ;

    return $distribution_facility_list;
 }
 /*
 * 分销 ：的淘宝拍下快递列表  zxcheng 2013.09.4
 */
 function get_distribution_pat_shipping_list ($party_id) {
    global $db ;
    $sql = "
        select
              tps.shipping_id,tps.pat_id,es.shipping_name,ps.pat_shipping_name
        from ecshop.ecs_store_pat_shipping tps
        left join ecshop.ecs_shipping es ON tps.shipping_id = es.shipping_id
        left join ecshop.ecs_pat_shipping ps ON tps.pat_id = ps.pat_id
        where tps.party_id = '{$party_id}'
              and tps.store_type = 'taobao'
              and tps.type = 'fenxiao'
        order by tps.shipping_id,ps.pat_id
    ";
    //Qlog::log('distribution_pat_shipping_list='.$sql);
    $distribution_pat_shipping_list = $db->getAll($sql) ;

    return $distribution_pat_shipping_list;
 }
 /*
 * 得到组织地区默认仓库展示列表（所以不分地区） ljzhou 2013.04.23
 */
 function get_party_region_assign_facility_list ($party_id) {
    global $db ;
    $sql = "
        select
              rf.facility_id,rf.region_id,f.facility_name,r.region_name
              , rp.region_id parent_region_id, rpp.region_id grand_parent_region_id , r.region_name 
                , rp.region_name parent_region_name, rpp.region_name grand_parent_region_name , 
            if( rpp.region_id > 1 , rpp.region_id , IF(rpp.region_id =1 , rp.region_id , r.region_id )) province , 
            if( rpp.region_id > 1 , rp.region_id , IF(rpp.region_id = 1 , r.region_id , 0 )) city , 
            if( rpp.region_id > 1 , r.region_id , 0 ) district      
        from ecshop.ecs_party_region_assign_facility rf
        left join ecshop.ecs_region r ON rf.region_id = r.region_id
        left JOIN ecshop.ecs_region rp on rp.region_id = r.parent_id
        LEFT JOIN ecshop.ecs_region rpp on rpp.region_id = rp.parent_id
        left join romeo.facility f ON rf.facility_id = f.facility_id
        where rf.party_id = {$party_id}
        order by  province , city ,district
    ";
    $party_region_assign_facility_list = $db->getAll($sql) ;

    return $party_region_assign_facility_list;
 }
 
/*
 * 得到店铺地区默认仓库展示列表
 */
 function get_distributor_region_assign_facility_list ($distributor_id) {
    global $db ;
//  $sql = "
//      select
//            rf.facility_id,rf.region_id,f.facility_name,r.region_name
//      from ecshop.ecs_distributor_region_assign_facility rf
//      left join ecshop.ecs_region r ON rf.region_id = r.region_id
//      left join romeo.facility f ON rf.facility_id = f.facility_id
//      where rf.distributor_id = {$distributor_id}
//      order by rf.facility_id,rf.region_id
//  ";

    $sql = "
        select
              rf.facility_id,rf.region_id,f.facility_name,r.region_name
              , rp.region_id parent_region_id, rpp.region_id grand_parent_region_id , r.region_name 
                , rp.region_name parent_region_name, rpp.region_name grand_parent_region_name , 
            if( rpp.region_id > 1 , rpp.region_id , IF(rpp.region_id =1 , rp.region_id , r.region_id )) province , 
            if( rpp.region_id > 1 , rp.region_id , IF(rpp.region_id = 1 , r.region_id , 0 )) city , 
            if( rpp.region_id > 1 , r.region_id , 0 ) district 
        from ecshop.ecs_distributor_region_assign_facility rf
        left join ecshop.ecs_region r ON rf.region_id = r.region_id
        left JOIN ecshop.ecs_region rp on rp.region_id = r.parent_id
        LEFT JOIN ecshop.ecs_region rpp on rpp.region_id = rp.parent_id
        left join romeo.facility f ON rf.facility_id = f.facility_id
        where rf.distributor_id = {$distributor_id}
        order by  province , city ,district
    ";
    

    $distributor_region_assign_facility_list = $db->getAll($sql) ;

    return $distributor_region_assign_facility_list;
 }

 /*
 * 获取映射后的仓库列表 ljzhou 2013.04.23
 */
 function get_real_facility_list ($facility_list) {
    $facility_real_list = array();
    foreach ( $facility_list as $facility ) {
        $facility_id = facility_convert ( $facility ['facility_id'] );
        if (!in_array($facility_id,$facility_real_list)) {
            $facility_real_list [] = $facility_id;
        }
    }

    return $facility_real_list;
 }


 /*
 * 获取店铺默认仓库和快递 ljzhou 2013.04.23
 */
 function get_shop_default_facility_shipping ($party_id) {
    global $db ;
    $sql = "select f.facility_name,s.shipping_name from ecshop.taobao_shop_conf t
    left join romeo.facility f ON t.facility_id = f.facility_id
    left join ecshop.ecs_shipping s ON t.shipping_id = s.shipping_id
    where t.party_id = {$party_id} limit 1";
    $facility_shipping = $db->getRow($sql) ;

    return $facility_shipping;
 }

 /*
 * 得到不能发违禁品的快递列表 ljzhou 2013.05.21
 * @param
 * @return 数组
 */

 function get_contraband_shipping () {
    // 违禁品不能发的快递：顺丰空运，顺丰空运-淘宝cod
    $contraband_shipping = array('44','121');

    return $contraband_shipping;
 }

/*
 * 根据订单号，检查订单是否含违禁品 ljzhou 2013.05.21
 * @param  unknown_type $order_id
 * @return true：有    false:没有
 */

 function check_order_contraband ($order_id) {
    global $db ;
    $sql = "select goods_id from ecshop.ecs_order_goods where order_id = {$order_id} group by goods_id";
    $goods_id_list = $db->getCol($sql) ;
    if(!check_goods_contraband($goods_id_list)) {
        return false;
    }

    return true;
 }


/*
 * 根据goods_id列表，检查其中是否含违禁品 ljzhou 2013.05.21
 * @param unknown_type $order_id
 */

 function check_goods_contraband ($goods_id_list) {
    global $db ;
    $sql = "select 1 from ecshop.ecs_goods where goods_id ".db_create_in($goods_id_list)." and is_contraband = 1 limit 1";
    $contraband = $db->getOne($sql) ;
    if(empty($contraband)) {
        return false;
    }

    return true;
 }


/*
 * 修改订单快递（参考订单详情页）  ljzhou 2013.05.21
 * @param $order_id,$to_shipping_id,$note
 */

 function change_order_shipping ($order_id,$to_shipping_id,$note = '') { // 修改违禁品快递，似乎已经废弃
    global $shopapi_client,$db;
    $message = array();
    if(!$order_id || !$to_shipping_id) {
        $message['message'] .= "order_id 或  to_shipping_id 不能为空！";
        return $message;
    }

    $order = $shopapi_client->getOrderById($order_id);
    if(!$order) {
         $message['message'] .= "系统里找不到该订单！order_id:".$order_id;
         return $message;
    }

    // 快递未更改
    if ($order->shippingId == $to_shipping_id) {
        $message['message'] .= "快递未更改，订单号:".$order->orderSn;
        return $message;
    }

    $shippings = getShippingTypes();
    $order->actionNote = '';

    require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');

    // 修改配送方式要检查是否是合并发货的
    if (($to_shipping_id && $order->shippingId!=$to_shipping_id) ) {
        // 判断是否是合并发货
        $is_merge_shipment=false;
        $handle=soap_get_client('ShipmentService');
        $response=$handle->getShipmentByPrimaryOrderId(array('primaryOrderId'=>$order_id));
        if(is_object($response->return)){
            $shipment=$response->return;
            if($shipment->status=='SHIPMENT_CANCELLED'){
                $is_merge_shipment=true;
            }
            else{
                $response2=$handle->getOrderShipmentByShipmentId(array('shipmentId'=>$shipment->shipmentId));
                if(is_array($response2->return->OrderShipment)){
                    $is_merge_shipment=true;
                }
            }
        }
        if($is_merge_shipment){
            $message['message'] .= "合并发货的订单不能修改配送方式 订单号:".$order->orderSn;
             return $message;
        }
    }

    // 修改配送方式
    if ($order->shippingId != $to_shipping_id && $to_shipping_id) {
        $origin_shippingname = $shippings[$order->shippingId]['shipping_name'];
        $new_shippingname = $shippings[$to_shipping_id]['shipping_name'];

        $order->shippingId = $to_shipping_id;
        $order->shippingName = $new_shippingname;

        $order->actionNote .= $note." 修改配送方式 从 $origin_shippingname 修改为  $new_shippingname  ";
    }

    $order->actionUser = $_SESSION['admin_name'];;
    // 修改处理时间
    $order->handleTime = time();
    $shipping = $shippings[$order->shippingId];

    $update_order = $shopapi_client->updateOrder($order);
    if($update_order != 1) {
         $message['message'] .= "更新订单快递失败！订单号:".$order->orderSn;
    }

    //  修改Shipment的配送方式
    if ($to_shipping_id) {
        $carrierId=$shippings[$to_shipping_id]['default_carrier_id'];

        // 更改承运商 killed by Sinri 20160105
        // $sql="update ecs_carrier_bill set carrier_id='{$carrierId}' where bill_id='".$order->billId."'";
        // $db->query($sql);
        try {
            $handle=soap_get_client('ShipmentService');
            $handle->updateShipmentByPrimaryOrderId(array(
                'primaryOrderId'=>$order_id,
                'shipmentTypeId'=>$to_shipping_id,
                'carrierId'=>$carrierId,
                'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
            ));
        }
        catch (Exception $e) {
             $message['message'] .= "修改Shipment的配送方式失败:order_sn:".$order->orderSn." ".$e;
        }
    }

    //update_order_mixed_status_note($order_id, 'worker', $order->actionNote);

    if( $message['message'] == '') {
        $message['message'] .= "修改快递成功!";
    }
    return $message;

 }

  function test_my(){
    $handle=soap_get_client('ShipmentService');
    if(empty($handle)){
        print_r("empty handle");
    }else{
        $response=$handle->getShipmentByPrimaryOrderId(array('primaryOrderId'=>'2768261'));
        pp($response);
    }
  }
 /*
 * 顺丰陆运与顺丰空运价格一致的地区,是顺丰陆运中打折部分 ljzhou 2013.05.23
 * @param  $order
 * @return
 */

 function check_is_discount_shunfeng ($order) {
    //上海仓：上海，江苏省，浙江省，安徽省，山东省，江西省，福建省，河南省，湖北省（除恩施外）
    //东莞仓：江西省，湖南省，广西省，福建省，江苏省徐州市，海南省（除海口市、三亚市、三沙市外），湖北省（除武汉市外），广东省
    //19568549：10,11,12,13,14,15,16,17,18(218)
    //19568548：15,19,21,14,113,22(268,269,4701),18(206),20
    $shanghai_region = array('10','11','12','13','14','15','16','17');
    $dongguang_region = array('15','19','21','14','20');

    $facility_id = facility_convert($order['addr']['facility_id']);
    // 仓库转化后，上海仓可以总结为 电商服务上海仓，怀轩上海仓
    $shanghai_facility = array('19568549','12768420');
    // 仓库转化后，东莞仓可以总结为 电商服务东莞仓
    $dongguang_facility = array('19568548');

    $province = $order['addr']['province'];
    $city = $order['addr']['city'];

    if(in_array($facility_id,$shanghai_facility)) {
        if(in_array($province,$shanghai_region) || ($province=='18' and $city !='218')) {
            return true;
        }
    } else if(in_array($facility_id,$dongguang_facility)) {
        if(in_array($province,$dongguang_region) || ($province=='18' and $city !='206')  ||  $city =='113'  || ($province=='22' and $city !='268' and $city !='269' and $city !='4701')) {
            return true;
        }
    }

    return false;
 }


/*
 * 根据生产日期，保质期（goods_id）得到到期时间 ljzhou 2013.05.27
 * @param  $order
 * @return
 */

 function get_end_validity ($start_validity,$goods_id) {
    global $db;
    $end_validity = $start_validity;

    $sql = "select goods_warranty from ecshop.ecs_goods where goods_id = {$goods_id} limit 1";
    $month = $db->getOne($sql);
    if(!empty($month)) {
       $start_validity = strtotime($start_validity);
       $end_validity = date('Y-m-d',$start_validity + $month*30*24*3600);
    }

    return $end_validity;
 }
 
 /*
 * 根据到期日期，保质期（goods_id）得到生产日期时间 
 * @param  $end_validity,$goods_id
 * @return
 */

 function get_start_validity ($end_validity,$goods_id) {
    global $db;
    $start_validity = $end_validity;

    $sql = "select goods_warranty from ecshop.ecs_goods where goods_id = {$goods_id} limit 1";
    $month = $db->getOne($sql);
    if(!empty($month)) {
       $end_validity = strtotime($end_validity);
       $start_validity = date('Y-m-d',$end_validity - $month*30*24*3600);
    }

    return $start_validity;
 }

 /*
 * 得到大订单中大商品的界线数值 ljzhou 2013.06.06
 * @param
 * @return
 */

 function get_big_goods_number() {
    $big_goods_number = 20;
    return $big_goods_number;
 }

 /*
 * 判断订单是否包含大商品,发货单级别 的，算该商品的总和
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function check_has_big_goods($goods_id,$style_id,$order_id_array) {
    global $db;
    $big_goods_number = get_big_goods_number();
    $sql = "
        SELECT
            ifnull(sum(og.goods_number),0) as goods_number_total
        FROM
            ecshop.ecs_order_goods AS og
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND
            og.order_id ". db_create_in($order_id_array);
    $goods_number_total = $db->getOne($sql);
    if($goods_number_total > $big_goods_number) {
        return true;
    }
    return false;
 }

 /*
 * 根据仓库，串号等信息获取新库存总数  ljzhou 2014.1.6
 * @param  $facility,$cond
 * @return
 */

 function get_item_stock_quantity($facility_id,$cond) {
    global $db,$ecs;
    $sql = "
        SELECT 
            sum(ii.quantity_on_hand_total)
        FROM
            romeo.product_mapping pm 
            LEFT JOIN romeo.inventory_item ii ON pm.product_id = ii.product_id and ii.status_id = 'INV_STTS_AVAILABLE'
        WHERE
            ii.facility_id = '{$facility_id}' AND ii.quantity_on_hand_total > 0
            %s
    ";

    $item_stock_quantity = $db->getOne(sprintf($sql, $cond));
    if(empty($item_stock_quantity)) {
        Qlog::log('function get_erp_stock_quantity erp_stock_quantity:0');
        return 0;
    }

    return $item_stock_quantity;
 }

/*
 * 得到订单中某大商品未出库总数,新库存  ljzhou 2014.1.6
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function get_big_goods_new_no_out_total($goods_id,$style_id,$order_id_array) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.rec_id,(og.goods_number + ifnull(sum(if(iid.quantity_on_hand_diff < 0,iid.quantity_on_hand_diff,0)),0) ) as no_out_number
        FROM
            {$ecs->table('order_goods')} AS og
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id AND ii.status_id = 'INV_STTS_AVAILABLE'
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'  AND
            og.order_id ". db_create_in($order_id_array) ." group by og.rec_id ";
    $rec_numbers = $db->getAll($sql);
    if(empty($rec_numbers)) {
        return 0;
    }
    $total_number = 0;
    foreach($rec_numbers as $rec_number) {
        $total_number += $rec_number['no_out_number'];
    }
    

    return $total_number;
 }
 
 /*
 * 根据order_id得到每个rec_id对应已出库的数量 ljzhou 2014.1.6
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function get_rec_out_numbers($order_id) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.rec_id, ifnull(-sum(if(iid.quantity_on_hand_diff < 0,iid.quantity_on_hand_diff,0)),0) as has_out_number
        FROM
            {$ecs->table('order_goods')} AS og
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id and ii.status_id = 'INV_STTS_AVAILABLE' 
        WHERE
            og.order_id ". db_create_in($order_id) ." group by og.rec_id ";
    //Qlog::log('get_big_goods_new_no_out_total:'.$sql);
    $rec_numbers = $db->getAll($sql);
    if(empty($rec_numbers)) {
        return null;
    }
    $rec_id_numbers = array();
    foreach($rec_numbers as $rec_number) {
        $rec_id_numbers[$rec_number['rec_id']] = $rec_number['has_out_number'];
    }

    return $rec_id_numbers;
 }
 
 /*
 * 得到某商品名  ljzhou 2013.06.06
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function get_big_goods_name($goods_id,$style_id,$order_id_array) {
    global $db,$ecs;
    $sql = "
        SELECT
            og.goods_name
        FROM
            {$ecs->table('order_goods')} AS og
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND
            og.order_id ". db_create_in($order_id_array) ." limit 1";
    $goods_name = $db->getOne($sql);
    if(empty($goods_name)) {
        return '';
    }
    return $goods_name;
 }


/*
 * 根据新库存得到大订单大商品未出库的rec_id,goods_number数组  ljzhou 2013.06.06
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function get_big_goods_rec_number_new($goods_id,$style_id,$order_id_array) {
    global $db,$ecs;
    $big_goods_number = get_big_goods_number();
    $sql = "
        SELECT 
            og.rec_id,(og.goods_number + ifnull(sum(if(iid.quantity_on_hand_diff < 0,iid.quantity_on_hand_diff,0)),0) ) as goods_number
        FROM
            {$ecs->table('order_goods')} AS og
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id AND ii.status_id = 'INV_STTS_AVAILABLE' 
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND og.goods_number >= {$big_goods_number}
            AND og.order_id ". db_create_in($order_id_array). " group by og.rec_id";

    $big_goods_rec_number = $db->getAll($sql);

    if(empty($big_goods_rec_number)) {
        return null;
    }
    return $big_goods_rec_number;
 }
 

/*
 * 根据新库存得到大订单大商品未出库的rec_id,goods_number数组  ljzhou 2013.06.06
 * @param  $goods_id,$style_id,$order_id_array
 * @return
 */

 function get_small_goods_quantity_new($goods_id,$style_id,$order_id_array) {
    global $db,$ecs;
    $big_goods_number = get_big_goods_number();
    $sql = "
        SELECT 
            og.rec_id,og.goods_number
        FROM
            {$ecs->table('order_goods')} AS og
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND og.goods_number < {$big_goods_number}
            AND og.order_id ". db_create_in($order_id_array)." group by og.rec_id";
            
    //Qlog::log('get_small_goods_quantity_new:'.$sql);
    $rec_numbers = $db->getAll($sql);
    if(empty($rec_numbers)) {
        return 0;
    }
    $small_goods_quantity = 0;
    foreach($rec_numbers as $rec_number) {
        $small_goods_quantity += $rec_number['goods_number'];
    }
    return $small_goods_quantity;
 }
 
 /**
 * 得到大订单小商品的唯一标识id数组，规则为order_goods_id 加上1,2,3...类似于之前的erp_id，唯一标识一个商品库存
 * ljzhou 2014-01-02
 */
 function get_small_goods_unique_item_ids($goods_id,$style_id,$order_id_array) {
    global $db,$ecs;
    $big_goods_number = get_big_goods_number();
    
    $sql = "
        SELECT 
            og.rec_id,og.goods_number
        FROM
            {$ecs->table('order_goods')} AS og
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND og.goods_number < {$big_goods_number}
            AND og.order_id ". db_create_in($order_id_array)." group by og.rec_id";
    //Qlog::log('get_small_goods_unique_item_ids  sql:'.$sql);
    $rec_nums = $db->getAll($sql);
    $item_unique_ids = format_item_unique_ids($rec_nums);
    return $item_unique_ids;
 }
 
/*
 * 检查订单是否在当前组织内  ljzhou 2013.06.14
 * @param  $order_id
 * @return $order_sn
 */

 function check_order_party($order_id) {
    global $db,$ecs;
    $sql = " SELECT order_sn".
           " FROM {$ecs->table('order_info')} ".
           " WHERE order_id = '{$order_id}' AND ". party_sql('party_id') ;
    $order_sn = $db->getOne($sql);

    if(empty($order_sn)) {
        return false;
    }
    return true;
 }

/*
 * 组装erp_id,erp_goods_sn,使得每个erp_id分配到一个erp_goods_sn  ljzhou 2013.06.14
 * @param  $order_rec_erp_sns
 * @return
 */

 function organize_erp_sn($order_rec_erp_sns) {
    global $db,$ecs;
    $erp_sns = array();$erp_ids = array();$erp_goods_sns = array();
    $j = 0;
    foreach($order_rec_erp_sns as $order_rec_erp_sn) {
        $erp_ids_str = $order_rec_erp_sn['erp_ids'];
        $erp_ids = explode(',',$erp_ids_str);
        $erp_goods_sns_str = $order_rec_erp_sn['erp_goods_sns'];
        $erp_goods_sns = explode(',',$erp_goods_sns_str);
        $len = count($erp_ids) > count($erp_goods_sns) ? count($erp_goods_sns) : count($erp_ids);

        for($i=0;$i< $len;$i++) {
            $erp_sns[$j]['erp_id'] = $erp_ids[$i];
            $erp_sns[$j]['erp_goods_sn'] = $erp_goods_sns[$i];
            $j++;
        }
    }

    if(empty($erp_sns)) {
        return null;
    }
    return $erp_sns;
 }

/*
 * 判断是否为通用商品组织  ljzhou 2013.07.03
 * @param
 * @return
 */

 function check_goods_common_party() {
    $common_party = get_tysp_party_id();
    if($_SESSION['party_id'] != $common_party) {
       return false;
    }
    return true;
 }

 /*
 * 通用商品组织ID  ljzhou 2013.07.03
 * @param
 * @return
 */

 function get_tysp_party_id() {
    return PARTY_TYSP;
 }

/*
 * 判断耗材商品在通用商品组织和具体组织中是否都存在 ljzhou 2013.07.03
 * @param
 * @return
 */

 function get_common_goods_partys($barcode) {
    global $db;
    $result = array();

    $sql = "
        SELECT
            g.goods_party_id
        FROM
            ecshop.ecs_goods g
            left join romeo.party p ON convert(g.goods_party_id using utf8) = p.party_id
        WHERE
            g.is_delete = '0' and g.barcode = '{$barcode}'
        ";

    $party_ids = $db->getCol($sql);
    $result['party_id'] = $party_ids;
    if(in_array(get_tysp_party_id(),$party_ids) && count($party_ids) > 1) {
        $result['error'] = true;
        $result['party_name'] = get_name_by_party_ids($party_ids);
    }else if( count($party_ids) == 1 ){ //判断耗材商品是否为安怡组或者安满组
        $result['partyId'] = $party_ids[0];
    }

    return $result;
 }

 /*
 * 根据组织ID得到组织名 ljzhou 2013.07.03
 * @param  $party_ids
 * @return $party_names
 */

 function get_name_by_party_ids($party_ids) {
    global $db;
    $sql = "select name from romeo.party where party_id ".db_create_in($party_ids);
    $party_names = $db->getCol($sql);

    return $party_names;
 }

/*
 * 判断条码是否为通用商品组织 ljzhou 2013.07.11
 * @param  $party_ids
 * @return $party_names
 */

 function check_tysp_barcode($barcode) {
    $party_id = get_tysp_party_id();
    global $db;
    $sql = "select 1 from ecshop.ecs_goods g left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id
            where g.goods_party_id = $party_id and g.is_delete = '0' and (g.barcode = '{$barcode}' or gs.barcode = '{$barcode}') limit 1";
    $check_tysp = $db->getOne($sql);
    if(empty($check_tysp)) {
        return false;
    }

    return true;
 }

 /*
 * 仓库按照区域转仓  ljzhou 2013.07.11
 * @param  $facilityId
 * @return $facilityId
 */
 function convert_facility_by_region($facilityId) {
     $facility_mapping = array (
                 '12768420' =>  FACILITY_TYSP_SH,    //  怀轩上海仓
                 '19568549' =>  FACILITY_TYSP_SH,    //  电商服务上海仓
                 '22143846' =>  FACILITY_TYSP_SH,    //  乐其上海仓_2（原乐其杭州仓）
                 '22143847' =>  FACILITY_TYSP_SH,    //  电商服务上海仓_2（原电商服务杭州仓）
                 '24196974' =>  FACILITY_TYSP_SH,    //  贝亲青浦仓
                 '3633071'  =>  FACILITY_TYSP_SH,    //  乐其上海仓
                 '69897656' =>  FACILITY_TYSP_SH,    //  天猫超市（上海）
                 '76161272' =>  FACILITY_TYSP_SH,    //  上海伊藤忠
                 '81569822' =>  FACILITY_TYSP_SH,    //  康贝分销上海仓
                 '81569823' =>  FACILITY_TYSP_SH,    //  一号店

                 '19568548' =>  FACILITY_TYSP_DG,    //  电商服务东莞仓
                 '3580047'  =>  FACILITY_TYSP_DG,    //  乐其东莞仓
                 '49858448' =>  FACILITY_TYSP_DG,    //  香港平世仓
                 '49858449' =>  FACILITY_TYSP_DG,    //  东莞乐贝仓
                 '76065524' =>  FACILITY_TYSP_DG,    //  电商服务东莞仓2
                 '77451244' =>  FACILITY_TYSP_DG,    //  天猫超市（广州）

                 '42741887' =>  FACILITY_TYSP_BJ,    //  乐其北京仓
                 '79256821' =>  FACILITY_TYSP_BJ,    //  电商服务北京仓
                 '137059428'=>  FACILITY_TYSP_CD,    //  电商服务成都仓
                 
                 '185963131' => FACILITY_TYSP_JXSG,  // 双11嘉兴水果仓-外包
                 '185963132' => FACILITY_TYSP_JXSG,  // 双11嘉兴水果仓-正常
                 '185963133' => FACILITY_TYSP_SHSG, // 双11上海水果仓-外包
                 '185963134' => FACILITY_TYSP_SHSG, // 双11上海水果仓-正常
                 '185963137' => FACILITY_TYSP_SZSG, // 双11苏州水果仓-外包
                 '185963138' => FACILITY_TYSP_SZSG, // 双11苏州水果仓-正常
                 '185963139' => FACILITY_TYSP_CDSG, // 双11成都水果仓-外包
                 '185963140' => FACILITY_TYSP_CDSG, // 双11成都水果仓-正常
                 '185963141' => FACILITY_TYSP_WHSG, // 双11武汉水果仓-外包
                 '185963142' => FACILITY_TYSP_WHSG, // 双11武汉水果仓-正常
                 '185963127' => FACILITY_TYSP_BJSG, // 双11北京水果仓-外包
                 '185963128' => FACILITY_TYSP_BJSG, // 双11北京水果仓-正常
                 '185963146' => FACILITY_TYSP_SHZSG, // 双11深圳水果仓-外包
                 '185963147' => FACILITY_TYSP_SHZSG, // 双11深圳水果仓-正常
             ) ;

     if (array_key_exists($facilityId, $facility_mapping)) {
         return $facility_mapping[$facilityId] ;
     } else {
         return $facilityId ;
     }

 }
 
  /*
 * 得到错误(已经被使用过)的商品商品串号 
 * 曾经入库过，但也出库了。允许再次入库（-c到错误仓库后-gt）
 * @param $erp_goods_sns
 * @return
 */
 function get_error_serial_numbers($serial_numbers) {
    global $db,$ecs;
    $in_serial_number = db_create_in($serial_numbers, 'serial_number');
    $sql = "SELECT serial_number,sum(QUANTITY_ON_HAND_TOTAL) as c
            FROM romeo.inventory_item 
            WHERE {$in_serial_number} 
            group by serial_number
			having c%2=1
            ";
    //Qlog::log('get_error_serial_numbers:'.$sql);
    $serial_numbers_error = $db->getCol($sql);
    if(empty($serial_numbers_error)){
        return null;
    }
    return $serial_numbers_error;
 }
 
/*
 * 得到出库时错误的的商品串号 
 * @param $serial_numbers，$order_id
 * @return
 */
 function get_pick_error_serial_numbers($serial_numbers,$order_id) {
    global $db,$ecs;
    if(empty($serial_numbers)) {
        return null;
    }
    $in_serial_number = db_create_in($serial_numbers, 'serial_number');
    $sql = "select ii.serial_number
            from ecshop.ecs_order_info oi
            inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            left join romeo.inventory_item ii ON pm.product_id = ii.product_id and oi.facility_id = ii.facility_id
            where ii.status_id='INV_STTS_AVAILABLE' and {$in_serial_number} 
            and oi.order_id = '{$order_id}'
            and ii.quantity_on_hand_total > 0
            group by ii.serial_number
            ";
    //Qlog::log('get_pick_error_serial_numbers:'.$sql);
    $serial_numbers_right = $db->getCol($sql);
    $serial_numbers_error = array();
    $serial_numbers_error = array_diff($serial_numbers,$serial_numbers_right);
//    Qlog::log('$serial_numbers:'.implode(',',$serial_numbers));
//    Qlog::log('$serial_numbers_right:'.implode(',',$serial_numbers_error));
//    Qlog::log('$serail_numbers_error:'.implode(',',$serial_numbers_error));
    
    if(empty($serial_numbers_error)){
        Qlog::log('$serail_numbers_error2:'.implode(',',$serial_numbers_error));
        return null;
    }
    Qlog::log('$serial_numbers_error3:'.implode(',',$serial_numbers_error));
    
    return $serial_numbers_error;
 }

 /*
 * 出库时组装商品串号 ljzhou 2014-2-19
 * 格式 product_id:serial_numbers
 * @param $erp_goods_sns
 * @return
 */
 function get_product_serial_numbers($serial_numbers,$order_id) {
    global $db,$ecs;
    if(empty($serial_numbers)) {
        return null;
    }
    $in_serial_number = db_create_in($serial_numbers, 'serial_number');
    $sql = "select pm.product_id,ii.serial_number
            from ecshop.ecs_order_info oi
            left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            left join romeo.inventory_item ii ON pm.product_id = ii.product_id and oi.facility_id = ii.facility_id
            where ii.status_id='INV_STTS_AVAILABLE' and {$in_serial_number} 
            and oi.order_id = '{$order_id}'
            and ii.quantity_on_hand_total > 0
            group by pm.product_id,ii.serial_number
            ";
    //Qlog::log('get_product_serial_numbers:'.$sql);
    $product_serial_numbers = $db->getAll($sql);

    $result = array();
    foreach($product_serial_numbers as $product_serial_number) {
        $result[$product_serial_number['product_id']]['product_id'] = $product_serial_number['product_id'];
        $result[$product_serial_number['product_id']]['serial_number'][] = $product_serial_number['serial_number'];
    }
    if(empty($result)){
        return null;
    }
    return $result;
 }
 
 /**
  * 得到订单的product_id对应的未出库的数量
  */
 function get_order_product_no_out_numbers($order_id) {
    global $db;
    // 查询每个订单的已入库数
    $sql = "
        SELECT
            pm.product_id,( og.goods_number + ifnull(sum(iid.quantity_on_hand_diff),0) ) AS not_out_number,og.goods_name
        FROM
        ecshop.ecs_order_goods og
        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id 
        WHERE
            og.order_id = '{$order_id}'
        GROUP BY pm.product_id
    ";
    //Qlog::log('get_order_product_no_out_numbers:'.$sql);
    $product_not_out_numbers = $db->getAll($sql);
    if(empty($product_not_out_numbers)) {
        return null;
    }
    $result = array();
    foreach($product_not_out_numbers as $product_not_out_number) {
        $result[$product_not_out_number['product_id']]['not_out_number'] = $product_not_out_number['not_out_number'];
        $result[$product_not_out_number['product_id']]['goods_name'] = $product_not_out_number['goods_name'];
    }

    return $result;
 }

 
 /**
  * 得到某订单未入库的数量
  */
 function get_order_not_in_number($order_id) {
    global $db;
    // 查询每个订单的已入库数
    $sql = "
        SELECT
            ( og.goods_number - ifnull(sum(iid.quantity_on_hand_diff),0) ) AS not_in_number
        FROM
            ecs_order_goods og
        LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id 
        WHERE
            og.order_id = '{$order_id}'
        GROUP BY og.order_id
    ";
    //Qlog::log('get_order_not_in_number:'.$sql);
    $not_in_number = $db->getOne($sql);
    if(empty($not_in_number)) {
        return 0;
    }
    return $not_in_number;
 }
 /*
 * 组装erp_id,erp_goods_sn,使得每个erp_id分配到一个erp_goods_sn zxcheng  2013.07.12
 * @param $order_erp_ids,$right_erp_goods_sns
 * @return
 */
 function organize_en_sn($erp_ids,$erp_goods_sns) {
    $erp_sns = array();
    $j = 0;
    $len = count($erp_ids) > count($erp_goods_sns) ? count($erp_goods_sns) : count($erp_ids);
    for($i=0;$i< $len;$i++) {
        $erp_sns[$j]['erp_id'] = $erp_ids[$i];
        $erp_sns[$j]['erp_goods_sn'] = $erp_goods_sns[$i];
        $j++;
    }
    if(empty($erp_sns)) {
        return null;
    }
    return $erp_sns;
 }


 /*
 * 检查条码是否需要维护有效期 ljzhou 2013.07.15
 * @param  $barcode
 * @return
 */

  function check_maintain_warranty($barcode,$party_id) {
    global $db;
    if(empty($party_id)) {
        $party_id = $_SESSION['party_id'];
    }

    $sql = "select
              g.is_maintain_warranty
            from ecshop.ecs_goods g
            left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id
            where
               g.goods_party_id = '{$party_id}' AND
               if(gs.barcode is null or gs.barcode = '',g.barcode,gs.barcode) = '{$barcode}'
            limit 1
         ";
    //Qlog::log('check_maintain_warranty sql:'.$sql);
    $is_maintain_warranty = $db->getOne($sql);
    if(empty($is_maintain_warranty) || $is_maintain_warranty == 0) {
        return false;
    }
    return true;
 }

  /*
 * 根据批次订单号获取组织id ljzhou 2013.07.15
 * @param  $barcode
 * @return
 */

 function get_party_by_batch_order_sn($batch_order_sn) {
    global $db;
    $result = array();

    $sql = "select party_id from ecshop.ecs_batch_order_info where batch_order_sn = '{$batch_order_sn}' limit 1 ";
    $party_id = $db->getOne($sql);
    Qlog::log('get_party_by_batch_order_sn:'.$party_id);
    if(!empty($party_id)) {
        $result['res'] = $party_id;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "没有找到组织id,批次订单号：".$batch_order_sn;
    }
    return $result;
 }

 // 得到码托的条码数组
 function get_tray_barcodes($number){
    global $db;
    $result = array();
    if($number <= 0){
        $result['error'] = "条码数必须大于0";
        return $result;
    }
    //获取当天已经打印的条码个数
    $num = get_today_tray_barcode_number();
    $num = $num + 1;
    $constant = date('Ymd',time());//'20130801' 获取当前日期
    $constant = substr($constant,2,strlen($constant));
    $groud_location_barcodes = array();
    // 设置条码规则
    for($i = 0;$i < $number;$i++) {
        if($num < 10){
            $groud_location_barcodes[$i] = 'MT'.$constant.'000'.$num;
        }else if($num >= 10 && $num < 100){
            $groud_location_barcodes[$i] = 'MT'.$constant.'00'.$num;
        }else if($num >= 100 && $num < 1000){
            $groud_location_barcodes[$i] = 'MT'.$constant.'0'.$num;
        }else{
            $groud_location_barcodes[$i] = 'MT'.$constant.$num;
        }
        $num++;
    }
    Qlog::log('条码数组='.implode(',',$groud_location_barcodes));
    //批量插入上架容器条码
    $res = tray_barcode_mapping($groud_location_barcodes);
    if($res['success']){
        $result['success'] = $res['success'];
        $result['res'] = $groud_location_barcodes;
    }else{
        $result['error'] = $res['error'];
        $result['success'] = false;
    }
    return $result;
 }
 // 码托条码，获取当天打印条数

 function get_today_tray_barcode_number(){
    global $db;
    $start_time = date("Y-m-d",time());
    $end_time = date("Y-m-d",strtotime('+1 day'));
    $sql = "select
               count(pallet_no)
            from romeo.pallet
            where created_time >= '{$start_time}' and created_time <= '{$end_time}'  limit 1";
    $length = $db->getOne($sql);
    return $length;
 }
 // 码托 批量插入条码
 function tray_barcode_mapping($barcodes){
    global $db;
    $party_id = $_SESSION['party_id'];
    //先查看grouding_location_barcode中有没有相同的记录，有记录在等待，，，提示稍后再插入。
    $sql_s = "select count(pallet_no) from `romeo`.`pallet` where ship_status = 'INIT' AND pallet_no".db_create_in($barcodes) ;
    $sql_i = "insert into `romeo`.`pallet`(`pallet_no`,`ship_status`,`created_user`,`created_time`) VALUES";
    $sql_v = "('%s', '%s', '%s', now())";
    $result = array();
    $location_barcode = $db->getOne($sql_s);
    if($location_barcode > 0){
       $result['error'] = "有用户在操作，请稍后重试！";
       $result['success'] = false;
       return $result;
    }
    $sql = $sql_i ;
    $index = 1 ;
    $list_barcodes = count($barcodes) ;
    $ship_status = 'INIT';
    foreach ($barcodes as $barcode) {
        if($index == $list_barcodes){
            $sql = $sql . sprintf($sql_v, $barcode, $ship_status, $_SESSION['admin_name']) . ';' ;
        }else{
            $sql = $sql . sprintf($sql_v, $barcode, $ship_status, $_SESSION['admin_name']) . ',' ;
        }
        $index++;
    }
    $db->query($sql);
    $result['success'] = true;
    return $result;
 }

 /*
 * 得到上架容器的条码数组 zxcheng 2013.08.02
 * @param  $number
 * @return
 */
 function get_grouding_location_barcodes($number){
    global $db;
    $result = array();
    if($number <= 0){
        $result['error'] = "条码数必须大于0";
        return $result;
    }
    //获取当天已经打印的条码个数
    $num = get_today_grouding_location_barcode_number();
    $num = $num + 1;
    $constant = date('Ymd',time());//'20130801'
    $groud_location_barcodes = array();
    // 设置条码规则
    for($i = 0;$i < $number;$i++) {
        if($num < 10){
            $groud_location_barcodes[$i] = $constant.'00'.$num;
        }else if($num >= 10 && $num < 100){
            $groud_location_barcodes[$i] = $constant.'0'.$num;
        }else{
            $groud_location_barcodes[$i] = $constant.$num;
        }
        $num++;
    }
    Qlog::log('条码数组='.implode(',',$groud_location_barcodes));
    //批量插入上架容器条码
    $res = grouding_location_barcode_mapping($groud_location_barcodes);
    if($res['success']){
        $result['success'] = $res['success'];
        $result['res'] = $groud_location_barcodes;
    }else{
        $result['error'] = $res['error'];
        $result['success'] = false;
    }
    return $result;
 }
 /*
 * 获取当天打印的条码数 zxcheng 2013.08.02
 * @return
 */
 function get_today_grouding_location_barcode_number(){
    global $db;
    $start_time = date("Y-m-d",time());
    $end_time = date("Y-m-d",strtotime('+1 day'));
    $sql = "select
               count(location_barcode)
            from romeo.location
            where created_stamp >= '{$start_time}' and created_stamp <= '{$end_time}' AND
                   location_type = 'IL_GROUDING' limit 1";
    //Qlog::log("get_today_grouding_location_barcode_number=".$sql);
    $length = $db->getOne($sql);
    return $length;
 }
 /*
 * 批量插入容器条码zxcheng 2013.08.02
 * @param  $barcodes
 * @return
 */
 function grouding_location_barcode_mapping($barcodes){
    global $db;
    $party_id = $_SESSION['party_id'];
    //先查看grouding_location_barcode中有没有相同的记录，有记录在等待，，，提示稍后再插入。
    $sql_s = "select count(location_barcode) from `romeo`.`location` where location_type = 'IL_GROUDING' AND location_barcode".db_create_in($barcodes) ;
    $sql_i = "insert into `romeo`.`location`(`location_barcode`,`location_type`,`action_user`,`created_stamp`,`party_id`) VALUES";
    $sql_v = "('%s', '%s', '%s', now(),$party_id)";
    $result = array();
    $location_barcode = $db->getOne($sql_s);
    if($location_barcode > 0){
       $result['error'] = "有用户在操作，请稍后重试！";
       $result['success'] = false;
       return $result;
    }
    $sql = $sql_i ;
    $index = 1 ;
    $list_barcodes = count($barcodes) ;
    $location_type = 'IL_GROUDING';
    foreach ($barcodes as $barcode) {
        if($index == $list_barcodes){
            $sql = $sql . sprintf($sql_v, $barcode, $location_type, $_SESSION['admin_name']) . ';' ;
        }else{
            $sql = $sql . sprintf($sql_v, $barcode, $location_type, $_SESSION['admin_name']) . ',' ;
        }
        $index++;
    }
    $db->query($sql);
    $result['success'] = true;
    return $result;
 }
 /*
 * 检测采购批次号的合法性 
 */
 function check_batch_order_sn($batch_order_sn,$out_ship=0){
    global $db;
    
    $outFacility = $db->getCol("SELECT facility_id FROM romeo.facility WHERE IS_OUT_SHIP = 'Y' ");
    $out_facility_list = implode("','",$outFacility);
    if($out_ship == 1){
        $cont = " and facility_id in ('{$out_facility_list}') ";
    }else{
        $cont = " and facility_id not in ('{$out_facility_list}') ";
    }
   $party_id = $_SESSION['party_id'];
   $sql = "select
               is_cancelled,is_over_c,is_in_storage
            from ecshop.ecs_batch_order_info
            where batch_order_sn = '{$batch_order_sn}' and party_id = '{$party_id}' {$cont} limit 1";
   $order_info = $db->getRow($sql);
   if(empty($order_info)){
      $result['error'] = "采购批次不存在,请选择正确的组织与仓库！";
      $result['success'] = false;
   }
   
   if($order_info['is_cancelled'] == 'Y'){
        $result['error'] = "已经废除！";
        $result['success'] = false;
   }else if($order_info['is_over_c'] == 'Y'){
        $result['error'] = "已经完结！";
        $result['success'] = false;
   }else if($order_info['is_in_storage'] == 'Y'){
        $result['error'] = "已经入库！";
        $result['success'] = false;
   }
   if(empty($result)){
         $result['success'] = true;
   }
   return $result;
 }


 /*
 * 检测工牌号的合法性 jwli 2016.02.24
 */
 function check_batch_employee_sn($batch_employee_sn){
    global $db;
   $sql = "select employee_name,employee_no from romeo.batch_pick_employee where employee_no = '{$batch_employee_sn}' limit 1";
   $employee_no = $db->getRow($sql);
   if(empty($employee_no)){
      $result['error'] = "工牌号不存在！";
      $result['success'] = false; 
      $result['error_id'] = 1; 
   }
   if(empty($result)){
         $result['success'] = true;
         $result['employee_name'] = $employee_no['employee_name'];
         $result['employee_no'] = $employee_no['employee_no'];
   }
   return $result;
 }
  /*
 * 检测批拣单号的合法性 jwli 2016.02.24
 */
 function check_batch_pick_sn($batch_pick_sn,$employee_no){
    global $db;
	$sql = "select employee_id,employee_no from romeo.batch_pick_employee  where employee_no = '{$employee_no}' limit 1"; 
	$employee_info = $db->getRow($sql);
    if(empty($employee_info)){//工牌号开始存在，在绑定过程中，被删除
      $result['error'] = "工牌号".$employee_no."不存在！";
      $result['error_id'] = 1;
      $result['success'] = false;
	} else {
		//查看批拣号对应选项是否有值
		$sql = "select batch_pick_sn,batch_pick_employee_id,employee_no,employee_name from romeo.batch_pick p 
			left join romeo.batch_pick_employee e  on p.batch_pick_employee_id = e.employee_id 
			where batch_pick_sn='{$batch_pick_sn}' limit 1";
        $batch_flag = $db->getRow($sql);
		if(empty($batch_flag)){
			$result['error'] = "批拣单号不存在！";
			$result['error_id'] = 2;
			$result['success'] = false;
		}elseif(!empty($batch_flag['employee_no'])){//存在且已绑定
            $result['success'] = false;
            $result['error_id'] = 3;
			$result['error'] = $batch_flag["employee_name"]."(".$batch_flag["employee_no"].")已经与批拣单号(".$batch_pick_sn.")绑定！";
		}elseif(empty($batch_flag['employee_no']) || empty($batch_flag['batch_pick_employee_id'])){//批拣号对应选项有值,但是工牌号已经被删除 || 并未绑定
            $sql = "update  `romeo`.`batch_pick` set batch_pick_employee_id = {$employee_info['employee_id']} ,employee_bind_stamp = now() " .
            		" where batch_pick_sn = '{$batch_pick_sn}' ";
            $db->query($sql);
            $result['success'] = true;
		}
   }
   return $result;
 }


 /*
 * 检测上架时订单号的合法性（-t,-h,-gh） ljzhou 2013.10.10
 * @param  $order_sn
 * @return
 */
 function check_common_grouding_order_sn($order_sn){
    global $db;
    $sql = "select
               1
            from ecshop.ecs_order_info
            where order_sn = '{$order_sn}' and order_type_id in('BORROW','RMA_RETURN','RMA_EXCHANGE','SUPPLIER_EXCHANGE') limit 1";
   //Qlog::log('check_common_grouding_order_sn='.$sql);

   $order_info = $db->getOne($sql);
   $result = array();
   if(empty($order_info)){
      $result['error'] = "订单不存在或不是(-t,-h,-gh)类型的！";
      $result['success'] = false;
   } else {
      $result['success'] = true;
   }

   return $result;
 }

 /*
 * 检测通用下架时订单号的合法性（-gt,-gh） ljzhou 2013.10.10
 * @param  $order_sn
 * @return
 */
 function check_common_undercarriage_order_sn($order_sn){
    global $db;
    $sql = "select
               1
            from ecshop.ecs_order_info
            where order_sn = '{$order_sn}' and order_type_id in('BORROW','SUPPLIER_RETURN','SUPPLIER_SALE','SUPPLIER_EXCHANGE') limit 1";
   //Qlog::log('check_common_grouding_order_sn='.$sql);

   $order_info = $db->getOne($sql);
   $result = array();
   if(empty($order_info)){
      $result['error'] = "订单不存在或不是(-gt,-gh)类型的！";
      $result['success'] = false;
   } else {
      $result['success'] = true;
   }

   return $result;
 }


 /*
 * 得到批次订单的基本信息  ljzhou 2013-09-06
 * @param  $batch_order_sn
 * @return
 */
 function get_batch_order_sn_info($batch_order_sn){
    global $db;
    $sql = "select
                f.facility_name
            from ecshop.ecs_batch_order_info boi
            left join romeo.facility f ON boi.facility_id = f.facility_id
            where boi.batch_order_sn = '{$batch_order_sn}' limit 1";
   $facility_name = $db->getOne($sql);
   //Qlog::log('check_batch_order_sn='.$sql);
   $result = array();
   if(empty($facility_name)){
      $result['error'] = "得到批次订单的基本信息时仓库名称找不到！";
      $result['success'] = false;
   } else {
      $result['success'] = true;
      $result['facility_name'] = $facility_name;
   }

   return $result;
 }

 /*
 * 检测收货时商品数量的合法性 zxcheng 2013.08.06
 * @param  $batch_order_sn,$goods_barcode,$input_number
 * @return
 */

 function check_receive_goods_number($batch_order_sn,$goods_barcode,$input_number){
    $result = array();
    $res = get_goods_not_in_number_v2($batch_order_sn,$goods_barcode);
    if(!$res['success']) {
        $result['error'] = $res['error'];
        $result['success'] = false;
        return $result;
    }
    $not_in_number = $res['not_in_number'];
    Qlog::log('check_receive_goods_number $not_in_number:'.$not_in_number.' $input_number:'.$input_number);
    
    if($input_number <= 0){
        $result['error'] = "入库数量必须大于0，请重新输入！";
        $result['success'] = false;
    }else if($input_number > $not_in_number){
        $result['error'] = "输入数量".$input_number."大于未入库数量：{$not_in_number},请检查后重新输入！";
        $result['success'] = false;
    }else{
        $result['success'] = true;
    }
    return $result;
 }
 /*
 * 得到收货时商品的goods_id 
 * @param  $batch_order_id,$goods_barcode
 * @return
 */
 function get_receive_goods_info($batch_order_sn,$goods_barcode){
    global $db;
    $sql = "SELECT og.goods_id,og.goods_name,oi.order_sn,om.is_cancelled,om.is_over_c,om.is_in_storage from
              ecshop.ecs_batch_order_info boi
            LEFT JOIN ecshop.ecs_batch_order_mapping om ON boi.batch_order_id = om.batch_order_id
            LEFT JOIN ecshop.ecs_order_info oi ON om.order_id = oi.order_id
            LEFT JOIN ecshop.ecs_order_goods og ON og.order_id = om.order_id
            LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
            LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
        WHERE boi.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}'";
    //Qlog::log('get_receive_goods_info sql:'.$sql);

    $goods_infos = $db->getAll($sql);
    $result = array();
    if(!empty($goods_infos)){
        $goods_info = $goods_infos[0];
        // 采购订单全部不能入库的才提示
        $sql = "SELECT 1 from
              ecshop.ecs_batch_order_info boi
            LEFT JOIN ecshop.ecs_batch_order_mapping om ON boi.batch_order_id = om.batch_order_id
            LEFT JOIN ecshop.ecs_order_goods og ON og.order_id = om.order_id
            LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
            LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
            WHERE boi.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}'
            and om.is_cancelled ='N' and om.is_over_c ='N' and om.is_in_storage ='N' limit 1";
        //Qlog::log('get_receive_goods_info2 sql:'.$sql);
    
        $has_all_in = $db->getRow($sql);
        // 如果为空 说明全部不能入了
        if(empty($has_all_in)) {
           if($goods_info['is_cancelled'] == 'Y'){
                $result['error'] = "该商品对应的采购订单：".$goods_info['order_sn']." 已经废除！";
                $result['success'] = false;
           }else if($goods_info['is_over_c'] == 'Y'){
                $result['error'] = "该商品对应的采购订单：".$goods_info['order_sn']." 已经完结！";
                $result['success'] = false;
           }else if($goods_info['is_in_storage'] == 'Y'){
                $result['error'] = "该商品对应的采购订单：".$goods_info['order_sn']." 已经入库！";
                $result['success'] = false;
           }
           
           return $result;
        }

        $result['success'] = true;
        $result['goods_id'] = $goods_info['goods_id'];
        $result['goods_name'] = $goods_info['goods_name'];
    }else{
        $result['success'] = false;
        $result['error'] = "商品条码不合法！串号商品也应该先扫条码";
    }

    return $result;
 }
 function check_receive_goods_serial($batch_order_sn,$goods_barcode){
    global $db;
    $sql = "SELECT  1  from
                romeo.inventory_item ii
             LEFT JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
             LEFT JOIN ecshop.ecs_order_goods og ON pm.ecs_goods_id = og.goods_id AND og.style_id = pm.ecs_style_id
             LEFT JOIN ecshop.ecs_batch_order_mapping om ON og.order_id = om.order_id
             LEFT JOIN ecshop.ecs_batch_order_info oi ON oi.batch_order_id = om.batch_order_id
        WHERE oi.batch_order_sn = '{$batch_order_sn}' AND
            ii.STATUS_ID = 'INV_STTS_AVAILABLE' AND
            ii.serial_number = '{$goods_barcode}' limit 1";
//  Qlog::log('check_receive_goods_serial sql:'.$sql);
    $res = $db->getOne($sql);
    return $res;
 }
 /*
 * 检测批次订单是否全部入库完成 
 * @param  $batch_order_sn
 * @return
 */
 function check_receive_all_in($batch_order_sn){
    global $db;
    $sql = "SELECT is_in_storage from ecshop.ecs_batch_order_info WHERE batch_order_sn = '{$batch_order_sn}' limit 1";
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        if($res['is_in_storage'] == 'Y'){
            $result['success'] = true;
        }else{
            $result['success'] = false;
            $result['res'] = "批次订单没有全部完成入库";
        }

    }else{
        $result['success'] = false;
        $result['error'] = "批次订单不存在！";
    }
    return $result;
 }
 /*
 * 检测上架容器的任务 zxcheng 2013.08.07
 * @param  $grouding_location_barcode
 * @return
 */
 function check_grouding_location_task($grouding_location_barcode){
    global $db;
    $result = array();

    // 先检测存在性
    $res = check_to_location_barcode($grouding_location_barcode,'IL_GROUDING');

    if(!$res['success']) {
        $result['success'] = false;
        $result['error'] = $res['error'];
        return $result;
    }

    $sql = "SELECT 1 from romeo.inventory_location  WHERE  location_barcode='{$grouding_location_barcode}' AND goods_number > 0 limit 1";
    //Qlog::log('check_grouding_location_task='.$sql);
    $res = $db->getOne($sql);
    if(!empty($res)){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['all_grouding'] = true;
        $result['error'] = '该上架容器没有商品上架：'.$grouding_location_barcode;
    }
    return $result;
 }
 /*
 * 检测条码是否在容器内 zxcheng 2013.08.07
 * @param  $location_barcode,$goods_barcode
 * @return
 */
 function check_location_goods_barcode($location_barcode,$barcode){
    global $db;
    $sql = "SELECT il.goods_number from romeo.inventory_location il
           LEFT JOIN romeo.location_barcode_serial_mapping sm ON il.location_barcode = sm.location_barcode
           WHERE il.location_barcode = '{$location_barcode}'
           AND (il.goods_barcode = '{$barcode}' OR sm.serial_number = '{$barcode}') limit 1";
    //Qlog::log('check_location_goods_barcode sql:'.$sql);
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        $result['success'] = true;
        $result['goods_number'] = $res;
    }else{
        $result['success'] = false;
        $result['error'] = "商品条码不在容器内！";
    }
    return $result;
 }

 /*
 * 检测通用上架时候商品条码是否在订单内  ljzhou 2013.10.10
 * @param  $location_barcode,$goods_barcode
 * @return
 */
 function check_common_grouding_goods_barcode($order_sn,$barcode){
    global $db;
    $sql = "SELECT 1 from ecshop.ecs_order_info oi
           LEFT JOIN ecshop.ecs_order_goods og ON oi.order_id = og.order_id
           LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_id
           LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
           LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
           LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id
           WHERE oi.order_sn = '{$order_sn}'
           AND (ii.serial_number = '{$barcode}' OR ( if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) = '{$barcode}' )) limit 1";
    //Qlog::log('check_common_grouding_goods_barcode sql:'.$sql);
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['error'] = "商品条码不在订单内！";
    }
    return $result;
 }

 /*
 * 检测通用下架时候商品条码是否在订单内  ljzhou 2013.10.10
 * @param  $location_barcode,$goods_barcode
 * @return
 */
 function check_common_undercarriage_goods_barcode($order_sn,$barcode){
    global $db;
    $sql = "SELECT 1 from ecshop.ecs_order_info oi
           LEFT JOIN ecshop.ecs_order_goods og ON oi.order_id = og.order_id
           LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_id
           LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
           LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
           LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id
           WHERE oi.order_sn = '{$order_sn}'
           AND (ii.serial_number = '{$barcode}' OR ( if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) = '{$barcode}' )) limit 1";
    //Qlog::log('check_common_undercarriage_goods_barcode sql:'.$sql);
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['error'] = "商品条码不在订单内！";
    }
    return $result;
 }


 /*
 * 检测上架时候商品数量是否合法（不超过上架容器内未上架的数量） zxcheng 2013.08.07
 * @param   $grouding_location_barcode,$goods_barcode,$input_number
 * @return
 */
 function check_grouding_goods_number($grouding_location_barcode,$goods_barcode,$input_number){
    global $db;
    $sql = "SELECT goods_number from romeo.inventory_location " .
            "WHERE location_barcode = '{$grouding_location_barcode}' AND goods_barcode = '{$goods_barcode}' AND goods_number > 0 limit 1";
    //Qlog::log('check_grouding_goods_number='.$sql);
    $goods_number = $db->getOne($sql);
    $result = array();
    if(!empty($goods_number)){
        if($goods_number >= $input_number){
             $result['success'] = true;
        }else{
             $result['error'] = "输入商品数超过上架容器未上架的数量:".$goods_number;
             $result['success'] = false;
        }
    }else{
        $result['success'] = false;
        $result['error'] = "检测上架时候商品数量是否合法时没找到上架容器或商品条码！";
    }
    return $result;
 }

 /* 检测目的容器是否存在  zxcheng 2013.08.09
 * @param  $location_barcode $location_type
 * @return
 */
 function check_to_location_barcode($location_barcode,$location_type){
    global $db;
    Qlog::log('检测目的容器是否存在  '.$location_barcode);
    $sql = "SELECT 1 from romeo.location WHERE  location_barcode='{$location_barcode}'  AND  location_type = '{$location_type}' limit 1";
    //Qlog::log('check_to_location_barcode='.$sql);
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['error'] = "容器:".$location_barcode."不存在,请先维护容器表！";
    }
    return $result;
 }
 
  /* 检测目的容器,party zxcheng 2013.12.24
 * @param  $location_barcode $location_type
 * @return
 */
 function check_to_location_barcode_party($from_location_barcode,$to_location_barcode,$location_type){
    global $db;
    //检查起始容器和目的容器的party_id是否一致
    $sql = "SELECT p.name,p.party_id from romeo.location l
                    left join romeo.party p on l.party_id = p.party_id
                    WHERE  l.location_barcode='{$from_location_barcode}'  limit 1";
    //Qlog::log('from_location_barcode='.$sql);
    $party = $db->getRow($sql);
    $party_id = $party['party_id'];
    $party_name_from = $party['name'];
    $result = array();
    if(empty($party_id)){
        $result['success'] = false;
        $result['error'] = "起始容器:".$from_location_barcode."不存在,请先维护容器表！";
    }
    else{
        //判断目的容器是是否存在
        $sql = "SELECT p.name,l.location_type from romeo.location l
                        left join romeo.party p on l.party_id = p.party_id
                        WHERE  l.location_barcode='{$to_location_barcode}' limit 1";
        //Qlog::log('to_location_barcode='.$sql);
        $to_location = $db->getROW($sql);
        $to_location_type = $to_location['location_type'];
        $party_name_to = $to_location['name'];
        if(empty($to_location_type)){
            $result['success'] = false;
            $result['error'] = "目的容器:".$to_location_barcode."不存在,请先维护容器表！";
        }else{
            if($to_location_type != $location_type){
                $result['success'] = false;
                $result['error'] = "容器:".$to_location_barcode."不是目的容器,不能操作哦！";
            }else{
                //检测起始容器和目的容器业务组是否一致
                $sql = "SELECT 1 from romeo.location  WHERE  location_barcode='{$to_location_barcode}'  and party_id = '{$party_id}' and  location_type = '{$location_type}' limit 1";
                //Qlog::log('party_is_like='.$sql);
                $res = $db->getOne($sql);
                if(!empty($res)){
                   $result['success'] = true;
                }else{
                   $result['success'] = false;
                   $result['error'] = "起始容器的组织：".$party_name_from."  目的容器的组织：".$party_name_to."  两者不一致,不能操作哦！";
                }
            }
        }
    }
    return $result;
 }

  /*
  * 病单 完结
  * */
 function terminal_sick_shipment($shipmentId){
     return terminalShipmentPick($shipmentId);
 }

 function inventory_stock_count($location_barcode,$goods_barcode,$serial_number,$goods_number){

    if(!check_location_goods_data($location_barcode,$goods_barcode,$serial_number)){
        $result = array();
        $result['success'] = false;
        $result['error'] = 'check_location_goods_data error';
        return $result;
    }
    include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    return inventoryStockCount($location_barcode,$goods_barcode,$serial_number,$goods_number);
 }

 function deliver_batch_location_product($batch_pick_sn,$from_location_barcode,$product_id){
    //check
    Qlog::log("deliver_batch_location_product{$bpsn}");

    $lock_name = "product_id_{$product_id}";
    $lock_file_name = get_file_lock_path($lock_name, 'pick');

    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;

    if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
        flock($lock_file_point, LOCK_UN);
        fclose($lock_file_point);
        $result = deliver_batch_location_product_real($batch_pick_sn,$from_location_barcode,$product_id);
        return $result;

    }else{
        fclose($lock_file_point);
        $result['success'] = false;
        $result['error'] = '同业务组有人正在捡该商品,请稍后提交。。。';
        return $result;
    }
 }
 function deliver_batch_location_product_real($bpsn,$location_barcode,$product_id){
     try{
        include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
        deliverLocationProduct($bpsn,$location_barcode,$product_id);

        $result['success'] = true;
        return $result;
    }catch (Exception $e){
        $result['success'] = false;
        $result['error'] = $e->getMessage();
        return $result;
    }
 }
  /**
  * 批拣出库并且容器转换 ljzhou 2013.08.15
  */
 function batch_pick_and_location_transaction($batch_pick_sn,$from_location_barcode,
        $goods_barcode,$goods_number,$serialNo,$shipmentId) {
    $result = array();
    if(!empty($goods_number)){
        $location_result = get_deliver_location_trans_params($from_location_barcode,$goods_barcode,$shipmentId);
        if($location_result['success']){
            $location_info = $location_result['res'];
            $goods_and_style = array('goods_id'=>$location_info['ecs_goods_id'], 'style_id'=>$location_info['ecs_style_id']);
            $validity = $location_info['validity'];
            $facilityId =  $location_info['facility_id'];
            $statusId = $location_info['status_id'];
            $product_id = $location_info['product_id'];
        }else{
            $result['success'] = false;
            $result['error'] = $location_result['error'];
            QLog::log($location_result['error']);
            QLog::log('begin batch_pick_and_location_transaction');
            QLog::log('batch_pick_sn:'.$batch_pick_sn);
            QLog::log('from_location_barcode:'.$from_location_barcode);
            QLog::log('goods_barcode:'.$goods_barcode);
            QLog::log('goods_number:'.$goods_number);
            QLog::log('serialNo:'.$serialNo);
            QLog::log('shipmentId:'.$shipmentId);
            return $result;
        }
        QLog::log('begin shipment');
        $shipment_result = get_shipment_goods_by_uniq_key($shipmentId,$goods_barcode,$from_location_barcode);
        if(!$shipment_result['success']){
            $result['success'] = false;
            $result['error'] = $shipment_result['error'];
            return $result;
        }
        $shipment = $shipment_result['res'];
        if($shipment['need_out_quantity'] < $goods_number){
            $result['success'] = false;
            $result['error'] = "出库数量大于该订单需要出库数量";
            return $result;
        }
        //遍历 shipment 对应的order
        $orders = get_order_ids($shipmentId,$product_id,$location_info['ecs_goods_id'],$location_info['ecs_style_id']);
        //合并订单才会循环
        foreach ($orders as $order){
            if($goods_number < 1){
                break;
            }
            if($order['goods_number'] < 1){
                continue;
            }
            $this_turn_num = ($goods_number > $order['goods_number'])?$order['goods_number']:$goods_number;
            $order_id = $order['order_id'];
            $toOrderId = $order_id;
            $orderGoodsId = $order['order_goods_id'];
            //location
            $res = createDeliverInventoryAndLocationTransaction(
                    'ITT_SALE',//transaction type
                    $goods_and_style,
                    $this_turn_num,//amount 该shipment对应的order出库的数量
                    $serialNo,
                    '',//from order id
                    $toOrderId,
                    'INV_STTS_AVAILABLE',//from  status
                    'INV_STTS_DELIVER', //to status
                    $orderGoodsId,
                    $facilityId, //fromFacilityId
                    $statusId,
                    $product_id,
                    $batch_pick_sn,
                    $from_location_barcode,
                    $validity,
                    'PICK',//actionType出库
                    $shipmentId);
            if(!$res){
                $result['success'] = false;
                $result['error'] = $res['error'];
                return $result;
            }
        }
        $result['success'] = true;
        return $result;
    }
 }

 function create_deliver_location($orderSn,$location_barcode,$goods_barcode,$goods_number,$serial_No){
    $result = array();
    $product_result = get_product_id_by_barcode($goods_barcode);
    if($product_result['success']){
        $product_info = $product_result['res'];
        $product_id = $product_info['product_id'];
    }else{
        $result['success'] = false;
        $result['error'] = $product_result['error'];
        QLog::log($product_result['error']);
        QLog::log('location_barcode:'.$location_barcode);
        QLog::log('goods_barcode:'.$goods_barcode);
        return $result;
    }
    $order_result = get_order_id_type($orderSn);
    if($order_result['success']){
        $order_info = $order_result['res'];
        $order_id   = $order_info['order_id'];
        $order_type = $order_info['order_type_id'];
        $party_id = $order_info['party_id'];
    }else{
        $result['success'] = false;
        $result['error'] = 'orderSn error';
        QLog::log('orderSn error:'.$orderSn);
        return $result;
    }

    $location_result = get_location_param_by_barcode($location_barcode,$goods_barcode,$goods_number);
    if($location_result['success']){
        $product_info = $location_result['res'];
        $validity = $product_info['validity'];
        $facility_id = $product_info['facility_id'];
        $status_id = $product_info['status_id'];
    }else{
        $result['success'] = false;
        $result['error'] = $location_result['error'];
        QLog::log('deliver location error');
        QLog::log('location_barcode:'.$location_barcode);
        QLog::log('goods_barcode:'.$goods_barcode);
        QLog::log('goods_number:'.$goods_number);
        return $result;
    }
    $action_type = 'ITT_SO_UNKNOWN';
    if(!empty($_CFG['adminvars']['order_type_transaction_type_map_out'][$order_type])){
        $action_type = $_CFG['adminvars']['order_type_transaction_type_map_out'][$order_type];
    }
    include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    $res = createDeliverLocationTransaction($product_id,//
                                $location_barcode,
                                $goods_number,
                                $validity,//
                                $facility_id,//
                                $status_id,//
                                $action_type,//
                                $order_id,//
                                $serial_No,
                                $party_id);
    if($res){
        if('OK' ==  $res['status']){
            $result['success'] = true;
        }else{
            $result['success'] = false;
            $result['error'] = $res['error'];
        }
    }else{
        $result['success'] = false;
    }
    return $result;
 }
  /*
  * 老库存出库：批捡单商品级别
  * */
 function one_key_batch_pick($bpsn){
    //check
    Qlog::log("one_key_batch_pick({$bpsn})");

    $lock_name = "party_{$_SESSION['party_id']}";
    $lock_file_name = get_file_lock_path($lock_name, 'pick');

    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;

    if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
        $result = one_key_batch_pick_deliver_inventory($bpsn);
        flock($lock_file_point, LOCK_UN);
        fclose($lock_file_point);
        return $result;

    }else{
        fclose($lock_file_point);
        $result['success'] = false;
        $result['error'] = '同业务组有人正在完结,请稍后完结。。。';
        return $result;
    }
 }
  /*
  * 库存出库：批捡单商品级别
  * */
 function one_key_batch_pick_deliver_inventory($bpsn){
    $result = array();
    try{
        include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
        $result['success'] = oneKeyBatchPickNew($bpsn);
        return $result;
    }catch (Exception $e){
        $result['success'] = false;
        $result['error'] = $e->getMessage();
        return $result;
    }
 }


  /**
  *  qdi 2013.08.21
  */
 function get_order_inventroy_num($order_id,$product_id){
    global $db;
    $sql = "select SUM(iid.QUANTITY_ON_HAND_DIFF) as already_out_number
        from romeo.inventory_item_detail iid
        left JOIN romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
        where iid.ORDER_ID = '{$order_id}'
        AND ii.PRODUCT_ID = '{$product_id}'
        AND iid.QUANTITY_ON_HAND_DIFF > 0
        group by iid.ORDER_ID
    ";
    $num = $db->getOne($sql);
    return $num;
 }

 /**
  * 得到运单号中具体商品对应的订单号 qdi 2013.08.21
  */
 function get_order_ids($shipement_id,$product_id,$goods_id,$style_id){
    global $db;
    $sql = "select oi.order_id, eog.goods_number + ifnull(iid.quantity_on_hand_diff,0) as goods_number, oi.order_type_id,eog.rec_id as order_goods_id
        from romeo.order_shipment os
        inner join ecshop.ecs_order_info oi on CAST( os.order_id AS UNSIGNED ) = oi.order_id
        inner join ecshop.ecs_order_goods eog on eog.order_id = os.order_id and eog.goods_id = '{$goods_id}' and eog.style_id = '{$style_id}'
        left join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(eog.rec_id using utf8)
        where
        os.shipment_id = '{$shipement_id}'";
    //Qlog::log('get_order_ids sql:'.$sql);
    $orders = $db->getAll($sql);
    foreach($orders as $order){
        $already_out_num = get_order_inventroy_num($order['order_id'],$product_id);
        $order['goods_number'] -= $already_out_num;
    }
    return $orders;
 }
  /**
  * 得到运单号中具体商品对应的订单号 qdi 2013.08.21
  */
 function get_order_ids_by_shipment_id($shipement_id){
    global $db;
    $sql = "select order_id
        from romeo.order_shipment
        where
        shipment_id = '{$shipement_id}'";
//  Qlog::log('get_order_ids sql:'.$sql);
    $order_ids = $db->getAll($sql);
    return $order_ids;
 }

/**
  * 得到所有运单号中对应的所有订单号 ytchen 2014.09.02
  */
 function get_order_ids_by_shipment_ids($shipment_ids){
    $shipmentIds = implode("','",$shipment_ids);
    global $db;
    $sql = "select DISTINCT(order_id)
        from romeo.order_shipment
        where
        shipment_id in ('{$shipmentIds}')";
    $order_ids = $db->getCol($sql);
    return $order_ids;
 }
  /**
  * 得到条码的订单号 ljzhou 2013.08.14
  */
  function get_barcode_order_id($batch_order_sn,$goods_barcode){
    global $db;
    $sql = "SELECT om.order_id from
              ecshop.ecs_batch_order_info oi
            LEFT JOIN ecshop.ecs_batch_order_mapping om ON oi.batch_order_id = om.batch_order_id
            LEFT JOIN ecshop.ecs_order_goods og ON og.order_id = om.order_id
            LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
            LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
        WHERE oi.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}' 
        group by om.order_id";
    // Qlog::log('get_barcode_order_id sql:'.$sql);

    $order_ids = $db->getCol($sql);
    $result = array();
    if(!empty($order_id)){
        $result['success'] = true;
        $result['res'] = $order_ids;
    }else{
        $result['success'] = false;
        $result['error'] = "根据批次号，条码找不到对应的订单号！";
    }
    return $result;
 }
 /**
  * 得到串号的订单号 ljzhou 2013.08.14
  */
  function get_serial_order_id($batch_order_sn,$serialNos){
    global $db;
    $sql = "SELECT om.order_id from
                ecshop.ecs_batch_order_info oi
              LEFT JOIN ecshop.ecs_batch_order_mapping om ON oi.batch_order_id = om.batch_order_id
              LEFT JOIN ecshop.ecs_order_goods og ON og.order_id = om.order_id
              LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
              LEFT JOIN romeo.inventory_item ii ON ii.product_id = pm.product_id
             WHERE oi.batch_order_sn = '{$batch_order_sn}' and
                   ii.serial_number = '{$serialNos}' limit 1";
    // Qlog::log('get_serial_order_id sql:'.$sql);
    $order_id = $db->getOne($sql);
    $result = array();
    if(!empty($order_id)){
        $result['success'] = true;
        $result['res'] = $order_id;
    }else{
        $result['success'] = false;
        $result['error'] = "根据批次号，串号找不到对应的订单号！";
    }
    return $result;
 }


 /*
 * 一个库位的1个sku只有一种生产日期检测  ljzhou 2013.09.14
 * @param  $to_location_barcode,$goods_barcode,$validity
 * @return
 */
 function check_goods_barcode_location_validity($to_location_barcode,$goods_barcode,$validity){
    global $db;
    $sql = "SELECT il.validity,g.is_maintain_warranty
    from romeo.inventory_location il
    left join romeo.product_mapping pm ON il.product_id = pm.product_id
    left join ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
    WHERE il.location_barcode = '{$to_location_barcode}' AND il.goods_barcode = '{$goods_barcode}' AND il.goods_number > 0
    limit 1";

    // Qlog::log('check_goods_barcode_location_validity sql:'.$sql);
    $res = $db->getRow($sql);
    $result = array();
    // 目的容器该商品还未维护,直接返回true
    if(empty($res)) {
        $result['success'] = true;
        return $result;
    }

    $origin_validity = $res['validity'];
    $is_maintain_warranty = $res['is_maintain_warranty'];
    Qlog::log('check_goods_barcode_location_validity $origin_validity:'.$origin_validity.' $validity:'.$validity);

    // 不维护生产日期的商品直接返回
    if(!$is_maintain_warranty) {
        $result['success'] = true;
        return $result;
    }

    if(!empty($res)){
        if($origin_validity == $validity){
             $result['success'] = true;
        }else{
             $result['error'] = "其他生产日期已经存在:".$origin_validity;
             $result['success'] = false;
        }
    }
    return $result;
 }

  /*
 * 得到库位上某商品的生产日期  ljzhou 2013.09.14
 * @param  $location_barcode,$goods_barcode
 * @return
 */
 function get_location_barcode_validity($location_barcode,$goods_barcode) {
    global $db;
    $sql = "SELECT il.validity
            FROM romeo.inventory_location il
            WHERE il.location_barcode = '{$location_barcode}' AND il.goods_barcode = '{$goods_barcode}' limit 1";

    // Qlog::log('get_location_barcode_validity sql:'.$sql);
    $validity = $db->getOne($sql);
    $result = array();

    if(empty($validity)) {
        $result['success'] = true;
        $result['validity'] = '';
    } else {
        $result['success'] = true;
        $result['validity'] = $validity;
    }

    return $result;
 }

 /*
 * 检测批拣单上发货单是否全部绑定面单 cywang 2013.10.21
 * @param  $batch_pick_sn
 * @return
 *  $result[success]    true：均有面单
 *                      false：至少有一个发货单没有绑定面单
 */
 function check_batch_pick_carrier_bill_all_printed($batch_pick_sn)
 {
    global $db;
    $sql = "SELECT * from (select * from romeo.batch_pick_mapping where batch_pick_sn = '{$batch_pick_sn}') as t1
            LEFT JOIN romeo.shipment s on t1.shipment_id = s.shipment_id
            where ISNULL(s.TRACKING_NUMBER)";
    $res = $db->getOne($sql);
    $result = array();
    if(empty($res)){
        $result['success'] = true;
        $result['error'] = "面单已完全打印";
    }
    else
    {
        $result['success'] = false;
        $result['error'] = "面单未完全打印";
    }
    return $result;
 }

 /*
 * 检测批捡号  zxcheng 2013.08.12
 * @param  $batch_pick_sn
 * @return
 */
 function check_batch_pick_barcode($batch_pick_sn){
    global $db;
    $sql = "SELECT is_pick from romeo.batch_pick WHERE batch_pick_sn = '{$batch_pick_sn}' limit 1";
    $res = $db->getOne($sql);
    $result = array();
    $result['success'] = true;
    if(!empty($res)){
        if($res == 'Y') {
            $result['error'] = "批拣号已完结";
            $result['success'] = false;
        }
        else
        {
            $carrier_bill_result = check_batch_pick_carrier_bill_all_printed($batch_pick_sn);
            if(!array_key_exists('success', $carrier_bill_result))
                die('check_batch_pick_carrier_bill_all_printed error');
            if(!$carrier_bill_result['success'])
            {
                $result = $carrier_bill_result;
            }
        }

    }else{
        $result['success'] = false;
        $result['error'] = "批拣号不存在！";
    }
    return $result;
 }
 /*
 * 检测批捡某商品的条码  zxcheng 2013.08.12
 * @param  $batch_pick_sn,$location_barcode，$goods_barcode
 * @return
 */
 function check_batch_pick_goods_barcode($batch_pick_sn,$location_barcode,$goods_barcode){
    global $db;
    $sql = "SELECT 1 from  romeo.batch_pick  bp
                   LEFT JOIN romeo.batch_pick_mapping bpm ON bp.batch_pick_sn = bpm.batch_pick_sn
                   LEFT JOIN romeo.shipment s ON  s.shipment_id = bpm.shipment_id
                   LEFT JOIN romeo.order_shipment  os ON os.shipment_id = s.shipment_id
                   LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = cast(os.order_id as unsigned)
                   LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
                   LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
                   LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
                   LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = og.goods_id AND og.style_id = pm.ecs_style_id
                   LEFT JOIN romeo.inventory_location il ON il.product_id = pm.product_id AND il.facility_id = oi.facility_id
            where bp.batch_pick_sn = '{$batch_pick_sn}'  AND
                if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}'  AND
                il.location_barcode = '{$location_barcode}' AND
                il.status_id = 'INV_STTS_AVAILABLE' AND
                oi.party_id = il.party_id limit 1";
    $res = $db->getOne($sql);
    $result = array();
    if(!empty($res)){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['error'] = "商品条码不合法！";
    }
    return $result;
 }
 /*
 * 检测批捡某商品的数量  zxcheng 2013.08.12
 * @param  $batch_pick_sn,$location_barcode，$goods_barcode,$goods_number
 * @return
 */
 function check_batch_pick_goods_number($batch_pick_sn,$location_barcode,$goods_barcode,$goods_number){
    global $db;
    $sql_a = "SELECT SUM(IFNULL(-iid.QUANTITY_ON_HAND_DIFF,0)) AS in_number,SUM(IFNULL(og.goods_number,0)) AS goods_number
              from  romeo.batch_pick  bp
                   LEFT JOIN romeo.batch_pick_mapping bpm ON bp.batch_pick_sn = bpm.batch_pick_sn
                   LEFT JOIN romeo.shipment s ON  s.shipment_id = bpm.shipment_id
                   LEFT JOIN romeo.order_shipment  os ON os.shipment_id = s.shipment_id
                   LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = cast(os.order_id as unsigned)
                   LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
                   LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
                   LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
                   LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
                   LEFT JOIN romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
                WHERE bp.batch_pick_sn = '{$batch_pick_sn}'  AND if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}'  limit 1";

    $sql_b ="SELECT reserved_quantity,out_quantity from  romeo.inventory_location_reserve WHERE
                     batch_pick_sn ='{$batch_pick_sn}'  limit 1";
    $res_a = $db->getRow($sql_a);
    $res_b = $db->getRow($sql_b);
    $result = array();
    if(!empty($res_a)&&!empty($res_b)){
        $has_out_sum = $res_a['in_number'];
        $out_sum = $res_a['goods_number'];
        $atp_sum = $res_b['reserved_quantity']-$res_b['out_quantity'];
        $not_out_sum = $out_sum - $has_out_sum;
        if($goods_number <= $not_out_sum&&$goods_number <= $atp_sum){
             $result['success'] = true;
        }else if($goods_number > $not_out_sum){
             $result['error'] = "输入的商品数量不能大于未出库商品数量！";
             $result['success'] = false;
        }else{
             $result['error'] = "输入的商品数量不能大于可预定数量！";
             $result['success'] = false;
        }
    }else{
        $result['success'] = false;
        $result['error'] = "未找到对应的商品！";
    }
    return $result;
 }

/**
 * 上架/移库 容器转换
 * ljzhou 2013.8.15
 */
function common_location_transaction($from_location_barcode,$to_location_barcode,$goods_barcode,$serial_number,$goods_number) {
     $result = array();

     // 得到容器转换的基本参数
     $location_result = get_location_trans_params($from_location_barcode,$goods_barcode);

     if(!$location_result['success']){
        $result['success'] = false;
        $result['error'] = $location_result['error'];
        return $result;
     }
     $location_info = $location_result['res'];

     $goods_and_style = array('goods_id'=>$location_info['ecs_goods_id'], 'style_id'=>$location_info['ecs_style_id']);
     $fromLocationBarcode = $from_location_barcode;
     $toLocationBarcode = $to_location_barcode;
     if(!empty($serial_number)) {
        $serialNos = array($serial_number);
     } else {
        $serialNos = null;
     }

     $goodsBarcode = $goods_barcode;
     $amount = $goods_number;
     $validity = $location_info['validity'];
     $batch_sn = $location_info['batch_sn'];
     $facilityId =  $location_info['facility_id'];
     $statusId = $location_info['status_id'];
     $fromLocationType = $location_info['from_location_type'];
     $to_location_result = get_to_location_type($to_location_barcode);
     if($to_location_result['location_type']) {
        $toLocationType = $to_location_result['location_type'];
     } else {
        $toLocationType = $fromLocationType;
     }
     $orderId = '1';
     if($fromLocationType == 'IL_GROUDING' && $toLocationType == 'IL_LOCATION') {
        $actionType = 'GROUDING';
     } else if($fromLocationType == 'IL_LOCATION' && $toLocationType == 'IL_LOCATION') {
        $actionType = 'MOVING';
     } else if($fromLocationType == 'IL_LOCATION' && $toLocationType == '') {
        $actionType = 'PICK';
     } else if($fromLocationType == '' && $toLocationType == 'IL_LOCATION') {
        $actionType = 'RECEIVE';
     } else {
        $actionType = 'GROUDING';
     }
     Qlog::log('goods_id:'.$location_info['ecs_goods_id']);
     Qlog::log('style_id:'.$location_info['ecs_style_id']);
     Qlog::log('fromLocationBarcode:'.$fromLocationBarcode);
     Qlog::log('toLocationBarcode:'.$toLocationBarcode);
     Qlog::log('serialNos:'.$serial_number);
     Qlog::log('goodsBarcode:'.$goodsBarcode);
     Qlog::log('amount:'.$amount);
     Qlog::log('validity:'.$validity);
     Qlog::log('batch_sn:'.$batch_sn);
     Qlog::log('facilityId:'.$facilityId);
     Qlog::log('statusId:'.$statusId);
     Qlog::log('actionType:'.$actionType);
     Qlog::log('orderId:'.$orderId);

     if (!function_exists("createPurchaseAcceptAndTransfer")) {
        include_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
     }

     $res = createTransferLocationTransaction(
                     $goods_and_style,
                     $fromLocationBarcode,
                     $toLocationBarcode,
                     $serialNos,
                     $goodsBarcode,
                     $amount,
                     $validity,
                     $batch_sn,
                     $facilityId,
                     $statusId,
                     $actionType,
                     $orderId
                     );
    Qlog::log('createTransferLocationTransaction error:'.$res['error']);
    Qlog::log('createTransferLocationTransaction status:'.$res['status']);

    if($res['status'] == 'OK') {
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = $res['error'];
    }
    return $result;
}
function get_sn_inventory($serial_number){
    $result = array();
    $sql = "
            select
                *
            from romeo.inventory_item
            where
                 serial_number = '{$serial_number}'
            limit 1
     ";
    // Qlog::log('get_to_location_type:'.$sql);
    $location_type = $db->getOne($sql);
}
function get_sn_location($serial_number){
    global $db;
    $result = array();
    $sql = "
            select
                serial_number
            from romeo.inventory_item
            where
                 serial_number = '{$serial_number}'
            limit 1
     ";
    // Qlog::log('get_to_location_type:'.$sql);
    $serial_number = $db->getOne($sql);
}
/*
 *
 * */
function check_location_goods_data($location_barcode,$goods_barcode,$serial_number){
    global $db;
    $sql = "
            select
                location_id
            from romeo.location
            where
                 location_barcode = '{$location_barcode}'
            limit 1
     ";
    $location_id = $db->getOne($sql);
    if(empty($location_id)){
        return false;
    }
    $product_result = get_product_id_by_barcode($goods_barcode);
    if($product_result['success']){
        $product_info = $product_result['res'];
        $goods_id = $product_info['ecs_goods_id'];
    }else{
        return false;
    }
    include_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    $item_type = getInventoryItemType($goods_id);
    if('NON-SERIALIZED' == $item_type && !empty($serial_number)){
        return false;
    }
    return true;
}

/**
 * 得到目的容器类型
 * ljzhou 2013.08.15
 */
function get_to_location_type($to_location_barcode) {
    global $db;
    $result = array();
    $sql = "
            select
                 location_type
            from romeo.location
            where
                 location_barcode = '{$to_location_barcode}'
            limit 1
     ";

    // Qlog::log('get_to_location_type:'.$sql);
    $location_type = $db->getOne($sql);
    Qlog::log('get_to_location_type location_type:'.$location_type);
    if(!empty($location_type)) {
        $result['location_type'] = $location_type;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到目的容器类型参数为空！";
    }

    return $result;
}

/**
 * 得到容器转换的基本参数
 * ljzhou 2013.08.15
 */
function get_location_trans_params($location_barcode,$goods_barcode) {
    global $db;
    $result = array();
    $sql = "
            select
                 il.validity, il.batch_sn,il.facility_id,il.status_id,il.party_id,l.location_type as from_location_type,pm.ecs_goods_id,pm.ecs_style_id
            from  romeo.location l
            inner  join romeo.inventory_location il ON l.location_barcode = il.location_barcode
            left  join romeo.product_mapping pm ON il.product_id = pm.product_id
            where
                 l.location_barcode = '{$location_barcode}' and il.goods_barcode = '{$goods_barcode}'
            limit 1
     ";

    // Qlog::log('get_location_trans_params:'.$sql);
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到容器转换的基本参数为空！";
    }

    return $result;
}

/**
 * 得到容器转换的基本参数
 * ljzhou 2013.08.15
 */
function get_deliver_location_trans_params($location_barcode,$goods_barcode,$shipment_id) {
    global $db;
    $result = array();
    $sql = "
        select il.inventory_location_id,il.validity,il.facility_id,il.status_id,il.party_id,
                pm.ecs_goods_id,pm.ecs_style_id,pm.product_id
            from romeo.inventory_location il
            LEFT  JOIN romeo.inventory_location_reserve ilr on il.inventory_location_id = ilr.inventory_location_id
            LEFT JOIN romeo.product_mapping pm ON il.product_id = pm.product_id
            where  ilr.shipment_id = '{$shipment_id}' and il.location_barcode = '{$location_barcode}' and il.goods_barcode = '{$goods_barcode}'
            limit 1
     ";
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到容器转换的基本参数为空！";
        // Qlog::log('get_location_trans_params:'.$sql);
    }

    return $result;
}
/**
 * qdi
 */
function get_product_id_by_barcode($goods_barcode) {
    global $db;
    $result = array();
    $sql = "
        select pm.product_id,pm.ecs_goods_id from romeo.product_mapping pm
            left join ecshop.ecs_goods g on pm.ECS_GOODS_ID = g.goods_id
            left join ecshop.ecs_goods_style gs on pm.ECS_STYLE_ID = gs.style_id and pm.ECS_GOODS_ID = gs.goods_id
            where IFNULL(gs.barcode,g.barcode) = '{$goods_barcode}'
            limit 1
     ";
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到容器转换的基本参数为空！";
        // Qlog::log('get_location_trans_params:'.$sql);
    }

    return $result;
}
/**
 * qdi
 */
function get_location_param_by_barcode($location_barcode,$goods_barcode,$goods_number) {
    global $db;
    $result = array();
    $sql = "
        select il.validity,il.facility_id,il.status_id
            from romeo.inventory_location il
            where  il.location_barcode = '{$location_barcode}'
                and il.goods_barcode = '{$goods_barcode}'
                and il.goods_number >= '{$goods_number}'
            limit 1
     ";
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到容器转换的基本参数为空！";
        // Qlog::log('get_location_trans_params:'.$sql);
    }

    return $result;
}

function get_shipment_goods_by_uniq_key($shipmentId,$goods_barcode,$from_location_barcode){
    global $db;
    $result = array();
    $sql = "SELECT
         ilr.shipment_id,
        (ilr.reserved_quantity - ilr.out_quantity) as need_out_quantity
        from romeo.inventory_location_reserve as ilr
        LEFT JOIN romeo.inventory_location as il on ilr.inventory_location_id = il.inventory_location_id
        WHERE
                ilr.shipment_id = '{$shipmentId}'
        AND il.goods_barcode = '{$goods_barcode}'
        AND il.location_barcode = '{$from_location_barcode}'
        AND (ilr.reserved_quantity - ilr.out_quantity) > 0
        limit 1
            ";
    $res = $db->getRow($sql);
    if(!empty($res)) {
        $result['res'] = $res;
        $result['success'] = true;
    } else {
        $result['success'] = false;
        $result['error'] = "得到shipment对应的商品信息的基本参数为空！";
        // Qlog::log('get_shipment_goods_by_uniq_key:'.$sql);
    }
    return $result;
}

/**
 * 判断是否串号控制 zxcheng 2013-08-19
 * @param  $party_id,$goods_barcode
 */
function check_goods_is_serial($party_id,$goods_barcode)
{
    $result = array();
    //查找goods_id,先检查串号的
    $res = get_goods_serial_goods_id($party_id,$goods_barcode);
    if($res['success']){
       $goods_id = $res['goods_id'];
       $goods_barcode_item_type = $res['serial'];
    }else{
        $res = get_goods_barcode_goods_id($party_id,$goods_barcode);
        if($res['success']){
          $goods_id = $res['goods_id'];
          $goods_barcode_item_type = $res['serial'];
        }else{
           $result['error'] = $res['error'];
           $result['success'] = false;
           return $result;
        }
    }
    require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
    $goods_item_type = getInventoryItemType ($goods_id);
    //对比是否非串号控制
    if(($goods_item_type == 'NON-SERIALIZED'&&$goods_barcode_item_type == false)||
       ($goods_item_type == 'SERIALIZED'&&$goods_barcode_item_type == true)){
        $result['success'] = true;
        $result['res'] = $goods_barcode_item_type;
    }else{
        $result['success'] = false;
        $result['error'] = '串号控制不一致,串号商品必须扫串号！';
    }
    return $result;
}

/**
* 检查字符串为barcode/SN/error  by cywang
*/
function check_string_type($party_id,$string_to_check)
{
    global $db;
    $result = array();
    //假设string为sn
    $sql = "SELECT og.goods_id from
             romeo.inventory_item ii
             LEFT JOIN   romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
             LEFT JOIN   ecshop.ecs_order_info oi ON oi.order_id = iid.order_id
             LEFT JOIN   ecshop.ecs_order_goods og ON oi.order_id = og.order_id
          WHERE oi.party_id = '{$party_id}' AND ii.serial_number = '{$string_to_check}'
          AND ii.status_id = 'INV_STTS_AVAILABLE'
          limit 1";
    $goods_id = $db->getOne($sql);
    if($goods_id)
    {
        //该string为串号，并找到其goods_id
        $result['success'] = true;
        $result['goods_id'] = $goods_id;
        $result['type'] = 'SN';
    }
    else
    {
        //该string非串号，check是否为barcode
        $sql = "SELECT  g.goods_id from
                 ecshop.ecs_goods g
                LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = g.goods_id
            WHERE g.goods_party_id = '{$party_id}' and if(gs.barcode is not null AND gs.barcode <>'',gs.barcode,g.barcode) = '{$string_to_check}' limit 1
            ";
        $goods_id = $db->getOne($sql);
        if($goods_id)
        {
            //该string为条码
            $result['success'] = true;
            $result['goods_id'] = $goods_id;
            $result['type'] = 'barcode';
        }
        else
        {
            //该string既非条码也非串号
            $result['success'] = false;
            $result['type'] = 'ERROR';
        }
    }
    return $result;
}


/**
 * 根据商品条码找goods_id
 */
function get_goods_barcode_goods_id($party_id,$goods_barcode){
    global $db;
    $result = array();
    $sql = "SELECT  g.goods_id from
             ecshop.ecs_goods g
            LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = g.goods_id
        WHERE g.goods_party_id = '{$party_id}' and if(gs.barcode is not null AND gs.barcode <>'',gs.barcode,g.barcode) = '{$goods_barcode}' limit 1
        ";
    $goods_id = $db->getOne($sql);
    if(!empty($goods_id)){
        $result['success'] = true;
        $result['goods_id'] = $goods_id;
        $result['serial'] = false;
    }else{
        $result['success'] = false;
        $result['serial'] = true;
        $result['error'] ='串号检测没找到对应的商品！';
    }
    return $result;
}
/*
 *根据商品串号找goods_id
 */
function get_goods_serial_goods_id($party_id,$goods_barcode){
    global $db;
    $result = array();
    $sql = "SELECT og.goods_id from
             romeo.inventory_item ii
             LEFT JOIN   romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
             LEFT JOIN   ecshop.ecs_order_info oi ON oi.order_id = iid.order_id
             LEFT JOIN   ecshop.ecs_order_goods og ON oi.order_id = og.order_id
          WHERE oi.party_id = '{$party_id}' AND ii.serial_number = '{$goods_barcode}'
          AND ii.status_id = 'INV_STTS_AVAILABLE'
          limit 1
        ";
    $goods_id = $db->getOne($sql);
    if(!empty($goods_id)){
        $result['success'] = true;
        $result['goods_id'] = $goods_id;
        $result['serial'] = true;
    }else{
        $result['success'] = false;
        $result['serial'] = false;
    }
    return $result;
}


/**
 * 非串号获取未入库数
 * @param  $batch_order_sn,$goods_barcode
 */
function get_goods_not_in_number($batch_order_sn,$goods_barcode){
    global $db;
    $result = array();
    $order_no_in_goods_numbers = get_order_no_in_goods_numbers($batch_order_sn,$goods_barcode);
    if(empty($order_no_in_goods_numbers)) {
        $result['success'] = false;
        $result['error'] ='未发现有未入库的商品,batch_order_sn:'.$batch_order_sn.' goods_barcode:'.$goods_barcode;
    }
    
    $not_in_number = 0;
    foreach($order_no_in_goods_numbers as $order_no_in_goods_number) {
        $not_in_number += $order_no_in_goods_number['not_in_number'];
    }
    
    $result['success'] = true;
    $result['not_in_number'] = $not_in_number;

    return $result;
}
/**
 * 非串号获取未入库数 (只取一条未入库数量最大的rec_id)
 */
function get_goods_not_in_number_v2($batch_order_sn,$goods_barcode){
    global $db;
    $result = array();
    $order_no_in_goods_numbers = get_order_no_in_goods_numbers_v2($batch_order_sn,$goods_barcode);
   
    if(empty($order_no_in_goods_numbers)) {
        $result['success'] = false;
        $result['error'] ='未发现有未入库的商品,batch_order_sn:'.$batch_order_sn.' goods_barcode:'.$goods_barcode;
    }
    $not_in_number = $order_no_in_goods_numbers['not_in_number'];
    
    $result['success'] = true;
    $result['not_in_number'] = $not_in_number;

    return $result;
}

/**
 * 获取通用上架未上架商品数 ljzhou 2013-10-10
 * @param  $order_sn,$goods_barcode,$serial_number
 */
function get_common_grouding_not_in_number($order_sn,$goods_barcode,$serial_number){
    global $db;
    $result = array();

    // 待测试
    // 非串号
    if(empty($serial_number)) {
         $sql = "SELECT SUM(IFNULL(ld.goods_number_diff,0)) as in_number,og.goods_number as goods_number from
                ecshop.ecs_order_info oi
                LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
                LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                LEFT JOIN romeo.inventory_location_detail ld ON oi.order_id = ld.order_id and pm.product_id = ld.product_id
                LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
                LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
                LEFT JOIN romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
            WHERE bo.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}' group by bo.batch_order_sn
         ";
    } else {
        $sql = "SELECT SUM(IFNULL(iid.QUANTITY_ON_HAND_DIFF,0)) as in_number,og.goods_number as goods_number from
               ecshop.ecs_batch_order_info bo
                LEFT JOIN ecshop.ecs_batch_order_mapping om ON bo.batch_order_id = om.batch_order_id
                LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = om.order_id
                LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
                LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
                LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
                LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
                LEFT JOIN romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
            WHERE bo.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}' group by bo.batch_order_sn
         ";
    }

    $res = $db->getRow($sql);
    if(!empty($res)){
        $result['success'] = true;
        $result['not_in_number'] = $res['goods_number'] - $res['in_number'];
    }else{
        $result['success'] = false;
        $result['error'] ='获取未入库商品数时出现异常！';
    }
    return $result;
}


/**
 * 非串号获取未入库数 ljzhou 2013-08-26
 * @param  $location_barcode,$goods_barcode,$serial_number
 */
function get_location_serail_goods_number($location_barcode,$goods_barcode,$serial_number){
    global $db;
    $result = array();
    $sql = "SELECT goods_number from romeo.location_barcode_serial_mapping
            WHERE location_barcode = '{$location_barcode}' and goods_barcode = '{$goods_barcode}' and serial_number = '{$serial_number}' limit 1
         ";
    $goods_number = $db->getOne($sql);
    if(!empty($goods_number) || $goods_number ==0){
        $result['success'] = true;
        $result['goods_number'] = $goods_number;
    }else{
        $result['success'] = false;
        $result['error'] ='非串号获取未入库数时系统未找到串号信息！';
    }
    return $result;
}

/**
 * 推荐上架库位 zxcheng 2013-08-20
 * @param $grouding_location_barcode,$goods_barcode,$status_id
 */
 function recommend_grouding_location($grouding_location_barcode,$goods_barcode,$status_id)
 {
      global $db;
      $result = array();
      $sql =  "SELECT il.facility_id,il.validity,il.status_id,il.party_id,il.goods_barcode
                 from romeo.location l
                 inner join romeo.inventory_location il ON il.location_barcode = l.location_barcode
                 where l.location_barcode = '{$grouding_location_barcode}' AND
                       il.status_id = '{$status_id}' AND
                       il.goods_barcode = '{$goods_barcode}' AND
                       l.location_type = 'IL_GROUDING'
                 limit 1
                 ";
     $res_a = $db->getRow($sql);
     if(empty($res_a)){
        $result['success'] = false;
        $result['error'] = $grouding_location_barcode.":该上架容器上没有找到对应的商品！";
        return $result;
     }
     $facility_id = $res_a['facility_id'];
     $validity = $res_a['validity'];
     $status_id = $res_a['status_id'];
     $party_id = $res_a['party_id'];
     $sql = " SELECT l.location_barcode from
                     romeo.location l
                     inner join romeo.inventory_location il ON il.location_barcode = l.location_barcode
                     where il.facility_id = '{$facility_id}' AND
                           il.validity = '{$validity}' AND
                           il.status_id = '{$status_id}' AND
                           il.party_id = '{$party_id}' AND
                           il.goods_barcode = '{$goods_barcode}' AND
                           l.location_type = 'IL_LOCATION' AND
                           il.goods_number > 0
               order by  il.goods_number LIMIT 1
             ";
    $location_barcode = $db->getOne($sql);
    if(!empty($location_barcode)){
        $result['success'] = true;
        $result['location_barcode'] = $location_barcode;
    }else{
        $result['success'] = false;
        $result['error'] = "没有找到推荐库位！";
    }
    return $result;
 }


/**
 * 推荐通用上架库位 ljzhou 2013-10-10
 * @param $order_sn,$goods_barcode,$status_id
 */
 function recommend_common_grouding_location($order_sn,$validity,$goods_barcode,$status_id)
 {
      global $db;
      $result = array();
      $sql =  "SELECT facility_id,party_id from ecshop.ecs_order_info where order_sn = '{$order_sn}'    limit 1";
     $res_a = $db->getRow($sql);
     if(empty($res_a)){
        $result['success'] = false;
        $result['error'] = $order_sn.":该订单没有找到对应的仓库组织！";
        return $result;
     }
     $facility_id = $res_a['facility_id'];
     $validity = $validity;
     $status_id = $status_id;
     $party_id = $res_a['party_id'];
     $sql = " SELECT l.location_barcode from
                     romeo.location l
                     inner join romeo.inventory_location il ON il.location_barcode = l.location_barcode
                     where il.facility_id = '{$facility_id}' AND
                           il.validity = '{$validity}' AND
                           il.status_id = '{$status_id}' AND
                           il.party_id = '{$party_id}' AND
                           il.goods_barcode = '{$goods_barcode}' AND
                           l.location_type = 'IL_LOCATION' AND
                           il.goods_number > 0
               order by  il.goods_number LIMIT 1
             ";
    $location_barcode = $db->getOne($sql);
    if(!empty($location_barcode)){
        $result['success'] = true;
        $result['location_barcode'] = $location_barcode;
    }else{
        $result['success'] = false;
        $result['error'] = "没有找到推荐库位！";
    }
    return $result;
 }

 /**
 * 获取组织   zxcheng 2013-08-21
 * @param $location_barcode,$goods_barcode
 */
 function get_party_by_location($location_barcode,$goods_barcode)
 {
      global $db;
      $result = array();
      $sql = "SELECT il.party_id
                  FROM    romeo.location l
                          inner join romeo.inventory_location il ON il.location_barcode = l.location_barcode
                          LEFT JOIN  romeo.location_barcode_serial_mapping bsm ON il.location_barcode = bsm.location_barcode AND
                          il.goods_barcode = bsm.goods_barcode
                  WHERE   l.location_barcode = '{$location_barcode}' AND
                          (il.goods_barcode  = '{$goods_barcode}' OR bsm.serial_number = '{$goods_barcode}') AND
                          il.status_id = 'INV_STTS_AVAILABLE'
                  limit 1
                 ";
      $party_id = $db->getOne($sql);
      if(!empty($party_id)){
          $result['success'] = true;
          $result['res'] = $party_id;
      }else{
          $result['success'] = false;
          $result['error'] = "根据容器没有找到对应的组织！";
          return $result;
      }
      return $result;
 }

  /**
 * 根据订单获取组织   ljzhou 2013-10-10
 * @param $order_sn
 */
 function get_party_by_order_sn($order_sn)
 {
      global $db;
      $result = array();
      $sql = "SELECT party_id FROM ecshop.ecs_order_info where order_sn = '{$order_sn}' limit 1 ";
      $party_id = $db->getOne($sql);
      if(!empty($party_id)){
          $result['success'] = true;
          $result['party_id'] = $party_id;
      }else{
          $result['success'] = false;
          $result['error'] = "根据订单没有找到对应的组织！order_sn:".$order_sn;
          return $result;
      }
      return $result;
 }

 /**
  * 根据shippment获取party_id
  * @param $shipment_id
  */
 function  get_partyId_by_shipmentId($shipment_id){
     global $db;
     $sql = "SELECT s.party_id from
                ecshop.ecs_goods g
                LEFT JOIN romeo.shipment s ON s.party_id = g.goods_party_id
            WHERE s.shipment_id = '{$shipment_id}' limit 1";
     $party_id = $db->getOne($sql);
     if(!empty($party_id)){
        return $party_id;
     }else{
        return null;
     }
 }
  /**
  *
  * @param  $shipment_id, $goods_barcode,$serial_number
  */
 function check_barcode_serial_number($shipment_id, $goods_barcode,$serial_number){
     global $db;
     //获取组织
     $party_id = get_partyId_by_shipmentId($shipment_id);
     $sql = "SELECT 1 from
                    ecshop.ecs_goods g
                LEFT JOIN ecshop.ecs_goods_style gs ON  gs.goods_id = g.goods_id
                LEFT JOIN romeo.product_mapping pm ON  pm.ecs_goods_id = gs.goods_id and pm.ecs_style_id = gs.style_id
                LEFT JOIN romeo.inventory_item ii ON ii.product_id = pm.product_id
             WHERE g.goods_party_id = '{$party_id}' AND
                   ii.serial_number = '{$serial_number}' AND
                   if(gs.barcode is not null AND gs.barcode <>'',gs.barcode,g.barcode) = '{$goods_barcode}' limit 1 ";
     $res = $db->getOne($sql);
      if($res){
          $result['success'] = true;
      }else{
          $result['success'] = false;
          $result['error'] = "没找到记录，抛异常！";
      }
     return $result;
 }
 /**
  * 根据批次订单号获取provider_id zxcheng 2013-08-23
  * @param $batch_order_sn
 */
 function get_provider_id($batch_order_sn){
    global $db;
    $result = array();
    $sql = "SELECT provider_id from  ecshop.ecs_batch_order_info  where batch_order_sn = '{$batch_order_sn}' limit 1
           ";
    $provider_id = $db->getOne($sql);
    if(!empty($provider_id)){
        $result['success'] = true;
        $result['provider_id'] = $provider_id;
    }else{
        $result['success'] = false;
        $result['error'] = "没有找到对应的供应商！";
    }
    return $result;
 }
 /**
  *  获取未上架商品数 zxcheng 2013-08-24
  * @param $grouding_location_barcode,$goods_barcode
  */
  function get_grouding_not_in_number($grouding_location_barcode,$goods_barcode){
    global $db;
    $result = array();
    $sql = "SELECT il.goods_number
                from  romeo.location l
                inner join romeo.inventory_location il ON il.location_barcode = l.location_barcode
                WHERE  l.location_barcode = '{$grouding_location_barcode}' AND
                       il.goods_barcode = '{$goods_barcode}' AND
                       l.location_type = 'IL_GROUDING' AND
                       il.goods_number > 0  limit 1
           ";
    $grouding_not_in_number = $db->getOne($sql);
    if(!empty($grouding_not_in_number)){
        $result['success'] = true;
        $result['not_in_number'] = $grouding_not_in_number;
    }else{
        $result['success'] = false;
        $result['error'] = "该上架容器内没有商品可上架！";
    }
    return  $result;
  }

  /**
  *  简单盘点商品条码检查 ljzhou 2013-09-17
  * @param $goods_barcode
  */
  function check_take_stock_goods_barcode($goods_barcode) {
    global $db;
    $result = array();
    // 先查串号
    $sql = "SELECT serial_number from
             romeo.inventory_item ii
          WHERE ii.serial_number = '{$goods_barcode}'
          AND ii.status_id = 'INV_STTS_AVAILABLE'
          limit 1
        ";
    $serial_number = $db->getOne($sql);
    if(!empty($serial_number)) {
        $result['is_serial'] = true;
        $result['success'] = true;
        return $result;
    } else {
        // 再检查条码
        $sql = "SELECT g.goods_id from
                 ecshop.ecs_goods g
                LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = g.goods_id
            WHERE if(gs.barcode is not null AND gs.barcode <>'',gs.barcode,g.barcode) = '{$goods_barcode}' limit 1
            ";
        $goods_id = $db->getOne($sql);

        if(!empty($goods_id)) {
            $result['is_serial'] = false;
            $result['success'] = true;
            return $result;
        } else {
            $result['success'] = false;
            $result['error'] = '商品条码或串号系统中找不到！请检查';
            return  $result;
        }
    }

  }

  /**
   * 通过一组shipment_ids数组得到尽可能多的可预订量够的shipment_ids ljzhou 2013-09-15
   * 用递归的思想做
   * $fiter_shipment_ids：全部shipment_ids数组
   * $goods_shipment_ids：已经ok的shipment_ids数组
   * $cur_pos：当前指针位置
   * $cur_length：目前长度
   * $need_length：需要的长度
   */
function get_good_shipment_ids($fiter_shipment_ids,$good_shipment_ids,$cur_pos,$cur_length,$need_length) {

    if(count($fiter_shipment_ids) < $need_length) {
        $need_length = count($fiter_shipment_ids);
    }
    if($cur_pos >= count($fiter_shipment_ids)) {
//      pp('>=');
//      pp($good_shipment_ids);
        $result = array();
        $result['last_pos'] = $cur_pos;
        $result['good_shipment_ids'] = $good_shipment_ids;
        return $result;
    }
    if($cur_length == $need_length) {
//      pp('==');
//      pp($good_shipment_ids);
        $result = array();
        $result['last_pos'] = $cur_pos;
        $result['good_shipment_ids'] = $good_shipment_ids;
        return $result;
    }
//  pp('add item:');pp($fiter_shipment_ids[$cur_pos]);
//  pp('good_shipment_ids:');pp($good_shipment_ids);
    $new_good_shipment_ids = $good_shipment_ids;
    $new_good_shipment_ids[] = $fiter_shipment_ids[$cur_pos];
//  pp('new_good_shipment_ids:');pp($new_good_shipment_ids);

    $res = is_shipments_have_enough_inventory($new_good_shipment_ids);
//  pp('res:');pp($res);
    if($res){
        return get_good_shipment_ids($fiter_shipment_ids,$new_good_shipment_ids,$cur_pos+1,$cur_length+1,$need_length);
    }else{
        return get_good_shipment_ids($fiter_shipment_ids,$good_shipment_ids,$cur_pos+1,$cur_length,$need_length);
    }
}

/**
 * 检查-T的某商品是否入了新库存
 * ljzhou 2013-09-27
 */
function check_t_inventory_in($order_sn,$rec_id,$product_id) {
    global $db;
    $sql = "select 1 from
     ecshop.ecs_order_info oi
     inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
     inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
     inner join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
     where oi.order_sn = '{$order_sn}' and pm.product_id = '{$product_id}' and og.rec_id = '{$rec_id}'
     limit 1";
    $result = $db->getOne($sql);
    if(!empty($result)) {
        QLog::log('check_t_inventory_in sql:'.$sql);
        return false;
    }

    return true;
}

/**
 * 检查发货单是否拣货完成
 * ljzhou 2013-10-08
 */
function check_shipment_over($shipment_id) {
    global $db;
    $sql = "select 1 from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' and is_pick='Y' limit 1";
    $result = $db->getOne($sql);
    if(empty($result)) {
        QLog::log('check_shipment_over sql:'.$sql);
        return false;
    }

    return true;
}

/**
 * 检测发货单号和运单号是否匹配
 * ljzhou 2013-10-08
 */
function check_shipment_id_tracking_number($shipment_id,$tracking_number) {
    global $db;
    $sql = "select 1 from romeo.shipment where shipment_id = '{$shipment_id}' and tracking_number='{$tracking_number}' limit 1";
    $result = $db->getOne($sql);
    if(empty($result)) {
        return false;
    }

    return true;
}

/**
 * 检测发货单是否已经复核
 * ljzhou 2013-10-08
 */
function check_shipment_recheck($shipment_id) {
    global $db;
    $sql = "select 1 from romeo.shipment s
           left join ecshop.ecs_order_info oi ON s.primary_order_id = oi.order_id
           where shipment_id = '{$shipment_id}' and oi.shipping_status=8
           limit 1";
    $result = $db->getOne($sql);
    if(empty($result)) {
        return false;
    }

    return true;
}

/**
 * 检测批拣单是否已经复核
 * ytchen 2015-07-08
 */
function check_bpsn_recheck($bpsn) {
    global $db;
    $sql = "select 1 from romeo.batch_pick_mapping bpm  
            inner join romeo.shipment s on s.shipment_id = bpm.shipment_id
           left join ecshop.ecs_order_info oi ON s.primary_order_id = oi.order_id
           where batch_pick_sn = '{$bpsn}' and oi.shipping_status!=8
           limit 1";
    $result = $db->getOne($sql);
    if(empty($result)) {
        return true;
    }else{
        return false;
    }
}

/**
 * 得到本批次的sku总数
 * ljzhou 2013-10-08
 */
function get_sku_nums($good_shipment_ids) {
    global $db;
    if(empty($good_shipment_ids)) {
        return 0;
    }

    $sql = "select count(distinct(pm.product_id)) as sku_num
           from
           ecshop.ecs_order_info oi
           inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
           left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
           left join romeo.order_shipment os ON CAST( os.order_id AS UNSIGNED ) = oi.order_id
           left join romeo.shipment s ON os.shipment_id = s.shipment_id
           where s.shipment_id ".db_create_in($good_shipment_ids);
//    QLog::log('get_sku_nums:'.$sql);
    $sku_num = $db->getOne($sql);
    if(empty($sku_num)) {
        return 0;
    }

    return $sku_num;
}

/**
 * 上库位特殊功能，慎用
 * ljzhou 2013-10-30
 */
function auto_grouding_location($party_id,$facility_id,$product_id,$goods_barcodes,$location_barcode) {
    global $db;

    $sql = "INSERT INTO `romeo`.`inventory_location` (location_barcode,is_serial,goods_barcode,product_id,
    goods_number,available_to_reserved,validity,party_id,facility_id,status_id,action_user,created_stamp,
    last_updated_stamp)values ('".$location_barcode."','0',$goods_barcodes,$product_id,'50000','50000','1970-01-01 00:00:00',
    $party_id,$facility_id,'INV_STTS_AVAILABLE','system',now(),now())";
    // Qlog::log('auto_grouding_location:'.$sql);
    $res = $db->query($sql);
    return $res;
}

/**
 * 得到热门商品的product_key,和排序信息 ljzhou 2013-11-5
 * $SingleMulti:array('goods_type_simple','goods_type_multy')等单品，多品分类数组
 * $product_key_size：sku种类
 * $shipment_size：返回的发货单数量
 * $filter_condition：仓库，组织，供应商等一些过滤条件
 * --------
 * Updated by Sinri Since 2015-10-23
 */
function get_hot_product_key_detail($SingleMulti,$product_key_size,$shipment_size,$filter_condition) {
    global $db;
    if($SingleMulti == array('goods_type_simple','goods_type_multy')) {
        // $product_key_category = ' having res.product_nums >= 1 ';
        $product_key_category = '';
    } else if($SingleMulti == array('goods_type_simple')) {
        // $product_key_category = ' having res.product_nums = 1 ';
        $product_key_category = ' having product_nums = 1 ';
    } else if($SingleMulti == array('goods_type_multy')) {
        // $product_key_category = ' having res.product_nums > 1 ';
        $product_key_category = ' having product_nums > 1 ';
    }

    $isUseOIMI=true;//mt_rand(0,1);
    $OIMI=($isUseOIMI?'force index(order_info_multi_index)':'');

    $result = array();
    // 热门商品筛选 或者 具体的某个组合（单品）
    // and ". facility_sql('oi.FACILITY_ID') ." and ". party_sql('oi.PARTY_ID') ."
    $sql="SELECT res.product_key,res.product_nums,count(res.shipment_id) as order_nums
        from (
          select 
            os.shipment_id,
            count(DISTINCT(pm.product_id)) as product_nums,
            group_concat(DISTINCT(pm.product_id) order by pm.product_id) as product_key
            from ecshop.ecs_order_info oi {$OIMI}
            inner join ecshop.ecs_order_goods og ON oi.order_id =og.order_id
            inner join romeo.product_mapping pm ON og.goods_id =pm.ecs_goods_id and og.style_id= pm.ecs_style_id
            inner join romeo.order_shipment os ON convert(oi.order_id using utf8)=os.order_id
            -- inner join romeo.shipment s ON os.shipment_id = s.shipment_id
            inner join romeo.order_inv_reserved r on oi.order_id = r.order_id and r.facility_id = oi.facility_id
            where
                r.STATUS = 'Y' and oi.shipping_status = 0 and oi.order_status = 1 
                and oi.order_type_id in ('SALE','SHIP_ONLY')
                $filter_condition
                -- 未批捡
                and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = os.shipment_id limit 1)
            group by os.shipment_id
            $product_key_category
        ) as res
        group by res.product_key
        -- $product_key_category
        order by order_nums desc
        limit $product_key_size
    ".' -- function::get_hot_product_key_detail '.__LINE__.PHP_EOL;
    
    $db_start_time=microtime(true);
    $product_keys = $db->getAll($sql);
    $db_end_time=microtime(true);

    Qlog::log('hot_product_key [function get_hot_product_key_detail] '.($isUseOIMI?'OIMI':'NON-OIMI').' (time= '.($db_end_time-$db_start_time).' s) sql:'.$sql);

    $hot_product_keys = array();
    $order_sum = 0;
    foreach($product_keys as $key=>$product_key) {
        if($order_sum > $shipment_size) {
            break;
        }
        $order_sum  += $product_key['order_nums'];
        $hot_product_keys[] = $product_key['product_key'];
        $sort_product_key[$product_key['product_key']] = $product_key['order_nums'];// 对product_key的优先级进行赋值
    }
    $result['hot_product_keys'] = $hot_product_keys;
    $result['sort_product_key'] = $sort_product_key;
    return $result;
}

 /**
 * 开始筛选热门商品的shipment ljzhou 2013-11-5
 * $SingleMulti:array('goods_type_simple','goods_type_multy')等单品，多品分类数组
 * $product_key_size：sku种类
 * $shipment_size：返回的发货单数量
 * $filter_condition：仓库，组织，供应商等一些过滤条件
 */
function get_hot_goods_shipment($hot_product_keys,$hot_product_num=null,$shipment_size,$filter_condition) {
    global $db;
    
    //if hot_product_keys is empty... directly return null
    // All Hail Sinri Eoogawa! 20151026
    if(empty($hot_product_keys)){
        $result=array();
        $result['list'] = array();
        $result['ref_fields'] = array();
        $result['ref_rowset'] = array();
        return $result;
    }else if(count($hot_product_keys)>1){
        // var_dump($hot_product_keys);die();
        // $hot_product_keys=array($hot_product_keys[0]);
        $having_sql="";
        $contain_sql="";
    }else{
        $having_sql="having product_key ".db_create_in($hot_product_keys)." $cont";
        
        $contain_sql.=" and not exists (SELECT 1
            FROM
                ecshop.ecs_order_goods inner_og
                    INNER JOIN
                romeo.product_mapping pm ON inner_og.goods_id = pm.ecs_goods_id
                    AND inner_og.style_id = pm.ecs_style_id
            WHERE
                inner_og.order_id = oi.order_id
                    AND pm.product_id not in ({$hot_product_keys[0]})
            )
        ";

        // $contain_product_id_array=explode(',', $hot_product_keys[0]);
        // $contain_sql="";
        // foreach ($contain_product_id_array as $product_id) {
        //     $contain_sql.=" and exists (SELECT 1
        //         FROM
        //             ecshop.ecs_order_goods inner_og
        //                 INNER JOIN
        //             romeo.product_mapping pm ON inner_og.goods_id = pm.ecs_goods_id
        //                 AND inner_og.style_id = pm.ecs_style_id
        //         WHERE
        //             inner_og.order_id = oi.order_id
        //                 AND pm.product_id = $product_id 
        //         )
        //     ";
        // }
    }

    $cont = " ";
    if(isset($hot_product_num)){
        $cont = " and SUM(og.goods_number) = $hot_product_num ";
    }
    $sql="SELECT facility_id from romeo.facility where is_out_ship = 'Y'";
    $out_facility_list = implode("','",$db->getCol($sql));

    $isUseOIMI=true;//mt_rand(0,1);
    $OIMI=($isUseOIMI?'force index(order_info_multi_index)':'');

    $sql_shipment_size=$shipment_size*10;
    
    // 开始具体的筛选
    // and ". facility_sql('oi.FACILITY_ID') ." and ". party_sql('oi.PARTY_ID') ." 
    $sql_from = "SELECT
            oi.order_time,s.SHIPMENT_ID,oi.shipping_id,oi.PARTY_ID,s.PRIMARY_ORDER_ID,
            count(distinct(pm.product_id)) as sku_num,
            group_concat(distinct(oi.order_sn) order by oi.order_sn) as order_sns,
            group_concat(distinct(pm.product_id) order by pm.product_id) as product_key,
            if(oi.handle_time = 0, oi.order_time, FROM_UNIXTIME(oi.handle_time)) handle_time
        from
            ecshop.ecs_order_info oi {$OIMI}
            inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
            inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
            inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
        where
            r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND'
            and oi.shipping_status = 0 and oi.order_status = 1 
            $filter_condition
            -- 订单处理时间已到
            and (oi.handle_time = 0 or oi.handle_time < UNIX_TIMESTAMP())
            -- 未打印
            -- and not exists (select 1 from order_mixed_status_history WHERE order_id = oi.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1)
            -- 未批捡
            and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = s.shipment_id limit 1)
            -- 所有订单都预定了
            and not exists (select 1 from romeo.order_shipment os2 left join romeo.order_inv_reserved r2 ON r2.order_id=cast(os2.order_id as UNSIGNED)
                            where os2.shipment_id=s.shipment_id and (r2.status !='Y' or r2.status is null) limit 1)
            
            and oi.FACILITY_ID not in ('{$out_facility_list}')
            AND (oi.shipping_id != 51 or (oi.shipping_id = 51 and s.tracking_number is not null and s.tracking_number != ''))
            -- strict product id check
            $contain_sql
            group by s.SHIPMENT_ID
        -- 在热门商品里面
        $having_sql
		order by oi.order_time
        limit $sql_shipment_size -- open it, any error reclose
    ".' -- function::get_hot_goods_shipment '.__LINE__.PHP_EOL;

    $db_start_time=microtime(true);

    $result=$list=$ref_fields=$ref_rowset=array();
    $list=$db->getAllRefby($sql_from,array('SHIPMENT_ID'),$ref_fields,$ref_rowset);
    $result['list'] = $list;
    $result['ref_fields'] = $ref_fields;
    $result['ref_rowset'] = $ref_rowset;

    $db_end_time=microtime(true);

    Qlog::log('get_hot_goods_shipment [function get_hot_goods_shipment] (time= '.($db_end_time-$db_start_time).' s) sql:'.$sql_from);
    return $result;
}


function  get_single_goods_shipment($one_product_key,$shipment_size,$filter_condition) {
    global $db;

    $isUseOIMI=true;//mt_rand(0,1);
    $OIMI=($isUseOIMI?'force index(order_info_multi_index)':'');
    
    $pids=array();
    $opk1=explode(',', $one_product_key);
    foreach ($opk1 as $opk1_t) {
        $opk2=explode('_', $opk1_t);
        $pids[]=$opk2[0];
    }
    $pids=implode(',', $pids);
    $contain_sql=" and not exists (SELECT 1
            FROM
                ecshop.ecs_order_goods inner_og
                    INNER JOIN
                romeo.product_mapping pm ON inner_og.goods_id = pm.ecs_goods_id
                    AND inner_og.style_id = pm.ecs_style_id
            WHERE
                inner_og.order_id = oi.order_id
                    AND pm.product_id not in ({$pids})
            )
        ";

    // 开始具体的筛选
    // and ". party_sql('oi.PARTY_ID') ."
    $sql_from = "SELECT temp.SHIPMENT_ID,temp.shipping_id,temp.PARTY_ID,temp.PRIMARY_ORDER_ID,    
            count(SHIPMENT_ID) as sku_num,temp.order_sns,temp.handle_time,
            GROUP_CONCAT(product_nums order BY product_nums desc ) as  one_key_product 
        from (
            SELECT s.SHIPMENT_ID,oi.shipping_id,oi.PARTY_ID,s.PRIMARY_ORDER_ID,
                CONCAT_WS('_',pm.product_id,SUM(og.goods_number)) as product_nums,  
                group_concat(distinct(oi.order_sn) order by oi.order_sn) as order_sns, 
                if(oi.handle_time = 0, oi.order_time, FROM_UNIXTIME(oi.handle_time)) handle_time
            from ecshop.ecs_order_info oi {$OIMI}
            INNER JOIN ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
            inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
            inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
            where
                r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND'
                and oi.shipping_status = 0 and oi.order_status = 1 
                $filter_condition
                -- 订单处理时间已到
                and (oi.handle_time = 0 or oi.handle_time < UNIX_TIMESTAMP())
                -- 未批捡
                and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = s.shipment_id limit 1)
                -- 所有订单都预定了
                and not exists (select 1 from romeo.order_shipment os2 left join romeo.order_inv_reserved r2 ON r2.order_id=cast(os2.order_id as UNSIGNED)
                                where os2.shipment_id=s.shipment_id and (r2.status !='Y' or r2.status is null) limit 1) 
                AND (oi.shipping_id != 51 or (oi.shipping_id = 51 and s.tracking_number is not null and s.tracking_number != ''))
            	{$contain_sql}
            group by
                s.SHIPMENT_ID,pm.product_id 
            order by oi.order_time
        ) as temp
        GROUP BY temp.SHIPMENT_ID
        HAVING one_key_product = '{$one_product_key}'
    ".' -- function::get_single_goods_shipment '.__LINE__.PHP_EOL;
    

    $db_start_time=microtime(true);

    $result=$list=$ref_fields=$ref_rowset=array();
    $list=$db->getAllRefby($sql_from,array('SHIPMENT_ID'),$ref_fields,$ref_rowset);
    $result['list'] = $list;
    $result['ref_fields'] = $ref_fields;
    $result['ref_rowset'] = $ref_rowset;

    $db_end_time=microtime(true);

    Qlog::log('get_single_goods_shipment [function get_single_goods_shipment] (time= '.($db_end_time-$db_start_time).' s) sql:'.$sql_from);
    return $result;
}

/**
 *
 * 根据shipment_id对一组商品：product_key进行数量排序
 * ljzhou 2013-11-05
 */
function get_sorted_product_keys($shipment_ids,$SingleMulti=null) {
    global $db;
    $sql = "SELECT res.goods_name,res.product_key,count(res.shipment_id) as sku_sum
        from
        (select
                group_concat(distinct(g.goods_name) separator '</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') as goods_name,s.shipment_id,group_concat(distinct(pm.product_id) order by pm.product_id) as product_key
            from
                ecshop.ecs_order_info oi
                inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                left  join ecshop.ecs_goods g ON og.goods_id = g.goods_id
                inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                inner join romeo.order_shipment m on CAST( m.ORDER_ID AS UNSIGNED ) = oi.ORDER_ID
                inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
            where s.shipment_id ".db_create_in($shipment_ids).
        " group by s.shipment_id
        ) as res
        group by res.product_key
        order by sku_sum desc";
//   Qlog::log('get_sorted_product_keys sql:'.$sql);
    $result = $db->getAll($sql);
    /**
     * ytchen 单品+购买数量再次细化显示
     */
    $result2 = $result;
    if($SingleMulti == array('goods_type_simple')) {
        foreach($result2 as $key=>$goods){
            $goods_name = $goods['goods_name'];
            $product_id = $goods['product_key'];
            $order_count = $goods['sku_sum'];
            $sql = "SELECT product_nums,count(shipment_id) as order_num,goods_number,group_concat(shipment_id) as shipment_ids
                    FROM (SELECT concat_ws('_',pm.product_id,sum(og.goods_number)) as product_nums,s.shipment_id,sum(og.goods_number) as goods_number
                        from ecshop.ecs_order_goods og
                        inner join ecshop.ecs_order_info oi ON oi.order_id = og.order_id
                        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                        inner join romeo.order_shipment m on CAST( m.ORDER_ID AS UNSIGNED ) = oi.ORDER_ID
                        INNER JOIN romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
                        where s.shipment_id ".db_create_in($shipment_ids)." and pm.product_id = '$product_id'  
                        group by s.shipment_id ) as order_shipment 
                    GROUP BY product_nums ";
//          QLog::log("get_goods_simple_detail_count sql:".$sql);
            $detail_count = $db->getAll($sql);
            $item_list = array();
            foreach($detail_count as $detail){
                $item_list[] = array("product_nums"=>$detail['product_nums'],"order_num"=>$detail['order_num'],"goods_number"=>$detail['goods_number'],"shipment_ids"=>$detail['shipment_ids']);
            }
            $result2[$key]['item_list']= $item_list;
        }
    }else if($SingleMulti == array('goods_type_multy')){
        foreach($result2 as $key=>$goods){
            $product_ids = str_replace(",","','",$goods['product_key']);
            $count = substr_count($goods['product_key'], ',');
            $sql = "SELECT count(temp.product_ids) as order_num,product_ids,group_concat(temp.SHIPMENT_ID) as shipment_ids from (
                SELECT GROUP_CONCAT(product_nums ORDER BY product_nums desc Separator '||') product_ids,t.shipment_id 
                FROM (select CONCAT_WS('_',pm.PRODUCT_ID,sum(og.goods_number)) as product_nums,s.shipment_id
                                        from ecshop.ecs_order_goods og
                                        inner join ecshop.ecs_order_info oi ON oi.order_id = og.order_id
                                        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
                                        inner join romeo.order_shipment m on CAST( m.ORDER_ID AS UNSIGNED ) = oi.ORDER_ID
                                        INNER JOIN romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
                                        where s.shipment_id ".db_create_in($shipment_ids)." and pm.product_id in ('{$product_ids}')
                GROUP BY s.shipment_id,pm.PRODUCT_ID
                ORDER BY product_nums desc,s.shipment_id desc
                )as t
                group by t.shipment_id  
                HAVING LENGTH(product_ids)-LENGTH(replace(product_ids,'||',','))={$count}
                 ) as temp 
                group by temp.product_ids 
                order by order_num desc  
                limit 5 ";
//          QLog::log("get_goods_type_multy_count sql:".$sql);
            $detail_count = $db->getAll($sql);
            $item_list = array();
            foreach($detail_count as $detail){
                $item_list[] = array("product_nums"=>$detail['product_ids'],"order_num"=>$detail['order_num'],"goods_number"=>'',"shipment_ids"=>$detail['shipment_ids']);
            }
            $result2[$key]['item_list']= $item_list;
        }
    }
    
    return $result2;
}


/**
 * 筛选详细的shipment信息
 * ljzhou 2013-11-5
 * ----------------
 * Updated by Sinri, 2015-10-23
 * Make it easy to get confirm time
 */

function get_shipment_details($shipment_ids) {
    global $db;
    $sql="SELECT
            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.facility_id,o.shipping_id,o.shipping_name,
            o.order_time,o.order_sn,o.consignee,o.distributor_id,
            o.order_time  AS confirm_time,
            r.STATUS as RESERVED_STATUS, 
            r.RESERVED_TIME AS reserved_time, 
            m.SHIPMENT_ID
        from
            romeo.order_shipment m
            left join romeo.order_inv_reserved r on r.ORDER_ID = cast(m.ORDER_ID as unsigned)
            left join ecshop.ecs_order_info as o on o.order_id = cast(m.ORDER_ID as unsigned)
        where
            m.SHIPMENT_ID ".db_create_in($shipment_ids);

    // Qlog::log('start get_shipment_details:sql:'.$sql);
    $result=$shipments=$ref_fields1=$ref_rowset1=array();
    $shipments=$db->getAllRefby($sql,array('SHIPMENT_ID'),$ref_fields1,$ref_rowset1);
    $result['result'] = $shipments;
    $result['ref_fields1'] = $ref_fields1;
    $result['ref_rowset1'] = $ref_rowset1;

    return $result;
}

/**
 * 得到批捡单的sku数
 * ljzhou 2013-11-5
 */
 function get_bpsn_sku_num($bpsns) {
    global $db;
    $sql = "
            select
                bp.batch_pick_sn,ifnull(count(distinct(pm.product_id)),0) as sku_num
            from
                romeo.batch_pick bp
                left join romeo.batch_pick_mapping m ON bp.batch_pick_sn = m.batch_pick_sn
                left join romeo.order_shipment os ON m.shipment_id = os.shipment_id
                left join ecshop.ecs_order_info oi ON CAST( os.order_id AS UNSIGNED ) = oi.order_id
                left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
                left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
            where bp.batch_pick_sn ".db_create_in($bpsns).
        " group by bp.batch_pick_sn
    ";
    // Qlog::log('get_bpsn_sku_num:'.$sql);
    $bpsn_sku_nums = $db->getAll($sql);
    $result = array();
    if(empty($bpsn_sku_nums)) {
        return null;
    }
    foreach($bpsn_sku_nums as $bpsn_sku_num) {
        $result[$bpsn_sku_num['batch_pick_sn']] = $bpsn_sku_num['sku_num'];
    }
    return $result;

 }


 /**
 * 得到-gt老库存的出库数 ljzhou 2013-11-18
 */
 function get_gt_new_deliver_number($supplier_return_ids) {
    global $db;
    if(empty($supplier_return_ids)) {
        return null;
    }
    $sql = "select s.supplier_return_id,ifnull(sum(-iid.quantity_on_hand_diff),0) as new_out_num
            from
            romeo.supplier_return_request s
            left join romeo.supplier_return_request_gt gt ON s.supplier_return_id = gt.supplier_return_id
            left join ecshop.ecs_order_info oi ON gt.SUPPLIER_RETURN_GT_SN = oi.order_sn
            left join ecshop.ecs_order_goods og ON oi.order_id=og.order_id
            left join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8)=iid.order_id
            where s.supplier_return_id ".db_create_in($supplier_return_ids)."
            group by s.supplier_return_id";
    $new_nums = $db->getAll($sql);
    if(empty($new_nums)) {
        return null;
    }
    $new_out_nums = array();
    foreach($new_nums as $new_num) {
        $new_out_nums[$new_num['supplier_return_id']] = $new_num['new_out_num'];
    }
    return $new_out_nums;
 }

 /**
  * 检查发货单中是否有未预定成功的订单 或 订单已取消
  * ljzhou 2013-11-19
  */
  function check_merge_order_no_reserved($shipment_ids_arr) {
    global $db;
    if(empty($shipment_ids_arr)) {
        return true;
    }
    $sql = "select 1 from romeo.order_shipment os
        left join ecshop.ecs_order_info oi ON CAST( os.order_id AS UNSIGNED ) = oi.order_id
        left join romeo.order_inv_reserved r ON oi.order_id = r.order_id 
        where (r.status != 'Y' or oi.order_status!=1 or (oi.facility_id!=r.facility_id)) and os.shipment_id ".db_create_in($shipment_ids_arr).
        "limit 1";
    // Qlog::log('check_merge_order_no_reserved:'.$sql);
    $result = $db->getOne($sql);
    if(!empty($result)) {
        return false;
    }

    return true;
  }

  /**
  * 已经批捡的就无法再合并订单
  * ljzhou 2013-11-19
  */

  function check_order_is_batch_pick($order_ids_arr) {
    global $db;
    if(empty($order_ids_arr)) {
        return null;
    }
    $sql = "select oi.order_sn from romeo.order_shipment os
        inner join ecshop.ecs_order_info oi ON CAST( os.order_id AS UNSIGNED ) = oi.order_id
        inner join romeo.batch_pick_mapping bp ON os.shipment_id = bp.shipment_id
        where os.order_id ".db_create_in($order_ids_arr);
    // Qlog::log('check_order_is_batch_pick:'.$sql);
    $result = $db->getCol($sql);
    if(!empty($result)) {
        $order_ids = implode(',',$result);
        return $order_ids;
    }

    return null;
  }

    /**
     * 检测上架容器合法性，包括订单的组织和容器的组织一致
     */
    function check_grouding_location_barcode_party($batch_order_sn,$location_barcode,$location_type) {
        global $db;
//        Qlog::log('检测上架容器合法性  '.$location_barcode);
        $sql = "SELECT party_id from romeo.location WHERE  location_barcode='{$location_barcode}'  AND  location_type = '{$location_type}' limit 1";
        // Qlog::log('check_grouding_location_barcode_party='.$sql);
        $party_id = $db->getOne($sql);
        $result = array();
        if(!empty($party_id)){
            $sql = "SELECT p.name as from_party_name,
            ifnull((select name from romeo.party where party_id = '{$party_id}' limit 1),'') as to_party_name
            from ecshop.ecs_batch_order_info oi 
            left join romeo.party p ON convert(oi.party_id using utf8) = p.party_id
            WHERE  oi.batch_order_sn='{$batch_order_sn}' AND oi.party_id !={$party_id} limit 1";
            // Qlog::log('check_grouding_location_barcode_party check_party='.$sql);
            $party_names = $db->getRow($sql);
            if(!empty($party_names)) {
                $result['error'] = "容器和订单的组织不一致！\n"." 上架容器:".$location_barcode."组织为：".$party_names['to_party_name']."\n采购批次号的组织为：".$party_names['from_party_name']."\n请重新选择该组织的上架容器";
                $result['success'] = false;
            } else {
                $result['success'] = true;
            }
        }else{
            $result['success'] = false;
            $result['error'] = "容器:".$location_barcode."不存在,请先维护容器表！";
        }
        return $result;
    }

/**
 * 检查订单里面有没有该串号 
 * ljzhou 2014-01-02
 */
 function get_goods_style($order_id_array,$serial_number) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.goods_id, og.style_id
        FROM
            {$ecs->table('order_goods')} AS og
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
        WHERE
            ii.status_id = 'INV_STTS_AVAILABLE' AND ii.serial_number = '{$serial_number}'
            AND og.order_id " . db_create_in($order_id_array)." limit 1"
    ;
    // Qlog::log('get_goods_style  sql:'.$sql);
    $goods_style = $db->getRow($sql);
    if(empty($goods_style)) {
        return null;
    }
    return $goods_style;
 }
 
/**
 * 查询某个订单商品的未出库的唯一标识id数组，规则为order_goods_id 加上1,2,3...类似于之前的erp_id，唯一标识一个商品库存
 * ljzhou 2014-01-02
 */
 function get_one_item_unique_ids($order_id_array,$goods_id,$style_id) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.rec_id,og.goods_number
        FROM
            {$ecs->table('order_goods')} AS og
        WHERE
            og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}' AND 
            og.order_id ". db_create_in($order_id_array)." group by og.rec_id";
    // Qlog::log('get_item_unique_ids  sql:'.$sql);
    $rec_nums = $db->getAll($sql);
    $item_unique_ids = format_item_unique_ids($rec_nums);
    return $item_unique_ids;
 }
 
 /**
 * 查询某个订单商品的未出库的唯一标识id数组，规则为order_goods_id 加上1,2,3...类似于之前的erp_id，唯一标识一个商品库存
 * ljzhou 2014-01-02
 */
 function get_item_unique_ids($order_id_array) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.rec_id,og.goods_number
        FROM
            {$ecs->table('order_goods')} AS og
        WHERE
            og.order_id ". db_create_in($order_id_array)." group by og.rec_id";
    // Qlog::log('get_item_unique_ids  sql:'.$sql);
    $rec_nums = $db->getAll($sql);
    $item_unique_ids = format_item_unique_ids($rec_nums);
    return $item_unique_ids;
 }
 
/**
 * 输入：rec_id,goods_number,new_out_number
 * 输出：格式化数组，规则为rec_id 加上1,2,3...类似于之前的erp_id，唯一标识一个商品库存
 * ljzhou 2014-01-02
 */
 function format_item_unique_ids($rec_nums) {
    if(empty($rec_nums)) {
        Qlog::log('format_item_unique_ids $rec_nums is null');
        return null;
    }
    
    $item_unique_ids = array();
    foreach($rec_nums as $rec_num) {
        Qlog::log('format_item_unique_ids rec_id:'.$rec_num['rec_id'].' goods_number:'.$rec_num['goods_number']);
        for($i=1;$i<=$rec_num['goods_number'];$i++) {
            $item_unique_ids['all_rec'][$rec_num['rec_id']][] = $rec_num['rec_id'].'-'.$i;
            $item_unique_ids['all_item_id'][] = $rec_num['rec_id'].'-'.$i;
        }
    }
    if(empty($item_unique_ids)) {
        return null;
    }

    return $item_unique_ids;
 }
 
 /**
 * 判断一个rec_id的商品是否已经全部出库
 */
 function check_rec_all_out($rec_id,$now_out_num) {
    global $db,$ecs;
    $sql = "
        SELECT 
            og.goods_number,ifnull(-sum(if(iid.quantity_on_hand_diff <0,iid.quantity_on_hand_diff,0)),0) as out_num
        FROM
            {$ecs->table('order_goods')} AS og
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id AND ii.status_id = 'INV_STTS_AVAILABLE'
        WHERE
             og.rec_id = '{$rec_id}' group by og.rec_id";
 
    // Qlog::log('check_rec_all_out  sql:'.$sql);
    $result = $db->getRow($sql);
    $goods_number = $result['goods_number'];
    $out_num = $result['out_num'];
    Qlog::log('check_rec_all_out rec_id:'.$rec_id.' $goods_number:'.$goods_number.' $out_num:'.$out_num.' $now_out_num:'.$now_out_num);
    if(($goods_number - $out_num) <= $now_out_num) {
        return true;
    }
    
    return false;
 }
 
 /**
  * 更新订单商品开票信息 ljzhou 2014-1-9
  */
  function update_order_shipping_invoice($order_id,$shipping_invoice='BKP') {
    global $db;
    if(empty($order_id) || empty($shipping_invoice)) {
        return false;
    }
    
    // 如果没有则创建
    $sql = "select 1 from romeo.order_shipping_invoice si WHERE si.order_id = '{$order_id}' limit 1";
    
    $has_exist = $db->getOne($sql);
    if(empty($has_exist)) {
        $sql = "insert into romeo.order_shipping_invoice (order_id, created_stamp) values ('{$order_id}', now())";
        $db->query($sql);
    }
    
    $sql = "UPDATE romeo.order_shipping_invoice si SET si.shipping_invoice = '{$shipping_invoice}', last_updated_stamp = now() WHERE si.order_id = '{$order_id}' limit 1";
    $db->query($sql);
    return true;
  }
  
  /**
   * 根据shipment_id得到product_id列表
   */
function get_product_ids($shipment_id) {
    global $db,$ecs;
    $sql = "
        SELECT 
            distinct(pm.product_id)
        FROM
            romeo.order_shipment os
            INNER JOIN ecshop.ecs_order_goods og ON CAST( os.order_id AS UNSIGNED ) = og.order_id
            INNER JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        WHERE
             os.shipment_id = '{$shipment_id}'";
 
    // Qlog::log('get_product_ids  sql:'.$sql);
    $product_ids = $db->getCol($sql);
    if(empty($product_ids)) {
        return null;
    }
    return $product_ids;
}

  /**
   * 根据order_sn得到product_id列表
   */
function get_product_ids_by_order_sn($order_sn) {
    global $db,$ecs;
    $sql = "
        SELECT 
            distinct(pm.product_id)
        FROM
            ecshop.ecs_order_info oi
            INNER JOIN ecshop.ecs_order_goods og ON oi.order_id = og.order_id
            INNER JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        WHERE
             oi.order_sn = '{$order_sn}'";
 
    // Qlog::log('get_product_ids  sql:'.$sql);
    $product_ids = $db->getCol($sql);
    if(empty($product_ids)) {
        return null;
    }
    return $product_ids;
}

/**
 * 得到已经出库的串号
 */
function get_out_serial_numbers($rec_id) {
    global $db,$ecs;
    $sql = "
        SELECT 
            ii.serial_number
        FROM
            ecshop.ecs_order_goods og 
            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)=iid.order_goods_id
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
        WHERE
             ii.status_id ='INV_STTS_AVAILABLE' and iid.quantity_on_hand_diff <0 and ii.inventory_item_type_id='SERIALIZED' 
             and og.rec_id = '{$rec_id}'";
 
    // Qlog::log('get_out_serial_numbers  sql:'.$sql);
    $serial_numbers = $db->getCol($sql);
    if(empty($serial_numbers)) {
        return null;
    }
    return $serial_numbers;
}

/**
 * 得到订单对应-T的订单号
 *
 */
 function get_order_t_sn($order_id) {
    global $db;
    $sql = "select oi2.order_sn from ecshop.ecs_order_info oi 
    left join ecshop.order_relation ol ON oi.order_id=ol.root_order_id
    left join ecshop.ecs_order_info oi2 ON ol.order_id=oi2.order_id
    where oi2.order_type_id in('RMA_RETURN') and oi.order_id='{$order_id}' limit 1";
    // Qlog::log('get_order_t_sn:'.$sql);
    $t_sn = $db->getOne($sql);
    if(empty($t_sn)) {
        return null;
    }
    return $t_sn;
 }
 
 /**
  * 格式化订单,按照商品级别统计 ljzhou 2014-2-19
  */
 function get_format_item_info_list($order_list) {
    global $db;
    if(empty($order_list)) {
        return null;
    }
    
    $item_info_list = array();
    // 格式化订单,按照商品级别统计
    foreach($order_list as $order_key=>$order_item)
    {
        // 根据order_id得到每个rec_id对应已出库的数量
        $rec_out_numbers = get_rec_out_numbers($order_item['order_id']);
        foreach($order_item['order_goods'] as $goods_key=>$order_goods)
        {
            $product_id = getProductId($order_goods['goods_id'], $order_goods['style_id']);
            $key=$order_goods['rec_id'];
            if(empty($rec_out_numbers[$key]) && $rec_out_numbers[$key]!=0) {
                Qlog::log('format rec_id:'.$key.' goods_number is null');
                $rec_out_numbers[$key] = 0;
            }
            
            $sql = "
                select 1 from ecshop.ecs_goods g, ecshop.ecs_category c
                where g.cat_id = c.cat_id and c.cat_name = '虚拟商品' and g.goods_id = '{$order_goods['goods_id']}'
                limit 1
            ";
            $item_info_list[$product_id]['is_productcode'] = false;
            if($db->getOne($sql)){
                $item_info_list[$product_id]['is_productcode'] = true;
            }
            $item_info_list[$product_id]['order_info_md5']=$order_item['order_info_md5'];
            $item_info_list[$product_id]['order_id']=$order_item['order_id'];
            $item_info_list[$product_id]['order_sn']=$order_item['order_sn'];
            $item_info_list[$product_id]['product_id']=$product_id;
            $item_info_list[$product_id]['goods_name']=$order_goods['goods_name'];
            $item_info_list[$product_id][$order_goods['rec_id']]['goods_number'] = $order_goods['goods_number'];
            $item_info_list[$product_id]['goods_number_total'] += $order_goods['goods_number'];
            $item_info_list[$product_id][$order_goods['rec_id']]['has_out_number'] = $rec_out_numbers[$key];
            $item_info_list[$product_id]['has_out_number_total'] += $rec_out_numbers[$key];
            $item_info_list[$product_id]['rec_id']=$order_goods['rec_id'];
            // 直接展示条码
            $item_info_list[$product_id]['productcode']= $order_goods['barcode'];//encode_goods_id($order_goods['goods_id'], $order_goods['style_id']);
            $is_serial=getInventoryItemType($order_goods['goods_id']);
            // 如果串号控制的，则得到出库的串号
            if($is_serial == 'SERIALIZED') {
                $serial_numbers = get_out_serial_numbers($order_goods['rec_id']);
                if(!empty($serial_numbers)) {
                    foreach($serial_numbers as $serial_number) {
                        $item_info_list[$product_id]['serial_numbers'][] = $serial_number;
                    }
                }
                $null_count = $order_goods['goods_number']-count($serial_numbers);
                if($null_count > 0) {
                    for($i=0;$i<$null_count;$i++) {
                        $item_info_list[$product_id]['serial_numbers'][] = '';
                    }
                }
                
            }
            $item_info_list[$product_id]['goods_type']= $is_serial;
            $item_info_list[$product_id]['status_id']=$order_goods['status_id'] == 'INV_STTS_AVAILABLE' ? '全新' : ( $order_goods['status_id'] == 'INV_STTS_USED' ? '二手' : $order_goods['status_id']) ;
        }
    }
    
    foreach($item_info_list as $key=>$item_info) {
        $item_info_list[$key]['left_out_number'] = $item_info['goods_number_total']-$item_info['has_out_number_total'];
    }
    
    return $item_info_list;
 }
 
 /**
  * 得到订单product_id对应的未出库的数量 ljzhou 2014-3-18
  */
 function get_order_no_out_goods_numbers($order_id) {
    global $db;
    $sql = "select pm.product_id,( og.goods_number + ifnull(sum(iid.quantity_on_hand_diff),0) )as no_out_number
        from ecshop.ecs_order_goods og
        left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
        left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        where og.order_id = '{$order_id}'
        group by og.rec_id";
//  QLog::log('get_real_out_goods_numbers:'.$sql);
    $product_no_out_numbers = $db->getAll($sql);
    if(empty($product_no_out_numbers)) {
        return null;
    }

    $order_no_out_goods_numbers = array();
    foreach($product_no_out_numbers as $product_no_out_number) {
        $order_no_out_goods_numbers[$product_no_out_number['product_id']] = $product_no_out_number['no_out_number'];
    }

    return $order_no_out_goods_numbers;
 }
 
  /**
  * 得到批次订单的条码对应的未入库的订单、数量映射数组
  */
 function get_order_no_in_goods_numbers($batch_order_sn,$goods_barcode) {
    global $db;
        
    $sql = "SELECT og.order_id,(og.goods_number - IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0)) as not_in_number, IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as in_number,og.goods_number from
           ecshop.ecs_batch_order_info bo
            LEFT JOIN ecshop.ecs_batch_order_mapping om ON bo.batch_order_id = om.batch_order_id
            LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = om.order_id
            LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
            LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
            LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
            LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
        WHERE bo.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}' 
        and om.is_cancelled ='N' and om.is_over_c ='N' and om.is_in_storage ='N'
        group by og.order_id
        having not_in_number > 0
     ";
    $order_no_in_goods_numbers = $db->getAll($sql);
    if(empty($order_no_in_goods_numbers)) {
        return null;
    }

    return $order_no_in_goods_numbers;
 }
 
 /**
  * 得到批次订单的条码对应的未入库的订单、数量映射数组
  * 
  * update 2016.01.21
  */
 function get_order_no_in_goods_numbers_v2($batch_order_sn,$goods_barcode) {
 	global $db;
	 	
 	$sql = "SELECT og.order_id,og.rec_id,(og.goods_number - IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0)) as not_in_number, IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as in_number,og.goods_number from
		   ecshop.ecs_batch_order_info bo
			LEFT JOIN ecshop.ecs_batch_order_mapping om ON bo.batch_order_id = om.batch_order_id
	        LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = om.order_id
	        LEFT JOIN ecshop.ecs_order_goods  og ON  og.order_id= oi.order_id
		    LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
		    LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND og.style_id = gs.style_id
		    LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
		WHERE bo.batch_order_sn = '{$batch_order_sn}' and if(og.style_id = 0,g.barcode,gs.barcode) = '{$goods_barcode}' 
		and om.is_cancelled ='N' and om.is_over_c ='N' and om.is_in_storage ='N'
		group by og.rec_id 
		having not_in_number > 0
		order by not_in_number desc 
 	 ";
	$order_no_in_goods_numbers = $db->getRow($sql);
	if(empty($order_no_in_goods_numbers)) {
		return null;
	}

	return $order_no_in_goods_numbers;
 }
 
  /**
  * 得到某商品对应数量的串号 ljzhou 2014-3-18
  */
 function get_no_out_serial_numbers($product_id,$facility_id,$number) {
    global $db;
    $sql = "select serial_number
        from romeo.inventory_item ii
        where ii.product_id = '{$product_id}' and ii.facility_id = '{$facility_id}' 
        and ii.status_id = 'INV_STTS_AVAILABLE' 
        and ii.quantity_on_hand_total >0
        and ii.inventory_item_type_id = 'SERIALIZED'
        limit $number";
//  QLog::log('get_no_out_serial_numbers:'.$sql);
    $serial_numbers = $db->getCol($sql);
    $result = array();
    if(empty($serial_numbers) || count($serial_numbers) < $number) {
        $result['error'] = "get_serial_numbers数量不够，需要".$number."个，实际上只有".count($serial_numbers)." 个";
        return $result;
    }
    $result['serial_numbers'] = $serial_numbers;
    return $result;
 }
 
 /**
  * 得到未入库的单价，供应商，数量列表:退货，借还机
  * 
  */
 function get_not_in_unit_cost_provider_list($out_order_goods_ids,$in_order_goods_ids) {
    global $db;
    if(empty($out_order_goods_ids) || empty($in_order_goods_ids)) {
        return null;
    }
    
    $sql = "select ii.unit_cost,ii.provider_id,sum(-iid.quantity_on_hand_diff) as out_number,
    concat_ws('-',ii.unit_cost,ii.provider_id) as unique_key
    from romeo.inventory_item ii
    inner join romeo.inventory_item_detail iid ON ii.inventory_item_id = iid.inventory_item_id
    where iid.quantity_on_hand_diff < 0 and iid.order_goods_id ".db_create_in($out_order_goods_ids)."
    group by unique_key";
    $out_unit_cost_providers_key = $out_unit_cost_providers_values = array();
    $out_unit_cost_providers = $db->getAllRefBy ( $sql, array ('unique_key' ), $out_unit_cost_providers_key, $out_unit_cost_providers_values );
    if(empty($out_unit_cost_providers_values)) {
        return null;
    }
    
    $sql = "select ii.unit_cost,ii.provider_id,sum(iid.quantity_on_hand_diff) as in_number,
    concat_ws('-',ii.unit_cost,ii.provider_id) as unique_key
    from romeo.inventory_item ii
    inner join romeo.inventory_item_detail iid ON ii.inventory_item_id = iid.inventory_item_id
    where iid.quantity_on_hand_diff > 0 and iid.order_goods_id ".db_create_in($in_order_goods_ids)."
    group by unique_key";
    $in_unit_cost_providers_key = $in_unit_cost_providers_values = array();
    $in_unit_cost_providers = $db->getAllRefBy ( $sql, array ('unique_key' ), $in_unit_cost_providers_key, $in_unit_cost_providers_values );
    
    $out_unit_cost_providers_values = $out_unit_cost_providers_values['unique_key'];
    $in_unit_cost_providers_values = $in_unit_cost_providers_values['unique_key'];
    
    $not_in_unit_cost_providers = array();
    foreach($out_unit_cost_providers_values as $key=>$out_unit_cost_providers_value) {
        $out_goods_number = $out_unit_cost_providers_values[$key][0]['out_number'];
        $in_goods_number = $in_unit_cost_providers_values[$key][0]['in_number'];
        if(empty($in_goods_number)) {
            $in_goods_number = 0;
        }
        if($out_goods_number > $in_goods_number) {
            $not_in_unit_cost_providers[$key]['unit_cost'] = $out_unit_cost_providers_value[0]['unit_cost'];
            $not_in_unit_cost_providers[$key]['provider_id'] = $out_unit_cost_providers_value[0]['provider_id'];
            $not_in_unit_cost_providers[$key]['goods_number'] = $out_goods_number - $in_goods_number;
        }
    }

    return $not_in_unit_cost_providers;
 }
 
 /**
  * 判断shipment对应的订单是否预定成功
  */
  function  check_shipment_all_reserved($shipment_id) {
    global $db;
    $sql = "SELECT os.order_id
            FROM
                romeo.order_shipment os 
            LEFT JOIN romeo.order_inv_reserved oir ON oir.order_id = CAST( os.order_id AS UNSIGNED ) 
            WHERE
                os.shipment_id = '{$shipment_id}' and (oir.status = 'N' or oir.status is null) limit 1";
    $no_reserve_order = $db->getOne($sql);
    return $no_reserve_order;
  }
 
 /**
  * 得到某商品的库存数
  */
  function get_product_stock_quantity($goods_id,$style_id,$serial_number,$facility_id,$status_id,$order_type) {
    global $db;
    if(!empty($serial_number)) {
        $cond = " and ii.serial_number='{$serial_number}' ";
    }
    $sql = "select ifnull(sum(ii.quantity_on_hand_total),0) as stock_quantity
    from romeo.product_mapping pm 
    inner join romeo.inventory_item ii ON pm.product_id = ii.product_id
    where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}' $cond
    and ii.facility_id = '{$facility_id}' and ii.status_id = '{$status_id}' and ii.INVENTORY_ITEM_ACCT_TYPE_ID = '{$order_type}'
    group by ii.product_id,ii.facility_id,ii.status_id,ii.INVENTORY_ITEM_ACCT_TYPE_ID";
    // Qlog::log('get_product_stock_quantity:'.$sql);
    $stock_quantity = $db->getOne($sql);
    return $stock_quantity;
  }
  
  /**
   *  检查可预订量，订单是否有某个商品可预订量不够
   */
   function get_order_goods_atp_info($order_id) {
        global $db;
        $sql = "SELECT eog.goods_name,
                ifnull(s.available_to_reserved - ifnull(gir.reserve_number,0),0) as atp,
                ifnull(sum(eog.goods_number),0) as need_number
                from ecshop.ecs_order_goods eog
                inner join ecshop.ecs_order_info oi ON eog.order_id = oi.order_id
                left JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
                left JOIN romeo.inventory_summary s ON pm.product_id = s.product_id and s.facility_id = oi.facility_id
                and s.STATUS_ID = 'INV_STTS_AVAILABLE' 
                left join romeo.order_inv_reserved_detail ird on ird.order_id = oi.order_id and ird.product_id = s.product_id and ird.status != 'Y'
                LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = eog.goods_id and gir.style_id = eog.style_id and gir.facility_id = oi.facility_id and gir.`status` = 'OK' 
                where eog.order_id = '{$order_id}' 
                group by pm.product_id
                having atp < need_number
                limit 1";
        $check_atp_info = $db->getRow($sql);
        if(empty($check_atp_info)) {
            return null;
        }
        return $check_atp_info;
   }
   
   function get_order_goods_atp_info_for_reserve_error($order_id) {
        global $db;
        $sql = "SELECT eog.goods_name,
                ifnull(s.available_to_reserved - ifnull(gir.reserve_number,0),0) as atp,
                ifnull(sum(eog.goods_number),0) as need_number
                from ecshop.ecs_order_goods eog
                inner join ecshop.ecs_order_info oi ON eog.order_id = oi.order_id
                left JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
                left JOIN romeo.inventory_summary s ON pm.product_id = s.product_id and s.facility_id = oi.facility_id
                and s.STATUS_ID = 'INV_STTS_AVAILABLE' 
                inner join romeo.order_inv_reserved_detail ird on ird.order_id = oi.order_id and ird.product_id = s.product_id and ird.status != 'Y'
                LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = eog.goods_id and gir.style_id = eog.style_id and gir.facility_id = oi.facility_id and gir.`status` = 'OK' 
                where eog.order_id = '{$order_id}' 
                group by pm.product_id
                having atp < need_number
                limit 1";
        $check_atp_info = $db->getRow($sql);
        if(empty($check_atp_info)) {
            return null;
        }
        return $check_atp_info;
   }
   
   /**
    * 查看订单预定不成功原因
    * ljzhou 2014-6-21
    */
   function get_order_reserve_info($order_id) {
        global $db;
        $result  = array();
        if(empty($order_id)) {
            $result['success'] = false;
            $result['reserve_error'] .= '没订单号';
            return $result;
        }
        $sql = "select oi.order_id,oi.order_status,oi.pay_status,oi.shipping_status,oi.order_type_id,oi.facility_id,
                p.pay_code,p.is_cod,r.status,r.reserved_time
            from ecshop.ecs_order_info oi 
            left join ecshop.ecs_payment p ON oi.pay_id = p.pay_id 
            left join romeo.order_inv_reserved r ON oi.order_id = r.order_id
            where oi.order_id = {$order_id} group by oi.order_id";
        $order_info  = $db->getRow($sql);
//          pp($order_info);
        do {
            if(!in_array($order_info['status'],array('Y','F'))) {
                $result['reserve_error'] = '没预定成功原因：';
                if(!empty($order_info['reserved_time'])){
                    $result['reserve_error'] .= '('.$order_info['reserved_time'].')';
                }
                // 判断订单状态
                if($order_info['order_status'] != 1) {
                    $result['success'] = false;
                    $result['reserve_error'] .= '订单不是确认状态';
                    break;
                }
                // 付款状态
                if(!( $order_info['pay_status'] == 2 || ($order_info['pay_code'] == 'cod' and $order_info['is_cod'] ==1) )) {
                    $result['success'] = false;
                    $result['reserve_error'] .= '订单不是付款状态';
                    break;
                }
                // 发货状态
                if($order_info['shipping_status'] != 0) {
                    $result['success'] = false;
                    $result['reserve_error'] .= '订单不是待配货状态';
                    break;
                }
                
                if(!in_array($order_info['order_type_id'],array('SALE','RMA_EXCHANGE', 'SHIP_ONLY'))) {
                    $result['success'] = false;
                    $result['reserve_error'] .= '订单不是销售订单';
                    break;
                }
                
                // atp不等查看
                $product_atp = check_order_product_atp_diff($order_info);
                
                if(!$product_atp['success']) {
                    $result['success'] = false;
                    $result['reserve_error'] .=  $product_atp['reserve_error'];
                    break;
                } 
                
                // 可预订量不够判断
                $check_atp_info = get_order_goods_atp_info_for_reserve_error($order_id);
                if(!empty($check_atp_info)) {
                    $result['success'] = false;
                    $result['reserve_error'] .=  $check_atp_info['goods_name'].' 需要：'.$check_atp_info['need_number'].' 个，实际可预订量：'.$check_atp_info['atp'];
                    break;
                }
                
                $result['success'] = false;
                $result['reserve_error'] .= '确认订单，已付款，待配货，可预订量足够等条件满足后，要过15分钟左右才能预定，请看是否时间还没到！'; 
            }
        } while(false);
        
        if(empty($result['reserve_error'])) {
            $result['success'] = true;
        }
       
        return $result;
   }
   
  /**
   *  检查订单商品是否系统atp不准
   */
   function check_order_product_atp_diff($order_info) {
        $result['success'] = true;
        return $result;
        
        global $db;
        $result = array();
        $sql = "select pm.product_id from ecshop.ecs_order_goods og 
        left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        where og.order_id = '{$order_info['order_id']}' group by pm.product_id";
        //Qlog::log('check_order_product_atp_diff:'.$sql);
        
        $product_ids = $db->getCol($sql);
        
        $sql = "select im.product_id,im.facility_id,  im.inventory_summary_id,  im.stock_quantity,im.available_to_reserved,
                     ifnull((select sum(ii.quantity_on_hand_total) from romeo.inventory_item ii where ii.status_id = im.status_id
                     and ii.product_id = im.product_id and ii.facility_id = im.facility_id and ii.quantity_on_hand_total > 0),0) as item_total,
                     ifnull((select sum(if(ird.status = 'Y',ird.reserved_quantity,0)) 
                     from romeo.order_inv_reserved_detail ird 
                     left join ecshop.ecs_order_info oi ON ird.order_id = oi.order_id
                     where 
                     -- 排除发货完但是还没还原预定的订单
                     oi.shipping_status not in(1,2,3,8,9,11,12) and ird.status_id = im.status_id
                     and ird.product_id = im.product_id and ird.facility_id = im.facility_id ),0) as reserved
                     from romeo.inventory_summary im
                     where im.status_id = 'INV_STTS_AVAILABLE' 
                     and im.facility_id = '{$order_info['facility_id']}' and im.product_id ".db_create_in($product_ids)."
                     group by im.product_id, im.facility_id 
                having (available_to_reserved+reserved) <> item_total limit 1";
        //Qlog::log('check_order_product_atp_diff2:'.$sql);
        $atp_diff = $db->getRow ($sql);
        if(!empty($atp_diff)) {
            $result['success'] = false;
            $result['reserve_error'] = '订单atp不准，请联系erp！ product_id:'.$atp_diff['product_id'].' facility_id:'.$atp_diff['facility_id'];
            return $result;
        }
                
        $result['success'] = true;
        return $result;
   }
   
  /**
   *  检查订单是否部分出库
   */
   function check_order_part_delivery($order_id) {
        global $db;
        $sql = "SELECT 1
                from 
                ecshop.ecs_order_info oi
                inner join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8) = iid.order_id
                inner join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                where oi.order_id = {$order_id}
                and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
                and iid.quantity_on_hand_diff < 0
                limit 1";
        $is_part_out = $db->getOne($sql);
        if(empty($is_part_out)) {
            return false;
        }
        return true;
   }
   
   /**
    * 判断订单是否已经送往菜鸟
    */
    function check_order_sendto_bird($order_id,$new_facility_id) {
        global $db;
        $sql = "
                SELECT eoi.facility_id,ebi.indicate_status
                from 
                ecshop.ecs_order_info eoi
                inner join ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
                where eoi.order_id = {$order_id} and ebi.indicate_status not in ('推送失败','等待推送','等待推送时取消成功','由ERP发货无须推送')";
        $is_express_bird = $db->getRow($sql);
        if(empty($is_express_bird)) {
            return "success";
        }
      //$facility = array('144624934','144624935','144624936','144624937','144676339');//测试环境
        $facility = array('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265');//正式环境
  
        //状态为【推送成功后转回ERP发货】的订单需要判断转的仓是菜鸟仓还是非菜鸟仓
        if($is_express_bird['indicate_status']=="推送成功后转回ERP发货" ){
            if(in_array($new_facility_id,$facility)){
                return "not_again"; //不能转回菜鸟仓
            }else{
                if(in_array($new_facility_id,$facility)){
                    return "not_again";  //不能转回菜鸟仓
                }else{
                    return "success";
                }
            }
        }
        if($is_express_bird['indicate_status']=="推送成功后取消成功" ){
            if(in_array($new_facility_id,$facility)){
                return "not_again"; //不能转回菜鸟仓
            }else{
                if(in_array($new_facility_id,$facility)){
                    return "not_again";  //不能转回菜鸟仓
                }else{
                    return "success";
                }
            }
        }
        return "error";
   }
   
    /**
    * 判断订单是否为'等待推送时取消成功','由ERP发货无须推送',
    * 这些推送状态是可以任意转仓的 by hzhang1 2015-10-09
    */
    function check_order_waitto_bird($order_id,$new_facility_id) {
        global $db;
        $sql = "
                SELECT eoi.facility_id,ebi.out_biz_code,ebi.indicate_status
                from 
                ecshop.ecs_order_info eoi
                inner join ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
                where eoi.order_id = {$order_id} and ebi.indicate_status in ('等待推送','等待推送时取消成功','由ERP发货无须推送','推送成功后转回ERP发货','推送失败')";
        $is_express_bird = $db->getRow($sql);
        if(empty($is_express_bird)) {
            return false;
        }
        
        //$facility = array('144624934','144624935','144624936','144624937','144676339');//测试环境
        $facility = array('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265');//正式环境
        if($is_express_bird['indicate_status']=='等待推送' || $is_express_bird['indicate_status']=='推送失败'){
            $sql ="update ecshop.express_bird_indicate set indicate_status='由ERP发货无须推送',last_updated_stamp=now() where out_biz_code ='{$is_express_bird['out_biz_code']}'";
            $db->query($sql);
        }
        
        if($is_express_bird['indicate_status']=='由ERP发货无须推送' and in_array($new_facility_id,$facility)){
            $sql ="update ecshop.express_bird_indicate set indicate_status='等待推送',created_stamp=now(),last_updated_stamp=now() where out_biz_code ='{$is_express_bird['out_biz_code']}'";
            $db->query($sql);
        }
         if(in_array($new_facility_id,$facility) && $is_express_bird['indicate_status']=='等待推送时取消成功'){
            $sql ="update ecshop.express_bird_indicate set indicate_status='等待推送',last_updated_stamp=now() where out_biz_code ='{$is_express_bird['out_biz_code']}'";
            $db->query($sql);
        }
        return true;
   }
   
   /**
    * 入库入口判断
    * 0->扫描枪入库  1->老流程收货入库  2->批量入库到容器 3->批次号入库到容器',
    */
   function check_in_storage_mode($in_storage_mode) {
        global $db,$_CFG;
        $party_id = $_SESSION['party_id'];

        $sql = "SELECT name,in_storage_mode
                from 
                romeo.party where party_id = $party_id and is_leaf = 'Y'
                limit 1";
        $party_info = $db->getRow($sql);
        if(empty($party_info)) {
            die('请切换到具体的组织再入库！');
        }

        if($party_info['in_storage_mode'] != $in_storage_mode) {
            // 老流程入库权限特殊处理
            if($in_storage_mode == 1) {
              if(in_array($_SESSION['admin_name'], array('lyma','xlhong','jxiong','xrlao','zhyan','mjzhou','ytchen','lyyuan','lxiao'))){
                 return true;
              }
            }
        
            $mode_name = $_CFG['adminvars']['in_storage_mode'][$party_info['in_storage_mode']];
            if(empty($mode_name)) {
                die('无此入库模式');
            } else {
                die($party_info['name'].' 请用下面的入库模式：'.$mode_name);
            }
        }
        return true;
   }
   
   /**
    * 验证用户是否有外包仓权限
    */
   function check_user_in_facility(){
        global $db;
        $admin_user = $_SESSION['admin_name'];
        $sql="select facility_id from romeo.facility where is_out_ship = 'Y'";
        $out_facility_array = $db->getCol($sql);
        $sql = "select facility_id from ecshop.ecs_admin_user where user_name = '".$admin_user ."' limit 1";
        $user_facilitys = $db->getOne($sql);
        $user_facility_array = explode(',',$user_facilitys);
        $intersect = array_intersect($out_facility_array,$user_facility_array);
        if(empty($intersect)){
            die('请开通外包仓权限后再行操作！');
        }else{
            return $intersect;
        }
        
   }
   
   /**
    * 订单赠品活动提醒
    * 
    */
   function get_order_gift_reminds($party_id,$distributor_id) {
        global $db;
        if(empty($party_id) || empty($distributor_id)) {
            return '';
        }
        //增加赠品活动提醒
        $sql="select distributor_ids,notice,start_time,end_time 
              from ecshop.ecs_group_goods_remind 
              where party_id ='{$party_id}'
              and end_time > now()
              and start_time < now()
              and status = 'OK'
              order by start_time";
        $goods_reminds=$db->getAll($sql);
        $notice = '';
        foreach($goods_reminds as $goods_remind){
            $distributor_ids=explode(",",$goods_remind['distributor_ids']);
            if(in_array($distributor_id,$distributor_ids)){
                $notice.=$goods_remind['notice']."<br>";
            }
        }
        return $notice;
   }
   
    /**
    * 检查亨氏的订单是否要维护出生日期，如果要维护，则返回系统已经维护的出生日期
    * ljzhou 2014-7-15
    */
   function check_heinz_user_birthday($order_id) {
        global $db;
        $result = array();
        if(empty($order_id)) {              
            $result['is_maintain_birthday'] = false;
            return $result;
        }
        $mobile = check_heinz_order_maintain_birthday($order_id);
        
        // 要维护的
        if(!empty($mobile)) {
            $result['is_maintain_birthday'] = true;
            $result['birthday'] = '0000-00-00';
            $sql="select date(birthday) from ecshop.brand_heinz_active_user where heinz_user_id = '{$mobile}' limit 1";
            $birthday = $db->getOne($sql);
            if(!empty($birthday)) {
                $result['birthday'] = $birthday;
            }
        } else {
            $result['is_maintain_birthday'] = false;
        }
        
        return $result;
   }
   
   /**
    * 判断亨氏的订单是否要维护宝宝出生日期
    */
   function check_heinz_order_maintain_birthday($order_id) {
        global $db;
        
        // 首先判断是否要维护出生日期
        $sql = "select eoi.mobile
            from ecshop.ecs_order_info eoi
            inner join ecshop.ecs_order_goods og ON eoi.order_id = og.order_id
            -- 商品是参加活动的
            inner join ecshop.brand_heinz_goods hg ON if(og.style_id !=0,concat(og.goods_id,'_',og.style_id),og.goods_id) = hg.goods_outer_id
            -- 用户是参加活动的
            inner join ecshop.brand_heinz_active_user u ON u.heinz_user_id = eoi.mobile
            where 
            hg.is_activity = 1
            and eoi.order_id = '{$order_id}'
            and eoi.distributor_id in ('1797','1900')
            and eoi.order_type_id = 'SALE'
            and eoi.party_id = '65609'
            and eoi.order_time >= u.start_time
            limit 1
       ";
            
        $mobile = $db->getOne($sql);
        return $mobile;
   }
   
   /**
    * 维护亨氏用户的宝宝出生日期
    * ljzhou 2014-7-15
    */
   function maintain_heinz_user_birthday($order_id,$birthday) {
        global $db;
        $result = array();
        if(empty($order_id) || empty($birthday)) {
            $result['error'] = '订单号或者生日为空';   
            $result['success'] = false;         
            return $result;
        }
        
        $mobile = check_heinz_order_maintain_birthday($order_id);

        // 更新出生日期
        if(!empty($mobile)) {
            $sql = "update ecshop.brand_heinz_active_user set birthday = '{$birthday}' where heinz_user_id ='{$mobile}' limit 1";
            $update_res = $db->query($sql);
        }
        $result['success'] = true;   
        return $result;
   }
   
   /**
    * 执行sql，保证事务性
    * ljzhou 2014-8-8
    */
    
    function exec_sql_transaction($sqls) {
        $result = array();
        if(empty($sqls)) {
            $result['success'] = false;
            $result['error'] = '没有要执行的sql';
            return $result;
        }
        global $db;
         //开始事务
        $db->start_transaction();       
        foreach ($sqls as $sql) {
            try {
                if(false == $db->query($sql)){
                    $db->rollback();
                    $result['success'] = false;
                    $result['error'] = "数据修改失败，请检查数据".$sql;
                    return $result;
                }
            }catch(Exception $e) {
                $result['success'] = false;
                $result['error'] = "数据修改失败，请检查数据".$sql.' exception:'.$e->getMessage();
                return $result;
            }
        }
        //  提交事务
        $db->commit();
        $result['success'] = true;
        return $result;
    }

    /*
     * 更新OR物品信息
     */
    function update_OR_goods($code,$material_number,$goods_type) {
        global $db;
        $sql = "select 1 from  ecshop.brand_or_product where code = '{$code}' limit 1";
        if($db->getOne($sql)) {
            $sql = "update ecshop.brand_or_product set material_number = '{$material_number}',goods_type='{$goods_type}',last_updated_stamp=now() where code='{$code}' limit 1";
        } else {            
            $sql = "insert into ecshop.brand_or_product (code,material_number,goods_type,created_stamp,last_updated_stamp) values('{$code}','{$material_number}','{$goods_type}',now(),now())";
        }
        return $db->query($sql);
    }
    
    function update_estee_goods($code,$material_number,$goods_type) {
        global $db;
        $sql = "select 1 from  ecshop.brand_estee_product where code = '{$code}' and party_id = {$_SESSION['party_id']} limit 1";
        if($db->getOne($sql)) {
            $sql = "update ecshop.brand_estee_product set material_number = '{$material_number}',goods_type='{$goods_type}',last_updated_stamp=now() where code='{$code}' and party_id = {$_SESSION['party_id']} limit 1";
        } else {            
            $sql = "insert into ecshop.brand_estee_product (party_id,code,material_number,goods_type,created_stamp,last_updated_stamp) values({$_SESSION['party_id']}, '{$code}','{$material_number}','{$goods_type}',now(),now())";
        }
        return $db->query($sql);
    }
    
    /**
     * 根据套餐code查找套餐
     */
    function get_group_order_goods($tc_codes,$party_id) {
        if(empty($tc_codes) || empty($party_id)) return array();
        global $db;
        $result = array();
        // 根据套餐code 查找套餐
        $sql = "select dg.code,dg.amount,
                i.goods_id,dg.name as group_name,
                ifnull(i.style_id,0) style_id,
                ifnull(i.goods_name,'') goods_name,
                ifnull(i.goods_number,0) goods_number,
                ifnull(i.price,0) goods_price,
                ifnull(i.shipping_fee,0) shipping_fee,
                g.goods_weight,
                g.goods_volume,
                g.added_fee
            from
                ecshop.distribution_group_goods dg
                inner join ecshop.distribution_group_goods_item i on dg.group_id=i.group_id
                left join ecshop.ecs_goods g on i.goods_id=g.goods_id
            where
                dg.code ".db_create_in($tc_codes)." and party_id='{$party_id}' and status='OK' ";
        
        $group_order_goods = $group_order_goods_keys = $group_order_goods_values = array();
        $group_order_goods = $db->getAllRefBy ( $sql, array ('code' ), $group_order_goods_keys, $group_order_goods_values );
        $result['codes'] = array_unique($group_order_goods_keys);
        $result['group_order_goods'] = $group_order_goods_values;
        
        return $result;
    }
    
    /**
     * 根据套餐和数量组装order_goods
     * $group_order_goods_temps 套餐
     * $tc_number 套餐的数量
     */
     function get_tc_order_goods($group_order_goods_temps,$tc_number) {
        if(empty($group_order_goods_temps) || empty($tc_number)) return array();

        $order_goods = array();
        for($i=0;$i<$tc_number;$i++) {
            foreach($group_order_goods_temps as $group_order_goods_temp) {
                $order_goods_item = array();
                $order_goods_item['goods_id'] = $group_order_goods_temp['goods_id'];
                $order_goods_item['style_id'] = $group_order_goods_temp['style_id'];
                $order_goods_item['price'] = $group_order_goods_temp['goods_price'];
                $order_goods_item['goods_number'] = $group_order_goods_temp['goods_number'];
                $order_goods_item['tc_code'] = $group_order_goods_temp['code'];
                $order_goods[] = $order_goods_item;
            }
        }
        
        return $order_goods;
     }
     
     /**
      * 对同一界面下的需要导入的EXCEL提供模板下载
      * 
      * @param array tpl 包含EXCEL文件所需格式 格式如下
      * array ('批量导入库存比例' => 
      *    array ('outer_id' => '商家编码', 
      *           'reserve_number' => '预留库存数量', 
      *           'reserve_ratio' => '预留库存比例' ) );
      * */
     function export_excel_template($tpl){
        $title_array = array();
        $file_name = null;
        foreach($tpl as $file_name => $form){
            foreach($form as $titleName){
                $title_array[] = $titleName;
            }
        }
        $title = array(0=>$title_array);
        $data = array();
        $type = array();
        $sheetname = $file_name;
        excel_export_model($title,$file_name.".xlsx",$data,$type,$sheetname);
     }
     
    /**
     * 对提交EXCEL文件之前进行检测，如果正确则返回对应结果
     * 
     * @param array tpl 包含EXCEL文件所需格式 格式如下
     * array ('批量导入库存比例' => 
     *    array ('outer_id' => '商家编码', 
     *           'reserve_number' => '预留库存数量', 
     *           'reserve_ratio' => '预留库存比例' ) );
     * 
     * @return array result 返回值为一个数组，若存在$result['message']则存在异常，
     * 否则数据存储在result['result'][$key],其中$key为参数tpl的第一个key值，如批量导入库存比例
     * */
    function before_upload_exam($tpl){
        require_once (ROOT_PATH . 'includes/helper/uploader.php');
        $result = array();
        QLog::log ( '商品导入：' );
    /* 文件上传并读取 */
        @set_time_limit ( 300 );
        $uploader = new Helper_Uploader ();
        $max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
        do{
            if (! $uploader->existsFile ( 'excel' )) {
                $result['message']='没有选择上传文件，或者文件上传失败';
                break;
            }
            
            // 取得要上传的文件句柄
            $file = $uploader->file ( 'excel' );
            // 检查上传文件
            if (! $file->isValid ( 'xls, xlsx', $max_size )) {
                $result['message']='非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
                break;
            }
            $failed = null;
            // 读取excel
            $result['result'] = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
            if (! empty ( $failed )) {
                $result['message']= reset ( $failed ) ;
                break;
            }
            /* 检查数据  */
            $key = '';
            foreach($result['result'] as $key =>$item){}
            $rowset = $result ['result'][$key];
            // 订单数据读取失败
            if (empty ( $rowset )) {
                $result['message']= 'excel文件中没有数据,请检查文件';
                break;
            }
        }while(false);
        return $result;
    }
    /**
     * @author jwang 2014-11-14
     * 下采购订单，
     * 和generate_order_actionV2.php是一样的，要改一起改，以后他也可以改用本函数
     * 默认自己库存、B2C、RMB
     * 不支持事务，因为我是跟自动入库一起，一荣俱荣，在外面一起控制事务
     * 不支持金宝贝和康贝的特殊处理，估计用不到
     */
    function generate_order($batch_info, $goods_list, $action_user='system', $admin_id='0') {
        $result = array('err_no' => 0, 'message' => '');
        $is_debug = true;
        
        // 默认值
        $provider_id = $batch_info['provider_id'] ? $batch_info['provider_id'] : 432;
        $order_type = $batch_info['order_type'] ? $batch_info['order_type'] : 'B2C';
        $currency = $batch_info['currency'] ? $batch_info['currency'] : 'RMB';
        //check
        
        if (empty($batch_info) || empty($goods_list)) {
            $result['err_no'] = 1;
            $result['message'] = "订单和商品都不能为空";
            return $result;
        }
        
        $facility_id = $batch_info['facility_id'];
        if (empty($facility_id)) {
            $result['err_no'] = 1;
            $result['message'] = "请选择收货仓库";
            return $result;
        }
    
        if (!party_explicit($batch_info['party_id'])) {
            $result['err_no'] = 1;
            $result['message'] = "请选择具体的分公司后再下采购订单";
            return $result;
        }

        if ($batch_info['party_id'] == PARTY_DRAGONFLY && $order_type != 'C2C') {
            $result['err_no'] = 1;
            $result['message'] = "DragonFly的订单必须是要c2c的";
            return $result;
        }
    
//      $order_goods_id = intval($_REQUEST['order_goods_id']);
//      if($order_goods_id != 0){
//          $result['err_no'] = 1;
//          $result['message'] = "order_goods_id 已存在";
//          return $result;
//      }
    
        global $ecs, $db;
//      $db->start_transaction();        //开始事务
    
//      if($batch_info['party_id'] == 65574){
//          $gymboree_vouch_file_name = $batch_info['gymboree_file_name'];
//          $gymboree_vouchID = $batch_info['gymboree_vouchID'];
//          
//          if($gymboree_vouch_file_name != "-1"){
//              $sql = "insert into ecshop.brand_gymboree_inoutvouch (fchrInOutVouchID,filename,is_send,create_timeStamp,upload_timeStamp)
//                  VALUES ('{$gymboree_vouchID}','{$gymboree_vouch_file_name}','false',NOW(),NOW());
//              ";
//              if(!$db->query($sql))
//              {
////                    $db->rollback();
//                  $result['err_no'] = 2;
//                  $result['message'] = "采购订单生成失败，请重新下单";
//                  return $result;
//              }
//          }               
//      }
        
        require_once ('includes/lib_order.php');
        require_once ('admin/includes/lib_goods.php');
        // 生成批次采购订单信息 ljzhou 2012.11.29
        do {
            $batch_order_sn = get_batch_order_sn(); //获取新订单号
            $sql = "INSERT INTO {$ecs->table('batch_order_info')}
                        (batch_order_sn, party_id, facility_id, order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
                        provider_id,purchaser,order_type,action_user
                        )
                        VALUES('{$batch_order_sn}', '".$batch_info['party_id']."', '{$facility_id}', NOW(),
                        '{$action_user}','','N','N','{$order['currency']}',
                        '{$provider_id}','{$action_user}','{$order_type}','{$action_user}'
                        )";
    
            $db->query ( $sql, 'SILENT' );
            $error_no = $db->errno ();
            if ($error_no > 0 && $error_no != 1062) {
//              $db->rollback();
                $result['err_no'] = 3;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        } while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据
        $batch_order_id = $db->insert_id();
        
        
        $total_pay = 0;    // 计算总采购费
        $rebate_strategy_data = array();    // 用于返利策略分配的数据
        $is_serial_in_batch = 'N';
        foreach ($goods_list as $goods_detail) {
            //分别添加不同代码
            $goods_id = intval($goods_detail['goods_id']);
            $style_id = intval($goods_detail['style_id']);
            $goods_number = intval($goods_detail['goods_number']);
            $customized = $goods_detail['customized']; //貌似已经不用了
            $purchase_paid_amount = $goods_detail['purchase_paid_amount'] ? $goods_detail['purchase_paid_amount'] : 0;
            $purchase_added_fee = $goods_detail['purchase_added_fee'] ? $goods_detail['purchase_added_fee'] : 1.17;
            $rebate = $goods_detail['rebate'] ? $goods_detail['rebate'] : 0;
    
            if (!$goods_id || !$goods_number) { $result['err_no'] = 3; $result['message'] = "没有商品";} //{ continue; } 不再continue，直接报错
            if ($rebate > ($purchase_paid_amount*$goods_number)) { $result['err_no'] = 3; $result['message'] = "返利不能大于订单金额";} //continue;
            
            $pay = $purchase_paid_amount*$goods_number;
            $total_pay += $pay;
    
            $error_no = 0;
            do {
                $order_sn = get_order_sn() . "-c"; //获取新订单号
                $sql = "INSERT INTO {$ecs->table('order_info')}
                        (order_sn, order_time, order_status, pay_status, user_id, 
                        party_id, facility_id, currency, order_type_id)
                        VALUES('{$order_sn}', now(), 2, 2, '{$admin_id}',                      
                        '".$batch_info['party_id']."', '{$facility_id}', '{$currency}', 'PURCHASE')";
                $db->query($sql, 'SILENT');
                $error_no = $db->errno();
                if ($error_no > 0 && $error_no != 1062) {
//                  $db->rollback();
                    $result['err_no'] = 4;
                    $result['message'] = "采购订单生成失败，请重新下单";
                    return $result;
                }
            } while ($error_no == 1062); //如果是订单号重复则重新提交数据
            $sqls[] = $sql;
            $order_id = $db->insert_id();
            
            //记录采购订单信息
            $is_serial = get_goods_item_type($goods_id) == "SERIALIZED" ? 'Y' : 'N';
            if($is_serial == 'Y' and $is_serial_in_batch == 'N'){
                $is_serial_in_batch = 'Y';
            }
            $sql = "INSERT INTO romeo.purchase_order_info
                        (order_id, purchase_paid_amount, purchaser, order_type, is_serial)
                        VALUES('{$order_id}', '{$purchase_paid_amount}', '{$action_user}', '{$order_type}', '{$is_serial}')";
            if(false == $db->query($sql, 'SILENT')){
//              $db->rollback();
                $result['err_no'] = 5;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
            $db->insert_id();
            
            //将采购订单号插入到此批次采购订单映射表中 
            $sql = "INSERT INTO {$ecs->table('batch_order_mapping')}
                        (batch_order_id, order_id)
                        VALUES('{$batch_order_id}', '{$order_id}')";
            if(false == $db->query($sql, 'SILENT')){
//              $db->rollback();
                $result['err_no'] = 6;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
            $batch_order_mapping_id = $db->insert_id();
    
            // 返利策略数据
            $rebate_strategy_data[] = array('order_id' => $order_id, 'pay' => $pay);
    
            $sql = "SELECT * FROM {$ecs->table('goods')} WHERE goods_id = '$goods_id'";
            $goods = $db->getRow($sql);
    
            if ($style_id > 0) {
                $sql = "SELECT *, IF (gs.goods_color = '', s.color, gs.goods_color) AS color FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s WHERE gs.goods_id = '{$goods['goods_id']}' AND gs.style_id = s.style_id AND s.style_id = '{$style_id}'";
                $style = $db->getRow($sql);
                $goods['goods_name'] .= " {$style['color']}";
                $goods['shop_price'] = $style['style_price'];
            }
    
            //对order_goods表数据进行修改
            $goods_name = addslashes($goods['goods_name']);
            $sql = "INSERT INTO {$ecs->table('order_goods')} (order_id, goods_id, goods_name, goods_number, goods_price, style_id, customized, added_fee) 
                                VALUES('{$order_id}', '{$goods_id}', '{$goods_name}', '{$goods_number}', '{$goods['shop_price']}', {$style_id}, '{$customized}', '{$purchase_added_fee}')";
            if(false == $db->query($sql)){
//              $db->rollback();
                $result['err_no'] = 9;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
            $order_goods_id = $db->insert_id();
            $sqls[] = $sql;
    
            //插入返利
            /*$sql  = "INSERT INTO `purchase_order_applied_rebate` (`order_id`, `applied_rebate`) VALUES ('$order_id', '$rebate')";
            if(false == $db->query($sql)){
                $db->rollback();
                $result['err_no'] = 10;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
            $sqls[] = $sql;*/
    
            // 把供价记录到价格跟踪系统中去
            $sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$action_user}'";
            $uuid = $db->getOne($sql);
            $sql = "SELECT goods_style_id FROM {$ecs->table('goods_style')} WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}' ";
            $goods_style_id = $db->getOne($sql);
            $sql = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) VALUE ('$goods_id', '$goods_style_id', '{$provider_id}', '$purchase_paid_amount', '$uuid', NOW())";
            if(false == $db->query($sql)){
//              $db->rollback();
                $result['err_no'] = 11;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        }
    
        if($is_serial_in_batch == 'Y'){
            $sql = "UPDATE ecshop.ecs_batch_order_info set is_serial = 'Y'  where batch_order_id = '{$batch_order_id}'";
            if(false == $db->query($sql)){
//              $db->rollback();
                $result['err_no'] = 12;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        }
    
        $db->commit();
        if($result['err_no']==0){
            $result['message'] =$batch_order_id;
        }
        return $result;
        
    }
    
    
    /**
     * 检查babynes订单中含有BabyNes智能冲调器这个商品
     * ljzhou 2014-11-27
     */
     function has_babynes_robot($order_id) {
        if(empty($order_id)) return false;
        global $db;
        $sql = "select 1 from ecshop.ecs_order_info oi
        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id 
        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
        where oi.order_id = {$order_id} and pm.product_id = '117786502' 
        limit 1";
        $has_robot = $db->getOne($sql);
        return $has_robot;
     }
     
     /**
     * 检查1个订单已经打印了多少个面单了
     * ljzhou 2014-11-27
     */
     function get_order_tracking_number_count($order_id) {
        if(empty($order_id)) return 0;
        global $db;
        $sql = "select count(distinct s.tracking_number) 
        from romeo.order_shipment os
        inner join romeo.shipment s ON os.shipment_id = s.shipment_id
        where os.order_id = '{$order_id}'
        and s.tracking_number is not null 
        and s.tracking_number <>'' limit 1";
        $tracking_number_count = $db->getOne($sql);
        return $tracking_number_count;
     }
    
    /**
     * 查询该用户权限下的所有外包仓库
     * hyzhou1
     */
    function get_available_outShip_facility(){
        global $db;
        $sql="SELECT facility_id, facility_name FROM romeo.facility WHERE IS_OUT_SHIP = 'Y'";
        $facilities = Helper_Array::toHashmap($db->getAll($sql), 'facility_id', 'facility_name');
        //当前用户在当前业务组下的外包仓
        $facility_list = array_intersect_assoc($facilities, get_available_facility($_SESSION['party_id']), get_user_facility());
        return $facility_list;
    }

    /**
     * 判断业务组是否为跨境业务组
     * bfxie 2016-03-15
     */
    function is_kuajing_party($party_id){
        global $db;
        $sql="select 1 from romeo.party where party_id = '{$party_id}' and party_group like '%跨境' ";
        $res = $db->getAll($sql);
        if(empty($res)){
            return false;
        }else {
            return true;
        }
    }
    
/*
 * 调拨采购入库
 */    
function transfer_dc($obj_data,$supplier_batch_order_id ,$batch_order_id)
{
    global $ecs, $db;
    
    if($supplier_batch_order_id == 0 || $batch_order_id == 0 || empty($supplier_batch_order_id) || empty($batch_order_id)){
       	$result['err_no'] = 3;
        $result['message'] = "未生成批次号";
        return $result;
    }
    $supplierReturnRequestId = $obj_data->supplierReturnRequestId;//
    $taxRate = $obj_data->tax_rate;
    $excutedAmount = 0 ;
    $goodsNumber = $obj_data->ret_amount;
    $orderTypeId = $obj_data->order_type_id;
    $returnSupplierId = $obj_data->ret_provider_id;
    $batchSn = $obj_data->batch_sn; 									//批次号
    $originalSupplierId = $obj_data->original_provider_id;
    $paymentTypeId = $obj_data->purchase_paid_type;
    $checkNo = $obj_data->chequeNo;//
    $partyId = $_SESSION['party_id'];
    $facilityId = $obj_data->facility_id;
    $productId = $obj_data->productId ;
    $unitPrice = $obj_data->goods_price ;
    $currency = $obj_data->currency ;
    $purchaseUnitPrice = $obj_data->purchase_unit_price ;
    $createdUserByLogin = $_SESSION['admin_name'];
    $statusId = $obj_data->status_id ;
    $fchrWarehouseID = $obj_data->fchr_warehouse_id;
    $ret_facility_id_dt	= $obj_data->ret_facility_id_dt;
	$goods_id = $obj_data->ret_goods_id ;
	$style_id = $obj_data->ret_style_id ;
	$remark=$obj_data->remark;
	$status_id = $obj_data->status_id ;
    $goods_name = $obj_data->goods_name;
    $arrive_time= $obj_data->arrive_time;
    $remark.=" 调拨申请号:".$supplierReturnRequestId;
    
    $result = array('err_no' => 0, 'message' => '');
    $is_debug = true;
	//check

    if (empty($ret_facility_id_dt)) {
        $result['err_no'] = 1;
        $result['message'] = "请选择收货仓库";
        return $result;
    }

    if (!party_explicit($_SESSION['party_id'])) {
        $result['err_no'] = 1;
        $result['message'] = "请选择具体的分公司后再下采购订单";
        return $result;
    }
    $provider_id = $_POST['provider_id'];
    $sql = "select provider_order_type from ecshop.ecs_provider where provider_id = '{$returnSupplierId}' limit 1";
    $order_type = $db->getOne($sql);

    if (empty($returnSupplierId) || !in_array($order_type,array('B2C','C2C','DX')) ) {
    	$result['err_no'] = 1;
        $result['message'] = "供应商或者订单类型错误，请重新下单";
        return $result;
    }
    
    if ($_SESSION['party_id'] == PARTY_DRAGONFLY && $order_type != 'C2C') {
        $result['err_no'] = 1;
        $result['message'] = "DragonFly的订单必须是要c2c的";
        return $result;
    }
    //开始事务
    $db->start_transaction();        
	
	$sql_pt="SELECT g.goods_party_id
	                FROM  ecshop.ecs_goods AS g
	                WHERE g.goods_id = '{$goods_id}'";
	
	$dcParty_id = $db->getOne($sql_pt);
	
	$provider_order_sn = "";
	$provider_out_order_sn = "";
	$inventory_type ="";
//	do {
//		$batch_order_sn = get_batch_order_sn(); 
//        $sql = "INSERT INTO {$ecs->table('batch_order_info')}
//                    (batch_order_sn, party_id, facility_id,arrive_time, order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
//                    provider_id,purchaser,order_type,action_user,provider_order_sn, provider_out_order_sn, inventory_type, remark
//                    )
//                    VALUES('{$batch_order_sn}', '".$dcParty_id."', '{$ret_facility_id_dt}','{$arrive_time}', NOW(),
//                    '{$_SESSION['admin_name']}','','N','N','$currency',
//                    '$returnSupplierId','{$_SESSION['admin_name']}','{$order_type}','{$_SESSION['admin_name']}',
//                    '{$provider_order_sn}' , '{$provider_out_order_sn}', '{$inventory_type}', '{$remark}'
//                    )";	
//		$db->query ( $sql, 'SILENT' );
//		$error_no = $db->errno ();
//		if ($error_no > 0 && $error_no != 1062) {
//            $db->rollback();
//            $result['err_no'] = 3;
//            $result['message'] = "采购订单生成失败，请重新下单";
//            return $result;
//		}
//	} while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据
//    $batch_order_id = $db->insert_id();
    
    
    $total_pay = 0;    // 计算总采购费
    $rebate_strategy_data = array();    // 用于返利策略分配的数据
    $is_serial_in_batch = 'N';
    

    //分别添加不同代码
    $error_no = 0;
    do {
        $order_sn = get_order_sn() . "-dc"; //获取新订单号
        $sql = "INSERT INTO {$ecs->table('order_info')}
                (order_sn, order_time, order_status, pay_status, user_id, 
                party_id, facility_id, currency, order_type_id)
                VALUES('{$order_sn}', NOW(), 2, 2, '{$_SESSION['admin_id']}',                      
                '".$dcParty_id."', '{$ret_facility_id_dt}', '{$currency}', 'PURCHASE_TRANSFER')";
        $db->query($sql, 'SILENT');
        $error_no = $db->errno();
        if ($error_no > 0 && $error_no != 1062) {
            $db->rollback();
            $result['err_no'] = 4;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
    } while ($error_no == 1062); //如果是订单号重复则重新提交数据
    $sqls[] = $sql;
    $order_id = $db->insert_id();
    
    
    //记录采购订单信息
    $is_serial = get_goods_item_type($goods_id) == "SERIALIZED" ? 'Y' : 'N';
    if($is_serial == 'Y' and $is_serial_in_batch == 'N'){
        $is_serial_in_batch = 'Y';
    }
    $sql = "INSERT INTO romeo.purchase_order_info
                (order_id, purchase_paid_amount, purchaser, order_type, is_serial)
                VALUES('{$order_id}', '{$purchaseUnitPrice}','{$_SESSION['admin_id']}', '{$order_type}', '{$is_serial}')";
    if(false == $db->query($sql, 'SILENT')){
        $db->rollback();
        $result['err_no'] = 5;
        $result['message'] = "采购订单生成失败，请重新下单";
        return $result;
    }
    $db->insert_id();
    
    //将采购订单号插入到此批次采购订单映射表中 
    $sql = "INSERT INTO {$ecs->table('batch_order_mapping')}
                (batch_order_id, order_id)
                VALUES('{$batch_order_id}', '{$order_id}')";
    if(false == $db->query($sql, 'SILENT')){
        $db->rollback();
        $result['err_no'] = 6;
        $result['message'] = "采购订单生成失败，请重新下单";
        return $result;
    }
    $batch_order_mapping_id = $db->insert_id();

    // 返利策略数据
    $rebate_strategy_data[] = array('order_id' => $order_id, 'pay' => $pay);

    $sql = "SELECT * FROM {$ecs->table('goods')} WHERE goods_id = '$goods_id'";
    $goods = $db->getRow($sql);

    if ($style_id > 0) {
        $sql = "SELECT *, IF (gs.goods_color = '', s.color, gs.goods_color) AS color FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s WHERE gs.goods_id = '{$goods['goods_id']}' AND gs.style_id = s.style_id AND s.style_id = '{$style_id}'";
        $style = $db->getRow($sql);
        $goods['goods_name'] .= " {$style['color']}";
        $goods['shop_price'] = $style['style_price'];
    }

    //对order_goods表数据进行修改
    $goods_name = addslashes($goods['goods_name']);
    $sql = "INSERT INTO {$ecs->table('order_goods')} (order_id, goods_id, goods_name, goods_number, goods_price, style_id, customized, added_fee, status_id) 
    					VALUES('{$order_id}', '{$goods_id}', '{$goods_name}', '$goodsNumber', '{$goods['shop_price']}', {$style_id}, '', '', '{$status_id}')";
    if(false == $db->query($sql)){
        $db->rollback();
        $result['err_no'] = 9;
        $result['message'] = "采购订单生成失败，请重新下单";
        return $result;
    }
    $order_goods_id = $db->insert_id();
    $sqls[] = $sql;

    if($is_serial_in_batch == 'Y'){
        $sql = "UPDATE ecshop.ecs_batch_order_info set is_serial = 'Y'  where batch_order_id = '{$batch_order_id}'";
        if(false == $db->query($sql)){
            $db->rollback();
            $result['err_no'] = 12;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
    }
 
     $sql = "SELECT order_id
			from romeo.supplier_return_request srr
			INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
			inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
			where srr.SUPPLIER_RETURN_ID = {$supplierReturnRequestId}";
     $ret_order_id  = $db -> getOne($sql); 
  
     $sqlt = "insert into ecshop.supplier_transfer_mapping (dt_order_id,dc_order_id,supplier_return_batch_id,purchase_batch_id,create_time)
     		  values('{$ret_order_id}','{$order_id}','{$supplier_batch_order_id}','{$batch_order_id}',NOW())";
     
     if(false == $db->query($sqlt)){
        $db->rollback();
        $result['err_no'] = 13;
        $result['message'] = "采购订单生成失败，请重新下单";
        return $result;
     }
    
    

    $db->commit();
    return $result;
}
function get_supplier_transfer_batch_sn()
    {
	  global $db;
	 do {
		  $day = date('Y-m-d',time()); 
		  $batch_order_sn = ''; 
		  $batch_order_sn = date('Ymd',time()); 
		  $a = mt_rand();
		  $b = substr('00'.abs($a), -2);
		  $batch_order_sn.=$b;
		
		  $sql = "select count(*) from ecshop.supplier_return_batch_info oi where oi.created_stamp >= curdate() limit 1";
		  $num = $db->getOne($sql);
		  $batch_order_sn = $batch_order_sn.'-'.($num+1);
		  $sql = "INSERT INTO ecshop.supplier_return_batch_info(batch_order_sn,created_stamp)
		           VALUES( '{$batch_order_sn}',NOW())";	
		    $db->query ($sql);
			$error_no = $db->errno ();
			if ($error_no > 0 && $error_no != 1062) {
			    $db->rollback();
			    $result['err_no'] = 3;
			$result['message'] = "dc订单生成失败，请重新下单";
			    return $result;
			}
	 } while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据
     $batch_order_id = $db->insert_id();
	 
	 return $batch_order_id;
    }


function get_purchase_batch_sn($obj_data,$batch_order_id)
    {
	 global $ecs, $db;
    
     if(empty($batch_order_id) || $batch_order_id == 0){
    	return 0;
     }
     $supplierReturnRequestId = $obj_data->supplierReturnRequestId; 
     
     $ret_facility_id_dt = $obj_data->ret_facility_id_dt;
     $arrive_time= $obj_data->arrive_time;
     $currency = $obj_data->currency ;
     $returnSupplierId = $obj_data->original_provider_id;
     $provider_order_sn = "";
	 $provider_out_order_sn = "";
	 $inventory_type ="";
	 
	 $sql_batch_sn = "SELECT batch_order_sn from ecshop.supplier_return_batch_info where batch_order_id = '{$batch_order_id}'";
	 
	 $supplier_bartch_order_sn =$db->getOne($sql_batch_sn); 
	 $remark.=" 调拨批次号:".$supplier_bartch_order_sn;  
	 $sql = "select provider_order_type from ecshop.ecs_provider where provider_id = '{$returnSupplierId}' limit 1";
     $order_type = $db->getOne($sql);
	 
	 do {
		$batch_order_sn = get_batch_order_sn(); 
        $sql = "INSERT INTO {$ecs->table('batch_order_info')}
                    (batch_order_sn, party_id, facility_id,arrive_time, order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
                    provider_id,purchaser,order_type,action_user,provider_order_sn, provider_out_order_sn, inventory_type, remark,supplier_return_batch_id
                    )
                    VALUES('{$batch_order_sn}', '{$_SESSION['party_id']}', '{$ret_facility_id_dt}','{$arrive_time}', NOW(),
                    '{$_SESSION['admin_name']}','','N','N','$currency',
                    '{$returnSupplierId}','{$_SESSION['admin_name']}','{$order_type}','{$_SESSION['admin_name']}',
                    '{$provider_order_sn}' , '{$provider_out_order_sn}', '{$inventory_type}', '{$remark}','{$batch_order_id}'
                    )";	
		$db->query ( $sql, 'SILENT' );
		$error_no = $db->errno ();
		if ($error_no > 0 && $error_no != 1062) {
            $db->rollback();
            $result['err_no'] = 3;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
		}
	} while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据
   
      $batch_order_id = $db->insert_id();
    
     return $batch_order_id;
    }
								
?>
