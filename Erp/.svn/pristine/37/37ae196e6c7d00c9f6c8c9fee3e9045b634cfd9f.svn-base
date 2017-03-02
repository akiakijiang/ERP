// JavaScript Document

var dialog_config = ['oDiv', 'jump']
function showDialog(msg, url, num) {
	var maskId = dialog_config[0];
	var jumpId = dialog_config[1];
	var numC = num;
	initConfirmDiv(maskId, jumpId, numC);
	if((typeof(msg) == "string" || msg instanceof String)){
		document.getElementById('dialog_msg').innerHTML = msg;
	}
	else{
		document.getElementById('dialog_msg').appendChild(msg);
	}
	jump(maskId, jumpId);
	if (url) {
		if(parseInt(num) == 3){
			urls = url.split("|");
			document.form_cancel.order_sn.value = urls[0];
			if (urls[1])
				document.form_cancel.bak_order_sn.value = urls[1];
		} else if (parseInt(num) == 5) {
			document.form_goods_inform.style_id.value = url;
		} else{
			var gogo = document.getElementById("gogo");
			gogo.onclick = function(){
				local(url);
			}
		}
	}
}


function closeDialog() {
	var maskId = dialog_config[0];
	var jumpId = dialog_config[1];
	//initDialogDiv(maskId, jumpId);

	closeDiv(maskId, jumpId);
}
function hideDialog(){
	var maskId = dialog_config[0];
	var jumpId = dialog_config[1];
	var maskDiv = document.getElementById(maskId);
	var jumpDiv = document.getElementById(jumpId);
	jumpDiv.style.display = "none";
	maskDiv.style.display = "none";

}
function jump(maskId, jumpId) {
	var maskDiv = document.getElementById(maskId);
	var sHeight = document.documentElement.scrollHeight;
	var cHeight = document.documentElement.clientHeight;
	var oselect = document.getElementsByTagName("select");
	var jumpDiv = document.getElementById(jumpId);
	if(oselect){
		for(i = 0; i < oselect.length; i++){
			oselect[i].style.visibility = "hidden";
		}
	}
	function po() {
		jumpDiv.style.top = document.documentElement.scrollTop + document.documentElement.clientHeight/3+ "px";
		jumpDiv.style.left = document.documentElement.clientWidth/2.5+"px";
	}
	po();
	window.onscroll=po;
	window.onresize=po;
	maskDiv.style.display = "block";
	jumpDiv.style.display = "block";
	if(sHeight >= cHeight){
		maskDiv.style.height = sHeight+"px";
	}else{
		maskDiv.style.height = cHeight+"px";
	}
	var oGogo = document.getElementById('gogo');
	oGogo.focus();
}

function closeDiv(maskId, jumpId) {
	var oGogo = document.getElementById('gogo');
	oGogo.blur();
	var oselect = document.getElementsByTagName("select");
	if(oselect){
		for(i = 0; i < oselect.length; i++){
			oselect[i].style.visibility = "visible";
		}
	}
	var dialogMsg = document.getElementById('dialog_msg');
	var frm = document.forms['formEdit'];
	if(dialogMsg.innerHTML == "请输入您的真实姓名！"){
		frm.user_realname.focus();
	}
	if(dialogMsg.innerHTML == "您输入的省市不对！"){
		frm.province.focus();
	}
	if(dialogMsg.innerHTML == "您输入的邮编格式不对！"){
		frm.zipcode.focus();
	}
	if(dialogMsg.innerHTML == "您输入的手机号码格式不对！"){
		frm.user_mobile.focus();
	}	
	var maskDiv = document.getElementById(maskId);
	var jumpDiv = document.getElementById(jumpId);
	jumpDiv.style.display = "none";
	maskDiv.style.display = "none";
	maskDId = document.getElementById("maskD");
	document.body.removeChild(maskDId);
}

function local(hrefUrl) {
	window.location.href = hrefUrl;
}

function initConfirmDiv(maskId, jumpId, num) {
	if (document.getElementById(maskId) != null) {
		return true;
	}
	var oDiv = document.createElement("DIV");
	oDiv.id = "maskD";
	document.body.appendChild(oDiv);

	switch(parseInt(num)) {
		case 1:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
		'<h2>欧酷购物提示</h2><div class="border_style">'+
		'<p id="dialog_msg"></p>'+
		'<p style="padding-left:60px;padding-top:10px;"><button onclick="closeDiv(\'oDiv\', \'jump\')" class="button9" id="gogo">进入购物车</button><button onclick="closeDiv(\'oDiv\', \'jump\');location.reload();" class="button9">继续购物</button></p>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;
		case 2:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
		'<h2>欧酷购物提示</h2><div class="border_style">'+
		'<p id="dialog_msg"></p>'+
		'<p style="padding-left: 120px;padding-top:10px;"><button onclick="closeDiv(\'oDiv\', \'jump\');" class="button8" id="gogo">确定</button><button onclick="closeDiv(\'oDiv\', \'jump\')" class="button1" >取消</button></p>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;
		case 3:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
		'<h2>欧酷购物提示</h2><div class="border_style">'+
		'<div id="dialog_msg"></div>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;
		case 4:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
		'<h2>欧酷购物提示</h2><div class="border_style">'+
		'<div id="dialog_msg"></div>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;
		/*case 5:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
		'<h2>欧酷网到货通知内容填写</h2><div class="border_style">'+
		'<div id="dialog_msg"></div>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;*/
		case 5:
			oDiv.innerHTML = '<div id="jump" class="thumb_box" style="display: none;">'+
		'<div class="thumb_bar"><em class="l"></em><em class="r"></em><a href="#" onclick="closeDiv(\'oDiv\', \'jump\');" title="关闭"></a><h3>欧酷网到货通知内容填写</h3>'+
    '</div><div id="dialog_msg"><div class="thumb_content"></div></div>'+
		'</div></div>'+
		'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		break;
		default:
			oDiv.innerHTML = '<div id="jump" style="display: none;">'+
	'<h2>欧酷购物提示</h2><div class="border_style">'+
	'<p id="dialog_msg"></p>'+
	'<p style="padding-left: 150px;padding-top:10px;"><button onclick="closeDiv(\'oDiv\', \'jump\');" class="button8" id="gogo">确定</button></p>'+
	'</div></div>'+
	'<div id="oDiv" style="display: none;" ondblclick="closeDiv(\'oDiv\', \'jump\')"></div>';
		;
	}

}
