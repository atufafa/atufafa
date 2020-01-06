<?php
/*
* 代理控制器
* 
*/
class CityagentAction extends CommonAction {

    private $create_fields = array('agent_name','price','rate','level','orderby','recruit'); //提交数据字段
    private $edit_fields = array('agent_name','price','rate','level','orderby','recruit');   //修改数据字段

    /*
    * 市、县级列表显示
    * 参数 $level int   市、县级标志
    * 参数 $list  array 等级信息
    */
    public function index($level=1) {	
        $obj = D('Cityagent');
		import('ORG.Util.Page');
		$count = $obj->where(array('level'=>'1'))->count();
        $Page = new Page($count,500);
        $show = $Page->show();
        $list = $obj->where(array('level'=>'1'))->order(array('orderby' =>'asc','agent_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('level', 1);
        $this->assign('page', $show);
        $this->display();
    }

    /*
    * 合伙人列表显示
    * 参数 $level int   合伙人标志
    * 参数 $list  array 等级信息
    */
    public function promo(){
    	//实例化M层
        $obj = D('Cityagent');
        import('ORG.Util.Page');
        $count = $obj->where(array('level'=>'2'))->count();
        $Page = new Page($count,500);
        $show = $Page->show();
        $list = $obj->where(array('level'=>'2'))->order(array('orderby' =>'asc','agent_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('level', 2);
        $this->assign('page', $show);
        $this->display();
    } 

    /*
    * 代理列表数据添加
    * 参数 $level int   代理类别标志  1.个体 2.企业
    */
    public function create($parent_id=0){
        
        $level= I('get.level');
        if ($this->isPost()) {
            $le = I('post.level');
            $data = $this->createCheck($le);
            $data['level'] = $le;
            //实例化M层
            $obj = D('Cityagent');
            if ($obj->add($data)) {
                $obj->cleanCache();
               ($le==2)?$this->tuSuccess('添加成功', U('cityagent/promo')): $this->tuSuccess('添加成功', U('cityagent/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('level',$level);
            $this->display();
        }
    }
    
    /*
    * 二级下拉菜单（废弃）
    */
    public function child($parent_id=0){
        $datas = D('Cityagent')->fetchAll();
        $str = '';
        foreach($datas as $var){
            if($var['parent_id'] == 0 && $var['agent_id'] == $parent_id){
                foreach($datas as $var2){
                    if($var2['parent_id'] == $var['agent_id']){
                        $str.='<option value="'.$var2['agent_id'].'">'.$var2['agent_name'].'</option>'."\n\r";
           
                        foreach($datas as $var3){
                            if($var3['parent_id'] == $var2['agent_id']){
                               $str.='<option value="'.$var3['agent_id'].'">&nbsp;&nbsp;--'.$var3['agent_name'].'</option>'."\n\r"; 
                            }
                        }
                    }  
                }
            }           
        }
        echo $str;die;
    }
    
    /*
    * 验证提交数据验证
    * 调用 private
    * 参数 $level int   代理类别标志  1.个体 2.企业  
    * 返回 array  
    * 
    */
    private function createCheck($level=1){

        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['agent_name'] = htmlspecialchars($data['agent_name']);
        if (empty($data['agent_name'])) {
            ($level==2)?$this->tuError('合伙人等级名称不能为空'):$this->tuError('代理等级名称不能为空');
        }
		
		$data['price'] = htmlspecialchars($data['price']);
        if (empty($data['price'])) {
            ($level==2)?$this->tuError('合伙人等级金额不能为空'): $this->tuError('代理等级金额不能为空');
        }
        if($data['rate'] <= 0) {
            $this->tuError('非法分佣比率');
        }

		if($data['price'] <= 0) {
            ($level==2)?$this->tuError('非法合伙人等级金额'): $this->tuError('非法代理等级金额');
        }

        $data['orderby'] = (int) $data['orderby'];
		$data['create_time'] = NOW_TIME;
        return $data;
    }

    /*
    * 修改数据
    * 调用 public
    * 参数 $level int   代理类别标志  1.个体 2.企业  
    *  
    * 
    */
    public function edit($agent_id = 0) {

        $level= I('get.level');
        if ($agent_id = (int) $agent_id) {
            $obj = D('Cityagent');
            if (!$detail = $obj->where(array('agent_id'=>$agent_id,'level'=>$level))->find()){
                ($level==2)?$this->tuError('请选择要编辑的合伙人等级'):$this->tuError('请选择要编辑的代理等级');
            }
           
            if ($this->isPost()) {
                $data = $this->editCheck($level);
                $data['agent_id'] = $agent_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    ($level==2)?$this->tuSuccess('操作成功', U('cityagent/promo')):$this->tuSuccess('操作成功', U('cityagent/index'));  
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('level',$level);
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的代理等级');
        }    	
    }

    /*
    * 验证修改数据
    * 调用 public
    * 参数 $level int   代理类别标志  1.个体 2.企业  
    *  
    */
    private function editCheck($level=1) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['agent_name'] = htmlspecialchars($data['agent_name']);
        $data['price'] = htmlspecialchars($data['price']);

        if (empty($data['agent_name'])) {
            ($level==2)?$this->tuError('合伙人等级名称不能为空'):$this->tuError('代理等级名称不能为空'); 
        }
		
        if (empty($data['price'])) {
            ($level==2)?$this->tuError('合伙人等级金额不能为空'):$this->tuError('代理等级金额不能为空');
        }

        if($data['rate'] <= 0) {
            $this->tuError('非法分佣比率');
        }

		if($data['price'] <= 0) {
            ($level==2)?$this->tuError('非法合伙人等级金额'):$this->tuError('非法代理等级金额');
        }

        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    /*
    * 删除数据
    * 调用 public
    * 参数 $level int   代理类别标志  1.个体 2.企业  
    *  
    * 
    */
    public function delete($agent_id = 0){
        $level= I('get.level');
        if (is_numeric($agent_id) && ($agent_id = (int) $agent_id)) {
            $obj = D('Cityagent');
            $obj->where(array('agent_id'=>$agent_id))->delete();
            $obj->cleanCache();
            ($level==2)? $this->tuSuccess('删除成功', U('cityagent/promo')):$this->tuSuccess('删除成功', U('cityagent/index'));
        } else {
            ($level==2)? $this->tuError('请选择要删除的合伙人等级分类'):$this->tuError('请选择要删除的代理等级分类');
        }
    }
    
    /*
    * 更新数据
    * 调用 public
    * 参数 $level int   代理类别标志  1.个体 2.企业  
    *
    */
    public function update($level=1) {
		$orderby = $this->_post('orderby', false);
        $obj = D('Cityagent');
        foreach ($orderby as $key => $val) {
            $data = array(
                'agent_id' => (int) $key,
                'orderby' => (int) $val
            );
            $obj->save($data);
        }
        $obj->cleanCache();
        ($level==2)? $this->tuSuccess('更新成功', U('cityagent/promo')): $this->tuSuccess('更新成功', U('cityagent/index'));
    }

    /*
    *
    *购买等级列表
    *
    */
    public function purchase(){

        $Shopgrade = D('Cityorder');
        import('ORG.Util.Page');
        $map = array('closed'=>0,'status'=>1);
        $count = $Shopgrade->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgrade->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $agent=D('Cityagent')->select();
        //var_dump($list);
        $this->assign('agent',$agent);
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //审核
    public function adopt($order_id){
         if($order_id = (int) $order_id){
            $obj = D('Cityorder');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('examine'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('cityagent/purchase'));
             }
         }else{

            $this->tuError('操作失败');
            } 
    }

    //删除
    public function hide($order_id){

       if($order_id = (int) $order_id){
            $obj = D('Cityorder');
            $details = $obj->where(array('order_id'=>$order_id))->save(array('closed'=>1));
           
            if ($details>0) {
                $this->tuSuccess('操作成功', U('cityagent/purchase'));
             }
         }else{

            $this->tuError('操作失败');
            } 
    }



}
