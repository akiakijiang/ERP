<?php
/**
 * 报表入库
 */
define('IN_ECS', true);
require_once('includes/init.php');
if (defined('REPORT_URL')) {
    $prefix = REPORT_URL;
} else {
    $prefix = "https://finadm.leqee.com/report/";
}

$version=getRequest('version','1');

switch ($version) {
	case '2':
		$url = "{$prefix}/index_new.jsp?session_value={$_COOKIE['OKEY']}";
		break;
	default:
		$url = "{$prefix}/index.jsp?session_value={$_COOKIE['OKEY']}";
		break;
}

//header("location: {$url}");

function getRequest($name,$default=''){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>报表</title>
	<script type="text/javascript">

	function checkURL(url)
	{
		var xmlhttp;
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		} else {// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function(){
			console.log("RS= "+xmlhttp.readyState+" S= "+xmlhttp.status);
			if (xmlhttp.readyState==4 ){			
				if(xmlhttp.status==200) {
					//document.open("<?php echo $url; ?>");
					document.write(xmlhttp.responseText);
				}else{
					window.open("<?php echo $url; ?>");
				}
			}
		}
		xmlhttp.open("GET",url,true);
		xmlhttp.send();
	}

	//checkURL("<?php echo $url; ?>");
	window.open("<?php echo $url; ?>");
	</script>
</head>
<body>
	<h1>常用报表 <small>Version: <?php echo $version; ?></small></h1>
	<p>报表使用的是自签名HTTPS证书，请点击【继续前往】（Chrome）或者【例外】（Firefox）以进入。</p>
	<a href="<?php echo $url; ?>" target="_blank">在新窗口打开报表</a>
</body>
</html>