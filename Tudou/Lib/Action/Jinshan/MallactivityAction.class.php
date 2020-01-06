<?php
class MallactivityAction extends CommonAction
{
    private $create_fields = array('type_name');
    private $create_time=array('time_name');
    private $create_fiel=array('title','intro','guige','is_reight','weight','yunfei','shop_id','photo','is_vs1','is_vs2','is_vs3','is_vs4','price','mall_price','num1','num','details');


    //显示类型
    public function index(){
        $obj=D('Mallactivity');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->order('type_id')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //添加类型
    public function addtype(){
        $obj = D('Mallactivity');
        if($this->isPost()){
            $data = $this->createCheck();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('mallactivity/index'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['type_name'] = htmlspecialchars($data['type_name']);
        if (empty($data['type_name'])) {
            $this->tuError('类型名称为空');
        }
        return $data;
    }

    //编辑类型
    public function edittype($type_id=0){
        if($type_id = (int) $type_id){
            $obj = D('Mallactivity');
            $detail = $obj->find($type_id);
            if (!($detail = $obj->find($type_id))){
                $this->tuError('请选择要编辑的活动类型');
            }
            if($this->isPost()){
                $data = $this->createCheck();
                $data['type_id'] = $type_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('mallactivity/index'));
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
    public function deltype($type_id=0){
        if (is_numeric($type_id) && ($type_id = (int) $type_id)) {
            $obj = D('Mallactivity');
            $obj->delete(array('type_id' => $type_id));
            $this->tuSuccess('删除成功', U('mallactivity/index'));
        }
    }

    //活动列表显示
    public function goodsindex(){
        $obj=D('MallactivityGoods');
        import('ORG.Util.Page');
        $map=array('colsed'=>0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order('create_time')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids=$type_ids=$time_ids=array();
        foreach ($list as $val){
            $shop_ids[]=$val['shop_id'];
            $type_ids[]=$val['type_id'];
            $time_ids[]=$val['time_id'];
        }

        $this->assign('shops',D('Shop')->itemsByIds($shop_ids));
        $this->assign('types',D('Mallactivity')->itemsByIds($type_ids));
        $this->assign('times',D('MallactivitySpecial')->itemsByIds($time_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }


    //审核信息
    public function shenhe($goods_id=0){
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('MallactivityGoods');
            $obj->where(array('goods_id' => $goods_id))->save(array('audit' => 1));
            $this->tuSuccess('审核成功', U('mallactivity/goodsindex'));
        }
        $this->tuError('审核失败');
    }

    //删除信息
    public function del($goods_id=0){
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('MallactivityGoods');
            $obj->where(array('goods_id' => $goods_id))->save(array('colsed' => 1));
            $this->tuSuccess('删除成功', U('mallactivity/goodsindex'));
        }
        $this->tuError('删除失败');

    }

    //时间显示
    public function timeindex(){
        $obj=D('MallactivitySpecial');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->order('time_id')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //添加时间段
    public function addtime(){
        $obj = D('MallactivitySpecial');
        if($this->isPost()){
            $data = $this->createtimeCheck();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('mallactivity/timeindex'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
    }

    private function createtimeCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_time);
        $data['time_name'] = htmlspecialchars($data['time_name']);
        if (empty($data['time_name'])) {
            $this->tuError('时间段为空');
        }
        return $data;
    }

    //编辑时间段
    public function edittime($time_id=0){
        if($time_id = (int) $time_id){
            $obj = D('MallactivitySpecial');
            $detail = $obj->find($time_id);
            if (!($detail = $obj->find($time_id))){
                $this->tuError('请选择要编辑的时间段');
            }
            if($this->isPost()){
                $data = $this->createCheck();
                $data['time_id'] = $time_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('mallactivity/timeindex'));
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

    //删除时间段
    public function deltime($time_id=0){
        if (is_numeric($time_id) && ($time_id = (int) $time_id)) {
            $obj = D('MallactivitySpecial');
            $obj->delete(array('time_id' => $time_id));
            $this->tuSuccess('删除成功', U('mallactivity/timeindex'));
        }
    }


    //平台商品显示
    public function indexgoods(){
        $Goods = D('AdminGoods');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    //平台添加商品
    public function addgoods(){
        if($this->isPost()){
            $data = $this->creCheck();
            $obj = D('AdminGoods');
            $objs = D('Goods');
            if($goodss = $objs->add($data)){
                $data['goods_id']=$goodss;
                $goods_id = $obj->add($data);
                $photos = $this->_post('photos', false);
                if(!empty($photos)){
                    D('AdminGoodsPic')->upload($goods_id, $photos);
                }
                $this->tuSuccess('添加成功', U('mallactivity/indexgoods'));
            }
            $this->tuError('操作失败');
        }else{
            $this->display();
        }
    }

    private function creCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fiel);
        $data['title'] = htmlspecialchars($data['title']);
        if(empty($data['title'])){
            $this->tuError('产品名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        $data['guige'] = htmlspecialchars($data['guige']);
        $data['num'] = (int) $data['num'];
        if(empty($data['num'])) {
            $this->tuError('虚拟库存不能为空');
        }
        $data['is_reight'] = (int) $data['is_reight'];
        $data['weight'] = (int) $data['weight'];
        $data['yunfei'] = (float) $data['yunfei'];
        if($data['is_reight'] == 1){
            if (empty($data['weight'])){
                $this->tuError('重量不能为空');
            }
        }

        if($data['is_reight'] == 1){
            if (empty($data['yunfei'])) {
                $this->tuError('运费不能为空');
            }
        }
        $data['shop_id'] = (int) $data['shop_id'];
        if(empty($data['shop_id'])){
            $this->tuError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if(empty($shop)){
            $this->tuError('请选择正确的商家');
        }

        $data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];

        $data['goods_type'] = I('goods_type');

        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if(!isImage($data['photo'])){
            $this->tuError('缩略图格式不正确');
        }
        $data['price'] = (float) $data['price'];
        if(empty($data['price'])){
            $this->tuError('市场价格不能为空');
        }
        $data['mall_price'] = (float) $data['mall_price'];
        if(empty($data['mall_price'])){
            $this->tuError('活动价格不能为空');
        }

        $data['details'] = SecurityEditorHtml($data['details']);
        if(empty($data['details'])){
            $this->tuError('商品详情不能为空');
        }
        if($words = D('Sensitive')->checkWords($data['details'])){
            $this->tuError('商品详情含有敏感词：' . $words);
        }

        $data['is_vs1'] = (int) $data['is_vs1'];
        $data['is_vs2'] = (int) $data['is_vs2'];
        $data['is_vs3'] = (int) $data['is_vs3'];
        $data['is_vs4'] = (int) $data['is_vs4'];
        $data['is_vs5'] = (int) $data['is_vs5'];
        $data['is_vs6'] = (int) $data['is_vs6'];
        $data['num1'] = (int) $data['num1'];
        if(empty($data['num1'])){
            $this->tuError('实际库存不能为空');
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int) $data['orderby'];

        return $data;
    }



    //平台编辑
    public function editgoods($goods_id){
        if($goods_id = (int) $goods_id){
            $obj = D('AdminGoods');
            if(!$detail = $obj->find($goods_id)){
                $this->tuError('请选择要编辑的商品');
            }
            if($this->isPost()){
                $data = $this->creCheck();
                $data['goods_id'] = $goods_id;
                $objs=D('Goods');
                $objs->save($data);
                if(false !== $obj->save($data)){
                    $photos = $this->_post('photos', false);
                    if(!empty($photos)){
                        D('AdminGoodsPic')->upload($goods_id, $photos);
                    }
                    $this->tuSuccess('操作成功', U('mallactivity/indexgoods'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $obj->_format($detail));
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->assign('photos', D('AdminGoodsPic')->getPics($goods_id));
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商品');
        }
    }

    //删除商品
    public function delgoods($goods_id){
        if($goods_id = (int) $goods_id){
            if(!($detail = D('AdminGoods')->find($goods_id))){
                $this->tuError('请选择要操作的商品');
            }
            D('Goods')->where(['goods_id'=>$goods_id])->delete();
            if(D('AdminGoods')->where(['goods_id'=>$goods_id])->delete()){
                $this->tuSuccess('删除成功', U('mallactivity/indexgoods'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }



}