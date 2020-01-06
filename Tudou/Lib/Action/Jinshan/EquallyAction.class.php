<?php
class EquallyAction extends CommonAction{
	//配送管理员分成日志
	public function index(){
        $Shop = D('DeliveryDivide');
        import('ORG.Util.Page');
        $map = array('colse' => 0);
        $count = $Shop->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $Shop->order(array('id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->display();
	}

    public function del($id){
        if($id = (int) $id){
            $obj = D('DeliveryDivide');
            $details = $obj->where(array('id'=>$id))->save(array('colse'=>1));

            if ($details>0) {
                $this->tuSuccess('操作成功', U('equally/index'));
            }
        }else{

            $this->tuError('操作失败');
        }
    }







}
