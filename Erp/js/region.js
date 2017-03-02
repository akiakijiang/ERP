/* $Id : region.js 4865 2007-01-31 14:04:10Z paulgao $ */

var region = new Object();

region.isAdmin = false;
region.path = "./";

/**
 * 搜索地域名的链接
 */
region.search = function(region_name)
{
  Ajax.call('../admin/shipping_area.php', 'act=search&region_name=' + region_name, region.search_response, "GET", "TEXT");	
}

region.search_response = function(result, text_result) 
{
  document.getElementById("result").innerHTML = result;	
}

region.loadRegions = function(parent, type, target,page)
{
	var link = '';
	if(page == 'check'){
		link = 'type=' + type + '&target=' + target + "&parent=" + parent + '&page=' + page;
	}else{
		link = 'type=' + type + '&target=' + target + "&parent=" + parent;
	}
  Ajax.call(region.getFileName(), link , region.response, "POST", "JSON");
}

/* *
 * 载入指定的国家下所有的省份
 *
 * @country integer     国家的编号
 * @selName string      列表框的名称
 */
region.loadProvinces = function(country, selName)
{
  var objName = (typeof selName == "undefined") ? "selProvinces" : selName;

  region.loadRegions(country, 1, objName);
}

/* *
 * 载入指定的省份下所有的城市
 *
 * @province    integer 省份的编号
 * @selName     string  列表框的名称
 */
region.loadCities = function(province, selName)
{
  var objName = (typeof selName == "undefined") ? "selCities" : selName;

  region.loadRegions(province, 2, objName);
}

/* *
 * 载入指定的城市下的区 / 县
 *
 * @city    integer     城市的编号
 * @selName string      列表框的名称
 */
region.loadDistricts = function(city, selName)
{
  var objName = (typeof selName == "undefined") ? "selDistricts" : selName;

  region.loadRegions(city, 3, objName);
}

/* *
 * 处理下拉列表改变的函数
 *
 * @obj     object  下拉列表
 * @type    integer 类型
 * @selName string  目标列表框的名称
 */
region.changed = function(obj, type, selName,page)
{
  var parent = obj.options[obj.selectedIndex].value;
  region.loadRegions(parent, type, selName,page);
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
}

region.response = function(result, text_result)
{
  var sel = document.getElementById(result.target);
  
  sel.length = 1;
  sel.selectedIndex = 0;
  sel.style.display = (result.regions.length == 0 && ! region.isAdmin && result.type + 0 == 3) ? "none" : '';
  var selectedIndex = -1;
  
  if (result.regions)
  {
    for (i = 0; i < result.regions.length; i ++ )
    {
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
      if (sel.cacheValue &&  sel.cacheValue == opt.value) {
        selectedIndex = i;
      }
    }
  }
  
  if (selectedIndex >= 0) sel.value = sel.cacheValue;
  sel.cacheValue  = null;
  if (document.all)
  {
    sel.fireEvent("onchange");
  }
  else
  {
    var evt = document.createEvent("HTMLEvents");
    evt.initEvent('change', true, true);
    sel.dispatchEvent(evt);
  }
}

region.getFileName = function(){
  if (region.isAdmin){
    return region.path + "ajax.php?act=get_regions";
  }else{
    return region.path + "ajax.php?act=get_regions";
  }
}
