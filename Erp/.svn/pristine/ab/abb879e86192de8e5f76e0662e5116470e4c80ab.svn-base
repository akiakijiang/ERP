<?php

/**
 * This is the model class for table "ecshop.ecs_provider".
 *
 * The followings are the available columns in table 'ecshop.ecs_provider':
 * @property integer $provider_id
 * @property string $provider_name
 * @property string $provider_code
 * @property integer $provider_type
 * @property string $address
 * @property string $hot_brand
 * @property string $contact_person
 * @property string $phone
 * @property string $email
 * @property string $license
 * @property string $other_guarantee
 * @property integer $provider_status
 * @property string $apply_time
 * @property string $provider_bank
 * @property string $bank_account
 * @property integer $order
 * @property string $provider_order_type
 */
class Supplier extends CActiveRecord
{

    private static $supplierMap;
    
    /**
     * 由于供应商表的数据不多，故先一下查出来备用会比较快
     */
    public static function findSupplierById($supplierId)
    {
        if (!self::$supplierMap)
        {
            self::$supplierMap = array();
            $_all = self::model()->findAll(array('condition' => 'provider_status = 1'));
            foreach ($_all as $supplier) 
            {
                self::$supplierMap[$supplier->provider_id] = $supplier;
            }
            
        }
        
        return isset(self::$supplierMap[$supplierId]) ? self::$supplierMap[$supplierId] : null;
    }
    
    public static function getSupplierMap($haveall = true)
    {
        self::findSupplierById(0);
        
        if ($haveall) {
            $map = array('' => '-- 不限 --');
        } else {
            $map = array();
        }
        foreach (self::$supplierMap as $supplierId => $supplier) 
        {
            $map[$supplierId] = $supplier->provider_name;
        }
        
        return $map;
    }
    
	/**
	 * Returns the static model of the specified AR class.
	 * @return Supplier the static model class
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
		return 'ecshop.ecs_provider';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('provider_type, provider_status', 'required'),
			array('provider_type, provider_status, order', 'numerical', 'integerOnly'=>true),
			array('provider_name, provider_code, contact_person, phone, email, license, other_guarantee', 'length', 'max'=>255),
			array('address', 'length', 'max'=>1000),
			array('hot_brand', 'length', 'max'=>500),
			array('provider_bank, bank_account', 'length', 'max'=>100),
			array('provider_order_type', 'length', 'max'=>3),
			array('apply_time', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('provider_id, provider_name, provider_code, provider_type, address, hot_brand, contact_person, phone, email, license, other_guarantee, provider_status, apply_time, provider_bank, bank_account, order, provider_order_type', 'safe', 'on'=>'search'),
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
			'provider_id' => 'Provider',
			'provider_name' => 'Provider Name',
			'provider_code' => 'Provider Code',
			'provider_type' => 'Provider Type',
			'address' => 'Address',
			'hot_brand' => 'Hot Brand',
			'contact_person' => 'Contact Person',
			'phone' => 'Phone',
			'email' => 'Email',
			'license' => 'License',
			'other_guarantee' => 'Other Guarantee',
			'provider_status' => 'Provider Status',
			'apply_time' => 'Apply Time',
			'provider_bank' => 'Provider Bank',
			'bank_account' => 'Bank Account',
			'order' => 'Order',
			'provider_order_type' => 'Provider Order Type',
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

		$criteria->compare('provider_id',$this->provider_id);
		$criteria->compare('provider_name',$this->provider_name,true);
		$criteria->compare('provider_code',$this->provider_code,true);
		$criteria->compare('provider_type',$this->provider_type);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('hot_brand',$this->hot_brand,true);
		$criteria->compare('contact_person',$this->contact_person,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('license',$this->license,true);
		$criteria->compare('other_guarantee',$this->other_guarantee,true);
		$criteria->compare('provider_status',$this->provider_status);
		$criteria->compare('apply_time',$this->apply_time,true);
		$criteria->compare('provider_bank',$this->provider_bank,true);
		$criteria->compare('bank_account',$this->bank_account,true);
		$criteria->compare('order',$this->order);
		$criteria->compare('provider_order_type',$this->provider_order_type,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
}