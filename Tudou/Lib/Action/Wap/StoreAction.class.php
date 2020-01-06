<?php
class StoreAction extends CommonAction{
    protected $cart = array();
    public function _initialize(){
        parent::_initialize();
        $this->cart = $this->getcart();
        $this->assign('cartnum', (int) array_sum($this->cart));
        $cate = D('Store')->getStoreCate();
        $this->assign('storecate', $cate);
        $cate_ids = D('Store')->getEleCateIds();
        $this->assign('elecates',$cate_ids);
        $cate_idss = D('Store')->getAllEleCate();
        $this->assign('elecatess',$cate_idss);
		if(empty($this->_CONFIG['operation']['store'])){
			$this->error('便利店功能已关闭');die;
		}

        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
        //天天特价
        $elespecial=D('GoodsEleStoreMarket')->where(['type'=>2,'type_id'=>4])->select();
        foreach ($elespecial as $value){
            $goods_id[]=$value['product_id'];
        }
        $this->assign('goods',D('Storeproduct')->itemsByIds($goods_id));
        $this->assign('elespecial',$elespecial);
        //团购
        $eletaun=D('GoodsEleStoreMarket')->where(['type'=>2,'type_id'=>6])->select();
        foreach ($eletaun as $value){
            $goods_ids[]=$value['product_id'];
        }
        $this->assign('goodstuan',D('Storeproduct')->itemsByIds($goods_ids));
        $this->assign('eletaun',$eletaun);
        //秒杀
        $elespike=D('GoodsEleStoreMarket')->where(['type'=>2,'type_id'=>5])->select();
        foreach ($elespike as $value){
            $goods_idss[]=$value['product_id'];
        }
        $this->assign('goodssha',D('Storeproduct')->itemsByIds($goods_idss));
        $this->assign('elespike',$elespike);

        //查询广告位
        $this->assign('elenotice',D('NoticeEleStoreMarket')->where(['type'=>2])->select());
    }
    public function getcart(){
        $shop_id = (int) $this->_param('shop_id');
        $cart = (array) json_decode($_COOKIE['store']);
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
        $eleproducts = D('Storeproduct')->itemsByIds($ids);
        foreach ($eleproducts as $k => $val) {
            $eleproducts[$k]['cart_num'] = $nums[$val['product_id']];
            $eleproducts[$k]['total_price'] = $nums[$val['product_id']] * $val['price'];
        }
        return $eleproducts;
    }
  
  
//   public function loadcart(){
//        if($goods = cookie('store')) {
//            $total = array('num' => 0, 'money' => 0);
//            $goods = (array) json_decode($goods);
//            $ids = array();
//            foreach ($goods as $shop_id => $items) {
//                foreach ($items as $k2 => $item) {
//                    $item = (array) $item;
//                    $total['num'] += $item['num'];
//                    $total['money'] += $item['price'] * $item['num'];
//                    $ids[] = $item['product_id'];
//                    $product_item_num[$item['product_id']] = $item['num'];
//                    $spec[]=$item['spec'];
//
//                }
//            }
//            $ids = implode(',', $ids);
//            $products = D('Storeproduct')->where('closed=0')->select($ids);
//            foreach ($products as $k => $val) {
//                $products[$k]['cart_num'] = $product_item_num[$val['product_id']];
//            }
//
//            $this->assign('cartgoods', $products);
//			$this->display();
//        }
//    }


    public function loadcart(){
        if($goods = cookie('store')) {
            $total = array('num' => 0, 'money' => 0);
            $goods = (array) json_decode($goods,true);
            $ids =$productm = array();
            foreach ($goods as $shop_id => $items) {
                foreach ($items as $k2 => $item) {
                    $item = (array) $item;
                    $total['num'] += $item['num'];
                    $total['money'] += $item['price'] * $item['num'];
                    $ids[] = $item['product_id'];
                    $product_item_num[$item['product_id']] = $item['num'];
                    $spec[]=$item['spec'];
                }
            }
            $ss=$goods;
//            foreach ($goods as $shop=>$ite){
//                foreach ($ite as $k =>$it){
//                    $productm[]=$it['product_id'];
//                }
//            }
            foreach($ss as $k => $v)
            {
                foreach($v as $key=>$val)
                {
                    $arr2[]=$val;
                    $sum['num'] += $val['num'];
                    $sum['money'] += $val['price'] * $val['num'];
                    $p_id[]=$val['product_id'];
                    $produc[$val['product_id']]=$val['num'];
                    $spc_id[]=$val['spec'];
                }
            }

            $this->assign('goods',$arr2);
            $sss=D('StoreSpecItem')->itemsByIds($spc_id);
            foreach ($sss as $ks => $vals){
                $sss[$k]['ss'] = $produc[$vals['product_id']];
            }
            $this->assign('spec',$sss);
            $this->assign('food',D('Storeproduct')->itemsByIds($p_id));
            $products = D('Storeproduct')->where('closed=0')->itemsByIds($p_id);
            foreach ($products as $k => $val) {
                $products[$k]['cart_num'] = $product_item_num[$val['product_id']];
            }
            $this->assign('cartgoods', $products);
			$this->display();
        }
    }

    //加入到购物车
    public function addCart(){
        $shop_id = I('post.shop_id');  //商店ID号
        $product_id = I('post.product_id'); //商店产品ID号

        $goods_store = cookie('goods_store');
        if(isset($goods_store[$product_id."|"])){
            $this->ajaxReturn(array('status' => 'success', 'msg' => '已加入购物车，请勿重复加入！'));
        }else{
            $goods_store[$product_id."|"] = 1;
            cookie('goods_store', $goods_store, 60 * 60 * 24 * 7);

            $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功！'));
        }
    }

    //便利店购物车
    public function cart(){
		
        $goods = cookie('goods');
        $back = end($goods);
        $back = key($goods);
        $goods_store = cookie('goods_store');
        $this->assign('back', $back);
		
        if(empty($goods_store)) {
            $this->error('亲还没有选购产品呢!', U('store/index'));
        }
		
        $spec_keys = array_keys($goods_store);
        $spec_arr = $this ->spec_to_arr($goods_store);           
        $goods_ids= $this->get_goods_ids($goods_store);

        foreach($goods_ids as $k=> $v){
           $cart_goods[] = D('Storeproduct')->where(array('product_id'=>$v))->find();
        }

        foreach($cart_goods as $key => $val){
            $cart_goods[$key]['buy_num'] = 1;
        }
		
		// print_r($cart_goods);
        $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('cart_goods', $cart_goods);
        $this->assign('cartnum', count($cart_goods));
		
        $this->display();
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

    public function cartdel(){
        $goods_spec = $_POST['goods_spec'];
        $goods_store = cookie('goods_store');
        if (isset($goods_store[$goods_spec."|"])) {
            unset($goods_store[$goods_spec."|"]);
            cookie('goods_store', $goods_store, 604800);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '删除失败'));
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

        $adds=cookie('storeadd');
        $this->assign('adds',$adds);

        $this->assign('nextpage', LinkTo('store/loaddata', $linkArr, array('t' => NOW_TIME, 'p' => '0000')));
        $this->assign('linkArr', $linkArr);
		$lists = D('Storeproduct')->where(array('is_tuijian'=>1, 'audit' => 1, 'cost_price' => array('neq','')))->order(array('sold_num' => 'desc','create_time' => 'desc'))->limit(0,6)->select();
        $this->display();
    }
	


	
	
    public function loaddata(){
        $obj = D('Store');
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


    if(empty($keyword) && $keyword!='输入便利店的关键字') {
        $lists = $obj->order($orderby)->where($map)->select();
        // echo $obj->getLastSql();die;
        foreach ($lists as $k => $val) {
			//if(!is_QQBrowser()){
				$lists[$k]['radius'] = $val['is_radius'];
				$lists[$k]['is_radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
				if (!empty($val['is_radius'])) { 
				   if ($lists[$k]['is_radius'] > $val['is_radius']*10000) { 
					   unset($lists[$k]);
					}
				}
			//}
            if (!empty($cate)) {
                if (strpos($val['cate'], $cate) === false) {
                    unset($lists[$k]);
                }
            }
			
        }
        $count = count($lists);
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = array_slice($lists, $Page->firstRow, $Page->listRows);
    }else{
        $where['product_name']=array('LIKE', '%' . $keyword . '%');
        $m = M("store_product as e");
        $listss=$m->order($orderby)
            ->join("LEFT JOIN tu_store as el ON e.shop_id = el.shop_id")
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
            if (!empty($cate)) {
                if (strpos($val['cate'], $cate) === false) {
                    unset($lists[$k]);
                }
            }
            $count = count($listss);
            $Page = new Page($count, 10);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
        }

        $list = array_slice($listss, $Page->firstRow, $Page->listRows);
    }
         //查询配送费阶段
        $config = D('Setting')->fetchAll();
        $shop_ids = array();
        foreach ($list as $k => &$val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
			$list[$k]['score'] = D('Storedianping')->getShopScore($val['shop_id']);
            if($this->closeshopele($val['busihour'])){
                $list[$k]['bsti'] = 1;
            }else{
                $list[$k]['bsti'] = 0;
            }
       }
		
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
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
            setcookie('storeadd',$addname,time()+1800);
            echoJson(['code'=>1]);

        }
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
	
	
	//便利店列表
    public function shop($shop_id = 0){
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
		$linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;
		
		$cat = $this->_param('cat', 'htmlspecialchars');
        $this->assign('cat', $cat);
        $linkArr['cat'] = $cat;
		
		$cate_id = $this->_param('cate_id', 'htmlspecialchars');
        $this->assign('cate_id', $cate_id);
        $linkArr['cate_id'] = $cate_id;

        //商城团购
        $tuans=D('Goods');
        $ma=array('is_pintuan'=>1,'closed'=>0);
        $c=$tuans->where($ma)->count();
        $listss=$tuans->where($ma)->select();
        foreach ($listss as $val){
            $shop2['shop_id']=$val['shop_id'];
        }

        $this->assign('shop2',D('Shop')->itemsByIds($shop2));
        $this->assign('cou',$c);
        $this->assign('goodstuan',$listss);
		
        $shop_id = $this->_param('shop_id', 'htmlspecialchars');
        $this->assign('shop_id', $shop_id);
        $linkArr['shop_id'] = $shop_id;
        $weisheng=M('shop_audit')->where(['shop_id'=>$shop_id])->find();
        $this->assign('weisheng',$weisheng['weisheng'].'?x-oss-process=style/atufafa');
		
        if(!($detail = D('Store')->find($shop_id))){
            $this->error('该便利店不存在');
        }
        if(!($shop = D('Shop')->find($shop_id))){
            $this->error('该便利店不存在');
        }
		if($this->closeshopele($detail['busihour'])){
           $detail['bsti'] = 1;
        }else{
           $detail['bsti'] = 0;
        }
		$this->assign('nextpage', LinkTo('store/load2', $linkArr, array('t' => NOW_TIME, 'p' => '0000')));
        $this->assign('linkArr', $linkArr);
		
        $this->assign('cates', D('Storecate')->where(array('shop_id' => $shop_id, 'closed' => 0))->select());
        //将物流费用存入session中

        session('logisticsfee',$detail['logistics_money']);
        //物流费用
        $wuliu=session('logisticsfee');
        $fee=Distributionfee($lat,$lng,$shop_id,3);
        session('storelogistics',$fee['logistics']);
        session('storezdlogistics',$fee['zdlogistics']);
        $this->assign('wuliu',$wuliu);
        $this->assign('logistics', $fee['logistics']);
        $this->assign('zdlogistics', $fee['zdlogistics']);
        $this->assign('listss', $list);
        $this->assign('shop', $shop);
		$this->assign('detail', $detail);

        //评价
         $shop_id = (int) $this->_get('shop_id');
        if (!($detail = D('Store')->find($shop_id))) {
            die('0');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            die('0');
        }
        $obj = D('Storedianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
        $count = $obj->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $show = $Page->show();
        $lists = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
            $this->assign('pics', D('Storedianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }

        $this->assign('totalnum', $count);
        $this->assign('lists', $lists);
        $this->assign('detail', $detail);

        //商家信息
         $shop_id = $this->_param('shop_id');
         $tu=D('Shoppic')->where(array('shop_id'=>$shop_id))->select();

         //拼团
        $tuan=D('Storeproduct');
        $maps=array('closed'=>0,'is_tuan'=>1,'store'=>1);
        $count=$tuan->where($maps)->count();
        $lis=$tuan->where($maps)->select();
        foreach ($lis as $key=>$val){
            $shop_id['shop_id']=$val['shop_id'];
            $is_open=M('store')->where(['shop_id'=>$val['shop_id']])->getField('is_open');
            if($is_open==0){
                unset($lis[$key]);
            }
        }
        $this->assign('count',$count);
        $this->assign('tuan',$lis);
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));




         $times=M('store')->where(array('shop_id'=>$shop_id))->find();
         $this->assign('times',$times);
        $this->assign('sjtu',$tu);
        $this->assign('shops', D('Shop')->itemsByIds($shop_id));   
        $this->display();
    }
	
	//便利店菜品列表
    public function load2($shop_id = 0){
		import('ORG.Util.Page');
		$shop_id = $this->_param('shop_id');
		if(!($detail = D('Store')->find($shop_id))){
            $this->error('该便利店不存在');
        }
        if(!($shop = D('Shop')->find($shop_id))){
            $this->error('该便利店不存在');
        }
		if($this->closeshopele($detail['busihour'])){
           $detail['bsti'] = 1;
        }else{
           $detail['bsti'] = 0;
        }
		$this->assign('shop', $shop);
		$this->assign('detail', $detail);
		
		$obj = D('Storeproduct');
		$map = array('closed' => 0, 'audit' => 1, 'shop_id' => $shop_id,'is_huodong'=>0);
		if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['product_name|desc'] = array('LIKE', '%' . $keyword . '%');
			$this->assign('keyword', $keyword);	
        }
		
		
		$cates = D('Storecate')->fetchAll();
        $cat = (int) $this->_param('cat');
        $cate_id = (int) $this->_param('cate_id');
        if($cat){
            if(!empty($cate_id)) {
                $map['cate_id'] = $cate_id;
            }else{
                $catids = D('Storecate')->getChildren($cat);
                if(!empty($catids)){
                    $map['cate_id'] = array('IN', $catids);
                }
            }
        }
        $this->assign('cat', $cat);
        $this->assign('cate_id', $cate_id);
		
		$count = $obj->where($map)->count();
        $Page = new Page($count, 8);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $config=D('Setting')->fetchAll();
        $this->assign('config',$config);
		$list = $obj->where($map)->order(array('sold_num' =>'desc','price' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('cates', D('Storecate')->where(array('shop_id' => $shop_id, 'closed' => 0))->select());
		$this->display();
	}
	
	
	
    public function order($type,$user=0){
        $_SESSION['price_type'] = $type ;
        if(empty($this->uid)){
            $this->error('请先登陆', U('passport/login'));
        }
		
		
		if($lists = cookie('store')){
            $lists = (array) json_decode($lists,true);
            $list = array();
			foreach($lists as $key=>$val){
				foreach($val as $k=>$v){
				  $list[] = $v;
				}
			}
        }

        if(empty($list)){
            $this->error('您还没有下单呢');
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
            $product = D('Storeproduct')->find($val['product_id']);
            $product_name[] = $product['product_name'];
            if (empty($product)) {
				$this->error('产品不正确');
            }
            $shop_id = $product['shop_id'];
            $product['buy_num'] = $val['num'];
            $product['spec']=$val['spec'];
            $product['moneys']=$val['price'];
            $products[$key] = $product;
            $shops[$shop_id] = $shop_id;
            $total['money'] += $val['price'] * $val['num'];
            $food=$val['price'] * $val['num'];
            $total['num'] += $val['num'];
			$total['tableware_price'] += $product['tableware_price'] * $val['num'];
			$settlement_price  += ($product['settlement_price'] * $val['num'])+($product['tableware_price']*$val['num']);
        }
        if (count($shops) > 1) {
            $this->error('您购买的商品是2个商户的');
        }
        if (empty($shop_id)) {
            $this->error('商家不存在');
        }
        $shop = D('Store')->find($shop_id);
        if (empty($shop)) {
            $this->error('该商家不存在');
        }
		
        if (!$shop['is_open']) {
            $this->error('商家已经打烊，实在对不住客官');
        }
		$busihour = $this->closeshopele($shop['busihour']);
		 if ($busihour == 1) {
            $this->error('商家休息中，请稍后再试');
        }
		
		$logistics_full_money = D('Storeorder')->get_logistics($total['money'],$shop_id);//获取配送费用

        //var_dump($logistics_full_money);die;
        $logistics=session('storelogistics');
        $zdlogistics=session('storezdlogistics');
        //echo $type;die

        //计算商品重量
        $config=D('Setting')->fetchAll();
        $sum=$config['freight']['store_num'];
        $peisongfei=$config['freight']['store_moneys'];

        foreach($list as $key => $val) {
            if($val['num']>$sum){
                $coun=($val['num']-$sum)*$peisongfei;
            }
        }

        //快递费
        $express=session('logisticsfee');
        if($type==1){
            $total['money'] += $logistics+$coun;
            $total['logistics_full_money']=$logistics+$coun;
            session('zdlogistics2',$logistics+$coun);
        }elseif($type==2){
            $total['money'] += $zdlogistics+$coun;
            $total['logistics_full_money']=$zdlogistics+$coun;
            session('zdlogistics2',$zdlogistics+$coun);
        }elseif($type==3){
            $total['money'] += $express;
            $total['logistics_full_money']=$express;
            session('zdlogistics2',$express);
        }elseif($type==4){
            $total['money'] += 0;
            $total['logistics_full_money']=0;
            session('zdlogistics2',0);
        }
        
		
        //$total['money'] += $shop['logistics'];//加上配送费
		//$total['money'] += $total['tableware_price'];//加上餐具费
		
        $total['need_pay'] = $total['money'];
		
		$total['full_reduce_price'] = D('Storeorder')->get_full_reduce_price($food,$shop_id);//获取满减费用
        if ($shop['since_money'] > $total['money']) {
            $this->error('客官，您再订点吧');
        }
		//新客户满多少减去多少
        if ($shop['is_new'] && !D('Storeorder')->checkIsNew($this->uid, $shop_id)) {
            if ($total['money'] >= $shop['full_money']) {
                $total['new_money'] = $shop['new_money'];
            }
        };
		
		
		//结算金额逻辑后期封装，如果是第三方配送，如果开通新单立减后，配送费用商家出，如果商家开启满减优惠，满减优惠商家出
		if($total['logistics_full_money']){
		    //var_dump(11);
			$logistics = 0;
			$shop_detail = D('Shop')->find($shop_id);
			//var_dump($settlement_price,$total['logistics_full_money'],$total['full_reduce_price']);die;
			if($shop_detail['is_store_pei'] == 1){
				$last_settlement_price = $settlement_price - $total['full_reduce_price'];
			}
		}else{
		   // var_dump(222);
			$logistics = $shop['logistics'];
			$last_settlement_price = $settlement_price - $total['full_reduce_price'];
		}

		//$total['need_pay'] = $total['need_pay'] - $total['new_money'] - $total['logistics_full_money'] - $total['reduce_coupun_money']- $total['full_reduce_price']+$total['tableware_price'];
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
            $total['need_pay'] = $total['need_pay'] - $total['new_money']- $full_reduce_price-$full_price+$total['tableware_price'];
        }else{
            $totals['logistics_full_money']= $total['logistics_full_money'];
            $total['need_pay'] = $total['need_pay'] - $total['new_money'] - $total['logistics_full_money'] - $full_reduce_price-$full_price+$total['tableware_price'];
        }

        if($shop['is_guozi']==1){
            $guoz=$total['num'];
        }else{
            $guoz=null;
        }
       // var_dump($last_settlement_price);die;
        $month = date('Ym', NOW_TIME);
        if ($order_id = D('Storeorder')->add(array(
			'user_id' => $this->uid, 
			'shop_id' => $shop_id, 
			'total_price' => $total['money'], 
			'need_pay' => $total['need_pay'], 
			'num' => $total['num'],
			'download_coupon_id'=>$coupon_id,
			'reduce_coupun_money'=>$full_price,
			'new_money' => (float) $total['new_money'],
			'logistics_full_money' => (float) $totals['logistics_full_money'],
			'full_reduce_price' => (float) $full_reduce_price,
			'logistics' =>(float) $total['logistics_full_money'],
			'tableware_price' => (float) $total['tableware_price'],
			'settlement_price' => $last_settlement_price, 
			'status' => 0, 
            'type'=>$type,
			'pin_user'=>$user,
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip(), 
			'is_pay' => 0, 
			'month' => $month,
            'fruit'=>$guoz
		))) {
            foreach ($products as $val) {
                D('Storeorderproduct')->add(array(
					'order_id' => $order_id, 
					'product_id' => $val['product_id'], 
					'num' => $val['buy_num'], 
					'total_price' => $val['moneys'] * $val['buy_num'],
					'tableware_price' => $val['tableware_price'] * $val['buy_num'], 
					'month' => $month,
                    'spec'=>$val['spec']
				));
            }
            setcookie('store', '', time() - 3600, '/');
            $this->success('便利店下单成功！您可以选择配送地址', U('store/pay', array('order_id' => $order_id)));
        }
        $this->error('创建订单失败');
    }
	
    public function message(){
        $order_id = (int) $this->_get('order_id');
        if (!($detail = D('Storeorder')->find($order_id))) {
            $this->tuMsg('没有该订单');
            die;
        }
        if ($detail['status'] != 0) {
            $this->tuMsg('参数错误');
            die;
        }
        $ele_shop = D('Store')->find($detail['shop_id']);
        $tags = $ele_shop['tags'];
        $tagsarray = array();
        if(!empty($tags)){
            $tagsarray = explode(',', $tags);
        }
        if($this->isPost()){
            if ($message = $this->_param('message', 'htmlspecialchars')){
                $data = array('order_id' => $order_id, 'message' => $message);
                if (D('Storeorder')->save($data)) {
                    $this->tuMsg('添加留言成功', U('Wap/store/pay', array('order_id' => $detail['order_id'])));
                }
            }
            $this->tuMsg('请填写留言');
        }else{
            $this->assign('detail', $detail);
            $this->assign('tagsarray', $tagsarray);
            $this->display();
        }
    }
    public function pay(){
        $type  =  isset($_SESSION['price_type']) ?$_SESSION['price_type']:"";
        if (empty($this->uid)) {
            header('Location:' . U('passport/login'));
            die;
        }
        $this->check_mobile();
        $order_id = (int) $this->_get('order_id');
        $order = D('Storeorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $this->assign('shop', D('Store')->find($order['shop_id']));
        $ordergoods = D('Storeorderproduct')->where(array('order_id' => $order_id))->select();
        $goods = $spec=array();
        foreach ($ordergoods as $key => $val) {
            $goods[$val['product_id']] = $val['product_id'];
            $spec[$val['spec']] = $val['spec'];
        }
        $this->assign('spec',D('StoreSpecItem')->itemsByIds($spec));
        if($this->isPost()){
            $useEnvelope = $_POST['useEnvelope'];
            D('Storeorder')->where(['order_id'=>$order_id])->save(['useEnvelope'=>$useEnvelope,'useEnvelope_id'=>$_POST['useEnvelope_id']]);
        }
        $products = D('Storeproduct')->itemsByIds($goods);
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

        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('useEnvelope',D('Order')->GetuseEnvelope($this->uid,$order['shop_id']));
        $this->assign('order', $order);
        $eles = D('Store')->find($order['shop_id']);
        if($type==2){
            $eles['zdlogistics']=$eles['zdlogistics'];

        }
        //运费
        $yun=session('logisticsfee');
         if($type==1){
            $eles['logistics'] = $p_data['logistics'];
        }elseif($type==2){
            $eles['logistics'] = $p_data['zdlogistics'];
        }elseif ($type==3){
             $eles['logistics']=$yun;
         }elseif ($type==4){
             $eles['logistics']=0;
         }

        if ($eles['is_pay'] == 1) {
            $payment = D('Payment')->getPayments(true);
        } else {
            $payment = D('Payment')->getPayments_delivery(true);
        }


          //判断是否减配送费
          $is_logistics_full_money = 0 ;
          $has_logistics_full_money = D('Storeorder')->get_logistics($order['total_price'] ,$order['shop_id'] );

        if( $has_logistics_full_money ){
            $is_logistics_full_money = 1 ; 
        }
         //var_dump($has_logistics_full_money);die;
        $this->assign('is_logistics_full_money', $is_logistics_full_money); 

        // print_r($payment);die;
        $this->assign('payment', $payment);
        $this->display();
    }

    //重新计算配送费
    public function calculation(){
        if (IS_AJAX) {
            $addr_id = (int) $_POST['addr_id'];
            $shop_id = (int) $_POST['shop_id'];
            $type = (int) $_POST['type'];
            $order_id = (int) $_POST['order_id'];
            //var_dump($addr_id,$shop_id,$type);die;
            $config = D('Setting')->fetchAll();
            //专送
            $pesongfei=(float) $config['freight']['store_money'];//起步价
            $qibu= (float) $config['freight']['store_distance'];//起步距离
            $jjiajia= (float) $config['freight']['store_chajia'];//超过基础公里加价
            //直达
            $pesongfei2=(float) $config['freight']['zdstore_money'];//起步价
            $qibu2= (float) $config['freight']['zdstore_distance'];//起步距离
            $jjiajia2= (float) $config['freight']['zdstore_chajia'];//超过基础公里加价

            $obj = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
            $shop=D('Store')->where(array('shop_id'=>$shop_id))->find();
            if (empty($obj)) {
                $this->ajaxReturn(array('status' => 'error','result'=>1, 'msg' => '改地址不存在'));
            }


            $ss=getDistance($shop['lat'],$shop['lng'],$obj['lat'],$obj['lng']);
           
            if(false == D('Storeorder')->where(array('order_id'=>$order_id))->save(array('distance'=>$ss))){
                echoJson(['code'=>-1,'msg'=>'操作失败，请重试','data'=>'']);
            }
            $config=D('Setting')->fetchAll();
            $sum_num=$config['store']['store_num'];
            $stores=D('Storeorder')->where(array('order_id'=>$order_id))->find();
            $peisongfei=$config['store']['store_moneys'];
            if($stores['num']>$sum_num){
                $coun=($stores['num']-$sum_num)*$peisongfei;
            }else{
                $coun=0;
            }
            //查询配送配
            if($type==1){
                if($ss>$qibu){
                    $sum=$pesongfei+($ss-$qibu)*$jjiajia;//当前距离-起步距离*每公里+多少钱
                    $sum_count=$sum+$coun;
                }else{
                    $sum=$pesongfei;
                    $sum_count=$sum+$coun;
                }
                //var_dump($ss,$qibu);
                $data=[
                    'sum_count'=>round($sum_count,2),
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功，配送费发生变化','data'=>$data]);
            }elseif($type==2){
                if($ss>$qibu2){
                    $sum=$pesongfei2+($ss-$qibu2)*$jjiajia2;//当前距离-起步距离*每公里+多少钱
                    $sum_count=$sum+$coun;
                }else{
                    $sum=$pesongfei2;
                    $sum_count=$sum+$coun;
                }
                $data=[
                    'sum_count'=>round($sum_count,2),
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功，配送费发生变化','data'=>$data]);
            }elseif($type==3){
                $data=[
                    'sum_count'=>round($shop['logistics_money'],2),
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功','data'=>$data]);
            }elseif($type==4){
                $data=[
                    'sum_count'=>0,
                    'user_addr'=>$obj,
                ];
                echoJson(['code'=>1,'msg'=>'选择成功','data'=>$data]);
            }


        }
    }


    public function pay2() {
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Storeorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid){
            $this->tuMsg('该订单不存在');
            die;
        }
        $addr_id = (int) $this->_post('addr_id');
        $uaddr = D('Useraddr')->where('addr_id =' . $addr_id)->find();
        if (empty($addr_id) && $order['type']!=4) {
            $this->tuMsg('请选择一个要配送的地址');
        }
        $mobile = D('Users')->where(array('user_id' => $this->uid))->getField('mobile');
        if(!$mobile){
            $this->tuMsg('请先绑定手机号码再提交');
        }
        //新增
        $xz = (int) $this->_post('yuyue');
        $times = $this->_post('times');
        $shop=D('Shop')->where(['shop_id'=>$order['shop_id']])->find();
        if($shop['is_yuyue']==1){
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
        D('Storeorder')->where(['order_id' => $order_id])->save(array('addr_id' => $addr_id, 'is_yuyue' => $xz, 'yuyuetime' => $t));
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }

        //重写配送费
        if($this->ispost()){
            $order['need_pay']=I('post.shifu');
            $order['logistics']=I('post.pei');
            $envelope=I('post.envelope');
            $envelope_money=I('post.envelope_money');

            D('Storeorder')->where(array('order_id'=>$order_id))->save(array('need_pay'=>$order['need_pay'],'logistics'=>$order['logistics'],'useEnvelope_id'=>$envelope,'useEnvelope'=>$envelope_money));

        }
	
        if($code == 'wait'){
			setcookie('store', '', time() - 3600, '/');
            D('Storeorder')->save(array('order_id' => $order_id, 'status' => 1));
            D('Storeorder')->save(array('order_id' => $order_id, 'is_daofu' => 1, 'status' => 1));
			D('Storeorder')->combination_store_print($order_id, $addr_id);//便利店打印万能接口
            //D('Sms')->storeTZshop($order_id);
            if(!empty($envelope)){
                $this->hongbaos($this->uid,$order_id,$envelope);
            }
			D('Storeorder')->store_month_num($order_id);//更新便利店销量
            $this->tuMsg('货到付款您下单成功', U('user/store/index'));
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->error('该支付方式不存在');
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('store', $order_id);
            if(empty($logs)){
                $logs = array('type' =>'store','user_id' =>$this->uid,'order_id' =>$order_id,'code' =>$code,'need_pay'=>$order['need_pay']-$order['useEnvelope'],
                    'create_time'=>NOW_TIME,'create_ip'=>get_client_ip(),'is_paid'=>0,'is_pin'=>$order['pin_user']);
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            if(!empty($envelope)){
                $this->hongbaos($this->uid,$order_id,$envelope);
            }
            $this->tuMsg('选择支付方式成功！下面请进行支付', U('payment/payment', array('log_id' => $logs['log_id'])));
            }
    }

    public function dianping(){
        $shop_id = (int) $this->_get('shop_id');
        if (!($detail = D('Store')->find($shop_id))) {
            $this->error('没有该便利店');
            die;
        }
        if ($detail['closed']) {
            $this->error('该便利店已经被删除');
            die;
        }
        $this->assign('detail', $detail);
        $this->display();
    }
	
    // public function dianpingloading(){
    //     $shop_id = (int) $this->_get('shop_id');
    //     if (!($detail = D('Store')->find($shop_id))) {
    //         die('0');
    //     }
    //     if ($detail['closed'] != 0 || $detail['audit'] != 1) {
    //         die('0');
    //     }
    //     $obj = D('Storedianping');
    //     import('ORG.Util.Page');
    //     $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
    //     $count = $obj->where($map)->count();
    //     $Page = new Page($count, 5);
    //     $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
    //     $p = $_GET[$var];
    //     if ($Page->totalPages < $p) {
    //         die('0');
    //     }
    //     $show = $Page->show();
    //     $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
    //         $this->assign('pics', D('Storedianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
    //     }
    //     $this->assign('totalnum', $count);
    //     $this->assign('list', $list);
    //     $this->assign('detail', $detail);
    //     $this->display();
   // }
	//点评详情
    public function img(){
        $dianping_id = (int) $this->_get('dianping_id');
        if (!($detail = D('Storedianping')->where(array('dianping_id'=>$dianping_id))->find())){
            $this->error('没有该点评');
            die;
        }
        if ($detail['closed']) {
            $this->error('该点评已经被删除');
            die;
        }
        $list = D('Storedianpingpics')->where(array('order_id' =>$detail['order_id']))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }

    //详情
    public function details($product_id){
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $obj=D('Storeproduct');
        $detail=$obj->where(array('product_id'=>$product_id))->find();
        $ss=explode(',',$detail['collocation_id']);
        foreach ($ss as $val){
            $s=$ss;
        }
        $eleshop=D('Store')->where(array('shop_id'=>$detail['shop_id']))->find();
        $this->assign('eleshop',$eleshop);

        $weisheng=M('shop_audit')->where(['shop_id'=>$detail['shop_id']])->find();
        $this->assign('weisheng',$weisheng['yingye'].'?x-oss-process=style/atufafa');
        $tuan=D('Storeproduct');
        $maps=array('closed'=>0,'is_tuan'=>1);
        $count=$tuan->where($maps)->count();
        $lis=$tuan->where($maps)->select();
        foreach ($lis as $val){
            $shop_id['shop_id']=$val['shop_id'];
        }

        $list = $obj->where(['closed'=>0,'audit'=>1])->order(array('sold_num' =>'desc','price' => 'asc'))->select();
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
            $cates = D('Storecate')->itemsByIds($cate_ids);
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
        $this->assign('cailist', $list);

        $spec=M('StoreSpec')->where(['shop_id'=>$detail['shop_id'],'type_id'=>$detail['spec_type']])->find();
        $mapg['id']=array('in',$detail['spec_id']);
        $guige=M('StoreSpecItem')->where($mapg)->select();
        $this->assign('guige',$guige);
        $this->assign('spec',$spec);
        //联系商家
        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $shops=D('Shop')->where(['shop_id'=>$detail['shop_id']])->find();
        $this->assign('storeshop',$shops);
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo=$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");
        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['product_name'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );
        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
        $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
        $this->assign('service_url', $service_url);


        $this->assign('cates', D('Storecate')->where(array('shop_id' => $detail['shop_id'], 'closed' => 0))->select());//分类
        $this->assign('tuijian', $tuijian = $obj->where(array('shop_id' => $detail['shop_id'],'is_huodong'=>0,'is_tuijian'=>1, 'closed' =>0, 'audit' => 1, 'cost_price' => array('neq','')))->order(array('create_time' =>'desc'))->limit(0,2)->select());//推荐开始
        $dleproduc=D('Storeproduct')->where(array('shop_id'=>$detail['shop_id'],'closed'=>0))->select();
        $this->assign('shoplist',$dleproduc);

        //快递费
        $id=D('Storeproduct')->where(array('product_id'=>$product_id))->find();
        $kuai=D('Pkuaidi')->where(['id'=>$id['kuaidi_id'],'shop_id'=>$id['shop_id'],'closed'=>0])->find();
        $fei=D('Pyunfei')->where(['kuaidi_id'=>$kuai['id']])->find();
        $this->assign('logisticsfee',$fei['shouzhong']);
        session('express',$fei['shouzhong']);
        //结束
        $this->assign('tuan',$lis);
        $this->assign('page',$count);
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $fee=Distributionfee($lat,$lng,$detail['shop_id'],3);

        session('storelogistics',$fee['logistics']);
        session('storezdlogistics',$fee['zdlogistics']);
        $this->assign('logistics',$fee['logistics']);
        $this->assign('zdlogistics', $fee['zdlogistics']);
        $this->assign('pintuan',D('Storeproduct')->where(array('is_tuan'=>1))->select());
        $this->assign('detail',$detail);

        $Eledianping = D('Storedianping');
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
            $this->assign('pics', D('Storedianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('lists', $lists);

        //商家
        $tu=D('Shoppic')->where(array('shop_id'=>$detail['shop_id']))->select();
        $times=D('Store')->where(array('shop_id'=>$detail['shop_id']))->find();
        $this->assign('times',$times);
        $this->assign('sjtu',$tu);

        //查询配置
        $config=D('Setting')->fetchAll();

        $filter_spec = $this->get_spec($product_id); //获取商品规格参数
        $spec_goods_price  = D('StoreGoodsPrice')->where("product_id = $product_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表

        if($spec_goods_price != null){
            $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表

        }

        $this->assign('filter_spec',$filter_spec);
        $this->assign('config',$config);
        $this->display();

    }

    public function get_spec($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = D('StoreGoodsPrice')->where("product_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if($keys){
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM tu_store_spec AS a INNER JOIN tu_store_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
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

//    //分享
//    public function fenxian(){
//
//        $config=D('Setting')->fetchAll();
//        $obj=D('Storeshare');
//        if($this->ispost()){
//            $product_id=I('post.product_id');
//            $shop=I('post.shop_id');
//
//            $set= (int) $config['store']['times'];
//            $num1=(int) $config['store']['num1'];
//            $num2=(int) $config['store']['num2'];
//            $nums=mt_rand($num1,$num2);
//            $nowtime=date('Y:m:d H:i:m',time());
//            $bg=date("Y-m-d H:i:s",strtotime('+'.$set.'hours',strtotime($nowtime)));
//            $endtime=strtotime($bg);
//            $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'product_id'=>$product_id,'closed'=>0))->find();
//            if(empty($cunzai)){
//                $data=array();
//                $data['product_id']=$product_id;
//                $data['shop_id']=$shop;
//                $data['user_id']=$this->uid;
//                $data['create_time']=NOW_TIME;
//                $data['create_ip']=get_client_ip();
//                $data['end_time']=$endtime;
//                $data['num']=$nums;
//                $obj->add($data);
//            }
//        }
//
//    }

    //分享抢红包
    public function share(){
        $tuan=D('Storeproduct');
        $map=array('closed'=>0,'audit'=>1,'is_tuan'=>1);
        $lis=$tuan->where($map)->order('tuan_money desc')->select();
        foreach ($lis as $val){
            $shop_id['shop_id']=$val['shop_id'];
        }
        $this->assign('tuan',$lis);
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $this->display();
    }

    //大牌满减
    public function reduction(){
        $this->display();
    }
    public function reductionloadata(){
        $obj=D('Store');
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

    //热卖活动
    public function heat(){

        $this->display();
    }
    public function heatloadata(){
        $obj=D('Storeproduct');
        import('ORG.Util.Page');
        $map = array('audit' => 1,'closed'=>0,'is_tuan'=>1);
        $list=$obj->where($map)->order('month_num desc')->select();

        $this->assign('list',$list);
        $this->display();
    }

    //0元起送活动
    public function zero(){
        $this->display();
    }
    public function zeroloadata(){
        $obj=D('Store');
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

    //通知广告
    public function notice(){
        $this->assign('notice',D('NoticeEleStoreMarket')->where(['type'=>2])->select());
        $this->display();
    }


}