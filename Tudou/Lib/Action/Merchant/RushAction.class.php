<?php
class RushAction extends CommonAction{
    private $create_fields = array('cate_name', 'orderby', 'shop_id');

    /*
     * 规格
     */
    public function spec(){
        //$obj = D('Zeroelementspec');
        $ts = D('Tuanspec')->getTableName();
        $tgt = D('Tuantype')->getTableName();
        $tsi = D('Tuanspecitem')->getTableName();
        $where = array("$ts.shop_id"=>$this->shop_id,"$ts.recycle"=>array('EXP','IS NULL'),"b.shop_id"=>$this->shop_id,"b.recycle"=>array('EXP','IS NULL'));
        $count = D('Tuanspec')->where($where)
            ->join($tgt." as b on b.id = $ts.type_id")
            ->join($tsi." as c on c.spec_id = $ts.id")
            ->group("$ts.id")
            ->count();
        import('ORG.Util.Page');
        $Page = new Page($count,13);
        $show = $Page->show();
        $specList = D('Tuanspec')->where($where)
            ->field("$ts.id,$ts.name,b.name as bname")
            ->join($tgt." as b on b.id = $ts.type_id")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->group("$ts.id")
            ->order('id desc')
            ->select();
        foreach($specList as $k => $v){
            $model = D('Tuanspecitem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $this->display();
    }

    //添加规格
    public function addspec(){
        $model = D("Tuanspec");
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
                    $this->tuSuccess('编辑成功', U('rush/spec'));
                }
                else{
                    $insert_id = $model->add($data); // 写入数据到数据库
                    //$this->afterSave($insert_id);
                    $this->action($_POST['id'],$insert_id);
                    $this->tuSuccess('添加成功', U('rush/spec'));
                }
            }
            else
            {
                $this->tuSuccess('操作失败', U('rush/spec'));
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
        $goodsTypeList = D('Tuantype')->where(array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    //编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = D('Tuanspecitem');
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
            $obj = D('Tuanspecitem');
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
            $obj = D('Tuanspecitem');
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

    //删除编辑规格项
    public function del($id = 0) {
        if ($id = (int) $id) {
            $obj = D('Tuanspecitem');
            $obj->where(array("shop_id"=>$this->shop_id))->delete($id);
            $uid = I('uid');
            $this->tuSuccess('删除成功', U('rush/addspec',array('id'=>$uid)));
        }
        $this->tuError('请选择要删除的选项');
    }

    //删除规格
    public function delete($id=0){
        if($id = (int) $id){
            $obj = D('Tuanspec');
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
            $this->tuSuccess('删除成功', U('rush/spec'));
        }
    }

    //规格回收站
    public function delspec(){
        //$goodsTypeList = D('Tuantype')->select();
        $type_id = I('audit');
        if(!empty($type_id)){
            $where = array('type_id'=>$type_id);
        }else{
            $where = ' 1 = 1 ';
        }
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
        $obj = D('Tuanspec');
        $count = $obj->where($where)->count();
        import('ORG.Util.Page');
        $Page = new Page($count,13);
        $show = $Page->show();
        $specList = $obj->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($specList as $k => $v){
            $model = D('Tuanspecitem');
            $arr = $model->where("spec_id =$v[id]")->order('id')->select();
            $arr = get_id_val($arr, 'id','item');
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        $this->assign('specList',$specList);
        $this->assign('page',$show);
        $goodsTypeList = D('Tuantype')->select();
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('type_id',$type_id);
        $this->display();
    }

    //还原规格
    public function reductionspecifications($id=0){
        if($id = (int) $id){
            $obj = D('Tuanspec');
            if(!$detail =$obj->find($id)){
                $this->tuError('规格不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('还原成功', U('rush/spec'));
        }
    }


        /*
         * 分类
         */
    public function branch(){
        $model = D("Tuanshopcate");
        $where = array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL'));
        $count = $model->where($where)->count();
        import('ORG.Util.Page');
        $Page  = new Page($count,100);
        $show  = $Page->show();
        $goodsTypeList = $model->where($where)->order("orderby asc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show',$show);
        $this->assign('autocates', $goodsTypeList);
        $this->display();
    }

    //添加分类
    public function addbranch(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Tuanshopcate');
            $data['shop_id'] = $this->shop_id;
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('rush/branch'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }

    private function createCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->tuError('分类不能为空');
        }
        $detail = D('Tuanshopcate')->where(array('shop_id' => $this->shop_id, 'cate_name' => $data['cate_name']))->select();
        if (!empty($detail)) {
            $this->tuError('分类名称已存在');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    //编辑分类
    public function editbranch($cate_id = 0){
        if ($cate_id = (int) $cate_id) {
            $obj = D('Tuanshopcate');
            if (!($detail = $obj->find($cate_id))) {
                $this->tuError('请选择要编辑的商家分类');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->tuError('不可以修改别人的内容');
            }
            if ($this->isPost()) {
                $data = $this->createCheck();
                $data['cate_id'] = $cate_id;
                $data['shop_id'] = $this->shop_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('rush/branch'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的商家分类');
        }
    }

    //删除分类
    public function  delclass($cate_id=0){
        if($cate_id = (int) $cate_id){
            $obj = D('Tuanshopcate');
            if(!$detail =$obj->find($cate_id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=1;
            $obj->where(array('cate_id'=>$cate_id))->save($data);
            $this->tuSuccess('删除成功', U('rush/branch'));
        }
    }

    //分类回收站
    public function delbranch(){
        $obj = D("Tuanshopcate");
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
        $count = $obj->where($where)->count();
        import('ORG.Util.Page');
        $Page  = new Page($count,100);
        $show  = $Page->show();
        $goodsTypeList = $obj->where($where)->order("cate_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    //还原分类
    public function reductionclass($cate_id=0){
        if($cate_id = (int) $cate_id){
            $obj =D('Tuanshopcate');
            if(!$detail =$obj->find($cate_id)){
                $this->tuError('分类不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('cate_id'=>$cate_id))->save($data);
            $this->tuSuccess('还原成功', U('rush/branch'));
        }
    }

    /*
         * 属性
         */
    public function attr(){
        $where = array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL'));
        $model = D('Tuanattribute');
        $count = $model->where($where)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page       = new Page($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    //添加--编辑属性
    public function addattr(){
        $model = D("Tuanattribute");
        $type = $_POST['attr_id'] > 0 ? 2 : 1;
        $uid = $_POST['attr_id'];
        if(empty($this->shop_id)){
            $this->tuError('请重新登录');
        }
        if(IS_POST){
            $data['attr_name'] = $_POST['attr_name'];
            $data['type_id'] = $_POST['type_id'];
            if(!$data['attr_name']){
                $this->tuError('输入属性名称');
            }
            if(!$data['type_id']){
                $this->tuError('输入商品类型');
            }

            //一个框写入多个字段
            $data1 = $this->_post('data', false);
            $data2 = $this->_post('new', false);
            if(empty($data2)) {
                $marge = $data1;
            }elseif(empty($data1)){
                $marge = $data2;
            }else {
                $marge = array_merge($data1,$data2);
            }
            $ii = '';
            for($i=0;$i<=count($marge)+1;$i++){
                if($i==count($marge)+1){
                    $ii .=trim($marge[$i]['item']);
                }else{
                    $ii .=$marge[$i]['item'].PHP_EOL;
                }
            }
            $data['attr_input_type'] = $_POST['attr_input_type'];
            $data['type_id'] = $_POST['type_id'];
            if($data['attr_input_type']!=1){
                $ii='';
                if($data['attr_input_type']==1){
                    $data['attr_type']=1;
                }
            }
            if($this->shop_id){
                $data['shop_id'] = $this->shop_id;
                if ($type == 2){
                    $data['attr_values']=trim($ii);
                    $data['attr_id']=$uid;
                    $model->save($data); //更新数据库
                    $this->tuSuccess('编辑成功', U('rush/attr'));
                }
                else{
                    $data['attr_values']=trim($ii);
                    $insert_id = $model->add($data); // 写入数据到数据库
                    $this->tuSuccess('添加成功', U('rush/attr'));
                }
            }
            else
            {
                $this->tuSuccess('操作失败', U('rush/attr'));
            }
        }
        $attr_id = $_GET['attr_id'] ? $_GET['attr_id'] : 0;
        $spec = $model->find($attr_id);
        $list_items = explode(PHP_EOL, $spec['attr_values']);
        foreach ($list_items as $key => $val)  {
            $val = str_replace('_', '', $val); // 替换特殊字符
            $val = str_replace('@', '', $val); // 替换特殊字符
            $val = trim($val);
            if(empty($val))
                unset($list_items[$key]);
            else
                $list_items[$key] = $val;
        }
        //$items = $this->getSpecItem($attr_id);
        $this->assign('spec',$spec);
        $this->assign('list_items',$list_items);
        //$this->assign('items',$items);
        $this->assign('uid',$attr_id);
        $goodsTypeList = D('Tuantype')->where(array("shop_id"=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }

    //删除属性
    public function delattrs($attr_id=0){
        if($attr_id = (int) $attr_id){
            $obj = D('Tuanattribute');
            if(!$detail =$obj->find($attr_id)){
                $this->tuError('属性不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=1;
            $obj->where(array('attr_id'=>$attr_id))->save($data);
            //$obj->delete($attr_id);
            $this->tuSuccess('删除成功', U('rush/attr'));

            $this->tuError('请选择要删除的属性');
        }
    }

    //属性回收站
    public function delattr(){
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
        $model =D('Tuanattribute');
        $count = $model->where($where)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page       = new Page($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    //还原属性
    public function reductionattr($attr_id=0){
        if($attr_id = (int) $attr_id){
            $obj = D('Tuanattribute');
            if(!$detail =$obj->find($attr_id)){
                $this->tuError('属性不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('attr_id'=>$attr_id))->save($data);
            $this->tuSuccess('还原成功', U('rush/attr'));
        }
    }

    /*
          * 类型
          */
    public function type(){
        $model = D("Tuantype");
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

    //添加--编辑类型
    public function addtype(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $id =$_POST['id'];
        $model = D("Tuantype");
        if(IS_POST){
            $model->create();
            $id = $_POST['id'];
            if($id){
                $data['name']=I('name');
                $data['id']=$id;
                $model->save($data);
                $this->tuSuccess("编辑成功!",U('rush/type'));
            }else{
                $data['shop_id']=$this->shop_id;
                $data['name']=I('name');
                $model->add($data);
                $this->tuSuccess("添加成功!",U('rush/type'));
            }
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('id',$_GET['id']);
        $this->assign('goodsType',$goodsType);
        $this->display();
    }

    //删除类型
    public function deltypes($id = 0){
        if($id = (int) $id){
            $obj = D('Tuantype');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=1;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('删除成功', U('rush/type'));
        }
    }

    //类型回收站
    public function deltype(){
        $model = D("Tuantype");
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
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

    //还原类型
    public function reduction($id = 0){
        if($id = (int) $id){
            $obj =D('Tuantype');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('还原成功', U('rush/type'));
        }
    }

    //投诉
    public function complaint(){
        $obj = D('Tuancomplaint');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'colse'=>0,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //回复
    public function tousureply($id){
        $id = (int) $id;
        $where =array('id' => $id);
        $hf = D('Tuancomplaint')->where($where)->find();

        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){
                $data=array('id'=>$id,'sjcontent'=>$sjcontent);
                $con=D('Tuancomplaint')->where($where)->save($data);
                if($con>0){
                    $this->tuSuccess('回复成功', U('rush/complaint'));
                }else{
                    $this->tuError('回复失败');
                }
            }
            $this->tuError('请填写回复信息');
        }else{

            $this->assign('list', $hf);
            $this->display();

        }

    }







}