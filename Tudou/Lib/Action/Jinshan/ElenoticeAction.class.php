<?php
class ElenoticeAction extends CommonAction{
    private $create_fields = array('title', 'photo', 'code', 'orderby');
    //显示列表
    public function index(){
        $obj = D('NoticeEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'type'=>1);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($site_id = (int) $this->_param('site_id')){
            $map['site_id'] = $site_id;
            $this->assign('site_id', $site_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //添加广告
    public function addnotice(){
        if($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('NoticeEleStoreMarket');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('elenotice/index'));
            }
            $this->tuError('操作失败');
        }
            $this->display();
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if(empty($data['title'])){
            $this->tuError('广告名称不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if(!empty($data['photo']) && !isImage($data['photo'])){
            $this->tuError('广告图片格式不正确');
        }
        $data['orderby'] = (int) $data['orderby'];
        $data['type'] = 1;
        $data['create_time'] = NOW_TIME;
        return $data;
    }

    //编辑广告
    public function editnotice($id){
        if($id = (int) $id){
            $obj = D('NoticeEleStoreMarket');
            if(!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的广告');
            }
            if($this->isPost()){
                $data = $this->createCheck();
                $data['id'] = $id;
                if(false !== $obj->save($data)){
                    $this->tuSuccess('操作成功', U('elenotice/index'));
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

    //删除广告
    public function delnotice($id){
        if(is_numeric($id) && ($id = (int) $id)) {
            $obj = D('NoticeEleStoreMarket');
            $detail = $obj ->where(array('id' => $id))->find();
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('elenotice/index'));
        }

    }


}