<?php

/**
 * ECSHOP 数据库导出类
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     wj                 <wjzhangq@126.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-17 11:44:15 +0800 (星期四, 17 五月 2007) $
 * $Id: cls_sql_dump.php 8651 2007-05-17 03:44:15Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class cls_sql_dump
{
    var $max_size  = 2097152; // 2M
    var $is_short  = false;
    var $offset    = 300;
    var $dump_sql  = '';
    var $sql_num   = 0;
    var $error_msg = '';

    var $db;

    /**
     *  类的构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function cls_sql_dump(&$db, $max_size=0)
    {
        $this->db = &$db;
        if ($max_size > 0 )
        {
            $this->max_size = $max_size;
        }

    }

    /**
     *  类的构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function __construct(&$db, $max_size =0)
    {
        $this->cls_sql_dump($db, $max_size);
    }

    /**
     *  获取指定表的定义
     *
     * @access  public
     * @param   string      $table      数据表名
     * @param   boolen      $add_drop   是否加入drop table
     *
     * @return  string      $sql
     */
    function get_table_df($table, $add_drop = false)
    {
        if ($add_drop)
        {
            $table_df = "DROP TABLE IF EXISTS `$table`;\r\n";
        }
        else
        {
            $table_df = '';
        }

        $tmp_arr = $this->db->getRow("SHOW CREATE TABLE `$table`");
        $tmp_sql = $tmp_arr['Create Table'];
        $tmp_sql = substr($tmp_sql, 0, strrpos($tmp_sql, ")") + 1); //去除行尾定义。

        if ($this->db->version() >= '4.1')
        {
            $table_df .= $tmp_sql . " ENGINE=MyISAM DEFAULT CHARSET=utf8;\r\n";
        }
        else
        {
            $table_df .= $tmp_sql . " TYPE=MyISAM;\r\n";
        }

        return $table_df;
    }

    /**
     *  获取指定表的数据定义
     *
     * @access  public
     * @param   string      $table      表名
     * @param   int         $pos        备份开始位置
     *
     * @return  int         $post_pos   记录位置
     */
    function get_table_data($table, $pos)
    {
        $post_pos = $pos;

        /* 获取数据表记录总数 */
        $total = $this->db->getOne("SELECT COUNT(*) FROM $table");

        if ($total == 0 || $pos >= $total)
        {
            /* 无须处理 */
            return -1;
        }

        /* 过滤字串函数 */
        $escape_string = (PHP_VERSION >= "4.3.0") ? 'mysql_real_escape_string' : 'mysql_escape_string';

        /* 确定循环次数 */
        $cycle_time = ceil(($total-$pos) / $this->offset); //每次取offset条数。需要取的次数

        /* 循环查数据表 */
        for($i = 0; $i<$cycle_time; $i++)
        {
            /* 获取数据库数据 */
            $data = $this->db->getAll("SELECT * FROM $table LIMIT " . ($this->offset * $i + $pos) . ', ' . $this->offset);
            $data_count = count($data);

            $fields = array_keys($data[0]);
            $start_sql = "INSERT INTO `$table` ( `" . implode("`, `", $fields) . "` ) VALUES ";

            /* 循环将数据写入 */
            for($j=0; $j< $data_count; $j++)
            {
                $record = array_map($escape_string, $data[$j]);//过滤非法字符

                /* 检查是否能写入，能则写入 */
                if ($this->is_short)
                {
                    if ($post_pos == $total-1)
                    {
                        $tmp_dump_sql = " ( '" . implode("', '" , $record) . "' );\r\n";
                    }
                    else
                    {
                        $tmp_dump_sql = " ( '" . implode("', '" , $record) . "' ),\r\n";
                    }

                    if ($post_pos == $pos)
                    {
                        /* 第一次插入数据 */
                        $tmp_dump_sql = $start_sql . "\r\n" . $tmp_dump_sql;
                    }
                }
                else
                {
                    $tmp_dump_sql = $start_sql . " ('" . implode("', '" , $record) . "');\r\n";
                }

                if (strlen($this->dump_sql) + strlen($tmp_dump_sql) > $this->max_size - 32)
                {
                    if ($this->sql_num == 0)
                    {
                        $this->dump_sql .= $tmp_dump_sql; //当是第一条记录时强制写入
                        $this->sql_num++;
                        $post_pos++;
                        if ($post_pos == $total)
                        {
                            /* 所有数据已经写完 */
                            return -1;
                        }
                    }

                    return $post_pos;
                }
                else
                {
                    $this->dump_sql .= $tmp_dump_sql;
                    $this->sql_num++; //记录sql条数
                    $post_pos++;
                }
            }
        }

        /* 所有数据已经写完 */
        return -1;
    }

    /**
     *  备份一个数据表
     *
     * @access  public
     * @param   string      $path       保存路径表名的文件
     * @param   int         $vol        卷标
     *
     * @return  array       $tables     未备份完的表列表
     */
    function dump_table($path, $vol)
    {
        $tables = $this->get_tables_list($path);

        if ($tables === false)
        {
            return false;
        }

        if (empty($tables))
        {
            return $tables;
        }

        $this->dump_sql = $this->make_head($vol);

        foreach($tables as $table => $pos)
        {

            if ($pos == -1)
            {
                /* 获取表定义，如果没有超过限制则保存 */
                $table_df = $this->get_table_df($table, true);
                if (strlen($this->dump_sql) + strlen($table_df) > $this->max_size - 32)
                {
                    if ($this->sql_num == 0)
                    {
                        /* 第一条记录，强制写入 */
                        $this->dump_sql .= $table_df;
                        $this->sql_num +=2;
                        $tables[$table] = 0;
                    }
                    /* 已经达到上限 */

                    break;
                }
                else
                {
                    $this->dump_sql .= $table_df;
                    $this->sql_num +=2;
                    $pos = 0;
                }
            }

            /* 尽可能多获取数据表数据 */
            $post_pos = $this->get_table_data($table, $pos);

            if ($post_pos == -1)
            {
                /* 该表已经完成，清除该表 */
                unset($tables[$table]);
            }
            else
            {
                /* 该表未完成。说明将要到达上限,记录备份数据位置 */
                $tables[$table] = $post_pos;
                break;
            }
        }

        $this->dump_sql .= '-- END ecshop v2.x SQL Dump Program ';
        $this->put_tables_list($path, $tables);

        return $tables;
    }

    /**
     *  生成备份文件头部
     *
     * @access  public
     * @param   int     文件卷数
     *
     * @return  string  $str    备份文件头部
     */
    function make_head($vol)
    {
        /* 系统信息 */
        $sys_info['os']         = PHP_OS;
        $sys_info['web_server'] = $GLOBALS['ecs']->get_domain();
        $sys_info['php_ver']    = PHP_VERSION;
        $sys_info['mysql_ver']  = $this->db->version();
        $sys_info['date']       = date('Y-m-d H:i:s');

        $head = "-- ecshop v2.x SQL Dump Program\r\n".
                 "-- " . $sys_info['web_server'] . "\r\n".
                 "-- \r\n".
                 "-- DATE : ".$sys_info["date"]."\r\n".
                 "-- MYSQL SERVER VERSION : ".$sys_info['mysql_ver']."\r\n".
                 "-- PHP VERSION : ".$sys_info['php_ver']."\r\n".
                 "-- ECShop VERSION : ".VERSION."\r\n".
                 "-- Vol : " . $vol . "\r\n";

        return $head;
    }

    /**
     *  获取备份文件信息
     *
     * @access  public
     * @param   string      $path       备份文件路径
     *
     * @return  array       $arr        信息数组
     */
    function get_head($path)
    {
        /* 获取sql文件头部信息 */
        $sql_info = array('date'=>'', 'mysql_ver'=> '', 'php_ver'=>0, 'ecs_ver'=>'', 'vol'=>0);
        $fp = fopen($path,'rb');
        $str = fread($fp, 250);
        fclose($fp);
        $arr = explode("\n", $str);

        foreach ($arr AS $val)
        {
            $pos = strpos($val, ':');
            if ($pos > 0)
            {
                $type = trim(substr($val, 0, $pos), "-\n\r\t ");
                $value = trim(substr($val, $pos+1), "/\n\r\t ");
                if ($type == 'DATE')
                {
                    $sql_info['date'] = $value;
                }
                elseif ($type == 'MYSQL SERVER VERSION')
                {
                    $sql_info['mysql_ver'] = $value;
                }
                elseif ($type == 'PHP VERSION')
                {
                    $sql_info['php_ver'] = $value;
                }
                elseif ($type == 'ECShop VERSION')
                {
                    $sql_info['ecs_ver'] = $value;
                }
                elseif ($type == 'Vol')
                {
                    $sql_info['vol'] = $value;
                }
            }
        }

        return $sql_info;
    }

    /**
     *  将文件中数据表列表取出
     *
     * @access  public
     * @param   string      $path    文件路径
     *
     * @return  array       $arr    数据表列表
     */
    function get_tables_list($path)
    {
        if (!file_exists($path))
        {
            $this->error_msg = $path . ' is not exists';

            return false;
        }

        $arr = array();
        $str = @file_get_contents($path);

        if (!empty($str))
        {
            $tmp_arr = explode("\n", $str);
            foreach ($tmp_arr as $val)
            {
                $val = trim ($val, "\r;");
                if (!empty($val))
                {
                    list($table, $count) = explode(':',$val);
                    $arr[$table] = $count;
                }
            }
        }

        return $arr;
    }

    /**
     *  将数据表数组写入指定文件
     *
     * @access  public
     * @param   string      $path    文件路径
     * @param   array       $arr    要写入的数据
     *
     * @return  boolen
     */
    function put_tables_list($path, $arr)
    {
        if (is_array($arr))
        {
            $str = '';
            foreach($arr as $key => $val)
            {
                $str .= $key . ':' . $val . ";\r\n";
            }

            if (@file_put_contents($path, $str))
            {
                return true;
            }
            else
            {
                $this->error_msg = 'Can not write ' . $path;

                return false;
            }
        }
        else
        {
            $this->error_msg = 'It need a array';

            return false;
        }
    }

    /**
     *  返回一个随机的名字
     *
     * @access  public
     * @param
     *
     * @return      string      $str    随机名称
     */
    function get_random_name()
    {
        $str = '';

        for ($i = 0; $i < 6; $i++)
        {
            $str .= chr(rand(97, 122));
        }

        $str .= date('Ymd');

        return $str;
    }

    /**
     *  返回错误信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function errorMsg()
    {
        return $this->error_msg;
    }
}

?>