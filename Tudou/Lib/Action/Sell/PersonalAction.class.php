<?php
class PersonalAction extends CommonAction{
	//申请信息
	public function about(){
		$user_id=$this->uid;
		$list=D('Lifesauthentication')->where(array('user_id'=>$user_id))->find();
		$v=D('Lifesvehicle')->where(array('user_id'=>$user_id))->find();
		if(!empty($list)){
			$this->assign('list',$list);
		}elseif(!empty($v)){
			$this->assign('list',$v);
		}

		$this->display();
	}

}
