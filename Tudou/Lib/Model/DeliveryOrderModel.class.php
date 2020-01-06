<?php
class DeliveryOrderModel extends RelationModel {
      protected $pk   = 'order_id';
      protected $tableName =  'delivery_order';

	  protected $_link = array(
        'Delivery' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Delivery',
            'foreign_key' => 'delivery_id',
            'mapping_fields' =>'name,mobile',
            'as_fields'=>'name,mobile', 
        ),
     );
	 
	 
	 
	 //抢单数据库操作
	 public function upload_deliveryOrder($delivery_id,$order_id){
		$config = D('Setting')->fetchAll();
		$interval_time = (int)$config['delivery']['interval_time'] ? (int)$config['delivery']['interval_time'] :'300';
		$res = M('DeliveryOrder')->where(array('delivery_id' =>$delivery_id,'status'=>'2','closed'=>'0'))->order('update_time desc')->find();
		$cha = time() - $res['update_time'];
		if($cha < $interval_time){
			$second = $interval_time  -	$cha;
		}
		if($res && $cha < $interval_time){
			$this->error = '操作频繁请【'.$second .'】秒后再试';
			return false;
		}
		 

			$Delivery = D('Delivery')->where(array('id'=>$delivery_id))->find();
			$do = D('DeliveryOrder')->where(array('order_id' =>$order_id))->find();//详情
			
			if(empty($do)){
				$this->error = '配送订单不存在';
				return false;
			}elseif(($do['is_appoint'] ==1) || (!empty($do['appoint_user_id']))){
				//如果指定了配送员，非配送员抢单报错处理
				if($Delivery['id'] != $do['appoint_user_id']){
					$this->error = '该订单指定了配送员配送您不能抢单';
					return false;
				}
			}elseif($do['closed'] ==1){
				$this->error = '当前订单已经关闭';
				return false;
			}else{
				if($do['type'] == 0){
				   D('Order')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'2'));
				   D('Ordergoods')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=0,$status=0);
				}elseif($do['type'] == 1){
				   if(!D('Eleorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'10'))){
					  $this->error = '更新外卖订单状态失败';
					  return false; 
				   }
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=1,$status=0);
				}elseif($do['type'] == 3){
				   if(!D('Marketorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'10'))){
					  $this->error = '更新菜市场订单状态失败';
					  return false; 
				   }
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=3,$status=0);
				}elseif($do['type'] == 4){
					//p($Delivery = D('Storeorder')->where(array('order_id'=>$do['type_order_id']))->find());die;
				   if(!D('Storeorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'10'))){
					  $this->error = '更新便利店订单状态失败';
					  return false; 
				   }
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=4,$status=0);
				}
			}
			return true;
	  } 
	  
	  
	  
	  
	 //确认完成数据库操作
	 public function ok_deliveryOrder($delivery_id,$order_id){
			$do = D('DeliveryOrder')->where(array('order_id'=>$order_id))->find();
			if(empty($do) ||$do['closed'] ==1 ){
				return false;	
			}else{
				if($do['type'] == 0){
				   D('Order')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'2'));
				   D('Ordergoods')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=0,$status=1);//微信通知用户已完成配送
				}elseif($do['type'] == 1){
				   D('EleOrder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'2'));
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=1,$status=1);//微信通知用户已完成配送
				}elseif($do['type'] == 3){
				   D('Marketorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'2'));
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=3,$status=1);//微信通知用户已完成配送
				}elseif($do['type'] == 4){
				   D('Storeorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'2'));
				   D('Weixintmpl')->delivery_qiang_tz_user($order_id,$delivery_id,$type=4,$status=1);//微信通知用户已完成配送
				}
			}
			return true;
	  }  
	  //外卖高峰期配送费结算    ---- 新增
	  public function getPeakMoney($order_id)
	  {
	  	 $do = D('DeliveryOrder')->where(array('type_order_id'=>$order_id))->find();
	  	 $delivery = D('Delivery')->find($do['delivery_id']);
	  	 $config = D('Setting')->fetchAll();
		   if($do['is_peak'] == 1){
		   	$info = "外卖订单【".$do['type_order_id']."】在高峰期内配送完成,获得高峰期补贴".$config['site']['peak_money']."元";
		   	D('Runningmoney')->add(array(
				'city_id' => $do['city_id'], 
				'area_id' => $do['area_id'], 
				'business_id' => $do['business_id'],
				'shop_id' => $do['shop_id'],  
				'running_id' => $do['type_order_id'], 
				'order_id' => $do['type_order_id'], 
				'delivery_id' => $do['delivery_id'], 
				'user_id' => $do['delivery_id'], 
				'money' => $config['site']['peak_money'], 
				'type' => ele, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip(), 
				'intro' => $info
			));
			D('Users')->addMoney($delivery['user_id'],$config['site']['peak_money'],$info);//写入余额
		   }
		   return true;
	  }		  

	//订单超时惩罚机制     ----新增
	public function getPunishment($order_id)
	{
	  	$do = D('DeliveryOrder')->where(array('type_order_id'=>$order_id))->find();
	  	$delivery = D('Delivery')->find($do['delivery_id']);
	  	//$shop = D('Shopdetails')->find($do['shop_id']);
	  	$config = D('Setting')->fetchAll();
	  	//$time =$do['pei_time']+$shop['delivery_time']*60;
	  	$time =$do['pei_time'];
	  	if($do['end_time'] > $time){//超时订单
	  		$timeover = (int)(($do['end_time'] - $time)/60);
	  		if($timeover >=$config['site']['timeover_3'] ){
	  			$info = "外卖订单【".$do['type_order_id']."】配送超时,扣除超时费用".$config['site']['timeover_three']."元";
			   	D('Runningmoney')->add(array(
					'city_id' => $do['city_id'], 
					'area_id' => $do['area_id'], 
					'business_id' => $do['business_id'],
					'shop_id' => $do['shop_id'],  
					'running_id' => $do['type_order_id'], 
					'order_id' => $do['type_order_id'], 
					'delivery_id' => $do['delivery_id'], 
					'user_id' => $do['user_id'], 
					'money' => -$config['site']['timeover_three'], 
					'type' => ele, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'intro' => $info
				));
				D('Users')->addMoney($delivery['user_id'],-$config['site']['timeover_three'],$info);
				return true;
	  		}
	  		if($timeover >=$config['site']['timeover_2'] && $timeover < $config['site']['timeover_3'] ){
	  			$info = "外卖订单【".$do['type_order_id']."】配送超时,扣除超时费用".$config['site']['timeover_two']."元";
			   	D('Runningmoney')->add(array(
					'city_id' => $do['city_id'], 
					'area_id' => $do['area_id'], 
					'business_id' => $do['business_id'],
					'shop_id' => $do['shop_id'],  
					'running_id' => $do['type_order_id'], 
					'order_id' => $do['type_order_id'], 
					'delivery_id' => $do['delivery_id'], 
					'user_id' => $do['delivery_id'], 
					'money' => -$config['site']['timeover_two'], 
					'type' => ele, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'intro' => $info
				));
				D('Users')->addMoney($delivery['user_id'],-$config['site']['timeover_two'],$info);
				return true;
	  		}
	  		if($timeover >=$config['site']['timeover_1'] && $timeover <$config['site']['timeover_2']){
	  			$info = "外卖订单【".$do['type_order_id']."】配送超时,扣除超时费用".$config['site']['timeover_one']."元";
			   	D('Runningmoney')->add(array(
					'city_id' => $do['city_id'], 
					'area_id' => $do['area_id'], 
					'business_id' => $do['business_id'],
					'shop_id' => $do['shop_id'],  
					'running_id' => $do['type_order_id'], 
					'order_id' => $do['type_order_id'], 
					'delivery_id' => $do['delivery_id'], 
					'user_id' => $do['delivery_id'], 
					'money' => -$config['site']['timeover_one'], 
					'type' => ele, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'intro' => $info
				));
				D('Users')->addMoney($delivery['user_id'],-$config['site']['timeover_one'],$info);
				return true;
	  		}
	  	}
	  	return true;
	}


 }
