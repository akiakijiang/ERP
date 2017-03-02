<?php
/**
 * @package Api
 * @author  lonce<czhang@oukoo.com>
 * @version 0.5
 * @example Api
 */
require_once("RpcController.php");
require_once('ApiConfig.php');

$objRpcContext = new RpcContext(HOST, PATH, PORT);
require_once('UniUserServiceClient.php');
$ObjUniUserService = new UniUserServiceClient($objRpcContext);

?>