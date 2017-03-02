<?php
define('IN_ECS', true);

require('includes/init.php');

require_once("function.php");

$submit = $_REQUEST['submit'];
if ($submit == '提交') {
	admin_priv('finance_order');
    $seqs = $_POST["seq"];
    if (is_array($seqs)) {
	    foreach ($seqs AS $seq) {
			$pay_id = intval($_POST["pay_id_{$seq}"]);
			$amount = $_POST["amount_{$seq}"];
			$note = $_POST["note_$seq"];
			if ($pay_id != -1 && $amount != null) {
				$sql = "select count(*) from {$ecs->table('finance_unkown_money')} where id={$seq}";
				$count = $db->getOne($sql);
				$user_name = $_SESSION['admin_name'];
				$note_with_name = $note."(".$user_name.") || ";
				if ($count == 0) {
					$sql = "insert into {$ecs->table('finance_unkown_money')}(pay_id, amount, note, user_name) 
					values({$pay_id}, {$amount}, '{$note_with_name}', '$user_name')";
				} else {
					$sql = "update {$ecs->table('finance_unkown_money')} set pay_id = {$pay_id}, 
							amount = {$amount}, note = '{$note_with_name}' where id = {$seq}";
				}
				$db->query($sql);
			}
		}
	}
	Header("Location: $back"); 
}
$sql = "
	SELECT id,pay_id, amount, note, user_name 
	FROM {$ecs->table('finance_unkown_money')}";

$unkown_moneys = $db->getAll($sql);


$smarty->assign('unkown_moneys', $unkown_moneys);
$smarty->display('oukooext/finance_unkown_money.htm');

?>


