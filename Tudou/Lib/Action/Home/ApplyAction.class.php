<?php
class ApplyAction extends CommonAction{
    private $create_fields = array('user_id','city_id', 'area_id', 'business_id', 'logo', 'cate_id', 'user_guide_id','tel', 'logo', 'photo', 'shop_name', 'contact', 'details', 'business_time', 'area_id', 'addr', 'lng', 'lat', 'recognition','is_pei');
	private $delivery_create_fields = array('city_id', 'user_id','photo', 'name', 'mobile', 'addr');

	public function delivery(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
		$obj = D('Delivery');
		$user_delivery = $obj->where(array('user_id' => $this->uid))->find();
		if($user_delivery['closed'] !=0){
			$this->error('非法错误');
		}
        if($this->isPost()){
//            $data = $this->delivery_createCheck();
//            if ($obj->add($data)){
//                $this->tuMsg('恭喜您申请成功', U('home/apply/delivery'));
//            }else{
//				$this->tuMsg('申请失败');
//			}
        }else{
            $ispaid = D('PaymentLogs')->where(array('psy'=>1,'user_id'=>$this->uid))->field('pay_time')->find();
            $this->assign('ispaid', $ispaid['pay_time']);
            $level = D('DeliveryLevel')->select();
            $this->assign('level', $level);
            $this->assign('payment', D('Payment')->getPayments(true));
			$this->assign('user_delivery', $user_delivery);
            $this->display();
        }
    }

    //重新提交
    public function deldelivery(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        $log_id = $this->_get('log_id');
        $obj = D('Delivery')->where("user_id=$this->uid and log_id=$log_id")->delete();
        if($obj !== false){
            D('PaymentLogs')->where("log_id=$log_id")->delete();
            header("Location:" . U('apply/delivery'));
        }else{
            header("Location:" . U('apply/deldelivery'));
        }
    }
    //配送员支付
    public function psy(){
	    if($this->isPost())
        {
            $obj = D('Delivery');
            $user = $obj->where(array('user_id' => $this->uid))->find();
            if($user)
            {
                $this->error('请别重复提交');
            }
            if(empty($this->uid)){
                header("Location:" . U('passport/login'));
                die;
            }
            $code = $this->_post('code', 'htmlspecialchars');
            $data = $this->checkFields($this->_post('data', false), $this->delivery_create_fields);
            $data['user_id'] = $this->uid;
            $data['photo'] = htmlspecialchars($data['photo']);
            if(empty($data['photo'])){
                $this->error('请上传身份证',U('apply/delivery'));
            }
            if(!isImage($data['photo'])){
                $this->error('身份证格式不正确',U('apply/delivery'));
            }
            $data['name'] = htmlspecialchars($data['name']);
            if(empty($data['name'])){
                $this->error('姓名不能为空',U('apply/delivery'));
            }
            $data['mobile'] = htmlspecialchars($data['mobile']);
            if(empty($data['mobile'])){
                $this->error('手机号不能为空',U('apply/delivery'));
            }
            if(!isPhone($data['mobile']) && !isMobile($data['mobile'])){
                $this->error('手机号格式不正确',U('apply/delivery'));
            }
            $data['addr'] = htmlspecialchars($data['addr']);
            if(empty($data['addr'])){
                $this->error('地址不能为空',U('apply/delivery'));
            }
            $num = $this->_post('num');
            if($num==0){
                $this->error('请选择配送员级别',U('apply/delivery'));
            }
            $moneys = D('DeliveryLevel')->where(array('id' => $num))->find();
            $money = (float) ($moneys['num']);
            $data['level'] = $num;
            $code = $this->_post('code', 'htmlspecialchars');
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->error('该支付方式不存在',U('apply/delivery'));
            }
            $logs = array(
                'user_id' => $this->uid,
                'type' => 'money',
                'code' => $code,
                'order_id' => 0,
                'psy' => 1,
                'need_pay' => $money,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip()
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $data['log_id'] = $logs['log_id'];
            if($logs['log_id']){
                if ($obj->add($data)){
                }else{
                    D('Paymentlogs')->delete($logs['log_id']);
                    $this->error('请重新输入',U('apply/delivery'));
                }
            }
            $this->assign('button', D('Payment')->getCode($logs));
            $this->assign('money', $money);
            $this->assign('logs', $logs);
            $this->display();
        }
    }
	
    private function delivery_createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->delivery_create_fields);
		$data['user_id'] = $this->uid;
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuMsg('请上传身份证');
        }
        if(!isImage($data['photo'])){
            $this->tuMsg('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if(empty($data['name'])){
            $this->tuMsg('姓名不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if(empty($data['mobile'])){
            $this->tuMsg('手机号不能为空');
        }
        if(!isPhone($data['mobile']) && !isMobile($data['mobile'])){
            $this->tuMsg('手机号格式不正确');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if(empty($data['addr'])){
            $this->tuMsg('地址不能为空');
        }        
        $data['auth_id'] = htmlspecialchars($data['grade_id']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

	public function artlist(){
    	$Article = D('Article')->where('article_id=999999')->find();
 //     print_r($Article);
      $this->assign('art',$Article);
      $this->display();
    }



}