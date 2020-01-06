<?php
class WideAction extends CommonAction
{
    //购买日志广告位
    public function index(){

        $user=$this->uid;
        $guangaos=D('Advertisement');
        $position=D('Adsite');
        $us=D('Users')->where(array('user_id'=>$user))->find();
        $obj=D('Ad');

        if($this->ispost()){
            $time=I('post.active_time');//购买时间
            $local=I('post.site_id');//购买位置
            $title=I('post.title');//标题
            $jifen=I('post.prestore_integral');//预存积分
            $photo=I('post.photo');//图片
            $intro=I('post.intro');//说明
            $city_id=I('post.city_id');//城市
            $goods_id = I('post.goods_id');//商品id
            //判断是否购买广告位
            $cunzai=D('Adrecord')->where(array('user_id'=>$user))->find();
            //判断要发送的广告位是否已存在
            //马上抢    412,413,414
            if($local != 412 || $local != 413 || $local != 414){
                $guangao=$obj->where(['site_id'=>$local])->select();
                $guanum=$obj->where(['site_id'=>$local])->count();
                if(!empty($guangao) && $guanum>6){
                    $this->tuError('当前广告位已被购买，请购买其他广告位');
                }
            }


            $shop_buy=$obj->where(array('shop_id'=>$this->shop_id,'closed'=>0,'site_id'=>$local))->find();
            if(!empty($shop_buy) && $user!=78){
                $this->tuError('当前广告位已购买，请购买其他广告位');
            }

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
            if($us['shopintegral']<$jifen){
                $this->tuError('当前广告积分不足，请充值后购买！');
            }
            if(empty($goods_id)){
                $this->tuError('请填写商品编号，必须与链接上的一致');
            }

            if(empty($photo)){
                $this->tuError('请上传图片');
            }
            $row=$guangaos->where(array('id'=>$time))->find();
            if($us['gold']<$row['buy_money']){
                $this->tuError('当前商户资金不足，请充值后购买！');
            }
            if(empty($intro)){
                $this->tuError('请填写说明');
            }

            $need_pay=$row['buy_money'];
            $times=NOW_TIME;
            //判断如果是月
            if($row['identification']==1){
                $dan=date("Y-m-d H:i:s",$times);
                $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."months",strtotime($dan)));
            }else{
                $dan=date("Y-m-d H:i:s",$times);
                $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."years",strtotime($dan)));
            }

            $sum=$this->diffBetweenTwoDays($dan,$yue);

            $logs=array(
                'user_id'=>$this->uid,
                'title'=>$title,
                'city_id'=>$city_id,
                'active_time'=>$sum,
                'site_id'=>$local,
                'prestore_integral'=>$jifen,
                'photo'=>$photo,
                'create_time'=>$dan,
                'create_ip'=>get_client_ip(),
                'intro'=>$intro,
                'money'=>$need_pay,
                'adv_id'=>$time,
                'shop_id'=>$this->shop_id,
                'end_time'=>$yue,
                'link_url'=>$intro,
                'goods_id'=>$goods_id
            );
            if($chenggo = D('Adrecord')->add($logs)){
                D('Users')->addGold($user,-$need_pay,'购买广告位【'.$local.'】扣除商户资金');
                $this->tuSuccess('添加申请成功，扣除商户资金【'.round($need_pay,2).'】元', U('wide/buy'));
            }

            $this->tuError('操作失败');

        }else{

            $this->citys = D('City')->fetchAll();
            $this->assign('citys', $this->citys);
            //马上抢    412,413,414
            //手机首页  250,251,252,253,254,255,256,257,258
            //商城首页  394,395,396,397,398,399,400,401,402
            //积分商城  259,260,261,262,263,264,265,266,267
            //0员购     340,341,342,343,344,345,346,347,348
            //在线抢购  367,368,369,370,371,372,373,374,375
            if($user!=78){
                $weizhi=$position->where(array('site_id'=>array(
                    'in','250,251,252,253,254,255,256,257,258,394,395,396,397,398,399,400,401,402,259,260,261,262,263,264,265,266,267,
                    340,341,342,343,344,345,346,347,348,367,368,369,370,371,372,373,374,375,412,413,414')))
                    ->select();
            }else{
                $weizhi=$position->order(array('site_id'=>'asc'))->select();
            }
            $buy_guangao=$guangaos->where(array('close'=>0))->select();
            $this->assign('guangao',$buy_guangao);
            $this->assign('weizhi',$weizhi);
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


    //购买广告位列表
    public function buy(){
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
                    $this->tuSuccess('操作成功，扣除账户积分【'.$jifen.'】', U('wide/buy'));
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


    //广告位点击日志
    public function buylog(){
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

    //删除
    public function delete($log_id = 0){
        if(is_numeric($log_id) && ($log_id = (int) $log_id)){
            $obj = D('AdRecordLogs');
            $res = $obj->find($log_id);
            $obj->where(array('log_id'=>$log_id))->save(array('close'=>1));
            $this->tuSuccess('删除成功', U('wide/buylog'));
        }
            $this->tuError('非法操作');
        }

    //广告位列表
    public function list_bit(){
        $obj = D('Adsite');
        import('ORG.Util.Page');
        $map = array('site_id'=>array('in','86,87,88,90,91,92,93,94,95'));
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('site_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }










}