<?php
class SaleAction extends CommonAction{
	//每日个人订单数量
	public function daypersonalorder(){
		$user_id=$this->uid;
		$obj=D('Delivery');
		import('ORG.Util.Page');
		$map=array('recommend'=>$user_id);

		$count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();	
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $bgin=time();
        $sameday=strtotime(date('Y-m-d',time()).'23:59:59');
        $end_where=array('between',array($bgin,$sameday));
        foreach ($list as $key=>$value){
         $list[$key]['order_count']=$this->countorder($value['level'],$value['id'],$end_where);
        }

        $this->assign('page', $show);
		$this->assign('list',$list);
		$this->display();
	}

	//当月个人订单数量
	public function monthpersonalorder(){
        $user_id=$this->uid;
        $obj=D('Delivery');
        import('ORG.Util.Page');
        $map=array('recommend'=>$user_id);

        $count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $bgin=strtotime(date("Y-m-01"));
        $sameday=date('Y-m-d',$bgin);
        $end_time=strtotime("$sameday +1 month -1 day");
        $end_where=array('between',array($bgin,$end_time));
        foreach ($list as $key=>$value){
            $list[$key]['order_count']=$this->countorder($value['level'],$value['id'],$end_where);
        }

        $this->assign('page', $show);
        $this->assign('list',$list);

		$this->display();
	}

	//每月团队订单数量
	public function monthteamoeder(){


        $this->assign('months',$_POST['months']);
        $where['status']=8;
        $where['months']=$_POST['months'];
        $list=M('DeliveryOrder')
            ->where($where)
            ->field("FROM_UNIXTIME(end_time,'%Y-%m') months,count(1) order_count")
            ->group('months')
            ->select();

        $this->assign('months',$_POST['months']);
        $this->assign('list',$list);
        $this->display();

	}

}