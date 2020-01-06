<?php
class StoreactivityAction extends CommonAction
{

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $config = D('Setting')->fetchAll();
        $is_ipen = $config['store']['is_open'];
        if ($is_ipen == 0) {
            $this->tuError('此活动暂未开放');
        }

        $Shopping = D('GoodsEleStoreMarket')->where(['shop_id' => $this->shop_id, 'type' => 2, 'type_id' => 4, 'audit' => 1, 'closed' => 0])->select();
        $this->assign('shopping', $Shopping);
    }
    private $create_fields = array('type_id','time_number','type2_id','is_shop','is_receive','time_id','spike_time','end_times', 'shop_id','product_id','type',  'price',  'end_date', 'num','link_url');

    //列表
    public function index(){
        $Activity = D('GoodsEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'shop_id'=>$this->shop_id,'type'=>2);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $count = $Activity->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Activity->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type_id =$product_id=$time_ids= array();
        foreach ($list as $key => $val) {
            $type_id[$val['type_id']] = $val['type_id'];
            $product_id[$val['product_id']] =$val['product_id'];
            $time_ids[$val['time_id']]=$val['time_id'];
        }
        $this->assign('type', D('ActivityEleStoreMarket')->where(['type'=>'store'])->itemsByIds($type_id));
        $this->assign('time',D('TimeEleStoreMarket')->where(['type'=>2])->itemsByIds($time_ids));
        $tuanlist = D('Storeproduct')->itemsByIds($product_id);
        $this->assign('goods',$tuanlist);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //参与活动
    public function create(){
        if ($this->isPost()) {

            $data = $this->createCheck();
            $obj = D('GoodsEleStoreMarket');
            $cunz=$obj->where(['product_id'=>$data['product_id'],'type_id'=>$data['type_id'],'closed'=>0,'audit'=>1,'type'=>2])->find();
            if(!empty($cunz)){
                $this->tuError('该商品已存在该活动');
            }
            if ($obj->add($data)) {
                D('Storeproduct')->where(['shop_id'=>$this->shop_id,'product_id'=>$data['product_id']])
                    ->save(['price'=>$data['price'],'type_id'=>$data['type_id'],'num'=>$data['num'],'num1'=>$data['num'],'is_huodong'=>1]);
                $this->tuSuccess('添加成功', U('storeactivity/index'));
            }
            $this->tuError('操作失败');
        } else {

            $shop_id = $this->shop_id;
            $this->assign('shop_id', $shop_id);
            $tuanlist = D('Storeproduct')->where(array('shop_id' => $shop_id))->select();
            $this->assign('goods',$tuanlist);
            $this->assign('time',D('TimeEleStoreMarket')->where(['type'=>2])->select());
            $this->assign('type', D('ActivityEleStoreMarket')->where(['type'=>'store'])->select());
            $this->display();
        }
    }

    private function createCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['product_id'] = (int)$data['product_id'];
        if (empty($data['product_id'])) {
            $this->tuError('活动商品不能为空');
        }
        $data['type_id'] = (int)$data['type_id'];
        if (empty($data['type_id'])) {
            $this->tuError('类型ID不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        $data['price'] = (float)$data['price'];
        if (empty($data['price'])) {
            $this->tuError('价格不能为空');
        }

        $data['type2_id'] = (int) $data['type_id'];
        $data['is_shop'] = (int) $data['is_shop'];

        //天天特价
        $data['time_id'] = htmlspecialchars($data['time_id']);
        if($data['type_id']==4 && $data['time_id']==0){
            $this->tuError('请选择开售时间');
        }

        $timenum=D('TimeEleStoreMarket')->where(['type'=>2,'time_id'=>$data['time_id']])->find();
        $data['time_number']=$timenum['time_name'];

        //限时秒杀
        $data['end_times'] = (int) $data['end_times'];
        if($data['type_id']==5 && empty($data['end_times'])){
            $this->tuError('请输入小时数');
        }
        if($data['type_id']==5){
            $hourtime = date("Y-m-d H:i:s", strtotime("+".$data['end_times']."hour"));
            $type_endtime=strtotime($hourtime);
            $data['spike_time']=$type_endtime;
        }else{
            $data['spike_time']=0;
        }
        if($data['type_id'] ==5){
            if (!ereg("^[0-9]{1,3}[.][8]$", $data['price'])) {
                $this->tuError('小数点后一位必须为8');
            }
        }
        //限量团购
        $data['num'] = (int) $data['num'];
        if($data['type_id']==6 && empty($data['num'])){
            $this->tuError('请选择售卖份数');
        }
        if($data['type_id']==4 && empty($data['num'])){
            $this->tuError('请选择售卖份数');
        }
        $data['link_url'] = htmlspecialchars($data['link_url']);
        $time=htmlspecialchars($data['end_date']);
        $end=str_replace("-","",$time);
        $yan=strtotime($end);
        if($yan<time()){
            $this->tuError('请选择正确时间');
        }
        $data['end_date'] = $yan;
        if (empty($data['end_date'])) {
            $this->tuError('活动结束时间不能为空');
        }
        $data['is_receive'] = (int) $data['is_receive'];
        //查询当前可下架时间
        $config=D('Setting')->fetchAll();
        $xait=$config['store']['xaitime'];
        $hourtime = date("Y-m-d", strtotime("+".$xait."hour"));
        $datetime=strtotime($hourtime);
        $data['xaitime']=$datetime;
        $data['create_time'] = NOW_TIME;
        $data['audit'] = 0;
        $data['type']=2;
        $data['create_ip'] = get_client_ip();
        
        return $data;
    }

    //删除
    public function delete($id = 0){
        $id = (int) $id;
        if(!empty($id)){
            $obj = D('GoodsEleStoreMarket');
            if(!$detail = $obj->find($id)){
                $this->tuError('删除的活动不存在');
            }
            if($detail['xaitime']>time()){
                $this->tuError('当前未满足下架时间');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('请不要非法操作');
            }
            $obj->where(['id'=>$id])->save(['closed'=>1]);
            D('Storeproduct')->where(['product_id'=>$detail['product_id'],'shop_id'=>$this->shop_id])
                ->save(['closed'=>1,'type_id'=>0,'num'=>0,'num1'=>0,'is_huodong'=>0]);
            $this->tuSuccess('删除成功,该产品以下架', U('storeactivity/index'));
        }else{
            $this->tuError('请选择要删除的活动');
        }
    }

    //发放免费够物卷
    public function shopping(){
        $obj=D('ShoppingEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'shop_id'=>$this->shop_id,'type'=>2);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //发放购物券
    public function shopcreate(){
        $config = D('Setting')->fetchAll();
        if($this->ispost()){
            $obj=D('ShoppingEleStoreMarket');
            $datail=$obj->where(['shop_id'=>$this->shop_id,'audit'=>1,'close'=>0,'type'=>2])->select();
            if(!empty($datail)){
                $this->tuError('您发布的抢购卷未过期，不能重复发布');
            }
            $num=I('post.num');
            $end=$config['store']['shopping_time'];
            $times=date("Y-m-d",strtotime('+'.$end.'day'));
            $data=array();
            $data['num']=$num;
            $data['shop_id']=$this->shop_id;
            $data['create_time']=NOW_TIME;
            $data['end_time']=$times;
            //$data['audit']=1;
            $data['create_ip']=get_client_ip();
            $data['type']=2;
            if($obj->add($data)){
                $this->tuSuccess('发布成功', U('storeactivity/shopping'));
            }
            $this->tuError('发布失败');
        }else{
            $this->assign('config',$config);
        }
        $this->display();
    }

    //领取日志
    public function shoppinglist(){
        $obj=D('CollarEleStoreMarket');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'shop_id'=>$this->shop_id);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids=array();
        foreach ($list as $val){
            $user_ids[]=$val['user_id'];
        }
        $this->assign('user',D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function del($id){
        $id = (int) $id;
        if(!empty($id)){
            $obj = D('CollarEleStoreMarket');
            if(!$detail = $obj->find($id)){
                $this->tuError('删除的活动不存在');
            }
            if($detail['shop_id'] != $this->shop_id){
                $this->tuError('请不要非法操作');
            }
            $obj->where(['id'=>$id])->save(['closed'=>1]);
            $this->tuSuccess('删除成功', U('storeactivity/shoppinglist'));
        }else{
            $this->tuError('请选择要删除的活动');
        }
    }

}