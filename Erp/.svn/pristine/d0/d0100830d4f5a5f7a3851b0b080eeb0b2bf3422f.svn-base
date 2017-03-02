<?php
/**
 * 分页类
 * $Author: ychen 
*/

class Pagination 
{

	var $current_page;			// 当前第几页
	var $page_param;			// 传递页数的参数
	var $total_count;			// 总共的行数
	var $page_size;				// 一页中的行数
	var $url;					// 链接页面，#page#代表页数	
	var $exclude_params;		// 去除的参数
	var $extra_params;			// 添加的参数
	
	var $first_page_number = 2;		// 开头显示几个页码
	var $last_page_number = 2;		// 结尾显示几个页码
	var $page_offset = 2;		// 当前为基准往前显示和往后显示的页码数
	
	var $prev_page_label = "上一页";
	var $next_page_label = "下一页";
	var $event = '';
	
	
	var $page_count;		// 总共的页面数
	
	
	var $page_replace = "#page#";
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
	function __construct($total_count, $page_size = 5, $current_page = -1, $page_param = 'page', $url = '', $exclude_params = null, $extra_params = null, $anchor = null) {
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
		
		$this->page_count = intval(ceil(floatval($total_count) / $page_size));
		
		$this->calculate_url();
	}
	
	function calculate_url() {
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
	 * 添加url的参数，若原先url中已经存在参数，更新值
	 *
	 * @param string $url 目标url
	 * @param string $param_name 添加的参数名
	 * @param string $param_value 添加的参数值
	 * @param boolean $is_encode 添加变量时是否编码
	 * @return string 返回添加参数的后的url
	 */
	function add_param_in_url($url, $param_name, $param_value, $is_encode = true) {
		if ($is_encode) {
			$param_name = urlencode($param_name);
			$param_value = urlencode($param_value);
		}
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
	
	/**
	 * 移除url中的参数
	 *
	 * @param string $url 目标url
	 * @param string $param_name 移除的参数
	 * @return string 返回移除参数后的url
	 */
	function remove_param_in_url($url, $param_name) {
		$pos = strpos($url, $param_name);
		if ($pos === false) {
			return $url;
		}
		$pre_char = $url[$pos - 1];
		$next_and = strpos($url, '&', $pos);
		
		if ($next_and !== false) {
			$new_url = substr($url, 0, $pos) . sub($url, $next_and + 1, strlen($url));
		} else {
			$new_url = substr($url, 0, $pos - 1);
		}
		return $new_url;
	}
	
	/**
	 * 返回一套分页的html
	 *
	 * @return string 一套分页的html
	 */
	function get_simple_output() {
		if ($this->current_page > $this->page_count)
			return '';
		$output = "";
		$output  .='<nav class="fenye_change"><ul class="pagination">';
		// $output .= '<div class="page">';
		
		// 显示上一页
		if ($this->current_page == 1) {
			$output .= "<li><a>{$this->prev_page_label}</a></li>";
		} else {
			$output .= $this->get_one_page_html(max($this->current_page - 1, 1), $this->prev_page_label);
		}
		
		// 页数过少，全部显示
		if ($this->page_count <= $this->first_page_number + $this->last_page_number + $this->page_offset * 2 + 1) {
			for ($i = 1; $i <= $this->page_count; $i++) {
				$output .= $this->get_one_page_html($i);
			}
		} else {
			// 显示前半部分
			for ($i = 1; $i <= $this->first_page_number; $i++) {
				$output .= $this->get_one_page_html($i);
			}
			
			$start = $this->current_page - $this->page_offset;
			$end = $this->current_page + $this->page_offset;
			
			if ($start <= $this->first_page_number) {
				$start = $this->first_page_number + 1;
				$end = $this->first_page_number + $this->page_offset * 2 + 1;
			}
			if ($end > $this->page_count - $this->last_page_number) {
				$start = $this->page_count - $this->last_page_number - $this->page_offset * 2;
				$end = $this->page_count - $this->last_page_number;
			}
			
			if ($start > $this->first_page_number + 1) {
				$output .= "<li><span>...</span></li>";
			}
			
			for ($i = $start; $i <= $end; $i++) {
				$output .= $this->get_one_page_html($i);
			}
			
			if ($end < $this->page_count - $this->last_page_number) {
				$output .= "<li><span>...</span></li>";
			}
			
			// 显示后半部分
			for ($i = $this->page_count - $this->last_page_number + 1; $i <= $this->page_count; $i++) {
				$output .= $this->get_one_page_html($i);
			}
		}

		
		// 显示上一页
		if ($this->current_page == $this->page_count) {
			$output .= "<li><a>{$this->next_page_label}</a></li>";
		} else {
			$output .= $this->get_one_page_html($this->current_page + 1, $this->next_page_label);
		}
		$output .= "<li><span>共{$this->page_count}页，当前第{$this->current_page}页</span></li>";
		// $output .= '</div>';
		$output .= '</ul></nav>';
		return $output;
	}
	
	/**
	 * 显示一个页码的html代码
	 *
	 * @param int $page_number 显示页码
	 * @return string 返回的html代码
	 */
	function get_one_page_html($page_number, $label = '') {
		if ($label == '') {
			$label = $page_number;
		}
		$output = "";
		if ($page_number == $this->current_page) {
			$output .= "<li><a class=\"currentPage\">$label</a></li>";
		} else {
			$output .= "<li><a href=\"" . str_replace($this->page_replace, $page_number, $this->url) . ($this->anchor ? '#'.$this->anchor : '') . "\" " . str_replace($this->page_replace, $page_number, $this->event) .  ">$label</a></li>";
		}
		return $output;
	}
	
}
?>