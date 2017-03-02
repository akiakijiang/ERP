<?php 

/**
 * 产品服务
 * @author yxiang@leqee.com
 */
class ProductServices
{
    /**
     * 通过商品取得产品ID
     *
     * @param int $goodsId
     * @param int $styleId
     * 
     * @return string 如果不存在返回null
     */
    public static function getProductId($goodsId,$styleId)
    {
        $goodsId=intval($goodsId);
        $styleId=intval($styleId);
        if($goodsId==0&&$styleId==0) return null;
        $productIds=self::getProductIds(array(array('goods_id'=>$goodsId,'style_id'=>$styleId)));
        return (isset($productIds[$goodsId.'_'.$styleId]) ? $productIds[$goodsId.'_'.$styleId] : null);
    }
    
    /**
     * 通过goodsId和styleId获得productId，
     * 该函数使用了缓存以保证最快的执行速度, 如果查询的商品不在缓存中，则通过service查询，并更新缓存
     * 
     * @param array $goodsIdStyleIdList 
     *   二维数组，每一行包括商品的goods_id和style_id: array('goods_id'=>{goodsId}, 'style_id'=>{styleId})
     * @param boolean 
     *   是否重新建立缓存, 不是特殊情况请不要设置为true
     *
     * @return array  
     *   返回结果为商品的goodsId加styleId与productId的对应关系,
     *   二维数组，每一行为这样的格式  array('{goodsId}_{styleId}' => productID),
     */
    public static function getProductIds($goodsIdStyleIdList,$refresh=false)
    {
        // 缓存商品和产品的对应关系
        static $productMapping;
        
        if($refresh===true)
            $productMapping=array();
        
        if(($cache=Yii::app()->getCache())!==null)
        {
            if($refresh===true)
                $cache->delete(__FUNCTION__);
            else if($productMapping===null)
                $productMapping=$cache->get(__METHOD__);
        }

        if($goodsIdStyleIdList===null)
            return $productMapping;
         
        $result=array();  // 返回结果
        $cacheHit=true;   // 缓存命中
        foreach($goodsIdStyleIdList as $key=>$item) 
        {
            // 格式化为int型，如将null转为0
            $goodsIdStyleIdList[$key]['goods_id']=intval($item['goods_id']);
            $goodsIdStyleIdList[$key]['style_id']=intval($item['style_id']);
    
            // 判断在缓存中是否有
            $gsId=$goodsIdStyleIdList[$key]['goods_id'] .'_'. $goodsIdStyleIdList[$key]['style_id'];
            if(isset($productMapping[$gsId]))
                $result[$gsId]=$productMapping[$gsId]; 
            else
            {
                $cacheHit=false;
                $gsIdList[]=$gsId;
                $goodsIds[]=$goodsIdStyleIdList[$key]['goods_id'];  // 用于查询商品         
            }
        }
        
        // 缓存中没有命中
        if(!$cacheHit) 
        {
        	Yii::import('application.services.InventoryServices', true);
            // 要确认ERP系统中存在该商品, 因为ROMEO的getProductIdByGoodsIdStyleId方法会自动建立Mapping
//            $sql="
//                SELECT 
//                    IF(s.color, CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)), g.goods_name) AS goods_name, 
//                    g.goods_id, IF(s.style_id, s.style_id, 0) AS style_id
//                FROM 
//                    ecs_goods AS g
//                    left join ecs_goods_style gs on gs.goods_id = g.goods_id
//                    left join ecs_style s on s.style_id = gs.style_id
//                WHERE 
//                    g.goods_id IN (:goods_id)
//            ";
            $sql="
                SELECT 
	               g.goods_id, g.goods_name, ifnull(gs.goods_color, '') as goods_color, ifnull(s.color, '') as color, ifnull(s.style_id, '0')  as style_id 
                FROM 
                    ecs_goods AS g
                    left join ecs_goods_style gs on gs.goods_id = g.goods_id
                    left join ecs_style s on s.style_id = gs.style_id
                WHERE 
                    g.goods_id IN (:goods_id)
            ";            
            $command=Yii::app()->getDb()->createCommand($sql)
            	->bindValue(':goods_id', implode(',',$goodsIds));
            $data=$command->queryAll();
            foreach($data as $goods)
            {
                $key=$goods['goods_id'] .'_'. $goods['style_id'];
                if (isset($productMapping[$key]) || !in_array($key,$gsIdList))
                    continue;

                // 取得productId
                try
                {
                	// 转化成prodcut_name
                	$goods_name =  $goods['goods_name'];
                	$style_id = 0 ;
                	if(!empty($goods['style_id'])){
                		$style_id = $goods['style_id'] ;
                		if(!empty($goods['goods_color'])){
                			$goods_name = $goods_name . ' ' . $goods['goods_color'];
                		}else if(!empty($goods['color'])){
                			$goods_name = $goods_name . ' ' . $goods['color'];
                		}
                	}
                	
                    $context=new InventoryServicesContext;
                    $context->put('goodsId',$goods['goods_id']);
                    $context->put('styleId', $style_id);
                    $context->put('productName', $goods_name);
                    $response=Yii::app()->getComponent('romeo')->InventoryService->getProductIdByGoodsIdStyleId(array('arg0'=>$context->getRequestParam()));
                    $res=new InventoryServicesContext($response->return);
                    $productId=$res->get('productId');
                    $productMapping[$key]=$productId;
                }
                catch (Exception $e)
                {
                	Yii::log("查询产品错误, goodsId:{$goods['goods_id']}, styleId:{$goods['style_id']}, ".$e->getMessage(),CLogger::LEVEL_ERROR,'application.product.inventory');
                }
            }
            
            // 写入缓存,该缓存不过期
            if(($cache=Yii::app()->getCache())!==null)
            {
                $lock=Yii::app()->getComponent('lock');
                if($lock->acquire(__METHOD__))
                {
                    $cache->set(__METHOD__,$productMapping,0);
                    $lock->release(__METHOD__);
                }
            }
            
            foreach($gsIdList as $gsId)
            {
                if(isset($productMapping[$gsId]))
                    $result[$gsId]=$productMapping[$gsId];
            }
        }
        
        return $result; 
    }
}