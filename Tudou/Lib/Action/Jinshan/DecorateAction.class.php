<?php
class DecorateAction extends CommonAction{
    //审核公司信息
    public function shopdetail(){
        $obj=D('DecorateShop');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $val){
            $shop_ids=$val['shop_id'];
        }
        $this->assign('shop',D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //审核
    public function audit1($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('DecorateShop');
            $obj->save(array('id' => $id,'audit'=>1,'reject'=>null));
            $this->tuSuccess('审核成功', U('decorate/shopdetail'));
        }
    }

    //删除
    public function dels($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('DecorateShop');
            $obj->where(['id'=>$id])->delete();
            $this->tuSuccess('删除成功', U('decorate/shopdetail'));
        }else{
            $this->tuError('请选择要删除的信息ID');
        }
    }

    //驳回
    public function reject($id){
        $obj = D('DecorateShop');
        if (is_numeric($id) && ($id = (int) $id)) {
            if ($this->isPost()) {
                $reason = htmlspecialchars($this->_param('reason'));
                if(!$reason){
                    $this->tuError('拒绝理由不能为空');
                }
                $obj->where(['id'=>$id])->save(['reject'=>$reason,'audit'=>3]);
                $this->tuSuccess('操作成功', U('decorate/shopdetail'));
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }

    //查看详情
    public function edit($id){
        $obj = D('DecorateShop');
        $details=$obj->where(['id'=>$id])->find();
        $this->assign('photos', D('DecorateQualifications')->getPics($details['shop_id']));
        $this->assign('honors', D('DecorateHonor')->getPics($details['shop_id']));
        $this->assign('detail',$details);
        $this->display();
    }


    //家装公司商家
    public function index(){
        $Goods = D('Decorate');
        import('ORG.Util.Page');
        $count = $Goods->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $val){
            $shop_ids=$val['shop_id'];
        }
        $this->assign('shop',D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //审核商家
    public function audit($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Decorate');
            $obj->save(array('id' => $id,'audit'=>1));
            $this->tuSuccess('审核成功', U('decorate/index'));
        }
    }

    //删除商家
    public function delshop($id){
        $id = (int) $id;
        $obj = D('Decorate');
        if(empty($id)){
            $this->tuError('该商品信息不存在');
        }
        if(!($detail = D('Decorate')->find($id))){
            $this->tuError('该商品信息不存在');
        }
        $obj->where(['id'=>$id])->delete();
        $this->tuSuccess('删除成功', U('decorate/index'));
    }

    //预约信息列表
    public function yuyue(){
        $Usermoneylogs = D('Lifereserve');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('type'=>3);
        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_id = $shop_id=array();
        foreach ($list as $val){
            $user_id[]=$val['user_id'];
            $shop_id[]=$val['sell_user_id'];
        }

        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //删除预约信息
    public function del($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Lifereserve');
            $obj->where(['id'=>$id])->delete();
            $this->tuSuccess('删除成功', U('decorate/yuyue'));
        }else{
            $this->tuError('请选择要删除的预约信息ID');
        }
    }

    //竞价信息
    public function jingjia(){
        $Goods = D('Decorate');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->order(array('check_price' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //点评
    public function dianping(){
        $obj = D('DecorateDianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $user = D('Users')->find($user_id);
            $this->assign('nickname', $user['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($shop_ids)) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除评价
    public function deldp($id = 0) {
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('DecorateDianping');
            $obj->where(array('id' => $id))->delete();
            $this->tuSuccess('删除成功', U('decorate/dianping'));
        }
            $this->tuError('请选择要删除的点评');
    }

}