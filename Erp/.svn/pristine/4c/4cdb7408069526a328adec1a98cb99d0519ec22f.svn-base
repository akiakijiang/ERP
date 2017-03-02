/* $Id: listtable.js 8049 2007-04-10 02:54:11Z scottye $ */
if (typeof Ajax != 'object')
{
  alert('Ajax object doesn\'t exists.');
}

if (typeof Utils != 'object')
{
  alert('Utils object doesn\'t exists.');
}

var listTable = new Object;

listTable.query = "query";
listTable.filter = new Object;
listTable.url = location.href.substring((location.href.lastIndexOf("/")) + 1, location.href.lastIndexOf("?")) + "?is_ajax=1";

/**
 * 创建一个可编辑区
 */
listTable.edit = function(obj, act, id, type, name)
{
  var tag = obj.firstChild.tagName;
	var name = name ? name : '';
  if (typeof(tag) != "undefined" && (tag.toLowerCase() == "input" || tag.toLowerCase() == "textarea"))
  {
    return;
  }
  /* 保存原始的内容 */
  var org = obj.innerHTML;
//  var val = Browser.isIE ? obj.innerText : obj.textContent;
  var val = obj.innerHTML;
  /* 创建一个输入框 */
	type = type ? type : 'input';
  if (type == 'input') {
  	var txt = document.createElement("INPUT");
  }else{
		var txt = document.createElement("textarea");
	}	
  
  txt.value = (val == 'N/A') ? '' : val;
	if (val == '未填') {
  	txt.style.width = 300 + "px";
  	if (type == 'textarea') 
  		txt.style.height = 50 + "px";
  }
  else {
  	txt.style.width = (obj.offsetWidth + 50) + "px";
  	if (type == 'textarea') 
  		txt.style.height = (obj.offsetHeight + 30) + "px";
  }
  /* 隐藏对象中的内容，并将输入框加入到对象中 */
  obj.innerHTML = "";
  obj.appendChild(txt);
  txt.focus();

  /* 编辑区输入事件处理函数 */
  txt.onkeypress = function(e)
  {
    var evt = Utils.fixEvent(e);
    var obj = Utils.srcElement(e);

    if (evt.keyCode == 13)
    {
      obj.blur();

      return false;
    }

    if (evt.keyCode == 27)
    {
      obj.parentNode.innerHTML = org;
    }
  }

  /* 编辑区失去焦点的处理函数 */
  txt.onblur = function(e)
  {
    if (Utils.trim(txt.value).length > 0)
    {
      res = Ajax.call(listTable.url, "act="+act+"&name="+encodeURIComponent(Utils.trim(name))+"&val=" + encodeURIComponent(Utils.trim(txt.value)) + "&id=" +id, null, "POST", "JSON", false);

      if (res.message)
      {
        alert(res.message);
      }

      obj.innerHTML = (res.error == 0) ? res.content : org;
    }
    else
    {
      obj.innerHTML = org;
    }
  }
}

/**
 * 编辑select
 */
listTable.editselect = function(obj, act, id, names, values, default_value, width)
{
  var tag = obj.firstChild.tagName;
  
  if (typeof(tag) != "undefined" && tag.toLowerCase() == "select")
  {
    return;
  }
  /* 保存原始的内容 */
  var org = obj.innerHTML;
  
  /* 创建一个输入框 */
  var sel = document.createElement("select");

  
  for (var i = 0; i < values.length; i++) {
  	var opt = document.createElement("option");
  	opt.value = values[i];
  	opt.innerHTML = names[i];
  	sel.appendChild(opt);  
  }
  
  sel.value = default_value;
  sel.style.width = width ;
  
  obj.innerHTML = "";
  obj.appendChild(sel);
  sel.focus();
  
  sel.onchange = function(e)
  {
    res = Ajax.call(listTable.url, "act="+act+"&val=" + sel.value + "&id=" +id, null, "POST", "JSON", false);

    if (res.message)
    {
      alert(res.message);
    }
    
    for (var i = 0; i < values.length; i++) {
    	if (values[i] == sel.value) {
    		org = names[i];
    		break;
    	}
    }
    
    this.focus();
  }
  
  sel.onblur = function(e)
  {
  	obj.innerHTML = org;
  }
}

/**
 * 切换状态
 */
listTable.toggle = function(obj, act, id)
{
  var val = (obj.src.match(/yes.gif/i)) ? 0 : 1;

  var res = Ajax.call(this.url, "act="+act+"&val=" + val + "&id=" +id, null, "POST", "JSON", false);

  if (res.message)
  {
    alert(res.message);
  }

  if (res.error == 0)
  {
    obj.src = (res.content > 0) ? 'images/yes.gif' : 'images/no.gif';
  }
}

/**
 * 切换排序方式
 */
listTable.sort = function(sort_by, sort_order)
{
  var args = "act="+this.query+"&sort_by="+sort_by+"&sort_order=";

  if (this.filter.sort_by == sort_by)
  {
    args += this.filter.sort_order == "DESC" ? "ASC" : "DESC";
  }
  else
  {
    args += "DESC";
  }

  for (var i in this.filter)
  {
    if (typeof(this.filter[i]) != "function" &&
      i != "sort_order" && i != "sort_by" && !Utils.isEmpty(this.filter[i]))
    {
      args += "&" + i + "=" + this.filter[i];
    }
  }

  this.filter['page_size'] = this.getPageSize();

  Ajax.call(this.url, args, this.listCallback, "POST", "JSON");
}

/**
 * 翻页
 */
listTable.gotoPage = function(page)
{
  if (page != null) this.filter['page'] = page;

  if (this.filter['page'] > this.pageCount) this.filter['page'] = 1;

  this.filter['page_size'] = this.getPageSize();

  this.loadList();
}

/**
 * 载入列表
 */
listTable.loadList = function()
{
  var args = "act="+this.query+"" + this.compileFilter();
  
  Ajax.call(this.url, args, this.listCallback, "POST", "JSON");
}

/**
 * 删除列表中的一个记录
 */
listTable.remove = function(id, cfm, opt)
{
  if (opt == null)
  {
    opt = "remove";
  }
  if (confirm(cfm))
  {
    var args = "act=" + opt + "&id=" + id + this.compileFilter();

    Ajax.call(this.url, args, this.listCallback, "GET", "JSON");
  }
}

listTable.gotoPageFirst = function()
{
  if (this.filter.page > 1)
  {
    listTable.gotoPage(1);
  }
}

listTable.gotoPagePrev = function()
{
  if (this.filter.page > 1)
  {
    listTable.gotoPage(this.filter.page - 1);
  }
}

listTable.gotoPageNext = function()
{
  if (this.filter.page < listTable.pageCount)
  {
    listTable.gotoPage(parseInt(this.filter.page) + 1);
  }
}

listTable.gotoPageLast = function()
{
  if (this.filter.page < listTable.pageCount)
  {
    listTable.gotoPage(listTable.pageCount);
  }
}

listTable.changePageSize = function(e)
{
    var evt = Utils.fixEvent(e);
    if (evt.keyCode == 13)
    {
        listTable.gotoPage();
        return false;
    };
}

listTable.listCallback = function(result, txt)
{
  if (result.error > 0)
  {
    alert(result.message);
  }
  else
  {
    try
    {
      document.getElementById('listDiv').innerHTML = result.content;

      if (typeof result.filter == "object")
      {
        listTable.filter = result.filter;
      }

      listTable.pageCount = result.page_count;
    }
    catch (e)
    {
      alert(e.message);
    }
  }
}

listTable.selectAll = function(obj, chk)
{
  if (chk == null)
  {
    chk = 'checkboxes';
  }

  var elems = obj.form.getElementsByTagName("INPUT");

  for (var i=0; i < elems.length; i++)
  {
    if (elems[i].name == chk || elems[i].name == chk + "[]")
    {
      elems[i].checked = obj.checked;
    }
  }
}

listTable.compileFilter = function()
{
  var args = '';
  for (var i in this.filter)
  {
    if (typeof(this.filter[i]) != "function" && !Utils.isEmpty(this.filter[i]))
    {
      args += "&" + i + "=" + encodeURIComponent(this.filter[i]);
    }
  }

  return args;
}

listTable.getPageSize = function()
{
  var ps = 15;

  pageSize = document.getElementById("pageSize");

  if (pageSize)
  {
    ps = Utils.isInt(pageSize.value) ? pageSize.value : 15;
    document.cookie = "ECSCP[page_size]=" + ps + ";";
  }
}