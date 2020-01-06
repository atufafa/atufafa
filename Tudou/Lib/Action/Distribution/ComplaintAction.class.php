<?php
class ComplaintAction extends CommonAction{
	//投诉列表
	public function index(){
		$users_id=$this->uid;
		//var_dump($users_id);
		$obj=D('Deliverycomplaintsrider');

		import('ORG.Util.Page');
		$user=D('Delivery')->where(array('recommend'=>$users_id))->select();
		
		//var_dump($user);
		$lists=array();
		foreach ($user as $key => $value) {
			$lists[]=$value['user_id'];
		}
		//var_dump($lists);
		$map=array('user_id'=>array('IN',$lists));
		$count = $obj->where($map)->count();
		
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

  		//var_dump($list);
        $this->assign('page', $show);
		$this->assign('list',$list);

		$this->display();
	}

}
