<?php
Yii::app()->clientScript->registerCss(
'view',

'
.view
{
padding: 10px;
margin: 3px 0;
border: 1px solid #C9E0ED;
}
.view b{
float: left;
margin-right: 10px;
position: relative;
text-align: right;
width: 50px;
}
.detail {clear:left;}
.view br{clear:both;}
'
);

?>
<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('user_id')); ?>:</b>
	<span class="detail">
	<?php
	echo CHtml::link(CHtml::encode($data->user_id), array('view', 'id'=>$data->user_id)); 
	echo " ";
	echo CHtml::link(CHtml::encode("编辑"), array('update', 'id'=>$data->user_id)); 
	?>
	</span>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('user_name')); ?>:</b>
	<span class="detail"><?php echo CHtml::encode($data->user_name); ?></span>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('password')); ?>:</b>
	<span class="detail"><?php echo CHtml::encode("******"); ?></span>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('supplier_id')); ?>:</b>
	<span class="detail"><?php 
    $supplier = Supplier::findSupplierById($data->supplier_id);
    if ($supplier) {
        echo CHtml::encode($supplier->provider_name); 
    } else {
        echo CHtml::encode($data->supplier_id); 
    }
    ?></span>
	<br />


</div>