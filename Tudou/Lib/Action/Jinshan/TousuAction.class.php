<?php
class TousuAction extends CommonAction{

	//投诉商家
	public function index(){
 	$obj = D('Eleordercomplaintsmerchant');
        import('ORG.Util.Page');
		$map = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
		if ($keyword) {
            $map['oeder_id|user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
         $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
	}

	 public function sj($complaintsmerchant_id,$p = 0){
        $obj = D('Eleordercomplaintsmerchant');
        if (!($detail = $obj->find($complaint_id))) {
            $this->error('请选择要审核的商家');
        }
        $data = array('status' =>1, 'complaintsmerchant_id' => $complaintsmerchant_id);
        // if ($detail['is_biz'] == 0) {
        //     $data['is_biz'] = 1;
        // }
        $obj->save($data);
        $this->tuSuccess('操作成功', U('tousu/index',array('p'=>$p)));
    }
}