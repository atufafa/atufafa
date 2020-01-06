<?php
class RunningAction extends CommonAction{
    /**
     * 
     *跑腿订单列表 
     *
     */
    public function index(){
        $running = D('Running');
        import('ORG.Util.Page');
        $map = array( 'closed' => 0);  
		$map = array( );
        if( $keyword = $this->_param( "keyword", "htmlspecialchars" ) ){
            $map['title'] = array("LIKE","%".$keyword."%");
            $this->assign( "keyword", $keyword );
        }
		if(isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        }else{
            $this->assign('st', 999);
        }
        $count = $running->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
		
        $list = $running->where($map)->order('running_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->assign('types', D('Running')->getType());
        $this->display();
       
    }

    public function detail( $running_id ){
        $running_id = $running_id;
        if(empty( $running_id ) || !( $detail = D( "Running" )->find( $running_id ) ) ){
            $this->error( "该跑腿不存在" );
        }
        $this->assign( "detail", $detail );
        $this->display( );
    }

   
     public function delete($running_id = 0) {
		$running_id = (int) $running_id;
		$obj = D('Running');
		$detail = $obj->where(array('running_id'=>$running_id))->find();
        if($detail['status'] =0){
            $this->tuError('状态错误');
        }else{
            $obj->delete($running_id);
            $this->tuSuccess('删除成功', U('running/index'));
        }
    }

    /**
     *
     *跑腿提现
     * 
     */
    public function cash(){
 $Userscash = D('Errands');
        import('ORG.Util.Page');
        $map = array('type' => user);
      $map['shop_id'] = array('exp','is null');
        if($account = $this->_param('account', 'htmlspecialchars')){
            $map['account'] = array('LIKE', '%' . $account . '%');
            $this->assign('account', $account);
        }
        if($cash_id = (int) $this->_param('cash_id')){
            $map['cash_id'] = $cash_id;
            $this->assign('cash_id', $cash_id);
        }
        if($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        if(isset($_GET['st']) || isset($_POST['st'])){
            $st = (int) $this->_param('st');
            if($st != 999){
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        }else{
            $this->assign('st', 999);
        }
        if($code = $this->_param('code', 'htmlspecialchars')){
            if($code != 999){
                $map['code'] = $code;
            }
            $this->assign('code', $code);
        }else{
            $this->assign('code', 999);
        }
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach($list as $row){
            $ids[] = $row['user_id'];
        }
        $Usersex = D('Usersex');
        $map = array();
        $map['user_id'] = array('in', $ids);
        $ex = $Usersex->where($map)->select();
        $tmp = array();
        foreach ($ex as $row) {
            $tmp[$row['user_id']] = $row;
        }
        foreach ($list as $key => $row) {
            $list[$key]['bank_name'] = empty($list[$key]['bank_name']) ? $tmp[$row['user_id']]['bank_name'] : $list[$key]['bank_name'];
            $list[$key]['bank_num'] = empty($list[$key]['bank_num']) ? $tmp[$row['user_id']]['bank_num'] : $list[$key]['bank_num'];
            $list[$key]['bank_branch'] = empty($list[$key]['bank_branch']) ? $tmp[$row['user_id']]['bank_branch'] : $list[$key]['bank_branch'];
            $list[$key]['bank_realname'] = empty($list[$key]['bank_realname']) ? $tmp[$row['user_id']]['bank_realname'] : $list[$key]['bank_realname'];
        }
      
   //   print_r($list);
      
        $this->assign('user_cash', round($user_cash = $Userscash->where(array('type' => user,'status' =>1))->sum('apply_money'),2));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

   //微信提现
    public function weixin_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Errands');
        $cash_id = (int) $cash_id;
        $detail = $obj->find($cash_id);
        if($detail = $obj->find($cash_id)){
            if ($detail['status'] == 0){
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if(false == $obj->weixinUserCach($cash_id,1)){//微信提现逻辑封装
                    $this->tuError($obj->getError());
                }else{
                    
                        $obj->save($data);
                        D('Weixintmpl')->weixin_cash_user($detail['user_id'],1,$detail['type']);//申请提现：1会员申请，2商家同意，3商家拒绝
                  
                        //后台提现转账
                        $commission = $detail['commission'];
                        if(!empty($commission)){
                          $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
                        }else{
                            $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
                        }
                        $money = $detail['apply_money'];
                  $Users = D('Users');
                        $Users->addMoneys($detail['user_id'], -($money+$commission),$intro);
                  
                        if($detail['type'] == shop){
                            $this->tuSuccess('商家提现操作成功', U('running/balance'));   
                        }else{
                            $this->tuSuccess('会员提现操作成功', U('running/balance'));  
                        }
                    
                }
            }else{
                $this->tuError('当前订单状态不正确');
            }
        }else{
            $this->tuError('没找到对应的提现订单');
        }
    }
    
    
    
    //支付宝提现
    public function alipay_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $detail = D('Errands')->find($cash_id);
        //var_dump($detail);die;
        if($detail['status'] == 0){
            $data = array();
            $data['cash_id'] = $cash_id;
            $data['status'] = $status;
                D('Errands')->save($data);       
             //后台提现转账
              $commission = $detail['commission'];
              $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
              $money = $detail['apply_money'];
               //var_dump($money);die;
              $Users = D('Users');
              $s=$Users->addMoneys($detail['user_id'], -($money+$commission),$intro);
                D('Weixintmpl')->weixin_cash_user($detail['user_id'],1,$detail['type']);//申请提现：1会员申请，2商家同意，3商家拒绝
                    $this->tuSuccess('会员支付宝提现操作成功', U('running/balance'));   
            }else{
                $this->tuError('当前订单状态不正确');
            }
    }
        
    //银行卡提现
    public function bank_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Errands');
        $cash_id = (int) $cash_id;
        if($detail = $obj->find($cash_id)){
            if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if($obj->save($data)){
                  //后台提现转账
                  $commission = $detail['commission'];
                  if(!empty($commission)){
                    $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
                  }else{
                    $intro = '您申请提现，扣款'.round($money,2).'元';
                  }
                  $money = $detail['apply_money'];
                  $Users = D('Users');
                  $Users->addMoneys($detail['user_id'], -($money+$commission),$intro);
                  
                    D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                    $this->tuSuccess('操作成功', U('running/balance'));
                }else{
                    $this->tuError('更新数据库失败');
                }
            } else {
                $this->tuError('请不要重复操作');
            }
            
        }else{
            $this->tuError('没找到对应的提现订单');
        }
    }

     //拒绝用户提现
    public function jujue(){
        $status = (int) $_POST['status'];
        $cash_id = (int) $_POST['cash_id'];
        $value = $this->_param('value', 'htmlspecialchars');
        if(empty($value)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝理由请填写'));
        }
        if(empty($cash_id)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => 'ID错误'));
        }
        if(!($detail = D('Errands')->find($cash_id))){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '提现订单详情错误'));
        }
        if($detail['status'] != 0){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝状态错误'));
        }
        if($status == 2){
            D('Users')->addMoneys($detail['user_id'], $detail['errandsmoney'] + $detail['commission'], '提现ID【'.$cash_id.'】会员申请提现拒绝退款，理由【'.$value.'】');
            if(D('Userscash')->save(array('cash_id' => $cash_id, 'status' => $status, 'reason' => $value))){
                D('Weixintmpl')->weixin_cash_user($detail['user_id'],3);
                $this->ajaxReturn(array('status' => 'success', 'msg' => '拒绝退款操作成功', 'url' => U('running/cash')));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝失败'));
            }
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '提现状态不正确'));
        }
    
    }

    /**
     *
     *跑腿余额日志
     * 
     */
    public function balance(){
 $Usermoneylogs = D('Errandsmoneylogs');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array();
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
        if ($user_id = (int) $this->_param('user_id')) {
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Usermoneylogs->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $Usermoneylogs->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    //配置
    public function peizhi(){
        $peizhi=M('errands_config');
        $list=$peizhi->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function peizhiedit(){
        if($this->Ispost()){
            $data=array();
            $data['e_time1']=I('post.e_time1');
            $data['e_time2']=I('post.e_time2');
            $data['price']=I('post.price');
            
            $peizhi=M('errands_config');
            $row=$peizhi->where(array('e_id'=>1))->save($data);
            $this->tuSuccess('保存成功', U('running/peizhi')); 
        }
    }
	
    
}


