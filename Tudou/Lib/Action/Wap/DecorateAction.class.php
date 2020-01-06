<?php
class DecorateAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        $this->assign('cartnum', (int) array_sum($this->cart));
        $cate = D('Decorate')->getEleCate();
            // print_r($cate);die;
        $this->assign('elecate', $cate);
        $cate_ids = D('Decorate')->getEleCateIds();
        $this->assign('elecates',$cate_ids);
        $cate_idss = D('Decorate')->getAllEleCate();
        $this->assign('elecatess',$cate_idss);
        if(empty($this->_CONFIG['operation']['decorate'])){
        $this->error('装修功能已关闭');die;
        }

        }
    //显示页面信息
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
        $this->assign('nextpage', LinkTo('decorate/loaddata', $linkArr, array('t' => NOW_TIME, 'p' => '0000')));
        $this->assign('linkArr', $linkArr);

        //获取IN
        $shops = D('Shop')->where(array('city_id'=>$this->city_id))->select();
        foreach($shops as $val){
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $lists = D('Eleproduct')->where(array('is_tuijian'=>1, 'audit' => 1, 'closed' =>0,'shop_id'=>array('in', $shop_ids),'cost_price' => array('neq','')))->order(array('sold_num' => 'desc','create_time' => 'desc'))->limit(0,6)->select();
        $this->assign('product', $list = second_array_unique_bykey($lists,'shop_id'));//去掉重复商家
        $this->display();
    }

    //加载
    public function loaddata(){
        $ele = D('Decorate');
        import('ORG.Util.Page');
        $map = array('audit' => 1,'city_id' => $this->city_id);

        $order = $this->_param('order', 'htmlspecialchars');
        switch($order){
            case 'a':
                $orderby = array("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') )" => 'asc',  'month_num' => 'desc',);
                break;
            case 's':
                $orderby = array('views' => 'desc');
                break;
            default:
                $orderby = array('check_price'=>'desc');
                break;
        }

        $cate = $this->_param('cate', 'htmlspecialchars');
        $lists = $ele->order($orderby)->where($map)->select();
        foreach($lists as $k => $val) {
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
        $shop_ids = array();
        foreach ($list as &$val){
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //详情页
        public function detail($id) {
            if (empty($this->uid)) {
                $this->error('请先登陆', U('passport/login'));
            }
            $id = (int) $id;
            if (empty($id)) {
                $this->error('商家不存在');
            }
            if (!($detail = D('Decorate')->find($id))) {
                $this->error('商家不存在');
            }
            $usershop=D('DecorateShop')->where(['shop_id'=>$detail['shop_id']])->find();
            $this->assign('tel',$usershop);

            if ($detail['closed'] != 0 || $detail['audit'] != 1) {
                $this->error('商家不存在');
            }
            $shop_id = $detail['shop_id'];
            $shops =D('Shop')->find($shop_id);

            //是否竞价
            if($detail['is_tui'] ==1 && $shops['user_id'] != $this->uid){
                D('Decorate')->check_price($id);
            }

            $user=D('Users')->where(['user_id'=>$shops['user_id']])->find();
            $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
            $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
            $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$user[mobile]'");
            $md=md5($rs[0]['memberIdx']);
            $datas=array(
                'chat_user_id' => $rs[0]['memberIdx'],
                'token'=>$md,
                'chat_shop_id'=>$re_shop[0]['memberIdx'],
                'shop_name'=>$detail['title']
            );

            $params = http_build_query($datas);
            $service_url = 'http://chat.atufafa.com/mobile/villageChat.php?' . $params;
            $this->assign('service_url', $service_url);

            $this->assign('detail', $detail);
            $this->assign('shop', D('Shop')->find($shop_id));
            $goodsss=D('Decorate')->find($id);
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

            $this->assign('goods', $goodsss);
            $pingnum = D('DecorateDianping')->where(array('order_id' => $id))->count();
            $this->assign('pingnum', $pingnum);
            $score = (int) D('DecorateDianping')->where(array('order_id' => $id))->avg('score');
            if ($score == 0) {
                $score = 5;
            }
            $this->assign('score', $score);
            if(($detail['is_vs1'] || $detail['is_vs2'] || $detail['is_vs3'] || $detail['is_vs4'] || $detail['is_vs5'] || $detail['is_vs6']) || $detail['is_vs7'] || $detail['is_vs8'] || $detail['is_vs9'] || $detail['is_vs10'] || $detail['is_vs11']==1 ){
                $this->assign('is_vs', $is_vs = 1);
            }else{
                $this->assign('is_vs', $is_vs = 0);
            }
            $this->assign('pics', D('Decoratephoto')->limit(0,1)->getPics($detail['goods_id']));
            //查询用户是否已购买返利劵
            $user=D('Users')->where(['user_id'=>$this->uid])->getField('decorate');
            $this->assign('user',$user);
            $this->assign('payment', D('Payment')->getPayments(true));

            $config = D('Setting')->fetchAll();
            $this->assign('fanlijuan',$config['site']['decoratejuan']);

            $this->display();
        }

    //购买返利卷
    public function pay(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        $data=array();
        $code = $this->_post('code', 'htmlspecialchars');
        $id = $this->_post('id','htmlspecialchars');
        $datas['fanlijuan']=$this->_post('fanlijuan','htmlspecialchars');
        $data['time']=$this->_post('time','htmlspecialchars');
        $data['tel'] =$this->_post('tel','htmlspecialchars');
        if(empty($data['time'])){
            $this->error('预约时间为空');
        }
        if(empty($data['tel'])){
            $this->error('联系电话不能为空');
        }
        $shop=D('Decorate')->where(['id'=>$id])->find();
        if(empty($shop)){
            $this->error('预约的商家不存在');
        }
        //var_dump($data['time']);die;
        $data['sell_user_id']=$shop['shop_id'];
        $data['user_id']=$this->uid;
        $data['life_id']=$id;
        $data['type']=3;

        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
        }
        $logs = array(
            'user_id' => $this->uid,
            'type' => 'decorate',
            'code' => $code,
            'order_id' => $order_id,
            'psy' => 0,
            'need_pay' =>$datas['fanlijuan'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip()
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];

        if($logs['log_id']){
            $obj = D('Lifereserve');
            $data['log_id']=$logs['log_id'];
            if ($obj->add($data)){

            }else{
                D('Paymentlogs')->delete($logs['log_id']);
                $this->error('请重新输入');
            }
        }

        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('logs', $logs);
        $this->display();
    }

    //预约
    public function yuyue(){
        if($this->ispost()){
            if(empty($this->uid)){
                header("Location:" . U('passport/login'));
                die;
            }
            $user=D('Users')->where(['user_id'=>$this->uid])->find();
            if($user['decorate']==0){
                $this->tuMsg('您未购买返利优惠劵，不能预约商家');
            }

            $time=I('post.time');
            $tel=I('post.tel');
            $id=I('post.id');
            $shop=D('Decorate')->where(['id'=>$id])->find();
            if(empty($shop)){
                $this->tuMsg('您要预约的商家不存在');
            }
            $exp=D('Lifereserve')->where(['user_id'=>$this->uid,'life_id'=>$id,'type'=>3,'is_pay'=>1,'close'=>0])->find();
            if(!empty($exp)){
                $this->tuMsg('您已预约过此商家了，请前往个人中心，预约信息列表查看',U('decorate/detail',array('id'=>$id)));
            }

            if(empty($time)){
                $this->tuMsg('预约时间不能为空');
            }
            if(empty($tel)){
                $this->tuMsg('联系方式不能为空');
            }

            $arr=array('life_id'=>$id, 'user_id'=>$this->uid, 'sell_user_id'=>$shop['shop_id'],'time'=>$time, 'tel'=>$tel, 'type'=>3, 'is_pay'=>1);
            if(false !== D('Lifereserve')->add($arr)){
                $this->tuMsg('预约成功，请前往个人中心，预约信息列表查看',U('decorate/detail',array('id'=>$id)));
            }
        }
    }

    //公司介绍
    public function shopdetail($id){
        $id = (int) $id;
        $shop=D('Decorate')->where(['id'=>$id])->find();
        $detail=D('DecorateShop')->where(['shop_id'=>$shop['shop_id']])->find();
        $this->assign('detail',$detail);
        $this->assign('yyzz',$detail['photo'].'?x-oss-process=style/atufafa');
        $this->assign('photos', D('DecorateQualifications')->getPics($shop['shop_id']));
        $this->assign('honors', D('DecorateHonor')->getPics($shop['shop_id']));
        $this->display();
    }

    //gps
    public function gps($shop_id,$type = '0'){
        $shop_id = (int) $shop_id;
        $type = (int) $this->_param('type');
        if(empty($shop_id)){
            $this->error('该商家不存在');
        }
        $shop = D('DecorateShop')->where(['shop_id'=>$shop_id])->find();
        $this->assign('shop', $shop);
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

    //评价
    public function dianping($id){
        $id = (int) $id;
        if(!($detail = D('Decorate')->find($id))){
            $this->tuMsg('没有该商品');
            die;
        }

        $this->assign('next', LinkTo('decorate/dianpingloading',array('id' => $id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->display();
    }

    //加载
    public function dianpingloading(){
        $goods_id = (int) $this->_get('id');
        if(!($detail = D('Decorate')->find($goods_id))){
            die('0');
        }

        $Goodsdianping = D('DecorateDianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'order_id' => $goods_id);
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
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }

        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('detail', $detail);
        $this->display();
    }



}