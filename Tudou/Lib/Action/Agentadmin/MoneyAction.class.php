<?php
class MoneyAction extends CommonAction{
	//代理资金管理
	public function finance(){
		$user_id=$this->uid;
		$counts=array();
		$counts['money'] = (int) D('Cityagentcash')->where(array('user_id' => $this->$user_id))->sum('money');
		$this->assign('counts',$counts);
		$this->display();
	}

	//资金记录
	public function index(){
		$user_id=$this->uid;
		$obj=D('Cityagentcash');
		//$money=D('Usermoneylogs');
		import('ORG.Util.Page');
		$map=array('status'=>1,'user_id'=>$user_id);
		//$maps=array('user_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
		$this->assign('list',$list);
		$this->display();
	}

	//余额日志
	public function balance(){

		$user_id=$this->uid;
		$obj=D('Usermoneylogs');
		import('ORG.Util.Page');
		$maps=array('user_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($maps)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
		$this->assign('list',$list);
		$this->display();


	}

	//提现申请
	public function tixianlog(){
		$user_id=$this->uid;
		$obj=D('Userscash');
		//var_dump($obj);die;
		import('ORG.Util.Page');
		$map=array('status'=>1,'user_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
  
        $this->assign('page', $show);
		$this->assign('list',$list);
        $this->display();
	}

	//提现
	public function tixian(){


		$this->display();
	}

	//购买代理等级
	public function grade(){

		$Shopgrade = D('Cityagent');
        import('ORG.Util.Page');
        $map = array('closed'=>0);
        $count = $Shopgrade->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgrade->where($map)->order(array('orderby' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		//var_dump($list);
        $this->assign('list', $list);
        $this->assign('page', $show);

		$this->display();
	}

	//购买等级权限
	public function pay_permissionss(){
        $agent_id = (int) $this->_param('agent_id');
		$apply_id = (int) $this->_param('apply_id');
        if (!$obj = D('Cityorder')->shop_pay_gradess($agent_id,$apply_id)) {
			$this->tuError(D('Cityorder')->getError());	
        }else{
			 $this->tuSuccess('恭喜您购买等级成功', U('money/grade'));
		}
        $this->display();
    }

}
