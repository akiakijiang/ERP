<?php

$act=getRequest('act');

if($act=='search_filelock'){
	$keyword=getRequest('keyword','');
	$date=getRequest('date','');

	if(empty($keyword)){
		die('KEYWORD EMPTY!');
	}
	if(empty($date)){
		$date=date('ymd');
	}

	$file=__DIR__.'/../filelock/devel-'.$date.'.log';

	$command="grep -n ".escapeshellarg($keyword)." ".escapeshellarg($file);
	$output=null;
	$lastline=exec ( $command , $output );
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>Elisha System</title>
</head>
<body>
<?php
	echo "<div>".$command."</div>";
	echo "<hr>";
	echo "<div>";
	foreach ($output as $line) {
		echo "<p><pre>".$line."</pre></p>";
	}
	echo "</div>";
?>
</body>
</html>
<?php
	exit();
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Elisha System</title>
</head>
<body>
	<h1>Elisha System</h1>
	<h1><small>WE BEHOLD FROM FAR AWAY</small></h1>
	<hr>
	<form target="result_iframe">
		<h3>GREP TOOL</h3>
		<input type='hidden' name="act" value="search_filelock">
		KEYWORD:<input type="text" name="keyword"> &nbsp;&nbsp;
		DATE:<input type="text" name="date"> (EMPTY FOR TODAY, OR YYMMDD) &nbsp;&nbsp;
		<button>QUERY</button>
	</form>
	<hr>
	<iframe name="result_iframe" src="" style="width:90%;height:400px;margin:auto;"></iframe>
</body>
</html>
<?php

function getRequest($name,$default=null){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

?>