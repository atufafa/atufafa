<?php
class RunningmoneyModel extends CommonModel{
    protected $pk   = 'money_id';
    protected $tableName =  'running_money';
	
	//写入外卖配送费
	public function add_delivery_logistics($order_id,$logistics,$type,$deliveradminpay){
		
		$do = D('DeliveryOrder')->where((array('type_order_id'=>$order_id,'type'=>$type)))->find();
		$delivery = D('Delivery')->where(array('id'=>$do['delivery_id']))->find();
		if($type==1){
		    $types='外卖';
		    $type_name='ele';
        }elseif($type==4){
		    $types='便利店';
		    $type_name='store';
        }elseif($type==3){
		    $types='菜市场';
		    $type_name='market';
        }

		$info = $types.'订单ID【'.$order_id.'】结算给配送员运费【'.round($logistics,2).'】元,其中配送管理员获得总分成'.$deliveradminpay;
		
		if(!empty($do) && $logistics > 0){
            $this->add(array(
				'city_id' => $do['city_id'], 
				'area_id' => $do['area_id'], 
				'business_id' => $do['business_id'],
				'shop_id' => $do['shop_id'],  
				'running_id' => $order_id, 
				'order_id' => $order_id, 
				'delivery_id' => $delivery['delivery_id'], 
				'user_id' => $delivery['id'], 
				'money' => $logistics, 
				'type' => $type_name,
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip(), 
				'intro' => $info
			));
            D('Users')->addMoney($delivery['user_id'],$logistics,$info);  //写入配送员余额
       }
        return true;
    }
	
	
	//写入商城配送费
	public function add_express_price($order_id,$express_price,$type){
		$order = D('Order')->find($order_id);	
		
		$do = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type'=>0))->find();	
		$delivery = D('Delivery')->where(array('id'=>$do['delivery_id']))->find();	
		$info = '商城订单ID【'.$order_id.'】【您是配送员,收到配送费'.round($do['logistics_price'],2).'】元';
		
		if($delivery && $do['logistics_price'] > 0){
             $this->add(array(
			 	'city_id' => $do['city_id'], 
				'area_id' => $do['area_id'], 
				'business_id' => $do['business_id'],
				'shop_id' => $do['shop_id'],  
				'running_id' => $order_id, 
				'order_id' => $order_id, 
				'delivery_id' => $delivery['delivery_id'], 
				'user_id' => $delivery['user_id'], 
				'money' => $do['logistics_price'], 
				'type' => 'goods', 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip(), 
				'intro' => $info
			));
            D('Users')->addMoney($delivery['user_id'], $do['logistics_price'],$info);  //写入配送员余额
       }
      return true;
    }

}