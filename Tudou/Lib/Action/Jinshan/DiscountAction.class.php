<?php
class DiscountAction extends CommonAction {
    //购买返利劵用户
    public function buy(){

         $obj = D('DecorateRebate');
        import('ORG.Util.Page');
        // 导入分页类
        $count = $obj->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $val){
            $user_id=$val['user_id'];
        }
        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

        $this->display();
    }

    //删除购买返利用户信息
    public function delete($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj=D('DecorateRebate');
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('discount/buy'));
        }
    }
    

    //申请返利用户
    public function index(){
        $obj = D('Liferebate');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('close'=>0,'type_id'=>3);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
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
    public function shenhe($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Liferebate');
            $obj->save(array('id' => $id,'state'=>1));
            $detail=$obj->where(['id'=>$id])->find();
            $user=D('Lifereserve')->where(['id'=>$detail['life_id'],'user_id'=>$detail['user_id'],'type'=>$detail['type_id']])->save(['fl'=>3]);
            if(false !== $user){
                $this->tuSuccess('审核成功', U('discount/index'));
            }else{
                $this->tuError('该信息有问题，请核实后操作');
            }

        }
    }


    //拒绝返利
    public function refuse($id){
        $obj = D('Liferebate');
        if (is_numeric($id) && ($id = (int) $id)) {
            if ($this->isPost()) {
                $reason = htmlspecialchars($this->_param('reason'));
                if(!$reason){
                    $this->tuError('拒绝理由不能为空');
                }
                $obj->where(['id'=>$id])->save(['reason'=>$reason,'state'=>2]);
                $detail=$obj->where(['id'=>$id])->find();
                $user=D('Lifereserve')->where(['id'=>$detail['life_id'],'user_id'=>$detail['user_id'],'type'=>$detail['type_id']])->save(['fl'=>4,'refuse'=>$reason]);
                if(false !== $user){
                    $this->tuSuccess('操作成功', U('discount/index'));
                }else{
                    $this->tuError('该信息有问题，请核实后操作');
                }
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }


    //确认返利
    public  function confirm($id)
    {
        $obj = D('Liferebate');
        if (is_numeric($id) && ($id = (int) $id)) {
            if ($this->isPost()) {
                $reason = htmlspecialchars($this->_param('reason'));
                if(!$reason){
                    $this->tuError('返利金额不能为空');
                }
                $obj->where(['id'=>$id])->save(['flmonry'=>$reason,'state'=>3,'confirm'=>1]);
                $detail=$obj->where(['id'=>$id])->find();
                $user=D('Lifereserve')->where(['id'=>$detail['life_id'],'user_id'=>$detail['user_id'],'type'=>$detail['type_id']])->save(['fl'=>3]);
                $users=D('Users')->where(['user_id'=>$detail['user_id']])->save(['rebate'=>0]);
                D('Users')->addMoney($detail['user_id'],$reason,'获得返利奖励'.$reason.'元');
                if(false !== $user && false !== $users){
                    $this->tuSuccess('返利成功', U('discount/index'));
                }else{
                    $this->tuError('该信息有问题，请核实后操作');
                }
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }

    //商家返利日志
    public function rebate(){
        $obj = D('DecorateShopRebate');
        import('ORG.Util.Page');
        // 导入分页类
        $count = $obj->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

        $this->display();
    }

    //删除
    public function del($log_id){
        if (is_numeric($log_id) && ($log_id = (int) $log_id)) {
            $obj=D('DecorateShopRebate');
            $obj->delete($log_id);
            $this->tuSuccess('删除成功', U('discount/rebate'));
        }
    }


}