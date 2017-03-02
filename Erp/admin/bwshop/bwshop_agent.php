<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_shop.php';

$active_tab='list';
$bw_shop_id_to_update=null;

$action_result=null;

admin_priv ( 'BWSHOP_SHOP_AGENT' );	


if(isset($_REQUEST['act'])){
	if($_REQUEST['act']=='create_shop'){
		$new_shop_id=BWShopAgent::createShop($_POST);
		if($new_shop_id===false){
			$action_result=false;
		}else{
			$action_result='创建了新的分销虾米。';
		}
	}
	elseif($_REQUEST['act']=='update_shop'){
		$params=$_POST;
		unset($params['shop_id']);
		unset($params['party_id']);
		unset($params['act']);
		$afx=BWShopAgent::updateShop($_POST['shop_id'],$params);
		if($afx===false){
			$action_result=false;
		}else{
			$action_result='分销虾米修改,变更行数：'.$afx.'。';
		}
	}
	elseif($_REQUEST['act']=='update_shop_page'){
		$active_tab='update';
		$bw_shop_id_to_update=$_REQUEST['shop_id'];
	}
	elseif($_REQUEST['act']=='create_shop_page'){
		$active_tab='create';
		$bw_shop_id_to_update=$_REQUEST['shop_id'];
	}
}


$shop_list=BWShopAgent::shopList();
$cand_dist_list=BWShopAgent::distributorList();

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
				<img src="http://to-a.ru/VZOVKk/img2">
			</div>
			<div class="col-xs-9">
				<div class="page-header">
					<h1>Toaru Leqee No Ebikome Kanri</h1>
					<p>Kuni to chikara to sakae toha kagirinaku nandi no mono ni nareba nari</p>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="col-xs-11">
			<?php 
				if($action_result!==null){ 
					if($action_result!==false){
			?>
				<div class="alert alert-success">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<h4>
						大事已成！
					</h4>
					<?php echo $action_result; ?>
				</div>
			<?php 	
					}else{
			?>
				<div class="alert alert-error">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<h4>
						呜呼哀哉！
					</h4>
					<?php echo $action_result; ?>
				</div>
			<?php
					}
				} 
			?>
			</div>
			<div class="col-xs-11">
				<div class="tabbable" id="tabs-564482">
					<ul class="nav nav-tabs">
						<li <?php if($active_tab=='list'){ ?> class="active" <?php } ?>>
							<a href="#panel-411722" data-toggle="tab">列表</a>
						</li>
						<li <?php if($active_tab=='create'){ ?> class="active" <?php } ?>>
							<a href="#panel-334320" data-toggle="tab">新建</a>
						</li>
						<li <?php if($active_tab=='update'){ ?> class="active" <?php } ?>>
							<a href="#panel-812745" data-toggle="tab">更新</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane <?php if($active_tab=='list'){ ?>active<?php } ?>" id="panel-411722">
							<?php
							if(empty($shop_list)){
								echo "<p>已经被全部吃掉</p>";
							}else{
								// print_r($shop_list);
							?>
							<table class="table table-hover table-bordered table-condensed">
								<thead>
									<tr>
										<th>索引</th>
										<th>名号</th>
										<th>分销商和主分销商</th>
										<th>公钥</th>
										<th>私钥</th>
										<th>正正</th>
										<th>组织</th>
										<th>特别协定</th>
										<th>操作</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ($shop_list as $shop) {
									?>
									<tr>
										<td><?php echo $shop['shop_id']; ?></td>
										<td><?php echo $shop['shop_name']; ?></td>
										<td><?php echo $shop['distributor_name']; ?> | <?php echo $shop['main_distributor_name']; ?></td>
										<td><?php echo $shop['shop_code']; ?></td>
										<td><?php echo $shop['shop_key']; ?></td>
										<td><?php echo $shop['zz_code']; ?></td>
										<td><?php echo $shop['party_name']; ?></td>
										<td><?php echo ($shop['free_shipping']=='Y'?'免除邮费。':'').
													($shop['credit_shipping']=='Y'?'提前发货同步。':'').
													($shop['is_sync']==0?'':'E2B调度。'); 
											?></td>
										<td>
											<a href="bwshop_agent.php?act=update_shop_page&shop_id=<?php echo $shop['shop_id']; ?>"><button>修改</button></a>
										</td>
									</tr>
									<?php
									}
									?>
								</tbody>
							</table>
							<?php
							}
							?>
						</div>
						<div class="tab-pane <?php if($active_tab=='create'){ ?>active<?php } ?>" id="panel-334320">
							<form action="bwshop_agent.php" method='POST' class="form-horizontal">
								<div class="control-group">
									<label class="control-label" for="input_distributor_id">分销商店铺</label>
									<div class="controls">
										<!-- <input id="input_distributor_id" type="text" class="input-control" /> -->
										<select id="input_distributor_id" name="distributor_id" class="input-control" >
											<?php 
											foreach ($cand_dist_list as $cand_dist) {
											?>
											<option value="<?php echo $cand_dist['distributor_id']; ?>"><?php echo $cand_dist['name']; ?></option>
											<?php
											}
											?>
										</select>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_shop_code">公钥</label>
									<div class="controls">
										<input id="input_shop_code" name="shop_code" type="text" class="input-control" />
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_shop_key">私钥</label>
									<div class="controls">
										<input id="input_shop_key" name="shop_key" type="text" class="input-control" />
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_zz_code">正正店铺码</label>
									<div class="controls">
										<input id="input_zz_code" name="zz_code" type="text" class="input-control" />
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_party_id">业务组</label>
									<div class="controls">
										<select id="input_party_id" name="party_id" class="input-control">
											<option value="">根据分销商自动</option>
										</select>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_credit_shipping">同步发货</label>
									<div class="controls">
										<select id="input_credit_shipping" name="credit_shipping" class="input-control">
											<option value='N'>正常出货后同步发货</option>
											<option value='Y'>单证放行即同步发货</option>
										</select>
										只有天猫国际和京东全球购有效
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_free_shipping">运费控除</label>
									<div class="controls">
										<select id="input_free_shipping" name="free_shipping" class="input-control">
											<option value="N">运费控除对象（以首重续重公式计费）</option>
											<option value="Y">运费控除对象外</option>
										</select>
									</div>
								</div>
								<div class="control-group">
									<div class="controls">
										<input type='hidden' name='act' value='create_shop'>
										<button type="submit" class="btn">创建</button>
									</div>
								</div>
							</form>
						</div>
						<div class="tab-pane <?php if($active_tab=='update'){ ?>active<?php } ?>" id="panel-812745">
							<?php 
							$old_shop=null;
							foreach ($shop_list as $shop) {
								if($shop['shop_id']==$bw_shop_id_to_update){
									$old_shop=$shop;
									break;
								}
							}
							if($old_shop===null){
								echo "未选择修改对象！";
							}else{
							?>
							<form action="bwshop_agent.php" method='POST' class="form-horizontal">
								<div class="control-group">
									<label class="control-label" for="input_shop_name">分销商</label>
									<div class="controls">
										<input id="input_shop_id" name="shop_id" type="hidden" class="input-control" value='<?php echo $old_shop['shop_id']; ?>' />
										<input id="input_shop_name" name="shop_name" type="text" class="input-control" value='<?php echo $old_shop['shop_name']; ?>' />
										现有值：<?php echo $old_shop['shop_name']; ?>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_shop_code">公钥</label>
									<div class="controls">
										<input id="input_shop_code" name="shop_code" type="text" class="input-control" value='<?php echo $old_shop['shop_code']; ?>' />
										现有值：<?php echo $old_shop['shop_code']; ?>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_shop_key">私钥</label>
									<div class="controls">
										<input id="input_shop_key" name="shop_key" type="text" class="input-control"  value='<?php echo $old_shop['shop_key']; ?>' />
										现有值：<?php echo $old_shop['shop_key']; ?>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_zz_code">正正店铺码</label>
									<div class="controls">
										<input id="input_zz_code" name="zz_code" type="text" class="input-control"  value='<?php echo $old_shop['zz_code']; ?>' />
										现有值：<?php echo $old_shop['zz_code']; ?>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_party_id">业务组</label>
									<div class="controls">
										<select id="input_party_id" name="party_id" class="input-control">
											<option value="">根据分销商自动</option>
										</select>
										然而并不能改
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_credit_shipping">发货同步</label>
									<div class="controls">
										<select id="input_credit_shipping" name="credit_shipping" class="input-control">
											<option value='N' <?php echo ($old_shop['credit_shipping']=='N'?'selected="selected"':''); ?>>正常出货后同步发货</option>
											<option value='Y' <?php echo ($old_shop['credit_shipping']=='Y'?'selected="selected"':''); ?>>单证放行即同步发货</option>
										</select>
									</div>
									只有天猫国际和京东全球购有效
								</div>
								<div class="control-group">
									<label class="control-label" for="input_is_sync">ERP2BWSHOP调度</label>
									<div class="controls">
										<select id="input_is_sync" name="is_sync" class="input-control">
											<option value='0' <?php echo ($old_shop['is_sync']=='0'?'selected="selected"':''); ?>>关</option>
											<option value='1' <?php echo ($old_shop['is_sync']=='1'?'selected="selected"':''); ?>>开</option>
										</select>
									</div>
								</div>
								<div class="control-group">
									<label class="control-label" for="input_free_shipping">运费控除</label>
									<div class="controls">
										<select id="input_free_shipping" name="free_shipping" class="input-control">
											<option value="N" <?php if($old_shop['free_shipping']=='N'){echo "selected='selected'";} ?>>
												运费控除对象（以首重续重公式计费）
											</option>
											<option value="Y" <?php if($old_shop['free_shipping']=='Y'){echo "selected='selected'";} ?>>
												运费控除对象外
											</option>
										</select>
										现有值：<?php if($old_shop['free_shipping']=='N'){echo "运费控除对象";}else{echo "运费控除对象外";} ?>
									</div>
								</div>
								<div class="control-group">
									<div class="controls">
										<input type='hidden' name='act' value='update_shop'>
										<button type="submit" class="btn">修改</button>
									</div>
								</div>
							</form>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>