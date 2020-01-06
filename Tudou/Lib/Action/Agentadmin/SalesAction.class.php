<?php
class SalesAction extends CommonAction{
	//每日个人销售额
	public function daypersonal(){
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
        foreach($list as $key=>$value){
            $list[$key]['order_count']=getShopOrderCount($value['cate_id'],$value['shop_id'],$day_where);
            $list[$key]['order_money']=getShopOrderMoney($value['cate_id'],$value['shop_id'],$day_where);
        }
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('page', $show);
        $this->assign('list',$list);
        $this->display();
	}

	//每月个人销售额
	public function monthpersonal(){
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
        foreach($list as $key=>$value){
            $list[$key]['order_count']=getShopOrderCount($value['cate_id'],$value['shop_id'],$day_where);
            $list[$key]['order_money']=getShopOrderMoney($value['cate_id'],$value['shop_id'],$day_where);
        }
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('page', $show);
        $this->assign('list',$list);
        $this->display();
	}

	//每月团队销售额
	public function team(){
	    if(!empty($_POST['shop_id']) && !empty($_POST['cate_id'])){
            $this->assign('shop_id',$_POST['shop_id']);
            $list=getShopEveryMonthData($_POST['cate_id'],$_POST['shop_id']);
        }
        $this->assign('list',$list);
        $shopList=M('shop')->where(['user_guide_id'=>$this->uid])->field('shop_id,cate_id,shop_name')->select();
        $this->assign('shopList',$shopList);
		$this->display();
	}

}