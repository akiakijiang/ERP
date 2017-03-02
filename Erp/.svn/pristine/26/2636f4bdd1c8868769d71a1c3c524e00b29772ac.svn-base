<?php 
/*
 * 店铺账号档案
 */  
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../includes/lib_main.php');
admin_priv('shop_distributor_manage');
require_once('../function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

global $db;
$party_id = $_SESSION['party_id'];
$shop_type_list=array(
    'taobao' => '淘宝',
    '360buy' => '京东',
    '360buy_overseas' => '京东境外',
    'yhd' => '一号店',
    'vipshop' => '唯品会',
    'miya' => '蜜芽',
    'suning' => '苏宁',
    'weixin' => '微信',
    'weigou' => '微购',
    'ChinaMobile' => '积分商城',
    'jumei' => '聚美',
    'sfhk' => '顺丰优选',
    'amazon' => '亚马逊',
    'scn' => '名鞋库',
    'weixinqs' => '惠氏微信',
    'koudaitong' => '口袋通',
    'weixinjf' => '微信人头马',
    'baidumall' => '百度Mall',
    'budweiser' => '百威礼物社交',
    'pinduoduo' => '拼多多',
    'cuntao' => '村淘'
);
$act = $_REQUEST['act'];
$sql = "select c.taobao_shop_conf_id,c.nick,c.shop_type,c.shop_account,c.currency,group_concat(p.provider_id) as provider_ids,group_concat(p.provider_name) as provider_names  
    from ecshop.taobao_shop_conf c 
    left join ecshop.shop_provider_mapping spm on spm.taobao_shop_id = c.taobao_shop_conf_id and spm.status = 'NORMAL' 
    left join ecshop.ecs_provider p on p.provider_id = spm.provider_id 
    where c.party_id = '{$party_id}' and c.status = 'OK' 
    group by c.taobao_shop_conf_id 
    ";
$shop_list = $db->getAll($sql);

foreach($shop_list as $key =>$value){
    $shop_list[$key]['shop_type'] = $shop_type_list[$shop_list[$key]['shop_type']];
    $provider_ids = explode(',',$shop_list[$key]['provider_ids']);
    $provider_names = explode(',',$shop_list[$key]['provider_names']);
    $counts = count($provider_ids);
    for($i=0;$i<$counts;$i++){
        $shop_list[$key]['provider_list'][$i]['provider_id'] = $provider_ids[$i];
        $shop_list[$key]['provider_list'][$i]['provider_name'] = $provider_names[$i];
    }  
}

if($act == 'add_provider'){
    $shop_id = $_REQUEST['shop_id'];
    $shop_account = $_REQUEST['shop_account'];
    $currency = $_REQUEST['currency'];
    $provider_id = $_REQUEST['provider_id'];
    $db->start_transaction();
    $sql_update = " update ecshop.taobao_shop_conf set shop_account = '{$shop_account}', currency = '{$currency}' where taobao_shop_conf_id = '{$shop_id}' 
        ";
    $sql = "select 1 from ecshop.shop_provider_mapping where taobao_shop_id = '{$shop_id}' and provider_id = '{$provider_id}'";
    $res_sql = $db->getAll($sql);
    if(empty($res_sql)){
        $sql_insert = " insert into ecshop.shop_provider_mapping(mapping_id,taobao_shop_id,provider_id,status) values(null,".$shop_id.",".$provider_id.",'NORMAL')
            ";
    }else{
        $sql_insert = "update ecshop.shop_provider_mapping set status = 'NORMAL' where taobao_shop_id = '{$shop_id}' and provider_id = '{$provider_id}' limit 1
            ";
    }
    Qlog::log($sql_insert);
    try {
        $res_update = $db->exec($sql_update);
        $res_insert = $db->exec($sql_insert);
        if(empty($res_update)){
            throw new Exception("更新店铺信息失败了！");
        }
        if(empty($res_insert)){
            throw new Exception("更新供应商信息失败了！");
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        $data['message'] = $e->getMessage();
        $data['value'] = 0;
        die(json_encode($data));
    }
    $data['message'] = "添加成功！";
    $data['value'] = 1;
    die(json_encode($data));
}

if($act == 'delete_provider'){
    $shop_id = $_REQUEST['shop_id'];
    $provider_id = $_REQUEST['provider_id'];
    $db->start_transaction();
    $sql_delete=" update ecshop.shop_provider_mapping set status = 'DELETE' where taobao_shop_id = '{$shop_id}' and provider_id = '{$provider_id}' limit 1 ";
    Qlog::log($sql_delete);
    try {
        $res_delete = $db->exec($sql_delete);
        if(empty($res_delete)){
            throw new Exception("删除供应商失败了！");
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        $data['message'] = $e->getMessage();
        $data['value'] = 0;
        die(json_encode($data));
    }
    $data['message'] = "删除成功！";
    $data['value'] = 1;
    die(json_encode($data));
}

$currencies = (is_kuajing_party($party_id)) ? get_currency_style() : (($_SESSION['party_id']=='65536')?array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币'):array('RMB' => '人民币'));
$smarty->assign('currencies', $currencies);
$smarty->assign("shop_list",$shop_list);
$smarty->display('oukooext/shop_distributor_info.htm');
?>