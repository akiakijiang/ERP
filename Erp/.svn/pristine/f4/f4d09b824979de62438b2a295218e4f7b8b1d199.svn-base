<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_mibun.php';

set_include_path(__DIR__."/../includes/Classes/");
require_once("PHPExcel.php");
require_once("PHPExcel/IOFactory.php");
require_once("PHPExcel/Writer/Excel2007.php");

$page=1;
$size=50;
$total=0;

$block_type='';

if(!empty($_REQUEST['act'])){
	$act=$_REQUEST['act'];
}else{
	$act='list';
}

$search_value='';
$upload_result='';
$since='';

if($act=='upload'){
	$upload_excel = $_FILES['upload_excel']['name'];
    $upload_file = $_FILES['upload_excel']['tmp_name'];

    $tpl = array('mibun_number','name');

    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    $objPHPExcel = $objReader->load($upload_file);
    $sheet = $objPHPExcel->getSheet(0);
    $Row = $sheet->getHighestRow();
    $Column = $sheet->getHighestColumn();
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($Column);//总列数

    $upload_result='<div class="alert"><button type="button" class="close" data-dismiss="info">×</button>';

    if($Row>1){
    	$done_count=0;
        $failed_count=0;
    	$upload_result.= "<h4>提示!</h4> <div>下面是各牺牲品的填埋信息。</div>";
    	for($m=2;$m<=$Row;$m++){
        	$mibun_number=$sheet->getCellByColumnAndRow(0, $m)->getValue();
        	$name=$sheet->getCellByColumnAndRow(1, $m)->getValue();

        	$mn_reg=preg_match('/^\d{17}[\dX]$/', $mibun_number);
        	
        	if(!empty($mibun_number) && !empty($name) && !empty($mn_reg)){
        		$r=BWMibunPool::addIkenie($mibun_number,$name);
        		
        		if(empty($r)){
        			$upload_result.= "<div>".$mibun_number."【".$name."】 = ".(empty($r)?'插入失败。可能写错可能重复':'牺牲品登记号：'.$r)."</div>";
        			$failed_count+=1;
        		}else{
        			$upload_result.= "<div style='display:none'>".$mibun_number."【".$name."】 = ".(empty($r)?'插入失败。可能已经存在':'牺牲品登记号：'.$r)."</div>";
        			$done_count+=1;
        		}
        	}else{
        		$upload_result.= "<div>".$mibun_number."【".$name."】 = 输入错误</div>";
        		$failed_count+=1;
        	}

    	}
    	$upload_result.="<div>共计成功{$done_count}只，失败{$failed_count}只。</div>";
	}else{
		$upload_result.= "<h4>提示!</h4> <div>你没有给正确的牺牲品列表。</div>";
	}

	$upload_result.="</div>";

	$act='list';
}elseif($act=='reset'){
	$reset_type=$_REQUEST['reset_type'];
	$since=$_REQUEST['since'];

	if($reset_type=='MONTH'){
		$afx=BWMibunPool::resetMonthUsage($since);
	}elseif($reset_type=='YEAR'){
		$afx=BWMibunPool::resetYearUsage($since);
	}
	$upload_result='<div class="alert"><button type="button" class="close" data-dismiss="info">×</button>'.
		"<h4>提示!</h4> <div>下面是各牺牲品的征用清空信息。</div><div>清空了".$afx.'只牺牲品的</div>'.
		'</div>';

	$act='list';
}elseif($act=='custom_block'){
	$block_status=$_REQUEST['block_status'];
	$id_str=$_REQUEST['id_str'];

	$list = preg_split("/[\s,]+/", $id_str);
	$afx=BWMibunPool::switchBlockStatusForList($list,$block_status);

	$diff_list=BWMibunPool::checkListForBlock($list);

	$upload_result='<div class="alert"><button type="button" class="close" data-dismiss="info">×</button>'.
		"<h4>提示!</h4> <div>海关封禁状态改变".intval($afx).'条。</div>'.
		"<div>以下身份证不存在，要操作请先导入：".implode('、', $diff_list)."</div>".
		'</div>';

	$act='list';
}
if($act=='search'){
	$search_value=$_REQUEST['search_value'];
	$bw_mibun_list=BWMibunPool::seekSomebody($search_value);
	$page_count=1;
}
if($act=='list'){
	if(isset($_REQUEST['page'])){
		$page=$_REQUEST['page'];
	}
	if(isset($_REQUEST['size'])){
		$size=$_REQUEST['size'];
	}
	
	if(isset($_REQUEST['block_type'])){
		$block_type=$_REQUEST['block_type'];//BLOCK OR FREE
	}

	$bw_mibun_list=BWMibunPool::mibunList($size,($page-1)*$size,$total,$block_type);

	$page_count= ceil($total / $size);
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>BWSHOP MIBUN POOL</title>
	<link rel="stylesheet" type="text/css" href="bootstrap-combined.min.css">
	<link rel="stylesheet" href="font-awesome.min.css">

	<script src="jquery.min.js"></script>
	<!-- // <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script> -->
	<script src="bootstrap.min.js"></script>

	<script type="text/javascript">
	$(function () { $("[data-toggle='tooltip']").tooltip(); });
	</script>
	<style type="text/css">
	.tooltip-inner {
		text-align: left;
		white-space:pre-wrap;
	}
	.black {
		color: black;
	}
	.red {
		color: red;
	}
	.orange {
		color: orange;
	}
	.green {
		color: green;
	}
	.blue {
		color: blue;
	}
	.input-control{
		min-width: 300px;
	}
	</style>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div  class="col-xs-2">
				<img src="http://to-a.ru/OELHce/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Leqee No Ikenie Rougoku</h1>
					<p>Tsumi wo kasanayou~</p>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div  class="col-xs-12">
				<?php echo $upload_result; ?>
				<div class="tabbable" id="tabs-518394">
				<ul class="nav nav-tabs">
					<li class="active">
						<a href="#panel-754050" data-toggle="tab">列表</a>
					</li>
					<li>
						<a href="#panel-548351" data-toggle="tab">导入</a>
					</li>
					<li>
						<a href="#panel-894664" data-toggle="tab">重置</a>
					</li>
					<li>
						<a href="#panel-261855" data-toggle="tab">封号</a>
					</li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="panel-754050">
						<div class="col-xs-4">
							<form class="form-search form-inline">
								<select name="block_type">
									<option value="" <?php if($block_type!='BLOCK' && $block_type!='FREE'){echo "selected='selected'";} ?>>全部</option>
									<option value="BLOCK" <?php if($block_type=='BLOCK'){echo "selected='selected'";} ?>>已查封</option>
									<option value="FREE" <?php if($block_type=='FREE'){echo "selected='selected'";} ?>>未查封</option>
								</select>
								<button type="submit" class="btn">筛选</button>
							</form>
						</div>
						<div class="col-xs-2">
							左右筛选※相互独立
						</div>
						<div class="col-xs-5">
							<form class="form-search form-inline">
								查找号码或名字，咕嘿嘿。
								<input type="hidden" name="act" value="search">
								<input class="input-medium search-query" type="text" id="search_value" name="search_value" value="<?php echo $search_value; ?>"/> 
								<button type="submit" class="btn">查找</button>
							</form>
						</div>
						<table class="table table-bordered table-hover table-condensed">
							<thead>
								<tr>
									<th>
										牺牲品填埋登记号
									</th>
									<th>
										良民证
									</th>
									<th>
										氏名
									</th>
									<th>
										状态
									</th>
									<th>
										月征用次数
									</th>
									<th>
										月征用统计起始时间
									</th>
									<th>
										年征用次数
									</th>
									<th>
										年征用统计起始时间
									</th>
									<th>
										最后更新
									</th>
								</tr>
							</thead>
							<tbody>
							<?php 
							// var_dump($bw_mibun_list);die();
							foreach ($bw_mibun_list as $mibun) {
							?>
								<tr>
									<td>
										<?php echo $mibun['ikenie_id']; ?>
									</td>
									<td>
										<?php 
										if(!empty($mibun['blocked_since'])){
											echo "<span style='color:red'><del>".$mibun['mibun_number']."</del></span>"; 
										}else{
											echo $mibun['mibun_number']; 
										}
										?>
									</td>
									<td>
										<?php echo $mibun['name']; ?>
									</td>
									<td>
										<?php 
										if(!empty($mibun['blocked_since'])){
											$diff1Day = new DateInterval('P1Y');
											$d0 = new DateTime($mibun['blocked_since']);
											$d0->add($diff1Day);
											$d_now=new DateTime();
											$interval = date_diff( $d_now,$d0);
											$diff_days=$interval->format('%R%a');
											echo "自 ".$mibun['blocked_since']." 查封。";
											//echo "建议解封日：".$d0->format('Y-m-d H:i:s')."。";
											echo "<br>离建议解封日还有".$diff_days."天。";
										}else{
											if($mibun['month_usage']>=4){
												echo "自主规制";
											}else{
												echo '正常'; 
											}
										}
										?>
									</td>
									<td>
										<?php echo $mibun['month_usage']; ?>
									</td>
									<td>
										<?php echo $mibun['month_usage_since']; ?>
									</td>
									<td>
										<?php echo $mibun['year_usage']; ?>
									</td>
									<td>
										<?php echo $mibun['year_usage_since']; ?>
									</td>
									<td>
										<?php echo $mibun['update_time']; ?>
									</td>
								</tr>
							<?php
							} 
							?>
								
							</tbody>
						</table>
						<div class="pagination pagination-small pagination-right">
							<ul>
								<li>
								<?php if(1<$page){ ?>
									<a href="bw_mibun_pool.php?page=<?php echo ($page-1); ?>&block_type=<?php echo $block_type; ?>">上一页</a>
								<?php }else{ ?>
									<a href="#">没有上一页了</a>
								<?php } ?>
								</li>
								<?php for ($i=max($page-5,0); $i < min($page_count,$page+5); $i++) {  ?>
								<li>
									<a href="bw_mibun_pool.php?page=<?php echo ($i+1); ?>&block_type=<?php echo $block_type; ?>" style="<?php 
									if(($i+1)==$page){
										echo "color: black;";
									}
								?>"><?php echo ($i+1); ?></a>
								</li>
								<?php } ?>
								<li>
								<?php if($page_count>$page){ ?>
									<a href="bw_mibun_pool.php?page=<?php echo ($page+1); ?>&block_type=<?php echo $block_type; ?>">下一页</a>
								<?php }else{ ?>
									<a href="#">没有下一页了</a>
								<?php } ?>
								</li>
							</ul>
						</div>
					</div>
					<div class="tab-pane" id="panel-548351">
						<p>
							咕嘿嘿，又有新鲜的牺牲品到来了。请把他们放进xlsx文件吧，模板在<a href="mibun_import.xlsx">这里</a>下载。
						</p>
						<form method="post" enctype="multipart/form-data" action="bw_mibun_pool.php" class="form-search form-inline">
							<!-- <input type="file" name="upload_excel" id="file_upload"> -->
							<input type="hidden" name="act" value="upload">
							<input id="lefile" name="upload_excel" type="file" style="display:none">
							<div class="input-append">
								<input id="photoCover" class="input-large" type="text">
								<a class="btn" onclick="$('input[id=lefile]').click();">Browse</a>
							</div>
							<button class="btn">上传</button>
							<script type="text/javascript">
							$('input[id=lefile]').change(function() {
							$('#photoCover').val($(this).val());
							});
							</script>
					    </form>
					</div>
					<div class="tab-pane" id="panel-894664">
						<p>
							尘归尘，土归土。为汝所欲为，即为汝之法。
							根据月份和年份分别清空征用次数。
						</p>
						<form method="post" enctype="multipart/form-data" action="bw_mibun_pool.php" class="form-search form-inline">
							<input type="hidden" name="act" value="reset">
							<select name="reset_type">
								<option value='MONTH'>按月</option>
								<option value='YEAR'>按年</option>
							</select>
							统计开始时间在此时间点及之前的会被归零。时间格式如 <code>2010-12-31 23:59:59</code>
							<input class="input-medium search-query" type="text" id="since" name="since" value="<?php echo $since; ?>"/> 
							<button type="submit" class="btn">归零</button>
						</form>
					</div>
					<div class="tab-pane" id="panel-261855">
						<p>
							被海关查封的牺牲品在这里登记。被海关解封的牺牲品在这里解冻。
						</p>
						<form method="POST" action="bw_mibun_pool.php" class="form-search form-inline">
							<input type="hidden" name="act" value="custom_block">
							<div style="margin: 5px 0px;">
							<select name="block_status" style="width: 6em;">
								<option value='BLOCK'>查封</option>
								<option value='FREE'>解封</option>
							</select>
							以下身份证，如有多个，以空格或英文逗号分隔之。
							</div>
							<div style="margin: 5px 0px;">
								<textarea style="width:60%;height:100px;" name="id_str"></textarea>
							</div>
							<div style="margin: 5px 0px;">
								<button type="submit" class="btn">递交</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			</div>
		</div>
	</div>
</body>
</html>