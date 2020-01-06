<?php
class RunningvehicleModel extends CommonModel{
    protected $pk   = 'running_id';
    protected $tableName =  'running_vehicle';

    protected $types = array(
		0 => '未付款', 
		1 => '已付款', 
		2 => '跑腿中', 
		3 => '跑完腿', 
		4 => '退款中', 
		5 => '已退款', 
		8 => '已完成'
	);
     public function getTypes(){
        return $this->types;
    }

    //检测发布跑腿时间
	public function Check_Running_Interval_Time($uid) {
		$uid = (int) $uid;
		$running = D('Runningvehicle')->where(array('user_id'=>$uid))->order('create_time desc')->find();
		if(!empty($running)){
			$config = D('Setting')->fetchAll();
			$current_time = NOW_TIME;
			$interval_time_difference = $current_time - $running['create_time'];
			$cha = $config['running']['interval_time'] - $interval_time_difference;
			if($interval_time_difference < $config['running']['interval_time']){
				$this->error = '发布太频繁了请休息'.$cha.'秒后再来提交哦';
            	return false;
			}
		}else{
			return true;
		}
        return true;
    } 

    //跑腿抢单
	public function Running_Confirm_Qiangs($running_id,$cid){
		$running = D('Runningvehicle')->find($running_id);
		if(!empty($running)){
			if($running['status'] == 1){//抢单中
			    $data = array('running_id' => $running_id,'cid' => $cid,'status' => 2,'update_time' => NOW_TIME);
                if (D('Runningvehicle')->save($data)){
					//D('Sms')->sms_Running_Delivery_Users($running_id);//给买家通知配送状态
                    return true;
                }
				return true;
		    }
		}else{
			return true;
		}
        return true;
    }


	//跑腿订单完成
	public function Running_Confirm_Completes($running_id,$cid){
		//var_dump($running_id);die;
		$running = D('Runningvehicle')->find($running_id);
		$delivery = D('Userspinche')->where(array('user_id'=>$running['cid']))->find();
		//var_dump($delivery['user_id']);die;
        $config = D('Setting')->fetchAll();
        $sum_need=$running['need_pay']-$config['freight']['vehiclechajia'];
        $sum_pei=$running['freight']-$config['freight']['vehiclechajia'];

		if(!empty($running)){
			if (false !== D('Runningvehicle')->save( array('running_id' => $running_id,'status' => 3,'end_time' => NOW_TIME))){
                        //新增配送管理员分成
                        $deliver=D('UsersPinche')->where(['user_id'=>$cid])->find();
                        $deliveradmin=D('Applicationmanagement')->where(['user_id'=>$deliver['recommend']])->find();
                        //查询配送员上级是否是管理员
                        if(!empty($delivery['recommend']) && !empty($deliveradmin['user_id'])){
                            //取出第一级分成
                            $dengji=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin['dj_id']])->find();
                            //计算分成
                            $fen1=round($sum_pei*($dengji['fencheng']/100),2);
                            D('Users')->addMoney($deliveradmin['user_id'],$fen1,'司机配送获得分成奖励'.$fen1);
                            $data1=array('d_id'=>$deliver['user_id'],'sq_id'=>$deliveradmin['sq_id'],'order_id'=>$running_id,'order_type'=>4,'money'=>$fen1,
                                'time'=>date('Y-m-d H:i:s'),'explain'=>'司机配送获得分成');
                            D('DeliveryDivide')->add($data1);
                            //判断是否存在上级
                            $deliveradmin2=D('Applicationmanagement')->where(['user_id'=>$deliveradmin['recommend']])->find();
                            if(!empty($deliveradmin2['user_id']) && $deliveradmin['dj_id']> $deliveradmin2['dj_id']){
                                //取出二级分成
                                $dengji2=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin2['dj_id']])->find();
                                $fen2=round($sum_pei*($dengji2['fencheng']/100),2);
                                D('Users')->addMoney($deliveradmin2['user_id'],$fen2,'司机配送获得分成奖励'.$fen2);
                                $data2=array('d_id'=>$deliver['user_id'],'sq_id'=>$deliveradmin2['sq_id'],'order_id'=>$running_id,'order_type'=>4,'money'=>$fen2,
                                    'time'=>date('Y-m-d H:i:s'),'explain'=>'司机配送获得分成');
                                D('DeliveryDivide')->add($data2);
                                //判断第三级
                                $deliveradmin3=D('Applicationmanagement')->where(['user_id'=>$deliveradmin2['recommend']])->find();
                                if(!empty($deliveradmin3['user_id']) && $deliveradmin2['dj_id']> $deliveradmin3['dj_id']){
                                    //取出二级分成
                                    $dengji3=D('Deliveryadmin')->where(['dj_id'=>$deliveradmin3['dj_id']])->find();
                                    $fen3=round($sum_pei*($dengji3['fencheng']/100),2);
                                    D('Users')->addMoney($deliveradmin3['user_id'],$fen3,'司机配送获得分成奖励'.$fen3);
                                    $data3=array('d_id'=>$deliver['user_id'],'sq_id'=>$deliveradmin3['sq_id'],'order_id'=>$running_id,'order_type'=>4,'money'=>$fen3,
                                        'time'=>date('Y-m-d H:i:s'),'explain'=>'司机配送获得分成');
                                    D('DeliveryDivide')->add($data3);
                                }
                            }
                            $logistics=$sum_pei-$fen1-$fen2-$fen3;
                            $deliveradminpay=$fen1+$fen2+$fen3;
                        }else{
                            $logistics=$sum_pei;
                            $deliveradminpay=0;
                        }
                    $info = '跑腿订单ID【'.$running_id.'】结算，实际支付：【'.round( $sum_need,2).'元】,其中配送管理员总获得分成【'.$deliveradminpay.'元】';
                    $infos = '跑腿订单ID【'.$running_id.'】结算，实际支付：【'.round( $sum_need,2).'元】，其中人工费为：【'.round($running['price'],2).'】元';
                    if($running['need_pay'] > 0) {
                        D('Runningmoney')->add(array(
                            'running_id' => $running_id,
                            'delivery_id' => $delivery['user_id'],
                            'user_id' => $delivery['user_id'],
                            'money' => $sum_need,
                            'type' =>  vehiclerunning,
                            'create_time' => NOW_TIME,
                            'create_ip' => get_client_ip(),
                            'intro' => $info
                        ));

					D('Users')->addMoney($delivery['user_id'], $running['dashan']+ $logistics,$info);
					D('Users')->addMoneyss($delivery['user_id'], $running['price'],$infos);
					   //写入配送员余额
						
                    }
                   
					//D('Sms')->sms_Running_Delivery_Users($running_id);//给买家通知配送状态
                    return true;
		 		}
		}else{
			return true;
		}
        return true;
    }


	//有余额直接在线付款
	public function Pay_Runnings($running_id,$uid){
        $running_id = (int) $running_id;
		$uid = (int) $uid;
        $running = D('Runningvehicle')->find($running_id);
		$users = D('Users')->find($uid);
        if (empty($running)){
			$this->error = '该订单不存在';
            return false;
        }elseif($running['status'] != 0 ){
			$this->error = '订单状态不正确';
            return false;
		}elseif($running['user_id'] != $uid){
			$this->error = '请不要非法操作1';
            return false;
		}elseif($running['need_pay'] <=0){
			$this->error = '交易金额不正确';
            return false;
		}elseif($running['need_pay'] >= $users['money']){
			$this->error = '您的余额不足,请不要非法操作哦';
            return false;
		}
		if (false !== D('Users')->addMoney($uid, -$running['need_pay'], '发布跑腿' . $running['title'] . '扣费，订单号：'.$running_id)) {
			 if (D('Runningvehicle')->save(array('running_id' => $running_id, 'status' => '1'))) {
				//D('Sms')->sms_running_users($running_id);//短信通知用户
				//D('Sms')->sms_delivery_users($running_id,2);//批量通知配送员抢单
				return TRUE; 
			 }else{
				$this->error = '更新付费状态失败，请联系管理员';
				return false;	 
			}
        } else {
			$this->error = '抱歉扣费失败，请稍后再试';
            return false;
        }
		return true;
    }
	
	
}