<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once("function.php");
require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
include_once(ROOT_PATH . 'includes/cls_json.php');

require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

$act = $_REQUEST['act'];
if (!empty($act) && $act == 'group_add'){
	$tpl = 
	array ('库位列表' => 
	         array ('barcode' => '库位'
	                 ) );
	while (1) {			
		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		

		if (! $uploader->existsFile ( 'excel' )) {
			$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
			break;
		}

		$file = $uploader->file ( 'excel' );
			
		// 检查上传文件 检查个毛线，能读不就好了
		/*
		if (! $file->isValid ( 'xls, xlsx', $max_size )) {
			$message .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			break;
		}
		*/
		// 读取excel
		$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$message .= "   " . reset ( $failed );
			break;
		}
		
		//判断是否符合条件
		if (sizeof($result['库位列表']) == 0) {
			$message .= "   导入的数据为空";
			break;
		}
		$barcode_list=array();
		$id=1;
		foreach ($result['库位列表'] as $key => $box) {
			$barcode_list[$id]=$box['barcode'];
			$id++;
		}
		break;
	}
	
	//pp($result);
	//pp($barcode_list);
}

?>

<html>
	<head>
		<title>库位条码集群打印</title>
		<script type="text/javascript">
			function set_start(x){
				var start_box=document.getElementById('START_ID');
				var end_box=document.getElementById('END_ID');
				start_box.value=x;
				if(end_box.value<x)end_box.value=x;
				check_selected();
			}
			function set_end(x){
				var start_box=document.getElementById('START_ID');
				var end_box=document.getElementById('END_ID');
				end_box.value=x;
				if(start_box.value>x) start_box.value=x;
				check_selected();
			}
			function check_selected(){
				var start_box=document.getElementById('START_ID');
				var end_box=document.getElementById('END_ID');
				for(var i=0;i<=size;i++){
					var check_it=document.getElementById('check_id_'+i);
					var check_its=document.getElementById('check_ids_'+i);
					try{
						if(i>=start_box.value && i<=end_box.value){
							check_it.checked=true;
							check_its.checked=true;
						}else{
							check_it.checked=false;
							check_its.checked=false;
						}
					} catch(err){
				  		//在这里处理错误
				  	}
				}
			}
			function record(){
				var start_box=document.getElementById('START_ID');
				var end_box=document.getElementById('END_ID');
				var box=document.getElementById('DONE_LIST');
				box.innerHTML+="PRINTED ID: ["+start_box.value+","+end_box.value+"]<br>";
			}
		</script>
	</head>
	<BODY>
		<script type="text/javascript">
			var size=<?php echo sizeof($barcode_list); ?>;
		</script>
		<div>
			<p>
				请选择XLS文件并导入！要求工作表名称为'库位列表'，A1单元格为'库位'。
			</p>
			<form action="inventory_location_barcodes_xls.php?act=group_add" id="upload" enctype="multipart/form-data" method="post">
				<input type="file" size="30" name="excel">
				<input type="submit" value="导入">
			</form>
		</div>
		<div id="message">
			<p>
				<?php echo $message; ?>
			</p>
			<p id="DONE_LIST" style="background-color:lightgray;">
				打印历史<br>
			</p>
		</div>
		<?php if (!empty($barcode_list) && sizeof($barcode_list)>0 ){ ?>
		<div>
			<form name="PRINT_THEM"  action="print_barcodes.php" method="post" target="print_frame">
				<input type="hidden" name="sugu_print" value="1">
				<input type="hidden" name="type" value="locations_post">
				<p>
					请使用以下方式给定打印条码的范围（两端均包含）：<br>
					# 手动输入ID(可以对照EXCEL的ID);<br>
					# 设置起始和结束打印的条码;<br>
					然后点击打印。
				</p>
				<p>
					起始ID：<input type="text" id="START_ID">
					~
					结束ID：<input type="text" id="END_ID">
					
					<!-- <input type="button" onclick="check_selected();" value="手动刷新"> -->

					<input type="button" onclick="
						check_selected();
						document.PRINT_THEM.submit();
						record();
					" value='打印'>
				</p>
				<table border=1 style="width:40%;text-align:center;">
					<TR>
						<td style="width:20%;">ID</td>
						<td style="width:40%;">条码</td>
						<td style="width:40%;">设定</td>
					</TR>
					<?php
					foreach ($barcode_list as $key => $value) {
						echo "<tr>";
						echo "<td><input type='checkbox' id='check_ids_$key' disabled='disabled' value='$value'>$key</td>";
						echo "<td>$value</td>";
						echo "<td>";
						echo "
							<input type='checkbox' id='check_id_$key' name='barcodes[]' value='$value' style=\"display:none;\">
							<input type='button' onclick=\"set_start($key);\" value='设为起始'>
							<input type='button' onclick=\"set_end($key);\" value='设为结束'>
						";
						echo "</td>";
						echo "</tr>";
					}
					?>
				</table>
			</form>
		</div>
		<?php } ?>
		<iframe name="print_frame" width="0" height="0" frameborder="0" src="about:blank" ></iframe>
	</BODY>
</html>