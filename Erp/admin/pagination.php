<?php
/**
 * ECSHOP 分页模块
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
 * $Author: Zandy $
 * $Date: 2007-05-15 16:52:17 +0800 (星期二, 15 五月 2007) $
 * $Id$
 * @see : ecshop\admin\includes\inc_menu.php
 * @see : ecshop\languages\zh_cn\admin\common.php
 * @see : ecshop\languages\zh_cn\admin\priv_action.php
*/

	class Pagination {
	
		public $origin_sql;
		public $count_sql;
		public $limit_sql;
		public $page_number;	//第几页
		public $page_param = "page";
		public $total_count;	//总共的行数
		public $page_row = 10;	//一页中的行数
		public $page_count;		//总共的页面数
		public $result;			//查询结果集
		
		function __construct() {
			$this->page_number = (int)$_REQUEST[$this->page_param];
			
			if ($this->page_number <= 0) {
				$this->page_number = 1;
			}
			
		}
		
		function set_page_count($row) {
			if ($row == 0) {
				$row = 5;
			}
			$this->page_row = $row;
		}
		
		function set_sql($sql, $db) {
			$this->origin_sql = $sql;
	
			$from_index = strpos($this->origin_sql, "from ");
			if (!$from_index) {
				$from_index = strpos($this->origin_sql, "FROM ");
				
			}
			
			$this->count_sql = "select count(*) " . substr($this->origin_sql, $from_index);
			
			$this->total_count = $db->getOne($this->count_sql);
			
			$this->page_count = ceil($this->total_count / $this->page_row);
			
			if ($this->page_number > $this->page_count) {
				$this->page_number = $this->page_count;
			}
			
			if ($this->page_number == 0) {
				$this->page_number = 1;
			}
			
			$from = ($this->page_number - 1) * $this->page_row;	//数据库查询起始位置
			$this->limit_sql = $this->origin_sql . " limit $from, $this->page_row";
			
			$this->result = $db->query($this->limit_sql);
		}
	
		function get_request_url($page_param_value) {
			$query = $_SERVER['QUERY_STRING'];
			$index = strpos($query, $this->page_param);
			if ($index !== false) {
				if ($pos = strpos($query, "&")) {
					$query = "$this->page_param=$page_param_value" . substr($query, $pos);
				} else {
					$query = "$this->page_param=$page_param_value";
				}
			} else {
				$query = $query == "" ? "$this->page_param=$page_param_value" : "$this->page_param=$page_param_value&$query";
			}
			$self = $_SERVER['PHP_SELF'];
			return "$self?$query";
		}
		
		function get_next_page_url() {
			if ($this->page_number < 1) {
				return $this->get_request_url(1);
			} else {
				if ($this->page_number > $this->page_count) {
					return $this->get_request_url($this->page_count);
				}
			}
			return $this->get_request_url($this->page_number + 1);
		}
		
		function get_next_page($view="下一页") {
			if ($this->is_last_page()) {
				return $view;
			} else {
				return "<a href=\"". $this->get_next_page_url() ."\">$view</a>";
			}
		}
		
		function get_pre_page_url() {
			if ($this->page_number <= 1) {
				return $this->get_request_url(1);
			} else {
				if ($this->page_number > $this->page_count) {
					return $this->get_request_url($page_count);
				}
			}
			return $this->get_request_url($this->page_number - 1);
		}
		
		function get_pre_page($view="上一页") {
			if ($this->is_first_page()) {
				return $view;
			} else {
				return "<a href=\"". $this->get_pre_page_url() ."\">$view</a>";		
			}
		}
		
		function get_first_page_url() {
			return $this->get_request_url(1);
		}
		
		function get_first_page($view="首页") {
			if ($this->is_first_page()) {
				return $view;
			} else {
				return "<a href=\"". $this->get_first_page_url() ."\">$view</a>";		
			}
		}
		
		function get_last_page_url() {
			return $this->get_request_url($this->page_count);
		}
		
		function get_last_page($view="末页") {
			if ($this->is_last_page()) {
				return $view;
			} else {
				return "<a href=\"". $this->get_last_page_url() ."\">$view</a>";
			}
		}
		
		function is_first_page() {
			return $this->page_number <= 1;
		}
		
		function is_last_page() {
			return $this->page_number >= $this->page_count;
		}
		
		function get_forward_view($first_view="首页", $pre_view="上一页", $next_view="下一页", $last_view="末页") {
			$result = "";
			$result .= $this->get_first_page($first_view);
			$result .= " ";	
			$result .= $this->get_pre_page($pre_view);
			$result .= " ";			
			$result .= $this->get_next_page($next_view);
			$result .= " ";
			$result .= $this->get_last_page($last_view);
			return $result;
		}		
	}
?>