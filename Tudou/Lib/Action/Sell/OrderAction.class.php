<?php
class OrderAction extends CommonAction{
	//成交订单
	public function index(){
		 $obj = D('Liferebate');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array(array('close'=>0,'shop_id'=>$this->uid));
        
        $count = $obj->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $obj->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('user',D('Users')->select());
        $this->assign('type',D('Lifetype')->select());
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);

		$this->display();
	}


}
