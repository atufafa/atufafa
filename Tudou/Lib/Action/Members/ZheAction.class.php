<?php
class ZheAction extends CommonAction{
    protected $Activitycates = array();
    public function _initialize() {
        parent::_initialize();
        if ($this->_CONFIG['operation']['zhe'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
        $this->shopcates = D('Shopcate')->fetchAll();
        $this->assign('shopcates', $this->shopcates);
        $this->getZheWeek = D('Zhe')->getZheWeek();
        $this->assign('weeks',  $this->getZheWeek);
        $this->getZheDate = D('Zhe')->getZheDate();
        $this->assign('dates',  $this->getZheDate);
        $this->assign('host',__HOST__);
    }
    public function index() {
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;


        $cate_id = (int) $this->_param('cate_id');
        $this->assign('cate_id', $cate_id);
        $linkArr['cate_id'] = $cate_id;


        $week_id = (int) $this->_param('week_id');
        $this->assign('week_id', $week_id);
        $linkArr['week_id'] = $week_id;

        if($date_id = (int) $this->_param('date_id')){
            $linkArr['date_id'] = $date_id;
            $this->assign('date_id', $date_id);
        }else{
            $date_id = date("j");
            $this->assign('date_id', $date_id);
            $linkArr['date_id'] = $date_id;
        }

        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;


        $this->assign('nextpage', LinkTo('zhe/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('linkArr',$linkArr);
        $this->display(); // 输出模板
    }

    public function zhe(){
        $obj = D('Zheorder');
        $map = array('user_id'=>$this->uid,'closed'=>0,'status'=>1,'end_time' => array('EGT', NOW_TIME));
        $count = $obj->where($map)->count();
        if($count > 1){
            $this->error('五折卡状态不正确，请联系管理员处理');
        }
        $detail = $obj->where($map)->find();
        $detail['user'] = D('Users')->find($detail['user_id']);
        $this->assign('detail',$detail);
        $this->display('zhe_detail');
    }

    public function zhe_yuyue(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    public function zhe_yuyue_loaddata(){
        $Zheyuyue = D('Zheyuyue');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);
        $bg_time = strtotime(TODAY);
        $NOW_TIME = strtotime(TODAY) + 86000;//今天晚上的时间
        $aready = (int) $this->_param('aready');
        if ($aready == -1) {//已过期
            $map['is_used'] = -1;
        } elseif ($aready == 1) {
            $map['is_used'] = 1;
        } else {//待消费未过期
            $aready == null;
            $map['is_used'] = 0;
        }
        $this->assign('aready', $aready);
        $count = $Zheyuyue->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Zheyuyue->where($map)->order(array('yuyue_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            if($zhe = D('Zhe')->where(array('zhe_id'=>$v['zhe_id']))->find()){
                $list[$k]['zhe'] = $zhe;
            }
            if($shop = D('Shop')->where(array('shop_id'=>$v['shop_id']))->find()){
                $list[$k]['shop'] = $shop;
            }
            if($users = D('Users')->where(array('user_id'=>$v['user_id']))->find()){
                $list[$k]['users'] = $users;
            }
            if(($v['create_time'] < $bg_time) && ($v['is_used'] == 0)){ //如果超过了今天
                $Zheyuyue->save(array('yuyue_id'=>$v['yuyue_id'],'is_used'=> -1));
                $list[$k]['is_used'] = -1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //五折卡详情
    public function zhe_yuyue_detail($yuyue_id){
        if(!$yuyue_id = (int)$yuyue_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Zheyuyue')->find($yuyue_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
            $detail['zhe'] = D('Zhe')->where(array('zhe_id'=>$detail['zhe_id']))->find();
            $detail['shop'] = D('Shop')->where(array('shop_id'=>$detail['shop_id']))->find();
            $detail['users'] = D('Users')->where(array('user_id'=>$detail['user_id']))->find();
            $this->assign('detail',$detail);
            $this->display();
        }
    }

    //五折卡详情
    public function zhe_yuyue_qrcode($yuyue_id){
        if(!$yuyue_id = (int)$yuyue_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Zheyuyue')->find($yuyue_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
            $url = U('seller/zhe/used', array('yuyue_id' => $yuyue_id, 't' => NOW_TIME, 'sign' => md5($yuyue_id . C('AUTH_KEY') . NOW_TIME)));
            $token = 'zhe_yuyue_id_' . $yuyue_id;
            $file = tuQrCode($token, $url);
            $this->assign('file', $file);
            $this->assign('detail',$detail);
            $this->display();
        }
    }

    public function loaddata() {
        $Zhe = D('Zhe');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'audit' => 1, 'end_date' => array('EGT', TODAY));
        $linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        $cates = D('Shopcate')->fetchAll();
        $cate_id = (int) $this->_param('cate_id');
        if ($cate_id) {
            $catids = D('Shopcate')->getChildren($cate_id);
            if (!empty($catids)) {
                $map['cate_id'] = array('IN', $catids);
                $linkArr['cate_id'] = $cate_id;
            } else {
                $map['cate_id'] = $cate_id;
                $linkArr['cate_id'] = $cate_id;
            }
        }
        $this->assign('cate_id', $cate_id);

        $this->assign('business_id', $business_id);
        //增加星期几选择
        $week_id = (int) $this->_param('week_id');
        if ($week_id) {
            $linkArr['week_id'] = $week_id;
        }
        $this->assign('week_id', $week_id);
        //增加日期选择
        if($date_id = (int) $this->_param('date_id')){

            if ($date_id == 999) {
                $linkArr['date_id'] = $date_id;
                $this->assign('date_id', 999);
            }else{
                $linkArr['date_id'] = $date_id;
                $this->assign('date_id', $date_id);
            }
        }else{
            $date_id = date("j");
            $this->assign('date_id', $date_id);
            $linkArr['date_id'] = $date_id;
        }
        $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 's':
                $orderby = array('yuyue_num' => 'asc');
                $linkArr['order'] = $order;
                break;
            case 'p':
                $orderby = array('price' => 'asc');
                $linkArr['order'] = $order;
                break;
            case 't':
                $orderby = array('create_time' => 'asc');
                $linkArr['order'] = $order;
                break;
            case 'v':
                $orderby = array('views' => 'asc');
                $linkArr['order'] = $order;
                break;
            default:
                $orderby = array('yuyue_num' => 'desc', 'zhe_id' => 'desc');
                break;
        }
        $this->assign('order', $order);

        $count = $Zhe->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $list = $Zhe->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $cate_ids = array();
        foreach ($list as $k => $val) {
            $list[$k]['get_day_week'] = $Zhe->get_day_week($val['zhe_id']);
            $list[$k]['get_day_date'] = $Zhe->get_day_date($val['zhe_id']);
            if (!empty($week_id)) {
                $a = $Zhe->get_weed_id_unset($val['week_id'],$week_id);
                if(empty($a)){
                    unset($list[$k]);
                }
            }
            if ($date_id != 999) {
                $b = $Zhe->get_date_id_unset($val['date_id'],$date_id);
                if(empty($b)){
                    unset($list[$k]);
                }
            }
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        if ($cate_ids) {
            $this->assign('shop_cates', D('Shopcate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('cates', $cates);
        $this->assign('page', $show);
        $this->assign('linkArr', $linkArr);
        $this->display();
    }

    public function detail($zhe_id) {
        $zhe_id = (int) $zhe_id;
        $Zhe = D('Zhe');
        if (!$detail = $Zhe->find($zhe_id)) {
            $this->error('该五折卡项目不存在');
            die;
        }
        $Zhe->updateCount($zhe_id, 'views');//更新浏览量
        $this->assign('week_ids', $week_ids = explode(',', $detail['week_id']));
        $this->assign('date_ids', $date_ids = explode(',', $detail['date_id']));
        $this->assign('shops', D('Shop')->find($detail['shop_id']));
        $this->assign('check_user_zhe',$check_user_zhe =  D('Zhe')->check_user_zhe($this->uid));
        $this->assign('detail', $detail);
        $this->display();
    }

    //五折卡开通
    public function open($zhe_id) {
        if (empty($this->uid)) {
            $this->error("请登录后购买" , U('passport/login'));die;
        }
        $zhe_id = (int) $zhe_id;
        $Zhe = D('Zhe');
        if (!$detail = $Zhe->find($zhe_id)) {
            $this->error('该五折卡项目不存在');die;
        }
        $this->assign('detail', $detail);
        $this->display();
    }
    //五折卡预约
    public function yuyue($zhe_id) {
        if (empty($this->uid)) {
            $this->tuMsg("请登录后预约" , U('passport/login'));die;
        }
        $zhe_id = (int) $zhe_id;
        $obj = D('Zhe');
        if (!$detail = $obj->find($zhe_id)) {
            $this->tuMsg('该五折卡项目不存在');
            die;
        }
        if (!D('Zheyuyue')->check_yuyue_time($zhe_id,$this->uid)){//判断很多错误问题
            $this->tuMsg(D('Zheyuyue')->getError());
        }
        $code = D('Zheyuyue')->get_zhe_yuyue_code();
        $data = array();
        $data['city_id'] = $this->city_id;
        $data['zhe_id'] = $zhe_id;
        $data['user_id'] = $this->uid;
        $data['mobile'] = $this->member['mobile'];
        $data['shop_id'] = $detail['shop_id'];
        $data['is_used'] = 0;
        $data['code'] = $code;
        $data['create_time'] =	time();
        $data['create_ip'] = get_client_ip();
        if($yuyue_id = D('Zheyuyue')->add($data)){
           // D('Sms')->sms_zhe_yuyue_user($yuyue_id);//预约通知用户
           // D('Sms')->sms_zhe_yuyue_shop($yuyue_id);//预约通知商家
            $obj->updateCount($zhe_id, 'yuyue_num');
            $this->tuMsg('恭喜您预约成功',U('user/member/zhe_yuyue'));
        }else {
            $this->tuMsg('预约失败');
        }
    }


    //五折卡购买直接支付页面
    public function buy(){
        if (empty($this->uid)) {
            $this->tuMsg("请登录后购买" , U('passport/login'));die;
        }
        $obj = D('Zheorder');
        if (!$obj->Check_Zhe_Order_User_Buy($this->uid)) {
            $this->tuMsg($obj->getError());
        }
        $zhe_id = (int) $this->_get('zhe_id');
        $type = (int) $this->_post('type');
        if (empty($type)) {
            $this->tuMsg('请选择五折卡类型');
        }
        $need_pay = $obj->get_zhe_need_pay($type);
        $number = $obj->get_zhe_number($this->uid);
        $data = array();
        $data['city_id'] = $this->city_id;
        $data['zhe_id'] = $zhe_id;
        $data['type'] = $type;
        $data['user_id'] = $this->uid;
        $data['status'] = 0;
        $data['need_pay'] = $need_pay;
        $data['number'] = $number;
        $data['create_time'] =	time();
        $data['create_ip'] = get_client_ip();
        if($order_id = $obj->add($data)){
            $this->tuMsg('恭喜您下单成功，正在为您跳转付款页面',U('zhe/pay',array('order_id'=>$order_id)));
        }else {
            $this->tuMsg('创建订单失败，请稍后再试试');
        }
        $this->display();
    }

    //五折卡购买直接支付页面
    public function pay(){
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Zheorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $this->assign('order', $order);
        $this->assign('type', crowd);
        $this->assign('payment', D('Payment')->getPaymentscode(true));
        $this->display();
    }
    //去付款
    public function pay2(){
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Zheorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
            die;
        }
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
        if ($code == 'wait') {
            $this->tuMsg('暂不支持货到付款，请重新选择支付方式');
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->tuMsg('该支付方式不存在，请稍后再试试');
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('appoint', $order_id);//查找日志
            $need_pay = $order['need_pay'];//再更新防止篡改支付日志
            if (empty($logs)) {//更新
                $logs = array(
                    'type' => 'zhe',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $need_pay,
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $need_pay;
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            if (false == D('Zheorder')->updateCount_buy_num($order_id)) {//更新什么什么的
                $this->tuMsg('更新购买信息出错');
            }else{
                $this->tuMsg('选择支付方式成功！下面请进行支付', U('level/payment',array('log_id' => $logs['log_id'])));
                //$this->tuMsg('选择支付方式成功！下面请进行支付', U('payment/payment',array('log_id' => $logs['log_id'])));
            }
        }
    }
    //购买日志
    public function zhelogs(){
        $this->assign('nextpage', LinkTo('zhe/zheloaddata', array('t' => NOW_TIME,'p' => '0000')));
        $this->display();
    }
    //购买日志
    public function zheloaddata(){
        $obj = D('Paymentlogs');
        import('ORG.Util.Page');
        $map = array('is_paid'=>'1','type'=>'zhe','user_id' => $this->uid);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 8);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}

