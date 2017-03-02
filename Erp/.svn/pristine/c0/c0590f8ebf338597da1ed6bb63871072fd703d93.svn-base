<?php

/**
 * This is the model class for table "supplier_dispatch_list".
 *
 * The followings are the available columns in table 'supplier_dispatch_list':
 * @property integer $supplier_dispatch_list_id
 * @property string $dispatch_sn
 * @property string $supplier_id
 * @property string $status
 * @property string $supplier_note
 * @property string $created_by_user_login
 * @property string $created_stamp
 * @property string $last_update_by_user_login
 * @property string $last_update_stamp
 */
class SupplierDispatchList extends CActiveRecord
{

    public static $statusMapping = array(
        '' => '所有待确认',
        '/' => '-- 不限 --',
        'CREATED' => '待确认是否能够生产',
        'CONFIRMED' => '确认能够生产',
        'CANCELED' => '待确认取消生产',
        'CANCELED-CONFIRMED' => '确认取消生产',
        'FINISHED' => '已取货',
        'DISCARDED' => '已废弃',
    );
    
	/**
	 * Returns the static model of the specified AR class.
	 * @return SupplierDispatchList the static model class
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
		return 'supplier_dispatch_list';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('dispatch_sn, supplier_id, status, created_by_user_login, last_update_by_user_login', 'length', 'max'=>30),
			array('supplier_note', 'length', 'max'=>45),
			array('created_stamp, last_update_stamp', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('supplier_dispatch_list_id, dispatch_sn, supplier_id, status, supplier_note, created_by_user_login, created_stamp, last_update_by_user_login, last_update_stamp', 'safe', 'on'=>'search'),
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
    
    public function findBySupplierId($condition) 
    {
        
        // 限定供应商的访问权限，不然所有的工单都可以看到，就完蛋了
        if (!Yii::app()->user->checkAccess('ViewAllSupplierDispatchList')) {
            $supplierId = Yii::app()->user->getState('supplierId');
            if (!$supplierId) 
            {
                throw new CHttpException(404,'该用户没有设定供应商，请联系管理人员解决');
            }
            $condition['supplierId'] = $supplierId;
        }
        
        $sqlc = "select count(*) 
                from mps.supplier_dispatch_list sd
                inner join romeo.dispatch_list d on sd.dispatch_sn = d.dispatch_sn
                left join ecshop.ecs_provider p on p.provider_id = cast(sd.supplier_id as unsigned)
                where 1 ";
        
        
        $sql = "select sd.*, 
        d.dispatch_list_id, d.goods_sn, d.price, d.due_date, d.dispatch_priority_id, p.provider_name, d.price,
        (
        select created_stamp 
        from romeo.dispatch_status_history dh 
        where dh.dispatch_list_id = d.dispatch_list_id and status = 'FINISHED' limit 1
        ) as finished_stamp
        from mps.supplier_dispatch_list sd
        inner join romeo.dispatch_list d on sd.dispatch_sn = d.dispatch_sn
        left join ecshop.ecs_provider p on p.provider_id = cast(sd.supplier_id as unsigned)
        where 1 ";
        
        
        $condition_sql = "";
        if (!empty($condition['supplierId'])) 
        {
            $condition_sql  .= " and sd.supplier_id = '{$condition['supplierId']}' ";
        }
        
        if (!empty($condition['dispatchSn']))
        {
            $condition_sql .= " and sd.dispatch_sn = '{$condition['dispatchSn']}' ";
        }
        
        if (empty($condition['status'])) // 默认显示 需要供应商确认的两种状态
        {
            $condition_sql .= " and sd.status in ('CREATED', 'CANCELED') ";
        }
        else 
        {
            if ($condition['status'] != '/') 
            {
                $condition_sql .= " and sd.status = '{$condition['status']}' ";
            }
        }
        
        if (!empty($condition['type'])) 
        {
            if ($condition['type'] == 'tobeovertime') 
            {
                $condition_sql .= " and d.due_date < date_add(now(), interval -3 day)";
            }
            elseif ($condition['type'] == 'overtime')
            {
                $condition_sql .= " and d.due_date < now()";
            } 
            elseif ($condition['type'] == 'rush')
            {
                $condition_sql .= " and d.dispatch_priority_id = 'RUSH' ";
            }
        }
        
        $sql  .= $condition_sql;
        $sqlc .= $condition_sql;
        
        $totalcount = Yii::app()->db->createCommand($sqlc)->queryScalar();
        $pagination = new CPagination($totalcount);
        $pagination->pageSize = 20;
        
        $sql .= " limit " . $pagination->pageSize . " offset " . $pagination->currentPage * $pagination->pageSize ;
        $supplierDispatchLists = Yii::app()->db->createCommand($sql)->queryAll();
        
        if ($supplierDispatchLists) {
            $dispatchIds = array();
            foreach ($supplierDispatchLists as $supplierDispatchList) {
                $dispatchIds[] = $supplierDispatchList['dispatch_list_id'];
            }
            
            $sql = "select dispatch_list_id, attribute_name, attribute_value " .
                   " from romeo.dispatch_attribute " .
                   " where dispatch_list_id in (" . join(',', $dispatchIds) . ")";

            $_dispatchAttributes = Yii::app()->db->createCommand($sql)->queryAll();
            
            $dispatchAttributes = array();
            foreach ($_dispatchAttributes as $dispatchAttribute) {
                $dispatchAttributes[$dispatchAttribute['dispatch_list_id']][$dispatchAttribute['attribute_name']] 
                    = $dispatchAttribute['attribute_value'];
            }
            
            foreach ($supplierDispatchLists as $key => $supplierDispatchList) {
                if (isset($dispatchAttributes[$supplierDispatchList['dispatch_list_id']])) {
                    $supplierDispatchLists[$key]['attributes'] = $dispatchAttributes[$supplierDispatchList['dispatch_list_id']];
                }
            }
        }
        
        
        return array($supplierDispatchLists, $pagination);
    }
    
    
    public function findBySn($sn) {
        $sql = "select sd.* , d.dispatch_sn, d.dispatch_list_id, o.order_time, d.price, d.goods_sn, d.order_sn, 
                d.external_order_sn, d.due_date,
                (
                select attr_value from ecshop.order_attribute oa
                where oa.attr_name = 'important_day' and 
                oa.order_id = o.order_id
                limit 1
                ) as important_day
                from mps.supplier_dispatch_list sd
                inner join romeo.dispatch_list d on sd.dispatch_sn = d.dispatch_sn
                left join ecshop.ecs_order_info o on o.order_id = cast(d.order_id as unsigned)
                where sd.dispatch_sn = '{$sn}' ";
        $supplierDispatchList = Yii::app()->db->createCommand($sql)->queryRow();
        
        if (!$supplierDispatchList) {
            throw new CHttpException(404,'对应的工单没有找到或者被删除');
        }
        
        if (preg_match('/g(\d+)/', $supplierDispatchList['goods_sn'], $matches)) {
            $supplierDispatchList['goods_id'] = $matches[1];
        } else {
            $supplierDispatchList['goods_id'] = $supplierDispatchList['goods_sn'];
        }
        
        return $supplierDispatchList;
    }
    
    
    public function findAttributesBySn($sn)
    {
        
        $sql = "select da.attribute_name, da.attribute_value ".
               " from romeo.dispatch_attribute da ".
               " inner join romeo.dispatch_list d on da.dispatch_list_id = d.dispatch_list_id " .
               " where d.dispatch_sn = '{$sn}' ";
        $_attributes = Yii::app()->db->createCommand($sql)->queryAll();
        
        $attributes = array();
        foreach ($_attributes as $_attribute) {
            $attributes[$_attribute['attribute_name']] = $_attribute['attribute_value'];
        }
        
        return $attributes;
    }
    

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'supplier_dispatch_list_id' => 'Supplier Dispatch List',
			'dispatch_sn' => 'Dispatch Sn',
			'supplier_id' => 'Supplier',
			'status' => 'Status',
			'supplier_note' => 'Supplier Note',
			'created_by_user_login' => 'Created By User Login',
			'created_stamp' => 'Created Stamp',
			'last_update_by_user_login' => 'Last Update By User Login',
			'last_update_stamp' => 'Last Update Stamp',
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

		$criteria->compare('supplier_dispatch_list_id',$this->supplier_dispatch_list_id);
		$criteria->compare('dispatch_sn',$this->dispatch_sn,true);
		$criteria->compare('supplier_id',$this->supplier_id,true);
		$criteria->compare('status',$this->status,true);
		$criteria->compare('supplier_note',$this->supplier_note,true);
		$criteria->compare('created_by_user_login',$this->created_by_user_login,true);
		$criteria->compare('created_stamp',$this->created_stamp,true);
		$criteria->compare('last_update_by_user_login',$this->last_update_by_user_login,true);
		$criteria->compare('last_update_stamp',$this->last_update_stamp,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
}