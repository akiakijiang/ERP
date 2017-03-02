<?php
$this->breadcrumbs=array(
	'Mps Users',
);

$this->menu=array(
	array('label'=>'Create MpsUser', 'url'=>array('create')),
	array('label'=>'Manage MpsUser', 'url'=>array('admin')),
);
?>

<h3>mps 用户列表</h3>

<a href="<?php echo $this->createUrl('create'); ?>">创建新的用户</a>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
