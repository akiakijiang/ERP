/* OUKOO脚本 */
function	ShowPhoto(id){
	var	Action			=	'ShowPhoto';
	var	url				= 	'Common.Controller.php';
	var	pars			= 	'Action='+Action+'&PhotoId='+id;
	$('Photo').innerHTML=	'<br><br><br><img src="themes/oukoo/image/loading.gif" border="0" ><br>请等待...';	
	var	InfoShowAjax	=	new Ajax.Request(url,{
		method:'get',
		parameters:pars,
		onComplete:function(r){
				var response			=	r.responseText;
				if(response){
				$('Photo').innerHTML	=	response;	
				}
			}
		}
	);	
}	

/* 选择地址改变 */
function	addressSelect(address_id){
	var	Action			=	'addressSelect';
	var	url				= 	'Cart.Controller.php';
	var	pars			= 	'Action='+Action+'&address_id='+address_id;
	showLoader();
	//$('Tx').innerHTML=	'<br><br><br><img src="themes/oukoo/image/loading.gif" border="0" ><br>请等待...';	
	var	InfoShowAjax	=	new Ajax.Request(url,{
		method:'get',
		parameters:pars,
		onComplete:function(r){
				var response			=	r.responseText;
				var responseInfo		=	eval('(' + response + ')');

				if(responseInfo){
					hideLoader();
					document.theForm['consignee'].value	=	responseInfo.info.consignee;
					document.theForm['tel'].value		=	responseInfo.info.tel;
					document.theForm['mobile'].value	=	responseInfo.info.mobile;
					document.theForm['country'].value	=	responseInfo.info.country;
					document.theForm['province'].value	=	responseInfo.info.province;
					document.theForm['city'].value		=	responseInfo.info.city;
					document.theForm['district'].value	=	responseInfo.info.district;
					document.theForm['address'].value	=	responseInfo.info.address;
					document.theForm['zipcode'].value	=	responseInfo.info.zipcode;	
					document.theForm['email'].value		=	responseInfo.info.email;

				}
			}
		}
	);	
}
