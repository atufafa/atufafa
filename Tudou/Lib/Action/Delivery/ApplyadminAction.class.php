<?php
class ApplyadminAction extends CommonAction{
	//显示列表
	public function index(){

    	$delivery_id = $this->user_id;
		
		$deliver = D('Applicationmanagement')->where(array('user_id'=>$delivery_id))->find();

		$this->assign('DELIVERY',$deliver);
		
       $this->display();
	}

}