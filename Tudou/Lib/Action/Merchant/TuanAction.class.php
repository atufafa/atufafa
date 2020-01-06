<?php
class TuanAction extends CommonAction{
    private $create_fields = array('shop_id','parent_id','shoplx','times_id','banjia','is_reight','weight','kuaidi_id', 'cate_id', 'intro', 'title', 'photo', 'price', 'tuan_price', 'settlement_price', 'num', 'sold_num', 'bg_date', 'end_date', 'fail_date', 'is_hot', 'is_new', 'is_chose', 'freebook','xiangou');
    private $edit_fields = array('shop_id','parent_id','shoplx','times_id','banjia', 'is_reight','weight','kuaidi_id','cate_id', 'intro', 'title', 'photo', 'price', 'tuan_price','num', 'sold_num', 'bg_date', 'end_date', 'fail_date', 'is_hot', 'is_new', 'is_chose', 'freebook');
    protected $tuancates = array();
    public function _initialize(){
        parent::_initialize();
		 if(empty($this->_CONFIG['operation']['tuan'])){
            $this->error('抢购功能已关闭');
            die;
        }
        $this->assign('logistics', $logistics = D('Logistics')->where(array('closed'=>0))->select());
        $this->tuancates = D('Tuancate')->fetchAll();
        $this->assign('tuancates', $this->tuancates);
        $this->autocates = D('Tuanshopcate')->where(array('shop_id' => $this->shop_id))->select();
        $this->assign('autocates', $this->autocates);
        $time=D('Tuantimes')->where(array('colse'=>0))->select();
        $this->assign('times',$time);
        $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$this->shop_id,'type'=>goods,'closed'=>0))->select());
    }
	
	
    public function index(){
        $Tuan = D('Tuan');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Tuan->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            $val = $Tuan->_format($val);
            $list[$k] = $val;
        }
        $this->assign('times',D('Tuantimes')->select());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
	public function delete($tuan_id = 0){
        $tuan_id = (int) $tuan_id;
        $obj = D('Tuan');
        if(empty($tuan_id)){
            $this->tuError('该抢购信息不存在');
        }
        if(!($detail = D('Tuan')->find($tuan_id))){
            $this->tuError('该抢购信息不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('非法操作');
        }
        D('Tuan')->where(['tuan_id'=>$tuan_id])->delete();
        $this->tuSuccess('删除成功', U('tuan/index'));
    }


    //商品上架下架
    public function update2($tuan_id = 0){
        if($tuan_id = (int) $tuan_id){
            if(!($detail = D('Tuan')->find($tuan_id))){
                $this->tuError('请选择要操作的商品');
            }
            $data = array('closed' =>0,'tuan_id' => $tuan_id);
            $intro = '上架商品成功';
            if($detail['closed'] == 0){
                $data['closed'] = 1;
                $intro = '下架商品成功';
            }

            if(D('Tuan')->save($data)){
                $this->tuSuccess($intro, U('tuan/index'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }
	
	
	
	
    public function history(){
        $Tuan = D('Tuan');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id, 'end_date' => array('LT', TODAY));
        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Tuan->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $val = $Tuan->_format($val);
            $list[$k] = $val;
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //全部订单
    public function order(){
        $this->status = array('IN',array(0,1,2,3,4,5,6,7,8));
        $this->is_daofu = array('IN',array(0,1));
        $this->showdatas();
        $this->display();
    }

    //待发货
    public function fahuo(){
        $this->status =1;
        $this->is_daofu = array('IN',array(0,1));
        $this->showdatas();
        $this->display();
    }

    //待确认
    public function confirm(){
        $this->status =2;
        $this->is_daofu = array('IN',array(0,1));
        $this->showdatas();
        $this->display();
    }


    //已完成
    public function complete(){
        $this->status =8;
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
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if(isset($_GET['st']) || isset($_POST['st'])){
            $st = (int) $this->_param('st');
            if($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        }else{
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
        $Tuanorder = D('Tuanorder');
        $count = $Tuanorder->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Tuanorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = $tuan_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
            $address_ids[$val['addr_id']] = $val['addr_id'];

        }
        $this->assign('addrs', D('Paddress')->itemsByIds($address_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('tuan', D('Tuan')->itemsByIds($tuan_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

    }

    //确认完成
    public function distribution(){
        $order_id = (int) $this->_get('order_id');
        $config = D('Setting')->fetchAll();
        $days = isset($config['site']['goods']) ? (int)$config['site']['goods'] : 15;
        $t = NOW_TIME - $days*86400;
        if (!$order_id) {
            $this->tuError('参数错误');
        }else if(!$order = D('Tuanorder')->find($order_id)){
            $this->tuError('该订单不存在');
        }else if($order['shop_id'] != $this->shop_id){
            $this->tuError('不能管理不是您的订单');
        }else if(($order['status'] != 2) && ($order['status']!=3)){
            $this->tuError('该订单状态不正确，不能发货');
        }else{
            D('Tuanorder')->overOrder($order_id); //发货订单接口
            $this->tuSuccess('确认订单完成，资金已结算', U('tuan/complete'));
        }
        $this->tuError('确认订单失败');
    }

    //发货
    public function express($order_id = 0){
        $data = $_POST;
        $order_id = $data['order_id'];
        //var_dump($order_id);die;
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        if (!($detail = D('Tuanorder')->find($order_id))) {
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
        if(D('Tuanorder')->save($add_express)){
            D('Tuanorder')->pc_express_deliver($order_id);//执行发货
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 2);
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 2);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '一键发货成功', 'url' => U('tuan/fahuo')));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '发货失败'));
        }
    }


    //申请退款
    public function sale(){
        import('ORG.Util.Page');
        $Order = D('Tuanrefund');
        $shop=D('Shop')->where(array('user_id'=>$this->uid))->find();
        $map=array('shop_id'=>$shop['shop_id']);
        $count = $Order->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Order->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('create_time desc')->select();
        $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $value) {
            $order = D('Tuanorder')->where(['tuan_id'=>$value['goods_id']])->find();
            $order_ids[$value['order_id']] = $value['order_id'];
            $shop_ids[$order['shop_id']] = $order['shop_id'];
        }

        if (!empty($order_ids)) {
            $goods = D('Tuanorder')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['tuan_id']] = $val['tuan_id'];
            }
            $this->assign('goods', $goods);
        }

        if (!empty($order_ids)) {
            $this->assign('orders', D('Tuanorder')->itemsByIds($order_ids));
        }
        $this->assign('products', D('Tuan')->itemsByIds($goods_ids));

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
        if(false == ($detail = D('Tuanorder')->where(['order_id'=>$order_id])->find())){
            $this->tuError('该退款订单不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError['请勿操作其他商家的退款订单'];
        }
        if($type ==2){
            $refund_list = D('Tuanrefund')->where(['order_id'=>$order_id])->find();
            if($refund_list['type'] != 1){
                if(false ==(!$da=D('Tuanrefund')->where(['order_id'=>$order_id])->save(['status'=>1]))){
                    $this->tuSuccess('审核成功，请注意查看物流确认后再点击确认退款', U('tuan/sale'));
                }else{
                    $this->tuError('审核失败1');
                }
            }else{
                if(false ==(!$da=D('Tuanrefund')->where(['order_id'=>$order_id])->save(['status'=>5]))){
                    $this->tuSuccess('审核成功，订单修改为可打款', U('tuan/sale'));
                }else{
                    $this->tuError('审核失败1');
                }
            }
        }else{
            if(D('Tuanrefund')->where(['order_id'=>$order_id])->save(['status'=>2])){
                $this->tuSuccess('取消成功');
            }else{
                $this->tuError('审核失败2');
            }
        }
    }


    //退款
    public function order_refund($order_id)
    {
        if(empty($order_id)){
            $this->tuError('退款订单号丢失');
        }

        $order = D('Tuanrefund');
        $detail = $order->where(['order_id'=>$order_id])->find();
        $shop=D('Shop')->where(array('shop_id'=>$detail['shop_id']))->find();
        $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();

        if($detail['status']==8){
            $this->tuError('您已经退过款了');
        }
        if($user['gold']<$detail['money']){

            $this->tuError('您的商户资金小于当前退款资金，请充值后进行退款！');
        }


        switch ($detail['type']) {
            case '1'://仅退款订单
                if($detail['status'] != 5){
                    $this->tuError('该订单暂不支持打款，请通过后再次尝试');
                }
                //查询该订单的支付信息
                $info = D('Paymentlogs')->where(['type'=>'tuan','order_id'=>$detail['order_id']])->find();
                // echo D('Paymentlogs')->getlastSql();die;
                if(!$info){
                    $where = " order_ids in(".$detail['order_id'].")";
                    $info = D('Paymentlogs')->where(['type'=>'tuan'])->where($where)->find();
                }
                //如果余额支付
                if($info['code'] =='money'){
                    if(false !== D('Users')->addMoney($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."取消，余额收到退款金额".$detail['money'])){
                        //修改订单状态
                        // D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);
                        $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                        D('Tuanorder')->where(array('order_id'=>$order_id))->save(array('status'=>4));
                        $this->tuSuccess('确认打款成功1',U('tuan/sale'));
                    }else{
                        $this->tuError('确认打款失败');
                    }
                }
                //如果支付宝支付
                if($info['code'] =='alipay'){
                    $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                    $payinfo = unserialize($pay_info['setting']);
                    if(false !== D('Users')->addMoneyLogs($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."取消，支付宝收到退款金额".$detail['money'])){
                        if(false !== D('Tuanrefund')->alipay_refund($payinfo,$detail['money'],$info['return_trade_no'],$info['return_msg'])){
                            $re = $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                            if($re){
                                D('Tuanorder')->where(array('order_id'=>$order_id))->save(array('status'=>4));
                                $this->tuSuccess('确认打款成功2',U('tuan/sale'));
                            }else{
                                $this->tuError('订单错误');
                            }
                        }else{
                            $re = $order->where(['order_id'=>$order_id])->save(['status'=>8]);
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
                $info = D('Paymentlogs')->where(['type'=>'tuan','order_id'=>$detail['order_id']])->find();
                //var_dump($info);die;
                if(!$info){
                    $where = " order_ids in(".$detail['order_id'].")";
                    $info = D('Paymentlogs')->where(['type'=>'tuan'])->where($where)->find();
                }

                //如果余额支付
                if($info['code'] =='money'){
//
                    if( false !== D('Users')->addMoney($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."收到退款金额".$detail['money']) && false!==D('Users')->addGold($shop['user_id'],-$detail['money'],"订单取消，扣除退款金额".$detail['money'])){
                        //修改订单状态
                        // D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);

                        $order->where(['order_id'=>$order_id])->save(['status'=>8]);
                        D('Tuanorder')->where(array('order_id'=>$order_id))->save(array('status'=>4));
                        $this->tuSuccess('确认打款成功',U('tuan/sale'));
                    }

                }
                //如果支付宝支付
                if($info['code'] =='alipay'){
                    $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                    $payinfo = unserialize($pay_info['setting']);
                    if(false !== D('Tuanrefund')->alipay_refund($payinfo,$detail['money'],$info['return_trade_no'],$info['return_msg']))
                    {
                        if(false !== D('Users')->addMoneyLogs($info['user_id'],$detail['money'],"订单号:".$detail['order_id']."支付宝收到退款金额".$detail['money']) && false !== D('Users')->addGold($shop['user_id'],-$detail['money'],"订单取消，扣除退款金额".$detail['money'])){
                            $re = $order->where(['order_id'=>$order_id])->save(['status'=>8]);

                            if($re){
                                D('Tuanorder')->where(array('order_id'=>$order_id))->save(array('status'=>4));
                                $this->tuSuccess('确认打款成功',U('tuan/sale'));
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

    //用户发给商家，确认收货
    public function quer_refund($order_id)
    {
        $order_id = (int) $order_id;
        $order = D('Tuanrefund');
        $detail = $order->where(['order_id'=>$order_id])->find();
        if($detail['status'] != 4){
            $this->tuError('该订单暂时不能收货');
        }
        if(false !== $order->where(['order_id'=>$order_id])->save(['status'=>5])){
            $this->tuSuccess('验收成功', U('tuan/sale'));
        }else{
            $this->tuError('验收失败');
        }
    }

    //换货
    public function refund_order($order_id){

        if($this->isPost()){

            if($order_id = (int) $order_id){
                if(!$order = D('Tuanrefund')->where(['order_id'=>$order_id])->find()){
                    $this->tuError('订单不存在');
                }elseif($order['type'] != 3){

                    $this->tuError('该订单不支持，请联系管理员');
                }else{
                    if($order['shop_id'] != $this->shop_id){
                        $this->tuError('无权限操作此订单');
                    }
                    if(false !== D('Tuanrefund')->where(['order_id'=>$order_id])->save(['express_no_shop'=>$_POST['express_no_shop'],'express_cp_shop'=>$_POST['express_cp_shop'],'status'=>6])){
                        $this->tuSuccess('订单操作成功',U('tuan/sale'));
                    }else{
                        $this->tuError('订单操作失败');
                    }
                }
            }else{
                $this->tuError('请选择要确认的订单');
            }
        }else{
            $this->assign('detail',D('Tuanrefund')->where(['order_id'=>$order_id])->find());
            $this->display();
        }
    }





















    public function used(){
        if ($this->isPost()) {
            $code = $this->_post('code', false);
            if (empty($code)) {
                $this->tuError('请输入抢购券');
            }
            $obj = D('Tuancode');
            $return = array();
            $ip = get_client_ip();
            if (count($code) > 10) {
                $this->tuError('一次最多验证10条抢购券');
            }
            $userobj = D('Users');
            foreach ($code as $key => $var){
                $var = trim(htmlspecialchars($var));
                if(!empty($var)){
                    $detail = $obj->find(array('where' => array('code' => $var)));
                    $shop = D('Shop')->find(array('where' => array('shop_id' => $detail['shop_id'])));
                    if(!empty($detail) && $detail['shop_id'] == $this->shop_id && (int) $detail['is_used'] == 0 && (int) $detail['status'] == 0) {
						$data = array();
						$data['is_used'] = 1;
						$data['worker_id'] = $this->uid;
						$data['used_time'] = NOW_TIME;
						$data['used_ip'] = get_client_ip();
             			if($obj->where(array('code_id'=>$detail['code_id']))->save($data)){
						   $res = $obj->saveShopMoney($detail,$shop);//统一更新
                           if($res == 1){
								$return[$var] = $var;
                                echo '<script>parent.used(' . $key . ',"√验证成功",1);</script>';
                            } else {
                                echo '<script>parent.used(' . $key . ',"√到店付抢购券验证成功，需要现金付款",2);</script>';
                            }
                        }
                    } else {
                        echo '<script>parent.used(' . $key . ',"X该抢购券无效",3);</script>';
                    }
                }
            }
        } else {
            $this->display();
        }
    }
	
	
    public function create(){
        if($this->isPost()){
            $data = $this->createCheck();
            $obj = D('Tuan');
            $details = $this->_post('details', 'SecurityEditorHtml');
            if(empty($details)){
                $this->tuError('抢购详情不能为空');
            }
            if($words = D('Sensitive')->checkWords($details)){
                $this->tuError('详细内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            if(empty($instructions)){
                $this->tuError('购买须知不能为空');
            }
            if($words = D('Sensitive')->checkWords($instructions)){
                $this->tuError('购买须知含有敏感词：' . $words);
            }
            $thumb = $this->_param('thumb', false);
            foreach($thumb as $k => $val){
                if(empty($val)){
                    unset($thumb[$k]);
                }
                if(!isImage($val)){
                    unset($thumb[$k]);
                }
            }
            // print_r($data);die;
            $data['thumb'] = serialize($thumb);
            if ($tuan_id = $obj->add($data)){
                $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                //抢购类型是2
                $this->shuxin($tuan_id);
                $this->saveGoodsAttr($tuan_id, $_POST['goods_type']); //更新商品属性 -->
                $obj->save(array('tuan_id' => $tuan_id, 'wei_pic' => $wei_pic));
                D('Tuandetails')->add(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions));
                $this->tuSuccess('添加成功', U('tuan/index'));
            }
            $this->tuError('操作失败');
        } else {
            $shop_user=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
            $shop_tel=$this->_CONFIG['goods']['shop_tel'];
            if($shop_user['mobile']==$shop_tel){
                $this->assign('cates',D('Goodscate')->fetchAll());
            }else{
                $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
            }

            $this->assign('goodsType', D("Tuantype")->where(array("shop_id" => $this->shop_id))->select());
            $this->display();
        }
    }
	
	
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        // print_r($data);die;
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])){
            $this->tuError('抢购分类不能为空');
        }
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;
		
        $data['shop_id'] = $this->shop_id;
		$data['city_id'] = $this->shop['city_id'];
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['lng'] = $this->shop['lng'];
        $data['lat'] = $this->shop['lat'];
		
		
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('抢购名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('抢购简介不能为空');
        }
        $data['is_reight'] = (int) $data['is_reight'];

        $data['weight'] = (int) $data['weight'];
        if ($data['is_reight'] == 1) {
            if (empty($data['weight'])) {
                $this->tuError('重量不能为空');
            }
        }
        $data['kuaidi_id'] = (int) $data['kuaidi_id'];
        if ($data['is_reight'] == 1) {
            if (empty($data['kuaidi_id'])) {
                $this->tuError('运费模板不能为空');
            }
        }

        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])){
            $this->tuError('抢购分类不能为空');
        }
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;


        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('图片格式不正确');
        }
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        }
        $data['tuan_price'] = (float) ($data['tuan_price']);
        if (empty($data['tuan_price'])) {
            $this->tuError('抢购价格不能为空');
        }

        $data['times_id'] = htmlspecialchars($data['times_id']);
        if(empty($data['times_id'])){
            $this->tuError('抢购时间不能为空');
        }
        if($data['times_id']==0){
            $this->tuError('请选择正确时间');
        }
        $config = D('Setting')->fetchAll();
        $time=D('Tuan')->where(array('times_id'=>$data['times_id']))->count();
        if($time>=$config['rush']['num']){
            $this->tuError('该时间段的商家已经满了，请换一个时间吧！');
       }
		//添加
        $data['settlement_price'] = (int) ($data['tuan_price'] - $data['tuan_price'] * $this->tuancates[$data['cate_id']]['rate'] / 1000);

        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        }
        $data['sold_num'] = (int) $data['sold_num'];
        $data['bg_date'] = htmlspecialchars($data['bg_date']);
        if (empty($data['bg_date'])) {
            $this->tuError('开始时间不能为空');
        }
        if (!isDate($data['bg_date'])) {
            $this->tuError('开始时间格式不正确');
        }
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('结束时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('结束时间格式不正确');
        }
        $data['is_hot'] = (int) $data['is_hot'];
        $data['xiangou'] = (int) $data['xiangou'];
        $data['is_new'] = (int) $data['is_new'];
        $data['is_chose'] = (int) $data['is_chose'];
        $data['is_multi'] = (int) $data['is_multi'];
        $data['freebook'] = (int) $data['freebook'];
        $data['banjia'] = (int) $data['banjia'];
        $data['is_return_cash'] = (int) $data['is_return_cash'];
        $data['fail_date'] = htmlspecialchars($data['fail_date']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
		$data['audit'] = 0;
        return $data;
    }
	
	
    public function setting($tuan_id = 0){
        if($tuan_id = (int) $tuan_id){
            $obj = D('Tuanmeal');
            if(!($detail = D('Tuan')->find($tuan_id))){
                $this->tuError('请选择要设置的抢购');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('请不要操作别人的抢购');
            }
            if($detail['closed'] != 0){
                $this->tuError('该抢购已被删除');
            }
            $tuan_details = D('Tuandetails')->getDetail($tuan_id);
            if($this->isPost()){
                $name = $this->_post('name', 'htmlspecialchars');
                if (empty($name)){
                    $this->tuError('主套餐名称不能为空');
                }
                $data = $this->_post('data', false);
                $obj->delete(array('where' => array('tuan_id' => $tuan_id)));
                $obj->add(array('tuan_id' => $tuan_id, 'id' => 0, 'name' => $name));
				$data = array_unique($data); 
                foreach ($data as $val) {
                    if (!empty($val['id']) && !empty($val['name'])) {
						if(!($Tuan = D('Tuan')->find($val['id']))) {
							$this->tuError('ID【'.$val['id'].'】错误');
						}
						if($Tuan['shop_id'] != $this->shop_id) {
							$this->tuError('ID【'.$val['id'].'】不属于您的抢购商品');
						}
                        $obj->add(array('tuan_id' => $tuan_id, 'id' => $val['id'], 'name' => $val['name']));
                    }
                }
                $this->tuSuccess('操作成功', U('tuan/setting',array('tuan_id'=>$tuan_id)));
            } else {
                $this->assign('detail', $detail);
                $this->assign('meals', $meals = $obj->where(array('tuan_id' => $tuan_id, 'id' => array('NEQ', 0)))->select());
				$this->assign('count', $count = $obj->where(array('tuan_id' => $tuan_id, 'id' => array('NEQ', 0)))->count());
                $this->assign('name', $obj->where(array('tuan_id' => $tuan_id, 'id' => 0))->find());
                $this->display();
            }
        } else {
            $this->tuError('请选择要设置的抢购');
        }
    }
	
	
    public function edit($tuan_id = 0){
        if ($tuan_id = (int) $tuan_id) {
            $obj = D('Tuan');
            if (!($detail = $obj->find($tuan_id))) {
                $this->tuError('请选择要编辑的抢购');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->tuError('请不要操作别人的抢购');
            }
            if ($detail['closed'] != 0) {
                $this->tuError('该抢购已被删除');
            }
            $tuan_details = D('Tuandetails')->getDetail($tuan_id);
            if ($this->isPost()) {
                $data = $this->editCheck();
                $details = $this->_post('details', 'SecurityEditorHtml');
                if (empty($details)) {
                    $this->tuError('抢购详情不能为空');
                }
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuError('详细内容含有敏感词：' . $words);
                }
                $instructions = $this->_post('instructions', 'SecurityEditorHtml');
				
                if (empty($instructions)) {
                    $this->tuError('购买须知不能为空');
                }
                if ($words = D('Sensitive')->checkWords($instructions)) {
                    $this->tuError('购买须知含有敏感词：' . $words);
                }
                $thumb = $this->_param('thumb', false);
                foreach ($thumb as $k => $val) {
                    if (empty($val)) {
                        unset($thumb[$k]);
                    }
                    if (!isImage($val)) {
                        unset($thumb[$k]);
                    }
                }
                $data['thumb'] = serialize($thumb);
                $data['tuan_id'] = $tuan_id;
                if (!empty($detail['wei_pic'])) {
                    if (true !== strpos($detail['wei_pic'], 'https://mp.weixin.qq.com/')) {
                        $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                        $data['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                    $data['wei_pic'] = $wei_pic;
                }
                $data['audit'] = 0;
                if(false !== $obj->save($data)){
                    $this->shuxin($tuan_id);
                    $this->saveGoodsAttr($tuan_id, $_POST['goods_type']); //更新商品属性 -->
                    D('Tuandetails')->save(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions));
                    $this->tuSuccess('操作成功', U('tuan/index'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $obj->_format($detail));
                $thumb = unserialize($detail['thumb']);
                $this->assign('thumb', $thumb);
				$this->assign('parent_id',D('tuancate')->getParentsId($detail['cate_id']));
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->assign('tuan_details', $tuan_details);
                $shop_user=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
                $shop_tel=$this->_CONFIG['goods']['shop_tel'];
                if($shop_user['mobile']==$shop_tel){
                    $this->assign('cates',D('Goodscate')->fetchAll());
                    $this->assign('parent',D('Goodscate')->select());
                }else{
                    $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                    $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
                }
                $this->assign('goodsType',D("Tuantype")->where(['shop_id'=>$this->shop_id])->select());
                $this->assign('attrs', D('Tuanattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的抢购');
        }
    }
	
	
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('抢购分类不能为空');
        }

        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;
		
        $data['shop_id'] = $this->shop_id;
		$data['city_id'] = $this->shop['city_id'];
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['lng'] = $this->shop['lng'];
        $data['lat'] = $this->shop['lat'];
		
		
		
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('商品名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('副标题不能为空');
        }

        $data['is_reight'] = (int) $data['is_reight'];

        $data['weight'] = (int) $data['weight'];
        if ($data['is_reight'] == 1) {
            if (empty($data['weight'])) {
                $this->tuError('重量不能为空');
            }
        }
        $data['kuaidi_id'] = (int) $data['kuaidi_id'];
        if ($data['is_reight'] == 1) {
            if (empty($data['kuaidi_id'])) {
                $this->tuError('运费模板不能为空');
            }
        }

        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuError('请上传图片');
        }
        if(!isImage($data['photo'])){
            $this->tuError('图片格式不正确');
        }
        $data['price'] = (float) ($data['price']);
        if(empty($data['price'])){
            $this->tuError('市场价格不能为空');
        }
        $data['tuan_price'] = (float) ($data['tuan_price']);
        if(empty($data['tuan_price'])){
            $this->tuError('抢购价格不能为空');
        }
		if($data['tuan_price'] >= $data['price']){
            $this->tuMsg('售价不能大于或者等于市场价');
        }
		
		
		//编辑
        $data['settlement_price'] = (int) ($data['tuan_price'] - $data['tuan_price'] * $this->tuancates[$data['cate_id']]['rate'] / 1000);//编辑时候暂时不写入结算价
        $data['times_id'] = htmlspecialchars($data['times_id']);
        if(empty($data['times_id'])){
            $this->tuError('抢购时间不能为空');
        }
        if($data['times_id']==0){
            $this->tuError('请选择正确时间');
        }

        $config = D('Setting')->fetchAll();
        $time=D('Tuan')->where(array('times_id'=>$data['times_id']))->count();
        if($time>=$config['rush']['num']){
            $this->tuError('该时间段的商家已经满了，请换一个时间吧！');
        }

        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        }
        $data['sold_num'] = (int) $data['sold_num'];
        $data['bg_date'] = htmlspecialchars($data['bg_date']);
        if (empty($data['bg_date'])) {
            $this->tuError('开始时间不能为空');
        }
        if (!isDate($data['bg_date'])) {
            $this->tuError('开始时间格式不正确');
        }
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('结束时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('结束时间格式不正确');
        }
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_new'] = (int) $data['is_new'];
        $data['is_chose'] = (int) $data['is_chose'];
        $data['is_multi'] = (int) $data['is_multi'];
        $data['freebook'] = (int) $data['freebook'];
        $data['is_return_cash'] = (int) $data['is_return_cash'];
        $data['banjia'] = (int) $data['banjia'];
        $data['fail_date'] = htmlspecialchars($data['fail_date']);
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetAttrInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
        $str = $this->getAttrInput($goods_id,$type_id);
        exit($str);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id){
        $attributeList = D('Tuanattribute')->where(array('type_id'=>$type_id))->select();
        foreach($attributeList as $key => $val){

            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
            //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; //加减符号
                //单选属性或者复选属性
                if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                    if($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";

                //手工录入
                if($val['attr_input_type'] == 0){
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                //从下面的列表中选择（一行代表一个可选值）
                if($val['attr_input_type'] == 1){
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                    foreach($tmp_option_val as $k2=>$v2){
                        //编辑的时候有选中值
                        $v2 = preg_replace("/\s/","",$v2);
                        if($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                }
                //多行文本框
                if($val['attr_input_type'] == 2){
                    $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                }
                $str .= "</td></tr>";
            }

        }
        return  $str;
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return D('Tuanattr')->where(array('goods_attr_id'=>$goods_attr_id))->select();
        if($goods_id > 0 && $attr_id > 0)
            return D('Tuanattr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('Tuanspec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = D('Tuanspecitem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = D('Tuanspecprice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        if($goods_id){
            $specImageList = D('Tuanspecimgs')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2);

        $spec = D('Tuanspec')->getField('id,name');
        $specItem = D('Tuanspecitem')->getField('id,item,spec_id');
        $keySpecGoodsPrice = D('Tuanspecprice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,store_count,bar_code');

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        foreach ($clo_name as $k => $v) {
            $str .=" <td><b>{$spec[$v]}</b></td>";
        }
        $str .="<td><b>价格</b></td>
              <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
        foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";
        }
        $str .= "</table>";
        return $str;
    }

    public function shuxin($goods_id){
        if($_POST['item']){
            $spec = D('Tuanspec')->getField('id,name');
            $specItem = D('Tuanspecitem')->getField('id,item');

            $specGoodsPrice = D("Tuanspecprice");
            $specGoodsPrice->where('goods_id = '.$goods_id)->delete();
            foreach($_POST['item'] as $k => $v){
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']);
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
            }
            $specGoodsPrice->addAll($dataList);
        }
        refresh_stock($goods_id);

    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type){


        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if($goods_type == 0)  {
            D('Tuanattr')->where(array('goods_id'=>$goods_id))->delete();
            return;
        }

        $GoodsAttrList = D('Tuanattr')->where(array('goods_id'=>$goods_id))->select();

        $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
        foreach($GoodsAttrList as $k => $v){
            $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
        }

        // post提交的属性以attr_id_和值的组合为键名
        $post_goods_attr = array();

        foreach($_POST as $k => $v){
            $attr_id = str_replace('attr_','',$k);
            if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2){
                $v2 = str_replace('_', '', $v2); //替换特殊字符
                $v2 = str_replace('@', '', $v2); //替换特殊字符
                $v2 = trim($v2);

                if(empty($v2))
                    continue;
                $tmp_key = $attr_id."_".$v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if(array_key_exists($tmp_key , $old_goods_attr)){
                    //如果这个属性原来就存在
                    if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){
                        //并且价格不一样就做更新处理
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        D('Tuanattr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));
                    }
                }else{
                    //否则这个属性 数据库中不存在 说明要做删除操作
                    D('Tuanattr')->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }
        }
        //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
        foreach($old_goods_attr as $k => $v){
            D('Tuanattr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
        }
    }

    //选择分类
	public function child($parent_id=0){
        $datas = D('Goodscate')->fetchAll();
        $str = '';
        foreach($datas as $var){
            if($var['parent_id'] == 0 && $var['cate_id'] == $parent_id){
                foreach($datas as $var2){
                    if($var2['parent_id'] == $var['cate_id']){
                        $str.='<option value="'.$var2['cate_id'].'">'.$var2['cate_name'].'</option>'."\n\r";
                        foreach($datas as $var3){
                            if($var3['parent_id'] == $var2['cate_id']){
                               $str.='<option value="'.$var3['cate_id'].'">&nbsp;&nbsp;--'.$var3['cate_name'].'</option>'."\n\r"; 
                            }
                            
                        }
                    }  
                }
                             
            }           
        }
        echo $str;die;
    }
	
	  public function ajax($cate_id,$goods_id=0){
        if(!$cate_id = (int)$cate_id){
            $this->error('请选择正确的分类');
        }
        if(!$detail = D('Tuancate')->find($cate_id)){
            $this->error('请选择正确的分类');
        }
        $this->assign('cate',$detail);
        $this->assign('attrs',D('Goodscateattr')->order(array('orderby'=>'asc'))->where(array('cate_id'=>$cate_id))->select());
        if($goods_id){
            $this->assign('detail',D('Goods')->find($goods_id));
            $this->assign('maps',D('GoodsCateattr')->getAttrs($goods_id));
        }
        $this->display();
    }
	
	
	// 抢购劵列表
    public function code(){
        $Tuancode = D('Tuancode');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id, 'closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['code_id|code'] = array('LIKE', '%' . $keyword . '%');
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
        $count = $Tuancode->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $Tuancode->where($map)->order(array('code_id' => 'desc','used_time'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $tuan_ids = $user_ids = array();
        foreach ($list as $val) {
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
			$user_ids[$val['user_id']] = $val['user_id'];
        }
        $this->assign('tuans', D('Tuan')->itemsByIds($tuan_ids));
		$this->assign('users', D('Users')->itemsByIds($user_ids));
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
			
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display(); 
    }
	//验证记录
	 public function usedok(){
        $Tuancode = D('Tuancode');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id, 'is_used' => '1');
        if (strtotime($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && strtotime($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            if (!empty($bg_time) && !empty($end_date)) {
                $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            }
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                if (!empty($bg_time)) {
                    $map['create_time'] = array('EGT', $bg_time);
                }
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                if (!empty($end_time)) {
                    $map['create_time'] = array('ELT', $end_time);
                }
                $this->assign('end_date', $end_date);
            }
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $keyword = intval($keyword);
            if (!empty($keyword)) {
                $map['code_id|code'] = array('LIKE', '%' . $keyword . '%');
                $this->assign('keyword', $keyword);
            }
        }
        $count = $Tuancode->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $Tuancode->where($map)->order(array('used_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('tuans', D('Tuan')->itemsByIds($tuan_ids));
        $this->display();
    }
	
	
	 public function refund($code_id = 0){
        $code_id = (int) $code_id;
        $detail = D('Tuancode')->find($code_id);
        $tuan_order = D('Tuanorder');
        $order = $tuan_order->find($detail['order_id']);
        if ($detail['status'] == 1 && (int) $detail['is_used'] === 0) {
            if ($order['status'] != 3) {
                $this->tuError('操作错误');
            }
			if ($order['shop_id'] != $this->shop_id) {
                $this->tuError('非法操作');
            }
            $tuan_order->save(array('order_id' => $detail['order_id'], 'status' => 4)); //改变订单状态
            if (D('Tuancode')->save(array('code_id' => $code_id, 'status' => 2))) {//将内容变成
                $obj = D('Users');
                if ($detail['real_money'] > 0) {
                    $obj->addMoney($detail['user_id'], $detail['real_money'], '抢购券退款:' . $detail['code']);
                }
                if ($detail['real_integral'] > 0) {
                    $obj->addIntegral($detail['user_id'], $detail['real_integral'], '抢购券退款:' . $detail['code']);
                }
            }
            $where['tuan_id'] = $detail['tuan_id'];
            $tuan_num = D("Tuanorder")->where($where)->getField("num");
			D('Sms')->tuancode_refund_user($code_id);// 退款成功通知用户
            D("Tuan")->where($where)->setInc("num", $tuan_num);// 修复退款后增加库存
            $this->tuSuccess('退款成功', U('tuan/code'));
        } else {
            $this->tuError('当前订单状态不正确');
        }
    }
	
	
	public function detail($order_id){
        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D('Tuanorder')->find($order_id))) {
            $this->error('该订单不存在');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要操作他人的订单');
        }
        if (!($dianping = D('Tuandianping')->where(array('order_id' => $order_id, 'user_id' => $this->uid))->find())) {
            $detail['dianping'] = 0;
        } else {
            $detail['dianping'] = 1;
        }
		$this->assign('users', D('Users')->find($detail['user_id']));
        $this->assign('tuans', D('Tuan')->find($detail['tuan_id']));
        $this->assign('detail', $detail);
        $this->display();
    }

    private function check_tuan(){

        $hotel = D('Tuan');
        $res =  $hotel->where(array('shop_id'=>$this->shop_id))->find();
        return $res['tuan_id'];
    }
    //点评
    public function comment(){
        $ktv_id = $this->check_tuan();
        $obj = M('TuanDianping');
        $map = array('closed' => 0, 'tuan_id' => $ktv_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if(isset($_GET['status']) || isset($_POST['status'])) {
            $status = (int) $this->_param('status');
            if ($status != 999) {
                $map['status'] = $status;
            }
            $this->assign('status', $status);
        }else{
            $this->assign('status', 999);
        }
        if($gotime = $this->_param('gotime', 'htmlspecialchars')){
            $gotime = strtotime($gotime);
            $map['gotime'] = array(array('ELT', $gotime+86399), array('EGT', $gotime));
        }

        import('ORG.Util.Page');
        $count  = $obj->where($map)->count();
        $Page  = new Page($count,25);
        $show  = $Page->show();
        $list = $obj->where($map)->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($list as $k => $v){
            $room = D('KtvRoom') -> where(array('room_id'=>$v['room_id'])) -> find();
            $list[$k]['room'] = $room['title'];
        }

        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display();
    }

    //点评回复
    public function reply($dianping_id){
        $dianping_id = (int) $dianping_id;
        $detail = D('TuanDianping')->find($dianping_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->tuError('没有该内容');
        }
        if ($this->isPost()) {
            $reply = $this->_param('reply', 'htmlspecialchars');
            print_r($reply);
            if ($reply) {
                $data = array(
                    'dianping_id' => $dianping_id,
                    'reply' => $reply,
                    'reply_ip' => get_client_ip(),
                    'reply_time' => time(),
                );
                if (D('TuanDianping')->save($data)) {
                    $this->tuSuccess('回复成功', U('tuan/comment'));
                }
            }
            $this->tuError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }

    //点评删除
    public function comment_delete($dianping_id =0)
    {
        if ($dianping_id = (int) $dianping_id){
            $obj = D('TuanDianping');
            $detail = D('TuanDianping')->where(array('dianping_id' => $dianping_id, 'shop_id' => $this->shop_id))->find();
            if (!$detail){
                $this->tuError('点评记录不存在');
            }
            if($obj->delete($dianping_id)){
                $this->tuSuccess('删除成功', U('tuan/comment'));
            }
            $this->tuError('操作失败');
        } else {
            $this->tuError('请选择要删除的点评');
        }
    }

    //0元购竞价
    public function tuan_top($goods_id = 0,$check_price,$check_price_money=0){
        if(IS_AJAX){
            $obj = D('Tuan');
            $goods_id = I('goods_id', 0, 'trim,intval');
            if(!($detail = $obj->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品ID【'.$goods_id.'】不存在'));
            }
            if($detail['audit'] !=1){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品未通过审核，不能开启竞价'));
            }
            $check_price = I('check_price');
            $check_price_money = I('check_price_money');
            $end_time = strtotime(I('end_time'));
            $status = M('Jingjia')->where(['id'=>1])->find();
            if(!$check_price){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '暂未出价'));
            }

            if($status['status'] ==1){
                $this->ajaxReturn(array('status'=>'error','msg'=>'竞价未开启'));
            }
            if(($status['min_price'])>$check_price){
                $this->ajaxReturn(array('status'=>'error','msg'=>'最低竞价金额￥'.$status['min_price']));
            }

            if(false == $obj->top_time($goods_id,$check_price,$check_price_money,$end_time)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => $obj->getError()));
            }else{
                $this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', U('tuan/index')));
            }
        }
    }


    //0元购取消竞价
    public function update_tuantop($tuan_id)
    {
        if(!($goods = D('Tuan')->find($tuan_id))){
            $this->tuError('请选择要操作的商品');
        }
        if($goods['is_tui'] ==0){
            $this->tuError('该商品暂未参加竞价');
        }
        //查询开启竞价时间
        if(NOW_TIME <($goods['start_time']+86400)){
            $time = (int)(($goods['start_time']+86400 - NOW_TIME)/60);
            $this->tuError('未到规定时间，无法取消，需等待'.$time.'分钟');
        }
        if(!false == D('Tuan')->where(['tuan_id'])->save(array('check_price'=>0,'is_tui'=>0))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }

    //竞价信息列表
    public function goodsjingjia()
    {
        $Goods = D('TuanShopJingjia');
        import('ORG.Util.Page');
        $map = array('shop_id' =>$this->shop_id);
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach($list as $k => $val){
            $goods = D('Tuan')->where(['tuan_id'=>$val['goods_id']])->find();
            $list[$k]['title'] = $goods['title'];
            $list[$k]['photo'] = $goods['photo'];
            $list[$k]['mall_price'] = $goods['mall_price'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //竞价列表删除
    public function jingjiadel($bid_id)
    {
        $bid_id = (int) $bid_id;
        $obj = D('TuanShopJingjia');
        if(empty($bid_id)){
            $this->tuError('该信息不存在');
        }
        if(!($detail = $obj->find($bid_id))){
            $this->tuError('该商品信息不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('非法操作');
        }
        $obj->where(['bid_id'=>$bid_id])->delete();
        $this->tuSuccess('删除成功', U('tuan/goodsjingjia'));
    }




}