<?php
class GoodsAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        if ($this->_CONFIG['operation']['mall'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
    }
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
        // 输出模板
    }
    public function goodsloaddata(){
        $Order = D('Order');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'user_id' => $this->uid);
		
		
		$aready = I('aready', '', 'trim,intval');
		if($aready == 999){
			$map['status'] = array('in',array(0,1,2,3,4,5,6,7,8));
		}elseif($aready == 0 || $aready == ''){
			$map['status'] = 0;
		}else{
			$map['status'] = $aready;
		}
		$this->assign('aready', $aready);
        $count = $Order->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Order->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
			if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>0,'closed'=>0))->find()){
				$comment = D('DeliveryComment')->where(array('order_id'=>$delivery_order['order_id'],'type'=>0,'delivery_id'=>$delivery_order['delivery_id']))->find();//配送订单点评
                $list[$key]['delivery_order'] = $delivery_order;
			    $list[$key]['comment'] = $comment;
            }
        }
        if (!empty($order_ids)) {
            $goods = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = $shop_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('goods', $goods);
            $this->assign('products', D('Goods')->itemsByIds($goods_ids));
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('types', D('Order')->getType());
        $this->assign('goodtypes', D('Order')->getType());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
    public function detail($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D('Order')->find($order_id))){
            $this->error('该订单不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的订单');
        }
        $order_goods = D('Ordergoods')->where(array('order_id' => $order_id))->select();
        $goods_ids = array();
        foreach ($order_goods as $k => $val){
            $goods_ids[$val['goods_id']] = $val['goods_id'];
        }
        if(!empty($goods_ids)){
            $this->assign('goods', D('Goods')->itemsByIds($goods_ids));
        }
		$this->assign('data', $data = D('Logistics')->get_order_express($order_id));//查询清单物流
        $this->assign('ordergoods', $order_goods);
        $this->assign('types', D('Order')->getType());
        $this->assign('goodtypes', D('Ordergoods')->getType());
		
		$detail['shop'] = D('Shop')->where(array('shop_id' => $detail['shop_id']))->find();
		$detail['delivery_order'] = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type'=>0,'closed'=>0))->find();
        $this->assign('detail', $detail);
        $this->display();
    }
	public function queren($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Order');
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
                D('Order')->overOrder($order_id); //确认到账入口
                $this->tuMsg($into, U('goods/index', array('aready' => 8)));
            }else{
				$this->tuMsg('操作失败');
			}
        } else {
            $this->tuMsg('请选择要确认收货的订单');
        }
    }
    //取消订单重做
    public function orderdel($order_id = 0){
        if ($order_id = (int) $order_id) {
            $obj = D('Order');
            if (!($detail = $obj->find($order_id))) {
                $this->tuMsg('该订单不存在');
            }
            if ($detail['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }
			//检测配送状态
			$shop = D('Shop')->find($detail['shop_id']);
            if ($shop['is_ele_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->find();
                if ($DeliveryOrder['status'] == 2 || $DeliveryOrder['status'] == 8) {
                    $this->tuMsg('配送员都接单了无法取消订单');
                }else{
					D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->setField('closed', 1);//没接单就关闭配送
				}
            }
			if ($detail['is_daofu'] == 1) {
				$into = '到付订单取消成功';
            }else{
				$into = '订单取消成功';
				if ($detail['status'] != 0) {
					$this->tuMsg('该订单暂时不能取消');
				}
			}
            if ($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
				$obj-> del_order_goods_closed($order_id);//更新状态
				$obj-> del_goods_num($order_id);//取消后加库存
                if ($detail['use_integral']) {
                    D('Users')->addIntegral($detail['user_id'], $detail['can_use_integral'], '取消商城购物，订单号：' . $detail['order_id'] . '积分退还');
                }
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 11);
			    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 11);
				$this->tuMsg($into, U('goods/index', array('aready' => 1)));
            }else{
				$this->tuMsg('操作失败');
			}
        } else {
            $this->tuMsg('请选择要取消的订单');
        }
    }
	
	
    //重写退款流程
    /**
     * 商城退款申请@pingdan
     * @param  integer $order_goods_id [description]
     * @return [type]                  [description]
     */
    public function refund() {
        if($this->isPost()) {
            $data = $this->createCheck();
            $order_goods = D('OrderGoods')->where(array('id' => $data['order_goods_id']))->find();

            if (!$order_goods) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'没有查到可退款的商品'));
            }
            if ($order_goods['status'] == 2) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经提交退款申请'));
            }
            if ($order_goods['status'] == 3) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'当前商品已经退款成功，请勿重复提交'));
            }
            if ($data['num'] > $order_goods['num']) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请数量错误'));
            }
            $refund_max_money = D('Ordergoods')->getRefundMaxMoney($order_goods['id'], $data['num']); //根据申请数量计算最大退款金额
            if ($data['money'] > $refund_max_money) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请金额超出可申请的金额'));
            }

            $reason = D('RefundReason')->where(array('reason_id' => $data['reason_id'], 'condition' => array('like', '%' . $data['received'] . '%')))->find();

            if (!$reason) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
            }
            // print_r($data);die;
            $arr['type'] = $data['type'];
            $arr['user_id'] = $this->uid;
            $arr['money'] = $data['money'];
            $arr['shop_id']=$data['shop'];
            $arr['order_id'] = $order_goods['order_id'];
            $arr['order_goods_id'] = $order_goods['id'];
            $arr['num'] = $data['num'];
            $arr['order_type'] = 'goods';
            $arr['reason_id'] = $reason['reason_id'];
            $arr['reason_name'] = $reason['reason_name'];
            $arr['remark'] = $data['remark'];
            $arr['photo'] = $data['photo'];
            $arr['create_time'] = NOW_TIME;
            if (D('Refund')->add($arr)) {
                D('OrderGoods')->where(array('id' => $order_goods['id']))->save(array('status' => 2,'is_no'=>$data['num'])); //更新为已申请退款
                $this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('goods/index')));
            } else {
                $this->ajaxReturn(array('code'=>'0','msg'=>'申请失败，请稍后重试'));
            }
        } else {
            $order_goods_id = I('get.order_goods_id');
            $order_goods = D('OrderGoods')->where(array('id' => $order_goods_id))->find();
            if (!$order_goods) {
                $this->error('订单商品不存在');
            }

            if ($order_goods['status'] == 2) {
                $this->error('订单商品已经申请过售后,不能重复申请');
            }
            $goods = D('Goods')->where(array('goods_id' => $order_goods['goods_id']))->find();
            $order_goods['title'] = $goods['title'];
            $order_goods['photo'] = $goods['photo'];
            $this->assign('order_goods', $order_goods);
            $this->display();
        }
    }

    //售后商品
    public function refund_sale()
    {
       $refund = D('Refund');
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
                $order_goods = D('Ordergoods')->where(array('id' => $value['order_goods_id']))->find();
                // $order_goods = D('Ordergoods')->where(' ')->find();  
                if (!$order_goods) {
                    unset($list[$key]);
                }
                $shop = D('Shop')->where(array('shop_id' => $order_goods['shop_id']))->find();
                if ($shop) {
                    $list[$key]['shop_name'] = $shop['shop_name'];
                    $goods = D('Goods')->where(array('goods_id' => $order_goods['goods_id']))->find();
                    $order_goods['title'] = $goods['title'];
                    $order_goods['photo'] = $goods['photo'];
                }
                $refund_type = D('Refund')->getRefundType();
                $refund_status = D('Refund')->getRefundStatus();
                $list[$key]['type_text'] = $refund_type[$value['type']];
                $list[$key]['status_text'] = $refund_status[$value['status']];
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('order_goods', $order_goods);
        $this->display();
    }

    //售后商品确认收货
    public function refund_sale_q($refund_id)
    {
        if(empty($refund_id)){
            $this->error('参数错误');
        }
        if(false == ($refund = D('Refund')->find($refund_id))){
            $this->error('未找到该订单');
        }
        if($refund['user_id'] != $this->uid){
            $this->error('无权限操作此订单');
        }
        if(false !==(D('Refund')->where(['refund_id'=>$refund_id])->save(['status'=>8]))){
            $this->tuMsg('确认成功',U('goods/refund_sale'));
        }else{
            $this->error('确认失败，请联系商家');
        }
    }
    /**
     * 获取最大可退金额@pingdan
     * @return [type] [description]
     */
    public function refund_max_money() {
        $order_goods_id = I('post.order_goods_id');
        $num = I('post.num');

        $refund_max_money = D('Ordergoods')->getRefundMaxMoney($order_goods_id, $num);
        echo $refund_max_money;
    }

    /**
     * 售后单列表@pingdan
     * @return [type] [description]
     */
    public function refund_order() {
        $this->display();
    }

    public function refundloaddata() {
        $refund = D('Refund');
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
                $order_goods = D('Ordergoods')->where(array('order_goods_id' => $value['order_goods_id']))->find();
                if (!$order_goods) {
                    unset($list[$key]);
                }
                $shop = D('Shop')->where(array('shop_id' => $order_goods['shop_id']))->find();
                if ($shop) {
                    $list[$key]['shop_name'] = $shop['shop_name'];
                    $goods = D('Goods')->where(array('goods_id' => $order_goods['goods_id']))->find();
                    $order_goods['title'] = $goods['title'];
                    $order_goods['photo'] = $goods['photo'];
                }
                $refund_type = D('Refund')->getRefundType();
                $refund_status = D('Refund')->getRefundStatus();
                $list[$key]['type_text'] = $refund_type[$value['type']];
                $list[$key]['status_text'] = $refund_status[$value['status']];
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('order_goods', $order_goods);
        $this->display();
    }

    /**
     * 售后详情@pingdan
     * @return [type] [description]
     */
    public function refund_detail($refund_id) {
        // $refund_id = I('refund_id');
        if ($refund_id < 1) {
            $this->error('售后ID参数错误');
        }
        $refund = D('Refund')->where(array('refund_id' => $refund_id))->find();
        if (!$refund) {
            $this->error('售后单不存在');
        }
        $order_goods = D('Ordergoods')->where(array('id' => $refund['order_goods_id']))->find();
        $shop = D('Shop')->where(array('shop_id' => $order_goods['shop_id']))->find();
        $goods = D('Goods')->where(array('goods_id' => $order_goods['goods_id']))->find();
        if ($shop) {
            $order_goods['shop_name'] = $shop['shop_name'];
        }
        if ($goods) {
            $order_goods['title'] = $goods['title'];
            $order_goods['photo'] = $goods['photo'];
        }
        $refund_type = D('Refund')->getRefundType();
        $refund_status = D('Refund')->getRefundStatus();
        $refund['type_text'] = $refund_type[$refund['type']];
        $refund['status_text'] = $refund_status[$refund['status']];
        $this->assign('refund', $refund);
        $this->assign('order_goods', $order_goods);
        $this->display();
    }
    /**
     * 用户录入快递单号@pingdan
     * @return [type] [description]
     */
    public function input_express() {
        if ($this->isPost()) {
            $refund = D('Refund')->where(array('refund_id' => I('post.refund_id')))->find();
            if (!$refund) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'售后单不存在'));
            }
            $data['express_cp_user'] = I('post.express_cp');
            $data['express_no_user'] = I('post.express_no');
            $data['status'] = 4; //买家已发货状态
            if ($data['express_cp_user'] < 1 || $data['express_no_user'] < 1) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请输入完整的快递信息'));
            }
            $result = D('Refund')->where(array('refund_id' => $refund['refund_id']))->save($data);
            if (!$result) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'快递单录入失败,请稍后重试'));
            }
            $this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('goods/refund_detail', array('refund_id' => $refund['refund_id']))));
        } else {
            $refund_id = I('get.refund_id');
            $refund = D('Refund')->where(array('refund_id' => $refund_id))->find();

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

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), array('type', 'received', 'reason_id', 'money', 'remark', 'order_id', 'order_goods_id', 'num','photo','shop'));

        //print_r($data);die;
        if ($data['received'] < 0) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'货物状态错误'));
        }
        if ($data['reason_id'] < 1) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请原因错误'));
        }
        if ($data['num'] < 1) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请数量错误'));
        }
        if (($data['money']) <= 0) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'退款金额错误'));
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
    public function dianping($order_id){
        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D("Order")->find($order_id))) {
            $this->error("该订单不存在");
        }
        if ($detail['user_id'] != $this->uid) {
            $this->tuMsg("请不要操作他人的订单");
        }
        if ($detail['is_dianping'] != 0) {
            $this->tuMsg("您已经点评过了");
        }
        $goodss = D('Ordergoods')->where('order_id =' . $detail['order_id'])->find();
        $goods_id = $goodss['goods_id'];
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post("data", FALSE), array("score", "cost", "contents"));
            $data['user_id'] = $this->uid;
            $data['order_id'] = $detail['order_id'];
            $data['shop_id'] = $detail['shop_id'];
            $data['goods_id'] = $goods_id;
            $data['score'] = (int) $data['score'];
            if ($data['score'] <= 0 || 5 < $data['score']) {
                $this->tuMsg("请选择评分");
            }
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg("不说点什么么");
            }
            $data['create_time'] = NOW_TIME;
            $data_mall_dianping = $this->_CONFIG['mobile']['data_mall_dianping'];
            $data['show_date'] = date('Y-m-d', NOW_TIME + $data_mall_dianping * 86400);
            $data['create_ip'] = get_client_ip();
            $obj = D("Goodsdianping");
            if ($dianping_id = $obj->add($data)) {
                $photos = $this->_post("photos", FALSE);
                $local = array();
                foreach ($photos as $val) {
                    if (isimage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D("Goodsdianpingpics")->upload($order_id, $local, $goods_id);
                }
                D("Order")->save(array("order_id" => $order_id, "is_dianping" => 1));
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                D("Users")->updateCount($this->uid, "ping_num");
                D("Users")->prestige($this->uid, "dianping");
                $this->tuMsg("评价成功", U("user/goods/index/"));
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
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid,'type'=>9))->find()){
            $this->error('已经投诉过了！');
        }

        $shop=D("Order")->where(array("order_id" => $order_id))->find();
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
            $data['type']=9;
            $ts= D('Shopcomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('goods/dianping',array('order_id' => $order_id))));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("shop", $shop);
        $this->display();
    }
}