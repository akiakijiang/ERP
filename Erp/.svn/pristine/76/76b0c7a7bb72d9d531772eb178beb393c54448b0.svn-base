<?php

Yii::import('application.commands.LockedCommand', true);

/**
 * 贝乐嘉积分同步
 * 
 * @author ychen
 *
 */
class CrmCommand extends LockedCommand
{
    public $bbl_token = "edcdf392c9f93a0f0889c45104dc9a46";

    /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $this->run(array('SynchronizePoints'));
    }

    /**
	 * 检查库存预定，因为如果没有建立ProductMapping的话，库存预订会一直失败的
	 */
    public function actionSynchronizePoints()
    {
        echo "[". date('c'). "] 同步用户积分开始 \n";
        $result = Yii::app()->getComponent('crm')->BBLService->synchronizePoints(array('token'=>$this->bbl_token));
        if ($result && $result->return->code == "") {
            echo "[". date('c'). "] 同步数量 {$result->return->result} \n";
        } else {
            echo "[". date('c'). "] 报错 {$result->return->msg} {$result->return->result}\n";
        }
    }

}