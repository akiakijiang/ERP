<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'admin/includes/lib_taobao.php';
require_once ROOT_PATH . 'includes/cls_mysql.php';
require_once ROOT_PATH . 'protected/components/TaobaoClient.php';
Yii::import('application.commands.LockedCommand', true);

/**
 * ACookie数据分析
 * 
 * @author wjzhu
 *
 */
class ACookieCommand extends LockedCommand
{
    private $report; // Report数据库  
    
    const APP_KEY      	 	= '21125119';
    const APP_SECRET		= '16007defbeb9a0db6cccb2213eaff4a0';

    /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $this->run(array('MatchVisitor'));
    }
    
	/**
     * Test
     */
    public function actionTest() {
    	$start = microtime(true);
    	$session_id = '00000362e1d1c731fbf04db5';
	  	$sql = " SELECT count(1) FROM acookie.session WHERE session_id = '{$session_id}' LIMIT 1"; 
	  	$count = $this->getReport()->getOne($sql);
	  	echo "[". date('c'). "] 去重acookie.session " . $session_id . " 耗时：".(microtime(true)-$start). "\n";
    }
    
    /**
     * 创建Task
     */
    public function actionCreateTask() {
    	$application_nicks = get_taobao_application_nicks();
    	$include_list=array(
		    'dccd25640ed712229d50e48f2170f7fd',		// ecco爱步官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a',		// blackmores官方旗舰店
			'573d454e82ff408297d56fbe1145cfb9',		// 金宝贝官方旗舰店
    		'87b6a6a6ced1499c90073197670b54ce',     // 玛氏宠物旗舰店
    		'85b1cf4b507b497e844c639733788480',   	// anmum安满官方旗舰店
           	'923ec15fa8b34e4a8f30e5dd8230cdef', 	// anlene安怡官方旗舰店
		);
		
    	foreach ($application_nicks as $application_key => $nick) {
			if (!in_array($application_key, $include_list)) {	
				continue;
			}
			
			$session_key = $this->getLatestSessionKey($application_key);
			if(!$session_key) {
				echo "[". date('c'). "]" . $nick . " need update session key. \n";
				continue;
			}
			
			// ACOOKIE 1
			// taobao.topats.visitlog.get 获取淘宝主站访问日志
			// taobao.topats.waplog.get   获取淘宝手机访问日志
			$this->CreateTasks($application_key, 'visitlog', $session_key);
			$this->CreateTasks($application_key, 'waplog', $session_key);
			
			// ACOOKIE 2
			// taobao.topats.shop.weblog.get 获取淘宝天猫Web端流量数据
			// taobao.topats.shop.waplog.get 获取淘宝天猫Wap端流量数据
			//$this->CreateTasks($application_key, 'shop.weblog', $session_key);
			//$this->CreateTasks($application_key, 'shop.waplog', $session_key);
    	}
    }
    
	/**
     * 获取Task
     */
    public function actionDownloadTask()
    {
    	$application_nicks = get_taobao_application_nicks();
    	$include_list=array(
		    'dccd25640ed712229d50e48f2170f7fd',		// ecco爱步官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a',		// blackmores官方旗舰店
			'573d454e82ff408297d56fbe1145cfb9',		// 金宝贝官方旗舰店
    		'87b6a6a6ced1499c90073197670b54ce',     // 玛氏宠物旗舰店
    		'85b1cf4b507b497e844c639733788480',   	// anmum安满官方旗舰店
           	'923ec15fa8b34e4a8f30e5dd8230cdef', 	// anlene安怡官方旗舰店
		);
		
		$total_count = 0;
		$total_done_count = 0;
		$total_start = microtime(true);
	    foreach ($application_nicks as $application_key => $nick) {
			if (!in_array($application_key, $include_list)) {	
				continue;
			}
			
	    	// 获取Task
			$start = microtime(true);
			$done_count = 0;
			$tasks = $this->getToDownloadTasks($application_key);
			$total_count += count($tasks);
			if(count($tasks) == 0) {
				continue;
			}
			
		    // 处理Task
			$taobao_client = null;
			foreach ( $tasks as $task ) {
				$task_start = microtime(true);
				// 获取TaobaoClient
				$taobao_client = $this->getTaobaoClient($task);
				if($taobao_client == null) {
					echo "[". date('c'). "]" . $nick . " Failed to get taobao client. " . "\n";
					continue;
				} 
		
				$result = $this->downloadTask($taobao_client, $task);
				if($result == false) {
					echo "[". date('c'). "]" . $nick . " Failed to download task. " . $task['task_id'] . " 耗时：".(microtime(true)-$task_start). "\n";
				} else {
					echo "[". date('c'). "]" . $nick . " Succeed to download task. " . $task['task_id'] . " 耗时：".(microtime(true)-$task_start). "\n";
					$done_count++;
				}
				
				usleep(500000);
			}
			
			$total_done_count += $done_count;
			echo "[". date('c'). "]" . $nick . " 待下载任务：共" . count($tasks) . " 成功下载" . $done_count . " 耗时：".(microtime(true)-$start)."\n";	
	    }
	    
	     echo "[". date('c'). "]待下载任务：共" . $total_count . " 成功下载" . $total_done_count . " 耗时：".(microtime(true)-$total_start)."\n";   
    }
    
    /**
     * 分析Task
     */
    public function actionDoTask()
    {
    	$application_nicks = get_taobao_application_nicks();
    	$include_list=array(
		    'dccd25640ed712229d50e48f2170f7fd',		// ecco爱步官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a',		// blackmores官方旗舰店
			'573d454e82ff408297d56fbe1145cfb9',		// 金宝贝官方旗舰店
    		'87b6a6a6ced1499c90073197670b54ce',     // 玛氏宠物旗舰店
    		'85b1cf4b507b497e844c639733788480',   	// anmum安满官方旗舰店
           	'923ec15fa8b34e4a8f30e5dd8230cdef', 	// anlene安怡官方旗舰店
		);
		
		$total_count = 0;
		$total_done_count = 0;
		$total_start = microtime(true);
	    foreach ($application_nicks as $application_key => $nick) {
			if (!in_array($application_key, $include_list)) {	
				continue;
			}
			
	    	// 获取Task
			$start = microtime(true);
			$done_count = 0;
			$tasks = $this->getTodoTasks($application_key);
			$total_count += count($tasks);
			if(count($tasks) == 0) {
				continue;
			}
			
		    // 处理Task
			$taobao_client = null;
			foreach ( $tasks as $task ) {
				$task_start = microtime(true);
				// 获取TaobaoClient
				$taobao_client = $this->getTaobaoClient($task);
				if($taobao_client == null) {
					echo "[". date('c'). "]" . $nick . " Failed to get taobao client. " . "\n";
					continue;
				} 
		
				$result = $this->doTask($taobao_client, $task);
				if($result == false) {
					echo "[". date('c'). "]" . $nick . " Failed to do task. " . $task['task_id'] . " 耗时：".(microtime(true)-$task_start). "\n";
				} else {
					echo "[". date('c'). "]" . $nick . " Succeed to do task. " . $task['task_id'] . " 耗时：".(microtime(true)-$task_start). "\n";
					$done_count++;
				}
				
				usleep(500000);
			}
			
			$total_done_count += $done_count;
			echo "[". date('c'). "]" . $nick . " 待完成任务：共" . count($tasks) . " 成功完成" . $done_count . " 耗时：".(microtime(true)-$start)."\n";	
	    }
	    
	     echo "[". date('c'). "]待完成任务：共" . $total_count . " 成功完成" . $total_done_count . " 耗时：".(microtime(true)-$total_start)."\n";   
    }
    
	/**
     * 生成Report
     */
    public function actionAnalyzeAdvert() {
    	$application_nicks = get_taobao_application_nicks();
    	$include_list=array(
		    'dccd25640ed712229d50e48f2170f7fd',		// ecco爱步官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a',		// blackmores官方旗舰店
			'573d454e82ff408297d56fbe1145cfb9',		// 金宝贝官方旗舰店
    		'87b6a6a6ced1499c90073197670b54ce',     // 玛氏宠物旗舰店
    		'85b1cf4b507b497e844c639733788480',   	// anmum安满官方旗舰店
           	'923ec15fa8b34e4a8f30e5dd8230cdef', 	// anlene安怡官方旗舰店
		);
		
    	foreach ($application_nicks as $application_key => $nick) {
			if (!in_array($application_key, $include_list)) {	
				continue;
			}
		
	    	$start = microtime(true);
	    	$sql = "
	    		SELECT		s.session_id as session_id
	    		FROM		acookie.session s
	    		INNER JOIN	acookie.taobao_session_temp t ON convert(t.party_id using utf8) = s.party_id			
	    		WHERE		t.application_key = '{$application_key}' 
	    		AND 		s.is_handled = '0'
	    		LIMIT 6000
	    	";
	    	$tracks = $this->getReport()->getAll($sql);
	    	foreach ( $tracks as $track ) {
	    		// 去重acookie.advert_effect
	    		if ($this->isSessionParsed($track['session_id'])) {
	    			echo "[". date('c'). "]" . $track['session_id'] . " session exists in acookie.advert_effect. " . "\n";
	    			continue;
	    		}
	  	
	    		$sql = "
	    			SELECT 		v.id, v.created_stamp, v.ip_address, v.current_url, v.referer_url, v.party_id, v.encrypted_nick, q.session_id, v.cookie_id 
	    			FROM		acookie.visitlog v
	    			LEFT JOIN	acookie.quantum q on v.id = q.visitlog_id
	    			WHERE		q.session_id = '{$track['session_id']}'
	    			ORDER BY	q.visit_times ASC
	    			LIMIT 1
	    		";
	    		$visitor = $this->getReport()->getRow($sql);
	    		if(!$visitor['referer_url']) {
	    			$root_referer_url	= addslashes($visitor['current_url']);
	    		} else {
	    			$root_referer_url	= addslashes($visitor['referer_url']);
	    		}
	    		
	    		$root_referer_url	= $this->get_long_url($root_referer_url);
	    		$url_array			= parse_url($root_referer_url); 
	    		$host 				= $url_array['host'];
	    		$path 				= $url_array['path'];
	    		$query 				= $url_array['query'];
	    		$keyword 			= $this->getKeywordFromQuery($host, $path, $query);
	    		$advert_source_id 	= $this->getAdvertSourceFromURL($host, $path, $keyword, $nick);
			  	$pv 				= $this->getPageViewBySession($visitor['session_id']);
			  	$conversions 		= $this->getConversionsBySession($visitor['session_id']);
			  	$dest				= $this->getLastURLBySession($visitor['session_id']);
			  	$advert_dest_id		= $this->getAdvertDestFromURL($dest);
			  	$time_on_session 	= $this->getTimeOnSessionBySession($visitor['session_id']);
	    		// double check去重acookie.advert_effect 
	    		if ($this->isSessionParsed($track['session_id'])) {
	    			echo "[". date('c'). "]" . $track['session_id'] . " session exists in acookie.advert_effect. " . "\n";
	    			continue;
	    		}
	    		
			  	$sql = "
			  		INSERT INTO acookie.`advert_effect` (`visitlog_id`, `created_stamp`, `ip_address`, `root_referer_url`, `host`, `path`, `last_url`, `advert_source_id`, `last_source_id`,
			  						`keyword`, `session_id`, `cookie_id`, `page_view`, `depth_of_visit`, `conversions`, `time_on_session`, `party_id`, `encrypted_nick`) 
			  		VALUES('{$visitor['id']}', '{$visitor['created_stamp']}', '{$visitor['ip_address']}', '{$root_referer_url}', '{$host}', '{$path}', '{$dest}', '{$advert_source_id}', '{$advert_dest_id}',
		    			'{$keyword}', '{$visitor['session_id']}', '{$visitor['cookie_id']}', '{$pv}', '{$pv}', '{$conversions}', '{$time_on_session}', '{$visitor['party_id']}', '{$visitor['encrypted_nick']}' )	
			  	";
			  	$this->getReport()->query($sql);
			  	
			  	$sql = " UPDATE acookie.`session` SET is_handled = '1' WHERE session_id = '{$visitor['session_id']}' ";
			  	$this->getReport()->query($sql);
			  	
			  	if($conversions > 0) {
			  		$sql = " UPDATE acookie.`session` SET is_secondary_purchase = '1' WHERE is_member = '1' AND session_id = '{$visitor['session_id']}' ";
  					$this->getReport()->exec($sql);
			  	}
	    	}
	    	echo "[". date('c'). "]" . $nick . " 广告分析数 " . count($tracks) . " 耗时：".(microtime(true)-$start)."\n";	
    	}	
    }
    
	/**
     * 解密 acookie.customer_cookie,匹配淘宝订单号和用户nick
     */
    public function actionMatchVisitor()
    {
    	$application_nicks = get_taobao_application_nicks();
    	$include_list=array(
		    'dccd25640ed712229d50e48f2170f7fd',		// ecco爱步官方旗舰店
			'7626299ed42c46b0b2ef44a68083d49a',		// blackmores官方旗舰店
			'573d454e82ff408297d56fbe1145cfb9',		// 金宝贝官方旗舰店
    		'87b6a6a6ced1499c90073197670b54ce',     // 玛氏宠物旗舰店
    		'85b1cf4b507b497e844c639733788480',   	// anmum安满官方旗舰店
           	'923ec15fa8b34e4a8f30e5dd8230cdef', 	// anlene安怡官方旗舰店
		);
    	
		$total_count = 0;
		$total_matched_count = 0;
		$total_start = microtime(true);
	    foreach ($application_nicks as $application_key => $nick) {
			if (!in_array($application_key, $include_list)) {	
				continue;
			}

			// 获取Visitors
			$start = microtime(true);
			$matched_count = 0;
			$visitors = $this->getToMatchVisitors($application_key);
			$total_count += count($visitors);
			if(count($visitors) == 0) {
				continue;
			}
	
			// 匹配Visitor
			foreach ( $visitors as $visitor ) {
				if(empty($visitor['encrypted_taobao_order_sn'])) {
					echo "[". date('c'). "]" . $nick . " Abornamal order " . $visitor['id'] . "\n";
					continue;
				}
				
				$result = $this->MatchVisitor($visitor);
				if($result == 0) {
					echo "[". date('c'). "]" . $nick . " Failed to match visitor " . $visitor['id'] . " for hit zero orders. \n";
				} else if ($result == 1) {
					$matched_count++;
					echo "[". date('c'). "]" . $nick . " Succeed to match visitor " . $visitor['id'] . "\n";
				} else {
					echo "[". date('c'). "]" . $nick . " Failed to match visitor " . $visitor['id'] . " for hit multi orders. \n";
				}
			}
			
			$total_matched_count += $matched_count;
			echo "[". date('c'). "]" . $nick . " 待匹配用户：共" . count($visitors) . " 成功匹配" . $matched_count . " 耗时：".(microtime(true)-$start)."\n";
		}

        echo "[". date('c'). "]待匹配用户：共" . $total_count . " 成功匹配" . $total_matched_count . " 耗时：".(microtime(true)-$total_start)."\n";
    }
    
     /**
     * 抓取网页标题
     */
    public function actionCrawlWebTitle() {
    	$start = microtime(true);
    	$sql = "
    		SELECT		ae.id, ae.root_referer_url, ae.host
    		FROM		acookie.advert_effect ae
    		WHERE		ae.advert_source_id in (473, 501, 535, 560) 
    		AND 		keyword = ''
    		LIMIT 50
    	";
    	$adverts = $this->getReport()->getAll($sql);
	    foreach ( $adverts as $advert ) {
	    	$temp = file_get_contents($advert['root_referer_url']);
	    	if(!$temp) {
	    		break;
	    	}
	    	$reg="/<title>(.*)<\\/title>/";
	    	preg_match_all($reg, $temp, $arr);
	    	$rawtitle = $arr[1][0];
	    	if(preg_match("/[\x7f-\xff]/", $rawtitle) == 0) {
	    		//不包含中文
	    		$title = $rawtitle;
	    	} else if($this->isBaiduJingyanLink($advert['host'])) {
	    		$title = $rawtitle;
	    	} else if($this->isBaiduZhidaoLink($advert['host'])) {
	    		$title = iconv( "GBK", "UTF-8" , $rawtitle);
	    	} else if($this->isBaiduTiebaLink($advert['host'])) {
	    		$title = iconv( "GBK", "UTF-8" , $rawtitle);
	    	}
	    	
	    	if($title) {
	    		$title_items = preg_split('/_/', $title);
	    		$keyword = $title_items[0];
	    		if($keyword == "百度--您的访问出错了") {
	    			echo "[". date('c'). "]" . $advert['root_referer_url'] . " 访问出错 " ."\n";
	    			break;
	    		}
	    		$sql = " UPDATE acookie.`advert_effect` SET keyword = '{$keyword}' WHERE id = '{$advert['id']}'	";
	    		$this->getReport()->query($sql);
	    	}
	    }
	    echo "[". date('c'). "]" . " 抓取更新网页标题 " . count($adverts) . " 耗时：".(microtime(true)-$start)."\n";	
    }
      
    
    /**
     * 统计TP/TS
     */
    protected function getTimeOnSessionBySession($session_id) {
    	$sql = "
    		SELECT 		q.visitlog_id, q.created_stamp
    		FROM		acookie.quantum q
    		WHERE		q.session_id = '{$session_id}'
    		ORDER BY	q.visit_times ASC
    	";
    	$visitors = $this->getReport()->getAll($sql);
    	
    	$last_visitlog_id 		= 0;
    	$last_visitlog_stamp 	= 0;
    	$first_visitlog_stamp 	= 0;
    	foreach ( $visitors as $visitor ) {
    		if($last_visitlog_id != 0) {
    			$time_on_page = $visitor['created_stamp'] - $last_visitlog_stamp;
    			$sql = " UPDATE acookie.`visitlog` SET time_on_page = '{$time_on_page}' WHERE id = '{$last_visitlog_id}' ";
    			$this->getReport()->query($sql); 
    		} else {
    			$first_visitlog_stamp = $visitor['created_stamp'];
    		}
    		
    		$last_visitlog_id = $visitor['visitlog_id'];
    		$last_visitlog_stamp = $visitor['created_stamp'];
    	}
    	
    	$sql = " UPDATE acookie.`visitlog` SET time_on_page = 1 WHERE id = '{$last_visitlog_id}' ";
    	$this->getReport()->query($sql); 
	    	
    	$time_on_session = $last_visitlog_stamp - $first_visitlog_stamp;
    	if($time_on_session <= 0) {
    		$time_on_session = 1;
    	}
	    	
	    return $time_on_session;
    }
    
    /**
     * 统计TP/TS
     */
	public function actionUpdateKeyword() {
		$sql = "
			SELECT 		id, root_referer_url
			FROM		acookie.advert_effect
			WHERE		id in(
			)";
		$adverts = $this->getReport()->getAll($sql);
    	foreach ( $adverts as $advert ) {
    		$root_referer_url	= addslashes($advert['root_referer_url']);
    		$url_array			= parse_url($root_referer_url); 
    		$host 				= $url_array['host'];
    		$path 				= $url_array['path'];
    		$query 				= $url_array['query'];
    		$keyword 			= $this->getKeywordFromQuery($host, $path, $query, true);
    		$sql = "UPDATE acookie.`advert_effect` SET keyword = '{$keyword}' WHERE id = '{$advert['id']}'";
    		$this->getReport()->query($sql);
    	}	
    }
    
    /**
     * Google短链接
     */
    protected function get_long_url($url) {
    	$longurl = $url;
    	if(preg_match("/goo.gl/", $url)) {
    		$param = "https://www.googleapis.com/urlshortener/v1/url?shortUrl=";
    		$url = $param . $url; 
	    	$ch = curl_init(); 
	    	curl_setopt($ch, CURLOPT_URL,$url); 
	    	curl_setopt($ch, CURLOPT_HEADER, 0); 
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	    	curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
	    	$data = curl_exec($ch); 
	    	curl_close($ch);

			if($data) {
				$data = trim($data, "{");
				$data = trim($data, "}");
	    	
	    		$factors = preg_split('/,/', $data);
	    		foreach ( $factors as $factor ) {
	    			if(preg_match("/longUrl/", $factor)) {
	    				$factor = preg_replace("/http:/", "http=", $factor);
	    				$items = preg_split('/:/', $factor);
	    				$items[0] = trim($items[0]);
	    				$items[0] = trim($items[0], "\"");
		    			if($items[0] == "longUrl") {
		    				$items[1] = trim($items[1]);
		    				$items[1] = trim($items[1], "\"");
		    				$items[1] = preg_replace("/http=/", "http:", $items[1]);
		    				$longurl = $items[1];
		    			}
	    			}
	    		}
			}
    	}
    	
    	return $longurl;
    }
    
	protected function getKeywordFromQuery($host, $path, $query, $update=false) {
		$keyword = '';
		$encoding = '';
		
		// 新浪微博搜索关键词特殊处理
		$isSinaWeiboSearch = $this->isSinaWeiboSearch($host);
		if($isSinaWeiboSearch) {
			$rawkey = preg_replace("/\\/weibo\\//", "", $path);
			if(preg_match("/&/", $rawkey)) {
				$len=strpos($rawkey,'&');
				$rawkey=substr($rawkey,0, $len);
			}
			$temp = urldecode($rawkey);
			$keyword = urldecode($temp);
			$keyword = addslashes($keyword);
			return $keyword;
		}
		
		// 百度搜索URL编码识别，不能保证100%
		$isBaiduLink = $this->isExpectedLink($host, "baidu");
		if($isBaiduLink) {
			if($this->isBaiduMobileLink($host)) {
				$encoding = "UTF-8";
			} else if($this->isBaiduJingyanLink($host)){
				$encoding = "UTF-8";
			} else {
				$temp = mb_detect_encoding($query);
				if($temp == "UTF-8") {
					$encoding = "UTF-8";
				}
			}
		}
		
		// 获取原始关键词
		$query_factors = preg_split('/&/', $query);
		foreach ( $query_factors as $query_factor ) {
			$query_items = preg_split('/=/', $query_factor);
			if($isBaiduLink && $query_items[0] == "ie") {
				// 百度搜索
				$encoding = $query_items[1];
			}
			
			if( $query_items[0] == "query" 
				|| $query_items[0] == "q" 
				|| $query_items[0] == "wd"
				|| $query_items[0] == "w"
				|| $query_items[0] == "word"
				|| $query_items[0] == "keyword")	{
				$rawkey = $query_items[1];
			}
		}
		
		// 关键词解码
		if($rawkey) {
			$temp = urldecode($rawkey);	
			if(($update == true) || (preg_match("/[\x7f-\xff]/", $temp) == 0)) {
				//不包含中文
				$keyword = $temp;
			} else {
				if($isBaiduLink) {
					if($encoding == "UTF-8" || $encoding == "utf-8" || $encoding == "utf8" || $encoding == "UTF8") {
						$keyword = $temp;
					} else {
						$keyword = iconv( "GBK", "UTF-8" , $temp);
						if(!$keyword) {
							$keyword = $temp;
						}
					}
				} else {
					$encoding = mb_detect_encoding($temp);
					if($encoding == "ASCII") {
						$keyword = iconv( "GBK", "UTF-8" , base64_decode($temp));
					} else {
						$keyword = iconv( "GBK", "UTF-8" , $temp);
					}
				}
			}
		}
		
		// 关键词插入数据库前转义
		$keyword = trim($keyword);
		$keyword = addslashes($keyword);
		return $keyword;
    }
    
	protected function getAdvertSourceFromURL($host, $path, $keyword, $nick) {
		$advertSourceId = 0;
		$isTaobaoLink = $this->isTaobaoLink($host);
		if($isTaobaoLink) {
			// 站内
			$advertSourceId = 14;
			$host = preg_replace("/mall.taobao/", "tmall", $host);
			if ($this->isFromShop($host)) {
				$home = $this->getHomepageByNick($nick);
				if($host != $home) {
					// 其他店铺
					$advertSourceId = 27;
				} else {
					// 自己店铺							
					if( $path == '/' || $path == ''
					 || $path == '/shop/view_shop.htm' 
					 || $path == '/view_shop.htm'
					 || $path == '/shop/view%20shop.htm'
					 || preg_match("/\\/shop\\/view_shop(.*?)/", $path)
					 || preg_match("/\\/view_page-\d{9}\\.htm/", $path)) {
					 	// 店铺页
					 	$advertSourceId = 35;
					 } elseif($path == '/search.htm' || $path == '//search.htm') {
					 	if ($keyword == '' || $keyword == null) {
					 		// 店铺内部导航
					 		$advertSourceId = 37;
					 	} else {
					 		// 店铺内部搜索
					 		$advertSourceId = 38;
					 	}
					 }
				}
			} 	// 访问来源于店铺内部外部进行区分
			else {
				// 判断商城类目或者商城搜索时需检测关键词
				$is_search = 'N';
				if (preg_match("/list[0-9]{0,1}\\.tmall\\.com/", $host) && $keyword != '' && $keyword != null) {
					$is_search = 'Y';
				} elseif(preg_match("/\\/go\\/act\\/(.*?)/", $path)) {
					//$path = preg_replace("/\\/go\\/act\\/(.*?)/", "/go/act/", $path);
					$path = "/go/act/";
				} elseif(preg_match("/\\/go\\/chn\\/(.*?)/", $path)) {
					//$path = preg_replace("/\\/go\\/chn\\/(.*?)/", "/go/chn/", $path);
					$path = "/go/chn/";
				}
				
				$sql = " SELECT advert_source_id FROM acookie.taobao_host_source_mapping WHERE host = '{$host}' AND keyword = '{$is_search}' " ;
				$adverts = $this->getReport()->getAll($sql);
				$count = count($adverts);
				if($count == 1) {
					// 根据域名和关键词即可判断来源分类
					$advertSourceId = $this->getReport()->getOne($sql);
				} elseif ($count > 1) {
					// 根据域名和关键词以及路径才可判断来源分类
					$sql = " 
						SELECT 		advert_source_id 
						FROM 		acookie.taobao_host_source_mapping 
						WHERE 		host = '{$host}' 
						AND			path = '{$path}' 
						AND 		keyword = '{$is_search}' 
						LIMIT 1
					" ;
					$advertSourceId = $this->getReport()->getOne($sql);
				}
			}
		} else {
			// 淘宝站外若域名之前不存在则认为是新的
			$this->getMatchedHost($host);
			$sql = " SELECT advert_source_id FROM acookie.advert_source WHERE advert_source_host = '{$host}' LIMIT 1 " ;
			$res = $this->getReport()->getOne($sql);
			if($res) {
				$advertSourceId = $res;
			} else {
				$sql = "
					INSERT INTO acookie.`advert_source` (`advert_source_name`, `advert_source_host`, `parent_source_id`, `root_source_id`, 
								`created_stamp`, `last_updated_stamp`, `last_updated_tx_stamp`, `created_tx_stamp`) " . 
					" VALUES ( '{$host}', '{$host}', 7, 2, now(), now(), now(), now() ) ";
				$this->getReport()->query($sql);
	    		$advertSourceId =  $this->getReport()->insert_id();
			}
		}

    	return $advertSourceId;
    }
    
	protected function getAdvertDestFromURL($dest) {
    	$advertDestId = 0;
    	$url_array = parse_url($dest); 
    	$sql = " 
    		SELECT 	advert_source_id 
    		FROM 	acookie.taobao_host_source_mapping 
    		WHERE 	host = '{$url_array['host']}' 
    		AND 	path = '{$url_array['path']}' 
    		LIMIT 1
    	" ;
    	$advertDestId = $this->getReport()->getOne($sql);   
    	return $advertDestId; 	
    }
    
    protected function getMatchedHost(&$host) {
    	$host = trim($host, ".");
    	$host = preg_replace("/www[0-9]{0,1}\\./", "", $host);
    	
    	// 58同城
    	if(preg_match("/(.*?)58\\.com/", $host)) {
    		$host = "58.com";
    	}

    	// 赶集网
    	if(preg_match("/(.*?)ganji\\.com/", $host)) {
    		$host = "ganji.com";
    	}
    	
    	// 百姓网
    	if(preg_match("/(.*?)baixing\\.com/", $host)) {
    		$host = "baixing.com";
    	}
    	
    	// 百度移动
    	if($this->isBaiduMobileLink($host)) {
    		$host = "m.baidu.com";
    	}
    	
    	// 百度
    	if(preg_match("/baidu\\.com\\.cn/", $host)) {
    		$host = "baidu.com";
    	}
    	
    	// 360搜索
    	if(preg_match("/so\\.360\\.cn/", $host) || preg_match("/so\\.com/", $host)) {
    		$host = "so.360.cn";
    	}
    	
    	// 百度知道
    	if(preg_match("/zhidao\\.baidu\\.com/", $host) || preg_match("/z\\.baidu\\.com/", $host)) {
    		$host = "zhidao.baidu.com";
    	}
    	
    	// QQ邮箱
    	if(preg_match("/m*\\.mail\\.qq\\.com/", $host)) {
    		$host = "mail.qq.com";
    	}
    	
    	// QQ空间
    	if(preg_match("/(.*?)\\.qzone\\.qq\\.com/", $host) 
    	|| preg_match("/(.*?)\\.qzs\\.qq\\.com/", $host)
    	|| preg_match("/blog\\.z\\.qq\\.com/", $host) ) {
    		$host = "qzone.qq.com";
    	}
    	
    	// Q+ Web
    	if(preg_match("/web[0-9]{0,1}\\.qq\\.com/", $host) 
    	|| preg_match("/webqq\\.qq\\.com/", $host) 
    	|| preg_match("/w\\.qq\\.com/", $host)) {
    		$host = "web.qq.com";
    	}
    	
    	// 互联星空
    	if(preg_match("/vnet\\.cn/", $host)) {
    		$host = "vnet.cn";
    	}
    	
    	// 豆芽网
    	if(preg_match("/douya\\.cn/", $host)) {
    		$host = "douya.cn";
    	}
    	
    	// 妈妈网
    	if(preg_match("/(.*?)mama\\.com(.*?)/", $host) && !preg_match("/alimama\\.com(.*?)/", $host)) {
    		$host = "mama.com";
    	}
    	
    	// 金宝贝官网
    	if(preg_match("/(.*?)gymboree\\.com(.*?)/", $host)) {
    		$host = "gymboree.com";
    	}
    	
    	// 伟嘉官网
    	if(preg_match("/(.*?)whiskas(.*?)\\.com(.*?)/", $host)) {
    		$host = "whiskas.com";
    	}
    	
    	// 秒针广告
    	if(preg_match("/(.*?)miaozhen\\.com(.*?)/", $host)) {
    		$host = "miaozhen.com";
    	}
    	
    	// 马可波罗采购网-搜索引擎
    	if(preg_match("/(.*?)makepolo\\.com(.*?)/", $host)) {
    		$host = "makepolo.com";
    	}
    	
    	// PPTV
    	if(preg_match("/(.*?)pptv\\.com/", $host)) {
    		$host = "pptv.com";
    	}
    	
    	// 慧聪网
    	if(preg_match("/(.*?)hc360\\.com/", $host)) {
    		$host = "hc360.com";
    	}
    	
    	// 朋友网
    	if(preg_match("/(.*?)pengyou\\.com/", $host)) {
    		$host = "pengyou.com";
    	}
    	
		// 7k7k小游戏
    	if(preg_match("/(.*?)7k7k\\.com/", $host)) {
    		$host = "7k7k.com";
    	}
    	
    	// SINA
    	if(preg_match("/(.*?)sina\\.com\\.cn/", $host)) {
    		$host = "sina.com.cn";
    	}
    	
    	// 域名纠错
    	if(preg_match("/(.*?)wo\\.com\\.cn/", $host)) {
    		$host = "dnserror.wo.com.cn";
    	}
    	
    	// 爆米花视频网站
    	if(preg_match("/(.*?)baomihua\\.com(.*?)/", $host) || preg_match("/(.*?)pomoho\\.com(.*?)/", $host)) {
    		$host = "baomihua.com";
    	}
    	
    	// 必应搜索
    	if(preg_match("/(.*?)\\.bing\\.com/", $host)) {
    		$host = "bing.com";
    	}
    	
    	// GOOGLE搜索
    	if(preg_match("/google\\.com(.*?)/", $host)) {
    		$host = "google.com";
    	}
    	
    	// Live邮箱
    	if(preg_match("/(.*?)mail[0-9]{0,1}\\.(.*?)\\.com/", $host) 
    	|| preg_match("/(.*?)mail[0-9]{0,1}\\.(.*?)\\.cn/", $host)
    	|| preg_match("/(.*?)mail[0-9]{0,1}\\.(.*?)\\.net/", $host)
    	|| preg_match("/(.*?)webmail(.*?)\\.(.*?)\\.com/", $host)
    	|| preg_match("/(.*?)webmail(.*?)\\.(.*?)\\.cn/", $host)) {
    		$host = "mail.com";
    	}
 
    	return $host;
    }
 
    protected function isFromShop($host) {
    	$sql = "
    		SELECT		id
    		FROM		acookie.taobao_shop
			WHERE 		website = '{$host}' 
    	"; 
		$id = $this->getReport()->getOne($sql);
		if ($id > 0) {
			return true;
		}
		
		return false;
    }
    
    protected function getHomepageByNick($nick) {
    	$sql = "
    		SELECT		website
    		FROM		acookie.taobao_session_temp
			WHERE 		nick = '{$nick}' 
    	"; 
		$home = $this->getReport()->getOne($sql);
		return $home;
    }
    
	protected function isBaiduMobileLink($host) {
		return (preg_match("/m[0-9]{1}\\.baidu\\.com/", $host)) 
		|| ($host == "m.baidu.com") 
		|| ($host == "wap.baidu.com") 
		|| ($host == "3g.baidu.com") 
		|| ($host == "baidu.mobi");
    }
    
    protected function isBaiduZhidaoLink($host) {
    	return ($host == "z.baidu.com") || ($host == "zhidao.baidu.com");
    }
    
	protected function isBaiduJingyanLink($host) {
    	return ($host == "jingyan.baidu.com");
    }
    
    protected function isBaiduTiebaLink($host) {
    	return ($host == "tieba.baidu.com");
    }
    
    protected function isTaobaoLink($host) {
		return ( $this->isExpectedLink($host, "taobao") || $this->isExpectedLink($host, "tmall") );
    }
    
    protected function isSinaWeiboSearch($host) {
    	return ($host == "s.weibo.com");
    }
    
    protected function isExpectedLink($host, $expected) {
		$isExpectedLink = false;
		$host_factors = preg_split('/\./', $host);
		foreach ( $host_factors as $host_factor ) {
			if($host_factor == $expected) {
				$isExpectedLink = true;
				break;
			}
		}
    	return $isExpectedLink;
    }
    
    protected function getPartyIdBySession($session_id) {
    	$sql = " SELECT visitlog_id FROM acookie.quantum WHERE session_id = '{$session_id}' LIMIT 1"; 
		$visitlog_id = $this->getReport()->getOne($sql);
		$sql = " SELECT party_id FROM acookie.visitlog WHERE id = '{$visitlog_id}' ";
		$party_id = $this->getReport()->getOne($sql);
		return $party_id;
    }
   
    protected function getIsSessionHandled($session_id) {
    	$is_handled = '0';
    	$sql = " SELECT count(1) FROM acookie.advert_effect WHERE session_id = '{$session_id}' LIMIT 1"; 
		$count = $this->getReport()->getOne($sql);
		if($count > 0) {
			$is_handled = '1'; 
		}
		return $is_handled;
    }
    
    protected function getPageViewBySession($session_id) {
    	$sql = " SELECT MAX(visit_times) FROM acookie.quantum WHERE session_id = '{$session_id}' "; 
		$pv = $this->getReport()->getOne($sql);
		$sql = " SELECT COUNT(1) FROM acookie.quantum WHERE session_id = '{$session_id}' ";
		$pv2 = $this->getReport()->getOne($sql);
		if($pv < $pv2) {
			echo "[". date('c'). "]" . $session_id . " page view are not accurate. " . "\n";	
			$pv = $pv2;
		}
		return $pv;
    }
    
    /*
     * 量子不是在所有页面埋点，访问深度在排除刷新页的时候只能排除埋点页面的刷新动作
     * */
    protected function getVisitDepthBySession($session_id) { 
		$pv = $this->getPageViewBySession($session_id);
		$sql = " 
			SELECT 		count(1)
			FROM 		acookie.quantum q
			LEFT JOIN 	acookie.visitlog v on v.id = q.visitlog_id
			WHERE 		q.session_id = '{$session_id}' 
			AND			v.referer_url = v.current_url
		";
		$refresh = $this->getReport()->getOne($sql);
		$depth = $pv - $refresh;
		if($depth == 0) {
			$depth = 1;
		}
		return $depth;
    }
    
    protected function getLastURLBySession($session_id) {
    	$sql = " 
			SELECT 		v.referer_url, v.current_url
			FROM 		acookie.quantum q
			LEFT JOIN 	acookie.visitlog v on v.id = q.visitlog_id
			WHERE 		q.session_id = '{$session_id}' 
			ORDER BY	q.visit_times 
			DESC LIMIT  1
		";
    	
    	$visitor = $this->getReport()->getRow($sql);
    	if($visitor['current_url']) {
    		$dest	= addslashes($visitor['current_url']);
    	} else {
    		$dest	= addslashes($visitor['referer_url']);
    	}
    	
		return $dest;
    }
    
    
    protected function getConversionsBySession($session_id) {
    	$sql = "
    		SELECT		COUNT(v.id)
    		FROM		acookie.visitlog v
    		LEFT JOIN	acookie.quantum q on v.id = q.visitlog_id
    		WHERE		q.session_id = '{$session_id}'
    		AND			v.encrypted_taobao_order_sn <> ''
    	"; 
		$conversions = $this->getReport()->getOne($sql);
		return $conversions;
    }
    
	protected function getToMatchVisitors($application_key) {
		$sql = " 
			SELECT 		v.id, v.party_id, v.cookie_id, v.created_stamp, v.encrypted_taobao_order_sn, v.ip_address
			FROM 		acookie.taobao_session_temp tst 
			INNER JOIN 	acookie.visitlog v ON convert(tst.party_id using utf8) = v.party_id
			WHERE 		tst.application_key = '{$application_key}' 
			AND			v.encrypted_taobao_order_sn is not null
			AND			v.encrypted_taobao_order_sn <> ''
			AND			v.taobao_order_sn is null
			AND			v.created_stamp >= '1350741600'
			ORDER BY	v.id DESC
			LIMIT 5000
		";
		$visitors = $this->getReport()->getAll($sql);
		return $visitors;
	}
	
	/**
     * 解密 acookie.customer_cookie,时间模糊匹配直接命中
     * 
     * @return hit count
     */
	protected function MatchVisitor($visitor) {
		$visitlog_id 	= $visitor['id'];
		$party_id 		= $visitor['party_id'];
		$created_stamp 	= $visitor['created_stamp'];
		$cookie_id		= $visitor['cookie_id'];
		$ip_address		= $visitor['ip_address'];
	
  		$sql = "
  			SELECT		oi.taobao_order_sn, oa2.attr_value as nick
  			FROM		ecshop.ecs_order_info oi
  			LEFT JOIN	ecshop.order_attribute oa on oa.order_id = oi.order_id
  			LEFT JOIN 	ecshop.order_attribute oa2 on oa2.order_id = oi.order_id
  			WHERE 		oa.attr_name = 'TAOBAO_ORDER_CREATED_TIME' 
  			AND			oa2.attr_name = 'TAOBAO_USER_ID'
  			AND			ABS(timestampdiff(second,oa.attr_value, FROM_UNIXTIME($created_stamp))) <= 60
  			AND 		oi.party_id = '$party_id'
  		";
  		
  		$orders = $this->getReport()->getAll($sql);
  		$hit_count = count($orders);
  		if($hit_count == 1) {
  			$order = $orders[0];
  			$sql = " UPDATE acookie.visitlog SET taobao_order_sn = '{$order['taobao_order_sn']}', nick = '{$order['nick']}' where id = $visitlog_id ";
  			$this->getReport()->exec($sql);
  			$sql = " SELECT count(1) FROM acookie.customer_cookie WHERE TAOBAO_USER_ID = '{$order['nick']}' AND COOKIE_ID = '{$cookie_id}' ";
  			$res = $this->getReport()->getOne($sql);
	  		if($res == 0){
	  			$sql = "INSERT INTO acookie.`customer_cookie` (`TAOBAO_USER_ID`, `COOKIE_ID`) VALUES ('{$order['nick']}', '{$cookie_id}')" ;
	  			$this->getReport()->exec($sql);
	  		}
  		} elseif ($hit_count > 1) {
  			foreach ( $orders as $order ) {
	  			if ($this->isIPMatched($order['taobao_order_sn'], $ip_address)) {
		  			$sql = " UPDATE acookie.visitlog SET taobao_order_sn = '{$order['taobao_order_sn']}', nick = '{$order['nick']}' where id = $visitlog_id ";
		  			$this->getReport()->exec($sql);
		  			$sql = " SELECT count(1) FROM acookie.customer_cookie WHERE TAOBAO_USER_ID = '{$order['nick']}' AND COOKIE_ID = '{$cookie_id}' ";
		  			$res = $this->getReport()->getOne($sql);
			  		if($res == 0){
			  			$sql = "INSERT INTO acookie.`customer_cookie` (`TAOBAO_USER_ID`, `COOKIE_ID`) VALUES ('{$order['nick']}', '{$cookie_id}')" ;
			  			$this->getReport()->exec($sql);
			  		}
			  		$hit_count = 1;
			  		echo "[". date('c'). "]" . " hit multi orders by ipaddress " . "\n";
			  		break;
	  			}
  			}
  		} else {
  			$sql = " UPDATE acookie.visitlog SET taobao_order_sn = 'UNKNOWN' where id = $visitlog_id ";
  			$this->getReport()->exec($sql);
  		}
	
	  	return $hit_count;
	}
	
	protected function isIPMatched($taobao_order_sn, $ip_address) {
		$sql = "
			SELECT		r1.region_name as province, r2.region_name as city, r3.region_name as district
			FROM		ecshop.ecs_order_info oi
			LEFT JOIN 	ecshop.ecs_region r1 ON oi.province = r1.region_id
			LEFT JOIN 	ecshop.ecs_region r2 ON oi.city = r2.region_id
			LEFT JOIN 	ecshop.ecs_region r3 ON oi.district = r3.region_id
			WHERE		oi.taobao_order_sn = '{$taobao_order_sn}'
		";
		$address_from_ecshop = $this->getReport()->getRow($sql);
		
		$ip_address = preg_replace("/\*/", "1", $ip_address);
		$ipnum = bindec(decbin(ip2long($ip_address)));
		$sql = " SELECT region, city, county FROM acookie.ip_taobao WHERE startipnum <= {$ipnum} AND endipnum >= {$ipnum} ";
		$address_from_acookie = $this->getReport()->getRow($sql);
		if( !empty($address_from_acookie) ) {
			if ( strpos($address_from_acookie['region'], $address_from_ecshop['province']) !== false
			  && strpos($address_from_acookie['city'], $address_from_ecshop['city']) !== false ) {
			 	return true;
			 } elseif (strpos($address_from_acookie['region'], $address_from_ecshop['province']) !== false
			  		&& strpos($address_from_acookie['city'], $address_from_ecshop['province']) !== false
			  		&& strpos($address_from_acookie['county'], $address_from_ecshop['city']) !== false ) {
			 	return true;
			 }
		} else {
			echo "[". date('c'). "]" . " IP library need to update " . $ipnum . " " . $ip_address . "\n";
			return false;
		}
		
		return false;
	}
	
	protected function CreateTasks($application_key, $task_type, $session_key){
		$start_date = date("Ymd", strtotime("-1 day"));
		$end_date 	= date("Ymd", time());
		$task = $this->getLatestCreatedTask($application_key, $task_type);
		if(empty($task)) {
			$task['app_key']	= self::APP_KEY;
			$task['app_secret'] = self::APP_SECRET;
			$task['session_id'] = $session_key;
			$task['task_type']	= $task_type;
			$start_date 		= date("Ymd", strtotime("-8 day"));
		} else {
			$task['session_id'] = $session_key;
			$task['task_type']	= $task_type;
			// 淘宝taobao.topats.visitlog.get参数格式Ymd,数据库存储的是Y-m-d格式
			$start_date_ex = date("Y-m-d", strtotime("-1 day"));
			if($start_date_ex == $task['day']) {
				echo "[". date('c'). "]" . " All task has been created with application key: " . $application_key . "\n";
				return true;
			}
			$start_date 		= $task['day'];
		}

		// 创建日期
		$start_stamp = strtotime($start_date);
		$date_stamp = strtotime("+1 day", $start_stamp);
		$date = date("Ymd", $date_stamp);
		while($date != $end_date) {
			$task['day'] = $date;
			$result = $this->createTask($application_key, $task);
			if (!$result) {
				echo "[". date('c'). "]" . " Failed to create task with application key: " . $application_key . "\n";
			}
			$date_stamp = strtotime("+1 day", $date_stamp);
			$date = date("Ymd", $date_stamp);
		}
		
		return true;
	}
	
	protected function createTask($application_key, $task) {
		$taobao_client = $this->getTaobaoClient($task);
		$method = $this->getMethodName($task['task_type']);
		$request = array ( 'day' => $task['day']);  
		$try_count = 1;
		while (1) {
			try {
				$response = $taobao_client->execute($method, $request);
				break;
			} catch (Exception $e) {
				echo("|  - has exception: ". $e->getMessage() . "\n");
			}
			
			$try_count++;
			if($try_count > 10) {
				echo "[". date('c'). "]" . $method . " Failed to get log. " . "\n";
				return false;
			}
			
			usleep(500000);
		}
		
		$task_id = 0;
		if($response->isSuccess()) {
			$task_id = $response->task->task_id;
		} else if($response->getSubCode()=='isv.task-duplicate') {
			$sub_msg = $response->getSubMsg();
			$msg_factors = preg_split('/=/', $sub_msg);
	  		if(count($msg_factors) == 2) {
		  		$temp = $msg_factors[1];
		  		$isExist = $this->isTaskExist($temp);
		  		if(!$isExist){
		  			$task_id = $temp;
		  		}
	  		}
		} 
		
		if(!$task_id) {
			echo "[". date('c'). "]" . $response->getSubMsg() . "\n";
			return false;
		}
		
		$created_stamp = time();
		$sql = " 
			SELECT 		t.nick, t.party_id
			FROM 		acookie.taobao_session_temp t 
			WHERE 		t.application_key = '{$application_key}' 
		";
		$shop = $this->getReport()->getRow($sql);
		
		$sql = "
			INSERT INTO acookie.task(created_stamp, application_key, nick, party_id, app_key, app_secret, session_id, day, type, status, task_id, download_url) " .
			" VALUES ('{$created_stamp}', '{$application_key}', '{$shop['nick']}', '{$shop['party_id']}', '{$task['app_key']}', '{$task['app_secret']}', 
				'{$task['session_id']}', '{$task['day']}', '{$task['task_type']}', 'N', '{$task_id}', ''
			) ";
		$this->getReport()->query($sql);
		return true;
	}
	
	protected function getMethodName($task_type) {
		$method = '';
		if ($task_type == 'visitlog') {
			$method = "taobao.topats.visitlog.get";
		} elseif ($task_type == 'waplog') {
			$method = "taobao.topats.waplog.get";
		} elseif ($task_type == 'shop.weblog') {
			$method = "taobao.topats.shop.weblog.get";
		} elseif ($task_type == 'shop.waplog') {
			$method = "taobao.topats.shop.waplog.get";
		}
		return $method;
	}
	
	protected function getLatestSessionKey($application_key) {
		$temp = date("Y-m-d", time());
		$sql = " 
			SELECT 		t.session_key
			FROM 		acookie.taobao_session_temp t 
			WHERE 		t.application_key = '{$application_key}'  
			AND 		t.last_updated_date = '{$temp}'
			LIMIT 1 
		";
		$session_key = $this->getReport()->getOne($sql);
		return $session_key;
	}
	
	protected function getLatestCreatedTask($application_key, $task_type) {
		$sql = " 
			SELECT 		t.party_id, t.type, t.day, t.app_key, t.app_secret, t.session_id
			FROM 		acookie.task t 
			WHERE 		t.application_key = '{$application_key}'  
			AND 		t.type = '{$task_type}'
			ORDER BY	t.day DESC
			LIMIT 1
		";
		$task = $this->getReport()->getRow($sql);
		return $task;
	}
	
	protected function isTaskExist($task_id) {
		$sql = " 
			SELECT 		count(1)
			FROM 		acookie.task 
			WHERE 		task_id = '{$task_id}'
			LIMIT 1
		";
		$count = $this->getReport()->getOne($sql);
		return ($count > 0) ? true : false;
	}
	
	protected function isSessionParsed($session_id) {
		$sql = "
			SELECT 	count(1) 
			FROM 	acookie.advert_effect 
			WHERE 	session_id = '{$session_id}'
			LIMIT 1
		";
		$count = $this->getReport()->getOne($sql);
		return ($count > 0) ? true : false;
	}
	
	protected function getToDownloadTasks($application_key) {
		$sql = " 
			SELECT 		t.party_id, t.type, t.task_id, t.app_key, t.app_secret, t.session_id, 'N' AS is_sandbox
			FROM 		acookie.taobao_session_temp tst 
			INNER JOIN 	acookie.task t ON tst.party_id = t.party_id
			WHERE 		tst.application_key = '{$application_key}' 
			AND			t.status = 'N'
			AND			t.download_url = ''
			AND			ABS( TIMESTAMPDIFF(day, DATE_FORMAT(NOW(),'%Y%m%d'), t.day) ) <= 30
			ORDER BY	t.day ASC
		";
		$tasks = $this->getReport()->getAll($sql);
		return $tasks;
	}
	
	protected function getTodoTasks($application_key) {
		$sql = " 
			SELECT 		t.party_id, t.type, t.task_id, t.download_url, t.app_key, t.app_secret, t.session_id, 'N' AS is_sandbox
			FROM 		acookie.taobao_session_temp tst 
			INNER JOIN 	acookie.task t ON tst.party_id = t.party_id
			WHERE 		tst.application_key = '{$application_key}' 
			AND			t.status = 'N'
			AND			t.download_url <> ''			
			AND			ABS( TIMESTAMPDIFF(day, DATE_FORMAT(NOW(),'%Y%m%d'), t.day) ) <= 30
			ORDER BY	t.day ASC
		";
		$tasks = $this->getReport()->getAll($sql);
		return $tasks;
	}
	
	protected function downloadTask($taobao_client, $task) {
		// 获取Task信息
		$task_id = $task['task_id'];
		$request = array ( 'task_id' => $task_id);  
		$try_count = 1;
		while (1) {
			try {
				$response = $taobao_client->execute('taobao.topats.result.get', $request);
				if( $response->task->status != 'done') {
					echo "[". date('c'). "]" . $task_id . " downloading... " . "\n";
					return false;
				}
				break;
			} catch (Exception $e) {
				echo("|  - has exception: ". $e->getMessage() . "\n");
			}
			
			$try_count++;
			if($try_count > 10) {
				echo "[". date('c'). "]" . $task_id . " Failed to get task. " . "\n";
				return false;
			}
			
			usleep(500000);
		}
		
		// 更新数据库记录
		$this->updateTaskDownloadURL($response->task->download_url, $task_id);
		return true;
	}
	
	protected function doTask($taobao_client, $task) {
		// 获取Task信息
		$task_id = $task['task_id'];
		$task_url = $task['download_url'];
		
		// 下载
		// http://dl.api.taobao.com/topdownload?app_key=21125119&sign=44E21B31FF244B787B8393104730F4BF&timestamp=1352100613192&token=6bb37b38-350b-4548-a68b-019063901a50
		// -> ../../ACookieData/visitlog/6bb37b38-350b-4548-a68b-019063901a50
		$filename = $this->getFileName($task_url);
		$dir = "/var/www/http/ACookieData/visitlog/";
		if(file_exists($dir . $filename) == false) {
			$result = $this->downloadFile($task_url, $dir, $filename);
			if($result == false) {
				echo "[". date('c'). "]" . " Failed to download task. " . $task_id . " with url: " . $task_url . "\n";
				return false;
			}
		}
		
		// 解压缩
		// -> ../../ACookie/visitlog/6bb37b38-350b-4548-a68b-019063901a50~
		$path = $dir . $filename;
		$extractedFileName = $filename . "~";
		if(file_exists($dir . $extractedFileName) == false) {
			$result = $this->decompressFile($path, $dir, $extractedFileName);
			if($result == false) {
				echo "[". date('c'). "]" . " Failed to decompress task. " . $task_id . " with url: " . $task_url . "\n";
				return false;
			}
		}
		
		// 文件->数据库
		$cookie_file = $dir . $extractedFileName;
		$result = $this->readFileToDB($cookie_file, $task['party_id'], $task['type']);
		if($result == false) {
			echo "[". date('c'). "]" . " Failed to convert file to db. " . $task_id . " with file: " . $cookie_file . "\n";
			return false;
		}
	
		// 更新数据库记录
		$this->updateTaskStatus($task_id);	
		return true;
	}
	
	/**
	 * 获取远程url下载文件名
	 */
	protected function getFileName($url) {
		$path=parse_url($url); 
		$str=end(explode('&',$path['query'])); 
		$filename=end(explode('=',$str));
		return $filename; 
	}
	
	/**
	 * 通过远程url，下载到本地
	 * @param: $url为远程链接
	 * @param: $filename为下载图片后保存的文件名
	 */
	protected function downloadFile($url, $dir, $filename) { 
	    if($url=="") {
	    	return false;
	    }
	    
	    ob_start(); 
	    $result = @readfile($url); 
	    if(!$result) {
	    	ob_end_clean();
	    	return false;
	    }
	    $file = ob_get_contents(); 
	    ob_end_clean(); 
	    $size = strlen($file); 
	     
	    //"../../images/books/"为存储目录，$filename为文件名
	    $fp2=@fopen($dir . $filename, "a"); 
	    fwrite($fp2,$file); 
	    fclose($fp2); 
	     
	    return $filename; 
	}
	
	protected function decompressFile($path, $dir, $filename) { 
		if($path=="") {
	    	return false;
	    }
	     
	    ob_start(); 
	    readfile($path); 
	    $file = ob_get_contents(); 
	    ob_end_clean(); 
	    $size = strlen($file); 
	
	   	$data = gzinflate(substr($file,10,-8));
		if(!$data) {
	    	return false;
	    }
	     
	    $fp2=@fopen($dir . $filename, "a"); 
	    fwrite($fp2,$data); 
	    fclose($fp2); 
	     
	    return $filename; 
	}
	
	protected function updateTaskDownloadURL($url, $task_id) {
		$sql = " UPDATE acookie.task SET download_url = '{$url}' WHERE task_id = '{$task_id}' ";
		$this->getReport()->query($sql);
	}
	
	protected function updateTaskStatus($task_id) {
		$sql = " UPDATE acookie.task SET status = 'S' WHERE task_id = '{$task_id}' ";
		$this->getReport()->query($sql);
	}
		
	protected function readFileToDB($file, $party_id, $task_type){
		$f= fopen($file, "r");
		if(!$f) {
			echo "[". date('c'). "]" . " Failed to open file " . $file . "\n";
			return false;
		}
	
		/**
		 * 逐个字符读入，判断是否是间隔符SOH或者是换行符LN：碰到间隔符SOH，代表一个词结束；碰到LN，一个词结束，且一条记录也结束
		 * 目前读完文件再插入，若以后数据量增大，则可以考虑采用读一行文件记录一行数据库
		 */
		while (!feof($f)) {
	  		$array  = array();
	  		$arrayex = array();
	  		$tes = 0;
	  		$word = '';
	  		while (1) {	
	  			$temp = fgetc($f);
	  			if(!is_string($temp) || feof($f)){
	  				break;
	  			}
	  	
	  			if ($temp == chr(1)){
	  				// SOH(start of headline)	标题开始
	  				$array[] = $word;
	  				$word = '';
	  			} else if($temp == chr(10)) {
	  				// LF (NL line feed, new line)	换行键
	  				$array[] = $word;
	  				$word = '';
	  		
	  				$this->ScanLogOneByOne($array, $party_id, $task_type);
	  				unset($array);
	  				$array  = array();
	  			} else{
	  				$word = $word . $temp;
	  				$tes++;
	  			}
	  		}
		}
		
		fclose($f);
		return true;
	}
	
	protected function ScanLogOneByOne($acookie, $party_id, $task_type) {
		if($task_type == 'waplog') {
		 	$this->ScanWaplogOneByOne($acookie, $party_id, false);
		} elseif($task_type == 'visitlog') {
		  	$this->ScanVisitlogOneByOne($acookie, $party_id, false);
		} elseif($task_type == 'shop.weblog') {
		  	$this->ScanVisitlogOneByOne($acookie, $party_id, true);
		} elseif($task_type == 'shop.waplog') {
		  	$this->ScanWaplogOneByOne($acookie, $party_id, true);
		} else {
			return false;
		}
		return true;
	}
	
	protected function ScanWaplogOneByOne($acookie, $party_id, $isAPlus) {
		if ($isAPlus == false) {
			$created_stamp 				= $acookie[0];
			$ip_address					= $acookie[1];
			$current_url				= addslashes($acookie[2]);
			$referer_url				= addslashes($acookie[3]);
			$member_id					= $acookie[4];
			$session_id					= $acookie[5];
		} else {
			$log_type					= $acookie[0];
			$created_stamp 				= $acookie[1];
			$ip_address					= $acookie[2];
			$current_url				= addslashes($acookie[3]);
			$referer_url				= addslashes($acookie[4]);
			$member_id					= $acookie[5];
			$session_id					= $acookie[6];
			$browser					= $acookie[7];
			$category_id				= $acookie[8];
			$item_id					= $acookie[9];
			$title						= $acookie[10];
			$phone_brand				= $acookie[11];
			$phone_model				= $acookie[12];
			$phone_os					= $acookie[13];
			$phone_imei					= $acookie[14];
		}
		
		$sql = " 
			SELECT 	id 
			FROM 	acookie.waplog 
			WHERE 	created_stamp = '{$created_stamp}' 
			AND 	(member_id = '{$member_id}' OR session_id = '{$session_id}') 
			AND 	current_url = '{$current_url}'
			AND		referer_url = '{$referer_url}'
		";
	  	$res = $this->getReport()->getOne($sql);
	  	if($res != 0) {
	  		$sql = " UPDATE acookie.waplog SET member_id = '{$member_id}', session_id = '{$session_id}' where id = $res ";
	  		$this->getReport()->query($sql);
	  	} else {
	  		if ($isAPlus == false) {
		    	$sql = "
		    		INSERT INTO	acookie.waplog(created_stamp, party_id, ip_address, current_url, referer_url, member_id, session_id) " .
		    		" VALUES ('{$created_stamp}', '{$party_id}', '{$ip_address}', '{$current_url}', '{$referer_url}', '{$member_id}', '{$session_id}')"; 
	  		} else {
	  			$sql = "
		    		INSERT INTO	acookie.waplog(log_type, created_stamp, party_id, ip_address, current_url, referer_url, member_id, session_id,
		    					browser, category_id, item_id, title, phone_brand, phone_model, phone_os, phone_imei) " .
		    		" VALUES ('{$log_type}', '{$created_stamp}', '{$party_id}', '{$ip_address}', '{$current_url}', '{$referer_url}', '{$member_id}', '{$session_id}',
		    			'{$browser}', '{$category_id}', '{$item_id}', '{$title}', '{$phone_brand}', '{$phone_model}', '{$phone_os}', '{$phone_imei}' )"; 
	  		}	
		    $this->getReport()->query($sql);
	  	}
	}
	
	/* 
	 * 	针对每一行ACOOKIE数据，需要完成如下几步
	 * 	第一步：去重，防止插入重复数据，以cookie和量子作为唯一性判断
	 * 	第二步：量子，quantum = session_id+created_stamp+visit_times
	 *  第三步：昵称，如果有加密订单号，则根据订单时间破解用户以及订单号。
	 *  第四步：来源，订单来源分析
	 */
	function ScanVisitlogOneByOne($acookie, $party_id, $isAPlus) {
		if ($isAPlus == false) {
			$file_version 				= $acookie[0];
			$created_stamp 				= $acookie[1];
			$ip_address					= $acookie[2];
			$cookie_id					= $acookie[3];
			$browser					= $acookie[4];
			$current_url				= addslashes($acookie[5]);
			$referer_url				= addslashes($acookie[6]);
			$quantum					= $acookie[7];
			$is_member					= $acookie[8];
			$current_title				= addslashes($acookie[9]);
			$encrypted_taobao_order_sn 	= addslashes($acookie[10]);
			$encrypted_nick 			= addslashes($acookie[11]);
		} else {
			$file_version 				= "3.0";
			$created_stamp 				= $acookie[0];
			$ip_address					= $acookie[1];
			$cookie_id					= $acookie[2];
			$browser					= $acookie[3];
			$current_url				= addslashes($acookie[4]);
			$referer_url				= addslashes($acookie[5]);
			$quantum					= $acookie[6];
			$is_member					= $acookie[7];
			$current_title				= addslashes($acookie[8]);
			$encrypted_taobao_order_sn 	= "";
			$encrypted_nick 			= addslashes($acookie[9]);
		}
	
	  	// 去重acookie.visitlog
	  	$sql = " 
	  		SELECT 	count(1) 
	  		FROM 	acookie.visitlog 
	  		WHERE 	cookie_id = '{$cookie_id}' 
	  		AND		file_version = '{$file_version}'
	  		AND		party_id = '{$party_id}'
	  		AND 	ip_address = '{$ip_address}'
	  		AND 	quantum = '{$quantum}' 
	  		AND 	created_stamp = '{$created_stamp}'
	  		AND 	browser = '{$browser}'
	  		AND 	is_member = '{$is_member}'
	  		AND 	current_url = '{$current_url}'
			AND		referer_url = '{$referer_url}'
			AND		current_title = '{$current_title}'
			AND		encrypted_taobao_order_sn = '{$encrypted_taobao_order_sn}'
			AND		encrypted_nick = '{$encrypted_nick}'	
	  	";
	  	$result = $this->getReport()->getOne($sql);
	  	if($result != 0){
	  		echo "[". date('c'). "]" . $quantum . " quantum exists in acookie.visitlog. " . "\n";
	  		return false;
	  	}
	  	
	    $sql = "
	    	INSERT INTO	acookie.visitlog(file_version, party_id, created_stamp, ip_address, cookie_id, browser, current_url, referer_url, 
	    			quantum, is_member, current_title, encrypted_taobao_order_sn, encrypted_nick) " .
	    	" VALUES ('{$file_version}', '{$party_id}', '{$created_stamp}', '{$ip_address}', '{$cookie_id}', '{$browser}', '{$current_url}', '{$referer_url}', 
	    			'{$quantum}', '{$is_member}', '{$current_title}', '{$encrypted_taobao_order_sn}', '{$encrypted_nick}') ";	
	  
	    $this->getReport()->query($sql);
	    $visitlog_id = $this->getReport()->insert_id();
	  	if($visitlog_id == 0) {
	 		echo "[". date('c'). "]" . $quantum . " quantum insert into visitlog failed. " . "\n";
	 		return false;
	  	}
	  	
	  	// 量子 acookie.quantum
	  	$quantum_factors = preg_split('/_/', $quantum);
	  	// $quantum_factor = explode("_", $quantum);
	  	if(count($quantum_factors) == 3) {
	  		$session_id = $quantum_factors[0];
	  		$created_stamp = $quantum_factors[1];
	  		$visit_times = $quantum_factors[2];
	  		$sql = "
	  			INSERT INTO acookie.`quantum` (`visitlog_id`, `quantum`, `session_id`, `created_stamp`, `visit_times`) 
	  			VALUES($visitlog_id, '$quantum', '$session_id', $created_stamp, $visit_times);
	  		";
	  		$this->getReport()->query($sql);
	  		
	  		$sql = " SELECT count(1) FROM acookie.session WHERE session_id = '{$session_id}' LIMIT 1"; 
	  		$count = $this->getReport()->getOne($sql);
	  		if($count < 1) {
	  			$sql = " SELECT count(1) FROM acookie.visitlog WHERE cookie_id = '{$cookie_id}' LIMIT 1";
	  			$visit_count = $this->getReport()->getOne($sql);
	  			$is_secondary_visitor = ($visit_count > 0) ? 1 : 0;
	  			$is_secondary_purchase = 0;
		  		$sql = "
			  		INSERT INTO acookie.`session` (`session_id`, `party_id`, `is_handled`, `is_member`, `is_secondary_visitor`, `is_secondary_purchase`) 
			  		VALUES('{$session_id}', '{$party_id}', '0', '{$is_member}', '{$is_secondary_visitor}', '{$is_secondary_purchase}');
			  	";
			  	$this->getReport()->query($sql);
	  		}
	  	}
	  	
	  	return true;
	}
	
	protected function getTaobaoClient($task) {
		static $clients = array ();
	    $key = $task ['session_id'];
		if (! isset ( $clients [$key] )) {
			$clients [$key] = new TaobaoClient ( $task['app_key'], $task['app_secret'], $task['session_id'], ($task['is_sandbox'] == 'Y' ? true : false) );
		}
		
		return $clients [$key];
	}
    
 	/**
     * 取得report数据库连接
     * 
     * @return CDbConnection
     */
    protected function getReport() {
        if(! $this->report) {
        	global $acookie_db_host;
        	global $acookie_db_user;
        	global $acookie_db_pass;
        	global $acookie_db_name;
        	$this->report = new cls_mysql($acookie_db_host, $acookie_db_user, $acookie_db_pass, $acookie_db_name);
        }
        
        return $this->report;
    }
}