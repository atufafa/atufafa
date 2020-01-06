<?php
class DecorateAction extends CommonAction{
    private $create=array('title','shop_id','type','money','region','city_id','photo','details','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','is_vs7','is_vs8','is_vs9','is_vs10','is_vs11','create_time','create_ip');
    private $create_shop=array('shop_id','audit','create_time','address','brief','tel','business','range','price','scale','service','shop_name','type','addr','money','term','establish','office','management','inspection','register','representative','photo','lng','lat');
    public function _initialize(){
        parent::_initialize();
        if($this->_CONFIG['operation']['decorate'] == 0){
            $this->error('此功能已关闭');die;
        }
    }

    //商家简介
    public function shopbrief(){
        if($this->isPost()) {
            $data = $this->createChck();
            $obj = D('DecorateShop');
            $shop=$obj->where(['shop_id'=>$this->shop_id])->find();
            if(empty($shop)){echo 1;
                if($shop_id = $obj->add($data)) {
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('DecorateQualifications')->upload($data['shop_id'], $photos);
                    }
                    $honors = $this->_post('honor',false);
                    if (!empty($honors)) {
                        D('DecorateHonor')->upload($data['shop_id'], $honors);
                    }
                    $this->tuSuccess('添加成功', U('decorate/shopbrief'));
                }
                $this->tuError('操作失败');
            }else{
                $data = $this->createChck();
                $obj = D('DecorateShop');echo 2;
                if (false !== $obj->where(['shop_id'=>$data['shop_id']])->save($data)) {
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('DecorateQualifications')->upload($data['shop_id'], $photos);
                    }
                    $honors = $this->_post('honor',false);
                    if (!empty($honors)) {
                        D('DecorateHonor')->upload($data['shop_id'], $honors);
                    }
                    $this->tuSuccess('操作成功', U('decorate/shopbrief'));
                }
                $this->tuError('操作失败');
            }

        }else{
            $detail=D('DecorateShop')->where(['shop_id'=>$this->shop_id])->find();
            $this->assign('photos', D('DecorateQualifications')->getPics($this->shop_id));
            $this->assign('honors', D('DecorateHonor')->getPics($this->shop_id));
            $this->assign('detail',$detail);
            $this->display();
        }

    }
    //验证
    public function createChck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_shop);
        $data['shop_id'] = $this->shop_id;
        $data['brief'] = htmlspecialchars($data['brief']);
        if(empty($data['brief'])){
            $this->tuError('发展历程不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        if(empty( $data['tel'])){
            $this->tuError('联系电话不能为空');
        }
        $data['business'] = htmlspecialchars($data['business']);
        if(empty( $data['business'])){
            $this->tuError('营业时间不能为空');
        }
        $data['range'] = htmlspecialchars($data['range']);
        if(empty( $data['range'])){
            $this->tuError('接单范围不能为空');
        }
        $data['price'] = htmlspecialchars($data['price']);
        if(empty( $data['price'])){
            $this->tuError('承接价位不能为空');
        }
        $data['scale'] = htmlspecialchars($data['scale']);
        if(empty($data['scale'])){
            $this->tuError('公司规模不能为空');
        }
        $data['service'] = htmlspecialchars($data['service']);

        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if(empty($data['shop_name'])){
            $this->tuError('公司名称不能为空');
        }
        $data['type'] = htmlspecialchars($data['type']);
        if(empty($data['type'])){
            $this->tuError('企业类型不能为空');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if(empty($data['addr'])){
            $this->tuError('注册地址不能为空');
        }
        $data['money'] = htmlspecialchars($data['money']);
        if(empty($data['money'])){
            $this->tuError('注册资金不能为空');
        }
        $data['term'] = htmlspecialchars($data['term']);
        if(empty($data['term'])){
            $this->tuError('营业期限不能为空');
        }
        $data['establish'] = htmlspecialchars($data['establish']);
        if(empty($data['establish'])){
            $this->tuError('成立日期不能为空');
        }
        $data['office'] = htmlspecialchars($data['office']);
        if(empty($data['office'])){
            $this->tuError('登记机关不能为空');
        }
        $data['management'] = htmlspecialchars($data['management']);
        if(empty( $data['management'])){
            $this->tuError('经营范围不能为空');
        }
        $data['inspection'] = htmlspecialchars($data['inspection']);
        if(empty($data['inspection'])){
            $this->tuError('年检时间不能为空');
        }
        $data['register'] = htmlspecialchars($data['register']);
        if(empty( $data['register'])){
            $this->tuError('注册号不能为空');
        }

        $data['representative'] = htmlspecialchars($data['representative']);
        if(empty( $data['representative'])){
            $this->tuError('法人代表不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuError('营业执照不能为空');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['address'] = htmlspecialchars($data['address']);
        if(empty($data['address'])){
            $this->tuError('公司所在地址不能为空');
        }
        $data['lng'] = htmlspecialchars($data['lng']);
        if(empty($data['lng'])){
            $this->tuError('经度不能为空');
        }
        $data['lat'] = htmlspecialchars($data['lat']);
        if(empty($data['lat'])){
            $this->tuError('纬度不能为空');
        }
        $data['audit']=0;
        $data['create_time']=NOW_TIME;
       return $data;
    }

    //显示信息页
    public function index(){
        $shop = D('DecorateShop')->where(['shop_id'=>$this->shop_id])->find();
        if(empty($shop)){
            $this->error('您未完善公司资料，请填写完',U('decorate/shopbrief'));die;

        }
        if($shop['audit']==0){
            $this->error('您的资料正在审核中，请审核通过后提交',U('decorate/shopbrief'));die;
        }
        if($shop['audit']==3){
            $this->error('您的资料已被驳回，请修改完后提交审核',U('decorate/shopbrief'));die;
        }

        $Goods = D('Decorate');
        import('ORG.Util.Page');
        $map = array('shop_id' =>$this->shop_id);

        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //创建页
    public function create(){
        if ($this->isPost()){
            $data = $this->createCheck();
            $obj = D('Decorate');
            if($goods_id = $obj->add($data)) {
                $photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Decoratephoto')->upload($goods_id, $photos);
                }
                $this->tuSuccess('添加成功', U('decorate/index'));
            }
            $this->tuError('操作失败');
        }
        $this->display();
    }

    //创建--编辑验证
    public function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('公司名称不能为空');
        }
        $shop=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
        $data['city_id'] = $shop['city_id'];
        $data['shop_id'] = $this->shop_id;
        $data['cate_id'] = $shop['cate_id'];
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传公司形象图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('公司形象图格式不正确');
        }

        if (empty($data['details'])) {
            $this->tuError('商品详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        }

        $data['is_vs1'] = (int) $data['is_vs1'];
        $data['is_vs2'] = (int) $data['is_vs2'];
        $data['is_vs3'] = (int) $data['is_vs3'];
        $data['is_vs4'] = (int) $data['is_vs4'];
        $data['is_vs5'] = (int) $data['is_vs5'];
        $data['is_vs6'] = (int) $data['is_vs6'];
        $data['is_vs7'] = (int) $data['is_vs7'];
        $data['is_vs8'] = (int) $data['is_vs8'];
        $data['is_vs9'] = (int) $data['is_vs9'];
        $data['is_vs10'] = (int) $data['is_vs10'];
        $data['is_vs11'] = (int) $data['is_vs11'];

        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['audit'] = 1;
        return $data;
    }

    //编辑
    public function edit($id)
    {

        $obj = D('Decorate');
        if (!$detail = $obj->find($id)) {
            $this->error('请选择要编辑的商品');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要试图越权操作其他人的内容');
        }
        if ($this->isPost()) {
            $data = $this->createCheck();
            $data['id'] = $id;
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
                    D('Goodsphoto')->upload($goods_id, $photos);
                }
                $this->tuSuccess('操作成功', U('decorate/index'));
            }
            $this->tuError('操作失败');
        } else {

            $goodsInfo = D('Decorate')->where('id=' . I('GET.id', 0))->find();
            $this->assign('goodsInfo', $goodsInfo);
            $this->assign('detail', $obj->_format($detail));
            $this->assign('photos', D('Decoratephoto')->getPics($id));
            $this->display();
        }

    }

    //置顶
    public function goods_top($goods_id = 0,$check_price,$check_price_money=0){
        if(IS_AJAX){
            $obj = D('Decorate');
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
                $this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', U('decorate/index')));
            }
        }
    }

    //取消竞价
    public function update_top($id)
    {
        if(!($goods = D('Decorate')->find($id))){
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
        if(!false == D('Decorate')->where(['id'=>$id])->save(array('check_price'=>0,'is_tui'=>0))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }


    //删除
    public function del($id){
        $id = (int) $id;
        $obj = D('Decorate');
        if(empty($id)){
            $this->tuError('该信息不存在');
        }
        if(!($detail = D('Decorate')->find($id))){
            $this->tuError('该信息不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('非法操作');
        }
        $obj->where(['id'=>$id])->delete();
        $this->tuSuccess('删除成功', U('decorate/index'));
    }

    //预约列表
    public function yuyue(){
        $Usermoneylogs = D('Lifereserve');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('sell_user_id'=>$this->shop_id,'type'=>3,'is_pay'=>1,'close'=>0);

        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_id = array();
        foreach ($list as $val){
            $user_id[]=$val['user_id'];
        }

        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //竞价信息
    public function jingjia(){
        $Goods = D('DecorateJinjiaLog');
        import('ORG.Util.Page');
        $map = array('shop_id' =>$this->shop_id);
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach($list as $k => $val){
            $goods = D('Decorate')->where(['id'=>$val['goods_id']])->find();
            $list[$k]['title'] = $goods['title'];
            $list[$k]['photo'] = $goods['photo'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }

    //竞价列表删除
    public function jingjiadel($bid_id)
    {
        $bid_id = (int) $bid_id;
        $obj = D('DecorateJinjiaLog');
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
        $this->tuSuccess('删除成功', U('decorate/jingjia'));
    }

    //点评
    public function dianping(){
        $Shopdianping = D('DecorateDianping');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id);
        $count = $Shopdianping->where($map)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $Shopdianping->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = $dianping_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $user_ids[$val['user_id']] = $val['user_id'];

        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //成交订单
    public function order()
    {
        $obj = D('Liferebate');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('shop_id'=>$this->shop_id,'type_id'=>3);
        $count = $obj->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $val){
            $user_id=$val['user_id'];
        }
        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

        $this->display();

    }

    //审核
    public function state($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Liferebate');
            if($obj->save(array('id' => $id,'shop_state'=>1))){
                $this->tuSuccess('审核成功', U('decorate/order'));
            }
            $this->tuError('审核失败');

        }
    }

    //拒绝
    public function reason($id){
        $obj = D('Liferebate');
        if (is_numeric($id) && ($id = (int) $id)) {
            if ($this->isPost()) {
                $reason = htmlspecialchars($this->_param('reply'));
                if(!$reason){
                    $this->tuError('拒绝理由不能为空');
                }
                if($obj->where(['id'=>$id])->save(['shop_reason'=>$reason,'shop_state'=>2])){
                    $this->tuSuccess('操作成功', U('discount/order'));
                }
                    $this->tuError('操作失败');
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }

    //返利日志
    public function rebatelogs(){
        $obj = D('DecorateShopRebate');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('shop_id'=>$this->shop_id);
        $count = $obj->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

        $this->display();
    }


}