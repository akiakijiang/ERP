<?php

define('IN_ECS', true);
require_once('../includes/init.php');

$act=$_REQUEST['act'];
$filepath=$_REQUEST['filepath'];//admin/index.php
$filepath=ROOT_PATH.$filepath;
$content=array('No file to load.');
$line = 0 ; //初始化行数 
if($act=='load'){
	if(!empty($filepath) && file_exists($filepath)){
		$thefile=fopen($filepath, 'r');
		if($thefile){
			//$content=file_get_contents($filepath); 
			
			$content=array();
			//获取文件的一行内容，注意：需要php5才支持该函数；  
			while(true){
				//$yihang=stream_get_line($thefile,8192,"\n");
				$yihang=fgets($thefile);
				if($yihang){
					$content[]=$yihang;//addcslashes($yihang,'\\');//addslashes($yihang);
					$line++; 
				}else{
					break;
				}				 
			}  

			fclose($thefile);
		}else{
			$content=array('Failed to open '.$filepath);
		}
	}else{
		$content=array('No such file '.$filepath);
	}
}elseif ($act=='ajax_dir'){
	$basedir=$_REQUEST['basedir'];
	$files=getSubDir($basedir);
	foreach ($files as $key => $value) {
		$pv=$basedir.'/'.$value;
		echo '<p class="c'.($key%2).'">';
		$file_last_update_time=date("Y-M-d H:i:s",fileatime(ROOT_PATH.$pv));
		if(is_dir(ROOT_PATH.$pv)){
			echo "[D] <a href='javascript:void(0)' onclick='click_path(\"".$pv."\")'>".$pv."</a> - ".$file_last_update_time;
		}else{
			echo "[F] <a href='./source_code_viewer.php?act=load&filepath=".urlencode($pv)."' >".$pv."</a> - ".$file_last_update_time;
		}
		echo "</p>";
	}
	exit('-- DONE --');
}else{
	$content=array('No action determined.');
}

function getSubDir($basedir){
	$dir=ROOT_PATH.$basedir;
	// echo "opening ".$dir;
	if(file_exists($dir)){
		$files=array();
	    //获取某目录下所有文件、目录名（不包括子目录下文件、目录名）
	    $handler = opendir($dir);
	    while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
	        if ($filename != "." && $filename != ".." 
	        	// && stristr($filename,'.log')
	        ) {
	            $files[] = $filename;
	        }
	    }
	    closedir($handler);
	    //natsort($files);
	    rsort($files);
	    return $files;
	}else{
		//echo "// Not a dir";
		return array();
	}
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Source Code Viewer</title>
<style type="text/css">
	#src_table {
		margin: 10px;
		width: 90%;
	}
	#src_table thead {
		text-align: center;
		font-size: 20px;
	}
	#src_table td.cc {
		background: #E7E5DC;
	}
	#src_table td.c0 {
		background: #F8F8F8;
	}
	#src_table td.c1 {
		background: #FFFFFF;
	}
	#searchedTA {
		background: #FCFCFC;
		max-height: 300px;
		height: auto;
		width: 90%;
		margin: 10px;
		overflow: scroll;
		display: none;
	}
	p.c0 {
		background: #F8F8F8;
	}
	p.c1 {
		background: #FFFFFF;
	}
</style>
<script type="text/javascript">
	function E(eid){
		return document.getElementById(eid);
	}
	function do_ajax(method,url,isAsync,info_span){
		var xmlhttp;
		if (window.XMLHttpRequest){
		  	xmlhttp=new XMLHttpRequest();
		} else {
		  	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function(){
			document.getElementById('searchedTA').style.display='block';
		  	if (xmlhttp.readyState==4 && xmlhttp.status==200){
		    	document.getElementById(info_span).innerHTML=xmlhttp.responseText;
		    }else{
		    	document.getElementById(info_span).innerHTML="Failed to load...";
		    }
		}
		xmlhttp.open(method,url,isAsync);
		xmlhttp.send();
	}
	function search_path(){
		var sp=E('search_path');
		var sta=E('searchedTA');
		sta.innerHTML='loading...';
		do_ajax('POST','./source_code_viewer.php?act=ajax_dir&basedir='+sp.value,true,'searchedTA');
	}
	function click_path(pv){
		var sp=E('search_path');
		sp.value=pv;
		search_path();
	}
	function searchedTASwitch(){
		var sta=E('searchedTA');
		if(sta.style.display=='none'){
			sta.style.display='block';
		}else{
			sta.style.display='none';
		}
	}
</script>
</head>
<body>
<div id='header'>
	<h1>Source Code Viewer</h1>
	<div>
	<form method='post' style="display: inline-block;">
		<input type='hidden' name='act' value='load'>
		Input the filepath, such as 'admin/index.php': 
		<input type='text' name='filepath' style='width:300px;'>
		<input type='submit'>
	</form>
		Path Search:
		<input type="text" id='search_path'>
		<input type='button' value="search" onclick='search_path()'>
		<input type="button" value="+/-" onclick="searchedTASwitch()">
	</div>
	<div id="searchedTA"></div>
</div>
<table id='src_table'>
<thead>
	<?php echo $filepath; ?>
</thead>
<?php 
foreach ($content as $key => $value) {
	echo "<tr>";
	echo "<td class='cc'>";
	echo "<pre><code>".($key+1)."</code></pre>";
	echo "</td>";
	echo "<td class='c".($key%2)."'>";
	echo "<pre><code>".htmlspecialchars($value,ENT_QUOTES )."</code></pre>";
	echo "</td>";
	echo "</tr>";
}
?>
</table>

</body>
</html>