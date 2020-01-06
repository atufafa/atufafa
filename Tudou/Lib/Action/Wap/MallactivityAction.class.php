<?php
class MallactivityAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
        $this->assign('user',$this->uid);
        $this->assign('times',D('MallactivitySpecial')->select());
        $where=array('type'=>4,'close'=>0,'user_id'=>$this->uid);
        $pin=D('FabulousEleStoreMarkert')->where($where)->select();
        $count=D('FabulousEleStoreMarkert')->where($where)->count();
        $this->assign('count',$count);
        $product_id=array();
        foreach ($pin as $val){
            $product_id[]=$val['goods_id'];
        }
        $this->assign('dianz',D('MallactivityGoods')->itemsByIds($product_id));
    }

    //限时抢购
    public function limited(){
        import('ORG.Util.Page');
        $obj=D('Ad');
        $ad=$obj->where(array('site_id'=>430))
            ->order(array('buckle_jifen' => 'desc'))
            ->limit(0,8)
            ->select();
        foreach ($ad as $val){
            $goods[]=$val['goods_id'];
        }
        $this->assign('ad',$ad);
        $this->assign('ele',D('Goods')->itemsByIds($goods));
        //查询更多广告位商品
        $ads=$obj->alias('a')
            ->where(array('site_id'=>430))
            ->order(array('buckle_jifen' => 'desc'))
            ->select();
        foreach ($ads as $k=>$val){
            $goo[]=$val['goods_id'];
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
        $this->assign('eles',D('Goods')->itemsByIds($goo));


        $this->assign('nextpage', LinkTo('mallactivity/limitedloaddata'));
        $this->assign('likepage',LinkTo('mallactivity/likeloaddata'));
        $this->display();
    }

    //加载
    public function limitedloaddata(){
        $obj = D('MallactivityGoods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type_id'=>2);
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
            $arr_id[]=$val['goods_id'];
            $shop_id[]=$val['shop_id'];
        }
        $this->assign('goods',D('Goods')->itemsByIds($arr_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //猜你喜欢
    public function likeloaddata(){
        $obj = D('Goods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 50);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->order(array('sold_num' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //限量团购
    public function groupbuy(){
        import('ORG.Util.Page');
        $obj=D('Ad');
        $ad=$obj->where(array('site_id'=>431))
            ->order(array('buckle_jifen' => 'desc'))
            ->limit(0,8)
            ->select();
        foreach ($ad as $val){
            $goods[]=$val['goods_id'];
        }
        $this->assign('ad',$ad);
        $this->assign('ele',D('Goods')->itemsByIds($goods));
        //查询更多广告位商品
        $ads=$obj->alias('a')
            ->where(array('site_id'=>431))
            ->order(array('buckle_jifen' => 'desc'))
            ->select();
        foreach ($ads as $k=>$val){
            $goo[]=$val['goods_id'];
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
        $this->assign('eles',D('Goods')->itemsByIds($goo));

        $this->assign('nextpages', LinkTo('mallactivity/grouploaddata'));
        $this->assign('tuanlike', LinkTo('mallactivity/tuanloaddata'));
        $this->display();
    }

    //猜你喜欢
    public function tuanloaddata(){
        $obj = D('Goods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'is_pintuan'=>1,'huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);
        $Page = new Page($count, 50);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->order(array('mall_price' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }
    //加载
    public function grouploaddata(){
        $obj = D('MallactivityGoods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type_id'=>3);
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
        ///$list = array_slice($lists,$Page->firstRow, $Page->listRows);

        foreach ($list as $val){
            $arr_id[]=$val['goods_id'];
            $shop_id[]=$val['shop_id'];
        }
        $this->assign('goods',D('Goods')->itemsByIds($arr_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //天天特价
    public function special(){
        $user_id=isset($_GET['user_id'])?$_GET['user_id']:0;
        $goods_id=isset($_GET['goods_id'])?$_GET['goods_id']:0;
        $params=array(
            'user_id'=>$user_id,
            'goods_id'=>$goods_id,
        );
        $this->assign('nextpag', LinkTo('mallactivity/specialloaddata',$params));
        $this->assign('telike', LinkTo('mallactivity/telikeloaddata'));
        $this->display();
    }

    public function telikeloaddata(){
        $obj = D('Goods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'huodong'=>0);
        $lists = $obj->where($map)->select();
        $count = count($lists);

        $Page = new Page($count, 20);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $obj->where($map)->order(array('mall_price' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       // $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //加载
    public function specialloaddata(){
        $obj = D('AdminGoods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0);
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

        $shijian=date("H:i:s",time());
        if('06:00:00'>$shijian){
            $time='6:00:00';
        }elseif('12:00:00'>$shijian){
            $time='12:00:00';
        }elseif ('20:00:00'>$shijian){
            $time='20:00:00';
        }elseif ('23:00:00'){
            $time='23:00:00';
        }
       
        $this->assign('time',$time);
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
            $config=D('Setting')->fetchAll();
            $shops=D('Shop')->where(['shop_id'=>$shop])->find();
            if($shops['is_manage']==1){
                $nums = $config['shop']['shop_zan'];
            }else{
                $nums = 5;
            }

            $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'product_id'=>$product_id,'type'=>4,'close'=>0))->find();
            if(empty($cunzai) ){
                $data=array();
                $data['product_id']=$product_id;
                $data['shop_id']=$shop;
                $data['user_id']=$this->uid;
                $data['create_time']=NOW_TIME;
                $data['create_ip']=get_client_ip();
                $data['countnum']=$nums;
                $data['type']=4;
                $data['type_id']=1;
                $data['is_pingtai']=1;
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

            $obj=D('FabulousEleStoreMarkert')->where(['user_id'=>$user_id1,'goods_id'=>$goods_id,'type'=>4,'type_id'=>1,'close'=>0])->find();
            $ss=explode(',',$obj['user_ids']);

            foreach ($ss as $val){
                $s=$ss;
            }
            if(in_array($user,$s)){
                echoJson(['status'=>'error','msg'=>'您已经参与此次点赞了','data'=>'']);
            }else{
                $config=D('Setting')->fetchAll();
                $num1=$config['goods']['goods_jifen1'];
                $num2=$config['goods']['goods_jifen2'];
                if(empty($obj['user_ids'])){
                    $jia=$user;
                }else{
                    $jia=$obj['user_ids'].','.$user;
                }
                $num=$obj['num']+1;
                if(false !== D('FabulousEleStoreMarkert')->where(['user_id'=>$user_id1,'goods_id'=>$goods_id])->save(['user_ids'=>$jia,'num'=>$num])){
                    $jifen=rand($num1,$num2);
                    D('Users')->addIntegral($user,$jifen,'帮助好友点赞获得积分'.$jifen);
                    echoJson(['status'=>'success','msg'=>'恭喜您点赞成功，获得积分'.$jifen,'data'=>'']);
                }else{
                    echoJson(['status'=>'success','msg'=>'抱歉稍后再试','data'=>'']);
                }
          }
        }else{
            echoJson(['status'=>'error','msg'=>'抱歉稍后再试','data'=>'']);
        }

    }

    //领取购物卷
    public function shopping(){
        $this->assign('nextpa', LinkTo('mallactivity/shoppingloaddata'));
        $this->display();
    }

    //加载
    public function shoppingloaddata(){
        $obj = D('ShoppingEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'close' =>0,'type'=>4);
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

    //新普通商城特价
    public function mallspecial(){
        $user_id=isset($_GET['user_id'])?$_GET['user_id']:0;
        $goods_id=isset($_GET['goods_id'])?$_GET['goods_id']:0;
        $params=array(
            'user_id'=>$user_id,
            'goods_id'=>$goods_id,
        );
        $this->assign('mallnex', LinkTo('mallactivity/mallspecialloaddata',$params));
        $this->assign('malltelike', LinkTo('mallactivity/malltelikeloaddata'));
        $this->display();
    }

    //加载
    public function mallspecialloaddata(){
        $obj = D('MallactivityGoods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'type_id'=>1,'type'=>0);
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
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
       // $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        foreach ($list as $val){
            $arr_id[]=$val['goods_id'];
            $time_ids[]=$val['time_id'];
        }
        $this->assign('goods',D('Goods')->itemsByIds($arr_id));
        $this->assign('time',D('MallactivitySpecial')->itemsByIds($time_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //猜你喜欢
    public function malltelikeloaddata(){
        $obj = D('Goods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' =>0,'huodong'=>0);

        $count = $obj->where($map)->count();
        $Page = new Page($count,50);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('mall_price' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$list = array_slice($lists,$Page->firstRow, $Page->listRows);
        $this->assign('list', $list);

        $this->display();

    }



}