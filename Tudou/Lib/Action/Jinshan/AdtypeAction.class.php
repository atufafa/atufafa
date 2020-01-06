<?php
class AdtypeAction extends CommonAction{
    //显示
    public function index(){
        $obj = D('Adtype');
        import('ORG.Util.Page');
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword){
            $map['type_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //添加
    public function create(){
        if($this->isPost()){
            $name=I('post.type_name');
            $data=array();
            $data['type_name']=$name;
            $data['time']=NOW_TIME;

            $obj = D('Adtype');
            if($obj->add($data)){
                $this->tuSuccess('添加成功', U('adtype/index'));
            }
            $this->tuError('操作失败');
        }else{
            $this->display();
        }
    }

    //编辑
    public function edit($id = 0){
        if($id = (int) $id){
            $obj = D('Adtype');
            if(!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的广告');
            }
            if($this->isPost()){
                $name=I('post.type_name');
                $data=array();
                $data['type_name']=$name;
                $data['time']=NOW_TIME;
                $data['id'] = $id;
                if(false !== $obj->save($data)){
                    $this->tuSuccess('操作成功', U('adtype/index'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $detail);
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的广告');
        }
    }

    //删除
    public function delete($id = 0){
        if(is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Adtype');
            if(!($detail = $obj->find($id))){
                $this->tuError('请选择要删除的广告');
            }
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('adtype/index'));
        }else{
            $id = $this->_post('id', false);
            if(is_array($id)){
                $obj = D('Adtype');
                foreach($id as $ids){
                    $obj->delete($ids);
                }
                $this->tuSuccess('批量删除成功', U('adtype/index'));
            }
            $this->tuError('请选择要删除的广告');
        }
    }



}