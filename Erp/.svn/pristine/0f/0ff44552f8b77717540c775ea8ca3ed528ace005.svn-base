<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>淘宝商品库存</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<table border="1" bordercolor="#9999FF">
	<tr>
		<th>淘宝商品名</th>
		<th>淘宝链接</th>
		<th>状态</th>
		<th>商家编码</th>
		<th>淘宝库存</th>
		<th>ERP库存</th>
		<th>是否多于实际库存</th>
	</tr>
	
	<?php foreach($items as $item): ?>
    
	<tr>
		<td><?php echo $item->title; ?></td>
		<td><?php echo $item->detail_url; ?></td>
		<td><?php if($item->approve_status=='onsale'): echo '在售'; ;elseif($item->approve_status=='instock'): echo '在库存'; ;else: echo '未知'; endif; ?></td>
		<td><?php echo isset($item->outer_id)?$item->outer_id:''; ?></td>
        
		<td><?php echo $item->num; ?></td>
		<td><?php echo $item->qohTotal; ?></td>
        <td><?php if ($item->num > $item->qohTotal):  echo 'Y'; endif; ?></td>
	</tr>
    
	<?php endforeach; ?>
    
</table>

</body>
</html>