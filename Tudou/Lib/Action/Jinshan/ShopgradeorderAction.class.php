<?php
class ShopgradeorderAction extends CommonAction{

    public function index(){
        $Shopgradeorder = D('Shopgradeorder');
        import('ORG.Util.Page');
        $map = array('closed'=>0);
		$keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        $count = $Shopgradeorder->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgradeorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = $grade_ids = $shop_ids = array();
        foreach ($list as $key => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
			$user_ids[$val['user_id']] = $val['user_id'];
			$grade_ids[$val['grade_id']] = $val['grade_id'];
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
		$this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('shopgrades', D('Shopgrade')->itemsByIds($grade_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    
	
     //购买记录审核
    public function adopt($order_id){

       if($order_id = (int) $order_id){
            $obj = D('Shopgradeorder');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('examine'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('shopgradeorder/index'));
             }
         }else{

            $this->tuError('操作失败');
            } 

    }

    //删除审核记录
    public function delete($order_id){

       if($order_id = (int) $order_id){
            $obj = D('Shopgradeorder');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('closed'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('shopgradeorder/index'));
             }
         }else{

            $this->tuError('操作失败');
            } 
    }
	
	
}