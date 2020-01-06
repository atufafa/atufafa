<?php
class IntegralorderAction extends CommonAction{
    public function _initialize() {
        parent::_initialize();
        $this->assign('logistics', $logistics = D('Logistics')->where(array('closed'=>0))->select());
        $this->assign('types',D('Integralexchange')->getType());
    }

    //全部订单
    public function whole_order(){
        $this->status = array('IN',array(0,1,2,3,4,5,6,7,8));
        $this->is_daofu = array('IN',array(0,1));
        $this->showdatas();
        $this->display();
    }

    //剩下的控制器
    public function showdatas() {
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'status' => $this->status , 'is_daofu' => $this->is_daofu ,'shop_id'=> $this->shop_id );
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars') ) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
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
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        if (isset($_GET['profit']) || isset($_POST['profit'])) {
            $profit = (int) $this->_param('profit');
            if ($profit != 999) {
                $map['is_profit'] = $profit;
            }
            $this->assign('profit', $profit);
        } else {
            $this->assign('profit', 999);
        }
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
        }
        $this->assign('keyword', $keyword);
        $Order = D('Integralorder');
        $count = $Order->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Order->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $address_ids[$val['address_id']] = $val['address_id'];
        }
        // dump($address_ids);
        if (!empty($order_ids)) {
            $goods = D('Integralordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
            }
            $this->assign('goods', $goods);
            $this->assign('products', D('Integralgoodslist')->itemsByIds($goods_ids));
        }
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Paddress')->itemsByIds($address_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('types', D('Integralorder')->getType());
        $this->assign('goodtypes', D('Integralordergoods')->getType());
        $this->assign('types', D('Integralorder')->getType());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('picks', session('order'));

    }

    //详细信息
    public function detail($order_id){

        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D('Integralorder')->find($order_id))) {
            $this->error('该订单不存在');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要操作其他商家的订单');
        }
        $order_goods = D('Integralordergoods')->where(array('order_id' => $order_id))->select();
        $goods_ids = array();
        foreach ($order_goods as $k => $val) {
            $goods_ids[$val['goods_id']] = $val['goods_id'];
        }
        if (!empty($goods_ids)) {
            $this->assign('goods', D('Integralgoodslist')->itemsByIds($goods_ids));
        }
        $data = D('Logistics')->get_exchange_express($order_id);//查询清单物流
        $this->assign('data', $data);
        $this->assign('ordergoods', $order_goods);
        $this->assign('users', D('Users')->find($detail['user_id']));
        $this->assign('Paddress', D('Paddress')->find($detail['address_id']));
        $this->assign('logistics', D('Logistics')->find($detail['express_id']));
        $this->assign('types', D('Integralorder')->getType());
        $this->assign('goodtypes', D('Integralordergoods')->getType());
        $this->assign('detail', $detail);
        $this->display();

    }


    //待处理的订单
    public function confirm_order(){
        $this->status = 1;
        $this->showdatas();
        $this->display();
    }

    //发货
    public function express($order_id = 0){
        $data = $_POST;
        $order_id = $data['order_id'];
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        if (!($detail = D('Integralorder')->find($order_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有该订单'.$order_id));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单已经被删除'));
        }
        if ($detail['status'] == 2 || $detail['status'] == 3 || $detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 5) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单状态不正确，不能发货'));
        }
        $express_id = $data['express_id'];
        if (empty($express_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择快递'));
        }
        if (!($detail = D('Logistics')->find($express_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有'.$detail['express_name'].'快递'));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该快递已关闭'));
        }
        $express_number = $data['express_number'];
        if (empty($express_number)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '快递单号不能为空'));
        }
        $add_express = array(
            'order_id' => $order_id,
            'express_id' => $express_id,
            'express_number' => $express_number
        );
        if(D('Integralorder')->save($add_express)){
            D('Integralorder')->pc_express_deliver($order_id);//执行发货
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 2);
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 2);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '一键发货成功', 'url' => U('integralorder/confirm_order')));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '发货失败'));
        }
    }

    //已发货订单
    public function deliver_order(){
        $this->status = 2;
        $this->showdatas();
        $this->display();
    }

    //确认完成
    public function distribution(){
        $order_id = (int) $this->_get('order_id');
        $config = D('Setting')->fetchAll();
        $days = isset($config['site']['goods']) ? (int)$config['site']['goods'] : 15;
        $t = NOW_TIME - $days*86400;
        if (!$order_id) {
            $this->tuError('参数错误');
        }else if(!$order = D('Integralorder')->find($order_id)){
            $this->tuError('该订单不存在');
        }else if($order['shop_id'] != $this->shop_id){
            $this->tuError('不能管理不是您的订单');
        }else if(($order['status'] != 2) && ($order['status']!=3)){
            $this->tuError('该订单状态不正确，不能发货');
        }else{
            D('Integralorder')->overOrder($order_id); //发货订单接口
            $this->tuSuccess('确认订单完成，资金已结算', U('integralorder/complete_order'));
        }
        $this->tuError('确认订单失败');
    }

    //已完成的订单
    public function complete_order(){
        $this->status = 8;
        $this->showdatas();
        $this->display();
    }


    //只支持单个退款
    public function refund($order_id = 0){
        $order_id = (int) $order_id;
        $order = D('Integralorder');
        $detail = $order->find($order_id);
        if ($detail['is_daofu'] == 0) {
            if ($detail['status'] != 4) {
                $this->tuError('操作错误');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('请不要恶意操作其他人的订单');
            }
            if(false !== $order->implemented_refund($order_id)){
                $this->tuSuccess('退款成功', U('integralorder/aftersale_order'));
            }else{
                $this->tuError('退款失败');
            }
        } else {
            $this->tuError('当前订单状态不正确');
        }
    }

    //售后订单
    public function aftersale_order(){
        import('ORG.Util.Page');
        $Order = D('Integralrefund');
        $shop=D('Shop')->where(array('user_id'=>$this->uid))->find();
        $map=array('shop_id'=>$shop['shop_id']);
        $count = $Order->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Order->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('create_time desc')->select();
        $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $value) {
            $goods = D('Integralordergoods')->where(['goods_id'=>$value['goods_id']])->find();
            $order = D('Integralorder')->where(['goods_id'=>$value['goods_id']])->find();
            $order_ids[$value['exchange_id']] = $value['exchange_id'];
            $shop_ids[$order['shop_id']] = $order['shop_id'];
        }

        if (!empty($order_ids)) {
            $goods = D('Integralordergoods')->where(array('exchange_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
            }
            $this->assign('goods', $goods);
        }

        if (!empty($order_ids)) {
            $this->assign('orders', D('Integralorder')->itemsByIds($order_ids));
        }
        $this->assign('products', D('Integralgoodslist')->itemsByIds($goods_ids));

        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //同意退款
    public function que_refund($order_id,$type){
        //var_dump($order_id);die;
        // $this->tuError('该退款订'.$id.'单不存在'.$type);
        if(false == ($detail = D('Integralordergoods')->where(['order_id'=>$order_id])->find())){
            $this->tuError('该退款订单不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError['请勿操作其他商家的退款订单'];
        }
        if($type ==2){
            // echo D('Refund')->getlastSql();die;
            $refund_list = D('Integralrefund')->where(['order_id'=>$order_id])->find();
            if($refund_list['type'] != 1){
                if(false ==(!$da=D('Integralrefund')->where(['order_id'=>$order_id])->save(['status'=>1]))){
                    $this->tuSuccess('审核成功，请注意查看物流确认后再点击确认退款', U('integralorder/aftersale_order'));
                }else{
                    $this->tuError('审核失败1');
                }
            }else{
                if(false ==(!$da=D('Integralrefund')->where(['order_id'=>$order_id])->save(['status'=>5]))){
                    $this->tuSuccess('审核成功，订单修改为可打款', U('integralorder/aftersale_order'));
                }else{
                    $this->tuError('审核失败1');
                }
            }
        }else{
            if(D('Integralrefund')->where(['order_id'=>$order_id])->save(['status'=>2])){
                $this->tuSuccess('取消成功');
            }else{
                $this->tuError('审核失败2');
            }
        }
    }


    //换货
    public function refund_order($order_id){
        // print_r($order_id);die;
        if($this->isPost()){
            // $hotel_id = $this->check_hotel();
            // print_r($order_id);die;
            if($order_id = (int) $order_id){
                if(!$order = D('Integralrefund')->where(['order_id'=>$order_id])->find()){
                    $this->tuError('订单不存在');
                }elseif($order['type'] != 3){
                    // echo D('Refund')->getlastSql();die;
                    // print_r($order);die;
                    $this->tuError('该订单不支持，请联系管理员');
                }else{
                    if($order['shop_id'] != $this->shop_id){
                        $this->tuError('无权限操作此订单');
                    }
                    if(false !== D('Integralrefund')->where(['order_id'=>$order_id])->save(['express_no_shop'=>$_POST['express_no_shop'],'express_cp_shop'=>$_POST['express_cp_shop'],'status'=>6])){
                        $this->tuSuccess('订单操作成功',U('integralorder/refund_order'));
                    }else{
                        $this->tuError('订单操作失败');
                    }
                }
            }else{
                $this->tuError('请选择要确认的订单');
            }
        }else{
            $this->assign('detail',D('Integralrefund')->where(['order_id'=>$order_id])->find());
            $this->display();
        }
    }

    //用户发给商家，确认收货
    public function quer_refund($order_id)
    {
        $order_id = (int) $order_id;
        $order = D('Integralrefund');
        $detail = $order->where(['order_id'=>$order_id])->find();
        if($detail['status'] != 4){
            $this->tuError('该订单暂时不能收货');
        }
        if(false !== $order->where(['order_id'=>$order_id])->save(['status'=>5])){
            $this->tuSuccess('验收成功', U('integralorder/aftersale_order'));
        }else{
            $this->tuError('验收失败');
        }
    }

    //退款
    public function order_refund($order_id)
    {
        if(empty($order_id)){
            $this->tuError('退款订单号丢失');
        }

        $order = D('Integralrefund');
        $detail = $order->where(['order_id'=>$order_id])->find();
        $shop=D('Shop')->where(array('shop_id'=>$detail['shop_id']))->find();
        $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();

        if($detail['status']==8){
            $this->tuError('您已经退过款了');
        }
        if($user['gold']<$detail['money']){

            $this->tuError('您的商户资金小于当前退款资金，请充值后进行退款！');
        }

        //var_dump($detail['type']);die;
        // print_r($detail);die;
        switch ($detail['type']) {
            case '1'://仅退款订单
                if($detail['status'] != 5){
                    $this->tuError('该订单暂不支持打款，请通过后再次尝试');
                }
                //查询该订单的支付信息
                $info = D('Paymentlogs')->where(['type'=>'exchange','order_id'=>$detail['order_id']])->find();
                // echo D('Paymentlogs')->getlastSql();die;
                if(!$info){
                    $where = " order_ids in(".$detail['order_id'].")";
                    $info = D('Paymentlogs')->where(['type'=>'exchange'])->where($where)->find();
                }
                // echo D('Paymentlogs')->getlastSql();die;
                // print_r($info);die;
                //如果余额支付
                if($info['code'] =='money'){
                    if(false !== D('Users')->addMoney($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."取消，余额收到退款金额".$detail['money'])){
                        //修改订单状态
                        // D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);
                        $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                        $this->tuSuccess('确认打款成功1',U('integralorder/aftersale_order'));
                    }else{
                        $this->tuError('确认打款失败');
                    }
                }
                //如果支付宝支付
                if($info['code'] =='alipay'){
                    $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                    $payinfo = unserialize($pay_info['setting']);
                    if(false !== D('Users')->addMoneyLogs($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."取消，支付宝收到退款金额".$detail['money'])){
                        if(false !== D('Integralrefund')->alipay_refund($payinfo,$detail['money'],$info['return_trade_no'],$info['return_msg'])){
                            $re = $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                            if($re){
                                $this->tuSuccess('确认打款成功2',U('integralorder/aftersale_order'));
                            }else{
                                $this->tuError('订单错误');
                            }
                        }else{
                            $re = $order->where(['order_goods_id'=>$order_id])->save(['status'=>8]);
                            $this->tuError('已经退款');
                        }
                    }else{
                        $this->tuError('订单错误');
                    }
                }
                break;
            case '2'://退货退款订单
                if($detail['status'] != 5){
                    $this->tuError('该订单暂不支持打款，请通过后再次尝试');
                }
                //查询该订单的支付信息
                $info = D('Paymentlogs')->where(['type'=>'exchange','order_id'=>$detail['order_id']])->find();
                //var_dump($info);die;
                if(!$info){
                    $where = " order_ids in(".$detail['order_id'].")";
                    $info = D('Paymentlogs')->where(['type'=>'exchange'])->where($where)->find();
                }

                //如果余额支付
                if($info['code'] =='money'){
//
                    if( false !== D('Users')->addMoney($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."收到退款金额".$detail['money']) && false!==D('Users')->addGold($shop['user_id'],-$detail['money'],"订单取消，扣除退款金额".$detail['money'])){
                        //修改订单状态
                        // D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);

                        $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                        $this->tuSuccess('确认打款成功',U('integralorder/aftersale_order'));
                    }

                }
                //如果支付宝支付
                if($info['code'] =='alipay'){
                    $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                    $payinfo = unserialize($pay_info['setting']);
                    if(false !== D('Integralrefund')->alipay_refund($payinfo,$detail['money'],$info['return_trade_no'],$info['return_msg']))
                    {
                        if(false !== D('Users')->addMoneyLogs($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."支付宝收到退款金额".$detail['money']) && false !== D('Users')->addGold($shop['user_id'],-$detail['money'],"订单取消，扣除退款金额".$detail['money'])){
                            $re = $order->where(['order_id'=>$order_id])->save(['status'=>8]);

                            if($re){
                                $this->tuSuccess('确认打款成功',U('integralorder/aftersale_order'));
                            }else{
                                $this->tuError('订单错误');
                            }
                        }
                    }else{
                        $this->tuError('支付宝退款失败，请联系管理员');
                    }

                }
                break;
            default://换货订单
                if($detail['status'] != 1){
                    $this->tuError('请审核通过后再次尝试');
                }
                //更改订单状态为发货状态
                break;
        }
    }


}