Client = function (host)
{
	this.host = host;
	this.serialize = function(obj) 
	{
	  var str = [];
	  for(var p in obj)
		if (obj.hasOwnProperty(p)) {
		  str.push(encodeURIComponent(p) + "=" + JSON.stringify(obj[p]));
		}
	  return str.join("&");
	}
	this.sendRequest = function (funcName,args,method,parseFunction,procError)
	{
		var xmlhttp;
		if (window.XMLHttpRequest)
			xmlhttp = new XMLHttpRequest();//IE7+, Firefox, Chrome, Opera, Safari
		else
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); //IE6, IE5
		args.func = funcName;
		var params = this.serialize(args);
		console.log("Request: " + params);

		var address = this.host
		if (method == "GET")
			address += "?" + params;
		xmlhttp.open(method, address, true);
		xmlhttp.onreadystatechange = function()
		{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{
				console.log("Response: " + xmlhttp.responseText);
				var data = eval("("+xmlhttp.responseText+")");
				if (data.error_code != 0)
				{
					if (typeof procError === 'function')
						procError(data.error_code,data.error_msg);
				}
				else
				{
					if (typeof(parseFunction) === 'function')
						parseFunction(data);
				}
			}
		}
		if (method == "POST")
		{
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			//xmlhttp.setRequestHeader("Content-length", params.length);
			//xmlhttp.setRequestHeader("Connection", "close");
			xmlhttp.send(params);
		}
		else
			xmlhttp.send();
	}
}
