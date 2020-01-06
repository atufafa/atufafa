<?php
class MembervipAction extends CommonAction{
    private $create_fields = array('title','intro','shoplx','guige', 'num','kuaidi_id','weight','kuaidi_id', 'photo', 'cate_id', 'price', 'sold_num', 'orderby', 'views', 'instructions', 'details', 'end_date', 'orderby','is_backers');
    private $create_kuaidi = array('type','shop_id','name', 'tel','audit','express_code');
    private $listcreate_fields = array('type','shop_id','name', 'tel','shouzhong','xuzhong','province_id');
    public function _initialize(){
        parent::_initialize();
        $this->assign('provinceList', $provinceList = D('Paddlist') -> where(array('level' => array('IN',array(1,2)))) -> select());
    }
    

    //商品列表
    public function goods(){
        $Goods = D('ExchangeGoods');
        import('ORG.Util.Page');
        $map = array();
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
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
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function create(){
        if($this->isPost()){
            $data = $this->createCheck();
            $obj = D('ExchangeGoods');
            if($goods_id = $obj->add($data)){
                $wei_pic = D('Weixin')->getCode($goods_id, 3); //购物类型是3
                $obj->save(array('goods_id'=>$goods_id,'wei_pic'=>$wei_pic));
                $photos = $this->_post('photos', false);
                if(!empty($photos)){
                    D('ExchangeGoodsPhotos')->upload($goods_id, $photos);
                }

                $this->shuxin($goods_id);//更新商品库存
                $this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性

                $this->tuSuccess('添加成功', U('membervip/goods'));
            }
            $this->tuError('操作失败');
        }else{
            $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>-1,'type'=>goods))->select());
            $this->assign('goodsInfo',D('ExchangeGoods')->where('goods_id='.I('GET.id',0))->find());  // 商品详情
            $this->assign('goodsType',D("ExchangeGoodsType")->select());
            $this->display();
        }
    }

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if(empty($data['title'])){
            $this->tuError('产品名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        $data['guige'] = htmlspecialchars($data['guige']);
        $data['num'] = (int) $data['num'];
        if(empty($data['num'])) {
            $this->tuError('库存不能为空');
        }

        $data['kuaidi_id'] = (int) $data['kuaidi_id'];

        if (empty($data['kuaidi_id'])) {
            $this->tuError('运费模板不能为空');
        }
        $data['weight'] = (int) $data['weight'];
        if (empty($data['weight'])){
            $this->tuError('重量不能为空');
        }

        $data['goods_type'] = I('goods_type');

        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if(!isImage($data['photo'])){
            $this->tuError('缩略图格式不正确');
        }
        $data['price'] = (float) ($data['price']);
        if(empty($data['price'])){
            $this->tuError('市场价格不能为空');
        }
        $data['views'] = (int) $data['views'];
        $data['instructions'] = SecurityEditorHtml($data['instructions']);
        if($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        if(empty($data['details'])){
            $this->tuError('商品详情不能为空');
        }
        if($words = D('Sensitive')->checkWords($data['details'])){
            $this->tuError('商品详情含有敏感词：' . $words);
        }
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if(empty($data['end_date'])){
            $this->tuError('过期时间不能为空');
        }
        if(!isDate($data['end_date'])){
            $this->tuError('过期时间格式不正确');
        }
        $data['sold_num'] = (int) $data['sold_num'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int) $data['orderby'];
        $data['is_mall'] = 1;

        return $data;
    }

    //编辑商品
    public function edit($goods_id = 0){
        if($goods_id = (int) $goods_id){
            $obj = D('ExchangeGoods');
            if(!$detail = $obj->find($goods_id)){
                $this->tuError('请选择要编辑的商品');
            }
            if($this->isPost()){
                $data = $this->createCheck();
                $data['goods_id'] = $goods_id;
                if(!empty($detail['wei_pic'])){
                    if(true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($goods_id, 3);
                        $data['wei_pic'] = $wei_pic;
                    }
                }else{
                    $wei_pic = D('Weixin')->getCode($goods_id,3);
                    $data['wei_pic'] = $wei_pic;
                }
                if(false !== $obj->save($data)){
                    $photos = $this->_post('photos', false);
                    if(!empty($photos)){
                        D('ExchangeGoodsPhotos')->upload($goods_id, $photos);
                    }

                    $this->shuxin($goods_id);//更新商品库存
                    $this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性

                    $this->tuSuccess('操作成功', U('membervip/goods'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $obj->_format($detail));
                $this->assign('photos', D('ExchangeGoodsPhotos')->getPics($goods_id));
                $goodsInfo=D('ExchangeGoods')->where('goods_id='.I('GET.goods_id',0))->find();
                $this->assign('goodsInfo',$goodsInfo);
                $this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>-1,'type'=>goods))->select());
                $this->assign('goodsType',D("ExchangeGoodsType")->select());
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商品');
        }
    }

    public function shuxin($goods_id){
        if($_POST['item']){
            $spec = M('ExchangeSpec')->getField('id,name');
            $specItem = M('ExchangeSpecItem')->getField('id,item');

            $specGoodsPrice = M("ExchangeSpecGoodsPrice");
            $specGoodsPrice->where('goods_id = '.$goods_id)->delete();
            foreach($_POST['item'] as $k => $v){
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']);
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
            }
            $specGoodsPrice->addAll($dataList);
        }
        refresh_stock($goods_id);

    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = M('ExchangeSpec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = M('ExchangeSpecItem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = M('ExchangeSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        if($goods_id){
            $specImageList = M('ExchangeSpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2);

        $spec = M('ExchangeSpec')->getField('id,name');
        $specItem = M('ExchangeSpecItem')->getField('id,item,spec_id');

        $keySpecGoodsPrice = M('ExchangeSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,store_count,bar_code');

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        foreach ($clo_name as $k => $v) {
            $str .=" <td><b>{$spec[$v]}</b></td>";
        }
        $str .="<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
        foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";
        }
        $str .= "</table>";
        return $str;
    }


    //动态获取商品属性入框根据不同的数据返回不同的输入框类型
    public function ajaxGetAttrInput(){
        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
        $str = $this->getAttrInput($goods_id,$type_id);
        exit($str);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id){

        $attributeList = M('ExchangeGoodsAttribute')->where(array('type_id'=>$type_id))->select();
        foreach($attributeList as $key => $val){

            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
            //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; //加减符号
                //单选属性或者复选属性
                if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                    if($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";

                //手工录入
                if($val['attr_input_type'] == 0){
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                //从下面的列表中选择（一行代表一个可选值）
                if($val['attr_input_type'] == 1){
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                    foreach($tmp_option_val as $k2=>$v2){
                        //编辑的时候有选中值
                        $v2 = preg_replace("/\s/","",$v2);
                        if($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                }
                //多行文本框
                if($val['attr_input_type'] == 2){
                    $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                }
                $str .= "</td></tr>";
            }

        }
        return  $str;
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return M('ExchangeGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->select();
        if($goods_id > 0 && $attr_id > 0)
            return M('ExchangeGoodsAttr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type){


        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if($goods_type == 0)  {
            M('ExchangeGoodsAttr')->where(array('goods_id'=>$goods_id))->delete();
            return;
        }

        $GoodsAttrList = M('ExchangeGoodsAttr')->where(array('goods_id'=>$goods_id))->select();

        $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
        foreach($GoodsAttrList as $k => $v){
            $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
        }

        // post提交的属性以attr_id_和值的组合为键名
        $post_goods_attr = array();

        foreach($_POST as $k => $v){
            $attr_id = str_replace('attr_','',$k);
            if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2){
                $v2 = str_replace('_', '', $v2); //替换特殊字符
                $v2 = str_replace('@', '', $v2); //替换特殊字符
                $v2 = trim($v2);

                if(empty($v2))
                    continue;
                $tmp_key = $attr_id."_".$v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if(array_key_exists($tmp_key , $old_goods_attr)){
                    //如果这个属性原来就存在
                    if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){
                        //并且价格不一样就做更新处理
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        M('ExchangeGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));
                    }
                }else{
                    //否则这个属性 数据库中不存在 说明要做删除操作
                    M('ExchangeGoodsAttr')->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }
        }
        //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
        foreach($old_goods_attr as $k => $v){
            M('ExchangeGoodsAttr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
        }
    }

//商品上架下架
    public function update($goods_id = 0){
        if($goods_id = (int) $goods_id){
            if(!($detail = D('ExchangeGoods')->find($goods_id))){
                $this->tuError('请选择要操作的商品');
            }
            $data = array('closed' =>0,'goods_id' => $goods_id);
            $intro = '上架商品成功';
            if($detail['closed'] == 0){
                $data['closed'] = 1;
                $intro = '下架商品成功';
            }
            if(D('ExchangeGoods')->save($data)){
                $this->tuSuccess($intro, U('membervip/goods'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }


    public function delete($goods_id = 0){
        if(is_numeric($goods_id) && ($goods_id = (int) $goods_id)){
            $obj = D('ExchangeGoods');
            $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('membervip/goods'));
        }else{
            $goods_id = $this->_post('goods_id', false);
            if(is_array($goods_id)) {
                $obj = D('ExchangeGoods');
                foreach ($goods_id as $id){
                    $obj->save(array('goods_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('删除成功', U('membervip/goods'));
            }
            $this->tuError('请选择要删除的商家');
        }
    }

    /**
     * 平台快递模板
     */
    public function express(){
        $Pkuaidi = D('Pkuaidi');
        import('ORG.Util.Page');
        $map = array('type'=> goods,'shop_id'=>-1,'closed'=> 0);
        if ($keyword = $this -> _param('keyword', 'htmlspecialchars')) {
            $map['name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this -> assign('keyword', $keyword);
        }

        $count = $Pkuaidi-> where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $Pkuaidi -> order(array('id' => 'desc')) -> where($map)->limit($Page->firstRow . ',' . $Page->listRows) -> select();
        $this -> assign('list', $list);
        $this -> assign('page', $show);
        $this -> display();
    }

    //添加快递模板
    public function createkuaidi(){
        if ($this -> isPost()) {
            $data = $this -> createkCheck();
            $obj = D('Pkuaidi');
            if ($obj -> add($data)) {
                D('Logistics')->add(['shop_id'=>-1,'default'=>1,'express_com'=>'1','express_name'=>$data['name'],'closed'=>0,'create_time'=>NOW_TIME,'express_code'=>$data['express_code']]);
                $this -> tuSuccess('添加成功', U('membervip/express'));
            }
            $this -> tuError('操作失败');
        } else {
            $company = D('Logistics')->getCompany();
            // print_r($company);die;
            $this->assign('company',$company);
            $this -> display();
        }
    }

    private function createkCheck() {
        $data = $this -> checkFields($this -> _post('data', false), $this -> create_kuaidi);
        $data['type'] = goods;
        $data['shop_id'] = -1;
        $data['audit']=1;
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this -> tuError('运费模板不能为空');
        }
        $data['tel'] = (int)$data['tel'];
        return $data;
    }

    //编辑快递
    public function editkuaidi($kuaidi_id = 0) {
        if ($kuaidi_id = (int)$kuaidi_id) {
            $obj = D('Pkuaidi');
            if (!$detail = $obj -> find($kuaidi_id)) {
                $this -> tuError('请选择要编辑的运费模板');
            }
            if ($this -> isPost()) {
                $data = $this -> createkCheck();
                $data['id'] = $kuaidi_id;
                if (false !== $obj -> save($data)) {
                    $this -> tuSuccess('操作成功',  U('membervip/express'));
                }
                $this -> tuError('操作失败');
            } else {
                $this -> assign('detail', $detail);
                $this -> display();
            }
        } else {
            $this -> tuError('请选择要编辑的运费模板');
        }
    }

    //删除
    public function delkuaidi($kuaidi_id = 0) {
        if (is_numeric($kuaidi_id) && ($kuaidi_id = (int) $kuaidi_id)) {
            $obj = D('Pkuaidi');
            $obj->save(array('id' => $kuaidi_id, 'closed' => 1));
            $this->tuSuccess('审核成功', U('membervip/express'));
        } else {
            $kuaidi_id = $this->_post('kuaidi_id', false);
            if (is_array($kuaidi_id)) {
                $obj = D('Pkuaidi');
                foreach ($kuaidi_id as $id) {
                    $obj->save(array('id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('删除成功', U('membervip/express'));
            }
            $this->tuError('请选择要删除的运费模板');
        }
    }

    /**
     * 运费
     */
    public function distribution($kuaidi_id = 0){
        if ($kuaidi_id = (int)$kuaidi_id) {
            $lists = D('Pyunfei');
            import('ORG.Util.Page');
            $map = array('type'=> goods,'shop_id'=>-1);
            if ($keyword = $this -> _param('keyword', 'htmlspecialchars')) {
                $map['name'] = array('LIKE', '%' . $keyword . '%');
                $this -> assign('keyword', $keyword);
            }
            $map['kuaidi_id'] = $kuaidi_id;
            $count = $lists ->where($map)->count();
            $Page = new Page($count, 20);
            $show = $Page->show();
            $list = $lists -> order(array('yunfei_id' => 'desc')) -> where($map)->limit($Page->firstRow . ',' . $Page->listRows) -> select();
            $shop_ids = array();
            foreach ($list as $key => $val) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            $this -> assign('list', $list);
            $this -> assign('page', $show);
            $this -> assign('count', $count);
            $this -> assign('kuaidi_id', $kuaidi_id);
            $this -> display();
        } else {
            $this -> tuError('请选择运费模板');
        }
    }

    //添加
    public function listcreate($kuaidi_id = 0){
        if ($this -> isPost()) {
            $data = $this -> listcreateCheck();
            $obj = D('Pyunfei');
            $kuaidi_id = (int)$kuaidi_id;
            $data['kuaidi_id'] = $kuaidi_id;
            $province_ids = $this->_post('id');

            if($yunfei_id = $obj->add($data)){
                foreach ($province_ids as $val) {
                    if(!empty($val)) {
                        $datas['yunfei_id'] = $yunfei_id;
                        $datas['kuaidi_id'] = $kuaidi_id;
                        $datas['province_id'] = $val;
                        D('Pyunfeiprovinces')->add($datas);
                    }
                }
                $this->tuSuccess('添加成功', U('membervip/distribution',array('kuaidi_id' =>$kuaidi_id)));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('ids', D('Pyunfeiprovinces')->getIds($kuaidi_id));
            $this-> assign('kuaidi_id', $kuaidi_id);
            $this-> display();
        }
    }

    private function listcreateCheck() {
        $data = $this -> checkFields($this -> _post('data', false), $this -> listcreate_fields);
        $data['type'] = goods;
        $data['shop_id'] = -1;
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuError('名称不能为空');
        }
        $data['shouzhong'] = (float) ($data['shouzhong']);
        if (empty($data['shouzhong'])) {
            $this->tuError('首重价格不能为空');
        }
        $data['xuzhong'] = (float) ($data['xuzhong']);
        if (empty($data['xuzhong'])) {
            $this->tuError('续重价格不能为空');
        }
        if ($data['xuzhong'] >= $data['shouzhong'] ) {
            $this->tuError('续重价格不能大于首重');
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function listedit($yunfei_id = 0) {
        if ($yunfei_id = (int)$yunfei_id) {
            $obj = D('Pyunfei');
            if (!$detail = $obj -> find($yunfei_id)) {
                $this->tuError('没找到运费地区详细内容');
            }
            if ($this -> isPost()) {
                $data = $this -> listcreateCheck();
                $data['yunfei_id'] = $yunfei_id;
                $province_ids = $this->_post('id');
                if($obj->save($data)){
                    D('Pyunfeiprovinces')->delete(array('where' => "yunfei_id = '{$yunfei_id}'"));//先删除再更新
                    foreach ($province_ids as $val) {
                        if(!empty($val)) {
                            $datas['yunfei_id'] = $yunfei_id;
                            $datas['kuaidi_id'] = $detail['kuaidi_id'];
                            $datas['province_id'] = $val;
                            D('Pyunfeiprovinces')->add($datas);
                        }
                    }
                    $this->tuSuccess('修改成功', U('membervip/distribution',array('kuaidi_id' =>$detail['kuaidi_id'])));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('ids', D('Pyunfeiprovinces')->getIds2($yunfei_id));
                $this->assign('ids3', D('Pyunfeiprovinces')->getIds3($detail['kuaidi_id'],$yunfei_id));
                $this -> assign('detail', $detail);
                $this -> display();
            }
        } else {
            $this->tuError('请选择要编辑的运费设置');
        }
    }

    public function listdelete($yunfei_id = 0) {
        $yunfei_id = (int)$yunfei_id;
        $obj = D('Pyunfei');
        if(!$detail = $obj->find($yunfei_id)) {
            $this->tuError('没找到运费地区详细内容');
        }
        $obj->delete($yunfei_id);
        D('Pyunfeiprovinces')->delete(array('where' => "yunfei_id = '{$yunfei_id}'"));//删除这些
        $this-> uSuccess('删除【'.$detail['name'].'】成功', U('membervip/distribution',array('kuaidi_id' =>$detail['kuaidi_id'])));

    }

}