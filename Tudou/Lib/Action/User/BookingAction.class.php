<?php
class BookingAction extends CommonAction{
	
	 protected function _initialize(){
        parent::_initialize();
        if($this->_CONFIG['operation']['booking'] == 0){
            $this->error('此功能已关闭');
            die;
        }
    }

    public function index() {
        $st = (int) $this->_param('st');
		$this->assign('st', $st);
        $this->display();
    }

    public function loaddata() {
		$obj = D('Bookingorder');
		import('ORG.Util.Page');
		$map = array('user_id' => $this->uid);
		$st = I('st', '', 'trim,intval');
		if($st == 999){
			$map['order_status'] = array('IN',array(0,-1,1,2,3,8));
		}elseif($st == 0 || $st == ''){
			$map['order_status'] = 0;
		}else{
			$map['order_status'] = $st;
		}
		$this->assign('st',$st);
		$count = $obj->where($map)->count();
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
			die('0');
		}
        $st = I('st', '', 'trim,intval');
        if($st == 999){
            $map['order_status'] = array('in',array(0,-1,1,2,8));
        }elseif($st == 0 || $st == ''){
            $map['order_status'] = 0;
        }else{
            $map['order_status'] = $st;
        }
		$list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$shop_ids = $room_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
			$room_ids[$val['room_id']] = $val['room_id'];
        }
        if (!empty($shop_ids)) {
            $this->assign('shops', D('Booking')->itemsByIds($shop_ids));
        }
		if (!empty($room_ids)) {
            $this->assign('room', D('Bookingroom')->itemsByIds($room_ids));
        }
		$this->assign('list', $list); 
		$this->assign('page', $show); 
		$this->display(); 
	}
    

    public function detail($order_id){
		$Bookingorder = D('Bookingorder');
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = $Bookingorder->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作');
        }else{
            $shop = D('Booking')->find($detail['shop_id']);
            $list = D('Bookingordermenu')->where(array('order_id'=>$order_id))->select();
            $menu_ids = array();
            foreach($list as $k=>$val){
                $menu_ids[$val['menu_id']] = $val['menu_id'];
            }
            if($menu_ids){
                $this->assign('menus',D('Bookingmenu')->itemsByIds($menu_ids));
            }
            $log = D('Paymentlogs')->where(array('type'=>'ding','order_id'=>$order_id))->find();
			$this->assign('room', D('Bookingroom')->find($detail['room_id']));
            $this->assign('log',$log);
            $this->assign('list',$list);
            $this->assign('shop',$shop);
            $this->assign('detail',$detail);
            $this->display();
        }
	}

    public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->error('订单不存在');
       }elseif(!$detail = D('Bookingorder')->find($order_id)){
           $this->error('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->error('非法操作订单');
       }else{
           if(false !== D('Bookingorder')->cancel($order_id)){
               $this->success('订单取消成功',U('booking/detail',array('order_id'=>$order_id)));
           }else{
               $this->error('订单取消失败');
           }
       }
    }
    
	public function comment($order_id) {
		$Bookingorder = D('Bookingorder');
        if(!$order_id = (int) $order_id){
            $this->error('没有该订单');
        }elseif (!$detail = $Bookingorder->find($order_id)) {
            $this->error('没有该订单');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('不要评价别人的订座订单');
        }elseif($detail['comment_status'] ==1){
            $this->error('该订单已评价过了');
        }else{
            if ($this->_Post()) {
                //$datas = $this->checkFields($this->_post('data', false), array('score','kw_score','hj_score','fw_score','contents'));
                $datas = $this->checkFields($this->_post('data', false), array('score','contents'));
                $data['user_id'] = $this->uid;
                $data['booking_id'] = $detail['shop_id'];
                $data['order_id'] = $order_id;
                $data['score'] = (int) $datas['score'];
                if (empty($data['score'])) {
                    $this->tuMsg('评分不能为空');
                }
                if ($data['score'] > 5 || $data['score'] < 1) {
                    $this->tuMsg('评分为1-5之间的数字');
                }
//                if (empty($datas['kw_score'])) {
//                    $this->tuError('口味评分不能为空');
//                }
//                if ($datas['kw_score'] > 5 || $datas['kw_score'] < 1) {
//                    $this->tuMsg('口味评分为1-5之间的数字');
//                }
//                if (empty($datas['hj_score'])) {
//                    $this->tuMsg('环境评分不能为空');
//                }
//                if ($datas['hj_score'] > 5 || $datas['hj_score'] < 1) {
//                    $this->tuMsg('环境评分为1-5之间的数字');
//                }
//                if (empty($datas['fw_score'])) {
//                    $this->tuMsg('服务评分不能为空');
//                }
//                if ($datas['fw_score'] > 5 || $datas['fw_score'] < 1) {
//                    $this->tuMsg('服务评分为1-5之间的数字');
//                }

                $data['contents'] = htmlspecialchars($datas['contents']);
                if (empty($data['contents'])) {
                    $this->tuMsg('评价内容不能为空');
                }
                if ($words = D('Sensitive')->checkWords($datas['contents'])) {
                    $this->tuMsg('评价内容含有敏感词：' . $words);
                }
                $photos = $this->_post('photos', false);
                if($photos){
                    $data['have_photo'] = 1;
                }
            	$data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_booking_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $data2 = array('shop_id'=>$detail['shop_id']);
                $shop = D('Booking')->find($detail['shop_id']);
//                $data2['kw_score'] = round(($shop['comments']*$shop['kw_score']+$datas['kw_score'])/($shop['comments']+1),1);
//                $data2['hj_score'] = round(($shop['comments']*$shop['hj_score']+$datas['hj_score'])/($shop['comments']+1),1);
//                $data2['fw_score'] = round(($shop['comments']*$shop['fw_score']+$datas['fw_score'])/($shop['comments']+1),1);
                $data2['score'] = round(($shop['comments']*$shop['score']+$data['score'])/($shop['comments']+1),1);
                $data2['comments'] = $shop['comments'] + 1;
                if (D('Bookingdianping')->add($data)) {
                    $photos = $this->_post('photos', false);
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        D('Bookingdianpingpic')->upload($order_id, $local);
                    }
                    D('Bookingorder')->updateCount($order_id, 'comment_status');
                    D('Booking')->save($data2);
                    D("Shop")->updateCount($detail['shop_id'], "score_num");
                    D("Users")->prestige($this->uid, "dianping");
                    D('Users')->updateCount($this->uid, 'ping_num');
                    $this->tuMsg('恭喜您点评成功!', U('booking/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $details = D('Booking')->find($detail['shop_id']);
                $this->assign('details', $details);
                $this->assign('order_id', $order_id);
                $this->display();
            }
        }
    }

    //在线订座申请退款
    public function refund($order_id){
        if($order_id ==0){
            $order_id = I('order_id', 0, 'trim,intval');
        }
        $bookingorder = D('Bookingorder');
        $appoint_order = $bookingorder->where('order_id =' . $order_id)->find();
        if(M('OrderRefund')->where(['type'=>0,'goods_id'=>$order_id])->find()){
            $this->error('已提交过申请，请等待商家审核');
        }
        if (!$appoint_order) {
            $this->error('未检测到ID');
        }else{
            if ($appoint_order['user_id'] != $this->uid) {
                $this->error('请不要操作他人的订单');
            }elseif($appoint_order['order_status'] != 1){
                $this->error('当前订单状态不永许这样操作');
            }else{
                if($this->isPost()){
                    $time=time();
                    //判断当前是不是退款有效时间
                    if($time>$appoint_order['end_time']){
                        $this->tuMsg('该订单已超过退款时间');
                    }
                    $data = $this->checkFields($this->_post('data', false),array('pic','attr_id','goods_price'));
                    if(empty($data['attr_id'])){
                        $this->error('请选择退款理由');
                    }
                    $data['user_id'] = $this->uid;
                    $data['shop_id'] = $appoint_order['shop_id'];
                    $data['create_time'] = NOW_TIME;
                    $data['type'] = 10;
                    $data['goods_price'] = htmlspecialchars($data['goods_price']);
                    $data['ramke'] = htmlspecialchars($data['ramke']);
                    $data['goods_id'] = $order_id;
                    $data['status'] = 0;
                    //var_dump($order_id);die;
                    if(D('Bookingorder')->where(['order_id'=>$order_id])->save(['order_status'=>3])){
                        if(false !==(M('OrderRefund')->add($data))){
                            $this->success('申请退款成功',U('booking/index'));
                        }else{
                            $this->error('申请退款失败1');
                        }
                    }else{
                        $this->error('申请退款失败2');
                    }
                }else{
                    $Appointworker = D('Bookingorder')->find($order_id);
                    $this->assign('appointworker',$Appointworker);
                    $this->assign('refund',M('RefundAttr')->where(['type'=>10])->select());
                    $this->assign('detail',$appoint_order);
                    $this->display();
                }

            }
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>10))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("Bookingorder")->where(array("order_id" => $order_id))->find();
        //查询订单信息
        if($this->_post()){
            //获取页面信息
            $content=I('post.content');
            $photo=I('post.photo');
            $userid=$this->uid;
            $shop_id=$shop["shop_id"];
            $data=array();
            $data['content']=$content;
            if(empty($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉内容不能为空'));
            }
            if($words = D('Sensitive')->checkWords($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评价内容含有敏感词：' . $words));
            }
            $data['photo']=$photo;
            $data['shop_id']=$shop_id;
            $data['order_id']=$order_id;
            $data['user_id']=$userid;
            $data['type']=10;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('booking/comment',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }




}
