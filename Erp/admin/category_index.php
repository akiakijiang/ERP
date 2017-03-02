<?php
	define('IN_ECS', true);
	require_once('includes/init.php');
	require_once('distribution.inc.php');
	
	require_once('includes/lib_category.php');
	require_once('includes/lib_goods.php');
	require_once('function.php');
	
	// 通用商品组织权限特殊判断 ljzhou 2013.07.03
	if(check_goods_common_party()) {
		admin_priv('category_edit_common');
	} else {
	    admin_priv('category_edit');
	}
	
	if ($_REQUEST['act'] == 'index')
	{
		$smarty->display('category_index.htm');
	}
	else if ($_REQUEST['act'] == 'start')
    {
    	$smarty->display('category_start.htm');
    }
    else if ($_REQUEST['act'] == 'edit')
    {
    	$party_id 		=$_SESSION ['party_id'];
    	$cat_id   		=$_REQUEST['cat_id'];
    	
    	$party      = getOnePartyName($party_id);
    	
    	if($cat_id!='')
    	{
	    	$check = checkCatParty($cat_id);
	    	$cat_info = getPartyNameCat($cat_id);
	    	
	    	if($check==-1)
	    	{
	    		echo "您现在选中是 <font color='red'> ".$cat_info."</font> 组织下的类,</br>请选当前组织  <font color='red'>".$party."</font> 下的类进行编辑!!!";
	    		return false;
	    	}
    	}

    	$cat_name   = mysql_fetch_row(getOneCatName($cat_id));
    	$parent_cat = getParentCatName($cat_id);
    	$can_delete = isCanDelete($cat_id);
    	
    	$smarty->assign('parent_cat',$parent_cat);
    	$smarty->assign('party',$party);
    	$smarty->assign('cat_id',$cat_id);
    	$smarty->assign('cat_name',$cat_name[0]);
    	$smarty->assign('can_delete',$can_delete);
    	
    	$smarty->display('category_add.htm');
    }
    else if ($_REQUEST['act'] == 'edit_cat')
    {
    	$party_id 		=$_SESSION ['party_id'];
    	$cat_id     	=$_POST['cat_id'];
    	$category_name	=$_POST['category_name'];
    	$cat_type		=$_POST['cat'];
    	
    	if($cat_type!=0)
    	{
	        $check=checkCatParty($cat_id);
	    	if($check==-1)
	    	{
	    		echo "请在您选的组织下,编辑类别!!!";
	    		return false;
	    	}
    	}
    	$res = add_cat($cat_id,$category_name,$cat_type,$party_id);
    	
    	if($res>0)
    	{
    		print "<script>alert('分类添加成功');</script>";
    		print "<script>window.parent.edit_cat_tree.location.reload();</script>";
			print "<script>window.location.href='category_index.php?act=edit&&cat_id=".$cat_id."';</script>";
			
    	}else if($res==-100)
    	{
    		print "<script>alert('此分类已经有商品,不能添加子类');</script>";
    		print "<script>window.location.href='category_index.php?act=edit&cat_id=".$cat_id."';</script>";
    	}else 
    	{
    		print "<script>alert('添加分类出错,请与ERP组联系');</script>";
    	}
    }
    else if ($_REQUEST['act'] == 'delete') {
    	$cat_id =$_REQUEST['cat_id'];
    	
		$check=checkCatParty($cat_id);
    	if($check==-1)
    	{
    		echo "请在您选的组织下,编辑类别!!!";
    		return false;
    	}
    	
    	$res = delete_cat($cat_id);
    	if ($res) {
    		print "<script>alert('分类删除成功');</script>";
    		print "<script>window.parent.edit_cat_tree.location.reload();</script>";
			print "<script>window.location.href='category_index.php?act=index';</script>";
    	} else {
    		print "<script>alert('删除分类出错,请与ERP组联系');</script>";
    	}
    }
?>








