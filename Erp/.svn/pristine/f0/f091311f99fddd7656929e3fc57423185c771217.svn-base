<?php 
    /*
     * 供应商详情维护
     * 专为leqee内贸直营店铺当月GMV统计报表维护一些字段用的 
     */
    define('IN_ECS', true);
    require_once('includes/init.php');
    admin_priv('distributor_info_manage');
    require_once('function.php');
    require_once(ROOT_PATH . 'includes/debug/lib_log.php');
    require_once(ROOT_PATH . 'includes/helper/uploader.php');
    
    global $db;
    $party_id =$_SESSION['party_id'];
    $sql = "select d.distributor_id,d.name,t.nick,di.brand_name,di.category_name,di.business_line,di.cooperation_model,di.platform 
        from ecshop.distributor d 
        left join ecshop.distributor_detail_info di on di.distributor_id = d.distributor_id
        left join ecshop.taobao_shop_conf t on t.distributor_id = d.distributor_id 
        where d.party_id = '{$party_id}' and d.status = 'NORMAL'
        ";
    $distributor_list = $db->getAll($sql);
    $smarty->assign('distributor_list',$distributor_list);
    
    $act = $_REQUEST['act'];
    if($act == 'add'){
        $distributor_id = $_REQUEST['distributor_id'];
        $platform = $_REQUEST['platform'];
        $business_line = $_REQUEST['business_line'];
        $brand_name = $_REQUEST['brand_name'];
        $cooperation_model = $_REQUEST['cooperation_model'];
        $category_name = $_REQUEST['category_name'];
        $add_result = updateDistributor($distributor_id,$platform,$business_line,$brand_name,$cooperation_model,$category_name);
        if($add_result){
            echo '<script>alert("修改成功！");location.href="distribution_info_manage.php"</script>';
        }else{
            echo '<script>alert("修改修改！");location.href="distribution_info_manage.php"</script>';
        }
    }else if($act == 'batch_edit_distributor'){
        $tpl = array('分销商详情修改'  =>
            array('distributor_id'=>'ERP分销商ID',
                'platform'=>'平台',
                'business_line'=>'业务线',
                'brand_name'=>'品牌',
                'cooperation_model'=>'合作模式',
                'category_name'=>'类目'
            ));
        @set_time_limit(300);
        $uploader = new Helper_Uploader();
        $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
        
        if (!$uploader->existsFile('excel')) {
            die('没有选择上传文件，或者文件上传失败');
        }
        
        // 取得要上传的文件句柄
        $file = $uploader->file('excel');
         
        // 检查上传文件
        if (!$file->isValid('xls, xlsx', $max_size)) {
            die('非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
        }
        
        // 读取excel
        $result = excel_read($file->filepath(), $tpl, $file->extname(), $failed);
        if (!empty($failed)) {
            die(reset($failed));
        }
        
        /* 检查数据  */
        $rowset = $result ['分销商详情修改'];
        
        // 订单数据读取失败
        if (empty($rowset)) {
            die('excel文件中没有”分销商详情修改“这个sheet');
        }
        
        $fail_distributors = '';
        foreach ($rowset as $key => $value){
            $res_update = updateDistributor($value['distributor_id'],$value['platform'],$value['business_line'],$value['brand_name'],$value['cooperation_model'],$value['category_name']);
            if(!$res_update){
                $fail_distributors .= "'{$value['distributor_id']}',";
            }
        }
        $smarty->assign('show_message','1');
        $smarty->assign('fail_distributors',$fail_distributors);     
    }
    
    
    function updateDistributor($distributor_id,$platform,$business_line,$brand_name,$cooperation_model,$category_name){
        if(isExistsDistributor($distributor_id)){
                $sql = "update ecshop.distributor_detail_info set platform = '{$platform}', business_line = '{$business_line}', brand_name = '{$brand_name}',
                    cooperation_model = '{$cooperation_model}', category_name = '{$category_name}', update_stamp = NOW()
                    where distributor_id = '{$distributor_id}'
                ";
            }else{
                $sql = "insert into ecshop.distributor_detail_info(distributor_id,brand_name,category_name,business_line,cooperation_model,platform,create_stamp,update_stamp)
                    values('{$distributor_id}','{$brand_name}','{$category_name}','{$business_line}','{$cooperation_model}','{$platform}',NOW(),NOW())
                ";
            }
         $res = $GLOBALS['db']->query($sql);
         return $res;
    }
    
    function isExistsDistributor($distributor_id){
        $sql = "select 1 from ecshop.distributor_detail_info where distributor_id = '{$distributor_id}'
            ";
        $res = $GLOBALS['db']->getAll($sql);
        if(empty($res)){
            return false;
        }else{
            return true;
        }
    }
    
    $smarty->display('distributor/distribution_info_manage.htm');
?>