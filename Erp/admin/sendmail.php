<?php
/**
 * 邮件发送
 */
define('IN_ECS', true);

require('includes/init.php');
require('function.php');
require('includes/lib_csv.php');

//增加权限
admin_priv('manus_send_mail');

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
        }
    } elseif ($source_type == 'file') {
        if (is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
         $csv_str = file_get_contents($_FILES['csvfile']['tmp_name']);
         $csv_str = iconv("GB18030","UTF-8", $csv_str);
         $target_emails =  csv_parse($csv_str,";");
        } else {
         print "no file";
         die();
        }
    }

    if (!count($target_emails)) {
    	die('no content');
    }

    foreach ($target_emails as $target_email) {
        $name = trim($target_email[0]);
        $email = trim($target_email[1]);
        if (!$email) {
            $sql = "select email from ecs_users where user_name = '".stripslashes($name)."' ";
            $email = $db->getOne($sql);
        }
        $result = send_mail($name, $email, $subject, $content, $type);
        if ($result) {
            print "send to $name : $email ok <br />";
        } else {
            print "send to $name : $email fail <br />";
        }
    }
    
    die();
}

$smarty->display("oukooext/sendmail.dwt");
