<?php
class ReceiveAction extends CommonAction{
    //领取列表
    public function lists(){
        $Coupondownload = D('Usersintegral');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('colse'=>0);
        $count = $Coupondownload->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $Coupondownload->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_id=array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $user_id[$val['user_id']] = $val['user_id'];
            }
            $val['create_ip'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
        }
        if ($user_id) {
            $this->assign('user', D('Users')->itemsByIds($user_id));
        }
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);
        // 赋值分页输出
        $this->display();
        // 输出模板
    }

    //删除
    public function del($id){
        $linqu=D('Usersintegral')->where(array('id'=>$id))->find();
        if($linqu==false){
            $this->tuError('该记录不存在');
        }else{
            $row=D('Usersintegral')->where(array('id'=>$id))->save(array('colse'=>1));
            if(false!=$row){
                $this->tuSuccess('删除成功',U('receive/lists'));
            }else{
                $this->tuError('操作失败');
            }
        }
    }

}