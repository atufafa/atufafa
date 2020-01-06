<?php
class MarketactivitysAction extends CommonAction{

    private $create_fields = array('cate_name');
    private $create_field = array('time_name');
    private $edit_field = array('type_id','price','num','is_shop','time_id','is_receive');
//活动类型
    public function typelist(){
        $Activitycate = D('ActivityEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('type'=>'market');
        if ($cate_name = $this->_param('cate_name', 'htmlspecialchars')) {
            $map['cate_name'] = array('LIKE', '%' . $cate_name . '%');
            $this->assign('cate_name', $cate_name);
        }
        $count = $Activitycate->where($map)->count();
        $Page = new Page($count, 10);
        $pager = $Page->show();
        $list = $Activitycate->where($map)->order(array('type_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $pager);
        $this->display();
    }

//添加类型
    public function addtype(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('ActivityEleStoreMarket');
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->tuSuccess('添加成功', U('marketactivitys/typelist'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->tuError('活动类型不能为空');
        }
        $data['type'] = 'market';
        return $data;
    }
//编辑类型
    public function edittype($type_id){
        if ($type_id = (int) $type_id) {
            $obj = D('ActivityEleStoreMarket');
            if (!($detail = $obj->find($type_id))) {
                $this->tuError('请选择要编辑的活动类型');
            }
            if ($this->isPost()) {
                $data = $this->createCheck();
                $data['type_id'] = $type_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('marketactivitys/typelist'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的活动类型');
        }
    }

//删除类型
    public function deltype($type_id){
        if (is_numeric($type_id) && ($type_id = (int) $type_id)) {
            $obj = D('ActivityEleStoreMarket');
            $obj->delete($type_id);
            $this->tuSuccess('删除成功', U('marketactivitys/typelist'));
        }
        $this->tuError('请选择要删除的活动类型');
    }

//活动列表
    public function index(){
        $Eledianping = D('GoodsEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'type'=>3);
        $count = $Eledianping->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Eledianping->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type_ids = $shop_ids = $goods_ids=$time_ids=array();
        foreach ($list as  $val) {
            $type_ids[]=$val['type_id'];
            $shop_ids[]=$val['shop_id'];
            $goods_ids[]=$val['product_id'];
            $time_ids[]=$val['time_id'];
        }
        $this->assign('type',D('ActivityEleStoreMarket')->where(['type'=>'market'])->itemsByIds($type_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('goods',D('Marketproduct')->itemsByIds($goods_ids));
        $this->assign('start',D('TimeEleStoreMarket')->where(['type'=>3])->itemsByIds($time_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

//编辑
    public function edit($id){
        if ($id = (int) $id) {
            $obj=D('GoodsEleStoreMarket');
            if (!($detail = $obj->find($id))) {
                $this->tuError('请选择要编辑的信息');
            }
            if ($this->isPost()) {
                $data = $this->editsCheck();
                $data['id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('marketactivitys/index'));
                }
                $this->tuError('操作失败');
            } else {
                $detail=$obj->where(['id'=>$id])->find();
                $this->assign('detail',$detail);
                $this->assign('type',D('ActivityEleStoreMarket')->where(['type'=>'market'])->select());
                $this->assign('start',D('TimeEleStoreMarket')->where(['type'=>3])->select());
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的活动类型');
        }
    }

    private function editsCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_field);
        $data['type_id'] = (int) $data['type_id'];
        if (empty($data['type_id'])) {
            $this->tuError('活动类型不能为空');
        }
        $data['price'] = (float)$data['price'];
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }
        //天天特价
        $data['time_id'] = htmlspecialchars($data['time_id']);
        if($data['type_id']==7 && $data['time_id']==0){
            $this->tuError('请选择开售时间');
        }
        $data['is_receive'] = (int) $data['is_receive'];

        if($data['type_id'] ==8){
            if (!ereg("^[0-9]{1,3}[.][8]$", $data['price'])) {
                $this->tuError('小数点后一位必须为8');
            }
        }
        //限量团购
        $data['num'] = (int) $data['num'];
        if($data['type_id']==9 && empty($data['num'])){
            $this->tuError('请选择售卖份数');
        }
        if($data['type_id']==7 && empty($data['num'])){
            $this->tuError('请选择售卖份数');
        }
        $data['is_shop'] = (int) $data['is_shop'];


        return $data;
    }

    //审核
    public function shenhe($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('GoodsEleStoreMarket');
            $obj->where(['id'=>$id])->save(['audit'=>1]);
            $this->tuSuccess('审核成功', U('marketactivitys/index'));
        }
        $this->tuError('请选择要审核的信息');
    }

    //删除
    public function del($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('GoodsEleStoreMarket');
            $obj->where(['id'=>$id])->save(['closed'=>1]);
            $this->tuSuccess('删除成功', U('marketactivitys/index'));
        }
        $this->tuError('请选择要删除的信息');
    }


    /*
     * 天天特价
     */
    public function timeindex()
    {
        $Activitycate = D('TimeEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('type'=>'3');
        if ($cate_name = $this->_param('time_name', 'htmlspecialchars')) {
            $map['time_name'] = array('LIKE', '%' . $cate_name . '%');
            $this->assign('cate_name', $cate_name);
        }
        $count = $Activitycate->where($map)->count();
        $Page = new Page($count, 10);
        $pager = $Page->show();
        $list = $Activitycate->where($map)->order(array('time_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $pager);
        $this->display();
    }

    //添加时间
    public function addtime(){
        if ($this->isPost()) {
            $data = $this->createtimeCheck();
            $obj=D('TimeEleStoreMarket');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('marketactivitys/timeindex'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }

    private function createtimeCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_field);
        $data['time_name'] = htmlspecialchars($data['time_name']);
        if (empty($data['time_name'])) {
            $this->tuError('时间段不能为空');
        }
        $data['type'] =3;
        return $data;
    }

    //编辑时间
    public function edittime($time_id){
        if ($time_id = (int) $time_id) {
            $obj = D('TimeEleStoreMarket');
            if (!($detail = $obj->find($time_id))) {
                $this->tuError('请选择要编辑的时间段');
            }
            if ($this->isPost()) {
                $data = $this->createtimeCheck();
                $data['time_id'] = $time_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('marketactivitys/timeindex'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的时间段');
        }
    }

    //删除时间
    public function deltime($time_id){
        if (is_numeric($time_id) && ($time_id = (int) $time_id)) {
            $obj = D('TimeEleStoreMarket');
            $obj->delete($time_id);
            $this->tuSuccess('删除成功', U('marketactivitys/timeindex'));
        }
        $this->tuError('请选择要删除的时间段');
    }

}