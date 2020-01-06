<?php

class EleorderModel extends CommonModel {
    protected $pk = 'order_id';
    protected $tableName = 'ele_order';
    protected $cfg = array(
        0 => '待付款',
        1 => '等待商家接单',
        9 => '商家已接单',
        10=> '派送员已接单',
        11 => '派送员已取餐，正在配送中',
		2 => '派送员已完成配送',
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
        $config = D('Setting')->fetchAll();
		$Useraddr = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
		$Shop = D('Shop')->find($shop_id);
		$Ele = D('Ele')->where(array('shop_id'=>$shop_id))->find();
		$getAddrDistance = getAddrDistance($Useraddr['lat'], $Useraddr['lng'], $Ele['lat'], $Ele['lng']);
		
		//var_dump($getAddrDistance);
//		if(empty($Ele['is_radius'])){
//			$radius = 5000;
//		}else{
//			$radius = $Ele['is_radius']*1000;
//		}
        //平台制定的时间
        $radius=$config['freight']['radius']*1000;
		if($getAddrDistance > $radius){
			return false;
		}
		return true;
	}

		
		
		
	//取消，删除订单逻辑封装
	public function cancel($order_id,$user_id){
		if($detail = $this->find($order_id)){
			$Shop = D('Shop')->find($detail['shop_id']);
			$obj = D('DeliveryOrder');
			if($Shop['is_ele_pei'] == 1){
            	$do = $obj->where(array('type_order_id' => $order_id, 'type' => 1))->find();
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
					if(!$obj->where(array('type_order_id' => $order_id, 'type' => 1))->save(array('closed'=>1))){
						$this->error = '抢单模式更新配送数据库失败';
						return false;
					}
				}
			}
			if($this->where(array('order_id'=>$order_id))->save(array('closed'=>1))){
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 11);
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 11);
			}else{
				$this->error = '更新数据库失败';
				return false;
			}
		}else{
			$this->error = '订单信息错误';
			return false;
		}
	}
	//删除过期外卖订单,商家id，会员ID，可选
	public function past_due_ele_order($shop_id ,$user_id){
		$config = D('Setting')->fetchAll();
		$past_due_ele_order_time = isset($config['ele']['past_due_ele_order_time']) ? (int)$config['ele']['past_due_ele_order_time'] : 15;
        $time = NOW_TIME - $past_due_ele_order_time * 60;
		$list = $this->where(array('closed'=>0,'status'=>0))->select();
		foreach ($list as $key => $val){
            if($val['create_time'] < $time){ 
                $this->cancel($val['order_id']);
            }
        }
		return true;
	}
	
	
	//根据订单ID获取外卖订单名称
	public function get_ele_order_product_name($order_id){
		    $order = D('Eleorder')->find($order_id);
            $product_ids = D('Eleorderproduct')->where('order_id=' . $order_id)->getField('product_id', true);
            $product_ids = implode(',', $product_ids);
            $map = array('product_id' => array('in', $product_ids));
            $product_name = D('Eleproduct')->where($map)->getField('product_name', true);
            $product_name = implode(',', $product_name);
			return $product_name;
		 
    }
		
	//退款逻辑封装
	public function ele_user_refund($order_id){
		$detail = $this->where('order_id =' . $order_id)->find();
		if(!$detail = $this->where('order_id =' . $order_id)->find()){
           $this->error = '没有找到订单';
		   return false;
        }else{
			if(!$Shop = D('Shop')->find($detail['shop_id'])){
			   $this->error = '没有找到该订单的商家信息';
			   return false;
			}else{
				if($Shop['is_ele_pei'] == 1){
					$do = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();
					if($do && $do['status'] != 1){
						$this->error = '亲，当前状态不能退款啦';
						return false;
					}
					if($do){
						if(!$res = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' =>1))->setField('closed', 1)){
							$this->error = '申请退款更新配送信息错误，请稍后再试';
							return false;
						}
					}
			     }
				 
				if($this->where('order_id =' . $order_id)->setField('status', 3)){
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 3);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 3);
					return true;
				}else{
					$this->error = '更新退款状态失败';
					return false;
				}
			}
        }
    }
	 


    public function overOrder($order_id) {
		if($detail = D('Eleorder')->find($order_id)){
			if($detail['status'] != 2){
				return false;
			}else{
				$Ele = D('Ele')->find($detail['shop_id']);
				if (D('Eleorder')->save(array('order_id' => $order_id, 'status' => 8,'end_time' => NOW_TIME))) { //防止并发请求
					$Intro = $detail['settlementIntro'];//获取结算说明
                   // var_dump($Intro,$detail['settlement_price']);die;
					D('Shopmoney')->insertData($order_id,$id ='0',$detail['shop_id'],$detail['settlement_price'],$type ='ele',$Intro);//结算给商家
					D('Users')->AddUser_guide($detail['user_id'],$detail['settlement_price'],$order_id,$type='ele');
					$this->AddDeliveryIogistics($order_id);//结算配送费给配送员
					if($detail['settlement_price'] > 0) {
						D('Users')->integral_restore_user($detail['user_id'],$order_id,$id ='0',$detail['settlement_price'],$type ='ele');//外卖购物返利积分
					}
					
					D('Users')->AddUser_guide($detail['user_id'],$detail['settlement_price'],$order_id,$type='ele');//外卖分销处理 ----新增
					D('Eleorderproduct')->updateByOrderId($order_id);
					D('Ele')->updateCount($detail['shop_id'], 'sold_num'); //这里是订单数
					D('Ele')->updateMonth($detail['shop_id']);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 8);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 8);
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
	public function AddDeliveryIogistics($order_id){
		
		if($detail = D('Eleorder')->find($order_id)){
			$ele = D('Ele')->find($detail['shop_id']);
        	$shop = D('Shop')->find($detail['shop_id']);
			$do = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();//0是商城1是配送
			
			//首先是配送状态
			if($shop['is_ele_pei'] == 1){
				if($do['logistics_price'] > 0){
					//$logistics = $do['logistics_price'];
				//新增配送管理员分成
                $deliver=D('Delivery')->where(['id'=>$do['delivery_id']])->find();
                $deliveradmin=D('Applicationmanagement')->where(['user_id'=>$deliver['recommend']])->find();
                //查询配送员上级是否是管理员
				if(!empty($deliver['recommend']) && !empty($deliveradmin['user_id'])){
                    //取出第一级分成
                    $dengji=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin['dj_id']])->find();
                    //计算分成
                    $fen1=round($do['logistics_price']*($dengji['fencheng']/100),2);
                    D('Users')->addMoney($deliveradmin['user_id'],$fen1,'配送员配送获得分成奖励'.$fen1);
                    $data1=array('d_id'=>$deliver['id'],'sq_id'=>$deliveradmin['sq_id'],'order_id'=>$order_id,'order_type'=>1,'money'=>$fen1,
                    'time'=>date('Y-m-d H:i:s'),'explain'=>'外卖获得分成');
                    D('DeliveryDivide')->add($data1);
                    //判断是否存在上级
                    $deliveradmin2=D('Applicationmanagement')->where(['user_id'=>$deliveradmin['recommend']])->find();
                    if(!empty($deliveradmin2['user_id']) && $deliveradmin['dj_id']> $deliveradmin2['dj_id']){
                        //取出二级分成
                        $dengji2=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin2['dj_id']])->find();
                        $fen2=round($do['logistics_price']*($dengji2['fencheng']/100),2);
                        D('Users')->addMoney($deliveradmin2['user_id'],$fen2,'配送员配送获得分成奖励'.$fen1);
                        $data2=array('d_id'=>$deliver['id'],'sq_id'=>$deliveradmin2['sq_id'],'order_id'=>$order_id,'order_type'=>1,'money'=>$fen2,
                            'time'=>date('Y-m-d H:i:s'),'explain'=>'外卖获得分成');
                        D('DeliveryDivide')->add($data2);
                        //判断第三级
                        $deliveradmin3=D('Applicationmanagement')->where(['user_id'=>$deliveradmin2['recommend']])->find();
                        if(!empty($deliveradmin3['user_id']) && $deliveradmin2['dj_id']> $deliveradmin3['dj_id']){
                            //取出二级分成
                            $dengji3=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin3['dj_id']])->find();
                            $fen3=round($do['logistics_price']*($dengji3['fencheng']/100),2);
                            D('Users')->addMoney($deliveradmin3['user_id'],$fen3,'配送员配送获得分成奖励'.$fen1);
                            $data3=array('d_id'=>$deliver['id'],'sq_id'=>$deliveradmin3['sq_id'],'order_id'=>$order_id,'order_type'=>1,'money'=>$fen3,
                                'time'=>date('Y-m-d H:i:s'),'explain'=>'外卖获得分成');
                            D('DeliveryDivide')->add($data3);
                        }
                    }
                    $logistics=$do['logistics_price']-$fen1-$fen2-$fen3;
                    $deliveradminpay=$fen1+$fen2+$fen3;

                }else{
                    $logistics=$do['logistics_price'];
                    $deliveradminpay=0;
                }
        }else{
            $logistics = $shop['express_price'];
        }
				D('Runningmoney')->add_delivery_logistics($order_id,$logistics,1,$deliveradminpay);//配送费接口
				D('DeliveryOrder')->getPeakMoney($order_id);//新增高峰期配送费结算
				D('DeliveryOrder')->getPunishment($order_id);//订单超时处理
				return true;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	public function ele_print($order_id,$addr_id) {	
			$order_id = (int) $order_id;
			$addr_id = (int) $addr_id;	
			$order = D('Eleorder')->find($order_id);
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
            $products = D('Eleorderproduct')->where(array('order_id' => $order['order_id']))->select();
            foreach ($products as $key => $value) {
                $product = D('Eleproduct')->where(array('product_id' => $value['product_id']))->find();
                $msg		  .= ($key+1).'.'.$product['product_name'].'—'.($product['price']).'元'.'*'.$value['num'].'份\r';
            }
            $msg .= '----------------------\r';
            $msg .= '@@2支付方式：' . $fukuan . '\r';
            $msg .= '外送费用：' . $order['logistics']  . '元\r';
			
			$msg .= '菜品金额：' .'总价'. round($order['total_price'] ). '元-新单立减'.round($order['new_money'] ).'元-免配送费'.round($order['logistics_full_money']).'元-满减优惠'.round($order['full_reduce_price']).'元=应付金额'.round($order['need_pay'] ). '元\r';
			
            $msg .= '应付金额：' . $order['need_pay']. '元\r';
			$msg .= '留言：'.$order['message'].'\r';
			return $msg;//返回数组
   }
   //打印接口中间件
   public function combination_ele_print($order_id,$addr_id) {	
  		    $order = D('EleOrder') -> where('order_id =' . $order_id) -> find();
			$shops = D('Shop') -> find($order['shop_id']);
			//外卖打印开始
			if($shops['is_ele_print'] ==1){
			  $msg = $this->ele_print($order['order_id'],$order['addr_id']);
			  $result = D('Print')->printOrder($msg, $shops['shop_id']);
			  $result = json_decode($result);
			  $backstate = $result -> state;
			  $ele = D('Ele') -> find($order['shop_id']);
			  if($ele['is_print_deliver'] ==1){//如果开启自动打印
				  if ($backstate == 1) {
						if($shops['is_ele_pei'] ==1){//1代表没开通配送确认发货步骤
							D('EleOrder')->where(array('order_id' =>$order_id)) -> save(array('status' => 2,'is_print'=>1,'orders_time' => NOW_TIME));
						}else{//如果是配送配送只改变打印状态
							 D('EleOrder') -> save(array('is_print'=>1), array("where" => array('order_id' => $order['order_id'])));
						}
					}	
			 }	
				
		    }
		  return true;
	  }
						
						
   public function ele_delivery_order($order_id,$wait = 0) {	
   			$order_id = (int) $order_id;
			if($wait == 0){
				$status = 1;
			}else{
				$status = 0;
			}
			$order = D('Eleorder')->find($order_id);
  			
			if (empty($order)){
				 return false;//没有找到订单返回假
			}
			
			$res = D('DeliveryOrder')->where(array('type'=>'1','type_order_id'=>$order_id))->find();//查询是不是已经插入了
		
			
			$DeliveryOrder = D('DeliveryOrder');
            $shops = D('Shop')->where(array('shop_id'=>$order['shop_id']))->find();
			
			if (!$Useraddr = D('Useraddr')->find($order['addr_id'])) {
				return false;//没有找到用户地址返回假
			}
			
			if ($ele = D('Ele')->find($order['shop_id'])) {
				if(!empty($ele['given_distribution'])){
					$is_appoint = 1;
				}else{
					$is_appoint  = 0;
				}
			}else{
				return false;//没有找到外卖商家返回假
			}
			//查询专送配送差价
            $config = D('Setting')->fetchAll();
//            $zscha=D('Freight')->select();
//			//查询直达配送差价
//            $zdcha=D('Zdfreifht')->select();
            //专送                                    //差价
            $monry=$config['freight']['price_1'];   $chajia=$config['freight']['chajia'];
            $monry2=$config['freight']['price_2'];  $chajia2=$config['freight']['chajia2'];
            $monry3=$config['freight']['price_3'];  $chajia3=$config['freight']['chajia3'];
            $monry4=$config['freight']['price_4'];  $chajia4=$config['freight']['chajia4'];
            $monry5=$config['freight']['price_5'];  $chajia5=$config['freight']['chajia5'];

            //直达                                            //差价
           $zdmonry=$config['freight']['zdprice_1'];         $zdchajia=$config['freight']['zdchajia'];
           $zdmonry2=$config['freight']['zdprice_2'];        $zdchajia2=$config['freight']['zdchajia2'];
           $zdmonry3=$config['freight']['zdprice_3'];        $zdchajia3=$config['freight']['zdchajia3'];
           $zdmonry4=$config['freight']['zdprice_4'];        $zdchajia4=$config['freight']['zdchajia4'];
           $zdmonry5=$config['freight']['zdprice_5'];        $zdchajia5=$config['freight']['zdchajia5'];




       //先看订单的配送费
			if($order['logistics'] > 0){
			    if($order['type']==1){
                    if($order['logistics']==$monry){
                        $logistics_price = $order['logistics']-$chajia;
                    }elseif($order['logistics']==$monry2){
                        $logistics_price = $order['logistics']-$chajia2;
                    }elseif($order['logistics']==$monry3){
                        $logistics_price = $order['logistics']-$chajia3;
                    }elseif ($order['logistics']==$monry4){
                        $logistics_price = $order['logistics']-$chajia4;
                    }elseif ($order['logistics']==$monry5){
                        $logistics_price = $order['logistics']-$chajia5;
                    }
                }elseif($order['type']==2){
                    if($order['logistics']==$zdmonry){
                        $logistics_price = $order['logistics']-$zdchajia;
                    }elseif($order['logistics']==$zdmonry2){
                        $logistics_price = $order['logistics']-$zdchajia2;
                    }elseif($order['logistics']==$zdmonry3){
                        $logistics_price = $order['logistics']-$zdchajia3;
                    }elseif ($order['logistics']==$zdmonry4){
                        $logistics_price = $order['logistics']-$zdchajia4;
                    }elseif ($order['logistics']==$zdmonry5){
                        $logistics_price = $order['logistics']-$zdchajia5;
                    }
                }elseif($order['type']==4){
					$logistics_price=0;
				}

			}else{
				$logistics_price =  ($ele['logistics'] > 10) ? $ele['logistics'] : $shops['express_price'];
			}

			if(!empty($order['fruit'])){
                $guoz=$order['num'];
            }else{
			    $guoz=0;
            }
		
			
			//解决重复下单
			if($shops['is_ele_pei'] == 1 && !$res && $order['type'] !=4){

				$deliveryOrder_data = array(
						'type' => 1, 
						'type_order_id' => $order['order_id'], 
						'delivery_id' => 0, 
						'shop_id' => $order['shop_id'],
						'city_id' => $shops['city_id'],
						'area_id' => $shops['area_id'], 
						'business_id' => $shops['business_id'],  
						'lat' => $Useraddr['lat'], 
						'lng' => $Useraddr['lng'],  
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
						'appoint_user_id' => $ele['given_distribution'], //指定配送员ID
						'create_time' => time(), 
						'update_time' => 0,
                        'peisong_type'=>$order['type'],
						'status' => $status,
						'closed'=>0,
                        'distance'=>$order['distance'],//配送距离
                        'shijian'=>date('Y-m-d',time()),
                        'guoz'=>$guoz
					);
                 $config = D('Setting')->fetchAll();
                $fenz=$config['ele']['chao_times'];//最低分钟数
                $now = date('Y-m-d H:i:s',time());
                $jieshu=strtotime(date("Y-m-d H:i:s",strtotime("+".$fenz."minutes",strtotime($now))));
                D('Eleorder')->where(['order_id' => $order_id])->save(array('status' => 9,'pei_time'=>$jieshu));
				D('DeliveryOrder')->add($deliveryOrder_data);
				//D('Sms')->sms_delivery_user($order_id,$type=1);//短信通知配送员
				D('Weixintmpl')->delivery_tz_user($order_id,$type=1);//微信消息全局通知
			}
	}
	
	public function ele_month_num($order_id) {	
   	   $order_id = (int) $order_id;
       $Eleorderproduct = D('Eleorderproduct')->where('order_id =' . $order_id)->select();
       foreach ($Eleorderproduct as $k => $v) {
       	 D('Eleproduct')->updateCount($v['product_id'], 'sold_num', $v['num']);
		 D('Ele')->updateCount($v['shop_id'], 'sold_num', $v['num']);
       }
      return TRUE;
	}
	//订单导出获取订单状态
	public function get_export_ele_order_status($order_id) {	
   	   $order = D('Eleorder')->find($order_id);
       if($order['is_daofu'] ==1){
		   return '货到付款';
		}else{
			return $this->cfg[$order['status']];
		}
	}

	
	//订单导出获取订单的商品信息
	public function get_export_ele_order_product($order_id) {	
   	  $Eleorderproduct = D('Eleorderproduct')->where(array('order_id'=>$order_id))->select();
	  foreach ($Eleorderproduct as $k => $v) {
       	 $Eleorderproduct[$k]['name'] = $this->get_ele_product_name($v['product_id']);
		 $Eleorderproduct[$k]['num'] = $v['num'];
		 $Eleorderproduct[$k]['total_price'] = $v['total_price'];
      }
	  return  $Eleorderproduct[$k]['name'].'*'.$Eleorderproduct[$k]['num'].'='.$Eleorderproduct[$k]['total_price'];
	}
	
	//订单导出获取订单状态
	public function get_ele_product_name($product_id) {	
   	   $Eleproduct = D('Eleproduct')->find($product_id);
       return $Eleproduct['product_name'];
	}
	
	//获取用户等待时间
	public function get_wait_time($order_id) {	
   	   $Eleorder = D('Eleorder')->find($order_id);
       if($Eleorder){
		   $now_time = time();
		   $cha_time = $now_time-$Eleorder['pay_time'];
		   return  ele_wait_Time($cha_time);
		}else{
		   return  false;
		}
	}
	
	//获取用户等待时间分钟数
	public function get_wait_time_minutes($order_id) {	
   	   $Eleorder = D('Eleorder')->find($order_id);
       if($Eleorder){
		   $now_time = time();
		   $cha_time = $now_time-$Eleorder['pay_time'];
		   return  $cha_time/60;
		}else{
		   return  false;
		}
	}
	
	//获取当前订单是否达到免邮条件
	public function get_logistics($total_money,$shop_id){	
		//var_dump($total_money);
	   $Ele = D('Ele')->where(array('shop_id' => $shop_id) )->find();
	   //echo D('Ele')->getLastSql();
	   //var_dump($Ele['logistics_full']);die;
	   if($Ele['logistics_full']<= $total_money){
	   	//echo "totay: $total_money , logistics_full : " . $Ele['logistics_full'];
		   if($total_money >= $Ele['logistics_full']){
			   return  $Ele['logistics_full'];
			}else{
				return 0; 
		    }
	   }else{
		  return 0; 
	   }
	}
	
	//获取当前订单满减
	public function get_full_reduce_price($total_money,$shop_id){	
	   $Ele = D('Ele')->find($shop_id);
	   if($Ele['is_full'] == 1){
		   //第一种可能
		   if(!empty($Ele['order_price_full_1']) && !empty($Ele['order_price_full_2'])){
			   //中间
			   if($total_money >= $Ele['order_price_full_1'] && $total_money <= $Ele['order_price_full_2']){
				   if($Ele['order_price_reduce_1'] > 0){
					  return $Ele['order_price_reduce_1'];   
				   }
				}
				//大于第二个满减
				if($total_money >= $Ele['order_price_full_2']){
				   if($Ele['order_price_reduce_2'] > 0){
					  return $Ele['order_price_reduce_2'];   
				   }
				}
				if($total_money <= $Ele['order_price_full_1']){
				   return 0; //不返回
				}
			}
			//第二种可能
			if(!empty($Ele['order_price_full_1'])){
			   if($total_money >= $Ele['order_price_full_1']){
				   if($Ele['order_price_reduce_1'] > 0){
					  return $Ele['order_price_reduce_1'];   
				   }
				}
			   if($total_money <= $Ele['order_price_full_1']){
				   return 0; //不返回
				}
			}
			return 0; 
	   }else{
		  return 0; 
	   }
	}
	 //外卖佣金结算部分
    public function get_shop_ymoney($order_id)
    {
        //判断订单是否完成
        if(false ==($order = D('Eleorder')->find($order_id))){
        	return false;
        }
        if($order['status'] !=8){
        	return false;
        }
        //取商家设置的分享金额比例
        $shop = D('Ele')->where(['shop_id'=>$order['shop_id']])->find();
        // $shop['y_bili'] = 0.1;
        //取订单的支付金额计算佣金
        $money = $order['need_pay'] * ($shop['y_bili']/10000);
        $shopInfo = D('Shop')->find($order['shop_id']);
        //   如果正常情况下先考虑减去对应商家用户的资金，如果用户资金不足的时候考虑扣除商户的资金
        $user = M('Users')->where(['user_id'=>$shopInfo['user_id']])->find();
        if($user['money'] <$money){
        	//减去商家的用户金额并记录
	        D('Users')->addMoney($order['is_tui'],$money,'推荐下单获得奖励￥'.$money);
	        D('Users')->addGold($shopInfo['user_id'],-$money,'推荐下单获得奖励￥'.$money);
	        return true;
        }else{
        	//加入用户金额并记录   
        	D('Users')->addMoney($order['is_tui'],$money,'推荐下单获得奖励￥'.$money);
        	D('Users')->addMoney($shopInfo['user_id'],-$money,'用户推荐下单扣除奖励金￥'.$money);
        	return true;
        }
    }
	
	
}

