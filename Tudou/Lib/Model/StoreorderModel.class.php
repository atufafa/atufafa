<?php

class StoreorderModel extends CommonModel {
    protected $pk = 'order_id';
    protected $tableName = 'store_order';
    protected $cfg = array(
        0 => '待付款',
        1 => '等待商家接单',
        9 => '商家已接单',
        10=> '派送员已接单',
        11=> '派送员已取到商品，正在配送中',
        2 => '配送中',
		3 => '退款中',
		4 => '已退款',		
        8 => '已完成',
    );
	public function getError() {
        return $this->error;
    }
    public function checkIsNew($uid, $shop_id) {
        $uid = (int) $uid;
        $shop_id = (int) $shop_id;
        return $this->where(array('user_id' => $uid, 'shop_id' => $shop_id, 'closed' => 0))->count();
    }

    public function getCfg() {
        return $this->cfg;
    }
	
	
	//检测用户收获地址是否超区
	public function getAddrDistance($addr_id,$shop_id){
		$Useraddr = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
		$Shop = D('Shop')->find($shop_id);
		$store = D('Store')->where(array('shop_id'=>$shop_id))->find();
		$getAddrDistance = getAddrDistance($Useraddr['lat'], $Useraddr['lng'], $Shop['lat'], $Shop['lng']);
		
		
		if(empty($store['is_radius'])){
			$radius = 5000;
		}else{
			$radius = $store['is_radius']*1000;
		}
		
		if($getAddrDistance >= $radius){
			return false;
		}
		return true;
	}

		
		
		
	//取消，删除订单逻辑封装
	public function cancel($order_id,$user_id){
		if($detail = $this->find($order_id)){
			$Shop = D('Shop')->find($detail['shop_id']);
			$obj = D('DeliveryOrder');
			if($Shop['is_store_pei'] == 1){
            	$do = $obj->where(array('type_order_id' => $order_id, 'type' => 4))->find();
				if($do){
					if($do['status'] == 2 || $do['status'] == 11 || $do['status'] == 10) {
						$this->error = '配送员已经抢单，无法删除';
						return false;
					}elseif($do['status'] == 8){
						$this->error = '配送员已经完成配置了，无法删除';
						return false;
					}elseif($do['closed'] == 1){
						$this->error = '该订单配送状态不正确';
						return false;
					}
					if(!$obj->where(array('type_order_id' => $order_id, 'type' => 4))->save(array('closed'=>1))){
						$this->error = '抢单模式更新配送数据库失败';
						return false;
					}
				}
			}
			if($this->where(array('order_id'=>$order_id))->save(array('closed'=>1))){
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 10,$status = 11);
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 10,$status = 11);
			}else{
				$this->error = '更新数据库失败';
				return false;
			}
		}else{
			$this->error = '订单信息错误';
			return false;
		}
	}
	//删除过期便利店订单,商家id，会员ID，可选
	public function past_due_store_order($shop_id ,$user_id){
		$config = D('Setting')->fetchAll();
		$past_due_store_order_time = isset($config['store']['past_due_store_order_time']) ? (int)$config['store']['past_due_store_order_time'] : 15;
        $time = NOW_TIME - $past_due_store_order_time * 60;
		$list = $this->where(array('closed'=>0,'status'=>0))->select();
		foreach ($list as $key => $val){
            if($val['create_time'] < $time){ 
                $this->cancel($val['order_id']);
            }
        }
		return true;
	}
	
	
	//根据订单ID获取便利店订单名称
	public function get_store_order_product_name($order_id){
		    $order = D('Storeorder')->find($order_id);
            $product_ids = D('Storeorderproduct')->where('order_id=' . $order_id)->getField('product_id', true);
            $product_ids = implode(',', $product_ids);
            $map = array('product_id' => array('in', $product_ids));
            $product_name = D('Storeproduct')->where($map)->getField('product_name', true);
            $product_name = implode(',', $product_name);
			return $product_name;
		 
    }
		
	//退款逻辑封装
	public function store_user_refund($order_id){
		$detail = $this->where('order_id =' . $order_id)->find();
		if(!$detail = $this->where('order_id =' . $order_id)->find()){
           $this->error = '没有找到订单';
		   return false;
        }else{
			if(!$Shop = D('Shop')->find($detail['shop_id'])){
			   $this->error = '没有找到该订单的商家信息';
			   return false;
			}else{
				if($Shop['is_store_pei'] == 1){
					
					$do = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' =>4))->find();
					if($do && $do['status'] != 1){
						$this->error = '当前配送状态不支持申请退款';
						return false;
					}
					if($do){
						if(!$res = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' =>4))->setField('closed', 1)){
							$this->error = '申请退款更新配送信息错误，请稍后再试';
							return false;
						}
					}
			     }
				if($this->where('order_id =' . $order_id)->setField('status', 3)){
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 10,$status = 3);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 10,$status = 3);
					return true;
				}else{
					$this->error = '更新退款状态失败';
					return false;
				}
			}
        }
    }
	 

	//便利店结算
    public function overOrder($order_id) {
		if($detail = D('Storeorder')->find($order_id)){
			if($detail['status'] != 2){
				return false;
			}else{
				$store = D('Store')->find($detail['shop_id']);
				if (D('Storeorder')->save(array('order_id' => $order_id, 'status' => 8,'end_time' => NOW_TIME))){ //防止并发请求
					$Intro = '便利店订单结算';
					D('Shopmoney')->insertData($order_id,$id ='0',$detail['shop_id'],$detail['settlement_price'],$type ='store',$Intro);//结算给商家
					if($detail['settlement_price'] > 0) {
						D('Userguidelogs')->AddMoney($detail['shop_id'], $detail['settlement_price'], $order_id,$type = 'store');//推荐员分成
						D('Users')->AddUser_guide($detail['user_id'],$detail['settlement_price'],$order_id,$type='store');
						D('Users')->integral_restore_user($detail['user_id'],$order_id,$id ='0',$detail['settlement_price'],$type ='store');//便利店购物返利积分
					}
					$this->AddDeliveryIogistics($order_id);//结算配送费给配送员
					D('Storeorderproduct')->updateByOrderId($order_id);
					D('Store')->updateCount($detail['shop_id'], 'sold_num'); //这里是订单数
					D('Store')->updateMonth($detail['shop_id']);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 10,$status = 8);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 10,$status = 8);
					return true;
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
		
    }
	//给配送员给钱
	public function AddDeliveryIogistics($order_id)
    {
        if ($detail = D('Storeorder')->find($order_id)) {
            $store = D('Store')->find($detail['shop_id']);
            $Shop = D('Shop')->find($detail['shop_id']);
            $do = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 4))->find();//0是商城1是配送
            if ($Shop['is_store_pei'] == 1) {
                if ($do['logistics_price'] > 0) {
                    //$logistics = $do['logistics_price'];
                    //新增配送管理员分成
                    $deliver = D('Delivery')->where(['id' => $do['delivery_id']])->find();
                    $deliveradmin = D('Applicationmanagement')->where(['user_id' => $deliver['recommend']])->find();
                    //查询配送员上级是否是管理员
                    if (!empty($deliver['recommend']) && !empty($deliveradmin)) {
                        //取出第一级分成
                        $dengji = D('Deliveryadmin')->where(['dj_id' => $deliveradmin['dj_id']])->find();
                        //计算分成
                        $fen1 = round($do['logistics_price'] * ($dengji['fencheng'] / 100), 2);
                        D('Users')->addMoney($deliveradmin['user_id'], $fen1, '配送员配送获得分成奖励' . $fen1);
                        $data1 = array('d_id' => $deliver['id'], 'sq_id' => $deliveradmin['sq_id'], 'order_id' => $order_id, 'order_type' => 2, 'money' => $fen1,
                            'time' => date('Y-m-d H:i:s'), 'explain' => '便利店获得分成');
                        D('DeliveryDivide')->add($data1);
                        //判断是否存在上级
                        $deliveradmin2 = D('Applicationmanagement')->where(['user_id' => $deliveradmin['recommend']])->find();
                        if (!empty($deliveradmin2) && $deliveradmin['dj_id'] > $deliveradmin2['dj_id']) {
                            //取出二级分成
                            $dengji2 = D('Deliveryadmin')->where(['dj_id' => $deliveradmin2['dj_id']])->find();
                            $fen2 = round($do['logistics_price'] * ($dengji2['fencheng']/ 100), 2);
                            D('Users')->addMoney($deliveradmin2['user_id'], $fen2, '配送员配送获得分成奖励' . $fen1);
                            $data2 = array('d_id' => $deliver['id'], 'sq_id' => $deliveradmin2['sq_id'], 'order_id' => $order_id, 'order_type' => 2, 'money' => $fen2,
                                'time' => date('Y-m-d H:i:s'), 'explain' => '便利店获得分成');
                            D('DeliveryDivide')->add($data2);
                            //判断第三级
                            $deliveradmin3 = D('Applicationmanagement')->where(['user_id' => $deliveradmin2['recommend']])->find();
                            if (!empty($deliveradmin3) && $deliveradmin2['dj_id'] > $deliveradmin3['dj_id']) {
                                //取出二级分成
                                $dengji3 = D('Deliveryadmin')->where(['dj_id' => $deliveradmin3['dj_id']])->find();
                                $fen3 = round($do['logistics_price'] * ($dengji3['fencheng'] / 100), 2);
                                D('Users')->addMoney($deliveradmin3['user_id'], $fen3, '配送员配送获得分成奖励' . $fen1);
                                $data3 = array('d_id' => $deliver['id'], 'sq_id' => $deliveradmin3['sq_id'], 'order_id' => $order_id, 'order_type' => 2, 'money' => $fen3,
                                    'time' => date('Y-m-d H:i:s'), 'explain' => '便利店获得分成');
                                D('DeliveryDivide')->add($data3);
                            }
                        }
                        $logistics = $do['logistics_price'] - $fen1 - $fen2 - $fen3;
                        $deliveradminpay=$fen1+$fen2+$fen3;
                    } else {
                        $logistics = $do['logistics_price'];
                        $deliveradminpay=0;
                    }
                    D('Runningmoney')->add_delivery_logistics($order_id, $logistics,4,$deliveradminpay);//配送费接口
                    return true;
                }
            } else {
                return true;
            }
        }
    }



	public function store_print($order_id,$addr_id) {	
			$order_id = (int) $order_id;
			$addr_id = (int) $addr_id;	
			$order = D('Storeorder')->find($order_id);
			if (empty($order))//没有找到订单返回假
            return false;
			if($order['is_daofu'] == 1){
				$fukuan = '货到付款';
			}else{
				$fukuan = '在线支付';
			}
            $member = D('Users')->find($order['user_id']);//会员信息
			if(!empty($addr_id)){
				$addr_id = $addr_id;	
			}else{
				$addr_id = $order['addr_id'];
			}
			$user_addr = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
			$shop_print = D('Shop')->where(array('shop_id'=> $order['shop_id']))->find();//商家信息
            $msg .= '@@2点菜清单__________NO:' . $order['order_id'] . '\r';
            $msg .= '店名：' . $shop_print['shop_name'] . '\r';
            $msg .= '联系人：' . $user_addr['name'] . '\r';
            $msg .= '电话：' . $user_addr['mobile'] . '\r';
            $msg .= '客户地址：' . $user_addr['addr'] . '\r';
            $msg .= '用餐时间：' . date('Y-m-d H:i:s', $order['create_time']) . '左右\r';
            $msg .= '用餐地址：' . $shop_print['addr'] . '\r';
            $msg .= '商家电话：' . $shop_print['tel'] . '\r';
            $msg .= '----------------------\r';
            $msg .= '@@2菜品明细\r';
            $products = D('Storeorderproduct')->where(array('order_id' => $order['order_id']))->select();
            foreach ($products as $key => $value) {
                $product = D('Storeproduct')->where(array('product_id' => $value['product_id']))->find();
                $msg		  .= ($key+1).'.'.$product['product_name'].'—'.($product['price']).'元'.'*'.$value['num'].'份\r';
            }
            $msg .= '----------------------\r';
            $msg .= '@@2支付方式：' . $fukuan . '\r';
            $msg .= '外送费用：' . $order['logistics']  . '元\r';
			
			$msg .= '菜品金额：' .'总价'. round($order['total_price'] ). '元-新单立减'.round($order['new_money'] ).'元-免配送费'.round($order['logistics_full_money']).'元-满减优惠'.round($order['full_reduce_price'] ).'元=应付金额'.round($order['need_pay'] ). '元\r';
			
            $msg .= '应付金额：' . $order['need_pay'] . '元\r';
			$msg .= '留言：'.$order['message'].'\r';
			return $msg;//返回数组
   }
   //打印接口中间件
   public function combination_store_print($order_id,$addr_id) {	
  		    $order = D('StoreOrder') -> where('order_id =' . $order_id) -> find();
			$shops = D('Shop') -> find($order['shop_id']);
			//便利店打印开始
			if($shops['is_ele_print'] ==1){
			  $msg = $this->store_print($order['order_id'],$order['addr_id']);
			  $result = D('Print')->printOrder($msg, $shops['shop_id']);
			  $result = json_decode($result);
			  $backstate = $result -> state;
			  $market = D('Store') -> find($order['shop_id']);
			  if($market['is_print_deliver'] ==1){//如果开启自动打印
				  if ($backstate == 1) {
						if($shops['is_store_pei'] ==1){//1代表没开通配送确认发货步骤
							D('Storeorder')->where(array('order_id' =>$order_id)) -> save(array('status' => 2,'is_print'=>1,'orders_time' => NOW_TIME));
						}else{//如果是配送配送只改变打印状态
							 D('Storeorder') -> save(array('is_print'=>1), array("where" => array('order_id' => $order['order_id'])));
						}
					}	
			 }	
				
		    }
		  return true;
	  }
			
		//更新红包抵扣部分
	public function updUseEnvelope($order_id)
	{
		$order = D('Storeorder')->find($order_id);
		//更新红包状态
		D('UserEnvelope')->where(['user_envelope_id'=>$order['useEnvelope_id']])->save(['close'=>2]);
		//写入使用记录
		D('UserEnvelopeLogs')->add([
			'user_id'=>$order['user_id'],
			'envelope' => $order['useEnvelope'],
			'intro' =>'在便利店购买商品使用，订单号为'.$order['order_id'],
			'create_time' => NOW_TIME,
			'create_ip' =>get_client_ip()
		]);
		return true;
	}					
						
   public function store_delivery_order($order_id,$wait = 0) {	
   			$order_id = (int) $order_id;
			if($wait == 0){
				$status = 1;
			}else{
				$status = 0;
			}
  			$order = D('Storeorder')->find($order_id);
			if (empty($order)){
				 return false;//没有找到订单返回假
			}
			
			$res = D('DeliveryOrder')->where(array('type'=>'4','type_order_id'=>$order_id))->find();//查询是不是已经插入了
			
			$DeliveryOrder = D('DeliveryOrder');
            $shops = D('Shop')->where(array('shop_id'=>$order['shop_id']))->find();
			
			if(!$Useraddr = D('Useraddr')->find($order['addr_id'])){
				return false;//没有找到用户地址返回假
			}
			
			if($store = D('Store')->find($order['shop_id'])){
				if(!empty($store['given_distribution'])){
					$is_appoint = 1;
				}else{
					$is_appoint  = 0;
				}
			}else{
				return false;//没有找到便利店商家返回假
			}

               $config = D('Setting')->fetchAll();

               //查询专送配送差价
               $chajia=$config['freight']['store_chajia'];//最低公里差价
               $zdgls=$config['freight']['store_distance'];//最低公里数
               $jiachajia=$config['freight']['store_jia'];//每超过1公里，加扣差价

               //查询直达配送差价
               $zdchajia=$config['freight']['zdstore_chajia'];//最低公里差价
               $zdzdgls=$config['freight']['zdstore_distance'];//最低公里数
               $zdjiachajia=$config['freight']['zdstore_jia'];//每超过1公里，加扣差价
			
			if($order['logistics'] > 0){
                if($order['type']==1){
                    if($order['distance']>$zdgls){
                        $logistics_price=$order['logistics']- (($order['distance']-$zdgls)*$jiachajia);
                    }else{
                        $logistics_price=$order['logistics']- $chajia;
                    }
                }elseif($order['type']==2){
                    if($order['distance']>$zdzdgls){
                        echo 1;
                        $logistics_price=$order['logistics']- (($order['distance']-$zdzdgls)*$zdjiachajia);
                    }else{
                        echo 2;
                        $logistics_price = $order['logistics']-$zdchajia;
                    }
                }
			}else{
				$logistics_price =($store['logistics'] > 10) ? $store['logistics'] : $shops['express_price'];
			}

           if(!empty($order['fruit'])){
               $guoz=$order['fruit'];
           }else{
               $guoz=0;
           }

       if($shops['is_store_pei'] == 1 && !$res){
				$deliveryOrder_data = array(
						'type' => 4, 
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
						'name' => $Useraddr['name'],
						'mobile' => $Useraddr['mobile'],
						'addr' => $Useraddr['addr'],
						'addr_id' => $order['addr_id'], 
						'address_id' => $order['address_id'], 
						'logistics_price' => $logistics_price, //订单配送费
						'intro' => $order['message'], //订单备注
						'is_appoint' => $is_appoint, //状态1位指定配送员
						'appoint_user_id' => $store['given_distribution'], //指定配送员ID
						'create_time' => time(), 
						'update_time' => 0,
						'peisong_type'=>$order['type'],
						'status' => $status,
						'closed'=>0,
                        'distance'=>$order['distance'],//配送距离
                        'shijian'=>date('Y-m-d',time()),
                        'guoz'=>$guoz
					);
				$order_id = D('DeliveryOrder')->add($deliveryOrder_data);
			}
			D('Storeorder')->where(['order_id'=>$order_id])->save(['status'=>9]);
			D('Sms')->sms_delivery_user($order_id,$type=4);//短信通知配送员
			D('Weixintmpl')->delivery_tz_user($order_id,$type=4);//微信消息全局通知
			return true;
	}
	
	public function store_month_num($order_id) {	
   	   $order_id = (int) $order_id;
       $storeorderproduct = D('Storeorderproduct')->where('order_id =' . $order_id)->select();
       foreach ($storeorderproduct as $k => $v) {
       	 D('Storeproduct')->updateCount($v['product_id'], 'sold_num', $v['num']);
		 D('Store')->updateCount($v['shop_id'], 'sold_num', $v['num']);
       }
      return TRUE;
	}
	//订单导出获取订单状态
	public function get_export_store_order_status($order_id) {	
   	   $order = D('Storeorder')->find($order_id);
       if($order['is_daofu'] ==1){
		   return '货到付款';
		}else{
			return $this->cfg[$order['status']];
		}
	}

	
	//订单导出获取订单的商品信息
	public function get_export_store_order_product($order_id) {	
   	  $storeorderproduct = D('Storeorderproduct')->where(array('order_id'=>$order_id))->select();
	  foreach ($storeorderproduct as $k => $v) {
       	 $storeorderproduct[$k]['name'] = $this->get_store_product_name($v['product_id']);
		 $storeorderproduct[$k]['num'] = $v['num'];
		 $storeorderproduct[$k]['total_price'] = $v['total_price'];
      }
	  return  $storeorderproduct[$k]['name'].'*'.$storeorderproduct[$k]['num'].'='.$storeorderproduct[$k]['total_price'];
	}
	
	//订单导出获取订单状态
	public function get_store_product_name($product_id) {	
   	   $storeproduct = D('Storeproduct')->find($product_id);
       return $storeproduct['product_name'];
	}
	
	//获取用户等待时间
	public function get_wait_time($order_id) {	
   	   $storeorder = D('Storeorder')->find($order_id);
       if($storeorder){
		   $now_time = time();
		   $cha_time = $now_time-$storeorder['pay_time'];
		   return  ele_wait_Time($cha_time);
		}else{
		   return  false;
		}
	}
	
	//获取用户等待时间分钟数
	public function get_wait_time_minutes($order_id) {	
   	   $storeorder = D('Storeorder')->find($order_id);
       if($storeorder){
		   $now_time = time();
		   $cha_time = $now_time-$storeorder['pay_time'];
		   return  $cha_time/60;
		}else{
		   return  false;
		}
	}
	
	//获取当前订单是否达到免邮条件
	public function get_logistics($total_money,$shop_id){	
	   $store =D('Store')->where(array('shop_id' => $shop_id) )->find();
	   //var_dump($total_money);die;
	   if($store['logistics_full']<= $total_money){
		   if($total_money >= $store['logistics_full']){
			   return  $store['logistics_full'];
			}else{
				return 0; 
		    }
	   }else{
		  return 0; 
	   }
	}
	
	//获取当前订单满减
	public function get_full_reduce_price($total_money,$shop_id){
	   $store = D('Store')->find($shop_id);
	   if($store['is_full'] == 1){
		   //第一种可能
		   if(!empty($store['order_price_full_1']) && !empty($store['order_price_full_2'])){
			   //中间
			   if($total_money >= $store['order_price_full_1'] && $total_money <= $store['order_price_full_2']){
				   if($store['order_price_reduce_1'] > 0){
					  return $store['order_price_reduce_1'];   
				   }
				}
				//大于第二个满减
				if($total_money >= $store['order_price_full_2']){
				   if($store['order_price_reduce_2'] > 0){
					  return $store['order_price_reduce_2'];   
				   }
				}
				if($total_money <= $store['order_price_full_1']){
				   return 0; //不返回
				}
			}
			//第二种可能
			if(!empty($store['order_price_full_1'])){
			   if($total_money >= $store['order_price_full_1']){
				   if($store['order_price_reduce_1'] > 0){
					  return $store['order_price_reduce_1'];   
				   }
				}
			   if($total_money <= $store['order_price_full_1']){
				   return 0; //不返回
				}
			}
			return 0; 
	   }else{
		  return 0; 
	   }
	}


    //红包获取部分
    public function GetuseEnvelope($uid,$shop_ids)
    {

        $shop=D('UserEnvelope')->where(array('shop_id'=>array('IN',$shop_ids),'user_id'=>$uid,'close'=>2,'is_use'=>0))->select();

        //判断是否有商家红包
        if(!empty($shop)){
            $map=array('user_id'=>$uid,'close'=>2,'is_use'=>0,'shop_id'=>array('IN',$shop_ids));

            $UsersEnvelope=D('UserEnvelope')->where($map)->select();
        }else{
            $map=array('user_id'=>$uid,'close'=>2,'shop_id'=>0,'is_use'=>0);
            $UsersEnvelope=D('UserEnvelope')->where($map)->select();
        }

        $useEnvelope = array();
        foreach ($UsersEnvelope as $key => $value) {
            $useEnvelope[$key]['type'] = $value['type'];
            $useEnvelope[$key]['envelope']+=$value['envelope'];
            $useEnvelope[$key]['useEnvelope_id'] = $value['user_envelope_id'];
        }

        //红包重组
        foreach ($useEnvelope as $k => $val) {
            switch ($val['type']) {
                case '1':
                    $val['type'] = '订单红包折扣';
                    break;

                case '2':
                    $val['type'] = '引流红包折扣';
                    break;

                case '3':
                    $val['type']='平台通用红包';
            }
            $useEnvelope[$k]['type'] = $val['type'];
        }
        // print_r($useEnvelope);die;
        return $useEnvelope;
    }



}

