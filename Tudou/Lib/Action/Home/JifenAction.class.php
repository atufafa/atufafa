<?php
class JifenAction extends CommonAction{
    protected function _initialize(){
        parent::_initialize();
        $jifen = (int) $this->_CONFIG['operation']['jifen'];
        if ($jifen == 0) {
            $this->error('此功能已关闭');
            die;
        }
    }
    public function main(){
        $this->display();
    }
    public function index(){
        $Integralgoods = D('Integralgoods');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'audit' => 1);
        $count = $Integralgoods->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 'ex':
                $orderby = array('exchange_num' => 'desc');
                break;
            case 'j':
                $orderby = array('integral' => 'asc');
                break;
            default:
                $orderby = array('orderby' => 'asc');
                break;
        }
        $this->assign('order', $order);
        $list = $Integralgoods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function shop($shop_id){
        $shop_id = (int) $shop_id;
        if (!($detail = D('Shop')->find($shop_id))) {
            $this->error('该联盟商家不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('该联盟商家不存在');
        }
        $this->assign('shopdetail', D('Shopdetails')->find($shop_id));
        $this->seodatas['shop_name'] = $detail['shop_name'];
        $this->assign('detail', $detail);
        $this->display();
    }
    public function detail($goods_id){
        //  user_addr
        $goods_id = (int) $goods_id;
        if (!($detail = D('Integralgoods')->find($goods_id))) {
            $this->error('该积分商品不存在或者已经下架');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('该积分商品不存在或者已经下架');
        }
        $this->assign('shop', D('Shop')->find($detail['shop_id']));
        $sd = D('ShopDetails');
        $rsd = $sd->where(array('shop_id' => $detail['shop_id']))->find();
        $this->assign('rsd', $rsd);
        $this->assign('detail', $detail);
        
        $this->seodatas['title'] = $detail['title'];
        $this->seodatas['price'] = $detail['price'];
        $this->seodatas['num'] = $detail['num'];
        if (!empty($detail['details'])) {
            $this->seodatas['details'] = tu_msubstr($detail['details'], 0, 200, false);
        } else {
            $this->seodatas['details'] = $detail['title'];
        }
        $useraddr = D('Useraddr')->where(array('user_id' => $this->uid))->limit(0, 5)->select();
        $this->assign('useraddr', $useraddr);
        $this->display();
    }
    public function exchange($goods_id){
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $goods_id = (int) $goods_id;
        if (!($detail = D('Integralgoods')->find($goods_id))) {
            $this->tuError('该积分商品不存在或者已经下架');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->tuError('该积分商品不存在或者已经下架');
        }
		
		$user_exchange = D('Integralexchange')->where(array('user_id'=>$this->uid,'goods_id'=>$goods_id))->count();
			if ($detail['limit_num'] < $user_exchange) {
                $this->tuError('此商品每人限制兑换'.$detail['limit_num'].'份');
        }
			
        if ($this->isPost()) {
            if ($detail['num'] <= 0) {
                $this->tuError('该商品已经兑换完了');
            }
            $addr_id = (int) $this->_post('addr_id');
            if (empty($addr_id)) {
                $data = $this->checkFields($this->_post('data', false), array('addr_id', 'area_id', 'business_id', 'name', 'mobile', 'addr'));
                $data['name'] = htmlspecialchars($data['name']);
                if (empty($data['name'])) {
                    $this->tuError('收货人不能为空');
                }
                $data['user_id'] = (int) $this->uid;
                $data['area_id'] = (int) $data['area_id'];
                $data['business_id'] = (int) $data['business_id'];
                if (empty($data['area_id'])) {
                    $this->tuError('地区不能为空');
                }
                if (empty($data['business_id'])) {
                    $this->tuError('商圈不能为空');
                }
                $data['mobile'] = htmlspecialchars($data['mobile']);
                if (empty($data['mobile'])) {
                    $this->tuError('手机号码不能为空');
                }
                if (!isMobile($data['mobile'])) {
                    $this->tuError('手机号码格式不正确');
                }
                $data['addr'] = htmlspecialchars($data['addr']);
                if (empty($data['addr'])) {
                    $this->tuError('具体地址不能为空');
                }
            } else {
                if (!($addr = D('Useraddr')->find($addr_id))) {
                    $this->tuError('请选择收货地址');
                }
                if ($addr['user_id'] != $this->uid) {
                    $this->tuError('请选择收货地址');
                }
            }
            $code = I('code','','trim,htmlspecialchars');
            if(!$code){
                $this->error('请选择支付方式');
            }

            $member = D('Users')->find($this->uid);
            if ($member['integral'] < $detail['integral']) {
                $this->tuError('您的积分不足！该商品您兑换不了');
            }

            if($code=='money'){
                if($member['money']<$detail['now_price']){
                    $this->error('您的余额不足');
                }
            }

            $arr = array(
                'type' => 'exchange',
                'user_id' => $this->uid,
                'goods_id'=>$detail['goods_id'],
                'code' => $code,
                'need_pay' => $detail['now_price'],
                'exchange'=>$detail['integral'],
                'create_time' => time(),
                'create_ip' => get_client_ip(),
                'is_paid'=>0
            );
            if($log_id = D('Paymentlogs')->add($arr)){
                $this->success('正在为您跳转支付',U('wap/payment/payment', array('log_id' =>$log_id)));
            }else{
                $this->error(array('code'=>'0','msg'=>'操作失败'));
            }

        } else {
            $useraddr = D('Useraddr')->where(array('user_id' => $this->uid))->limit(0, 5)->select();
            $this->assign('useraddr', $useraddr);
            $this->assign('detail', $detail);
            $this->display();
        }
    }
}