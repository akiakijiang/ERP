<?php

/**
 * @author yxiang@leqee.com
 * @example
 * @code
 *   class SomeModel extends CSoapObjectModel
 *   {
 *       public function rules()
 *       {
 *           return array(.....);
 *       }
 *   }
 * 
 *   $client=new SoapClient("some.wsdl", $options); 
 *   $object=$client->SomeFunction($a, $b, $c);
 *   $model=SomeModel($object);
 *   $model->property1;
 *   $model->property2='value';
 *   $model->validate();
 * @encode
 */
abstract class CSoapObjectModel extends CModel
{
	// attribute names
	private static $_attributeNames=array();     
	
    private $_object;      // object
    private $_attributes;  // attribute name => attribute value
    
    private $_new=false;   // whether this instance is new or not
    
    /**
     * 构造函数
     * 当参数为一个对象时，表示这是一个持久化实例。否则参数为一个数组时，表示这是一个游离化实例。
     *
     * @param stdClass $object
     */
    public function __construct($attributes)
    {
    	if(is_object($attributes))
    		$this->_object=$attributes;
        else if(is_array($attributes))
        {
            $this->_attributes=$attributes;
        	$this->setIsNewRecord(true);
        }
    	$this->init();
        $this->attachBehaviors($this->behaviors());
    }
    
    /**
     * 初始化，取得该对象的属性值
     */
    public function init()
    {
        if($this->_attributes===null && $this->_object!==null) 
            $this->_attributes=get_object_vars($this->_object);
    }
    
    /**
     * 该模型的属性列表
     *
     * @return array
     */
    public function attributeNames()
    {
        $className=get_class($this);
        if(!isset(self::$_attributeNames[$className]))
            return self::$_attributeNames[$className]=array_keys($this->_attributes);
        else
            return self::$_attributeNames[$className];
    }
    
    /**
     * __get
     */
    public function __get($name) 
    {
        if(isset($this->_attributes[$name]) || array_key_exists($name,$this->_attributes))
            return $this->_attributes[$name];
        else
            return parent::__get($name);    
    }
    
    /**
     * __set
     */
    public function __set($name,$value)
    {
        if(isset($this->_attributes[$name]) || array_key_exists($name,$this->_attributes))
            $this->_attributes[$name]=$value;
        else
            parent::__set($name,$value);
    }
    
    /**
     * __isset
     */
    public function __isset($name)
    {
        if(isset($this->_attributes[$name]))
            return isset($this->_attributes[$name]);
        else
            return parent::__isset($name);
    }
    
    /**
     * __unset
     */
    public function __unset($name)
    {
        if(isset($this->_attributes[$name]))
            unset($this->_attributes[$name]);
        else
            parent::__unset($name);
    }
    
    /**
     * @return boolean whether the record is new and should be inserted when calling {@link save}.
     * This property is automatically set in constructor and {@link populateRecord}.
     * Defaults to false, but it will be set to true if the instance is created using
     * the new operator.
     */
    public function getIsNewRecord()
    {
        return $this->_new;
    }

    /**
     * @param boolean whether the record is new and should be inserted when calling {@link save}.
     * @see getIsNewRecord
     */
    public function setIsNewRecord($value)
    {
        $this->_new=$value;
    }
}