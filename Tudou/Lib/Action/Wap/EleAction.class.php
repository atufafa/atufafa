<?php
class EleAction extends CommonAction{
    protected $cart = array();
    public function _initialize(){
        parent::_initialize();
        $this->cart = $this->getcart();
        $this->assign('cartnum', (int) array_sum($this->cart));
        $cate = D('Ele')->getEleCate();
        // print_r($cate);die;
        $this->assign('elecate', $cate);
        $cate_ids = D('Ele')->getEleCateIds();
        $this->assign('elecates',$cate_ids);
        $cate_idss = D('Ele')->getAllEleCate();
        $this->assign('elecatess',$cate_idss);
		if(empty($this->_CONFIG['operation']['ele'])){
			$this->error('外卖功能已关闭');die;
		}
        $config = D('Setting')->fetchAll();
		$this->assign('config',$config);
        //天天特价
		$elespecial=D('GoodsEleStoreMarket')->where(['type'=>1,'type_id'=>1])->select();
		foreach ($elespecial as $value){
		    $goods_id[]=$value['product_id'];
        }
		$this->assign('goods',D('Eleproduct')->itemsByIds($goods_id));
        $this->assign('elespecial',$elespecial);
        //团购
        $eletaun=D('GoodsEleStoreMarket')->where(['type'=>1,'type_id'=>3])->select();
        foreach ($eletaun as $value){
            $goods_ids[]=$value['product_id'];
        }
        $this->assign('goodstuan',D('Eleproduct')->itemsByIds($goods_ids));
        $this->assign('eletaun',$eletaun);
        //秒杀
        $elespike=D('GoodsEleStoreMarket')->where(['type'=>1,'type_id'=>2])->select();
        foreach ($elespike as $value){
            $goods_idss[]=$value['product_id'];
        }
        $this->assign('goodssha',D('Eleproduct')->itemsByIds($goods_idss));
        $this->assign('elespike',$elespike);

        //查询广告位
        $this->assign('elenotice',D('NoticeEleStoreMarket')->where(['type'=>1])->select());

    }
    public function getcart(){
        $shop_id = (int) $this->_param('shop_id');
        $cart = (array) json_decode($_COOKIE['ele']);
        $carts = array();
        foreach ($cart as $kk => $vv) {
            foreach ($vv as $key => $v) {
                $carts[$kk][$key] = (array) $v;
            }
        }
        $ids = $nums = array();
        foreach ($carts[$shop_id] as $k => $val) {
            $ids[$val['product_id']] = $val['product_id'];
            $nums[$val['product_id']] = $val['num'];
        }
        $eleproducts = D('Eleproduct')->itemsByIds($ids);
        foreach ($eleproducts as $k => $val) {
            $eleproducts[$k]['cart_num'] = $nums[$val['product_id']];
            $eleproducts[$k]['total_price'] = $nums[$val['product_id']] * $val['price'];
        }
        return $eleproducts;
    }
  
  
   public function loadcart(){
        if($goods = cookie('ele')) {
            $total = array('num' => 0, 'money' => 0);
            $goods = (array) json_decode($goods);
            $ids = array();
            foreach ($goods as $shop_id => $items) {
                foreach ($items as $k2 => $item) {
                    $item = (array) $item;
                    $total['num'] += $item['num'];
                    $total['money'] += $item['price'] * $item['num'];
                    $ids[] = $item['product_id'];
                    $product_item_num[$item['product_id']] = $item['num'];
                }
            }
            $ids = implode(',', $ids);
            $products = D('Eleproduct')->where('closed=0')->select($ids);
            foreach ($products as $k => $val) {
                $products[$k]['cart_num'] = $product_item_num[$val['product_id']];
            }
            $this->assign('cartgoods', $products);
			$this->display();
        }
    }
	
	
    public function index(){
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;
        $cate = $this->_param('cate', 'htmlspecialchars');
        $this->assign('cate', $cate);
        $linkArr['cate'] = $cate;
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;
        $area = (int) $this->_param('area');
        $this->assign('area', $area);
        $linkArr['area'] = $area;
        $business = (int) $this->_param('business');
        $this->assign('business', $business);
        $linkArr['business'] = $business;
        $this->assign('nextpage', LinkTo('ele/loaddata', $linkArr, array('t' => NOW_TIME, 'p' => '0000')));
        $this->assign('linkArr', $linkArr);
		$adds=cookie('addname');
		$this->assign('adds',$adds);
		
		//获取IN
        $shops = D('Shop')->where(array('city_id'=>$this->city_id))->select();
        foreach($shops as $val){
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
		$lists = D('Eleproduct')->where(array('is_tuijian'=>1, 'audit' => 1, 'closed' =>0,'shop_id'=>array('in', $shop_ids),'cost_price' => array('neq','')))->order(array('sold_num' => 'desc','create_time' => 'desc'))->limit(0,6)->select();
		$this->assign('product', $list = second_array_unique_bykey($lists,'shop_id'));//去掉重复商家
        $this->display();
    }
	


	
	
    public function loaddata(){
        $ele = D('Ele');
        import('ORG.Util.Page');
        $map = array('audit' => 1,'is_open'=>1, 'city_id' => $this->city_id);
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            //$map['shop_name'] = array('LIKE', '%' . $keyword . '%');
        }
        $business = (int) $this->_param('business');
        if ($business) {
            $map['business_id'] = $business;
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));

        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
		$order = $this->_param('order', 'htmlspecialchars');
        switch($order){
            case 'a':
                $orderby = array("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') )" => 'asc', 'orderby' => 'asc', 'month_num' => 'desc', 'distribution' => 'asc', 'since_money' => 'asc');
                break;
            case 'p':
                $orderby = array('since_money' => 'asc');
                break;
            case 'v':
                $orderby = array('distribution' => 'asc');
                break;
            case 'd':
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}')) asc ";
                break;
            case 's':
                $orderby = array('month_num' => 'desc');
                break;
			default:
                $orderby = array( 'orderby' => 'asc',"(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}'))" => 'asc');
                break;
        }
		
        $cate = $this->_param('cate', 'htmlspecialchars');



        //查询配送费阶段
        $config = D('Setting')->fetchAll();
        $pei=$config['freight']['radius'];
        $shop_ids = array();
        if(empty($keyword) && $keyword!='输入外卖的关键字'){
            $lists = $ele->order($orderby)->where($map)->select();

            foreach($lists as $k => $val){
                //if(!is_QQBrowser()){
                $lists[$k]['radius'] = $val['is_radius'];
                $lists[$k]['is_radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
                if (!empty($val['is_radius'])){
                    if ((int) $lists[$k]['is_radius'] > $pei*10000){
                        unset($lists[$k]);
                    }
                }
                //}
                if($this->closeshopele($val['busihour'])){
                    $lists[$k]['bsti'] = 1;
                    unset($lists[$k]);//不要让打样店铺显示
                }
                //分类筛选
                $cates = explode(',',$val['cate']);
                $res = array_search($cate,$cates);
                if($cate && $res === false){
                    unset($lists[$k]);
                }
            }
            $count = count($lists);
            $Page = new Page($count, 10);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $list = array_slice($lists, $Page->firstRow, $Page->listRows);
        }else{
            $where['product_name']=array('LIKE', '%' . $keyword . '%');
            $m = M("ele_product as e");
            $listss=$m->order($orderby)
                ->join("LEFT JOIN tu_ele as el ON e.shop_id = el.shop_id")
                ->where($where)
                ->group('e.shop_id')
                ->select();
            foreach($listss as $k => $val){
                //if(!is_QQBrowser()){
                $lists[$k]['radius'] = $val['is_radius'];
                $lists[$k]['is_radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
                if (!empty($val['is_radius'])){
                    if ($lists[$k]['is_radius'] > $val['is_radius']*10000){
                        unset($lists[$k]);
                    }
                }
                //}
                if($this->closeshopele($val['busihour'])){
                    $lists[$k]['bsti'] = 1;
                    unset($lists[$k]);//不要让打样店铺显示
                }
                //分类筛选
                $cates = explode(',',$val['cate']);
                $res = array_search($cate,$cates);
                if($cate && $res === false){
                    unset($lists[$k]);
                }
            }
            $count = count($listss);
            $Page = new Page($count, 10);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $list = array_slice($listss, $Page->firstRow, $Page->listRows);
        }




        foreach ($list as &$val){
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $val['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
            $val['score'] = D('Eledianping')->getShopScore($val['shop_id']);
            //新版筛选分类高于10个分类解决方案
        }
		
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function closeshopele($busihour) {
        $timestamp = time();
        $now = date('G.i', $timestamp);
        $close = true;
        if (empty($busihour)) {
            return false;
        }
        foreach (explode(',', str_replace(':', '.', $busihour)) as $period) {
            list($periodbegin, $periodend) = explode('-', $period);
            if ($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend) || $periodbegin < $periodend && $now >= $periodbegin && $now < $periodend) {
                $close = false;
            }
        }
        return $close;
    }
	//菜品列表
    public function shop($shop_id = 0){
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));

        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $shop_id = $this->_param('shop_id');
        $this->assign('is_tui',$_GET['is_tui']);
        if(!($detail = D('Ele')->find($shop_id))){
            $this->error('该餐厅不存在1');
        }

        if(!($shop = D('Shop')->find($shop_id))){
            $this->error('该餐厅不存在2');
        }
		if($this->closeshopele($detail['busihour'])){
           $detail['bsti'] = 1;
        }else{
           $detail['bsti'] = 0;
        }
        $weisheng=M('shop_audit')->where(['shop_id'=>$detail['shop_id']])->find();
        $this->assign('weisheng',$weisheng['weisheng'].'?x-oss-process=style/atufafa');
		$obj = D('Eleproduct');
		$map = array('closed' => 0, 'audit' => 1, 'shop_id' => $shop_id,'is_huodong'=>0);

		if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['product_name|desc'] = array('LIKE', '%' . $keyword . '%');
			$this->assign('keyword', $keyword);	
        }
		$this->assign('tuijian', $tuijian = $obj->where(array('shop_id' => $shop_id,'is_tuijian'=>1, 'closed' =>0, 'audit' => 1,'is_huodong'=>0, 'cost_price' => array('neq','')))->order(array('create_time' =>'desc'))->limit(0,2)->select());//推荐开始
		$list = $obj->where($map)->order(array('sold_num' =>'desc','price' => 'asc'))->select();

		foreach ($list as $k => $val){
			
			/*
			foreach ($tuijian as $kk => $val2){
				if($val2['product_id'] == $val['product_id']){
					unset($list[$k]);
				}
			}*/
			
			
            $list[$k]['cart_num'] = $this->cart[$val['product_id']]['cart_num'];
        }
		
		
		
        foreach($list as $k => $val){
            if($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
            $list[$k] = $val;
        }
        if($cate_ids) {
            $cates = D('Elecate')->itemsByIds($cate_ids);
            $ids = array();
            foreach($cates as $k => $val){
                $ids[$d][] = $k;
            }
            ksort($ids);
            $showcates = array();
            foreach ($ids as $arr1) {
                foreach ($arr1 as $val) {
                    $showcates[$val] = $cates[$val];
                }
            }
            $this->assign('cate', $showcates);
        }
        
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->assign('cates', D('Elecate')->where(array('shop_id' => $shop_id, 'closed' => 0))->select());
        $this->assign('shop', $shop);

        //商品列表查询存入session的值
        $fee=Distributionfee($lat,$lng,$shop_id,1);
        $logistics=session('elelogistics');
        $zdlogistics=session('elezdlogistics');
        if(!empty($logistics) || !empty($zdlogistics)){
            session('elelogistics',null);
            session('elezdlogistics',null);
        }
        session('elelogistics',$fee['logistics']);
        session('elezdlogistics',$fee['zdlogistics']);
        $this->assign('logistics', $fee['logistics']);
        $this->assign('zdlogistics', $fee['zdlogistics']);
        $this->assign('listss', $list);
        //$this->assign('zdlogistics', $listss[0]['zdlogistics']);
        //评价
        $shop_id = (int) $this->_get('shop_id');
        if(!($details = D('Ele')->find($shop_id))){
            die('0');
        }
        if($details['closed'] != 0 || $details['audit'] != 1){
            die('0');
        }
        $Eledianping = D('Eledianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
        $count = $Eledianping->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $show = $Page->show();
        $lists = $Eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($lists as $k => $val) {
            $lists[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($order_ids)) {
            $this->assign('pics', D('Eledianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('lists', $lists);
        $this->assign('detail', $details);
        
       //商家信息
         $shop_id = $this->_param('shop_id');
         $tu=D('Shoppic')->where(array('shop_id'=>$shop_id))->select();

         $times=D('Ele')->where(array('shop_id'=>$shop_id))->find();
         $this->assign('times',$times);
        $this->assign('sjtu',$tu);
        $this->assign('shops', D('Shop')->itemsByIds($shop_id));   

        //获取外卖配送费
//        $pe=D('Freight')->select();
//        $zdpei=D('Zdfreifht')->select();
//        $this->assign('pei',$pe);
//        $this->assign('zdpei',$zdpei);

        $this->display();
    }

    public function order($is_tui=0 , $type ,$user=0 ){
        $_SESSION['price_type'] = $type ;
        //$_SESSION['pin_user']=$user;
        if(empty($this->uid)){
            $this->error('请先登陆', U('passport/login'));
        }
		
		
		if($lists = cookie('ele')){
            $lists = (array) json_decode($lists,true);

            $list = array();
			foreach($lists as $key=>$val){
				foreach($val as $k=>$v){
				  $list[] = $v;
				}
			}
        }

        if(empty($list)){
            $this->error('您还没有订餐呢');
        }
        $shop_id = 0;
        $shops = array();
        $products = array();
        $total = array('money' => 0, 'num' => 0);
        $product_name = array();
		
		foreach($list as $key => $val) {
            if($val['num'] < 1  || $val['num'] > 99){
				unset($list[$key]);
            }
           
        }
		if(!$list){
			$this->error('请选择正确的购买数量');
		}
        foreach ($list as $key => $val) {
            $product = D('Eleproduct')->find($val['product_id']);
            $product_name[] = $product['product_name'];
            if (empty($product)) {
				$this->error('产品不正确');
            }
            $shop_id = $product['shop_id'];
            $product['buy_num'] = $val['num'];
            $product['spec'] = $val['spec'];
            $products[$key] = $product;
            $shops[$shop_id] = $shop_id;
            $total['num'] += $val['num'];
			$total['tableware_price'] += $product['tableware_price'] * $val['num'];//结算费总计
            $food=($product['price'] * $val['num']) +($product['tableware_price'] * $val['num']);
			$total['money'] += ($product['price'] * $val['num']) +($product['tableware_price'] * $val['num']);//用户值及支付 = 菜品价格+餐盒费
			$settlement_price  += ($product['settlement_price'] * $val['num'])+($product['tableware_price'] * $val['num']);//结算价格 = 菜品结算价+餐盒费
        }
        //var_dump($settlement_price,$product['settlement_price'],$product['tableware_price']);die;
        if (count($shops) > 1) {
            $this->error('您购买的商品是2个商户的');
        }
        if (empty($shop_id)) {
            $this->error('商家不存在');
        }
        $shop = D('Ele')->find($shop_id);
        if (empty($shop)) {
            $this->error('该商家不存在');
        }
		if (false == D('Shop')->check_shop_user_id($shop_id,$this->uid)) {//不能购买自己家的产品
			 $this->error('您不能购买自己的外卖');
		}
		
        if (!$shop['is_open']) {
            $this->error('商家已经打烊，实在对不住客官');
        }
		$busihour = $this->closeshopele($shop['busihour']);
		 if ($busihour == 1) {
            $this->error('商家休息中，请稍后再试');
        }

        //新增部分
        // if(!empty($is_tui)){

        // }
        if ($shop['since_money'] > $total['money']){
            $this->error('客官，您再订点吧');
        }
        //新客户满多少减去多少
        if ($shop['is_new'] && !D('Eleorder')->checkIsNew($this->uid, $shop_id)) {
            if ($total['money'] >= $shop['full_money']) {
                $total['new_money'] = $shop['new_money'];
            }
        };


		  $logistics_full_money  = intval( D('Eleorder')->get_logistics($total['money'],$shop_id));//获取用户实际支付配送费用


        $logistics=session('elelogistics');
        $zdlogistics=session('elezdlogistics');
        //echo $type;die

        //查询当前配送数量
        $config=D('Setting')->fetchAll();
        $sum_num=$config['freight']['ele_num'];
        $peisongfei=$config['freight']['num_money'];

        $sum=$config['freight']['ele_num'];
        $peisongfei=$config['freight']['num_money'];
        if($val['num']>$sum){
            $coun=($val['num']-$sum)*$peisongfei;
        }

        if($type==1){
            $total['money'] += $logistics+$coun;
            $total['logistics_full_money']=$logistics+$coun;
            session('zdlogistics2',$logistics+$coun);
        }elseif($type==2){
            $total['money'] += $zdlogistics+$coun;
            $total['logistics_full_money']=$zdlogistics+$coun;
            session('zdlogistics2',$zdlogistics+$coun);
        }elseif($type==4){
            $total['money'] += 0;
            $total['logistics_full_money']=0;
            session('zdlogistics2',$logistics+$coun);
        }
        $logistics=$total['logistics_full_money'];
        //加上配送费
		//$total['money'] += $total['tableware_price'];//加上餐具费
		
        //扣减红包后需要支付的金额 = 总金额 - 商家红包 - 通用红包
        $total['need_pay'] = $total['money'];
		
		$total['full_reduce_price'] = D('Eleorder')->get_full_reduce_price($total['money'],$shop_id);//获取满减费用
        //查询用户是否领取了优惠劵
        $coupon=D('Coupondownload')->where(['shop_id'=>$shop_id,'user_id'=>$this->uid,'is_used'=>0])->find();
        if(!empty($coupon)){
            $ypuhui=D('Coupon')->where(['coupon_id'=>$coupon['coupon_id']])->find();
            if($food >= $ypuhui['full_price']){
                $full_price=$ypuhui['reduce_price'];
                $coupon_id=$ypuhui['coupon_id'];
                $full_reduce_price=0;
            }else{
                $full_price=0;
                $coupon_id=0;
                $full_reduce_price=$total['full_reduce_price'];
            }
        }else{
            $full_price=0;
            $coupon_id=0;
            $full_reduce_price=$total['full_reduce_price'];
        }

        //判断是否减费送费
        if($logistics_full_money==0){
            $totals['logistics_full_money']=0;
            $total['need_pay'] = $total['need_pay'] - $total['new_money'] - $full_price - $full_reduce_price;
        }else{
            $totals['logistics_full_money']=$total['logistics_full_money'];
            $total['need_pay'] = $total['need_pay'] - $total['new_money'] - $total['logistics_full_money'] -  $full_reduce_price;
        }

        
        //结算金额逻辑后期封装，如果是第三方配送，如果开通新单立减后，配送费用商家出，如果商家开启满减优惠，满减优惠商家出
		
		$shop_detail = D('Shop')->where(array('shop_id'=>$shop_id))->find();
		if($shop_detail){
			$logistics = 0;
			if($shop_detail['is_ele_pei'] == 1){
				//第三方平台配送，结算价-新单立减-配送费-满减费用
				$last_settlement_price = $settlement_price - $total['new_money'] - $totals['logistics_full_money'] - $total['full_reduce_price'];
				//var_dump('不知道：'.$settlement_price,'差价：'.$last_settlement_price,'新单：'.$total['new_money'],'满减配：'.$total['logistics_full_money'],'满减'.$total['full_reduce_price']);die;
				$settlementIntro.='实际结算价格【'.$last_settlement_price.'元】=';
				$settlementIntro.='结价格【'.$settlement_price.'元】';
				if($total['new_money']){
					$settlementIntro.='-新单立减：【'.$total['new_money'].'元】';
				}
				if($total['logistics_full_money']&&$logistics_full_money!=0){
					$settlementIntro.='- 满减配送费：【'.$totals['logistics_full_money'].'元】';
				}
				if($total['logistics_full_money']){
					$settlementIntro.=' - 满减活动扣费：【'.$total['full_reduce_price'].'元】';
				}
				//p($settlementIntro);die;
			}
		}else{
			//商家自己配送，结算价-新单立减+ 配送费-满减费用
			
			$logistics = ($shop['logistics'] >= 50) ? $shop['logistics'] : '50';
			
			$last_settlement_price = $settlement_price - $total['new_money'] - $total['full_reduce_price'] - $total['logistics_full_money'] + $logistics;
			
			$settlementIntro.='实际结算价格【'.$last_settlement_price.'元】=';
			$settlementIntro.='结价格【'.$settlement_price.'元】';
			if($total['new_money']){
				$settlementIntro.='-新单立减：【'.$total['new_money'].'元】';
			}
			if($total['full_reduce_price']){
				$settlementIntro.=' - 满减活动扣费：【'.$total['full_reduce_price'].'元】';
			}
			if($total['logistics_full_money']){
				$settlementIntro.=' - 满减配送费：【'.$total['logistics_full_money'].'元】';
			}
			
			if($logistics){
				$settlementIntro.=' + 配送费：【'.$logistics.'元】';
			}
			
			
		}

		if($shop['is_guozi']==1){
            $guoz=$total['num'];
        }else{
            $guoz=null;
        }
        $month = date('Ym', NOW_TIME);
        //写入外卖订单表
        $order_id = D('Eleorder')->add(array(
            'user_id' => $this->uid, 
            'shop_id' => $shop_id, 
            'total_price' => $total['money'], 
            'need_pay' => $total['need_pay'], 
            'num' => $total['num'],
            'download_coupon_id'=>$coupon_id,
            'reduce_coupun_money'=>$full_price,
            'new_money' => (float) $total['new_money'],
            'logistics_full_money' => (float) $totals['logistics_full_money'],
            'full_reduce_price' => (float) $total['full_reduce_price'],
            'logistics' => (float) $total['logistics_full_money'],
            'tableware_price' => (float) $total['tableware_price'],
            'settlement_price' => $last_settlement_price, 
            'settlementIntro' => $settlementIntro, 
            'status' => 0, 
            'create_time' => NOW_TIME, 
            'create_ip' => get_client_ip(), 
            'is_pay' => 0, 
            'type'=>$type,
            'month' => $month,
            'is_tui' => $is_tui,
            'pin_user'=>$user,
            'order_envelope' => 0,
            'fruit'=>$guoz
        ));

        if ($order_id) {
            //写入外卖订单商品表
            foreach ($products as $val) {
                D('Eleorderproduct')->add(array(
					'order_id' => $order_id, 
					'product_id' => $val['product_id'], 
					'num' => $val['buy_num'], 
					'total_price' => $val['price'] * $val['buy_num'], 
					'tableware_price' => $val['tableware_price'] * $val['buy_num'], 
					'month' => $month,
                    'spec'=>$val['spec']
				));
            }

            setcookie("ele", "", time() - 3600, "/");
            $this->success('下单成功，您可以选择配送地址', U('ele/pay', array('order_id' => $order_id)));
        }
        $this->error('创建订单失败');
    }




    public function message(){
        $order_id = (int) $this->_get('order_id');
        if (!($detail = D('Eleorder')->find($order_id))) {
            $this->tuMsg('没有该订单');
            die;
        }
        if ($detail['status'] != 0) {
            $this->tuMsg('参数错误');
            die;
        }
        $ele_shop = D('Ele')->find($detail['shop_id']);
        $tags = $ele_shop['tags'];
        $tagsarray = array();
        if (!empty($tags)) {
            $tagsarray = explode(',', $tags);
        }
        if ($this->isPost()) {
            if ($message = $this->_param('message', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'message' => $message);
                if (D('Eleorder')->save($data)) {
                    $this->tuMsg('添加留言成功', U('Wap/ele/pay', array('order_id' => $detail['order_id'])));
                }
            }
            $this->tuMsg('请填写留言');
        } else {
            $this->assign('detail', $detail);
            $this->assign('tagsarray', $tagsarray);
            $this->display();
        }
    }
    public function pay(){
        $type  =  isset($_SESSION['price_type']) ?$_SESSION['price_type']:"";
        //$user = isset($_SESSION['pin_user']) ?$_SESSION['pin_user']:"";
        //var_dump($type);
        if(empty($this->uid)){
            header('Location:' . U('passport/login'));
            die;
        }
        $this->check_mobile();
        $order_id = (int) $this->_get('order_id');
        $order = D('Eleorder')->find($order_id);
        if(empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid){
            $this->error('该订单不存在');
            die;
        }

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        $myshop = D('Ele')->find($order['shop_id']);
        //$p_data = compare_price($order['shop_id'] , $lat , $lng, $myshop['city_id'] );
        $this->assign('shop', D('Ele')->find($order['shop_id']));
        $ordergoods = D('Eleorderproduct')->where(array('order_id' => $order_id))->select();

        $goods = array();
        /*print_r($ordergoods);
        exit;*/
        $t_price_array = array();
        $t_price =  0 ;
        foreach($ordergoods as $key => $val){
            $goods[$val['product_id']] = $val['product_id'];
            array_push($t_price_array, $val['total_price']);
        }
        $t_price =  array_sum($t_price_array);
        $products = D('Eleproduct')->itemsByIds($goods);
        $this->assign('products', $products);
        $this->assign('ordergoods', $ordergoods);
        
        $useraddr_is_default = D('Useraddr')->where(array('user_id' => $this->uid,'closed'=>0))->limit(0, 3)->select();
        $this->assign('useraddr', $useraddr_is_default);

//        //默认地址
//        $default=D('Useraddr')->where(['user_id'=>$this->uid,'is_default'=>1,'closed'=>0])->find();
//        //如果没有默认地址
//        $add=D('Useraddr')->where(['user_id'=>$this->uid,'closed'=>0])->find();
//        if(!empty($default)){
//            $this->assign('newadd',$default);
//        }else{
//            $this->assign('newadd',$add);
//        }



        $is_logistics_full_money = 0 ; 
        
        /*echo $ordergoods[0]['total_price'] ;
        exit;*/
        $has_logistics_full_money = D('Eleorder')->get_logistics($t_price ,$order['shop_id'] );
        if( $has_logistics_full_money  ){
            $is_logistics_full_money = 1 ; 
        }
        //var_dump($is_logistics_full_money);die();
        $this->assign('is_logistics_full_money', $is_logistics_full_money);

        $myshop = D('Ele')->find($order['shop_id']);
        $this->assign('order', $order);

        $eles = D('Ele')->find($order['shop_id']);

        if($type==2){
            $eles['zdlogistics']=$eles['zdlogistics'];

        }
         if($type==1){
            $eles['logistics'] = $p_data['logistics'];
        }elseif($type==2){
            $eles['logistics'] = $p_data['zdlogistics'];
        }elseif($type==4){
             $eles['logistics'] =0;
         }

         $codes = "money,alipay" ;
         $payment = D('Payment')->where(array('code' => array('IN', $codes)))->select();

        $this->assign('ele', $eles);
        $this->assign('payment', $payment);
        $this->assign('useEnvelope',D('Order')->GetuseEnvelope($this->uid,$order['shop_id']));
        $this->display();
    }

    //重新计算配送费
	public function calculation(){
        if (IS_AJAX) {
            $addr_id = (int) $_POST['addr_id'];
            $shop_id = (int) $_POST['shop_id'];
            $type = (int) $_POST['type'];
            $order_id = (int) $_POST['order_id'];
            $obj = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
            $shop=D('Ele')->where(array('shop_id'=>$shop_id))->find();
            if (empty($obj)) {
                $this->ajaxReturn(array('status' => 'error','result'=>1, 'msg' => '改地址不存在'));
            }
            //查询当前配送数量
            $config=D('Setting')->fetchAll();
            $sum_num=$config['freight']['ele_num'];
            $peisongfei=$config['freight']['num_money'];
            $nums=D('Eleorder')->where(array('order_id'=>$order_id))->find();

            $ss=getDistance($shop['lat'],$shop['lng'],$obj['lat'],$obj['lng']);
            D('Eleorder')->where(array('order_id'=>$order_id))->save(array('distance'=>$ss));
            $sum=compare_price($ss,$type);
            if($nums['num']>$sum_num){
                $coun=($nums['num']-$sum_num)*$peisongfei;
                $sum_count=$sum+$coun;
            }else{
                $sum_count=$sum;
            }
            if($type==4){
                $sum_count=0;
            }

            if(false == D('Eleorder')->getAddrDistance($addr_id,$shop_id)){
                echoJson(['code'=>-1,'msg'=>'抱歉当前距离已超过配送范围','data'=>'']);
            }elseif($type==1 || $type==2){
                $data=[
                    'sum_count'=>round($sum_count, 2),
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功，配送费发生变化','data'=>$data]);
            }else{
                $data=[
                    'sum_count'=>round($sum_count,2),
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功','data'=>$data]);
            }
        }
    }

    //手动修改地址
    public function dizhi(){
        if (IS_AJAX) {
            $lng = $_POST['lng'];
            $lat = $_POST['lat'];
            $addname = $_POST['address'];
            cookie('lng',null);
            cookie('lat',null);
            cookie('addname',null);
            setcookie('lng',$lng);
            setcookie('lat',$lat);
            setcookie('addname',$addname,time()+1800);
            echoJson(['code'=>1]);

        }
    }



    public function pay2() {
        if(empty($this->uid)){
            $this->ajaxLogin();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Eleorder')->find($order_id);
        if(empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid){
            $this->tuMsg('该订单不存在');
            die;
        }
		
        $addr_id = (int) $this->_post('addr_id');
        $uaddr = D('Useraddr')->where('addr_id =' . $addr_id)->find();
        if(empty($addr_id) && $order['type']!=4){
            $this->tuMsg('请选择一个要配送的地址');
        }
        //新增
        $shop=D('Ele')->where(['shop_id'=>$order['shop_id']])->find();
		$xz = (int) $this->_post('yuyue');
        $times = $this->_post('times');
        if($shop['is_yuyue'] ==1 ){
            if(empty($xz)){
                $this->tuMsg('请选择是否要预约');
            }
            if($xz==1&&empty($times)){
                $this->tuMsg('请选择预约时间');
            }
            if($xz==1){
                $shijian=str_replace("T","",$times);
                $end=str_replace("-","",$shijian);
                $t=strtotime($end);
                if($t<=time()){
                    $this->tuMsg('请选择正确时间');
                }
            }
        }

       //结束
        D('Eleorder')->where(['order_id' => $order_id])->save(array('addr_id' => $addr_id, 'is_yuyue'=>$xz,'yuyuetime'=>$t));
        if (!($code = $this->_post('code'))){
            $this->tuMsg('请选择支付方式');
        }
		

		if( $order['type']!=4 && false == D('Eleorder')->getAddrDistance($addr_id,$order['shop_id'])){
			$this->tuMsg('您选择地址不在配送范围内');
		}
		//var_dump($code);die;
		//重写配送费
        if($this->ispost()){
            $order['need_pay']=I('post.shifu');
            $order['logistics']=I('post.pei');
            $envelope=I('post.envelope');
            $envelope_money=I('post.envelope_money');
            D('Eleorder')->where(array('order_id'=>$order_id))
                ->save(array('need_pay'=>$order['need_pay'],'logistics'=>$order['logistics'],'useEnvelope_id'=>$envelope,'useEnvelope'=>$envelope_money));

        }

//var_dump($order['need_pay']);var_dump($order['logistics']);die;
        if($code == 'wait'){
            D('Eleorder')->ele_delivery_order($order_id);//外卖配送接口
            D('Eleorder')->save(array('order_id' => $order_id, 'status' => 1));
            setcookie("ele", "", time() - 3600, "/");
            D('Eleorder')->save(array('order_id' => $order_id, 'is_daofu' => 1, 'status' => 1));
			D('Eleorder')->combination_ele_print($order_id, $addr_id);//外卖打印万能接口
            //D('Sms')->eleTZshop($order_id);
			D('Eleorder')->ele_month_num($order_id);//更新外卖销量
			D('Weixintmpl')->weixin_notice_ele_user($order_id,$this->uid,0);//外卖微信通知货到付款
            if(!empty($envelope)){
                $this->hongbaos($this->uid,$order_id,$envelope);
            }
            $this->tuMsg('货到付款您下单成功', U('user/eleorder/index'));
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->error('该支付方式不存在');
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('ele', $order_id);
            if(empty($logs)){
                $logs = array('type' => 'ele', 'user_id' => $this->uid, 'order_id' => $order_id, 'code' => $code, 'need_pay' => $order['need_pay'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip(), 'is_paid' => 0,'is_pin'=>$order['pin_user']);
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            //如果退款时间不为空，就限制退款时间
            $config = D('Setting')->fetchAll();
            if(!empty($config['complaint']['ele_time'])){
                $times=$config['complaint']['ele_time'];
                $now = date('Y-m-d H:i:s',time());
                $end_time=date("Y-m-d H:i:s",strtotime('+'.$times.'minutes',strtotime($now)));
                $end=strtotime($end_time);
                D('Eleorder')->where(array('order_id'=>$order_id))->save(array('tui_time'=>$end));
            }
            if(!empty($envelope)){
                $this->hongbaos($this->uid,$order_id,$envelope);
            }
            $this->tuMsg('选择支付方式成功，正在跳转到支付页面', U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }
   
   
    public function favorites(){
        if(empty($this->uid)){
            $this->ajaxLogin();
        }
        $shop_id = (int) $this->_get('shop_id');
        if(!($detail = D('Shop')->find($shop_id))){
            $this->error('没有该商家');
        }
        if($detail['closed']){
            $this->error('该商家已经被删除');
        }
        if(D('Shopfavorites')->check($shop_id, $this->uid)){
            $this->error('您已经收藏过了');
        }
        $data = array('shop_id' => $shop_id, 'user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
        if(D('Shopfavorites')->add($data)){
            $this->success('恭喜您收藏成功', U('ele/detail', array('shop_id' => $shop_id)));
        }
        $this->error('收藏失败');
    }
	
	
    public function detail(){
        $shop_id = (int) $this->_param('shop_id');
        if(!($detail = D('Ele')->find($shop_id))){
            $this->error('没有该商家');
            die;
        }
        if($detail['closed'] != 0 || $detail['audit'] != 1){
            $this->error('该商家不存在');
            die;
        }
        $this->assign('detail', $detail);
        $this->assign('shop', D('Shop')->find($shop_id));
        $this->assign('ex', D('Shopdetails')->find($shop_id));
        $this->display();
    }
	
	
    public function dianping(){
        $shop_id = (int) $this->_get('shop_id');
        if(!($detail = D('Ele')->find($shop_id))){
            $this->error('没有该商家');
            die;
        }
        if($detail['closed']){
            $this->error('该商家已经被删除');
            die;
        }
        $this->assign('detail', $detail);
        $this->display();
    }
    // public function dianpingloading(){
    //     $shop_id = (int) $this->_get('shop_id');
    //     if(!($detail = D('Ele')->find($shop_id))){
    //         die('0');
    //     }
    //     if($detail['closed'] != 0 || $detail['audit'] != 1){
    //         die('0');
    //     }
    //     $Eledianping = D('Eledianping');
    //     import('ORG.Util.Page');
    //     $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
    //     $count = $Eledianping->where($map)->count();
    //     $Page = new Page($count, 5);
    //     $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
    //     $p = $_GET[$var];
    //     if ($Page->totalPages < $p) {
    //         die('0');
    //     }
    //     $show = $Page->show();
    //     $list = $Eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
    //     $user_ids = $order_ids = array();
    //     foreach ($list as $k => $val) {
    //         $list[$k] = $val;
    //         $user_ids[$val['user_id']] = $val['user_id'];
    //         $order_ids[$val['order_id']] = $val['order_id'];
    //     }
    //     if (!empty($user_ids)) {
    //         $this->assign('users', D('Users')->itemsByIds($user_ids));
    //     }
    //     if (!empty($order_ids)) {
    //         $this->assign('pics', D('Eledianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
    //     }
    //     $this->assign('totalnum', $count);
    //     $this->assign('list', $list);
    //     $this->assign('detail', $detail);
    //     $this->display();
    // }
	//点评详情
    public function img(){
        $dianping_id = (int) $this->_get('dianping_id');
        if (!($detail = D('Eledianping')->where(array('dianping_id'=>$dianping_id))->find())){
            $this->error('没有该点评');
            die;
        }
        if ($detail['closed']) {
            $this->error('该点评已经被删除');
            die;
        }
        $list =  D('Eledianpingpics')->where(array('order_id' =>$detail['order_id']))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }


   public function gps($shop_id, $type = '0', $gps_type = 'shop'){
        
        if (!is_numeric($shop_id) || !in_array($type, ['0', '1', '2']) || !in_array($gps_type, ['shop', 'buyer'])) { 
            $this->error('该'. ($gps_type == 'shop' ? '商家' : '买家'). '信息有误');
        }
        
        
        if ($gps_type == 'buyer') { //如果买家, 则额外添加字段 shop_name
        $shop = D('shop')->where(array('shop_id'=>I('shop_id')))->find(); 
            $shop['shop_name'] = $shop['name'];
        }else{
        $shop = D('shop')->where(array('shop_id'=>I('shop_id')))->find($shop_id);   
            
        }
        $this->assign('shop', $shop);
        $this->assign('shop_id', $shop_id);
        $this->assign('gps_type', $gps_type);
        $this->assign('type', $type);
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lng'],$shop['lat']));
        $this->display();
    }
	

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


     public  function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
                {
                 $pi80 = M_PI / 180;
                 $lat1 *= $pi80;
                 $lng1 *= $pi80;
                 $lat2 *= $pi80;
                 $lng2 *= $pi80;
                 $r = 6372.797; // mean radius of Earth in km
                 $dlat = $lat2 - $lat1;
                 $dlng = $lng2 - $lng1;
                 $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
                 $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                 $km = $r * $c;
                 return ($miles ? ($km * 0.621371192) : $km);
                }

        //详细信息
        public function details($product_id){
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));

            if(empty($lat) || empty($lng)){
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $obj=D('Eleproduct');
            $detail=$obj->where(array('product_id'=>$product_id))->find();
            $ss=explode(',',$detail['collocation_id']);
            foreach ($ss as $val){
                $s=$ss;
            }
            $eleshop=D('Ele')->where(array('shop_id'=>$detail['shop_id']))->find();
            $this->assign('eleshop',$eleshop);
            $shop=D('Eleproduct')->where(array('shop_id'=>$detail['shop_id'],'product_id'=>array('IN',$s)))->limit(0,6)->select();
            //var_dump($shop);die;
            $weisheng=M('shop_audit')->where(['shop_id'=>$detail['shop_id']])->find();
            $this->assign('weisheng',$weisheng['weisheng'].'?x-oss-process=style/atufafa');
            $tuan=D('Eleproduct');
            $map=array('closed'=>0,'is_tuan'=>1,'store'=>1);
            $count=$tuan->where($map)->count();
            $lis=$tuan->where($map)->select();
            foreach ($lis as $key=>$val){
                $shop_id['shop_id']=$val['shop_id'];
                $is_open=M('ele')->where(['shop_id'=>$val['shop_id']])->getField('is_open');
                if($is_open==0){
                    unset($lis[$key]);
                }
            }

            $list = $obj->where(['closed'=>0,'is_huodong'=>0,'audit'=>1,'shop_id'=>$detail['shop_id']])->order(array('sold_num' =>'desc','price' => 'asc'))->select();

            foreach ($list as $k => $val){

                $list[$k]['cart_num'] = $this->cart[$val['product_id']]['cart_num'];
            }

            foreach($list as $k => $val){
                if($val['cate_id']) {
                    $cate_ids[$val['cate_id']] = $val['cate_id'];
                }
                $list[$k] = $val;
            }
            if($cate_ids) {
                $cates = D('Elecate')->itemsByIds($cate_ids);
                $ids = array();
                foreach($cates as $k => $val){
                    $ids[][] = $k;
                }
                ksort($ids);
                $showcates = array();
                foreach ($ids as $arr1) {
                    foreach ($arr1 as $val) {
                        $showcates[$val] = $cates[$val];
                    }
                }
                $this->assign('cate', $showcates);
            }

            $spec=M('EleSpec')->where(['shop_id'=>$detail['shop_id'],'type_id'=>$detail['spec_type']])->find();
            $mapg['id']=array('in',$detail['spec_id']);
            $guige=M('EleSpecItem')->where($mapg)->select();
            $this->assign('guige',$guige);
            $this->assign('spec',$spec);
            $this->assign('cailist', $list);
            $this->assign('cates', D('Elecate')->where(array('shop_id' => $detail['shop_id'], 'closed' => 0))->select());//分类
            $this->assign('tuijian', $tuijian = $obj->where(array('shop_id' => $detail['shop_id'],'is_tuijian'=>1,'is_huodong'=>0, 'closed' =>0, 'audit' => 1, 'cost_price' => array('neq','')))->order(array('create_time' =>'desc'))->limit(0,4)->select());//推荐开始
            $dleproduc=D('Eleproduct')->where(array('shop_id'=>$detail['shop_id'],'closed'=>0))->select();
            $this->assign('shoplist',$dleproduc);
            $this->assign('tuan',$lis);
            $this->assign('page',$count);
            $this->assign('shop',D('Shop')->itemsByIds($shop_id));
            $fee=Distributionfee($lat,$lng,$shop_id,1);
            $logistics=session('elelogistics');
            $zdlogistics=session('elezdlogistics');
            if(!empty($logistics) || !empty($zdlogistics)){
                session('elelogistics',null);
                session('elezdlogistics',null);
            }
            session('elelogistics',$fee['logistics']);
            session('elezdlogistics',$fee['zdlogistics']);
            $this->assign('logistics', $fee['logistics']);
            $this->assign('zdlogistics', $fee['zdlogistics']);
            $this->assign('pintuan',D('Eleproduct')->where(array('is_tuan'=>1))->select());
            $this->assign('list',$shop);
            $this->assign('detail',$detail);

            $Eledianping = D('Eledianping');
            import('ORG.Util.Page');
            $map = array('closed' => 0, 'shop_id' => $detail['shop_id'], 'show_date' => array('ELT', TODAY));
            $count = $Eledianping->where($map)->count();
            $Page = new Page($count, 5);
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
            $show = $Page->show();
            $lists = $Eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = array();
            foreach ($lists as $k => $val) {
                $lists[$k] = $val;
                $user_ids[$val['user_id']] = $val['user_id'];
                $order_ids[$val['order_id']] = $val['order_id'];
            }
            if (!empty($user_ids)) {
                $this->assign('users', D('Users')->itemsByIds($user_ids));
            }
            if (!empty($order_ids)) {
                $this->assign('pics', D('Eledianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
            }
            $this->assign('totalnum', $count);
            $this->assign('lists', $lists);

            //商家
            $tu=D('Shoppic')->where(array('shop_id'=>$detail['shop_id']))->select();
            $times=D('Ele')->where(array('shop_id'=>$detail['shop_id']))->find();
            $this->assign('times',$times);
            $this->assign('sjtu',$tu);

            //查询配置
            $config=D('Setting')->fetchAll();

            $this->assign('config',$config);
            $this->display();

        }

//        //分享
//        public function fenxian(){
//            $config=D('Setting')->fetchAll();
//            $obj=D('Eleshare');
//            if($this->ispost()){
//                $product_id=I('post.product_id');
//                $shop=I('post.shop_id');
//
//                $set= (int) $config['ele']['times'];
//                $num1=(int) $config['ele']['num1'];
//                $num2=(int) $config['ele']['num2'];
//                $nums=mt_rand($num1,$num2);
//                $nowtime=date('Y:m:d H:i:m',time());
//                $bg=date("Y-m-d H:i:s",strtotime('+'.$set.'hours',strtotime($nowtime)));
//                $endtime=strtotime($bg);
//                $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'product_id'=>$product_id,'closed'=>0))->find();
//                if(empty($cunzai)){
//                    $data=array();
//                    $data['product_id']=$product_id;
//                    $data['shop_id']=$shop;
//                    $data['user_id']=$this->uid;
//                    $data['create_time']=NOW_TIME;
//                    $data['create_ip']=get_client_ip();
//                    $data['end_time']=$endtime;
//                    $data['num']=$nums;
//                    $obj->add($data);
//                }
//            }
//        }
    
    //添加到购物车
    public function addCart(){
        $shop_id = I('post.shop_id');
        $product_id = I('post.product_id');

        $goods_ele = cookie('goods_ele');
        if(isset($goods_ele[$shop_id])){
            $this->ajaxReturn(array('status' => 'success', 'msg' => '店铺已收藏，请勿重复收藏！'));
        }else{
            $goods_ele[$shop_id] = 1;
            cookie('goods_ele', $goods_ele, 60 * 60 * 24 * 7);

            $this->ajaxReturn(array('status' => 'success', 'msg' => '收藏店铺成功！'));
        }
    }

    //外卖购物车
    public function cart(){
        $goods = cookie('goods');
        $back = end($goods);
        $back = key($goods);
        $goods_ele = cookie('goods_ele');
        $this->assign('back', $back);
		
        if(empty($goods_ele)) {
            $this->error('亲还没有选购产品呢!', U('ele/index'));
        }
         
        $goods_ids= $this->get_goods_ids($goods_ele);
        foreach($goods_ids as $k=> $v){
           $cart_goods[] = D('shop')->where(array('shop_id'=>$v))->find();
        }

        // print_r($cart_goods);

        $this->assign('cart_goods', $cart_goods);
        // $this->assign('cartnum', count($cart_goods));
		
        $this->display();
    }

    public function cartdel(){
        $goods_spec = I('post.goods_spec');  //商店ID号
        $goods_ele = cookie('goods_ele');
        if (isset($goods_ele[$goods_spec])) {
            unset($goods_ele[$goods_spec]);
            cookie('goods_ele', $goods_ele, 604800);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '删除失败'));
        }
    }

    private function spec_to_arr($goods_spec){
        $spec_key = array_keys($goods_spec);

        foreach($spec_key as $k=> $v){
            $spec_arr[$k] = explode('|',$v); 
            $spec_arr[$k][]= $goods_spec[$v];
        }
        return $spec_arr;
    }
    
    private function get_goods_ids($goods_spec){
        $spec_arr = $this -> spec_to_arr($goods_spec);
        foreach($spec_arr as $k => $v){
                $goods_ids[] = $v[0];
            }		
        return $goods_ids;
    }

        //通知广告
    public function notice(){
        $this->assign('notice',D('NoticeEleStoreMarket')->where(['type'=>1])->select());
        $this->display();
    }

    //0元起送活动
    public function zero(){
        $this->display();
    }
    public function zeroloadata(){
        $obj=D('Ele');
        import('ORG.Util.Page');
        $map = array('audit'=>1,'city_id'=>$this->city_id,'since_money'=>0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list=$obj->where($map)->select();
        foreach ($list as $val){
            $shop_id[]=$val['shop_id'];
        }
        $this->assign('shops',D('Shop')->itemsByIds($shop_id));
        $this->assign('list',$list);
        $this->display();
    }

    //热卖活动
    public function heat(){
        $this->display();
    }

    public function heatloadata(){
        $obj=D('Eleproduct');
        import('ORG.Util.Page');
        $map = array('audit' => 1,'closed'=>0,'is_tuan'=>1);
        $list=$obj->where($map)->order('month_num desc')->select();

        $this->assign('list',$list);
        $this->display();
    }

    //大牌满减
    public function reduction(){
        $this->display();
    }
     public function reductionloadata(){
         $obj=D('Ele');
         import('ORG.Util.Page');
         $map = array('audit' => 1,'is_full'=>1,'is_open'=>1);
         $count = $obj->where($map)->count();
         $Page = new Page($count, 5);
         $show = $Page->show();
         $list=$obj->where($map)->order('order_price_reduce_1 desc')->select();
         $shop_id=array();
         foreach ($list as $val){
             $shop_id[]=$val['shop_id'];
         }
         $this->assign('shops',D('Shop')->itemsByIds($shop_id));
         $this->assign('list',$list);
         $this->display();
     }

    //分享抢红包
    public function share(){
        $tuan=D('Eleproduct');
        $map=array('closed'=>0,'audit'=>1,'is_tuan'=>1);
        $lis=$tuan->where($map)->order('tuan_money desc')->select();
        foreach ($lis as $val){
            $shop_id['shop_id']=$val['shop_id'];
        }
        $this->assign('tuan',$lis);
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $this->display();
    }

    public function foodtype(){
        if(IS_AJAX){
            $food_id=I('post.food_id');
            $shop_id=I('post.shop_id');
            $goods=D('Eleproduct')->where(['product_id'=>$food_id,'shop_id'=>$shop_id])->find();
            $type=M('EleSpec')->where(['shop_id'=>$shop_id,'type_id'=>$goods['spec_type']])->find();
            $map['id']=array('in',$goods['spec_id']);
            $spe=M('EleSpecItem')->where($map)->select();
            if(!empty($spe)){
             echoJson(['code'=>1,'data'=>$spe,'data2'=>$type]);
            }
        }
    }


}