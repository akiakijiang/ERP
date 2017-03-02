<?php

/**
 * This is the model class for table "ecs_admin_user".
 *
 * The followings are the available columns in table 'ecs_admin_user':
 * @property integer $user_id
 * @property integer $party_id
 * @property string $facility_id
 * @property string $roles
 * @property string $user_name
 * @property string $email
 * @property string $password
 * @property string $join_time
 * @property string $last_time
 * @property string $last_ip
 * @property string $action_list
 * @property string $nav_list
 * @property string $lang_type
 * @property string $allowedip_type
 * @property string $allowedip_list
 * @property string $real_name
 *
 * The followings are the available model relations:
 */
class AdminUser extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return AdminUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ecs_admin_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('facility_id, roles, action_list, nav_list, allowedip_list, real_name', 'required'),
			array('party_id', 'numerical', 'integerOnly'=>true),
			array('roles, user_name, email', 'length', 'max'=>60),
			array('password', 'length', 'max'=>32),
			array('last_ip', 'length', 'max'=>15),
			array('lang_type', 'length', 'max'=>50),
			array('allowedip_type', 'length', 'max'=>8),
			array('real_name', 'length', 'max'=>100),
			array('join_time, last_time', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('user_id, party_id, facility_id, roles, user_name, email, password, join_time, last_time, last_ip, action_list, nav_list, lang_type, allowedip_type, allowedip_list, real_name', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'user_id' => 'User',
			'party_id' => 'Party',
			'facility_id' => 'Facility',
			'roles' => 'Roles',
			'user_name' => 'User Name',
			'email' => 'Email',
			'password' => 'Password',
			'join_time' => 'Join Time',
			'last_time' => 'Last Time',
			'last_ip' => 'Last Ip',
			'action_list' => 'Action List',
			'nav_list' => 'Nav List',
			'lang_type' => 'Lang Type',
			'allowedip_type' => 'Allowedip Type',
			'allowedip_list' => 'Allowedip List',
			'real_name' => 'Real Name',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('party_id',$this->party_id);
		$criteria->compare('facility_id',$this->facility_id,true);
		$criteria->compare('roles',$this->roles,true);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('join_time',$this->join_time,true);
		$criteria->compare('last_time',$this->last_time,true);
		$criteria->compare('last_ip',$this->last_ip,true);
		$criteria->compare('action_list',$this->action_list,true);
		$criteria->compare('nav_list',$this->nav_list,true);
		$criteria->compare('lang_type',$this->lang_type,true);
		$criteria->compare('allowedip_type',$this->allowedip_type,true);
		$criteria->compare('allowedip_list',$this->allowedip_list,true);
		$criteria->compare('real_name',$this->real_name,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
}