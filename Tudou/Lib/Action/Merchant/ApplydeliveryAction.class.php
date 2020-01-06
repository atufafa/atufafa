<?php
class ApplydeliveryAction extends CommonAction {

	public function index(){
		$shop=$this->shop_id;

		if($dc = D('Apply')->where(array('shop_id'=>$shop))->find()){
            $this->error('您已经申请过了,请耐心等候审核！');
        }


		if($this->_post()){
			 $content=I('post.content');

			$data=array();
			$data['content']=$content;
			 if(empty($data['content'])){
			 	$this->tuError('申请内容不能为空');
                
            }
             if($words = D('Sensitive')->checkWords($data['content'])){
       
                 $this->tuError('申请内容含有敏感词：'. $words);
           }
			$data['shop_id']=$shop;

			 $pey= D('Apply')->add($data);

			 if($pey>0){
			 	$this->tuSuccess('申请成功！', U('applydelivery/index')); 
            }else{
                $this->tuError('申请失败！');
            }
		}



		$this->display();
	}












}


