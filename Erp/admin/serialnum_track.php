<?php
/**
 * 串号跟踪
 * 
 * $Id: serialnum_track.php 27232 2011-05-13 04:34:21Z zwsun $
 * 
 * @author yxiang@leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('serialnum_track');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once('includes/lib_product_code.php');

// act
$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array(
    'generator', 
    'generate', 
    'print', 
    'search', 
    'snti_h'
)) ? $_REQUEST['act'] : 'list';
// 消息
$info = isset($_REQUEST['info']) && trim($_REQUEST['info']) ? $_REQUEST['info'] : false;

if ($info) {
    $smarty->assign('message', $info);
}

// 串号生成
if ($act == 'generate') {
    if (lock_acquire('serialnum_generate')) {
        do {
            // 去掉输入的空白
            Helper_Array::removeEmpty($_POST,true);
            
            if (empty($_POST['serialnum_track'])) {
                $smarty->assign('message', '非法请求');
                break;
            }
            
            $serialnum_track = $_POST['serialnum_track'];
            
            if (!is_numeric($serialnum_track['quantity']) || $serialnum_track['quantity'] < 1) {
                $smarty->assign('message', '请输入正确的数量');
                break;
            }
            
            $exists_track_number = $db->getOne("select track_number from serialnum_track where track_number = '{$serialnum_track['track_number']}' LIMIT 1");
            if ($exists_track_number) {
                $smarty->assign('message', "跟踪码{$exists_track_number}已经存在！请确认后重试");
                break;
            }
            
            // 查询产品
            if ($serialnum_track['style_id'] > 0) {
                $goods = $db->getRow(sprintf("select g.goods_party_id, concat_ws(' ',g.goods_name,IF(gs.goods_color = '', s.color, gs.goods_color)) as goods_name from ecs_goods as g left join ecs_goods_style as gs on gs.goods_id=g.goods_id left join ecs_style s on s.style_id = gs.style_id where g.goods_id = %d and s.style_id=%d", $serialnum_track['goods_id'], $serialnum_track['style_id']));
            } elseif ($serialnum_track['style_id'] == 0) {
                $goods = $db->getRow(sprintf("select goods_party_id, goods_name from ecs_goods where goods_id=%d", $serialnum_track['goods_id']));
            }
            if (!$goods) {
                $smarty->assign('message', '该产品不存在');
                break;
            }
            
            // 插入数据
            $timestamp = date('Y-m-d H:i:s');
            $sql = "insert into serialnum_track (party_id,track_number,goods_id,style_id,goods_name,quantity,production_date,created_timestamp,updated_timestamp) values ";
            $sql .= "({$goods['goods_party_id']},'{$serialnum_track['track_number']}', '{$serialnum_track['goods_id']}', '{$serialnum_track['style_id']}', '{$goods['goods_name']}', '{$serialnum_track['quantity']}', '{$serialnum_track['production_date']}', '$timestamp','$timestamp')";
            $result = $db->query($sql, 'SILENT');
            if ($result) {
                $serialnum_track_id = $db->insert_id();
                $sql_item = "insert into serialnum_track_item (serialnum_track_id,serial_number,created_timestamp,updated_timestamp) values ";
                $base_serial_number = 'D' . time();
                for ($i = 1; $i <= $serialnum_track['quantity']; $i++) {
                    $serialnum_track_items[] = "($serialnum_track_id,'" . ($base_serial_number . sprintf('%02d', $i)) . "','$timestamp','$timestamp')";
                }
                $sql_item .= implode(',', $serialnum_track_items);
                if (!$db->query($sql_item, 'SILENT')) {
                    // 生成失败
                    $db->query("delete from serialnum_track where serialnum_track_id = $serialnum_track_id");
                    $smarty->assign('message', '生成失败');
                    break;
                }
            } else {
                $smarty->assign('message', '生成失败');
                break;
            }
            
            lock_release('serialnum_generate');
            header("Location: serialnum_track.php?act=generator&info=" . urlencode("串号已经生成，可以去查询页打印"));
            exit();
        } while (false);
        lock_release('serialnum_generate');
        $smarty->assign('serialnum_track', $serialnum_track);
        $smarty->display('oukooext/serialnum_track_generator.htm');
    } else {
        header("Location: serialnum_track.php?act=generator&info=" . urlencode("正在执行串号生成，请稍候再试"));
        exit();
    }
} 

// 串号生成页面
elseif ($act == 'generator') {
    $smarty->display('oukooext/serialnum_track_generator.htm');
} 

// 打印页面
elseif ($act == 'print') {
    // id
    $serialnum_track_id = isset($_REQUEST['serialnum_track_id']) && is_numeric($_REQUEST['serialnum_track_id']) ? $_REQUEST['serialnum_track_id'] : false;
    if ($serialnum_track_id) {
        $serialnum_track = $db->getRow(sprintf("select * from serialnum_track where serialnum_track_id=%d", $serialnum_track_id));
        $serialnum_track['item_list'] = $db->getAll(sprintf("select * from serialnum_track_item where serialnum_track_id=%d", $serialnum_track_id));
        if ($serialnum_track && is_array($serialnum_track['item_list'])) {
            foreach ($serialnum_track['item_list'] as &$item) {
                $item['code_width'] = max(240 + (str_len($item['serial_number']) - 10) * 30, 150);
            }
            $smarty->assign('serialnum_track', $serialnum_track);
            $smarty->display('oukooext/serialnum_track_print.htm');
        }
    }
} 

// {{{ by Zandy at 2010.12
elseif ($act == 'snti_h') {
    $snti = $_POST['snti'];
    $printer_id = $_REQUEST['printer_id'];
    
    foreach ($snti as $track_number => $item_list) {
        $sql = "
        	SELECT party_id 
        	FROM ecshop.serialnum_track 
        	WHERE track_number = '$track_number' 
        	LIMIT 1 ";
        $serialnum_track = $db->getRow($sql);
        foreach ($item_list as $serial_number) {
            /*
            $data = array(
                'party_id' => $serialnum_track['party_id'], 
                'code' => $serial_number, 
                'amount' => intval($_POST["printnum_{$track_number}_{$serial_number}"])
            );
            if ($data['amount'] > 0) {
                $db->insert('ecshop.print_serial_number', $data);
            }
            */
            
            $amount = intval($_POST["printnum_{$track_number}_{$serial_number}"]);
            $party_id = $serialnum_track['party_id'];

            print_product_code(0, $serial_number, $amount, 0, $printer_id, '', $party_id);
        }
    }
    $back = $_REQUEST['back'] ? $_REQUEST['back'] : '?';
    $msg = '已加入打印队列。';
    echo <<<aaa
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript">
    alert('$msg');
	location.href = '$back';
    </script>
aaa;
    die();
} // }}}


// 默认页面 
else {
    require_once (ROOT_PATH . 'includes/cls_page.php');
    
    // 当前页码
    $page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;
    
    // 查询关键字
    $keyword = !empty($_REQUEST['keyword']) && trim($_REQUEST['keyword']) ? $_REQUEST['keyword'] : false;
    
    $extra_params = array();
    if ($keyword) {
        $extra_params['keyword'] = $keyword;
    }
    
    // 按分页取得列表
    if ($keyword) {
        $sql_count = "select count(serialnum_track_id) from serialnum_track where " . party_sql('party_id') . " and track_number='" . $keyword . "'";
    } else {
        $sql_count = "select count(serialnum_track_id) from serialnum_track where " . party_sql('party_id');
    }
    $total = $db->getOne($sql_count); // 总数
    $page_size = 15; // 每页数量
    $total_page = ceil($total / $page_size); // 总页数
    $page = max(1, min($page, $total_page)); // 当前页
    $offset = ($page - 1) * $page_size;
    $limit = $page_size;
    if ($keyword) {
        $sql_list = "select * from serialnum_track where " . party_sql('party_id') . " and track_number='" . $keyword . "' order by serialnum_track_id DESC";
    } else {
        $sql_list = "select * from serialnum_track where " . party_sql('party_id') . " order by serialnum_track_id DESC";
    }
    $list = $db->getAll($sql_list);
    
    // 分页
    $pagination = new Pagination($total, $page_size, $page, 'page', $url = 'serialnum_track.php', null, $extra_params);
    
    // {{{ by Zandy at 2010.12
    if ($keyword && $total > 0) {
        $serialnum_track = $list[0];
        $serialnum_track_id = $serialnum_track['serialnum_track_id'];
        $serialnum_track['item_list'] = $db->getAll(sprintf("select * from serialnum_track_item where serialnum_track_id=%d", $serialnum_track_id));
        if ($serialnum_track && is_array($serialnum_track['item_list'])) {
            foreach ($serialnum_track['item_list'] as &$item) {
                $item['code_width'] = max(240 + (str_len($item['serial_number']) - 10) * 30, 150);
            }
        }
        // 上一个，下一个
        $_prev = $db->getOne("SELECT track_number FROM ecshop.serialnum_track WHERE serialnum_track_id > '{$serialnum_track['serialnum_track_id']}' ORDER BY serialnum_track_id ASC LIMIT 1 ");
        $_next = $db->getOne("SELECT track_number FROM ecshop.serialnum_track WHERE serialnum_track_id < '{$serialnum_track['serialnum_track_id']}' ORDER BY serialnum_track_id DESC LIMIT 1 ");
        $serialnum_track['prev'] = $_prev;
        $serialnum_track['next'] = $_next;
        $smarty->assign('serialnum_track', $serialnum_track);
    }
    // }}}
    
    $smarty->assign('printers', get_serial_printers());
    $smarty->assign('serialnum_track_list', $list);
    $smarty->display('oukooext/serialnum_track.htm');
}
