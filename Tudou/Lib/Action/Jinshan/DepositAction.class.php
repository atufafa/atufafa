<?php
class DepositAction extends CommonAction{
    //列表
    public  function index(){
        $Life = D('Depositmanagement');
        import('ORG.Util.Page');
        $map = array('closed' => 0);

        if($userid = (int) $this->_param('userid')) {
            $map['user_id'] = $userid;
            $this->assign('usreid', $userid);
        }
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Life->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }

        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //扣除惩罚押金
    public function koufei($deposit_id=0)
    {
        if (!($detial = D('Depositmanagement')->find($deposit_id))) {
            $this->tuError('押金记录不存在');
        }
        if ($this->ispost()) {
            $fa=I('post.money');

            if ($detial['nowmoney'] < $fa) {
                $this->tuError('当前押金小于惩罚押金，请提醒该会员充值');
            }


            $xainmoney=D('Depositmanagement')->where(['deposit_id'=>$deposit_id])->find();

            $chengfa = $xainmoney['koumoney'] + $fa;
            $money = $xainmoney['nowmoney'] - $fa;

            $Users = D('Depositmanagement');
            $shop = D('Depositmanagement')->where(array('deposit_id' => $deposit_id))->find();
            $intro = '产生不良记录，平台扣除押金费用' . $fa . '元';
            $Users->yaJin($shop['user_id'], -$fa, $intro);

            if (D('Depositmanagement')->save(array('deposit_id' => $deposit_id, 'koumoney' => $chengfa, 'nowmoney' => $money))) {
                $this->tuSuccess('操作成功', U('deposit/index'));
            } else {
                $this->tuError('操作失败');
            }

        } else {
            $this->assign('deposit_id', $detial['deposit_id']);
            $this->display();
        }
    }
}