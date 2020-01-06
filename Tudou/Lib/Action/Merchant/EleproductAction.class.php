<?php
class EleproductAction extends CommonAction{
    private $create_fields = array('spec_type','spec_id','product_name','end_time','times' ,'is_tuan','is_yuyue','yuyue_time','collocation_id', 'desc', 'cate_id', 'photo', 'cost_price', 'price','tableware_price', 'is_new', 'is_hot', 'is_tuijian', 'create_time', 'create_ip','product_fen');
    private $edit_fields = array('spec_type','spec_id','product_name','times' ,'is_tuan','is_yuyue','yuyue_time','collocation_id', 'desc', 'cate_id', 'photo', 'cost_price', 'price', 'tableware_price','is_new', 'is_hot', 'is_tuijian', 'is_hongbao','product_fen');
    public function _initialize(){
        parent::_initialize();
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
        $this->ele = D('Ele')->find($this->shop_id);
        if (!empty($this->ele) && $this->ele['audit'] == 0){
            $this->error("亲，您的申请正在审核中！");
        }
        if (empty($this->ele) && ACTION_NAME != 'apply'){
            $this->error('您还没有入住外卖频道', U('ele/apply'));
        }
        $this->assign('ele', $this->ele);
        $this->elecates = D('Elecate')->where(array('shop_id' => $this->shop_id, 'closed' => 0))->select();
        $this->assign('elecates', $this->elecates);
        $this->assign('eletype',M('EleType')->where(['shop_id'=>$this->shop_id])->select());
        $this->assign('shop',$this->shop_id);
    }
	
	
    public function index(){
        $Eleproduct = D('Eleproduct');
        import('ORG.Util.Page');
        $map = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $Eleproduct->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Eleproduct->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids = array();
        foreach ($list as $k => $val) {
            if ($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if ($cate_ids) {
            $this->assign('cates', D('Elecate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
    public function create(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $hobby_arr = array();
            $hobby_arr =I('post.collocation_id');
            $arr=implode($hobby_arr,',');
            //拼接implode----取出explode()
            $data['collocation_id']=$arr;
            $obj = D('Eleproduct');
            if($data['product_fen'] <3){
                $this->tuError('分佣比例最低为3%');
            }
            if ($obj->add($data)) {
                D('Elecate')->updateNum($data['cate_id']);
                $this->tuSuccess('添加成功', U('eleproduct/index'));
            }
            $this->tuError('操作失败');
        } else {
            $config = D('Setting')->fetchAll();
            $list=D('Eleproduct')->where(array('shop_id'=>$this->shop_id))->select();
            $this->assign('config',$config);
            $this->assign('list',$list);
            $this->display();
        }
    }
	
	
    private function createCheck(){
        $config = D('Setting')->fetchAll();
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['spec_type'] = (int) $data['spec_type'];
        $spec = I('post.spec_id');
        $ss=implode(",",$spec) ;
        if(!empty($data['spec_type']) && empty($ss)){
            $this->tuError('请勾选口味');
        }
        $data['spec_id']=$ss;
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('菜名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('菜单介绍不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
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
		
	$data['times'] = (int) ($data['times']);
		if(empty($data['times'])){
            $this->tuError('炒制时间不能为空');
        }
		if($data['times']>$config['ele']['chao_times']){
            $this->tuError('炒制时间不能大于'.$config['ele']['chao_times'].'分钟');
	    }		

		$data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Ele')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到外卖设置开启支持预约购买后，再上传预约产品');
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
		//添加
		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->ele['rate']/1000);
		if(false == D('Eleproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuError(D('Eleproduct')->getError());//检测餐具费合理性
		}
		
		
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $now = date('Y-m-d H:i:s',time());
        $data['end_time']=strtotime(date("Y-m-d H:i:s",strtotime("+1years",strtotime($now))));
        $data['audit'] = 1;
        return $data;
    }
    public function edit($product_id = 0){
        if ($product_id = (int) $product_id) {
            $obj = D('Eleproduct');
            if (!($detail = $obj->find($product_id))) {
                $this->tuError('请选择要编辑的菜单管理');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->tuError('请不要操作其他商家的菜单管理');
            }
            if ($this->isPost()) {
                $hobby_arr = array();
                $hobby_arr =I('post.collocation_id');
                $arr=implode($hobby_arr,',');
                //拼接implode----取出explode()

                $data = $this->editCheck();
                $data['collocation_id']=$arr;

                $data['product_id'] = $product_id;
                if($data['product_fen'] <3){
                    $this->tuError('分佣比例最低为3%');
                }
                if (false !== $obj->save($data)) {
                    D('Elecate')->updateNum($data['cate_id']);
                    $this->tuSuccess('操作成功', U('eleproduct/index'));
                }
                $this->tuError('操作失败');
            } else {
                $config = D('Setting')->fetchAll();
                $list=D('Eleproduct')->where(array('shop_id'=>$this->shop_id))->select();
                $spec=M('EleSpec')->where(['shop_id'=>$this->shop_id,'type_id'=>$detail['spec_type']])->find();
                $guige=M('EleSpecItem')->where(['shop_id'=>$this->shop_id,'spec_id'=>$spec['id']])->select();
                $s=explode(',',$detail['spec_id']);
                $this->assign('guige',$guige);
                $this->assign('xz',$s);
                $this->assign('list',$list);
                $this->assign('config',$config);
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $config = D('Setting')->fetchAll();
            $this->assign('config',$config);
            $this->tuError('请选择要编辑的菜单管理');
        }
    }
	
	
    private function editCheck(){
        $config = D('Setting')->fetchAll();
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['spec_type'] = (int) $data['spec_type'];
        $spec = I('post.spec_id');
        $ss=implode(",",$spec) ;
        if(!empty($data['spec_type']) && empty($ss)){
            $this->tuError('请勾选口味');
        }
        $data['spec_id']=$ss;
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('菜名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('菜单介绍不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
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

	$data['times'] = (int) ($data['times']);
	  if(empty($data['times'])){
            $this->tuError('炒制时间不能为空');
        }
        if($data['times']>$config['ele']['chao_times']){
            $this->tuError('炒制时间不能大于'.$config['ele']['chao_times'].'分钟');
	    }
	    
        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Ele')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到外卖设置开启支持预约购买后，再上传预约产品');
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

		//编辑
		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->ele['rate']/1000);
       
		if(false == D('Eleproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuError(D('Eleproduct')->getError());//检测餐具费合理性
		}
		
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['is_hongbao'] = (int) $data['is_hongbao'];
        return $data;
    }
	
		
	//上架下架更新
    public function update($product_id = 0){
        if($product_id = (int) $product_id){
			if(!($detail = D('EleProduct')->find($product_id))){
				$this->tuError('请选择要编辑的菜品');
			}
			$data = array('closed' =>0,'product_id' => $product_id);
			$intro = '上架菜品成功';
			if($detail['closed'] == 0) {
				$data['closed'] = 1;
				$elegoods=M('goods_ele_store_market')->where(['type'=>1,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->find();
				if(!empty($elegoods)){
				    M('goods_ele_store_market')->where(['type'=>1,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->delete();
                }
				$intro = '下架菜品成功';
			}
			if(D('EleProduct')->save($data)){
				$this->tuSuccess($intro, U('eleproduct/index'));
			}
        }else{
            $this->tuError('请选择要删除的菜品管理');
        }
    }
	
    public function delete($product_id = 0){
        if ($product_id = (int) $product_id){
            $obj = D('Eleproduct');
            if (!($detail = $obj->where(array('shop_id' => $this->shop_id, 'product_id' => $product_id))->find())) {
                $this->tuError('请选择要删除的菜单管理');
            }
            D('Elecate')->updateNum($detail['cate_id']);
            $obj->delete($product_id);
            $elegoods=M('goods_ele_store_market')->where(['type'=>1,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->find();
            if(!empty($elegoods)){
                M('goods_ele_store_market')->where(['type'=>1,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->delete();
            }
            $this->tuSuccess('删除成功', U('eleproduct/index'));
        }
        $this->tuError('请选择要删除的菜单管理');
    }

    //外卖类型
public function edittype(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $id =$_POST['id'];
        $model = M("EleType");
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
        $model = D("EleType");
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
            $obj = M('EleType');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }

            $obj->where(array('id'=>$id))->delete();
            $this->tuSuccess('删除成功', U('eleproduct/type'));
        }
    }

    public function flavor(){
        $obj = D('EleSpec');
        $ts = M('EleSpec')->getTableName();
        $tgt = M('EleType')->getTableName();
        $tsi = M('EleSpecItem')->getTableName();
        $where = array("$ts.shop_id"=>$this->shop_id,"$ts.recycle"=>array('EXP','IS NULL'),"b.shop_id"=>$this->shop_id,"b.recycle"=>array('EXP','IS NULL'));
        $count = M('EleSpec')->where($where)
            ->join($tgt." as b on b.id = $ts.type_id")
            ->join($tsi." as c on c.spec_id = $ts.id")
            ->group("$ts.id")
            ->count();
        import('ORG.Util.Page');
        $Page = new Page($count,100);
        $show = $Page->show();
        $specList = M('EleSpec')->where($where)
            ->field("$ts.id,$ts.name,b.name as bname")
            ->join($tgt." as b on b.id = $ts.type_id")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->group("$ts.id")
            ->order('id desc')
            ->select();
        foreach($specList as $k => $v){
            $model = M('EleSpecItem');
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
        $model = D("EleSpec");
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
                    $this->tuSuccess('编辑成功', U('eleproduct/flavor'));
                }
                else{
                    $insert_id = $model->add($data); // 写入数据到数据库
                    //$this->afterSave($insert_id);
                    $this->action($_POST['id'],$insert_id);
                    $this->tuSuccess('添加成功', U('eleproduct/flavor'));
                }
            }
            else
            {
                $this->tuSuccess('操作失败', U('eleproduct/flavor'));
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
        $goodsTypeList = M('EleType')->where(array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }


    //删除规格项目
    public function dele($id = 0) {
        if($id = (int) $id){
            $obj = D('EleSpec');
            if(!$detail =$obj->find($id)){
                $this->tuError('规格不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $obj->where(array('id'=>$id))->delete();
            $this->tuSuccess('删除成功', U('eleproduct/flavor'));
        }
    }

//编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = M('EleSpecItem');
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
            $obj = M('EleSpecItem');
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
            $obj = M('EleSpecItem');
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

    public function foodtype(){
        if(IS_AJAX){
            $type_id=I('post.type_id');
            $shop_id=I('post.shop_id');
            $spec=M('EleSpec')->where(['shop_id'=>$shop_id,'type_id'=>$type_id])->find();
            $guige=M('EleSpecItem')->where(['shop_id'=>$shop_id,'spec_id'=>$spec['id']])->select();
            if(!empty($guige)){
                echoJson(['code'=>1,'data'=>$guige]);
            }else{
                echoJson(['code'=>0,'msg'=>'该类型没有口味选择项']);
            }
        }
    }









}