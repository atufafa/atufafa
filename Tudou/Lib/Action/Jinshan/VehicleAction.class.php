<?php
class VehicleAction extends CommonAction{
private $addtype=array('v_type','v_price');
	//显示车辆类型
	public function vehicletype(){
		 $obj = D('Vehicle');
        import('ORG.Util.Page'); 
      
        $count = $obj->count();  
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $obj->order(array('t_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('list', $list); 
        $this->assign('page', $show); 

        $this->display(); 
	}

	//添加类型
	public function Vehicleadd(){

		if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Vehicle');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('vehicle/vehicletype'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
	}

	 private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->addtype);
        $data['v_type'] = htmlspecialchars($data['v_type']);
		if(empty($data['v_type'])) {
            $this->tuError('车辆类型不能为空');
        }
		
        $data['v_price'] = htmlspecialchars($data['v_price']);
        if (empty($data['v_price'])) {
            $this->tuError('类型押金不能为空');
        }
        return $data;
    }

    //修改类型
    public function vehicleedit($id=0){

    	if($id = (int) $id){
            $obj = D('Vehicle');
            $detail = $obj->find($id);
            if (!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的类型');
            }
            if($this->isPost()){
                $data = $this->leveleditCheck();
                $data['t_id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('vehicle/vehicletype'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的类型');
        }

    }


     private function leveleditCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->addtype);
        $data['v_type'] = htmlspecialchars($data['v_type']);
        if (empty($data['v_type'])) {
           $this->tuError('车辆类型不能为空');
        }
        $data['v_price'] = htmlspecialchars($data['v_price']);
        if(empty($data['v_price'])){
            $this->tuError('类型押金不能为空');
        }
       
        return $data;
    }


    //删除
    public function vehicledel($id=0){
    	if($id = (int) $id){
            $obj = D('Vehicle');
               if (false !== $obj->where(array('t_id'=>$id))->delete()){ 
                    $this->tuSuccess('操作成功', U('vehicle/vehicletype'));
                }else{
                $this->tuError('操作失败');
          		 }
        } else {
            $this->tuError('请选择要删除的类型');
        }
	}
		/**
	 *
	 *司机订单显示
	 * 
	 */
	//显示
	public function index(){
		 $running = D('Runningvehicle');
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
		$this->assign('types', D('Runningvehicle')->getTypes());
        $this->display();
	}

	//删除
     public function delete($running_id = 0) {
		$running_id = (int) $running_id;
		$obj = D('Runningvehicle');
		$detail = $obj->where(array('running_id'=>$running_id))->find();
        if($detail['status'] =0){
            $this->tuError('状态错误');
        }else{
            $obj->delete($running_id);
            $this->tuSuccess('删除成功', U('vehicle/index'));
        }
    }

    /**
     *
     *司机提现列表
     * 
     */
    public function cash(){
 	$Userscash = D('Vehiclecash');
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
        $obj = D('Vehiclecash');
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
                        $Users->addMoneyss($detail['user_id'], -($money+$commission),$intro);
                  
                        if($detail['type'] == shop){
                            $this->tuSuccess('商家提现操作成功', U('vehicle/balance'));   
                        }else{
                            $this->tuSuccess('会员提现操作成功', U('vehicle/balance'));  
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
        $detail = D('Vehiclecash')->find($cash_id);
        //var_dump($detail);die;
        if($detail['status'] == 0){
            $data = array();
            $data['cash_id'] = $cash_id;
            $data['status'] = $status;
                D('Vehiclecash')->save($data);       
             //后台提现转账
              $commission = $detail['commission'];
              $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
              $money = $detail['apply_money'];
               //var_dump($money);die;
              $Users = D('Users');
              $s=$Users->addMoneyss($detail['user_id'], -($money+$commission),$intro);
                D('Weixintmpl')->weixin_cash_user($detail['user_id'],1,$detail['type']);//申请提现：1会员申请，2商家同意，3商家拒绝
                    $this->tuSuccess('会员支付宝提现操作成功', U('vehicle/balance'));   
            }else{
                $this->tuError('当前订单状态不正确');
            }
    }
        
    //银行卡提现
    public function bank_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Vehiclecash');
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
                  $Users->addMoneyss($detail['user_id'], -($money+$commission),$intro);
                  
                    D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                    $this->tuSuccess('操作成功', U('vehicle/balance'));
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
            D('Users')->addMoneyss($detail['user_id'], $detail['vehiclemoney'] + $detail['commission'], '提现ID【'.$cash_id.'】会员申请提现拒绝退款，理由【'.$value.'】');
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
     *司机余额列表
     * 
     */
      public function balance(){
        $Usermoneylogs = D('Vehiclemoneylogs');
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

    //评价
    public function pjlist(){
        $obj = D('Vehicledianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
       
        if($comment_id= (int) $this->_param('comment_id')){
            $map['comment_id'] = $comment_id;
            $this->assign('comment_id', $comment_id);
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $user = D('Users')->find($user_id);
            $this->assign('nickname', $user['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $obj->where($map)->order(array('comment_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($shop_ids)){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        // dump($list);die;
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
    }

    //删除点评
     public function comment_delete($comment_id= 0) {
        if(is_numeric($comment_id) && ($comment_id= (int) $comment_id)){
            $obj = D('Vehicledianping');
            $obj->save(array('comment_id' => $comment_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('vehicle/pjlist'));
        }else{
            $comment_id= $this->_post('comment_id', false);
            if(is_array($comment_id)){
                $obj = D('Vehicledianping');
                foreach ($comment_id as $id) {
                    $obj->save(array('comment_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('vehicle/pjlist'));
            }
            $this->tuError('请选择要删除的点评');
        }
    }

    //投诉
    public function tslist(){
        $obj = D('Vehiclets');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['running_id|name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

//审核
public function sh($complaint_id,$p = 0){
        $obj = D('Vehiclets');
        if (!($detail = $obj->find($complaint_id))) {
            $this->error('请选择要审核的配送员');
        }
        $data = array('status' =>1, 'complaint_id' => $complaint_id);
        // if ($detail['is_biz'] == 0) {
        //     $data['is_biz'] = 1;
        // }
        $obj->save($data);
        $this->tuSuccess('操作成功', U('vehicle/tslist',array('p'=>$p)));
    }




}