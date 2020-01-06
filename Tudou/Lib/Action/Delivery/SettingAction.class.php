<?php
class SettingAction extends CommonvehicleAction{
	    private $edit_fieldss = array('card_photo', 'name', 'mobile', 'addr_str');
	
 public function vehicle() {
        $detail = D('Userspinche')->where(array('user_id'=>$this->uid))->find();
        if(empty($detail)){
             $this->error('未知错误');
        }elseif($detail['audit'] !=1){
             $this->error('您的信息未审核');   
        }elseif($detail['closed'] !=0){
             $this->error('状态不正确');     
        }
        $this->assign('detail', $detail); 
        $this->display();
    }
    
    public function vehicleedit($user_id = 0){
    	//var_dump($user_id);
         if ($user_id = (int) $user_id) {
            $obj = D('Userspinche');
            $detail = $obj->where(array('user_id'=>$user_id))->find();
            //var_dump($detail['user_id']);die;
            if (empty($detail)) {
                $this->error('请选择要编辑的资料');
            }
            if($detail['user_id'] !=$this->uid){
                $this->error('不能修改其他人的资料');
            }
           if ($this->isPost()) {
                $data = $this->editChecks();
                if (false !== $obj->where(array('user_id'=>$user_id))->save($data)) {
                    $this->tuMsg('操作成功', U('setting/vehicle'));
                }
                $this->tuMsg('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuMsg('请选择要编辑的资料');
        }
    }
    
     private function editChecks(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fieldss);

        $data['card_photo'] = htmlspecialchars($data['card_photo']);
        if (empty($data['card_photo'])) {
            $this->tuMsg('请上传身份证');
        }
        if (!isImage($data['card_photo'])) {
            $this->tuMsg('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuMsg('姓名不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('电话不能为空');
        }
        if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuMsg('电话应该为13位手机号码');
        }
        $data['addr_str'] = htmlspecialchars($data['addr_str']);
        if (empty($data['addr_str'])) {
            $this->tuMsg('地址不能为空');
        }        
        return $data;
    }

        public function vsms($user_id){
        $obj = D('Userspinche');
        $detail = $obj->where(array('user_id'=>$user_id))->find();
  
        if (empty($detail)) {
            $this->error('请选择要编辑的配送员资料');
        }
        if($detail['user_id'] !=$this->uid){
            $this->error('不能修改其他人的资料');
        }
        $data = array();
        //var_dump($data);die;
        if ($detail['is_sms'] == 0) {
            $data['is_sms'] = 1;
        }elseif($detail['is_sms'] == 1){
        	 $data['is_sms'] = 0;
        }
       //var_dump($data);die;
        $obj->where(array('user_id'=>$user_id))->save($data);
        $this->success('修改短信通知状态成功', U('setting/vehicle'));
    }
    public function vweixin($user_id){
        $obj = D('Userspinche');
        $detail = $obj->where(array('user_id'=>$user_id))->find();
        if (empty($detail)) {
            $this->error('请选择要编辑的配送员资料');
        }
        if($detail['user_id'] !=$this->uid){
            $this->error('不能修改其他人的资料');
        }
        $data = array();
        if ($detail['is_weixin'] == 0) {
            $data['is_weixin'] = 1;
        }elseif($detail['is_weixin'] == 1){
			$data['is_weixin'] = 0;
        }
        $obj->where(array('user_id'=>$user_id))->save($data);
        $this->success('修改微信通知状态成功', U('setting/vehicle'));
    }
    public function vmusic($user_id){
        $obj = D('Userspinche');
        $detail = $obj->where(array('user_id'=>$user_id))->find();
        if (empty($detail)) {
            $this->error('请选择要编辑的配送员资料');
        }
        if($detail['id'] !=$this->uid){
            $this->error('不能修改其他人的资料');
        }
        $data = array();
        if ($detail['is_music'] == 0) {
            $data['is_music'] = 1;
        }elseif($detail['is_music'] == 1){
        	$data['is_music'] = 0;
        }
        $obj->where(array('user_id'=>$user_id))->save($data);
        $this->success('修改语音通知状态成功', U('setting/vehicle'));
    }


    public function vopen($user_id){
        $obj = D('Userspinche');
        $detail = $obj->where(array('user_id'=>$user_id))->find();
        if (empty($detail)) {
            $this->error('请选择要编辑的配送员资料');
        }
        if($detail['user_id'] !=$this->uid){
            $this->error('不能修改其他人的资料');
        }
        $data = array();
        if ($detail['is_open'] == 1) {
            $data['is_open'] = 0;
        }elseif($detail['is_open'] == 0){
        	 $data['is_open'] = 1;
        }
        $obj->where(array('user_id'=>$user_id))->save($data);
        $this->success('修改接单通知状态成功', U('setting/vehicle'));
    }



}