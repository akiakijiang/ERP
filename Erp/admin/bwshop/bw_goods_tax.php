<?php
require_once "lib_bw_tax.php";

admin_priv ( 'BWSHOP_SHOP_AGENT' );	

$params=array();
$limit=20;
$offset=0;

$act='list';
$message="";
$message_type="alert-info";
if(isset($_REQUEST['act'])){
	$act=$_REQUEST['act'];

	if($act=='insert'){
		$outer_id=$_REQUEST['outer_id'];
		$tax_rate=$_REQUEST['tax_rate'];

		$result=BWTaxAgent::addOneRecord($outer_id,$tax_rate);
		if($result===false){
			$message="更新失败：[{$outer_id}] 对应 {$tax_rate}% 的税率。";
			$message_type="alert-error";
		}else{
			$message="已更新： [{$outer_id}] 对应 {$tax_rate}% 的税率。";
			$message_type="alert-success";
		}
		$act='list';
	}
}

$search="";
if(isset($_REQUEST['search'])){
	$search=$_REQUEST['search'];
	$params['search']=$search;
}

$page=1;
if(isset($_REQUEST['page'])){
	$page=$_REQUEST['page'];
	$offset=($page-1)*$limit;
}

$count=0;
$list=BWTaxAgent::query($params,$limit,$offset,$count);
$page_count= ceil(1.0*$count / $limit);

?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>BWSHOP AGENCY</title>
	<link rel="stylesheet" type="text/css" href="bootstrap-combined.min.css">
	<link rel="stylesheet" href="font-awesome.min.css">

	<script src="jquery.min.js"></script>
	<!-- // <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script> -->
	<script src="bootstrap.min.js"></script>

	<script type="text/javascript">
	$(function () { $("[data-toggle='tooltip']").tooltip(); });
	</script>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<div class="row-fluid"><!-- row-fluid -->
			<div  class="col-xs-2">
				<img src="http://to-a.ru/L2JQgZ/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Leqee No Ebikome Zeiritsu</h1>
					<p>Koutei no mono ha koutei ni</p>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<?php if(!empty($message)){ ?>
			<div class="col-xs-11">
				<div class="alert <?php echo $message_type; ?>">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<?php echo $message; ?>
				</div>
			</div>
			<?php } ?>
			<div class="col-xs-12">
				<h4>
					未登记之商品默认税率10%。
				</h4>
			</div>
			<div class="col-xs-6">
				<form class="form-search form-inline" method="post">
					
					<input type="hidden" name="act" value="insert">
					商家编码（GOODS_STYLE格式）： <input class="input-medium" type="text" name="outer_id"/> &nbsp;
					税率： <input class="input-medium" type="text" name="tax_rate" style="width: 6em;" /> % &nbsp;
					<button type="submit" class="btn">添加 / 更新</button>
				</form>
			</div>
			<div class="col-xs-5">
				<form class="form-search form-inline" method="post">
					模糊搜索商品（商家编码或名称） <input class="input-medium" type="text" name="search" value="<?php echo $search; ?>" />  &nbsp;
					<button type="submit" class="btn">搜索</button>
				</form>
			</div>
			<div class="col-xs-11">
				<table class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>
								商品ID（GOODS格式）
							</th>
							<th>
								商家编码（GOODS_STYLE格式）
							</th>
							<th>
								商品名称
							</th>
							<th>
								税率
							</th>
						</tr>
					</thead>
					<tbody>
					<?php 
					foreach ($list as $line) {
					?>
						<tr>
							<td>
								<?php echo $line['product_id']; ?>
							</td>
							<td>
								<?php echo $line['outer_id']; ?>
							</td>
							<td>
								<?php echo $line['goods_name']; ?>
							</td>
							<td>
								<?php echo ($line['tax_rate']*100).'%'; ?>
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
							<a href="bw_goods_tax.php?page=<?php echo ($page-1); ?>">上一页</a>
						<?php }else{ ?>
							<a href="#">没有上一页了</a>
						<?php } ?>
						</li>
						<?php for ($i=max($page-5,0); $i < min($page_count,$page+5); $i++) {  ?>
						<li>
							<a href="bw_goods_tax.php?page=<?php echo ($i+1); ?>" style="<?php 
							if(($i+1)==$page){
								echo "color: black;";
							}
						?>"><?php echo ($i+1); ?></a>
						</li>
						<?php } ?>
						<li>
						<?php if($page_count>$page){ ?>
							<a href="bw_goods_tax.php?page=<?php echo ($page+1); ?>">下一页</a>
						<?php }else{ ?>
							<a href="#">没有下一页了</a>
						<?php } ?>
						</li>
					</ul>
				</div>
			</div>
			
		</div>
	</div>
</body>
</html>