<?php
define('IN_ECS', true);
require_once('../includes/init.php');

$alive_only=queryChecker('alive_only',0);

$workers=getHitoList($alive_only);

$board_height=(100+20*count($workers));
$board_width_mirai=100;
$board_width=$board_width_mirai+queryChecker('timeline_width',1000);

function queryChecker($name,$default){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

function getHitoList($alive_only=false){
	global $db;
	$cond="";
	if($alive_only){
		$cond.=" AND status='OK' ";
	}
	$sql="select concat('{name: \'', user_name, '\', real_name: \'', real_name, '\', join: ', UNIX_TIMESTAMP(join_time), ', last: ', UNIX_TIMESTAMP(last_time), ', status: \'', status, '\'}' ) from ecshop.ecs_admin_user where 1 {$cond} order by join_time";
	$list=$db->getCol($sql);
	// print_r($list);
	return $list;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Chart</title>
		<script type="text/javascript">
		var SinriDrawer = {
			board: null,
			setCanvas2d: function (canvas_id){
				this.board = document.getElementById("myCanvas").getContext("2d");
			},
			setFillColor: function (color){
				this.board.fillStyle=color;
			},
			setStrokeColor: function (color){
				this.board.strokeStyle=color;
			},
			//Rect
			fillRect: function (color,x,y,w,h){
				this.board.fillStyle=color;
				this.board.fillRect(x,y,w,h);
			},
			strokeRect: function (color,x,y,w,h){
				this.board.strokeStyle=color;
				this.board.strokeRect(x,y,w,h);
			},
			//Line
			strokeLine: function (x_s,y_s,x_e,y_e){
				this.board.moveTo(x_s,y_s);
				this.board.lineTo(x_e,y_e);
				this.board.stroke();
			},
			//Circle
			strokeCircle: function (x,y,r){
				this.board.beginPath();
				this.board.arc(x,y,r,0,2*Math.PI);
				this.board.stroke();
			},
			//Text
			fillText: function (color,font,text,x,y){
				this.board.fillStyle=color;
				this.board.font = font;//"30px Arial";
				this.board.fillText(text,x,y);
			},
			strokeText: function (color,font,text,x,y){
				this.board.strokeStyle=color;
				this.board.font = font;//"30px Arial";
				this.board.strokeText(text,x,y);
			},
			//Gradient
			makeLinearGradient: function (gxs,gys,color_s,stop_s,gxe,gye,color_e,stop_e){
				// Create gradient
				var grd = this.board.createLinearGradient(gxs,gys,gxe,gye);
				grd.addColorStop(stop_s,color_s);
				grd.addColorStop(stop_e,color_e);
				return grd;
			},
			drawLinearGradient: function (x,y,w,h,grd){
				// Fill with gradient
				this.board.fillStyle = grd;
				this.board.fillRect(x,y,w,h);
			},
		}
		</script>
	</head>
	<body>
		<h1>Data Visual Test</h1>
		<p>This is an experimental page for HTML Canvas and Data Visualization.</p>
		<div id="the_draw_board">
			<canvas id="myCanvas" width="<?php echo $board_width; ?>" height="<?php echo $board_height; ?>" style="border:1px solid #000000;">
			</canvas>
		</div>
	</body>
	<script type="text/javascript">
		SinriDrawer.setCanvas2d('myCanvas');
		// SinriDrawer.fillRect("#FFFF00",0,0,150,75);

		//draw timeline

		var last_point_time=Math.floor( new Date().getTime() / 1000 );
		var first_point_time=Math.floor( new Date('2007-01-01 00:00:00').getTime() / 1000 );

		SinriDrawer.strokeLine(0,30,<?php echo $board_width; ?>,30);		

		var list=[
			<?php 
			echo implode(','.PHP_EOL, $workers);
			?>
		];
		for(i=0;i<list.length;i++){
			start=(list[i].join-first_point_time)/((last_point_time-first_point_time)/<?php echo ($board_width-$board_width_mirai); ?>);
			if(start<=0)start=0;
			end=(list[i].last-first_point_time)/((last_point_time-first_point_time)/<?php echo ($board_width-$board_width_mirai); ?>);
			if(end<=0)end=start;
			if(list[i].status=='DISABLED'){
				color="#FF0000";
			}else{
				color="#00FF00";
				end=(last_point_time-first_point_time)/((last_point_time-first_point_time)/<?php echo ($board_width-$board_width_mirai); ?>);
			}
			
			SinriDrawer.fillRect(color,start,50+i*20+1,end-start,18);
			SinriDrawer.fillText('black','10px Arial',list[i].real_name+"("+list[i].name+")",start+5,50+i*20+15);
		}

		for(i=0;i<=<?php echo ($board_width-$board_width_mirai); ?>;i=i+50){	
			if(i%100==0){
				unixTimestamp = new Date((first_point_time+(last_point_time-first_point_time)/<?php echo ($board_width-$board_width_mirai); ?>*i) * 1000) ;
				// commonTime = unixTimestamp.toLocaleString();
				commonTime = unixTimestamp.getFullYear()+"-"+unixTimestamp.getMonth()+"-"+unixTimestamp.getDay();
				SinriDrawer.fillText('black','10px',commonTime,i,20);
				SinriDrawer.strokeCircle(i,30,4);
				SinriDrawer.setStrokeColor('#CCCCCC');
				SinriDrawer.strokeLine(i,30,i,<?php echo $board_height; ?>);
			}else{
				SinriDrawer.strokeCircle(i,30,2);
			}
		}
	</script>
</html>