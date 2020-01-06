<?php
class MoneyAction extends CommonAction{
	//资金管理
	public function finance(){



		$this->display();
	}

	//资金记录
	public function index(){
		$user_id=$this->uid;
		$Usermoneylogs = D('Usermoneylogs');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('user_id'=>$user_id);
        
        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        
        $this->assign('list', $list);
        $this->assign('page', $show);




		$this->display();
	}

	//提现记录
	public function tixianlog(){
		$user_id=$this->uid;
		$obj=D('Userscash');
		import('ORG.Util.Page');
		$map=array('user_id'=>$user_id);
		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
  
        $this->assign('page', $show);
		$this->assign('list',$list);

        $this->display();

	}

	//申请提现
	public function tixian(){

		$this->display();
	}

	//购买管理等级
	public function grade(){

 		$Shopgrade = D('Deliveryadmin');
        import('ORG.Util.Page');
        $map = array('closed'=>0);
        $count = $Shopgrade->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgrade->where($map)->order(array('dj_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->display();
	}


	//购买等级权限
	public function pay_permission(){
        $dj_id = (int) $this->_param('dj_id');
		$user_id = (int) $this->_param('user_id');
		
        if (!$obj = D('Deliverypurchase')->shop_pay_grades($dj_id,$user_id)) {
			$this->tuError(D('Deliverypurchase')->getError());	
        }else{
			 $this->tuSuccess('恭喜您购买等级成功', U('money/grade'));
		}
        $this->display();
    }

	

}

