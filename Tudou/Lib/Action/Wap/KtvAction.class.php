<?php
class KtvAction extends CommonAction {
    protected $types = array();
    public function _initialize() {
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['ktv'])){
			$this->error('KTV功能已关闭');die;
		}
        $this->assign('dates',  $dates = D('Ktv')->getKtvDate());
		$this->assign('day',  $day = date("j"));
    }
	//新版首页
    public function index(){
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;
		
        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $linkArr['area_id'] = $area_id;
		
        $business_id = (int) $this->_param('business_id');
        $this->assign('business_id', $business_id);
        $linkArr['business_id'] = $business_id;
        
        $date_id = (int) $this->_param('date_id');
        $this->assign('date_id', $date_id);
        $linkArr['date_id'] = $date_id;
		
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;
		
        $this->assign('nextpage', LinkTo('ktv/loaddata', $linkArr,array('t'=>NOW_TIME,'p' => '0000')));
		$this->assign('linkArr', $linkArr);
        $this->display();
    }
    
    public function loaddata() {
        $obj = M('Ktv');
        import('ORG.Util.Page');
        $map = array('audit' =>1,'closed' => 0);
		$linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		$area_id = (int) $this->_param('area_id');
        if ($area_id) {
            $map['area_id'] = $area_id;
            $linkArr['area_id'] = $area_id;
        }
        $this->assign('area_id', $area_id);
		
        $business_id = (int) $this->_param('business_id');
        if ($business_id) {
            $map['business_id'] = $business_id;
            $linkArr['business_id'] = $business_id;
        }
        $this->assign('business_id', $business_id);
		
		$date_id = (int) $this->_param('date_id');
		if ($date_id) {
			$linkArr['week_id'] = $date_id;
		}
		$this->assign('date_id', $date_id);
		
		
		$order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case '1':
                $orderby = array('orderby' => 'asc');
                $linkArr['order'] = $order;
                break;
            case '2':
                $orderby = array('orders_num' => 'asc');
                $linkArr['order'] = $order;
                break;
            case '3':
                $orderby = array('views' => 'asc');
                $linkArr['order'] = $order;
                break;
            case '4':
                $orderby = array('create_time' => 'asc');
                $linkArr['order'] = $order;
                break;
            default:
                $orderby = array('orderby' => 'desc', 'create_time' => 'desc');
                break;
        }
		$this->assign('order', $order);
        $count = $obj->where($map)->count();
        $Page  = new Page($count,10);
        $show = $Page->show();
        
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order('orderby desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        foreach($list as $k => $val){
             $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
             if($room = D('KtvRoom')->where(array('ktv_id'=>$val['ktv_id']))->find()){
                 $list[$k]['room'] = $room;
             }
			 if ($date_id) {
			    $b = D('Ktv')->get_date_id_unset($val['date_id'],$date_id);
				if(empty($b)){
				  	unset($list[$k]);
				 }
            }
        }
        $this->assign('list',$list);
        $this->assign('page',$show);
		$this->assign('linkArr', $linkArr);
        $this->display(); 
    }
    
    

    public function detail($ktv_id){
        $obj = D('Ktv');
		$obj->updateCount($ktv_id, 'views');//增加浏览量
        if(!$ktv_id = (int)$ktv_id){
            $this->error('该Ktv不存在');
        }elseif(!$detail = $obj->where(array('ktv_id'=>$ktv_id))->find()){
            $this->error('该Ktv不存在');
        }elseif($detail['closed'] == 1||$detail['audit'] == 0){
            $this->error('该Ktv已删除或未通过审核');
        }else{
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if (empty($lat) || empty($lng)) {
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $score = (int) D('KtvComment')->where(array('ktv_id' => $ktv_id))->avg('score');
            if ($score == 0) {
                $score = 5;
            }
            $count = D('KtvComment')->where(array('ktv_id' => $ktv_id))->count();
            $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);
            $room = D('KtvRoom')->where(array('ktv_id'=>$detail['ktv_id']))->select();
			$this->assign('room',$room = D('KtvRoom')->where(array('ktv_id'=>$detail['ktv_id']))->select());
			$this->assign('date_ids', $date_ids = explode(',', $detail['date_id']));
            $this->assign('detail',$detail);
            $this->assign('count',$count);
            $this->assign('score',$score);
            $this->display();
        }
    }

    
    public function order($ktv_id,$room_id){
        if(!$ktv_id){
            $this->error('KTV错误!');
        }elseif(!$ktv = D('Ktv')->where(array( 'ktv_id'=>$ktv_id))->find()){
            $this->error('房间不存在!');
        }elseif(!$room_id){
            $this->error('房间没有选择!');
        }elseif(!$res = D('KtvRoom')->where(array('room_id'=>$room_id))->find()){
            $this->error('房间不存在!');
        }else{
            $this->assign('room',$room = D('KtvRoom')->where(array('ktv_id'=>$ktv_id))->select());
			$this->assign('date_ids', $date_ids = explode(',', $ktv['date_id']));
			$this->assign('room_id',$room_id);
            $this->assign('ktv',$ktv);
            $this->display();
        }
    }
	public function YuyueDate(){
		$obj = D('KtvOrder');
        if ($this->isPost()) {
			$gotime = strtotime($this->_post('gotime'));
            $ktv_id = $this->_post('ktv_id');
			if(false == $obj->checkYuyueDate($gotime,$ktv_id ,$this->uid)){//判断当前时间是否支持预约
				 $this->ajaxReturn(array('status' => 'error', 'msg' =>$obj->getError()));
			}else{
				$this->ajaxReturn(array('status' => 'success', 'msg' => '当前时间可预约'));
			}
            $this->ajaxReturn(array('status' => 'error', 'msg' => '非法错误'));
        }
	}
    
    public function orderCreate($ktv_id){
        if (empty($this->uid)) {
            $this->tuMsg('您还没有登录',U('passport/login'));
            die;
        }
        if($this->ispost()){

            $data = I('data');
            $gotime = I('gotime',0,'trim');
            $data['gotime'] = strtotime(trim($data['gotime']));
            $data['name'] = htmlspecialchars(trim($data['name']));
            $data['mobile'] = trim($data['tel']);
            $data['room_id'] = intval(trim($data['room_id']));
            $data['note'] = htmlspecialchars(trim($data['note']));
            if(!$data['gotime']){
                $this->tuMsg('没有选择时间');
            }else if(!$data['name']){
                $this->tuMsg('没有填写联系人');
            }else if(!$data['mobile'] || !isMobile($data['mobile'])){
                $this->tuMsg('手机号码不正确！');
            }else if(!$data['room_id']) {
                $this->tuMsg('没有选择房间');
            }else if(!$data['note']){
                $this->tuMsg('请填写备注');
            }else if($data['note']=='填写备注'){
                $this->tuMsg('请填写备注');
            }

            $room_id = (int) $this->_get('room_id');

            $obj = D('KtvOrder');
				$room = D('KtvRoom')->find($data['room_id']);

				$ktv = D('Ktv')->where(array('ktv_id' =>$ktv_id))->find();
       
				if(false == $obj->checkYuyueDate($data['gotime'],$room['ktv_id'],$this->uid)){//判断当前时间是否支持预约
					$this->tuMsg($obj->getError());	  
				}
				//当天预约人次选择
				$count  = $obj->roomTomayNum($data['room_id']);
				if($count >= $room['num']){
					$this->tuMsg('当前房间限制每天预约'.$room['num'].'人次，请选择其他房间');
				}
            //查询用户是否领取了优惠劵
            $coupon=D('Coupondownload')->where(['shop_id'=>$ktv['shop_id'],'user_id'=>$this->uid,'is_used'=>0])->find();
            if(!empty($coupon) && $room['price'] >= $coupon['full_price']){
                $ypuhui=D('Coupon')->where(['coupon_id'=>$coupon['coupon_id']])->find();
                $full_price=$ypuhui['reduce_price'];
                $coupon_id=$ypuhui['coupon_id'];
                $need_pay=$room['price']-$full_price;
            }else{
                $full_price=0;
                $coupon_id=0;
                $need_pay=$room['price'];
            }
			
				$data['order_number'] = $obj->getOrderNumber();
				$data['shop_id'] = $ktv['shop_id'];
                $data['user_id'] = $this->uid;
                $data['ktv_id'] =$room['ktv_id'];
                $data['price'] = $need_pay;
                $data['coupun_id']=$coupon_id;
                $data['coupun_money']=$full_price;
                $data['jiesuan_price'] = $room['jiesuan_price'];
				$data['code'] = $obj->getCode();
                $data['create_time'] = time();
                $data['create_ip'] = get_client_ip();

                if($order_id = $obj->add($data)){
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 8,$status = 0);
                    $this->tuMsg('下单成功',U('ktv/pay',array('order_id'=>$order_id)));
                }else{
                    $this->tuMsg('下单失败!');
                }
            }

        
    }
	//支付页面
    public function pay(){
        if (empty($this->uid)) {
            $this->error('您还没有登录',U('passport/login'));
            die;
        }

        $order_id = (int) $this->_get('order_id');
        $order = D('KtvOrder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $room = D('KtvRoom')->find($order['room_id']);
        if (!$room) {
            $this->error('该套餐不存在');
            die;
        }
        $this->assign('useEnvelope',D('KtvOrder')->GetuseEnvelope($this->uid,$order['shop_id']));
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('room', $room);
        $this->assign('order', $order);
        $this->display();
    }
	//提交付款
    public function pay2(){
        if (empty($this->uid)) {
           $this->tuMsg('您还没有登录',U('passport/login'));
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('KtvOrder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
            die;
        }
		$y = date("Y");
		//获取当天的月份
		$m = date("m");
		//获取当天的号数
		$d = date("d");
		$todayTime= mktime(0,0,0,$m,$d,$y)+86400-1;
		
		if($order['status'] == 2 || ($todayTime >= ($order['gotime']+86400))){
			$this->tuMsg('该订单已失效无法次支付');
		}
		
        if (!$code = $this->_post('code')) {
            $this->tuMsg('请选择支付方式');
        }
        
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->tuMsg('该支付方式不存在');
        }
        $room = D('KtvRoom')->find($order['room_id']);
        if (!$room) {
            $this->error('该套餐不存在');
            die;
        }


        if($this->ispost()) {
            $money = I('post.money');
            $hongbao_id=I('post.envelope');
            $hongbao_money=I('post.envelope_money');
            //var_dump($hongbao_id);die;
            $mm = D('KtvOrder')->where(array('order_id' => $order_id))
                ->save(array('price' => $money, 'envelope_id' => $hongbao_id,'envelope_money'=>$hongbao_money));

            $need_pay=$money;
            $logs = D('Paymentlogs')->getLogsByOrderId('ktv', $order_id);
            if (empty($logs)) {
                $logs = array(
                    'type' => 'ktv',
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

            //判断是否使用红包
           if(!empty($hongbao_id)){
               $this->hongbaos($this->uid,$order_id,$hongbao_id);
           }
            //如果退款时间不为空，就限制退款时间
            $config = D('Setting')->fetchAll();
            if(!empty($config['complaint']['ktv_time'])){
                $times=$config['complaint']['ktv_time'];
                $now = date('Y-m-d H:i:s',time());
                $end_time=date("Y-m-d H:i:s",strtotime('+'.$times.'hours',strtotime($now)));
                $end=strtotime($end_time);
                D('KtvOrder')->where(array('order_id'=>$order_id))->save(array('end_time'=>$end));
            }
            $this->tuMsg('选择支付方式成功！下面请进行支付', U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }
    
    /**
     * KTV点评
     * @author pingdan <[<email address>]>
     * @return [type] [description]
     */
    public function comment(){
        $ktv_id = (int) $this->_get('ktv_id');
        if(!($detail = D('Ktv')->where(array('ktv_id' => $ktv_id))->find())){
            $this->error('没有该商家');
            die;
        }
        if($detail['closed']){
            $this->error('该商家已经被删除');
            die;
        }

        $this->assign('next', LinkTo('ktv/commentloading', $linkArr, array('ktv_id' => $ktv_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->display();
    }

    /**
     * 点评加载数据
     * @author pingdan <[<email address>]>
     * @return [type] [description]
     */
    public function commentloading(){
        $ktv_id = (int) $this->_get('ktv_id');
        
        $detail = D('KtvComment')->where(array('ktv_id' => $ktv_id))->find();

        if(!$detail){
            die('0');
        }
        if($detail['closed']){
            die('0');
        }
        $ktv_comment = D('KtvComment');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'ktv_id' => $ktv_id);
        $count = $ktv_comment->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $ktv_comment->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $orders_ids = array();
        foreach($list as $k => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $orders_ids[$val['order_id']] = $val['order_id'];
        }

        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($orders_ids)){
            $this->assign('pics', D('Ktvuploadpics')->where(array('order_id' => array('IN', $orders_ids), 'type' => 1))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('detail', $detail);
        $this->display();
    }

    //点评详情
    public function img(){
        $comment_id = (int) $this->_get('comment_id');
        if(!($detail = D('KtvComment')->find($comment_id))){

            $this->error('没有该点评');
            die;
        }
        if($detail['closed']){
            $this->error('该点评已经被删除');
            die;
        }
        $list =  D('Ktvuploadpics')->where(array('order_id' => $detail['order_id'], 'type' => 1))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }
   
}
