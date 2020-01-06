<?php

class ListsAction extends CommonAction {
	
	public function _initialize() {
        parent::_initialize();
		$Delivery = D('Delivery') -> where(array('id'=>$this->delivery_id)) -> find();
		$this->assign('delivery', $Delivery);
		$this->assign('peisong',$this->delivery_id);
    }
	
	
	//抢单
	public function scraped() {
//	    echo session( "lat" );
//	    echo session( "lng" );
		$keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $type = (int) $this->_param('type');
        $this->assign('type', $type);
        $order = (int) $this->_param('order');
		$this->assign('order', $order);
        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $business_id = (int) $this->_param('business_id');
        $this->assign('business_id', $business_id);
        $this->assign('nextpage', LinkTo('lists/scraped_load', array('type' => $type,'area_id' => $area_id, 'business_id' => $business_id,'order' => $order, 'keyword' => $keyword,  't' => NOW_TIME, 'p' => '0000','lat'=>$lat,"lng"=>$lng)));
        $this->display();
	}

	//取餐
	public function qurren(){
        if(IS_AJAX){
            $order_id = I('order_id',0,'trim,intval');
            $type = I('type',0,'trim,intval');
            if($type==1){//外卖
                $DeliveryOrder = D('DeliveryOrder');
                $delivery_order = $DeliveryOrder -> where('type_order_id ='. $order_id ) -> find();//详情
                if($delivery_order['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'error','message'=>'对不起，该订单已关闭!'));
                }
                if(!$delivery_order){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }else{
                    $ele=D('EleOrder')->where(array('order_id'=>$order_id))->find();
                    if(!empty($ele['shop_time'])){
                        D('EleOrder')->where(array('order_id'=>$order_id))->save(array('delivery_time'=>NOW_TIME,'status'=>11));

                        //判断配送时间
                        $config = D('Setting')->fetchAll();
                        $fenz=$config['freight']['one_time'];//最低分钟数
                        $km=$config['freight']['start_distance'];//在多上距离内
                        $jiafenz=$config['freight']['strat_jia'];//超过最低距离。每公里加多上分钟
                        if($km >=$delivery_order['distance']){
                            $times=$fenz;
                        }else{
                            $times=$fenz+(($delivery_order['distance']-$km)*$jiafenz);
                        }
                        $now = date('Y-m-d H:i:s',time());
                        $jieshu=strtotime(date("Y-m-d H:i:s",strtotime("+".$times."minutes",strtotime($now))));
                        $DeliveryOrder->where(['type_order_id'=>$order_id])->save(['qucan'=>1,'meals_time'=>NOW_TIME,'pei_time'=>$jieshu]);
                        $this->ajaxReturn(array('status'=>'success','message'=>'取餐成功！请尽快进行配送！'));
                    }else{
                        $this->ajaxReturn(array('status'=>'error','message'=>'商家未确认炒制完成，您不能提前取餐！'));
                    }
                }
            }elseif($type==4){//便利店
                $DeliveryOrder = D('DeliveryOrder');
                $delivery_order = $DeliveryOrder -> where('type_order_id ='. $order_id ) -> find();//详情
                if($delivery_order['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'error','message'=>'对不起，该订单已关闭!'));
                }
                if(!$delivery_order){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }else{
                    $ele=D('Storeorder')->where(array('order_id'=>$order_id))->find();
                    if(!empty($ele['shop_time'])){
                        D('Storeorder')->where(array('order_id'=>$order_id))->save(array('delivery_time'=>NOW_TIME,'status'=>11));
                        //判断配送时间
                        $config = D('Setting')->fetchAll();
                        $fenz=$config['freight']['one_time'];//最低分钟数
                        $km=$config['freight']['start_distance'];//在多上距离内
                        $jiafenz=$config['freight']['strat_jia'];//超过最低距离。每公里加多上分钟
                        if($km >=$delivery_order['distance']){
                            $times=$fenz;
                        }else{
                            $times=$fenz+(($delivery_order['distance']-$km)*$jiafenz);
                        }
                        $now = date('Y-m-d H:i:s',time());
                        $jieshu=strtotime(date("Y-m-d H:i:s",strtotime("+".$times."minutes",strtotime($now))));
                        $DeliveryOrder->where(['type_order_id'=>$order_id])->save(['qucan'=>1,'meals_time'=>NOW_TIME,'pei_time'=>$jieshu]);

                        $this->ajaxReturn(array('status'=>'success','message'=>'成功取走商品！请尽快进行配送！'));
                    }else{
                        $this->ajaxReturn(array('status'=>'error','message'=>'商家未确认最后的完成，您不能提前走商品！'));
                    }
                }
            }elseif($type==3){//菜市场
                $DeliveryOrder = D('DeliveryOrder');
                $delivery_order = $DeliveryOrder -> where('type_order_id ='. $order_id ) -> find();//详情
                if($delivery_order['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'error','message'=>'对不起，该订单已关闭!'));
                }
                if(!$delivery_order){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }else{
                    $ele=D('Marketorder')->where(array('order_id'=>$order_id))->find();
                    if(!empty($ele['shop_time'])){
                        D('Marketorder')->where(array('order_id'=>$order_id))->save(array('delivery_time'=>NOW_TIME,'status'=>11));
                        //判断配送时间
                        $config = D('Setting')->fetchAll();
                        $fenz=$config['freight']['one_time'];//最低分钟数
                        $km=$config['freight']['start_distance'];//在多上距离内
                        $jiafenz=$config['freight']['strat_jia'];//超过最低距离。每公里加多上分钟
                        if($km >=$delivery_order['distance']){
                            $times=$fenz;
                        }else{
                            $times=$fenz+(($delivery_order['distance']-$km)*$jiafenz);
                        }
                        $now = date('Y-m-d H:i:s',time());
                        $jieshu=strtotime(date("Y-m-d H:i:s",strtotime("+".$times."minutes",strtotime($now))));
                        $DeliveryOrder->where(['type_order_id'=>$order_id])->save(['qucan'=>1,'meals_time'=>NOW_TIME,'pei_time'=>$jieshu]);
                        $this->ajaxReturn(array('status'=>'success','message'=>'成功取走商品！请尽快进行配送！'));
                    }else{
                        $this->ajaxReturn(array('status'=>'error','message'=>'商家未确认最后的完成，您不能提前走商品！'));
                    }
                }
            }
        }
    }

	public function scraped_load() {
		$id = $this->delivery_id;
		$Delivery = D('Delivery')->where(array('id'=>$user_id))->find();
		$DeliveryOrder = D('DeliveryOrder');
		import('ORG.Util.Page');
		$map['closed'] = 0;
		$map['status'] = array('IN', array(0,1));
		if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
			$map['shop_name|addr'] = array('LIKE', '%' . $keyword . '%');
		}
		$type = (int) $this->_param('type');
        if($type == 1){
            $map['type'] = 1;
        }elseif ($type == 2){
            $map['type'] = 0;
        }elseif($type == 3){
            $map['type'] = array('IN',array(0,1));
        }
		
		$area_id = (int) $this->_param('area_id');
        if($area){
            $map['area_id'] = $area_id;
        }
		
        $business_id = (int) $this->_param('business_id');
        if($business_id){
            $map['business_id'] = $business_id;
        }
		$lat = addslashes( cookie( "lat" ) );
        $lng = addslashes( cookie( "lng" ) );
//        print_r($_SESSION);
//        print_r($_COOKIE);
//        print_r($lat);
//        print_r($lng);
        if(empty($lat)|| empty($lng)){
//            $lat = $this->city['lat'];
//             $lng = $this->city['lng'];
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];
        }
//        $lat = $_POST['lat'];
//        $lng = $_POST['lng'];
		$order = (int) $this->_param('order');
		switch ($order){
            case 1:
                $orderby = array("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') )" => 'asc', 'create_time' => 'desc');
                break;
            case 2:
                $orderby = array('create_time' => 'desc');
                break;
            case 3:
                $orderby = array('order_id' => 'desc');
                break;
            default:
                $orderby = array('create_time' => 'desc');
                break;
        }
		$this->assign('order', $order);
        $lists = $DeliveryOrder ->where($map)->order($orderby)->select();
        foreach ($lists as $k => $val ){
		  if (!empty($val['appoint_user_id'])) {
                $lists[$k]['appoint_user_id'] =  $val['appoint_user_id'];
                if ($lists[$k]['appoint_user_id'] != $Delivery['id']) {
                    unset($lists[$k]);
                }
            }
         }
		//重新排序
		$count = $DeliveryOrder->where($map)->count(); 
        $Page=new Page(count($lists),6);
        $show = $Page->show(); 
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
		$list = array_slice($lists, $Page->firstRow, $Page->listRows);
		$shop_ids = $user_ids = $addr_ids = $address_ids = array( );
        foreach ($lists as $k => $val ){
          $shop_ids[$val['shop_id']] = $val['shop_id'];
		  $user_ids[$val['user_id']] = $val['user_id'];
		  $list[$k]['d'] = getdistance( $lng, $lat, $val['lat'], $val['lng'] );
         }
		$this->assign('shops', D('Shop')->itemsByIds($shop_ids));	
		$this->assign('users', D('Users')->itemsByIds($user_ids));
		//计算那个距离结
		$this->assign('page', $show); // 赋值分页输出
        $this->assign('list',$list);
		$this->display();      
    }
	
	//配送中
	public function distribution() {
	
	
        $type = (int) $this->_param('type');
        $this->assign('type', $type);
        $order = (int) $this->_param('order');
		$this->assign('order', $order);
        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $business_id = (int) $this->_param('business_id');
        $this->assign('business_id', $business_id);
        $this->assign('nextpage', LinkTo('lists/distribution_load', array('type' => $type,'area_id' => $area_id, 'business_id' => $business_id,'order' => $order,'t' => NOW_TIME, 'p' => '0000')));
        $this->display(); 	
	}
	
	
	//配送中数据加载
	public function distribution_load(){

		$DeliveryOrder = D('DeliveryOrder');
		import('ORG.Util.Page'); 
		$map = array('closed' =>0, 'status' =>2,'delivery_id'=>$this->delivery_id);
		$type = (int) $this->_param('type');
        if ($type == 1) {
            $map['type'] = 1;
        }elseif ($type == 2) {
            $map['type'] = 0;
        }elseif($type == 3) {
            $map['type'] = array('IN',array(0,1));
        }
		$area_id = (int) $this->_param('area_id');
        if ($area) {
            $map['area_id'] = $area_id;
        }
        $business_id = (int) $this->_param('business_id');
        if ($business_id) {
            $map['business_id'] = $business_id;
        }
		$lat = addslashes( cookie( "lat" ) );
        $lng = addslashes( cookie( "lng" ) );
        if ( empty( $lat ) || empty( $lng ) ){
            $lat = $this->city['lat'];
             $lng = $this->city['lng'];
        }
		$order = (int) $this->_param('order');
		switch ($order) {
            case 2:
                $orderby = array('create_time' => 'desc');
                break;
            case 3:
                $orderby = array('order_id' => 'desc');
                break;
            default:
                $orderby = array("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') )" => 'asc', 'create_time' => 'desc');
                break;
        }
		$this->assign('order', $order);
	
		$count = $DeliveryOrder->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $DeliveryOrder ->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
	
        $shop_ids = $user_ids = $addr_ids = $address_ids = array( );
        foreach ($list as $k => $val ){
          $shop_ids[$val['shop_id']] = $val['shop_id'];
		  $user_ids[$val['user_id']] = $val['user_id'];
          $list[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
        }
        $config = D('Setting')->fetchAll();
        $this->assign('is_timeover',$config['site']['is_timeover']);
        $this->assign('timeover',$config['site']['timeover']);
		$this->assign('Shopdetails', D('Shopdetails')->itemsByIds($shop_ids));
		$this->assign('shops', D('Shop')->itemsByIds($shop_ids));	
		$this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('page', $show); 
        $this->assign('list',$list);
		$this->display();      
    }
	
	
	
	
	
	
	
	//已完成
	public function finished() {
        $this->assign('nextpage', LinkTo('lists/finished_load', array('t' => NOW_TIME, 'p' => '0000')));
        $this->display(); 
	}
	//已完成数据加载
	public function finished_load() {
		$DeliveryOrder = D('DeliveryOrder');
		import('ORG.Util.Page'); 
		$map = array('closed' =>0, 'status' =>8,'delivery_id'=>$this->delivery_id);
		$count = $DeliveryOrder->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $DeliveryOrder ->where($map)->order('update_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = $addr_ids = $address_ids = array( );
        foreach ($list as $k => $val ){
          $shop_ids[$val['shop_id']] = $val['shop_id'];
		  $user_ids[$val['user_id']] = $val['user_id'];
          $list[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
         }
		$this->assign('Shopdetails', D('Shopdetails')->itemsByIds($shop_ids));
		$this->assign('shops', D('Shop')->itemsByIds($shop_ids));	
		$this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('page', $show); 
        $this->assign('list',$list);
		$this->display();      
    }

	//详情
	public function detail($order_id = 0){
		if ($order_id  = (int) $order_id ){
            $obj = D('DeliveryOrder');
            if(!$detail = $obj->find($order_id )){
                $this->error('未知错误');
            }
			if($detail['closed']){
                $this->error('订单已关闭');
            }
			
			if($detail['type'] == 0){ 
               $Order = D('Order');
               $lists = $Order ->where('order_id ='.$detail['type_order_id']) -> find();//商品
               $t = 0;
               $OrderGoods = D('OrderGoods');
               $new_list = $OrderGoods -> where('order_id ='.$lists['order_id']) ->  select();
               $Goods = D('Goods');
               foreach($new_list as $key => $val){
                   $title = $Goods->where('goods_id ='.$val['goods_id'])->getField('title');
				   $photo = $Goods->where('goods_id ='.$val['goods_id'])->getField('photo');
                   $new_list[$key]['title'] = $title ;
				   $new_list[$key]['photo'] = $photo ;
               }
            }elseif($detail['type'] == 1){ //外卖
               $EleOrder = D('EleOrder');
               $lists = $EleOrder -> where('order_id ='.$detail['type_order_id']) -> find();
			   
               $t = 1;
               $EleOrderProduct = D('EleOrderProduct');
               $new_list = $EleOrderProduct -> where('order_id ='.$lists['order_id']) ->select();
               $EleProduct = D('EleProduct');
               foreach($new_list as $key => $val){
                  $title = $EleProduct->where('product_id ='.$val['product_id'])->getField('product_name');
				  $photo = $EleProduct->where('product_id ='.$val['product_id'])->getField('photo');
                  $new_list[$key]['title'] = $title;  
				  $new_list[$key]['photo'] = $photo ;
               }
            }elseif($detail['type'] == 3){ //菜市场
               $MarketOrder = D('MarketOrder');
               $lists = $MarketOrder -> where('order_id ='.$detail['type_order_id']) -> find();
               $t = 1;
               $MarketOrderProduct = D('MarketOrderProduct');
               $new_list = $MarketOrderProduct -> where('order_id ='.$lists['order_id']) ->select();
               $MarketProduct = D('MarketProduct');
               foreach($new_list as $key => $val){
                  $title = $MarketProduct->where('product_id ='.$val['product_id'])->getField('product_name');
				  $photo = $MarketProduct->where('product_id ='.$val['product_id'])->getField('photo');
                  $new_list[$key]['title'] = $title;  
				  $new_list[$key]['photo'] = $photo ;
               }
            }elseif($detail['type'] == 4){ //便利店
               $StoreOrder = D('StoreOrder');
               $lists = $StoreOrder -> where('order_id ='.$detail['type_order_id']) -> find();
               $t = 1;
               $StoreOrderProduct = D('StoreOrderProduct');
               $new_list = $StoreOrderProduct -> where('order_id ='.$lists['order_id']) ->select();
               $StoreProduct = D('StoreProduct');
               foreach($new_list as $key => $val){
                  $title = $StoreProduct->where('product_id ='.$val['product_id'])->getField('product_name');
				  $photo = $StoreProduct->where('product_id ='.$val['product_id'])->getField('photo');
                  $new_list[$key]['title'] = $title;  
				  $new_list[$key]['photo'] = $photo ;
               }
            }
			$this->assign('shops', D('Shop')->find($detail['shop_id']));
			$this->assign('addrs', D('Useraddr')->find($detail['addr_id']));
			$this->assign('Paddress', D('Paddress')->find($detail['address_id']));
			$this->assign('lists',$lists);
			$this->assign('new_list',$new_list);
			$this->assign('order_id',$order_id);
			$this->assign('detail',$detail);					
            $this->display();
        } else {
            $this->error('错误');
        }
	}
	
	//状态
	public function state($order_id = 0){
		if ($order_id  = (int) $order_id ){
            $obj = D('DeliveryOrder');
            if(!$detail = $obj->find($order_id )){
                $this->error('未知错误');
            }
			if($detail['closed']){
                $this->error('订单已关闭');
            }
			$this->assign('deliverys', D('Delivery')->find($detail['delivery_id']));
			$this->assign('shops', D('Shop')->find($detail['shop_id']));
			$this->assign('order_id',$order_id);
			$this->assign('detail',$detail);					
            $this->display();
        }else{
            $this->error('错误');
        }
	}
	
	
	//抢单
    public function handle(){
        if(IS_AJAX){
            $order_id = I('order_id',0,'trim,intval');
            $DeliveryOrder = D('DeliveryOrder');
                $delivery_order = $DeliveryOrder -> where('order_id ='. $order_id ) -> find();//详情
                if($delivery_order['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'error','message'=>'对不起，该订单已关闭!'));
                }
				if($delivery_order['status'] == 2){
                    $this->ajaxReturn(array('status'=>'error','message'=>'该订单已被抢了'));
                }
                $prisongyuan=D('Delivery')->where(array('id'=>$this->delivery_id))->find();
                if($prisongyuan['is_open']==0){
                    $this->ajaxReturn(array('status'=>'error','message'=>'您已关闭接单，请到设置打开'));
                }

                $ma['status']=array('neq',8);
                $jiedan=$DeliveryOrder->where(['delivery_id'=>$this->delivery_id,'qucan'=>1])->where($ma)->select();
                if(!empty($jiedan)){
                    $this->ajaxReturn(array('status'=>'error','message'=>'您有未配送完成的订单，不能连续接单'));
                }

                if(!$delivery_order){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }else{
					$delivery_id = $this->delivery_id; //获取配送员ID
					//逻辑重写，这个前面更新
					if(false == D('DeliveryOrder')->upload_deliveryOrder($delivery_id,$order_id)){
						$this->ajaxReturn(array('status'=>'error','message'=>D('DeliveryOrder')->getError()));
					}
                    $config = D('Setting')->fetchAll();
                    if($config['site']['is_peak'] ==1){
                        $is_peak =1;
                    }else{
                        $is_peak =0;
                    }
                    if($config['site']['is_timeover'] ==1){
                        $is_timeover =1;
                    }else{
                        $is_timeover =0;
                    }
                    $data = array(
						'delivery_id' => $delivery_id,
						'status' => 2,
						'update_time' => time(),
                        'is_peak' => $is_peak,
                        'is_timeover' => $is_timeover
					);
					$upload = $DeliveryOrder->where("order_id={$order_id}")->save($data);//更新数据
                    if($upload){
                        //更新部分
                        $Delivery = D('Delivery')->where(array('id'=>$delivery_id))->find();
                        $do = D('DeliveryOrder')->where(array('order_id' =>$order_id))->find();
                        if($Delivery['is_insurance'] ==0){
                            $info = "当日首次抢单扣除管理费用".$config['site']['insurance']."元";
                            D('Runningmoney')->add(array(
                                'city_id' => $do['city_id'], 
                                'area_id' => $do['area_id'], 
                                'business_id' => $do['business_id'],
                                'shop_id' => $do['shop_id'],  
                                'running_id' => $do['type_order_id'], 
                                'order_id' => $do['type_order_id'], 
                                'delivery_id' => $do['delivery_id'], 
                                'user_id' => $do['delivery_id'], 
                                'money' => -$config['site']['insurance'],
                                'type' => ele, 
                                'create_time' => NOW_TIME, 
                                'create_ip' => get_client_ip(), 
                                'intro' => $info
                            ));
                            D('Users')->addMoney($Delivery['user_id'],-$config['site']['insurance'],$info);
                            D('Delivery')->where(['id'=>$delivery_id])->save(['is_insurance'=>1]);
                        }
						$this->ajaxReturn(array('status'=>'success','message'=>'恭喜您！接单成功！请尽快进行配送！'));
                    }else{
                        $this->ajaxReturn(array('status'=>'error','message'=>'接单失败！错误！'));
                    }
            }
        }
    }

//     //配送管理员分成
//    public function fengcheng($order_id=84){
//         $this->fengcheng($order_id);
//        $order_msg = D("DeliveryOrder")->where(array("order_id"=>$order_id))->getField("order_id,delivery_id,logistics_price");
//        $allprice = $order_msg[$order_id]["logistics_price"];
//        $peisongyuan = $order_msg[$order_id]["delivery_id"];
//        $peusongyuanmsg = D("Delivery")->where(array("id"=>$peisongyuan))->field("recommend,user_id")->find();
//        if($peusongyuanmsg["recommend"]>0){
//
//            $getAllAdmins = D("Applicationmanagement")->getField("user_id,recommend,dj_id");
//            $datalist = array();
//            foreach ($getAllAdmins as $key => $value) {
//                if($value["user_id"]==$peusongyuanmsg["recommend"]){
//                    $datalist[$value["user_id"]]=$value["dj_id"];
//                    if($value["recommend"]>0){
//                        $datalist[$value["recommend"]]=$getAllAdmins[$value["recommend"]]["dj_id"];
//                        $threerecomm = $getAllAdmins[$value["recommend"]]["recommend"];
//                        if($threerecomm>0){
//                            $datalist[$getAllAdmins[$threerecomm]["user_id"]]=$getAllAdmins[$threerecomm]["dj_id"];
//                        }
//                    }
//                }
//            }
//            $data = array();
//            $count = 0;
//
//            foreach ($datalist as $key => $value) {
//                $fenc = D("DeliveryAdmin")->where(array("dj_id"=>$value))->field("fencheng")->find();
//                $price = $allprice*$fenc["fencheng"];
//                $data[$key]=$price;
//                $count  = $count+$price;
//            }
//            $data[$peisongyuan]=$allprice-$count;
//            var_dump($data);exit();
//
//        }else{
//            $data = D("Users")->where(array("user_id"=>$peisongyuan))->setInc('money',$peisongyuan);
//            if($data){
//                return false;
//            }
//            return true;
//        }
//    }

	
    //确认完成
    public function set_ok(){
        if(IS_AJAX){
            $order_id = I('order_id',0,'trim,intval');
            if(empty($this->delivery_id)){
                $this->ajaxReturn(array('status'=>'error','message'=>'您还没有登录或登录超时!'));
            }else{
                $do = D('DeliveryOrder')-> where('order_id ='.$order_id)->find();
				if(!$do){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }
				if($do['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'success','message'=>'对不起，该订单已关闭'));
                }
				if($do['status'] == 8){
                    $this->ajaxReturn(array('status'=>'error','message'=>'该订单已经完成'));
                }
				if($do['delivery_id'] != $this->delivery_id){
                    $this->ajaxReturn(array('status'=>'error','message'=>'请不要操作别人已经抢到的订单'));
                }
				if(D('DeliveryOrder')->where('order_id ='.$order_id)->save(array('status' => 8,'end_time' => time()))){
					D('DeliveryOrder')->ok_deliveryOrder($this->delivery_id,$order_id);
					$this->ajaxReturn(array('status'=>'success','message'=>'订单完成成功'));
				}else{
					$this->ajaxReturn(array('status'=>'error','message'=>'操作失败'));
				}
            }
        }
    }

   //取消订单
    public function cancel(){
        if(IS_AJAX){
            $order_id = I('order_id',0,'trim,intval');
            if(empty($this->delivery_id)){
                $this->ajaxReturn(array('status'=>'error','message'=>'您还没有登录或登录超时!'));
            }else{
                $do = D('DeliveryOrder')-> where('order_id ='.$order_id)->find();
                if(!$do){
                    $this->ajaxReturn(array('status'=>'error','message'=>'当前订单在数据库中找不到'));
                }
                if($do['closed'] == 1){
                    $this->ajaxReturn(array('status'=>'success','message'=>'对不起，该订单已关闭'));
                }
                if($do['status'] == 8){
                    $this->ajaxReturn(array('status'=>'error','message'=>'该订单已经完成'));
                }
                if($do['delivery_id'] != $this->delivery_id){
                    $this->ajaxReturn(array('status'=>'error','message'=>'请不要操作别人已经抢到的订单'));
                }
                $config = D('Setting')->fetchAll();
                $jian=$config['delivery']['refuse_money'];
                if(!empty($do)){
                    if($do['type']==0){
                        D('Order')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
                        D('Ordergoods')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'0'));
                    }elseif($do['type']==1){
                        D('Eleorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
                    }elseif($do['type']==3){
                        D('Marketorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
                    }elseif($do['type']==4){
                        D('Storeorder')->where(array('order_id'=>$do['type_order_id']))->save(array('status'=>'1'));
                    }
                    $user=D('Delivery')->where(array('id'=>$do['delivery_id']))->find();
                    D('Users')->addMoney($user['user_id'],-$jian,'由于您取消配送，将对您惩罚'.$jian.'元,订单号'.$order_id.'。');
                    if(D('DeliveryOrder')->where('order_id ='.$order_id)->save(array('status' => 1,'update_time' => 0,'delivery_id'=>0))){
                        $this->ajaxReturn(array('status'=>'success','message'=>'订单取消成功'));
                    }
                }else{
                    $this->ajaxReturn(array('status'=>'error','message'=>'操作失败'));
                }
            }
        }
    }


//语音通知
	 public function get_message(){
        if(IS_AJAX){
            $last_time = cookie('last_time');
            cookie('last_time',time(),86400*30); //存一个月 
            if(empty($last_time)){  
                $this->ajaxReturn(array('status'=>'0','message'=>'开始抢单了!'));
            }
            else{
                $cid = $this->delivery_id;
				$delivery_type = D('Delivery')->where('id='.$cid)->getField('delivery_type');
				$t_e = C('DB_PREFIX').'ele_order';
				$t_d = C('DB_PREFIX').'delivery_order';
				$t_o = C('DB_PREFIX').'order';
				$dv = D('DeliveryOrder')->join($t_e.' on '.$t_d.'.type_order_id = '.$t_e.'.order_id');
				$dv = $dv->join($t_o.' on '.$t_d.'.type_order_id = '.$t_o.'.order_id');
				$map = array();
				if($delivery_type == 0){
					$map['_string'] = '('.$t_e.'.is_pay = 1 or '.$t_o.'.is_daofu = 0) ';
				}
				elseif($delivery_type == 1){
					$map['_string'] = '('.$t_e.'.is_pay = 0 or '.$t_o.'.is_daofu = 1) ';
				}
				$map['_string'] = $map['_string'].'and '.$t_d.'.create_time>='.$last_time.' and '.$t_d.'.status <2 and '.$t_d.'.delivery_id =0';
				$count = $dv -> where($map) -> count();
            //file_put_contents('1.log',$dv->getLastSql());
            if($count>0)
                $this->ajaxReturn(array('status'=>'2','message'=>'有新的订单了!'));
            else
                $this->ajaxReturn(array('status'=>'1','message'=>''));
            }
        }
        
    }
	
	public function gps($shop_id, $type = '0', $gps_type = 'shop'){
		
		if (!is_numeric($shop_id) || !in_array($type, ['0', '1', '2']) || !in_array($gps_type, ['shop', 'buyer'])) { 
			$this->error('该'. ($gps_type == 'shop' ? '商家' : '买家'). '信息有误');
		}
		
		
		if ($gps_type == 'buyer') { //如果买家, 则额外添加字段 shop_name
		$shop = D('DeliveryOrder')->where(array('order_id'=>I('shop_id')))->find();	
			$shop['shop_name'] = $shop['name'];
			$peitype=1;
		}else{
		$shop = D('shop')->where(array('shop_id'=>I('shop_id')))->find($shop_id);	
			$peitype=2;
		}
		$this->assign('pei',$peitype);
        $this->assign('shop', $shop);
        $this->assign('shop_id', $shop_id);
        $this->assign('gps_type', $gps_type);
		$this->assign('type', $type);
		$this->assign('amap', $amap= $this->bd_decrypt($shop['lng'],$shop['lat']));
		$this->display();
    }
   
      //BD-09(百度) 坐标转换成  GCJ-02(火星，高德) 坐标
      //@param bd_lon 百度经度
      //@param bd_lat 百度纬度
	  public function bd_decrypt($bd_lon,$bd_lat){
			$x_pi = 3.14159265358979324 * 3000.0 / 180.0;
			$x = $bd_lon - 0.0065;
			$y = $bd_lat - 0.006;
			$z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
			$theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
			$data['gg_lon'] = $z * cos($theta);
			$data['gg_lat'] = $z * sin($theta);
			return $data;
		}
  
   //配送app下载(配送员)
        public function appxz(){
            $this->display();
        }

}