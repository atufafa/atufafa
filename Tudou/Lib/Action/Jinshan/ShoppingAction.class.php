<?php
class ShoppingAction extends CommonAction{
    //显示
    public function index(){
        $Coupon = D('ShoppingEleStoreMarket');
        import('ORG.Util.Page');
        if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = $audit === 1 ? 1 : 0;
            $this->assign('audit', $audit);
        }
        $count = $Coupon->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Coupon->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //审核
    public function audit($id=0){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('ShoppingEleStoreMarket');
            $obj->save(array('id' => $id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('shopping/index'));
        } else {
            $coupon_id = $this->_post('id', false);
            if (is_array($coupon_id)) {
                $obj = D('ShoppingEleStoreMarket');
                foreach ($coupon_id as $ids) {
                    $obj->save(array('id' => $ids, 'audit' => 1));
                }
                $this->tuSuccess('审核成功', U('shopping/index'));
            }
            $this->tuError('请选择要审核的信息');
        }
    }

    //删除
    public function delete($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('ShoppingEleStoreMarket');
            $daoqi=$obj->where(['id'=>$id])->find();
            if($daoqi['end_time']>time()){
                $this->tuError('当前免费购物卷未过期，不能删除');
            }
            $obj->delete(array('id' => $id));
            $this->tuSuccess('删除成功', U('shopping/index'));
        }
            $this->tuError('请选择要删除的信息');
    }

    //领取
    public function collarindex(){
        $obj = D('CollarEleStoreMarket');
        import('ORG.Util.Page');
        if ($is_use = (int) $this->_param('is_use')) {
            $map['is_use'] = $is_use === 1 ? 1 : 0;
            $this->assign('is_use', $is_use);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
        }
        if ($user_ids) {
            $this->assign('user', D('Users')->itemsByIds($user_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function del($id=0){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('CollarEleStoreMarket');
            $daoqi=$obj->where(['id'=>$id])->find();
            $end=D('ShoppingEleStoreMarket')->where(['id'=>$daoqi['shopping_id']])->find();
            if($end['end_time']>time() || $daoqi['is_use']==0){
                $this->tuError('当前免费购物卷未过期或用户还未使用该免费购物卷，不能删除');
            }
            $obj->delete(array('id' => $id));
            $this->tuSuccess('删除成功', U('shopping/collarindex'));
        }
            $this->tuError('请选择要删除的优惠券');
    }

}