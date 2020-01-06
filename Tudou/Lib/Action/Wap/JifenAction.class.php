<?php
class JifenAction extends CommonAction {
	
	  protected function _initialize() {
        parent::_initialize();
		$jifen = (int)$this->_CONFIG['operation']['jifen'];
		if ($jifen == 0) {
				$this->error('此功能已关闭');
				die;
		}
          $goods = cookie('goodsjifen');
          $this->assign('cartnum', (int) array_sum($goods));
          $cat = (int) $this->_param('cat');
          $goodscates = D('Goodscate')->where(array('parent_id'=>0,'cate_id'=>array('not in','179')))->select();
          $this->assign('goodscates',$goodscates);
          $cates = (int)$this->_param('cates');
          $this->assign('goodscatess',$getFurniture = D('Goodscate')->getFurniture($cates));
          $config=D('Setting')->fetchAll();
          $this->assign('config',$config);
          $check_user_addr = D('Paddress')->where(array('user_id'=>$this->uid))->find();//全局检测地址
          $this->assign('check_user_addr', $check_user_addr);
          $user=D('Usersintegral')->where(array('user_id'=>$this->uid))->find();
          $this->assign('user',$user);
     }

    public function index() {
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $order = $this->_param('order', 'htmlspecialchars');

        $cat = (int) $this->_param('cat');
        if($cat){
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttribut($cat));
        }
        $this->assign('cat', $cat);
        $linkArr['cat'] = $cat;

        $orderby = '';
        switch ($order) {
            case x:
                $orderby = array('exchange_num' => 'desc');
                break;
            case t:
                $orderby = array('orderby' => 'asc');
                break;
            default:
                $orderby = array('exchange_num' => 'desc', 'orderby' => 'asc');
                break;
        }

        $shop_id = (int) $this->_param('shop_id');
        $this->assign('order', $order);
        $this->assign('nextpage', LinkTo('jifen/loaddata2', array('t' => NOW_TIME, 'shop_id' => $shop_id, 'order' => $order, 'keyword' => $keyword, 'p' => '0000')));
        //商品类型
        $this->assign('goodsca',D('Goodscate')->where(array('cate_id'=>array('in','41,110,113,75,72,1,4,10,13,15,17,19,22,25,30,31,32,33,34,35,36,37,38')))->select());



        $this->display();
    }

//    public function loaddata() {
//
//        $Integralgoods = D('Integralgoods');
//        import('ORG.Util.Page');
//        $map = array('closed' => 0, 'audit' => 1,'shop_closed'=>0);
//
//        if ($shop_id = (int) $this->_param('shop_id')) {
//            $map['shop_id'] = $shop_id;
//        }
//
//        $count = $Integralgoods->where($map)->count();
//        $Page = new Page($count, 25);
//        $show = $Page->show();
//        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
//        $p = $_GET[$var];
//        if ($Page->totalPages < $p) {
//            die('0');
//        }
//        $list = $Integralgoods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
//        $config= D('Setting')->fetchAll();
//        foreach ($list as $k => $val) {
//            if ($val['shop_id']) {
//                $shop_ids[$val['shop_id']] = $val['shop_id'];
//            }
//        }
//        if ($shop_ids) {
//            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
//        }
//
//        $this->assign('money',$money);
//        $this->assign('list', $list);
//        $this->assign('page', $show);
//        $this->display();
//    }

    //新增商品列表
    public function loaddata2(){
        $Goods = D('Integralgoodslist');
        import('ORG.Util.Page');

        $area = (int) $this->_param('area');
        if($area){
            $map['area_id'] = $area;
            $this->assign('area', $area);
            $linkArr['area'] = $area;
        }


        $business = (int) $this->_param('business');
        if($business){
            $map['business_id'] = $business;
            $this->assign('business', $business);
            $linkArr['business'] = $business;
        }



        $shop_id = (int) $this->_param('shop_id');
        if($shop_id){
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
            $linkArr['shop_id'] = $shop_id;
        }

        $user_id = (int) $this->_param('user_id');
        if($user_id){
            $this->assign('user_id', $user_id);
            $linkArr['user_id'] = $user_id;
        }

        $type = (int) $this->_param('type');
        if($type){
            $this->assign('type', $type);
            $linkArr['type'] = $type;
        }

        $map['audit'] = 1;
        $map['closed'] = 0;
        $map['end_date'] = array('egt', TODAY);
        $map['city_id'] = $this->city_id;


        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keyword'] = $keyword;
        }


        //开始组装数组
        $query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
        $arr = array();
        foreach($query_string as $key=>$values){
            if(strstr( $values , 'values_' ) !== false){
                array_push($arr, $values);
            }
        }

        foreach($arr as $k=>$v){
            $arr[$v] = $this->_param($v,'htmlspecialchars');
            $query[$v] = $arr[$v];
            $this->assign('query',$query);
            $linkArr[$v] = $arr[$v];
        }

        $array = array();
        foreach($query as $kk=>$vv){
            $explode = explode('_',$kk);
            $array[$kk]['attr_id'] = $explode['1'];
            $array[$kk]['attr_value'] = $vv;
        }
        foreach($array as $val){
            $attr_values[$val['attr_value']] = $val['attr_value'];
        }

        $maps['attr_value']  = array('IN',$attr_values);
        //$TpGoodsAttr = M('TpGoodsAttr')->where($map)->group('attr_value')->select();
        $TpGoodsAttr = D('Integralgoodsattr')->where($maps)->select();



        $result= array();
        foreach($TpGoodsAttr as $key => $info){
            $result[$info['goods_id']][] = $info;
        }

        foreach($result as $kkk => $vvv){
            foreach($vvv as $k2 => $v2){
                $attr_valuess[$kkk][$k2] = $v2['attr_value'];
            }
        }

        $implode = implode('_',$attr_values);

        foreach($attr_valuess as $k3 => $v3){
            $implodes = implode('_',$v3);
            if($implodes != $implode){
                unset($attr_valuess[$k3]);
            }
        }


        foreach($attr_valuess as $k4 => $v4){
            $goods_ids[$k4] = $k4;
        }
        if($array){
            $map['goods_id'] = array('IN',$goods_ids);
        }
        //多属性搜索结束



        $cate_id = (int) $this->_param('cate_id');
        $cat = (int) $this->_param('cat');
        if($cate_id){
            if($cate_id){
                if(empty($array)){
                    $map['cate_id'] = $cate_id;
                }
                $linkArr['cate_id'] = $cate_id;
                $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
            }
        }else{
            $catids = D('Goodscate')->getChildren($cat);
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
            if(!empty($catids)){
                if(empty($array)){
                    $map['cate_id'] = array('IN', $catids);
                }
            }else{
                $map['cate_id'] = $cate_id;
                $linkArr['cate_id'] = $cate_id;

            }

        }
        $this->assign('cat', $cat);
        $this->assign('cate_id', $cate_id);

        $count = $Goods->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $order = $this->_param('order', 'htmlspecialchars');
        switch($order){
            case '1':
                $orderby = array('top_time' => 'desc');
                break;
            case '2':
                $orderby = array('old_num' => 'asc');
                break;
            case '3':
                $orderby = array('mall_price' => 'desc');
                break;
            case '4':
                $orderby = array('mall_price' => 'asc');
                break;
            case '5':
                $orderby = array('top_time' => 'desc','orderby' =>'asc');
                break;
            default:
                $orderby = array('top_time' => 'desc','mall_price'=>'asc');
                break;
        }

        //->order($orderby)
        $list = $Goods->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        //var_dump($list);
        foreach ($list as $k => $val){
            if($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $Shop = D('Shop')->find($val['shop_id']);
            $val['d'] = getDistance($lat, $lng, $Shop['lat'], $Shop['lng']);
            $list[$k] = $val;
        }
        //图片循环
        $this->assign('imgs',D('Ad')->where(array('site_id'=>array('in','80,86,87,88,89,14'),'closed'=>0))->select());
        $goods_one = D('Integralgoodslist')->where(array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY)))->order(array('orderby' =>'asc'))->limit(0,10)->select();
        //板块一的商品推荐
        $this->assign('goods_one',$goods_one);
        //板块二的商品推荐

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function getTpGoodsAttributes($cat){
        $res = D('Goodscate')->find($cat);
        $TpGoodsType = D('Integralgoodstype')->where(array('id'=>$res['type_id']))->find();
        $TpGoodsAttributes = D('Integralgoodsattribute')->where(array('type_id'=>$TpGoodsType['id'],'attr_input_type'=>1))->select();
        foreach($TpGoodsAttributes as $k => $val){
            if(empty($val['attr_values']) || trim($val['attr_values']) == ''){
                unset($TpGoodsAttributes[$k]);
            }

        }
        foreach($TpGoodsAttributes as $kk => $vv){
            $TpGoodsAttribute[$kk]['attr_id'] = $vv['attr_id'];
            $TpGoodsAttribute[$kk]['attr_name'] = $vv['attr_name'];
            $TpGoodsAttribute[$kk]['attr_values'] = explode(PHP_EOL,$vv['attr_values']);
        }
        return $TpGoodsAttribute;
    }

    public function getTpGoodsAttribut($cat){
        $res = D('Goodscate')->find($cat);
        $TpGoodsType = D('Integralgoodstype')->where(array('id'=>$res['type_id']))->find();
        $TpGoodsAttributes = D('Integralgoodsattribute')->where(array('type_id'=>$TpGoodsType['id'],'attr_input_type'=>1))->select();
        foreach($TpGoodsAttributes as $k => $val){
            if(empty($val['attr_values']) || trim($val['attr_values']) == ''){
                unset($TpGoodsAttributes[$k]);
            }

        }
        foreach($TpGoodsAttributes as $kk => $vv){
            $TpGoodsAttribute[$kk]['attr_id'] = $vv['attr_id'];
            $TpGoodsAttribute[$kk]['attr_name'] = $vv['attr_name'];
            $TpGoodsAttribute[$kk]['attr_values'] = explode(PHP_EOL,$vv['attr_values']);
        }
        return $TpGoodsAttribute;
    }

//    public function detail($goods_id) {
//        $goods_id = (int) $goods_id;
//        if (!$detail = D('Integralgoods')->find($goods_id)) {
//            $this->error('该积分商品不存在或者已经下架');
//        }
//        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
//            $this->error('该积分商品不存在或者已经下架');
//        }
//
//
//        $this->assign('shop', D('Shop')->find($detail['shop_id']));
//        $other_goods = D('Integralgoods')->where(array('audit' => 1, 'closed' => 0, 'shop_id' => $detail['shop_id'], 'goods_id' => array('NEQ', $goods_id)))->limit(0, 4)->select();
//        $this->assign('othergoods', $other_goods);
//        $this->assign('detail', $detail);
//        $this->display();
//    }

    //新增，商品详细信息
    public function detail2($goods_id){
        if (empty($this->uid)) {
            $this->tuMsg('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!($detail = D('Integralgoodslist')->find($goods_id))) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }
        $shop_id = $detail['shop_id'];
        $shops =D('Shop')->find($shop_id);

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


        if($detail['is_tui'] ==1 && $shops['user_id'] != $this->uid){
            D('Integralgoodslist')->check_price($goods_id);
        }
        $xiangsi=D('Integralgoodslist')->where(array('goods_id'=>$goods_id))->find();

//        $this->assign('recom',$recom = D('Goods')
//            ->where(array('shop_id' => $shop_id,'audit'=>1,'closed'=>0,'goods_id' => array('neq', $goods_id),'end_date' => array('EGT', TODAY)))
//            ->limit(0, 5)->select());

        $this->assign('list',D('Integralgoodslist')->where(array('cate_id'=>$xiangsi['cate_id'],'audit'=>1,'closed'=>0))->select());

        // print_r($detail);die;s
        $this->assign('detail', $detail);
        $this->assign('shop', D('Shop')->find($shop_id));

        $filter_spec = $this->get_spec($goods_id); //获取商品规格参数
        $goodsss=D('Integralgoodslist')->find($goods_id);
        $goodsss['mall_price']=$goodsss['mall_price'];
        $spec_goods_price  = D('Integralgoodsspecprice')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表

        if($spec_goods_price != null){
            $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表

        }
        $yh=$goodsss[yh];
        if($yh!= '0'){
            $yh=explode(PHP_EOL,$yh);
            for ($i=0; $i < count($yh)-1;$i++){
                $yh[s][]=explode(',',$yh[$i]);
            }
            foreach($yh[s] as $k2=>$vo){
                foreach($vo as $k2=>$v2){
                    $rs[$k2][] = $v2;
                }
            }
            $goodsss['zks'][]=$rs[0];
            $goodsss['zks'][]=$rs[1];
        }
        $this->assign('filter_spec',$filter_spec);
        $this->assign('goods', $goodsss);
        $pingnum = D('Integralcomment')->where(array('goods_id' => $goods_id, 'show_date' => array('ELT', TODAY)))->count();
        $this->assign('pingnum', $pingnum);
        $score = (int) D('Integralcomment')->where(array('goods_id' => $goods_id))->avg('score');
        if ($score == 0) {
            $score = 5;
        }
        $this->assign('score', $score);
        if(($detail['is_vs1'] || $detail['is_vs2'] || $detail['is_vs3'] || $detail['is_vs4'] || $detail['is_vs5'] || $detail['is_vs6']) || $detail['is_vs7'] || $detail['is_vs8'] || $detail['is_vs9']==1 ){
            $this->assign('is_vs', $is_vs = 1);
        }else{
            $this->assign('is_vs', $is_vs = 0);
        }
        $this->assign('pics', D('Integralgoodsphoto')->getPics($detail['goods_id']));
        $this->assign('count_goodsfavorites',$count_goodsfavorites = D('Goodsfavorites')->where(array('goods_id'=>$detail['goods_id']))->count());
        $this->assign('goodsfavorites', $goodsfavorites = D('Goodsfavorites')->check($goods_id, $this->uid));//检测自己是不是收藏
        $map_coupon_where = array('shop_id' =>$detail['shop_id'], 'audit' => 1,'closed' => 0, 'expire_date' => array('EGT', TODAY));
        $this->assign('goods_attribute',$goods_attribute = D('Integralgoodsattribute')->getField('attr_id,attr_name'));//属性值
        $this->assign('goods_attr_list',$goods_attr_list = D('Integralgoodsattr')->where("goods_id = $goods_id")->select());//属性列表
        $this->display();
    }

    public function get_spec($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = D('Integralgoodsspecprice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if($keys){
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__integral_goods_spec AS a INNER JOIN __PREFIX__integral_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
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

    //点评
    public function dianping(){
        $goods_id = (int) $this->_get('goods_id');
        if(!($detail = D('Integralgoodslist')->find($goods_id))){
            $this->error('没有该商品');
            die;
        }
        if($detail['closed']){
            $this->error('该商品已经被删除');
            die;
        }

        $this->assign('next', LinkTo('jifen/dianpingloading', $linkArr, array('goods_id' => $goods_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->display();
    }


    public function dianpingloading(){
        $goods_id = (int) $this->_get('goods_id');
        if(!($detail = D('Integralgoodslist')->find($goods_id))){
            die('0');
        }
        if($detail['closed']){
            die('0');
        }
        $Goodsdianping = D('Integralcomment');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'goods_id' => $goods_id, 'show_date' => array('ELT', TODAY));
        $count = $Goodsdianping->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Goodsdianping->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $orders_ids = array();
        foreach($list as $k => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $orders_ids[$val['order_id']] = $val['order_id'];
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($orders_ids)){
            $this->assign('pics', D('Integralcommentpics')->where(array('order_id' => array('IN', $orders_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $Page);
        $this->assign('detail', $detail);
        $this->display();
    }

    //购物车
    public function cart(){

        $goods = cookie('jifengoods');
        $back = end($goods);
        $back = key($goods);
        $goods_spec = cookie('goodsjifen');
        $this->assign('back', $back);


        if(empty($goods_spec)) {
            $this->error('亲还没有选购产品呢!', U('jifen/index'));
        }

        $spec_keys = array_keys($goods_spec);
        $spec_arr = $this ->spec_to_arr($goods_spec);
        $goods_ids= $this->get_goods_ids($goods_spec);

        foreach($goods_ids as $k=> $v){
            $cart_goods[] = D('Integralgoodslist')->itemsByIds($v);
        }
        $shop_ids = array();
        foreach ($cart_goods as $k => $val) {
            foreach($val as $key => $det){
                $cart_goods[$k][$key]['buy_num'] = $spec_arr[$k][2];//购买数量
                $cart_goods[$k][$key]['sky'] =  $spec_arr[$k][1];
                $cart_goods[$k][$key]['goods_spec'] = $spec_keys[$k];
                $shop_ids[$det['shop_id']] = $det['shop_id'];
                if(!empty($cart_goods[$k][$key][sky])){
                    //通过这个sky来查多属性里面的价格  其实也就是一条数据
                    $spt=D('Integralgoodsspecprice')->where("`key`='{$cart_goods[$k][$key][sky]}' and `goods_id`='{$cart_goods[$k][$key][goods_id]}'")->find();            		$cart_goods[$k][$key]['mall_price']=$spt['price'];
                    $cart_goods[$k][$key]['key_name']=$spt['key_name'];
                }
            }

        }
        //	print_r($cart_goods);
        $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('cart_goods', $cart_goods);

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
    //添加购物车
    public function cartadds(){
        if (IS_AJAX) {
            $shop_id = (int) $_POST['shop_id'];
            $goods_id = (int) $_POST['goods_id'];
            $goods_spec= cookie('goodsjifen');
            $spec_key =  $_POST['spec_key'];
            $num =  $_POST['num'];
            if (empty($goods_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择商品'));
            }
            if (!($detail = D('Integralgoodslist')->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }

            //查询用户积分是否大于
//            $user=D('Users')->where(['user_id'=>$this->uid])->find();
//            $counjifen=$detail['use_integral']*$num;
//            if($user['integral']<$counjifen){
//                $this->ajaxReturn(array('status' => 'error', 'msg' => '您的积分不足，不能购买'));
//            }
            if ($detail['closed'] != 0 || $detail['audit'] != 1) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }
            if ($detail['end_date'] < TODAY) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
            }

            if ($detail['num'] <= 0) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！没有库存了！'));
            }
            $goods_spec_v = $goods_id.'|'.$spec_key;
            //重新组合那个 商品id和那个啥规格键
            //加入购物车时候检查规格库存  如果不走这里他会走下面的
            $is_spec_stock = is_spec_stock_jifen($goods_id,$spec_key,$num);
            if(!$is_spec_stock){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
            }
            if ($detail['num'] < $num) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
            }
            if (isset($goods_spec[$goods_spec_v])) {
                $goods_spec[$goods_spec_v] += $num;
            } else {
                $goods_spec[$goods_spec_v] = $num;
            }
            cookie('goodsjifen', $goods_spec, 604800);
            $goods = cookie('jifengoods');
            if (isset($goods[$goods_id])) {
                $goods[$goods_id] = $goods[$goods_id] + 1;
            } else {
                $goods[$goods_id] = 1;
            }
            $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功'));
        }
    }

    //添加购物车2
    public function cartadd(){
        if (IS_AJAX) {
            $shop_id = (int) $_POST['shop_id'];
            $goods_id = (int) $_POST['goods_id'];
            $goods_spec= cookie('goodsjifen');
            $spec_key =  $_POST['spec_key'];
            $num =  $_POST['num'];
            if (empty($goods_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择商品'));
            }
            if (!($detail = D('Integralgoodslist')->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }
            //查询用户积分是否大于
//            $user=D('Users')->where(['user_id'=>$this->uid])->find();
//            $counjifen=$detail['use_integral']*$num;
//            if($user['integral']<$counjifen){
//                $this->ajaxReturn(array('status' => 'error', 'msg' => '您的积分不足，不能购买'));
//            }
            if ($detail['closed'] != 0 || $detail['audit'] != 1) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }
            if ($detail['end_date'] < TODAY) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
            }

            if ($detail['num'] <= 0) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！没有库存了！'));
            }
            $goods_spec_v = $goods_id.'|'.$spec_key;
            //重新组合那个 商品id和那个啥规格键
            //加入购物车时候检查规格库存  如果不走这里他会走下面的
            $is_spec_stock = is_spec_stock_jifen($goods_id,$spec_key,$num);
            if(!$is_spec_stock){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
            }
            if ($detail['num'] < $num) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
            }
            if (isset($goods_spec[$goods_spec_v])) {
                $goods_spec[$goods_spec_v] += $num;
            } else {
                $goods_spec[$goods_spec_v] = $num;
            }
            cookie('goodsjifen', $goods_spec, 604800);
            $goods = cookie('jifengoods');
            if (isset($goods[$goods_id])) {
                $goods[$goods_id] = $goods[$goods_id] + 1;
            } else {
                $goods[$goods_id] = 1;
            }
            $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功,正在跳转到购物车', 'url' => U('jifen/cart')));
        }
    }

//    //立即购买
//    public function buy($goods_id){
//        $goods_id = (int) $goods_id;
//        if (empty($goods_id)) {
//            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
//        }
//        if (!($detail = D('Integralgoodslist')->find($goods_id))) {
//            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品不存在'));
//        }
//        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
//            $this->tuMsg('该商品不存在');
//        }
//        if ($detail['end_date'] < TODAY) {
//            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
//        }
//        $goods_spec= cookie('goodsjifen');
//        $num = (int) $this->_get('num');
//        $spec_key =  $this->_get('spec_key');
//        if (empty($num) || $num <= 0) {
//            $num = 1;
//        }
//        $is_spec_stock = is_spec_stock_jifen($goods_id,$spec_key,$num);
//        if(!$is_spec_stock){
//            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
//        }
//        if ($detail['exchange_num'] < $num) {
//            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['exchange_num'] . '件了，少买点吧！'));
//        }
//        $goods_spec_v = $goods_id.'|'.$spec_key; //重新组合那个 商品id和那个啥规格键
//        if (isset($goods_spec[$goods_spec_v])) {
//            $goods_spec[$goods_spec_v] += $num;
//        } else {
//            $goods_spec[$goods_spec_v] = $num;
//        }
//        $key[$goods_id]=$spec_key;//规格
//        cookie('goodsjifen', $goods_spec, 604800);
//
//    }

    //删除购物车
    public function cartdel(){
        $goods_spec = $_POST['goods_spec'];
        $goods_spec_all = cookie('goodsjifen');
        if (isset($goods_spec_all[$goods_spec])) {
            unset($goods_spec_all[$goods_spec]);
            cookie('goodsjifen', $goods_spec_all, 604800);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '删除失败'));
        }
    }

    //结算
    public function order(){
        if (empty($this->uid)) {
            $this->tuMsg('请先登陆', U('passport/login'));
        }

        $num = $this->_post('num', false);
        $type = $this->_post('type',false);
        $goods_ids = array();
        foreach ($num as $k => $val) {
            $val = (int) $val;
            if (empty($val)) {
                unset($num[$k]);
            } elseif ($val < 1 || $val > 99) {
                unset($num[$k]);
            } else {
                $spec_keys[]=$k;
                $spec_arr[] = explode('|',$k);
                $spec_temp = explode('|',$k);
                $goods_ids[$k][] = (int)$spec_temp[0];
            }
        }
        foreach($goods_ids as $v){
            $goods[] = D('Integralgoodslist')->itemsByIds($v);
        }

        foreach ($goods as $va){
            $val = reset($va);
            $goods_num +=$val['use_integral'];
        }
        if($type==2){
            $user=D('Users')->where(['user_id'=>$this->uid])->find();
            if($user['integral']<$goods_num){
                $this->tuMsg('很抱歉，您的积分不够，请用全额结算');
            }
        }


        foreach ($goods as $k => $v) {
            foreach($v as $key => $val){
                if ($val['closed'] != 0 || $val['audit'] != 1 || $val['end_date'] < TODAY) {
                    unset($goods[$key]);
                }
                //把这个商品的规格存进数组
                $goods[$k][$key][sky]=$spec_arr[$k][1]; //把后面的规格存进来 148_150
                $goods[$k][$key]['goods_spec'] = $spec_keys[$k];//整个存一下
                if(!empty($goods[$k][$key][sky])){
                    //改变价格
                    $spt=D('Integralgoodsspecprice')->where("`key`='{$goods[$k][$key][sky]}' and `goods_id`='{$goods[$k][$key][goods_id]}'")->find();
                    $goods[$k][$key]['mall_price']=$spt['price'];
                    $goods[$k][$key]['key_name']=$spt['key_name'];//建的中文名
                }
            }
        }
        if (empty($goods)) {
            $this->tuMsg('很抱歉，您提交的产品暂时不能购买');
        }
        //下单前检查库存
        foreach ($goods as $val) {
            $val = reset($val);
            //二维数组 取第一个
            //加入购物车时候检查规格库存  如果不走这里他会走下面的
            $is_spec_stock = is_spec_stock_jifen($val[goods_id],$val[sky],$num[$val['goods_spec']]);
            if(!$is_spec_stock){
                $spec_one_num =  get_one_spec_stock_jifen($val[goods_id],$val[sky]);
                $this->tuMsg('亲！规格为<' . $val['key_name']. '>的商品库存不够了,只剩' . $spec_one_num . '件了');
            }
            if ($val['num'] < $num[$val['goods_spec']]) {
                $this->tuMsg('亲！商品<' . $val['title'] . '>库存不够了,只剩' . $val['num'] . '件了');
            }
        }
        $tprice = 0;
        $all_integral = $total_mobile = 0;
        $ip = get_client_ip();
        $total_canuserintegral = $ordergoods = $total_price = array();
        foreach ($goods as $val) {
            $val = reset($val);
            //二维数组 取第一个
            //二次开发的 其他人可能看不懂 之前是  $num[$val['goods_id']]  这个我前面那个num已经改过了 但是下面的代码不想改了 所以统一赋值一下
            //前面已经通过这个规格的键值来重新传了
            $num[$val['goods_id']] = $num[$val['goods_spec']];
            $price = $val['mall_price'] * $num[$val['goods_id']];
            $js_price = $val['settlement_price'] * $num[$val['goods_id']];
            $mobile_fan = $val['mobile_fan'] * $num[$val['goods_id']]; //每个商品的手机减少的钱
            $canuserintegral = $val['use_integral'] * $num[$val['goods_id']];
            $order_express_price = D('Integralordergoods')->calculation_jifen_price($this->uid,$val['kuaidi_id'], $num[$val['goods_id']],$val['goods_id'],0);
            //返回单个商品运费
            $m_price = $price - $mobile_fan;
            $tprice += $m_price;
            $total_mobile += $mobile_fan;
            $all_integral += $canuserintegral;
            $ordergoods[$val['shop_id']][] = array(
                'goods_id' => $val['goods_id'],
                'shop_id' => $val['shop_id'],
                'num' => $num[$val['goods_id']],
                'kuaidi_id' => $val['kuaidi_id'],
                'price' => $val['mall_price'],
                'total_price' => $price,
                'mobile_fan' => $mobile_fan,
                'express_price' => $order_express_price, //单个商品运费总价
                'is_mobile' => 1,
                'js_price' => $js_price,
                'create_time' => NOW_TIME,
                'create_ip' => $ip,
                'key'=> $val['sky'],
                'key_name' => $val['key_name']
            );
            $total_canuserintegral[$val['shop_id']] += $canuserintegral; //不同商家可使用积分
            $total_price[$val['shop_id']] += $price; //不同商家的总价格
            $express_price[$val['shop_id']] += $order_express_price; //不同商家总运费
            $mm_price[$val['shop_id']] += $mobile_fan;  //不同商家的手机下单立减
        }
        $order = array('user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => $ip, 'is_mobile' => 1);
        $tui = cookie('tui');
        if (!empty($tui)) {
            $tui = explode('_', $tui);
            $tuiguang = array('uid' => (int) $tui[0], 'goods_id' => (int) $tui[1]);
        }
        $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);//收货地址部分重写
        $order_ids = array();
        foreach ($ordergoods as $k => $val) {
            $order['shop_id'] = $k;
            $order['total_price'] = $total_price[$k];
            $order['mobile_fan'] = $mm_price[$k];
            $jifen = $total_canuserintegral[$k];
            $order['express_price'] = $express_price[$k];//写入运费
            $order['address_id'] = $defaultAddress['id'];//写入快递ID
            if($type==2){
                $order['can_use_integral']=$jifen;
            }else{
                $order['can_use_integral']=0;
            }
            $val[0]['express_price'] = $express_price[$k];//写入运费
            $val[0]['address_id'] = $defaultAddress['id'];//写入快递
            $order['type']=$type;
            $shop = D('Shop')->find($k);
            $order['is_shop'] = (int) $shop['is_goods_pei'];
            if ($order_id = D('Integralorder')->add($order)) {//这里写入订单表了
                $order_ids[] = $order_id;

                foreach ($val as $k1 => $val1) {
                    $Goods = D('Integralgoodslist')->find($val1['goods_id']);
                    $val1['cate_id'] = $Goods['cate_id'];
                    $val1['weight'] = $Goods['weight'];
                    $val1['order_id'] = $order_id;
                    if(!empty($tuiguang)) {
                        if ($tuiguang['goods_id'] == $val1['goods_id']) {
                            $val1['tui_uid'] = $tuiguang['uid'];
                        }
                    }
                    D('Integralordergoods')->add($val1);
                }
            }
        }

        cookie('goodsjifen', null);// 清空 cookie
        if (count($order_ids) > 1) {
            echo 1;
            $need_pay = D('Integralorder')->useIntegrals($this->uid, $order_ids);
            $logs = array(
                'type' => 'exchange',
                'user_id' => $this->uid,
                'order_id' => 0,
                'order_ids' => join(',', $order_ids),
                'code' => '',
                'need_pay' => $need_pay,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $this->tuMsg('合并下单成功，接下来选择支付方式和配送地址', U('jifen/paycode', array('log_id' => $logs['log_id'])));
        } else {
            $this->tuMsg('下单成功，接下来选择支付方式和配送地址', U('jifen/pay', array('order_id' => $order_id,'address_id'=>$defaultAddress['id'])));
        }
    }


    //单个商品支付
    public function pay(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $this->check_mobile();
        cookie('jifengoods', null); //销毁cookie
        $order_id = (int) $this->_get('order_id');
        $order = D('Integralorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
//        //不清楚在这为什么要做post提交==所以先注释掉
//        if($this->isPost()){
//            $useEnvelope = $_POST['useEnvelope'];
//            D('Order')->where(['order_id'=>$order_id])->save(['useEnvelope'=>$useEnvelope*100,'useEnvelope_id'=>$_POST['useEnvelope_id']]);
//            D('Paymentlogs')->where(['log_id'=>$Paymentlogs['log_id']])->save(['need_pay'=>$need_pay]);
//        }
        $ordergood = D('Integralordergoods')->where(array('order_id' => $order_id))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('goods', D('Integralgoodslist')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);

        //收货地址部分重写
        if (false == $defaultAddress = D('Paddress')->order_address_ids($this->uid,$order_id)) {
            $this->error('获取用户地址出错，请先去会员中心添加积分商城地址后下单');
        }
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => exchange, 'order_id' => $order_id));
        $this -> assign('defaultAddress', $defaultAddress);
        $this -> assign('changeAddressUrl', $changeAddressUrl);


        $this->assign('use_integral', $use_integral = D('Integralorder')->GetUseIntegrals($this->uid, array($order_id)));//预算积分抵扣
        //var_dump($use_integral);die;
        $Paymentlogs = D('Paymentlogs')->getLogsByOrderId('exchange', $order_id);
        if($Paymentlogs['need_pay']){
            $need_pay = $Paymentlogs['need_pay'];
        }else{
            $need_pay = $order['total_price'] + $order['express_price'];
        }

        $this->assign('need_pay', $need_pay);
        $this->assign('order', $order);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->display();
    }

    //单个商品结算
    public function pay2(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $obj = D('Integralorder');
        $order_id = (int) $this->_get('order_id');
        $order = $obj ->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
        }

        $address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : $order['address_id'];//检测配送地址ID
        if (empty($address_id)) {
            $this->tuMsg('配送的地址异常');
        }else{
            $obj ->save(array('order_id' =>$order_id,'address_id' =>$address_id));
        }

        if(!($code = $this->_post('code'))){
            $this->tuMsg('请选择支付方式');
        }
        $this->goods_mum($order_id);//检测库存
        $address = D('Paddress')->where(array('address_id' => $order['address_id']))->find();
        if ($code == 'wait'){
            //如果是货到付款
            $obj ->save(array('order_id' => $order_id, 'status' => 1,'is_daofu' => 1));
            D('Integralordergoods')->save(array('is_daofu' => 1,'status' => 1), array('where' => array('order_id' => $order_id)));
            $obj ->mallSolds($order_id);//更新销量
            $obj ->jifenPeisong(array($order_id), 1);//更新配送
           // D('Sms')->mallTZshop($order_id);//用户下单通知商家
            $obj ->combination_jifen_print($order_id);//万能商城订单打印
            D('Weixintmpl')->weixin_notice_goods_user($order_id,$this->uid,0);//商城微信通知
            $this->tuMsg('恭喜您下单成功', U('user/exchange/index'));
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->tuMsg('该支付方式不存在');
            }


            $logs = D('Paymentlogs')->getLogsByOrderId('exchange', $order_id); //写入支付记录


            if($order['is_changeis_change'] != 1){
                $need_pay = $obj->useIntegrals($this->uid, array($order_id));//更新支付结果,这里加了配送费，这里是没改价的状态，这里改变的是积分状态
            }else{
                $need_pay = $order['need_pay'];//如果是改价的扫码都不加
            }
            //判断计算好的价格
            if($this->ispost()){
                $money=I('post.money');
                if(empty($logs)){
                    $logs = array(
                        'type' => 'exchange',
                        'user_id' => $this->uid,
                        'order_id' => $order_id,
                        'code' => $code,
                        'need_pay' => $money,
                        'create_time' => NOW_TIME,
                        'create_ip' => get_client_ip(),
                        'is_paid' => 0,
                    );

                    //单个付款走的这里，为什么没写入数据库
                    $logs['log_id'] = D('Paymentlogs')->add($logs);
                }else{
                    $logs['need_pay'] = $money;
                    $logs['code'] = $code;
                    D('Paymentlogs')->save($logs);
                }

            }
            $obj ->where("order_id={$order_id}")->save(array('need_pay' => $money));//再更新一次最终的价格
            D('Weixintmpl')->weixin_notice_goods_user($order_id,$this->uid,1);//商城微信通知

            $this->tuMsg('订单设置完成，即将进入付款。', U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }

    //多个商家商品支付
    public function paycode(){
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->error('没有有效支付记录');
        }
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->error('没有有效的支付记录');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录');
        }
        $order_ids = explode(',', $detail['order_ids']);
        $ordergood = D('Integralordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('Integralgoodslist', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);
        //收货地址部分重写
        $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => goods, 'log_id' => $log_id));
        $this -> assign('defaultAddress', $defaultAddress);
        $this -> assign('changeAddressUrl', $changeAddressUrl);
        //重写结束
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('logs', $detail);
        $this->display();
    }

    //多个商家商品支付
    public function paycode2(){
        //这里是因为原来的是按订单付，这里是合并付款逻辑部分
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->tuMsg('没有有效支付记录');
        }
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->tuMsg('没有有效的支付记录');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->tuMsg('没有有效的支付记录');
        }
        $order_ids = explode(',', $detail['order_ids']);
        //这里合并付款逻辑暂时不做1，做留言系统，2，做优惠劵ID，3;优惠劵减去的金额
        D('Integralorder')->where(array('order_id' => array('IN', $order_ids)))->save(array('addr_id' => $addr_id));
        /**********************修复合并付款的时候的系列订单错误问题*****************************/
        $orders = D('Integralorder')->where(array('order_id' => array('IN', $order_ids)))->select();
        foreach ($orders as $k => $val) {
            $need_pay[$val[order_id]] = $val['total_price'] - $val['mobile_fan'] - $val['use_integral'];
            D('Integralorder')->where(array('order_id' => $val['order_id']))->save(array('need_pay' => $need_pay[$val[order_id]]));
        }
        /*****************************************************/
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
        if ($code == 'wait') {
            //如果是货到付款
            D('Integralorder')->save(array('is_daofu' => 1, 'status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
            D('Integralordergoods')->save(array('is_daofu' => 1, 'status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
            D('Integralorder')->mallSolds($order_ids);//更新销量
            D('Integralorder')->jifenPeisong(array($order_ids), 1);//更新配送
           // D('Sms')->mallTZshop($order_ids);//用户下单通知商家
            D('Integralorder')->combination_jifen_print($order_ids);//多商家订单打印
            $this->tuMsg('恭喜您下单成功', U('user/exchange/index'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->tuMsg('该支付方式不存在');
            }
            //合并付款开始
            foreach($order_ids as $v){
                $need_pay = D('Integralorder')->useIntegrals($this->uid, array($v));//这个不知道能不能返回
                D('Integralorder')->where("order_id={$v}")->save(array('need_pay' => $need_pay));//合并付款的时候更新实际付款金额
                $log_need +=$need_pay;
            }
            $detail['need_pay']= $log_need;
            $detail['code'] = $code;
            //合并付款结束
            $detail['code'] = $code;
            D('Paymentlogs')->save($detail);
            $this->tuMsg('订单设置完成，即将进入付款。', U('jifen/combine', array('log_id' => $detail['log_id'])));
        }
    }

    public function combine(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $log_id = (int) $this->_get('log_id');
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->error('没有有效的支付记录');
        }

        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录');
        }
        $this->assign('button', D('Payment')->getCode($detail));
        $this->assign('logs', $detail);
        $this->display();
    }

    //付款前检测库存
    public function goods_mum($order_id){
        $order_id = (int) $order_id;
        $ordergoods_ids = D('Integralordergoods')->where(array('order_id' => $order_id))->select();
        foreach($ordergoods_ids as $k => $v){
            $goods_num = D('Integralgoodslist')->where(array('goods_id' => $v['goods_id']))->find();
            //也得检查下那个多规格的 这里
            $is_spec_stock = is_spec_stock_jifen($v[goods_id],$v[key],$v['num']);
            if(!$is_spec_stock){
                $spec_one_num =  get_one_spec_stock_jifen($v[goods_id],$v[key]);
                $this->tuMsg('亲！规格为<' . $v['key_name']. '>的商品库存不够了,只剩' . $spec_one_num . '件了');
            }
            if($goods_num['num'] < $v['num']){
                $this->tuMsg('商品ID' . $v['goods_id'] . '库存不足无法付款',U('user/exchange/index',array('aready'=>1)));;
            }
        }
        return false;
    }

    //会员领取积分
    public function receive(){
	      if(IS_AJAX){
              if (empty($this->uid)) {
                  echoJson(['code'=>1,'msg'=>'登录状态失效','url'=>U('passport/login')]);
              }
              $config=D('Setting')->fetchAll();
              $jifen=$config['integral']['right_integral'];
              $data=array(
                  'user_id'=>$this->uid,
                  'integral'=>$jifen,
                  'time'=>NOW_TIME,
                  'type'=>1,
                  'is_use'=>1,
                  'create_ip'=>get_client_ip()
              );
              if(false !== D('Usersintegral')->add($data)){
                  D('Users')->addIntegral($this->uid,$jifen,'领取新人专享积分');
                  echoJson(['code'=>1,'msg'=>'领取成功','url'=>U('/jifen')]);
              }else{
                  echoJson(['code'=>-1,'msg'=>'领取失败，稍后再试']);
              }
          }
    }

    //用户发邀请
    public function invitation(){
        if(IS_AJAX){
            if (empty($this->uid)) {
                echoJson(['code'=>1,'msg'=>'登录状态失效','url'=>U('passport/login')]);
            }
            $config=D('Setting')->fetchAll();
            $jifen=$config['integral']['right_one_integral'];
            $end=$config['integral']['integral_time'];
            $jieshu=date("Y-m-d H:i:s", strtotime("+".$end."hour"));;
            $user=D('Usersintegral')->where(['user_id'=>$this->uid,'type'=>2,'is_use'=>0])->select();
            $times=D('Usersintegral')->where(array('user_id'=>$this->uid,'type'=>2))->order(array('id'=>'desc'))->find();
            if(empty($user) && time()>$times['end_time']){
                $data=array(
                    'user_id'=>$this->uid,
                    'integral'=>$jifen,
                    'time'=>NOW_TIME,
                    'type'=>2,
                    'end_time'=>strtotime($jieshu),
                    'create_ip'=>get_client_ip()
                );
                if(false !== D('Usersintegral')->add($data)){
                    echoJson(['code'=>1,'msg'=>'复制成功,转发好友抢积分！']);
                }else{
                    echoJson(['code'=>-1,'msg'=>'复制失败']);
                }
            }
            echoJson(['code'=>1,'msg'=>'复制成功,转发好友抢积分！']);
        }
    }

    //用户点赞
    public function dainzan()
    {
        if (IS_AJAX) {
            if (empty($this->uid)) {
                echoJson(['code'=>1,'msg'=>'登录状态失效','url'=>U('passport/login')]);
            }
         
            $id=(int) $_POST['id'];
            $config=D('Setting')->fetchAll();
            $jifen1=$config['integral']['zan_integral_one'];
            $jifen2=$config['integral']['zan_integral_two'];
            $sumjifen=rand($jifen1,$jifen2);
            $yaoqin=D('Usersintegral')->where(['id'=>$id])->find();
            if($yaoqin['user_id']!=$this->uid && $yaoqin['user_id2'] != $this->uid && $yaoqin['user_id3'] != $this->uid && $yaoqin['user_id4'] != $this->uid){
                if(empty($yaoqin['user_id2'])){
                    $num=$yaoqin['num']+1;
                    $yes=D('Usersintegral')->where(['id'=>$id])->save(array('user_id2'=>$this->uid,'num'=>$num));
                    if(false !== $yes){
                        D('Users')->addIntegral($this->uid,$sumjifen,'帮助好友点赞获得积分');
                    }
                    echoJson(['code'=>1,'msg'=>'助力好友点赞成功','nums'=>1]);
                }elseif(empty($yaoqin['user_id3'])){
                    $num=$yaoqin['num']+1;
                    $yes=D('Usersintegral')->where(['id'=>$id])->save(array('user_id3'=>$this->uid,'num'=>$num));
                    if(false !== $yes){
                        D('Users')->addIntegral($this->uid,$sumjifen,'帮助好友点赞获得积分');
                    }
                    echoJson(['code'=>1,'msg'=>'助力好友点赞成功','nums'=>2]);
                }elseif(empty($yaoqin['user_id4'])){
                    $num=$yaoqin['num']+1;
                    $yes=D('Usersintegral')->where(['id'=>$id])->save(array('user_id4'=>$this->uid,'num'=>$num));
                    if(false !== $yes){
                        D('Users')->addIntegral($this->uid,$sumjifen,'帮助好友点赞获得积分');
                    }
                    echoJson(['code'=>1,'msg'=>'助力好友点赞成功','nums'=>3]);
                }else{
                    echoJson(['code'=>-1,'msg'=>'好友点赞已完成，请下次参与']);
                }
            }else{
                if($yaoqin['user_id']==$this->uid){
                    echoJson(['code'=>-1,'msg'=>'发起人不能参与点赞']);
                }else{
                    echoJson(['code'=>-1,'msg'=>'好友点赞已完成，请下次参与']);
                }

            }
        }
    }

    //非新领取积分
    public function collar(){
        if (IS_AJAX) {
            if (empty($this->uid)) {
                echoJson(['code'=>1,'msg'=>'登录状态失效','url'=>U('passport/login')]);
            }
            $id = (int)$_POST['id'];
            $config=D('Setting')->fetchAll();
            $jifen=$config['integral']['right_one_integral'];
            $lingqu=D('Usersintegral')->where(['id'=>$id])->save(['is_use'=>1]);
            $ji=D('Usersintegral')->where(['user_id'=>$this->uid,'id'=>$id])->find();
            if(false !== $lingqu){
                D('Users')->addIntegral($ji['user_id'],$jifen,'完成点赞成功，获得积分');
                echoJson(['code'=>1,'msg'=>'领取成功','url'=>U('wap/jifen/index')]);
            }
            echoJson(['code'=>-1,'msg'=>'领取失败，请稍后再试']);

        }
    }


}
