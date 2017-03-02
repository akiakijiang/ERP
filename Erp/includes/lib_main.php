<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 更新用户SESSION,COOKIE及登录时间、登录次数。
 *
 * @access  public
 * @return  void
 */
function update_user_info()
{
    if (!$_SESSION['user_id'])
    {
        return false;
    }

    /* 查询会员信息 */
    $time = date('Y-m-d');
    $sql = 'SELECT u.user_money, u.pay_points, u.user_rank, u.rank_points, u.last_time, u.last_ip'.
            ' FROM ' .$GLOBALS['ecs']->table('users'). ' AS u ' .
            " WHERE u.user_id = '$_SESSION[user_id]'";
    if ($row = $GLOBALS['db']->getRow($sql))
    {
        /* 更新SESSION */
        $_SESSION['user_money']  = $row['user_money'];
        $_SESSION['user_points'] = $row['pay_points'];
        $_SESSION['user_bonus']  = 0;
        $_SESSION['last_time']   = $row['last_time'];
        $_SESSION['last_ip']     = $row['last_ip'];
        
        /* 取得用户等级和折扣 */
        include_once(ROOT_PATH . 'includes/lib_oukoo.php');
        if ($row['user_rank']) {
        	$_SESSION['rank_id'] = $row['user_rank'];

        } else {
        	$_SESSION['rank_id'] = getRankIdByPoints($_SESSION['user_id']); 
        }
        $_SESSION['pay_points'] = $row['pay_points'];
        $_SESSION['rank_points'] = $row['rank_points'];
    }

    /* 更新登录时间，登录次数及登录ip */
    $sql = "UPDATE " .$GLOBALS['ecs']->table('users'). " SET".
           " visit_count = visit_count + 1, ".
           " last_ip = '" .real_ip(). "',".
           " last_time = '" .date('Y-m-d H:i:s'). "'".
           " WHERE user_id = '" . $_SESSION['user_id'] . "'";
    $GLOBALS['db']->query($sql);

    /* 更新Cookie */
    setcookie('ECS[username]', $_SESSION['user_name'], time() + 3600 * 24 * 30, '/', COOKIE_DOMAIN);
}

/**
 *  获取用户信息数组
 *
 * @access  public
 * @param
 *
 * @return array        $user       用户信息数组
 */
function get_user_info($id=0)
{
	#die('error in file: '.__FILE__);

    if ($id == 0)
    {
        $id = $_SESSION['user_id'];
    }
    $time = date('Y-m-d');
    $sql  = 'SELECT u.user_id, u.email, u.sex, u.user_name, u.user_money, u.pay_points'.
            ' FROM ' .$GLOBALS['ecs']->table('users'). ' AS u ' .
            " WHERE u.user_id = '$id'";
    // {{{ membership add by Zandy at 2007-12-20
    $sql  = 'SELECT * '.
            ' FROM ' .$GLOBALS['ecs']->table('users'). ' AS u ' .
            " WHERE u.user_id = '$id'";
    // }}}
    $user = $GLOBALS['db']->getRow($sql);

    $user['username']    = $user['user_name'];
	$user['sex']    = $user['sex'];
    $user['user_points'] = $user['pay_points'] . $GLOBALS['_CFG']['integral_name'];
    $user['user_money']  = price_format($user['user_money']);
    $user['user_bonus']  = price_format(0);

    return $user;
}

/**
 * 调用主导航上的所有商品分类
 *
 * @access  public
 * @return  array
 */
function get_navigator()
{
    $iscategory = (basename($_SERVER['PHP_SELF']) == 'ModList.php') ? 1 : 0;

    /* 获得导航上的商品分类 */
    $sql = 'SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('category') .
            ' WHERE show_in_nav = 1 ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $nav = array();
    foreach ($res AS $idx => $row)
    {
        $nav[$idx]['label'] = htmlspecialchars($row['cat_name']);
        $nav[$idx]['cat_id'] = htmlspecialchars($row['cat_id']);
        $nav[$idx]['url']   = build_uri('ModList', array('bid'=> $row['cat_id'],'BrandId'=> $row['cat_id'],'Action'=>'ModList'), $row['cat_name']);

        if ($iscategory)
        {
            $nav[$idx]['id'] = $row['cat_id'];
        }
    }

    /* 获得导航上的文章分类 */
    $sql = 'SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('article_cat') .
            ' WHERE show_in_nav = 1 ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res AS $row)
    {
        $nav[] = array('label' => htmlspecialchars($row['cat_name']),
                         'url' => build_uri('ModList', array('bid'=> $row['cat_id'],'BrandId'=> $row['cat_id'],'Action'=>'ModList'), $row['cat_name']));
    }

    return $nav;
}

/**
 * 取得当前位置和页面标题
 *
 * @access  public
 * @param   integer     $cat    分类编号（只有商品及分类、文章及分类用到）
 * @param   string      $str    商品名、文章标题或其他附加的内容（无链接）
 * @return  array
 */
function assign_ur_here($cat = 0, $str = '')
{
    /* 取得文件名 */
    $filename = substr(basename($_SERVER['PHP_SELF']), 0, -4);

    /* 初始化“页面标题”和“当前位置” */
    $page_title = $GLOBALS['_CFG']['shop_title'];
    $ur_here    = '<a href=".">' . $GLOBALS['_LANG']['home'] . '</a>';

    /* 根据文件名分别处理中间的部分 */
    if ($filename != 'index')
    {
        /* 处理有分类的 */
        if (in_array($filename, array('category', 'goods', 'article_cat', 'article')))
        {
            /* 商品分类或商品 */
            if ('category' == $filename || 'goods' == $filename)
            {
                if ($cat > 0)
                {
                    $cat_arr = get_parent_cats($cat);

                    $key     = 'cid';
                    $type    = 'category';
                }
                else
                {
                    $cat_arr = array();
                }
            }
            /* 文章分类或文章 */
            elseif ('article_cat' == $filename || 'article' == $filename)
            {
                if ($cat > 0)
                {
                    $sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('article_cat') . " WHERE cat_id = '$cat'";
                    $cat_arr[0]['cat_id']   = $cat;
                    $cat_arr[0]['cat_name'] = $GLOBALS['db']->getOne($sql);

                    $key  = 'acid';
                    $type = 'article_cat';
                }
                else
                {
                    $cat_arr = array();
                }
            }

            /* 循环分类 */
            if (!empty($cat_arr))
            {
                krsort($cat_arr);
                foreach ($cat_arr AS $val)
                {
                    $page_title = htmlspecialchars($val['cat_name']) . '_' . $page_title;
                    $args       = array($key => $val['cat_id']);
                    $ur_here   .= ' <code>&gt;</code> <a href="' . build_uri($type, $args, $val['cat_name']) . '">' .
                                    htmlspecialchars($val['cat_name']) . '</a>';
                }
            }
        }
        /* 处理无分类的 */
        else
        {
            /* 团购 */
            if ('group_buy' == $filename)
            {
                $page_title = $GLOBALS['_LANG']['group_buy_goods'] . '_' . $page_title;
                $args       = array('gbid' => '0');
                $ur_here   .= ' <code>&gt;</code> <a href="group_buy.php">' .
                                $GLOBALS['_LANG']['group_buy_goods'] . '</a>';
            }
            /* 其他的在这里补充 */
        }
    }

    /* 处理最后一部分 */
    if (!empty($str))
    {
        $page_title  = $str . '_' . $page_title;
        $ur_here    .= ' <code>&gt;</code> ' . $str;
    }

    /* 返回值 */
    return array('title' => $page_title, 'ur_here' => $ur_here);
}

/**
 * 获得指定分类的所有上级分类
 *
 * @access  public
 * @param   integer $cat    分类编号
 * @return  array
 */
function get_parent_cats($cat)
{
    if ($cat == 0)
    {
        return array();
    }

    $arr = $GLOBALS['db']->GetAll('SELECT cat_id, cat_name, parent_id FROM ' . $GLOBALS['ecs']->table('category'));

    if (empty($arr))
    {
        return array();
    }

    $index = 0;
    $cats  = array();

    while (1)
    {
        foreach ($arr AS $row)
        {
            if ($cat == $row['cat_id'])
            {
                $cat = $row['parent_id'];

                $cats[$index]['cat_id']   = $row['cat_id'];
                $cats[$index]['cat_name'] = $row['cat_name'];

                $index++;
                break;
            }
        }

        if ($index == 0 || $cat == 0)
        {
            break;
        }
    }

    return $cats;
}

/**
 * 根据提供的数组编译成页面标题
 *
 * @access  public
 * @param   string  $type   类型
 * @param   array   $arr    分类数组
 * @return  string
 */
function build_pagetitle($arr, $type = 'category')
{
    $str = '';

    foreach ($arr AS $val)
    {
        $str .= htmlspecialchars($val['cat_name']) . '_';
    }

    return $str;
}

/**
 * 根据提供的数组编译成当前位置
 *
 * @access  public
 * @param   string  $type   类型
 * @param   array   $arr    分类数组
 * @return  void
 */
function build_urhere($arr, $type = 'category')
{
    krsort($arr);

    $str = '';
    foreach ($arr AS $val)
    {
        switch ($type)
        {
            case 'category':
            case 'brand':
                $args = array('cid' => $val['cat_id']);
                break;
            case 'article_cat':
                $args = array('acid' => $val['cat_id']);
                break;
        }

        $str .= ' <code>&gt;</code> <a href="' . build_uri($type, $args). '">' . htmlspecialchars($val['cat_name']) . '</a>';
    }

    return $str;
}

/**
 * 获得指定页面的动态内容
 *
 * @access  public
 * @param   string  $tmp    模板名称
 * @return  void
 */
function assign_dynamic($tmp)
{
    $sql = 'SELECT id, number, type FROM ' . $GLOBALS['ecs']->table('template') .
        " WHERE filename = '$tmp' AND type > 0";
    $res = $GLOBALS['db']->getAll($sql);

   foreach ($res AS $row)
    {
        switch ($row['type'])
        {
            case 1:
                /* 分类下的商品 */
                $GLOBALS['smarty']->assign('goods_cat_' . $row['id'], assign_cat_goods($row['id'], $row['number']));
            break;
            case 2:
                /* 品牌的商品 */
                $brand_goods = assign_brand_goods($row['id'], $row['number']);

                $GLOBALS['smarty']->assign('brand_goods_' . $row['id'], $brand_goods['goods']);
                $GLOBALS['smarty']->assign('goods_brand_' . $row['id'], $brand_goods['brand']);
            break;
            case 3:
                /* 文章列表 */
                $cat_articles = assign_articles($row['id'], $row['number']);

                $GLOBALS['smarty']->assign('articles_cat_' . $row['id'], $cat_articles['cat']);
                $GLOBALS['smarty']->assign('articles_' . $row['id'], $cat_articles['arr']);
            break;
        }
    }
}

/**
 * 分配文章列表给smarty
 *
 * @access  public
 * @param   integer     $id     文章分类的编号
 * @param   integer     $num    文章数量
 * @return  array
 */
function assign_articles($id, $num)
{
    $sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('article_cat') . " WHERE cat_id = '" . $id ."'";

    $cat['id']       = $id;
    $cat['name']     = $GLOBALS['db']->getOne($sql);
    $cat['url']      = build_uri('article_cat', array('acid' => $id), $cat['name']);

    $articles['cat'] = $cat;
    $articles['arr'] = get_cat_articles($id, 1, $num);

    return $articles;
}

/**
 * 分配帮助信息
 *
 * @access  public
 * @return  array
 */
function get_shop_help()
{
    $sql = 'SELECT c.cat_id, c.cat_name, c.sort_order, a.article_id, a.title, a.file_url, a.open_type ' .
            'FROM ' .$GLOBALS['ecs']->table('article'). ' AS a ' .
            'LEFT JOIN ' .$GLOBALS['ecs']->table('article_cat'). ' AS c ' .
            'ON a.cat_id = c.cat_id WHERE c.cat_type = 0 ' .
            'ORDER BY c.sort_order ASC, a.article_id';
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res AS $key => $row)
    {
        $arr[$row['cat_id']]['cat_name']                     = $row['cat_name'];
        $arr[$row['cat_id']]['article'][$key]['article_id']  = $row['article_id'];
        $arr[$row['cat_id']]['article'][$key]['title']       = $row['title'];
        $arr[$row['cat_id']]['article'][$key]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
            sub_str($row['title'], 0, $GLOBALS['_CFG']['article_title_length']) : $row['title'];
        $arr[$row['cat_id']]['article'][$key]['url']         = $row['open_type'] != 1 ?
            build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
    }

    return $arr;
}

/**
 * 创建分页信息
 *
 * @access  public
 * @param   string  $app            程序名称，如category
 * @param   string  $cat            分类ID
 * @param   string  $record_count   记录总数
 * @param   string  $size           每页记录数
 * @param   string  $sort           排序类型
 * @param   string  $order          排序顺序
 * @param   string  $page           当前页
 * @param   string  $keywords       查询关键字
 * @param   string  $brand          品牌
 * @param   string  $price_min      最小价格
 * @param   string  $price_max      最高价格
 * @return  void
 */
function assign_pager($app, $cat, $record_count, $size, $sort, $order, $page = 1,
                        $keywords = '', $brand = 0, $price_min = 0, $price_max = 0)
{
    $sch = array('keywords'  => $keywords,
                 'sort'      => $sort,
                 'order'     => $order,
                 'cat'       => $cat,
                 'brand'     => $brand,
                 'price_min' => $price_min,
                 'price_max' => $price_max);

    $page = intval($page);
    if ($page < 1)
    {
        $page = 1;
    }

    $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;
    $page_prev  = ($page > 1) ? $page - 1 : 1;
    $page_next  = ($page < $page_count) ? $page + 1 : $page_count;

    $pager['page']         = $page;
    $pager['size']         = $size;
    $pager['sort']         = $sort;
    $pager['order']        = $order;
    $pager['record_count'] = $record_count;
    $pager['page_count']   = $page_count;

    switch ($app)
    {
        case 'category':
            $uri_args = array('cid' => $cat, 'bid' => $brand, 'sort' => $sort, 'order' => $order);
            break;
        case 'article_cat':
            $uri_args = array('acid' => $cat, 'sort' => $sort, 'order' => $order);
            break;
        case 'brand':
            $uri_args = array('cid' => $cat, 'bid' => $brand, 'sort' => $sort, 'order' => $order);
            break;
        case 'search':
            $uri_args = array('cid' => $cat, 'bid' => $brand, 'sort' => $sort, 'order' => $order);
            break;
    }

    $pager['page_first'] = build_uri($app, $uri_args, '', 1, $sch);
    $pager['page_prev']  = build_uri($app, $uri_args, '', $page_prev);
    $pager['page_next']  = build_uri($app, $uri_args, '', $page_next);
    $pager['page_last']  = build_uri($app, $uri_args, '', $page_count);
    $pager['array']      = array();

    for ($i = 1; $i <= $page_count; $i++)
    {
        $pager['array'][$i] = $i;
    }

    $pager['search']['category'] = $cat;
    foreach ($sch AS $key => $row)
    {
        $pager['search'][$key] = $row;
    }

    $GLOBALS['smarty']->assign('pager', $pager);
}

/**
 *  生成给pager.lbi赋值的数组
 *
 * @access  public
 * @param   string      $url        分页的链接地址(必须是带有参数的地址，若不是可以伪造一个无用参数)
 * @param   array       $param      链接参数 key为参数名，value为参数值
 * @param   int         $record     记录总数量
 * @param   int         $page       当前页数
 * @param   int         $size       每页大小
 *
 * @return  array       $pager
 */
function get_pager($url, $param, $record_count, $page = 1, $size = 10)
{
    $size = intval($size);
    if ($size < 1)
    {
        $size = 10;
    }

    $page = intval($page);
    if ($page < 1)
    {
        $page = 1;
    }

    $record_count = intval($record_count);

    $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;
    if ($page > $page_count)
    {
        $page = $page_count;
    }
    $page_prev  = ($page > 1) ? $page - 1 : 1;
    $page_next  = ($page < $page_count) ? $page + 1 : $page_count;

    /* 将参数合成url字串 */
    $param_url = '?';
    foreach ($param AS $key => $value)
    {
        $param_url .= $key . '=' . $value . '&';
    }

    $pager['url']          = $url;
    $pager['start']        = ($page -1) * $size;
    $pager['page']         = $page;
    $pager['size']         = $size;
    $pager['record_count'] = $record_count;
    $pager['page_count']   = $page_count;

    $pager['page_first']   = $url . $param_url . 'page=1';
    $pager['page_prev']    = $url . $param_url . 'page=' . $page_prev;
    $pager['page_next']    = $url . $param_url . 'page=' . $page_next;
    $pager['page_last']    = $url . $param_url . 'page=' . $page_count;

    $pager['search'] = $param;

    $pager['array']  = array();
    for ($i = 1; $i <= $page_count; $i++)
    {
        $pager['array'][$i] = $i;
    }

    return $pager;
}

/**
 * 调用调查内容
 *
 * @access  public
 * @param   integer $id   调查的编号
 * @return  array
 */
function get_vote($id = '')
{
    /* 随机取得一个调查的主题 */
    if (empty($id))
    {
        $time = date('Y-m-d');
        $sql = 'SELECT vote_id, vote_name, can_multi, vote_count, RAND() AS rnd' .
               ' FROM ' . $GLOBALS['ecs']->table('vote') .
               " WHERE begin_date <= '$time' AND end_date >= '$time' ".
               ' ORDER BY rnd LIMIT 1';
    }
    else
    {
        $sql = 'SELECT vote_id, vote_name, can_multi, vote_count' .
               ' FROM ' . $GLOBALS['ecs']->table('vote').
               " WHERE vote_id = '$id'";
    }

    $vote_arr = $GLOBALS['db']->getRow($sql);

    if ($vote_arr !== false && !empty($vote_arr))
    {
        /* 通过调查的ID,查询调查选项 */
        $sql_option = 'SELECT v.*, o.option_id, o.vote_id, o.option_name, o.option_count ' .
                      'FROM ' . $GLOBALS['ecs']->table('vote') . ' AS v, ' .
                            $GLOBALS['ecs']->table('vote_option') . ' AS o ' .
                      "WHERE o.vote_id = v.vote_id AND o.vote_id = '$vote_arr[vote_id]'";
        $res = $GLOBALS['db']->getAll($sql_option);

        /* 总票数 */
        $sql = 'SELECT SUM(option_count) AS all_option FROM ' . $GLOBALS['ecs']->table('vote_option') .
               " WHERE vote_id = '" . $vote_arr['vote_id'] . "' GROUP BY vote_id";
        $option_num = $GLOBALS['db']->getOne($sql);

        $arr = array();
        $count = 100;
        foreach ($res AS $idx => $row)
        {
            if ($option_num > 0 && $idx == count($res) - 1)
            {
                $percent = $count;
            }
            else
            {
                $percent = ($row['vote_count'] > 0 && $option_num > 0) ? round(($row['option_count'] / $option_num) * 100) : 0;

                $count -= $percent;
            }
            $arr[$row['vote_id']]['options'][$row['option_id']]['percent'] = $percent;

            $arr[$row['vote_id']]['vote_id']    = $row['vote_id'];
            $arr[$row['vote_id']]['vote_name']  = $row['vote_name'];
            $arr[$row['vote_id']]['can_multi']  = $row['can_multi'];
            $arr[$row['vote_id']]['vote_count'] = $row['vote_count'];

            $arr[$row['vote_id']]['options'][$row['option_id']]['option_id']    = $row['option_id'];
            $arr[$row['vote_id']]['options'][$row['option_id']]['option_name']  = $row['option_name'];
            $arr[$row['vote_id']]['options'][$row['option_id']]['option_count'] = $row['option_count'];
        }

        $vote_arr['vote_id'] = (!empty($vote_arr['vote_id'])) ? $vote_arr['vote_id'] : '';

        $vote = array('id' => $vote_arr['vote_id'], 'content' => $arr);

        return $vote;
    }
}

/**
 * 获得浏览器名称和版本
 *
 * @access  public
 * @return  string
 */
function getbrowser()
{
    global $_SERVER;

    if (!isset($_SERVER['HTTP_USER_AGENT']))
    {
        return 'Unknow browser';
    }

    $agent       = $_SERVER['HTTP_USER_AGENT'];
    $browser     = '';
    $browser_ver = '';

    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = 'Internet Explorer';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'FireFox';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/Maxthon/i', $agent, $regs))
    {
        $browser     = '(Internet Explorer ' .$browser_ver. ') Maxthon';
        $browser_ver = '';
    }
    elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Opera';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = 'OmniWeb';
        $browser_ver = $regs[2];
    }
    elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Netscape';
        $browser_ver = $regs[2];
    }
    elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Safari';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Lynx';
        $browser_ver = $regs[1];
    }

    if (!empty($browser))
    {
       return addslashes($browser . ' ' . $browser_ver);
    }
    else
    {
        return 'Unknow browser';
    }
}

/**
 * 获得客户端的操作系统
 *
 * @access  private
 * @return  void
 */
function get_os()
{
    if (!isset($_SERVER['HTTP_USER_AGENT']))
    {
        return 'Unknown';
    }

    $agent = $_SERVER['HTTP_USER_AGENT'];
    $os    = '';

    if (eregi('win', $agent) && eregi('nt 5.1', $agent))
    {
        $os = 'Windows XP';
    }
    elseif (eregi('win 9x', $agent) && strpos($agent, '4.90'))
    {
        $os = 'Windows ME';
    }
    elseif (eregi('win', $agent) && ereg('98', $agent))
    {
        $os = 'Windows 98';
    }
    elseif (eregi('win', $agent) && strpos($agent, '95'))
    {
        $os = 'Windows 95';
    }
    elseif (eregi('win', $agent) && eregi('nt 5', $agent))
    {
        $os = 'Windows 2000';
    }
    elseif (eregi('win', $agent) && eregi('nt', $agent))
    {
        $os = 'Windows NT';
    }
    elseif (eregi('win', $agent) && ereg('32', $agent))
    {
        $os = 'Windows 32';
    }
    elseif (eregi('linux', $agent))
    {
        $os = 'Linux';
    }
    elseif (eregi('unix', $agent))
    {
        $os = 'Unix';
    }
    elseif (eregi('sun', $agent) && eregi('os', $agent))
    {
        $os = 'SunOS';
    }
    elseif (eregi('ibm', $agent) && eregi('os', $agent))
    {
        $os = 'IBM OS/2';
    }
    elseif (eregi('Mac', $agent) && eregi('PC', $agent))
    {
        $os = 'Macintosh';
    }
    elseif (eregi('PowerPC', $agent))
    {
        $os = 'PowerPC';
    }
    elseif (eregi('AIX', $agent))
    {
        $os = 'AIX';
    }
    elseif (eregi('HPUX', $agent))
    {
        $os = 'HPUX';
    }
    elseif (eregi('NetBSD', $agent))
    {
        $os = 'NetBSD';
    }
    elseif (eregi('BSD', $agent))
    {
        $os = 'BSD';
    }
    elseif (ereg('OSF1', $agent))
    {
        $os = 'OSF1';
    }
    elseif (ereg('IRIX', $agent))
    {
        $os = 'IRIX';
    }
    elseif (eregi('FreeBSD', $agent))
    {
        $os = 'FreeBSD';
    }
    elseif (eregi('teleport', $agent))
    {
        $os = 'teleport';
    }
    elseif (eregi('flashget', $agent))
    {
        $os = 'flashget';
    }
    elseif (eregi('webzip', $agent))
    {
        $os = 'webzip';
    }
    elseif (eregi('offline', $agent))
    {
        $os = 'offline';
    }
    else
    {
        $os = 'Unknown';
    }

    return $os;
}

/**
 * 统计访问信息
 *
 * @access  public
 * @return  void
 */
function visit_stats()
{
    include_once(ROOT_PATH . 'includes/ip/cls_ip.php');
    include_once(ROOT_PATH . 'includes/iconv/cls_iconv.php');

    $ip_search = new ip_search();
    $iconv     = new Chinese();

    /* 检查客户端是否存在访问统计的cookie */
    $visit_times = (!empty($_COOKIE['ECS']['visit_times'])) ? intval($_COOKIE['ECS']['visit_times']) + 1 : 1;
    setcookie('ECS[visit_times]', $visit_times, time() + 3600 * 24 * 365, '/', COOKIE_DOMAIN);

    $browser  = getbrowser();
    $os       = get_os();
    $ip       = real_ip();
    $ip_area  = $ip_search->getlocation($ip);
    $area     = $iconv->convert('GB2312', 'UTF8', $ip_area['country']);
    $keywords = '';

    /* 语言 */
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
    {
        $pos  = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ';');
        $lang = addslashes(($pos !== false) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, $pos) : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }
    else
    {
        $lang = '';
    }

    /* 来源 */
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9)
    {
        $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
        if ($pos !== false)
        {
            $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
            $path   = substr($_SERVER['HTTP_REFERER'], $pos);
        }
        else
        {
            $domain = $path = '';
        }
    }
    else
    {
        $domain = $path = '';
    }

    /* 来源关键字 */
    if (!empty($path))
    {
        if (strstr($domain, 'google.') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
        {
            $keywords = urldecode($regs[1]); // google
        }
        elseif (strstr($domain, 'baidu.') !== false && preg_match('/wd=([^&]*)/i', $path, $regs))
        {
            $keywords = $iconv->convert('GB2312', 'UTF8', urldecode($regs[1])); // baidu
        }
        elseif (strstr($domain, 'baidu.') !== false && preg_match('/word=([^&]*)/i', $path, $regs))
        {
            $keywords = $iconv->convert('GB2312', 'UTF8', urldecode($regs[1])); // baidu
        }
        elseif (strstr($domain, 'cn.yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs))
        {
            $keywords = $iconv->convert('GB2312', 'UTF8', urldecode($regs[1])); // yahoo china
        }
        elseif (strstr($domain, 'yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs))
        {
            $keywords = urldecode($regs[1]); // yahoo
        }
        elseif (strstr($domain, 'msn.') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
        {
            $keywords = urldecode($regs[1]); // msn
        }
        if (!empty($keywords))
        {
            $keywords = addslashes($keywords);
        }
    }

    $domain = addslashes($domain);
    $path = addslashes($path);

//    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('stats') . ' ( ' .
//                'ip_address, visit_times, browser, system, language, area, referer_domain, ' .
//                'referer_path, keywords, access_url, access_time' .
//            ') VALUES (' .
//                "'$ip', '$visit_times', '$browser', '$os', '$lang', '$area', '$domain', '$path', ".
//                "'$keywords', '" . addslashes($_SERVER['PHP_SELF']) ."', '" . time() . "')";
//
//    $GLOBALS['db']->query($sql); 危险！这个代码不适合我们的网站！！！！！
}

/**
 * 调用网店的相关声明
 *
 * @access  public
 * @return  void
 */
function assign_declaration()
{
    $sql = 'SELECT article_id, title, file_url, open_type FROM ' . $GLOBALS['ecs']->table('article') . ' WHERE cat_id = 0 ORDER BY article_id';
    $res = $GLOBALS['db']->getAllCached($sql);

    $arr = array();
    foreach ($res AS $key => $article)
    {
        $arr[$key]['url']   = $article['open_type'] != 1 ?
            build_uri('article', array('aid' => $article['article_id']), $article['title']) : trim($article['file_url']);
        $arr[$key]['title'] = htmlspecialchars($article['title']);
    }

    return $arr;
}

/**
 * 获得指定用户、商品的所有标记
 *
 * @access  public
 * @param   integer $goods_id
 * @param   integer $user_id
 * @return  array
 */
function get_tags($goods_id = 0, $user_id = 0)
{
    $where = '';
    if ($goods_id > 0)
    {
        $where .= " goods_id = '$goods_id'";
    }

    if ($user_id > 0)
    {
        if ($goods_id > 0)
        {
            $where .= " AND";
        }
        $where .= " user_id = '$user_id'";
    }

    if ($where > '')
    {
        $where = ' WHERE' . $where;
    }

    $sql = 'SELECT tag_id, user_id, tag_words, COUNT(tag_id) AS tag_count' .
            ' FROM ' . $GLOBALS['ecs']->table('tag') .
            "$where GROUP BY tag_words";
    $arr = $GLOBALS['db']->getAll($sql);

    return $arr;
}

/**
 * 将需要输出到页面的插件的内容赋值给模板引擎
 *
 * @access  public
 * @return  void
 */
function assign_plugins()
{
    /* 获得所有已安装且需要赋值的插件 */
    $sql = 'SELECT code FROM ' . $GLOBALS['ecs']->table('plugins') . ' WHERE assign = 1';
    $res = $GLOBALS['db']->getAll($sql);

    /* 遍历所有需要赋值的插件，并调用相应的函数 */
    foreach ($res AS $row)
    {
        include_once(ROOT_PATH . 'plugins/' . $row['code'] . '/' . $row['code'] . '_inc.php');

        $plugin = new $row['code'];
        $plugin->assign_val();
    }
}

/**
 * 检查指定goods_id有没有参与夺宝奇兵
 *
 * @access  public
 * @param   int       goods_id    商品id
 *
 * @return array()
 */
function snatch_goods($goods_id)
{
    $cur_time = time();
    $sql = 'SELECT s.snatch_id, a.end_time FROM ' . $GLOBALS['ecs']->table('activity') . ' AS a, ' . $GLOBALS['ecs']->table('snatch') . ' AS s ' .
           "WHERE a.activity_id = s.activity_id AND goods_id = '$goods_id' AND start_time <= '$cur_time' AND end_time > '$cur_time'";
    $row = $GLOBALS['db']->getRow($sql);

    $snatch   = array();
    if ($row)
    {
        $snatch['snatch_id'] = $row['snatch_id'];
        $snatch['url']       = build_uri('snatch', array('sid' => $row['snatch_id']));
        $snatch['end_time']  = $row['end_time'];
    }

    return $snatch;
}

/**
 * 获取指定主题某个模板的主题的动态模块
 *
 * @access  public
 * @param   string       $theme    模板主题
 * @param   string       $tmp      模板名称
 *
 * @return array()
 */
function get_dyna_libs($theme, $tmp)
{
    $sql = 'SELECT region, library, sort_order, id, number, type' .
            ' FROM ' . $GLOBALS['ecs']->table('template') .
            " WHERE theme = '$theme' AND filename = '" . substr($tmp, 0, strrpos($tmp, '.dwt')) . "' AND type > 0".
            ' ORDER BY region, library, sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $dyna_libs = array();
    foreach ($res AS $row)
    {
        $dyna_libs[$row['region']][$row['library']][] = array(
            'id'     => $row['id'],
            'number' => $row['number'],
            'type'   => $row['type']
        );
    }

    return $dyna_libs;
}

/**
 * 替换动态模块
 *
 * @access  public
 * @param   string       $matches    匹配内容
 *
 * @return string        结果
 */
function dyna_libs_replace($matches)
{
    $key = '/' . $matches[1];

    if ($row = array_shift($GLOBALS['libs'][$key]))
    {
        $str = '';
        switch($row['type'])
        {
            case 1:
                // 分类的商品
                $str = '{assign var="cat_goods" value=$cat_goods_' .$row['id']. '}{assign var="goods_cat" value=$goods_cat_' .$row['id']. '}';
                break;
            case 2:
                // 品牌的商品
                $str = '{assign var="brand_goods" value=$brand_goods_' .$row['id']. '}{assign var="goods_brand" value=$goods_brand_' .$row['id']. '}';
                break;
            case 3:
                // 文章列表
                $str = '{assign var="articles" value=$articles_' .$row['id']. '}{assign var="articles_cat" value=$articles_cat_' .$row['id']. '}';
                break;
            case 4:
                //广告位
                $str = '{assign var="ads_id" value=' . $row['id'] . '}{assign var="ads_num" value=' . $row['number'] . '}';
                break;
        }
        return $str . $matches[0];
    }
    else
    {
        return $matches[0];
    }
}

/**
 * 处理上传文件，并返回上传图片名(上传失败时返回图片名为空）
 *
 * @access  public
 * @param array     $upload     $_FILES 数组
 * @param array     $type       图片所属类别，即data目录下的文件夹名
 *
 * @return string               上传图片名
 */
/* 屏蔽上传文件 by ychen 2009/07/31
function upload_file($upload, $type)
{
    if (!empty($upload['tmp_name']))
    {
        $ftype = check_file_type($upload['tmp_name'], $upload['name'], '|jpg|jpeg|gif|doc|xls|txt|zip|ppt|pdf|rar|');
        if (!empty($ftype))
        {
            $name = date('Ymd');
            for ($i = 0; $i < 6; $i++)
            {
                $name .= chr(rand(97, 122));
            }

            $name = $_SESSION['user_id'] . '_' . $name . '.' . $ftype;

            $target = ROOT_PATH . 'data/' . $type . '/' . $name;
            if (!move_uploaded_file($upload['tmp_name'], $target))
            {
                $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_error'], 1);

                return false;
            }
            else
            {
                return $name;
            }
        }
        else
        {
            $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_type'], 1);

            return false;
        }
    }
    else
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_error']);
        return false;
    }
}
*/

/**
 * 显示一个提示信息
 *
 * @access  public
 * @param   string  $content
 * @param   string  $link
 * @param   string  $href
 * @param   string  $type       信息类型：warning, error, info
 * @return  void
 */
function show_message($content, $link = '', $href = '', $type = 'info', $path = '', $templatename = '')
{
    assign_template();

    $msg['content'] = $content;
    $msg['link']    = empty($link) ? $GLOBALS['_LANG']['back_up_page'] : $link;
    $msg['href']    = empty($href) ? 'javascript:history.back()'       : $href;
    $msg['type']    = $type;

    $position = assign_ur_here(0, $GLOBALS['_LANG']['sys_msg']);
    $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
    $GLOBALS['smarty']->assign('ur_here',    $position['ur_here']); // 当前位置

    if (is_null($GLOBALS['smarty']->get_template_vars('helps')))
    {
        $GLOBALS['smarty']->assign('helps', get_shop_help()); // 网店帮助
    }

    if (is_null($GLOBALS['smarty']->get_template_vars('nav_list')))
    {
        $GLOBALS['smarty']->assign('nav_list', get_navigator()); // 导航栏
    }

    $GLOBALS['smarty']->assign('message', $msg);
    if ($templatename) {
    	$GLOBALS['smarty']->display($templatename);
    } else {
      $GLOBALS['smarty']->display('message.dwt');
    }
    
    exit;
}

/*------------------------------------------------------ */
//-- 以下4个smarty为注册string资源函数
/*------------------------------------------------------ */
/**
 * smarty 检索资源函数
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $tpl_source[string]       模板内容
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function str_get_template ($tpl_name, &$tpl_source, &$smarty_obj)
{
     $tpl_source = $tpl_name;

     return true;
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
function str_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
{
    $tpl_timestamp = time();

    return true;
}

/**
 * smarty 确认资源是否安全
 *
 * @param: $tpl_name[string]         模板代码
 * @param: $smarty_obj[object]       smarty 对象
 *
 * @return boolean
 */
function str_get_secure($tpl_name, &$smarty_obj)
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
function str_get_trusted($tpl_name, &$smarty_obj)
{
    /* 不使用该函数 */
}

/**
 * 将一个形如+10, 10, -10, 10%的字串转换为相应数字，并返回操作符号
 *
 * @access  public
 * @param   string      str     要格式化的数据
 * @param   char        operate 操作符号，只能返回‘+’或‘*’;
 * @return  float       value   浮点数
 */
function parse_rate_value($str, &$operate)
{
    $operate = '+';
    $is_rate = false;

    $str = trim($str);
    if (empty($str))
    {
        return 0;
    }
    if ($str[strlen($str) - 1] == '%')
    {
        $value = floatval($str);
        if ($value > 0)
        {
            $operate = '*';

            return $value / 100;
        }
        else
        {
            return 0;
        }
    }
    else
    {
        return floatval($str);
    }
}

/**
 * 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
 *
 * @access  public
 * @return  void
 */
function recalculate_price()
{
    /* 取得重算价格前参与送赠品的商品总金额 */
    $sql = 'SELECT SUM(goods_price * goods_number) ' .
            ' FROM ' . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            ' AND is_gift = 0 AND can_handsel = 1 ' .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $old_amount = $GLOBALS['db']->getOne($sql);

    /* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
    $sql = 'SELECT c.goods_id, c.goods_attr, g.shop_price AS goods_price, g.promote_price, g.promote_start, g.promote_end, '.
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price ".
            'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c '.
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE session_id = '" .SESS_ID. "' AND c.parent_id = 0 AND c.is_gift = 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
    $res = $GLOBALS['db']->getAll($sql);

    if ($res != false)
    {
        foreach ($res AS $row)
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
            }
            else
            {
                $promote_price = 0;
            }

            $goods_price = ($promote_price > 0) ? $promote_price : $row['shop_price'];
            $goods_price += spec_price($row['goods_attr']);

            $GLOBALS['db']->query('UPDATE ' .$GLOBALS['ecs']->table('cart'). " SET goods_price = '$goods_price' ".
                        "WHERE goods_id = '" . $row['goods_id'] . "' AND session_id = '" .SESS_ID. "'");
        }
    }

    /* 取得重算价格后参与送赠品的商品总金额 */
    $sql = 'SELECT SUM(goods_price * goods_number) ' .
            ' FROM ' . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            ' AND is_gift = 0 AND can_handsel = 1 ' .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $new_amount = $GLOBALS['db']->getOne($sql);

    /* 如果重算价格前后参与送赠品的商品总金额不一致，删除赠品，重新选择 */
    if ($old_amount != $new_amount)
    {
        $GLOBALS['db']->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND is_gift = 1");
    }
}

/**
 * 查询评论内容
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @return  array
 */
function assign_comment($id, $type, $page = 1)
{
    /* 取得评论列表 */
    $count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('comment').
           " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0");
    $size  = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;

    $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
            " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0".
            ' ORDER BY comment_id DESC';
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);

    $arr = array();
    $ids = '';
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
        $arr[$row['comment_id']]['id']       = $row['comment_id'];
        $arr[$row['comment_id']]['email']    = $row['email'];
        $arr[$row['comment_id']]['username'] = $row['user_name'];
        $arr[$row['comment_id']]['content']  = nl2br(htmlspecialchars($row['content']));
        $arr[$row['comment_id']]['rank']     = $row['comment_rank'];
        $arr[$row['comment_id']]['add_time'] = date($GLOBALS['_CFG']['time_format'], $row['add_time']);
    }
    /* 取得已有回复的评论 */
    if ($ids)
    {
        $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('comment') .
                " WHERE parent_id IN( $ids )";
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetch_array($res))
        {
            $arr[$row['parent_id']]['re_content']  = nl2br(htmlspecialchars($row['content']));
            $arr[$row['parent_id']]['re_add_time'] = date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $arr[$row['parent_id']]['re_email']    = $row['email'];
            $arr[$row['parent_id']]['re_username'] = $row['user_name'];
        }
    }
    $pager['page']         = $page;
    $pager['size']         = $size;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count;
    $pager['page_first']   = "javascript:gotoPage(1, $id, $type)";
    $pager['page_prev']    = $page > 1 ? "javascript:gotoPage(" .($page-1). ", $id, $type)" : 'javascript:;';
    $pager['page_next']    = $page < $page_count ? 'javascript:gotoPage(' .($page + 1) . ", $id, $type)" : 'javascript:;';
    $pager['page_last']    = $page < $page_count ? 'javascript:gotoPage(' .$page_count. ", $id, $type)"  : 'javascript:;';

    $cmt = array('comments' => $arr, 'pager' => $pager);

    return $cmt;
}

function assign_template()
{
    global $smarty;

    $smarty->assign('image_width',   $GLOBALS['_CFG']['image_width']);
    $smarty->assign('image_height',  $GLOBALS['_CFG']['image_height']);
    $smarty->assign('points_name',   $GLOBALS['_CFG']['integral_name']);
    $smarty->assign('qq',            explode(',', $GLOBALS['_CFG']['qq']));
    $smarty->assign('ww',            explode(',', $GLOBALS['_CFG']['ww']));
    $smarty->assign('ym',            explode(',', $GLOBALS['_CFG']['ym']));
    $smarty->assign('msn',           explode(',', $GLOBALS['_CFG']['msn']));
    $smarty->assign('skype',         explode(',', $GLOBALS['_CFG']['skype']));
    $smarty->assign('stats_code',    $GLOBALS['_CFG']['stats_code']);
    $smarty->assign('copyright',     sprintf($GLOBALS['_LANG']['copyright'], date('Y'), $GLOBALS['_CFG']['shop_name']));
    $smarty->assign('shop_name',     $GLOBALS['_CFG']['shop_name']);
    $smarty->assign('service_email', $GLOBALS['_CFG']['service_email']);
    $smarty->assign('service_phone', $GLOBALS['_CFG']['service_phone']);
    $smarty->assign('shop_address',  $GLOBALS['_CFG']['shop_address']);
    $smarty->assign('ecs_version',   VERSION);
    $smarty->assign('icp_number',    $GLOBALS['_CFG']['icp_number']);
    //$smarty->assign('declaration',   assign_declaration());
    $smarty->assign('username',      !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : '');
    //$smarty->assign('category_list', cat_list(0, 0, true,  2));
    //$smarty->assign('catalog_list',  cat_list(0, 0, false, 1));
}

/**
 *
 *  将一个本地时间戳转成GMT时间戳
 *
 * @access  public
 * @param   int     $time
 *
 * @return int      $gmt_time;
 */
function time2gmt($time)
{
    return strtotime(gmdate('Y-m-d H:i:s', $time));
}

/**
 * 查询会员的红包金额
 *
 * @access  public
 * @param   integer     $user_id
 * @return  void
 * 
 * TODO 废弃的函数
 */
function get_user_bonus($user_id = 0)
{
    if ($user_id == 0)
    {
        $user_id = @$_SESSION['user_id'];
    }

    $sql = "SELECT SUM(bt.type_money) AS bonus_value, COUNT(*) AS bonus_count ".
            "FROM " .$GLOBALS['ecs']->table('user_bonus'). " AS ub, ".
                $GLOBALS['ecs']->table('bonus_type') . " AS bt ".
            "WHERE ub.user_id = '$user_id' AND ub.bonus_type_id = bt.type_id AND ub.order_id = 0";
    $row = $GLOBALS['db']->getRow($sql);

    return $row;
}

/**
 * 过滤部分词语
 *
 * @access  public
 * @param   string     $text 待过滤的内容
 * @return  string     $text 过滤后的内容
 */
function filter_word($text) {
  static $filter_words;
  if (!$filter_words) {
    global $_CFG;
    $lines = explode("\n", $_CFG['filter_words_text']);
    $finds = array("/(http|https):\/\/[\/\/\w\.\?&=%]*/i", "/1(3|5)(\d){9}/");
    $replaces = array("1\$1*********", "******");
    foreach ($lines as $line) {
      $f_r = explode("=",$line);
      if (trim($f_r[0])) {
        $finds[] = "/".trim($f_r[0])."/i";
        if (trim($f_r[1])) {
          $replaces[] = trim($f_r[1]);
        } else {
          $replaces[] = "***";
        }
      }
    }
    $filter_words['find'] = $finds;
//    pp($finds, $replaces);
    $filter_words['replace'] = $replaces;
  }
  
  $text = str_replace(array("\r", "\n"), array("",""), $text);
  $text = @preg_replace($filter_words['find'], $filter_words['replace'], $text);
  return $text;
}


/**
 * 判断商品大的分类
 *
 * @access  public
 * @param   int     $top_cat_id 顶级分类
 * @param   int     $cat_id 分类
 * @return  int     $real_cat_id 真实的分类 1 手机， 2 手机配件， 3 小家电， 4 DVD
 */
function get_real_cat_id($top_cat_id, $cat_id = 0) {
  if($top_cat_id == 1) return 1;
  if($top_cat_id == 597) return 2;
  if(!in_array($top_cat_id, array(1, 597, 1109)) && $cat_id != 1157 ) return 3;
  if($cat_id == 1157) return 4;
}

/**
 * 给出商品的分类名
 *
 * @access  public
 * @param   int     $real_cat_id 分类
 * @return  string     $real_cat_id 真实的分类名
 */
function get_real_cat_name($real_cat_id) {
  static $cat = array(1 => '手机', 2 => '手机配件', 3 => '小家电', 4 => 'DVD', );
  $cat_id = intval($cat_id);
  return $cat[$real_cat_id];
}