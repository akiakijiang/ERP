if (typeof readCookie != "function")
{
	function readCookie(name)
	{
		var aCookie = document.cookie.split("; ");
		for (var i=0; i < aCookie.length; i++)
		{
			var aCrumb = aCookie[i].split("=");
			if (name == aCrumb[0])
				return decodeURIComponent(aCrumb[1]);
		}
		return null;
	}
}
if (document.all) {
	window.attachEvent('onload', pss);
} else {
	window.addEventListener('load', pss, false);
}
function pss()
{
	if (readCookie('OKSID'))
	{
		try
		{
			var aj = new Object();
			aj.createXMLHttpRequest = function() {
				var request = false;
				if (window.XMLHttpRequest) {
					request = new XMLHttpRequest();
					if(request.overrideMimeType) {
						request.overrideMimeType('text/xml');
					}
				} else if(window.ActiveXObject) {
					var versions = ['Microsoft.XMLHTTP', 'MSXML.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.7.0', 'Msxml2.XMLHTTP.6.0', 'Msxml2.XMLHTTP.5.0', 'Msxml2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP'];
					for(var i=0; i<versions.length; i++) {
						try {
							request = new ActiveXObject(versions[i]);
							if(request) {
								return request;
							}
						} catch(e) {}
					}
				}
				return request;
			}
			aj.XMLHttpRequest = aj.createXMLHttpRequest();
			aj.XMLHttpRequest.open('GET', __pssUrl);
			aj.XMLHttpRequest.send(null);
			aj.XMLHttpRequest = null;
		}
		catch(e)
		{
			try
			{
				Ajax.call(__pssUrl, '', function(){}, 'GET', 'TEXT');
			}
			catch (ee)
			{
			}
		}
	}
}
// http://www.xidea.org/