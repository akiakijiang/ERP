<?php
$this->breadcrumbs=array(
	'Mps Users'=>array('index'),
	$model->user_id=>array('view','id'=>$model->user_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List MpsUser', 'url'=>array('index')),
	array('label'=>'Create MpsUser', 'url'=>array('create')),
	array('label'=>'View MpsUser', 'url'=>array('view', 'id'=>$model->user_id)),
	array('label'=>'Manage MpsUser', 'url'=>array('admin')),
);
?>

<h3>修改用户 <?php echo $model->user_name; ?></h3>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>