<?php
class IntegralmallAction extends CommonAction{
    //显示列表
    public function index(){
        $obj = D('Shopintegral');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('close'=>0);
        $count = $obj->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 15);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
     $shop_ids = array();
        foreach ($list as $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);
        // 赋值分页输出
        $this->display();
        // 输出模板
    }

    //审核
    public function shenhe($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Shopintegral');
            $obj->save(array('id' => $id, 'shenhe' => 1));
            $this->tuSuccess('审核成功', U('integralmall/index'));
        }
            $this->tuError('请选择要审核入驻商家');
    }

    //删除
    public function delete($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Shopintegral');
            $obj->save(array('id' => $id, 'close' => 1));
            $this->tuSuccess('删除成功', U('integralmall/index'));
        }
        $this->tuError('请选择要审核入驻商家');

    }

    //申请奖励
    public function rewardlist(){
        $obj = D('Shoprewardlogs');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('close'=>0);
        $count = $obj->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 15);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = array();
        foreach ($list as $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);
        // 赋值分页输出
        $this->display();
    }

    //审核
    public function shenhes($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Shoprewardlogs');
            $obj->save(array('id' => $id, 'examine' => 1));
            $this->tuSuccess('审核成功', U('integralmall/rewardlist'));
        }
        $this->tuError('请选择要审核入驻商家');

    }

    //拒绝
    public function refuse($id){
        $obj=D('Shoprewardlogs');
        if($this->ispost()){
            $ju=I('post.jutitle');
            if(empty($ju)){
                $this->tuError('拒绝理由不能为空');
            }
            $row=$obj->save(array('id'=>$id,'examine'=>2,'jutitle'=>$ju));
            if($row>0){
                $this->tuSuccess('操作成功',U('integralmall/rewardlist'));
            }else{
                $this->tuError('操作失败');
            }

        }else{
            $des=$obj->where(array('id'=>$id))->find();
            $this->assign('des',$des);
            $this->display();
        }
    }

    //发放奖励
    public function grant($id){
        $obj=D('Shoprewardlogs');

        if($this->ispost()){
            $money=I('post.fa_money');
            if(empty($money)){
                $this->tuError('请选择');
            }
            $jiangli=$obj->where(array('id'=>$id))->find();
            $shop=D('Shop')->where(array('shop_id'=>$jiangli['shop_id']))->find();
            $intor='本月销售额达标，获得奖励'.$money.'元。';
            $fafan=D('Users')->addGold($shop['user_id'],$money,'积分商城奖励');
            if($fafan>0){
                $obj->save(array('id'=>$id,'fa_money'=>$money,'intor'=>$intor));
                $this->tuSuccess('发放奖励成功，向商户资金充值【'.$money.'】元',U('integralmall/rewardlist'));
            }
            $this->tuError('操作失败');
        }else{
            $des=$obj->where(array('id'=>$id))->find();
            $config = D('Setting')->fetchAll();
            $this->assign('CONFIG',$config);
            $this->assign('des',$des);
            $this->display();
        }
    }


}