<?php
class DivideAction extends CommonAction{
    //分成日志
    public function index(){
        $Shop = D('DeliveryDivide');
        import('ORG.Util.Page');
        $map = array('colse' => 0,'sq_id'=>$this->uid);
        $count = $Shop->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $Shop->order(array('id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


}