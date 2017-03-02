<?php

/**
 * To use this API, pick a unique name for the lock. A sensible choice is the
 * name of the function performing the operation. A very simple example use of
 * this API:
 * @example
 * @code
 *   if (Yii::app()->lock->acquire('mymodule_long_operation')) {
 *     // Do the long operation here.
 *     // ...
 *     Yii::app()->lock->release('mymodule_long_operation');
 *   }
 * }
 * @endcode
 *
 * @author Yang Xiang <swaygently@gmail.com>
 */
class CDbLock extends CApplicationComponent
{
	/**
	 * @var string the ID of a {@link CDbConnection} application component. If not set, a SQLite database
	 * will be automatically created and used. The SQLite database file is
	 * is <code>protected/runtime/session-YiiVersion.db</code>.
	 */
	public $connectionID;
	/**
	 * @var string the name of the DB table to store session content.
	 * Note, if {@link autoCreateSessionTable} is false and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * <pre>
	 * (id CHAR(32) PRIMARY KEY, expire INTEGER, data TEXT)
	 * </pre>
	 * @see autoCreateSessionTable
	 */
	public $lockTableName='semaphore';
	/**
	 * @var boolean whether the session DB table should be automatically created if not exists. Defaults to true.
	 * @see sessionTableName
	 */
	public $autoCreateLockTable=true;

	private $_db;  // @var CDbConnection the DB connection instance
	private $_lockId;
	private $_locks;

	/**
	 * Creates the lock DB table.
	 * @param CDbConnection the database connection
	 * @param string the name of the table to be created
	 */
	protected function createLockTable($db,$tableName)
	{
		$sql="
CREATE TABLE $tableName
(
	name VARCHAR(255),
	value VARCHAR(255),
	expire DOUBLE,
	PRIMARY KEY (`name`),
	KEY `expire` (`expire`)
)";
		$db->createCommand($sql)->execute();
	}

	/**
	 * @return CDbConnection the DB connection instance
	 * @throws CException if {@link connectionID} does not point to a valid application component.
	 */
	protected function getDbConnection()
	{
		if($this->_db!==null)
            return $this->_db;
		else if(($id=$this->connectionID)!==null)
		{
			if(($this->_db=Yii::app()->getComponent($id)) instanceof CDbConnection)
                return $this->_db;
			else
                throw new CException(Yii::t('yii','CDbLock.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
                    array('{id}'=>$id)));
		}
		else
		{
			$dbFile=Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'lock-'.Yii::getVersion().'.db';
			return $this->_db=new CDbConnection('sqlite:'.$dbFile);
		}
	}

	/**
	 * Helper function to get this request's unique id.
	 */
	public function getLockId()
	{
		if ($this->_lockId===null)
		{
			$db=$this->getDbConnection();
			$db->setActive(true);

			if($this->autoCreateLockTable)
			{
				$sql="DELETE FROM {$this->lockTableName} WHERE expire<".microtime(true);
				try
				{
					$db->createCommand($sql)->execute();
				}
				catch(CDbException $e)
				{
					$this->createLockTable($db,$this->lockTableName);
				}
			}

			$this->_lockId=uniqid(mt_rand(),TRUE);
			Yii::app()->attachEventHandler('onEndRequest',array($this,'releaseAll'));
		}

		return $this->_lockId;
	}

	/**
	 * Initializes the application component.
	 * This method is required by IApplicationComponent and is invoked by application.
	 */
	public function init()
	{
		parent::init();
		$this->_locks=array();
	}

	/**
	 * Acquire (or renew) a lock, but do not block if it fails.
	 *
	 * @param $name
	 *   The name of the lock.
	 * @param $timeout
	 *   A number of seconds (float) before the lock expires (minimum of 0.001).
	 * @return
	 *   TRUE if the lock was acquired, FALSE if it failed.
	 */
	public function acquire($name,$timeout=30.0)
	{
		$expire=microtime(true)+max($timeout, 0.001);
		if (isset($this->_locks[$name]))
		{
			// Try to extend the expiration of a lock we already acquired.
			$sql="UPDATE {$this->lockTableName} SET expire=$expire WHERE name=:name AND value=:value";
			$success=(bool)$this->getDbConnection()->createCommand($sql)->bindValue(':name',$name,PDO::PARAM_STR)->bindValue(':value',$this->getLockId(),PDO::PARAM_STR)->execute();
			if (!$success) { unset($this->_locks[$name]); }
			return $success;
		}
		else
		{
			// Optimistically try to acquire the lock, then retry once if it fails.
			// The first time through the loop cannot be a retry.
			$retry=false;
			do
			{
				try
				{
					$sql="INSERT INTO {$this->lockTableName} (name,value,expire) VALUES (:name,:value,$expire)";
					$this->getDbConnection()->createCommand($sql)->bindValue(':name',$name,PDO::PARAM_STR)->bindValue(':value',$this->getLockId(),PDO::PARAM_STR)->execute();
					$this->_locks[$name]=true;
					$retry=false;
				}
				catch(CDbException $e)
				{
                    $retry=$retry?false:$this->getIsAvailable($name);
				}
			}
			while($retry);
		}

		return isset($this->_locks[$name]);
	}

	/**
	 * Check if lock acquired by a different process may be available.
	 *
	 * If an existing lock has expired, it is removed.
	 *
	 * @param $name
	 *   The name of the lock.
	 * @return
	 *   TRUE if there is no lock or it was removed, FALSE otherwise.
	 */
	public function getIsAvailable($name)
	{
		$sql="SELECT name,value,expire FROM {$this->lockTableName} WHERE name=:name AND value=:value";
		$lock=$this->getDbConnection()->createCommand($sql)->bindValue(':name',$name,PDO::PARAM_STR)->bindValue(':value',$this->getLockId(),PDO::PARAM_STR)->queryRow();
		if (!$lock) { return true; }

		$now=microtime(true);
		$expire=(float)$lock['expire'];
		if ($now>$expire)
		{
			// We check two conditions to prevent a race condition where another
			// request acquired the lock and set a new expire time. We add a small
			// number to $expire to avoid errors with float to string conversion.
			$expire = 0.0001+$expire;
			$sql="DELETE FROM {$this->lockTableName} WHERE name=:name AND value=:value AND expire<=$expire";
			return (bool)$this->getDbConnection()->createCommand($sql)->bindValue(':name',$name,PDO::PARAM_STR)->bindValue(':value',$lock['value'],PDO::PARAM_STR)->execute();
		}

		return false;
	}

	/**
	 * Wait for a lock to be available.
	 *
	 * This function may be called in a request that fails to acquire a desired
	 * lock. This will block further execution until the lock is available or the
	 * specified delay in seconds is reached. This should not be used with locks
	 * that are acquired very frequently, since the lock is likely to be acquired
	 * again by a different request during the sleep().
	 *
	 * @param $name
	 *   The name of the lock.
	 * @param $delay
	 *   The maximum number of seconds to wait, as an integer.
	 * @return
	 *   TRUE if the lock holds, FALSE if it is available.
	 */
	public function wait($name,$delay=30)
	{
		$delay=(int)$delay;
		while ($delay--)
		{
			sleep(1);
			if ($this->getIsAvailable($name))
			// No longer need to wait.
			return false;
		}
		// The caller must still wait longer to get the lock.
		return true;
	}

	/**
	 * Release a lock previously acquired by lock_acquire().
	 *
	 * This will release the named lock if it is still held by the current request.
	 *
	 * @param $name
	 *   The name of the lock.
	 */
	public function release($name)
	{
		unset($this->_locks[$name]);
		$sql="DELETE FROM {$this->lockTableName} WHERE name=:name AND value=:value";
		$this->getDbConnection()->createCommand($sql)->bindValue(':name',$name,PDO::PARAM_STR)->bindValue(':value',$this->getLockId(),PDO::PARAM_STR)->execute();
	}

	/**
	 * Release all previously acquired locks.
	 */
	public function releaseAll()
	{
		$this->_locks=array();
		$sql="DELETE FROM {$this->lockTableName} WHERE value=:value";
		$this->getDbConnection()->createCommand($sql)->bindValue(':value',$this->getLockId(),PDO::PARAM_STR)->execute();
	}
}
