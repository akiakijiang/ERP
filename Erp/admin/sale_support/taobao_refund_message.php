<?php
	define('IN_ECS', true);
	require_once('../includes/init.php');
	require_once(ROOT_PATH . 'includes/lib_order.php');
	require_once(ROOT_PATH . 'includes/cls_json.php');
	include_once(ROOT_PATH . 'admin/function.php'); 
	// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
	require_once(ROOT_PATH . 'includes/helper/array.php');

	require_once('postsale_function.php');

	$owner_role_mapping=array(
		1=>"买家",
		2=>"卖家",
		3=>"小二"
	);

	if(isset($_REQUEST['taobao_refund_id'])){
		$taobao_refund_id=$_REQUEST['taobao_refund_id'];
	}else{
		die("没有给出淘宝退款单，没法干活。");
	} 
?>
<html>
<head>
	<title><?php echo "taobao refund message of ".$taobao_refund_id; ?></title>
	<style type="text/css">
	div {
		margin: 5px;
		padding: 5px;
		border:1px solid;
		border-radius:15px;
		-moz-border-radius:15px; /* Old Firefox */
	}
	div.msgbox_1 {
		text-align: left;
		margin-right: 20%;
		background-color: #DFEFF8;
		border-color: #DFEFFF;
	}
	div.msgbox_2 {
		text-align: right;
		margin-left: 20%;
		background-color: #E7E9EA;
		border-color: #E7FFEA;
	}
	div.msgbox_3 {
		text-align: center;
		margin-right: 10%;
		margin-left: 10%;
		background-color: #E9EBEC;
		border-color: #FFEBEC;
	}
	</style>
</head>
<body>
<?php
	$all_refults=getTaobaoRefundMessages(null,$taobao_refund_id);

	if($all_refults && count($all_refults)>0){
		echo "<p>淘宝系退款单[".$taobao_refund_id."]留言记录（冒泡式，最新的记录在最上面）</p><hr>";
		foreach ($all_refults as $rfno => $line) {
			$line_owner_role=$line['owner_role'];
			echo "<div class='msgbox_".$line_owner_role."''>".$line['created']." ";
			echo $owner_role_mapping[$line_owner_role]."留言记录";
			
			echo "<hr>";
			echo $line['content']."<br>";
			if($line['pic_urls']!=""){
				$urls=explode(';', $line['pic_urls']);
				if($urls && count($urls)>0){
					echo "附图：";
					foreach ($urls as $picurlno => $url) {
						echo "<a href='".$url."'>图片".($picurlno+1)."</a>；";
					}
				}
			}else{
				//echo "没有附图。";
			}
			echo "</div>";
		}
	}else{
		echo "什么都没有找到。";
	}
?>
</body>
</html>