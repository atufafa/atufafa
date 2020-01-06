<?php 
class RunningAction extends CommonAction{
	
	private $create_fields = array('city_id', 'user_id','title', 'thumb','name', 'addr', 'mobile', 'price', 'freight','need_pay', 'lng', 'lat', 'addre','dashan','addrs','lats','lngs');
	
	   private $create_fieldss = array('city_id','is_ues','times','vehicle_type', 'user_id','title', 'artificial','artificialnumber','thumb','name', 'addre', 'mobile', 'price', 'freight','need_pay', 'lng', 'lat', 'addr','dashan','addrs','lats','lngs');
	
    protected function _initialize(){
        parent::_initialize();
        if((int) $this->_CONFIG['operation']['running'] == 0){
            $this->error('此功能已关闭');
            die;
        }
		$this->assign('types', D('Running')->getType());
    }
	
	
	
    public function index(){
        $status = (int) $this->_param('status');
        $this->assign('status', $status);
        $this->display();
    }
	
	
    public function load(){
        $running = D('Running');
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
        if(empty($running_id) || !($detail = D('Running')->find($running_id))){
            $this->error('该跑腿不存在');
        }
        if($detail['user_id'] != $this->uid) {
            $this->error('请不要操作他人的跑腿');
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Delivery')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign('detail', $detail);
        $this->display();
    }
	
	public function detail($running_id){
        $running_id = (int) $running_id;
        if(empty($running_id) || !($detail = D('Running')->find($running_id))){
            $this->error('该跑腿不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的跑腿');
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Delivery')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign('detail', $detail);
        $this->display();
    }
	
    public function create(){
        if($this->isPost()){
            $data = $this->createCheck();
			if(!D('Running')->Check_Running_Interval_Time($this->uid)){
				$this->tuMsg(D('Running')->getError(), U('running/index'));	 
			}
            if($running_id = D('Running')->add($data)){
				$running = D('Running')->where(array('running_id'=>$running_id))->find();
				$this->tuMsg('恭喜您发布跑腿成功，正在为您跳转付款', U('running/pay', array('running_id' => $running_id)));
            }
            $this->tuMsg('发布失败');
        }else{
            $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid, 'is_default' => 1))->limit(0, 1)->select());
            $this->display();
        }
    }
	
	
    public function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		$data['city_id'] = $this->city_id;
        $data['user_id'] = $this->uid;
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuMsg('需求不能为空');
        }
		//传图组合开始
		$thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
        $data['thumb'] = serialize($thumb);
		//传图组合结束	
		if(!empty($MEMBER['nickname'])){
			$name = $MEMBER['nickname'];
		}else{
			$name = $MEMBER['account'];
		}
        $data['name'] = $name;
		$data['mobile'] = $this->member['mobile'];
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->tuMsg('收货地址不能为空，请点击选择详细地址');
        }

        $data['addre']=htmlspecialchars($data['addre']);
        if(empty($data['addre'])){
            $this->tuMsg('具体门牌号不能为空');
        }

         $data['addrs'] = htmlspecialchars($data['addrs']);
        if (empty($data['addrs'])) {
            $this->tuMsg('购买不能为空，请点击选择详细地址');
        }

        $data['price'] = (float) $data['price'];
        if (empty($data['price'])) {
            $this->tuMsg('物品价格不能为空');
        }

        $data['dashan'] = (float) $data['dashan'];

        $data['freight'] = (float) $data['freight'];
        if (empty($data['freight'])) {
            $this->tuMsg('运费不能为空');
        }

        $data['need_pay'] = $data['price']+$data['freight']+$data['dashan'];//应付金额
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
   
    public function delete($running_id){
        if (is_numeric($running_id) && ($running_id = (int) $running_id)) {
            $obj = D('Running');
            if (!($detail = $obj->find($running_id))) {
                $this->error('跑腿不存在');
            }
            if ($detail['closed'] == 1 || $detail['status'] != 0 && $detail['status'] != 2) {
                $this->error('该跑腿状态不允许被删除');
            }
            $obj->save(array('running_id' => $running_id, 'closed' => 1));
            $this->success('删除成功', u('running/index'));
        }
    }
	
	//跑腿直接支付页面
	 public function pay(){
        if (empty($this->uid)) {
            header('Location:' . U('passport/login'));
            die;
        }
        $running_id = (int) $this->_get('running_id');
        $running = D('Running')->where(array('running_id'=>$running_id))->find();
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
        $running = D('Running')->where(array('running_id'=>$running_id))->find();
        if (empty($running) || $running['status'] != 0 || $running['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        if (!($code = $this->_post('code'))) {
            $this->error('请选择支付方式');
        }
        if ($code == 'wait') {
             $this->error('暂不支持货到付款，请重新选择支付方式');
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->error('该支付方式不存在，请稍后再试试');
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
                $logs['log_id'] = D('Paymentlogs')->add($logs);
				$this->tuMsg('选择支付方式成功！下面请进行支付', U('wap/payment/payment',array('log_id' => $logs['log_id'])));
			}else{
				$this->error('非法操作用');
			}
        }
    }

 //提高赏金
    public function dsmoney($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Running")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
        $runn=D('Running')->where(array('running_id'=>$running_id))->find();
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('rr',$runn);
        $this->display();
    }


    //提高打赏金额
    public function tigao($running_id){
        $running_id = (int) $running_id;
        $runn=D('Running');
        if($this->Ispost()){
            $money=I('post.dashan');
            $ti=I('post.ti');
            if(empty($ti)){
                $this->ajaxReturn(array('code'=>'0','msg'=>'请输入打赏金额'));
            }
            if($ti<0){
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


            //var_dump($data['need_pay']);die;
            //$runn->where(array('running_id'=>$running_id))->setField('dashan',$data);
            $runn->save(array('dashan'=>$data['dashan'],'need_pay'=>$data['need_pay'],'running_id' => $running_id)); 
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

    //确认收货
    public function yes($running_id){
         $running_id = (int) $running_id;  
         $running = D('Running')->save(array('statu'=>1,'status'=>8,'running_id'=>$running_id));
         $this->success('恭喜您完成订单!', u('running/index'));
    }



    public function vehicle(){

        if($this->isPost()){
            $data = $this->createChecks();
            //var_dump($data);die;
            if(!D('Runningvehicle')->Check_Running_Interval_Time($this->uid)){
                $this->tuMsg(D('Runningvehicle')->getError(), U('running/index'));
            }
            //var_dump($data);die;
            if($running_id = D('Runningvehicle')->add($data)){
                $running = D('Runningvehicle')->where(array('running_id'=>$running_id))->find();
                $this->tuMsg('恭喜您发布跑腿成功，正在为您跳转付款', U('running/pays', array('running_id' => $running_id)));
            }
            $this->tuMsg('发布失败');
        }else{
            $list=M('errands_config')->select();
            $this->assign('list',$list);
            $yun=M('errands_vehicle')->select();
            $this->assign('yun',$yun);
            //var_dump($list);die;
            $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid, 'is_default' => 1))->limit(0, 1)->select());
            $this->display();
        }
    }

     public function createChecks(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fieldss);
        $data['city_id'] = $this->city_id;
        $data['user_id'] = $this->uid;
        $data['vehicle_type'] = (int) $data['vehicle_type'];
        $data['is_ues'] = (int) $data['is_ues'];
        $t= htmlspecialchars($data['times']);
         if(empty($data['vehicle_type'])){
             $this->tuMsg('请选择车辆类型');
         }
        if(empty($data['is_ues'])){
            $this->tuMsg('请选择用车情况');
        }
        if($data['is_ues']==1 && empty($t)){
            $this->tuMsg('请填写预约用车时间');
        }

         $str = str_replace('T', ' ', $t ).':00';
         $data['times']=strtotime($str);

        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuMsg('需求不能为空');
        }
        //传图组合开始
        $thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
        $data['thumb'] = serialize($thumb);
        //传图组合结束    
        if(!empty($MEMBER['nickname'])){
            $name = $MEMBER['nickname'];
        }else{
            $name = $MEMBER['account'];
        }
        $data['name'] = $name;
        $data['mobile'] = $this->member['mobile'];


        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->tuMsg('搬货地址不能为空，请点击选择详细地址');
        }

        $data['addre']=htmlspecialchars($data['addre']);
        if(empty($data['addre'])){
            $this->tuMsg('具体门牌号不能为空');
        }

         $data['addrs'] = htmlspecialchars($data['addrs']);
        if (empty($data['addrs'])) {
            $this->tuMsg('送达地址不能为空，请点击选择详细地址');
        }
        $data['artificialnumber']=htmlspecialchars($data['artificialnumber']);

        $data['artificial']=htmlspecialchars($data['artificial']);
       
        $data['price'] =(float) $data['price'];
    
        $data['dashan'] = (float) $data['dashan'];
        //var_dump($data);die;
        $data['freight'] = (float) $data['freight'];
        // if (empty($data['freight'])) {
        //     $this->tuMsg('运费不能为空');
        // }
        $data['need_pay'] = $data['price']+$data['freight']+$data['dashan'];//应付金额
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();

        //var_dump($data);die;
        return $data;
    }
//跑腿直接支付页面
     public function pays(){
        if (empty($this->uid)) {
            header('Location:' . U('passport/login'));
            die;
        }
        $running_id = (int) $this->_get('running_id');
        $running = D('Runningvehicle')->where(array('running_id'=>$running_id))->find();
        if (empty($running) || $running['status'] != 0 || $running['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
            die;
        }
        $this->assign('running', $running);
        $this->assign('payment', D('Payment')->getPayments_running(true));//新版跑腿支付
        $this->display();
    }
     //去付款
     public function pay2s(){
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $running_id = (int) $this->_get('running_id');
        $running = D('Runningvehicle')->find($running_id);
        ///var_dump($running);die;
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
                $logs['log_id'] = D('Paymentlogs')->add($logs);
                if(!empty($running)){
                    $running=D('Runningvehicle')->where(array('running_id'=>$running_id))->save(array('status'=>1));
                }
                $this->tuMsg('选择支付方式成功！下面请进行支付', U('wap/payment/payment',array('log_id' => $logs['log_id'])));
            }else{
                $this->tuMsg('非法操作用');
            }
        }
    }

    //根据车型算钱
    public function fare(){
        if(IS_AJAX){
            $type = (int) $_POST['type'];
            $config = D('Setting')->fetchAll();
            if($type==1){
                $qibujia=array(
                    'gls'=>$config['freight']['vehicle_distance'],
                    'qibu'=>$config['freight']['vehicle_price'],
                    'jiaja'=>$config['freight']['vehicle_jiaja']
                );
            }elseif($type==2){
                $qibujia=array(
                    'gls'=>$config['freight']['xvehicle_distance'],
                    'qibu'=>$config['freight']['xvehicle_price'],
                    'jiaja'=>$config['freight']['xvehicle_jiaja']
                );
            }elseif($type==3){
                $qibujia=array(
                    'gls'=>$config['freight']['zvehicle_distance'],
                    'qibu'=>$config['freight']['zvehicle_price'],
                    'jiaja'=>$config['freight']['zvehicle_jiaja']
                );
            }
            echoJson(['code'=>1,'data'=>$qibujia]);

        }
    }





}