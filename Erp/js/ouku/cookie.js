
/**
 * @author Zandy<yzhang@oukoo.com>
 * @version $Id$
 */

var Ouku_Cookie = {

	/**
	 * 设置
	 */
	set: function (name, value, expires, path, domain, secure)
	{
		if (expires){
			var x = new Date();
			x.setTime(x.getTime()+expires);
		}
		// escape
		document.cookie= name + "=" + encodeURIComponent(value) +
			((expires) ? "; expires=" + x.toGMTString() : "") +
			((path) ? "; path=" + path : "") +
			((domain) ? "; domain=" + domain : "") +
			((secure) ? "; secure" : "");
	},

	/**
	 * 读取
	 */
	get: function (name)
	{
		var dc = document.cookie;
		var prefix = name + "=";
		var begin = dc.indexOf("; " + prefix);
		if (begin == -1)
		{
			begin = dc.indexOf(prefix);
			if (begin != 0) return null;
		}
		else
		{
			begin += 2;
		}
		var end = document.cookie.indexOf(";", begin);
		if (end == -1)
		{
			end = dc.length;
		}
		// unescape
		return decodeURIComponent(dc.substring(begin + prefix.length, end));
	},

	/**
	 * 删除
	 */
	del: function (name, path, domain)
	{
		if (this.get(name))
		{
			document.cookie = name + "=" + 
				((path) ? "; path=" + path : "") +
				((domain) ? "; domain=" + domain : "") +
				"; expires=Thu, 01-Jan-70 00:00:01 GMT";
		}
	}

}