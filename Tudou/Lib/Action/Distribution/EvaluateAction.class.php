<?php
class EvaluateAction extends CommonAction{
	//差评管理
	public function index(){
		$user_id=$this->uid;
		$obj=D('DeliveryComment');

		import('ORG.Util.Page');
		$user=D('Delivery')->where(array('recommend'=>$user_id))->select();
		$lists=array();
		foreach ($user as $key => $value) {
			$lists[]=$value['id'];
		}


		$map=array('delivery_id'=>array('IN',$lists),'score'=>1,'d1'=>1,'d2'=>1,'d3'=>1);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
  		
        $this->assign('page', $show);
		$this->assign('list',$list);

		$this->display();
	}

}
