<?php
class EleactivityAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
        $this->assign('user',$this->uid);
        $this->assign('times',D('TimeEleStoreMarket')->where(['type'=>1])->select());
        $where=array('type'=>1,'close'=>0,'user_id'=>$this->uid,'type_id'=>1);
        $pin=D('FabulousEleStoreMarkert')->where($where)->select();
        $count=D('FabulousEleStoreMarkert')->where($where)->count();
        $this->assign('count',$count);
        $product_id=array();
        foreach ($pin as $val){
            $product_id[]=$val['product_id'];
        }
        $this->assign('dianz',D('GoodsEleStoreMarket')->where(['type'=>1])->itemsByIds($product_id));

    }

    //限时抢购
    public function limited(){
        $lat = cookie('lat');
        $lng = cookie('lng');
        import('ORG.Util.Page');
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $obj=D('Ad');
        $ad=$obj
            ->alias('a')
            ->join('tu_ele e on(a.shop_id=e.shop_id)','left')
            ->where(array('site_id'=>416))
            ->order(array('buckle_jifen' => 'desc'))
            ->limit(0,6)
            ->select();
        //查询配送范围
        $config = D('Setting')->fetchAll();
        $pei=$config['freight']['radius'];
        foreach ($ad as $k=>$val){
            $goods[]=$val['goods_id'];
            $ad[$k]['radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
            if (!empty($ad[$k]['radius'])){
                if ((int) $ad[$k]['radius'] > $pei*10000){
                    unset($ad[$k]);
                }
            }
        }
        $list = array_slice($ad,0,6);
        $this->assign('ad',$list);
        $this->assign('ele',D('Eleproduct')->itemsByIds($goods));
        //查询更多广告位商品
        $ads=$obj->alias('a')
            ->join('tu_ele e on(a.shop_id=e.shop_id)','left')
            ->where(array('site_id'=>416))
            ->order(array('buckle_jifen' => 'desc'))
            ->select();
        foreach ($ads as $k=>$val){
            $goo[]=$val['goods_id'];
            $ad[$k]['radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
            if (!empty($ad[$k]['radius'])){
                if ((int) $ad[$k]['radius'] > $pei*10000){
                    unset($ad[$k]);
                }
            }
        }
        $count = count($ads);
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $adlist = array_slice($ads,$Page->firstRow, $Page->listRows);
        $this->assign('ads',$adlist);
        $this->assign('eles',D('Eleproduct')->itemsByIds($goo));

        $this->assign('nextpage', LinkTo('eleactivity/limitedloaddata'));
        $this->assign('likepage',LinkTo('eleactivity/likeloaddata'));
        $this->display();
    }

    //加载
    public function limitedloaddata(){
        $obj = D('GoodsEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type'=>1,'type_id'=>2);
        $where['spike_time']=array('EGT', time());
        $where['end_time']=array('EGT',time());
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $arr_id=$shop_id=array();

        $list = $obj->where($map)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        foreach ($list as $val){
            $arr_id[]=$val['product_id'];
            $shop_id[]=$val['shop_id'];
        }
        $this->assign('goods',D('Eleproduct')->itemsByIds($arr_id));
        $this->assign('shop',D('Ele')->itemsByIds($shop_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //猜你喜欢
    public function likeloaddata(){
        $obj = D('Eleproduct');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'is_huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 30);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->order(array('month_num' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //限量团购
    public function groupbuy(){
        $lat = cookie('lat');
        $lng = cookie('lng');
        import('ORG.Util.Page');
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $obj=D('Ad');
        $ad=$obj
            ->alias('a')
            ->join('tu_ele e on(a.shop_id=e.shop_id)','left')
            ->where(array('site_id'=>417))
            ->order(array('buckle_jifen' => 'desc'))
            ->limit(0,6)
            ->select();
        //查询配送范围
        $config = D('Setting')->fetchAll();
        $pei=$config['freight']['radius'];
        foreach ($ad as $k=>$val){
            $goods[]=$val['goods_id'];
            $ad[$k]['radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
            if (!empty($ad[$k]['radius'])){
                if ((int) $ad[$k]['radius'] > $pei*10000){
                   unset($ad[$k]);
                }
            }
        }
        $list = array_slice($ad,0,6);
        $this->assign('ad',$list);
        $this->assign('ele',D('Eleproduct')->itemsByIds($goods));

        //查询更多广告位商品
        $ads=$obj->alias('a')
            ->join('tu_ele e on(a.shop_id=e.shop_id)','left')
            ->where(array('site_id'=>417))
            ->order(array('buckle_jifen' => 'desc'))
            ->select();
        foreach ($ads as $k=>$val){
            $goo[]=$val['goods_id'];
            $ad[$k]['radius'] = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
            if (!empty($ad[$k]['radius'])){
                if ((int) $ad[$k]['radius'] > $pei*10000){
                   unset($ad[$k]);
                }
            }
        }
        $count = count($ads);
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $adlist = array_slice($ads,$Page->firstRow, $Page->listRows);
        $this->assign('ads',$adlist);
        $this->assign('eles',D('Eleproduct')->itemsByIds($goo));
        $this->assign('nextpages', LinkTo('eleactivity/grouploaddata'));
        $this->assign('tuanlike', LinkTo('eleactivity/tuanloaddata'));
        $this->display();
    }

    //猜你喜欢
    public function tuanloaddata(){
        $obj = D('Eleproduct');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'is_tuan'=>1,'is_huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }
    //加载
    public function grouploaddata(){
        $obj = D('GoodsEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type'=>1,'type_id'=>3);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $arr_id=$shop_id=array();

        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        foreach ($list as $val){
            $arr_id[]=$val['product_id'];
            $shop_id[]=$val['shop_id'];
        }
        $this->assign('goods',D('Eleproduct')->itemsByIds($arr_id));
        $this->assign('shop',D('Ele')->itemsByIds($shop_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //天天特价
    public function special(){
        $user_id=isset($_GET['user_id'])?$_GET['user_id']:0;
        $goods_id=isset($_GET['product_id'])?$_GET['product_id']:0;
        $params=array(
            'user_id'=>$user_id,
            'product_id'=>$goods_id,
        );

         $this->assign('nextpag', LinkTo('eleactivity/specialloaddata',$params));
         $this->assign('telike', LinkTo('eleactivity/telikeloaddata'));
        $this->display();
    }

    public function telikeloaddata(){
        $obj = D('Eleproduct');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'is_huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 30);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->order(array('price' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //加载
    public function specialloaddata(){
        $obj = D('GoodsEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type'=>1,'type_id'=>1);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $arr_id=$shop_id=$time_ids=array();
        $time=date('H:i:s',time());
        $where['time_number'] = array('gt',$time);
        $list = $obj->where($map)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        foreach ($list as $val){
            $arr_id[]=$val['product_id'];
            $shop_id[]=$val['shop_id'];
            $time_ids[]=$val['time_id'];
        }
        $this->assign('goods',D('Eleproduct')->itemsByIds($arr_id));
        $this->assign('shop',D('Ele')->itemsByIds($shop_id));
        $this->assign('time',D('TimeEleStoreMarket')->where(['type'=>1])->itemsByIds($time_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //分享
    public function fenxian(){
        $obj=D('FabulousEleStoreMarkert');
        if($this->ispost()){
            $product_id=I('post.product_id');
            $shop=I('post.shop_id');
            $type_id=I('post.type_id');
            $detail=D('GoodsEleStoreMarket')->where(['product_id'=>$product_id,'type'=>1])->find();
            $times=D('TimeEleStoreMarket')->where(['time_id'=>$detail['time_id'],'type'=>1])->find();
            $time=date('H:00',time());
            $nums=5;
            $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'product_id'=>$product_id,'close'=>0,'type'=>1,'type_id'=>$type_id))->find();
            //&& (int) $times['time_name']>(int) $time
            if(empty($cunzai) ){
                $data=array();
                $data['product_id']=$product_id;
                $data['shop_id']=$shop;
                $data['user_id']=$this->uid;
                $data['create_time']=NOW_TIME;
                $data['create_ip']=get_client_ip();
                $data['end_time']=$times['time_name'];
                $data['countnum']=$nums;
                $data['type']=1;
                $data['type_id']=1;
                $obj->add($data);
            }
        }
    }

    //点赞
    public function spot(){
        if (IS_AJAX) {
            $user=$this->uid;
            $user_id1 = (int) $_POST['user_id1'];
            $goods_id = (int) $_POST['goods_id'];
            $type_id = (int) $_POST['type_id'];

            $obj=D('FabulousEleStoreMarkert')->where(['user_id'=>$user_id1,'product_id'=>$goods_id,'type_id'=>$type_id,'type'=>1,'close'=>0])->find();
            $ss=explode(',',$obj['user_ids']);
            foreach ($ss as $val){
                $s=$ss;
            }
            if(in_array($user,$s)){
                echoJson(['status'=>'error','msg'=>'您已经参与此次点赞了','data'=>'']);

            }else{
                $config=D('Setting')->fetchAll();
                $num1=$config['ele']['ele_jifen1'];
                $num2=$config['ele']['ele_jifen2'];
                $jia=$obj['user_ids'].','.$user;
                $num=$obj['num']+1;
                if(false !== D('FabulousEleStoreMarkert')->where(['user_id'=>$user_id1,'product_id'=>$goods_id,'type_id'=>$type_id])->save(['user_ids'=>$jia,'num'=>$num])){
                    $jifen=rand($num1,$num2);
                    D('Users')->addIntegral($user,$jifen,'帮助好友点赞获得积分'.$jifen);
                    echoJson(['status'=>'success','msg'=>'恭喜您点赞成功，获得积分'.$jifen,'data'=>'']);
                }else{
                    echoJson(['status'=>'error','msg'=>'抱歉稍后再试','data'=>'']);
                }
            }
        }else{
           echoJson(['status'=>'error','msg'=>'抱歉稍后再试','data'=>'']);
        }

    }

    //领取购物卷
    public function shopping(){
        $this->assign('nextpa', LinkTo('eleactivity/shoppingloaddata'));
        $this->display();
    }

    //加载
    public function shoppingloaddata(){
        $obj = D('ShoppingEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'close' =>0,'type'=>1);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $config=D('Setting')->fetchAll();
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('config',$config);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();

    }

    //领取优惠卷
    public function receive(){
        if (IS_AJAX) {
            $obj=D('CollarEleStoreMarket');
            $user=$this->uid;
            $id = (int) $_POST['id'];
            $dateil=$obj->where(['user_id'=>$user,'closed'=>0])->find();
            if(!empty($dateil)){
                echoJson(['code'=>'-1','msg'=>'您已经领取过了','data'=>'']);

            }else{
                $obj2=D('ShoppingEleStoreMarket')->where(['id'=>$id])->find();
                $data=array();
                $data['user_id']=$user;
                $data['shopping_id']=$id;
                $data['shop_id']=$obj2['shop_id'];
                $data['create_time']=NOW_TIME;
                $data['create_ip']=get_client_ip();

                $sum=$obj2['num']-1;
                if(false !== $obj->add($data) && false !== D('ShoppingEleStoreMarket')->where(['id'=>$id])->save(['num'=>$sum])){
                    echoJson(['code'=>'1','msg'=>'恭喜您领取成功','data'=>$sum]);
                }
            }
        }else{
            echoJson(['code'=>'-1','msg'=>'领取失败','data'=>'']);
        }
    }

    //抢购商品详情页
    public function details($product_id){
        $obj=D('Eleproduct');
        $detail=$obj->where(array('product_id'=>$product_id))->find();

        $list=session('shop_list');
        $logistics=0;
        $zdlogistics=0;
        foreach ($list as $key => $value) {
            if($value['shop_id']==$detail['shop_id']){
                $logistics=$value['logistics'];
                $zdlogistics=$value['zdlogistics'];
            }
        }

        //查询点赞是否达到
        $fabulous=D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$product_id,'type'=>1,'type_id'=>1,'close'=>0])->find();
        $this->assign('fabulous',$fabulous);
        //查询当前商品是否在开抢时间内
        $goods=D('GoodsEleStoreMarket')->where(['product_id'=>$product_id,'type'=>1,'type_id'=>1,'closed'=>0])->find();
        $time=D('TimeEleStoreMarket')->where(['time_id'=>$goods['time_id'],'type'=>1])->find();
        $this->assign('time',$time);
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
        $eleshop=D('Ele')->where(['shop_id'=>$detail['shop_id']])->find();
        $this->assign('eleshop',$eleshop);
        $this->assign('tuijian', $tuijian = $obj->where(array('shop_id' => $detail['shop_id'],'is_tuijian'=>1,'is_huodong'=>0, 'closed' =>0, 'audit' => 1, 'cost_price' => array('neq','')))->order(array('create_time' =>'desc'))->limit(0,2)->select());//推荐开始
        $dleproduc=D('Eleproduct')->where(array('shop_id'=>$detail['shop_id'],'closed'=>0))->select();
        $this->assign('shoplist',$dleproduc);
        $this->assign('tuan',$lis);
        $this->assign('page',$count);
        session('logistics',$logistics);
        session('zdlogistics',$zdlogistics);
        $this->assign('logistics', $logistics);
        $this->assign('zdlogistics', $zdlogistics);
        $this->assign('pintuan',D('Eleproduct')->where(array('is_tuan'=>1))->select());
        $this->assign('detail',$detail);
        $this->assign('totalnum', $count);
        $this->display();

    }

    //下单
    public function order($type,$product_id){
        if(empty($this->uid)){
            $this->tuMsg('请先登陆', U('passport/login'));
        }
        $stock=D('Eleproduct')->where(['product_id'=>$product_id])->find();
        //判断库存是否大于0
        if($stock['num']>0){
            $logistics=session('logistics');
            $zdlogistics=session('zdlogistics');

            if($type==1){
                $total=$logistics;
            }elseif($type==2){
                $total=$zdlogistics;
            }elseif($type==4){
                $total= 0;
            }

        $data=array(
                'user_id'=>$this->uid,
                'goods_id'=>$product_id,
                'type'=>2,
                'shop_id'=>$stock['shop_id'],
                'time'=>NOW_TIME,
                'delivery_type'=>$type,
                'delivery_money'=>$total
            );
            $secondk=D('GoodsHurry')->add($data);//添加进虚拟表
            if(false !== $secondk){
                //修改库存数量
                $sum=$stock['num']-1;
                D('Eleproduct')->where(['product_id'=>$product_id])->save(['num'=>$sum]);
                $month = date('Ym', NOW_TIME);
                $arr=array(
                    'shop_id'=>$stock['shop_id'],
                    'user_id'=>$this->uid,
                    'total_price'=>$stock['price'],
                    'logistics'=>$total,
                    'tableware_price'=>$stock['tableware_price'],
                    'num'=>1,
                    'type'=>$type,
                    'month' => $month,
                    'need_pay'=>round($stock['price']+$total+$stock['tableware_price'],2),
                    'is_pay' => 0,
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'status' => 0,
                );
                $order= D('Eleorder')->add($arr);
                $arr2=array(
                    'order_id'=>$order,
                    'product_id'=>$product_id,
                    'num'=>1,
                    'total_price'=>$stock['price'],
                    'tableware_price'=>$stock['tableware_price'],
                    'month'=>$month
                );
                D('Eleorderproduct')->add($arr2);
                D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$product_id,'type'=>1,'type_id'=>1])->save(['close'=>1]);
                //抢购成功，跳转到支付页面
                $this->ajaxReturn(array('status' => 'success', 'msg' => '抢购成功!','url'=>U('user/eleorder/index',array('aready'=>0))));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '抢购失败!'));
            }

        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '活动已结束,请参与下轮活动'));
        }

    }






}