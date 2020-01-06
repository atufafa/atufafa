<?php
class FarmAction extends CommonAction
{

    public function index() {
        $status = (int) $this->_param('status');
        $this->assign('status', $status);
        $this->display();
    }

    public function loaddata() {
        $obj = D('FarmOrder');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'shop_id'=>$this->shop_id);
        $status = (int) $this->_param('status');
        if ($status == 0 || empty($status)) {
            $map['order_status'] = 0;
        }elseif ($status == 1) {
            $map['order_status'] = 1;
        }elseif ($status == 2) {
            $map['order_status'] = 2;
        }elseif ($status == 3) {
            $map['order_status'] = 3;
        }elseif ($status == 4) {
            $map['order_status'] = 4;
        }elseif ($status == 8) {
            $map['order_status'] = 8;
        }elseif($status == 999){
            $map = array('closed' => 0,'shop_id'=>$this->shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $val){
            $pid=$val['pid'];
            $farm_id=$val['farm_id'];
        }
        $this->assign('farm', D('Farm')->itemsByIds($farm_id));
        $this->assign('farmpackage', D('FarmPackage')->itemsByIds($pid));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

}