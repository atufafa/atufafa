<?php
class StoreproductAction extends CommonAction{
    private $create_fields = array('spec_type','product_name','end_time','is_tuan','is_yuyue','yuyue_time', 'details','desc', 'cate_id', 'photo', 'cost_price', 'price','tableware_price', 'is_new', 'is_hot', 'is_tuijian', 'create_time', 'create_ip','product_fen');
    private $edit_fields = array('spec_type','product_name', 'is_tuan','is_yuyue','yuyue_time','details','desc', 'cate_id', 'photo', 'cost_price', 'price', 'tableware_price','is_new', 'is_hot', 'is_tuijian','product_fen');
    public function _initialize(){
        parent::_initialize();
        $getStoreCate = D('Store')->getStoreCate();
        $this->assign('getStoreCate', $getStoreCate);
        $this->store = D('Store')->find($this->shop_id);
        if(!empty($this->store) && $this->store['audit'] == 0) {
            $this->error('亲，您的申请正在审核中');
        }
        if(empty($this->store) && ACTION_NAME != 'apply'){
            $this->error('您还没有入住便利店频道', U('store/apply'));
        }
        $this->assign('store', $this->store);
        $this->storecates = D('Storecate')->where(array('shop_id' => $this->shop_id, 'closed' => 0))->select();
        $this->assign('storecates', $this->storecates);
        $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$this->shop_id,'type'=>goods,'closed'=>0))->select());//快递

        $this->assign('eletype',M('StoreType')->where(['shop_id'=>$this->shop_id])->select());
        $this->assign('shop',$this->shop_id);

    }
    public function index(){
        $obj = D('Storeproduct');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids = array();
        foreach ($list as $k => $val) {
            if ($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if ($cate_ids) {
            $this->assign('cates', D('Storecate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function create(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $config = D('Setting')->fetchAll();
            $obj = D('Storeproduct');
            if($data['product_fen'] <$config['site']['product_fen']){
                $this->tuError('分佣比例最低为'.$config['site']['product_fen'].'%');
            }
            $details = $this->_post('details', 'SecurityEditorHtml');
            if($words = D('Sensitive')->checkWords($details)){
                $this->tuError('详细内容含有敏感词：' . $words);
            }
            if ($goods_id=$obj->add($data)) {
                $this->shuxin($goods_id);
                D('Storecate')->updateNum($data['cate_id']);
                $this->tuSuccess('添加成功', U('storeproduct/index'));
            }
            $this->tuError('操作失败');
        } else {
            $re =D('Storecate')->where(array('shop_id'=>$this->shop_id))->select();
            // print_r($re);die;
            $this->assign('storecates',$re);
            $config = D('Setting')->fetchAll();
            $this->assign('config',$config);
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['spec_type'] = (int) $data['spec_type'];
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('商品介绍不能为空');
        }
        $data['shop_id'] = $this->shop_id;
		
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
		$res = D('Storecate')->where(array('cate_id'=>$data['cate_id']))->find();
		if($res['parent_id'] == 0){
			$this->tuError('请选择二级分类');
		}
		 
		 
		 
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
		$data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }
		if($data['cost_price']){
			if($data['price'] >= $data['cost_price']){
				$this->tuError('售价不能高于原价');
			}
		}

        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Store')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到便利店设置开启支持预约购买后，再上传预约产品');
        }
        if($data['is_yuyue']==1 && empty($data['yuyue_time'])){
            $this->tuError('请填写发货时间');
        }

        $data['is_tuan'] = (int) $data['is_tuan'];
        if($data['is_tuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }

		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->store['rate'] / 1000);

		if(false == D('Storeproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuError(D('Storeproduct')->getError());//检测餐具费合理性
		}
		
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['audit'] = 0;
        $now = date('Y-m-d H:i:s',time());
        $data['end_time']=strtotime(date("Y-m-d H:i:s",strtotime("+1years",strtotime($now))));
        return $data;
    }
	
	
    public function edit($product_id = 0){
        if ($product_id = (int) $product_id) {
            $obj = D('Storeproduct');
            if (!($detail = $obj->find($product_id))) {
                $this->tuError('请选择要编辑的商品管理');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->tuError('请不要操作其他商家的商品管理');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['product_id'] = $product_id;
                if($data['product_fen'] <1){
                    $this->tuError('分佣比例最低为1%');
                }
                $details = $this->_post('details', 'SecurityEditorHtml');
                if($words = D('Sensitive')->checkWords($details)){
                    $this->tuError('详细内容含有敏感词：' . $words);
                }
                if (false !== $obj->save($data)) {
                    D('Storecate')->updateNum($data['cate_id']);
                    $this->shuxin($product_id);
                    $this->tuSuccess('操作成功', U('storeproduct/index'));
                }
                $this->tuError('操作失败');
            }else{
				$this->assign('parent_id',D('Storecate')->getParentsId($detail['cate_id']));
                $this->assign('goodsType',M("StoreType")->select());
                $this->assign('detail', $detail);
                $config = D('Setting')->fetchAll();
                $this->assign('config',$config);
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商品管理');
        }
    }
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['spec_type'] = (int) $data['spec_type'];
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('商品介绍不能为空');
        }
		
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
		$res = D('Storecate')->where(array('cate_id'=>$data['cate_id']))->find();
		if($res['parent_id'] == 0){
			$this->tuError('请选择二级分类');
		}
		 
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
		$data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }
		if($data['cost_price']){
			if($data['price'] >= $data['cost_price']){
				$this->tuError('售价不能高于原价');
			}
		}

        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Store')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到便利店设置开启支持预约购买后，再上传预约产品');
        }
        if($data['is_yuyue']==1 && empty($data['yuyue_time'])){
            $this->tuError('请填写发货时间');
        }
        $data['is_tuan'] = (int) $data['is_tuan'];
        if($data['is_tuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }

		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->store['rate'] / 1000);
		if(false == D('Storeproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuError(D('Storeproduct')->getError());//检测餐具费合理性
		}
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        return $data;
    }
    public function dstorete($product_id = 0){
        if (is_numeric($product_id) && ($product_id = (int) $product_id)) {
            $obj = D('Storeproduct');
            if (!($detail = $obj->where(array('shop_id' => $this->shop_id, 'product_id' => $product_id))->find())) {
                $this->tuError('请选择要删除的商品管理');
            }
            D('Storecate')->updateNum($detail['cate_id']);
            $obj->save(array('product_id' => $product_id, 'closed' => 1));
            $storegoods=M('goods_ele_store_market')->where(['type'=>2,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->find();
            if(!empty($storegoods)){
                M('goods_ele_store_market')->where(['type'=>2,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->delete();
            }
            $this->tuSuccess('删除成功', U('storeproduct/index'));
        }
        $this->tuError('请选择要删除的商品管理');
    }


    //外卖类型
    public function edittype(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $id =$_POST['id'];
        $model = M("StoreType");
        if(IS_POST){
            $model->create();
            $id = $_POST['id'];
            if($id){
                $data['name']=I('name');
                $data['id']=$id;
                $model->save($data);
                $this->tuSuccess("编辑成功!",U('type'));
            }else{
                $data['shop_id']=$this->shop_id;
                $data['name']=I('name');
                $model->add($data);
                $this->tuSuccess("添加成功!",U('type'));
            }
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('id',$_GET['id']);
        $this->assign('goodsType',$goodsType);
        $this->display();
    }

    public function type(){
        $model = D("StoreType");
        $where = array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL'));
        $count = $model->where($where)->count();
        import('ORG.Util.Page');
        $Page  = new Page($count,100);
        $show  = $Page->show();
        $goodsTypeList = $model->where($where)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show',$show);

        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('shopid',$this->shop_id);
        $this->display();
    }

    public function deltype($id = 0){
        if($id = (int) $id){
            $obj = M('StoreType');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }

            $obj->where(array('id'=>$id))->delete();
            $this->tuSuccess('删除成功', U('storeproduct/type'));
        }
    }

    public function flavor(){
        $obj = D('StoreSpec');
        $ts = M('StoreSpec')->getTableName();
        $tgt = M('StoreType')->getTableName();
        $tsi = M('StoreSpecItem')->getTableName();
        $where = array("$ts.shop_id"=>$this->shop_id,"$ts.recycle"=>array('EXP','IS NULL'),"b.shop_id"=>$this->shop_id,"b.recycle"=>array('EXP','IS NULL'));
        $count = M('StoreSpec')->where($where)
            ->join($tgt." as b on b.id = $ts.type_id")
            ->join($tsi." as c on c.spec_id = $ts.id")
            ->group("$ts.id")
            ->count();
        import('ORG.Util.Page');
        $Page = new Page($count,100);
        $show = $Page->show();
        $specList = M('StoreSpec')->where($where)
            ->field("$ts.id,$ts.name,b.name as bname")
            ->join($tgt." as b on b.id = $ts.type_id")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->group("$ts.id")
            ->order('id desc')
            ->select();
        foreach($specList as $k => $v){
            $model = M('StoreSpecItem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $this->display();
    }

//修改编辑
    public  function spec(){
        $model = D("StoreSpec");
        $type = $_POST['id'] > 0 ? 2 : 1;
        $uid = $_GET['uid'];
        if(IS_POST){
            $data['name'] = $_POST['name'];
            if(!$data['name']){
                $this->tuError('输入规格名称');
            }
            $data['type_id'] = $_POST['type_id'];
            if(!$data['type_id']){
                $this->tuError('请选择类型');
            }
            $data['type_id'] = $_POST['type_id'];
            $data['search_index'] = $_POST['search_index'];
            if($this->shop_id){
                $data['shop_id'] = $this->shop_id;
                if ($type == 2){
                    //$data['id']=$type;
                    $model->where("id=".$_POST['id'])->save($data); //更新数据库
                    //$lastid =$this->afterSave($_POST['id']);
                    $this->action($_POST['id'],'');
                    $this->tuSuccess('编辑成功', U('storeproduct/flavor'));
                }
                else{
                    $insert_id = $model->add($data); // 写入数据到数据库
                    //$this->afterSave($insert_id);
                    $this->action($_POST['id'],$insert_id);
                    $this->tuSuccess('添加成功', U('storeproduct/flavor'));
                }
            }
            else
            {
                $this->tuSuccess('操作失败', U('storeproduct/flavor'));
            }
        }
        $id = $_GET['id'] ? $_GET['id'] : 0;
        if($id>0){
            $spec = $model->where(array('id'=>$id,'shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->find();
        }else{ $spec=array();}
        //$spec = $model->where(array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->find($id);
        $items = $this->getSpecItem($id);
        $this->assign('spec',$spec);
        $this->assign('items',$items);
        $this->assign('uid',$id);
        $goodsTypeList = M('StoreType')->where(array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }


    //删除规格项目
    public function dele($id = 0) {
        if($id = (int) $id){
            $obj = D('StoreSpec');
            if(!$detail =$obj->find($id)){
                $this->tuError('规格不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $obj->where(array('id'=>$id))->delete();
            $this->tuSuccess('删除成功', U('storeproduct/flavor'));
        }
    }

//编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = M('StoreSpecItem');
        $arr = $model->where(array('spec_id'=>$spec_id))->order('id')->select();
//        $arr = get_id_val($arr, 'id','item');
        return $arr;
    }

    //保存规格项
    public function action($uid,$aa){
        $local['shop_id'] = $this->shop_id;
        if(empty($aa))
        {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            $obj = M('StoreSpecItem');
            foreach ($data as $k => $val) {
                $local['id'] = (int) $k;
                $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                $local['spec_id'] = (int)$uid;
                if (!empty($local['item']) && !empty($local['spec_id'])) {
                    $obj->save($local);
                }
            }
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    $local2['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                    $local2['spec_id'] = (int)$uid;
                    $local2['shop_id'] = $this->shop_id;
                    if (!empty($local2['item']) && !empty($local2['spec_id'])) {
                        $obj->add($local2);
                    }
                }
            }
        }
        else
        {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            if(empty($new[1][item])){
                unset($new);
            }
            $obj = M('StoreSpecItem');
            foreach ($data as $k => $val) {
                $local['id'] = (int) $k;
                $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                $local['spec_id'] = (int)$uid;
                if (!empty($local['item']) && !empty($local['spec_id'])) {
                    $obj->save($local);
                }
            }
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    $local2['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                    $local2['spec_id'] = (int)$aa;
                    $local2['shop_id'] = $this->shop_id;
                    if (!empty($local2['item']) && !empty($local2['spec_id'])) {
                        $obj->add($local2);
                    }
                }
            }
        }
    }


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['product_id'] ? $_GET['product_id'] : 0;

        $specList = D('StoreSpec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = D('StoreSpecItem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = M('StoreGoodsPrice')->where('product_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        if($goods_id){
            $specImageList = M('StoreSpecImage')->where("product_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){

        $goods_id = $_REQUEST['product_id'] ? $_REQUEST['product_id'] : 0;
        $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2);

        $spec = M('StoreSpec')->getField('id,name');
        $specItem = M('StoreSpecItem')->getField('id,item,spec_id');
        $keySpecGoodsPrice = M('StoreGoodsPrice')->where('product_id = '.$goods_id)->getField('key,key_name,price,store_count,bar_code');

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        foreach ($clo_name as $k => $v) {
            $str .=" <td><b>{$spec[$v]}</b></td>";
        }
        $str .="<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
        foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";
        }
        $str .= "</table>";
        return $str;
    }

    public function shuxin($goods_id){
        if($_POST['item']){
            $spec = M('StoreSpec')->getField('id,name');
            $specItem = M('StoreSpecItem')->getField('id,item');
            $specGoodsPrice = M("StoreGoodsPrice");
            $specGoodsPrice->where('product_id = '.$goods_id)->delete();
            foreach($_POST['item'] as $k => $v){
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']);
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = array('product_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
            }
            $specGoodsPrice->addAll($dataList);
        }
        refresh_store_stock($goods_id);

    }



}