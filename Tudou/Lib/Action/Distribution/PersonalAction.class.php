<?php
class PersonalAction extends CommonAction{
	//申请信息
	public function about(){
		$user_id=$this->uid;
		$obj=D('Applicationmanagement');
		$list=$obj->where(array('user_id'=>$user_id))->find();

		$this->assign('list',$list);
		$this->display();
	}

}
