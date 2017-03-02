<?php

/**
 * ECSHOP 动态内容函数库
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-16 17:48:54 +0800 (星期三, 16 五月 2007) $
 * $Id: lib_insert.php 8644 2007-05-16 09:48:54Z paulgao $
*/

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function insert_query_info()
{
	if ($GLOBALS['db']->queryTime == '')
	{
		$query_time = 0;
	}
	else
	{
		list($now_usec, $now_sec)     = explode(' ', microtime());
		list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);

		$query_time = ($now_sec - $start_sec) + ($now_usec - $start_usec);
		$query_time = number_format($query_time, 6);
	}

	/* 是否启用了 gzip */
	$gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];

	/* 内存占用情况 */
	if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
	{
		$memory_usage = sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1024 / 1024);
	}
	else
	{
		$memory_usage = '';
	}

	return sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time) . $gzip_enabled . $memory_usage;
}

/**
 * 调用浏览历史
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
	$str = '';

	if (!empty($_COOKIE['ECS']['history']))
	{
		$where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
		$sql   = 'SELECT goods_id, goods_name FROM ' . $GLOBALS['ecs']->table('goods') .
		" WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
		$query = $GLOBALS['db']->query($sql);

		$res = array();
		while ($row = $GLOBALS['db']->fetch_array($query))
		{
			$res[$row['goods_id']] = $row;
		}

		$tureorder = explode(',', $_COOKIE['ECS']['history']);

		foreach ($tureorder AS $key => $val)
		{
			$goods_name = htmlspecialchars($res[$val]['goods_name']);
			if ($goods_name)
			{
				$short_name = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($goods_name, 0,
				$GLOBALS['_CFG']['goods_name_length']) : $goods_name;
				$str .= '<li><a href="' . build_uri('goods', array('gid' => $val), $goods_name). '" title="' .
				$goods_name . '">' . $short_name . '</a></li>';
			}
		}
	}

	return $str;
}

/**
 * 调用购物车信息
 *
 * @access  public
 * @return  string
 */
function insert_cart_info()
{
	global $spath;
	$sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
	' FROM ' . $GLOBALS['ecs']->table('cart') .
	" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'";
	$row = $GLOBALS['db']->GetRow($sql);

	if ($row)
	{
		$number = intval($row['number']);
		$amount = floatval($row['amount']);
	}
	else
	{
		$number = 0;
		$amount = 0;
	}

	$str = sprintf($GLOBALS['_LANG']['cart_info'], $number);

	return '<a href="'.WEB_ROOT.'Cart.php" target="_blank">' . $str . '</a>';
}

/**
 * 新购物车信息
 */
function insert_new_cart_info() {
	require_once($WEB_ROOT . "includes/lib_order.php");
	require_once($WEB_ROOT . "includes/lib_biaoju.php");

	$back = urlencode($_SERVER['REQUEST_URI']);
	$smarty = $GLOBALS['smarty'];

	$cart_goods = show_cart_goods();
	$biaoju_cart_goods = show_biaoju_cart_goods();

	$total_count = $cart_goods['total'] + $biaoju_cart_goods['total'];
	$total_count_formatted = price_format($total_count);

	$new_goods = null;
	$new_rec_id = 0;
	$total_goods_number = 0;

	$cart_goods_list = array();



	foreach ($cart_goods['goods_list'] as $goods) {
		$total_goods_number += $goods['goods_number'];
		//$goods['href'] = "goodsId". $goods['goods_id'] . ".htm";
        $goods['href'] = "goods". $goods['goods_id'] . "/";
		$cart_goods_list[$goods['rec_id']] = $goods;
		if ($goods['rec_id'] > $new_rec_id && $goods['parent_id'] == 0) { /* 最新加入商品不能是配件 */
			$new_goods = $goods;
			$new_rec_id = $goods['rec_id'];
		}
	}

	foreach ($biaoju_cart_goods['store_list'] as $store) {
		foreach ($store['goodsList'] as $goods) {
			$total_goods_number += $goods['goods_number'];
			$goods['href'] = 'biaojuproductdetail.php?StoreGoodsId=' . $goods['biaoju_store_goods_id'];
			$cart_goods_list[$goods['rec_id']] = $goods;
			if ($goods['rec_id'] > $new_rec_id && $goods['parent_id'] == 0) {
				$new_goods = $goods;
				$new_rec_id = $goods['rec_id'];
			}
		}
	}
	unset($cart_goods_list[$new_rec_id]);
	$smarty->assign('sImagePath', ImagePath . 's/');
	$smarty->assign('back', $back);
	$smarty->assign('new_goods', $new_goods);
	$smarty->assign('total_count', $total_count);
	$smarty->assign('total_count_formatted', $total_count_formatted);
	$smarty->assign('cart_goods_list', $cart_goods_list);
	$smarty->assign('cart_goods_list_count', count($cart_goods_list));
	$smarty->assign('total_goods_number', $total_goods_number);

	$html = $smarty->fetch('library/newCart.dwt');

	return $html;

}
/**
 * insert_cart_list
 *
 * @return string
 */
function insert_cart_list()
{
	$outCartList	=	'';
	$InfoPage		=	WEB_ROOT.'productdetail.php?GoodsId=';
	$sql = 'SELECT * ' .
	' FROM ' . $GLOBALS['ecs']->table('cart') .
	" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "' order by rec_id desc limit 5";
	$row = $GLOBALS['db']->getAll($sql);
	if(is_array($row)&&count($row)>0){
		foreach ($row as $key=>$value){
			if((int)$key%2){//取余数
				$outCartList	.=	'<li><a href="'.$InfoPage.$value['goods_id'].'" target="_blank" title="'.$value['goods_name'].'">'.sub_str($value['goods_name'],0,8).'</a><strong>'.price_format($value['goods_price']).'</strong></li>';
			}else{
				$outCartList	.=	'<li class="eq"><a href="'.$InfoPage.$value['goods_id'].'" target="_blank" title="'.$value['goods_name'].'">'.sub_str($value['goods_name'],0,8).'</a><strong>'.price_format($value['goods_price']).'</strong></li>';
			}
		}
		$outCartList	.=	'<li style="text-align:center;padding-top:6px;"><a href="'.WEB_ROOT.'Cart.php">去购物车结算</a></li>';
		return $outCartList;
	}else{
		$outCartList	=	'<li style="text-align:center;padding-top:4px;" >购物车暂无商品</li>';
		return $outCartList;
	}
}

/**
 * 调用指定的广告位的广告
 *
 * @access  public
 * @param   integer $id     广告位ID
 * @param   integer $num    广告数量
 * @return  string
 */
function insert_ads($arr)
{
	static $static_res = NULL;

	if (!empty($arr['num']) && $arr['num'] != 1)
	{
		$time = date('Y-m-d');
		$sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, ' .
		'p.ad_height, p.position_style, RAND() AS rnd ' .
		'FROM ' . $GLOBALS['ecs']->table('ad') . ' AS a '.
		'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
		"WHERE enabled = 1 AND start_date <= '" . $time . "' AND end_date >= '" . $time . "' ".
		"AND a.position_id = '" . $arr['id'] . "' " .
		'ORDER BY rnd LIMIT ' . $arr['num'];
		$res = $GLOBALS['db']->GetAllCached($sql);
	}
	else
	{
		if ($static_res[$arr['id']] === NULL)
		{
			$time = date('Y-m-d');
			$sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, '.
			'p.ad_height, p.position_style, RAND() AS rnd ' .
			'FROM ' . $GLOBALS['ecs']->table('ad') . ' AS a '.
			'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
			"WHERE enabled = 1 AND a.position_id = '" . $arr['id'] . "' AND start_date <= '" . $time . "' AND end_date >= '" . $time . "' " .
			'ORDER BY rnd LIMIT 1';
			$static_res[$arr['id']] = $GLOBALS['db']->GetAllCached($sql);
		}
		$res = $static_res[$arr['id']];
	}
	$ads = array();
	$position_style = '';

	foreach ($res AS $row)
	{
		if ($row['position_id'] != $arr['id'])
		{
			continue;
		}
		$position_style = $row['position_style'];
		switch ($row['media_type'])
		{
			case 0: // 图片广告
			$src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
			"data/afficheimg/$row[ad_code]" : $row['ad_code'];
			$ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'><img src='$src' width='" .$row['ad_width']. "' height='$row[ad_height]'
                border='0' /></a>";
			break;
			case 1: // Flash
			$src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
			"data/afficheimg/$row[ad_code]" : $row['ad_code'];
			$ads[] = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" " .
			"codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\"  " .
			"width='$row[ad_width]' height='$row[ad_height]'>
                           <param name='movie' value='$src'>
                           <param name='quality' value='high'>
                           <embed src='$src' quality='high'
                           pluginspage='http://www.macromedia.com/go/getflashplayer'
                           type='application/x-shockwave-flash' width='$row[ad_width]'
                           height='$row[ad_height]'></embed>
                         </object>";
			break;
			case 2: // CODE
			$ads[] = '<a href=' . '"' . 'affiche.php?ad_id=' . $row['ad_id'] . '&amp;uri=' .
			urlencode($row["ad_link"]) . '"' . ' target="_blank">' . $row['ad_code'] . '</a>';
			break;
			case 3: // TEXT
			$ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'>" .htmlspecialchars($row['ad_code']). '</a>';
			break;
		}
	}
	$position_style = 'str:' . $position_style;

	$need_cache = $GLOBALS['smarty']->caching;
	$GLOBALS['smarty']->caching = false;

	$GLOBALS['smarty']->assign('ads', $ads);
	$val = $GLOBALS['smarty']->fetch($position_style);

	$GLOBALS['smarty']->caching = $need_cache;

	return $val;
}


function insert_index_member_info()
{
	$need_cache = $GLOBALS['smarty']->caching;
	$GLOBALS['smarty']->caching = false;

	if ($_SESSION['user_id'] > 0)
	{
		$userInfo 	=	get_user_default($_SESSION['user_id']);
		//print_r($userInfo);
		/*
		$rankPoint	=	(int)$userInfo['rank_points'];
		$rankConfig = $GLOBALS['_CFG']['rank_config'];
		!is_array($rankConfig) && $rankConfig = array();
		$rankId = $rankConfig[0]['rank_id'];
		$rankId = isset($rankConfig[0]['rank_id']) ? $rankConfig[0]['rank_id'] : 0;

		foreach ($rankConfig as $rank) {
		if ($rankPoint >= $rank['min_points']) {
		$rankId = $rank['rank_id'];
		} else {
		break;
		}
		}
		$userInfo['rank_id']	=	$rankId;
		*///print_r($userInfo);
		$GLOBALS['smarty']->assign('info', $userInfo);
	}
	else
	{
		if (!empty($_COOKIE['ECS']['username']))
		{
			$GLOBALS['smarty']->assign('ecs_username', $_COOKIE['ECS']['username']);
		}
	}
	$output = $GLOBALS['smarty']->fetch('library/Memberlogin.lbi');
	return $output;
}
/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info()
{
	$need_cache = $GLOBALS['smarty']->caching;
	$GLOBALS['smarty']->caching = false;
	if ($_SESSION['user_id'] > 0)
	{
		$userInfo		=get_user_default($_SESSION['user_id']);

		/*
		$rankPoint	=	(int)$userInfo['rank_points'];
		$rankConfig = $GLOBALS['_CFG']['rank_config'];
		!is_array($rankConfig) && $rankConfig = array();
		$rankId = $rankConfig[0]['rank_id'];
		$rankId = isset($rankConfig[0]['rank_id']) ? $rankConfig[0]['rank_id'] : 0;
		print_r($userInfo);
		foreach ($rankConfig as $rank) {
		if ($rankPoint >= $rank['min_points']) {
		$userInfo['rank_id'] = $rank['rank_id'];
		break;
		}
		}
		$userInfo['rank_id']	=	$rankId;
		*/
		$GLOBALS['smarty']->assign('info', $userInfo);
	}
	else
	{
		if (!empty($_COOKIE['ECS']['username']))
		{
			$GLOBALS['smarty']->assign('ecs_username', $_COOKIE['ECS']['username']);
		}
	}

	$output = $GLOBALS['smarty']->fetch('library/login.lbi');
	return $output;
}


/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments($arr)
{
	$need_cache = $GLOBALS['smarty']->caching;
	$GLOBALS['smarty']->caching = false;

	/* 验证码相关设置 */
	if ($GLOBALS['_CFG']['comment_captcha'] && gd_version() > 0)
	{
		$GLOBALS['smarty']->assign('enabled_captcha', 1);
		$GLOBALS['smarty']->assign('rand', mt_rand());
	}
	$GLOBALS['smarty']->assign('username',     $_SESSION['user_name']);
	$GLOBALS['smarty']->assign('email',        $_SESSION['email']);
	$GLOBALS['smarty']->assign('comment_type', $arr['type']);
	$GLOBALS['smarty']->assign('id',           $arr['id']);
	$cmt = assign_comment($arr['id'],          $arr['type']);
	$GLOBALS['smarty']->assign('comments',     $cmt['comments']);
	$GLOBALS['smarty']->assign('pager',        $cmt['pager']);

	$val = $GLOBALS['smarty']->fetch('library/comments_list.lbi');

	$GLOBALS['smarty']->caching = $need_cache;

	return $val;
}



/**
 * 新的首页，顶部用户信息
 * Tao Fei 2008 4 2
 * add 顾客评价, 购物车商品, 留言回复, 我的评价 等等的数目 by Tao Fei 2008-5-12
 */
function insert_user_info($type)
{

    global $WEB_ROOT;

    if($_GET['back_act'])
    {
    	$back = $_GET['back_act'];
    }
    else
    {
        $back = urlencode($_SERVER['REQUEST_URI']);
    }
    require_once(ROOT_PATH."includes/lib_order.php");
    require_once(ROOT_PATH."includes/lib_transaction.php");
    include_once(ROOT_PATH.'includes/lib_oukoo.php');
    
    //所有订单评论数目
    $all_order_comment_num = get_after_order_comment_count_by_store_id(0);	//只显示欧酷的评论

				    
    if ($_SESSION['user_id'] > 0)
    {
        //留言回复
        require_once(ROOT_PATH . 'includes/lib_bj_comment.php');
        $userInfo = get_user_default($_SESSION['user_id']);
        //我的评价
        $order_comment_num = get_after_order_comment_count($userInfo['userId']);        
        $comment_list_num = get_user_bjcomment_times($userInfo['userId']);
//

//
//        $ret = "<p><a id='my_link' href=\"{$WEB_ROOT}my.php\" class=\"topMy\" style='position:relative;z-index:100'>我的欧酷</a>" .
//               "<a href=\"{$WEB_ROOT}Cart.php\" class=\"topCart\" id=\"topCartId\">购物车<span id='cartTotalNum' style='color:#ffff9b;font-weight:bold;margin:0 3px;'>$cart_goods_num</span>件</a>" .
//               "您好 {$userInfo[username]} 欢迎来欧酷！<a href=\"{$WEB_ROOT}User.Controller.php?Action=Logout\" target='_self' style='position:relative;z-index:100'>[退出]</a>" .
//               "<a href=\"{$WEB_ROOT}orderCommentList.php?store_id=0\" style='position:relative;z-index:100'>[查看客户评价<span style='color:#fe6601;'>$all_order_comment_num</span>条]</a></p>";
//
//        $ret .= sprintf("<script type='text/javascript'>
//        function write_num(e){
//            document.getElementById('message_num').innerHTML = '[%d]';
//            document.getElementById('my_order_comment_num').innerHTML = '[%d]';
//            document.getElementById('order_num').innerHTML = '[%d]';
//        }
//        try{
//            window.addEventListener('load', write_num, false);
//        }
//        catch(e){
//            window.attachEvent('onload', write_num);
//        }
//        </script>
//        ", $commentListNum, $order_comment_num, get_user_order_count($_SESSION['user_id'])
//        );
          $ret = " 您好 ".$userInfo['username']."，欢迎来到欧酷商城！<a href='{$WEB_ROOT}User.Controller.php?Action=Logout' target='_self'>退出</a>|<a href='http://bbs.ouku.com' target='_blank' style='margin-right:0;'>欧酷食堂论坛</a><span style='color:red;margin-right:10px;'>[新!]</span>|<a href='{$WEB_ROOT}help/index.php?id=1'>帮助中心</a>"; 
          if($type['val'] == 'all_order_comment_num'){
            $ret = $all_order_comment_num;
          }
          if($type['val'] == 'comment_list_num'){
            $ret = $comment_list_num;
          }
          if($type['val'] == 'my_order_comment_num'){
            $ret = $order_comment_num;
          }                     
          if($type['val'] == 'order_num'){
            $ret = get_user_order_count($_SESSION['user_id']);
          }                                    
        	if($type['val'] == 'goods_inform_num'){
            $ret = get_goods_inform_num($_SESSION['user_id']);
          }
          if($type['val'] == 'pricecut_inform_num'){
            $ret = get_pricecut_inform_num($_SESSION['user_id']);
          }
    }
    else
    {
//        $ret =   "<p><a id='my_link' href=\"{$WEB_ROOT}my.php\" class=\"topMy\" style='position:relative;z-index:100'>我的欧酷</a>
//                 <a href=\"{$WEB_ROOT}Cart.php\" class=\"topCart\" id=\"topCartId\">购物车<span id='cartTotalNum'  style='color:#ffff9b;font-weight:bold;margin:0 3px;'>$cart_goods_num</span>件</a>
//                   您好，欢迎来到欧酷！<a href=\"{$WEB_ROOT}login.php?back_act=$back\" target='_self'>[登录]</a>
//                 <a href=\"{$WEB_ROOT}register.php?back_act=$back\" target='_self' style='color:#fe6601;font-weight:bold;position:relative;z-index:100'>[免费注册]</a>
//                 <a href=\"{$WEB_ROOT}orderCommentList.php?store_id=0\" style='position:relative;z-index:100'>[查看客户评价<span style='color:#fe6601;'>$all_order_comment_num</span>条]</a>
//                 </p>";
//
//        $ret .= "<script type='text/javascript'>
//        function rewrite_url(e){
//            var links = document.getElementById('oukuInfo').getElementsByTagName('a');
//            for(var i=0; i < links.length; i++)
//            {
//            	if(links[i].href.indexOf('back_act') == -1){
//                    links[i].href = '{$WEB_ROOT}' + \"loginregister.php?back_act=\" + links[i].href;
//                }
//            }
//            var ele = document.getElementById('my_link');
//            if(ele.href.indexOf('back_act') == -1){
//                ele.href = '{$WEB_ROOT}' + \"loginregister.php?back_act=\" + ele.href;
//            }
//        }
//        try{
//        	window.addEventListener('load', rewrite_url, false);
//        }
//        catch(e){
//            window.attachEvent('onload', rewrite_url);
//        }
//        </script>
//        ";

          $ret = "您好，欢迎来到欧酷商城！<a href='{$WEB_ROOT}login.php?back_act={$back}' target='_self'>请登录</a>|<span><a href='{$WEB_ROOT}register.php?back_act={$back}' target='_self'>免费注册</a></span>|<a href='http://bbs.ouku.com' target='_blank' style='margin-right:0;'>欧酷食堂论坛</a><span style='color:red;margin-right:10px;'>[新!]</span>|<a href='{$WEB_ROOT}help/index.php?id=1'>帮助中心</a> ";
          if($type['val'] == 'all_order_comment_num'){
            $ret = $all_order_comment_num;
          }
          if($type['val'] == 'comment_list_num'){
            $ret = 0;
          }
          if($type['val'] == 'my_order_comment_num'){
            $ret = 0;
          }                     
          if($type['val'] == 'order_num'){
            $ret = 0;
          }                 
          if($type['val'] == 'goods_inform_num'){
            $ret = 0;
          }                 
          if($type['val'] == 'pricecut_inform_num'){
            $ret = 0;
          }
    }
    //获取订单商品
    $cart_goods_num = get_cart_goods_num();
    $cart_goods_num = $cart_goods_num ? $cart_goods_num : 0;
    if($type['val'] == 'cart_goods_num'){
      $ret = $cart_goods_num;
    }
    if($type['val'] == 'cart_goods'){
      $cart_goods = show_cart_goods();
      $ret = '';
      foreach($cart_goods['goods_list'] as $goods)
      {
        $ret .= '<li><a href="'.toGoodsPath($goods['goods_id']).'" target="_blank" >'.$goods['goods_name'].'</a><span style="color:#fe6601;margin:0 10px;font-weight:bold;">￥'.$goods['shop_price'].'</span>×'.$goods['goods_number'].'</li>';       
      }
        
      $total_amout = $cart_goods['total'] + $cart_biaoju_goods['total'];
      $total_num = $cart_goods_num;       
      $ret .= '<li style="border:0;color:#666;text-align:left;padding-top:15px;padding-bottom:10px;"><a href="'.WEB_ROOT.'Cart.php" style="float:right;margin-top:-5px;margin-left:10px;" target="_self"><img src="'.WEB_ROOT.'themes/ouku/images/checkCart.png" alt="查看购物车"></a><a href="'.WEB_ROOT.'Checkout.php?z=check" style="float:right;margin-top:-5px;" target="_self"><img src="'.WEB_ROOT.'themes/ouku/images/inPayCenter.png" alt="进入结算中心"></a>合计<span style="margin:0 5px;font-weight:bold;color:#fe6601;">'.$total_num.'</span>件<span id="sumPrice_id" class="bRed" style="margin-left:10px;font-weight:bold;color:#fe6601;">'.$total_amout.'</span></li>';   
      if(!$cart_goods['goods_list']){
        $ret = '<p style="color:#666;text-align:center;background:#fff;padding:5px 0;">您的购物袋中暂无商品，赶快选择心爱的商品吧！</p>';
      }
    }    
    return $ret;
}

?>
