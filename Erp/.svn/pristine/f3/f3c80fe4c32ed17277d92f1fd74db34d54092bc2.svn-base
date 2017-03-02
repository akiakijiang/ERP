<?php

/**
 * 用户服务
 *
 */
class UserServices 
{
	/**
	 * 取得用户所有可用的PARTY列表， 比如用户有电商的权限，也会返回电商下面的
	 *
	 * @param int $userId 用户的ID
	 * @return array 
	 */
	public static function getAvaiablePartyList($userId)
	{
        $response=Yii::app()->getComponent('romeo')->UserService->getUserParty(array('userId'=>$userId));
        if($response && $response->return->UserParty)
        {
        	if(is_object($response->return->UserParty))
                $list=array($response->return->UserParty);
            else
                $list=$response->return->UserParty;
        }
        else
            $list=array();
	    return $list;
	}
	
    /**
     * 取得用户的party
     *
     * @param int $userId
     * 
     * @return object
     */
	public static function getDefaultParty($userId)
	{
        $list=self::getAvaiablePartyList($userId);
        if($list!==array() && ($party=reset($list))!==false)
            return $party;
        else
            return null;
	}
}