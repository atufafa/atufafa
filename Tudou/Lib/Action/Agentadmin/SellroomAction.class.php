<?php
class SellroomAction extends CommonAction{
	//今日卖房销售额
	public function daysell(){
        $user_id=$this->uid;
        $obj=D('Shop');
        import('ORG.Util.Page');
        $map=array('user_guide_id'=>$user_id);
        $count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();

        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $start_time=time();
        $end_time=strtotime(date('Y-m-d',time()).' 23:59:59');
        $day_where=['between'=>[$start_time,$end_time]];
        $day_where['type_id']=1;
        $day_where['state']=1;
        $day_where['confirm']=1;
        foreach($list as $key=>$value){
            $order_count=M('life_rebate')->where($day_where)->count();
            $order_money=M('life_rebate')->where($day_where)->sum('money');
            $list[$key]['order_count']=isset($order_count)?$order_count:0;
            $list[$key]['order_money']=isset($order_money)?$order_money:0;
        }
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('page', $show);
        $this->assign('list',$list);
        $this->display();
	}

	//本月卖房销售额
	public function monthsell(){
        $user_id=$this->uid;
        $obj=D('Shop');
        import('ORG.Util.Page');
        $map=array('user_guide_id'=>$user_id);
        $count = $obj->where($map)->count();
        $Page = new Page($count,20);
        $show = $Page->show();

        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $start_time=strtotime(date("Y-m-01"));
        $begin_time=date('Y-m-d',$start_time);
        $end_time=strtotime("$begin_time +1 month -1 day");
        $day_where=['between'=>[$start_time,$end_time]];
        $day_where['type_id']=1;
        $day_where['state']=1;
        $day_where['confirm']=1;
        foreach($list as $key=>$value){
            $order_count=M('life_rebate')->where($day_where)->count();
            $order_money=M('life_rebate')->where($day_where)->sum('money');
            $list[$key]['order_count']=isset($order_count)?$order_count:0;
            $list[$key]['order_money']=isset($order_money)?$order_money:0;
        }
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('page', $show);
        $this->assign('list',$list);
        $this->display();
	}

	//每月买房团队销售额
	public function countsell(){
        if(!empty($_POST['shop_id'])){
            $this->assign('shop_id',$_POST['shop_id']);
            $where['type_id']=1;
            $where['state']=1;
            $where['confirm']=1;
            $where['shop_id']=$_POST['shop_id'];
            $list=M('life_rebate')
                ->where($where)
                ->field("DATE_FORMAT(time,'%Y-%m') months,count(1) order_count,sum(money) total_price")
                ->group('months')
                ->select();
        }
        $this->assign('list',$list);
        $shopList=M('shop')->where(['user_guide_id'=>$this->uid])->field('shop_id,cate_id,shop_name')->select();
        $this->assign('shopList',$shopList);
        $this->display();
	}

}
