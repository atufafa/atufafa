<?php
class AdAction extends CommonAction
{


    public function _initialize(){
        parent::_initialize();
        $this->citys = D('City')->fetchAll();
        $this->assign('citys', $this->citys);
    }

    //购买页面
    public function index()
    {
        $type = $_GET['type'];
        $user = $this->uid;
        $guangao = D('Advertisement');
        $position = D('Adsite');
        $us = D('Users')->where(array('user_id' => $user))->find();
        $obj = D('Ad');

        if ($this->ispost()) {
            $time = I('post.active_time');//购买时间
            $local = I('post.site_id');//购买位置
            $title = I('post.title');//标题
            $jifen = I('post.prestore_integral');//预存积分
            $intro = I('post.intro');//说明
            $city_id = I('post.city_id');//城市
            $goods_id = I('post.goods_id');//商品id
            $types=I('post.types');
//            $shop_buy = $obj->where(array('shop_id' => $this->shop_id, 'closed' => 0, 'site_id' => $local))->find();
//            if (!empty($shop_buy)) {
//                $this->tuError('当前广告位您已购买，请购买其他广告位');
//            }

            if(empty($time)){
                $this->tuError('请选择购买播放广告时间');
            }
            if($local==0){
                $this->tuError('请选择购买广告位置');
            }
            if(empty($title)){
                $this->tuError('请填写标题');
            }
            if(empty($jifen)){
                $this->tuError('请填写预存积分');
            }
            if($jifen<100){
                $this->tuError('预存积分必须大于100');
            }

            if (empty($city_id) && $types!=4) {
                $this->tuError('请选择展示的城市');
            }
            if(empty($goods_id)){
                $this->tuError('请填写商品编号，必须与链接上的一致');
            }

            if ($us['shopintegral'] < $jifen) {
                $this->tuError('当前广告积分不足，请充值后购买！');
            }

            $row=$guangao->where(array('id'=>$time))->find();
            if($us['gold']<$row['buy_money']){
                $this->tuError('当前商户资金不足，请充值后购买！');
            }
            if(empty($intro)){
                $this->tuError('请填写说明');
            }
          
            $row = $guangao->where(array('id' => $time))->find();
            $need_pay = $row['buy_money'];
            $times = NOW_TIME;
            //判断如果是月
            if($row['identification']==1){
                $dan=date("Y-m-d H:i:s",$times);
                $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."months",strtotime($dan)));
            }else{
                $dan=date("Y-m-d H:i:s",$times);
                $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."years",strtotime($dan)));
            }

            $sum=$this->diffBetweenTwoDays($dan,$yue);

            $logs = array(
                'user_id' => $this->uid,
                'title' => $title,
                'city_id' => $city_id,
                'active_time' => $sum,
                'site_id' => $local,
                'prestore_integral' => $jifen,
                'create_time' => $dan,
                'create_ip' => get_client_ip(),
                'intro' => $intro,
                'money' => $need_pay,
                'adv_id' => $time,
                'shop_id' => $this->shop_id,
                'end_time' => $yue,
                'link_url' => $intro,
                'goods_id'=>$goods_id
            );
            if ($chenggo = D('Adrecord')->add($logs)) {
                D('Users')->addGold($user, -$need_pay, '购买广告位【' . $local . '】扣除商户资金');
                $this->tuSuccess('添加申请成功，扣除商户资金【' . round($need_pay, 2) . '】元', U('ad/index'));
            }

            $this->tuError('操作失败');

        }else{

            $this->citys = D('City')->fetchAll();
            $this->assign('citys', $this->citys);
            if ($type == 1) {
                $weizhi = $position->where(array('site_id' => array('in', '416,417')))->select();
            } elseif ($type == 2) {
                $weizhi = $position->where(array('site_id' => array('in', '420,421')))->select();
            } elseif ($type == 3) {
                $weizhi = $position->where(array('site_id' => array('in', '418,419')))->select();
            }elseif ($type==4){
                $weizhi = $position->where(array('site_id' => array('in', '430,431')))->select();
            }
            $buy_guangao=$guangao->where(array('close'=>0))->select();
            $this->assign('guangao',$buy_guangao);
            $this->assign('weizhi',$weizhi);
            $this->assign('type',$type);
            $this->display();
        }

    }


    function diffBetweenTwoDays ($day1, $day2){
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }


    //广告列表
    public function index2(){
        $obj = D('Ad');
        import('ORG.Util.Page');
        $map = array('user_id'=>$this->uid);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($site_id = (int) $this->_param('site_id')){
            $map['site_id'] = $site_id;
            $this->assign('site_id', $site_id);
        }
        $map['site_id']=array('in','416,417,418,419,420,421,430,431');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('ad_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k =>$v){
            $result = D('Users')->where(array('user_id'=>$v['user_id']))->find();
            $list[$k]['nickname'] = $result['nickname'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('sites', D('Adsite')->fetchAll());
        $this->assign('types', D('Adsite')->getType());

        $this->display();
    }

    //充值积分
    public function edit($ad_id){
        if($ad_id = (int) $ad_id){
            $obj = D('Ad');
            if(!($detail = $obj->find($ad_id))){
                $this->tuError('请选择要充值的广告');
            }
            if($this->isPost()){
                $jifen=I('post.jifen');
                if(empty($jifen)){
                    $this->tuError('充值积分不能为空');
                }
                if($jifen<100){
                    $this->tuError('积分最低充值为100');
                }
                $user=D('Users')->where(array('user_id'=>$this->uid))->find();
                if($user['shopintegral']<$jifen){
                    $this->tuError('当前账户积分不足，请充值后，修改');
                }
                $sum=$detail['prestore_integral']+=$jifen;
                // var_dump($sum);die;
                D('Users')->addShopIntegra($user['user_id'],'-'.$jifen,'充值广告积分，扣除账户【'.$jifen.'】积分。');
                if(false !== D('Ad')->where(array('ad_id'=>$ad_id))->save(array('prestore_integral'=>$sum))){
                    $this->tuSuccess('操作成功，扣除账户积分【'.$jifen.'】', U('ad/index2'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $detail);
                $this->display();
            }
        }else{
            $this->tuError('请选择要充值的广告');
        }
    }


    //日志
    public function index3(){
        $obj = D('AdRecordLogs');
        import('ORG.Util.Page');
        $map=array('close'=>0,'shop_id'=>$this->shop_id);
        if($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        }else{
            if($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($id = (int) $this->_param('id')){
            $map['id'] = $id;
            $this->assign('id', $id);
        }
        if($city_id = (int) $this->_param('city_id')){
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if($site_id = (int) $this->_param('site_id')){
            $map['site_id'] = $site_id;
            $this->assign('site_id', $site_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count,15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('sites', D('Adsite')->fetchAll());
        $this->assign('types', D('Adsite')->getType());
        $this->display();
    }

    //置顶
    public function goods_top(){
        if(IS_AJAX){
            $obj = D('ad');
            $ad_id = I('ad_id', 0, 'trim,intval');
            if(!($detail = $obj->find($ad_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该广告位不存在'));
            }
            $check_price = I('check_price');
            if($check_price<=3){
                $this->ajaxReturn(array('status'=>'error','msg'=>'最低竞价积分：3'));
            }
            if(false == $obj->where(['ad_id'=>$ad_id])->save(['is_tui'=>1,'start_time'=>NOW_TIME,'buckle_jifen'=>$check_price])){
                $this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', 'url'=>U('ad/index2')));
            }
        }
    }

    //取消竞价
    public function update_top($ad_id)
    {
        if(!($goods = D('Ad')->find($ad_id))){
            $this->tuError('请选择要操作的商品');
        }
        if($goods['is_tui'] ==0){
            $this->tuError('该商品暂未参加竞价');
        }
        //查询开启竞价时间
        if(NOW_TIME <($goods['start_time']+86400)){
            $time = (int)(($goods['start_time']+86400 - NOW_TIME)/60);
            $this->tuError('未到规定时间，无法取消，需等待'.$time.'分钟');
        }
        if(!false == D('Ad')->where(['ad_id'=>$ad_id])->save(array('buckle_jifen'=>3,'is_tui'=>0,'start_time'=>null))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }


    //编辑广告
    public function editad($ad_id){
        $obj=D('Ad');
        $detail=$obj->find($ad_id);
        if($this->ispost()){
            $goods_id=I('post.goods_id');
            $link=I('post.intro');
            if(false !== $obj->where(['ad_id'=>$ad_id])->save(['goods_id'=>$goods_id,'link_url'=>$link])){
                $this->tuSuccess('编辑成功',U('ad/index2'));
            }
            $this->tuError('编辑失败');
        }else{
            $this->assign('detail',$detail);
            $this->display();
        }


    }

}