<?php
/**
 * @author      Ma Bingyao(andot@ujn.edu.cn)
 * @copyright   CoolCode.CN
 * @package     PHP_PHPRPC_CLIENT
 * @version     2.1
 * @last_update 2006-08-10
 * @link        http://www.coolcode.cn/?p=144
 *
 * Example usage:
 *
 * client.php
 * <?php
 * include('phprpc.php');
 * $rpc_client = new phprpc_client();
 * $rpc_client->use_service('http://test.coolcode.cn/phprpc/server.php', true);
 * $rpc_client->encrypt = 2;
 * echo $rpc_client->add(1, 2);
 * echo "<br />";
 * echo $rpc_client->Sub(1, 2); // the function name is case-insensitive
 * echo "<br />";
 * // error handle
 * echo "<pre>";
 * $result = $rpc_client->mul(1, 2);  // no mul function
 * if (get_class($result) == "phprpc_error") {
 *     print_r($result);
 * }
 * $result = $rpc_client->add(1);    // wrong arguments
 * if (get_class($result) == "phprpc_error") {
 *     print_r($result);
 * }
 * $rpc_client->use_service('wrong url');  // wrong url
 * $result = $rpc_client->add(1, 2);
 * if (get_class($result) == "phprpc_error") {
 *     print_r($result);
 * }
 * echo "</pre>";
 * ?>
 */

class phprpc_error {
    var $errno;
    var $errstr;
    function phprpc_error($errno, $errstr) {
        $this->errno = $errno;
        $this->errstr = $errstr;
    }
}

class __phprpc_client {
    var $scheme;
    var $host;
    var $port;
    var $path;
    var $user;
    var $pass;
    var $timeout;
    var $output;
    var $warning;
    var $proxy;
    var $__encrypt;
    var $encrypt;
    var $cookie;

    function __phprpc_client($url = '', $user = '', $pass = '', $timeout = 10) {
        if ($url != '') {
            $this->use_service($url);
        }
        $this->user = $user;
        $this->pass = $pass;
        $this->timeout = $timeout;
        $this->encrypt = 0;
        $this->cookie = '';
        $this->proxy = null;
    }
    function set_proxy($host, $port, $user = null, $pass = null) {
        $this->proxy = array();
        $this->proxy['host'] = $host;
        $this->proxy['port'] = $port;
        $this->proxy['user'] = $user;
        $this->proxy['pass'] = $pass;
    }
    function use_service($url, $encrypt = false) {
        $urlparts = parse_url($url);
        $this->__encrypt = $encrypt;
        if (!isset($urlparts['host'])) {
            if (isset($_SERVER["HTTP_HOST"])) {
                $urlparts['host'] = $_SERVER["HTTP_HOST"];
            }
            else if (isset($_SERVER["SERVER_NAME"])) {
                $urlparts['host'] = $_SERVER["SERVER_NAME"];
            }
            else {
                $urlparts['host'] = "localhost";
            }
            if (!isset($urlparts['scheme'])) {
                if (!isset($_SERVER["HTTPS"]) ||
                    $_SERVER["HTTPS"] == "off"  ||
                    $_SERVER["HTTPS"] == "") {
                    $urlparts['scheme'] = "";
                }
                else {
                    $urlparts['scheme'] = "https";
                }
            }
            if (!isset($urlparts['port'])) {
                $urlparts['port'] = $_SERVER["SERVER_PORT"];
            }
        }

        if (isset($urlparts['scheme']) && ($urlparts['scheme'] == "https")) {
            $urlparts['scheme'] = "ssl";
        }
        else {
            $urlparts['scheme'] = "";
        }

        if (!isset($urlparts['port'])) {
            if ($urlparts['scheme'] == "ssl") {
                $urlparts['port'] = 443;
            }
            else {
                $urlparts['port'] = 80;
            }
        }

        if (!isset($urlparts['path'])) {
            $urlparts['path'] = "/";
        }
        else if (($urlparts['path']{0} != '/') && ($_SERVER["PHP_SELF"]{0} == '/')) {
            $urlparts['path'] = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], '/') + 1) . $urlparts['path'];
        }

        $this->scheme = $urlparts['scheme'];
        $this->host = $urlparts['host'];
        $this->port = $urlparts['port'];
        $this->path = $urlparts['path'];
        if ($this->__encrypt) {
            return $this->__switch_key();
        }
    }

    function __post($request) {
        if ($this->proxy == null) {
            $host = ($this->scheme) ? $this->scheme . "://" . $this->host : $this->host;
            $handle = @fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
        }
        else {
            $handle = @fsockopen($this->proxy['host'], $this->proxy['port'], $errno, $errstr, $this->timeout);
        }
        if ($handle) {
            $proxy = '';
            if ($this->proxy) {
                $proxy = "Proxy-Connection: Keep-Alive\r\n";
                if ($this->proxy['user']) {
                    $proxy .= "Proxy-Authorization: Basic " . base64_encode($this->proxy['user'] . ":" . $this->proxy['pass']) . "\r\n";
                }
            }
            $auth = '';
            if ($this->user) {
                $auth = "Authorization: Basic " . base64_encode($this->user . ":" . $this->pass) . "\r\n";
            }
            $cookie = '';
            if ($this->cookie) {
                $cookie = "Cookie: " . $this->cookie . "\r\n";
            }
            $content_len = strlen($request);
            $url = (($this->scheme) ? "https://" : "http://") . $this->host . ":" . $this->port . $this->path;
            $http_request =
                "POST $url HTTP/1.0\r\n" .
                "User-Agent: PHPRPC Client/2.1\r\n" .
                "Host: $this->host:$this->port\r\n" .
                $proxy .
                $auth .
                $cookie .
                "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n" .
                "Content-Length: $content_len\r\n" .
                "\r\n" .
                $request;
            fputs($handle, $http_request, strlen($http_request));
            $buf = '';
            while (!feof($handle)) {
                $buf .= fgets($handle, 1024);
            }
            fclose($handle);
            $buf = explode("\r\n\r\n", $buf);
            return $buf;
        }
        else {
            $result = new phprpc_error($errno, $errstr);
        }
    }

    function __switch_key() {
        $errno = 0;
        $errstr = '';
        $result = true;
        $request = "phprpc_encrypt=true";
        $response = $this->__post($request);
        if (is_array($response)) {
            $header = $response[0];
            $body = $response[1];
            if (strpos($header, 'X-Powered-By: PHPRPC Server') !== FALSE) {
                if (preg_match('/\r\nSet\-Cookie\:(.*?)(\r\n|$)/', $header, $match)) {
                    $this->cookie = array();
                    $cookie = explode(";", $match[1]);
                    for ($i = 0; $i < count($cookie); $i++) {
                        $cookie[$i] = trim($cookie[$i]);
                        if ((substr($cookie[$i], 0, 5) != 'path=') and
                            (substr($cookie[$i], 0, 7) != 'domain=')) {
                            $this->cookie[] = $cookie[$i];
                        }
                    }
                    $this->cookie = join('; ', $this->cookie);
                }
                $body = explode("\r\n", trim($body));
                if (substr($body[0], 0, 14) == "phprpc_encrypt") {
                    require_once('bcmath.php');
                    require_once('xxtea.php');
                    $this->__encrypt = unserialize(base64_decode(substr($body[0], 16, strlen($body[0]) - 18)));
                    if (extension_loaded('big_int')) {
                        $this->__encrypt['x'] = bi_to_str(bi_set_bit(bi_rand(127), 126));
                        $key = bcdec2str(bi_to_str(bi_powmod(bi_from_str($this->__encrypt['y']),
                                                             bi_from_str($this->__encrypt['x']),
                                                             bi_from_str($this->__encrypt['p']))));
                        $this->__encrypt['k'] = str_repeat("\0", 16 - strlen($key)) . $key;
                        $encrypt = bi_to_str(bi_powmod(bi_from_str($this->__encrypt['g']),
                                                       bi_from_str($this->__encrypt['x']),
                                                       bi_from_str($this->__encrypt['p'])));
                    }
                    else {
                        $this->__encrypt['x'] = bcrand(127, 1);
                        $key = bcdec2str(bcpowmod($this->__encrypt['y'],
                                                  $this->__encrypt['x'],
                                                  $this->__encrypt['p']));
                        $this->__encrypt['k'] = str_repeat("\0", 16 - strlen($key)) . $key;
                        $encrypt = bcpowmod($this->__encrypt['g'],
                                            $this->__encrypt['x'],
                                            $this->__encrypt['p']);
                    }
                    $request = "phprpc_encrypt=$encrypt";
                    $this->__post($request);
                }
                else {
                    $this->__encrypt = false;
                }
            }
            else {
                $this->__encrypt = false;
                $result = new phprpc_error(E_ERROR, "Wrong PHPRPC Server1");
            }
        }
        else {
            $this->__encrypt = false;
            $result = $response;
        }
        return $result;
    }
    function call($function, &$arguments, $ref = true) {
        $request = "phprpc_func=$function";
        if (count($arguments) > 0) {
            $args = serialize($arguments);
            if (($this->__encrypt !== false) and ($this->encrypt > 0)) {
                $args = xxtea_encrypt($args, $this->__encrypt['k']);
            }
            $request .= "&phprpc_args=" . base64_encode($args);
        }
        $request .= "&phprpc_encrypt={$this->encrypt}";
        if (!$ref) {
            $request .= "&phprpc_ref=false";
        }
        $request = str_replace('+', '%2B', $request);
        $response = $this->__post($request);
        if (is_array($response)) {
            $header = $response[0];
            //die(join("================", $response));
            if (strpos($header, 'X-Powered-By: PHPRPC Server') !== false) {
                $body = explode("\r\n", trim($response[1]));
                $this->warning = null;
                if (substr($body[0], 0, 12) == "phprpc_errno") {
                    $errno = (int)substr($body[0], 14, strlen($body[0]) - 16);
                    $errstr = base64_decode(substr($body[1], 15, strlen($body[1]) - 17));
                    $result = new phprpc_error($errno, $errstr);
                    $this->output = base64_decode(substr($body[2], 15, strlen($body[2]) - 17));
                }
                else if (substr($body[0], 0, 13) == "phprpc_result") {
                    $result = base64_decode(substr($body[0], 15, strlen($body[0]) - 17));
                    $has_args = (substr($body[1], 0, 11) == "phprpc_args");
                    if ($has_args) {
                        $args = base64_decode(substr($body[1], 13, strlen($body[1]) - 15));
                        $errno = $body[2];
                        $errstr = $body[3];
                        $output = $body[4];
                    }
                    else {
                        $errno = $body[1];
                        $errstr = $body[2];
                        $output = $body[3];
                    }
                    if (($this->__encrypt !== false) and ($this->encrypt > 0)) {
                        if ($this->encrypt > 1) {
                            $result = xxtea_decrypt($result, $this->__encrypt['k']);
                        }
                        if ($has_args) {
                            $args = xxtea_decrypt($args, $this->__encrypt['k']);
                        }
                    }
                    $result = unserialize($result);
                    if ($has_args) {
                        $args = unserialize($args);
                        for ($i = 0; $i < count($args); $i++) {
                            $arguments[$i] = $args[$i];
                        }
                    }
                    $errno = (int)substr($errno, 14, strlen($errno) - 16);
                    $errstr = base64_decode(substr($errstr, 15, strlen($errstr) - 17));
                    if ($errno != 0) {
                        $this->warning = new phprpc_error($errno, $errstr);
                    }
                    $this->output = base64_decode(substr($output, 15, strlen($output) - 17));
                }
                else {
                    $result = new phprpc_error(E_ERROR, "Wrong PHPRPC Server2");
                }
            }
            else {
                $result = new phprpc_error(E_ERROR, "Wrong PHPRPC Server3");
            }
        }
        else {
            $result = $response;
        }
        return $result;
    }
}

if (function_exists("overload") && version_compare(phpversion(), "5", "<")) {
    eval('
    class phprpc_client extends __phprpc_client {
        function __call($function, $arguments, &$return) {
            $return = $this->call($function, $arguments, false);
            return true;
        }
    }
    overload("phprpc_client");
    ');
}
else {
    class phprpc_client extends __phprpc_client {
        function __call($function, $arguments) {
            return $this->call($function, $arguments, false);
        }
    }
}
?>