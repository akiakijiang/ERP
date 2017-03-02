<?php
/**
 * 咨询显示内容查看
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);

require_once('../includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('taobao_consult_shop_statistics', 'taobao_consult_sales_statistics');

$shop_id = isset($_GET['shop_id']) ? trim($_GET['shop_id']) : false ; 
$act = isset($_GET['act']) ? trim($_GET['act']) : false;
$start = $_GET['start'];
$end = $_GET['end'];

switch ($act) {
    /**
     * 最大响应时间
     */
    case 'max_respond' :
        $sql = "
            SELECT c.section_id, c.time
            FROM taobao_consulting_content AS c
                LEFT JOIN taobao_consulting_section AS s ON s.section_id = c.section_id
            WHERE s.taobao_shop_id = '{$shop_id}' AND (time BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
            ORDER BY `interval` DESC LIMIT 1
        ";
        $list[0] = $db->getRow($sql);
        if ($list[0]) {
            $list[0]['contents'] = $db->getAll("
                SELECT * FROM taobao_consulting_content WHERE section_id = '{$list[0]['section_id']}' ORDER BY `time` ASC 
            ");
        }
    break;
    
    /**
     * 响应时间大于3分钟的
     */
    case 'long_respond' :
    $sql = "
        SELECT DISTINCT c.section_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id  
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') AND 
            c.`interval` > 180 AND s.taobao_shop_id = '{$shop_id}'  -- 响应时间大于3分钟的
    ";
    $links = array(
        // 一条咨询章节对应多条咨询内容记录
        array(
	        'sql' => 'SELECT * FROM taobao_consulting_content WHERE :in ORDER BY `time` ASC',
	        'source_key' => 'section_id',
	        'target_key' => 'section_id',
	        'mapping_name' => 'contents',
	        'type' => 'HAS_MANY',
        ),
    );  
    $list = $db->findAll($sql, $links);
    break;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>咨询内容详细</title>
  <link href="../styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
  </style>
</head>
<body>


<div style="width:800px; margin:0 auto;">

<h3>聊天记录</h3>
<?php if (!empty($list)) : foreach ($list as $consulting) : ?> 
<table class="bWindow">
    <?php if (!empty($consulting['contents'])) : foreach ($consulting['contents'] as $item) : ?>
	<tr>
  		<td width="32%" <?php
  		    if ($item['type'] == 'REPLY') {
	  		    if (isset($consulting['time'])) {
	  		    	if ($consulting['time'] == $item['time']) {
	  		    	   print 'bgcolor="red"'; 
	  		    	}
	  		    } else {
	  		    	if ($item['interval'] > 180) {
	  		    		print 'bgcolor="red"';
	  		    	}
	  		    }
  		    }
  		?>>
            &nbsp;<strong><?php print $item['referee']; ?><?php print $item['replier']; ?></strong>
            (<?php print $item['time']; ?>)
        </td>
    	<td>&nbsp;&nbsp;<?php print $item['content']; ?></td>
	</tr>
    <?php endforeach; endif; ?>
<table>
<br />
<?php endforeach; endif; ?>

</div>
<br />
</body>
</html>
