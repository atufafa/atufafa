<?php
class ElerewardAction extends CommonAction{
    //显示列表
    public function index(){
        $obj = D('Elereward');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('closed'=>0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('id  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $value){
            $shop_id['shop_id']=$value['shop_id'];
        }
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //审核
    public function shenhe($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Elereward');
            $obj->save(array('id' => $id,'audit'=>1));
            $this->tuSuccess('审核成功', U('elereward/index'));
        }else{
            $this->tuError('请选择要审核的信息');
        }
    }

    //拒绝
    public function refuse($id){
        $obj = D('Elereward');
        if (is_numeric($id) && ($id = (int) $id)) {
            if ($this->isPost()) {
                $reason = htmlspecialchars($this->_param('reason'));
                if(!$reason){
                    $this->tuError('拒绝理由不能为空');
                }
                $obj->save(array('id' => $id, 'audit' => 2,'refuse'=>$reason));
                $this->tuSuccess('操作成功', U('elereward/index'));
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }

    //删除
    public function delete($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Elereward');
            $obj->save(array('id' => $id,'closed'=>1));
            $this->tuSuccess('删除成功', U('elereward/index'));
        }else{
            $this->tuError('请选择要删除的信息');
        }
    }

    //发放奖励
    public function jiangli($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Elereward')->where(array('id'=>$id))->find();
            $shop=D('Shop')->where(array('shop_id'=>$obj['shop_id']))->find();
            //查询要发放的奖励金额
            $config = D('Setting')->fetchAll();
            $money=$config['ele']['money'];
            if(false !== D('Users')->addGold($shop['user_id'],$money,'销售量达到平台所制定的，获得奖励'.$money.'元')){
                D('Elereward')->where(array('id'=>$id))->save(array('audit'=>3));
                $this->tuSuccess('奖励成功，发放奖励'.$money.'元', U('elereward/index'));
            }
            $this->tuError('操作失败');
        }else{
            $this->tuError('请选择要奖励的信息');
        }
    }
}