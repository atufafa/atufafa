<?php
class LevelAction extends CommonAction{
	public function respond($type = '0'){
		$code = $this->_get('code');
		$id = $this->_get('id');
		if(empty($code)){
			$this ->error('没有该支付方式');
			die ;
		}
		$ret = D('Payment')->respond($code,$id);//去执行验证参数
		if($code != 'paypal' && $ret == false){
            $this->error('支付验证失败');
            die;
        }
		$type = D('Payment')->getType();
		$log_id = D('Payment')->getLogId();
		$log_id = ($code == 'paypal') ? $id : $log_id;//如果是paypal支付换一个回调模式
        $detail = D('Paymentlogs')->find($log_id);
		if(!empty($detail)){
			if($detail['type'] == 'ele'){
				$this->success('恭喜您外卖订单付款成功', U('user/eleorder/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'booking'){
				$this->success('恭喜您付款成功', U('user/booking/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'farm'){
				$this->success('恭喜您付款成功', U('user/farm/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'appoint'){
				$this->success('恭喜您家政支付成功啦', U('user/appoint/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'running'){
				$this->success('恭喜您付款成功', U('user/running/index'));
			}elseif($detail['type'] == 'goods'){
				if($detail['order_id']){
					$this->success('商城订单支付成功啦', U('user/goods/detail',array('order_id'=>$detail['order_id'])));
				}else{
					$this->success('商城合并付款支付成功啦', U('user/goods/index',array('aready'=>'1')));
				}
			}elseif($detail['type'] == 'edu') {
				$this->success('恭喜您支付成功啦', U('user/edu/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'community'){
				$this->success('恭喜您小区缴费成功啦',  U('user/community/order'));
			}elseif($detail['type'] == 'stock'){
				$this->success('恭喜您支付成功啦', U('user/stock/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'money'){
				$this->success('恭喜您充值成功', U('user/member/index'));
			}elseif ($detail['type'] == 'book'){
				$this->success('恭喜您付款成功', U('user/book/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'breaks'){
				$this->success('恭喜您买单付款成功', U('user/breaks/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'life'){
				$this->success('恭喜您分类信息付款成功', U('user/life/index'));
			}else{
				$this->success('恭喜您付款成功', U('user/member/index'));
			}
		}else{
			$this->success('支付成功', U('user/member/index'));
		}
	}
	public function yes($log_id,$type = '0'){
		$log_id = (int)$log_id;
		$detail = D('Paymentlogs')->find($log_id);
		if($detail['is_paid'] == 1){
			if($detail['type'] == 'ele'){
				$this->success('恭喜您外卖订单付款成功', U('user/eleorder/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'booking'){
				$this->success('恭喜您付款成功', U('user/booking/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'farm'){
				$this->success('恭喜您付款成功', U('user/farm/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'appoint'){
				$this->success('恭喜您家政支付成功啦', U('user/appoint/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'running'){
				$this->success('恭喜您付款成功', U('user/running/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'goods'){
				if($detail['order_id']){
					$this->success('商城订单支付成功啦', U('user/goods/detail',array('order_id'=>$detail['order_id'])));
				}else{
					$this->success('商城合并付款支付成功啦', U('user/goods/index',array('aready'=>'2')));
				}
			}elseif($detail['type'] == 'edu') {
				$this->success('恭喜您支付成功啦', U('user/edu/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'community'){
				$this->success('恭喜您小区缴费成功啦',  U('user/community/order'));
			}elseif($detail['type'] == 'stock'){
				$this->success('恭喜您支付成功啦', U('user/stock/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'money'){
				$this->success('恭喜您充值成功', U('user/member/index'));
			}elseif ($detail['type'] == 'book'){
				$this->success('恭喜您付款成功', U('user/book/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'breaks'){
				$this->success('恭喜您买单付款成功', U('user/breaks/detail',array('order_id'=>$detail['order_id'])));
			}elseif($detail['type'] == 'life'){
				$this->success('恭喜您分类信息付款成功', U('user/life/index'));
			}else{
				$this->success('恭喜您付款成功', U('user/member/index'));
			}
		}else{
			$this->success('请去会员中心查看状态', U('user/member/index'));
		}
	}
	public function payment($log_id){
		if(empty($this -> uid)) {
			header("Location:" . U('passport/login'));
			die ;
		}
		$log_id = (int)$log_id;
		$logs = D('Paymentlogs') -> find($log_id);
		if(empty($logs) || $logs['user_id'] != $this ->uid || $logs['is_paid'] == 1) {
			$this->error('没有有效的支付记录');
			die ;
		}
		$this->assign('button', D('Payment')->getCode($logs));
		$this->assign('types', D('Payment')->getTypes());
		$this->assign('logs', $logs);
		$this->assign('paytype', D('Payment')->getPayments());
		$this->assign('paytypes',$paytype =  D('PaymentLogs')->getcode($logs['code']));
		$this->display();
	}

    public function pay($code= ''){
        $logs_id = (int) $this->_param('logs_id');
        $code = $this->_param('code', 'htmlspecialchars');
        $name = ($code =='money') ? '余额' : '积分';
        if(empty($this->uid)){
            $this->error('UID不存在', U('members/set/buy'));
        }
        if(empty($logs_id)){
            $this->error('支付ID不存在', U('members/set/buy'));
        }
        if(empty($code)){
            $this->error('支付方式不存在', U('members/set/buy'));
        }
        if(!($detail = D('Paymentlogs')->find($logs_id))){
            $this->error('支付记录不存在', U('members/set/buy'));
        }
        if($detail['code'] != $code){
            $this->error('支付方式不正确', U('members/set/buy'));
        }
        if($detail['need_pay'] <= 0){
            $this->error('支付金额有误', U('members/set/buy'));
        }
        $member = D('Users')->find($this->uid);
        if($detail['is_paid']){
            $this->error('支付日志状态错误', U('members/set/buy'));
        }
        if($code == 'money' && $member['money'] < $detail['need_pay']){
            $this->error('余额不足无法支付', U('members/set/buy'));
        }
        if($code == 'integral' && $member['integral'] < $detail['need_pay']){
            $this->error('您积分账户不足无法支付', U('members/set/buy'));
        }
        switch ($detail['type']) {
            case 'ele':
                $detail['pay_name'] = "消费";
                break;
            case 'booking':
                $detail['pay_name'] = "用于预约订座";
                break;
            case 'farm':
                $detail['pay_name'] = "用于购买农家乐";
                break;
            case 'appoint':
                $detail['pay_name'] = "用于支付家政费用";
                break;
            case 'tuan':
                $detail['pay_name'] = "用于支付抢购";
                break;
            case 'edu':
                $detail['pay_name'] = "用于购买课程";
                break;
            default:
                $detail['pay_name'] = "用于购买商品";
                break;
        }
        // $intro = '【'.$name.'】支付'.round($detail['need_pay']/100,2).'元，'.$detail['pay_name'].'支付ID('.$logs_id.')，原始订单ID：【'.$detail['order_id'].'】';
        $intro = '【'.$name.'】支付'.round($detail['need_pay'],2).'元，'.$detail['pay_name'];
        if($code == 'money'){
            $member['money'] -= $detail['need_pay'];
            if(D('Users')->save(array('user_id' => $this->uid,'money' => $member['money']))){
                D('Usermoneylogs')->add(array(
                    'user_id' => $this->uid,
                    'money' => - $detail['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'intro' => $intro
                ));
            }
        }
        if($code == 'integral'){
            $member['integral'] -= $detail['need_pay'];
            if(D('Users')->save(array('user_id' =>$this->uid, 'integral' => $member['integral']))){
                D('Userintegrallogs')->add(array(
                    'user_id' => $this->uid,
                    'integral' => - $detail['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'intro' => $intro
                ));
            }
        }
        D('Paymentlogs')->where(array('log_id'=>$logs_id))->save(array('code'=>$code));//更新支付方式
        D('Payment')->logsPaid($logs_id);//回调函数
        if($detail['type'] == 'ele'){
            $this->success('恭喜您外卖订单付款成功', U('eleorder/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'booking') {
            $this->success('恭喜您付款成功', U('booking/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'farm') {
            $this->success('恭喜您付款成功', U('farm/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'appoint') {
            $this->success('恭喜您家政支付成功啦', U('appoint/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'running') {
            $this->success('恭喜您付款成功', U('user/running/index'));
        }elseif($detail['type'] == 'goods'){
            if($detail['order_id']){
                $this->success('商城订单支付成功啦', U('goods/detail',array('order_id'=>$detail['order_id'])));
            }else{
                $this->success('商城合并付款支付成功啦', U('goods/index',array('aready'=>'1')));
            }
        }elseif($detail['type'] == 'edu') {
            $this->success('恭喜您支付成功啦', U('edu/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'community') {
            $this->success('恭喜您小区缴费成功啦', U('community/order'));
        }elseif($detail['type'] == 'stock') {
            $this->success('恭喜您支付成功啦', U('stock/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'money') {
            $this->success('恭喜您充值成功', U('member/index'));
        }elseif($detail['type'] == 'book'){
            $this->success('恭喜您付款成功', U('book/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'breaks'){
            $this->success('恭喜您买单付款成功', U('breaks/detail',array('order_id'=>$detail['order_id'])));
        }elseif($detail['type'] == 'life'){
            $this->success('恭喜您分类信息付款成功', U('life/index'));
        }else{
            $this->success('恭喜您付款成功', U('members/index/index'));
        }
    }
}
