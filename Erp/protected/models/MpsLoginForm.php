<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class MpsLoginForm extends CFormModel
{
	public $username;
	public $password;
	public $rememberMe;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return array(
			// username and password are required
			array('username, password', 'required'),
			// password needs to be authenticated
			array('password', 'authenticate', 'skipOnError'=>true),
			// rememberMe needs to be a boolean
			array('rememberMe', 'boolean'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'rememberMe'=>'一天内不用登录',
            'username'=>'用户',
            'password'=>'密码',
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute,$params)
	{
		$this->_identity=new MpsUserIdentity($this->username,$this->password);
		if(!$this->_identity->authenticate())
			$this->addError('password','用户名或密码错误.');
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if($this->_identity===null)
		{
			$this->_identity=new MpsUserIdentity($this->username,$this->password);
			$this->_identity->authenticate();
		}
        
		if($this->_identity->errorCode === MpsUserIdentity::ERROR_NONE)
		{
			$duration=$this->rememberMe ? 3600*24 : 0; // 1 days
			Yii::app()->user->login($this->_identity,$duration);
            
			return true;
		}
		else
			return false;
	}
}
