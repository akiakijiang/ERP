<?php
define('IN_ECS', true);
require_once(__DIR__.'/../includes/init.php');

$search_status_list=getCrStatusMapping();

$search_date='';
$search_status='';
$search_mobile='';
$limit=100;
$page=(isset($_GET['page'])?intval($_GET['page']):1);if($page<=0)$page=1;
$page_count=0;
$list=false;
$count=0;

if(isset($_GET['act'])){

	if(isset($_GET['date'])){
		$search_date=trim($_GET['date']);
	}
	if(isset($_GET['status'])){
		$search_status=trim($_GET['status']);
	}
	if(isset($_GET['mobile'])){
		$search_mobile=trim($_GET['mobile']);
	}

	$list=search($search_date,$search_status,$search_mobile,$limit,$page,$count);
	$page_count=(intval(($count+($limit-1))/$limit));
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>创瑞短信反馈到ERP系统的查询工具</title>
	<style type="text/css">
	#result_box {
		margin-top: 20px;
	}

	table{
		border-collapse: collapse;
		border: 1px solid gray;
	}
	th,td {
		border: 1px solid gray;
		padding: 2px;
	}
	</style>
</head>
<body>
	<h1>创瑞短信反馈到ERP系统的查询工具</h1>
	<script>console.log('一切都是天主的旨意');</script>
	<hr>
	<div id="condition_box">
		<form method='GET'>
			<div>
				发送日期 <input type='date' name='date' value='<?php echo $search_date; ?>'>
				状态 <select name='status'>
					<option value='DELIVRD' <?php if($search_status=='DELIVRD'){echo "selected='selected'";} ?>>成功</option>
					<option value='ELSE' <?php if($search_status!='DELIVRD'){echo "selected='selected'";} ?>>有问题</option>
				</select>
				手机号 <input type='text' name='mobile' value='<?php echo $search_mobile; ?>'>
				<button>搜查</button>
				<input type='hidden' name='act' value='search'>
				<input type='hidden' name='guhehe' value='<?php echo uniqid(); ?>'> 
			</div>
			<div>
				<p>
					共计<?php echo $count; ?>条，<?php echo $page_count; ?>页。
					<select name='page'>
						<?php for($i=1;$i<=$page_count;$i++){ ?>
						<option value='<?php echo $i; ?>'>第<?php echo $i; ?>页</option>
						<?php } ?>
					</select>
				</p>
			</div>
		</form>
	</div>
	<div id="result_box">
		<?php if(is_array($list)){ ?>
		<table>
			<tr>
				<th>#</th>
				<!-- <th>申请编号</th> -->
				<th>目标手机</th>
				<th>发送时间</th>
				<th>最新状态</th>
				<th>响应时间</th>
				<th>签名</th>
				<th>内容</th>
			</tr>
			<?php foreach ($list as $item) { ?>
			<tr>
				<td><?php echo $item['sms_id']; ?></td>
				<!-- <td><?php echo $item['sms_send_id']; ?></td> -->
				<td><?php echo $item['mobile']; ?></td>
				<td><?php echo $item['send_time']; ?></td>
				<td><?php echo $item['status']; ?><br><?php echo getCrStatusMapping($item['status']); ?></td>
				<td><?php echo $item['response_time']; ?></td>
				<td><?php echo $item['sign']; ?></td>
				<td><div readonly='readonly' style='height:30px;width:auto;max-width:300px;padding:2px;margin:auto;overflow-y:auto;'><?php echo $item['content']; ?></div></td>
			</tr>
			<?php } ?>
		</table>
		<?php }else{ ?>
		<p></p>
		<?php } ?>
	</div>
</body>
</html>
<?php

function search($search_date,$search_status,$search_mobile,$limit=100,$page=1,&$count=0){
	global $db;
	$sql_condition='1 ';
	if(!empty($search_date))
	{
		$sql_condition.=" AND send_time >= '{$search_date} 00:00:00' AND send_time <= '{$search_date} 23:59:59' ";
	}
	if(!empty($search_status)){
		if($search_status=='DELIVRD'){
			$sql_condition.=" AND status = 'DELIVRD' ";
		}else{
			$sql_condition.=" AND status != 'DELIVRD' ";
		}
	}
	if(!empty($search_mobile)){
		$sql_condition.=" AND mobile = '{$search_mobile}' ";
	}

	$count_sql="SELECT count(*) FROM ecshop.cr_sms WHERE {$sql_condition} ";
	$count=$db->getOne($count_sql);

	$offset=($page-1)*$limit;
	$sql="SELECT * FROM ecshop.cr_sms WHERE {$sql_condition} LIMIT $offset,$limit ";
	// echo $sql;
	$list=$db->getAll($sql);
	return $list;
}

function getCrStatusList(){
	// global $db;
	// $sql="SELECT distinct status FROM ecshop.cr_sms where 1";
}

function getCrStatusMapping($code=false){
	//DI：DI开头的是属于本平台自定义错误，这类内容可能短时间解决，其他的均为运营商的错误。
	//MK：这类错误是移动网关直接推过来的，一般是手机端错误，可以通过重启手机解决。
	//WZ:9403
	//显示DELIVED，但是手机收不到 个别情况下会发生，主要是长短信时的iphone，解决办法：清空短信，手机重启，实在不行只能先用手机发送一条长短信然后才能接收到。
	static $mapping=array(
		"DELIVRD"=>"已收到",
		"APPLIED"=>"提交成功但尚未得到回复",
		"THROWN"=>"短信代理商拒收",
		"DB:0309"=>"拦截！",
		"MK:0015"=>"手机终端问题",
		"104,1435826329004"=>"有屏蔽词",
		"MBBLACK"=>"黑名单",
		"SGIP:3"=>"连接过多，指单个节点要求同时建立的连接数过多",
		"MM:0174"=>"屏蔽",
		"DB:0141"=>"是黑名单",
		"CJ:0008"=>"是网关拦截",
		"MA:0054"=>"是发送到手机号码归属地的省份，但是没有收到归属地运营商的反馈 就是这样的状态返回值",
		"CJ:0007"=>"网关拒绝   网络出现拥堵  所以部分验证码失败",
		"16"=>"就是余额不足失败的",
		"20"=>"是频繁获取的",
		"37"=>"敏感词汇",
		"MK:0004"=>"电信业务不支持  检查被叫号码是否具有短消息功能，检查HLR中的数据配置，调整被叫号码的业务属性",
		"MA:0054"=>"运营商那边没有反馈",
		"MX:0013"=>"网关黑名单",
		"sendSingleMessage"=>"内容中有 # 号",
		"sendBatchMess"=>"内容中有 # 号",
		"DI:9429"=>"签名错误的意思 这个概率很小 长短信签名后置 碰到特殊字符的时候出现这个问题",
		"DB0119"=>"关键词",
		"MA:0002"=>"关键词",
		"MK:0012"=>"消息等待队列满",
		"MK:0011"=>"手机是空号",
		"DISTURB"=>"号码超限制",
		"ID:0076"=>"运营商错误代码：广东移动对批量内容的落地屏蔽，可以通过修改内容解决。一般2000条同样内容开始屏蔽，并且为随机屏蔽。",
		"UNDELIV"=>"运营商错误代码：一般为空号，欠费停机，或者长时间关机。",
		"REJECTD"=>"运营商错误代码：即时回来的一般为空号或停机，如果第二，三天回来的一般为目标省网关拒绝。",
		"UNKNOWN"=>"运营商错误代码：没有状态报告，原因未知，该类情况很少，运营商不计费。",
		"DI:9402"=>"平台自定义错误代码：非法内容，因触发移动过滤规则而自动拒绝发送，可以到系统的业务操作平台下的根据号码查询内容自助查询，根据查询结果做内容上的调整。",
		"DI:9403"=>"平台自定义错误代码：手机号码黑名单，本平台黑名单来源只有2个，在运营商处投诉过，或者回T（TD）之类，在运营商投诉过的号码无法解禁。回T号码可以找客服解决。",
		"DI:9415"=>"平台自定义错误代码：人工审核，判断内容不合规而拒绝发送",
		"DI:9422"=>"平台自定义错误代码：人工二次审核，判断内容不合规而拒绝发送",
		"DI:9423"=>"平台自定义错误代码：高危审核，判断内容不合规而拒绝发送",
		"DI:9432"=>"平台自定义错误代码：整个平台验证码1分钟最多发1条。如果是测试手机，让客服加入免限手机名单。",
		"DI:9433"=>"平台自定义错误代码：整个平台验证码1小时最多发5条。如果是测试手机，让客服加入免限手机名单。",
		"DI:9434"=>"平台自定义错误代码：整个平台验证码1天最多可发10条，超过就被拦截，如果是测试手机联系客服加入免限名单。",
		"DI:9427"=>"平台自定义错误代码：特级高危内容，系统自动拒绝",
		"DI:9429"=>"平台自定义错误代码：签名超过长度（20字）限制而拒绝发送，注意后置签名的】后面不能有任何字，也不能有空格或回车等特殊符号。前置签名【前面也不能有特殊符号，包括空格或回车等。",
		"DI:9909"=>"平台自定义错误代码：本平台提交到移动失败，网络偶然异常，客户侧可以通过再次提交解决。平时很少出现，节假日短信繁忙的时候容易出现。",
		"DI:9430"=>"平台自定义错误代码：客户侧长短信提交不完整 请客户侧检查",
		"DI:9501"=>"平台自定义错误代码：非法目标地址，即手机号码错误",
		"DI:9413"=>"平台自定义错误代码：10秒以内同样内容，同样号码重复发送2次以上，系统自动拦截",
		"DI:9431"=>"签名被拒绝，因为疑是高危验证码炸弹。请核实。",
		"DI:9417"=>"平台自定义错误代码：夜间模板审核，因非模板而自动拒绝发送",
		"DI:9420"=>"平台自定义错误代码：夜间（晚上22点到早上8点）因群发而系统自动拒绝",
		"DI:9421"=>"平台自定义错误代码：夜间（晚上22点到早上8点）因发送而系统自动拒绝",
		"DI:9424"=>"平台自定义错误代码：夜间系统审核因非法字符，繁体字等判断自动拒绝发送",
		"IB:0008"=>"移动内部超速，可以重新再提交",
		"YX:9402"=>"平台自定义错误代码：非法内容，因触发移动过滤规则而自动拒绝发送，可以到系统的业务操作平台下的根据号码查询内容自助查询，根据查询结果做内容上的调整。",
		"YX:9403"=>"平台自定义错误代码：手机号码黑名单",
		"YX:9416"=>"平台自定义错误代码：系统审核，疑是诈骗信息而自动拒绝发送",
		"YX:9422"=>"平台自定义错误代码：人工二次审核，判断内容不合规而拒绝发送",
		"YX:9423"=>"平台自定义错误代码：高危审核，判断内容不合规而拒绝发送",
		"YX:9424"=>"平台自定义错误代码：夜间系统审核因非法字符，繁体字等判断自动拒绝发送",
		"YX:9425"=>"平台自定义错误代码：系统判断多签名而自动拒绝发送",
		"YX:9426"=>"平台自定义错误代码：营销系统未报备，系统自动拒绝发送",
		"YX:9427"=>"平台自定义错误代码：特级高危内容，系统自动拒绝",
		"YX:9428"=>"平台自定义错误代码：短信内容为空系统自动拒绝发送",
		"YX:9429"=>"平台自定义错误代码：签名超过长度（20字）限制而拒绝发送，如果在后置签名】后面还有空格或者其他符号，也会出现类似出错。前置签名【前面也不能有特殊符号，包括空格或回车等。",
		"YX:9430"=>"平台自定义错误代码：客户侧长短信提交不完整 请客户侧检查",
		"YX:9432"=>"平台自定义错误代码：整个平台验证码1分钟最多发1条。如果是测试手机，让客服加入免限手机名单。",
		"YX:9433"=>"平台自定义错误代码：整个平台验证码1小时最多发3条。如果是测试手机，让客服加入免限手机名单。",
		"YX:9434"=>"平台自定义错误代码：整个平台验证码1天最多可发6条，超过就被拦截，如果是测试手机联系客服加入免限名单。",
		"YX:9501"=>"平台自定义错误代码：非法目标地址，即手机号码错误",
		"YX:9413"=>"平台自定义错误代码：10秒以内同样内容，同样号码重复发送2次以上，系统自动拦截",
		"YX:9431"=>"签名被临时解决。因为疑是高危验证码炸弹，请核实！",
		"YX:9415"=>"平台自定义错误代码：人工审核，判断内容不合规而拒绝发送",
		"YX:9417"=>"平台自定义错误代码：夜间模板审核，因非模板而自动拒绝发送",
		"YX:9419"=>"平台自定义错误代码：非验证码内容一天超过10次被龙卷风系统屏蔽，如果确认号码安全，在群里联系客服让其加入免限号码。",
		"YX:9420"=>"平台自定义错误代码：夜间（晚上22点到早上8点）因群发而系统自动拒绝",
		"YX:9421"=>"平台自定义错误代码：夜间（晚上22点到早上8点）因发送而系统自动拒绝",
		"YX:9909"=>"平台自定义错误代码：本网关提交到移动失败，网络偶然异常，客户侧可以通过再次提交解决",
		"WZ:9403"=>"提交次数太多了,号码被限制",
	);
	
	if($code===false){
		return $mapping;
	}else{
		if(isset($mapping[$code])){
			return $mapping[$code];
		}else{
			return '不知道';
		}
	}
}
