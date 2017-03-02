<?php
/**
 * 分页类
 * $Author: ychen 
*/

class Paginations {
	public $current_page;			// 当前第几页
	public $page_param;			// 传递页数的参数
	public $total_count;			// 总共的行数
	public $page_size;				// 一页中的行数
	public $url;					// 链接页面，#page#代表页数
	public $exclude_params;		// 去除的参数
	public $extra_params;			// 添加的参数

	public $first_page_number = 2;		// 开头显示几个页码
	public $last_page_number = 2;		// 结尾显示几个页码
	public $page_offset = 2;		// 当前为基准往前显示和往后显示的页码数

	public $prev_page_label = "上一页";
	public $next_page_label = "下一页";
	public $event = '';

	public $page_count;		// 总共的页面数

	public $page_replace = "#page#";

	public $query_sql;			// 数据库查询sql
	public $count_sql;			// 查询总数的sql
	public $data_set;			// 查询的数据
	protected $db;				// 数据库处理对象


	/**
	 * 构造函数
	 *
	 * @param int $total_count
	 * @param int $page_size
	 * @param int $page_param
	 * @param int $current_page	-1表示从url中读取$page_param的值
	 * @param array $exclude_params
	 * @param array $extra_params
	 */
	public function __construct($total_count = 0, $page_size = 10, $current_page = -1, $page_param = 'page', $url = '', $exclude_params = null, $extra_params = null, $anchor = null) {
		$this->total_count = $total_count;
		$this->page_size = $page_size;
		$this->page_param = $page_param;
		$this->url = $url;
		$this->exclude_params = $exclude_params;
		$this->extra_params = $extra_params;
		$this->anchor = $anchor;

		if ($current_page == -1) {
			$current_page = intval($_REQUEST[$page_param]);
			$current_page = $current_page > 0 ? $current_page : 1;
			$this->current_page = $current_page;
		} else {
			$this->current_page = $current_page;
		}

		//$this->page_count = intval(ceil(floatval($total_count) / $page_size));
		$this->page_count = ceil($total_count / $page_size);

		$this->calculate_url();
	}

	/**
	 * 设置数据库查询sql，用来获取数据与计算总长度。若memcache不为NULL，则使用memcache读取/缓存count查询结果。
	 *
	 * @param string $sql 数据库查询sql
	 * @param cls_mysql $db 数据库查询对象
	 * @param Memcache $memcache memcache缓存处理类
	 * @param int $cache_time 缓存时间
	 */
	public function set_query($sql, $db, $memcache = null, $cache_time = 3600) {
		$this->query_sql = trim($sql);
		$this->count_sql = preg_replace(array('/SELECT.*?FROM /Asi', '/ORDER BY .*/'), array('SELECT COUNT(*) FROM ', ''), $this->query_sql);
		$this->db = $db;

		$start = ($this->current_page - 1) * $this->page_size;
		$this->data_set = $db->getAll($this->query_sql . " LIMIT {$this->page_size} OFFSET {$start}");

		if ($memcache != null && get_class($memcache) == "Memcache") {	// 判断是否缓存
			$this->total_count = $memcache->get(md5($this->count_sql));
			if ($this->total_count === false) {
				$this->total_count = $db->getOne($this->count_sql);
				$memcache->set(md5($this->count_sql), $this->total_count, 0, $cache_time);
			}
		} else {
			$this->total_count = $db->getOne($this->count_sql);
		}

		$this->page_count = ceil($this->total_count / $this->page_size);
	}

	/**
	 * 向url中添加参数并返回添加参数后的url，参数名和参数值都会进行编码
	 *
	 * @author Zandy<yzhang@ouku.com>
	 * 
	 * @param string $url 处理前的URL
	 * @param string $key 添加的参数名
     * @param string $value 添加的参数值
     * @param string $is_encode 添加的参数是否需要编码
	 *
	 * @return string 添加完参数后的url
	 **/
	protected function add_param_in_url ($url, $key, $value, $is_encode = true) {
		if ($is_encode) {
			return $this->remove_param_in_url($url, $key, true) . urlencode($key) . '=' . urlencode($value);
		} else {
			return $this->remove_param_in_url($url, $key, true) . "{$key}={$value}";
		}
	}

	/**
	 * 从url中移除参数，并返回移除参数后的url
	 *
	 * @author Zandy<yzhang@ouku.com>
	 * 
	 * @param string $url 处理前的URL
	 * @param string $pkey 需要移除的参数名
	 * @param boolean $append 是否在尾部附加 ? 或 & 
	 *
	 * @return string 添加完参数后的url
	 **/
	function remove_param_in_url ($url, $pkey, $append = false) {
		$preg = '/[\?|&](' . preg_quote($pkey) . '=([^&=]*))/';
		$m = null;
		preg_match_all($preg, $url, $m);
		if (isset($m[1]) && is_array($m[1])) {
			foreach ($m[1] as $v) {
				$url = str_replace($v, "", $url);
			}
		}
		
		$url = str_replace(array("?&", "&&"), array("?", "&"), $url);
		$r = rtrim($url, ' &?');
		
		if ($append) {
			if (strpos($r, '?') === false) {
				$r .= '?';
			}
			if (substr($r, -1) != '?' && substr($r, -1) != '&') {
				$r .= '&';
			}
		}
		return $r;
	}


	/**
	 * 计算当前url
	 *
	 */
	protected function calculate_url() {
		if ($this->url[0] == '#') {
			return;
		}


		if ($this->url == '') {
			$this->url = $_SERVER['REQUEST_URI'];
		}
		
		if (is_array($this->exclude_params)){
			foreach ($this->exclude_params as $key => $param) {
				$this->url = $this->remove_param_in_url($this->url, $param);
			}
		}

		if (is_array($this->extra_params)) {
			foreach ($this->extra_params as $name => $value) {
				$this->url = $this->add_param_in_url($this->url, $name, $value);
			}
		}
		
		$this->url = $this->add_param_in_url($this->url, $this->page_param, $this->page_replace, false);
	}

	/**
	 * 返回一套分页的html
	 * @param $range int 表示显示几个可点的页码
	 * @return string 一套分页的html
	 */
	public function get_simple_output($range = 9) {
		$output = "共". $this->page_count . "页&nbsp;&nbsp;共".$this->total_count."条";

		if ($this->page_count > 1) {
			$start_page = $this->current_page - ($range - 1) / 2;
			$end_page = $start_page + $range - 1;

			if ($start_page < 1) {
				$start_page = 1;
			}
			if ($end_page > $this->page_count) {
				$end_page = $this->page_count;
			}

			if ($start_page > 1) {
				$output .= " " . $this->get_one_page_html(1, '首页');
				$output .= " " . $this->get_one_page_html($this->current_page - 1, '上一页');
				$output .= " ...";
			}
			for ($i = $start_page; $i <= $end_page; $i++) {
				$output .= " " . $this->get_one_page_html($i) ;
			}

			if ($end_page < $this->page_count) {
				$output .= " ...";
				$output .= " " . $this->get_one_page_html($this->current_page + 1, '下一页');
				$output .= " " . $this->get_one_page_html($this->page_count, '最后一页');
			}
		}
		return $output;
	}

	/**
	 * 显示一个页码的html代码
	 *
	 * @param int $page_number 显示页码
	 * @return string 返回的html代码
	 */
	public function get_one_page_html($page_number, $label = '') {
		if ($label == '') {
			$label = $page_number;
		}
		$output = "";
		if ($page_number == $this->current_page) {
			$output .= "<b>$label</b>";
		} else {
			$output .= "<a href=\"" . str_replace($this->page_replace, $page_number, $this->url) . ($this->anchor ? '#'.$this->anchor : '') . "\" " . str_replace($this->page_replace, $page_number, $this->event) .  ">$label</a>";
		}
		return $output;
	}

}
?>
