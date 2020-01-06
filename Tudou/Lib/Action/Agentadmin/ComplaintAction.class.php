<?php
class ComplaintAction extends CommonAction{
	//投诉商家信息
	public function shopcomplaint(){
		$user_id=$this->uid;
		$ele=D('Eleordercomplaintsmerchant');
	
		import('ORG.Util.Page');
		$map = array('status' => 1);
		$count = $ele->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();

        $list = $ele->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->display();
	}


}

