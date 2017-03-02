<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;

	public function authenticate()
	{
        $record=AdminUser::model()->find(array(
            'select'=>'user_id,party_id,facility_id,roles,user_name,password',
            'condition'=>'user_name=:username',
            'params'=>array(':username'=>$this->username),
        ));
		if($record===null)
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		else if($record->password!==md5($this->password))
            $this->errorCode=self::ERROR_PASSWORD_INVALID;
		else 
		{
			$this->_id=$record->user_id;
            $this->setPersistentStates(array('partyId'=>$record->party_id,'facilityId'=>$record->facility_id));			
			$this->errorCode=self::ERROR_NONE;
		}
		return !$this->errorCode;
	}
	
    public function getId()
    {
        return $this->_id;
    }
}