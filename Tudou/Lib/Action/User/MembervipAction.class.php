<?php
class MembervipAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        if ($this->_CONFIG['operation']['exchange'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
    }

    //显示
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    //加载
    public function loaddata(){
        $Order = D('ExchangeOrder');
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
            $goods = D('ExchangeOrderGoods')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
            }
            $this->assign('goods',$goods);

            $this->assign('products',D('ExchangeGoods')->itemsByIds($goods_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('types', D('ExchangeOrder')->getType());
        $this->assign('goodtypes', D('ExchangeOrder')->getType());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //详细信息
    public function detail($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D('ExchangeOrder')->find($order_id))){
            $this->error('该订单不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的订单');
        }
        $order_goods = D('ExchangeOrderGoods')->where(array('order_id' => $order_id))->select();
        $goods_ids = array();
        foreach ($order_goods as $k => $val){
            $goods_ids[$val['goods_id']] = $val['goods_id'];
        }
        if(!empty($goods_ids)){
            $this->assign('goods', D('ExchangeGoods')->itemsByIds($goods_ids));
        }
        $this->assign('data', $data = D('Logistics')->get_order_exchange($order_id));//查询清单物流
        $this->assign('ordergoods', $order_goods);
        $this->assign('types', D('ExchangeOrder')->getType());
        $this->assign('goodtypes', D('ExchangeOrderGoods')->getType());
        $detail['delivery_order'] = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type'=>0,'closed'=>0))->find();
        $this->assign('detail', $detail);
        $this->display();
    }

    //确认收货
    public function queren($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('ExchangeOrder');
            if (!($detial = $obj->find($order_id))) {
                $this->tuMsg('该订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }
            //检测配送状态
            if($detial['is_daofu'] == 1) {
                $into = '货到付款确认收货成功';
            }else{
                if ($detial['status'] != 2) {
                    $this->tuMsg('该订单暂时不能确定收货');
                }
                $into = '确认收货成功';
            }
            if ($obj->save(array('order_id' => $order_id, 'status' => 3))) {
                D('ExchangeOrder')->overOrder($order_id); //确认到账入口
                $this->tuMsg($into, U('membervip/index', array('aready' => 8)));
            }else{
                $this->tuMsg('操作失败');
            }
        } else {
            $this->tuMsg('请选择要确认收货的订单');
        }
    }

    /*
     * 会员专享兑换卷
     */
    public function coupon(){
        $this->display();
    }

    //加载loading
    public function couponloading(){
        $Coupondownloads = D('BuyVip');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid,'colsed'=>0);
        $count = $Coupondownloads->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Coupondownloads->where($map)->order('id asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $hui=$Coupondownloads->where($map)->find();
        $time=date('Y-m-d',time());
        $endtime=$this->diffBetweenTwoDays($hui['end_time'],$time);
        if($endtime<=5){
            $daoqi=1;
        }else{
            $daoqi=2;
        }
        $this->assign('daoqi',$daoqi);
        $this->assign('time',$endtime);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    function diffBetweenTwoDays ($day1, $day2){
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }

    //消费日志
    public function losloading(){
        $Coupondownloads = D('Buyviplog');
        import('ORG.Util.Page');
        //$map = array('user_id' => $this->uid,'softdel'=>1);
        $map = array('user_id' => $this->uid,'colsed'=>0);
        $count = $Coupondownloads->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Coupondownloads->where($map)->order('create_time asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除日志
    public function del($log_id=0){
        $log_id = (int) $log_id;
        if (empty($log_id)) {
            $this->error('该兑换劵不存在');
        }
        if (!($detail = D('Buyviplog')->find($log_id))) {
            $this->error('该优惠券不存在');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->error('请不要操作别人的优惠券');
        }

        D('Buyviplog')->where(array('log_id'=>$log_id))->save(array('colsed'=>1));
        $this->success('删除成功', U('membervip/coupon'));
    }




}