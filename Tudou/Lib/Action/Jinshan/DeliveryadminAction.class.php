<?php		
class DeliveryadminAction extends CommonAction{
 	private $deliveryadd_list = array('name', 'price','fencheng','recruit');
 	private $deliveryaddry_list = array('ry_name', 'ry_price');
 	/**
 	 *
 	 *管理等级列表
 	 * 
 	 */
	//显示等级
	public function djindex(){
		$obj = D('Deliveryadmin');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->order('dj_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
	}

	//添加
	public function deliveryadd(){
	 $obj = D('Deliveryadmin');
        if($this->isPost()){
            $data = $this->addCheck();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('deliveryadmin/djindex'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
	}

		//添加验证
	  private function addCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->deliveryadd_list);
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuError('配送管理员级别为空');
        }
        $data['price'] = htmlspecialchars($data['price']);
        if(empty($data['price'])){
            $this->tuError('配送管理员等级为空');
        }
         $data['fencheng'] = htmlspecialchars($data['fencheng']);
        if(empty($data['fencheng'])){
            $this->tuError('跑单分成比例为空');
        }
       
        $data['recruit'] = htmlspecialchars($data['recruit']);
        if(empty($data['recruit'])){
            $this->tuError('招商分成比例为空');
        }
       
        return $data;
    }

    //修改
    public function deliveryedit($id = 0){

    	 if($id = (int) $id){
            $obj = D('Deliveryadmin');
            $detail = $obj->find($id);
            if (!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的配送员级别');
            }
            if($this->isPost()){
                $data = $this->addCheck();
                $data['dj_id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('deliveryadmin/djindex'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的配送员级别');
        }

    }

    //删除
    public function deliverydel($id=0){
    	 if ($id = (int) $id) {
            $obj = D('Deliveryadmin')->where(array('dj_id' => $id))->delete();
         
            $this->tuSuccess('删除成功', U('deliveryadmin/djindex'));
       
        }
    }

/**
 *
 *荣誉列表
 * 
 */
public function ryindex(){
		$obj = D('Deliveryahonor');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order('ry_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
	}

	//添加
	public function deliveryaddry(){
	 $obj = D('Deliveryahonor');
        if($this->isPost()){
            $data = $this->addryCheck();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('deliveryadmin/ryindex'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
	}

		//添加验证
	  private function addryCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->deliveryaddry_list);
        $data['ry_name'] = htmlspecialchars($data['ry_name']);
        if (empty($data['ry_name'])) {
            $this->tuError('荣誉名称为空');
        }
        return $data;
    }

    //修改
    public function deliveryeditry($id = 0){

    	 if($id = (int) $id){
            $obj = D('Deliveryahonor');
            $detail = $obj->find($id);
            if (!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的荣誉名称');
            }
            if($this->isPost()){
                $data = $this->addryCheck();
                $data['ry_id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('deliveryadmin/ryindex'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的配送员级别');
        }

    }

    //删除
    public function deliverydelry($id=0){
    	 if ($id = (int) $id) {
            $obj = D('Deliveryahonor')->where(array('ry_id' => $id))->delete();
         
            $this->tuSuccess('删除成功', U('deliveryadmin/ryindex'));
       
        }
    }


    /**
     *
     *配送员、管理员奖励配置
     * 
     */
    //显示列表
    public function jlindex(){

    	 $list = D('Deliveryordinary')->select();
      	 $lists = D('Deliverysuper')->select();

      
        $this->assign('list', $list);
      	$this->assign('lists', $lists);
       
    	$this->display();
    }


    //修改信息
    public function upjiangli(){

		 if ($this->isPost()) {
		 	$data=array();
		 	$data['recommend1']=I('post.recommend1');
		 	$data['reward1']=I('post.reward1');


		 	$datas=array();
		 	$datas['recommend2']=I('post.recommend2');
		 	$datas['reward2']=I('post.reward2');

	
		 	$gai=D('Deliveryordinary')->where(array('jl_id'=>1))->save($data);	
			$gais=D('Deliverysuper')->where(array('gly_id'=>1))->save($datas);

			if($gais>0){
			 		$this->tuSuccess('保存成功', U('deliveryadmin/jlindex'));
			 	}else{
			 		 $this->tuError('操作失败');
			 	}
		}

    }


    /**
     *
     *配送管理员申请列表
     * 
     */
    //显示列表 Applicationmanagement
    public function sqindex(){
 		$Shop = D('Applicationmanagement');
        import('ORG.Util.Page');
		
        $map = array('sq_delete' => 0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['user_id|sq_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		

        $count = $Shop->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $Shop->order(array('sq_time' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $dengji_id = $yongyu_id = array();

        foreach ($list as $value){
            $dengji_id[]=$value['dj_id'];
            $yongyu_id[]=$value['ry_id'];
        }

        $daili=D('Deliveryadmin')->itemsByIds($dengji_id);
        $hoor=D('Deliveryahonor')->itemsByIds($yongyu_id);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('mc',$hoor);
        $this->assign('daili',$daili);

        $this->display();
    }



    //修改列表
    public function updelivery($id=0){


    	 if($id = (int) $id){
            $obj = D('Applicationmanagement');
            $dj=D('Deliveryadmin')->select();
            $ry=D('Deliveryahonor')->select();
            $this->assign('dj', $dj);
            $this->assign('ry', $ry);


            $detail = $obj->find($id);
            if (!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的荣誉名称');
            }
            if($this->isPost()){
                //$data = $this->addryChecks();
                $data['ry_id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('deliveryadmin/ryindex'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的配送员级别');
        }
    }

    //添加验证
      private function addryChecks(){
        $data = $this->checkFields($this->_post('data', false), $this->deliveryadd_list);
        $data['sq_name'] = htmlspecialchars($data['sq_name']);
        if (empty($data['name'])) {
            $this->tuError('配送管理员级别为空');
        }
        $data['price'] = htmlspecialchars($data['price']);
        if(empty($data['price'])){
            $this->tuError('配送管理员等级为空');
        }
         $data['fencheng'] = htmlspecialchars($data['fencheng']);
        if(empty($data['fencheng'])){
            $this->tuError('分成比例为空');
        }
       
        $data['recruit'] = htmlspecialchars($data['recruit']);
        if(empty($data['recruit'])){
            $this->tuError('招商分成比例为空');
        }
       
       
        return $data;
    }

    //审核通过
    public function shen($id=0){

       if($id = (int) $id){
            $obj = D('Applicationmanagement');
            $details = $obj->where(array('sq_id'=>$id))->save(array('sq_state'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('deliveryadmin/sqindex'));
             }
         }else{

            $this->tuError('操作失败');
            } 
    }



    //删除信息-------逻辑删除
    public function deldelivery($id=0){
         if($id = (int) $id){
            $obj = D('Applicationmanagement');
            $details = $obj->where(array('sq_id'=>$id))->save(array('sq_delete'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('deliveryadmin/sqindex'));
             }
         }else{

            $this->tuError('操作失败');
            } 

    }

    //同意分成
    public function fencheng($id=0){
        if($id = (int) $id){
            $obj = D('Applicationmanagement')->where(array('sq_id'=>$id))->find();
            $dengji=D('Deliveryadmin')->where(array('dj_id'=>$obj['dj_id']))->find();

            if($obj['recommend']!=0){
                //判断该会员是不是代理
                $existe=D('UsersAgentApply')->where(['user_id'=>$obj['recommend'],'audit'=>1,'closed'=>0])->find();
                //判断是不是配送管理员
                $pei=D('Applicationmanagement')->where(['user_id'=>$obj['recommend'],'sq_state'=>1,'sq_delete'=>0])->find();
                $hui=D('Users')->where(['user_id'=>$obj['recommend']])->find();
                if(!empty($existe)){
                    $fen = D('Cityagent')->where(array('agent_id'=>$existe['agent_id']))->find();
                    $sum=$dengji['price']*($fen['recruit']/100);
                }elseif(!empty($pei)){
                    $fenp=D('Deliveryadmin')->where(['dj_id'=>$pei['dj_id']])->find();
                    $sum=$dengji['price']*($fenp['recruit']/100);
                }else{
                    $fenh=D('Userrank')->where(['rank_id'=>$hui['rank_id']])->find();
                    $sum=$dengji['price']*($fenh['reward2']/100);
                }

            $info='恭喜您，推荐会员'.$obj['user_id'].'成为配送管理员，管理等级为【'.$dengji['name'].'】,所以您获得分成金额为:'.$sum.'。';
              D('Users')->addMoney($obj['recommend'],$sum,$info);

                $details = D('Applicationmanagement')->where(array('sq_id'=>$id))->save(array('fencheng'=>1));

                if ($details>0) {
                    $this->tuSuccess('操作成功,返给推荐人：'.$sum.'元', U('deliveryadmin/sqindex'));
                }
            }else{
                $this->tuError('该配送管理员上级为平台方，不参与分成');
            }
        }else{
            $this->tuError('操作失败');
        }


    }

    /**
     *
     *登录配送管理员
     * 
     */

    //管理登录
      public function login($id){
        $obj = D('Applicationmanagement');
        if (!($detail = $obj->find($id))){
            $this->error('请选择要编辑的配送管理员');
        }
        if (empty($detail['user_id'])) {
            $this->error('该用户没有绑定管理者账号');
        }
        setUid($detail['user_id']);
        header('Location:' . U('Distribution/index/index'));
        die;
    }

    /**
     *
     *购买等级记录
     * 
     */

    //配送管理员购买记录
    public function purchase(){
        $Shopgradeorder = D('Deliverypurchase');
        import('ORG.Util.Page');
        $map = array('closed'=>0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        
        $count = $Shopgradeorder->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgradeorder->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $dj=D('Deliveryadmin')->select();
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('gldj',$dj);

        $this->assign('list', $list);
        $this ->assign('page', $show);

        $this->display();
    }

    //购买记录审核
    public function adopt($order_id){

       if($order_id = (int) $order_id){
            $obj = D('Deliverypurchase');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('examine'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('deliveryadmin/purchase'));
             }
         }else{

            $this->tuError('操作失败');
            } 

    }

    //删除审核记录
    public function delete($order_id){

       if($order_id = (int) $order_id){
            $obj = D('Deliverypurchase');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('closed'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('deliveryadmin/purchase'));
             }
         }else{

            $this->tuError('操作失败');
            } 
    }



}
