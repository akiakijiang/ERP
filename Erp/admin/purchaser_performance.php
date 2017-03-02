<?php
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
admin_priv('tj_analyze_purchase_data');

$csv = $_REQUEST['csv'];

$end = $_REQUEST['end_time'];
$start = $_REQUEST['start_time'];
$datediff = (strtotime($end) - strtotime($start))/(24*60*60) + 1;

if (strtotime($end) <= 0) {
	$end = date("Y-m-d", strtotime("-1 days"));
}
if (strtotime($start) <= 0) {
	$start = date("Y-m-d", strtotime("-1 days"));
}
$condition = getCondition();	
$sql = "select sum(A) AS A, sum(B) AS B, sum(C) AS C,
			   sum(D) AS D, sum(G) AS G, 
			   cast(sum(E)/$datediff as decimal(10,2)) AS E,
			   sum(H1) AS H1,
			   provider_name, purchaser 
		from cl_caigou_summary
		where 1
		{$condition}
		group by purchaser";
$summary_by_purchaser = $db->getAll($sql);
$sql = "select distinct purchaser 
		from cl_caigou_summary
		where 1
		{$condition}";
$purchasers = $db->getAll($sql);
foreach ($purchasers as $key=>$purchaser) {
	$sql = "select sum(A) as A, sum(B) AS B, sum(C) AS C, sum(D) AS D, sum(H1) AS H1,
			cast(sum(E)/$datediff as decimal(10,2)) AS E, sum(G) AS G, provider_name
			from cl_caigou_summary
			where 1
			{$condition}
			AND purchaser = '{$purchaser['purchaser']}'
			group by provider_name";
	$purchasers[$key]['providers'] = $db->getAll($sql);
	$sql = "select sum(A) as A, sum(B) AS B, sum(C) AS C, sum(D) AS D, sum(H1) AS H1,
			cast(sum(E)/$datediff as decimal(10,2)) AS E, sum(G) AS G
			from cl_caigou_summary
			where 1
			{$condition}
			AND purchaser = '{$purchaser['purchaser']}'";
	$purchasers[$key]['total'] = $db->getRow($sql);
}

$sql = "select sum(A) AS A, sum(B) AS B, sum(C) AS C, sum(H1) AS H1,
			   sum(D) AS D, cast(sum(E)/$datediff as decimal(10,2)) AS E, sum(G) AS G 
		from cl_caigou_summary
		where 1
		{$condition}";
$total_summary = $db->getRow($sql);


$smarty->assign('start', $start);
$smarty->assign('end', $end);
$smarty->assign('summary_by_purchaser', $summary_by_purchaser);
$smarty->assign('purchasers', $purchasers);
$smarty->assign('total_summary', $total_summary);
$smarty->display('oukooext/purchaser_performance.htm');

?>

<?php
function getCondition() {
	global $start, $end;
	$goods_cagetory = $_REQUEST['goods_cagetory'];
	
	$condition = "";
	$condition .= " AND DATE_FORMAT(run_time, '%Y-%m-%d') <= '{$end}'";
	$condition .= " AND DATE_FORMAT(run_time, '%Y-%m-%d') >= '{$start}'";

	return $condition;
}
?>
