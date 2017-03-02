<?php
/**
 * 开票清单添加页面
 *
 */

define('IN_ECS', true);

require('../includes/init.php');
require(ROOT_PATH . 'admin/function.php');

admin_priv("cg_edit_purchase_invoice");

$smarty->display('oukooext/purchase_invoice/purchase_invoice_request_add.htm');

?>