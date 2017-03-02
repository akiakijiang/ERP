<?php
/**
 * @package ApiConfig
 * @author  lonce<czhang@oukoo.com>
 * @version 0.5
 * @example ApiConfig
 */
/*基本配置*/

global $sso_rpc_host, $sso_rpc_path, $sso_rpc_port;
define('HOST', $sso_rpc_host);
define('PATH', $sso_rpc_path);
define('PORT', $sso_rpc_port);


/*个别方法配置*/
define('SEARCH_SIZE',10);
define('SEARCH_START_KEY',0);
?>