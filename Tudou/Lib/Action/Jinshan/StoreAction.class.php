<?php
class StoreAction extends CommonAction {
    private $create_fields = array('shop_id', 'cate', 'distribution', 'is_open', 'is_pay', 'is_daofu','is_coupon', 'is_new', 'full_money', 'new_money','is_full','order_price_full_1','order_price_reduce_1','order_price_full_2','order_price_reduce_2', 'logistics','logistics_full', 'since_money', 'sold_num', 'month_num', 'intro', 'audit', 'orderby', 'rate');
    private $edit_fields = array('shop_name','glass_num','is_guozi','city_id', 'area_id', 'business_id','is_open', 'cate', 'distribution', 'is_pay', 'is_daofu','is_coupon',  'is_new', 'full_money', 'new_money','is_full','order_price_full_1','order_price_reduce_1','order_price_full_2','order_price_reduce_2', 'logistics','logistics_full','since_money', 'sold_num', 'month_num', 'intro', 'orderby','lng','lat','audit', 'rate');

    public function _initialize() {
        parent::_initialize();
        $getStoreCate = D('Store')->getStoreCate();
        $this->assign('getStoreCate', $getStoreCate);
    }

    public function index() {
        $obj = D('Store');
        import('ORG.Util.Page'); 
        $map = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
		if (isset($_GET['is_open']) || isset($_POST['is_open'])) {
            $is_open = (int) $this->_param('is_open');
            if ($is_open != 999) {
                $map['is_open'] = $is_open;
            }
            $this->assign('is_open', $is_open);
        } else {
            $this->assign('is_open', 999);
        }
		if (isset($_GET['is_new']) || isset($_POST['is_new'])) {
            $is_new = (int) $this->_param('is_new');
            if ($is_new != 999) {
                $map['is_new'] = $is_new;
            }
            $this->assign('is_new', $is_new);
        } else {
            $this->assign('is_new', 999);
        }
        $count = $obj->where($map)->count();  
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $obj->where($map)->order(array('shop_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->display(); 
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Store');
            $cate = $this->_post('cate', false);
            $cate = implode(',', $cate);
            $data['cate'] = $cate;
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('store/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->tuError('ID不能为空');
        }
        if (!$shop = D('Shop')->find($data['shop_id'])) {
            $this->tuError('商家不存在');
        }
        $data['shop_name'] = $shop['shop_name'];
        $data['lng'] = $shop['lng'];
        $data['lat'] = $shop['lat'];
        $data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];
        $data['is_open'] = (int) $data['is_open'];
        $data['is_pay'] = (int) $data['is_pay'];
        $data['is_daofu'] = (int) $data['is_daofu'];
		$data['is_coupon'] = (int) $data['is_coupon'];
        $data['is_new'] = (int) $data['is_new'];
        $data['full_money'] = (float) ($data['full_money']);
        $data['new_money'] = (float) ($data['new_money'] );
	
		$data['is_full'] = (int) $data['is_full'];
		$data['order_price_full_1'] = (float) ($data['order_price_full_1']);
		$data['order_price_reduce_1'] = (float) ($data['order_price_reduce_1']);
		$data['order_price_full_2'] = (float) ($data['order_price_full_2']);
		$data['order_price_reduce_2'] = (float) ($data['order_price_reduce_2']);
		if($data['is_full']){
			if($data['order_price_full_1'] == 0 || $data['order_price_reduce_1'] == 0){
				$this->tuError('满多少1或者减多少1必填或者填写错误');
			}
			if($data['order_price_reduce_1'] >= $data['order_price_full_1']){
				$this->tuError('减去多少1不能大于满多少1');
			}
			if($data['order_price_full_2']){
				if($data['order_price_full_2'] == 0 || $data['order_price_reduce_2'] == 0){
					$this->tuError('满多少1或者减多少1必填或者填写错误');
				}
				if($data['order_price_reduce_2'] >= $data['order_price_full_2']){
					$this->tuError('减去多少2不能大于满多少2');
				}
				if($data['order_price_full_1'] >= $data['order_price_full_2']){
					$this->tuError('满多少1不能大于满多少2');
				}
			}
		}
		
		
        $data['logistics'] = (float) ($data['logistics']);
		$data['logistics_full'] = (float) ($data['logistics_full']);
        $data['since_money'] = (float) ($data['since_money']);
        $data['sold_num'] = (int) $data['sold_num'];
        $data['month_num'] = (int) $data['month_num'];
        $data['rate'] = (int) $data['rate'];
		if(empty($data['rate'])) {
            $this->tuError('销售提成不能为空');
        }
		if($data['rate'] < 0) {
            $this->tuError('销售提成设置错误');
        }
        $data['audit'] = (int) $data['audit'];
        $data['distribution'] = (int) $data['distribution'];
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('说明不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function edit($shop_id = 0) {
        if ($shop_id = (int) $shop_id) {
            $obj = D('Store');
            if (!$detail = $obj->find($shop_id)) {
                $this->tuError('请选择要编辑的餐饮商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['shop_id'] = $shop_id;
                $cate = $this->_post('cate', false);
                $cate = implode(',', $cate);
                $data['cate'] = $cate;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('store/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('province',D('province')->where(['is_open'=>1])->select());
                $cate = explode(',', $detail['cate']);
                $this->assign('cate', $cate);
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的餐饮商家');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
		$data['shop_name'] = htmlspecialchars($data['shop_name']);
        if (empty($data['shop_name'])) {
            $this->tuError('便利店名称不能为空');
        }
		$data['city_id'] = (int) $data['city_id'];
		if (empty($data['city_id'])) {
            $this->tuError('所在城市不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->tuError('所在区域不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->tuError('所在商圈不能为空');
        }
        $data['is_open'] = (int) $data['is_open'];
        $data['is_pay'] = (int) $data['is_pay'];
        $data['is_daofu'] = (int) $data['is_daofu'];
		$data['is_coupon'] = (int) $data['is_coupon'];
        $data['is_new'] = (int) $data['is_new'];
        $data['full_money'] = (float) ($data['full_money'] );
        $data['new_money'] = (float) ($data['new_money']);
		$data['is_full'] = (int) $data['is_full'];
        $data['is_guozi'] = (int) $data['is_guozi'];
        $data['glass_num'] = (int) $data['glass_num'];
        if($data['is_guozi']!=1&& !empty($data['glass_num'])){
            $this->tuError('该商家未开启赠送果汁，不能填写');
        }
		$data['order_price_full_1'] = (float) ($data['order_price_full_1']);
		$data['order_price_reduce_1'] = (float) ($data['order_price_reduce_1']);
		$data['order_price_full_2'] = (float) ($data['order_price_full_2'] );
		$data['order_price_reduce_2'] = (float) ($data['order_price_reduce_2']);
		if($data['is_full']){
			if($data['order_price_full_1'] == 0 || $data['order_price_reduce_1'] == 0){
				$this->tuError('满多少1或者减多少1必填或者填写错误');
			}
			if($data['order_price_reduce_1'] >= $data['order_price_full_1']){
				$this->tuError('减去多少1不能大于满多少1');
			}
			if($data['order_price_full_2']){
				if($data['order_price_full_2'] == 0 || $data['order_price_reduce_2'] == 0){
					$this->tuError('满多少1或者减多少1必填或者填写错误');
				}
				if($data['order_price_reduce_2'] >= $data['order_price_full_2']){
					$this->tuError('减去多少2不能大于满多少2');
				}
				if($data['order_price_full_1'] >= $data['order_price_full_2']){
					$this->tuError('满多少1不能大于满多少2');
				}
			}
		}
		
        $data['logistics'] = (float) ($data['logistics']);
		$data['logistics_full'] = (float) ($data['logistics_full']);
        $data['since_money'] = (float) ($data['since_money']);
        $data['sold_num'] = (int) $data['sold_num'];
        $data['month_num'] = (int) $data['month_num'];
        $data['distribution'] = (int) $data['distribution'];
        $data['audit'] = (int) $data['audit'];
        $data['intro'] = htmlspecialchars($data['intro']);
        $data['rate'] = (int) $data['rate'];
		if(empty($data['rate'])) {
            $this->tuError('销售提成不能为空');
        }
		if($data['rate'] < 0) {
            $this->tuError('销售提成设置错误');
        }
        if (empty($data['intro'])) {
            $this->tuError('说明不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
		$data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        return $data;
    }

    public function delete($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Store');
            $obj->delete($shop_id);
            $this->tuSuccess('删除成功', U('store/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Store');
                foreach ($shop_id as $id) {
                    $obj->delete($id);
                }
                $this->tuSuccess('删除成功', U('store/index'));
            }
            $this->tuError('请选择要删除的餐饮商家');
        }
    }

    //新增配送费开关
    public function IsDelivery($shop_id = 0,$is_pei) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Store');
            $obj->where(array('shop_id' =>$shop_id))->save(array('is_pei' => $is_pei));
            $this->tuSuccess('操作成功', U('store/index'));
        }
    }

    public function opened($shop_id = 0, $type = 'open') {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Store');
            $is_open = 0;
            if ($type == 'open') {
                $is_open = 1;
            }
            $obj->save(array('shop_id' => $shop_id, 'is_open' => $is_open));
            $this->tuSuccess('操作成功', U('store/index'));
        }
    }

    public function select(){
        $obj = D('Store');
        import('ORG.Util.Page'); 
        $map = array('audit'=>1);
        if($keyword = $this->_param('keyword','htmlspecialchars')){
            $map['shop_name|intro'] = array('LIKE','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }
        $count = $obj->where($map)->count(); 
        $Page = new Page($count, 10); 
        $pager = $Page->show(); 
        $list = $obj->where($map)->order(array('shop_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); 
        $this->assign('page', $pager); 
        $this->display(); 
        
    }


    
    //新增部分 --分类列表
    public function cate_list()
    {
        $ele = M('Storecate');
        import('ORG.Util.Page'); 
        $map =" parent_id =0";
        $count = $ele->where($map)->count(); 
        $Page = new Page($count, 10); 
        $pager = $Page->show(); 
        $list = $ele->where($map)->order(array('cate_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); 
        $this->assign('page', $pager); 
        $this->display();
    }

    //新增部分  --开启分类
    public function cate_status($cate_id,$status)
    {
        if($cate_id = (int)$cate_id){
            if(false !== (M('Storecate')->where(['cate_id'=>$cate_id])->save(['status'=>$status]))){
                $this->Success('操作成功');
            }else{
                $this->tuError('操作失败');
            }
        }
    }
    //新增部分  --开启前台展示
    public function cate_IsLook($cate_id,$is_look)
    {
        if($cate_id = (int)$cate_id){
            if(false !== (M('Storecate')->where(['cate_id'=>$cate_id])->save(['is_look'=>$is_look]))){
                $this->Success('操作成功');
            }else{
                $this->tuError('操作失败');
            }
        }
    }
    //新增部分  --分类操作
    public function cate_edit($cate_id)
    {
        if($cate_id = (int)$cate_id){
            if(false !==($detail = M('Storecate')->where(['cate_id'=>$cate_id])->find())){
                if($this->isPost()){
                    $data = $this->checkFields($this->_post('data', false), array('cate_name','cate_pic','status','is_look'));
                    // print_r($data);die;
                    if(false !==(M('Storecate')->where(['cate_id'=>$cate_id])->save($data))){
                        $this->tuSuccess('修改成功',U('store/cate_list'));
                    }else{
                        $this->tuError('修改失败');
                    }
                }else{
                    $this->assign('detail',$detail);
                    $this->display();
                }
            }else{
                $this->tuError('参数错误');
            }    
        }
    }

    //新增部分  --新增分类
    public function cate_add()
    {
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('cate_name','cate_pic','status','is_look'));
            if(false !==(M('Storecate')->add($data))){
                $this->tuSuccess('新增成功',U('store/cate_list'));
            }else{
                $this->tuError('新增失败');
            }
        }
        $this->display();
    }
    
    //新增部分  --删除分类
    public function cate_del($cate_id)
    {
        if($cate_id = (int)$cate_id){
            if(false !==(M('Storecate')->where(['cate_id'=>$cate_id])->delete())){
                $this->Success('删除成功',U('store/cate_list'));
            }else{
                $this->Error('删除失败');
            }
        }else{
            $this->Error('参数错误');
        }
    }
    //新增部分  --添加子类
    public function cate_parent($parent_id)
    {
        if(!($parent_id = (int) $parent_id)) {
            $this->tuError('请选择正确的父级菜单');
        }
        if ($this->isPost()) {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            $obj = D('Storecatelist');
            foreach ($data as $k => $val) {
                $local = array();
                $local['cate_id'] = (int) $k;
                $local['cate_name'] = htmlspecialchars($val['cate_name'], ENT_QUOTES, 'UTF-8');
                $local['status'] = htmlspecialchars($val['status'], ENT_QUOTES, 'UTF-8');
                $local['is_look'] = (int) $val['is_look'];
                if (!empty($local['cate_name']) && !empty($local['cate_id'])) {
                    $obj->save($local);
                }
            }
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    $local = array();
                    $local['cate_name'] = htmlspecialchars($val['cate_name'], ENT_QUOTES, 'UTF-8');
                    $local['status'] = htmlspecialchars($val['status'], ENT_QUOTES, 'UTF-8');
                    $local['is_look'] = (int) $val['is_look'];
                    $local['parent_id'] = $parent_id;
                    if (!empty($local['cate_name'])) {
                        $obj->add($local);
                    }
                }
            }
            $obj->cleanCache();
            $this->tuSuccess('更新成功', U('store/cate_list'));
        } else {
            $menu = D('Storecatelist')->select();
            // print_r($parent_id);die;
            $this->assign('datas', $menu);
            $this->assign('parent_id', $parent_id);
            $this->display();
        }
    }
    
}
