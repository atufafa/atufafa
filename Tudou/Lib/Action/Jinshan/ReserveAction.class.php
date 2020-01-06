<?php		
class ReserveAction extends CommonAction{
	//显示
	public function index(){
		$reserve = D('Lifereserve');
        import('ORG.Util.Page');
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        // 导入分页类
        $map = array('close'=>0,'type'=>array('IN','1,2'));
         if ($keyword) {
            $map['user_id'] = array('LIKE', '%' . $keyword . '%');
        }
        $this->assign('keyword', $keyword);
        $count = $reserve->where($map)->count();
        $Page = new Page($count,25);
        $show = $Page->show();
        $list = $reserve->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        
        $this->assign('list', $list);
        $this->assign('page', $show);

		$this->display();
	}

	//删除
	public function delete($id=0){

		 if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Lifereserve');
            $obj->save(array('id' => $id,'close'=>1));
            $this->tuSuccess('删除成功', U('reserve/index'));
        }else{
        	 $this->tuError('请选择要删除的预约信息ID');
        }

	}


}