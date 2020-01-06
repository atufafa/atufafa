<?php
class AgentAction extends CommonAction{
	//代理管理--基本管理
	public function about(){
		$user_id=$this->uid;
		$obj=D('UsersAgentApply')->where(array('user_id'=>$user_id))->find();
		//var_dump($obj);
		$this->assign('list',$obj);
		$this->display();
	}


}
