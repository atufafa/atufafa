<?php
class ApplyAction extends CommonAction {

	//申请权限
	public function apply(){


		$this->display();
	}



	//申请列表
	public function index(){
		$obj=D('Apply');

		 import('ORG.Util.Page');
		$map = array('closed' => 0);
		if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['a_id|name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);

		$this->display();
	}

	//审核
	public function sh($a_id,$p=0){

	$obj = D('Apply');
	        if (!($detail = $obj->find($a_id))) {
	            $this->error('请选择要审核的商家');
	        }
	        $data = array('state' =>1, 'a_id' => $a_id);

	        $obj->save($data);
	        $this->tuSuccess('操作成功', U('apply/index',array('p'=>$p)));


		}

}
