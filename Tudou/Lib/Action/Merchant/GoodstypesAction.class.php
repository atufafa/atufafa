<?php

class GoodstypesAction extends CommonAction {

    public function index(){
        $model = D("TpGoodsType");
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
    //回收站
    public function recycle(){
        $model = D("TpGoodsType");
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




    //编辑商品类型
    public  function goodsType(){
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $id =$_POST['id'];
        $model = M("TpGoodsType");
        if(IS_POST){
            $model->create();
            $id = $_POST['id'];
            if($id){
                $data['name']=I('name');
                $data['id']=$id;
                $model->save($data);
                $this->tuSuccess("编辑成功!",U('index'));
            }else{
                $data['shop_id']=$this->shop_id;
                $data['name']=I('name');
                $model->add($data);
                $this->tuSuccess("添加成功!",U('index'));
            }
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('id',$_GET['id']);
        $this->assign('goodsType',$goodsType);
        $this->display();
    }


    //商品属性列表
    public function goodsAttributeList(){

        $where = ' 1 = 1 '; // 搜索条件
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索
        $model = M('TpGoodsAttribute');
        $count = $model->where($where)->count();
        import('ORG.Util.Page'); // 导入分页类
        $Page       = new Page($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $goodsTypeList = M("TpGoodsType")->select();
        foreach ($goodsTypeList as $k => $v) {
            $ss[$v[id]]=$v[name];
        }
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeLists',$ss);
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = M("TpGoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->display();
    }


    //添加修改商品属性
    public  function addEditGoodsAttribute(){

        $model = D("TpGoodsAttribute");
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
                $this->tuSuccess("更新成功!!!",U('goodsAttributeList',array("type_id"=>$type_id)));
            }
            else{
                $insert_id = $model->add(); // 写入数据到数据库
                $this->tuSuccess("添加成功!!!",U('goodsAttributeList',array("type_id"=>$type_id)));
            }



        }
        // 点击过来编辑时
        $_GET['attr_id'] = $_GET['attr_id'] ? $_GET['attr_id'] : 0;
        $goodsTypeList = M("TpGoodsType")->select();
        $goodsAttribute = $model->find($_GET['attr_id']);
        $this->assign('goodsTypeList',$goodsTypeList);
        $this->assign('goodsAttribute',$goodsAttribute);
        $this->display('goodsAttribute');
    }



    //删除商品属性
    public function delGoodsAttribute($attr_id = 0) {
        if (is_numeric($attr_id) && ($attr_id = (int) $attr_id)) {

            //D('TpGoodsAttr')->judge_goods_attr($attr_id);//判断商品属性暂时取消

            $obj = D('TpGoodsAttribute');
            $obj->delete($attr_id);
            $this->tuSuccess('删除成功', U('goodsAttributeList',array('type_id'=>$_GET['oo'])));
        } else {
            $attr_ids = $this->_post('attr_id', false);
            if (is_array($attr_ids)) {
                //D('TpGoodsAttr')->judge_goods_attr($attr_id);//判断商品属性暂时取消
                $obj = D('TpGoodsAttribute');
                foreach ($attr_ids as $id) {
                    $obj->delete($id);
                }
                $this->tuSuccess('删除成功', U('goodsAttributeList',array('type_id'=>$_GET['oo'])));
            }
            $this->tuError('请选择要删除的规格');
        }
    }




    //删除商品类型
    public function delGoodsType($id = 0) {
        if($id = (int) $id){
            $obj = M('TpGoodsType');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=1;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('删除成功', U('goodstypes/index'));
        }
        //D('TpGoodsAttribute')->where(array("type_id"=>$id))->delete();
        //D('TpSpec')->where(array("type_id"=>$id))->delete();
        //D('TpSpecItem')->where(array("type_id"=>$id))->delete();
        //$obj = M('TpGoodsType');
        //$obj->delete($id);
    }
    //还原
    public function reduction($id = 0) {
        if($id = (int) $id){
            $obj = M('TpGoodsType');
            if(!$detail =$obj->find($id)){
                $this->tuError('类型不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('id'=>$id))->save($data);
            $this->tuSuccess('还原成功', U('goodstypes/index'));
        }
    }
}
