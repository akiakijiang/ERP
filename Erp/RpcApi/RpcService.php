<?php
while(ob_get_length() !== false) @ob_end_clean();
@ob_start();

header("X-Powered-By: OUKU_PHP_RPC Server/1.0");

require_once 'ApiRpcEncode.php';
require_once 'ApiRpcDecode.php';

$service = $_GET['service'];
$method = $_GET['method'];

if (empty($service) || empty($method)) {
	echo ApiRpcDecode(false);
	die();
}

// {{{ body
$input = file_get_contents("php://input");
$params = explode("\n", trim($input));

!is_array($params) && $params = array();
foreach ($params as $k => $v)
{
	$pv[] = ApiRpcDecode(trim($v));
}

$pvs = join(", ", $pv);
// }}}

// {{{ service file
$explode = explode(".", $service);
$classFile = join("/", $explode).'.php';

if (!@include_once $classFile)
{
	echo ApiRpcDecode(false);
	die();
}
// }}}

$className = $explode[sizeof($explode)-1];
$obj = new $className;

$result = null;
eval("\$result = \$obj->\$method($pvs);");

echo ApiRpcEncode($result, gettype($result));

ob_end_flush();
?>