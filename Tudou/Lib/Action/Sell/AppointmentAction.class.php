<?php
class AppointmentAction extends CommonAction{
	//申请信息
	public function index(){
		$user_id=$this->uid;
		
		$Usermoneylogs = D('Lifereserve');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('sell_user_id'=>$user_id,'is_pay'=>1,'close'=>0);
        
        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_id = array();
        foreach ($list as $val){
            $user_id[]=$val['user_id'];
        }

        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('list', $list);
        $this->assign('page', $show);

		$this->display();
	}

}