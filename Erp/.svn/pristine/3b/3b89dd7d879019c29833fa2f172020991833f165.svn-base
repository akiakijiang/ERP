<?php
$this->breadcrumbs=array(
	'Supplier Dispatch Lists'=>array('index'),
	$model->supplier_dispatch_list_id,
);

$this->menu=array(
	array('label'=>'List SupplierDispatchList', 'url'=>array('index')),
	array('label'=>'Create SupplierDispatchList', 'url'=>array('create')),
	array('label'=>'Update SupplierDispatchList', 'url'=>array('update', 'id'=>$model->supplier_dispatch_list_id)),
	array('label'=>'Delete SupplierDispatchList', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->supplier_dispatch_list_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage SupplierDispatchList', 'url'=>array('admin')),
);
?>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'dispatch_sn',
		'supplier_id',
		'status',
		'supplier_note',
		'created_by_user_login',
		'created_stamp',
		'last_update_by_user_login',
		'last_update_stamp',
	),
)); ?>
