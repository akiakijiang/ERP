<?php
/**
 * @author      Ma Bingyao(andot@ujn.edu.cn)
 * @copyright   CoolCode.CN
 * @package     PHP_PHPRPC_SERVER
 * @version     2.1
 * @last_update 2006-08-10
 * @link        http://www.coolcode.cn/?p=143
 *
 * Example usage:
 *
 * server.php
 * <?php
 * include('phprpc_server.php');
 * function add($a, $b) {
 *     return $a + $b;
 * }
 * function sub($a, $b) {
 *     return $a - $b;
 * }
 * new phprpc_server(array('add', 'sub'));
 * ?>
 */

if (!function_exists("ob_get_clean")) {
    function ob_get_clean() {
        $ob_contents = ob_get_contents();
        while(ob_get_length() !== false) @ob_end_clean(); 
        return $ob_contents;
    }
}

@ob_start();

class phprpc_server {
    var $callback;
    var $encode;
    var $ref;
    var $encrypt;
    var $debug;
    var $errno;
    var $errstr;
    var $functions;
    function tolower(&$func, $keys) {
        $func = strtolower($func);
    }
    function addjsslashes($str, $flag = true) {
        if ($flag) {
            $str = addcslashes($str, "\0..\006\010..\012\014..\037\042\047\134\177..\377");
        }
        else {
            $str = addcslashes($str, "\0..\006\010..\012\014..\037\042\047\134");
        }
        return str_replace(array(chr(7), chr(11)), array('\007', '\013'), $str);
    }
    function error_handler($errno, $errstr, $errfile, $errline) {
        if ($this->debug) {
            $errstr .= "\nfile: $errfile\nline: $errline";
        }
        if (($errno == E_ERROR) or ($errno == E_CORE_ERROR) or
            ($errno == E_COMPILE_ERROR) or ($errno == E_USER_ERROR)) {
            $output = ob_get_clean();
            echo "phprpc_errno=\"$errno\";\r\n";
            if ($this->encode) {
                echo "phprpc_errstr=\"" . base64_encode($errstr) . "\";\r\n";
                echo "phprpc_output=\"" . base64_encode($output) . "\";\r\n";
            }
            else {
                echo "phprpc_errstr=\"" . $this->addjsslashes($errstr, false) . "\";\r\n";
                echo "phprpc_output=\"" . $this->addjsslashes($output, false) . "\";\r\n";
            }
            echo $this->callback;
            exit();
        }
        else {
            if (($errno == E_NOTICE) or ($errno == E_USER_NOTICE)) {
                if ($this->errno == 0) {
                    $this->errno = $errno;
                    $this->errstr = $errstr;
                }
            }
            else {
                if (($this->errno == 0) or
                    ($this->errno == E_NOTICE) or
                    ($this->errno == E_USER_NOTICE)) {
                    $this->errno = $errno;
                    $this->errstr = $errstr;
                }
            }
        }
        return true;
    }
    function call($function, &$args) {
        $arguments = array();
        for ($i = 0; $i < count($args); $i++) {
            $arguments[$i] = &$args[$i];
        }
        return call_user_func_array($function, $arguments);
    }
    function get_request($name) {
        $result = $_REQUEST[$name];
        if (get_magic_quotes_gpc()) {
            $result = stripslashes($result);
        }
        return $result;
    }
    function phprpc_server($functions = null, $debug = false, $obj = null, $charset = "utf-8") {
        $this->functions = array();
        if ($functions != null) {
            $this->add($functions, $obj);
            $this->start($debug, $charset);
        }
    }
    function add($functions, $obj = null) {
        if ($functions == null) return false;
        if (is_string($functions)) {
            $functions = array($functions);
        }
        $this->functions[] = array('functions' => $functions, 'obj' => $obj);
        return true;
    }
    function start($debug = false, $charset = "utf-8") {
        while(ob_get_length() !== false) @ob_end_clean();
        @ob_start();
        header("HTTP/1.1 200 OK");
        header("Connection: close");
        header("Content-Type: text/plain; charset=$charset");
        header("X-Powered-By: PHPRPC Server/2.1");
        header("Date: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Encoding: none');        
        $this->debug = $debug;
        $this->encode = true;
        if (isset($_REQUEST['phprpc_encode'])) {
            $this->encode = strtolower($this->get_request('phprpc_encode'));
            if ($this->encode == "false") {
                $this->encode = false;
            }
        }
        if (isset($_REQUEST['phprpc_callback'])) {
            $this->callback = base64_decode($this->get_request('phprpc_callback'));
        }
        else {
            $this->callback = "";
        }
        $this->ref = true;
        if (isset($_REQUEST['phprpc_ref'])) {
            $this->ref = strtolower($this->get_request('phprpc_ref'));
            if ($this->ref == "false") {
                $this->ref = false;
            }
        }
        $this->errno = 0;
        $this->errstr = "";
        error_reporting(0);
        set_error_handler(array(&$this, 'error_handler'));

        $this->encrypt = false;
        if (isset($_REQUEST['phprpc_encrypt'])) {
            $this->encrypt = $this->get_request('phprpc_encrypt');
            if ($this->encrypt === "true") $this->encrypt = true;
            if ($this->encrypt === "false") $this->encrypt = false;
            if ($this->encrypt != false) {
                require_once('keypair.php');
                require_once('xxtea.php');
                session_start();
            }
        }

        $functions = array();
        $obj = array();
        foreach ($this->functions as $value) {
            foreach ($value['functions'] as $function) {
                $functions[] = $function;
                $objects[] = $value['obj'];
            }
        }
        if (isset($_REQUEST['phprpc_func'])) {
            array_walk($functions, array(&$this, 'tolower'));
            $function = strtolower($this->get_request('phprpc_func'));
            if (in_array($function, $functions)) {
                if (isset($_REQUEST['phprpc_args'])) {
                    $arguments = base64_decode($this->get_request('phprpc_args'));
                    if ($this->encrypt > 0) {
                        if (isset($_SESSION['PHPRPC_ENCRYPT']['k'])) {
                            $arguments = xxtea_decrypt($arguments, $_SESSION['PHPRPC_ENCRYPT']['k']);
                        }
                        else {
                            $this->errno = E_ERROR;
                            $this->errstr = "Can't find the key for decryption.";
                        }
                    }
                    $arguments = unserialize($arguments);
                }
                else {
                    $arguments = array();
                }
                $obj = $objects[array_search($function, $functions)];
                if ($obj != null && $obj != "") {
                    if (is_object($obj)) {
                        $function = array(&$obj, $function);
                    }
                    else if (is_string($obj)) {
                        $function = array($obj, $function);
                    }
                }
                if ($this->ref) {
                    $result = serialize($this->call($function, $arguments));
                    $arguments = serialize($arguments);
                }
                else {
                    $result = serialize(call_user_func_array($function, $arguments));
                }
                if ($this->encrypt > 0) {
                    if (isset($_SESSION['PHPRPC_ENCRYPT']['k'])) {
                        if ($this->encrypt > 1) {
                            $result = xxtea_encrypt($result, $_SESSION['PHPRPC_ENCRYPT']['k']);
                        }
                        if ($this->ref) {
                            $arguments = xxtea_encrypt($arguments, $_SESSION['PHPRPC_ENCRYPT']['k']);
                        }
                    }
                    else {
                        $this->errno = E_ERROR;
                        $this->errstr = "Can't find the key for encryption.";
                    }
                }
                if ($this->encode) {
                    $result = base64_encode($result);
                    if ($this->ref) {
                        $arguments = base64_encode($arguments);
                    }
                }
                else {
                    $result = $this->addjsslashes($result);
                    if ($this->ref) {
                        $arguments = $this->addjsslashes($arguments);
                    }
                }
            }
            else {
                $this->errno = E_ERROR;
                $this->errstr = "Can't find this function $function().";
            }
            $output = ob_get_clean();
            if ($this->errno != E_ERROR) {
                echo "phprpc_result=\"$result\";\r\n";
                if ($this->ref) {
                    echo "phprpc_args=\"$arguments\";\r\n";
                }
            }
            echo "phprpc_errno=\"{$this->errno}\";\r\n";
            if ($this->encode) {
                echo "phprpc_errstr=\"" . base64_encode($this->errstr) . "\";\r\n";
                echo "phprpc_output=\"" . base64_encode($output) . "\";\r\n";
            }
            else {
                echo "phprpc_errstr=\"" . $this->addjsslashes($this->errstr, false) ."\";\r\n";
                echo "phprpc_output=\"" . $this->addjsslashes($output, false) . "\";\r\n";
            }
        }
        else {
            if ($this->encrypt != false) { 
                require_once('bcmath.php');
                if (extension_loaded('big_int')) {
                    if ($this->encrypt === true) {
                        $encrypt = $phprpc_keypair[mt_rand(0, count($phprpc_keypair) - 1)];
                        $_SESSION['PHPRPC_ENCRYPT'] = $encrypt;
                        $_SESSION['PHPRPC_ENCRYPT']['x'] = bi_to_str(bi_set_bit(bi_rand(127), 126));
                        $encrypt['y'] = bi_to_str(bi_powmod(bi_from_str($_SESSION['PHPRPC_ENCRYPT']['g']),
                                                            bi_from_str($_SESSION['PHPRPC_ENCRYPT']['x']),
                                                            bi_from_str($_SESSION['PHPRPC_ENCRYPT']['p'])));
                    }
                    else {
                        $_SESSION['PHPRPC_ENCRYPT']['y'] = $this->encrypt;
                        $key = bcdec2str(bi_to_str(bi_powmod(bi_from_str($_SESSION['PHPRPC_ENCRYPT']['y']),
                                                             bi_from_str($_SESSION['PHPRPC_ENCRYPT']['x']),
                                                             bi_from_str($_SESSION['PHPRPC_ENCRYPT']['p']))));
                        $_SESSION['PHPRPC_ENCRYPT']['k'] = str_repeat("\0", 16 - strlen($key)) . $key;
                        $encrypt = true;
                    }
                }
                else {
                    if ($this->encrypt === true) {
                        $encrypt = $phprpc_keypair[mt_rand(0, count($phprpc_keypair) - 1)];
                        $_SESSION['PHPRPC_ENCRYPT'] = $encrypt;
                        $_SESSION['PHPRPC_ENCRYPT']['x'] = bcrand(127, 1);
                        $encrypt['y'] = bcpowmod($_SESSION['PHPRPC_ENCRYPT']['g'],
                                                 $_SESSION['PHPRPC_ENCRYPT']['x'],
                                                 $_SESSION['PHPRPC_ENCRYPT']['p']);
                    }
                    else {
                        $_SESSION['PHPRPC_ENCRYPT']['y'] = $this->encrypt;
                        $key = bcdec2str(bcpowmod($_SESSION['PHPRPC_ENCRYPT']['y'],
                                                  $_SESSION['PHPRPC_ENCRYPT']['x'],
                                                  $_SESSION['PHPRPC_ENCRYPT']['p']));
                        $_SESSION['PHPRPC_ENCRYPT']['k'] = str_repeat("\0", 16 - strlen($key)) . $key;
                        $encrypt = true;
                    }
                }
                if ($this->encode) {
                    echo "phprpc_encrypt=\"" . base64_encode(serialize($encrypt)) . "\";\r\n";
                }
                else {
                    echo "phprpc_encrypt=\"" . $this->addjsslashes(serialize($encrypt)) . "\";\r\n";
                }
            }
            if ($this->encode) {
                echo "phprpc_functions=\"" . base64_encode(serialize($functions)) . "\";\r\n";
            }
            else {
                echo "phprpc_functions=\"" . $this->addjsslashes(serialize($functions)) . "\";\r\n";
            }
        }
        echo $this->callback;
        restore_error_handler();
        ob_end_flush();
    }
}
?>
