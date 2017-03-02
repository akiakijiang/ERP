<html>
<head>
	<title>Hit Mouse</title>
	<script lang="text/javascript">
		var score=0;
		function dishu_click(which){
			var dishu=document.getElementById(which);
			if(dishu.value=='X'){
				score=score+1;
			} else{
				alert("Final Score: "+score);
				score=0;
			}
			document.getElementById("score").innerHTML="Hit Mouses!<br>Score "+score;
			target=Math.random()*100;
			target=target.toFixed(0)%10+1;
			if(target<1 || target>9) target=5;
			for(var i=1;i<=9;i++){
				if(i==target)document.getElementById("dishu"+i).value="X";
				else document.getElementById("dishu"+i).value="O";
			}
		}
	</script>
	<SCRIPT TYPE="text/javascript">
			function getKey(e){ 
				e = e || window.event; 
				var keycode = e.which ? e.which : e.keyCode; 
				alert(keycode);
				if(keycode == 13 || keycode == 108){ //如果按下ENTER键 
					//在这里设置你想绑定的事件 
					//alert("ENTER");
					when_enter();
				} else if(keycode==17){
					when_ctrl();
				}
			} 

			// 把keyup事件绑定到document中 
			function listenKey() { 
				if (document.addEventListener) { 
					document.addEventListener("keyup",getKey,false); 
				} else if (document.attachEvent) { 
					document.attachEvent("onkeyup",getKey); 
				} else { 
					document.onkeyup = getKey; 
				} 
			} 
		</SCRIPT>
</head>
<body onload="listenKey();">
	<div style="margin:auto;width:190px;height:150px;" id="DS">
		<p style="text-align:center" id="score">Hit Mouses!<br>Score 0</p>
		<center>
		<table>
			<tr>
				<td>
					<input type="button" id="dishu1" value="O" onclick="dishu_click('dishu1');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu2" value="O" onclick="dishu_click('dishu2');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu3" value="O" onclick="dishu_click('dishu3');" style="width:40px;height:40px;font-size:18px;">
				</td>
			</tr>
			<tr>
				<td>
					<input type="button" id="dishu4" value="O" onclick="dishu_click('dishu4');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu5" value="X" onclick="dishu_click('dishu5');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu6" value="O" onclick="dishu_click('dishu6');" style="width:40px;height:40px;font-size:18px;">
				</td>
			</tr>
			<tr>
				<td>
					<input type="button" id="dishu7" value="O" onclick="dishu_click('dishu7');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu8" value="O" onclick="dishu_click('dishu8');" style="width:40px;height:40px;font-size:18px;">
				</td>
				<td>
					<input type="button" id="dishu9" value="O" onclick="dishu_click('dishu9');" style="width:40px;height:40px;font-size:18px;">
				</td>
			</tr>
		</table></center>
	</div>
</body>
</html>
