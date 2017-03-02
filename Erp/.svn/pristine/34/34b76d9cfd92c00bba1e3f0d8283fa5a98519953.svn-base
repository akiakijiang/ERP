// JavaScript Document
var navUserAgent 	= navigator.userAgent;
var isie 	= !(navUserAgent.indexOf("Firefox") >= 0 || navUserAgent.indexOf("Opera") >= 0);
var o		=	'bg';
var tips; var theTop = 250/*这是默认高度,越大越往下*/; var old = theTop;


function setOpacity(obj, opacity){
	opacity = (opacity == 100)?99.999:opacity;
	obj.style.filter = "alpha(opacity:"+opacity+")";
	obj.style.KHTMLOpacity = opacity/100;
	obj.style.MozOpacity = opacity/100;
	obj.style.opacity = opacity/100;
}

function tbdisabled(){
	document.getElementById(o).style.top='0px';
	document.getElementById(o).style.left='0px';
	document.getElementById(o).style.width='100%';
	document.getElementById(o).style.height=(isie ? document.documentElement.scrollHeight : document.documentElement.scrollHeight)+'px';
	document.getElementById(o).style.display='block';
	setOpacity(document.getElementById(o), 60);
}

function initFloatTips(IdName) {
	var	IdName	=	IdName.toString();
 	tips = document.getElementById(IdName);
  	moveTips();
};


function moveTips() {
  var tt=50;
  if (window.innerHeight) {
    pos = window.pageYOffset
  }
  else if (document.documentElement && document.documentElement.scrollTop) {
    pos = document.documentElement.scrollTop
  }
  else if (document.body) {
    pos = document.body.scrollTop;
  }
  pos=pos-tips.offsetTop+theTop;
  pos=tips.offsetTop+pos/10;
  
  if (pos < theTop) pos = theTop;
  if (pos != old) {
    tips.style.top = pos+"px";
    tt=10;
  }
  old = pos;
  setTimeout(moveTips,tt);
}

function tbclose(){	
	document.getElementById('QuestionInfoShow').style.display='none';
	document.getElementById(o).style.display='none';
}
document.write('<div id="'+o+'"  ondblclick="javascript:tbclose();"></div>');