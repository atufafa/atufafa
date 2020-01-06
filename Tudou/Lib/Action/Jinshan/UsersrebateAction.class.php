<?php		
class UsersrebateAction extends CommonAction{
	//返利列表
	public function index(){
		 $obj = D('Liferebate');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('close'=>0,'type_id'=>array('IN','1,2'));
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
        
        $this->assign('user',D('Users')->select());
        $this->assign('type',D('Lifetype')->select());
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

		$this->display();
	}

	  //删除
    public function delete($id=0){
    	 if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Liferebate');
            $obj->save(array('id' => $id,'close'=>1));
            $this->tuSuccess('删除成功', U('usersrebate/index'));
        }
    }

    //审核
    public function shenhe($id){
    	 if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Liferebate');
            $obj->save(array('id' => $id,'state'=>1));
            $detail=$obj->where(['id'=>$id])->find();
            $user=D('Lifereserve')->where(['id'=>$detail['life_id'],'user_id'=>$detail['user_id'],'type'=>$detail['type_id']])->save(['fl'=>3]);
            if(false !== $user){
                $this->tuSuccess('审核成功', U('usersrebate/index'));
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
                    $this->tuSuccess('操作成功', U('usersrebate/index'));
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
                    $this->tuSuccess('返利成功', U('usersrebate/index'));
                }else{
                    $this->tuError('该信息有问题，请核实后操作');
                }
            }else{
                $this->assign('id',$id);
                $this->display();
            }
        }
    }

    //商家返利列表
    public  function shopindex()
    {
        $fl = D('Rebatelog');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('closed'=>0);
        
        $count = $fl->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $fl->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        $user=D('Users')->select();
        $this->assign('user',$user);
        $this->assign('list', $list);
        $this->assign('page', $show);

       $this->display();
    }

    //删除
    public function del($log_id){
        
         if (is_numeric($log_id) && ($log_id = (int) $log_id)) {
            $obj = D('Rebatelog');
            $obj->save(array('log_id' => $log_id,'closed'=>1));
            $this->tuSuccess('删除成功', U('usersrebate/shopindex'));
        }

    }
}