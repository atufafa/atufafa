<?php
class KtvAction extends CommonAction {
	protected function _initialize(){
       parent::_initialize();
	   $this->assign('getTypes', D('KtvOrder')->getType());//订单状态
    }
  
    public function index() {
        $status = (int) $this->_param('status');
		$this->assign('status', $status);
		$this->display(); 
    }
    
    public function loaddata() {
		$obj = D('KtvOrder');
		import('ORG.Util.Page'); 
		$map = array('user_id' => $this->uid,'closed'=>0); 
		$status = (int) $this->_param('status');
		if ($status == 0 || empty($status)) { 
			$map['status'] = 0;
		}elseif ($status == 1) {    
			$map['status'] = 1;
		}elseif ($status == 2) {    
			$map['status'] = 2;
		}elseif ($status == 3) {    
			$map['status'] = 3;
		}elseif ($status == 4) {    
			$map['status'] = 4;
		}elseif ($status == 8) {    
			$map['status'] = 8;
		}
		$count = $obj->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
            die('0');
		}
		$list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
            if($ktv = D('Ktv')->where(array('ktv_id'=>$v['ktv_id']))->find()){
                $list[$k]['ktv'] = $ktv;
            }
			if($room = D('KtvRoom')->where(array('room_id'=>$v['room_id']))->find()){
                $list[$k]['room'] = $room;
            }
        }
		$this->assign('list', $list); 
		$this->assign('page', $show); 
		$this->display(); 
	}

    
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('KtvOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $detail['room'] = D('KtvRoom')->where(array('room_id'=>$detail['room_id']))->find(); 
           $detail['ktv'] = D('Ktv')->where(array('ktv_id'=>$detail['ktv_id']))->find();
           $this->assign('detail',$detail);
           $this->display();
        }
    }


	//KTV订单已付款二维码
	 public function qrcode(){
        $order_id = $this->_get('order_id');
        if (!($detail = D('KtvOrder')->find($order_id))) {
            $this->error('没有该订单');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->error("非法操作！");
        }
        if ($detail['status'] != 1 || $detail['is_used_code'] != 0) {
            $this->error('该订单未付款或者已验证');
        }
        $url = U('seller/ktv/used', array('order_id' => $order_id, 't' => NOW_TIME, 'sign' => md5($order_id . C('AUTH_KEY') . NOW_TIME)));
        $token = 'ktv_order_id_' . $order_id;
        $file = tuQrCode($token, $url);
        $this->assign('file', $file);
        $this->assign('detail', $detail);
        $this->display();
    }
   //删除订单
   public function delete($order_id){
	   $obj = D('KtvOrder');
       if(!$order_id = (int)$order_id){
           $this->tuMsg('订单不存在');
       }elseif(!$detail = $obj->find($order_id)){
           $this->tuMsg('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->tuMsg('非法操作订单');
       }elseif($detail['status'] != 0){
           $this->tuMsg('该订单无法删除');
       }else{
           if($obj->where(array('order_id'=>$order_id))->save(array('closed'=>1))){
			    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 8,$status = 11);
			    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 8,$status = 11);
               $this->tuMsg('订单删除成功');
           }else{
               $this->tuMsg('订单删除失败');
           }
       }
   }
   
   //最新封装退款
    public function refund($order_id){
        if($order_id ==0){
            $order_id = I('order_id', 0, 'trim,intval');
        }
        $obj = D('KtvOrder');
    		if(!$detail = $obj->where('order_id =' . $order_id)->find()) {
               $this->error('错误');
            }elseif($detail['user_id'] != $this->uid) {
               $this->error('请不要操作他人的订单');
            }elseif($detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 3) {
               $this->error('当前订单状态不支持退款');
            }else{
              if($this->isPost()){
                  $time=time();
                  //判断当前是不是退款有效时间
                  if($time>$detail['end_time']){
                      $this->tuMsg('该订单已超过退款时间');
                  }
                $data = $this->checkFields($this->_post('data', false),array('pic','attr_id','goods_price'));
                    if(empty($data['attr_id'])){
                        $this->error('请选择退款理由');
                    }
                    $data['user_id'] = $this->uid;
                    $data['shop_id'] = $detail['shop_id'];
                    $data['create_time'] = NOW_TIME;
                    $data['type'] = 9;
                    $data['goods_price'] = htmlspecialchars($data['goods_price']);
                    $data['ramke'] = htmlspecialchars($data['ramke']);
                    $data['goods_id'] = $order_id;
                    $data['status'] = 0;
                    if(M('KtvOrder')->where(['order_id'=>$order_id])->save(['status'=>3])){
                        if(false !==(M('OrderRefund')->add($data))){
                            $this->success('申请退款成功',U('ktv/index'));
                        }else{
                            $this->error('申请退款失败1');
                        }   
                    }else{
                        $this->error('申请退款失败2');
                         // $this->tuMsg('申请退款失败2');
                    }
              }else{
                $this->assign('refund',M('RefundAttr')->where(['type'=>9])->select());
                $this->assign('ktv',M('KtvRoom')->where(['room_id'=>$detail['room_id'],'ktv_id'=>$detail['ktv_id']])->find());
                $this->assign('detail',$detail);
                $this->display();
              }
        			// if(false == $obj->ktv_user_refund($order_id)) {//更新什么什么的
        			// 	$this->tuMsg($obj->getError());
        			// }else{
        			// 	$this->tuMsg('申请退款成功', U('ktv/index', array('status' => 3)));
    		}
    }
   
      /**
     * KTV评价
     * @author pingdan <[<email address>]>
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function comment($order_id){
        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D("KtvOrder")->find($order_id))) {
            $this->error("该订单不存在");
        }
        if ($detail['user_id'] != $this->uid) {
            $this->error("请不要操作他人的订单aa");
        }
        if ($detail['is_comment'] == 1) {
            $this->error("您已经点评过了");
        }
        $room = D('KtvRoom')->where(array('room_id' => $detail['room_id']))->find();
        if ($room) {
          $detail['title'] = $room['title'];
          $detail['photo'] = $room['photo'];
          $detail['daofu_price'] = $room['daofu_price'];
        }

        if ($this->isPost()) {
            $data = $this->checkFields($this->_post("data", FALSE), array("score", "cost", "content"));
            $data['user_id'] = $this->uid;
            $data['order_id'] = $detail['order_id'];
            $data['ktv_id'] = $detail['ktv_id'];
            $data['shop_id'] = $detail['shop_id'];
            $data['room_id'] = $room['room_id'];
            $data['score'] = (int) $data['score'];
            if ($data['score'] <= 0 || 5 < $data['score']) {
                $this->tuMsg("请选择评分");
            }
            $data['content'] = htmlspecialchars($data['content']);
            if (empty($data['content'])) {
                $this->tuMsg("不说点什么么");
            }
            $data['create_time'] = NOW_TIME;
            $data_ktv_dianping = $this->_CONFIG['mobile']['data_ktv_dianping'];
            $data['show_date'] = date('Y-m-d', NOW_TIME + $data_ktv_dianping * 86400);
            $data['create_ip'] = get_client_ip();

            $obj = D("KtvComment");
            if ($comment_id = $obj->add($data)) {
                $photos = $this->_post("photos", FALSE);
                $local = array();
                foreach ($photos as $val) {
                    if (isimage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D("Ktvuploadpics")->upload($order_id, $local, $type=1); //type=1点评
                }
                D("KtvOrder")->save(array("order_id" => $order_id, "is_comment" => 1));
                D("Users")->updateCount($this->uid, "ping_num");
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                //D("Users")->prestige($this->uid, "dianping");
                $this->tuMsg("评价成功", U("user/ktv/index/"));
            }
            $this->tuMsg("操作失败！");
        } else {
            $this->assign("detail", $detail);
            $goods = D('Goods')->where('goods_id =' . $goods_id)->find();
            $this->assign("goods", $goods);
            $this->display();
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>5))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("KtvOrder")->where(array("order_id" => $order_id))->find();
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
            $data['type']=5;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('ktv/comment',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }
}
