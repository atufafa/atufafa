<?php
class EduAction extends CommonAction {
	public function _initialize() {
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['edu'])) {
            $this->error('教育功能已关闭');
            die;
        }
        $this->age = D('Edu')->getEduage();
        $this->assign('age', $this->age);
        $this->get_time = D('Edu')->getEduTime();
        $this->assign('get_time', $this->get_time);
		$this->get_edu_class = D('Edu')->getEduClass();
        $this->assign('class', $this->get_edu_class);
		$this->assign('cates', D('Educate')->fetchAll());
		$this->assign('types', D('EduOrder')->getType());
    }
  
    public function index() {
        $st = (int) $this->_param('st');
		$this->assign('st', $st);
		$this->display(); 
    }
    //教育订单列表
    public function loaddata() {
		$EduOrder = D('EduOrder');
		import('ORG.Util.Page'); 
		$map = array('user_id' => $this->uid); 
		$st = (int) $this->_param('st');
		if ($st == 0 || empty($st)) { 
			$map['order_status'] = 0;
		}elseif ($st == 1) {    
			$map['order_status'] = 1;
		}elseif ($st == -1) {    
			$map['order_status'] = -1;
		}elseif ($st == 8) {    
			$map['order_status'] = 8;
		}
		$count = $EduOrder->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
            die('0');
		}
		$list = $EduOrder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
            if($course = D('Educourse')->where(array('course_id'=>$v['course_id']))->find()){
                $list[$k]['course'] = $course;
            }
        }
		$this->assign('list', $list); 
		$this->assign('page', $show); 
		$this->display(); 
	}
	//教育订单已付款二维码
	 public function qrcode(){
        $order_id = $this->_get('order_id');
        if (!($detail = D('EduOrder')->find($order_id))) {
            $this->error('没有该订单');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->error("非法操作！");
        }
        if ($detail['order_status'] != 1 || $detail['is_used_code'] != 0) {
            $this->error('该订单未付款或者已验证');
        }
        $url = U('seller/edu/used', array('order_id' => $order_id, 't' => NOW_TIME, 'sign' => md5($order_id . C('AUTH_KEY') . NOW_TIME)));
        $token = 'edu_order_id_' . $order_id;
        $file = tuQrCode($token, $url);
        $this->assign('file', $file);
        $this->assign('detail', $detail);
        $this->display();
    }

    //教育订单详情
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('EduOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $detail['course'] = D('Educourse')->where(array('course_id'=>$detail['course_id']))->find(); 
           $detail['edu'] = D('Edu')->where(array('edu_id'=>$detail['edu_id']))->find();
           $this->assign('detail',$detail);
           $this->display();
        }
    }

    //教育订单取消
   public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->error('订单不存在');
       }elseif(!$detail = D('EduOrder')->find($order_id)){
           $this->error('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->error('非法操作订单');
       }else{
           if(false !== D('EduOrder')->cancel($order_id)){
               $this->success('订单取消成功');
           }else{
               $this->error('订单取消失败');
           }
       }
   }
   //教育订单点评
   public function comment($order_id) {
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('EduOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作订单');
        }elseif($detail['comment_status'] == 1){
            $this->error('已经评价过了');
        }else{
            if ($this->_Post()) {
                $data = $this->checkFields($this->_post('data', false), array('score', 'content'));
                $data['user_id'] = $this->uid;
				if (!$Educourse= D('Educourse')->find($detail['course_id'])) {
                    $this->tuMsg('没有找到课程，请稍后再试');
                }
				$edu = D('Edu')->where(array('edu_id'=>$detail['edu_id']))->find();
				$data['shop_id'] = $edu['shop_id'];
                $data['course_id'] = $detail['course_id'];
                $data['order_id'] = $order_id;
                $data['score'] = (int) $data['score'];
                if (empty($data['score'])) {
                    $this->tuError('评分不能为空');
                }
                if ($data['score'] > 5 || $data['score'] < 1) {
                    $this->tuError('评分为1-5之间的数字');
                }
                $data['content'] = htmlspecialchars($data['content']);
                if (empty($data['content'])) {
                    $this->tuError('评价内容不能为空');
                }
                if ($words = D('Sensitive')->checkWords($data['content'])) {
                    $this->tuError('评价内容含有敏感词：' . $words);
                }
				$data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_edu_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $photos = $this->_post('photos', false);
                if($photos){
                    $data['have_photo'] = 1;
                }
                if ($comment_id = D('EduComment')->add($data)) {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        foreach($local as $k=>$val){
                            D('EduCommentPics')->add(array('comment_id'=>$comment_id,'photo'=>$val));
                        }
                    }
                    D('EduOrder')->save(array('order_id'=>$order_id,'comment_status'=>1));
                    D('Users')->updateCount($this->uid, 'ping_num');
                    D("Shop")->updateCount($edu['shop_id'], "score_num");
                    $this->tuMsg('恭喜您点评成功!', U('edu/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $detail['course'] = D('Educourse')->where(array('course_id'=>$detail['course_id']))->find();
                $detail['edu'] = D('Edu')->where(array('edu_id'=>$detail['edu_id']))->find();
                // print_r($detail);die;
                $this->assign('detail', $detail);
                $this->display();
            }
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>7))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("EduOrder")->where(array("order_id" => $order_id))->find();
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
            $data['type']=7;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('edu/comment',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }

    //教育申请退款
    public function refund($order_id){
        if($order_id ==0){
            $order_id = I('order_id', 0, 'trim,intval');
        }
        $eduorder = D('EduOrder');
        $appoint_order = $eduorder->where('order_id =' . $order_id)->find();
        $map2 = array('order_id'=>$order_id,'start_time'=>array('gt',0),'end_time'=>array('gt',0));
        $minutes = M('edu_class_record')->where($map2)->sum('minutes');
        $money=M('edu_course')->where(['edu_id'=>$appoint_order['edu_id']])->find();
        $minutes_price=($money['money'])/60*$minutes;
        $minutes_price=round($minutes_price,2);
        $need_pay=($appoint_order['need_pay'])-$minutes_price;
        $this->assign('need_pay',$need_pay);
        $this->assign('minutes_price',$minutes_price);
        //$eduorder['class_time']
        if(M('OrderRefund')->where(['type'=>7,'status'=>0,'goods_id'=>$order_id])->find()){
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
                    $data = $this->checkFields($this->_post('data', false),array('pic','attr_id','goods_price','minutes_price'));
                    if(empty($data['attr_id'])){
                        $this->error('请选择退款理由');
                    }
                    $data['user_id'] = $this->uid;
                    $data['shop_id'] = $appoint_order['shop_id'];
                    $data['create_time'] = NOW_TIME;
                    $data['type'] = 7;
                    $data['goods_price'] = htmlspecialchars($data['goods_price']);
                    $data['minutes_price'] = htmlspecialchars($data['minutes_price']);
                    $data['ramke'] = htmlspecialchars($data['ramke']);
                    $data['goods_id'] = $order_id;
                    $data['status'] = 0;
                    if(M('EduOrder')->where(['order_id'=>$order_id])->save(['order_status'=>3])){
                        if(false !==(M('OrderRefund')->add($data))){

                            $this->success('申请退款成功',U('edu/index'));
                        }else{
                            $this->error('申请退款失败1');
                        }
                    }else{
                        $this->error('申请退款失败2');
                        // $this->tuMsg('申请退款失败2');
                    }
                }else{
                    $Appointworker = D('Educourse')->find($appoint_order['course_id']);
                    $this->assign('appointworker',$Appointworker);
                    $this->assign('refund',M('RefundAttr')->where(['type'=>7])->select());
                    $this->assign('detail',$appoint_order);
                    $this->display();
                }
            }
        }
    }

    //教育订单授课详情
    public function details($order_id){
        $EduOrder = M('edu_class_record');
        import('ORG.Util.Page');
        $map = array('user_id'=>$this->uid,'order_id'=>$order_id);
        $count = $EduOrder->where($map)->count();
        $map2 = array('user_id'=>$this->uid,'order_id'=>$order_id,'start_time'=>array('gt',0),'end_time'=>array('gt',0));
        $minutes = $EduOrder->where($map2)->sum('minutes');
        $Page  = new Page($count,25);
        $show  = $Page->show();
        $list = $EduOrder->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('list',$list);
        $this->assign('minutes',$minutes);
        $this->assign('page',$show);
        $this->display();
    }





}
