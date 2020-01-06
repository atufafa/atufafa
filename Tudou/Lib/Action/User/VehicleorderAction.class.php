<?php

class VehicleorderAction extends CommonAction {

	//司机跑腿订单
	public function index(){

		$status = (int) $this->_param('status');
        $this->assign('status', $status);
		$this->display();
	}

	 public function load(){
        $running = D('Runningvehicle');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);
		
		$status = I('status', '', 'trim,intval');
		if($status == 999){
			$map['status'] = array('in',array(0,1,2,3,8));
        }elseif($status == 0){
			$map['status'] = 0;
		}else{
			$map['status'] = $status;
		}
		$this->assign('status', $status);
		
		
        $count = $running->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $running->where($map)->order('running_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

      public function state($running_id){
        $running_id = (int) $running_id;
        if(empty($running_id) || !($detail = D('Runningvehicle')->find($running_id))){
            $this->error('该跑腿不存在');
        }
        if($detail['user_id'] != $this->uid) {
            $this->error('请不要操作他人的跑腿');
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', M('users_pinche')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign('detail', $detail);
        $this->display();
    }


    public function detail($running_id){
        $running_id = (int) $running_id;
        if(empty($running_id) || !($detail = D('Runningvehicle')->find($running_id))){
            $this->error('该跑腿不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的跑腿');
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', M('users_pinche')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign('detail', $detail);
        $this->display();
    }


    //跑腿直接支付页面
	 public function pay(){
        if (empty($this->uid)) {
            header('Location:' . U('passport/login'));
            die;
        }
        $running_id = (int) $this->_get('running_id');
        $running = D('Runningvehicle')->find($running_id);
        if (empty($running) || $running['status'] != 0 || $running['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
		$this->assign('running', $running);
        $this->assign('payment', D('Payment')->getPayments_running(true));//新版跑腿支付
        $this->display();
    }
	 //去付款
	 public function pay2(){
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $running_id = (int) $this->_get('running_id');
        $running = D('Runningvehicle')->find($running_id);
        if (empty($running) || $running['status'] != 0 || $running['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
            die;
        }
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
        if ($code == 'wait') {
             $this->tuMsg('暂不支持货到付款，请重新选择支付方式');
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->tuMsg('该支付方式不存在，请稍后再试试');
            }
			$need_pay = $running['need_pay'];//再更新防止篡改支付日志
			if(!empty($need_pay)){
				$logs = array(
					'type' => 'running', 
					'user_id' => $this->uid, 
					'order_id' => $running_id, 
					'code' => $code, 
					'need_pay' => $need_pay, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'is_paid' => 0
				);
                 if(!empty($running)){
                    $running=D('Runningvehicle')->where(array('running_id'=>$running_id))->save(array('status'=>1));
                }
                $logs['log_id'] = D('Paymentlogs')->add($logs);
				$this->tuMsg('选择支付方式成功！下面请进行支付', U('wap/payment/payment',array('log_id' => $logs['log_id'])));
			}else{
				$this->tuMsg('非法操作用');
			}
        }
    }



 //提高赏金
    public function dsmoney($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Runningvehicle")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
        $runn=D('Runningvehicle')->where(array('running_id'=>$running_id))->find();
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('rr',$runn);
        $this->display();
    }


    //提高打赏金额
    public function tigao($running_id){
        $running_id = (int) $running_id;
        $runn=D('Runningvehicle');
        if($this->Ispost()){
            $money=I('post.dashan');
            $ti=I('post.ti');
            if(empty($ti)){
                $this->ajaxReturn(array('code'=>'0','msg'=>'请输入打赏金额'));
            }

            if($ti<=0){
                $this->ajaxReturn(array('code'=>'0','msg'=>'打赏金额错误'));
            }

            $price=I('post.price');
            $freight=I('post.freight');
            $code = I('code','','trim,htmlspecialchars');
            if(!$code){
                $this->ajaxReturn(array('code'=>'0','msg'=>'必须选择支付方式'));
            }

            $data['dashan']=$money+$ti;
            $data['need_pay']=$money+$price+$freight+$ti;
            
            $runn->where(['running_id' => $running_id])->save(array('dashan'=>$data['dashan'],'need_pay'=>$data['need_pay']));
            $arr = array(
                'type' => 'pao',
                'user_id' => $this->uid,
                'order_id' => $order_id,
                'code' => $code,
                'need_pay' => $ti,
                'create_time' => time(),
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            if($log_id = D('Paymentlogs')->add($arr)){
                $this->ajaxReturn(array('code'=>'1','msg'=>'正在为您跳转支付','url'=>U('wap/payment/payment', array('log_id' =>$log_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'操作失败'));
            }


            $this->success('恭喜您提高打赏金额成功！', u('running/index'));
        }
    }

	 public function delete($running_id){
	        if (is_numeric($running_id) && ($running_id = (int) $running_id)) {
	            $obj = D('Runningvehicle');
	            if (!($detail = $obj->find($running_id))) {
	                $this->error('跑腿不存在');
	            }
	            if ($detail['closed'] == 1 || $detail['status'] != 0 && $detail['status'] != 2) {
	                $this->error('该跑腿状态不允许被删除');
	            }
	            $obj->save(array('running_id' => $running_id, 'closed' => 1));
	            $this->success('删除成功', u('vehicleorder/index'));
	        }
	    }

  //确认收货
    public function yes($running_id){
         $running_id = (int) $running_id;  
         $running = D('Runningvehicle')->save(array('statu'=>1,'status'=>8,'running_id'=>$running_id));
         $this->success('恭喜您完成订单!', u('vehicleorder/index'));
    }




    public function dianping($running_id){

         if($this->_Post()){
            $data = $this->checkFields($this->_post('data', false), array('score','d1','d2','d3','content','photos'));
            if(!$res = D('Runningvehicle')->where(array('running_id'=>$running_id))->find()){
                $this->ajaxReturn(array('code'=>'0','msg'=>'配送订单不存在'));
            }

            $data['user_id'] = $this->uid;
            //$data['shop_id'] = $res['shop_id'] ? $res['shop_id'] : '1';
            //$data['type_order_id'] = $res['type_order_id'];
            $data['che_id'] = $res['cid'];
            $data['running_id'] = $running_id;
            $data['score'] = (int) $data['score'];
            if(empty($data['score'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评分不能为空'));
            }
            if($data['score'] > 5 || $data['score'] < 1){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评分为1-5之间的数字'));
            }
            $config = $config = D('Setting')->fetchAll();
            $data['d1'] = (int) $data['d1'];
            if(empty($data['d1'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d1'].'评分不能为空'));
            }
            if($data['d1'] > 5 || $data['d1'] < 1){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d1'].'格式不正确'));
            }
            $data['d2'] = (int) $data['d2'];
            if(empty($data['d2'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d2'].'评分不能为空'));
            }
            if($data['d2'] > 5 || $data['d2'] < 1){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d2'].'格式不正确'));
            }
            $data['d3'] = (int) $data['d3'];
            if(empty($data['d3'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d3'].'评分不能为空'));
            }
            if($data['d3'] > 5 || $data['d3'] < 1){
                $this->ajaxReturn(array('code'=>'0','msg'=>$config['appoint']['d3'].'格式不正确'));
            }
            $data['content'] = htmlspecialchars($data['content']);
            if(empty($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评价内容不能为空'));
            }
            if($words = D('Sensitive')->checkWords($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评价内容含有敏感词：' . $words));
           }
           $data['photos'] = htmlspecialchars($data['photos']);
           $data['create_time'] = NOW_TIME;
           $data['create_ip'] = get_client_ip();
           
           $tag = $this->_post('tag', false);
           $tag = implode(',', $tag);
           $data['tag'] = $tag;
           $data['type'] = $type;       
           // print_r($data);die;
           if($comment_id = D('Vehicledianping')->add($data)){
                
                $this->ajaxReturn(array('code'=>'1','msg'=>'恭喜您点评成功','url'=>U('vehicleorder/index')));  
            }
            $this->ajaxReturn(array('code'=>'0','msg'=>'点评失败'));
        }else{

            if(empty($this->uid)){
            $this->error('请登录后操作', U('passport/login'));
        }
        if(!$running_id = (int) $running_id){
            $this->error('订单ID不存在');
        }
        if(!$res = D('Runningvehicle')->where(array('running_id'=>$running_id))->find()){
            $this->error('配送订单不存在或者该订单不是配送员配送');
        }
        if($res['status'] != 8){
            $this->error('该配送订单未完成');
        }
        if($res['closed'] != 0){
            $this->error('该配送订单已被删除');
        }
        if($dc = D('Vehicledianping')->where(array('running_id'=>$res['running_id'],'user_id'=>$this->uid))->find()){
            $this->error('该配送订单您已经点评过了');
        }
        $this->assign('res',$res);
        $this->assign('type',$type);
        $this->assign('detail',$detail);
        $this->assign('delivery',D('Userspinche')->find($res['delivery_id']));
        $this->assign('tags', D('DeliveryCommentTag')->order(array('orderby' => 'asc'))->where(array('closed' => '0'))->select());
        $this->display();

        }



    }

    //投诉
    public function tslist($running_id){
         if(!$running_id = (int) $running_id){
            $this->error('订单ID不存在！');
        }
        if($dc = D('Vehiclets')->where(array('running_id'=>$running_id))->find()){
            $this->error('您已经投诉过了！');
        }
      //查询订单信息
         $yonghu= D('Runningvehicle')->where(array('running_id'=>$running_id))->find();

         if($this->_post()){ 
          //获取页面信息
          $content=I('post.content');
          $photo=I('post.photo');
          //获取外卖订单信息
       
          $userid=$yonghu["user_id"];
          $delivery_id=$yonghu["cid"];
          //$order_id=$order_id;
         
          //创建数组存储
          $data=array();
          $data['content']=$content;

            if(empty($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉内容不能为空'));
            }
             if($words = D('Sensitive')->checkWords($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评价内容含有敏感词：' . $words));
           }
          $data['photo']=$photo;
          $data['che_id']=$delivery_id;
          $data['running_id']=$running_id;
          $data['user_id']=$userid;

         //var_dump($data);
         $pey= D('Vehiclets')->add($data);

            if($pey>0){
               $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('vehicleorder/index')));  
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
    }
      $this->assign('yonghu', $yonghu);  
      $this->display();
    }


}