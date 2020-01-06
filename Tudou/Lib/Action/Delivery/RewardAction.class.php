<?php
class RewardAction extends CommonAction
{
    public function _initialize() {
        parent::_initialize();
    }
    public function index(){
        $config = D('Setting')->fetchAll();
        $this->assign('CONFIG',$config);
        $this->display();
    }

    //日志
    public function rewardlog(){
        $this->display();
    }

    public function load(){
        $obj=D('Deliverylog');
        $user=$this->uid;
        import('ORG.Util.Page');
        $map=array('user_id'=>$user);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order('create_time','desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display( );
    }
}