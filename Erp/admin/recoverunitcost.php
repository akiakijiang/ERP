<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require_once('includes/lib_order.php');
include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH . "includes/lib_order.php");


//开始读取文件
if(!$fp=fopen("recoverunitcost.csv",r))
echo "读取文件失败<br>";
//读取文件的每一行
$total = 0;
while(!feof($fp)){
	$row[$total]=fgets($fp);
	$total++;
}
//关闭文件
fclose($fp); 

$comment = $row[0];
$colum = count(explode(",", $row[0]));
for($a=1;$a<$total;$a++) {
    $row[$a]=explode(",",$row[$a]); 
}

//die();
if (!($_SESSION['admin_name'] == "pgu" || $_SESSION['admin_name'] == 'zwsun')) {
    die();
}

for ($a = 1; $a < $total; $a++) {
    if ($colum != count($row[$a])) continue;

//    $sub_row = explode(",", $row[$a]);
//    pp($sub_row);
//    pp($row[$a]);
    recoverInventoryItemValueByRootInventoryItemId($row[$a][0], intval($row[$a][1]));
}

function  recoverInventoryItemValueByRootInventoryItemId($rootInventoruItemId, $unitCost) {
  global $soapclient;
  $keys = array('rootInventoruItemId'=>'StringValue', 'user'=>'StringValue', 'unitCost' => 'StringValue');
  $user = $_SESSION['admin_name'];
  $param = new HashMap();
  foreach ($keys as $key => $type) {
    if(${$key} === null) { continue; }
    $gv = new GenericValue();
    $method = 'set'.$type;
    $gv->$method(${$key});
    $param->put($key, $gv->getObject());
  }
  if($unitCost == 0) pp($param->getObject());
  $result = $soapclient->recoverInventoryItemValueByRootInventoryItemId(array('arg0'=>$param->getObject()));
  $return_hashmap = new HashMap();
  $return_hashmap->setObject($result->return);
  $status = $return_hashmap->get("status")->stringValue;
  pp($status);
}


