<?php
/**
 * @author yxiang@leqee.com
 * @copyright Copyright &copy; 2010 leqee.com
 */

/**
 * 检查romeo是否执行错误
 * 如果出现了错误，则会发邮件到ERP组通知相关人员
 *
 * @author yxiang@leqee.com
 * @version $Id$
 * @package application.commands
 */
class CheckRomeoExecuteLogCommand extends CConsoleCommand
{
    public function run($args)
    {
        if(!isset($args[0]))
            $this->usageError('the hours is not specified');

        $hours=$args[0];
        $sql="SELECT * FROM romeo_execute_log WHERE message_type='OK' AND datetime>:datetime ORDER BY datetime DESC";
        if (($connection=Yii::app()->getComponent('slave'))===null)
            $connection=Yii::app()->getDb();
        $rows=$conncetion->createCommand($sql)->bindValue(':datetime',date('Y-m-d H:i:s',time()-3600*$hours),PDO::PARAM_STR)->queryAll();
        if($rows)
        {
        	// 邮件发送
            $html = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-size:12px;color:#000;background:#fff;">
<div style="margin:auto;width:800px;">
<h3>romeo执行报错啦 （'. date('Y-m-d H:i') .'）</h3>
            ';
            
            $html .= '
<table width="100%" style="border:1px solid black;">
    <tr>
        <th>ID</th>
        <th>SCRIPT</th>
        <th>MESSAGE</th>
        <th>TYPE</th>
        <th>DATETIME</th>
    </tr>
            ';
            
            foreach ($rows as $row) {
                $html .= '
    <tr align="center">
        <td>'. $row['log_id'] .'</td>
        <td>'. $row['script'] .'</td>
        <td><pre>'. print_r(unserialize($row['message']),true) .'</pre></td>
        <td>'. $row['message_type'] .'</td>
        <td>'. $row['datetime'] .'</td>
    </tr>
                ';	
            }
            
            $html .= '</table></div></body></html>';

            try 
            {
                $mail = Helper_Mail::smtp();
                $mail->CharSet='UTF-8';
                $mail->Subject="romeo执行报错 ";
                $mail->AddReplyTo('erp@leqee.com', 'ERP组');
                $mail->SetFrom('erp@leqee.com', 'ERP定时任务');
                $mail->AddAddress('erp@leqee.com',   'ERP组');
                $mail->AddBCC('yxiang@leqee.com', 'yxiang');
                $mail->IsHTML(true);
                $mail->Body=$html;
                $mail->Send();
            }
            catch (phpmailerException $e) 
            {
                Yii::log('邮件发送失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'app.commands.'.$this->getName());
            }
        }
    }
    
    public function getHelp()
    {
        return <<<EOD
USAGE
  yiic checkromeoexecutelog [hours]

DESCRIPTION
    检查指定的最近时间段是否有romeo执行错误

PARAMETERS
 * hours: 必须的, 检查最近多少个小时的log
 
EOD;
    }
}