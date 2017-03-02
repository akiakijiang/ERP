<?php

// {{{ 发现这个文件并没有用到 Zandy 2007-11-19
#header("location: ".WEB_ROOT);
die('如果您看到了这些文字，请立即联系我们技术部。');
//实际使用的includes\modules\integrates\ecshop.php
// }}}

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
	$i = (isset($modules)) ? count($modules) : 0;

	/* 会员数据整合插件的代码必须和文件名保持一致 */
	$modules[$i]['code']    = 'ecshop';

	/* 被整合的第三方程序的名称 */
	$modules[$i]['name']    = '欧  酷';

	/* 被整合的第三方程序的版本 */
	$modules[$i]['version'] = '2.0';

	/* 插件的作者 */
	$modules[$i]['author']  = 'ECSHOP R&D TEAM';

	/* 插件作者的官方网站 */
	$modules[$i]['website'] = 'http://www.oukoo.com';

	return;
}



class ecshop
{
	/*------------------------------------------------------ */
	//-- PUBLIC ATTRIBUTEs
	/*------------------------------------------------------ */

	/* 整合对象使用的数据库主机 */
	var $db_host        = '192.168.1.116:3306';

	/* 整合对象使用的数据库名 */
	var $db_name        = 'ecshop';

	/* 整合对象使用的数据库用户名 */
	var $db_user        = 'tit';

	/* 整合对象使用的数据库密码 */
	var $db_pass        = 'omg';

	/* 整合对象数据表前缀 */
	var $prefix         = 'ecs_';

	/* 整合对象使用的cookie的domain */
	var $cookie_domain  = '';

	/* 整合对象使用的cookie的path */
	var $cookie_path    = '/';

	/* 会员ID的字段名 */
	var $field_id       = 'user_id';

	/* 会员名称的字段名 */
	var $field_name     = 'user_name';

	/* 会员密码的字段名 */
	var $field_pass     = 'password';

	/* 会员密码的字段名 */
	var $field_email    = 'email';

	/* 会员session_key */
	var $field_session_key    = 'userId';

	/* 注册日期的字段名 */
	var $field_reg_date = 'reg_time';

    var $field_track_id = 'track_id';

	var $error          = 0;

	/*------------------------------------------------------ */
	//-- PRIVATE ATTRIBUTEs
	/*------------------------------------------------------ */

	var $db;

	/*------------------------------------------------------ */
	//-- PUBLIC METHODs
	/*------------------------------------------------------ */

	/**
     * 会员数据整合插件类的构造函数
     *
     * @access      public
     * @param       string  $db_host    数据库主机
     * @param       string  $db_name    数据库名
     * @param       string  $db_user    数据库用户名
     * @param       string  $db_pass    数据库密码
     * @return      void
     */
	function ecshop($db_host = 'localhost', $db_name = '', $db_user = 'root', $db_pass = '', $prefix = '', $cookie_domain = '', $cookie_path = '/', $charset)
	{
		$this->db = &$GLOBALS['db'];

		/* 获得cookie的域名和路径 */
		$this->cookie_domain = $cookie_domain;
		$this->cookie_path   = $cookie_path;
	}

	/**
     * 根据用户名、密码验证用户身份的函数
     *
     * @param       string      username    用户名
     * @param       string      password    登录密码
     *
     * @return bool
     */
	function login($username, $password)
	{
		$sql = 'SELECT ' . $this->field_id . ', ' . $this->field_name . ', ' . $this->field_email .
		','. $this->field_session_key .' FROM ' . $GLOBALS['ecs']->table('users') .
		' WHERE ' . $this->field_name . " = '$username'";
		$row = $this->db->getRow($sql);
		
		if ($row)
		{
			/* SSO系统的用户登陆操作接口 */
			$arContext['UserInput']	=	$username;
			$arContext['Password']	=	$password;
			$arContext['TimeOut']	=	36000;
			$arContext['ClientIp']	=	incept_ip();

			$arError			=	$GLOBALS['SSO']->userControllerApp($arContext,'loginUser');
		
			if($arError['errorCode']){
				return false;
			}
			/* 登陆成功 */
			/* SSO记下COOKIES */
			$OKSID	=	$arError['reInfo']->userId;
			$SSOTIME	=	time()+(int)$arError['reInfo']->timeOut;
			setcookie('OKSID', $OKSID, $SSOTIME, '/', COOKIE_DOMAIN);
			
			$this->set_user_session($row['user_id'],$arError['reInfo']->userId, $row['user_name'],$arError['reInfo']->sessionKey, $arError['reInfo']->email);
		
			return true;
		}
		else
		{

			return false;
		}
	}

	/**
     * 根据cookie来验证用户身份的函数
     *
     * @access      public
     * @return      void
     */
	function get_cookie()
	{
		/*
		// ECSHOP 本身的会员系统不需要自动登录
		if (empty($_COOKIE['ECS']['user_id']))
		{
		return false;
		}

		$sql = 'SELECT ' . $this->field_id . ', ' . $this->field_name . ', ' . $this->field_email . '  FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE ' . $this->field_id . " = '" . $_COOKIE['ECS']['user_id'] . "' AND " . $this->field_pass . " = '" . $_COOKIE['ECS']['password'] . "'";
		$row = $this->db->GetRow($sql);

		if ($row)
		{
		// 登陆成功
		$this->set_user_session($row[$this->field_id], $row[$this->field_name], $row[$this->field_email]);

		return true;
		}
		else
		{
		return false;
		} */

		/* 不允许自动登陆 */
		return false;
	}

	/**
     * 用户退出
     *
     * @access      public
     * @return      void
     */
	function logout()
	{
		//session_start();
		//$time = time() - 3600;
		$OKSID	=	$_COOKIE['OKSID'];
	
		/* SSO系统的用户注册操作接口 */
		$arError			=	$GLOBALS['SSO']->userControllerApp($OKSID,'logoutUser');
	
		if($arError['errorCode']){
			return $arError['reString']	;
		}

		//setcookie('OKSID',  '', $time, '/', COOKIE_DOMAIN);
		//setcookie('ECS[user_id]',  '', $time, '/', COOKIE_DOMAIN);
		//setcookie('ECS[password]', '', $time, '/', COOKIE_DOMAIN);
		clearUserInfo();
		//echo('sessionKey:'.$sessionKey.'<br>');
		/* 清除session */
		//$GLOBALS['sess']->destroy_session();
	}

	/**
     * 添加新用户的函数
     *
     * @access      public
     * @param       string      username    用户名
     * @param       string      password    登录密码
     * @param       string      email       邮件地址
     * @return      int         返回最新的ID
     */
	function add_user($username, $password, $email)
	{
		/* SSO系统的用户注册操作接口 */
		$arContext['UserName']	=	$username;
		$arContext['Password']	=	$password;
		$arContext['Email']	=	$email;

		/* 检查用户名是否已经存在 */
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
		' WHERE ' . $this->field_name . " = '$username'";


		if ($this->db->getOne($sql) > 0)
		{
			$this->error = ERR_USERNAME_EXISTS;

			return '注册失败';
		}

		/* SSO系统的用户注册操作接口 */
		$arError			=	$GLOBALS['SSO']->userControllerApp($arContext,'verifyUserName');

		if($arError['errorCode']){
			return $arError['reString']	;
		}


		/* SSO系统的用户注册操作接口 */
		$arError			=	$GLOBALS['SSO']->userControllerApp($arContext,'verifyPassword');
		if($arError['errorCode']){
			return $arError['reString']	;
		}


		/* 检查邮件地址是否重复 */
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
		' WHERE ' . $this->field_email . " = '$email'";
		if ($this->db->getOne($sql) > 0)
		{
			$this->error = ERR_EMAIL_EXISTS;

			return '注册失败';
		}

		/* SSO系统的用户注册Email操作接口 */
		$arError			=	$GLOBALS['SSO']->userControllerApp($arContext,'verifyEmail');
		if($arError['errorCode']){
			return $arError['reString']	;
		}

		/* 编译密码 */
		$password = '';
		//$password = $GLOBALS['ecs']->compile_password($password);

		$arContext['CreatedIP']	=	incept_ip();

		$arError			=	$GLOBALS['SSO']->userControllerApp($arContext,'createUser');

		if($arError['errorCode']){
			return $arError['reString']	;
		}

        #用户来源
        $track_id =  (isset($_COOKIE['OKTID']) && strlen((string)$_COOKIE['OKTID']) == 32) ? $_COOKIE['OKTID']: '';
        $track_id = $this->db->escape_string($track_id);

		/* 插入数据库 */
		$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(' .
		$this->field_name . ', ' . $this->field_pass . ', ' . $this->field_email . ',' . $this->field_reg_date .
		' ,' . $this->field_session_key . ',' . $this->field_track_id . ') VALUES ( ' .
		"'$username', '$password', '$email', " . time() . ',"'.$arError['reInfo']->userId. '","' . $track_id . '")';
		//echo($sql);
		$res = $this->db->query($sql);

		if ($res)
		{
			$new_id = $this->db->Insert_ID();

			return $new_id;
		}
		else
		{
			$this->error = ERR_USERNAME_EXISTS;

			return '注册失败';
		}
	}

	/**
     * 编辑用户帐号信息的函数
     *
     * @access      public
     * @param       int         $user_id        用户编号
     * @param       string      $username       用户名
     * @param       string      $email          邮件地址
     * @return      bool
     */
	function edit_user($user_id, $username = '', $email = '')
	{
		$fields = '';
		if (!empty($username))
		{
			/* 检查用户名是否重复 */
			$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
			' WHERE ' . $this->field_name . " = '$username' AND " . $this->field_id . " <> '$user_id'";

			if ($this->db->getOne($sql))
			{
				$this->error = ERR_USERNAME_EXISTS;

				return false;
			}
			else
			{
				$fields .= $this->field_name . " = '$username'";
			}
		}

		if (!empty($email))
		{
			/* 检查邮件地址是否重复 */
			$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
			' WHERE ' . $this->field_email . " = '$email' AND " . $this->field_id . " <> '$user_id'";

			if ($this->db->getOne($sql))
			{
				$this->error = ERR_EMAIL_EXISTS;

				return false;
			}
			else
			{
				$fields .= (empty($fields)) ? '' : ',';
				$fields .= $this->field_email . " = '$email'";
			}
		}

		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET $fields WHERE " . $this->field_id . " = '$user_id'";
		$this->db->query($sql);

		return true;
	}

	/**
     * 删除用户帐号
     *
     * @access      public
     * @param       mix     users   用户编号
     * @return      bool
     */
	function remove_user($users)
	{
		$where = is_array($users) ? $this->field_id . db_create_in($users) : $this->field_id . " = '$users'";

		$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('users') . " WHERE $where";

		return $this->db->query($sql);
	}

	/**
     * 编辑用户密码的函数
     * 在这个函数里判断用户输入的原来的密码是否正确，如果不正确则返回FALSE
     *
     * @access      public
     * @param       int         user_id         用户编号
     * @param       string      password        原来的登录密码
     * @param       string      new_password    新的登录密码
     * @param       string      code            附加的
     * @return      void
     */
	function edit_password($user_id, $password, $new_password)
	{
		/* 比较原密码是否相符 */
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') .
		" WHERE user_id = '$user_id'" .
		" AND password = '" .$GLOBALS['ecs']->compile_password($password) . "'";
		if ($GLOBALS['db']->getOne($sql) == 0)
		{
			return false;
		}
		if (!$this->update_password($user_id, $new_password))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
     * 找回用户密码的函数
     *
     * @access      public
     * @param       int         user_id         用户编号
     * @param       string      code            验证串
     * @param       string      new_password    新的登录密码

     * @return      void
     */
	function fetch_password($user_id, $code, $new_password)
	{
		/* 比较code是否合法 */
		$sql = "SELECT password FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
		$password = $this->db->getOne($sql);
		$md5_password = md5($user_id . $password);

		if ($md5_password <> $code)
		{
			return false;
		}

		if (!$this->update_password($user_id, $new_password))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
     * 更新用户密码的函数
     *
     * @access      private
     * @param       int         user_id         用户编号
     * @param       string      new_password    新的登录密码
     * @return      void
     */
	function update_password($user_id, $new_password)
	{
		if (empty($user_id) || empty($new_password))
		{
			return false;
		}

		/* 更新密码 */
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET ' .
		"password = '" . $GLOBALS['ecs']->compile_password($new_password) . "' ".
		"WHERE user_id = '$user_id'";
		$result = $this->db->query($sql);
		if ($result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * 检验在找回密码输入的用户名和email是否匹配
     *
     * @access      public
     * @param       int         user_name        用户名
     * @param       string      email            email
     * @return      array
     */
	function check_pwd_info($user_name, $email)
	{
		$sql = 'SELECT user_id, password FROM ' .$GLOBALS['ecs']->table('users') .
		" WHERE user_name = '$user_name' AND email = '$email'";
		$res = $GLOBALS['db']->getRow($sql);
		if ($res)
		{
			$res[0] = $res['user_id'];
			$res[1] = $res['password'];

			return $res;
		}
		else
		{
			return false;
		}
	}
	/**
     * 检验从邮件地址链接过来的code是否合法
     *
     * @access      public
     * @param       int         user_id        用户ID
     * @param       string      code           code值
     * @return      array
     */
	function check_param($user_id, $code)
	{
		/* 比较code是否合法 */
		$sql = "SELECT password FROM " .$GLOBALS['ecs']->table('users').
		" WHERE user_id = '$user_id'";
		$password = $GLOBALS['db']->getOne($sql);

		$md5_password = md5($user_id . $password);
		if ($md5_password <> $code)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	/**
     * 获得用户总数
     *
     * @access      public
     * @param       string      查询的关键字
     * @return      int
     */
	function get_user_count($keyword = '', $ex_where = '')
	{
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE 1';

		if (!empty($keyword))
		{
			$sql .= ' AND ' . $this->field_name . " LIKE '%$keyword%'";
		}
		if (!empty($ex_where))
		{
			$sql .= ' AND ' . $this->field_id . $ex_where;
		}

		return $this->db->getOne($sql);
	}

	/**
     * 获得指定用户的帐号信息
     *
     * @access: public
     * @param:  int     $id     会员编号
     *
     * @return  array
     */
	function get_user_info($id)
	{
		$sql = 'SELECT sex , * ,' . $this->field_name . ', ' .$this->field_email .
		' FROM ' . $GLOBALS['ecs']->table('users') .
		' WHERE ' . $this->field_id . " = '$id'";
		$row = $this->db->GetRow($sql);
		 print_r($row);
		$arr['user_id']  = $id;
		$arr['username'] = $row ? htmlspecialchars($row[$this->field_name]) : '';
		$arr['email']    = $row ? htmlspecialchars($row[$this->field_email]) : '';
		$arr['question'] = '';
		$arr['answer']   = '';
		$arr['sex']  	 = $row['sex'];
		

		return $arr;
	}

	/**
     * 返回会员列表
     *
     * @access      public
     * @param       int     page      当前的页数
     * @param       int     limit     记录总数
     * @return      mysql result
     */
	function &get_users($page = 1, $limit = 15, $sort = 'uid', $order = 'DESC', $keywords = '', $ex_where = '')
	{
		$start = ($page - 1) * $limit;
		$where = ' WHERE 1 ';
		if (!empty($keywords))
		{
			$where .= ' AND ' . $this->field_name . " LIKE '%$keywords%' ";
		}
		if (!empty($ex_where))
		{
			$where .= ' AND ' . $this->field_id . $ex_where;
		}

		$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('users') . " $where " .
		"ORDER BY $sort $order, " . $this->field_reg_date . ' DESC';
		$res = $this->db->SelectLimit($sql, $limit, $start);

		$list = array();
		while ($row = $this->db->fetchRow($res))
		{
			$list[] = array($this->field_id => $row[$this->field_id], $this->field_name => $row[$this->field_name], $this->field_email => $row[$this->field_email], $this->field_reg_date => $row[$this->field_reg_date]);
		}
		
		return $list;
	}

	/**
     * 同步会员数据
     *
     * @access  public
     * @param   int     $target     同步的目标 0为ecshop, 1为整合对象自己
     * @param   int     $start      开始同步的位置
     * @param   int     $num        本次同步的记录数量
     * @return  int     返回同步了多少记录
     */
	function sync($target, $start = 0, $num = 1000)
	{
		return 0;
	}

	/**
     * 获得最后一个错误信息
     *
     * @access  public
     * @return  void
     */
	function error_msg()
	{
		return $this->db->ErrorMsg();
	}

	/*------------------------------------------------------ */
	//-- PRIVATE METHODs
	/*------------------------------------------------------ */

	/**
     * 设置整合对象的cookie
     *
     * @access      private
     * @param       int         user_id         用户编号
     * @param       string      user_password   登录密码
     * @param       string      salt            加密串
     * @return      void
     */
	function set_cookie($user_id, $time, $salt = '')
	{
		setcookie("OKSID", $user_id, time()+$time, '/', COOKIE_DOMAIN);
		/* ECSHOP 本身的会员系统不需要自动登录
		$time = time() + 3600 * 24 * 30;

		setcookie('ECS[user_id]', $user_id, $time, '/', COOKIE_DOMAIN);
		setcookie('ECS[password]',  $GLOBALS['ecs']->compile_password($user_password), $time, '/', COOKIE_DOMAIN);
		*/
		return;
	}

	/**
     * 设置用户session
     *
     * @access  public
     * @param
     *
     * @return void
     */
	function set_user_session($user_id,$userId, $user_name,$session_key, $email)
	{
		$_SESSION['user_id']   			= $user_id;
		$_SESSION['userId']   			= $userId;
		$_SESSION['user_name'] 			= $user_name;
		/* SSO 的 sessionKey */
		$_SESSION['session_key'] 		= $session_key;
		$_SESSION['email']     			= $email;
	}
}



/**
 * CheckIp
 *
 * @param String $user_ip= ""
 * @return String char 15
 */
function  incept_ip($user_ip="")
{
	$ip=false;
	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
		for ($i = 0; $i < count($ips); $i++) {
			if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
?>
