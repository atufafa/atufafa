<?php
class MarketproductAction extends CommonAction{
    private $create_fields = array('product_name','end_time','is_tuan','is_yuyue','yuyue_time', 'details','desc', 'cate_id', 'photo', 'cost_price', 'price','tableware_price', 'is_new', 'is_hot', 'is_tuijian', 'create_time', 'create_ip','product_fen');
    private $edit_fields = array('product_name', 'is_tuan','is_yuyue','yuyue_time','details','desc', 'cate_id', 'photo', 'cost_price', 'price', 'tableware_price','is_new', 'is_hot', 'is_tuijian','product_fen');
    public function _initialize(){
        parent::_initialize();
        $getMarketCate = D('Market')->getMarketCate();
        $this->assign('getMarketCate', $getMarketCate);
        $this->market = D('Market')->find($this->shop_id);
        if(!empty($this->market) && $this->market['audit'] == 0) {
            $this->error('亲，您的申请正在审核中');
        }
        if(empty($this->market) && ACTION_NAME != 'apply'){
            $this->error('您还没有入住菜市场频道', U('market/apply'));
        }
        $this->assign('market', $this->market);
        $this->marketcates = D('Marketcate')->where(array('shop_id' => $this->shop_id, 'closed' => 0))->select();
        $this->assign('marketcates', $this->marketcates);
        $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$this->shop_id,'type'=>goods,'closed'=>0))->select());//快递
        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
    }
    public function index(){
        $obj = D('Marketproduct');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids = array();
        foreach ($list as $k => $val) {
            if ($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if ($cate_ids) {
            $this->assign('cates', D('Marketcate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function create(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $config = D('Setting')->fetchAll();
            $obj = D('Marketproduct');
            if($data['product_fen'] <$config['site']['product_fen']){
                $this->tuError('分佣比例最低为'.$config['site']['product_fen'].'%');
            }

            $details = $this->_post('details', 'SecurityEditorHtml');
            if($words = D('Sensitive')->checkWords($details)){
                $this->tuError('详细内容含有敏感词：' . $words);
            }

            if ($obj->add($data)) {
                D('Marketcate')->updateNum($data['cate_id']);
                $this->tuSuccess('添加成功', U('marketproduct/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('商品介绍不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
        $res = D('Marketcate')->where(array('cate_id'=>$data['cate_id']))->find();
        if($res['parent_id'] == 0){
            $this->tuError('请选择二级分类');
        }
         
         
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }
        if($data['cost_price']){
            if($data['price'] >= $data['cost_price']){
                $this->tuError('售价不能高于原价');
            }
        }

        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Market')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到菜市场设置开启支持预约购买后，再上传预约产品');
        }
        if($data['is_yuyue']==1 && empty($data['yuyue_time'])){
            $this->tuError('请填写发货时间');
        }
        $data['is_tuan'] = (int) $data['is_tuan'];
        if($data['is_tuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }


        $data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->market['rate'] / 1000);

        if(false == D('Marketproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
            $this->tuError(D('Marketproduct')->getError());//检测餐具费合理性
        }
        
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['audit'] = 0;
        $now = date('Y-m-d H:i:s',time());
        $data['end_time']=strtotime(date("Y-m-d H:i:s",strtotime("+1years",strtotime($now))));
        $data['product_fen'] = (int)$data['product_fen'];
        return $data;
    }
    public function edit($product_id = 0){
        if ($product_id = (int) $product_id) {
            $obj = D('Marketproduct');
            if (!($detail = $obj->find($product_id))) {
                $this->tuError('请选择要编辑的商品管理');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->tuError('请不要操作其他商家的商品管理');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['product_id'] = $product_id;
                if($data['product_fen'] <1){
                    $this->tuError('分佣比例最低为1%');
                }
                $details = $this->_post('details', 'SecurityEditorHtml');
                if($words = D('Sensitive')->checkWords($details)){
                    $this->tuError('详细内容含有敏感词：' . $words);
                }
                if (false !== $obj->save($data)) {
                    D('Marketcate')->updateNum($data['cate_id']);
                    $this->tuSuccess('操作成功', U('marketproduct/index'));
                }
                $this->tuError('操作失败');
            }else{
                $config = D('Setting')->fetchAll();
                $this->assign('config',$config);
                $this->assign('parent_id',$parent_id = D('Marketcate')->getParentsId($detail['cate_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商品管理');
        }
    }
    
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuError('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuError('商品介绍不能为空');
        }
        
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
        $res = D('Marketcate')->where(array('cate_id'=>$data['cate_id']))->find();
        if($res['parent_id'] == 0){
            $this->tuError('请选择二级分类');
        }
         
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price'] );
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }
        if($data['cost_price']){
            if($data['price'] >= $data['cost_price']){
                $this->tuError('售价不能高于原价');
            }
        }

        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $shop=D('Market')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到菜市场设置开启支持预约购买后，再上传预约产品');
        }
        if($data['is_yuyue']==1 && empty($data['yuyue_time'])){
            $this->tuError('请填写发货时间');
        }

        $data['is_tuan'] = (int) $data['is_tuan'];
        if($data['is_tuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }


        $data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->market['rate'] / 1000);
        if(false == D('Marketproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
            $this->tuError(D('Marketproduct')->getError());//检测餐具费合理性
        }
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        return $data;
    }
    public function dmarkette($product_id = 0){
        if (is_numeric($product_id) && ($product_id = (int) $product_id)) {
            $obj = D('Marketproduct');
            if (!($detail = $obj->where(array('shop_id' => $this->shop_id, 'product_id' => $product_id))->find())) {
                $this->tuError('请选择要删除的商品管理');
            }
            D('Marketcate')->updateNum($detail['cate_id']);
            $obj->save(array('product_id' => $product_id, 'closed' => 1));
            $marketgoods=M('goods_ele_store_market')->where(['type'=>3,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->find();
            if(!empty($marketgoods)){
                M('goods_ele_store_market')->where(['type'=>3,'product_id'=>$product_id,'shop_id'=>$this->shop_id])->delete();
            }
            $this->tuSuccess('删除成功', U('marketproduct/index'));
        }
        $this->tuError('请选择要删除的商品管理');
    }
}