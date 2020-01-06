<?php
class TeamAction extends CommonAction{
	//配送员团队列表
	public function index(){
		$user_id=$this->uid;
		$obj=D('Delivery');
		import('ORG.Util.Page');
		$map=array('recommend'=>$user_id);
		
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        
        $this->assign('page', $show);
		$this->assign('list',$list);

		$this->display();
	}

	//推荐招商团队
	public function recruitindex(){
		$user_id=$this->uid;
		$obj=D('Applicationmanagement');
		import('ORG.Util.Page');
		$map=array('recommend'=>$user_id);
		
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $recruit=D('Deliveryadmin')->select();
     
        $this->assign('recruit',$recruit);
        $this->assign('page', $show);
		$this->assign('list',$list);


		$this->display();
	}

}