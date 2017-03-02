<?php

/**
 * @author qxu@leqee.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

/**
 * 判断套餐淘宝价格与ERP价格的不一致（不一致发送邮件至YY@i9i8.com）
 * 
 * @author qxu@leqee.com
 * @version $Id$
 * @package protected.commands
 */
class WarnpricediffCommand extends CConsoleCommand {
    private $slave; // Slave数据库    


    private $mailAddress = array (
        '4' => array ('何建成' => 'bbgfx@i9i8.com' ),
        '5' => array ('陈开湖' => 'khchen@i9i8.com' ), 
        '9' => array ('郭俊廷' => 'jtguo@i9i8.com' ), 
        '10' => array ('洪国建' => 'gjhong@leqee.com' ), 
        '13' => array ('毛画画' => 'hhmao@i9i8.com' ), 
        '14' => array ('陈继丁' => 'jdchen@leqee.com' ), 
        '15' => array ('来秀婷' => 'xtlai@leqee.com', '姜冲' => 'cjiang@i9i8.com' ), 
        '16' => array ('谌一枝' => 'yzshen@i9i8.com' ), 
        '17' => array ('杨飞' => 'fyang@i9i8.com' ), 
        '18' => array ('丁静' => 'jding@leqee.com', '林琳' => 'llin@leqee.com', '郑雅敏' => 'ymzheng@leqee.com' ), 
        '19' => array ('来秀婷' => 'xtlai@leqee.com' ), 
        '20' => array ('吴海潮' => 'hcwu@i9i8.com' ), 
        '21' => array ('陈继丁' => 'jdchen@leqee.com' ), 
        '22' => array ('闫泽红' => 'zhyan@leqee.com', '芦潇' => 'xl@i9i8.com', '肖均匀' => 'jxiao@i9i8.com' ), 
        '23' => array ('李峰' => 'fli@leqee.com' ), 
        '24' => array ('鲁良' => 'llu@leqee.com', '姜冲' => 'cjiang@i9i8.com' ),
        '26' => array ('李峰' => 'fli@leqee.com' ),  
        '28' => array ('吴海潮' => 'hcwu@i9i8.com' ) 
    );
    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
        // 检查直销价格是否一致
        $this->run ( array ('CheckPrice' ) );
        
        //检查分销价格是否一致
        $this->run ( array ('CheckPrice_fenxiao' ) );    
    }
    
    /**
     * 查询淘宝价格，调用发送邮件的函数
     */
    public function actionCheckPrice() {
        $exclude_list  = array (
//            'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
//            'd1ac25f28f324361a9a1ea634d52dfc0', //怀轩名品专营店
            'fd42e8aeb24b4b9295b32055391e9dd2', //oppo乐其专卖店
//            '239133b81b0b4f0ca086fba086fec6d5', //贝亲官方旗舰店
//            '11b038f042054e27bbb427dfce973307', //多美滋官方旗舰店
//            'ee0daa3431074905faf68cddf9869895', //accessorize旗舰店
//            'ee6a834daa61d3a7d8c7011e482d3de5', //金奇仕官方旗舰店
//            'fba27c5113229aa0062b826c998796c6', //方广官方旗舰店
//            'f38958a9b99df8f806646dc393fdaff4', //阳光豆坊旗舰店
//            '7f83e72fde61caba008bad0d21234104', //nutricia官方旗舰店
//            '62f6bb9e07d14157b8fa75824400981f', //雀巢官方旗舰店
//            '753980cc6efb478f8ee22a0ff1113538', //gallo官方旗舰店
//            '589e7a67c0f94fb686a9287aaa9107db', //yukiwenzi-分销
//            'fe1441b38d4742008bd9929291927e9e', //好奇官方旗舰店
//            'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利官方旗舰店
//            'dccd25640ed712229d50e48f2170f7fd', //ecco爱步官方旗舰店
//            '9f6ca417106894739e99ebcbf511e82f', //每伴旗舰店
//            'd2c716db4c9444ebad50aa63d9ac342e' //皇冠巧克力
        );        
        //循环淘宝店铺，根据每个店铺的套餐来做判断
        foreach ( $this->getTaobaoShopList () as $taobaoShop ) {            
            if (in_array ( $taobaoShop ['application_key'], $exclude_list )) {
                continue;
            }
            $str = "淘宝店铺【" . $taobaoShop ['nick'] . "】以下套餐在淘宝上的价格和系统上的价格不对，会影响到财务系统的对账，请及时在ERP系统上进行更新。\n";
            $control = false;
            
            $chunk_list = array ();
            $request = array ('fields' => 'num_iid', 'page_no' => 1, 'page_size' => 40 );
            
            try {
                $hasNext = true;
                while ( $hasNext ) {
                    $response = $this->getTaobaoClient ( $taobaoShop )->execute ( 'taobao.items.onsale.get', $request );
                    if (! isset ( $response->items )) {
                        $hasNext = false;
                    } else {
                        foreach ( $response->items->item as $num ) {
                            array_push ( $chunk_list, $num->num_iid );
                        }
                        $request ['page_no'] += 1;
                    }
                }
                //以20个结果为一组来查询商品信息        
                foreach ( array_chunk ( $chunk_list, 20 ) as $item_chunk ) {
                    $request = array ('fields' => 'num_iid,outer_id,price,sku,approve_status', 'num_iids' => implode ( ',', $item_chunk ) );
                    
                    $response = $this->getTaobaoClient ( $taobaoShop )->execute ( 'taobao.items.list.get', $request );
                    if (empty ( $response )) {
                        continue;
                    }
                    //循环通过num_iid得到的结果集
                    foreach ( $response->items as $items ) {
                        foreach ( $items as $item ) {
                            if (! isset ( $item->outer_id ) || is_object ( $item->outer_id )) {
                                continue;
                            }    
                         
                            //判断通过num_iid得到的商品是否有SKUS，没有就直接取价格
                            if (! isset ( $item->skus ) && substr ( $item->outer_id, 0, 3 ) == "TC-") {
                                $taobaoPrice = $item->price;
                                $outer_id = $item->outer_id;
                                $erp_price = $this->localsendmessage ( $taobaoShop, $taobaoPrice, $outer_id );
                                if ($erp_price == - 1) {
                                    $control = true;
                                    $str = $str . "套餐编号：" . $outer_id . "在ERP系统上不存在" . "\n";
                                } else if (! empty ( $erp_price )) {
                                    $control = true;
                                    $str = $str . "套餐编号：" . $outer_id . "    淘宝价格为：" . $taobaoPrice . "     ERP价格为：" . $erp_price . "\n";
                                }
                            } else if (isset ( $item->skus )) {
                                //若有SKUS，则需要对SKU进行判断，是否是套餐，是套餐则取价格的操作
                                foreach ( $item->skus->sku as $skuItem ) {
                                    if (substr ( $skuItem->outer_id, 0, 3 ) == "TC-") {
                                        $taobaoPrice = $skuItem->price;
                                        $outer_id = $skuItem->outer_id;
                                        $erp_price = $this->localsendmessage ( $taobaoShop, $taobaoPrice, $outer_id );
                                        if ($erp_price == - 1) {
                                            $control = true;
                                            $str = $str . "套餐编号：" . $outer_id . "在ERP系统上不存在" . "\n";
                                    } else if (! empty ( $erp_price )) {
                                        $control = true;
                                        $str = $str . "套餐编号：" . $outer_id . "    淘宝价格为：" . $taobaoPrice . "     ERP价格为：" . $erp_price . "\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } catch ( Exception $e ) {
                echo ("|  - has exception: " . $e->getMessage () . "\n");
            }
            
            //当control为true的时候，即有该店铺有商品价格不符时，统一发送邮件
            if ($control) {
                        
                try {
                    $mail = Yii::app ()->getComponent ( 'mail' );
                    
                    $mail->Subject = "淘宝店铺【 " . $taobaoShop ['nick'] . "】的套餐在淘宝上的价格和系统上的价格不对";
                    
                    $mail->Body = $str;
                    
                    $mail->ClearAddresses ();
//                    var_dump($this->mailAddress [$taobaoShop ['taobao_shop_conf_id']] );
                    foreach ($this->mailAddress [$taobaoShop ['taobao_shop_conf_id']] as $keys => $values ) {
                        if (! empty ( $keys )) {
                            $mail->AddAddress ( $values, $keys );
                        }
                    }
                    $mail->AddAddress ( 'zgliu@leqee.com', '刘志刚' );
                    $mail->AddAddress ( 'qxu@leqee.com', '许强' );
                    $mail->send ();
                } catch ( Exception $e ) {
                    var_dump ( "发送邮件异常" );
                }
            }
        }
    }
        
    /**
     * 处理分销的商品价格问题（主要是因为分销的不能抓售价  ，要抓采购价）
     */
    public function actionCheckPrice_fenxiao() {
        $exclude_list = array ('f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
//            'd1ac25f28f324361a9a1ea634d52dfc0',        //怀轩名品专营店
        //            'fd42e8aeb24b4b9295b32055391e9dd2',        //oppo乐其专卖店
        //            '239133b81b0b4f0ca086fba086fec6d5',        //贝亲官方旗舰店
        //            '11b038f042054e27bbb427dfce973307',        //多美滋官方旗舰店
        //            'ee0daa3431074905faf68cddf9869895',        //accessorize旗舰店
        //            'ee6a834daa61d3a7d8c7011e482d3de5',        //金奇仕官方旗舰店
        //            'fba27c5113229aa0062b826c998796c6',     //方广官方旗舰店
        //            'f38958a9b99df8f806646dc393fdaff4',        //阳光豆坊旗舰店
        //            '7f83e72fde61caba008bad0d21234104',        //nutricia官方旗舰店
        //            '62f6bb9e07d14157b8fa75824400981f',        //雀巢官方旗舰店
        //            '753980cc6efb478f8ee22a0ff1113538',        //gallo官方旗舰店
        //            '589e7a67c0f94fb686a9287aaa9107db',        //yukiwenzi-分销
        //            'fe1441b38d4742008bd9929291927e9e',        //好奇官方旗舰店
        'f1cfc3f7859f47fa8e7c150c2be35bfc' )//金佰利官方旗舰店
        //          'dccd25640ed712229d50e48f2170f7fd',        //ecco爱步官方旗舰店
        //            '9f6ca417106894739e99ebcbf511e82f',        //每伴旗舰店
        //            'd2c716db4c9444ebad50aa63d9ac342e',        //皇冠巧克力
        ;
        
        foreach ( $this->getTaobaoShopList () as $taobaoShop ) {
            
            if (! in_array ( $taobaoShop ['application_key'], $exclude_list )) {
                continue;
            }
            
            $str = "淘宝店铺【" . $taobaoShop ['nick'] . "】【分销】以下套餐在淘宝上的价格和系统上的价格不对，会影响到财务系统的对账，请及时在ERP系统上进行更新。\n";
            $control = false;
            
            $request = array ('fields' => 'skus,cost_price,outer_id', 'status' => 'up' );
            $response = $this->getTaobaoClient ( $taobaoShop )->execute ( 'taobao.fenxiao.products.get', $request );
            
            if (isset ( $response->products->fenxiao_product )) {
                $products = $response->products;
            }
            foreach ( $products as $product ) {                
                foreach ( $product as $item ) {
                    if (! isset ( $item->skus ) && substr ( $item->outer_id, 0, 3 ) == 'TC-') {
                        $taobaoPrice = $item->cost_price;
                        $outer_id = $item->outer_id;
                        $erp_price = $this->localsendmessage ( $taobaoShop, $taobaoPrice, $outer_id );
                        if ($erp_price == - 1) {
                            $control = true;
                            $str = $str . "套餐编号：" . $outer_id . "在ERP系统上不存在" . "\n";
                        } else if (! empty ( $erp_price )) {
                            $control = true;
                            $str = $str . "套餐编号：" . $outer_id . "    淘宝价格为：" . $taobaoPrice . "     ERP价格为：" . $erp_price . "\n";
                        }
                    }
                if (isset ( $item->skus ) && ! empty ( $item->skus->fenxiao_sku )) {
                        foreach ( $item->skus->fenxiao_sku as $sku ) {
                            if (substr ( $sku->outer_id, 0, 3 ) == 'TC-') {
                                $taobaoPrice = $sku->cost_price;
                                $outer_id = $sku->outer_id;
                                $erp_price = $this->localsendmessage ( $taobaoShop, $taobaoPrice, $outer_id );
                                if ($erp_price == - 1) {
                                    $control = true;
                                    $str = $str . "套餐编号：" . $outer_id . "在ERP系统上不存在" . "\n";
                                } else if (! empty ( $erp_price )) {
                                    $control = true;
                                    $str = $str . "套餐编号：" . $outer_id . "    淘宝价格为：" . $taobaoPrice . "     ERP价格为：" . $erp_price . "\n";
                                }
                            }
                        }
                    }
                }            
            }
            if ($control) {
            
                try {
                    $mail = Yii::app ()->getComponent ( 'mail' );
                        
                    $mail->Subject = "淘宝店铺 " . $taobaoShop ['nick'] . "的套餐在淘宝上的价格和系统上的价格不对";
                        
                    $mail->Body = $str;
                        
                    $mail->ClearAddresses ();
                        
                    foreach ( $this->mailAddress [$taobaoShop ['taobao_shop_conf_id']] as $keys => $values ) {
                        if (! empty ( $keys )) {
                            $mail->AddAddress ( $values, $keys );
                        }
                    }
                    $mail->AddAddress ( 'zgliu@leqee.com', '刘志刚' );
                    $mail->AddAddress ( 'qxu@leqee.com', '许强' );
                    $mail->send ();
                } catch ( Exception $e ) {
                    var_dump ( "发送邮件异常" );
                }
                
                
            }
        }
    }
    
    /**
     * 比对淘宝价格和ERP价格，不相等则返回该套餐的outer_id
     * @param taobaoClient $taobaoshop
     * @param float $taobao_price
     * @param String $outer_id
     */
    protected function localsendmessage($taobaoshop, $taobao_price, $outer_id) {
        $db = Yii::app ()->getDb ();
        $db->setActive ( true );
        
        $sql1 = "SELECT sum(gi.goods_number*gi.price) 
                    FROM ecshop.distribution_group_goods g
                    INNER JOIN ecshop.distribution_group_goods_item gi on gi.group_id = g.group_id 
                    WHERE g.code = :code";
        
        $erp_price = $db->createCommand ( $sql1 )->bindValue ( ":code", $outer_id )->queryColumn ();
        if ($erp_price [0] != null && $taobao_price != $erp_price [0]) {
            return $erp_price [0];
        } else if (empty ( $erp_price [0] )) {
            return - 1;
        } else {
            return null;
        }
    }
    /**
     * 返回请求对象
     *
     * @param array $taobaoShop
     * @return TaobaoClient
     */
    protected function getTaobaoClient($taobaoShop) {
        static $clients = array ();
        $key = $taobaoShop ['taobao_shop_conf_id'];
        if (! isset ( $clients [$key] ))
            $clients [$key] = new TaobaoClient ( $taobaoShop ['params'] ['app_key'], $taobaoShop ['params'] ['app_secret'], $taobaoShop ['params'] ['session_id'], ($taobaoShop ['params'] ['is_sandbox'] == 'Y' ? true : false) );
        return $clients [$key];
    }
    
    /**
     * 取得启用的淘宝店铺的列表
     * 
     * @return array
     */
    protected function getTaobaoShopList() {
        static $list;
        if (! isset ( $list )) {
            $sql = "select * from taobao_shop_conf where status='OK'";
            $list = $this->getSlave ()->createCommand ( $sql )->queryAll ();
            $command = $this->getSlave ()->createCommand ( "select * from taobao_api_params where taobao_api_params_id=:id" );
            foreach ( $list as $key => $item )
                $list [$key] ['params'] = $command->bindValue ( ':id', $item ['taobao_api_params_id'] )->queryRow ();
        }
        return $list;
    }
    
    /**
     * 取得slave数据库连接
     * 
     * @return CDbConnection
     */
    protected function getSlave() {
        if (! $this->slave) {
            if (($this->slave = Yii::app ()->getComponent ( 'slave' )) === null)
                $this->slave = Yii::app ()->getDb ();
            $this->slave->setActive ( true );
        }
        return $this->slave;
    }
}
