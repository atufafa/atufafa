<?php
class TuanAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        $this->assign('tuancates', $tuancates = D('Tuancate')->fetchAll());
        $cun=D('Zeroelementforward')->where(array('user_id'=>$this->uid,'colse'=>0))->find();
        $this->assign('conduct',$cun);
        $this->assign('jinxzhon',D('Pindan')->where(array('tuan_id'=>$cun['tuan_id']))->find());
    }
    
    public function index(){
        $time=time();
        $time_id=0;
        $time_arr=M('tuan_times')->order('times asc')->select();
        foreach($time_arr as $value){
            $start_time=strtotime(date('Y-m-d',time()).' '.$value['times']);
            $end_time=strtotime(date('Y-m-d',time()).' '.$value['end_time']);
            if($time>=$start_time  && $time<$end_time){
                $time_id=$value['id'];
                break;
            }
        }
        $goodscates = D('Goodscate')->where(array('parent_id'=>0,'cate_id'=>array('not in','179')))->select();
        $this->assign('goodscates',$goodscates);
        $this->assign('time_id',$time_id);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $cat = (int) $this->_param('cat');
        $this->assign('cat', $cat);
		$area = (int) $this->_param('area');
        $this->assign('area', $area);
		
		$shop_id = (int) $this->_param('shop_id');
        $this->assign('shop_id',$shop_id);
		
		
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('times',D('Tuantimes')->where(array('colse'=>0))->order('id asc')->select());
        $this->assign('order', $order);
        $this->assign('nextpage', LinkTo('tuan/loaddata', array('cat' => $cat, 'area' => $area, 'order' => $order, 'shop_id' => $shop_id,'t' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));
        $this->display();
    }

    public function loaddata(){
        $Tuan = D('Tuan');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
        }
        $cat = (int) $this->_param('cat');
        if($cat){
            $catids = D('Tuancate')->getChildren($cat);
            if(!empty($catids)){
                $map['cate_id'] = array('IN', $catids);
            }else{
                $map['cate_id'] = $cat;
            }
        }
        $area = (int) $this->_param('area');
        if($area){
            $map['area_id'] = $area;
        }
		
		$shop_id = (int) $this->_param('shop_id');
        if($shop_id){
            $map['shop_id'] = $shop_id;
        }
        $order = $this->_param('order', 'htmlspecialchars');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = '';
        switch ($order){
            case 3:
                $orderby = array('create_time' => 'desc');
                break;
            case 2:
                $orderby = array('orderby' => 'asc', 'tuan_id' => 'desc');
                break;
            default:
                $orderby = array('orderby' => 'asc','sold_num' => 'desc');
                break;
        }
        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Tuan->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['end_time'] = strtotime($val['end_date']) - NOW_TIME + 86400;
            $list[$k] = $val;
        }
        if($shop_ids){
            $shops = D('Shop')->itemsByIds($shop_ids);
            $ids = array();
            foreach ($shops as $k => $val){
                $shops[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
                $d = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
                $ids[$d][] = $k;
            }
            ksort($ids);
            $showshops = array();
            foreach($ids as $arr1){
                foreach ($arr1 as $val){
                    $showshops[$val] = $shops[$val];
                }
            }
            $this->assign('shops', $showshops);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        //图片循环
        $this->assign('imgs',D('Ad')->where(array('site_id'=>array('in','80,86,87,88,89,14'),'closed'=>0))->select());

        $this->display();
    }
	
    public function detail(){
        if (empty($this->uid)) {
            $this->tuMsg('请先登陆', U('passport/login'));
        }

        $tuan_id = (int) $this->_get('tuan_id');

        $tao_arr = D('Tuanmeal')->order(array('id' => 'asc'))->where(array('tuan_id' => $tuan_id))->select();
        $this->assign('tuan_id', $tuan_id);
        $this->assign('tao_arr', $tao_arr);

        if(!($detail = D('Tuan')->find($tuan_id))){
            $this->error('该抢购信息不存在');
            die;
        }
        if($detail['audit'] != 1){
            $this->error('该抢购信息还在审核中哦');
            die;
        }
        if($detail['closed']==1){
            $this->error('该抢购信息不存在');
            die;
        }

        $shops=D('Shop')->where(['shop_id'=>$detail['shop_id']])->find();

        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo=$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");

        $usergroup=$rs[0]['memberIdx'];
        $shopname=$re_shop[0]['memberIdx'];
        //聊天默认为好友
        $default=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        if(empty($default)){
            $addgroup=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($usergroup,'商城好友')");
        }
        $default2=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        $groupid=$default2[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default3=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$shopname and mygroupIdx=$groupid");
        if(empty($default3)){
            $addfriend=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid,$shopname)");

        }

        //商家加陌生人
        $default4=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        if(empty($default4)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($shopname,'商城好友')");
        }
        $default5=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        $groupid2=$default5[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default6=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$usergroup and mygroupIdx=$groupid2");
        if(empty($default6)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid2,$usergroup)");
        }

        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['title'].'--'.$detail['intro'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );

        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
        $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
        $this->assign('service_url', $service_url);



        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $detail = D('Tuan')->_format($detail);
        $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);
        $detail['end_time'] = strtotime($detail['end_date']) - NOW_TIME + 86400;
        $this->assign('detail', $detail);
        $shop_id = $detail['shop_id'];
        $shop = D('Shop')->find($shop_id);

        if($detail['is_tui'] ==1 && $shop['user_id'] != $this->uid){
            D('Tuan')->check_price($tuan_id);
        }

        $this->assign('tuans', D('Tuan')->where(array('audit' => 1, 'closed' => 0, 'shop_id' => $shop_id, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY), 'tuan_id' => array('NEQ', $tuan_id)))->limit(0, 5)->select());
        $pingnum = D('Tuandianping')->where(array('tuan_id' => $tuan_id))->count();
        $this->assign('pingnum', $pingnum);
        $score = (int) D('Tuandianping')->where(array('tuan_id' => $tuan_id))->avg('score');
        if($score == 0){
            $score = 5;
        }

        $filter_spec = $this->get_specs($tuan_id); //获取商品规格参数
        $this->assign('filter_spec',$filter_spec);

        $spec_goods_prices  = D('Tuanspecprice')->where("goods_id = $tuan_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        if($spec_goods_prices != null){
            $this->assign('spec_goods_price', json_encode($spec_goods_prices,true)); // 规格 对应 价格 库存表

        }

        $this->assign('score', $score);
        $this->assign('tuandetails', $tuandetails = D('Tuandetails')->find($tuan_id));
        $this->assign('shop', $shop);
        $this->assign('tuansids', $tuansids = $detail['cate_id']);
        $this->assign('thumb', $thumb = unserialize($detail['thumb']));
		$this->assign('tuan_favorites', $tuan_favorites = D('Tuanfavorites')->check($tuan_id, $this->uid));//检测自己是不是收
        $this->assign('detail',$detail);
        $this->display();
    }

    public function get_specs($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = D('Tuanspecprice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        // var_dump($keys);die;
        $filter_spec = array();
        if($keys){
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__tuan_spec AS a INNER JOIN __PREFIX__tuan_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = M()->db(2,'mysql://zgtianxin:sTXMbCGhCAxLNCNs@127.0.0.1:3306/zgtianxin')->query($sql);
            foreach($filter_spec2 as $key => $val){
                $filter_spec[$val['name']][] = array(
                    'item_id'=> $val['id'],
                    'item'=> $val['item'],
                );
            }
        }
        return $filter_spec;
    }

    //团购图片详情
    public function pic(){
        $tuan_id = (int) $this->_get('tuan_id');
        if(!($detail = D('Tuan')->find($tuan_id))){
            $this->error('没有该团购');
            die;
        }
        if($detail['closed']){
            $this->error('该团购已经被删除');
            die;
        }
        $thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
        $this->assign('detail', $detail);
        $this->display();
    }
   
   
    public function dianpingloading(){
        $tuan_id = (int) $this->_get('tuan_id');
        if(!($detail = D('Tuan')->find($tuan_id))){
            die('0');
        }
        if($detail['closed']){
            die('0');
        }
		
        $Tuandianping = D('Tuandianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'tuan_id' => $tuan_id, 'show_date' => array('ELT', TODAY));
        $count = $Tuandianping->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $orders_ids = array();
        foreach($list as $k => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $orders_ids[$val['order_id']] = $val['order_id'];
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($orders_ids)){
            $this->assign('pics', D('Tuandianpingpics')->where(array('order_id' => array('IN', $orders_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('detail', $detail);
        $this->display();
    }
	
	
	
	//点评详情
    public function img(){
        $dianping_id = (int) $this->_get('dianping_id');
        if(!($detail = D('Tuandianping')->where(array('dianping_id'=>$dianping_id))->find())){
            $this->error('没有该点评');
            die;
        }
        if($detail['closed']){
            $this->error('该点评已经被删除');
            die;
        }
        $list =  D('Tuandianpingpics')->where(array('order_id' =>$detail['order_id']))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }
	
	
    public function order(){
        if(!$this->uid){
            $this->ajaxReturn(array('status' =>'login'));
        }
		if(empty($this->member['mobile'])){
			$this->ajaxReturn(array('status' =>'mobile'));
		}
		$tuan_id = I('tuan_id', 0, 'trim,intval');
        if(!($detail = D('Tuan')->find($tuan_id))){
			$this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在','url'=>1));
        }
        if($detail['closed'] == 1 || $detail['end_date'] < TODAY){
			$this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经结束','url'=>1));
        }

        $addrs=D('Paddress')->where(array('default'=>1,'closed'=>0,'user_id'=>$this->uid))->find();

        if(empty($addrs)){
            $this->ajaxReturn(['status'=>'error','msg'=>'请在个人中心设置默认地址后再购买','url'=>U('wap/address/addrcat',array('type'=>'qiango','order_id'=>$tuan_id))]);
        }

        if($detail['is_reight']==1){
            $kuaidi=D('Pyunfei')->where(array('kuaidi_id'=>$detail['kuaidi_id'],'shop_id'=>$detail['shop_id']))->find();
            $yunfei=$kuaidi['shouzhong'];
        }else{
            $yunfei=0;
        }

		$num = I('num2',0,'trim,intval');
        $attr=I('attr_id');

        if($detail['banjia']==1 && $num>=2){
            $row=D('Tuanspecprice')->where(array('key'=>$attr,'goods_id'=>$tuan_id))->find();
            $banjias=$row['price']/2;
        }else{
            $banjias=0;
        }
        if(!empty($attr)){
            $row=D('Tuanspecprice')->where(array('key'=>$attr,'goods_id'=>$tuan_id))->find();
            $need_pays=$row['price']* $num - $banjias;

        }else{
            $need_pays=$detail['tuan_price']*$num;
        }
        if($num <= 0 || $num > 99){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请输入正确的购买数量','url'=>1));
        }
        if(false == D('Shop')->check_shop_user_id($detail['shop_id'],$this->uid)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '您不能购买自己的产品','url'=>1));
        }
		if($num > $detail['num']){
			$this->ajaxReturn(array('status' => 'error', 'msg' => '亲，您最多购买' . $detail['num'] . '份哦','url'=>1));
        }
		if($num <= 0 || $num > 99){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请输入正确的购买数量','url'=>1));
        }
        if(false == D('Shop')->check_shop_user_id($detail['shop_id'],$this->uid)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '您不能购买自己的产品','url'=>1));
        }
        if($num > $detail['xiangou'] && $detail['xiangou'] > 0){
			$this->ajaxReturn(array('status' => 'error', 'msg' => '亲，每人只能购买' . $detail['xiangou'] . '份哦','url'=>1));
        }
        if($detail['xiadan'] == 1){
            $where['user_id'] = $this->uid;
            $where['tuan_id'] = $tuan_id;
            $xdinfo = D('Tuanorder')->where($where)->order('order_id desc')->Field('order_id')->find();
            if($xdinfo){
				$this->ajaxReturn(array('status' => 'error', 'msg' => '该商品只允许购买一次','url'=>1));
            }
        }
        if($detail['xiangou'] > 0){
            $y = date('Y');
            $m = date('m');
            $d = date('d');
            $day_start = mktime(0, 0, 0, $m, $d, $y);
            $day_end = mktime(23, 59, 59, $m, $d, $y);
            $where['user_id'] = $this->uid;
            $where['tuan_id'] = $tuan_id;
            $xdinfo = D('Tuanorder')->where($where)->order('order_id desc')->Field('create_time,num')->select();
            $order_num = 0;
            foreach($xdinfo as $k => $val){
                if($val['create_time'] >= $day_start && $val['create_time'] <= $day_end){
                    $order_num += $val['num'] + $num;
                    if($order_num > $detail['xiangou']){
						$this->ajaxReturn(array('status' => 'error', 'msg' => '该商品每天每人限购' . $detail['xiangou'] . '份','url'=>1));
                    }
                }
            }
        }
        $yun=(float)$yunfei;
        $need=(float)$need_pays;
        $yinfu=$need + $yun;
        //var_dump($yinfu);die;
        $data = array(
			'tuan_id' => $tuan_id, 
			'num' => $num, 
			'user_id' => $this->uid, 
			'shop_id' => $detail['shop_id'], 
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip(), 
			'total_price' => $need,
			'mobile_fan' => $detail['mobile_fan'] * $num, 
			'need_pay' => $yinfu - $detail['mobile_fan'] * $num,
			'status' => 0,
			'addr_id'=>$addrs['id'],
			'is_mobile' => 1,
            'key'=>$attr,
            'key_name'=>$row['key_name'],
            'freight_price'=>$yunfei
		);
		
        if($order_id = D('Tuanorder')->add($data)){
			$this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜下单成功','order_id'=>$order_id));
        }else{
			$this->ajaxReturn(array('status' => 'error', 'msg' => '创建订单失败','url'=>1));
		}
    }
 
 
    public function pay(){
        if(empty($this->uid)){
            header('Location:' . U('passport/login'));
            die;
        }
		$order_id = I('order_id', 0, 'trim,intval');
        $order = D('Tuanorder')->find($order_id);//orderid就是tuan_order表的id
        if(empty($order)){
            $this->error('该订单不存在');
            die;
        }
		if($order['status'] != 0){
            $this->error('订单状态不正确');
            die;
        }
		if($order['user_id'] != $this->uid){
            $this->error('非法操作');
            die;
        }
        $Tuan = D('Tuan')->find($order['tuan_id']);
        if(empty($Tuan)){
            $this->error('该抢购不存在');
            die;
        }
		if($Tuan['closed'] == 1){
            $this->error('该抢购已删除');
            die;
        }
		if($Tuan['end_date'] < TODAY){
            $this->error('抢购已过期');
            die;
        }

        $defaultAddress = D('Paddress')->where(['user_id'=>$this->uid,'id'=>$order['addr_id']])->find();
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' =>'qian', 'order_id' => $order_id));
        $this -> assign('defaultAddress', $defaultAddress);
        $this -> assign('changeAddressUrl', $changeAddressUrl);



        $this->assign('use_integral', $Tuan['use_integral'] * $order['num']);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('tuan', $Tuan);
        $this->assign('order', $order);

        //判断是否有优惠 并计算最终需价格
        $userlevelArr = M('Users')->where(array('user_id'=>$this->uid))->field('rank_id')->find();
        $level = $userlevelArr['rank_id'];
        $lastPrice = floatval($order['need_pay']);
        if($level > 1){
            //普通会员 不减优惠
            $allFavoour = $Tuan['favour_price'] * $order['num'];
            $lastPrice = floatval($order['need_pay'] - $allFavoour);
        }

        $this->assign('lastPay',$lastPrice);
        $this->assign('level',$level);
        $this->assign('allFavoour',$allFavoour);
        $this->display();
    }
	
	
	
    public function tuan_mobile(){
        $this->mobile();
    }
    public function tuan_mobile2(){
        $this->mobile2();
    }
    public function tuan_sendsms(){
        $this->sendsms();
    }

    public function pay3(){
        if($this->ispost()){
            if(empty($this->uid)){
                $this->tuMsg('登录状态失效!', U('passport/login'));
            }
            $order_id = (int) $this->_get('order_id');
            $order = D('Tuanorder')->find($order_id);
            if(empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid){
                $this->tuMsg('该订单不存在');
            }
            if(!($code = $this->_post('code'))){
                $this->tuMsg('请选择支付方式');
            }
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->tuMsg('该支付方式不存在');
            }
            $order['need_pay'] = D('Tuanorder')->get_tuan_need_pay($order_id,$this->uid,2);//获取实际支付价格封装
            $logs = D('Paymentlogs')->getLogsByOrderId('tuan', $order_id);
            if(empty($logs)){
                $logs = array(
                    'type' => 'tuan',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $order['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            D('Weixintmpl')->weixin_notice_tuan_user($order_id,$this->uid,1);
            $this->tuMsg('订单设置完毕，即将进入付款', U('payment/payment', array('log_id'=>$logs['log_id'])),200);
        }

    }



	
    public function pay2(){
        if(empty($this->uid)){
            $this->tuMsg('登录状态失效!', U('passport/login'));
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Tuanorder')->find($order_id);
        if(empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid){
            $this->tuMsg('该订单不存在');
        }
        if(!($code = $this->_post('code'))){
            $this->tuMsg('请选择支付方式');
        }
        $mobile = D('Users')->where(array('user_id' => $this->uid))->getField('mobile');
        if(!$mobile){
            $this->tuMsg('请先绑定手机号码再提交');
        }
		
        $pay_mode = '在线支付';
		
        if($code == 'wait'){
            $pay_mode = '货到支付';
            $codes = array();
            $obj = D('Tuancode');
            if (D('Tuanorder')->save(array('order_id' => $order_id,'status' => '-1'))){
                //更新成到店付的状态
                $tuan = D('Tuan')->find($order['tuan_id']);
                for ($i = 0; $i < $order['num']; $i++) {
                    $local = $obj->getCode();
                    $insert = array(
						'user_id' => $this->uid, 
						'shop_id' => $tuan['shop_id'], 
						'order_id' => $order['order_id'], 
						'tuan_id' => $order['tuan_id'], 
						'code' => $local, 
						'price' => 0, 
						'real_money' => 0, 
						'real_integral' => 0, 
						'fail_date' => $tuan['fail_date'], 
						'settlement_price' => 0, 
						'create_time' => NOW_TIME, 
						'create_ip' => get_client_ip()
					);
                    $codes[] = $local;
                    $obj->add($insert);
                }
                D('Tuan')->updateCount($tuan['tuan_id'], 'sold_num');//更新卖出产品
				D('Sms')->sms_tuan_user($this->uid,$order['order_id']);//团购商品通知用户
                D('Users')->prestige($this->uid, 'tuan');
                D('Sms')->tuanTZshop($tuan['shop_id']);
       			D('Weixintmpl')->weixin_notice_tuan_user($order_id,$this->uid,0);
                $this->tuMsg('恭喜您下单成功', U('user/tuan/index'));
            }else{
                $this->tuMsg('您已经设置过该抢购为到店付了');
            }
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->tuMsg('该支付方式不存在');
            }
			
//			$order['need_pay'] = D('Tuanorder')->get_tuan_need_pay($order_id,$this->uid,2);//获取实际支付价格封装
            $order['need_pay'] = $this->_post('newpay');
            $logs = D('Paymentlogs')->getLogsByOrderId('tuan', $order_id);
            if(empty($logs)){
                $logs = array(
					'type' => 'tuan', 
					'user_id' => $this->uid, 
					'order_id' => $order_id, 
					'code' => $code, 
					'need_pay' => $order['need_pay'], 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'is_paid' => 0,
                    'buy_type'=>'online_buy'//在线抢购
				);
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            $codestr = join(',', $codes);
            D('Weixintmpl')->weixin_notice_tuan_user($order_id,$this->uid,1);
            $this->tuMsg('订单设置完毕，即将进入付款', U('payment/payment', array('log_id'=>$logs['log_id'])),200);
        }
    }


	
	//抢购收藏
	public function favorites(){
        if(empty($this->uid)){
            $this->tuMsg('登录状态失效!', U('passport/login'));
            die;
        }
        $tuan_id = (int) $this->_get('tuan_id');
        if(!($detail = D('Tuan')->find($tuan_id))){
            $this->tuMsg('没有该抢购');
        }
        if($detail['closed']){
            $this->tuMsg('该抢购已经被删除');
        }
        if(D('Tuanfavorites')->check($tuan_id, $this->uid)){
            $this->tuMsg('您已经收藏过了');
        }
        $data = array('tuan_id' => $tuan_id, 'user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
        if(D('Tuanfavorites')->add($data)){
            $this->tuMsg('恭喜您收藏成功', U('tuan/detail', array('tuan_id' => $tuan_id)));
        }
        $this->tuMsg('收藏失败');
    }
    //分销商品入口      -------新增
    public function fxsp(){
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $cat = (int) $this->_param('cat');
        $this->assign('cat', $cat);
        $area = (int) $this->_param('area');
        $this->assign('area', $area);
        
        $shop_id = (int) $this->_param('shop_id');
        $this->assign('shop_id',$shop_id);
        $this->assign('tuancate2',D('Goodscate')->where(array('parent_id'=>0))->order('orderby asc ')->select());
        //var_dump($tuancate2);
       // var_dump($tuancates);die;
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $this->assign('nextpage', LinkTo('tuan/tuan_loaddata', array('cat' => $cat, 'area' => $area, 'order' => $order, 'shop_id' => $shop_id,'t' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));
        $this->display();
    }
    public function tuan_loaddata(){
        $Tuan = D('Pindan');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
        }
        $cat = (int) $this->_param('cat');
        if($cat){
            $catids = D('Goodscate')->getChildren($cat);
            if(!empty($catids)){
                $map['cate_id'] = array('IN', $catids);
            }else{
                $map['cate_id'] = $cat;
            }
        }
        $area = (int) $this->_param('area');
        if($area){
            $map['area_id'] = $area;
        }
        
        $shop_id = (int) $this->_param('shop_id');
        if($shop_id){
            $map['shop_id'] = $shop_id;
        }
        $order = $this->_param('order', 'htmlspecialchars');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = '';
        switch ($order){
            case 3:
                $orderby = array('create_time' => 'desc');
                break;
            case 2:
                $orderby = array('orderby' => 'asc', 'tuan_id' => 'desc');
                break;
            default:
                $orderby = array('orderby' => 'asc','sold_num' => 'desc');
                break;
        }
        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Tuan->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['end_time'] = strtotime($val['end_date']) - NOW_TIME + 86400;
            $list[$k] = $val;
        }
        if($shop_ids){
            $shops = D('Shop')->itemsByIds($shop_ids);
            $ids = array();
            foreach ($shops as $k => $val){
                $shops[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
                $d = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
                $ids[$d][] = $k;
            }
            ksort($ids);
            $showshops = array();
            foreach($ids as $arr1){
                foreach ($arr1 as $val){
                    $showshops[$val] = $shops[$val];
                }
            }
            $this->assign('shops', $showshops);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);


        //图片循环
        $this->assign('imgs',D('Ad')->where(array('site_id'=>array('in','80,86,87,88,89,14'),'closed'=>0))->select());

        $this->display();
    }
    //分销商品详情      --------新增部分
    public function tuan_detail($tuan_id)
    {
        if (empty($this->uid)) {
            $this->tuMsg('请先登陆', U('passport/login'));
        }

         $tuan_id = (int) $this->_get('tuan_id');
        $tao_arr = D('Tuanmeal')->order(array('id' => 'asc'))->where(array('tuan_id' => $tuan_id))->select();
        $this->assign('tuan_id', $tuan_id);
        $this->assign('tao_arr', $tao_arr);
        if(empty($tuan_id)){
            $this->error('该商品信息不存在');
            die;
        }
        if(!($detail = D('Pindan')->find($tuan_id))){
            $this->error('该商品信息不存在');
            die;
        }
        $shops=D('Shop')->where(['shop_id'=>$detail['shop_id']])->find();
        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo=$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");

        $usergroup=$rs[0]['memberIdx'];
        $shopname=$re_shop[0]['memberIdx'];
        //聊天默认为好友
        //陌生人加商家
        $default=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        if(empty($default)){
            $addgroup=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($usergroup,'商城好友')");
        }
        $default2=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        $groupid=$default2[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default3=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$shopname and mygroupIdx=$groupid");
        if(empty($default3)){
            $addfriend=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid,$shopname)");

        }

        //商家加陌生人
        $default4=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        if(empty($default4)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($shopname,'商城好友')");
        }
        $default5=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        $groupid2=$default5[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default6=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$usergroup and mygroupIdx=$groupid2");
        if(empty($default6)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid2,$usergroup)");
        }


        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['title'].'--'.$detail['intro'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );

        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
        $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
        $this->assign('service_url', $service_url);



        if($detail['audit'] != 1){
            $this->error('该商品信息还在审核中哦');
            die;
        }
        if($detail['closed']==1){
            $this->error('该商品信息不存在');
            die;
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $detail = D('Pindan')->_format($detail);
        $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);
        $detail['end_time'] = strtotime($detail['end_date']) - NOW_TIME + 86400;
        $this->assign('detail', $detail);
        $shop_id = $detail['shop_id'];
        $shop = D('Shop')->find($shop_id);
        if($detail['is_tui'] ==1 && $shop['user_id'] != $this->uid){
            D('Pindan')->check_price($tuan_id);
        }

        $this->assign('tuans', D('Pindan')->where(array('audit' => 1, 'closed' => 0, 'shop_id' => $shop_id, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY), 'tuan_id' => array('NEQ', $tuan_id)))->limit(0, 5)->select());
        $pingnum = D('Tuandianping')->where(array('tuan_id' => $tuan_id))->count();
        $this->assign('pingnum', $pingnum);
        $score = (int) D('Tuandianping')->where(array('tuan_id' => $tuan_id))->avg('score');
        if($score == 0){
            $score = 5;
        }
        //判断当前是否有自发点赞的
       // $this->assign('cunzai',D('Zeroelementforward')->where(array('tuan_id'=>$tuan_id))->find());
        $this->assign('score', $score);
        $this->assign('tuandetails', $tuandetails = D('Pindandetails')->find($tuan_id));
        $this->assign('shop', $shop);
        $this->assign('tuansids', $tuansids = $detail['cate_id']);
        $this->assign('thumb', $thumb = unserialize($detail['thumb']));
        $filter_spec = $this->get_spec($tuan_id); //获取商品规格参数
        $this->assign('filter_spec',$filter_spec);
        $this->assign('user',$user=$this->uid);
        //var_dump($this->uid);
        $spec_goods_price  = D('Zeroelementprice')->where("goods_id = $tuan_id")->getField("key,price,yun_price,store_count"); // 规格 对应 价格 库存表
       // var_dump($spec_goods_price);
        if($spec_goods_price != null){
            $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        }
        $dianzan=D('Zeroelementforward')->where(array('user_id'=>$this->uid,'tuan_id'=>$tuan_id,'shop_id'=>$detail['shop_id'],'colse'=>0))->find();//查看是否存在已转发
        $this->assign('dianan',$dianzan);
        $this->assign('goods_attribute',$goods_attribute = D('Zeroelementattribute')->getField('attr_id,attr_name'));//属性值
        $this->assign('goods_attr_list',$goods_attr_list = D('zeroelementattr')->where(array('goods_id'=> $tuan_id))->select());//属性列表
        //内容滚动
        $dz=D('Zeroelementforward')->where(['colse'=>0])->select();
        foreach ($dz as $value){
            $user_id[]=$value['user_id'];
        }
        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('gundong',$dz);
        $this->assign('tuan_favorites', $tuan_favorites = D('Tuanfavorites')->check($tuan_id, $this->uid));//检测自己是不是收
        //类似商品
        $this->assign('leisi',D('Pindan')->where(array('cate_id'=>$detail['cate_id'],'audit'=>1))->select());
        $this->display();
    }

    public function get_spec($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = D('Zeroelementprice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
       // var_dump($keys);die;
        $filter_spec = array();
        if($keys){
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__zero_element_spec AS a INNER JOIN __PREFIX__zero_element_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = M()->db(2,'mysql://zgtianxin:sTXMbCGhCAxLNCNs@127.0.0.1:3306/zgtianxin')->query($sql);
            foreach($filter_spec2 as $key => $val){
                $filter_spec[$val['name']][] = array(
                    'item_id'=> $val['id'],
                    'item'=> $val['item'],
                );
            }
        }
        return $filter_spec;
    }

    //分享
    public function fenxian(){
        $config=D('Setting')->fetchAll();
        $obj=D('Zeroelementforward');
        if($this->ispost()){
            $tuan_id=I('post.tuan_id');
            $shop=I('post.shop_id');
            $money1=(int) $config['zero']['money_one'];
            $num1=$config['zero']['num_one'];

            $money2=(int) $config['zero']['money_two'];
            $num2=$config['zero']['num_two'];

            $money3=(int) $config['zero']['money_three'];
            $num3=$config['zero']['num_three'];

            $money4= (int) $config['zero']['money_fore'];
            $num4=$config['zero']['num_fore'];

            $num5=$config['zero']['num_five'];

            $money=D('Pindan')->where(array('tuan_id'=>$tuan_id))->find();
            $shopmoney=$money['price'];

            if($shopmoney<=$money1){
                $nums=$num1;
            }elseif($shopmoney>$money1 && $shopmoney<=$money2){
                $nums=$num2;
            }elseif($shopmoney>$money2 && $shopmoney<=$money3){
                $nums=$num3;
            }elseif($shopmoney>$money3 && $shopmoney<=$money4){
                $nums=$num4;
            }else{
                $nums=$num5;
            }
            $set= (int) $config['zero']['times'];
            $nowtime=date('Y:m:d H:i:m',time());
            $bg=date("Y-m-d H:i:s",strtotime('+'.$set.'hours',strtotime($nowtime)));
            $endtime=strtotime($bg);
            $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'tuan_id'=>$tuan_id,'colse'=>0))->find();
            if(empty($cunzai)){
                $data=array();
                $data['tuan_id']=$tuan_id;
                $data['shop_id']=$shop;
                $data['user_id']=$this->uid;
                $data['create_time']=NOW_TIME;
                $data['create_ip']=get_client_ip();
                $data['end_time']=$endtime;
                $data['nums']=$nums;
                $obj->add($data);
            }
        }

    }

    //好友点赞
    public function dianzan(){
        $obj=D('Zeroforwardlist');
        if($this->ispost()){
            $tuan_id=I('post.tuan_id');
            $user_id=I('post.user_id');
            $row2=$obj->where(array('user_id1'=>$this->uid,'user_id'=>$user_id,'tuan_id'=>$tuan_id))->find();
            if(!empty($row2)){
                echoJson(['status'=>0,'msg'=>'您已经帮好友点过赞了','data'=>'']);
            }
            $config=D('Setting')->fetchAll();
            $jifen=rand($config['zero']['jifen_one'],$config['zero']['jifen_two']);

            $row=D('Zeroelementforward')->where(array('tuan_id'=>$tuan_id,'user_id'=>$user_id,'colse'=>0))->find();
            if(!empty($row)){
                D('Zeroelementforward')->where(array('tuan_id'=>$tuan_id,'user_id'=>$user_id))->setInc('num');
                D('Users')->addIntegral($this->uid,$jifen,'点赞获得积分'.$jifen);
                $arr=array();
                $arr['user_id']=$user_id;
                $arr['tuan_id']=$tuan_id;
                $arr['user_id1']=$this->uid;
                $arr['create_time']=NOW_TIME;
                $arr['create_ip']=get_client_ip();
                 if($obj->add($arr)){
                     echoJson(['status'=>1,'msg'=>'点赞成功,恭喜您获得积分：'.$jifen,'data'=>'']);
                  }
            }
        }
    }

    //更多分享
    public function more(){
        $this->assign('nextpages', LinkTo('tuan/moreloaddata'));
        $this->display();
    }

    public function moreloaddata(){
        $Tuan = D('Zeroelementforward');
        import('ORG.Util.Page');
        $map = array('colse' => 0,'user_id'=>$this->uid);

        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Tuan->where($map)->order('create_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $tuan_ids= $shop_id=array();
        foreach ($list as $value){
            $tuan_ids[]=$value['tuan_id'];
            $shop_id[]=$value['shop_id'];
        }
        $this->assign('tuan_id',$tuan_ids);
        $this->assign('shop_id',$shop_id);
        $this->assign('tuan',D('Pindan')->itemsByIds($tuan_ids));
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //分销商品结算部分       --------新增部分
    
    public function tuan_pay($order_id)
    {
        if(empty($this->uid)){
            header('Location:' . U('passport/login'));
            die;
        }
        $order_id = I('order_id', 0, 'trim,intval');
        $order = D('Pindanorder')->find($order_id);
        if(empty($order)){
            $this->error('该订单不存在');
            die;
        }
        if($order['status'] != 0){
            $this->error('订单状态不正确');
            die;
        }
        if($order['user_id'] != $this->uid){
            $this->error('非法操作');
            die;
        }
        $Tuan = D('Pindan')->find($order['tuan_id']);
        if(empty($Tuan)){
            $this->error('该商品不存在');
            die;
        }
        if($Tuan['closed'] == 1){
            $this->error('该商品已删除');
            die;
        }
        if($Tuan['end_date'] < TODAY){
            $this->error('商品已过期');
            die;
        }
        $defaultAddress = D('Paddress')->where(['user_id'=>$this->uid,'id'=>$order['addr_id']])->find();
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' =>'fenxian', 'order_id' => $order_id));
        $this -> assign('defaultAddress', $defaultAddress);
        $this -> assign('changeAddressUrl', $changeAddressUrl);

        $this->assign('use_integral', $Tuan['use_integral'] * $order['num']);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('tuan', $Tuan);
        $this->assign('order', $order);
        $this->display();
    }

    //分销商品订单部分     --------新增部分
    public function tuan_order($tuan_id)
    {
        if(!$this->uid){
            $this->ajaxReturn(array('status' =>'login'));
        }

        if(false == ($detail = D('Pindan')->find($tuan_id))){
            $this->ajaxReturn(['status'=>'error','msg'=>'未找到该商品','url'=>1]);
        }
        $dianzan=D('Zeroelementforward')->where(array('user_id'=>$this->uid,'tuan_id'=>$tuan_id,'shop_id'=>$detail['shop_id'],'colse'=>0))->find();//查看是否存在已转发
        if(empty($dianzan)){
            $this->ajaxReturn(['status'=>'error','msg'=>'您未发起点赞，请点赞成功后下单！','url'=>1]);
        }
        if($dianzan['nums']>$dianzan['num']){
            $counts=$dianzan['nums']-$dianzan['num'];
            $this->ajaxReturn(['status'=>'error','msg'=>'您未达到点赞下单要求，还需要'.$counts.'位好友点赞']);
        }

        if($detail['closed'] ==1){
            $this->ajaxReturn(['status'=>'error','msg'=>'该商品已经下架','url'=>1]);
        }
        $addrs=D('Paddress')->where(array('default'=>1,'closed'=>0,'user_id'=>$this->uid))->find();

        if(empty($addrs)){
            $this->ajaxReturn(['status'=>'error','msg'=>'请在个人中心设置默认地址后再购买','url'=>U('wap/address/addrcat',array('type'=>'fenx','order_id'=>$tuan_id))]);
        }



        $num = I('num2',0,'trim,intval');
        $attr=I('attr_id',0,'trim,intval');
        if(!empty($attr)){
            $row=D('Zeroelementprice')->where(array('key'=>$attr,'goods_id'=>$tuan_id))->find();
            $need_pays=$row['yun_price']* $num;
        }else{
            $need_pays=$detail['tuan_price']*$num;
        }
        if($num <= 0 || $num > 99){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请输入正确的购买数量','url'=>1));
        }
        if(false == D('Shop')->check_shop_user_id($detail['shop_id'],$this->uid)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '您不能购买自己的产品','url'=>1));
        }
        $data = array(
            'tuan_id' => $tuan_id, 
            'num' => $num, 
            'user_id' => $this->uid, 
            'shop_id' => $detail['shop_id'], 
            'create_time' => NOW_TIME, 
            'create_ip' => get_client_ip(), 
            'total_price' => $need_pays,
            'mobile_fan' => $detail['mobile_fan'] * $num, 
            'need_pay' =>$need_pays,
            'key'=>$attr,
            'key_name'=>$row['key_name'],
            'status' => 0, 
            'is_mobile' => 1,
            'addr_id'=>$addrs['id']
        );
        if($order_id = D('Pindanorder')->add($data)){
            $this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜下单成功','order_id'=>$order_id));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '创建订单失败','url'=>1));
        }
    }

    //分销商品支付      ------新增部分
    public function tuan_pay2()
    {
        if(empty($this->uid)){
            $this->tuMsg('登录状态失效!', U('passport/login'));
        }
        $order_id = (int) $this->_get('order_id');

        $order = D('Pindanorder')->find($order_id);
        if(empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid){
            $this->tuMsg('该订单不存在');
        }
        if(!($code = $this->_post('code'))){
            $this->tuMsg('请选择支付方式');
        }
        $mobile = D('Users')->where(array('user_id' => $this->uid))->getField('mobile');
        if(!$mobile){
            $this->tuMsg('请先绑定手机号码再提交');
        }
        
        $pay_mode = '在线支付';
        if($code == 'wait'){
            $pay_mode = '货到支付';
            $codes = array();
            $obj = D('Pindancode');
            if (D('Pindanorder')->save(array('order_id' => $order_id,'status' => '-1'))){
                //更新成到店付的状态
                $tuan = D('Tuan')->find($order['tuan_id']);
                for ($i = 0; $i < $order['num']; $i++) {
                    $local = $obj->getCode();
                    $insert = array(
                        'user_id' => $this->uid, 
                        'shop_id' => $tuan['shop_id'], 
                        'order_id' => $order['order_id'], 
                        'tuan_id' => $order['tuan_id'], 
                        'code' => $local, 
                        'price' => 0, 
                        'real_money' => 0, 
                        'real_integral' => 0, 
                        'fail_date' => $tuan['fail_date'], 
                        'settlement_price' => 0, 
                        'create_time' => NOW_TIME, 
                        'create_ip' => get_client_ip()
                    );
                    $codes[] = $local;
                    $obj->add($insert);
                }
                D('Pindan')->updateCount($tuan['tuan_id'], 'sold_num');//更新卖出产品
                D('Sms')->user_pindan($this->uid,$order['order_id']);//团购商品通知用户
                D('Users')->prestige($this->uid, 'tuan');
                D('Sms')->shop_pindan($tuan['shop_id']);
                D('Weixintmpl')->weixin_notice_tuan_user($order_id,$this->uid,0);
                $this->tuMsg('恭喜您下单成功', U('user/tuan/index'));
            }else{
                $this->tuMsg('您已经设置过该商品为到店付了');
            }
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->tuMsg('该支付方式不存在');
            }
            $order['need_pay'] = D('Pindanorder')->get_tuan_need_pay($order_id,$this->uid,2);//获取实际支付价格封装
            $logs = D('Paymentlogs')->getLogsByOrderId('pindan', $order_id);
            if(empty($logs)){
                $logs = array(
                    'type' => 'pintuan',
                    'user_id' => $this->uid, 
                    'order_id' => $order_id, 
                    'code' => $code, 
                    'need_pay' => $order['need_pay'], 
                    'create_time' => NOW_TIME, 
                    'create_ip' => get_client_ip(), 
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            $codestr = join(',', $codes);
            D('Weixintmpl')->weixin_notice_tuan_user($order_id,$this->uid,1);
            $this->tuMsg('订单设置完毕，即将进入付款', U('payment/payment', array('log_id'=>$logs['log_id'])),200);
        }
    }

    //分销商品点评       ------- 新增部分
     public function pindandianpingloading(){
        $tuan_id = (int) $this->_get('tuan_id');
        if(!($detail = D('Pindan')->find($tuan_id))){
            die('0');
        }
        if($detail['closed']){
            die('0');
        }
        $Tuandianping = D('Pindandianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'tuan_id' => $tuan_id, 'show_date' => array('ELT', TODAY));
        $count = $Tuandianping->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $orders_ids = array();
        foreach($list as $k => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $orders_ids[$val['order_id']] = $val['order_id'];
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($orders_ids)){
            $this->assign('pics', D('Pindandianpingpics')->where(array('order_id' => array('IN', $orders_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('detail', $detail);
        $this->display();
    }
    
    
    
    //点评详情
    public function pindanimg(){
        $dianping_id = (int) $this->_get('dianping_id');
        if(!($detail = D('Pindandianping')->where(array('dianping_id'=>$dianping_id))->find())){
            $this->error('没有该点评');
            die;
        }
        if($detail['closed']){
            $this->error('该点评已经被删除');
            die;
        }
        $list =  D('Pindandianpingpics')->where(array('order_id' =>$detail['order_id']))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }


}