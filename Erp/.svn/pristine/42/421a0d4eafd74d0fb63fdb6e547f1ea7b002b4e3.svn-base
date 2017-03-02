<?php

/**
 * ECSHOP mssql 公用类库
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
 * $Author: paulgao $
 * $Date: 2007-06-22 12:07:22 +0800 (星期五, 22 六月 2007) $
 * $Id: cls_mssql.php 9404 2007-06-22 04:07:22Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class cls_mssql
{
    var $link_id    = NULL;

    var $queryCount = 0;
    var $queryTime  = '';
    var $queryLog   = array();

    var $max_cache_time = 300; // 最大的缓存时间，以秒为单位

    var $cache_data_dir = 'templates/caches/';
    var $root_path      = '';

    var $error_message  = array();
    var $platform       = '';
    var $version        = '';
    var $dbhash         = '';
    var $starttime      = 0;
    var $timeline       = 0;
    var $timezone       = 0;

    var $mssql_config_cache_file_time = 0;

    var $mssql_disable_cache_tables = array(); // 不允许被缓存的表，遇到将不会进行缓存

    function __construct($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0)
    {
        $this->cls_mssql($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect);
    }

    function cls_mssql($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0)
    {
        if (defined('ROOT_PATH') && !$this->root_path)
        {
            $this->root_path = ROOT_PATH;
        }

        if ($pconnect)
        {
            if (!($this->link_id = @mssql_pconnect($dbhost, $dbuser, $dbpw)))
            {
                $this->ErrorMsg("Can't pConnect mssql Server($dbhost)!");

                return false;
            }
        }
        else
        {
            if (PHP_VERSION >= '4.2')
            {
                $this->link_id = @mssql_connect($dbhost.',1433', $dbuser, $dbpw, true);
            }
            else
            {
                $this->link_id = @mssql_connect($dbhost, $dbuser, $dbpw);

                mt_srand((double)microtime() * 1000000); // 随机数函数初始化
            }
            if (!$this->link_id)
            {
                $this->ErrorMsg("Can't Connect mssql Server($dbhost)!");
//                $this->ErrorMsg("Can't Connect Database Server !"); // Zandy 2007-11-27

                return false;
            }
        }

        $this->dbhash  = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
//        $this->version = mssql_get_server_info($this->link_id);

        //如果mssql 版本是 4.1+ 以上，需要对字符集进行初始化
        if ($this->version > '4.1')
        {
            if ($charset != 'latin1')
            {
                mssql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
            }
            if ($this->version > '5.0.1')
            {
                mssql_query("SET sql_mode=''", $this->link_id);
            }
        }

        $sqlcache_config_file = $this->root_path . $this->cache_data_dir . 'sqlcache_config_file_' . $this->dbhash . '.php';

        @include($sqlcache_config_file);

        $this->starttime = time();

        /* 选择数据库 */
        if ($dbname)
        {
            if (mssql_select_db($dbname, $this->link_id) === false )
            {
                $this->ErrorMsg("Can't select mssql database($dbname)!");

                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }
    }

    function select_database($dbname)
    {
        return mssql_select_db($dbname, $this->link_id);
    }

    function set_mssql_charset($charset)
    {
        /* 如果mssql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        if ($this->version > '4.1')
        {
            if (in_array(strtolower($charset), array('gbk', 'big5', 'utf-8', 'utf8')))
            {
                $charset = str_replace('-', '', $charset);
            }
            if ($charset != 'latin1')
            {
                mssql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
            }
        }
    }

    function fetch_array($query, $result_type = mssql_ASSOC)
    {
        return mssql_fetch_array($query, $result_type);
    }

    function query($sql, $type = '')
    {
        $this->queryCount++;
        $this->queryLog[] = $sql;
        if ($this->queryTime == '')
        {
            $this->queryTime = microtime();
        }

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (PHP_VERSION >= '4.3' && time() > $this->starttime + 1)
        {
            mssql_ping($this->link_id);
        }

        if (!($query = mssql_query($sql, $this->link_id)) && $type != 'SILENT')
        {
            $this->error_message[]['message'] = 'mssql Query Error';
            $this->error_message[]['sql'] = $sql;
            $this->error_message[]['error'] = mssql_error($this->link_id);
            $this->error_message[]['errno'] = mssql_errno($this->link_id);

            $this->ErrorMsg();

            return false;
        }

        if (defined('DEBUG_MODE') && (DEBUG_MODE & 8) == 8)
        {
            $logfilename = $this->root_path . 'data/mssql_query_' . $this->dbhash . '_' . date('Y_m_d') . '.log';
            $str = $sql . "\n\n";

            if (PHP_VERSION >= '5.0')
            {
                file_put_contents($logfilename, $str, FILE_APPEND);
            }
            else
            {
                $fp = @fopen($logfilename, 'ab+');
                if ($fp)
                {
                    fwrite($fp, $str);
                    fclose($fp);
                }
            }
        }

        return $query;
    }

    function affected_rows()
    {
        return mssql_rows_affected($this->link_id);
    }

    function error()
    {
        return mssql_error($this->link_id);
    }

    function errno()
    {
        return mssql_errno($this->link_id);
    }

    function result($query, $row)
    {
        return @mssql_result($query, $row);
    }

    function num_rows($query)
    {
        return mssql_num_rows($query);
    }

    function num_fields($query)
    {
        return mssql_num_fields($query);
    }

    function free_result($query)
    {
        return mssql_free_result($query);
    }

    function insert_id()
    {
        return mssql_insert_id($this->link_id);
    }

    function fetchRow($query)
    {
        return mssql_fetch_assoc($query);
    }

    function fetch_object($query)
    {
        return mssql_fetch_object($query);
    }

    function fetch_fields($query)
    {
        return mssql_fetch_field($query);
    }

    function version()
    {
        return $this->version;
    }

    function ping()
    {
        if (PHP_VERSION >= '4.3')
        {
            return mssql_ping($this->link_id);
        }
        else
        {
            return false;
        }
    }

    function escape_string($unescaped_string)
    {
        if (PHP_VERSION >= '4.3')
        {
            return mssql_real_escape_string($unescaped_string);
        }
        else
        {
            return mssql_escape_string($unescaped_string);
        }
    }

    function close()
    {
        return mssql_close($this->link_id);
    }

    function ErrorMsg($message = '', $sql = '')
    {
        if ($message)
        {
            echo "<b>OUKU info</b>: $message\n\n";
        }
        else
        {
            echo "<b>mssql server error report:";
            print_r($this->error_message);
			#pp(debug_backtrace());
        }

        exit;
    }

/* 仿真 Adodb 函数 */
    function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0)
        {
            $sql .= ' LIMIT ' . $num;
        }
        else
        {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    function getOne($sql, $limited = false)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $row = mssql_fetch_row($res);

            return $row[0];
        }
        else
        {
            return false;
        }
    }

    function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'mssqlFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst){
            return $this->getOne($sql, true);
        }else{
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true){
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mssql_fetch_assoc($res))
            {
                $arr[] = $row;
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'mssqlFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getAll($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getRow($sql, $limited = false)
    {
        if ($limited == true)
        {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false)
        {
            return mssql_fetch_assoc($res);
        }
        else
        {
            return false;
        }
    }

    function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'mssqlFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getRow($sql, true);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mssql_fetch_row($res))
            {
                $arr[] = $row[0];
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'mssqlFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getCol($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT')
        {
            $fields = $values = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
               // echo($sql);
            }
        }
        else
        {
            $sets = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets))
            {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs AS $value)
        {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI')
            {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names AS $value)
        {
            if (array_key_exists($value, $field_values) == true)
            {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values AS $key => $value)
        {
            if (array_key_exists($key, $field_values) == true)
            {
                if (is_numeric($value))
                {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                }
                else
                {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys))
        {
            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        }
        else
        {
            if ($this->version() >= '4.1')
            {
                if (!empty($fields))
                {
                    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                    if (!empty($sets))
                    {
                        $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                    }
                }
            }
            else
            {
                if (empty($where))
                {
                    $where = array();
                    foreach ($primary_keys AS $value)
                    {
                        if (is_numeric($value))
                        {
                            $where[] = $value . ' = ' . $field_values[$value];
                        }
                        else
                        {
                            $where[] = $value . " = '" . $field_values[$value] . "'";
                        }
                    }
                    $where = implode(' AND ', $where);
                }

                if ($where && (!empty($sets) || !empty($fields)))
                {
                    if (intval($this->getOne("SELECT COUNT(*) FROM $table WHERE $where")) > 0)
                    {
                        if (!empty($sets))
                        {
                            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
                        }
                    }
                    else
                    {
                        if (!empty($fields))
                        {
                            $sql = 'REPLACE INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                        }
                    }
                }
            }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    function getSqlCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->root_path . $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $data = @file_get_contents($result['filename']);
        if (isset($data{23}))
        {
            $filetime = substr($data, 13, 10);
            $data     = substr($data, 23);

            if (($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) || ($cached == 'mssqlFIRST' && $this->table_lastupdate($this->get_table_name($sql)) > $filetime))
            {
                $result['storecache'] = true;
            }
            else
            {
                $result['data'] = @unserialize($data);
                if ($result['data'] === false)
                {
                    $result['storecache'] = true;
                }
                else
                {
                    $result['storecache'] = false;
                }
            }
        }
        else
        {
            $result['storecache'] = true;
        }

        return $result;
    }

    function setSqlCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename'])
        {
            @file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));
            clearstatcache();
        }
    }

    /* 获取 SQL 语句中最后更新的表的时间，有多个表的情况下，返回最新的表的时间 */
    function table_lastupdate($tables)
    {
        $lastupdatetime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->mssql_disable_cache_tables = str_replace('`', '', $this->mssql_disable_cache_tables);

        foreach ($tables AS $table)
        {
            if (in_array($table, $this->mssql_disable_cache_tables) == true)
            {
                $lastupdatetime = '2037-12-31 23:59:59';

                break;
            }

            if (strstr($table, '.') != NULL)
            {
                $tmp = explode('.', $table);
                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . "` LIKE '" . trim($tmp[1]) . "'";
            }
            else
            {
                $sql = "SHOW TABLE STATUS LIKE '" . trim($table) . "'";
            }
            $result = mssql_query($sql, $this->link_id);

            $row = mssql_fetch_assoc($result);
            if ($row['Update_time'] > $lastupdatetime)
            {
                $lastupdatetime = $row['Update_time'];
            }
        }
        $lastupdatetime = strtotime($lastupdatetime) - $this->timezone + $this->timeline;

        return $lastupdatetime;
    }

    function get_table_name($query_item)
    {
        $query_item = trim($query_item);
        $table_names = array();

        /* 判断语句中是不是含有JOIN */
        if (stristr($query_item, ' JOIN ') == '')
        {
            /* 解析一般的SELECT FROM语句 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is', $query_item, $table_names))
            {
                $table_names = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $table_names[1]);

                return preg_split('/\s*,\s*/', $table_names);
            }
        }
        else
        {
            /* 对含有JOIN的语句进行解析 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is', $query_item, $table_names))
            {
                $other_table_names = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $query_item, $other_table_names);

                return array_merge(array($table_names[1]), $other_table_names[1]);
            }
        }

        return $table_names;
    }

    /* 设置不允许进行缓存的表 */
    function set_disable_cache_tables($tables)
    {
        if (!is_array($tables))
        {
            $tables = explode(',', $tables);
        }

        foreach ($tables AS $table)
        {
            $this->mssql_disable_cache_tables[] = $table;
        }

        array_unique($this->mssql_disable_cache_tables);
    }
}

?>