<?php
class ExchangeOrderModel extends CommonModel {
    protected $pk = 'order_id';
    protected $tableName = 'exchange_order';
	
	   protected $types = array(
		0 => '等待付款', 
		1 => '等待发货', 
		2 => '仓库已捡货', 
		3 => '客户已收货', 
		4 => '申请退款中', //待开发
		5 => '已退款', //待开发
		6 => '申请售后中', //待开发
		7 => '已完成售后', //待开发
		8 => '已完成配送'
	);
	
	
	
    public function getType(){
        return $this->types;
    }
	public function getError(){
        return $this->error;
    }
	
	
	//更新商城销售接口
    public function mallSold($order_ids) {
        if (is_array($order_ids)) {
            $order_ids = join(',', $order_ids);//这里还是有一点点区别
            $ordergoods = D('ExchangeOrderGoods')->where("order_id IN ({$order_ids})")->select();
            foreach ($ordergoods as $k => $v) {
                D('ExchangeGoods')->updateCount($v['goods_id'], 'sold_num', $v['num']);
                //这里操作多规格的库存
                 refresh_spec_exchange($v['goods_id'],$v['key'],-$v['num']);     
			D('ExchangeGoods') -> updateCount($v['goods_id'], 'num', -$v['num']);//减去库存
            }
        } else {
            $order_ids = (int) $order_ids;
            $ordergoods = D('ExchangeOrderGoods')->where('order_id =' . $order_ids)->select();
            foreach ($ordergoods as $k => $v) {
                D('ExchangeGoods')->updateCount($v['goods_id'], 'sold_num', $v['num']);//更新销量
                 //这里操作多规格的库存
                 refresh_spec_exchange($v['goods_id'],$v['key'],-$v['num']);     
		     D('ExchangeGoods') -> updateCount($v['goods_id'], 'num', -$v['num']);//减去库存		     
            }
        }
        return TRUE;
    }
	
	 //商城购物配送接口
    public function mallPeisong($order_ids,$wait = 0) {
        if($wait == 0){
            $status = 1;
        }else{
            $status = 0;
        }
        foreach ($order_ids as $order_id) {
            $order = D('ExchangeOrder')->where('order_id =' . $order_id)->find();
            $shops = D('Shop')->find($order['shop_id']);
			
			if($order['express_price'] < $shops['express_price'] ){
				$logistics_price = $shops['express_price'];
			}else{
				$logistics_price = $order['express_price'];
			}
			$Paddress = D('Paddress')->find($order['address_id']);
			
			$res = D('DeliveryOrder')->where(array('type'=>'0','type_order_id'=>$order_id))->find();//查询是不是已经插入了
			
			
            if(!empty($shops['tel'])){
                $mobile = $shops['tel'];
            }else{
                $mobile = $shops['mobile'];
            }
			
            if($shops['is_goods_pei'] == 1 && !$res){
                $arr = array(
                    'type' => 0,
                    'type_order_id' => $order['order_id'],
                    'delivery_id' => 0,
                    'shop_id' => $order['shop_id'],
                    'city_id' => $shops['city_id'],
					'area_id' => $shops['area_id'],
					'business_id' => $shops['business_id'],
                    'lat' => $shops['lat'],
                    'lng' => $shops['lng'],
					'user_id' => $order['user_id'],
					'shop_name' => $shops['shop_name'],
					'name' => $Paddress['xm'],
					'mobile' => $Paddress['tel'],
					'addr' => $Paddress['area_str'].$Paddress['info'],
                    'addr_id' => $order['addr_id'],
                    'address_id' => $order['address_id'],
                    'logistics_price' => $logistics_price,
                    'create_time' => NOW_TIME,
                    'update_time' => 0,
                    'status' => $status
                );
				
                D('DeliveryOrder')->add($arr);
				D('Sms')->sms_delivery_user($order_id,$type=0);//短信通知配送员
				D('Weixintmpl')->delivery_tz_user($order_id,$type=0);//微信消息全局通知
            }
        }
        return true;
    }
	
	//商城万能打印接口
    public function combination_goods_print($order_ids) {
        if (is_array($order_ids)) {
            $order_ids = join(',', $order_ids);
            $Order = D('ExchangeOrder')->where("order_id IN ({$order_ids})")->select();
            foreach ($Order as $k => $v) {
                $this->goods_order_print($v['order_id']);
            }
        }else{
			//单商家
            $order_ids = (int) $order_ids;
            $Order = D('ExchangeOrder')->where('order_id =' . $order_ids)->select();
            foreach($Order as $k => $v) {
               $this->goods_order_print($v['order_id']);
            }
        }
        return TRUE;
    }
	
		//正式打印
	public function goods_order_print($order_id) {
	        $Order = D('ExchangeOrder')->find($order_id);
			$Shop = D('Shop')->find($Order['shop_id']);
			if ($Shop['is_goods_print'] == 1) {
				$msg = $this->goods_print($Order['order_id'], $Order['address_id']);
			
				$result = D('Print')->printOrder($msg, $Shop['shop_id']);
				$result = json_decode($result);
				
				$backstate = $result -> state;
				if ($backstate == 1) {
					if($Shop['is_goods_pei'] == 1){//1代表没开通配送确认发货步骤
						D('Order')->save(array('status' => 2,'is_print'=>1), array("where" => array('order_id' => $Order['order_id'])));
						D('Ordergoods')->save(array('status' => 1), array("where" => array('order_id' => $Order['order_id'])));
					}else{//如果是配送配送只改变打印状态
						D('Order')->save(array('is_print'=>1), array("where" => array('order_id' => $Order['order_id'])));	
					}
				}	
		   }
		return TRUE;		
	}
	
	//PC端输入物流单号发货
	public function pc_express_deliver($order_id){
		D('ExchangeOrder')->save(array('status' => 2), array("where" => array('order_id' => $order_id)));
        D('ExchangeOrderGoods')->save(array('status' => 1), array("where" => array('order_id' => $order_id)));
        return true;
    }
	
		//最终确认收货，不按照类目结算价按照订单用户实际金额扣点结算
	public function overOrder($order_id){
		$config = D('Setting')->fetchAll();
		if($detail = $this->find($order_id)){
			if($detail['status'] != 2 && $detail['status'] != 3) {
				return false;
			}else{
				if($this->save(array('status' => 8, 'order_id' => $order_id))){
					D('ExchangeOrderGoods')->save(array('status' => 8), array('where' => array('order_id' => $order_id)));//先更新		
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 8);
				    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 8);
					return true;
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
       
	}

    //更换地址
    public function update_add($uid,$type,$order_id,$address_id){
        $order = D('ExchangeOrder')->where(['user_id'=>$uid,'order_id'=>$order_id])->save(['address_id'=>$address_id]);
        if(false ==$order){
            return false;
        }
        return true;
    }
	
	
	
}