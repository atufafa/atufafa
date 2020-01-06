<?php
class ZeroAction extends CommonAction {
    public function order(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $keyword = (int) $this->_param('keyword');
        $this->assign('keyword', $keyword);
        $this->assign('nextpage', linkto('zero/loaddata', array('aready'=>$aready,'keyword'=>$keyword,'t' => NOW_TIME, 'p' => '0000')));
        $this->display();
    }

    public function loaddata(){
        $Tuanorder = D('Pindanorder');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('shop_id' => $this->shop_id);

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $keyword = intval($keyword);
            if (!empty($keyword)) {
                $map['order_id'] = array('LIKE', '%' . $keyword . '%');
                $this->assign('keyword', $keyword);
            }
        }
        if (isset($_GET['aready']) || isset($_POST['aready'])) {
            $aready = (int) $this->_param('aready');
            if ($aready != 999) {
                $map['status'] = $aready;
            }
            $this->assign('aready', $aready);
        } else {
            $map['status'] = 0;
            $this->assign('aready', 999);
        }
        $count = $Tuanorder->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Tuanorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //var_dump($list);die;
        $shop_ids = $user_ids = $tuan_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('tuan', D('Pindan')->itemsByIds($tuan_ids));

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function detail($order_id)
    {
        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D('Pindanorder')->find($order_id))) {
            $this->error('该订单不存在');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要操作他人的订单');
        }

        $this->assign('tuans', D('Pindan')->find($detail['tuan_id']));
        $this->assign('detail', $detail);
        $this->display();
    }


}