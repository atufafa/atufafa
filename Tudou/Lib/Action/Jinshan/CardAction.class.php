<?php
class CardAction extends CommonAction{

    //显示列表
    public function index(){
        $User = D('Users');
        import('ORG.Util.Page');
        $map = array('u.closed'=>array('IN','0,-1'));
        $map['u.mobile'] = array('neq',"");
        if($account = $this->_param('account','htmlspecialchars')){
            $map['u.account'] = array('LIKE','%'.$account.'%');
            $this->assign('account',$account);
        }

        if($userid = (int) $this->_param('userid')){
            $map['u.user_id']=$userid;
            $this->assign('usreid',$userid);
        }
        
        if($nickname = $this->_param('nickname','htmlspecialchars')){
            $map['u.nickname'] = array('LIKE','%'.$nickname.'%');
            $this->assign('nickname',$nickname);
        }

        if($shopnum = $this->_param('shopnum','htmlspecialchars')){
            $map['u.shopnum'] = array('LIKE','%'.$shopnum.'%');
            $this->assign('shopnum',$shopnum);
        }

        if($rank_id = (int)$this->_param('rank_id')){
            $map['u.rank_id'] = $rank_id;
            $this->assign('rank_id',$rank_id);
        }
        if($ext0 = $this->_param('ext0','htmlspecialchars')){
            $map['u.ext0'] = array('LIKE','%'.$ext0.'%');
            $this->assign('ext0',$ext0);
        }
        $profit_min_rank_id = (int)$this->_CONFIG['profit']['profit_min_rank_id'];
        if ($profit_min_rank_id) {
            $rank = D('Userrank')->find($profit_min_rank_id);
            if ($rank) {
                $map['u.prestige'] = array('EGT', $rank['prestige']);
            }
        }
        $join = ' LEFT JOIN ' . C('DB_PREFIX') . 'users f ON f.user_id = u.fuid1';
        $count = $User->alias('u')->join($join)->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $User->alias('u')->field('u.*, f.user_id AS fuserid, f.account AS fusername')->join($join)->where($map)->order(array('u.user_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $uids = $rank = $level1 = $level2 = $level3 = array();
        foreach($list as $k=>$val){
            $val['reg_ip_area'] = $this->ipToArea($val['reg_ip']);
            $val['last_ip_area']   = $this->ipToArea($val['last_ip']);
            $uids[$val['user_id']] = $val['user_id'];
            $list[$k] = $val;
            $rank[$val['rank_id']] = $val['rank_id'];
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('rank',D('Userrank')->itemsByIds($rank));
        $this->assign('ranks',D('Userrank')->fetchAll());
        $this->display();
    }

    //编辑列表
    public function edit($user_id){
        $obj = D('Users');
        if (is_numeric($user_id) && ($id = (int) $user_id)) {
            if ($this->isPost()) {
                $num = (int) $this->_param('num');
                $num1 = (int) $this->_param('num1');
                $num2 = htmlspecialchars($this->_param('num2'));

                if(false !==  $obj->where(['user_id'=>$user_id])->save(['card_number'=>$num,'frequency'=>$num1,'shopnum'=>$num2])){
                    $this->tuSuccess('操作成功', U('card/index'));
                }else{
                    $this->tuError('该信息有问题，请核实后操作');
                }
            }else{
                $this->assign('details',$obj->find($user_id));
                $this->assign('user_id',$user_id);
                $this->display();
            }
        }
    }
}