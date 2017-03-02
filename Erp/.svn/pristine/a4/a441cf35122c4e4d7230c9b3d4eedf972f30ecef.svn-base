<?php
/**
 * 公用函数库
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 如果字符串长度大于传入长度，则截取字符串，然后加上后缀，最后返回的字符串长度小于等于传入的长度
 *
 * @param string $str 截取字符串
 * @param int $length 判断长度
 * @param string $append 添加的后缀
 * @return string 截取后的长度
 */
function truncate($str, $length, $append = "...") {
	if (strlen($str) <= $length) {
		return $str;
	}else {
		return substr($str, 0, $length - strlen($append)) . $append;
	}
}

/**
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $start      截取的起始位置
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
function sub_str($str, $start = 0, $length = 0, $append = true)
{
    $str = trim($str);
    if ($length == 0)
    {
        return $str;
    }

    if (function_exists('mb_substr'))
    {
        $newstr = mb_substr($str, $start, $length, 'UTF-8');
    }
    else
    {
        $strlength = strlen($str);

        if ($length < 0)
        {
            $length = $strlength + $length;
        }
        elseif ($length >= $strlength)
        {
            return $str;
        }
        $newstr = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $start .  '}((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $length . '}).*#s', '$1', $str);
    }

    if ($append && $str != $newstr)
    {
        $newstr .= '...';
    }

    return $newstr;
}

/**
 * 去除字符串右侧可能出现的乱码
 *
 * @param   string      $str        字符串
 *
 * @return  string
 */
function trim_right($str)
{
    $length = strlen(preg_replace('/[\x00-\x7F]+/', '', $str)) % 3;

    if ($length > 0)
    {
        $str = substr($str, 0, 0 - $length);
    }

    return $str;
}

/**
 * 计算字符串的长度（汉字按照两个字符计算）
 *
 * @param   string      $str        字符串
 *
 * @return  int
 */
function str_len($str)
{
    $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));

    if ($length)
    {
        return strlen($str) - $length + intval($length / 3) * 2;
    }
    else
    {
        return strlen($str);
    }
}

/**
 * 获得用户操作系统的换行符
 *
 * @access  public
 * @return  string
 */
function get_crlf()
{
/* LF (Line Feed, 0x0A, \N) 和 CR(Carriage Return, 0x0D, \R) */
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Win'))
    {
        $the_crlf = '\r\n';
    }
    elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Mac'))
    {
        $the_crlf = '\r'; // for old MAC OS
    }
    else
    {
        $the_crlf = '\n';
    }

    return $the_crlf;
}

/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 * @author   Xuan Yan
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '')
{
    if (empty($item_list))
    {
        return $field_name . " IN ('') ";
    }
    else
    {
        if (!is_array($item_list))
        {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item)
        {
            $item = trim($item);
            if ($item !== '')
            {
                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp))
        {
            return $field_name . " IN ('') ";
        }
        else
        {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}

/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function real_ip()
{
    static $realip = NULL;

    if ($realip !== NULL)
    {
        return $realip;
    }

    if (isset($_SERVER))
    {
    	if (isset($_SERVER['HTTP_X_REAL_IP']))
    	{
    		$realip = $_SERVER['HTTP_X_REAL_IP'];
    	}
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip)
            {
                $ip = trim($ip);

                if ($ip != 'unknown')
                {
                    $realip = $ip;

                    break;
                }
            }
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else
        {
            if (isset($_SERVER['REMOTE_ADDR']))
            {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            else
            {
                $realip = '0.0.0.0';
            }
        }
    }
    else
    {
        if (getenv('HTTP_X_FORWARDED_FOR'))
        {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_CLIENT_IP'))
        {
            $realip = getenv('HTTP_CLIENT_IP');
        }
        else
        {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}

/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($email)
{
    $pattern = '/([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/';

    return preg_match($pattern, $email);
}

/**
 * 检查是否为一个合法的时间格式
 *
 * @access  public
 * @param   string  $time
 * @return  void
 */
function is_time($time)
{
    $pattern = '/[\d]{4}-[\d]{1,2}-[\d]{1,2}\s[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/';

    return preg_match($pattern, $time);
}

/**
 * 检查是否为一个合法的数值格式，整数或浮点数
 *
 * @access  public
 * @param   string  $num
 * @return  boolean
 */
function is_int_float($num)
{
    $pattern = '/^[\d]{1,}(\.([\d]){1,}){0,}$/';

    return preg_match($pattern, $num);
}
/**
 * 获得查询时间和次数，并赋值给smarty
 *
 * @access  public
 * @return  void
 */
function assign_query_info()
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
    $GLOBALS['smarty']->assign('query_info', sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time));

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
    {
        $GLOBALS['smarty']->assign('memory_info', sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1024 / 1024));
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];
    $GLOBALS['smarty']->assign('gzip_enabled', $gzip_enabled);
}

/**
 * 创建地区的返回信息
 *
 * @access  public
 * @param   array   $arr    地区数组 *
 * @return  void
 */
function region_result($parent, $sel_name, $type)
{
    global $cp;

    $arr = get_regions($type, $parent);
    foreach ($arr AS $v)
    {
        $region      =& $cp->add_node('region');
        $region_id   =& $region->add_node('id');
        $region_name =& $region->add_node('name');

        $region_id->set_data($v['region_id']);
        $region_name->set_data($v['region_name']);
    }
    $select_obj =& $cp->add_node('select');
    $select_obj->set_data($sel_name);
}

/**
 * 获得指定国家的所有省份
 *
 * @access      public
 * @param       int     country    国家的编号
 * @return      array
 */
function get_regions($type = 0, $parent = 0)
{
    $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";
    
//    if ($type >= 2) {
//      db_create_in($region_id_list);
//    	$sql = " SELECT re.region_id, re.region_name, s.support_cod, s.support_no_cod 
//            FROM  {$GLOBALS['ecs']->table('shipping')}  AS s
//            INNER JOIN {$GLOBALS['ecs']->table('shipping_area')} AS a ON a.shipping_id = s.shipping_id 
//            INNER JOIN {$GLOBALS['ecs']->table('area_region')} AS r ON r.shipping_area_id = a.shipping_area_id
//            INNER JOIN {$GLOBALS['ecs']->table('region')} as re ON r.region_id = re.region_id
//            WHERE s.enabled = 1
//            AND re.parent_id = '$parent' AND re.region_type = '$type'
//            ";
//    }
//
    return $GLOBALS['db']->GetAll($sql);
}

/**
 * 根据区域名称 返回区域
 */
function get_region_by_name($region_name)
{
    $sql = sprintf("SELECT region_id, region_name FROM %s WHERE region_name = '%s'" , 
        $GLOBALS['ecs']->table('region'),
        $GLOBALS['db']->escape_string($region_name)
    );
    return $GLOBALS['db']->GetRow($sql);
}


/**
 * 获得配送区域中指定的配送方式的配送费用的计算参数
 *
 * @access  public
 * @param   int     $area_id        配送区域ID
 *
 * @return array;
 */
function get_shipping_config($area_id)
{
    /* 获得配置信息 */
    $sql = 'SELECT configure FROM ' . $GLOBALS['ecs']->table('shipping_area') . " WHERE shipping_area_id = '$area_id'";
    $cfg = $GLOBALS['db']->getOne($sql);

    if ($cfg)
    {
        /* 拆分成配置信息的数组 */
        $arr = unserialize($cfg);
    }
    else
    {
        $arr = array();
    }

    return $arr;
}

/**
 * 初始化会员数据整合类
 *
 * @access  public
 * @return  object
 */
function &init_users()
{
    include_once(ROOT_PATH . 'includes/modules/integrates/' . $GLOBALS['_CFG']['integrate_code'] . '.php');

    $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);

    @$cls = new $GLOBALS['_CFG']['integrate_code']($cfg['db_host'], $cfg['db_name'], $cfg['db_user'],
            $cfg['db_pass'], $cfg['prefix'], $cfg['cookie_domain'], $cfg['cookie_path'], $cfg['db_chartset'], $cfg['cookie_prefix']);

    return $cls;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @return  mix
 */
function cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('category') . ' ORDER BY parent_id, sort_order ASC';
        $res = $GLOBALS['db']->getAllCached($sql);
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= ($var['is_leaf'] == 1) ? 'class="leafCat">' : '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars($var['cat_name']) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('category', array('cid' => $value['cat_id']), $value['cat_name']);
        }

        return $options;
    }
}

/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options($spec_cat_id, $arr)
{

    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        while (!empty($arr))
        {
        	// {{{ add at 2007-12-23
        	if (++$_k > 1000000)
        	{
        		break;
        	}
        	// }}}
            foreach ($arr AS $key => $value)
            {
                $cat_id = $value['cat_id'];
                if ($level == 0 && $last_cat_id == 0)
                {
                    if ($value['parent_id'] > 0)
                    {
                        break;
                    }

                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['is_leaf'] == 1)
                    {
                        continue;
                    }
                    $last_cat_id  = $cat_id;
                    $cat_id_array = array($cat_id);
                    $level_array[$last_cat_id] = ++$level;
                    continue;
                }

                if ($value['parent_id'] == $last_cat_id)
                {
                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['is_leaf'] == 0)
                    {
                        if (end($cat_id_array) != $last_cat_id)
                        {
                            $cat_id_array[] = $last_cat_id;
                        }
                        $last_cat_id    = $cat_id;
                        $cat_id_array[] = $cat_id;
                        $level_array[$last_cat_id] = ++$level;
                    }
                }
                elseif ($value['parent_id'] > $last_cat_id)
                {
                    break;
                }
            }

            $count = count($cat_id_array);
            if ($count > 1)
            {
                $last_cat_id = array_pop($cat_id_array);
            }
            elseif ($count == 1)
            {
                if ($last_cat_id != end($cat_id_array))
                {
                    $last_cat_id = end($cat_id_array);
                }
                else
                {
                    $level = 0;
                    $last_cat_id = 0;
                    $cat_id_array = array();
                    continue;
                }
            }

            if ($last_cat_id && isset($level_array[$last_cat_id]))
            {
                $level = $level_array[$last_cat_id];
            }
            else
            {
                $level = 0;
            }
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 获取网店帮助及网店帮助文章数组。
 *
 * @access  public
 *
 * @return array
 */
function shophelp_list()
{
    /* 获取文章 */
    $sql = 'SELECT article_id, author,title, cat_id, article_type, add_time FROM ' . $GLOBALS['ecs']->table('article') . " WHERE author = '_SHOPHELP' ORDER BY article_type DESC";
    $res = $GLOBALS['db'] -> GetAll($sql);

    /* 获取帮助分类 */
    $sql = 'SELECT cat_id, cat_name, sort_order FROM ' . $GLOBALS['ecs']->table('article_cat') . ' WHERE cat_type = 0 ORDER BY sort_order DESC ';
    $catlist = $GLOBALS['db'] -> GetAll($sql);

    $list= array();
    for ($i = 0, $count = count($catlist); $i < $count; $i++)
    {
        $list[] = array('article_id' => 0, 'title' => $catlist[$i]['cat_name'], 'cat_id' => $catlist[$i]['cat_id'], 'sort_order' => $catlist[$i]['sort_order']);
        for ($j = 0, $count2 = count($res); $j < $count2; $j++)
        {
            if ($res[$j]['cat_id'] == $catlist[$i]['cat_id'] )
            {
                $list[] = array( 'article_id' => $res[$j]['article_id'], 'title' => $res[$j]['title'], 'cat_id' => $res[$j]['cat_id'], 'article_type' => $res[$j]['article_type'], 'add_time' => date($GLOBALS['_CFG']['time_format'], $res[$j]['add_time']));
            }
        }
    }

    return $list;
}

/**
 * 获得服务器上的 GD 版本
 *
 * @access      public
 * @return      int         可能的值为0，1，2
 */
function gd_version()
{
    include_once(ROOT_PATH . 'includes/cls_image.php');

    $instance=new cls_image();
    return $instance->gd_version();
}

/**
 * 载入配置信息
 *
 * @access  public
 * @return  array
 */
function load_config()
{
    $arr = array();

    if ($_GET['do'] == 'clearcache') { // clear config cache by zwsun 2008-10-10
    	$GLOBALS['db']->setMaxCacheTime(0);
    }
    $sql = 'SELECT code, value FROM ' . $GLOBALS['ecs']->table('shop_config') . ' WHERE parent_id > 0';
    $res = $GLOBALS['db']->getAllCached($sql);

    foreach ($res AS $row)
    {
        $arr[$row['code']] = $row['value'];
    }

    /* 对数值型设置处理 */
    $arr['watermark_alpha']      = intval($arr['watermark_alpha']);
    $arr['market_price_rate']    = floatval($arr['market_price_rate']);
    $arr['currency_scale']       = intval($arr['currency_scale']);
    $arr['integral_scale']       = intval($arr['integral_scale']);
    $arr['integral_percent']     = intval($arr['integral_percent']);
    $arr['cache_time']           = intval($arr['cache_time']);
    $arr['thumb_width']          = intval($arr['thumb_width']);
    $arr['thumb_height']         = intval($arr['thumb_height']);
    $arr['image_width']          = intval($arr['image_width']);
    $arr['image_height']         = intval($arr['image_height']);
    $arr['best_number']          = intval($arr['best_number'])     > 0 ? intval($arr['best_number'])     : 3;
    $arr['new_number']           = intval($arr['new_number'])      > 0 ? intval($arr['new_number'])      : 3;
    $arr['hot_number']           = intval($arr['hot_number'])      > 0 ? intval($arr['hot_number'])      : 3;
    $arr['promote_number']       = intval($arr['promote_number'])  > 0 ? intval($arr['promote_number'])  : 3;
    $arr['top_number']           = intval($arr['top_number'])      > 0 ? intval($arr['top_number'])      : 10;
    $arr['history_number']       = intval($arr['history_number'])  > 0 ? intval($arr['history_number'])  : 5;
    $arr['comments_number']      = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
    $arr['article_number']       = intval($arr['article_number'])  > 0 ? intval($arr['article_number'])  : 5;
    $arr['page_size']            = intval($arr['page_size'])       > 0 ? intval($arr['page_size'])       : 10;
    $arr['bought_goods']         = intval($arr['bought_goods']);
    $arr['goods_name_length']    = intval($arr['goods_name_length']);
    $arr['top10_time']           = intval($arr['top10_time']);
    $arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
    $arr['no_picture']           = !empty($arr['no_picture']) ? str_replace('../', './', $arr['no_picture']) : 'images/no_picture.gif'; // 修改默认商品图片的路径
    $arr['integrate_code']       = !empty($arr['integrate_code']) ? $arr['integrate_code'] : 'ecshop';
    $arr['qq']                   = !empty($arr['qq']) ? $arr['qq'] : '';
    $arr['ww']                   = !empty($arr['ww']) ? $arr['ww'] : '';

    
    // 读取user_rank配置  rank_state = 0 启用
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('user_rank') . ' where rank_state = 0 order by min_points';
	//$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('user_rank') . ' order by min_points, min_price';
    $res = $GLOBALS['db']->getAllCached($sql);

    $rank_config = array();
    foreach ($res AS $row)
    {
    	$rank_config[$row['rank_id']] = $row;
    }
    $arr['rank_config'] = $rank_config;

    if (empty($arr['lang']))
    {
        // 默认语言为简体中文
        $arr['lang'] = 'zh_cn';
    }

    return $arr;
}

/**
 * 取得品牌列表
 * @return array 品牌列表 id => name
 */
function get_brand_list()
{
    $sql = 'SELECT brand_id, brand_name FROM ' . $GLOBALS['ecs']->table('brand') . ' ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $brand_list = array();
    foreach ($res AS $row)
    {
        $brand_list[$row['brand_id']] = $row['brand_name'];
    }

    return $brand_list;
}

/**
 * 获得某个分类下
 *
 * @access  public
 * @param   int     $cat
 * @return  array
 */
function get_brands($cat = 0, $app = 'brand')
{
    $children = ($cat > 0) ? ' AND ' . get_children($cat) : '';

    $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, COUNT(g.goods_id) AS goods_num, IF(b.brand_logo > '', '1', '0') AS tag ".
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                $GLOBALS['ecs']->table('goods') . " AS g ".
            "WHERE g.brand_id = b.brand_id $children AND is_show = 1 " .
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY tag DESC, b.sort_order ASC";

    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row AS $key => $val)
    {
        $row[$key]['url'] = build_uri($app, array('cid' => $cat, 'bid' => $val['brand_id']), $val['brand_name']);
    }

    return $row;
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 * @return  string
 */
function get_children($cat = 0)
{
    return 'g.cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(cat_list($cat, 0, false)))));
}

/**
 * 邮件发送
 *
 * @param: $name[string]        接收人姓名
 * @param: $email[string]       接收人邮件地址
 * @param: $subject[string]     邮件标题
 * @param: $content[string]     邮件内容
 * @param: $type[int]           0 普通邮件， 1 HTML邮件
 * 
 * @return boolean
 */
function send_mail($name, $email, $subject, $content, $type = 0, $party_id = NULL)
{
    /* 如果邮件编码不是utf8，创建字符集转换对象，转换编码 */
    if ($GLOBALS['_CFG']['mail_charset'] != 'UTF8')
    {
        include_once (ROOT_PATH . 'includes/iconv/cls_iconv.php');
        $iconv = new Chinese(ROOT_PATH);
        $name = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $name);
        $subject = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $subject);
        $content = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $content);
        $GLOBALS['_CFG']['shop_name'] = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $GLOBALS['_CFG']['shop_name']);
        $charset = $GLOBALS['_CFG']['mail_charset'];
    }
    else
    {
        $charset = 'UTF-8';
    }

    // ncchen 090309 添加邮件测试参数
    if ($GLOBALS['_CFG']['smtp_debug'] == 'debug')
    {
        // 保存测试邮件到本地
        if (stripos(PHP_OS, 'win') !== false)
        {
            $smtp_path = "F:/out/";
        }
        else
        {
            $smtp_path = $GLOBALS['_CFG']['smtp_debug_path'] != '' ? $GLOBALS['_CFG']['smtp_debug_path'] : "/tmp/mail_debug/";
        }
        return file_put_contents($smtp_path . strip_tags("{$email}_") . date("Y-m-d_H-i-s") .".htm", $content);
    }
    else
    {
        $shop_name = isset($GLOBALS['_CFG']['shop_name_'. $party_id]) ? $GLOBALS['_CFG']['shop_name_'. $party_id] : $GLOBALS['_CFG']['shop_name'] ;
        $smtp_mail = isset($GLOBALS['_CFG']['smtp_mail_'. $party_id]) ? $GLOBALS['_CFG']['smtp_mail_'. $party_id] : $GLOBALS['_CFG']['smtp_mail'] ;

        /* 邮件的头部信息 */
        $content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
        $headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
        $headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($name) . '?=' . '" <' . $email . '>';
        $headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($shop_name) . '?=' . '" <' . $smtp_mail . '>';
        $headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode($subject) . '?=';
        $headers[] = $content_type . '; format=flowed';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'Content-Disposition: inline';

        /* 获得邮件服务器的参数设置 */
        $params['host'] = $GLOBALS['_CFG']['smtp_host'];
        $params['port'] = $GLOBALS['_CFG']['smtp_port'];
        $params['user'] = $GLOBALS['_CFG']['smtp_user'];
        $params['pass'] = $GLOBALS['_CFG']['smtp_pass'];

        if (empty($params['host']) || empty($params['port']))
        {
            // 如果没有设置主机和端口直接返回 false
            $GLOBALS['err']->add($GLOBALS['_LANG']['smtp_setting_error']);
            return false;
        }
        else
        {
            if ($GLOBALS['_CFG']['mail_config'] == 'test')
            {
                add_mail_queue($name, $email, $subject, $content, $type);
                return true;
            }
            // 发送邮件
            include_once (ROOT_PATH . 'includes/cls_smtp.php');
            static $smtp;

            $send_params['recipients'] = $email;
            $send_params['headers'] = $headers;
            $send_params['from'] = $GLOBALS['_CFG']['smtp_mail'];
            $send_params['body'] = base64_encode($content);

            if (!isset($smtp))
            {
                $smtp = new smtp($params);
            }

            if ($smtp->connect() && $smtp->send($send_params))
            {
                mail_log($name, $email, $subject, $content, $type);
                return true;
            }
            else
            {
                $err_msg = $smtp->error_msg();
                if (empty($err_msg))
                {
                    $GLOBALS['err']->add('Unknown Error');
                }
                else
                {
                    if (strpos($err_msg, 'Failed to connect to server') !== false)
                    {
                        $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['smtp_connect_failure'], $params['host'] . ':' . $params['port']));
                    }
                    else
                        if (strpos($err_msg, 'AUTH command failed') !== false)
                        {
                            $GLOBALS['err']->add($GLOBALS['_LANG']['smtp_login_failure']);
                        } 
                        else if (strpos($err_msg, 'bad sequence of commands') !== false)
                        {
                            $GLOBALS['err']->add($GLOBALS['_LANG']['smtp_refuse']);
                        }
                        else
                        {
                            $GLOBALS['err']->add($err_msg);
                        }
                }

                return false;
            }
        }
    }
}

/**
 * 记录邮件日志
 *
 * @param string $name
 * @param string $email
 * @param string $subject
 * @param string $content
 * @param string $type
 */
 
function mail_log($name, $email, $subject, $content, $type) {
	if ($GLOBALS['_CFG']['mail_log'] == 'yes') {
		$sql = "INSERT INTO {$GLOBALS['ecs']->table('mail_log')} (`create`, subject, content, dest_name, dest_email, is_html) 
				  VALUES (NOW(), '{$subject}', '{$content}', '{$name}', '{$email}', '{$type}') ";
		$GLOBALS['db']->query($sql);
	}
}

/**
 * 添加邮件队列
 *
 * @param string $name
 * @param string $email
 * @param string $subject
 * @param string $content
 * @param string $type
 */
function add_mail_queue($name, $email, $subject, $content, $type) {
	$content = $GLOBALS['db']->escape_string($content); 
	$sql = "INSERT INTO {$GLOBALS['ecs']->table('mail_queue')} (`create`, `status`, subject, content, create_by, dest_name, dest_email, is_html) 
				VALUES (NOW(), 'WAIT', '{$subject}', '{$content}', '', '{$name}', '{$email}', '{$type}') ";
	$GLOBALS['db']->query($sql);
}

/**
 * 更新邮件队列
 *
 * @param int $ids
 * @param string $status
 * @return int
 */
function update_mail_queue($ids, $status) {
	if ($status != 'REJECT' && ($status == 'WAIT' || $GLOBALS['_CFG']['mail_config'] == 'test')) return -1;
	if ($status == 'SEND') {
		foreach ($ids as $id) {
			$sql = "SELECT dest_name, dest_email, subject, content, is_html 
				  FROM {$GLOBALS['ecs']->table('mail_queue')} 
				  WHERE  id = '{$id}' LIMIT 1 ";
			$mail = $GLOBALS['db']->getRow($sql);
			send_mail($mail['dest_name'], $mail['dest_name'], $mail['subject'], $mail['content'], $mail['is_html']);
		}
		
	}
	$sql_ids_in = db_create_in($ids, 'id');
	$sql = "UPDATE {$GLOBALS['ecs']->table('mail_queue')} SET `status` = '{$status}', `release` = NOW() WHERE {$sql_ids_in} LIMIT 1";
	$GLOBALS['db']->query($sql);
	return 1;
}

function list_mail_queue($status, $start, $size) {
	$sql = "SELECT * FROM {$GLOBALS['ecs']->table('mail_queue')} ".
			  ($status != null && $status != '' ? " WHERE `status` = '{$status}' ":""). 
			" ORDER BY id DESC
			 LIMIT {$start}, {$size}" ;
	$mail_queue['mail_list'] = $GLOBALS['db']->getAll($sql);
	$sqlc = "SELECT COUNT(*) FROM {$GLOBALS['ecs']->table('mail_queue')} ".
			  ($status != null && $status != '' ? " WHERE `status` = '{$status}' ":"");
	$mail_queue['mail_count'] = $GLOBALS['db']->getOne($sqlc);
	return $mail_queue;
}

/**
 * smarty 检索资源函数
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $tpl_source[string]       模板内容
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_get_template($tpl_name, &$tpl_source, &$smarty_obj)
{
    $party_id = (int)$smarty_obj->get_template_vars('_MAIL_TPL_PARTY_ID_');
    if ($party_id > 0) { 
        $cond = "AND " . party_sql('party_id', $party_id);
    }
    $sql = "SELECT template_content FROM {$GLOBALS['ecs']->table('mail_templates')} WHERE template_code = '$tpl_name' {$cond} ";
    if ($tpl_source = $GLOBALS['db']->getOne($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * smarty 请求资源的最后修改时间函数
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $tpl_timestamp[string]    UNIX 时间戳
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
    // 取得要调用模版的组织
    $party_id = (int)$smarty_obj->get_template_vars('_MAIL_TPL_PARTY_ID_');
    if ($party_id > 0) {
        $cond = "AND " . party_sql('party_id', $party_id);
    }
    $sql = "SELECT last_modify FROM {$GLOBALS['ecs']->table('mail_templates')} WHERE template_code = '$tpl_name' {$cond} ";
    if ($tpl_timestamp = $GLOBALS['db']->getOne($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * smarty 确认资源是否安全
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_get_secure($tpl_name, &$smarty_obj)
{
    /* 全部安全 */
    return true;
}

/**
 * smarty 确认资源是值的信任
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_get_trusted($tpl_name, &$smarty_obj)
{
    /* 不使用该函数 */
}

/**
 * smarty 检索资源函数
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $tpl_source[string]       模板内容
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_msg_get_template($tpl_name, &$tpl_source, &$smarty_obj)
{
    // 取得要调用模版的组织，默认为欧酷网
    $party_id = (int)$smarty_obj->get_template_vars('_MSG_TPL_PARTY_ID_');
    if ($party_id > 0) {
        $cond = "AND ". party_sql('party_id', $party_id);
    }
    $sql = "SELECT template_content FROM {$GLOBALS['ecs']->table('msg_templates')} WHERE template_code = '$tpl_name' {$cond}";
    if ($tpl_source = $GLOBALS['db']->getOne($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * smarty 请求资源的最后修改时间函数
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $tpl_timestamp[string]    UNIX 时间戳
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_msg_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
    // 取得要调用模版的组织，默认为欧酷网
    $party_id = (int)$smarty_obj->get_template_vars('_MSG_TPL_PARTY_ID_');
    if ($party_id > 0) {
        $cond = "AND ". party_sql('party_id', $party_id);
    }
    $sql = "SELECT last_modify FROM {$GLOBALS['ecs']->table('msg_templates')} WHERE template_code = '$tpl_name' {$cond}";
    if ($tpl_timestamp = $GLOBALS['db']->getOne($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * smarty 确认资源是否安全
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_msg_get_secure($tpl_name, &$smarty_obj)
{
    /* 全部安全 */
    return true;
}

/**
 * smarty 确认资源是值的信任
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function db_msg_get_trusted($tpl_name, &$smarty_obj)
{
    /* 不使用该函数 */
}

/**
 * 获取邮件模板
 *
 * @access  public
 * @param:  $tpl_name[string]       模板代码
 *
 * @return array
 */
function get_mail_template($tpl_name, $party_id = NULL)
{
    if ($party_id > 0) {
        $cond = "AND ". party_sql('party_id', $party_id);
    }
    $sql = "SELECT template_code, template_subject, is_html, is_send FROM {$GLOBALS['ecs']->table('mail_templates')} WHERE template_code = '$tpl_name' {$cond} LIMIT 1";
    return $GLOBALS['db']->GetRow($sql);
}

/**
 * 获取短信模板
 *
 * @access  public
 * @param:  $tpl_name[string]       模板代码
 * @param int $party_id
 *
 * @return array
 */
function get_msg_template($tpl_name, $party_id = NULL)
{
    if ($party_id > 0) {
        $cond = "AND ". party_sql('party_id', $party_id);
    }
    $sql = "SELECT template_code, template_subject, server_name, is_send FROM {$GLOBALS['ecs']->table('msg_templates')} WHERE template_code = '$tpl_name' {$cond} LIMIT 1";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 获得订单操作记录
 *
 * @access  public
 * @param   string  $order_sn           订单编号
 * @return  void
 */
function get_order_action($order_sn, $action = array(), $limit = 1)
{
    $cond = "1";
    foreach($action as $key => $value)
    {
        $cond .= sprintf(" AND '%s' = '%s'", $key, $value);
    }
    $sql = sprintf("SELECT oa.*, oi.order_sn FROM %s AS oi LEFT JOIN %s AS oa ON oi.order_id = oa.order_id WHERE oi.order_sn = '%s' AND (%s) ORDER BY action_time DESC",
        $GLOBALS['ecs']->table('order_info'),
        $GLOBALS['ecs']->table('order_action'),
        $GLOBALS['db']->escape_string($order_sn),
        $cond
    );

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $price_format = -1)
{
    if($price_format == -1)
    {
        $price_format = $GLOBALS['_CFG']['price_format'];
    }
    switch ($price_format)
    {
        case 0:
            $price = number_format($price, 2, '.', '');
            break;
        case 1: // 保留不为 0 的尾数
            $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

            if (substr($price, -1) == '.')
            {
                $price = substr($price, 0, -1);
            }
            break;
        case 2: // 不四舍五入，保留1位
            $price = substr(number_format($price, 2, '.', ''), 0, -1);
            break;
        case 3: // 直接取整
            $price = intval($price);
            break;
        case 4: // 四舍五入，保留 1 位
            $price = number_format($price, 1, '.', '');
            break;
        case 5: // 先四舍五入，不保留小数
            $price = round($price);
            break;
    }

    return sprintf($GLOBALS['_CFG']['currency_format'], $price);
}

/**
 * 返回订单中的虚拟商品
 *
 * @access  public
 * @param   int   $order_id   订单id值
 * @param   bool  $shipping   是否已经发货
 *
 * @return array()
 */
function get_virtual_goods($order_id, $shipping = false)
{
    if ($shipping)
    {
        $sql = 'SELECT goods_id, goods_name, send_number AS num, extension_code FROM '.
           $GLOBALS['ecs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND is_real = 0 AND send_number > 0 AND extension_code > ''";
    }
    else
    {
        $sql = 'SELECT goods_id, goods_name, (goods_number - send_number) AS num, extension_code FROM '.
           $GLOBALS['ecs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND is_real = 0 AND (goods_number - send_number) > 0 AND extension_code > '' ";
    }
    $res = $GLOBALS['db']->getAll($sql);

    $virtual_goods = array();
    foreach ($res AS $row)
    {
        $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
    }

    return $virtual_goods;
}

/**
 *  虚拟商品发货
 *
 * @access  public
 * @param   array  $virtual_goods   虚拟商品数组
 * @param   string $msg             错误信息
 * @param   string $order_sn        订单号。
 *
 * @return bool
 */
function virtual_goods_ship($virtual_goods, &$msg, $order_sn, $return_result = false)
{
    /* 取得虚拟商品需要那些插件支持 */
    $extension_code = array_unique(array_keys($virtual_goods));
    foreach ($extension_code AS $code)
    {
        if (file_exists(ROOT_PATH . 'plugins/' . $code . '/' . $code.'_inc.php'))
        {
            include_once(ROOT_PATH . 'plugins/' . $code . '/' . $code . '_inc.php');
            /* 存在语言项包含语言项 */
            if (file_exists(ROOT_PATH . 'plugins/' . $code . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php'))
            {
                include_once(ROOT_PATH . 'plugins/' . $code . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
            }
            $$code = new $code();
        }
        else
        {
            $msg = sprintf($GLOBALS['_LANG']['plugins_not_found'], $code);

            return false;
        }
    }

    foreach ($virtual_goods AS $code => $goods_list)
    {
        foreach ($goods_list AS $goods)
        {
            if (!$$code->shipping($goods, $order_sn, $msg))
            {
                return false;
            }
            else
            {
                if ($return_result)
                {
                    $msg .= $$code->result($order_sn, $goods);
                }
            }

        }
    }

    return true;
}

if (!function_exists('file_get_contents'))
{
    /**
     * 如果系统不存在file_get_contents函数则声明该函数
     *
     * @access  public
     * @param   string  $file
     * @return  mix
     */
    function file_get_contents($file)
    {
        if (($fp = @fopen($file, 'rb')) === false)
        {
            return false;
        }
        else
        {
            $fsize = @filesize($file);
            if ($fsize)
            {
                $contents = fread($fp, $fsize);
            }
            else
            {
                $contents = '';
            }
            fclose($fp);

            return $contents;
        }
    }
}

if (!function_exists('file_put_contents'))
{
    /**
     * 如果系统不存在file_put_contents函数则声明该函数
     *
     * @access  public
     * @param   string  $file
     * @param   mix     $data
     * @return  int
     */
    function file_put_contents($file, $data)
    {
        $contents = (is_array($data)) ? implode('', $data) : $data;

        if (($fp = @fopen($file, 'wb')) === false)
        {
            return false;
        }
        else
        {
            $bytes = fwrite($fp, $contents);
            fclose($fp);

            return $bytes;
        }
    }
}

if (!function_exists('floatval'))
{
    /**
     * 如果系统不存在 floatval 函数则声明该函数
     *
     * @access  public
     * @param   mix     $n
     * @return  float
     */
    function floatval($n)
    {
        return (float) $n;
    }
}

/**
 * 文件或目录权限检查函数
 *
 * @access          public
 * @param           string  $file_path   文件路径
 * @param           bool    $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
 *
 * @return          int     返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
 *                          返回值在二进制计数法中，四位由高到低分别代表
 *                          可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
 */
function file_mode_info($file_path)
{
    /* 如果不存在，则不可读、不可写、不可改 */
    if (!file_exists($file_path))
    {
        return false;
    }

    $mark = 0;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
    {
        /* 测试文件 */
        $test_file = $file_path . '/cf_test.txt';

        /* 如果是目录 */
        if (is_dir($file_path))
        {
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if ($dir === false)
            {
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if (@readdir($dir) !== false)
            {
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);

            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if ($fp === false)
            {
                return $mark; //如果目录中的文件创建失败，返回不可写。
            }
            if (@fwrite($fp, 'directory access testing.') !== false)
            {
                $mark ^= 2; //目录可写可读011，目录可写不可读 010
            }
            @fclose($fp);

            @unlink($test_file);

            /* 检查目录是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if ($fp === false)
            {
                return $mark;
            }
            if (@fwrite($fp, "modify test.\r\n") !== false)
            {
                $mark ^= 4;
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false)
            {
                $mark ^= 8;
            }
            @unlink($test_file);
        }
        /* 如果是文件 */
        elseif (is_file($file_path))
        {
            /* 以读方式打开 */
            $fp = @fopen($file_path, 'rb');
            if ($fp)
            {
                $mark ^= 1; //可读 001
            }
            @fclose($fp);

            /* 试着修改文件 */
            $fp = @fopen($file_path, 'ab+');
            if ($fp && @fwrite($fp, '') !== false)
            {
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false)
            {
                $mark ^= 8;
            }
        }
    }
    else
    {
        if (@is_readable($file_path))
        {
            $mark ^= 1;
        }

        if (@is_writable($file_path))
        {
            $mark ^= 14;
        }
    }

    return $mark;
}

function log_write($arg, $file = '', $line = '')
{
    if ((DEBUG_MODE & 4) != 4)
    {
        return;
    }

    $str = "\r\n-- ". date('Y-m-d H:i:s'). " --------------------------------------------------------------\r\n";
    $str .= "FILE: $file\r\nLINE: $line\r\n";

    if (is_array($arg))
    {
        $str .= '$arg = array(';
        foreach ($arg AS $val)
        {
            foreach ($val AS $key => $list)
            {
                $str .= "'$key' => '$list'\r\n";
            }
        }
        $str .= ")\r\n";
    }
    else
    {
        $str .= $arg;
    }

    file_put_contents(ROOT_PATH . 'data/log.txt', $str);
}

/**
 * 检查目标文件夹是否存在，如果不存在则自动创建该目录
 *
 * @access      public
 * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
 *
 * @return      bool
 */
function make_dir($folder)
{
    $reval = false;

    if (!file_exists($folder))
    {
        /* 如果目录不存在则尝试创建该目录 */
        @umask(0);

        /* 将目录路径拆分成数组 */
        preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        /* 如果第一个字符为/则当作物理路径处理 */
        $base = ($atmp[0][0] == '/') ? '/' : '';

        /* 遍历包含路径信息的数组 */
        foreach ($atmp[1] AS $val)
        {
            if ('' != $val)
            {
                $base .= $val;

                if ('..' == $val || '.' == $val)
                {
                    /* 如果目录为.或者..则直接补/继续下一个循环 */
                    $base .= '/';

                    continue;
                }
            }
            else
            {
                continue;
            }

            $base .= '/';

            if (!file_exists($base))
            {
                /* 尝试创建目录，如果创建失败则继续循环 */
                if (@mkdir($base, 0777))
                {
                    @chmod($base, 0777);
                    $reval = true;
                }
            }
        }
    }
    else
    {
        /* 路径已经存在。返回该路径是不是一个目录 */
        $reval = is_dir($folder);
    }

    clearstatcache();

    return $reval;
}

/**
 * 获得系统是否启用了 gzip
 *
 * @access  public
 *
 * @return  boolean
 */
function gzip_enabled()
{
    return ($GLOBALS['_CFG']['enable_gzip'] && function_exists('ob_gzhandler'));
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function addslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 * @author   Xuan Yan
 *
 * @return   mix                  对象或者数组
 */
function addslashes_deep_obj($obj)
{
    if (is_object($obj) == true)
    {
        foreach ($obj AS $key => $val)
        {
            $obj->$key = addslashes_deep($val);
        }
    }
    else
    {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       文件后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($is_cache = true, $ext = '')
{
    $dirs = array();

    if ($is_cache)
    {
        $dirs[] = ROOT_PATH . 'templates/caches/';
    }
    else
    {
        $dirs[] = ROOT_PATH . 'templates/compiled/';
        $dirs[] = ROOT_PATH . 'templates/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count   = 0;

    foreach ($dirs AS $dir)
    {
        $folder = @opendir($dir);

        if ($folder == false)
        {
            continue;
        }

        while ($file = readdir($folder))
        {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
                /* 如果有后缀判断后缀是否匹配 */
                if ($str_len > 0)
                {
                    $ext_str = substr($file, -$str_len);

                    if ($ext_str == $ext)
                    {
                        if (@unlink($dir . $file))
                        {
                            $count++;
                        }
                    }
                }
                else
                {
                    if (@unlink($dir . $file))
                    {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}

/**
 * 清除模版编译文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_compiled_files($ext = null)
{
    return clear_tpl_files(false, $ext);
}

/**
 * 清除缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_cache_files($ext = null)
{
    return clear_tpl_files(true, $ext);
}

/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = null)
{
    $count =  clear_tpl_files(false, $ext);
    $count += clear_tpl_files(true,  $ext);

    return $count;
}

/**
 * 页面上调用的js文件
 *
 * @access  public
 * @param   string      $files
 * @return  void
 */
function smarty_insert_scripts($args)
{
    static $scripts = array();

    $arr = explode(',', str_replace(' ','',$args['files']));

    $str = '';
    foreach ($arr AS $val)
    {
        if (in_array($val, $scripts) == false)
        {
            $scripts[] = $val;
            if ($val{0} == '.')
            {
                $str .= '<script type="text/javascript" src="' . $val . '"></script>';
            }
            else
            {
                $str .= '<script type="text/javascript" src="js/' . $val . '"></script>';
            }
        }
    }

    return $str;
}

/**
 * 创建分页的列表
 *
 * @access  public
 * @param   integer $count
 * @return  string
 */
function smarty_create_pages($params)
{
    extract($params);

    $str = '';
    $len = 10;

    if (empty($page))
    {
        $page = 1;
    }

    if (!empty($count))
    {
        $step = 1;
        $str .= "<option value='1'>1</option>";

        for ($i = 2; $i < $count; $i += $step)
        {
            $step = ($i >= $page + $len - 1 || $i <= $page - $len + 1) ? $len : 1;
            $str .= "<option value='$i'";
            $str .= $page == $i ? " selected='true'" : '';
            $str .= ">$i</option>";
        }

        if ($count > 1)
        {
            $str .= "<option value='$count'";
            $str .= $page == $count ? " selected='true'" : '';
            $str .= ">$count</option>";
        }
    }

    return $str;
}

/**
 * 重写 URL 地址
 *
 * @access  public
 * @param   string  $app    执行程序
 * @param   array   $params 参数数组
 * @param   string  $append 附加字串
 * @param   integer $page   页数
 * @return  void
 */
function build_uri($app, $params, $append = '', $page = 0, $size = 0)
{
    static $rewrite = NULL;

    if ($rewrite === NULL)
    {
        $rewrite = intval($GLOBALS['_CFG']['rewrite']);
    }

    $args = array('cid'   => 0,
                  'gid'   => 0,
                  'bid'   => 0,
                  'acid'  => 0,
                  'aid'   => 0,
                  'sid'   => 0,
                  'gbid'  => 0,
                  'sort'  => '',
                  'order' => ''
                );
    extract(array_merge($args, $params));

    $uri = '';
    switch ($app)
    {
        case 'category':
            if (empty($cid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'category-' . $cid;
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'category.php?id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'goods-' . $gid : 'goods.php?id=' . $gid;
            }

            break;
        case 'brand':
            if (empty($bid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'brand-' . $bid;
                    if (isset($cid))
                    {
                        $uri .= '-c' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'brand.php?id=' . $bid;
                    if (!empty($cid))
                    {
                        $uri .= '&amp;cat=' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'article_cat':
            if (empty($acid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'article_cat-' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'article_cat.php?id=' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'article':
            if (empty($aid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'article-' . $aid : 'article.php?id=' . $aid;
            }

            break;
        case 'group_buy':
            if (empty($gbid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&id=' . $gbid;
            }

            break;
        case 'snatch':
            if (empty($sid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid;
            }

            break;
        case 'search':
            break;
        default:
            return false;
            break;
    }

    if ($rewrite)
    {
        if ($rewrite == 2 && !empty($append))
        {
            $uri .= '-' . urlencode(preg_replace('/[\.|\/|\?|&|\+|\\\|\'|"|,]+/', '', $append));
        }

        $uri .= '.html';
    }

    return $uri;
}

/**
 * 格式化重量：小于1千克用克表示，否则用千克表示
 * @param   float   $weight     重量
 * @return  string  格式化后的重量
 */
function formated_weight($weight)
{
    $weight = round(floatval($weight), 3);
    if ($weight > 0)
    {
        if ($weight < 1)
        {
            /* 小于1千克，用克表示 */
            return intval($weight * 1000) . $GLOBALS['_LANG']['gram'];
        }
        else
        {
            /* 大于1千克，用千克表示 */
            return $weight . $GLOBALS['_LANG']['kilogram'];
        }
    }
    else
    {
        return 0;
    }
}

/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string       $str         待转换字串
 *
 * @return  string       $str         处理后字串
 */
function make_semiangle($str)
{
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                 '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                 'ｙ' => 'y', 'ｚ' => 'z',
                 '（' => '(', '）' => ')', '［' => '[', '］' => ']', '【' => '[',
                 '】' => ']', '〖' => '[', '〗' => ']', '「' => '[', '」' => ']',
                 '『' => '[', '』' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                 '》' => '>',
                 '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                 '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
                 '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
                 '＂' => '"', '＇' => '`', '｀' => '`', '｜' => '|', '〃' => '"',
                 '　' => ' ');

    return strtr($str, $arr);
}

/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '')
{
    if ($realname)
    {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    }
    else
    {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file)
    {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    }
    else
    {
        if (stristr($filename, ROOT_PATH) === false)
        {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                $extname == 'xls' || $extname == 'txt'  || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                $extname == 'pdf' || $extname == 'rm'   || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                $extname == 'swf' || $extname == 'chm'  || $extname == 'sql' || $extname == 'cert')
            {
                $format = $extname;
            }
        }
        else
        {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2 )
    {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt')
        {
            $format = 'mid';
        }
        elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav')
        {
            $format = 'wav';
        }
        elseif (substr($str ,0, 3) == "\xFF\xD8\xFF")
        {
            $format = 'jpg';
        }
        elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt')
        {
            $format = 'gif';
        }
        elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
        {
            $format = 'png';
        }
        elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt')
        {
            $format = 'bmp';
        }
        elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt')
        {
            $format = 'swf';
        }
        elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0")
        {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc')
            {
                $format = 'doc';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls')
            {
                $format = 'xls';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt')
            {
                $format = 'ppt';
            }
        } elseif (substr($str ,0, 4) == "PK\x03\x04")
        {
            $format = 'zip';
        } elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt')
        {
            $format = 'rar';
        } elseif (substr($str ,0, 4) == "\x25PDF")
        {
            $format = 'pdf';
        } elseif (substr($str ,0, 3) == "\x30\0x82\0x0a")
        {
            $format = 'cert';
        } elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt')
        {
            $format = 'chm';
        } elseif (substr($str ,0, 4) == "\x2ERMF")
        {
            $format = 'rm';
        } elseif ($extname == 'sql')
        {
            $format = 'sql';
        } elseif ($extname == 'txt')
        {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false)
    {
        $format = '';
    }

    return $format;
}

/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @access      public
 * @param       string      string  内容
 * @return      string
 */
function mysql_like_quote($str)
{
    return strtr($str, array("\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%'));
}

/**
 * 获取服务器的ip
 *
 * @access      public
 *
 * @return string
 **/
function real_server_ip()
{
    static $serverip = NULL;

    if ($serverip !== NULL)
    {
        return $serverip;
    }

    if (isset($_SERVER))
    {
        if (isset($_SERVER['SERVER_ADDR']))
        {
            $serverip = $_SERVER['SERVER_ADDR'];
        }
        else
        {
            $serverip = '0.0.0.0';
        }
    }
    else
    {
        $serverip = getenv('SERVER_ADDR');
    }

    return $serverip;
}

// {{{ add funciton
function pp() {
	$argvs = func_get_args();
	echo '<div style="text-align: left;">';
	foreach ($argvs as $k => $v) {
		echo "<xmp>";
		print_r($v);
		echo "</xmp>";
	}
	echo '</div>';
}
function vv() {
	$argvs = func_get_args();
	echo '<div style="text-align: left;">';
	foreach ($argvs as $k => $v) {
		echo "<xmp>";
		var_dump($v);
		echo "</xmp>";
	}
	echo '</div>';
}


// 注册函数
function to_goods_path ($params) {
	include_once('lib_oukoo.php');
	extract($params); 
	if(empty($goods_id))
		return '';
	else
		return toGoodsPath($goods_id);
}

function to_bj_goods_path ($params) {
	include_once('lib_oukoo.php');
	extract($params); 
	if(empty($goods_id))
		return '';
	else
		return toBiaojuGoodsPath($goods_id);
}

function to_bj_store_path ($params) {
	include_once('lib_oukoo.php');
	extract($params); 
	if(empty($store_id))
		return '';
	else
		return toBiaojuStorePath($store_id);	
}

// 排序函数
function sort_array_tree(&$arr, $key1, $key2) {
  $result = array();
  $added = array();
  
  $parents = array();
  foreach ($arr as $key => $value) {
    if ($value[$key2] == 0) {
      $parents[] = $value;
      $added[$key] = 1;
    }
  }
  
  foreach ($parents as $key => $value) {
    $result[] = $value;
    $pkey = $value[$key1];
    foreach ($arr as $key0 => $value0) {
      if ($added[$key0] == 1)  continue;
      if ($pkey == $value0[$key2]) {
        $result[] = $value0;
        $added[$key0] = 1;
      }
    }
  }
  
  // 处理剩下的
  foreach ($arr as $key => $value) {
    if ($added[$key] != 1) {
      $result[] = $value;
    }
  }
  return $result;
}


// 添加参数到url
function add_param_in_url($url, $param_name, $param_value) {
	$param_name = urlencode($param_name);
	$param_value = urlencode($param_value);
	if (strpos($url, '?') === false) {
		$new_url = "$url?$param_name=$param_value";
	} else {
		if (strpos($url, $param_name) === false) {
			$new_url = "$url&$param_name=$param_value";
		} else {
			$pos = strpos($url, $param_name);
			$next = strpos($url, '&', $pos);
			if ($next === false) {
				$new_url = substr($url, 0, $pos) . $param_name . '=' . $param_value;	
			} else {
				$new_url = substr($url, 0, $pos) . $param_name . '=' . $param_value . substr($url, $next);
			}
		}
	}
	return $new_url;
}

// 移除url的参数
function remove_param_in_url($url, $param_name) {
	$pos = strpos($url, $param_name);
	if ($pos === false) {
		return $url;
	}
	$pre_char = $url[$pos - 1];
	$next_and = strpos($url, '&', $pos);
	
	if ($next_and !== false) {
		$new_url = substr($url, 0, $pos) . substr($url, $next_and + 1, strlen($url));
	} else {
		$new_url = substr($url, 0, $pos - 1);
	}
	return $new_url;
}
// }}}


/**
 * 发送手机短信
 *
 * @param string $msg 短消息
 * @param mix $dest_mobile 目标手机号码，可以是一个字符串作为一个号码，也可以是一个数组
 * @param int $party_id 短信用户
 * @param string $server 短息服务提供商
 * 
 * @return int $result 
 */
function send_message($msg, $dest_mobile, $party_id = NULL, $server = 'emay') {
    if($server=='crsms'){
        require_once(dirname(__FILE__) . '/../'.'admin/includes/lib_crsms.php');
        return send_message_with_crsms($msg,$dest_mobile,$party_id);
    }

	require_once(dirname(__FILE__) . '/../'.'admin/includes/cls_message2.php');
	require_once(dirname(__FILE__) . '/../'.'admin/includes/cls_yidu_post_message.php');

	//初始化亿度短信客户端(群发)
	$yidu_message_url = trim(YD_MESSAGE_URL);
	$yidu_message_serialnumber = trim(YD_MESSAGE_SERIALNUMBER);
	$yidu_message_password = trim(YD_MESSAGE_PASSWORD);
	$yidu_message_sessionkey = trim(YD_MESSAGE_SESSIONKEY);
	$yidu_message_client = new YiduPostMessageClient($yidu_message_url,$yidu_message_serialnumber,$yidu_message_password,$yidu_message_sessionkey);
		
	//初始化亿度短信客户端(单发)
	$yidu_single_message_url = trim(YD_SINGLE_MESSAGE_URL);
	$yidu_single_message_serialnumber = trim(YD_SINGLE_MESSAGE_SERIALNUMBER);
	$yidu_single_message_password = trim(YD_SINGLE_MESSAGE_PASSWORD);
	$yidu_single_message_sessionkey = trim(YD_SINGLE_MESSAGE_SESSIONKEY);
	$yidu_single_message_client = new YiduPostMessageClient($yidu_single_message_url,$yidu_single_message_serialnumber,
									$yidu_single_message_password,$yidu_single_message_sessionkey);
	
	//初始化亿美短信客户端
	$emay_message_url = trim(MESSAGE_URL);
	$emay_message_serialnumber = trim(MESSAGE_SERIALNUMBER);
	$emay_message_password = trim(MESSAGE_PASSWORD);
	$emay_message_sessionkey = trim(MESSAGE_SESSIONKEY);
	$emay_message_client = new Client($emay_message_url,$emay_message_serialnumber,$emay_message_password,$emay_message_sessionkey);
	$emay_message_client->setOutgoingEncoding("UTF-8");
	
	if($server=='yidu') {
		$MessageClient = $yidu_message_client;
		$message_serialnumber = $yidu_message_serialnumber;
	} else if($server=='yiduSingle') {
		$MessageClient = $yidu_single_message_client;
		$message_serialnumber = $yidu_single_message_serialnumber;
	}
	else if($server=='emay') {
		$MessageClient = $emay_message_client;
		$message_serialnumber = $emay_message_serialnumber;
	}
	
	global $db;
	if ($MessageClient == null || $dest_mobile == '') {
	    return false;
	}

	// 判断短信服务提供商
	if (!in_array($server, array('yidu', 'emay', 'yiduSingle'))) {
		$server = 'emay';
	}

	// 过滤短信信息
	$msg = str_replace("移动", "移 动", $msg);
	$msg = str_replace("付款", "付 款", $msg);
	if ($server == "nineorange") {
		$msg = str_replace("1258", "12 58", $msg);
		$msg = str_replace("1259", "12 59", $msg);
	}

	// 检查手机号码
	$dest_mobile_array = array();
	$dest_mobile = is_array($dest_mobile) ? $dest_mobile : array($dest_mobile);
	foreach ($dest_mobile as $mobile) {
        if (check_phonenumber($mobile)) {
            $dest_mobile_array[] = cleanup_phonenumber($mobile); //对号码进行一次处理
        }
	}
	
	//得到用户
	if(is_null($party_id)) {
		$party_id=1;
	}
	$sql = "select user_id from message.message_user where party_id='{$party_id}' limit 1";
	$user_id = $db->getOne($sql);
	if (is_null($user_id)) $user_id = 1;
	print date("Y-m-d H:i:s") . " " . "begin sendSMS" . "\r\n";
	// 调用短信发送API
	$result = false;
	if (!empty($dest_mobile_array)) {
		$send_result = $MessageClient->sendSMS($dest_mobile_array, $msg);
			
		if(!is_numeric($send_result)){
			$send_result = 1; 
		}
		if ($send_result<>0) {
			$send_result = 1;
		} 
		
		foreach ($dest_mobile_array as $key => $mobile) {
			$sql = "insert into message.message_history
                         (result, type, send_time, dest_mobile, user_id, content, server_name) 
                    values 
                         ({$send_result}, 'BATCH', now(), '{$mobile}', {$user_id}, '{$msg}', '{$server}')   
            ";
			$db->query ( $sql );
        }
	}
	
	print date("Y-m-d H:i:s") . " " . "begin getBalance" . "\r\n";
	    //查询余额
    $balance = $MessageClient->getBalance();
    $sql = "insert into message.message_balance
                   (server_name, serial_number, balance, created_stamp, last_update_stamp)
            values ('{$server}', '{$message_serialnumber}', {$balance}, now(), now())";
    $db->query($sql);
    return $send_result;
}

//In admin/includes/lib_crsms.php
//function send_message_with_crsms($msg, $dest_mobile, $sign = '乐其', &$response=null)


/**
 * 获取短信余额
 *
 * @param string $serverName 服务商
 * @return float
 */
function get_message_remainder_amount($serverName="nineorange") { // 此函数已经被遗弃
	global $message_client, $application_key;
	if ($message_client == null || $application_key[1] == null)
		return false;
	return $message_client->getRemainedAmount($application_key[1], $serverName);
}
/**
 * 亿美短信注册
 *
 * @return int
 */
function regist_message() { // 此函数已经被遗弃
	global $message_client, $application_key;
	if ($message_client == null || $application_key[1] == null)
		return -1;
	return $message_client->registEx($application_key[1], 'emay');
}
/**
 * 亿美短信注销
 *
 * @return int
 */
function logout_message() {
	global $message_client, $application_key;
	if ($message_client == null || $application_key[1] == null)
		return -1;
	return $message_client->logout($application_key[1], 'emay');
}

/**
 * 手机号码处理
 *
 * @param mix $mobile 用户提交的电话号码
 * @return mix $return_mobile 处理过的手机号码
 */
function cleanup_phonenumber($mobile) {
  static $SBC_case_array = array('１','２','３','４','５','６','７','８','９','０');
  static $DBC_case_array = array('1','2','3','4','5','6','7','8','9','0');
  if (is_array($mobile)) {
    return array_map('cleanup_phonenumber', $mobile);
  } else {
  	$return_mobile = str_replace($SBC_case_array, $DBC_case_array, $mobile); // 全角转成半角的
    $return_mobile = preg_replace('/[^0-9]/','',$return_mobile);//去掉里面非数字的
    return $return_mobile;
  }
}

/**
 * 检查电话号码
 *
 * @param string $mobile 用户提交的电话号码
 * @return boolen $return 检查是否合法
 */
function check_phonenumber($mobile) {
  $mobile = cleanup_phonenumber($mobile);
  if (!$mobile) {
  	return false;
  }
  
  $mobile = (string) $mobile;
  if (substr($mobile,0,2) == '13' || substr($mobile,0,2) == '15') {
  	if (str_len($mobile) != 11) {
  		return false;
  	}
  }
  
  return true;
}

/**
 * 获得一段时间内商品各种颜色的价格
 *
 * @param int $goods_id 商品的id
 * @param int $day 天数
 * @return array 结果数组
 */
function get_goods_price_history($goods_id, $day = 30) {
  global $ecs, $db;
  $one_day = 24 * 3600;
  $time_offset = 8 * 2400;
  
  $goods_id = intval($goods_id);
  $now = time() + $time_offset;
  $past = $now - ($now % $one_day)  - $day * $one_day;
  $past_day = date("Y-m-d", $past);
  
  $sql = "SELECT h.goods_style_id, h.shop_price, h.price_date FROM {$ecs->table('history_goods_price')} h WHERE goods_id = $goods_id AND price_date >= '$past_day' ORDER BY price_date ASC, goods_style_id DESC ";
  $goods_change = $db->getAll($sql);
  $goods_price_history = array();
  foreach ($goods_change as $goods_change_item) {
    $goods_style_id = $goods_change_item['goods_style_id'];
    $goods_price_history[$goods_style_id][] = $goods_change_item;
  }
  return $goods_price_history;  
}

/**
 * 当前时间ems货到付款是否派送
 *
 * @author ncchen
 * @return boolean
 */
function is_emscod_deliver() {
	$w = date('w');
	$g = date('G');
	if ($w == 6 || ($w == 5 && $g >= 17) || ($w == 0 && $g <= 17)) {
		return false;
	}
	return true;
}

/**
 * 是否是 POST 请求
 * @author Zandy
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

/**
 * 去掉转义
 * @author Zandy
 * 
 * @param mixed $obj
 * @param int $case
 */
function ZY_stripSlashes($obj, $case = 0)
{
    $strip = false;
    switch ($case) {
        case 1:
            if ((function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())) {
                $strip = true;
            }
            break;
        case 2:
            $strip = true;
            break;
        default:
            $strip = false;
            break;
    }
    v($strip);
    if ($strip) {
        $o = array(
            $obj
        );
        array_walk_recursive($o, create_function('&$item', '$item = stripslashes($item);'));
        $obj = $o[0];
    }
    return $obj;
}

/**
 * 面单号检查
 * @param int $shipping_id 快递方式（如EMS_货到付款）
 * @param string $tracking_number  面单号
 */
function check_tracking_number($shipping_id, $tracking_number){
    global $db;
    if ($tracking_number == '') {
        $message = "面单号为空，请填写正确的面单号";
    }
    $sql = "
        SELECT default_carrier_id FROM ecshop.ecs_shipping WHERE shipping_id = '{$shipping_id}';
    ";
    $carrier_id = $db->getOne($sql);
    if (empty($carrier_id)) {
        $message = "carrier_id为空";
    }
    $flag = true;
    switch($carrier_id) {
        case '3'://圆通
          	if (!preg_match("/^(0|1|2|3|4|5|6|7|8|9|S|E|D|F|G|V|W|e|d|f|g|s|v|w)[0-9]{9}([0-9]{2})?([0-9]{6})?$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '5'://宅急送10位0,1,9开头
            if (!preg_match("/^(0|1|9)[0-9]{9}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '15':// 宅急送COD
        	if (!preg_match("/^(0|1|9)[0-9]{9}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '44'://顺丰快递（陆运）
        case '10'://顺丰快递
        case '17'://顺丰快递COD
        	if (!preg_match("/^\d{12}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        
        case '9'://邮政EMS13位ER，EQ，ET，EF，开头
        	if(shipping_id==118){
        		if (!preg_match("/^(50|51)[0-9]{11}$/", $tracking_number)) {
                    $flag = false;
                }
        	}
        	else{
        		if (!preg_match("/^[A-Z]{2}[0-9]{9}[A-Z]{2}$|^[0-9]{13}$/", $tracking_number)) {
        			$flag = false;
                }
        	}
        	 break;
        case '14'://邮政COD
            if (!preg_match("/^[A-Z]{2}[0-9]{9}[A-Z]{2}$|^[0-9]{13}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '29':   // 韵达快运13位12,16开头
            if (!preg_match("/^[\s]*[0-9]{13}[\s]*$/", $tracking_number)) {
            	$flag = false;
            }
            break;
        case '28':   // 汇通快运
            if (!preg_match("/^(A|B|C|D|E|H|0)(D|X|[0-9])(A|[0-9])[0-9]{10}$|^(21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39)[0-9]{10}$|^(50|70)[0-9]{12}$/", $tracking_number)) {
                $flag = false;
            }
        break;
       case '20'://申通快运12位268,368,468,568,668开头
        	if (!preg_match("/^(229|268|888|588|688|368|468|568|668|768|868|968|220|227)[0-9]{9}$|^(229|268|888|588|688|368|468|568|668|768|868|968|220)[0-9]{10}$|^(STO)[0-9]{10}$/", $tracking_number)) {
        		$flag = false;
        	}
        break;
       case '36'://E邮宝快递13位
    	   if (!preg_match("/^[0-9]{13}$/", $tracking_number)) {
    		   $flag = false;
    	   }
    	break;
	   case '40'://上饶运输公司
			if (!preg_match("/^(JBLGX|GALLOGX)[0-9]{10}$/", $tracking_number)) {
				$flag = false;
			}
		break;
	   case '41'://中通快递
            if (!preg_match("/^((618|680|778|768|688|618|828|988|118|888|571|518|010|628|205|880|717|718|719|728|738|761|762|763|701|757|358|359|530)[0-9]{9})$|^((36|37|40)[0-9]{10})$|^((1)[0-9]{12})$|^((2008|2010|8050|7518)[0-9]{8})$/", $tracking_number)) {
		    	$flag = false;
		    }
        break; 
	   case '43'://EMS经济型
	   		if (!preg_match("/^(50|51)[0-9]{11}$/", $tracking_number)) {
	   			$flag = false;
	   		}
	   break;
	   case '45'://邮政小包
	   		if (!preg_match("/^[GA]{2}[0-9]{9}([2-5][0-9]|[1][1-9]|[6][0-5])$|^[99]{2}[0-9]{11}$|^[96]{2}[0-9]{11}$/", $tracking_number)) {
	   			$flag = false;
	   		}
	   break;
                 
    }
    if (!$flag) {
        sys_msg('提醒：运单号和发货的类型不匹配!');
    }
}

