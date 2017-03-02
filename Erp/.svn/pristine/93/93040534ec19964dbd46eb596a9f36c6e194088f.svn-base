<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';

$command='egrep "^ *[^#].*$" /etc/cron.d/cron_*';
$output=array();
$lastline = exec ( $command , $output );

$cronjob_list=array();

foreach ($output as $line) {
	$pos=stripos ( $line , ':' );
	if($pos===false){
		continue;
	}
	$file=substr($line, 0, $pos);
	$script=substr($line,$pos+1);

	if(!isset($cronjob_list[$file])){
		$cronjob_list[$file]=array();
	}

	$parts = preg_split("/[\s]+/", $script);
	if($parts && count($parts)>5){
		$minute=$parts[0];
		$hour=$parts[1];
		$day=$parts[2];
		$month=$parts[3];
		$weekday=$parts[4];
		$cmd="";
		for ($i=5; $i < count($parts); $i++) { 
			$cmd.=$parts[$i].' ';
		}
		$cmd=trim($cmd);

		$matches=array();
		$cmd_yiic=preg_match('/(yiic\s+)(.+)$/', $cmd, $matches);

		if($cmd_yiic){
			$fin_cmd_log=$matches[2];

			$tpos=strpos($fin_cmd_log, '>>');
			if($tpos!==false){
				$fin_cmd=substr($fin_cmd_log, 0,$tpos);
				$fin_cmd=trim($fin_cmd);
				$fin_log=substr($fin_cmd_log, $tpos+2,strlen($fin_cmd_log)-$tpos-2);
				$fin_log=trim($fin_log);
			}else{
				$fin_log=$fin_cmd_log;
				$fin_log='';
			}

		}else{
			$fin_cmd=$cmd;
			$fin_log='';
		}

		// $cmd_log=preg_match('/(>>\s*)(.+)$/', $cmd, $matches);

		// if($cmd_log){
		// 	$fin_log=$matches[1];
		// }else{
		// 	$fin_log="";
		// }

		$cronjob_list[$file][]=array(
			'minute'=>$minute,
			'hour'=>$hour,
			'day'=>$day,
			'month'=>$month,
			'weekday'=>$weekday,
			'fin_cmd'=>$fin_cmd,
			'fin_log'=>$fin_log,
		);		
		
	}else{
		continue;
	}
}

if(isCommandLineInterface()){
	foreach ($cronjob_list as $file => $list) {
		echo "IN FILE [$file]".PHP_EOL;
		foreach ($list as $item) {
			extract($item);
			echo "Min[$minute] Hour[$hour] Day[$day] Month[$month] Weekday[$weekday] $fin_cmd >> $fin_log".PHP_EOL;
		}
	}
	echo "OSHIMAI";
	exit();
}

function isCommandLineInterface()
{
    return (php_sapi_name() === 'cli');
}


function changeCronParamToSet($str,$max,$min=0){
	if($str=='*'){
		return '*';
	}
	// */N => 0-max/N
	// A,B,C-D/N
	$s1=preg_replace('/\*/', $min.'-'.$max, $str);
	$s2=1;
	$div_pos=strpos($s1, '/');
	if($div_pos!==false){
		$s2=substr($s1, $div_pos+1,strlen($s1)-$div_pos-1);
		$s1=substr($s1, 0,$div_pos);
	}

	// echo "changeCronParamToSet($str,$max) : ";

	// echo "s1=$s1 s2=$s2 -> ";

	$set=array();

	$items=explode(',', $s1);
	foreach ($items as $a) {
		$b_pos=strpos($a, '-');
		if($b_pos===false){
			if($max>=$a && $a>=$min && $a % $s2 == 0){
				if($max==7 && $a==7){
					$a=0;
				}
				$set[$a]=$a;
			}
		}else{
			$b1=substr($a, 0,$b_pos);
			$b2=substr($a, $b_pos+1,strlen($a)-$b_pos-1);
			for ($c=$b1; $c <=$b2 ; $c++) { 
				if($max>=$c && $c>=$min && $c % $s2 == 0){
					if($max==7 && $c==7){
						$c=0;
					}
					$set[$c]=$c;
				}
			}
		}
	}

	// echo implode(' & ', $set).PHP_EOL;

	if(count($set)==$max-$min+1){
		return '*';
	}

	return $set;
}

function test_for_changeCronParamToSet(){
	changeCronParamToSet('1',60);
	changeCronParamToSet('1,3',60);
	changeCronParamToSet('1-5',60);
	changeCronParamToSet('1-5/2',60);
	changeCronParamToSet('*/2',60);
	changeCronParamToSet('1,2,7,8,10-20/2',60);
	die();
}

$now_month = date('n'); //1 到 12
$now_day = date('j');//1-31 月份中的第几天
$now_weekday = date('N'); //1-7 周中的第几天

$task_today=array();

?>
<html>
	<head>
		<title>当前定时任务</title>
		<style type="text/css">
		div.cronjob_list {
			margin: 10px;
		}
		div.cronjob_list h3 {
			background-color: lightgray;
		}
		div.cronjob_item {
			border: 1px solid gray;
			border-radius: 10px;
			padding: 10px;
		} 
		p.cronjob_time_line{
			color: black;
		}
		p.cronjob_cmd_line{
			color: blue;
		}
		p.cronjob_log_line{
			color: purple;
		}
		</style>
		<script type="text/javascript">
		function task_detail_switch(box_name){
			var boxes=document.getElementsByName(box_name); 
			for (var i = boxes.length - 1; i >= 0; i--) {
				var box=boxes[i];
				if(box.style.display=='none'){
					box.style.display='block';
				}else{
					box.style.display='none';
				}
			};
		}
		</script>
	</head>
	<body>
		<h1>当前有效的定时任务</h1>
		<hr>
		<div class="cronjob_list">
			<?php foreach ($cronjob_list as $file => $list) { ?>
			<h3>CLUSTER: <?php echo $file; ?></h3>
			<div>
				<?php 
				foreach ($list as $item) { 

					// Month
					$month_list=changeCronParamToSet($item['month'],12,1);
					// Day
					$day_list=changeCronParamToSet($item['day'],31,1);
					// Weekday
					$weekday_list=changeCronParamToSet($item['weekday'],7,0);
					// Hour
					$hour_list=changeCronParamToSet($item['hour'],23,0);
					// Minute
					$minute_list=changeCronParamToSet($item['minute'],59,0);
					
					echo "<div class='cronjob_item'>";

					echo "<p class='cronjob_time_line'>";

					if($month_list!='*'){
						echo implode(',', $month_list)."月";
					}elseif($day_list!='*'){
						echo "每月";
					}
					if($day_list!='*'){
						echo implode(',', $day_list)."日";
					}elseif($weekday_list=='*'){
						echo "每日";
					}
					if($weekday_list!='*'){
						echo "遇到周".implode(',', $weekday_list);
					}
					if($hour_list!='*'){
						echo implode(',', $hour_list)."时";
					}else{
						echo "每时";
					}
					if($minute_list!='*'){
						echo implode(',', $minute_list)."分";
					}else{
						echo "每分";
					}

					echo "</p>";
					echo "<p class='cronjob_cmd_line'>".$item['fin_cmd']."</p>";
					echo "<p class='cronjob_log_line'>".$item['fin_log']."</p>";

					echo "</div>";

					// LATER USE

					if(
						($month_list=='*' || in_array($now_month, $month_list))
						&&
						($day_list=='*' || in_array($now_day, $day_list))
						&& 
						($weekday_list=='*' || in_array($now_weekday, $weekday_list))
					){
						if($hour_list=='*'){
							$hour_list=array();
							for ($i=0; $i < 24; $i++) { 
								$hour_list[$i]=$i;
							}
						}
						if($minute_list=='*'){
							$minute_list=array();
							for ($i=0; $i < 60; $i++) { 
								$minute_list[$i]=$i;
							}
						}
						foreach ($hour_list as $h) {
							foreach ($minute_list as $m) {
								$task_today[$h][$m][]=array('fin_cmd'=>$item['fin_cmd'],'fin_log'=>$item['fin_log']);
							}
						}
					}
				} 
				?>
			</div>
			<?php } ?>
		</div>
		<div>
			<h2>分时间段</h2>
			<?php 
			ksort($task_today);
			foreach ($task_today as $h => $m_list) {
				echo "<div>";
				ksort($m_list);
				foreach ($m_list as $m => $tasks) {
					$r=128;
					$g=intval($h*255.0/24);
					$b=intval($m*255.0/60);
					$background_color='white;';//'rgb('.$r.','.$g.','.$b.');';
					echo "<div style='background-color:".$background_color." margin:10px;'>";
					echo "<p>";
					echo $h.':'.$m.' 合计 '.count($tasks).' 件。';
					echo "<button onclick='task_detail_switch(\"task_details_".$h."_".$m."\")'>Switch Details</button>";
					echo "</p>";
					foreach ($tasks as $task) {
						echo "<div style='display:none;' name='task_details_".$h."_".$m."'>";
						
						echo "<div style='float:left;width:50px;margin:2px;'>".$h."</div>";
						echo "<div style='float:left;width:50px;margin:2px;'>".$m."</div>";
						echo "<div style='float:left;width:700px;margin:2px;'>".$task['fin_cmd'].'&nbsp;&nbsp; &gt;&gt; &nbsp;&nbsp;'.$task['fin_log']."</div>";

						echo "<div style='clear: both;'></div>";
						echo "</div>";
					}
					echo "</div>";
				}
				echo "</div>";
			} 
			?>
		</div>
	</body>
</html>