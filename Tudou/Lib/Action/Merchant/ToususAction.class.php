<?php
class ToususAction extends CommonAction{

	public function index(){
    
		$obj = D('Eleordercomplaintsmerchant');
        import('ORG.Util.Page');
        //获取当前商家id
		$map = array('shop_id' => $this->shop_id,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();

	}


    //商家回复
	 public function tousureply($order_id){
        $order_id = (int) $order_id;
         $where =array('order_id' => $order_id);
        $hf = D('Eleordercomplaintsmerchant')->where($where)->find();
     
        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){

                $data=array('order_id'=>$order_id,'sjcontent'=>$sjcontent);

              
                $con=D('Eleordercomplaintsmerchant')->where($where)->save($data);

                if($con>0){
                          $this->tuSuccess('回复成功', U('tousus/index')); 
                }else{
                     $this->tuError('回复失败'); 
                }


            }
             $this->tuError('请填写回复信息');
        }else{

             $this->assign('list', $hf);
             $this->display();

        }
       
    }


}





