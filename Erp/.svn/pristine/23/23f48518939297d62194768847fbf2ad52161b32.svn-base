<?php

/**
 * 分销订单收货地址分析 
 *
 * @author yxiang@oukoo.com
 * @copyright 2010 leqee.com 
 */

// 字典文件
$__region_dict = array(
	/* 
	// 定义地域字典数组的格式
	'吉林'=>array(
		'_id'=>8,
		'长春'=>array(
			'_id'=>88
			'朝阳区'=>2423  // 最后一级不是数组
			'南关区'=>2424
		),
		'吉林市'=>array(
			'_id'=>89,
			'船营区'=>2433,
			'昌邑区'=>2434
		)
	)
	*/
);

// TODO 不要用Romeo的缓存库
require_once(ROOT_PATH .'RomeoApi/lib_cache.php');
$cache=RomeoApi_Cache::instance(array('life_time'=>86400));
if (!($__region_dict = $cache->get('__region_dict')))
{
	$_provinces=$db->getAll("SELECT region_id, replace(region_name,' ','') as region_name FROM ecs_region WHERE region_type = 1 AND parent_id = 1");
	foreach ($_provinces as $p) {
	    $__region_dict[$p['region_name']]['_id']=$p['region_id'];
	    $_citys=$db->getAll("SELECT region_id, replace(region_name,' ','') as region_name FROM ecs_region WHERE region_type = 2 AND parent_id = {$p['region_id']}");
	    if (!$_citys) { continue; }
	    foreach ($_citys as $c) {
	        $_districts=$db->getAll("SELECT region_id, replace(region_name,' ','') as region_name FROM ecs_region WHERE region_type = 3 AND parent_id = {$c['region_id']}");
	        if ($_districts) {
	            $__region_dict[$p['region_name']][$c['region_name']]['_id']=$c['region_id'];
	            foreach ($_districts as $d) {
	                $__region_dict[$p['region_name']][$c['region_name']][$d['region_name']]=$d['region_id'];
	            }
	        }
	        else {
	            $__region_dict[$p['region_name']][$c['region_name']]=$c['region_id'];
	        }
	    }
	}
	$cache->set('__region_dict',$__region_dict);
}

// 同义词字典
$__synonym_dict = array(
	'内蒙古'=>array('内蒙古自治区'),
	'广西'=>array('广西壮族自治区'),
	'新疆'=>array('新疆维吾尔自治区'),
	'香港'=>array('香港特别行政区'),
	'西藏'=>array('西藏自治区'),
	'宁夏'=>array('宁夏回族自治区'),
	'北京'=>array('北京北京市'),
	'天津'=>array('天津天津市'),
	'上海'=>array('上海上海市'),
	'重庆'=>array('重庆重庆市'),

	'高新技术开发区'=>array('高新区'),
	'新界(東區)'=>array('新界'),
	'大兴安岭'=>array('大兴安岭地区'),
	'恩施'=>array('恩施土家族苗族自治州'),
	'湘西'=>array('湘西土家族苗族自治州'),
	'阿坝'=>array('阿坝藏族羌族自治州'),
	'甘孜'=>array('甘孜藏族自治州'),
	'凉山'=>array('凉山彝族自治州'),
	'铜仁'=>array('铜仁地区'),
	'毕节'=>array('毕节地区'),
	'黔西南'=>array('黔西南布依族苗族自治州'),
	'黔东南'=>array('黔东南苗族侗族自治州'),
	'黔南'=>array('黔南布依族苗族自治州'),
	'文山'=>array('文山壮族苗族自治州'),
	'红河'=>array('红河哈尼族彝族自治州'),
	'西双版纳'=>array('西双版纳傣族自治州'),
	'楚雄'=>array('楚雄彝族自治州'),
	'大理'=>array('大理白族自治州'),
	'德宏'=>array('德宏傣族景颇族自治州'),
	'怒江'=>array('怒江傈僳族自治州'),
	'迪庆'=>array('迪庆藏族自治州'),
	'昌都'=>array('昌都地区'),
	'山南'=>array('山南地区'),
	'那曲'=>array('那曲地区'),
	'阿里'=>array('阿里地区'),
	'林芝'=>array('林芝地区'),
	'临夏'=>array('临夏回族自治州'),
	'甘南'=>array('甘南藏族自治州'),
	'海北州'=>array('海北藏族自治州'),
	'黄南州'=>array('黄南藏族自治州'),
	'海南州'=>array('海南藏族自治州'),
	'果洛州'=>array('果洛藏族自治州'),
	'玉树州'=>array('玉树藏族自治州'),
	'海西州'=>array('海西蒙古族藏族自治州'),
	'吐鲁番'=>array('吐鲁番地区'),
	'哈密'=>array('哈密地区'),
	'和田'=>array('和田地区'),
	'喀什'=>array('喀什地区'),
	'克孜勒苏'=>array('克孜勒苏柯尔克孜自治州'),
	'巴音郭楞'=>array('巴音郭楞蒙古自治州'),
	'昌吉'=>array('昌吉回族自治州'),
	'博尔塔拉'=>array('博尔塔拉蒙古自治州'),
	'伊犁'=>array('伊犁哈萨克自治州'),
	'塔城'=>array('塔城地区'),
	'阿勒泰'=>array('阿勒泰地区'),

	'麻阳自治县'=>array('麻阳苗族自治县'),
	'积石山保安族东乡'=>array('积石山保安族东乡族撒拉族自治县'),
);


/**
 * 地址分析
 * 
 * @param $str
 * @return array
 */
function distribution_order_address_analyze($address)
{   
    global $__region_dict;
    $province = $city = $district = 0;
    
    // 去掉地址中的空格
    if ($address) {
        $address = preg_replace('/[\s]+/', '', $address);
    }
    
    if ($address) {
		// 1级
		$p = distribution_str_match($address, 8, $__region_dict, array('省' => 1, '市' => 1));
		if ($p !== false) {
			if (is_array($__region_dict[$p])) {
				$province = $__region_dict[$p]['_id'];

				// 2级
				$c = distribution_str_match($address, 11, $__region_dict[$p], array('市' => 1, '区' => 1, '县' => 1));
				if ($c !== false) { 
					if (is_array($__region_dict[$p][$c])) {
						$city = $__region_dict[$p][$c]['_id'];

						// 3级
						$d = distribution_str_match($address, 13, $__region_dict[$p][$c], array('市' => 1, '区' => 1, '县' => 1));
						if ($d !== false) {
							if (is_array($__region_dict[$p][$c][$d])) {
								$district = $__region_dict[$p][$c][$d]['_id'];
							}
							else {
								$district = $__region_dict[$p][$c][$d];
							}
						}
					}
					else {
						$city = $__region_dict[$p][$c];
					}
				}
			}
			else {
				$province = $__region_dict[$p];
			}		
		}
    }

    return compact('province', 'city', 'district', 'address');
}

/**
 * 字符串匹配
 * 
 * @param string  $str
 * @param int     $word_len
 * @param array   $cs_keys 匹配字符字典
 * @param array   $cf_keys 过滤字符字典
 * @param boolean $truncate 是否将匹配到的字符截取掉
 * 
 * @return 从首字符串开始匹配，如果在给出的字典中有该词条，则返回该词条，并从原始字符串中截取掉该词条，无匹配返回false
 */
function distribution_str_match(& $str, $word_len, & $cs_keys, $cf_keys = array(), $truncate = true)
{
	global $__synonym_dict;
    if (trim($str) == '') return false;
 
	if (is_array($cs_keys)) {
		// 通过同义词扩充字典
		foreach($cs_keys as $key => $tmp) {
			if (isset($__synonym_dict[$key])) {
				foreach ($__synonym_dict[$key] as $synonym) {
					if (!isset($cs_keys[$synonym])) $cs_keys[$synonym]=&$cs_keys[$key];
				}
			}
		}
	}
	else {
		$cs_keys=array();
	}

    $found = 0;
    $len = mb_strlen($str);
    
    // 采用用正向最大化匹配搜索
    for ($i = 1; $i <= min($word_len, $len); $i++) {
        $word = mb_substr($str, 0, $i, "UTF-8");
        # if (ord($word[0]) < 176) break;  // 非中文

        if (isset($cs_keys[$word])) {
            $next = $found = $i;
            $nextchar = mb_substr($str, $i, 1, "UTF-8");  
            if (isset($cf_keys[$nextchar])) { $next++; }
        }
    }

    if ($found) {
        $word = mb_substr($str, 0, $found, "UTF-8");
        if ($truncate) { $str = mb_substr($str, $next, $len, "UTF-8"); }
        return $word;
    } else {
        return false;
    }
}
