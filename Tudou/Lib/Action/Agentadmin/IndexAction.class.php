<?php
class IndexAction extends CommonAction {
	//显示
    public function index(){
        $this->display();
    }

    //主界面
    public function main(){
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

		$this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());

        //代理总人数
        $user_id=$this->uid;
		$obj=D('Users');
		$map=array('fuid1'=>$user_id);
		$count = $obj->where($map)->count();
        $this->assign('count', $count);

        //商家总人数
        $objs=D('Shopguide');
		$maps=array('user_id'=>$user_id);
		$counts = $objs->where($maps)->count();
        $this->assign('counts', $counts);

        //卖房\车总人数
        $objss=D('Lifesauthentication');
        $mapss=array('recommend'=>$user_id);
        $countss=$objss->where($mapss)->count();
        $this->assign('countss',$countss);

        //查询是不是乡村管理员
        $Village=D('Village')->where(['user_id'=>$user_id])->find();
        $this->assign('village',$Village);
        $this->display();
    }


}
