<?php
class EleAction extends CommonAction{
	
	 public function _initialize() {
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['ele'])) {
            $this->error('外卖功能已关闭');
            die;
        }
    }
	
	
    public function index(){
        $Eleorder = D('Eleorder');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        $count = $Eleorder->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Eleorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $result = D('Eledianping')->where(array('user_id' => $this->uid))->select();
        $orders = array();
        foreach ($result as $v) {
            $orders[] = $v['order_id'];
        }
        $user_ids = $order_ids = $addr_ids = $shops_ids = array();
        foreach ($list as $k => $val) {
            if (in_array($val['order_id'], $orders)) {
                $list[$k]['dianping'] = 1;
            } else {
                $list[$k]['dianping'] = 0;
            }
            if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>1,'closed'=>0))->find()){
                $comment = D('DeliveryComment')->where(array('order_id'=>$delivery_order['order_id'],'delivery_id'=>$delivery_order['delivery_id']))->find();//配送订单点评
                $list[$k]['delivery_order'] = $delivery_order;
                $list[$k]['comment'] = $comment;
            }
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
            $user_ids[$val['user_id']] = $val['user_id'];
            $shops_ids[$val['shop_id']] = $val['shop_id'];
        }
        if (!empty($shops_ids)) {
            $this->assign('shop_s', D('Shop')->itemsByIds($shops_ids));
        }
        if (!empty($order_ids)) {
            $products = D('Eleorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
            $product_ids = $shop_ids = array();
            foreach ($products as $val) {
                $product_ids[$val['product_id']] = $val['product_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('products', $products);
            $this->assign('eleproducts', D('Eleproduct')->itemsByIds($product_ids));
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('cfg', D('Eleorder')->getCfg());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function dianping($order_id){
        $order_id = (int) $order_id;
        if (!($detail = D('Eleorder')->find($order_id))) {
            $this->error('没有该订单');
        } else {
            if ($detail['user_id'] != $this->uid) {
                $this->error('不要评价别人的订餐订单');
                die;
            }
        }
        if (D('Eledianping')->check($order_id, $this->uid)) {
            $this->error('已经评价过了');
        }
        if ($this->_Post()) {
            $data = $this->checkFields($this->_post('data', false), array('score', 'speed', 'contents','cost'));
            $data['user_id'] = $this->uid;
            $data['shop_id'] = $detail['shop_id'];
            $data['order_id'] = $order_id;
            $data['score'] = (int) $data['score'];
            if (empty($data['score'])) {
                $this->tuError('评分不能为空');
            }
            if (empty($data['cost'])) {
                $this->tuError('平均消费不能为空');
            }
            if ($data['score'] > 5 || $data['score'] < 1) {
                $this->tuError('评分为1-5之间的数字');
            }
            $data['speed'] = (int) $data['speed'];
            if (empty($data['speed'])) {
                $this->tuError('送餐时间不能为空');
            }
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuError('评价内容不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->tuError('评价内容含有敏感词：' . $words);
            }
            $data['show_date'] = date('Y-m-d', NOW_TIME);
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if (D('Eledianping')->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D('Eledianpingpics')->upload($order_id, $local);
                }
                D("Users")->updateCount($this->uid, "ping_num");
                D("Eleorder")->updateCount($order_id, "is_dianping");
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                $this->tuSuccess('恭喜您点评成功!', U('ele/index'));
            }
            $this->tuError('点评失败');
        } else {
            $details = D('Shop')->find($detail['shop_id']);
            $this->assign('details', $details);
            $this->assign('order_id', $order_id);
            $this->display();
        }
    }
    //
    public function yes($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            if (!($detial = D('Eleorder')->find($order_id))) {
                $this->tuError('您确认收货的订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuError('请不要操作别人的订单');
            }
            $shop = D('Shop')->find($detial['shop_id']);
            if ($shop['is_pei'] == 0) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();
                if ($DeliveryOrder['status'] == 2) {
                    $this->tuError('配送员还未完成订单');
                }
            } else {
                //不走配送
                if ($detial['status'] != 2) {
                    $this->tuError('当前状态不能确认收货');
                }
            }
            $obj = D('Eleorder');
            $obj->overOrder($order_id);
            $obj->save(array('order_id' => $order_id, 'status' => 8,'end_time' => NOW_TIME));
            $this->tuSuccess('确认收货成功', U('ele/index'));
        } else {
            $this->tuError('请选择要确认收货的订单');
        }
    }
    public function elecancle($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $member = D('Users')->find($this->uid);
            if (!($detial = D('Eleorder')->find($order_id))) {
                $this->tuError('您取消的订单不存在');
            }
            $shop = D('Shop')->find($detial['shop_id']);
            if ($shop['is_pei'] != 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();
                if ($DeliveryOrder['status'] != 1) {
                    $this->tuMsg('亲，当前状态不能退款啦');
                }
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuError('请不要操作别人的订单');
            }
            if ($detial['is_pay'] == 0) {
                $this->tuError('当前状态不能退款');
            }
            if ($detial['status'] != 1) {
                $this->tuError('当前状态不能退款');
            }
            if (D('Eleorder')->save(array('order_id' => $order_id, 'status' => 3))) {
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 3);
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 3);
                $this->tuSuccess('申请成功！等待网站客服处理', U('ele/index'));
            }
        }
        $this->tuError('操作失败');
    }
    public function eleqxtk($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $member = D('Users')->find($this->uid);
            if (!($detial = D('Eleorder')->find($order_id))) {
                $this->tuError('您取消的订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuError('请不要操作别人的订单');
            }
            if ($detial['status'] != 3) {
                $this->tuError('当前状态不能退款');
            }
            if (D('Eleorder')->save(array('order_id' => $order_id, 'status' => 1))) {
                $this->tuSuccess('取消退款成功', U('ele/index'));
            }
        }
        $this->tuError('操作失败');
    }
    public function delete($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $member = D('Users')->find($this->uid);
            if (!($detial = D('Eleorder')->find($order_id))) {
                $this->tuError('您删除的订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuError('请不要操作别人的订单');
            }
            if ($detial['status'] != 0 && $detial['status'] != 8 && $detial['status'] != 4) {
                $this->tuError('当前状态不能删除');
            }
            if (D('Eleorder')->save(array('order_id' => $order_id, 'closed' => 1))) {
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 11);
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 11);
                $this->tuSuccess('删除成功', U('ele/index'));
            }
            $this->tuError('操作失败');
        }
    }
    //配送员点评
    public function delivery($order_id,$type = 0){
        if(!$order_id = (int) $order_id){
            $this->error('订单ID不存在');
        }
        if(!$res = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type'=>$type))->find()){
            $this->error('配送订单不存在或者该订单不是配送员配送');
        }
        if($res['status'] != 8){
            $this->error('该配送订单未完成');
        }
        if($res['closed'] != 0){
            $this->error('该配送订单已被删除');
        }
        if($dc = D('DeliveryComment')->where(array('order_id'=>$res['order_id'],'type_order_id'=>$res['type_order_id'],'user_id'=>$this->uid,'type'=>$type))->find()){
            $this->error('该配送订单您已经点评过了');
        }
        $this->assign('res', $res);
        $this->assign('delivery',D('Delivery')->find($res['delivery_id']));
        $this->assign('tags', D('DeliveryCommentTag')->order(array('orderby' => 'asc'))->where(array('closed' => '0'))->select());
        $this->display();
    }

    //点评
    public function remark($order_id,$type = 0){
        if($this->_Post()){
            $data = $this->checkFields($this->_post('data', false), array('score','d1','d2','d3','content'));
            if(!$res = D('DeliveryOrder')->where(array('order_id'=>$order_id))->find()){
                $this->ajaxReturn(array('code'=>'0','msg'=>'配送订单不存在或者该订单不是配送员配送'));
            }

            $data['user_id'] = $this->uid;
            $data['shop_id'] = $res['shop_id'] ? $res['shop_id'] : '1';
            $data['type_order_id'] = $res['type_order_id'];
            $data['delivery_id'] = $res['delivery_id'];
            $data['order_id'] = $order_id;
            $data['score'] = (int) $data['score'];
            if(empty($data['score'])){
                $this->tuError('综合评分不能为空');
            }
            if($data['score'] > 5 || $data['score'] < 1){
                $this->tuError('评分为1-5之间的数字');
            }
            $config = $config = D('Setting')->fetchAll();
            $data['d1'] = (int) $data['d1'];
            if(empty($data['d1'])){
                $this->tuError($config['appoint']['d1'].'评分不能为空');
            }
            if($data['d1'] > 5 || $data['d1'] < 1){
                $this->tuError($config['appoint']['d1'].'格式不正确');
            }
            $data['d2'] = (int) $data['d2'];
            if(empty($data['d2'])){
                $this->tuError($config['appoint']['d2'].'评分不能为空');
            }
            if($data['d2'] > 5 || $data['d2'] < 1){
                $this->tuError($config['appoint']['d2'].'格式不正确');
            }
            $data['d3'] = (int) $data['d3'];
            if(empty($data['d3'])){
                $this->tuError($config['appoint']['d3'].'评分不能为空');
            }
            if($data['d3'] > 5 || $data['d3'] < 1){
                $this->tuError($config['appoint']['d3'].'格式不正确');
            }
            $data['content'] = htmlspecialchars($data['content']);
            if(empty($data['content'])){
                $this->tuError('评价内容不能为空');
            }
            if($words = D('Sensitive')->checkWords($data['content'])){
                $this->tuError('评价内容含有敏感词：' . $words);
            }
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();

            $tag = $this->_post('tag', false);
            $tag = implode(',', $tag);
            $data['tag'] = $tag;
            // print_r($data);die;
            if($comment_id = D('DeliveryComment')->add($data)){
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val){
                    if(isImage($val))
                        $local[] = $val;
                }
                if(!empty($local)){
                    foreach($local as $k=>$val){
                        D('DeliveryCommentPics')->add(array('comment_id'=>$comment_id,'order_id'=>$order_id,'photo'=>$val));
                    }
                }
                $this->tuSuccess('恭喜您点评成功', U('ele/index'));
            }
            $this->tuError('点评失败');
        }
    }
}