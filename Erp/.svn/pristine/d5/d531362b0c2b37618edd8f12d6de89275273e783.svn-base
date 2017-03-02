//oukoo
/**
 * 会员登录
 */
function signIn()
{
  var frm = document.forms['ECS_LOGINFORM'];

  if (frm)
  {
    var username = frm.elements['username'].value;
    var password = frm.elements['password'].value;

    if (username.length == 0 || password.length == 0)
    {
       showDialog("请输入用户名和密码！");
        return;
    }
    else
    {
       Ajax.call(path+'User.Controller.php?Action=signin', 'path='+path+'&username=' + username + '&password=' + encodeURIComponent(password), signinoukuResponse, "POST", "TEXT");
    }
	
  }
  else
  {
    showDialog('Template error!');
  }
}


function logout()
{
	Ajax.call(path+'User.Controller.php?Action=indexLogout', 'path='+path, logoutResponse, "POST", "TEXT");
}

function logoutResponse(result)
{
	var mzone = document.getElementById("loginUserInfo");
	var res   = result.parseJSON();

	if (res.error > 0)
	{
		// 登出失败
		showDialog(res.content);
	}
	else
	{
		if (mzone)
		{
			if (typeof __productdetail__ == 'undefined') {
				mzone.innerHTML = res.content;
			} else {
				top.location.reload();
			}
		}
		else
		{
			showDialog("Template Error!");
		}
	}
}

function signinoukuResponse(result)
{
	
	var mzone = document.getElementById("loginUserInfo");
	var res   = result.parseJSON();
	if (res.error > 0)
	{
		showDialog(res.content.reString);
	}
	else
	{
		if (mzone)
		{
			if (typeof __productdetail__ == 'undefined') {
				mzone.innerHTML = res.content;
			} else {
				top.location.reload();
			}
		}
		else
		{
			showDialog("Template Error!");
		}
	}
}

