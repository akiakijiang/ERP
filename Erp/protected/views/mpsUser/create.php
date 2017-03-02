<?php
$this->breadcrumbs=array(
	'Mps Users'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List MpsUser', 'url'=>array('index')),
	array('label'=>'Manage MpsUser', 'url'=>array('admin')),
);
?>

<h3>创建mps用户</h3>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>