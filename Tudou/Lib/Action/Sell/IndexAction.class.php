<?php
class IndexAction extends CommonAction{
//    public function _initialize(){
//        $this->uid = getUid();
//        $type =(int) $this-> _get('type');
//        if($type==1){
//            $room = M('lifes_authentication')->where(array('user_id' => $this->uid,'close' => 0,'examine'=>1))->find();
//            if(!empty($room)){
//                $this->assign('information',$room);
//            }
//        }elseif($type==2){
//            $vehicle = M('lifes_vehicle')->where(array('user_id' => $this->uid,'close' => 0,'examine'=>1))->find();
//            if(!empty($vehicle)){
//                $this->assign('information',$vehicle);
//            }
//        }
//    }

    public function index(){


        $this->display();

    }
    public function main(){
		$user_id=$this->uid;

        //var_dump($this->uid);
        $bg_time = strtotime(TODAY);
        $str_1 = '-1 day';
		$str_2 = '-2 day';
		$str_3 = '-3 day';
		$str_4 = '-4 day';
		$str_5 = '-5 day';
        $bg_time_1 = strtotime(date('Y-m-d', strtotime($str_1)));
		$bg_time_2 = strtotime(date('Y-m-d', strtotime($str_2)));
		$bg_time_3 = strtotime(date('Y-m-d', strtotime($str_3)));
		$bg_time_4 = strtotime(date('Y-m-d', strtotime($str_4)));
		$bg_time_5 = strtotime(date('Y-m-d', strtotime($str_5)));
        
        $this->assign('m',$m = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time)), 'shop_id' => $this->shop_id))->sum('money'));
       
       
        $this->assign('m_1',$m_1 = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time), array('EGT', $bg_time_1)), 'shop_id' => $this->shop_id))->sum('money'));
		
		$this->assign('m_2',$m_2 = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time_1), array('EGT', $bg_time_2)), 'shop_id' => $this->shop_id))->sum('money'));
		 
		$this->assign('m_3',$m_3 = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time_2), array('EGT', $bg_time_3)), 'shop_id' => $this->shop_id))->sum('money'));
		  
		$this->assign('m_4',$m_4 = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time_3), array('EGT', $bg_time_4)), 'shop_id' => $this->shop_id))->sum('money'));
		   
	  $this->assign('m_5', $m_5 = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time_4), array('EGT', $bg_time_5)), 'shop_id' => $this->shop_id))->sum('money'));
		$this->assign('jingjia',M('Jingjia')->where(['id'=>1])->find());
		// print_r(M('Jingjia')->where(['id'=>1])->find());die;
		$this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('hoor',D('Deliveryahonor')->select());
        $this->assign('business', D('Business')->fetchAll());


        $this->display();
    }


}
