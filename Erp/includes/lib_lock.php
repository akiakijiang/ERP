<?php

/**
 * 一个由数据库实现的排它锁，
 */

$_locks=array();

/**
 * API:
 * @code
 * function mymodule_long_operation() {
 *   if (lock_acquire('mymodule_long_operation')) {
 *     // Do the long operation here.
 *     // ...
 *     lock_release('mymodule_long_operation');
 *   }
 * }
 * @endcode
 */

/**
 * Helper function to get this request's unique id.
 */
function _lock_id() {
    static $lock_id;
  
	if (!isset($lock_id)) {
        $lock_id = uniqid(mt_rand(), TRUE);
        register_shutdown_function('lock_release_all', $lock_id);
	}
  
    return $lock_id;
}

/**
 * 占有锁
 *
 * @param $name
 *   The name of the lock.
 * @param $timeout
 *   A number of seconds (float) before the lock expires (minimum of 0.001).
 *
 * @return
 *   TRUE if the lock was acquired, FALSE if it failed.
 */
function lock_acquire($name, $timeout = 60.0) {
    global $_locks, $db;

	// Insure that the timeout is at least 1 ms.
	$timeout = max($timeout, 0.001);
	$expire = microtime(TRUE) + $timeout;
	if (isset($_locks[$name])) {
        // Try to extend the expiration of a lock we already acquired.
        if (!$db->query(sprintf("UPDATE semaphore SET expire = %f WHERE name = '%s' AND value = '%s'", $expire, $name, _lock_id()))) {
            // The lock was broken.
            unset($_locks[$name]);
        }
	}
	else {
        // Optimistically try to acquire the lock, then retry once if it fails.
        // The first time through the loop cannot be a retry.
        $retry = FALSE;
        // We always want to do this code at least once.
        do {
            if ($db->query(sprintf("INSERT INTO semaphore (name,value,expire) VALUES ('%s','%s',%f)", $name, _lock_id(), $expire),'SILENT')) {
                // We track all acquired locks in the global variable.
                $_locks[$name] = TRUE;
                // We never need to try again.
                $retry = FALSE;
            }
            else {
                // Suppress the error. If this is our first pass through the loop,
				// then $retry is FALSE. In this case, the insert must have failed
				// meaning some other request acquired the lock but did not release it.
				// We decide whether to retry by checking lock_may_be_available()
				// Since this will break the lock in case it is expired.
                $retry = $retry ? FALSE : lock_may_be_available($name);
            }
			// We only retry in case the first attempt failed, but we then broke
			// an expired lock.
	  } while ($retry);
	}
	return isset($_locks[$name]);
}

/**
 * 检查被其他进程占有的锁是否可用了
 *
 * @param $name
 *   The name of the lock.
 *
 * @return
 *   TRUE if there is no lock or it was removed, FALSE otherwise.
 */
function lock_may_be_available($name) {
	global $db;
	
	$lock = $db->getRow(sprintf("SELECT expire, value FROM semaphore WHERE name = '%s'", $name));
	if (!$lock) {
        return TRUE;
	}
	$expire = (float) $lock['expire'];
	$now = microtime(TRUE);
	if ($now > $expire) {
        // We check two conditions to prevent a race condition where another
        // request acquired the lock and set a new expire time.  We add a small
        // number to $expire to avoid errors with float to string conversion.
        $db->query(sprintf("DELETE FROM semaphore WHERE name = '%s' AND value = '%s' AND expire <= %f", $name, $lock['value'], 0.0001 + $expire));
        return (bool)$db->affected_rows();
	}
	return FALSE;
}

/**
 * 等待锁，直到锁可用
 *
 * @param $name
 *   The name of the lock.
 * @param $delay
 *   The maximum number of seconds to wait, as an integer.
 *
 * @return
 *   TRUE if the lock holds, FALSE if it is available.
 */
function lock_wait($name, $delay = 30) {
	$delay = (int) $delay;
	while ($delay--) {
        // This function should only be called by a request that failed to get a
        // lock, so we sleep first to give the parallel request a chance to finish
        // and release the lock.
        sleep(1);
        if (lock_may_be_available($name)) {
            // No longer need to wait.
            return FALSE;
        }
	}
	// The caller must still wait longer to get the lock.
	return TRUE;
}

/**
 * 释放已占有的锁
 *
 * @param $name
 *   The name of the lock.
 */
function lock_release($name) {
	global $_locks, $db;
	unset($_locks[$name]);
	$db->query(sprintf("DELETE FROM semaphore WHERE name = '%s' AND value = '%s'", $name, _lock_id()));
}

/**
 * 释放所以已占有的锁
 */
function lock_release_all($lock_id = NULL) {
	global $_locks, $db;
	
	$_locks = array();
	if (empty($lock_id)) {
	  $lock_id = _lock_id();
	}
	
	$db->query(sprintf("DELETE FROM semaphore WHERE value = '%s'", _lock_id()));
}
