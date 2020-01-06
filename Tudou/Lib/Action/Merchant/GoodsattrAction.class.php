<?php

class GoodsattrAction extends CommonAction {

    //规格列表
    public function index(){
        $obj = D('TpSpec');
        $ts = M('TpSpec')->getTableName();
        $tgt = M('TpGoodsType')->getTableName();
        $tsi = M('TpSpecItem')->getTableName();
        $where = array("$ts.shop_id"=>$this->shop_id,"$ts.recycle"=>array('EXP','IS NULL'),"b.shop_id"=>$this->shop_id,"b.recycle"=>array('EXP','IS NULL'));
        $count = M('TpSpec')->where($where)
            ->join($tgt." as b on b.id = $ts.type_id")
            ->join($tsi." as c on c.spec_id = $ts.id")
            ->group("$ts.id")
            ->count();
        import('ORG.Util.Page');
        $Page = new Page($count,100);
        $show = $Page->show();
        $specList = M('TpSpec')->where($where)
            ->field("$ts.id,$ts.name,b.name as bname")
            ->join($tgt." as b on b.id = $ts.type_id")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->group("$ts.id")
            ->order('id desc')
            ->select();
        foreach($specList as $k => $v){
            $model = M('TpSpecItem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $this->display();
    }

    public function recycle(){
        $goodsTypeList = M('TpGoodsType')->select();
        $type_id = I('audit');
        if(!empty($type_id)){
            $where = array('type_id'=>$type_id);
        }else{
            $where = ' 1 = 1 ';
        }
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
        $obj = D('TpSpec');
        $count = $obj->where($where)->count();
        import('ORG.Util.Page');
        $Page = new Page($count,13);
        $show = $Page->show();
        $specList = $obj->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($specList as $k => $v){
            $model = M('TpSpecItem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $goodsTypeList = M('TpGoodsType')->select();
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('type_id',$type_id);
        $this->display();
    }
    

    //修改编辑
    public  function spec(){
         $model = D("TpSpec");
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
                     $this->tuSuccess('编辑成功', U('goodsattr/index'));
                 }
                 else{
                     $insert_id = $model->add($data); // 写入数据到数据库
                     //$this->afterSave($insert_id);
                     $this->action($_POST['id'],$insert_id);
                     $this->tuSuccess('添加成功', U('goodsattr/index'));
                 }
             }
             else
             {
                 $this->tuSuccess('操作失败', U('goodsattr/index'));
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
       $goodsTypeList = M('TpGoodsType')->where(array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
       $this->assign('goodsTypeList',$goodsTypeList);           
       $this->display();           
    }  



	
	//删除规格项目
    public function delete($id = 0) {
        if($id = (int) $id){
			$obj = D('TpSpec');
			if(!$detail =$obj->find($id)){
				$this->tuError('规格不存在');
			}
			if($detail['shop_id'] != $this->shop_id){
				$this->tuError('非法操作');
			}
			$data['recycle']=1;
            $obj->where(array('id'=>$id))->save($data);
			//D('TpSpecItem')->where(array("spec_id"=>$id))->delete();
            //$obj->delete($id);
            $this->tuSuccess('删除成功', U('goodsattr/index'));
        }
    }

    //还原
    public function reduction($id = 0) {
        if($id = (int) $id){
            $obj = D('TpSpec');
            if(!$detail =$obj->find($id)){
                $this->tuError('规格不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('还原成功', U('goodsattr/index'));
        }
    }
	

	//编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = M('TpSpecItem');
        $arr = $model->where(array('spec_id'=>$spec_id))->order('id')->select();
//        $arr = get_id_val($arr, 'id','item');
        return $arr;
    }   

	//后置操作方法 废除
     public function afterSave($id){
        $model = M("TpSpecItem"); 
        $post_items = explode(PHP_EOL, $_POST['items']);        
        foreach ($post_items as $key => $val)  {
            $val = str_replace('_', '', $val); // 替换特殊字符
            $val = str_replace('@', '', $val); // 替换特殊字符
            $val = trim($val);
            if(empty($val)) 
                unset($post_items[$key]);
            else                     
                $post_items[$key] = $val;
        }
        $db_items = $model->where("spec_id = $id")->getField('id,item');
        
        // 提交过来的 跟数据库中比较 不存在插
        foreach($post_items as $key => $val){
            if(!in_array($val, $db_items))            
                $dataList[] = array('spec_id'=>$id,'item'=>$val);            
        }
        $dataList && $model->addAll($dataList);
        /* 数据库中的 跟提交过来的比较 不存在删除*/
        foreach($db_items as $key => $val){
            if(!in_array($val, $post_items))       {       
               M("TpSpecGoodsPrice")->where("`key` REGEXP '^{$key}_' OR `key` REGEXP '_{$key}_' OR `key` REGEXP '_{$key}$'")->delete(); // 删除规格项价格表
               $model->where('id='.$key)->delete(); // 删除规格项
            }
        }        
    }
    //保存规格项
    public function action($uid,$aa){
        $local['shop_id'] = $this->shop_id;
        if(empty($aa))
        {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            $obj = M('TpSpecItem');
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
            $obj = M('TpSpecItem');
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

    //删除规格项
    public function delGoodsSpecType($id = 0) {
        if ($id = (int) $id) {
            $obj = D('TpSpecItem');
            $obj->where(array("shop_id"=>$this->shop_id))->delete($id);
            $uid = I('uid');
            $this->tuSuccess('删除成功', U('goodsattr/spec',array('id'=>$uid)));
        }
        $this->tuError('请选择要删除的选项');
    }
}
