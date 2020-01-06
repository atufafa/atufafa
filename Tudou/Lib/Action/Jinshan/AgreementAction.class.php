<?php		
class AgreementAction extends CommonAction{
	private $typeadds = array('typename','explain');
	private $create_fields=array('title','x_id','details');
	private $edit_fields=array('title','x_id','details');
	//类型
	public function type(){
		$obj = D('Agreement');
 		$map = array('cose' => 0);
        import('ORG.Util.Page');
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $map = array();
        if ($keyword) {
            $map['typename'] = array('LIKE', '%' . $keyword . '%');
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('times desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
	}

	//添加
	public function add(){
	$obj = D('Agreement');
        if($this->isPost()){
            $data = $this->tadd();
            if($id = $obj->add($data)) {
                $this->tuSuccess('添加成功', U('agreement/type'));
            }
            $this->tuError('创建失败');
        }else{
            $this->display();
        }
	}

	 private function tadd(){
  		$data = $this->checkFields($this->_post('data', false), $this->typeadds);
        $data['typename'] = htmlspecialchars($data['typename']);
        if (empty($data['typename'])) {
            $this->tuError('协议类型不能为空！');
        }
         $data['explain'] = htmlspecialchars($data['explain']);
        if (empty($data['explain'])) {
            $this->tuError('协议说明不能为空！');
        }
        return $data;

    }

	//修改
	public function edit($x_id){
 		if($x_id = (int) $x_id){
            $obj = D('Agreement');
            $detail = $obj->find($x_id);
            if (!($detail = $obj->find($x_id))){
                $this->tuError('请选择要编辑的类型名称');
            }
            if($this->isPost()){
                $data = $this->tadd();
                $data['x_id'] = $x_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('agreement/type'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的类型名称');
        }
	}

	//删除
	public function delete($x_id){

		 if (is_numeric($x_id) && ($x_id = (int) $x_id)) {
            $obj = D('Agreement');
            $obj->save(array('x_id' => $x_id,'cose'=>1));
            $this->tuSuccess('删除成功', U('agreement/type'));
        }else{
        	  $this->tuError('请选择要删除的类型名称');
        }
	}


	/**
	 *
	 *协议列表
	 * 
	 */
	public function index(){
	 $Article = D('Agreementlist');
        import('ORG.Util.Page');
        $map = array('cose' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Article->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Article->where($map)->order(array('times' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
           foreach ($list as $val){
            $x_id=$val['x_id'];
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('cates', D('Agreement')->itemsByIds($x_id));

        $this->display();
	}

	//添加
	public function create(){
		if ($this->isPost()) {
            $data = $this->createCheck();
            $em=D('Agreementlist')->where(['x_id'=>$data['x_id']])->select();
            if(!empty($em)){
                $this->tuError('该协议已存在，请核对后添加');
            }
            $obj = D('Agreementlist');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('agreement/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('cates', D('Agreement')->select());
            $this->display();
        }
	}
	private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['x_id'] = (int) $data['x_id'];
        if (empty($data['x_id'])) {
            $this->tuError('分类不能为空');
        }

        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('标题不能为空');
        }
       
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('详细内容不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('详细内容含有敏感词：' . $words);
        }
        
        return $data;
    }

    //编辑文章
    public function wzedit($id){
		if($id = (int) $id){
            $obj = D('Agreementlist');
            $detail = $obj->find($id);
            if (!($detail = $obj->find($id))){
                $this->tuError('请选择要编辑的文章');
            }
            if($this->isPost()){
                $data = $this->createCheck();
                $data['id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('agreement/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('cates', D('Agreement')->select());
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的文章');
        }
    }

  //删除
    public function wzdelete($id){

    	 if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Agreementlist');
            $obj->save(array('id' => $id,'cose'=>1));
            $this->tuSuccess('删除成功', U('agreement/index'));
        }else{
        	$this->tuError('请选择要删除的文章');
        }


    }


}
