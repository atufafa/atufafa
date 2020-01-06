<?php
class MoneyAction extends CommonAction{
	//资金管理
	public function finance(){
		$user_id=$this->uid;
		$fl = D('Rebatelog');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('user_id'=>$user_id,'shopclosed'=>0);
        
        $count = $fl->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $fl->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        
        $this->assign('list', $list);
        $this->assign('page', $show);


		$this->display();
	}

	//资金记录
	public function index(){
		$user_id=$this->uid;
		$Usermoneylogs = D('Usermoneylogs');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('user_id'=>$user_id);
        
        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        
        $this->assign('list', $list);
        $this->assign('page', $show);

		$this->display();
	}

	//提现记录
	public function tixianlog(){
		$user_id=$this->uid;
		$obj=D('Userscash');
		import('ORG.Util.Page');
		$map=array('user_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
  
        $this->assign('page', $show);
		$this->assign('list',$list);

        $this->display();

	}

	//申请提现
	public function tixian(){

		$this->display();
	}

	//删除
    public function delete($log_id){
        $log_id = (int) $log_id;
        
		if(D('Rebatelog')->save(array('log_id' => $log_id, 'shopclosed' => 1))){
			$this->tuSuccess('删除成功', U('money/finance'));
		}else{
			$this->tuError('删除失败');
		}
    }
	

}

