<?php
class GoodsattrAction extends CommonAction {
    //规格列表
    public function index(){
        $goodsTypeList = M("TpGoodsType")->select();
        $type_id = I('audit');
        if(!empty($type_id)){
            $where = array('type_id'=>$type_id);
        }else{
            $where = ' 1 = 1 '; // 搜索条件
        }
        $model = D('TpSpec');
        $count = $model->where($where)->count();
        import('ORG.Util.Page');
        $Page = new Page($count,13);
        $show = $Page->show();
        $specList = $model->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($specList as $k => $v){
            $model = M('TpSpecItem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $goodsTypeList = M("TpGoodsType")->select();
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('type_id',$type_id);
        $this->display();
    }
    //修改编辑
    public  function addEditSpec(){
        $model = D("TpSpec");
        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $uid = $_GET['uid'];
        if(IS_POST){
            $data['name'] = $_POST['name'];
            $data['search_index'] = $_POST['search_index'];
            if(!$data['name']){
                $this->tuError('输入规格名称');
            }
            $data['type_id'] = $_POST['type_id'];
            if(!$data['type_id']){
                $this->tuError('请选择类型');
            }
            if($type == 1){
                if($TpSpec = D("TpSpec")->where(array('name'=>$data['name'],'type_id'=>$data['type_id']))->find()){
                    $this->tuError('名称【'.$data['name'].'】重复');
                }
            }
            if ($type == 2){

                $data['id']=$uid;
                $model->save($data); //更新数据库
                //$lastid =$this->afterSave($_POST['id']);
                $this->action($uid,'');
                $this->tuSuccess('编辑成功', U('goodsattr/index'));
            }
            else{
                $insert_id = $model->add($data); // 写入数据到数据库
                //$this->afterSave($insert_id);
                $this->action($uid,$insert_id);
                $this->tuSuccess('添加成功', U('goodsattr/index'));
            }
        }
        // 点击过来编辑时
        $id = $_GET['id'] ? $_GET['id'] : 0;
        $spec = $model->find($id);
        $items = $this->getSpecItem($id);
        $this->assign('spec',$spec);
        $this->assign('items',$items);
        $this->assign('uid',$id);
        $goodsTypeList = M("TpGoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display('spec');
    }
    //删除规格项目
    public function delGoodsSpec($id = 0) {
        if(is_numeric($id) && ($id = (int) $id)){
            $obj = D('TpSpec');
            $count = $obj->judge_goods_item($id);//判断规格
            if($count > 0){
                $this->tuError('该类型下有规格不得删除');
            }
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('goodsattr/index'));
        }else{
            $ids = $this->_post('id', false);
            if(is_array($ids)){
                //无效
//                foreach ($ids as $id){
//                    $count = $obj->judge_goods_item($id);//判断规格
//                    if($count > 0){
//                        $this->tuError('该类型下有规格不得删除');
//                    }
//                    $obj->delete($id);
//                }
                $this->tuSuccess('删除成功', U('goodsattr/index'));
            }
            $this->tuError('请选择要删除的规格');
        }
    }

    //编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = M('TpSpecItem');
        $arr = $model->where("spec_id = $spec_id")->order('id')->select();
        //$arr = get_id_val($arr, 'id','item');
        return $arr;
    }

    //后置操作方法
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


    public function action($uid,$aa){
        if(empty($aa))
        {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            $obj = M('TpSpecItem');
            foreach ($data as $k => $val) {
                $local = array();
                $local['id'] = (int) $k;
                $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                $local['spec_id'] = (int)$uid;
                if (!empty($local['item']) && !empty($local['spec_id'])) {
                    $obj->save($local);
                }
            }
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    print_r($new);
                    $local = array();
                    $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                    $local['spec_id'] = (int)$uid;
                    if (!empty($local['item']) && !empty($local['spec_id'])) {
                        $obj->add($local);
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
                $local = array();
                $local['id'] = (int) $k;
                $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                $local['spec_id'] = (int)$uid;
                if (!empty($local['item']) && !empty($local['spec_id'])) {
                    $obj->save($local);
                }
            }
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    print_r($new);
                    $local = array();
                    $local['item'] = htmlspecialchars($val['item'], ENT_QUOTES, 'UTF-8');
                    $local['spec_id'] = (int)$aa;
                    if (!empty($local['item']) && !empty($local['spec_id'])) {
                        $obj->add($local);
                    }
                }
            }
        }
    }


    public function delGoodsSpecType($id = 0) {
        if ($id = (int) $id) {
            $obj = D('TpSpecItem');
            $obj->delete($id);
            $uid = I('uid');
            $this->tuSuccess('删除成功', U('goodsattr/addEditSpec',array('id'=>$uid)));
        }
        $this->tuError('请选择要删除的选项');
    }
}