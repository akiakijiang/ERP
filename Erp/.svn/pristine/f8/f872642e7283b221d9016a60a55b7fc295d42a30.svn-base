<?php
define('IN_ECS', true);

require('includes/init.php');
require('function.php');
require('includes/lib_common.php');
require('includes/lib_csv.php');

if (!in_array($_SESSION['admin_name'], array('zwsun', 'liangliang', 'mzhou'))) {
	die('no privilege');
}

set_time_limit(600);
$source_type = $_REQUEST['source_type'];

$subject =  stripslashes($_REQUEST['subject']);
$content = stripslashes($_REQUEST['content']);
$type = $_REQUEST['type'];
$act = $_REQUEST['act'];

if ($act == 'sendmail') {
	
if ($source_type == 'csv') {
	$csv_str = stripslashes($_REQUEST['csv_str']);
	if ($csv_str) {
  	$target_emails =  csv_parse($csv_str,";");
  //	pp($target_emails);
  }
} elseif ($source_type == 'file') {
  if (is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
//     echo "File ". $_FILES['csvfile']['name'] ." uploaded successfully.\n";
//     echo "Displaying contents\n";
     $csv_str = file_get_contents($_FILES['csvfile']['tmp_name']);
    // $csv_str = iconv("GB18030","UTF-8", $csv_str);
    $target_emails =  csv_parse($csv_str,";");
  } else {
     print "no file";
     die();
  }
}



if (!count($target_emails)) {
	die('no content');
}

//$target_emails = $db->getAll($sql);
foreach ($target_emails as $target_email) {
  $name = trim($target_email[1]);
  $email = trim($target_email[0]);
  if (!$email) {
  	$sql = "select email from ecs_users where user_name = '".stripslashes($name)."' ";
  	$email = $db->getOne($sql);
  
  }
  $newcontent = str_replace('{$user_name}', $name, $content);
  $newcontent = iconv("UTF-8", "GB18030",$newcontent);
  $name = iconv("UTF-8","GB18030", $name);

//  $subject = iconv("UTF-8","GB18030", $subject);
	$result = erp_send_mail_gbk($name, $email, $subject, $newcontent, $type);
  if ($result) {
  	print "send to $name : $email ok <br />";
  } else {
    print "send to $name : $email fail <br />";
  }
}
die();
}
//$smarty->assign('html', $_REQUEST['html']);
$smarty->display("oukooext/sendmail.dwt");



function erp_send_mail_gbk($name, $email, $subject, $content, $type = 0)
{
    $charset = 'gb2312';

    /* 邮件的头部信息 */
    $content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
    $content   =  base64_encode($content);

    $headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
    $headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($name) . '?=' . '" <' . $email. '>';
    $headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode(iconv("UTF-8","GB18030", $GLOBALS['_CFG']['shop_name'])) . '?='.'" <' . $GLOBALS['_CFG']['smtp_mail'] . '>';
    $headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode(iconv("UTF-8","GB18030",$subject)) . '?=';
    $headers[] = $content_type . '; format=flowed';
    $headers[] = 'Content-Transfer-Encoding: base64';
    $headers[] = 'Content-Disposition: inline';

    /* 获得邮件服务器的参数设置 */
    $params['host'] = $GLOBALS['_CFG']['smtp_host'];
    $params['port'] = $GLOBALS['_CFG']['smtp_port'];
    $params['user'] = $GLOBALS['_CFG']['smtp_user'];
    $params['pass'] = $GLOBALS['_CFG']['smtp_pass'];
    
    if (empty($params['host']) || empty($params['port']))
    {
        // 如果没有设置主机和端口直接返回 false
        $GLOBALS['err'] ->add($GLOBALS['_LANG']['smtp_setting_error']);

        return false;
    }
    else
    {
        // 发送邮件
        include_once('includes/cls_smtp.php');
        static $smtp;

        $send_params['recipients'] = $email;
        $send_params['headers']    = $headers;
        $send_params['from']       = $GLOBALS['_CFG']['smtp_mail'];
        $send_params['body']       = $content;
        if (!isset($smtp))
        {
            $smtp = new smtp($params);
        }

        if ($smtp->connect() && $smtp->send($send_params))
        {
            return true;
        }
        else
        {
            $err_msg = $smtp->error_msg();
            if (empty($err_msg))
            {
                $GLOBALS['err']->add('Unknown Error');
            }
            else
            {
                if (strpos($err_msg, 'Failed to connect to server') !== false)
                {
                    $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['smtp_connect_failure'], $params['host'] . ':' . $params['port']));
                }
                else if (strpos($err_msg, 'AUTH command failed') !== false)
                {
                    $GLOBALS['err']->add($GLOBALS['_LANG']['smtp_login_failure']);
                }
                elseif (strpos($err_msg, 'bad sequence of commands') !== false)
                {
                    $GLOBALS['err']->add($GLOBALS['_LANG']['smtp_refuse']);
                }
                else
                {
                    $GLOBALS['err']->add($err_msg);
                }
            }

            return false;
        }
    }
}																																																																							
