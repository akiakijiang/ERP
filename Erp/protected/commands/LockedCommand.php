<?php

/**
 * 有排它锁命令的执行命令
 * 
 * @author yxiang
 */
abstract class LockedCommand extends CConsoleCommand
{
	/**
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params)
	{
		if(strnatcasecmp($action,'index')==0)
			return true;
		
		// 加锁
		$lockName="commands.".$this->getName().".".$action;
		if(($lock=Yii::app()->getComponent('lock'))!==null && $lock->acquire($lockName,120))
		{
			// 记录命令的最后一次执行的开始时间
			$key='commands.'.$this->getName().'.'.strtolower($action).':start';
			Yii::app()->setGlobalState($key,microtime(true));
			return true;	
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] 命令{$action}正在被执行，或上次执行异常导致独占锁没有被释放，请稍候再试。\n";
			return false;
		}
	}
	
	/**
	 * 执行完毕后执行
	 * 
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action,$params)
	{	
		if(strnatcasecmp($action,'index')==0)
			return;

		// 记录命令的最后一次执行的完毕时间
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		Yii::app()->setGlobalState($key,microtime(true));
		
		// 释放锁
		$lockName="commands.".$this->getName().".".$action;
		$lock=Yii::app()->getComponent('lock');
		$lock->release($lockName);
	}
	
	/**
	 * 取得最后一次执行完毕的时间
	 *
	 * @param string $action
	 */
	protected function getLastExecuteTime($action)
	{
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		return Yii::app()->getGlobalState($key,0);
	}
}