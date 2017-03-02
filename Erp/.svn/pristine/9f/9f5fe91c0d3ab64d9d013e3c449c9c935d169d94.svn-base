<?php
/**
 * oukoo[欧酷网]
 * OUKOO特有动作集合
 * @author :czhang<czhang@oukoo.com>
 * @copyright oukoo<0.5>
*/
/**
 * 获得产品url
 * @author pengcheng
 */
function toGoodsPath($id) {
	return WEB_ROOT."goods$id/";
}
/**
 * 获得镖局产品url
 * @author pengcheng
 */
function toBiaojuGoodsPath($id) {
	return WEB_ROOT."biaojuproductdetail.php?StoreGoodsId=$id";
}
/**
 * 获得镖局商家url
 * @author pengcheng
 */
function toBiaojuStorePath($id) {
	return WEB_ROOT."biaojustoreinfo.php?StoreId=$id";
}
/**
 * 删除 QUERY_STRING 里的某个值对
 * @author Zandy
 *
 * @return unknown
 */
function unsetGetParam() {
	$func_get_args = func_get_args();
	$qs = $func_get_args[0];
	if ($qs === null) {
		$qs = $_SERVER['QUERY_STRING'];
	}
	unset($func_get_args[0]);
	$qss = explode("&", $qs);
	$arr = array();
	foreach ($qss as $v) {
		$a = explode("=", $v);
		'' != $a[0] && $arr[$a[0]] = $a[1];
	}
	foreach ($func_get_args as $k => $v) {
		if (is_string($v)) {
			if (array_key_exists($v, $arr)) {
				unset($arr[$v]);
			}
		} elseif (is_array($v)) {
			foreach ($v as $kk => $vv) {
				if (array_key_exists($vv, $arr)) {
					unset($arr[$v]);
				}
			}
		}
	}
	$b = array();
	foreach ($arr as $k => $v) {
	'' != $k && $b[] = "$k=$v";
	}
	return join("&", $b);
}

/**
 * 测试 QUERY_STRING 是否有某个变量 
 * @author TaoFei
 * @param string qs queryString or null
 * @param string key key to test (可以是正则表达式)
 * @return Bool 
 */
function hasGetParam($qs,$key) {
	if ($qs === null) {
		$qs = $_SERVER['QUERY_STRING'];
	}
	$qss = explode("&", $qs);
	
	foreach ($qss as $v) {
		$a = explode("=", $v);
		if('' != $a[0]) {
			if (preg_match('/^'.$key.'$/', $a[0]))
				return true;
		}
	}
	return false;
}

function delparam()
{
	$func_get_args = func_get_args();
	if (sizeof($func_get_args) < 2)
	{
		//return '';
	}
	$qs = $func_get_args[0];
	if ($qs === null)
	{
		$qs = $_SERVER['QUERY_STRING'];
	}

	$qss = explode("&", $qs);
	$arr = array();
	foreach ($qss as $v)
	{
		$a = explode("=", $v);
		'' != $a[0] && $arr[$a[0]] = $a[1];
	}

	unset($func_get_args[0]);

	foreach ($arr as $k => $v)
	{
		foreach ($func_get_args as $func_get_arg)
		{
			if (preg_match('/^'.$func_get_arg.'$/', $k))
			{
				unset($arr[$k]);
			}
		}
	}

	$b = array();
	foreach ($arr as $k => $v)
	{
	'' != $k && $b[] = "$k=$v";
	}
	$r = join("&", $b);
	return $r;
}

/**
 * 根据上平的 goods_id 取得当前的供应商的 provider_id
 *
 * @param int $goods_id
 * @return int provider_id
 */
function getProviderIdByGoodsId($goods_id) {
	$goods_id = intval($goods_id);
	$sql = 'SELECT provider_id from '.$GLOBALS['ecs']->table('goods')." where goods_id = '".$goods_id."'";
	$row	= $GLOBALS['db']->getRow($sql);
	return $row['provider_id'];
}

//{{ modified by taofei 加入镖局浏览记录
//数组改成 array( array($good_id, $biaoju_good_id) ......) 这种形式
/*添加浏览纪录*/
function addHistory($iGoodId, $iBiaojuGoodId = 0){
	
    
	$iSaveTime		=	time()+36000;
	$arGoodsHistory	=	unserialize($_COOKIE['GoodsHistory']);
	if($arGoodsHistory){
		
	    $flag = false;
	    foreach($arGoodsHistory as $k => $v)
        {
        	if ((is_array($v) && $v[0] == $iGoodId && $v[1] == $iBiaojuGoodId)
                ||  ($v == $iGoodId && $iBiaojuGoodId == 0))
            {
            	$flag = true;
                break;
            }
        }
        if(!$flag)
        {
            $arGoodsHistory[]  =   array($iGoodId, $iBiaojuGoodId);
            setcookie('GoodsHistory', serialize($arGoodsHistory), $iSaveTime, '/', COOKIE_DOMAIN);
        }
	}else{
		$arGoodsHistory[0]	=	array($iGoodId, $iBiaojuGoodId);
		setcookie('GoodsHistory', serialize($arGoodsHistory), $iSaveTime, '/', COOKIE_DOMAIN);
	}
    
	return $arGoodsHistory;
}


//modified by taofei 加入镖局浏览记录
//uglly
/**
 * getGoodsHistory
 *
 * @param array $arGoodsHistory
 * @return array
 */
function getGoodsHistory($arGoodsHistory,$ilimit = 0){
	if(is_array($arGoodsHistory)){
		$arGoodsId = array();
        foreach($arGoodsHistory as $k => $v)
        {
            if(is_array($v))
            {
                $arGoodsId[$v[0]][] = $v[1];
            }
            else
            {
                $arGoodsId[$v][] = 0;
            }
        }
        //$arGoodsId 是一个 goodsid->biaojugoodsid 的数组
		$sGoodsHistory	=	implode('", "',array_keys($arGoodsId));
		$ilimit	=	$ilimit?' limit '.$ilimit:"";
		$Sql	=	' select g.goods_thumb ,g.goods_name,g.shop_price, g.goods_id , g.integral , g.is_on_sale, IFNULL(AVG(r.comment_rank), 0) AS comment_rank from ' . $GLOBALS['ecs']->table('goods') .' AS  g '.
		' LEFT JOIN'.$GLOBALS['ecs']->table('comment') .' as r ON  g.goods_id = r.id_value '.
		' where g.goods_id in ("'.$sGoodsHistory.'")  GROUP BY g.goods_id '.$ilimit;
		$arHistory	=	$GLOBALS['db']->getAll($Sql);
        $ret = array();
		if($arHistory){
			if(is_array($arHistory)){
				foreach ($arHistory as $key =>$value){
					//$arHistory[$key]['rank']	=	(int)$value['comment_rank'];
                    $value['rank'] = (int)$value['comment_rank'];
                    foreach($arGoodsId[$value['goods_id']] as $biaoju_goods_id)
                    {
                        
                        if($biaoju_goods_id) //是镖局商品
                        {
	                            $sql2 = sprintf("SELECT sg.price, store_id FROM `bj_store_goods` as sg WHERE store_goods_id = '%s' limit 1",$biaoju_goods_id);
                            $sg = $GLOBALS['db']->getAll($sql2);
                            if($sg)
                            {	
                                $sg = $sg[0];
                                $ret[$key.$biaoju_goods_id] = $value;
                                $ret[$key.$biaoju_goods_id]['is_biaoju'] = 1;
                                $ret[$key.$biaoju_goods_id]['biaoju_goods_id'] = $biaoju_goods_id;
                                $ret[$key.$biaoju_goods_id]['biaoju_price'] = $sg['price'];
                                $ret[$key.$biaoju_goods_id]['store_id'] = $sg['store_id'];
                            }
                        }
                        else
                        {
                        	$ret[$key] = $value;
                        }
                    }
                    
				}
			}
			return $ret;
		}else {
			return false;
		}
	}
}
//}}


function arSearchResult($SearchResult){
	global $objectSearch;


}

/**
 * resultInfo
 * @param int id
 * @return array
 */
function resultInfo($id){
	//$sql	=	'SELECT * from '.$GLOBALS['ecs']->table('goods').' where goods_id = '.$id;
	$sql	=	'SELECT market_price, shop_price,goods_id,is_on_sale, is_display, goods_desc,goods_details,sale_status,goods_thumb as goods_img ,goods_brief as goods_brief, vote_score,vote_times,(vote_score/vote_times) AS comment_rank from '.$GLOBALS['ecs']->table('goods')
	.' where goods_id = '.$id ;
	//echo($sql);
	
	$row	= $GLOBALS['db']->getRow($sql);
	// add different prices by zwsun 2008/6/12

	$prices =$GLOBALS['db']->getRow("SELECT count(*) as styles_num, max(style_price) as style_price_max, min(style_price) as style_price_min FROM ".$GLOBALS['ecs']->table('goods_style')."  where goods_id = '$id'");
	if($prices['styles_num'] > 0 ) {
	  $row['style_multi'] = true;
	  $row['style_price_max'] = $prices['style_price_max'];
	  $row['style_price_min'] = $prices['style_price_min'];
	} else {
		$row['style_multi'] = false;
		$row['style_price_max'] = $row['shop_price'];
		$row['style_price_min'] = $row['shop_price'];
	}	
	return $row;
}


/**
 * 分页
 * 
 * @author Zandy <yzhang@oukoo.com>
 *
 * @param int $num
 * @param int $curpage
 * @param string $mpurl
 * @param int $perpage
 * @param int $maxpages
 * @param int $page
 * @param int $simple
 * @param string $event
 * @param int $offset
 * @param int $start
 * @param string urlend 追加链接信息
 * @return string
 */
function multi($num,  $curpage, $mpurl, $perpage=10, $maxpages = 0, $page = 10, $simple = 0, $event = '', $offset = 2, $start = 2, $urlend = '') {

	$multipage = '';
	if($num > $perpage) {
		#$start = 1;
		#$offset = 2;

		if ($start > $offset * 2 + 1) {
			return '';
		}

		// {{{ 网络分页
		if (!$GLOBALS['__multiSimple']) {
			$prePageText = '上一页';
			$nextPageText = '下一页';
		} else {
			$prePageText = '&#171';
			$nextPageText = '&#187';
		}
		// }}}

		$curpage = max(1, $curpage);

		$realpages = @ceil($num / $perpage);

		$pages = $maxpages > 0 ? min($maxpages, $realpages) : $realpages;

		$multipage .= '<div class="page">';
		if ($curpage == 1) {
			$multipage .= '<a >'.$prePageText.'</a>';
		} else {
			$multipage .= '<a href="'.$mpurl.($curpage-1).$urlend.'"'. str_replace('#page#', $curpage-1, $event) . '>'.$prePageText.'</a>';
		}

		//$info = array();

		#$pages = 90; // for test
		if ($pages <= ($start + $offset)*2 + 1) {
			for ($i = 1; $i <= $pages; $i++) {
				if ($i == $curpage) {
					$multipage .= '<a class="currentPage">'.$i.'</a>';
				} else {
					$multipage .= '<a href="'.$mpurl.$i.$urlend.'" '.str_replace('#page#', $i, $event).'>'.$i.'</a>';
				}
			}
		} else {
			for ($i = 1; $i <= $start; $i++) {
				if ($i == $curpage) {
					$multipage .= '<a class="currentPage">'.$i.'</a>';
				} else {
					$multipage .= '<a href="'.$mpurl.$i.$urlend.'" '.str_replace('#page#', $i, $event).'>'.$i.'</a>';
				}
			}
			//$multipage .= '<span>...</span>';
            

			$from = $curpage - $start;
			$to = $curpage + $start;
			
			if ($from <= $start) {
				$from = $start + 1;
				$to = $from + $offset*2;
				#$multipage .= 'aaa';
			} elseif ($to >= $pages) {
				$to = $pages - $start;
				$from = $to - $offset*2;
				#$multipage .= 'bbb';
			} else {
				#$multipage .= "$start.ccc.$to";
			}
			
           if($from > $start + 1)
           {
           	   $multipage .= '<span>...</span>';
           }
			for ($i = $start+1; $i <= $pages; $i++) {
				if ($i < $from || $i > $to) {
					continue;
				}
				if ($i == $curpage) {
					$multipage .= '<a class="currentPage">'.$i.'</a>';
				} else {
					$multipage .= '<a href="'.$mpurl.$i.$urlend.'" '.str_replace('#page#', $i, $event).'>'.$i.'</a>';
				}
			}
           if($pages - $start + 1 > $to + 1)
           {
               $multipage .= '<span>...</span>';
           }
		   //$multipage .= '<span>...</span>';

			for ($i = $pages - $start + 1; $i <= $pages; $i++) {
				if ($i == $curpage) {
					$multipage .= '<a class="currentPage">'.$i.'</a>';
				} else {
					$multipage .= '<a href="'.$mpurl.$i.$urlend.'" '.str_replace('#page#', $i, $event).'>'.$i.'</a>';
				}
			}

		}

		if ($curpage == $pages) {
			$multipage .= '<a >'.$nextPageText.'</a>';
		} else {
			$multipage .= '<a href="'.$mpurl.($curpage+1).$urlend.'" '. str_replace('#page#', $curpage+1, $event) .'>'.$nextPageText.'</a>';
		}
		//$multipage .= '<a >'.$from.' : '.$to.'</a>';

		if (!$GLOBALS['__multiSimple']) {
			$multipage .= '<span>到第<input type="text" class="text2" onBlur="if(this.value > 0 && this.value <= '.$pages.'){window.location=\''.$mpurl.'\'+this.value+\''.$urlend.'\';}" onKeyDown="if(event.keyCode==13) {window.location=\''.$mpurl.'\'+this.value+\''.$urlend.'\'; return false;}" />页</span><img onBlur="if(this.value > 0 && this.value <= '.$pages.'){window.location=\''.$mpurl.'\'+this.value+\''.$urlend.'\';}" src="'.WEB_ROOT.'themes/ouku/images/ok.gif" alt="确定" style="margin-top:-3px; cursor: pointer;"/></div>';
		}
	}

	return $multipage;
}

/**
 * newarg
 *
 * @param String $pagearg
 * @return string
 */
function newarg($pagearg,$url=""){
	$str = "";
	$urlar = $_GET;
	unset($urlar[$pagearg]);
	if($urlar){
		foreach($urlar as $key=>$val){
			if($str == "") {
				$str = "?$key=$val";
			}else {
				$str .= "&$key=$val";
			}
		}
		$str .= "&$pagearg=";
	}else{
		if($url){
			$str = "&$pagearg=";
		}else{
			$str = "?$pagearg=";
		}
	}
	return $str;
}

/**
 * newarg_with_urlencode
 * 会进行urlencode
 * @param String $pagearg
 * @return string
 */
function newarg_with_urlencode($pagearg,$url=""){
    $str = "";
    $urlar = $_GET;
    unset($urlar[$pagearg]);
    if($urlar){
        foreach($urlar as $key=>$val){
            if($str == "") {
                $str = "?$key=" . urlencode($val);
            }else {
                $str .= "&$key=" . urlencode($val);
            }
        }
        $str .= "&$pagearg=";
    }else{
        if($url){
            $str = "&$pagearg=";
        }else{
            $str = "?$pagearg=";
        }
    }
    return $str;
}


/**
 * $arGoods
 *
 * @param array $arGoods
 * @return array
 */
function	arCompareGoods($arGoods){
	if(is_array($arGoods)&&(count($arGoods)>=2)){
		$arList	=	array();
		foreach ($arGoods as $key=>$value){
			if(is_array($value)){
				foreach ($value as $keys=>$values){
					if(!strpos($values['name'],'#')){
						if(!in_array($values['name'],$arList)){
							$arList[]	=	$values['name'];
						}
					}
				}
			}
		}
        
		if(is_array($arList)&&is_array($arGoods)){

			$arCompareList	=	array();
			foreach ($arList as $key =>$value){

				foreach ($arGoods as $keys =>$values){
                    $hasAttr = false;
					foreach ($values as $valueKey =>$valueValue){
                        
						if($value == $valueValue['name']){
							$arCompareList[$value][$keys]	=	$valueValue['value'];
                            $hasAttr = true;
						}
					}
                    if(!$hasAttr)
                    {
                    	$arCompareList[$value][$keys]  =   "  ";
                    }
				}

			}
			return $arCompareList;
		}
	}else{
		return false;
	}
}

/**
 * 根据积分返回等级名字
 * @param int $user_id
 * @return string 等级名字
 */
function getRankNameByPoints($user_id) {
	global $ecs, $db;
	$rankConfig = $GLOBALS['_CFG']['rank_config'];
	$sql = "SELECT SUM(order_amount) FROM {$ecs->table('order_info')} WHERE user_id = '$user_id' AND NOT order_amount IS NULL AND order_status = 1 AND shipping_status in (2, 6)";
	$rankPrice = $db->getOne($sql);
    //修正VIP 级数计算问题.
    //这里rankPrice 可能是null 值, null >= 0 === false
    $rankPrice = $rankPrice ? floatval($rankPrice) : 0; 
	!is_array($rankConfig) && $rankConfig = array();
	$rankName = isset($rankConfig[0]['rank_name']) ? $rankConfig[0]['rank_name'] : '';
	foreach ($rankConfig as $rank) {
		if ($rankPrice >= $rank['min_points']) {
			$rankName = $rank['rank_name'];
		} else {
			break;
		}
	}
	return $rankName;
}

/**
 * 根据积分返回等级Id
 * @param int $user_id
 * @return string 等级id
 */
function getRankIdByPoints($user_id) {
	global $ecs, $db;
	$rankConfig = $GLOBALS['_CFG']['rank_config'];
	$sql = "SELECT SUM(order_amount) FROM {$ecs->table('order_info')} WHERE user_id = '{$user_id}' AND NOT order_amount IS NULL AND order_status = 1 AND shipping_status in (2, 6)";
	$rankPrice = $db->getOne($sql);
    //修正VIP 级数计算问题.
    //这里rankPrice 可能是null 值, null >= 0 === false
    $rankPrice = $rankPrice ? floatval($rankPrice) : 0; 
	!is_array($rankConfig) && $rankConfig = array();
	$rankId = isset($rankConfig[0]['rank_id']) ? $rankConfig[0]['rank_id'] : 0;
	if(is_array($rankConfig)){
		foreach ($rankConfig as $rank) {
            
			if ($rankPrice >= $rank['min_price']) {
				$rankId = $rank['rank_id'];
			} else {
				break;
			}
		}
	}
	return $rankId;
}

/**
 * 获得文章数目
 * update By Tao Fei (ftao@ouku.com)  2006-05-16
 * 增加了$cat_id 这个参数 
 */
function getArticleCount($is_open = false, $cat_id = 1)
{
    $idCondition = "cat_id = $cat_id";
    if (is_int($is_open))
    {
        $idCondition .= " and is_open = '$is_open' ";
    }	
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('article') . " WHERE $idCondition";
    return $GLOBALS['db']->getOne($sql);
}
/**
 * 获得文章
 * update By Tao Fei (ftao@ouku.com)  2006-05-16
 * 增加了$cat_id 这个参数 
 *
 */
function getArticleList($start = 0, $count = 0, $articleId = 0, $is_open = 1, $cat_id = 1) {
	if ($start >= 0 && $count > 0) {
		$limit = " limit $start,$count";
	}

	$idCondition = '';
	
	if ($articleId > 0) {
		$idCondition .= "and article_id = $articleId";
	}

	if (is_int($is_open))
	{
		$idCondition .= " and is_open = '$is_open' ";
	}
		
	$sql = "SELECT * FROM ". $GLOBALS['ecs']->table('article') ." where cat_id = $cat_id $idCondition order by article_type desc, add_time desc $limit";

	// ncchen 090102 添加分页，标题
	$rows = $GLOBALS['db']->getAll($sql);
	foreach ($rows as $key => $row) {
		$rows[$key]['content'] = getArticleContent($row['content']);
	}
//	pp($sql);
	return $rows;	
}

/**
 * split ArticleContent and get details
 *
 * @author ncchen 090102
 * @param string $str ArticleContent
 * @return array
 */
function getArticleContent($str = "") {
	$contents = preg_split("/{ouku:pagebreak:ouku}/", $str);
	$article = array();
	foreach ($contents as $key => $content) {
		preg_match("/{ouku:pagetitle.*value='(.*)'.*:ouku}/", $content, $pagetitle);
		$article[$key]['content'] = preg_replace("/{ouku:pagetitle.*value='.*'.*:ouku}/", "", $content);
		$article[$key]['pagetitle'] = trim($pagetitle[1]);
		$article[$key]['pagetitle_short'] = sub_str(trim($pagetitle[1]), 0, 15);
	}
//	pp( $article);die();
	return $article;	
}

?>
