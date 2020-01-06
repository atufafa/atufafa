<?php
class TuanAction extends CommonAction{
	
	protected function _initialize(){
       parent::_initialize();
	   if(empty($this->_CONFIG['operation']['tuan'])) {
            $this->error('抢购功能已关闭');
            die;
        }
    }
	
	
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }
	
	public function orderloading(){
        $obj = D('Tuanorder');
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
        $this->assign('tuans', D('Tuan')->itemsByIds($tuan_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
   
    public function detail($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D('Tuanorder')->find($order_id))){
            $this->error('该订单不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的订单');
        }
        if(!($dianping = D('Tuandianping')->where(array('order_id' => $order_id, 'user_id' => $this->uid))->find())) {
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
        $obj = D('Tuanorder');
        if(!($detail = D('Tuanorder')->find($order_id))){
            $this->tuMsg('抢购不存在', U('tuan/index'));
        }
	    if($detail['status'] == -1) {
			$Tuancode = D('Tuancode');
			$tuan_code_is_used = $Tuancode->where(array('order_id' => $order_id,'status'=>0,'is_used'=>1))->select();
			$maps['order_id'] = array('eq',$order_id);
			$maps['status'] = array('gt',0);
			$tuan_code_status = $Tuancode->where($maps)->select();
			if(!empty($tuan_code_is_used)){
				$this->tuMsg('已有抢购劵验证不能取消订单');
			}elseif(!empty($tuan_code_status)){
				$this->tuMsg('已有抢购劵申请退款不行执行此操作');
			}else{
				$tuan_code = $Tuancode->where(array('order_id' => $order_id,'status'=>0,'is_used'=>0))->select();
				foreach($tuan_code as $k => $value){
					$Tuancode->save(array('code_id' => $value['code_id'], 'closed' => 1));
				}	
				$obj->save(array('order_id' => $order_id, 'closed' => 1));
				D('Users')->addIntegral($detail['user_id'], $detail['use_integral'], '取消抢购订单' . $detail['order_id'] . '积分退还');//返积分
				$this->tuMsg('取消订单成功!', U('tuan/index'));
			}
        }elseif($detail['status'] != 0){
			$this->tuMsg('状态不正确', U('tuan/index'));
		}elseif($detail['closed'] == 1){
			$this->tuMsg('抢购已关闭', U('tuan/index'));
		}elseif($detail['user_id'] != $this->uid){
			$this->tuMsg('不能操作别人的抢购', U('tuan/index'));
		}else{
			 if($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
				D('Users')->addIntegral($detail['user_id'], $detail['use_integral'], '取消抢购订单' . $detail['order_id'] . '积分退还');//返积分
				$this->tuMsg('取消订单成功!', U('tuan/index'));
			 }else{
				$this->tuMsg('操作失败');
			 }
	    }
        
    }



    public function dianping($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D("Tuanorder")->find($order_id))){
            $this->error("该订单不存在");
        }
        if(!($tc = D("Tuancode")->where(array("order_id" => $order_id, "is_used" => 1))->find())){
            $this->error("您的抢购券还没有使用");
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
            $obj = d("Tuandianping");

            if ($dianping_id = $obj->add($data)) {
                $photos = $this->_post("photos", FALSE);
                $local = array();
                foreach ($photos as $val) {
                    if (isimage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D("Tuandianpingpics")->upload($order_id, $local);
                }
                D("Tuanorder")->save(array("order_id" => $order_id, "is_dianping" => 1));
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                D("Users")->updateCount($this->uid, "ping_num");
                D("Users")->prestige($this->uid, "dianping");
                $this->tuMsg("评价成功", U("member/index"));
            }
            $this->tuMsg("操作失败");
        } else {
            $this->assign("detail", $detail);
            $tuan = D("Tuan")->find($detail['tuan_id']);
            $this->assign("tuan", $tuan);
            $this->display();
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Tuancomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid))->find()){
            $this->ajaxReturn(array('code'=>'0','msg'=>'已经投诉过了！'));
        }

        $shop=D("Tuanorder")->where(array("order_id" => $order_id))->find();
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

            $ts= D('Tuancomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('tuan/index')));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }

    //确认收货
    public function queren($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Tuanorder');
            if (!($detial = $obj->find($order_id))) {
                $this->tuMsg('该订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }
            //检测配送状态
            $shop = D('Shop')->find($detial['shop_id']);
            if ($shop['is_goods_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->find();
                if ($DeliveryOrder['status'] != 8) {
                    $this->tuMsg('配送员还未完成订单');
                }
            }
            if($detial['is_daofu'] == 1) {
                $into = '货到付款确认收货成功';
            }else{
                if ($detial['status'] != 2) {
                    $this->tuMsg('该订单暂时不能确定收货');
                }
                $into = '确认收货成功';
            }
            if ($obj->save(array('order_id' => $order_id, 'status' => 3))) {
                D('Tuanorder')->overOrder($order_id); //确认到账入口
                $this->tuMsg($into, U('zero/index', array('aready' => 8)));
            }else{
                $this->tuMsg('操作失败');
            }
        } else {
            $this->tuMsg('请选择要确认收货的订单');
        }
    }

    //申请退款
    public function refund(){
        if($this->isPost()) {
            $data = $this->createCheck();

            $order_goods = D('Tuanorder')->where(array('order_id' => $data['order_id']))->find();

            if (!$order_goods) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'没有查到可退款的商品'));
            }
            if ($order_goods['status'] == 3) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经提交退款申请'));
            }
            if ($order_goods['status'] == 4) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经退款成功，请勿重复提交'));
            }
            if ($data['num'] > $order_goods['num']) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请数量错误'));
            }
            $refund_max_money = D('Tuanorder')->getRefundMaxMoney($order_goods['order_id'], $data['num']); //根据申请数量计算最大退款金额

            if ($data['money'] > $refund_max_money) {
                // var_dump($data['money']);var_dump($refund_max_money);die;
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请金额超出可申请的金额'));
            }

            $reason = D('RefundReason')->where(array('reason_id' => $data['reason_id'], 'condition' => array('like', '%' . $data['received'] . '%')))->find();

            if (!$reason) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
            }
            //print_r($data);die;
            $arr['type'] = $data['type'];
            $arr['user_id'] = $this->uid;
            $arr['money'] = $data['money'];
            $arr['shop_id']=$order_goods['shop_id'];
            $arr['order_id'] = $order_goods['order_id'];
            $arr['goods_id'] = $order_goods['tuan_id'];
            $arr['num'] = $data['num'];
            $arr['order_type'] = 'tuan';
            $arr['reason_id'] = $reason['reason_id'];
            $arr['reason_name'] = $reason['reason_name'];
            $arr['remark'] = $data['remark'];
            $arr['photo'] = $data['photo'];
            $arr['create_time'] = NOW_TIME;
           // var_dump($arr);die;
            if (D('Tuanrefund')->add($arr)) {
                D('Tuanorder')->where(array('order_id' => $order_goods['order_id']))->save(array('status' => 3)); //更新为已申请退款
                $this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('tuan/index')));
            } else {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请失败，请稍后重试'));
            }
        } else {
            $order_goods_id = I('get.order_goods_id');
            $order_goods = D('Tuanorder')->where(array('order_id' => $order_goods_id))->find();
            if (!$order_goods) {
                $this->error('订单商品不存在');
            }

            if ($order_goods['status'] == 3) {
                $this->error('订单商品已经申请过售后,不能重复申请');
            }
            $goods = D('Tuan')->where(array('tuan_id' => $order_goods['tuan_id']))->find();
            $order_goods['title'] = $goods['title'];
            $order_goods['photo'] = $goods['photo'];
            if(empty($order_goods['key'])){
                $guige=D('Tuanspecprice')->where(array('goods_id'=>$order_goods['tuan_id'],'key'=>$order_goods['key']))->find();
                $order_goods['tuan_price'] = $guige['price'];
            }else{
                $order_goods['tuan_price'] =  $goods['tuan_price'];
            }

            $this->assign('order_goods', $order_goods);
            $this->display();
        }
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), array('type', 'received', 'reason_id', 'money', 'num','remark','photo','order_id','shop_id'));

        //print_r($data);die;

        if($data['type']<0){
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请类型错误'));
        }
        if ($data['received'] < 0) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'货物状态错误'));
        }
        if ($data['reason_id'] < 1) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
        }
        if (($data['money']) <= 0) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'退款金额错误'));
        }
        if(empty($data['remark'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'退款说明不能为空'));
        }

        return $data;
    }

    /**
     * 获取退款原因@pingdan
     * @return [type] [description]
     */
    public function refund_reason() {
        $received = I('post.received');
        $reason = D('RefundReason')->where(array('condition' => array('like', '%' . $received . '%')))->select();
        $html = '<option value="">请选择</option>';
        if ($reason) {
            foreach ($reason as $val) {
                $html .= '<option value="' . $val['reason_id'] . '">'. $val['reason_name'] .'</option>';
            }
        }
        echo $html;
    }

    /**
     * 获取最大可退金额@pingdan
     * @return [type] [description]
     */
    public function refund_max_money() {
        $order_goods_id = I('post.order_goods_id');
        //var_dump($order_goods_id);
        $num = I('post.num');

        $refund_max_money = D('Tuanorder')->getRefundMaxMoney($order_goods_id, $num);
        echo $refund_max_money;
    }

    //售后商品
    public function refund_sale()
    {
        $refund = D('Tuanrefund');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid);

        $count = $refund->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $refund->where($map)->order(array('status' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $order_goods = D('Tuanorder')->where(array('order_id' => $value['order_id']))->find();
                // $order_goods = D('Ordergoods')->where(' ')->find();
                if (!$order_goods) {
                    unset($list[$key]);
                }
                $shop = D('Shop')->where(array('shop_id' => $order_goods['shop_id']))->find();
                if ($shop) {
                    $list[$key]['shop_name'] = $shop['shop_name'];
                        $goods = D('Tuan')->where(array('tuan_id' => $order_goods['tuan_id']))->find();
                    $order_goods['title'] = $goods['title'];
                    $order_goods['photo'] = $goods['photo'];
                }
                $refund_type = D('Tuanrefund')->getRefundType();
                $refund_status = D('Tuanrefund')->getRefundStatus();
                $list[$key]['type_text'] = $refund_type[$value['type']];
                $list[$key]['status_text'] = $refund_status[$value['status']];
            }
        }
        $this->assign('list', $list);
        //var_dump($list);
        $this->assign('page', $show);
        $this->assign('order_goods', $order_goods);
        $this->display();
    }

    /**
     * 用户录入快递单号@pingdan
     * @return [type] [description]
     */
    public function input_express() {
        if ($this->isPost()) {

            $refund = D('Tuanrefund')->where(array('id' => I('post.id')))->find();

            if (!$refund) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'售后单不存在'));
            }
            $data['express_cp_user'] = I('post.express_cp');
            $data['express_no_user'] = I('post.express_no');
            $data['status'] = 4; //买家已发货状态
            if ($data['express_cp_user'] < 1 || $data['express_no_user'] < 1) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请输入完整的快递信息'));
            }
            $result = D('Tuanrefund')->where(array('id' => $refund['id']))->save($data);
            if (!$result) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'快递单录入失败,请稍后重试'));
            }
            $this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('tuan/index', array('id' => $refund['id']))));
        } else {
            $refund_id = I('get.id');
            $refund = D('Tuanrefund')->where(array('id' => $refund_id))->find();

            if (!$refund) {
                $this->error('售后单不存在');
            }
            if ($refund['type'] == 1 || $refund['status'] != 1) { //type=1类型为仅退款,status=3售后状态为卖家已同意，待买家退货
                $this->error('当前状态不允许录入快递单号');
            }
            $this->assign('refund', $refund);
            $this->display();
        }
    }

    //售后商品确认收货
    public function refund_sale_q($id)
    {

        if(empty($id)){
            $this->error('参数错误');
        }
        if(false == ($refund = D('Tuanrefund')->where(array('id'=>$id))->find())){
            $this->error('未找到该订单');
        }
        if($refund['user_id'] != $this->uid){
            $this->error('无权限操作此订单');
        }
        if(false !==(D('Tuanrefund')->where(['id'=>$id])->save(['status'=>8]))){
            $this->tuMsg('操作成功',U('tuan/index'));
        }else{
            $this->error('确认失败，请联系商家');
        }
    }




}