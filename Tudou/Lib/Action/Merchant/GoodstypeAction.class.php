<?php
class GoodstypeAction extends CommonAction {
    //规格列表
    public function index(){
        //$where = ' 1 = 1 '; // 搜索条件
        //I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索
        $where = array('shop_id'=>$this->shop_id,'recycle'=>array('EXP','IS NULL'));
        $model = M('TpGoodsAttribute');
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
    //回收站
    public function recycle(){
        $where = array('shop_id'=>$this->shop_id,'recycle'=>1);
        $model = M('TpGoodsAttribute');
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
    //修改编辑
    public  function spec(){
       $model = D("TpGoodsAttribute");
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
                 $this->tuSuccess('编辑成功', U('goodstype/index'));
             }
             else{
                 $data['attr_values']=trim($ii);
                 $insert_id = $model->add($data); // 写入数据到数据库
                 $this->tuSuccess('添加成功', U('goodstype/index'));
             }
         }
         else
         {
             $this->tuSuccess('操作失败', U('goodstype/index'));
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
       $goodsTypeList = M('TpGoodsType')->where(array("shop_id"=>$this->shop_id,'recycle'=>array('EXP','IS NULL')))->select();
       $this->assign('goodsTypeList',$goodsTypeList);
       $this->display();           
    }
	//删除规格项目
    public function delete($attr_id = 0) {
        if($attr_id = (int) $attr_id){
			$obj = D('TpGoodsAttribute');
			if(!$detail =$obj->find($attr_id)){
				$this->tuError('属性不存在');
			}
			if($detail['shop_id'] != $this->shop_id){
				$this->tuError('非法操作');
			}
            $data['recycle']=1;
            $obj->where(array('attr_id'=>$attr_id))->save($data);
            //$obj->delete($attr_id);
            $this->tuSuccess('删除成功', U('goodstype/index'));
           
            $this->tuError('请选择要删除的属性');
        }
    }
    //还原
    public function reduction($attr_id = 0) {
        if($attr_id = (int) $attr_id){
            $obj = D('TpGoodsAttribute');
            if(!$detail =$obj->find($attr_id)){
                $this->tuError('属性不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('非法操作');
            }
            $data['recycle']=null;
            $obj->where(array('attr_id'=>$attr_id))->save($data);
            $this->tuSuccess('还原成功', U('goodstype/index'));
        }
    }
	//编辑的时候执行
    public function getSpecItem($spec_id) {
        $model = M('TpSpecItem');
        $arr = $model->where(array('spec_id'=>$spec_id))->order('id')->select();
//        $arr = get_id_val($arr, 'id','item');
        return $arr;
    }
    //删除规格项
    public function delGoodsSpecType($id = 0) {
        if ($id = (int) $id) {
            $obj = D('TpSpecItem');
            $obj->delete($id);
            $uid = I('uid');
            $this->tuSuccess('删除成功', U('goodstype/spec',array('id'=>$uid)));
        }
        $this->tuError('请选择要删除的选项');
    }
}