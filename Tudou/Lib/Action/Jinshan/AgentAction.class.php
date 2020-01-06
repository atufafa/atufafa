<?php
class AgentAction extends CommonAction{
	public function _initialize(){
        parent::_initialize();
    }
    public function index(){
        $obj = D('UsersAgentApply');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($order_id = (int) $this->_param('order_id')) {
            $map['order_id'] = $order_id;
            $this->assign('order_id', $order_id);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if (isset($_GET['type']) || isset($_POST['type'])) {
            $type = (int) $this->_param('type');
            if ($type != 999) {
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        } else {
            $this->assign('type', 999);
        }
        $count =  $obj ->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list =  $obj ->where($map)->order(array('apply_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $agent_ids = $user_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
			$agent_ids[$val['agent_id']] = $val['agent_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('agents', D('Cityagent')->itemsByIds($agent_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function delete($apply_id = 0){
        if ($apply_id = (int) $apply_id){
            $obj = D('UsersAgentApply');
			if(!$detail = $obj->find($apply_id)){
				$this->tuError('申请订单不存在');
		    }elseif($detail['type'] == 2){
				$this->tuError('余额付款不支持删除');
			}else{
				if($obj->save(array('apply_id' => $apply_id, 'closed' => 1))){
					$this->tuSuccess('取消订单成功', U('agent/index'));
				}else{
					$this->tuError('更新数据库失败');
				}
			}
        } else {
            $this->tuError('请选择要取消的代理申请');
        }
    }
	
	public function audit($apply_id = 0){

        $level = I("get.level");
        if ($apply_id = (int) $apply_id){
            $obj = D('UsersAgentApply');
			if(!$detail = $obj->find($apply_id)){
				$this->tuError('不存在的申请');
		    }elseif($detail['closed'] == 1){
				$this->tuError('该申请已删除');
			}else{

                //审核成功计算提成
                if($obj->save(array('apply_id' => $apply_id, 'audit' => 1))){
                    $this->tuSuccess('审核申请成功', U('agent/index'));
                }else{
                    $this->tuSuccess('审核申请失败');
                }
			}
        } else {
            $this->tuError('请选择要审核的代理申请');
        }
    }

    //管理
     public function login($apply_id){
        $obj = D('UsersAgentApply');
        if (!($detail = $obj->find($apply_id))){
            $this->tuError('请选择要编辑的代理');
        }
        if (empty($detail['user_id'])) {
            $this->tuError('该用户没有绑定代理');
        }
        setUid($detail['user_id']);
        header('Location:'.U('Agentadmin/index/index'));
        die;
    }

    //返利推荐人
    public function fanli($apply_id){
            $obj = D('UsersAgentApply');
            $detail=$obj->where(['apply_id'=>$apply_id])->find();
            if(empty($detail)){
                $this->tuError('请选择要操作对象');
            }
            //查询代理推荐人代理等级分成百分比 $ rate
            if($detail['user_guide_id'] >0){
                $objd = D('Cityagent')->where(array('agent_id'=>$detail['agent_id']))->find();
                //判断该会员是不是代理
                $existe=$obj->where(['user_id'=>$detail['user_guide_id'],'audit'=>1,'closed'=>0])->find();
                //判断是不是配送管理员
                $pei=D('Applicationmanagement')->where(['user_id'=>$detail['user_guide_id'],'sq_state'=>1,'sq_delete'=>0])->find();
                $hui=D('Users')->where(['user_id'=>$detail['user_guide_id']])->find();
                if(!empty($existe)){
                    $fen = D('Cityagent')->where(array('agent_id'=>$existe['agent_id']))->find();
                    $sum=$objd['price']*($fen['recruit']/100);
                }elseif(!empty($pei)){
                    $fenp=D('Deliveryadmin')->where(['dj_id'=>$pei['dj_id']])->find();
                    $sum=$objd['price']*($fenp['recruit']/100);
                }else{
                    $fenh=D('Userrank')->where(['rank_id'=>$hui['rank_id']])->find();
                    $sum=$objd['price']*($fenh['reward2']/100);
                }
                //分成总表查询
                $arr['user_id'] = $detail['user_guide_id'];
                $arr['agent_id'] = $detail['agent_id'];
                $arr['level'] = $detail['level'];
                $arr['info']  = '您的招商代理分成金额已充至您的账户,金额为'.$sum.'元,请注意查收！';
                $arr['is_separate'] = 0;
                $arr['money']= $sum;
                $arr['code'] = $detail['type']==1?'alipay':'money';
                $arr['addtime'] = time();
                if(M('cityagent_cash')->add($arr)){
                   D('Users')->addMoney($detail['user_guide_id'],$sum,$arr['info']);
                   $obj->where(['apply_id'=>$apply_id])->save(['is_fan'=>1]);
                    $this->tuSuccess('返利成功总返利'.$sum.'元', U('agent/index'));
                }

            }
    }







}