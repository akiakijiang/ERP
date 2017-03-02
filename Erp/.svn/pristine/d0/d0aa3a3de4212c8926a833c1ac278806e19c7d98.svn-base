<?php
$this->breadcrumbs=array(
	'Mps Users'=>array('index'),
	$model->user_id,
);

$this->menu=array(
	array('label'=>'List MpsUser', 'url'=>array('index')),
	array('label'=>'Create MpsUser', 'url'=>array('create')),
	array('label'=>'Update MpsUser', 'url'=>array('update', 'id'=>$model->user_id)),
	array('label'=>'Delete MpsUser', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->user_id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage MpsUser', 'url'=>array('admin')),
);
?>

<h3>查看 #<?php echo $model->user_name; ?></h3>

<?php 
    $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'user_id',
		'user_name',
        array(
            'name' => 'password',
            'value' => '******',
        ),
        array(
            'name' => 'supplier_id',
            'value' => (Supplier::findSupplierById($model->supplier_id) 
                        ? Supplier::findSupplierById($model->supplier_id)->provider_name
                        : $model->supplier_id),
        ),
	),
)); ?>
