<?php
class IntegralmallAction extends CommonAction
{
    private $create_field = array('title', 'cate_id', 'now_price', 'shop_id', 'face_pic', 'integral', 'price', 'num', 'limit_num', 'exchange_num', 'details', 'orderby', 'create_time', 'create_ip');
    private $add_field = array('money', 'order_num', 'shop_id', 'title');
    private $create = array(
        'title', 'photo', 'shoplx','cate_id','intro','guige', 'num','is_reight','weight','kuaidi_id',
        'limit_num', 'exchange_num','select1', 'select2', 'select3', 'select4', 'select5','price', 'shopcate_id',
        'mall_price','use_integral','instructions', 'details', 'end_date','is_vs1','is_vs2','is_vs3','is_vs4',
        'is_vs5','is_vs6','is_vs7','is_vs8','is_vs9','parent_id');
    private $edit = array(
        'title', 'photo', 'shoplx','cate_id','intro','guige', 'num','is_reight','weight','kuaidi_id',
        'limit_num', 'exchange_num','select1', 'select2', 'select3', 'select4', 'select5','price', 'shopcate_id',
        'mall_price','use_integral','instructions', 'details', 'end_date','is_vs1','is_vs2','is_vs3','is_vs4',
        'is_vs5','is_vs6','is_vs7','is_vs8','is_vs9','parent_id');
    public function _initialize()
    {
        parent::_initialize();
        //快递
        $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$this->shop_id,'type'=>goods,'closed'=>0))->select());
        $shop = D('Shop')->where(array('shop_id' => $this->shop_id))->find();
        $cate = D('Shopcate')->where(array('cate_id' => $shop['cate_id']))->find();
        $coun = D('Shopcate')->where(array('parent_id' => array('in', $cate['cate_id'])))->select();
        $this->assign('cates', $coun);
        $this->autocates = D('Integralshopcate')->where(array('shop_id' => $this->shop_id))->select();
        $this->assign('autocates', $this->autocates);
    }

    //申请入驻Shopintegral
    public function settled()
    {
        $user = $this->uid;
        $ruzhu = D('Integralsetting');
        if ($this->ispost()) {
            $id = I('post.id_id');
            if ($id == 0) {
                $this->tuError('请选择');
            }
            $row = $ruzhu->where(array('id' => $id))->find();
            $shop = D('Shop')->where(array('shop_id' => $this->shop_id, 'user_id' => $user))->find();
            $us = D('Users')->where(array('user_id' => $shop['user_id']))->find();
            if ($us['gold'] < $row['buy_money']) {
                $this->tuError('当前商户资金不足，请充值后购买！');
            }
            $need_pay = $row['buy_money'];
            $time = NOW_TIME;
            //判断当前入驻积分商城是否过期
            $lo = D('Shopintegral')->where(array('shop_id' => $this->shop_id))->find();
            if ($lo['end_time'] > $time) {
                $this->tuError('当前购买的积分商城费用还未过期！');
            }

            //判断如果是月
            if ($row['identification'] == 1) {
                $dan = date("Y-m-d H:i:s", $time);
                $yue = date("Y-m-d H:i:s", strtotime("+" . $row['num'] . "months", strtotime($dan)));
                $times = strtotime($yue);
                //判断如果是年
            } elseif ($row['identification'] == 2) {
                $dan = date("Y-m-d H:i:s", $time);
                $yue = date("Y-m-d H:i:s", strtotime("+" . $row['num'] . "years", strtotime($dan)));
                $times = strtotime($yue);
            }
            $logs = array(
                'shop_id' => $this->shop_id,
                'shop_mall' => $id,
                'time' => $row['buy_time'],
                'money' => $need_pay,
                'num' => $row['goods_num'],
                'goods_money' => $row['num_money'],
                'create_time' => NOW_TIME,
                'end_time' => $times
            );

            if ($chenggo = D('Shopintegral')->add($logs)) {
                D('Users')->addGold($user, -$need_pay , '申请入驻积分商城【' . $id . '】扣除预存资金');
                $this->tuSuccess('添加成功，扣除商户资金【' . round($need_pay, 2) . '】元', U('integralmall/index'));
            }
            $this->tuError('操作失败');

        } else {
            $buy_hongbao = $ruzhu->where(array('close' => 0))->select();
            $this->assign('hongbao', $buy_hongbao);
            $this->display();
        }
    }

    //申请列表1564984508
    public function index()
    {
        $obj = D('Shopintegral');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id, 'shop_close' => 0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('end_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function del($id)
    {
        $detial = D('Shopintegral')->where(array('id' => $id, 'shop_id' => $this->shop_id))->find();
        if (empty($detial)) {
            $this->tuError('记录不存在');
        }
        $time = NOW_TIME;
        if ($time < $detial['end_time']) {
            $this->tuError('该使用记录还未到期');
        }

        if (D('Shopintegral')->save(array('eid' => $id, 'shop_close' => 1))) {
            $this->tuSuccess('操作成功', U('integralmall/index'));
        } else {
            $this->tuError('操作失败');
        }
    }

    //添加积分商品
    public function addgoods()
    {
        if ($this->isPost()) {
            $goodsnum = D('Integralgoods')->where(array('shop_id' => $this->shop_id, 'closed' => 0))
                ->count("goods_id");
            $shop = D('Shopintegral')->where(array('shop_id' => $this->shop_id, 'close' => 0))->find();
            if (empty($shop['shop_id'])) {
                $this->tuSuccess('您还未入住积分商城，请您先入住', U('integralmall/settled'));
            }
            if ($shop['shenhe'] == 0) {
                $this->tuError('您的申请还未通过,请通过后添加商品');
            }
            if ($goodsnum >= $shop['num']) {
                $this->tuError('您上传的商品数量已达到限制');
            }
            $data = $this->createCheck();
            $obj = D('Integralgoods');
            $data['shop_id'] = $this->shop_id;
            if ($obj->add($data)) {
                if ($goodsnum > 0) {
                    D('Users')->addGold($this->uid, -$shop['goods_money'] , '再次上传积分商城商品,扣除预存资金');
                    $this->tuSuccess('添加成功，扣除商户资金【' . round($shop['goods_money'], 2) . '】元', U('integralmall/goodslist'));
                } else {

                    $this->tuSuccess('添加成功', U('integralmall/goodslist'));
                }
            }
            $this->tuError('操作失败');
        } else {
            $shops = D('Shopintegral')->where(array('shop_id' => $this->shop_id))->find();
            $this->assign('config', $shops);
            $this->display();
        }
    }

    private function createCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create_field);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('产品名称不能为空');
        }
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        var_dump($parent_id);die;
        $data['parent_id']=$parent_id;
        $data['cate_id'] = htmlspecialchars($data['cate_id']);
        if (empty($data['cate_id'])) {
            $this->tuError('商品分类不能为空');
        }

        $data['face_pic'] = htmlspecialchars($data['face_pic']);
        if (empty($data['face_pic'])) {
            $this->tuError('请上传产品图片');
        }
        if (!isImage($data['face_pic'])) {
            $this->tuError('产品图片格式不正确');
        }
        $data['integral'] = (int)$data['integral'];
        if (empty($data['integral'])) {
            $this->tuError('兑换积分不能为空');
        }
        $data['price'] = (float)$data['price'];
        if (empty($data['price'])) {
            $this->tuError('原价不能为空');
        }
        $data['now_price'] = (float)$data['now_price'];
        if (empty($data['now_price'])) {
            $this->tuError('现价不能为空');
        }
        if ($data['now_price'] > $data['price']) {
            $this->tuError('现价不能大于原价');
        }
        $data['num'] = (int)$data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存数量不能为空');
        }
        $data['limit_num'] = (int)$data['limit_num'];
        if (empty($data['limit_num'])) {
            $this->tuError('限制单用户兑换数量不能为空');
        }
        $data['exchange_num'] = (int)$data['exchange_num'];
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('商品介绍不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品介绍含有敏感词：' . $words);
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int)$data['orderby'];
        return $data;
    }

    //编辑
    public function edit($goods_id)
    {
        $obj = D('Integralgoods');
        if ($this->ispost()) {
            $data = $this->createCheck();
            $data['goods_id'] = $goods_id;
            $data['shop_id'] = $this->shop_id;
            if ($obj->save($data)) {
                $this->tuSuccess('修改成功', U('integralmall/goodslist'));
            }

            $this->tuError('操作失败');

        } else {
            $detail = $obj->where(array('goods_id' => $goods_id))->find();
            $this->assign('detail', $detail);
            $this->display();
        }
    }

    //下架
    public function delete($goods_id)
    {
        if (D('Integralgoods')->save(array('goods_id' => $goods_id, 'shop_closed' => 1))) {
            $this->tuSuccess('下架成功', U('integralmall/goodslist'));
        } else {
            $this->tuError('操作失败');
        }
    }

    //显示列表
    public function goodslist()
    {
        $Integralgoods = D('Integralgoods');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = (int)$this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($audit = (int)$this->_param('audit')) {
            $map['audit'] = $audit === 1 ? 1 : 0;
            $this->assign('audit', $audit);
        }
        $count = $Integralgoods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Integralgoods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
        }

        $this->assign('types', D('Shopcate')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //奖励制度
    public function reward()
    {
        $shop = D('Shopintegral')->where(array('shop_id' => $this->shop_id))->find();
        $config = D('Setting')->fetchAll();
        $this->assign('CONFIG', $config);
        $this->assign('shop', $shop);
        $this->display();
    }

    //申请奖励说明
    public function addreward()
    {
        if ($this->isPost()) {
            $data = $this->addCheck();
            $obj = D('Shoprewardlogs');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('integralmall/rewardlist'));

            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }

    public function addCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->add_field);
        $data['money'] = htmlspecialchars($data['money']);
        if (empty($data['money'])) {
            $this->tuError('销售金额不能为空');
        }
        $data['order_num'] = htmlspecialchars($data['order_num']);
        if (empty($data['order_num'])) {
            $this->tuError('订单数量不能为空');
        }
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('申请说明不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        $data['create_time'] = NOW_TIME;

        return $data;
    }

    //申请日志
    public function rewardlist()
    {
        $obj = D('Shoprewardlogs');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //新显示列表
    public function goodsindex(){
        $Goods = D('Integralgoodslist');
        import('ORG.Util.Page');
        $map = array('shop_id' =>$this->shop_id);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = array('IN', D('Goodscate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }

        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $kuaidi_id=array();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;

            $kuaidi_id['kuaidi_id'] = $val['kuaidi_id'];
           //
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }

       // $this->assign('kuaidi',D('Pkuaidi')->itemsByIds($kuaidi_id));
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //新添加商品
    public function creategoods()
    {
        if ($this->isPost()) {
            $goodsnum = D('Integralgoodslist')->where(array('shop_id' => $this->shop_id, 'closed' => 0))
                ->count("goods_id");
            $shop = D('Shopintegral')->where(array('shop_id' => $this->shop_id, 'close' => 0))->find();
            if (empty($shop['shop_id'])) {
                $this->tuSuccess('您还未入住积分商城，请您先入住', U('integralmall/settled'));
            }
            if ($shop['shenhe'] == 0) {
                $this->tuError('您的申请还未通过,请通过后添加商品');
            }
            if ($goodsnum >= $shop['num']) {
                $this->tuError('您上传的商品数量已达到限制');
            }

            $data = $this->createChecks();
            $obj = D('Integralgoodslist');
            if ($goods_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($goods_id, 3);
                $obj->save(array('goods_id' => $goods_id, 'wei_pic' => $wei_pic));
                $photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Integralgoodsphoto')->upload($goods_id, $photos);
                }
                $this->shuxin($goods_id);
                $this->saveGoodsAttr($goods_id, $_POST['goods_type']); //更新商品属性 -->
                if ($goodsnum > 0) {
                    D('Users')->addGold($this->uid, -$shop['goods_money'] , '再次上传积分商城商品,扣除预存资金');
                    $this->tuSuccess('添加成功，扣除商户资金【' . round($shop['goods_money'], 2) . '】元', U('integralmall/goodsindex'));
                } else {

                    $this->tuSuccess('添加成功', U('integralmall/goodsindex'));
                }

            }
            $this->tuError('操作失败');
        } else {
            $shop_user = D('Shop')->where(array('shop_id' => $this->shop_id))->find();
            $this->assign('goodsInfo', D('Integralgoodslist')->where('goods_id=' . I('GET.id', 0))->find());  // 商品详情
            $this->assign('goodsType', D("Integralgoodstype")->where(array("shop_id" => $this->shop_id))->select());
            // dump($shop_user);
            // dump( D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());die;
            $shop_tel=$this->_CONFIG['goods']['shop_tel'];
            if($shop_user['mobile']==$shop_tel){
                $this->assign('cates',D('Goodscate')->fetchAll());
            }else{
                $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
            }
            $this->display();
        }

    }

    //编辑
    public function edits($goods_id = 0){

        if ($goods_id = (int) $goods_id) {
            $obj = D('Integralgoodslist');
            if (!$detail = $obj->find($goods_id)) {
                $this->error('请选择要编辑的商品');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->error('请不要试图越权操作其他人的内容');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['goods_id'] = $goods_id;
                if (!empty($detail['wei_pic'])) {
                    if (true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($goods_id, 3);
                        $data['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($goods_id, 3);
                    $data['wei_pic'] = $wei_pic;
                }

                if (false !== $obj->save($data)) {
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Integralgoodsphoto')->upload($goods_id, $photos);
                    }
                    $this->shuxin($goods_id);
                    $this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性

                    $this->tuSuccess('操作成功', U('integralmall/goodsindex'));
                }
                $this->tuError('操作失败');
            } else {

                $goodsInfo=D('Integralgoodslist')->where('goods_id='.I('GET.goods_id',0))->find();
                $this->assign('goodsInfo',$goodsInfo);
                $this->assign('goodsType',D("Integralgoodstype")->select());

                $this->assign('detail', $obj->_format($detail));
                $this->assign('parent_id',D('Goodscate')->getParentsId($detail['cate_id']));
                $this->assign('attrs', D('Goodscateattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->assign('photos', D('Integralgoodsphoto')->getPics($goods_id));
                $shop_user=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
                $shop_tel=$this->_CONFIG['goods']['shop_tel'];
                if($shop_user['mobile']==$shop_tel){
                    $this->assign('cates',D('Goodscate')->fetchAll());
                }else{
                    $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                    $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
                }


                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的商品');
        }

    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('产品名称不能为空');
        } $data['shop_id'] = (int) $this->shop_id;
        if (empty($data['shop_id'])) {
            $this->tuError('商家不能为空');
        }
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;

        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('副标题不能为空');
        }

        $data['guige'] = htmlspecialchars($data['guige']);
        if (empty($data['guige'])) {
            $this->tuError('规格不能为空');
        }

        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        }
        $data['is_reight'] = (int) $data['is_reight'];

        $data['weight'] = (int) $data['weight'];
        if ($data['is_reight'] == 1) {
            if (empty($data['weight'])) {
                $this->tuError('重量不能为空');
            }
        }
        $data['kuaidi_id'] = (int) $data['kuaidi_id'];
        if ($data['is_reight'] == 1) {
            if (empty($data['kuaidi_id'])) {
                $this->tuError('运费模板不能为空');
            }
        }
        $shopdetail = D('Shop')->find($this->shop_id);
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }

        $data['shopcate_id'] = (int) $data['shopcate_id'];
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['city_id'] = $this->shop['city_id'];
        $data['goods_type'] = I('goods_type');
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        }
        $data['mall_price'] = (float) ($data['mall_price'] );
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }

        $data['use_integral']=htmlspecialchars($data['use_integral']);
        if (empty($data['use_integral'])) {
            $this->tuError('积分抵扣不能为空');
        }

        $data['limit_num']=htmlspecialchars($data['limit_num']);
        $data['exchange_num']=htmlspecialchars($data['exchange_num']);

        $data['instructions'] = SecurityEditorHtml($data['instructions']);

        if (empty($data['instructions'])) {
            $this->tuError('购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        } $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('商品详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        } $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('过期时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('过期时间格式不正确');
        }

        //var_dump($data['end_date']);var_dump($data['day_date']);die;
        $data['is_vs1'] = (int) $data['is_vs1'];
        $data['is_vs2'] = (int) $data['is_vs2'];
        $data['is_vs3'] = (int) $data['is_vs3'];
        $data['is_vs4'] = (int) $data['is_vs4'];
        $data['is_vs5'] = (int) $data['is_vs5'];
        $data['is_vs6'] = (int) $data['is_vs6'];
        $data['is_vs7'] = (int) $data['is_vs7'];
        $data['is_vs8'] = (int) $data['is_vs8'];
        $data['is_vs9'] = (int) $data['is_vs9'];

        $data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];

        $data['profit_enable'] = (int) $data['profit_enable'];
        $data['profit_rate1'] = (int) $data['profit_rate1'];
        $data['profit_rate2'] = (int) $data['profit_rate2'];
        $data['profit_rate3'] = (int) $data['profit_rate3'];
        $data['orderby'] = (int) $data['orderby'];
        $data['audit'] = 1;
        return $data;
    }

    //商品上架下架
    public function update($goods_id = 0){
        if($goods_id = (int) $goods_id){
            if(!($detail = D('Integralgoodslist')->find($goods_id))){
                $this->tuError('请选择要操作的商品');
            }
            $data = array('closed' =>0,'goods_id' => $goods_id);
            $intro = '上架商品成功';
            if($detail['closed'] == 0){
                $data['closed'] = 1;
                $intro = '下架商品成功';
            }
            if(D('Integralgoodslist')->save($data)){
                $this->tuSuccess($intro, U('integralmall/goodsindex'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }


    public function shuxin($goods_id){
        if($_POST['item']){
            $spec = D('Integralgoodsspec')->getField('id,name');
            $specItem = D('Integralspecitem')->getField('id,item');

            $specGoodsPrice = D("Integralgoodsspecprice");
            $specGoodsPrice->where('goods_id = '.$goods_id)->delete();
            foreach($_POST['item'] as $k => $v){
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']);
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
            }
            $specGoodsPrice->addAll($dataList);
        }
        refresh_stock($goods_id);

    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetAttrInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
        $str = $this->getAttrInput($goods_id,$type_id);
        exit($str);
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type){


        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if($goods_type == 0)  {
            D('Integralgoodsattr')->where(array('goods_id'=>$goods_id))->delete();
            return;
        }

        $GoodsAttrList = D('Integralgoodsattr')->where(array('goods_id'=>$goods_id))->select();

        $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
        foreach($GoodsAttrList as $k => $v){
            $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
        }

        // post提交的属性以attr_id_和值的组合为键名
        $post_goods_attr = array();

        foreach($_POST as $k => $v){
            $attr_id = str_replace('attr_','',$k);
            if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2){
                $v2 = str_replace('_', '', $v2); //替换特殊字符
                $v2 = str_replace('@', '', $v2); //替换特殊字符
                $v2 = trim($v2);

                if(empty($v2))
                    continue;
                $tmp_key = $attr_id."_".$v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if(array_key_exists($tmp_key , $old_goods_attr)){
                    //如果这个属性原来就存在
                    if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){
                        //并且价格不一样就做更新处理
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        D('Integralgoodsattr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));
                    }
                }else{
                    //否则这个属性 数据库中不存在 说明要做删除操作
                    D('Integralgoodsattr')->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }
        }
        //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
        foreach($old_goods_attr as $k => $v){
            D('Integralgoodsattr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
        }
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('Integralgoodsspec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = D('Integralspecitem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = D('Integralgoodsspecprice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        if($goods_id){
            $specImageList = D('Integralspecimgs')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');
    }
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }


    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2);

        $spec = D('Integralgoodsspec')->getField('id,name');
        $specItem = D('Integralspecitem')->getField('id,item,spec_id');
        $keySpecGoodsPrice = D('Integralgoodsspecprice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,store_count,bar_code');

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        foreach ($clo_name as $k => $v) {
            $str .=" <td><b>{$spec[$v]}</b></td>";
        }
        $str .="<td><b>价格</b></td>
              <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
        foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";
        }
        $str .= "</table>";
        return $str;
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id){
        $attributeList = D('Integralgoodsattribute')->where(array('type_id'=>$type_id))->select();
        foreach($attributeList as $key => $val){

            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
            //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; //加减符号
                //单选属性或者复选属性
                if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                    if($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";

                //手工录入
                if($val['attr_input_type'] == 0){
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                //从下面的列表中选择（一行代表一个可选值）
                if($val['attr_input_type'] == 1){
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                    foreach($tmp_option_val as $k2=>$v2){
                        //编辑的时候有选中值
                        $v2 = preg_replace("/\s/","",$v2);
                        if($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                }
                //多行文本框
                if($val['attr_input_type'] == 2){
                    $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                }
                $str .= "</td></tr>";
            }

        }
        return  $str;
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return D('Integralgoodsattr')->where(array('goods_attr_id'=>$goods_attr_id))->select();
        if($goods_id > 0 && $attr_id > 0)
            return D('Integralgoodsattr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();
    }


    private function createChecks()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('产品名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('副标题不能为空');
        }

        $data['guige'] = htmlspecialchars($data['guige']);
        if (empty($data['guige'])) {
            $this->tuError('规格不能为空');
        }
        $data['num'] = (int)$data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        }
        $data['is_reight'] = (int)$data['is_reight'];

        $data['weight'] = (int)$data['weight'];
        if ($data['is_reight'] == 1) {
            if (empty($data['weight'])) {
                $this->tuError('重量不能为空');
            }
        }
        $data['kuaidi_id'] = (int)$data['kuaidi_id'];
        if ($data['is_reight'] == 1) {
            if (empty($data['kuaidi_id'])) {
                $this->tuError('运费模板不能为空');
            }
        }
        $data['shop_id'] = $this->shop_id;
        $shopdetail = D('Shop')->find($this->shop_id);

        $data['cate_id'] = (int)$data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }
        //$Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
        $parent_id = (int)$data['parent_id'];
        if ($parent_id == 0) {
            $this->tuError('请选择二级分类');
        }
        $data['shopcate_id'] = (int)$data['shopcate_id'];


        $data['area_id'] = $shopdetail['area_id'];
        $data['business_id'] =$shopdetail['business_id'];
        $data['city_id'] = $shopdetail['city_id'];

        $data['goods_type'] = I('goods_type');

        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }


        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['price'] = (float)($data['price'] );
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        }
        $data['mall_price'] = (float)($data['mall_price'] );
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }
        $data['select5'] = (int)$data['select5'];
        $data['use_integral'] = (int)$data['use_integral'];
        if (empty($data['use_integral'])) {
            $this->tuError('积分抵扣不能为空');
        }

        $data['limit_num']=htmlspecialchars($data['limit_num']);
        $data['exchange_num']=htmlspecialchars($data['exchange_num']);
        //商城检测积分合法性结束
        $data['instructions'] = SecurityEditorHtml($data['instructions']);
        if (empty($data['instructions'])) {
            $this->tuError('购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('商品详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        }
        $data['end_date'] = htmlspecialchars($data['end_date']) ;
        if (empty($data['end_date'])) {
            $this->tuError('过期时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('过期时间格式不正确');
        }
        $data['is_vs1'] = (int)$data['is_vs1'];
        $data['is_vs2'] = (int)$data['is_vs2'];
        $data['is_vs3'] = (int)$data['is_vs3'];
        $data['is_vs4'] = (int)$data['is_vs4'];
        $data['is_vs5'] = (int)$data['is_vs5'];
        $data['is_vs6'] = (int)$data['is_vs6'];
        $data['is_vs7'] = (int)$data['is_vs7'];
        $data['is_vs8'] = (int)$data['is_vs8'];
        $data['is_vs9'] = (int)$data['is_vs9'];

        $data['select1'] = (int)$data['select1'];
        $data['select2'] = (int)$data['select2'];
        $data['select3'] = (int)$data['select3'];
        $data['select4'] = (int)$data['select4'];

        $data['profit_enable'] = (int)$data['profit_enable'];
        $data['profit_rate1'] = (int)$data['profit_rate1'];
        $data['profit_rate2'] = (int)$data['profit_rate2'];
        $data['profit_rate3'] = (int)$data['profit_rate3'];

        if ($res = D('Integralgoodslist')->where(array('title' => $data['title'], 'details' => $data['details'], 'end_date' => $data['end_date']))->find()) {
            $this->tuError('请勿重复添加商品');
        }

        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['sold_num'] = 0;
        $data['view'] = 0;
        $data['is_mall'] = 1;
        $data['audit'] = 1;
        return $data;
    }

    //付费推广
    public function goods_top($goods_id = 0,$check_price,$check_price_money=0){
        if(IS_AJAX){
            $obj = D('Integralgoodslist');
            $goods_id = I('goods_id', 0, 'trim,intval');
            if(!($detail = $obj->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品ID【'.$goods_id.'】不存在'));
            }
            if($detail['audit'] !=1){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品未通过审核，不能开启竞价'));
            }
            $check_price = I('check_price');
            $check_price_money = I('check_price_money');
            $end_time = strtotime(I('end_time'));
            // print_r(strtotime($end_time));die;
            // $this->(array('status' => 'error', 'msg' => strtotime($eajaxReturnnd_time)));
            $status = M('Jingjia')->where(['id'=>1])->find();
            if(!$check_price){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '暂未出价'));
            }
            // if(!$end_time){
            //     $this->ajaxReturn(array('status' => 'error', 'msg' => '必须选择置顶时间'));
            // }
            if($status['status'] ==1){
                $this->ajaxReturn(array('status'=>'error','msg'=>'竞价未开启'));
            }
            if(($status['min_price'])>$check_price){
                $this->ajaxReturn(array('status'=>'error','msg'=>'最低竞价金额￥'.$status['min_price']));
            }

            if(false == $obj->top_time($goods_id,$check_price,$check_price_money,$end_time)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => $obj->getError()));
            }else{
                $this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', U('integralmall/goodsindex')));
            }
        }
    }

    //取消竞价
    public function update_top($goods_id)
    {
        if(!($goods = D('Integralgoodslist')->find($goods_id))){
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
        if(!false == D('Integralgoodslist')->where(['goods_id'])->save(array('check_price'=>0,'is_tui'=>0))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }

    //竞价信息列表
    public function goodsjingjia()
    {
        $Goods = D('IntegralShopJingJiaLog');
        import('ORG.Util.Page');
        $map = array('shop_id' =>$this->shop_id);
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach($list as $k => $val){
            $goods = D('Integralgoodslist')->where(['goods_id'=>$val['goods_id']])->find();
            $list[$k]['title'] = $goods['title'];
            $list[$k]['photo'] = $goods['photo'];
            $list[$k]['mall_price'] = $goods['mall_price'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //竞价列表删除
    public function jingjiadel($bid_id)
    {
        $bid_id = (int) $bid_id;
        $obj = D('IntegralShopJingJiaLog');
        if(empty($bid_id)){
            $this->tuError('该信息不存在');
        }
        if(!($detail = $obj->find($bid_id))){
            $this->tuError('该商品信息不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('非法操作');
        }
        $obj->where(['bid_id'=>$bid_id])->delete();
        $this->tuSuccess('删除成功', U('integralmall/goodsjingjia'));
    }


}