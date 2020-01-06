<?php
class MembervipAction extends CommonAction
{
    public function _initialize()
    {
        parent::_initialize();
        if ($this->_CONFIG['operation']['exchange'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
        $goods = cookie('goods_spec');
        $this->assign('cartnum', (int)array_sum($goods));
        $cat = (int)$this->_param('cat');
        $this->assign('goodscates', $goodscates = D('Goodscate')->fetchAll());
        $cates = (int)$this->_param('cates');
        $this->assign('goodscatess', $getFurniture = D('Goodscate')->getFurniture($cates));
        $this->assign('title', D('Goodscate')->getFurnitureName($cates));
        // print_r($getFurniture);die;
        $check_user_addr = D('Paddress')->where(array('user_id' => $this->uid))->find();//全局检测地址
        $this->assign('check_user_addr', $check_user_addr);
        $config = D('Setting')->fetchAll();
        $is_open=$config['backers']['is_shopping'];
        $this->assign('open',$is_open);
    }

    //显示
    public function index(){
        $this->assign('nextpage', LinkTo('membervip/loaddata',array('t' => NOW_TIME, 'p' => '0000')));
        $this->display();
    }

    public function loaddata(){
        $Goods = D('ExchangeGoods');
        import('ORG.Util.Page');

        $map['audit'] = 1;
        $map['closed'] = 0;
        $map['end_date'] = array('egt', TODAY);
        // $map['city_id'] = $this->city_id;


        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keyword'] = $keyword;
        }


        //开始组装数组
        $query_string = explode('/', $_SERVER["QUERY_STRING"]);
        $arr = array();
        foreach ($query_string as $key => $values) {
            if (strstr($values, 'values_') !== false) {
                array_push($arr, $values);
            }
        }

        foreach ($arr as $k => $v) {
            $arr[$v] = $this->_param($v, 'htmlspecialchars');
            $query[$v] = $arr[$v];
            $this->assign('query', $query);
            $linkArr[$v] = $arr[$v];
        }

        $array = array();
        foreach ($query as $kk => $vv) {
            $explode = explode('_', $kk);
            $array[$kk]['attr_id'] = $explode['1'];
            $array[$kk]['attr_value'] = $vv;
        }
        foreach ($array as $val) {
            $attr_values[$val['attr_value']] = $val['attr_value'];
        }

        $maps['attr_value'] = array('IN', $attr_values);
        //$TpGoodsAttr = M('TpGoodsAttr')->where($map)->group('attr_value')->select();
        $TpGoodsAttr = M('ExchangeGoodsAttr')->where($maps)->select();


        $result = array();
        foreach ($TpGoodsAttr as $key => $info) {
            $result[$info['goods_id']][] = $info;
        }

        foreach ($result as $kkk => $vvv) {
            foreach ($vvv as $k2 => $v2) {
                $attr_valuess[$kkk][$k2] = $v2['attr_value'];
            }
        }

        $implode = implode('_', $attr_values);

        foreach ($attr_valuess as $k3 => $v3) {
            $implodes = implode('_', $v3);
            if ($implodes != $implode) {
                unset($attr_valuess[$k3]);
            }
        }


        foreach ($attr_valuess as $k4 => $v4) {
            $goods_ids[$k4] = $k4;
        }
        if ($array) {
            $map['goods_id'] = array('IN', $goods_ids);
        }
        //多属性搜索结束

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
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }

        $list = $Goods->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $val['d'] = getDistance($lat, $lng, $Shop['lat'], $Shop['lng']);
            $list[$k] = $val;
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function detail($goods_id)
    {
        $goods_id = (int)$goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!($detail = D('ExchangeGoods')->find($goods_id))) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }

        $this->assign('recom', $recom = D('ExchangeGoods')->where(array('audit' => 1, 'closed' => 0, 'goods_id' => array('neq', $goods_id), 'end_date' => array('EGT', TODAY)))->limit(0, 5)->select());

        // print_r($detail);die;s
        $this->assign('detail', $detail);
        $filter_spec = $this->get_spec($goods_id); //获取商品规格参数
        $goodsss = M('ExchangeGoods')->find($goods_id);
        $goodsss[mall_price] = $goodsss[mall_price];
        $spec_goods_price = M('ExchangeSpecGoodsPrice')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        if ($spec_goods_price != null) {
            $this->assign('spec_goods_price', json_encode($spec_goods_price, true)); // 规格 对应 价格 库存表
        }
        $yh = $goodsss[yh];
        if ($yh != '0') {
            $yh = explode(PHP_EOL, $yh);
            for ($i = 0; $i < count($yh) - 1; $i++) {
                $yh[s][] = explode(',', $yh[$i]);
            }
            foreach ($yh[s] as $k2 => $vo) {
                foreach ($vo as $k2 => $v2) {
                    $rs[$k2][] = $v2;
                }
            }
            $goodsss['zks'][] = $rs[0];
            $goodsss['zks'][] = $rs[1];
        }

        $this->assign('filter_spec', $filter_spec);
        $this->assign('goods', $goodsss);
        $this->assign('exchange',D('BuyVip')->where(['user_id'=>$this->uid,'is_exchange'=>0])->find());
        $this->assign('pics', D('ExchangeGoodsPhotos')->getPics($detail['goods_id']));
        $this->assign('goods_attribute', $goods_attribute = M('ExchangeGoodsAttribute')->getField('attr_id,attr_name'));//属性值
        $this->assign('goods_attr_list', $goods_attr_list = M('ExchangeGoodsAttr')->where("goods_id = $goods_id")->select());//属性列表
        $this->display();
    }

    public function get_spec($goods_id)
    {
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('ExchangeSpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if ($keys) {
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__exchange_spec AS a INNER JOIN __PREFIX__exchange_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = M()->query($sql);
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[$val['name']][] = array(
                    'item_id' => $val['id'],
                    'item' => $val['item'],
                );
            }
        }
        return $filter_spec;
    }

    //立即购买
    public function buy($goods_id)
    {
        $goods_id = (int)$goods_id;
        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!($detail = D('ExchangeGoods')->find($goods_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->tuMsg('该商品不存在');
        }
        if ($detail['end_date'] < TODAY) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $goods_spec = cookie('exchange_spec');

        $num = (int)$this->_get('num');

        $spec_key = $this->_get('spec_key');

        $type=$this->_get('type');

        if (empty($num) || $num <= 0) {
            $num = 1;
        }

        if($type==1){
            $money=M('ExchangeSpecGoodsPrice')->where(['goods_id'=>$goods_id,'key'=>$spec_key])->find();
            $youhui=D('BuyVip')->where(['user_id'=>$this->uid])->find();
            if($money['price']>$youhui['money']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '您的兑换值不够，不能兑换此商品'));
            }
        }elseif($type==2){
            $money=M('ExchangeSpecGoodsPrice')->where(['goods_id'=>$goods_id,'key'=>$spec_key])->find();
        }

        $is_spec_stock = is_spec_exchange($goods_id, $spec_key, $num);
        if (!$is_spec_stock) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
        }
        if ($detail['num'] < $num) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
        }
        $goods_spec_v = $goods_id . '|' . $spec_key; //重新组合那个 商品id和那个啥规格键
        if (isset($goods_spec[$goods_spec_v])) {
            $goods_spec[$goods_spec_v] += $num;
        } else {
            $goods_spec[$goods_spec_v] = $num;
        }
        $key[$goods_id] = $spec_key;//规格

        $goods=D('ExchangeGoods')->where(['goods_id'=>$goods_id])->find();
        $kuaidi=M('Pkuaidi')->where(['id'=>$goods['kuaidi_id']])->find();
        $yunfie=M('Pyunfei')->where(['kuaidi_id'=>$kuaidi['id']])->find();
        $attr=M('ExchangeSpecItem')->where(['id'=>$spec_key])->find();
        $defaultAddress = D('Paddress')->defaultAddress($this->uid, $type);//收货地址部分重写
        if($type==1){
            $yunf=(float) $yunfie['shouzhong'];
            $need_pay=(float) 0;
        }elseif($type==2){
            $yunf=(float) $yunfie['shouzhong'];
            $need_pay= (float) $money['price'];
        }
        $arr=array(
            'user_id'=>$this->uid,
            'total_price' => $need_pay,
            'express_price'=>$yunf,
            'need_pay'=>$need_pay+$yunf,
            'address_id' => $defaultAddress['id'],
            'create_time'=>NOW_TIME,
            'create_ip'=>get_client_ip(),

        );

        if ($order_id = D('ExchangeOrder')->add($arr)) {
            $data=array(
               'order_id'=>$order_id,
                'goods_id'=>$goods_id,
                'weight'=>$goods['weight'],
                'num'=>$num,
                'kuaidi_id'=>$goods['kuaidi_id'],
                'price'=>$need_pay,
                'total_price'=>$need_pay+$yunf,
                'express_price'=>$yunf,
                'create_time'=>NOW_TIME,
               'create_ip'=>get_client_ip(),
                'key'=>$spec_key,
                'type'=>$type,
                'key_name'=>$attr['item']
            );
            D('ExchangeOrderGoods')->add($data);
        }

        cookie('exchange_spec', $goods_spec, 604800);
        echoJson(array('status' => 'success', 'msg' => '兑换成功，正在跳转到支付页面', 'url' => U('membervip/pay',['order_id'=>$order_id])));
    }

    //支付
    public function pay()
    {
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $this->check_mobile();
        $order_id = (int)$this->_get('order_id');
        $order = D('ExchangeOrder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }

        if ($this->isPost()) {
            D('ExchangeOrder')->where(['order_id' => $order_id])->save(['useEnvelope' => $useEnvelope, 'useEnvelope_id' => $_POST['useEnvelope_id']]);
            D('Paymentlogs')->where(['log_id' => $Paymentlogs['log_id']])->save(['need_pay' => $need_pay]);
        }
        $ordergood = D('ExchangeOrderGoods')->where(array('order_id' => $order_id))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('goods', D('ExchangeGoods')->itemsByIds($goods_id));
        $this->assign('ExchangeOrderGoods', $ordergood);

        //收货地址部分重写
        if (false == $defaultAddress = D('Paddress')->order_address_exp($this->uid, $order_id)) {
            $this->error('获取用户地址出错，请先去会员中心添加商城地址后下单');
        }
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => exp, 'order_id' => $order_id));
        $this->assign('defaultAddress', $defaultAddress);
        $this->assign('changeAddressUrl', $changeAddressUrl);

        $Paymentlogs = D('Paymentlogs')->getLogsByOrderId('goods', $order_id);
        if ($Paymentlogs['need_pay']) {
            $need_pay = $Paymentlogs['need_pay'];
        } else {
            $need_pay = $order['total_price'] + $order['express_price'] - $coupon['reduce_price'] - $use_integral - $order['mobile_fan'] - $useEnvelope;
        }
        $this->assign('need_pay', $need_pay);
        $this->assign('order', $order);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->display();
    }

    //付款
    public function pay2()
    {
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $obj = D('ExchangeOrder');
        $order_id = (int)$this->_get('order_id');
        $order = $obj->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
        }

        $address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : $order['address_id'];//检测配送地址ID
        if (empty($address_id)) {
            $this->tuMsg('配送的地址异常');
        } else {
            $obj->save(array('order_id' => $order_id, 'address_id' => $address_id));
        }

        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
        $this->goods_mum($order_id);//检测库存
        $address = D('Paddress')->where(array('address_id' => $order['address_id']))->find();
        if ($code == 'wait') {
            //如果是货到付款
            $obj->save(array('order_id' => $order_id, 'status' => 1, 'is_daofu' => 1));
            D('ExchangeOrderGoods')->save(array('is_daofu' => 1, 'status' => 1), array('where' => array('order_id' => $order_id)));
            $obj->mallSold($order_id);//更新销量
            $obj->mallPeisong(array($order_id), 1);//更新配送
           // D('Sms')->mallTZshop($order_id);//用户下单通知商家
            $obj->combination_goods_print($order_id);//万能商城订单打印
            D('Weixintmpl')->weixin_notice_goods_user($order_id, $this->uid, 0);//商城微信通知
            $this->tuMsg('恭喜您下单成功', U('user/goods/index'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->tuMsg('该支付方式不存在');
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('vip', $order_id); //写入支付记录


            if ($order['is_change'] != 1) {
                $need_pay = $order['need_pay'];
            } else {
                $need_pay = $order['need_pay'];//如果是改价的扫码都不加
            }

            if (empty($logs)) {
                $logs = array(
                    'type' => 'vip',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $need_pay,
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0,
                );
                //单个付款走的这里，为什么没写入数据库
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $need_pay;
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }

            $obj->where("order_id={$order_id}")->save(array('need_pay' => $need_pay));//再更新一次最终的价格
            D('Weixintmpl')->weixin_notice_goods_user($order_id, $this->uid, 1);//商城微信通知
            cookie('exchange_spec',null);
            $this->tuMsg('订单设置完成，即将进入付款。', U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }

    //付款前检测库存
    public function goods_mum($order_id)
    {
        $order_id = (int)$order_id;
        $ordergoods_ids = D('ExchangeOrderGoods')->where(array('order_id' => $order_id))->select();
        foreach ($ordergoods_ids as $k => $v) {
            $goods_num = D('ExchangeGoods')->where(array('goods_id' => $v['goods_id']))->find();
            //也得检查下那个多规格的 这里
            $is_spec_stock = is_spec_exchange($v[goods_id], $v[key], $v['num']);
            if (!$is_spec_stock) {
                $spec_one_num = get_one_spec_exchange($v[goods_id], $v[key]);
                $this->tuMsg('亲！规格为<' . $v['key_name'] . '>的商品库存不够了,只剩' . $spec_one_num . '件了');
            }
            if ($goods_num['num'] < $v['num']) {
                $this->tuMsg('商品ID' . $v['goods_id'] . '库存不足无法付款', U('user/goods/index', array('aready' => 1)));;
            }
        }
        return false;
    }

    //说明
    public function explain(){
        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
        $hui=D('BuyVip')->where(['user_id'=>$this->uid])->find();
        $this->assign('hui',$hui);



        $this->display();
    }


}