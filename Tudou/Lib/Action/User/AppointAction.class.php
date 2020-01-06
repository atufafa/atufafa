<?php
class AppointAction extends CommonAction{
    protected function _initialize(){
        parent::_initialize();
		if ($this->_CONFIG['operation']['appoint'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
		$Appointcate = D('Appointcate')->fetchAll();//分类表
        $this->assign('appointcate', $Appointcate);
		
		$this->assign('orderTypes', $orderTypes = D('Appoint')->getAppoinOrderType());
		$this->assign('stars', $stars = D('Appoint')->getAppoinStar());
		$this->assign('certs', $certs = D('Appoint')->getAppoinCert());
		$this->assign('dates', $dates = D('Appoint')->getAppoinDate());
		
		$this->assign('zodiacs', $zodiacs = D('Appoint')->getAppoinZodiac());
		$this->assign('constellatorys', $constellatorys = D('Appoint')->getAppoinConstellatory());
		$this->assign('mandarins', $mandarins = D('Appoint')->getAppoinMandarin());
		
		$this->assign('states',$state = D('AppointCard')->getstate());//优惠卡订单状态
		$this->assign('statuss',D('AppointCard')->getStatus());//优惠卡订单状态
     
    }
	
	
	
    public function index(){
		$st = (int) $this->_param('st');
		$this->assign('st', $st);
        $this->display();
    }
   public function loaddata() {
        $Appoint = D('Appoint');
        $Appointorder = D('Appointorder');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid,'closed'=>0);
		$st = (int) $this->_param('st');
		if ($st == 1) {
			$map['status'] = 1;
		}elseif ($st == 2) {
			$map['status'] = 2;
		}elseif ($st ==3) {
			$map['status'] = array('in',array(3,5));
		}elseif ($st == 4) {
			$map['status'] = 4;
		}elseif ($st == 8) {
			$map['status'] = 8;
		}else{
			$map['status'] = 0;
		}
        $count = $Appointorder->where($map)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Appointorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $worker_ids = $appoint_ids = array();
        foreach ($list as $k => $val) {
            $appoint_ids[$val['appoint_id']] = $val['appoint_id'];
			$worker_ids[$val['worker_id']] = $val['worker_id'];
        }
        $this->assign('appoints', $Appoint->itemsByIds($appoint_ids));
		$this->assign('worker', D('Appointworker')->itemsByIds($worker_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	//家政详情
	public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Appointorder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $Appoint = D('Appoint')->find($detail['appoint_id']);
		   $this->assign('appoint',$Appoint);
		   $Appointworker = D('Appointworker')->find($detail['worker_id']);
		   $this->assign('appointworker',$Appointworker);
		   
           $this->assign('detail',$detail);
           $this->display();
        }
    }

    //家政详情
    public function details($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Appointorder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
            $Appoint = D('Appoint')->find($detail['appoint_id']);
            $this->assign('appoint',$Appoint);
            $this->assign('useEnvelope',D('Appointorder')->GetuseEnvelope($this->uid,$detail['shop_id']));
            $Appointworker = D('Appointworker')->find($detail['worker_id']);
            $this->assign('appointworker',$Appointworker);

            $this->assign('detail',$detail);
            $this->display();
        }
    }
	
  //删除订单重做
    public function orderdel($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Appointorder');
            if (!($detial = $obj->find($order_id))) {
                $this->tuMsg('该订单不存在');
            }elseif($detial['status'] != 0 && $detial['status'] != 4){
				$this->tuMsg('该订单暂时不能取消');
			}elseif($detial['user_id'] != $this->uid){
				$this->tuMsg('请不要操作他人的订单');
			}else{
				if ($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 11);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 11);
					$this->tuMsg('您已成功删除家政订单', U('appoint/index', array('st' => 1)));
				}else{
					$this->tuMsg('操作失败');
				}
			}
		}
    }
	//家政申请退款
	public function refund($order_id){
        if($order_id ==0){
            $order_id = I('order_id', 0, 'trim,intval');
        }
        $Appointorder = D('Appointorder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
        if(M('OrderRefund')->where(['type'=>1,'status'=>0,'goods_id'=>$order_id])->find()){
            $this->error('已提交过申请，请等待商家审核');
        }
        if (!$appoint_order) {
            $this->error('未检测到ID');
        }else{
            if ($appoint_order['user_id'] != $this->uid) {
                $this->error('请不要操作他人的订单');
            }else{

                if($this->isPost()){
                     $data = $this->checkFields($this->_post('data', false),array('pic','attr_id','goods_price'));
                    if(empty($data['attr_id'])){
                        $this->error('请选择退款理由');
                    }
                    $OrderRefund=M('OrderRefund')->where(['goods_id'=>$order_id])->find();
                    if($OrderRefund==null){
                        $data['user_id'] = $this->uid;
                        $data['shop_id'] = $appoint_order['shop_id'];
                        $data['create_time'] = NOW_TIME;
                        $data['type'] = 1;
                        $data['goods_price'] = htmlspecialchars($data['goods_price']);
                        $data['ramke'] = htmlspecialchars($data['ramke']);
                        $data['goods_id'] = $order_id;
                        $data['status'] = 0;
                        if(M('AppointOrder')->where(['order_id'=>$order_id])->save(['status'=>3])){
                            if(false !==(M('OrderRefund')->add($data))){
                                $this->success('申请退款成功',U('appoint/index'));
                            }else{
                                $this->error('申请退款失败1');
                            }
                        }else{
                            $this->error('申请退款失败2');
                            // $this->tuMsg('申请退款失败2');
                        }
                    }else{
                        $Model = M();
                        $Model->startTrans();
                        $res2=M('AppointOrder')->where(array('order_id'=>$order_id))->save(array('status'=>3));
                        $detail=M('OrderRefund')->where(array('goods_id'=>$order_id))->save(array('status'=>0));
                        if($detail && $res2){
                            $Model->commit();
                            $this->success('申请退款成功',U('appoint/index'));
                        }else{
                            $Model->rollback();
                            $this->error('申请退款失败3');
                        }
                    }
                }else{
                    $Appointworker = D('Appointworker')->find($detail['worker_id']);
                    $this->assign('appointworker',$Appointworker);
                     $this->assign('refund',M('RefundAttr')->where(['type'=>1])->select());
                    $this->assign('detail',$appoint_order);
                    $this->display();
                }
				// $Appointorder->where('order_id =' . $order_id)->setField('status', 3);
				// D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 3);
				// D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 3);
    //        		$this->tuMsg('申请退款成功', U('appoint/index', array('st' => 3)));
			}
        }		
    }
	//取消家政申请退款订单
    public function cancel_refund(){
        $order_id = I('order_id', 0, 'trim,intval');
        $Appointorder = D('Appointorder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
        if (!$appoint_order) {
            $this->tuMsg('操作错误');
        } else {
            if ($appoint_order['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }elseif($appoint_order['status'] != 3){
				 $this->tuMsg('订单状态不正确');
			}else{
				$Appointorder->where('order_id =' . $order_id)->setField('status', 1);
				$this->tuMsg('家政取消退款成功！',U('appoint/index', array('st' => 2)));
			}
			
        }
    }
	
	//用户确认订单完成
    public function confirm_complete(){
        $order_id = I('order_id', 0, 'trim,intval');
        $Appointorder = D('Appointorder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
        if (!$appoint_order) {
            $this->tuMsg('操作错误');
        } else {
            if ($appoint_order['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }elseif($appoint_order['status'] != 2){
				 $this->tuMsg('当前订单状态不永许这样操作');
			}else{
				if (false == D('Appointorder')->appoint_settlement($order_id)) {//确认订单去把余额返回给商家
					$this->tuMsg('非法操作');
				}else{
					$this->tuMsg('您已成功确认订单，请给我们评价下吧',U('appoint/index', array('st' => 8)));	
				}
				
			}
			
        }
    }
	
	//家政点评
	 public function comment($order_id) {
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Appointorder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作订单');
        }elseif($detail['comment_status'] == 1){
            $this->error('已经评价过了');
        }else{
            if ($this->_Post()) {
                $data = $this->checkFields($this->_post('data', false), array('score','d1','d2','d3','contents'));
                $data['user_id'] = $this->uid;
				$data['shop_id'] = $detail['shop_id'];
                $data['appoint_id'] = $detail['appoint_id'];
				$data['worker_id'] = $detail['worker_id'];
                $data['order_id'] = $order_id;
                $data['score'] = (int) $data['score'];
                if (empty($data['score'])) {
                    $this->tuMsg('评分不能为空');
                }
                if ($data['score'] > 5 || $data['score'] < 1) {
                    $this->tuMsg('评分为1-5之间的数字');
                }
				
				$config = $config = D('Setting')->fetchAll();
				$data['d1'] = (int) $data['d1'];
				if(empty($data['d1'])){
					$this->tuMsg($config['appoint']['d1'].'评分不能为空');
				}
				if($data['d1'] > 5 || $data['d1'] < 1){
					$this->tuMsg($config['appoint']['d1'].'格式不正确');
				}
				$data['d2'] = (int) $data['d2'];
				if(empty($data['d2'])){
					$this->tuMsg($config['appoint']['d2'].'评分不能为空');
				}
				if($data['d2'] > 5 || $data['d2'] < 1){
					$this->tuMsg($config['appoint']['d2'].'格式不正确');
				}
				$data['d3'] = (int) $data['d3'];
				if(empty($data['d3'])){
					$this->tuMsg($config['appoint']['d3'].'评分不能为空');
				}
				if($data['d3'] > 5 || $data['d3'] < 1){
					$this->tuMsg($config['appoint']['d3'].'格式不正确');
				}
		
		
				
                $data['contents'] = htmlspecialchars($data['contents']);
                if (empty($data['contents'])) {
                    $this->tuMsg('评价内容不能为空');
                }
                if ($words = D('Sensitive')->checkWords($data['contents'])) {
                    $this->tuMsg('评价内容含有敏感词：' . $words);
                }
				$data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_appoint_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $photos = $this->_post('photos', false);
                if ($dianping_id = D('Appointdianping')->add($data)) {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        foreach($local as $k=>$val){
                            D('Appointdianpingpics')->add(array('dianping_id'=>$dianping_id,'order_id'=>$order_id,'pic'=>$val));
                        }
                    }
                    D('Appointorder')->save(array('order_id'=>$order_id,'comment_status'=>1));
                    D('Users')->updateCount($this->uid, 'ping_num');
                    $this->tuMsg('恭喜您点评成功', U('appoint/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $this->assign('detail', $detail);
                $this->assign('appoint',D('Appoint')->find($detail['appoint_id']));
				$this->assign('worker',D('Appointworker')->find($detail['worker_id']));
                $this->display();
            }
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>6))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("Appointorder")->where(array("order_id" => $order_id))->find();
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
            $data['type']=6;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('appoint/comment',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }
	
	
	public function card() {
        $state= (int) $this->_param('state');
		$this->assign('state', $state);
		$this->display(); 
    }
  
	//优惠卡列表
    public function cardloaddata(){
        $obj = D('AppointCard');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid);
		
        if($state = (int) $this->_param('state')){
            if($state != 0){
                $map['state'] = $state;
            }else{
				$map['state'] = 0;
			}
            $this->assign('state', $state);
        }else{
			$map['state'] = 0;
            $this->assign('state', 0);
        }
        $count = $obj->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if($Page->totalPages < $p) {
            die('0');
		}
        $list = $obj->where($map)->order(array('card_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
           if($Users = D('Users')->where(array('user_id'=>$val['user_id']))->find()){
                $list[$k]['user'] = $Users;
           }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function logs() {
        $status= (int) $this->_param('status');
		$this->assign('status', $status);
		$this->display(); 
    }
	
	//优惠卡使用记录
   public function cardload(){
        $obj = D('AppointCardLogs');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid);
        if($status = (int) $this->_param('status')){
            if($status){
                $map['status'] = $status;
            }else{
				$map['status'] = 0;
			}
            $this->assign('status', $status);
        }else{
			$map['status'] = 0;
            $this->assign('status', 0);
        }
        $count = $obj->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if($Page->totalPages < $p) {
            die('0');
		}
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
           if($Users = D('Users')->where(array('user_id'=>$val['user_id']))->find()){
                $list[$k]['user'] = $Users;
           }
		   if($Shop = D('Shop')->where(array('shop_id'=>$val['shop_id']))->find()){
                $list[$k]['shop'] = $Shop;
           }
		   if($Card = D('CouponCard')->where(array('card_id'=>$val['card_id']))->find()){
                $list[$k]['card'] = $Card;
           }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	//兑换页面
	public function exchange(){
		
        if($this->isPost()) {
            $cardNumber = $this->_post('cardNumber');
            if(empty($cardNumber)){
                $this->tuMsg('兑换码不能为空');
            }
            $res = D('AppointCard')->where(array('cardNumber'=>$cardNumber))->find();
            if(!$res){
                $this->tuMsg('兑换码不存在');
            }
			
			if($res['end_date'] < TODAY){
                $this->tuMsg('兑换码已经过期');
            }
			if($res['state'] != 0){
                $this->tuMsg('兑换码已经失效');
            }
			if($res['user_id']){
                $this->tuMsg('兑换码已经是别人持有');
            }
			if($res['cardMoney'] <= 0){
                $this->tuMsg('兑换码参数错误');
            }
		   	if(D('AppointCard')->where(array('cardNumber'=>$cardNumber))->save(array('user_id'=>$this->uid))){
			   $this->tuMsg('兑换成功', U('appoint/card'));
			}else{
				$this->tuMsg('操作失败');
			}
        }
		
        $this->display();
    }
}