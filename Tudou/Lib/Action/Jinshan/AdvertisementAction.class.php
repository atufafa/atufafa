<?php
class AdvertisementAction extends CommonAction{
    private $create_fields = array('buy_time','buy_money','identification','num');
    //显示列表
    public function index(){
        $obj = D('Advertisement');
        import('ORG.Util.Page');
        $map = array('close' => 0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('create_time'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //添加
    public function add(){
        $obj = D('Advertisement');
        if($this->isPost()){
            $data = $this->createCheck();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('advertisement/index'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
    }

    public function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['buy_time'] = htmlspecialchars($data['buy_time']);
        if (empty($data['buy_time'])) {
            $this->tuError('购买时间不能为空');
        }

        $data['num']=htmlspecialchars($data['num']);
        if(empty($data['num'])){
            $this->tuError('数字时间不能为空');
        }

        $data['identification']=(int) htmlspecialchars($data['identification']);
        if(empty($data['identification'])){
            $this->tuError('唯一标识不能为空');
        }elseif($data['identification'] !=1 && $data['identification'] !=2){
            //var_dump($data['identification']);die;
            $this->tuError('唯一标识只能是1或2');
        }
        $data['buy_money'] = htmlspecialchars($data['buy_money']);
        if(empty($data['buy_money'])){
            $this->tuError('购买价格不能为空');
        }

        return $data;
    }
    //修改
    public function edit($id){
        $obj = D('Advertisement');
        if($this->isPost()){
            $data = $this->createCheck();
            $data['id']=$id;
            if($id = $obj->save($data)) {
                $this->tuSuccess('编辑成功', U('advertisement/index'));
            }
            $this->tuError('编辑失败');
        }else{
            $detail=$obj->where(array('id'=>$id))->find();
            $this->assign('detail',$detail);
            $this->display();
        }
    }

    //删除
    public function del($id){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Advertisement');
            $obj->save(array('id' => $id,'close'=>1));
            $this->tuSuccess('删除成功', U('advertisement/index'));
        }else{
            $this->tuError('请选择要删除的信息');
        }
    }


}