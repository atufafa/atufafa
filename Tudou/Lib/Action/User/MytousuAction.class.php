<?php
class MytousuAction extends CommonAction{

	public function tslist(){
		$this->display();
	}



	//投诉配送员列表
	public function tspsy(){
	    $list=M('delivery_complainter')->where(['user_id'=>$this->uid])->select();
		$this->assign('list', $list);
		$this->display();
	}

	
	//修改配送员投诉信息
	public function edittspsy(){
		  $order_id = (int) $this->_get('order_id');
		  $where =array('order_id' => $order_id, 'user_id' => $this->uid);
		  $detail = D('Deliverycomplaintsrider')->where($where)->find();

	 	if($this->_Post()){

		  	 // //获取页面信息
          $content=I('post.content');
          $photo=I('post.photo');

   		 //创建数组存储
          $data=array();
          $data['content']=$content;

            if(empty($data['content'])){
                $this->tuMsg('投诉内容不能为空');
            }
            
             if($words = D('Sensitive')->checkWords($data['content'])){
                $this->tuMsg('评价内容含有敏感词：' . $words);
           }
          $data['photo']=$photo;
        
         $pey=D('Deliverycomplaintsrider')->where($where)->save($data);

            if($pey>0){

               $this->tuMsg('修改投诉信息成功！', U('mytousu/tslist'));
            }else{
                $this->tuMsg('修改投诉信息失败！');
            }


		}

		$this->assign('list', $detail);
		$this->display();
}
	
	//删除投诉配送员信息
	public function deltspsy(){
		 $order_id = (int) $this->_get('order_id');
		 $where =array('order_id' => $order_id, 'user_id' => $this->uid);

		 $del=D('Deliverycomplaintsrider')->where($where)->delete();

		 if($del>0){
		 	 $this->success('删除投诉信息成功！', U('mytousu/tslist'));
            }else{
           
                 $this->error('删除投诉信息失败！');
            } 
	}

//投诉商家列表
	public function tssj(){

		$list=D('Shopcomplaint')->where(['user_id'=>$this->uid])->select();
		$this->assign('list', $list);
		$this->display();
	}



	//修改商家投诉信息
	public function edittssj(){
		  $order_id = (int) $this->_get('order_id');
		  $where =array('order_id' => $order_id, 'user_id' => $this->uid);
		  $detail = D('Eleordercomplaintsmerchant')->where($where)->find();

	 	if($this->_Post()){

		  	 // //获取页面信息
          $content=I('post.content');
          $photo=I('post.photo');

   		 //创建数组存储
          $data=array();
          $data['content']=$content;

            if(empty($data['content'])){
                $this->tuMsg('投诉内容不能为空');
            }
            
             if($words = D('Sensitive')->checkWords($data['content'])){
                $this->tuMsg('评价内容含有敏感词：' . $words);
           }
          $data['photo']=$photo;
        
         $pey=D('Eleordercomplaintsmerchant')->where($where)->save($data);

            if($pey>0){

               $this->tuMsg('修改投诉信息成功！', U('mytousu/tslist'));
            }else{
                $this->tuMsg('修改投诉信息失败！');
            }


		}

		$this->assign('list', $detail);
		$this->display();
}

 //删除商家信息
	public function deltssj(){
		 $order_id = (int) $this->_get('order_id');
		 $where =array('order_id' => $order_id, 'user_id' => $this->uid);

		 $del=D('Eleordercomplaintsmerchant')->where($where)->delete();

		 if($del>0){
		 	 $this->success('删除投诉信息成功！', U('mytousu/tslist'));
            }else{
           
                 $this->error('删除投诉信息失败！');
            } 
	}


}
