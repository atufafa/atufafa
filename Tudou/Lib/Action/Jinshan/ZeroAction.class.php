<?php
class ZeroAction extends CommonAction{
    /*
        * 属性
        */
    public function attribute(){
        $where = ' 1 = 1 '; // 搜索条件
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索
        $model = D('Zeroelementattribute');
        $count = $model->where($where)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $goodsTypeList = D("Zeroelementtype")->select();
        foreach ($goodsTypeList as $k => $v) {
            $ss[$v[id]]=$v[name];
        }
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeLists',$ss);
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = D("Zeroelementtype")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    //添加--编辑属性
    public function addAttribute(){
        $model = D("Zeroelementattribute");
        $type = $_POST['attr_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $_POST['attr_values'] = str_replace('_', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = str_replace('@', '', $_POST['attr_values']); // 替换特殊字符
        $_POST['attr_values'] = trim($_POST['attr_values']);

        if(IS_POST){
            $model->create();
            $attr_id=I('attr_id');
            $type_id=I('type_id');

            if ($attr_id)  {
                $model->save(); // 写入数据到数据库
                $this->tuSuccess("更新成功!!!",U('zero/attribute'));
            }
            else{
                $insert_id = $model->add(); // 写入数据到数据库
                $this->tuSuccess("添加成功!!!",U('zero/attribute'));
            }



        }
        // 点击过来编辑时
        $_GET['attr_id'] = $_GET['attr_id'] ? $_GET['attr_id'] : 0;
        $goodsTypeList = D("Zeroelementtype")->select();
        $goodsAttribute = $model->find($_GET['attr_id']);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('goodsAttribute',$goodsAttribute);
        $this->display('goodsAttribute');
    }

    //删除
    public function delAttribute($attr_id = 0){
        if (is_numeric($attr_id) && ($attr_id = (int) $attr_id)) {
            //D('TpGoodsAttr')->judge_goods_attr($attr_id);//判断商品属性暂时取消

            $obj = D('Zeroelementattribute');
            $obj->delete($attr_id);
            $this->tuSuccess('删除成功', U('zero/attribute'));
        }
        $this->tuError('请选择要删除的规格');

    }

    /*
     * 规格
     */
    public function spec(){
        $goodsTypeList = D("Zeroelementtype")->select();
        $type_id = I('audit');
        if(!empty($type_id)){
            $where = array('type_id'=>$type_id);
        }else{
            $where = ' 1 = 1 '; // 搜索条件
        }
        $model = D('Zeroelementspec');
        $count = $model->where($where)->count();
        import('ORG.Util.Page');
        $Page = new Page($count,13);
        $show = $Page->show();
        $specList = $model->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($specList as $k => $v){
            $model = D('Zeroelementspecitem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $goodsTypeList = D("Zeroelementtype")->select();
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('type_id',$type_id);
        $this->display();
    }

    //添加规格
    public function addSpec(){
        $model = D("Zeroelementspec");
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
                if($TpSpec = D("Zeroelementspec")->where(array('name'=>$data['name'],'type_id'=>$data['type_id']))->find()){
                    $this->tuError('名称【'.$data['name'].'】重复');
                }
            }
            if ($type == 2){

                $data['id']=$uid;
                $model->save($data); //更新数据库
                //$lastid =$this->afterSave($_POST['id']);
                $this->action($uid,'');
                $this->tuSuccess('编辑成功', U('zero/spec'));
            }
            else{
                $insert_id = $model->add($data); // 写入数据到数据库
                //$this->afterSave($insert_id);
                $this->action($uid,$insert_id);
                $this->tuSuccess('添加成功', U('zero/spec'));
            }
        }
        // 点击过来编辑时
        $id = $_GET['id'] ? $_GET['id'] : 0;
        $spec = $model->find($id);
        $items = $this->getSpecItem($id);
        $this->assign('spec',$spec);
        $this->assign('items',$items);
        $this->assign('uid',$id);
        $goodsTypeList = D("Zeroelementtype")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display('addspecs');
    }

    //编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = D('Zeroelementspecitem');
        $arr = $model->where("spec_id = $spec_id")->order('id')->select();
        //$arr = get_id_val($arr, 'id','item');
        return $arr;
    }

    public function action($uid,$aa){
        if(empty($aa))
        {
            $data = $this->_post('data', false);
            $new = $this->_post('new', false);
            $obj = D('Zeroelementspecitem');
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
            $obj = D('Zeroelementspecitem');
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

    //删除
    public function delSpec($id=0){
        if (is_numeric($id) && ($id = (int) $id)) {
            //D('TpGoodsAttr')->judge_goods_attr($attr_id);//判断商品属性暂时取消
            $obj = D('Zeroelementspec');
            $obj->where(array('id'=>$id))->delete();

            $this->tuSuccess('删除成功',  U('zero/spec'));
        }
        $this->tuError('请选择要删除的选项');

    }

    public function delGoodsSpecType($id = 0) {
        if ($id = (int) $id) {
            $obj = D('Zeroelementspecitem');
            $obj->delete($id);
            $uid = I('uid');
            $this->tuSuccess('删除成功', U('zero/addSpec',array('id'=>$uid)));
        }
        $this->tuError('请选择要删除的选项');
    }

    /*
     * 类型
     */
    public function type(){
        $model = D("Zeroelementtype");
        $count = $model->count();
        import('ORG.Util.Page');
        $Page  = new Page($count,100);
        $show  = $Page->show();
        $goodsTypeList = $model->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show',$show);

        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    //添加--编辑类型
    public function addType(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $model = D("Zeroelementtype");
        if(IS_POST){
            $model->create();
            $id = $_POST['id'];
            if(!empty($id)){
                $data['name']=I('name');
                $data['id']=$id;
                $model->save($data);
                $this->tuSuccess("编辑成功!",U('type'));
            }else{
                $model->add();
                $this->tuSuccess("添加成功!",U('type'));
            }
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('goodsType',$goodsType);
        $this->display('addTypes');
    }

    //删除类型
    public function delType($id=0){
        if (is_numeric($id) && ($id = (int) $id)) {
            $count = D('Zeroelementattribute')->where(array('type_id'=>$id))->count();
            if($count > 0){
                $this->tuError('该类型下有商品属性不得删除');
            }
            $obj = D('Zeroelementtype');
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('zero/type'));
        } else {
            $ids = $this->_post('id', false);
            if (is_array($ids)) {
                $obj = D('Zeroelementtype');
                foreach ($ids as $id){
                    $count = D('Zeroelementattribute')->where(array('type_id'=>$id))->count();
                    if($count > 0){
                        $this->tuError('该类型下有商品属性不得删除');
                    }
                    $obj->delete($id);
                }
                $this->tuSuccess('删除成功', U('zero/type'));
            }
            $this->tuError('请选择要删除的规格');
        }
    }
}