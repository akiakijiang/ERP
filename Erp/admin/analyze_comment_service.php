<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");

$prefix = array('', '超', '相当','很','真','蛮','够','十分','太','好','挺');
$shipping_ok = array('满意', '快', '好', '感谢', '支持','不赖','赞','酷','一流','棒','非常','不错');
$shipping_justsoso = array('还', '可以', '一般', '不错');
$shipping_bad = array('坏','出错','慢','差','很烂','忍不住','无语','期待','能不能');



$service_ok = array('非常','好','支持','满意','效率高','感谢','值得推荐');
$service_justsoso =array('还', '可以', '一般', '不错');
$service_bad = array('期待','失望','越来越','少');

$price_ok = array('超低','赞','爽','货真价实','诚信','实惠');
$price_justsoso =array('还', '可以', '一般', '不错');
$price_bad = array('降价','补差价','更低','不实在');

$quality_ok = array('好','全','舒服','值得肯定','满意','漂亮','物美价廉');
$quality_justsoso =array('还', '可以', '一般', '不错');
$quality_bad = array('失望','烂','不好','不满意','无语','难受','不喜欢');

$design_ok = array('时尚','活力','青春','亲切','可爱','人性化');
$design_justsoso =array('还', '可以', '一般', '不错');
$design_bad = array('失望','烂','不好','不满意','无语','难受','不喜欢');

$stat = array();
foreach (array('shipping' => $shipping_ok, 'service' => $service_ok, 'price' => $price_ok, 'quality' => $quality_ok,  'design' => $design_ok) as $key=>$ss) {
  $temp = array();
  foreach ($prefix as $p) {
    foreach ($ss as $s) {
      $temp[] = $p.$s;
    }
  }
  $count[$key]['okstr'] = ($temp);
  $stat[$key]['okstr_count'] = 0;
}
  
  foreach (array('shipping' => $shipping_justsoso, 'service' => $service_justsoso, 'price' => $price_justsoso, 'quality' => $quality_justsoso,  'design' => $design_justsoso) as $key=>$ss) {
    $temp = array();
    foreach ($prefix as $p) {
    foreach ($ss as $s) {
      $temp[] = $p.$s;
    }
    }
    $count[$key]['justsosostr'] = $temp;
    $stat[$key]['justsosostr_count'] = 0;
  }
  
  foreach (array('shipping' => $shipping_bad, 'service' => $service_bad, 'price' => $price_bad, 'quality' => $quality_bad,  'design' => $design_bad) as $key=>$ss) {
    $temp = array();
    foreach ($prefix as $p) {
    foreach ($ss as $s) {
      $temp[] = $p.$s;
    }
    }
    $count[$key]['badstr'] = $temp;
    $stat[$key]['badstr_count'] = 0;
  }
  

//$shipping_ok_count = 0;
//$shipping_justsoso_count = 0;
//$shipping_bad_count = 0;
//
//$service_ok_count = 0;
//$service_justsoso_count = 0;
//$service_bad_count = 0;
//
//$design_ok_count = 0;
//$design_justsoso_count = 0;
//$design_bad_count = 0;

$comments = array(array('content'=>'很好'), array('content'=>'不好'));
$comments = $db->getAll(" SELECT content FROM {$ecs->table('after_order_comment')} WHERE user_type = 1 ");
foreach ($comments as $comment) {
  foreach ($count as $key => $str) {
    foreach ($str as $key2=>$ss) {
      foreach ($ss as $s) {
        if (strpos($comment['content'], $s) !== false) {
        	$stat[$key][$key2.'_count'] ++;
//        	if ($key2 == 'badstr') {
//        		print '--------------------'.$comment['content'].'-------------------s'.$s.'------------';
//        	}
//        	print $s;
        	break;
        }
      }
    }
  }  
}
pp($stat);
//pp($count);