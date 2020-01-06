<?php
class PindanAction extends CommonAction{
	
	protected function _initialize(){
       parent::_initialize();
    }
	
	
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }
	
	public function orderloading(){
        $obj = D('Pindanorder');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);
		
		$aready = I('aready', '', 'trim,intval');
		
		if($aready == 999){
			$map['status'] = array('in',array(1,-1,0,2,3,4,5,6,7,8));
        }elseif($aready == 0){
			$map['status'] = 0;
		}else{
			$map['status'] = $aready;
		}
        if($map['status'] ==3){
            $status = ['3','4'];
            $map['status'] = array('IN',$status);
        }
        if($map['status'] ==1){
            $status = ['1','2','5'];
            $map['status'] = array('IN',$status);
        }
		$this->assign('aready', $aready);
	
        $count = $obj->where($map)->count(); 
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $tuan_ids = array();
        foreach ($list as $k => $val) {
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        }
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('tuans', D('Pindan')->itemsByIds($tuan_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function detail($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D('Pindanorder')->find($order_id))){
            $this->error('该订单不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的订单');
        }
        if(!($dianping = D('Pindandianping')->where(array('order_id' => $order_id, 'user_id' => $this->uid))->find())) {
            $detail['dianping'] = 0;
        }else{
            $detail['dianping'] = 1;
        }
        $this->assign('tuans', D('Tuan')->find($detail['tuan_id']));
        $this->assign('detail', $detail);
        $this->display();
    }
	
	
	
    public function delete($order_id){
		$order_id = I('order_id', 0, 'trim,intval');
        $obj = D('Pindanorder');
        if(!($detail = D('Tuanorder')->find($order_id))){
            $this->tuMsg('商品不存在', U('tuan/index'));
        }
	    if($detail['status'] == -1) {
			$Tuancode = D('Pindancode');
			$tuan_code_is_used = $Tuancode->where(array('order_id' => $order_id,'status'=>0,'is_used'=>1))->select();
			$maps['order_id'] = array('eq',$order_id);
			$maps['status'] = array('gt',0);
			$tuan_code_status = $Tuancode->where($maps)->select();
			if(!empty($tuan_code_is_used)){
				$this->tuMsg('已有商品劵验证不能取消订单');
			}elseif(!empty($tuan_code_status)){
				$this->tuMsg('已有商品劵申请退款不行执行此操作');
			}else{
				$tuan_code = $Tuancode->where(array('order_id' => $order_id,'status'=>0,'is_used'=>0))->select();
				foreach($tuan_code as $k => $value){
					$Tuancode->save(array('code_id' => $value['code_id'], 'closed' => 1));
				}	
				$obj->save(array('order_id' => $order_id, 'closed' => 1));
				D('Users')->addIntegral($detail['user_id'], $detail['use_integral'], '取消商品订单' . $detail['order_id'] . '积分退还');//返积分
				$this->tuMsg('取消订单成功!', U('Pindan/index'));
			}
        }elseif($detail['status'] != 0){
			$this->tuMsg('状态不正确', U('Pindan/index'));
		}elseif($detial['closed'] == 1){
			$this->tuMsg('商品已关闭', U('Pindan/index'));
		}elseif($detail['user_id'] != $this->uid){
			$this->tuMsg('不能操作别人的商品', U('Pindan/index'));
		}else{
			 if($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
				D('Users')->addIntegral($detail['user_id'], $detail['use_integral'], '取消商品订单' . $detail['order_id'] . '积分退还');//返积分
				$this->tuMsg('取消订单成功!', U('Pindan/index'));
			 }else{
				$this->tuMsg('操作失败');
			 }
	    }
        
    }



    public function dianping($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D("Pindanorder")->find($order_id))){
            $this->error("该订单不存在");
        }
        if($detail['user_id'] != $this->uid){
            $this->error("请不要操作他人的订单");
        }
        if($detail['is_dianping'] != 0){
            $this->error("您已经点评过了");
        }
        if($this->isPost()){
            $data = $this->checkFields($this->_post("data", FALSE), array("score", "cost", "contents"));
            $data['user_id'] = $this->uid;
            $data['order_id'] = $detail['order_id'];
            $data['shop_id'] = $detail['shop_id'];
            $data['tuan_id'] = $detail['tuan_id'];
            $data['score'] = (int) $data['score'];
            if ($data['score'] <= 0 || 5 < $data['score']) {
                $this->tuMsg("请选择评分");
            }
            $data['cost'] = (int) $data['cost'];
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg("不说点什么么");
            }
            $data['create_time'] = NOW_TIME;
            $data_tuan_dianping = $this->_CONFIG['mobile']['data_tuan_dianping'];
            $data['show_date'] = date('Y-m-d', NOW_TIME + $data_tuan_dianping * 86400);
            $data['create_ip'] = get_client_ip();
            $obj = D("Pindandianping");
            if ($dianping_id = $obj->add($data)) {
                $photos = $this->_post("photos", FALSE);
                $local = array();
                foreach ($photos as $val) {
                    if (isimage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D("Pindandianpingpics")->upload($order_id, $local);
                }
                D("Pindanorder")->save(array("order_id" => $order_id, "is_dianping" => 1));
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                D("Users")->updateCount($this->uid, "ping_num");
                D("Users")->prestige($this->uid, "dianping");
                $this->tuMsg("评价成功", U("pindan/index"));
            }
            $this->tuMsg("操作失败");
        } else {
            $this->assign("detail", $detail);
            $tuan = D("Pindan")->find($detail['tuan_id']);
            $this->assign("tuan", $tuan);
            $this->display();
        }
    }

    //分销商品售后部分     -------新增部分
    public function refund()
    {
        if($this->isPost()) {
            $data = $this->createCheck();
            $order_goods = D('Pindanorder')->where(array('id' => $data['order_goods_id']))->find();

            if (!$order_goods) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'没有查到可退款的商品'));
            }
            if ($order_goods['status'] == 3) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经提交退款申请'));
            }
            if ($order_goods['status'] == 4) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经退款成功，请勿重复提交'));
            }
            $reason = M('PindanRefundReason')->where(array('reason_id' => $data['reason_id'], 'condition' => array('like', '%' . $data['received'] . '%')))->find();

            if (!$reason) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
            }
            // print_r($data);die;
            $arr['type'] = $data['type'];
            $arr['user_id'] = $this->uid;
            $arr['shop_id'] = $order_goods['shop_id'];
            $arr['money'] = $order_goods['need_pay'];
            $arr['order_id'] = $order_goods['order_id'];
            $arr['order_goods_id'] = $order_goods['id'];
            $arr['num'] = $data['num'];
            $arr['order_type'] = 'pindan';
            $arr['reason_id'] = $reason['reason_id'];
            $arr['reason_name'] = $reason['reason_name'];
            $arr['remark'] = $data['remark'];
            $arr['photo'] = $data['photo'];
            $arr['create_time'] = NOW_TIME;
            // print_r($arr);die;
            if (M('PindanRefund')->add($arr)) {
                D('PindanOrder')->where(array('id' => $order_goods['id']))->save(array('status' => 3)); //更新为已申请退款
                $this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('pindan/index')));
            } else {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请失败，请稍后重试'));
            }
        } else {
            $order_goods_id = I('get.order_id');
            $order_goods = D('Pindanorder')->where(array('order_id' => $order_goods_id))->find();
            if (!$order_goods) {
                $this->error('订单商品不存在');
            }
            if ($order_goods['status'] == 2) {
                $this->error('订单商品已经申请过售后,不能重复申请');
            }
            $goods = D('Pindan')->where(array('tuan_id' => $order_goods['tuan_id']))->find();
            $order_goods['title'] = $goods['title'];
            $order_goods['photo'] = $goods['photo'];
            $this->assign('order_goods', $order_goods);
            $this->display();
        }
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), array('type', 'received', 'reason_id', 'money', 'remark', 'order_id', 'order_goods_id', 'num','photo'));
        // print_r($data);die;
        if ($data['received'] < 0) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'货物状态错误'));
        }
        if ($data['reason_id'] < 1) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
        }
        return $data;
    }

    public function refund_reason()
    {
        $received = I('post.received');
        $reason = M('PindanRefundReason')->where(array('condition' => array('like', '%' . $received . '%')))->select();
        $html = '<option value="">请选择</option>';
        if ($reason) {
            foreach ($reason as $val) {
                $html .= '<option value="' . $val['reason_id'] . '">'. $val['reason_name'] .'</option>';
            }
        }
        echo $html;
    }

    //新增分销商品确认收货       ------新增部分
    public function queren($order_id)
    {
        if(empty($this->uid)) {
            $this->tuMsg(array('status' => 'login'));
        }
        if(!($detail = D('Pindanorder')->find($order_id))){
            $this->tuMsg('没有该订单'.$order_id);
        }
        if($detail['closed'] != 0){
            $this->tuMsg('该订单已经被删除');
        }
        if ($detail['status'] != 2 ) {
            $this->tuMsg('订单暂未发货');
        }
        if(false != D('Pindanorder')->where(['order_id'=>$order_id])->save(['status'=>5])){
            $this->tuMsg('确认成功',U('pindan/index',array('aready'=>1)));
        }else{
            $this->tuMsg('确认失败');   
        }
    }

    //新增分销商品确认完成      -------新增部分
    public function queren_all($order_id)
    {
        if(empty($this->uid)) {
            $this->tuMsg(array('status' => 'login'));
        }
        if(!($detail = D('Pindanorder')->find($order_id))){
            $this->tuMsg('没有该订单'.$order_id);
        }
        if($detail['closed'] != 0){
            $this->tuMsg('该订单已经被删除');
        }
        if ($detail['status'] != 5 ) {
            $this->tuMsg('订单暂未确认收货');
        }
        if(false != D('Pindanorder')->where(['order_id'=>$order_id])->save(['status'=>8])){
            $this->tuMsg('确认成功',U('pindan/index',array('aready'=>1)));
        }else{
            $this->tuMsg('确认失败');   
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Pindancomplaint')->where(array('order_id'=>$order_id))->find()){
            $this->error('您已经投诉过了！');
        }
        $shop=D("Pindanorder")->where(array("order_id" => $order_id))->find();
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

            //var_dump($tsdata);

            $ts= D('Pindancomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('pindan/index')));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("sj", $shop);
        $this->display();
    }


}