<?php
$this->breadcrumbs=array(
	'Supplier Dispatch Lists',
);

$this->menu=array(
	array('label'=>'Create SupplierDispatchList', 'url'=>array('create')),
	array('label'=>'Manage SupplierDispatchList', 'url'=>array('admin')),
);


// 浮动层的相关代码
Yii::import('ext.qtip.QTip');

$opts = array(
    'position' =>  array(
        'corner' => array(
        'tooltip' => 'leftTop',
        'target' => 'rightTop',
        ),
    ),
    'show' => array(
        'when' => array('event' => 'mouseover' ),
        //'effect' => array( 'length' => 300 )
    ),
    
    'hide' => array(
        'when' => array('event' => 'mouseout' ),
        //'effect' => array( 'length' => 1000 )
        'fixed' => true,
    ),
    
    'style' => array(
        'color' => 'black',
        'name' => 'blue',
        'tip' => true,
        'border' => array(
            'width' => 3,
            'radius' => 5,
        ),
        'width' => 550,
    ),
    
    'content' => "",
);


$attrmap = array(
    '胸围' => 'bust',
    '腰围' => 'waist',
    '臀围' => 'hips',
    '喉咙到下摆' => 'hollow_to_hem',
    '喉咙到地' => 'hollow_to_floor',
    '颜色' => 'color',
    '腰带颜色' => 'sash_color',
    '腰带长' => 'sash_size',
    '材质' => 'textures',
);

Yii::app()->clientScript->registerCss('main',
'#main {width:100%;background:#999;margin:8px auto;}
#main td{background:#fff;}
#main tr th{background-color:#F2F1F0}');


if ($isAlert && $pagination->itemCount > 0)
{
    Yii::app()->clientScript->registerScript('', "alert('有 {$pagination->itemCount} 个工单已经过期，要加紧生产那');");
}


/**
 * 返回两个时间的时间差
 * @param int $interval
 *
 * @return string $diff_str
 */
function convert_interval($interval) {
    $diff_str = '';
    
    $map = array(
        '86400' => ' 天',
        '3600' => ' 小时',
        '60' => ' 分',
        '1' => ' 秒',
    );
    
    $level = 0;
    $_interval = abs(intval($interval));
    foreach ($map as $time_interval => $desc) {
        if ($_interval >= $time_interval) {
            $diff_str .= " " .floor($_interval / $time_interval) . $desc;
            $_interval = $_interval % $time_interval;
            
            $level ++;
            if ($level >= 2) {
                break;
            }
        }
        
    }

    return $interval < 0 ? '剩下 ' . $diff_str  : '超时 ' . $diff_str;
}
?>

<?php if($isAlert):?>
<h3 style="text-align: center; font-size: 32px;">工单管理系统</h3>
<p align="center">
<a href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => '')); ?>" style="font-size: 16px;margin-right: 24px;">工单管理</a>
<a href="<?php echo $this->createUrl('problem/index', array('condition[status]' => 'PROBLEM')); ?>" style="font-size: 16px;">出问题的衣服</a>
<?php elseif(isset($_GET['condition']['status']) && $_GET['condition']['status'] == 'PROBLEM'):?>
<a href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => '')); ?>">工单管理</a>
<?php else:?>
<h3>工单列表</h3>
<span>* 默认显示待确认能够生产和待确认取消的工单</span>
<p>
<!-- 
<a href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => 'CREATED')); ?>">还没有确认收到的工单</a> 
<a href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => 'CANCELED')); ?>">还没有确认取消的工单</a>
-->
<a href="<?php echo $this->createUrl('SupplierDispatchList/index'); ?>">待确认工单</a>

<a style="margin-left:50px;" href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => 'CONFIRMED')); ?>">所有要做的工单</a>

<a style="margin-left:50px;" href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[type]' => 'rush','condition[status]' => 'CONFIRMED')); ?>">加急的工单</a>

<a style="margin-left:50px;" href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[type]' => 'tobeovertime','condition[status]' => 'CONFIRMED')); ?>">快到期的工单</a>
<a style="margin-left:50px;" href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[type]' => 'overtime', 'condition[status]' => 'CONFIRMED')); ?>">超时的工单</a>
<a style="margin-left:50px;" href="<?php echo $this->createUrl('SupplierDispatchList/index', array('condition[status]' => 'CANCELED')); ?>">取消的工单</a>
<?php endif;?>
</p>
<?php 
echo CHtml::beginForm($this->createUrl('SupplierDispatchList/index'), 'get', array('id' => 'searchForm'));

// 供应商
if (Yii::app()->user->checkAccess('ViewAllSupplierDispatchList')) 
{
    echo CHtml::label(" 供应商", 'condition[supplierId]');
    echo CHtml::dropDownList("condition[supplierId]", isset($condition['supplierId']) ? $condition['supplierId'] : 0, Supplier::getSupplierMap());
}

// 生产单状态
echo CHtml::label(" 工单状态", 'condition[status]');
echo CHtml::dropDownList("condition[status]", 
isset($condition['status']) ? $condition['status'] : '', SupplierDispatchList::$statusMapping,
array('onchange' => 'document.getElementById("searchForm").submit()')
); 

// 生产单单号 
echo CHtml::label(" 工单号", 'condition[dispatchSn]');
echo CHtml::textField("condition[dispatchSn]", isset($condition['dispatchSn']) ? $condition['dispatchSn'] : '');

echo " ";

echo CHtml::submitButton('搜索');
echo CHtml::endForm();
?>

<table cellpadding="5" cellspacing="1" id="main">
<tr>
<th>工单号</th>
<?php if ($isAlert) { echo "<th>过期时间</th>"; } ?><?php if(!$isAlert):?>
<th>供应商</th>
<th>商品编号</th>
<th>价格</th><?php endif;?>
<th>订货日期</th>
<th>交货时间</th><?php if(!$isAlert):?>
<th>完成时间</th>
<th>当前状态</th><?php endif;?>
<th>操作</th>
</tr>
<?php foreach($supplierDispatchLists as $data) { 

$content = $data['dispatch_sn'] . " 详情 <br />";

if (isset($data['attributes']['goodsImage0_m'])) {
    $data['attributes']['goodsImage0_m'] =str_replace('imgerp.leqee.com', 'imgerp.aaasea.com', $data['attributes']['goodsImage0_m']); 
    $content .= "<div style=\"float:left;\"><img src=\"{$data['attributes']['goodsImage0_m']}\" /></div>";
}

$content .= '<div style="float:left;">';
foreach ($attrmap as $desc => $key) {
    $attrname = "goodsStyle_" . $key;
    
    if (isset($data['attributes'][$attrname])) {
        $content .= "{$desc}： {$data['attributes'][$attrname]}<br />";
    }
}

$content .= '</div><br style="clear:both;" />';

$sn = "sn_{$data['dispatch_sn']}";
?>
<tr>
<td class="sn" id="<?php echo $sn; ?>">
<?php
if ($data['dispatch_priority_id'] == 'RUSH') {
    print '<img src="' . Yii::app()->baseUrl . '/images/rush.gif" />';
}
$opts['content'] = $content;
QTip::qtip('#'.$sn, $opts);
?>
<?php echo CHtml::link(CHtml::encode($data['dispatch_sn']), array('print', 'sn'=>$data['dispatch_sn'])); ?>
</td>

<?php 
if ($isAlert) 
{ 
$diff = time() - strtotime($data['due_date']);
echo "<td".
    ($diff>0 ? ' style="color:red;"' : '').">" . 
    convert_interval($diff) .
    "</td>"; 
} 
?><?php if(!$isAlert):?>

<td>
<?php echo CHtml::encode($data['provider_name']); ?>
</td>

<td>
<?php 
if (preg_match('/g(\d+)/', $data['goods_sn'], $matches)) {
    echo CHtml::encode($matches[1]);
} else {
    echo CHtml::encode($data['goods_sn']);
}
 ?>
</td>

<td>
<?php 
if(Yii::app()->user->checkAccess('ViewPrice')) {
    echo CHtml::encode($data['price']); 
} else {
    echo '***';
}
?>
</td><?php endif;?>

<td>
<?php echo CHtml::encode($data['created_stamp']); ?>
</td>

<td>
<?php echo CHtml::encode($data['due_date']); ?>
</td><?php if(!$isAlert):?>

<td>
<?php echo CHtml::encode($data['finished_stamp']); ?>
</td>

<td>
<?php echo CHtml::encode(SupplierDispatchList::$statusMapping[$data['status']]); ?>
</td><?php endif;?>
<td>
<?php

switch ($data['status']) {
    case "CREATED" :
        print CHtml::link(CHtml::encode(SupplierDispatchList::$statusMapping['CONFIRMED']),
                            array('updatestatus', 'sn'=>$data['dispatch_sn'], 'status' => 'CONFIRMED')
                        );
        break;
    case "CANCELED" :
        print CHtml::link(CHtml::encode(SupplierDispatchList::$statusMapping['CANCELED-CONFIRMED']),
                            array('updatestatus', 'sn'=>$data['dispatch_sn'], 'status' => 'CANCELED-CONFIRMED')
                        );
        break;
    case "CONFIRMED" :
        print CHtml::link(CHtml::encode("打印"),
                            array('print', 'sn'=>$data['dispatch_sn'])
                        );
        break;
}

?>
</td>
</tr>
<?php } ?>
</table>
<br />

<?php 
// 分页
$this->widget('CLinkPager', array('pages' => $pagination)); 
?>
<div style="margin-top:550px;"></div>

