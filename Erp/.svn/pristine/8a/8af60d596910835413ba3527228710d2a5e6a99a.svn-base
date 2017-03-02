<?php

/**
 * ECSHOP SESSION 公用类库
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Paul Gao <paulgao@yeah.net>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: weberliu $
 * $Date: 2007-06-26 10:35:03 +0800 (星期二, 26 六月 2007) $
 * $Id: cls_session.php 9436 2007-06-26 02:35:03Z weberliu $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class cls_session
{
    var $db            = NULL;
    var $session_table = '';
    var $session_name = '';
    
    var $SESSION_KEY_NAME = 'OKEY';

    var $max_life_time = 21600; // SESSION 过期时间 6小时

    var $session_id    = '';
    
    var $ip = '';

    function __construct(&$db, $session_table)
    {
        $this->cls_session($db, $session_table);
        $this->ip = real_ip();
    }

    function cls_session(&$db, $session_table)
    {
		if (isset($_COOKIE[$this->SESSION_KEY_NAME]) && strlen((string)$_COOKIE[$this->SESSION_KEY_NAME]) == 32)
		{
			$this->session_id = $_COOKIE[$this->SESSION_KEY_NAME];
		}
		else
		{
			$this->session_id = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].microtime(true).mt_rand());
			setcookie($this->SESSION_KEY_NAME, $this->session_id, 0, '/', COOKIE_DOMAIN);
		}
		
		define('SESS_ID', $this->session_id);
		$this->db = $db;
     	$this->session_table = $session_table;
        $this->load_session();
        register_shutdown_function(array(&$this, 'close_session'));
    }

    function insert_session($id)
    {
        return $this->db->query('INSERT INTO ' . $this->session_table . " (sesskey, expiry, ip, data) VALUES ('" . $id . "', '". time() ."', '". $this->ip ."', 'a:0:{}')");
    }

    function load_session()
    {
        global $_SESSION;

        $session = $this->db->getRow('SELECT data, expiry FROM ' . $this->session_table . " WHERE sesskey = '" . $this->session_id . "'");
        if (empty($session))
        {
            $this->insert_session($this->session_id);
            $_SESSION = array();
        }
        else
        {
            if (!empty($session['data']) && time() - $session['expiry'] <= $this->max_life_time)
            {
                $_SESSION = unserialize($session['data']);
            }
            else
            {
                $_SESSION = array();
            }
        }
    }

    function update_session()
    {
        global $_SESSION;
        $adminid = !empty($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
        $userid  = !empty($_SESSION['user_id'])  ? intval($_SESSION['user_id'])  : 0;
        return $this->db->query('UPDATE ' . $this->session_table . " SET expiry = '" . time() . "', ip = '" . $this->ip . "', userid = '" . $userid . "', adminid = '" . $adminid . "', data = '" . addslashes(serialize($_SESSION)) . "' WHERE sesskey = '" . $this->session_id . "' LIMIT 1");
    }

    function close_session()
    {
        $this->update_session();
        return $this->db->query('DELETE FROM ' . $this->session_table . ' WHERE expiry < ' . (time() - $this->max_life_time));
    }

    function delete_spec_admin_session($adminid)
    {
        if (!empty($_SESSION['admin_id']) && $adminid)
        {
            return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE adminid = '$adminid'");
        }
        else
        {
            return false;
        }
    }

    function destroy_session()
    {
        global $_SESSION;

        $_SESSION = array();

        //setcookie($this->session_name, $this->session_id, 1, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);

        /* 自定义执行部分 */
        //if (!empty($GLOBALS['ecs']))
        //{
        //    $this->db->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '$this->session_id'");
        //}
        /* 自定义执行部分 */

        return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE sesskey = '". $this->session_id ."' LIMIT 1");
    }

    function get_session_id()
    {
        return $this->session_id;
    }

    function get_users_count()
    {
        return $this->db->getOne('SELECT count(*) FROM ' . $this->session_table);
    }
}

?>