<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Insert title here</title>
<link href="styles/treeView.css" rel="stylesheet" type="text/css" />
<script src="js/js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.treeview.js" type="text/javascript"></script>
<script type="text/javascript">
		$(document).ready(function(){
			$("#search").treeview({
				collapsed: true,
				animated: "medium",
				persist: "location"
			});
			$("#browser").treeview({
				collapsed: true,
				animated: "medium",
				persist: "location"
			});
		});
	</script>
</head>
<body>
<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('distribution.inc.php');
require_once ('includes/lib_category.php');
?>
<div id="tree">
<h4>产品目录      <a href='category_index.php?act=edit' target='edit_content'>添加顶级分类</a></h4>
<?php
	$party_id = $_SESSION ['party_id'];
	print  "<ul id='browser' class='filetree'>";
	print  read($party_id,-1);
	function read($party_id,$parent_id) {
		$cat_list = get_category_list_by_party_id ($party_id,$parent_id);
		foreach ($cat_list as $cate) {
			if ($cate ['has_children'] > 0) //如果是根节点
			{ 
				print "<li><span class='folder'><a href='category_index.php?act=edit&cat_id={$cate['cat_id']}' target='edit_content'>{$cate['cat_name']}</a></span>";
				print "<ul>";
				print  read($party_id,$cate['cat_id']);
				print  "</ul></li>";
			} 
			elseif ($cate ['has_children'] == 0) //如果是叶子
			{
				print "<li><a href='category_index.php?act=edit&cat_id={$cate['cat_id']}' target='edit_content'><span class='file'>{$cate['cat_name']}</span></a></li>";
			}
		}
	}
	print  "</ul>";
?>
</div>
</body>
</html>