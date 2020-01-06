<?php
class TousupsAction extends CommonAction{

	//投诉配送员
	public function index(){
 	$obj = D('Deliverycomplaintsrider');
        import('ORG.Util.Page');
		$map = array('closed' => 0);
		if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
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
    public function sh($complaint_id){
        $obj = D('Deliverycomplaintsrider');
        if (!($detail = $obj->find($complaint_id))) {
            $this->error('请选择要审核的配送员');
        }
        $data = array('status' =>1, 'complaint_id' => $complaint_id);

        $obj->save($data);
        $this->tuSuccess('操作成功', U('tousups/index'));
    }

    //扣费
    public function koufei($complaint_id){
       $obj = D('Deliverycomplaintsrider');
       $detail = $obj->where(array('complaint_id'=>$complaint_id))->find();
        if (empty($detail)) {
            $this->error('请选择要惩罚的配送员');
        }
        //配送员的扣费delivery_ts
        $ps=$this->_CONFIG['site']['delivery_ts'];
        //管理员的扣费delivery_tsp
        $gl=$this->_CONFIG['site']['delivery_tsp'];
        //配送员
         $peison=D('Delivery')->where(array('user_id'=>$detail['user_id']))->find();
         if($peison['recommend']>0){
            //管理员
             $peisonadmin = D("Applicationmanagement")->getField("user_id,recommend,dj_id");
              $datalist = array();
              foreach ($peisonadmin as $key => $value) {
                //判断配送员上级管理员是谁
                 if($value['user_id']==$peison['recommend']){
                      $datalist[$value['user_id']]==$value['dj_id'];
                      //扣费
                      if(!empty($gl)){
                        $intro = '您下级被投诉，扣款'.$gl.'元';
                      }
                      $money = $gl;
                      $Users = D('Users');
                      $Users->addMoney($value['user_id'], -$money,$intro);
                      //判断是不是还有上级
                      if($value['recommend']>0){
                        $datalist[$value['recommend']]==$peisonadmin[$value['recommend']]['dj_id'];
                            $peisonadmin2=$peisonadmin[$value['recommend']]['recommend'];                          
                             if(!empty($gl)){
                                $intro = '您下级被投诉，扣款'.$gl.'元';
                              }
                              $money = $gl;
                              $Users = D('Users');
                              $Users->addMoney($value['recommend'], -$money,$intro);
                            //判断是否还有上级
                            if($peisonadmin2>0){
                                $datalist[$peisonadmin[$peisonadmin2]['user_id']]==$peisonadmin[$peisonadmin2]['dj_id'];
                            if(!empty($gl)){
                              $intro = '您下级被投诉，扣款'.$gl.'元';
                            }
                             $money = $gl;
                             $Users = D('Users');
                             $Users->addMoney($peisonadmin2, -$money,$intro);
                           }
                      }  
                 }
              }

             if(!empty($ps)){
                 $intro = '您被投诉，扣款'.$ps.'元';
             }
             $money = $ps;
             $Users = D('Users');
             $Users->addMoney($detail['user_id'], -$money,$intro);

         }else{
            if(!empty($ps)){
                 $intro = '您被投诉，扣款'.$ps.'元';
             }
             $money = $ps;
             $Users = D('Users');
             $Users->addMoney($detail['user_id'], -$money,$intro);
         }

        $data = array('is_pay' =>1, 'complaint_id' => $complaint_id); 

        $obj->save($data);
        $this->tuSuccess('操作成功', U('tousups/index'));



    }

}