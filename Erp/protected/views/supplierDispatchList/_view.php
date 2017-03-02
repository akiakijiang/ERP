<tr>

<td>
<?php echo CHtml::link(CHtml::encode($data->dispatch_sn), array('view', 'sn'=>$data->dispatch_sn)); ?>
</td>

<td>
<?php echo CHtml::encode(SupplierDispatchList::$statusMapping[$data->status]); ?>
</td>

<td>
<?php echo CHtml::encode($data->created_by_user_login); ?>
</td>

<td>
<?php echo CHtml::encode($data->created_stamp); ?>
</td>
</tr>