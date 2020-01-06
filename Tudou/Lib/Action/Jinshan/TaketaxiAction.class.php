<?php
class TaketaxiAction extends CommonAction
{
    //数据统计
    public function indexlog(){
        $obj = D('TaketaxiLog');
        import('ORG.Util.Page');
        $count = $obj->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('count',$count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除数据
    public function del($log_id=0){
        if (is_numeric($log_id) && ($log_id = (int) $log_id)) {
            $obj = D('TaketaxiLog');
            $obj->delete($log_id);
            $this->tuSuccess('删除成功', U('taketaxi/indexlog'));
        } else {
            $order_id = $this->_post('log_id', false);
            if (is_array($order_id)) {
                $obj = D('TaketaxiLog');
                foreach ($order_id as $id) {
                        $obj->delete($id);
                }
                $this->tuSuccess('删除成功', U('taketaxi/indexlog'));
            }
            $this->tuError('请选择要删除的信息');
        }
    }



}
