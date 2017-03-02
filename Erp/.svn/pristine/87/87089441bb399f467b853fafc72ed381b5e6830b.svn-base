<?php
/**
 * oukoo[欧酷网]
 * 帮助系统函数库
 * @author :Tao Fei<ftao@oukoo.com>
 * @copyright oukoo<0.5>
*/

/**
 * 获得底部的帮助列表
 */
function get_footer_help_cats() {
  global $ecs, $db;
  $sql = "SELECT cat_name, cat_id FROM {$ecs->table('help_cat')} WHERE show_in_footer = 1  ORDER BY sort_order ";
  $help_cats = $db->getAll($sql);
  foreach($help_cats as $key => $value)
  {
    $sql = "SELECT title, type, help_id, url FROM {$ecs->table('help')} WHERE cat_id = {$value['cat_id']} AND show_in_footer = 1  ORDER BY sort_order";
    $help_cats[$key]['help_items'] = $db->getAll($sql);
  }
  return $help_cats;
}


/**
 * 获得分类列表
 */
function get_help_cats()
{
    $sql = sprintf("SELECT * FROM %s ORDER BY sort_order", $GLOBALS['ecs']->table('help_cat'));
    $help_cats = $GLOBALS['db']->getAll($sql);
    foreach($help_cats as $key => $value)
    {
        $help_cats[$key]['help_items'] = get_help_by_cat($value['cat_id']);
    }
//    pp($help_cats);
    return $help_cats;
}

function get_help_by_cat($cat_id)
{
    $sql = sprintf("SELECT * FROM %s WHERE cat_id = %s ORDER BY sort_order", $GLOBALS['ecs']->table('help'), $cat_id);
    $help_items = $GLOBALS['db']->getAll($sql);
    return $help_items;
}

function get_help_by_id($help_id)
{
    $sql = sprintf("SELECT * FROM %s WHERE help_id = %s", $GLOBALS['ecs']->table('help'), $help_id);
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 根据帮助id获得其子帮助
 *
 * @param int $help_id 帮助id
 * @param int $limit 限制的条数
 * @param int $offset 开始的位置
 * @return array 子帮助数组
 */
function get_sub_help($help_id, $limit, $offset) {
  $sql = sprintf("SELECT title,help_id FROM %s WHERE parent_id = %d AND help_type='LIST' ORDER BY sort_order", $GLOBALS['ecs']->table('help'), $help_id);
  $sub_help_tab = $GLOBALS['db']->getAll($sql);
//  pp($sub_help_tab);
  if($sub_help_tab){
	  foreach ($sub_help_tab as $tab){
	    $sql = sprintf("SELECT COUNT(*) FROM %s WHERE parent_id = %d ", $GLOBALS['ecs']->table('help'), $tab['help_id']);
	    $total= $GLOBALS['db']->getOne($sql);
	    if($total > 0){
	      $newarr[] = $tab;   
	    }
	  }
	  $sub_help_tab = $newarr;  	
    $tmp = array('title' => '所有', 'help_id' => $help_id);
    @array_unshift($sub_help_tab,$tmp);
    $sql = sprintf("SELECT COUNT(*) FROM %s WHERE parent_help_id = %d ", $GLOBALS['ecs']->table('help'), $help_id);
    $total_list = $GLOBALS['db']->getOne($sql);  
    $sql = sprintf("SELECT title,help_id FROM %s WHERE parent_help_id = %d ORDER BY sort_order LIMIT %d OFFSET %d", $GLOBALS['ecs']->table('help'), $help_id, $limit, $offset);
    $sub_help_list = $GLOBALS['db']->getAll($sql);     
  }else{
  	$sql = sprintf("SELECT title,help_id,parent_help_id FROM %s WHERE parent_id = %d AND help_type='CONTENT' ORDER BY sort_order", $GLOBALS['ecs']->table('help'), $help_id);
  	$sub_help_list = $GLOBALS['db']->getAll($sql);
    $sql = sprintf("SELECT COUNT(*) FROM %s WHERE  parent_id = %d AND help_type='CONTENT' ", $GLOBALS['ecs']->table('help'), $help_id);
    $total_list = $GLOBALS['db']->getOne($sql);
    $sql = "SELECT parent_id, parent_help_id FROM {$GLOBALS['ecs']->table('help')} WHERE help_id = '{$help_id}'";
    $help = $GLOBALS['db']->getRow($sql);
    if($help['parent_help_id']){
      $sql = sprintf("SELECT title,help_id FROM %s WHERE parent_id = %d AND help_type='LIST' ORDER BY sort_order", $GLOBALS['ecs']->table('help'), $help['parent_id']);
      $sub_help_tab = $GLOBALS['db']->getAll($sql);
	    foreach ($sub_help_tab as $tab){
	      $sql = sprintf("SELECT COUNT(*) FROM %s WHERE parent_id = %d ", $GLOBALS['ecs']->table('help'), $tab['help_id']);
	      $total= $GLOBALS['db']->getOne($sql);
	      if($total > 0){
	        $newarr[] = $tab;   
	      }
	    }
	    $sub_help_tab = $newarr;       
      $tmp = array('title' => '所有', 'help_id' => $help['parent_id']);
      @array_unshift($sub_help_tab,$tmp);             
    }
  }
  return array('tab' => $sub_help_tab , 'total' => $total_list, 'list' => $sub_help_list);
}

function get_help_by_title($title)
{
    $sql = sprintf("SELECT * FROM %s WHERE title = '%s'", 
        $GLOBALS['ecs']->table('help'), 
        $GLOBALS['db']->escape_string($title)
    );
    return $GLOBALS['db']->getRow($sql);
}

function to_orginal_help_url($url)
{
    global $WEB_ROOT;
    if (strpos($url, "http") !== False)
    {
       return $url; 
    }
    else
    {
        return $WEB_ROOT . $url;
    }
}
?>
