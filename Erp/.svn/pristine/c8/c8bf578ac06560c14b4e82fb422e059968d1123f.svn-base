<?php 

/**
 * 多美滋特殊订单导入
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
/*if(!in_array($_SESSION['admin_name'],array('jjhe','ychen','shyuan','zwsun','lchen', 'lwang', 'ygu', 'fzou', 'lqlu', 'qju')))
{
	die('没有权限');
}*/
admin_priv('dumex_order_import');
require_once('function.php');
require_once(ROOT_PATH.  'includes/lib_order.php');
//require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');

require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'admin/distribution_order_address_analyze.php');
if ($_SESSION['party_id'] != 65540) {
    sys_msg('请选择多美滋组织');
}

// 信息
if ($info) {
    $smarty->assign('message', $info);
}


 // 当前时间 
 $now = date('Y-m-d H:i:s');

 $taocan_list = array(
    '61' => array(
            '24539621M' => array(
                       'goods_number' => 3,
                       'goods_price' => 128.00,
                     ),
            '24539621G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '28' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539570M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
          ),
    '201' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539571M' => array(
                       'goods_number' => 3,
                       'goods_price' => 54.00,
                     ),
          ),
    '27' => array(
            '24539571G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539570M' => array(
                       'goods_number' => 2,
                       'goods_price' => 179.00,
                     ),
          ),
    '15' => array(
            '24600818M' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),
            '24539573G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '16' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539575G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '17' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539619M' => array(
                       'goods_number' => 1,
                       'goods_price' => 33.00,
                     ),
            '24539575G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),
    '18' => array(
            '24539619G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
            '24539617M' => array(
                       'goods_number' => 1,
                       'goods_price' => 189.00,
                     ),
          ),
    '224' => array(
            '24539574M' => array(
                       'goods_number' => 3,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ),          
    '223' => array(
            '24539574M' => array(
                       'goods_number' => 6,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 2,
                       'goods_price' => 0.00,
                     ),
          ), 
    '222' => array(
            '24600818M' => array(
                       'goods_number' => 3,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '221' => array(
            '24600818M' => array(
                       'goods_number' => 6,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 2,
                       'goods_price' => 0.00,
                     ),
          ), 
    '182' => array(
            '24539574M' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '181' => array(
            '24600818M' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '162' => array(
            '24539574M' => array(
                       'goods_number' => 2,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '161' => array(
            '24600818M' => array(
                       'goods_number' => 2,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '144' => array(
            '24539574M' => array(
                       'goods_number' => 3,
                       'goods_price' => 179.00,
                     ),
            '24539574G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '143' => array(
            '24600818M' => array(
                       'goods_number' => 3,
                       'goods_price' => 199.00,
                     ),
            '24600818G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '121' => array(
            '24539620M' => array(
                       'goods_number' => 3,
                       'goods_price' => 248.00,
                     ),
            '24539620G' => array(
                       'goods_number' => 1,
                       'goods_price' => 0.00,
                     ),
          ), 
    '21' => array(
            '24539621' => array(
                       'goods_number' => 1,
                       'goods_price' => 128.00,
                     ),    
          ),
    '22' => array(
            '24502600' => array(
                       'goods_number' => 1,
                       'goods_price' => 98.00,
                     ),    
          ),
    '23' => array(
            '24539620' => array(
                       'goods_number' => 1,
                       'goods_price' => 248.00,
                     ),    
          ),
    '25' => array(
            '24539571' => array(
                       'goods_number' => 1,
                       'goods_price' => 54.00,
                     ),    
          ),
    '24' => array(
            '24539570' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),    
          ),
    '4' => array(
            '24539573' => array(
                       'goods_number' => 1,
                       'goods_price' => 82.00,
                     ),    
          ),
    '9' => array(
            '24600818' => array(
                       'goods_number' => 1,
                       'goods_price' => 199.00,
                     ),    
          ),
    '5' => array(
            '24539575' => array(
                       'goods_number' => 1,
                       'goods_price' => 69.00,
                     ),    
          ),
    '10' => array(
            '24539574' => array(
                       'goods_number' => 1,
                       'goods_price' => 179.00,
                     ),    
          ),
    '7' => array(
            '24539609' => array(
                       'goods_number' => 1,
                       'goods_price' => 58.00,
                     ),    
          ),
    '6' => array(
            '24539602' => array(
                       'goods_number' => 1,
                       'goods_price' => 159.00,
                     ),    
          ),
    '8' => array(
            '24539619' => array(
                       'goods_number' => 1,
                       'goods_price' => 66.00,
                     ),    
          ),
    '11' => array(
            '24539617' => array(
                       'goods_number' => 1,
                       'goods_price' => 189.00,
                     ),    
          ),
   ) ;


 /*
  * 处理post请求
  */
 if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['act'] == 'upload') {
            require_once(ROOT_PATH.'admin/includes/cls_message.php');
            $message_url = trim(MESSAGE_URL);
            $message_serialnumber = trim(MESSAGE_SERIALNUMBER);
            $message_password = trim(MESSAGE_PASSWORD);
            $message_sessionkey = trim(MESSAGE_SESSIONKEY);
            $MessageReplyClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
            $MessageReplyClient->setOutgoingEncoding("UTF-8");       
           /**
            * 上传文件， 检查上传的excel格式，并读取数据提取插入到临时表 
            */
            if (!lock_acquire('distribution_order_import-upload')) {
                $smarty->assign('message', '导入操作正在被执行，请稍后执行'); 
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }

            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('excel')) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('excel');

            // 检查上传文件
            if (!$file->isValid('csv', $max_size)) {
                $smarty->assign('message', '非法的文件! 请检查文件类型类型(csv), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }
            
            // 福建、广西、湖南、海南、重庆、云南、贵州、四川、广东 订单转到东莞仓
            $zhuanDongGuanCang = array(14, 19, 22, 23, 24, 25, 26, 21, 20) ;
            // 短信发送地区改为全国
            //$region_list = array(10, 11, 12, 13, 14, 15, 16, 18, 19, 20, 21);
            //读取短信模板
            $sql = "select template_content from ecshop.ecs_msg_templates where template_code = 'dumex_cod_order_import'";
            $content = $db->getOne($sql);
            // 读取数据
            $imported_orders = array() ;
            $exists_taobao_order_sn = array() ;
            $error_msg = array();
            $batch_no = strtoupper($_SESSION['admin_name']) . '-'. time(); // 批次号
            $goodscode = null;
            $order_info = array(); 
            $goods_info = array();   
            $line_number = 0;
            if (($handle = fopen($file->filepath(), "r")) !== FALSE) {
               	 while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                      if(0 == $line_number){
                          $line_number ++ ;  
                          continue;
                      }
                      
                      $taobao_order_sn = $data[0];
                      $distribution_purchase_order_sn = 'dumex-'.$data[0];
                      $consignee = $data[1];
                      $mobile = $data[2];
                      $province = $data[3];
                      $city = $data[4];
                      $district = $data[5];
                      $address = $data[6];
                      $order_amount = $data[7];
                      $goods = $data[9];
                      $postscript = $data[12].' '.$data[13];
                      
                      
                      // 检查重复订单
                      $checkResult = $db->getRow(sprintf("select 1 from ecshop.ecs_order_info where taobao_order_sn = '%s'", $taobao_order_sn));
                      if(!empty($checkResult)){
                          $exists_taobao_order_sn[] = $data[0] ;
                          $line_number ++ ;  
                          continue;
                      }
                      
                      
                      
                      // 填充数据信息
                      $order_info = array(
                              'taobao_order_sn' => $taobao_order_sn,
                              'distribution_purchase_order_sn' => $distribution_purchase_order_sn,
                              'consignee' => $consignee,
                              'mobile' => $mobile,
                              'order_amount' => $order_amount,
                              'distributor_id' => 164,
                              'party_id' => 65540,
                              'order_time' => $now,
                              'order_status' => 0,
                              'shipping_status' => 0,
                              'pay_status' => 0,
                              'shipping_id' => 11,
                              'shipping_name' => '宅急送快递_货到付款',
                              'shipping_fee' => 0,
                              'shipping_proxy_fee' => 0,
                              'pay_id' => 1 ,             // 支付方式都是宅急送
                              'pay_name' => '货到付款',
                              'goods_amount' => 0,
                              'order_amount' => $order_amount,
                              'carrier_bill_id' => '',
                              'order_type_id' => 'SALE', 
                              // 'facility_id' => '22143847',
                              'postscript' => $postscript,
                              'user_id' => 1,                   // 指定用户 
                              'pack_fee' => 0,          // 包装费
                            );
                      
                      // 分析订单地址, 将取得订单的 province, city, district, address
                      $order_info = array_merge($order_info, (array)distribution_order_address_analyze($province . $city . $district . $address));
                      // 广东地区的所有COD订单导到东莞仓，其他区域还是留在杭州仓操作
                      if (in_array(intval($order_info['province']), $zhuanDongGuanCang)) {
                      	  $order_info['facility_id'] = '19568548' ;     // 电商服务东莞仓
                      } else {
                      	  $order_info['facility_id'] = '22143847' ;     // 电商服务杭州仓
                      }
                      // 添加商品
                      $order_goods = $goods_item = array();
                      $goodsList = array();
                      $goodsList = preg_split('/，/', $goods);      // 套餐、商品之间全角逗号分隔
                      foreach($goodsList as $goodsitem){
                          // 解析出商品、及数量
                          $goods_number = intval(substr(strrchr($goodsitem, '*'), 1));
                          $goods_code = substr($goodsitem, 0, strpos($goodsitem, '_'));
                      
                          $taocan_item = $taocan_list[$goods_code];
                          if(empty($taocan_item)){
                              $error_msg[] = '套餐：'.$goods.' 还没有添加到配置文件内，请联系ERP组，，，';
                      	      continue ;
                          }
                      
                          
                          foreach((array)$taocan_item as $key => $item){
                      	       $goods_style = getGoodsIdStyleIdByProductId(intval($key));
                      	       $goods_item['goods_id'] = $goods_style['goods_id'];
                      	       $goods_item['style_id'] = $goods_style['style_id'];
                      	       $goods_item['price'] = $item['goods_price'];
                      	       $goods_item['goods_number'] = $item['goods_number'] * $goods_number ;
                      	   
                        	   $order_goods[] = $goods_item;
                      	       
                          }
                      	
                      }
                     
                      // 订单导入
                      $carrier_id = 15 ;
                      $order_type = 'dumex_cod_import';             // 多美滋货到付款
                      $message = '';
                      $osn = distribution_generate_sale_order($order_info, $order_goods, $carrier_id, $order_type, $message);
                      if(empty($osn['error'])) {
				      	  	$order_sn = $osn['order_sn'];
				      } else {
				      		$order_sn = false;
				      }
                      //添加发送短信
                      if( !empty($order_info['mobile']) && $order_info['party_id'] == 65540 && $GLOBALS['_CFG']['sms_cod_message'] == '1'){
                          try {
                              //if (in_array($order_info['province'],$region_list)) {
                              //$content = "亲爱的客户，您在多美滋关爱中心订购的奶粉即将安排发货，若您需要奶粉，请短信回复Y；若不需要，请回复N。有问题电联0571-28280632";
                              $mobile = array();
                              $mobile[0] = $order_info['mobile'];
                              $send_result = $MessageReplyClient->sendSMS($mobile, $content);
                              $sql = "INSERT INTO message.message_history (history_id, result, type, send_time, dest_mobile, user_id, content, server_name) VALUES (null,'{$send_result}','SINGLE','".date('Y-m-d H:i:s')."','{$order_info['mobile']}','1','{$content}','emay')";
                              $db->query($sql);
                              $sql = "select order_id,order_status,pay_status,shipping_status from ecshop.ecs_order_info where order_sn = '{$order_sn}' limit 1";
                              $order = $db->getRow($sql);
                              //如果短线发送成功 记录 到order_action 记录短信
                              if($send_result === "0"){
                                  $action = array(
                               	      'order_id' => $order['order_id'],
                                      'action_user' => 'system',
                                      'shipping_status' => $order['shipping_status'],
                                      'order_status' =>  $order['order_status'],
                                      'pay_status' => $order['pay_status'],
                                      'action_time' => date("Y-m-d H:i:s"),
                                      'invoice_status' => 0,
                                      'action_note' => '订单短信已发送',
                                      'note_type' => 'message'
                                  );
                                  $db->autoExecute($ecs->table('order_action'), $action);
                                  // update_order_mixed_status($order_id, array('order_status' => '{$order_status}'), 'system');
                              }
                              //}
                          }catch (Exception $e) {

                          }
                      }
                      if ($order_sn !== false) {
                          $imported_orders[] = $order_sn ;
                          $order_id = $db->getRow(sprintf("select order_id from ecshop.ecs_order_info where order_sn = '%s' limit 1 ", $order_sn));
                           
                          $db->query(sprintf("insert into ecshop.order_attribute (order_id, attr_name, attr_value) VALUES ( %d, 'OUTER_TYPE', 'dumex_cod_import')", intval($order_id['order_id'])));
                      }
               	 }
            }
            // 关掉文件
            fclose($handle);
            
            
            // 删除上传的文件
            $file->unlink();
            if ($exists_taobao_order_sn) {
                $smarty->assign('message', "成功导入订单数：". count($imported_orders) ."。该文件中有部分订单已经导入过了, 重复的订单编号: ". implode('， ', $exists_taobao_order_sn)); 
            } else {
                $smarty->assign('message', "数据导入成功。本次导入订单数：". count($imported_orders) .", 请仔细核对结果: ");
            }

            // 释放锁
            lock_release('distribution_order_import-upload');
            
            
            if (!empty($imported_orders)) {
                $smarty->assign('message', "成功生成订单, 请见下表，请修改订单的配送地址和配送方式等");
                // 查询出已生成订单列表
                $sql = "
                    select o.order_id, o.order_sn, o.order_amount, o.taobao_order_sn
                      from ecshop.ecs_order_info o 
                    where o.order_sn ". db_create_in($imported_orders);
                $imported_list = $db->getAll($sql);
                $smarty->assign('imported_list', $imported_list);
            } else {
                $smarty->assign('message', '没有订单生成。'. $message);
            }
            
            if(!empty($error_msg)){
            	$smarty->assign('message', implode('<br/>', $error_msg));
            }
 }
        


$smarty->display('distributor/dumex_taocan_order_import.htm');

