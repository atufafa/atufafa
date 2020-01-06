<?php
class TeamAction extends CommonAction{
	//团队管理
	public function index(){
		$user_id=$this->uid;
		$obj=D('UsersAgentApply');
		import('ORG.Util.Page');
		$map=array('user_guide_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('level',D('Cityagent')->select());
        $this->assign('page', $show);
		$this->assign('list',$list);
		$this->display();
	}

	//商家团队列表
	public function shopindex(){
		$user_id=$this->uid;
        import('ORG.Util.Page');
		$obj=D('Shop');
        $map=array('user_guide_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	

        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
		$this->assign('list',$list);
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
		//var_dump($list);
		$this->display();
	}

	//卖房
	public function sellindex(){

		$user_id=$this->uid;
		$obj=D('Lifesauthentication');
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

	//卖车团队列表
    public function sellvehicle(){
        $user_id=$this->uid;
        $obj=M('LifesVehicle');

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

}