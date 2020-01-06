<?php
class FarmAction extends CommonAction {
	protected function _initialize(){
       parent::_initialize();
        if ($this->_CONFIG['operation']['farm'] == 0) {
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
		$farmorder = D('FarmOrder');
		import('ORG.Util.Page'); 
		$map = array('user_id' => $this->uid); 
		$st = (int) $this->_param('st');
		if ($st == 1) { 
			$map['order_status'] = array('in',array(-1,2));
		}elseif($st ==2){
            $map['order_status'] = array('in',array(2,3));
        }elseif ($st == 0) {   
			$map['order_status'] = array('in',array(0,1));
		}else{  
			$map['order_status'] = array('in',array(0,1));
		}
		$count = $farmorder->where($map)->count(); 
        
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
            die('0');
		}
		$list = $farmorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
            if($f = D('Farm')->where(array('farm_id'=>$v['farm_id']))->find()){
                $list[$k]['farm'] = $f;
            }
        }
        // print_r($list);die;
		$this->assign('list', $list); 
		$this->assign('page', $show); 
		$this->display(); 
	}

    
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('FarmOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $detail['package'] = D('FarmPackage')->where(array('pid'=>$detail['pid']))->find(); 
           $detail['farm'] = D('Farm')->where(array('farm_id'=>$detail['farm_id']))->find();
           $this->assign('detail',$detail);
           $this->display();
        }
    }

    
   public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->error('订单不存在');
       }elseif(!$detail = D('Hotelorder')->find($order_id)){
           $this->error('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->error('非法操作订单');
       }else{
           if(false !== D('Hotelorder')->cancel($order_id)){
               $this->success('订单取消成功');
           }else{
               $this->error('订单取消失败');
           }
       }
   }
   
   public function comment($order_id) {
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('FarmOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作订单');
        }elseif($detail['comment_status'] == 1){
            $this->error('已经评价过了');
        }else{
            if ($this->_Post()) {
                $data = $this->checkFields($this->_post('data', false), array('score', 'content'));
                $data['user_id'] = $this->uid;
                // print_r($detail);die;
				if (!$Farm = D('Farm')->where(array("farm_id"=>$detail['farm_id']))->find()) {
                    // echo D('Farm')->getlastSql();die;
                    $this->tuMsg('没有找到对应的农家乐，暂时无法点评，请稍后再试');
                }
				$data['shop_id'] = $Farm['shop_id'];
                $data['farm_id'] = $detail['farm_id'];
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
				$data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_fram_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $photos = $this->_post('photos', false);
                if($photos){
                    $data['have_photo'] = 1;
                }
                if ($comment_id = D('FarmComment')->add($data)) {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        foreach($local as $k=>$val){
                            D('FarmCommentPics')->add(array('comment_id'=>$comment_id,'photo'=>$val));
                        }
                    }
                    D('FarmOrder')->save(array('order_id'=>$order_id,'comment_status'=>1));
                    D('Users')->updateCount($this->uid, 'ping_num');
                    D('Farm')->updateCount($detail['farm_id'],'comments');
                    D('Farm')->updateCount($detail['farm_id'],'score',$data['score']);
                    $this->tuMsg('恭喜您点评成功!'.$comment_id, U('farm/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $detail['package'] = D('FarmPackage')->where(array('pid'=>$detail['pid']))->find();
                $detail['farm'] = D('Farm')->where(array('farm_id'=>$detail['farm_id']))->find();
                $this->assign('detail', $detail);
                $this->display();
            }
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>8))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("FarmOrder")->where(array("order_id" => $order_id))->find();
        $shops=D('Farm')->where(array('farm_id'=>$shop['farm_id']))->find();
        //查询订单信息
        if($this->_post()){
            //获取页面信息
            $content=I('post.content');
            $photo=I('post.photo');
            $userid=$this->uid;
            $shop_id=$shops["shop_id"];
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
            $data['type']=8;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('farm/comment',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }

    public function refund($order_id)
    {
        if($order_id ==0){
            $order_id = I('order_id', 0, 'trim,intval');
        }
        $Appointorder = D('FarmOrder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
        $shop=D('Farm')->where(array('farm_id'=>$appoint_order['farm_id']))->find();
        if(M('OrderRefund')->where(['type'=>4,'goods_id'=>$order_id])->find()){
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
                    $data['shop_id'] = $shop['shop_id'];
                    $data['create_time'] = NOW_TIME;
                    $data['type'] = 4;
                    $data['goods_price'] = htmlspecialchars($data['goods_price']);
                    $data['ramke'] = htmlspecialchars($data['ramke']);
                    $data['goods_id'] = $order_id;
                    $data['status'] = 0;
                    if(M('FarmOrder')->where(['order_id'=>$order_id])->save(['order_status'=>3])){
                        if(false !==(M('OrderRefund')->add($data))){
                            $this->success('申请退款成功',U('farm/index'));
                        }else{
                            $this->error('申请退款失败1');
                        }   
                    }else{
                        $this->error('申请退款失败2');
                         // $this->tuMsg('申请退款失败2');
                    }
                }else{
                    $Appointworker = D('Farm')->find($detail['farm_id']);
                    $this->assign('appointworker',$Appointworker);
                     $this->assign('refund',M('RefundAttr')->where(['type'=>4])->select());
                    $this->assign('detail',$appoint_order);
                    $this->display();
                }
                // $Appointorder->where('order_id =' . $order_id)->setField('status', 3);
                // D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 3);
                // D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 3);
    //              $this->tuMsg('申请退款成功', U('appoint/index', array('st' => 3)));
            }
        }       
    }

}
