<?php
class  ApiAction extends CommonAction{
	
	
	 public function getProvince(){
        $province = D('Paddlist')->field('id,name')->where(array('upid' =>0))->select();
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $province);
        exit(json_encode($res));
    }
	
	public function getRegionByParentId(){
        $parent_id = I('parent_id');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
            $region_list = D('Paddlist')->field('id,name')->where(array('upid'=>$parent_id))->select();
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $region_list);
        }
        exit(json_encode($res));
    }
	
	public function dispatching() {        
        $goods_id = I('goods_id');
        $region_id = I('region_id');
		
		$Paddlists = D('Paddlist')->where(array('id'=>$region_id))->find();
		$Paddlist = D('Paddlist')->where(array('id'=>$Paddlists['upid']))->find();
		
	    $Pyunfeiprovinces = D('Pyunfeiprovinces')->where(array('id'=>$Paddlist['province_id']))->find();
		$Pyunfei = D('Pyunfei')->where(array('yunfei_id'=>$Pyunfeiprovinces['yunfei_id']))->find();
		
		$Goods = D('Goods')->where(array('kuaidi_id'=>$Pyunfei['kuaidi_id']))->find();
		
		if($Goods){
			if($Pyunfei['shouzhong']){
				$return_data['status'] = 1;
				$return_data['price'] = round($Pyunfei['shouzhong'],2);
			}else{
				$return_data['status'] = 1;
				$return_data['price'] = '免邮';
			}
		}else{
			$return_data['status'] = 1;
			$return_data['price'] = '无邮费';
		}
        exit(json_encode($return_data));
    }

    //用户支付
	public function weigh(){
	     $user_id=(int) $this->_param('user_id');
	     $user=D('Users')->where(['user_id'=>$user_id])->find();
	     if(IS_AJAX){
             $count1 = M('market_order')->where(array('status'=>0,'user_id' =>$user_id,'confirm'=>1,'closed'=>0))->count();
             if($count1 >= 1){
                 $status = 1;
                 $explain .= '您有待支付订单'.$count1.'个<br>';
             }
             if($count1 >= 1 && $user['is_voice_pay']==0){
                 $this->ajaxReturn(array('code'=>1,'status'=>$status,'message'=>$explain,'count'=>$count));
             }else{
                 $this->ajaxReturn(array('status'=>-1));
             }
         }
    }

    //用户称重
    public function weigh2(){
        $user_id=(int) $this->_param('user_id');
        $user=D('Users')->where(['user_id'=>$user_id])->find();
        if(IS_AJAX){
            $count2 = M('market_order')->where(array('user_id' =>$user_id,'confirm'=>0,'status'=>0,'closed'=>0))->count();
            if($count2 >= 1){
                $status = 2;
                $explain .= '您有待称重订单'.$count2.'个<br>';
            }
            if($count2>=1 && $user['is_voice_weigh']==0){
                $this->ajaxReturn(array('code'=>1,'status'=>$status,'message'=>$explain,'count'=>$count));
            }else{
                $this->ajaxReturn(array('status'=>-1));
            }
        }
    }

    //商家待称重订单
    public function shopwigh(){
        $shop_id = (int) $this->_param('shop_id');
        if(IS_AJAX){
            $count2 = M('market_order')->where(array('shop_id' =>$shop_id,'confirm'=>0,'status'=>0,'closed'=>0))->count();
            if($count2 >= 1){
                $status = 1;
                $explain .= '您有待称重订单'.$count2.'个<br>';
            }
            if($count2>=1){
                $this->ajaxReturn(array('code'=>1,'status'=>$status,'message'=>$explain,'count'=>$count2));
            }else{
                $this->ajaxReturn(array('status'=>-1));
            }
        }
    }


	
	public function reminds(){
		$shop_id = (int) $this->_param('shop_id');
		$this->cronyes($order_id=0);//处理应该自动收货的订单
		
		if(IS_AJAX){
			
			$count1 = D('Eleorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count2 = D('Order')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count3 = D('Marketorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count4 = D('Storeorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count5 = D('Hotel')->where(array('order_status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count6 = D('Bookingorder')->where(array('order_status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			$count7 = D('Tuanorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count(); 
			$count8 = D('Integralorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
			if($count1 >= 1){
				$status = 1;
				$explain .= '您有待处理外卖订单'.$count1.'个<br>';	
			}
			if($count2 >= 1){
				$status = 2;
				$explain .= '您有待处理商城订单'.$count2.'个<br>';	
			}
			if($count3 >= 1){
				$status = 3;
				$explain .= '您有待处理菜市场订单'.$count3.'个<br>';	
			}
			if($count4 >= 1){
				$status = 4;
				$explain .= '您有待处理便利店订单'.$count4.'个<br>';	
			}
			if($count5 >= 1){
				$status = 5;
				$explain .= '您有待处理酒店订单'.$count5.'个<br>';	
			}
			if($count6 >= 1){
				$status = 6;
				$explain .= '您有待处理订座订单'.$count6.'个<br>';	
			}
			if($count7 >= 1){
				$status = 2;
				$explain .= '您有待处理抢购订单'.$count7.'个<br>';
			}

			if($count8>=1){
			    $status = 2;
			    $explain .='您有待处理积分商城订单'.$count8.'个<br>';
            }
			
			if($count >= 1 || $count1 >= 1  || $count2 >= 1  || $count3 >= 1  || $count4 >= 1  || $count5 >= 1   || $count6 >= 1   || $count7 >= 1 || $count8 >= 1){
				$this->ajaxReturn(array('code'=>1,'status'=>$status,'message'=>$explain,'count'=>$count));
			}else{
				$this->ajaxReturn(array('code'=>0,'msg'=>'暂时没订单','count'=>0));
			}

			
			
        }      
	}
	
	
		
    //自动确认订单，新增菜市场，便利店
    public function cronyes($order_id,$type = '0',$admin_id = '0'){
		
	    $CONFIG = D('Setting')->fetchAll();
		$time = time();
		
		$goods_time = (($CONFIG['site']['goods'] > 1) ? $CONFIG['site']['goods'] :'7')*24*3600;
		$order_time = $time - $goods_time; 
		$list = D('Order')->where(array('status'=>'2','create_time'=>array(array('ELT',$order_time))))->select();
		
        //商城订单
	    if(is_array($list)){
		  	  $k = 0;
			  foreach($list as $var){
					$date = true;
					if(!$detial = D('Order')->find($var['order_id'])){
						$date = false;
					}
					if($detial['status'] != 2){
					   $date = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_goods_pei'] != 1){
					    $do = D('DeliveryOrder')->where(array('type_order_id' =>$var['order_id'],'type' =>0)) -> find();
						if($do['status'] != 8){
							$date = false;
						}
					}
					if($date){
						 $k++;
						 D('Order')->save(array('order_id'=>$var['order_id'],'status'=>3));
						 D('Order')->overOrder($var['order_id']); //确认到账入口
					}
			 }
			$explain .= '已结算商城订单【'.$k.'】单';
        }
		
	    //外卖订单
	    $ele_time = (($CONFIG['site']['ele'] > 1) ? $CONFIG['site']['ele'] :'3')*3600;
		$eleorder_time = $time - $ele_time; 
		$list1 = D('Eleorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$eleorder_time))))->select();
	
		if(is_array($list1)){
				$k1 = 0;
				foreach($list1 as $item){
					$dateele = true;
					if(!$detial = D('Eleorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_ele_pei'] == 0){
					   $do = D('DeliveryOrder')-> where(array('type_order_id' =>$item['order_id'],'type' =>1)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k1++;
						D('Eleorder')->overOrder($item['order_id']);
						D('Eleorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算外卖订单【'.$k1.'】单';	
		 }
		 
		 //菜市场订单
	    $market_time = (($CONFIG['site']['market'] > 1) ? $CONFIG['site']['market'] :'3')*3600;
		$marketorder_time = $time - $market_time; 
		$list3 = D('Marketorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$marketorder_time))))->select();
	
		if(is_array($list3)){
				$k3 = 0;
				foreach($list3 as $item){
					$dateele = true;
					if(!$detial = D('Marketorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_market_pei'] == 0){
					   $do = D('Marketorder')-> where(array('type_order_id' =>$item['order_id'],'type' =>3)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k3++;
						D('Marketorder')->overOrder($item['order_id']);
						D('Marketorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算菜市场订单【'.$k3.'】单';	
		 }
		 
		//便利店订单
	    $store_time = (($CONFIG['site']['store'] > 1) ? $CONFIG['site']['store'] :'3')*3600;
		$storeorder_time = $time - $store_time; 
		$list4 = D('Storeorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$storeorder_time))))->select();
	
		if(is_array($list4)){
				$k4 = 0;
				foreach($list4 as $item){
					$dateele = true;
					if(!$detial = D('Storeorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_store_pei'] == 0){
					   $do = D('DeliveryOrder')-> where(array('type_order_id' =>$item['order_id'],'type' =>4)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k4++;
						D('Storeorder')->overOrder($item['order_id']);
						D('Storeorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算便利店订单【'.$k4.'】单';	
		 }
		  
	  
	  
	
		 if($type == 1 && $admin_id){
			  $arr['admin_id'] = $admin_id;
			  $arr['type'] = 1;
			  $arr['intro'] = $explain;
			  $arr['create_time'] = NOW_TIME;
			  $arr['create_ip'] = get_client_ip();
			  M('AdminActionLogs')->add($arr);  
		 }
		 
		 if($type == 1){
			 $this->tuSuccess($explain, U('admin/index/main')); 
		 }else{
			 return true; 
		 }
    }

    //司机跑腿接单
    public function driver($id,$uid){
        $count2 = M('running_vehicle')->where(array('status'=>'1','closed'=>0))->count();
        if($count2 >= 1){
            $explain .= '您有待处理跑腿订单'.$count2.'个';
        }
        if($count2 >= 1){
            $this->ajaxReturn(array('code'=>1,'msg'=>$explain,'count'=>$count2));
        }else{
            $this->ajaxReturn(array('code'=>0,'msg'=>'暂时没订单','count'=>0));
        }
    }
	
	
	//处理单通知
	public function notice($id,$uid){
		
		$count = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>0))->count();
		$count1 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>1))->count();
		$count3 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>3))->count();
		$count4 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>4))->count();
		
		$count2 = M('Running')->where(array('status'=>'1','closed'=>0))->count();
		
		if($count >= 1){
			$explain .= '您有待处理商城订单'.$count.'个';	
		}
		if($count1 >= 1){
			$explain .= '您有待处理外卖订单'.$count1.'个';	
		}
		if($count2 >= 1){
			$explain .= '您有待处理跑腿订单'.$count2.'个';	
		}
		if($count3 >= 1){
			$explain .= '您有待处理菜市场订单'.$count3.'个';	
		}
		if($count4 >= 1){
			$explain .= '您有待处理便利店订单'.$count4.'个';	
		}
		
		if($count >= 1 || $count1 >= 1  || $count2 >= 1  || $count3 >= 1  || $count4 >= 1){
			$this->ajaxReturn(array('code'=>1,'msg'=>$explain,'count'=>$count));
		}else{
			$this->ajaxReturn(array('code'=>0,'msg'=>'暂时没订单','count'=>0));
		}
	}

	//查询各种订单
	public function dingdan(){
        $user_id = (int) $this->_param('user_id');
        if(IS_AJAX) {
            //家政
            $count = D('Appointorder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count>=1){$sum=$count;}else{$sum=0;}
            //酒店
            $count1 = D('Hotelorder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count1>=1){$sum1=$count1;}else{$sum1=0;}
            //教育
            $count2 = D('EduOrder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count2>=1){$sum2=$count2;}else{$sum2=0;}
            //农家乐
            $count3 = D('FarmOrder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count3>=1){$sum3=$count3;}else{$sum3=0;}
            //KTV
            $count4 = D('KtvOrder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count4>=1){$sum4=$count4;}else{$sum4=0;}
            //跑腿
            $count5 = D('Running')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count5>=1){$sum5=$count5;}else{$sum5=0;}
            //司机跑腿
            $count6 = D('Runningvehicle')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count6>=1){$sum6=$count6;}else{$sum6=0;}
            //在线抢购
            $count7 = D('Tuanorder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count7>=1){$sum7=$count7;}else{$sum7=0;}
            //0元抢购
            $count8 = D('Pindanorder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count8>=1){$sum8=$count8;}else{$sum8=0;}
            //积分商城
            $count9 = D('Integralorder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count9>=1){$sum9=$count9;}else{$sum9=0;}
            //预约
            $count10 = D('Lifereserve')->where(array('user_id' =>$user_id,'close'=>0))->count();
            if($count10>=1){$sum10=$count10;}else{$sum10=0;}
            //会员礼品
            $count11 = D('ExchangeOrder')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count11>=1){$sum11=$count11;}else{$sum11=0;}
            //商品收藏
            $count12 = D('Goodsfavorites')->where(array('user_id' =>$user_id))->count();
            if($count12>=1){$sum12=$count12;}else{$sum12=0;}
            //商家预约
            $count13 = D('Shopyuyue')->where(array('status'=>1,'user_id' =>$user_id,'closed'=>0))->count();
            if($count13>=1){$sum13=$count13;}else{$sum13=0;}
            //预定订单
            $ele=D('Eleorder')->where(array('user_id' =>$user_id, 'closed' => 0,'is_yuyue'=>1))->count();//外卖
            $market=D('Storeorder')->where(array('user_id' =>$user_id, 'closed' => 0,'is_yuyue'=>1))->count();//便利店
            $store=D('Marketorder')->where(array('user_id' =>$user_id, 'closed' => 0,'is_yuyue'=>1))->count();//菜市场
            $goods=D('Order')->where(array('closed' => 0, 'user_id' =>$user_id,'is_yuyeu'=>1))->count();//商城
            $count14=$ele+$store+$market+$goods;
            if($count14>=1){$sum14=$count14;}else{$sum14=0;}
            //配送订单
            $count15=D('DeliveryOrder')->where(array('status'=>1,'closed'=>0))->count();
            if($count15>=1){$sum15=$count15;}else{$sum15=0;}
            //乡村
            $count16=D('Villagejoin')->where(array('user_id'=>$user_id))->count();
            if($count16>=1){$sum16=$count16;}else{$sum16=0;}

            $data=array(
                'jiazhen'=>$sum,
                'jiudian'=>$sum1,
                'jiaoyu'=>$sum2,
                'njiale'=>$sum3,
                'ktv'=>$sum4,
                'pao'=>$sum5,
                'siji'=>$sum6,
                'qian'=>$sum7,
                'linyuan'=>$sum8,
                'jifen'=>$sum9,
                'yuyue'=>$sum10,
                'vip'=>$sum11,
                'shoucan'=>$sum12,
                'yu'=>$sum13,
                'yudin'=>$sum14,
                'pei'=>$sum15,
                'xiancun'=>$sum16
            );
            $this->ajaxReturn(array('code'=>1,'status'=>1,'count'=>$data));
        }

    }

    //商家订单查询
    public function shoporder(){
        $shop_id = (int) $this->_param('shop_id');
        if(IS_AJAX) {
            //家政
            $count = D('Appointorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count>=1){$sum=$count;}else{$sum=0;}
            //酒店
            $count1 = D('Hotelorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count1>=1){$sum1=$count1;}else{$sum1=0;}
            //教育
            $count2 = D('EduOrder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count2>=1){$sum2=$count2;}else{$sum2=0;}
            //农家乐
            $shop=D('Farm')->where(['shop_id'=>$shop_id])->find();
            $count3 = D('FarmOrder')->where(array('order_status'=>1,'farm_id' =>$shop['farm_id'],'closed'=>0))->count();
            if($count3>=1){$sum3=$count3;}else{$sum3=0;}
            //KTV
            $count4 = D('KtvOrder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count4>=1){$sum4=$count4;}else{$sum4=0;}
            //在线抢购
            $count5 = D('Tuanorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count5>=1){$sum5=$count5;}else{$sum5=0;}
            //商城
            $count6 = D('Order')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count6>=1){$sum6=$count6;}else{$sum6=0;}
            //外卖
            $count7 = D('Eleorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count7>=1){$sum7=$count7;}else{$sum7=0;}
            //菜市场
            $count8 = D('Marketorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count8>=1){$sum8=$count8;}else{$sum8=0;}
            //便利店
            $count9 = D('Storeorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count9>=1){$sum9=$count9;}else{$sum9=0;}
            //0元抢购
            $count10 = D('Pindanorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count10>=1){$sum10=$count10;}else{$sum10=0;}
            //积分商城
            $count11 = D('Integralorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0))->count();
            if($count11>=1){$sum11=$count11;}else{$sum11=0;}
            //商城预约单
            $count12 = D('Order')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0,'is_yuyeu'=>1))->count();
            if($count12>=1){$sum12=$count12;}else{$sum12=0;}
            //外卖预约单
            $count13 = D('Eleorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0,'is_yuyue'=>1))->count();
            if($count13>=1){$sum13=$count13;}else{$sum13=0;}
            //便利店预约单
            $count14 = D('Storeorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0,'is_yuyue'=>1))->count();
            if($count14>=1){$sum14=$count14;}else{$sum14=0;}
            //菜市场预约单
            $count15 = D('Marketorder')->where(array('status'=>1,'shop_id' =>$shop_id,'closed'=>0,'is_yuyue'=>1))->count();
            if($count15>=1){$sum15=$count15;}else{$sum15=0;}

            $data=array(
                'jiazhen'=>$sum,
                'jiudian'=>$sum1,
                'jiaoyu'=>$sum2,
                'njiale'=>$sum3,
                'ktv'=>$sum4,
                'qian'=>$sum5,
                'shancheng'=>$sum6,
                'ele'=>$sum7,
                'market'=>$sum8,
                'store'=>$sum9,
                'linyuan'=>$sum10,
                'jifen'=>$sum11,
                'shanchengyu'=>$sum12,
                'eleyu'=>$sum13,
                'storeyu'=>$sum14,
                'marketyu'=>$sum15
            );
            $this->ajaxReturn(array('code'=>1,'status'=>1,'count'=>$data));
        }
    }

    //查询订单是否超时(配送员的)
    public function orderovertime(){
        $id = (int) $this->_param('id');
        if(IS_AJAX) {

            $order=M('DeliveryOrder')->where(['status'=>2,'closed'=>0,'delivery_id'=>$id,'qucan'=>1])->select();
            foreach ($order as $val){
                $endtime=$val['pei_time'];
                $end=time();
                $jeishu=($endtime-$end)/60;
                if(5 >=$jeishu && $jeishu>0){
                    $explain = '您有即将超时订单';
                    $this->ajaxReturn(array('code'=>1,'msg'=>$explain));
                }
            }
        }
    }

    //商家的订单是否过期
    public function shopovertime(){
        $shop_id = (int) $this->_param('shop_id');
        if(IS_AJAX) {
            $order=M('ele_order')->where(['status'=>10,'closed'=>0,'shop_id'=>$shop_id])->select();
            foreach ($order as $val){
                $endtime=$val['pei_time'];
                $end=time();
                $jeishu=($endtime-$end)/60;
                if(5 >= $jeishu && $jeishu>0){
                    $explain = '您有即将超时订单';
                    $this->ajaxReturn(array('code'=>1,'msg'=>$explain));
                }
            }
        }
    }


	//获取后台验证码
	public function adminsendsms(){
		$username = I('username','','trim,htmlspecialchars');
		$Admin = D('Admin')->where(array('username'=>$username))->find();
		if(!$Admin['mobile']){
			$this->ajaxReturn(array('code'=>0,'msg'=>'该管理员没有绑定手机号'));
		}
		session('mobile',$Admin['mobile']);
		$randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);
		D('Sms')->sms_yzm($Admin['mobile'],$randstring);//发送短信
		$this->ajaxReturn(array('code'=>1,'msg'=>'获取短信成功','scode'=>$randstring));
    }
	
	
	//领取红包
    public function envelope($envelope_id = 0,$order_id = 0,$orderType = 0,$user_id = 0){
        $oenvelope_id = I('envelope_id', '', 'intval,trim');
		$order_id = I('order_id', '', 'intval,trim');
		$orderType = I('orderType', '', 'intval,trim');
		$user_id = I('user_id', '', 'intval,trim');
		
		
		if(!$envelope_id){
			$this->ajaxReturn(array('code'=>'0','msg'=>'红包ID不存在'));
		}
		if(!$Envelope = M('Envelope')->find($envelope_id)){
			$this->ajaxReturn(array('code'=>'0','msg'=>'红包详情'));
		}
		if($EnvelopeLogs = M('EnvelopeLogs')->where(array('user_id'=>$user_id,'order_id'=>$order_id,'orderType'=>$orderType))->find()){
			$this->ajaxReturn(array('code'=>'0','msg'=>'您已经领取过了，不能重复领取哦'));
		}
		if(!$order_id){
			$this->ajaxReturn(array('code'=>'0','msg'=>'订单ID不存在'));
		}
		if(!$orderType){
			$this->ajaxReturn(array('code'=>'0','msg'=>'订单类型不能为空'));
		}
	
		
		$getOrderType = D('Envelope')->getOrderType();
		if($orderType == 1){
			$need_pay = M('Order')->where(array('order_id'=>$order_id))->getField('need_pay');
		}elseif($orderType == 2){
			$need_pay = M('EleOrder')->where(array('order_id'=>$order_id))->getField('need_pay');
		}elseif($orderType == 4){
			$need_pay = M('BookOrder')->where(array('order_id'=>$order_id))->getField('amount');
		}elseif($orderType == 5){
			$need_pay = M('BreaksOrder')->where(array('order_id'=>$order_id))->getField('need_pay');
			$need_pay  = $need_pay;
		}
		
		$ratio = $Envelope['ratio'];//比例
		$money = (float)($need_pay*$ratio);//分成金额
		
		if($Envelope['prestore'] < $money){
			M('Envelope')->where(array('envelope_id'=>$envelope_id))->save(array('closed'=>1)); //关闭返还
			if($Envelope['type'] < 2){
				$shop = M('Shop')->find($Envelope['shop_id']);
				D('Users')->addMoney($shop['user_id'],$Envelope['prestore'],'用户兑换的金额【'.round($money,2).'】大于红包剩余余额，自动关闭该红包');
			}
			$this->ajaxReturn(array('code'=>'0','msg'=>'当前红包库金额不足无法领取'));
		}
		
		$rand = rand(10,80);
		if($money > 0){
			$intro = $getOrderType[$orderType].'ID【'.$order_id.'】分享后领取红包【'.round($money,2).'】元，分成比例【'.$Envelope['ratio'].'%】';
		}else{
			$money = $rand;
			$intro = $getOrderType[$orderType].'ID【'.$order_id.'】分享后领取随机红包【'.round($money,2).'】元';
		}
		
		if($money > 0){
			$arr['type'] = $Envelope['type'];
			$arr['orderType'] = $orderType;
			$arr['envelope_id'] = $envelope_id;
			$arr['shop_id'] = $Envelope['shop_id'];
			$arr['user_id'] = $user_id;
			$arr['order_id'] = $order_id;
			$arr['money'] = $money;
			$arr['surplus_prestore'] = $Envelope['prestore'] - $money;
			$arr['intro'] = $intro;
			$arr['create_time'] = time();
			$arr['create_ip'] = get_client_ip();
			if(M('EnvelopeLogs')->add($arr)){
				D('Users')->addMoney($user_id,$money,$intro);//领取红包
				D('Envelope')->where(array('envelope_id'=>$envelope_id))->setDec('prestore',$money); 
				$this->ajaxReturn(array('code'=>'1','msg'=>$intro,'url'=>U('user/logs/moneylogs')));        
			}else{
				$this->ajaxReturn(array('code'=>'0','msg'=>'领取失败'));
			}
		}else{
			$this->ajaxReturn(array('code'=>'0','msg'=>'红包配置有误'));
		}
		
    }


	
}