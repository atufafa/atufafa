<?php

class GoodssettingAction extends CommonAction {

     private $create_fields = array('min_price', 'min_money', 'max_price', 'check_num', 'status');
	
	
    public function _initialize(){
        parent::_initialize();
        $this->assign('ranks',D('Userrank')->fetchAll());
    }
	
	//商城
    public function index(){
        $Goods = D('Goods');
        import('ORG.Util.Page'); 
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->count();
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->order(array('check_price' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list); 
        $this->assign('page', $show);
        $this->display(); 
    }

    //商品竞价配置
    public function setting()
    {
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false),$this->create_fields);
            $data['min_price'] = (float)($data['min_price']);
            $data['min_money'] = (float)($data['min_money']);
            $data['max_price'] = (float)($data['max_price']);
            $data['check_num'] = (float)($data['check_num']);
            $data['status'] = (int)$data['status'];
            // print_r($data);die;
            if(false !==(D('Goodssetting')->where(['id'=>1])->save($data))){
                $this->tuSuccess('操作成功',U('goodssetting/setting'));
            }else{
                $this->tuError('操作失败');
            }
        }else{
            $this->assign('profit',D('Goodssetting')->find());
            $this->display();
        }
    }

    //积分商城
    public function integral(){
        $Goods = D('Integralgoodslist');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->order(array('check_price' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //0元购
    public function element(){
        $Goods = D('Pindan');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->order(array('check_price' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //在线抢购
    public function rush(){
        $Goods = D('Tuan');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->where(['audit'=>'1','is_tui'=>1])->order(array('check_price' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }




	
}
