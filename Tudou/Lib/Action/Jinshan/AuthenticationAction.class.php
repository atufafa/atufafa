<?php		
class AuthenticationAction extends CommonAction{
    private $edit_index = array('handphoto','positivephoto','backphoto','businessphoto','personname','persontel','address','end_date');
       private $edit_listcat = array('handphoto','positivephoto','backphoto','businessphoto','personname','persontel','address','end_date');

	//显示
	public function index(){
 		$Shop = D('Lifesauthentication');
        import('ORG.Util.Page');
        $map = array('close' => 0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['personname|persontel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		

        $count = $Shop->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $Shop->order(array('times' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
	}

	//审核
	public function examine($rz_id){
		if (is_numeric($rz_id) && ($rz_id = (int) $rz_id)) {
            $obj = D('Lifesauthentication');
            $obj->save(array('rz_id' => $rz_id, 'examine' => 1));
            $this->tuSuccess('审核成功', U('authentication/index'));
        }else{
        	$this->tuError('请选择要审核的认证信息');
        }


	}

	//删除
	public function delete($rz_id,$user_id){
		 if (is_numeric($rz_id) && ($rz_id = (int) $rz_id) && is_numeric($user_id) && ($user_id = (int) $user_id)) {
            $obj = D('Lifesauthentication');
            $fan=D('Lifes');
            $fan->where(array('user_id' =>["in",[$user_id]]))->save(array('closed' => 1));
            $obj->save(array('rz_id' => $rz_id, 'close' => 1));
            $this->tuSuccess('删除成功', U('authentication/index'));
        }else{
        	$this->tuError('请选择要删除的认证信息');
        }
	}

    //编辑
    public function editindex($rz_id){
        
       if($rz_id = (int) $rz_id) { 
        $obj=D('Lifesauthentication');
         if($this->isPost()){
            $data = $this->editCheck($rz_id);
            $data['rz_id'] = $rz_id;
            if(false !== $obj->where(['rz_id'=>$rz_id])->save($data)){
                 $this->tuSuccess('操作成功', U('authentication/index'));    
            }else{
                $this->error('操作失败');
            }

        }else{
            $detail=$obj->where(array('rz_id'=>$rz_id))->find();
            $this->assign('detail',$detail);
        }
     }else{
        $this->error('请选择要编辑的商家');
     }

        $this->display();
    }


    //编辑卖房商家验证
     private function editCheck($rz_id){
         $data = $this->checkFields($this->_post('data', false), $this->edit_index);
          $data['address'] = htmlspecialchars($data['address']);
        if (empty($data['address'])) {
            $this->tuError('地址不能为空');
        }

         $data['persontel'] = htmlspecialchars($data['persontel']);
        if (empty($data['persontel'])) {
            $this->tuError('联系电话不能为空');
        }

         $data['personname'] = htmlspecialchars($data['personname']);
        if (empty($data['personname'])) {
            $this->tuError('联系人不能为空');
        }

        $data['end_date'] = htmlspecialchars($data['end_date']);
        if(!empty($data['end_date'])){
            if(!isDate($data['end_date'])) {
                $this->tuError('结束时间格式不正确');
            }
        }

        $data['positivephoto'] = htmlspecialchars($data['positivephoto']);
        if (empty($data['positivephoto'])) {
            $this->tuError('请上传身份证正面照');
        }
        if (!isImage($data['positivephoto'])) {
            $this->tuError('身份证正面照格式不正确');
        }

        $data['businessphoto'] = htmlspecialchars($data['businessphoto']);
        if (empty($data['businessphoto'])) {
            $this->tuError('请上传营业执照');
        }
        if (!isImage($data['businessphoto'])) {
            $this->tuError('营业执照格式不正确');
        }

        $data['backphoto'] = htmlspecialchars($data['backphoto']);
        if (empty($data['backphoto'])) {
            $this->tuError('请上传身份证背面照');
        }
        if (!isImage($data['backphoto'])) {
            $this->tuError('身份证背面照格式不正确');
        }

        $data['handphoto'] = htmlspecialchars($data['handphoto']);
        if (empty($data['handphoto'])) {
            $this->tuError('请上传个人手持身份证');
        }
        if (!isImage($data['handphoto'])) {
            $this->tuError('个人手持身份证格式不正确');
        }

        return $data;
     }    


    //管理卖房商家后台
     public function login($rz_id){
        $obj = D('Lifesauthentication');
        if (!($detail = $obj->find($rz_id))){
            $this->error('请选择要编辑的商家');
        }
        if (empty($detail['user_id'])) {
            $this->error('该用户没有绑定管理者');
        }
        setUid($detail['user_id']);
        header('Location:' . U('Sell/index/index',array('type'=>1)));
        die;
    }


    //卖车公司认证列表
    public function listcat(){
        $obj = D('Lifesvehicle');
        import('ORG.Util.Page');
        $map = array('close' => 0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['personname|persontel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        

        $count = $obj->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $obj->order(array('times' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }

    //审核
    public function exami($crz_id){
        if (is_numeric($crz_id) && ($crz_id = (int) $crz_id)) {
            $obj = D('Lifesvehicle');
            $obj->save(array('crz_id' => $crz_id, 'examine' => 1));
            $this->tuSuccess('审核成功', U('authentication/listcat'));
        }else{
            $this->tuError('请选择要审核的认证信息');
        }


    }

    //删除
    public function dele($crz_id,$user_id){
         if (is_numeric($crz_id) && ($crz_id = (int) $crz_id) && is_numeric($user_id) && ($user_id = (int) $user_id)) {

            $obj = D('Lifesvehicle');
            $che=D('Lifessell');
            $che->where(array('user_id' =>["in",[$user_id]]))->save(array('closed' => 1));
            $obj->save(array('crz_id' => $crz_id, 'close' => 1));
            $this->tuSuccess('删除成功', U('authentication/listcat'));
        }else{
            $this->tuError('请选择要删除的认证信息');
        }
    }

    //编辑edit_listcat
    public function editlistcat($crz_id){
    if($crz_id = (int) $crz_id) { 
         $obj=D('Lifesvehicle');
        if($this->isPost()){             
            $data = $this->editCat($crz_id);
            $data['crz_id'] = $crz_id;
            if(false !== $obj->where(['crz_id'=>$crz_id])->save($data)){
                 $this->tuSuccess('操作成功', U('authentication/listcat'));    
            }else{
                $this->error('操作失败');
            }
        }else{
            $detail=$obj->where(array('crz_id'=>$crz_id))->find();
            $this->assign('detail',$detail);
        }
    }else{
        $this->error('请选择要编辑的商家');
    }

        $this->display();
    }

    //卖车验证
     //编辑卖房商家验证
     private function editCat($crz_id){
         $data = $this->checkFields($this->_post('data', false), $this->edit_index);
          $data['address'] = htmlspecialchars($data['address']);
        if (empty($data['address'])) {
            $this->tuError('地址不能为空');
        }

         $data['persontel'] = htmlspecialchars($data['persontel']);
        if (empty($data['persontel'])) {
            $this->tuError('联系电话不能为空');
        }

         $data['personname'] = htmlspecialchars($data['personname']);
        if (empty($data['personname'])) {
            $this->tuError('联系人不能为空');
        }

        $data['end_date'] = htmlspecialchars($data['end_date']);
        if(!empty($data['end_date'])){
            if(!isDate($data['end_date'])) {
                $this->tuError('结束时间格式不正确');
            }
        }

        $data['positivephoto'] = htmlspecialchars($data['positivephoto']);
        if (empty($data['positivephoto'])) {
            $this->tuError('请上传身份证正面照');
        }
        if (!isImage($data['positivephoto'])) {
            $this->tuError('身份证正面照格式不正确');
        }

        $data['businessphoto'] = htmlspecialchars($data['businessphoto']);
        if (empty($data['businessphoto'])) {
            $this->tuError('请上传营业执照');
        }
        if (!isImage($data['businessphoto'])) {
            $this->tuError('营业执照格式不正确');
        }

        $data['backphoto'] = htmlspecialchars($data['backphoto']);
        if (empty($data['backphoto'])) {
            $this->tuError('请上传身份证背面照');
        }
        if (!isImage($data['backphoto'])) {
            $this->tuError('身份证背面照格式不正确');
        }

        $data['handphoto'] = htmlspecialchars($data['handphoto']);
        if (empty($data['handphoto'])) {
            $this->tuError('请上传个人手持身份证');
        }
        if (!isImage($data['handphoto'])) {
            $this->tuError('个人手持身份证格式不正确');
        }

        return $data;
     }    

    //管理卖车商家后台
     public function logi($crz_id){
        $obj = D('Lifesvehicle');
        if (!($detail = $obj->find($crz_id))){
            $this->error('请选择要编辑的商家');
        }
        if (empty($detail['user_id'])) {
            $this->error('该用户没有绑定管理者');
        }
        setUid($detail['user_id']);
        header('Location:' . U('Sell/index/index',array('type'=>2)));
        die;
    }

    //卖车回收站
    public function vehicle(){

    $obj = D('Lifesvehicle');
        import('ORG.Util.Page');
        $map = array('close' =>1);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['personname|persontel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        

        $count = $obj->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $obj->order(array('times' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //恢复卖车
    public function recoveryvehicle($crz_id,$user_id){
         if (is_numeric($crz_id) && ($crz_id = (int) $crz_id) && is_numeric($user_id) && ($user_id = (int) $user_id)) {
            $obj = D('Lifesvehicle');
            $che=D('Lifessell');
             $che->where(array('user_id' =>["in",[$user_id]]))->save(array('closed' => 0));
            $obj->save(array('crz_id' => $crz_id, 'close' => 0));
            $this->tuSuccess('操作成功', U('authentication/vehicle'));
        }else{
            $this->tuError('请选择要恢复的认证信息');
        }
    }


    //卖房公司认证
    public function room(){
    $Shop = D('Lifesauthentication');
            import('ORG.Util.Page');
            $map = array('close' => 1);
            if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
                $map['personname|persontel'] = array('LIKE', '%' . $keyword . '%');
                $this->assign('keyword', $keyword);
            }
            

            $count = $Shop->where($map)->count();
            $Page = new Page($count,10);
            $show = $Page->show();
            $list = $Shop->order(array('times' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
            
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //恢复卖房
    public function recoveryroom($rz_id,$user_id){
         if (is_numeric($rz_id) && ($rz_id = (int) $rz_id) && is_numeric($user_id) && ($user_id = (int) $user_id)) {
            $obj = D('Lifesauthentication');
            $fan=D('Lifes');
            $fan->where(array('user_id' =>["in",[$user_id]]))->save(array('closed' => 0));
            $obj->save(array('rz_id' => $rz_id, 'close' => 0));
            $this->tuSuccess('操作成功', U('authentication/room'));
        }else{
            $this->tuError('请选择要恢复的认证信息');
        }


    }

}
