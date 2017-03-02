<?php

/**
 * This is the model class for table "supplier_dispatch_list_status_history".
 *
 * The followings are the available columns in table 'supplier_dispatch_list_status_history':
 * @property integer $supplier_dispatch_list_status_history_id
 * @property string $supplier_dispatch_list_id
 * @property string $status
 * @property string $created_stamp
 */
class SupplierDispatchListStatusHistory extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return SupplierDispatchListStatusHistory the static model class
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
		return 'supplier_dispatch_list_status_history';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('supplier_dispatch_list_status_history_id', 'required'),
			array('supplier_dispatch_list_status_history_id', 'numerical', 'integerOnly'=>true),
			array('supplier_dispatch_list_id, status', 'length', 'max'=>30),
			array('created_stamp', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('supplier_dispatch_list_status_history_id, supplier_dispatch_list_id, status, created_stamp', 'safe', 'on'=>'search'),
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
			'supplier_dispatch_list_status_history_id' => 'Supplier Dispatch List Status History',
			'supplier_dispatch_list_id' => 'Supplier Dispatch List',
			'status' => 'Status',
			'created_stamp' => 'Created Stamp',
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

		$criteria->compare('supplier_dispatch_list_status_history_id',$this->supplier_dispatch_list_status_history_id);
		$criteria->compare('supplier_dispatch_list_id',$this->supplier_dispatch_list_id,true);
		$criteria->compare('status',$this->status,true);
		$criteria->compare('created_stamp',$this->created_stamp,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
}