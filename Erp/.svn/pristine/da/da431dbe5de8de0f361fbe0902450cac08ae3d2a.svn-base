/**
 * @projectDescription ouku对象库，规定常用对象。
 * @author mzhou mzhou@ouku.com
 * @version 0.1
 */
(function(){
	window.ouku = {
		path : {
			/** 
			 * 定义网站跟目录
			 * @alias ouku.path.root
			 */
			root : web_root,
      /** 
       * 定义当前网页地址
       * @alias ouku.path.uri
       */			
			uri : request_uri,
      /** 
       * 定义网站图片地址
       * @alias ouku.path.img
       */   			
			img  : web_root + 'themes/ouku/' 
		},
		ajax : {
			/**
			 * 简化ajax调用
			 * @alias ouku.ajax.call
			 * @param {Object} transferUrl  传递参数的url
			 * @param {Object} params       传递的参数，get方式下可以采用&id=1&type=2，post方式下采用对象传递
			 * @param {Object} callback     回调函数
			 * @param {Object} transferMode 传递的方式 get,post
			 * @param {Object} responseType 数据类型，xml，html，script，json，jsonp，text
			 * @param {Object} isShow            是否显示ajax调用提示信息，默认显示
			 * @param {Boolen} cacheTrue    是否缓存 true or false
			 */
			call : function(transferUrl, params, callback, transferMode, responseType, isShow, cacheTrue){
				var show = '', hide = '';
				cacheTrue = cacheTrue ? cacheTrue : false;
				transferMode = transferMode.toLowerCase();
				responseType = responseType.toLowerCase();
				if(typeof(isShow) == 'string'){
          show = '';
					hide = '';
				}else if(typeof(isShow) == 'object'){
          show = isShow.showLoader;
          hide = isShow.hideLoader;					
				}else{
          show = this.showLoader;
          hide = this.hideLoader;       					
				};
				$.ajax({
	        url: transferUrl,
	        data: params,
	        beforeSend: show,
	        complete: hide,
	        success: callback,
	        type: transferMode,
	        dataType: responseType,
	        cache: cacheTrue
	      });
			},
      /**
       * 显示ajax调用信息
       * @alias ouku.ajax.showLoader
       */
			showLoader : function ()
			{
			  $('body').append('<div id="loader">正在处理您的请求...</div>');
			},
      /**
       * 去除ajax调用信息
       * @alias ouku.ajax.hideLoader
       */
			hideLoader : function()
			{
			   $('#loader').remove();
			}
		},
		cookie : {
			/**
			 * 创建cookie
			 * @alias ouku.cookie.create
			 * @param {String} name  cookie的名字
			 * @param {String} value cookie的值
			 * @param {Number} days  cookie的有效时间
			 */
			create : function(name,value,days){
        if(days){
          var date = new Date();
				  date.setTime(date.getTime()+(days*24*60*1000));
				  var expires = "; expires="+date.toGMTString();
        }else{
					var expires = "";
				};
					document.cookie = name+"="+encodeURIComponent(value)+expires+"; path=/";	
			},
			/**
			 * 读取cookie信息
			 * @param {Object} name cookie的名字
			 * @return {String} 返回cookie里面的内容
			 */
			read : function(name){
        var aCookie = document.cookie.split("; ");
        for (var i=0; i < aCookie.length; i++){
				  var aCrumb = aCookie[i].split("=");
					if (name == aCrumb[0]){
            return decodeURIComponent(aCrumb[1]);
					}
				};
					return null;
		  },
			/**
			 * 删除cookie
			 * @param {Object} name cookie的名字
			 */
			del : function(name){
        this.create(name,"",-1);
		  }
		},
		date : {
			/**
			 * 按星期计算，取得当前的星期数，0 - 6
			 * @alias ouku.date.day
			 */		
			day : new Date().getDay(),
      /**
       * 24小时制，取得当前的小时
       * @alias ouku.date.hours
       */ 			
			hours : new Date().getHours(),
      /**
       * 60分钟，取得当前的分数
       * @alias ouku.date.minutes
       */   			
			minutes : new Date().getMinutes(),
			/**
			 * getTime() 方法可返回距 1970 年 1 月 1 日之间的毫秒数。
			 * @alias ouku.date.milliseconds
			 */
			milliseconds : new Date().getTime() 
		},
		validate : {
			is_ok : true,
			/**
			 * 判断 表单 值是否为空
			 * @alias ouku.validate.is_empty
			 * @param {Object} id   input父级的id
 			 * @param {Object} name input的name
			 * @param {Object} msg  弹出的提示信息
			 */
			is_empty : function(id, name, msg){
				if($('#'+id+' input[name="'+name+'"]').val() == ''){
					alert(msg);
					this.is_ok = false;
					$('#'+id+' input[name="'+name+'"]').focus();
				}else{
					this.is_ok = true;
				}
				return this.is_ok;
			},
			is_selected : function(id, name, msg){
				if ($('#'+id+' select[name="' + name + '"]').css('display') != 'none') {
					if ($('#'+id+' select[name="' + name + '"] option:eq(0)').attr('selected') == true) {
						alert(msg);
						this.is_ok = false;
					}
					else {
						this.is_ok = true;
					}
			  }
				return this.is_ok;			
		  },
			/**
			 * 判断是否是邮编
			 * @alias ouku.validate.is_zip
       * @param {Object} id   input父级的id
       * @param {Object} name input的name
       * @param {Object} msg  弹出的提示信息
			 */
			is_zip : function(id, name, msg){
			  if(! /^\d{6}$/.test($('#'+id+' input[name="'+name+'"]').val())){
			    alert(msg);
			    this.is_ok = false;
					$('#'+id+' input[name="'+name+'"]').focus();
			  }else{
					this.is_ok = true;
				}
				return this.is_ok;
			},
			/**
			 * 判断是否手机号码
			 * @alias ouku.validate.is_phone
			 * @param {Object} id   input父级的id
			 * @param {Object} name input的name
			 * @param {Object} msg  弹出的提示信息
			 */
			is_phone : function(id, name, msg){
				if(! /1\d{10}$/.test($('#'+id+' input[name="'+name+'"]').val())){
			    alert(msg);
			    this.is_ok = false;
					$('#'+id+' input[name="'+name+'"]').focus();					
				}else{
			    this.is_ok = true;
			  }
				return this.is_ok;
			},
			/**
			 * 判断email格式
			 * @alias ouku.validate.is_email
			 * @param {Object} id   input父级的id
			 * @param {Object} name input的name
			 * @param {Object} msg  弹出的提示信息
			 */
			is_email : function(id, name, msg){
				if(! /@/.test($('#'+id+' input[name="'+name+'"]').val())){
			    alert(msg);
			    this.is_ok = false;
			    $('#'+id+' input[name="'+name+'"]').focus();          
			  }else{
			    this.is_ok = true;
			  }
			    return this.is_ok;					
			},
      /**
       * 判断email格式
       * @alias ouku.validate.is_same
       * @param {Object} id    input父级的id
       * @param {Object} name1 需要对比input的name1
       * @param {Object} name2 需要对比input的name2
       * @param {Object} msg   弹出的提示信息
       */			
			is_same : function(id, name1, name2, msg){
			  if($('#'+id+' input[name="'+name1+'"]').val() != $('#'+id+' input[name="'+name2+'"]').val()){
			    alert(msg);
			    this.is_ok = false;
			    $('#'+id+' input[name="'+name2+'"]').focus();          
			  }else{
			    this.is_ok = true;
			  }
			    return this.is_ok;    				
			},
			/**
			 * 判断input值的长度
			 * @alias ouku.validate.is_size
			 * @param {Object} id   input父级的id
			 * @param {Object} name input的name
			 * @param {Object} size 给定的长度
			 * @param {Object} msg  弹出的提示信息
			 */
			is_size : function(id, name, size, msg){
				if ($('#' + id + ' input[name="' + name + '"]').val().length != size) {
			    alert(msg);
			    this.is_ok = false;
					$('#' + id + ' input[name="' + name + '"]').focus();       
			  }else{
			    this.is_ok = true;
			  }
			    return this.is_ok;  
			},
			is_checked : function(id,name){
				var is_ok = true;
				$('#'+id+' input[name="'+name+'"]').each(function(i){
					if($(this).attr('checked') == true){
						is_ok = false;
						return false;
					}
				});
				this.is_ok = is_ok;
				return this.is_ok;
			}
		},
		position : {
			/**
			 * 兼容浏览器取得滚动条滚动高度
			 * @alias ouku.position.sTop
			 */
			sTop : function(){return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0},
      /**
       * 兼容浏览器取得滚动条滚动长度
       * @alias ouku.position.sLeft
       */			
			sLeft : function(){return window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0}	
		},
    /**
     * 格式化价格
     * @alias ouku.format_price
     * @param {Object} price 价格
     */
		format_price : function(price) {
			var text = "" + (parseFloat(price) + 0.005);
			if (text.indexOf(".") == 0){
				text = "0" + text;
			}else if(text.indexOf(".") > 0){
				text = text + "00";
			}else{
				text = text + ".00";
			};
			var index = text.indexOf(".");
			text = text.substring(0, index + 3);
		
			if(text.charAt(text.indexOf(".")+1) == '0' && text.charAt(text.indexOf(".") + 2) == '0'){
			  text = text.substring(0, text.indexOf("."));
			};
			return text;
		},
		region:{
			/**
			 * 使用ajax调用区域数据
			 * @alias ouku.region.loadRegions
			 * @param {Object} parent 所在区域的上级 字段对应 parent_id
			 * @param {Object} type   区域，国家 0，省1，市2，县3 字段对应 region_type
			 * @param {Object} target 下级区域联动 id
			 * @param {Object} page   判断是否有自提点
			 */
			loadRegions : function(parent, type, target, page){
			  var link = '';
			  if(page == 'check'){
			    link = 'type=' + type + '&target=' + target + "&parent=" + parent + '&page=' + page;
			  }else{
			    link = 'type=' + type + '&target=' + target + "&parent=" + parent;
			  }
			  window.ouku.ajax.call(window.ouku.path.root + "region.php", link , this.response, "get", "json");
			},
			/**
			 * 载入国家里的所有省、直辖市
			 * @alias ouku.region.loadProvinces
			 * @param {Object} country 国家的编号
			 * @param {Object} selName 列表框的name
			 */
			loadProvinces : function(country, selName){
			  var objName = (typeof selName == "undefined") ? "selProvinces" : selName;
			  this.loadRegions(country, 1, objName);
			},
			/**
			 * 载入指定的省份下所有的城市
			 * @alias ouku.region.loadCities
			 * @alias ouku.region.loadCities
			 * @param {Object} province  省的编号
			 * @param {Object} selName   列表框的名字
			 */
			loadCities : function(province, selName){
			  var objName = (typeof selName == "undefined") ? "selCities" : selName;
			  this.loadRegions(province, 2, objName);
			},
			/**
			 * 载入指定的城市下的区 / 县
			 * @alias ouku.region.loadDistricts
			 * @param {Object} city    市的编号
			 * @param {Object} selName 列表框的名字
			 */
			loadDistricts : function(city, selName){
			  var objName = (typeof selName == "undefined") ? "selDistricts" : selName;			
			  this.loadRegions(city, 3, objName);
			},
			/**
			 * 处理下拉框触发函数
			 * @param {Object} obj     触发的对象
			 * @param {Object} type    区域，region_type
			 * @param {Object} selName 列表框的name
			 * @param {Object} page    是否自提
			 */
			changed : function(obj, type, selName, page){
				if(typeof(obj) == 'string'){
					obj = document.getElementById(obj);
				}
			  var parent = obj.options[obj.selectedIndex].value;
			  this.loadRegions(parent, type, selName, page);
			  if (page == "check") {
			    var opts = obj.options;
			    for (var i = 0; i < opts.length; i++) {
			      if (opts[i].getAttribute('self') == 1 && opts[i].selected == true) {
			        obj.style.color = "red";
			      }else if (opts[i].getAttribute('self') != 1 && opts[i].selected == true) {
			          opts[i].style.color = "#000";
			          obj.style.color = "#000";
			      }else if (opts[i].getAttribute('self') != 1) {
			            opts[i].style.color = "#000";
			      }
			    }
			  }
			},
			/**
			 * 回调函数，返回数据
			 * @alias ouku.region.response
			 * @param {Object} result      返回ajax调用的数据
			 * @param {Object} text_result
			 */
			response : function(result, text_result){
			  var sel = document.getElementById(result.target);
//				alert(result.target);
			  sel.length = 1;
			  sel.selectedIndex = 0;
			  sel.style.display = (result.regions.length == 0 && result.type + 0 == 3) ? "none" : '';
			  var selectedIndex = -1;
			  
			  if (result.regions){
//					alert(sel.cacheValue);
			    for (i = 0; i < result.regions.length; i ++ ){
			      var opt = document.createElement("OPTION");
			      opt.value = result.regions[i].region_id;
			      opt.text  = result.regions[i].region_name;
			      if(result.page == 'check'){
			        if (result.regions[i].self == 1) {
			          opt.style.color = 'red';
			          opt.setAttribute('self',1);
			        }else{
			          opt.style.color = '#000';
			          opt.setAttribute('self',0);
			        }
			      } 
			      sel.options.add(opt);
//						alert(sel.cacheValue);
			      if (sel.cacheValue &&  sel.cacheValue == opt.value) {
			        selectedIndex = i;
			      }
			    }
			  }
			  if (selectedIndex >= 0) sel.value = sel.cacheValue;
			  sel.cacheValue  = null;
			  if (document.all){
			    sel.fireEvent("onchange");
			  }
			  else{
			    var evt = document.createEvent("HTMLEvents");
			    evt.initEvent('change', true, true);
			    sel.dispatchEvent(evt);
			  }
			}
		}			
	};
})();
/**
 * 为数组添加删除数据函数
 * @alias Array.remove
 * @param {Object} m  数组数据所在的位置
 */
Array.prototype.remove = function(m){
	if(m<0){
		return this;
	}else{
		return this.splice(m,1);
	}
}
