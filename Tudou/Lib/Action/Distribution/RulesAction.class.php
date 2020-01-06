<?php
class RulesAction extends CommonAction{
	//惩罚管理
	public function index(){
		$jianli=D('Deliverysuper')->select();
		// $cf=D('Setting')->where(array('k'=>'site'))->select();
		// var_dump($cf);
		$cp=$this->_CONFIG['site']['delivery_xp'];
		$ts=$this->_CONFIG['site']['delivery_tsp'];

		$this->assign('cp',$cp);
		$this->assign('ts',$ts);
		$this->assign('list',$jianli);
		$this->display();
	}

}