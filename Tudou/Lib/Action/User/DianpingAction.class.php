<?php
class DianpingAction extends CommonAction{
    public $item = array('商城'=>'GoodsDianping', '外卖'=>'EleDianping', '菜市场'=>'MarketDianping', '便利店'=>'StoreDianping', 'KTV'=>'KtvComment', '酒店'=>'HotelComment', '教育'=>'EduComment', '家政'=>'AppointDianping','农家乐'=>'FarmComment','配送员'=>'DeliveryComment');
    public function index($shop_id){
        $shop_id = (int) $shop_id;
        if (!($detail = D('Shop')->find($shop_id))) {
            $this->tuMsg('该商家不存在');
        }
		
        $cates = D('Shopcate')->where(array('cate_id'=>$detail['cate_id']))->find();
		$cates['d1'] = $cates['d1'] ? $cates['d1'] : '商家描述';
		$cates['d2'] = $cates['d2'] ? $cates['d2'] : '服务态度';
		$cates['d3'] = $cates['d3'] ? $cates['d3'] : '服务守时';
		$this->assign('cate',$cates);
		
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('score', 'd1', 'd2', 'd3', 'cost', 'contents'));
            $data['user_id'] = $this->uid;
            $data['shop_id'] = $shop_id;
            $data['score'] = (int) $data['score'];
            if ($data['score'] <= 0 || $data['score'] > 5) {
                $this->tuMsg('请选择评分');
            }
            $data['d1'] = (int) $data['d1'];
            if (empty($data['d1'])) {
                $this->tuMsg($cates['d1'] . '评分不能为空');
            }
            if ($data['d1'] > 5 || $data['d1'] < 1) {
                $this->tuMsg($cates['d1'] . '评分不能不正确');
            }
            $data['d2'] = (int) $data['d2'];
            if (empty($data['d2'])) {
                $this->tuMsg($cates['d2'] . '评分不能为空');
            }
            if ($data['d2'] > 5 || $data['d2'] < 1) {
                $this->tuMsg($cates['d2'] . '评分不能不正确');
            }
            $data['d3'] = (int) $data['d3'];
            if (empty($data['d3'])) {
                $this->tuMsg($cates['d3'] . '评分不能为空');
            }
            if ($data['d3'] > 5 || $data['d3'] < 1) {
                $this->tuMsg($cates['d3'] . '评分不能不正确');
            }
            $data['cost'] = (int) $data['cost'];
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg('不说点什么么');
            }
            $data['create_time'] = NOW_TIME;
            $data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_shop_dianping'] * 86400));
            $data['create_ip'] = get_client_ip();
            $obj = D('Shopdianping');
            if ($dianping_id = $obj->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D('Shopdianpingpics')->upload($dianping_id, $data['shop_id'], $local);
                }
                D('Shop')->updateCount($shop_id, 'score_num');
                D('Users')->updateCount($this->uid, 'ping_num');
                D('Shopdianping')->updateScore($shop_id);
				D('Users')->prestige($this->uid, 'dianping_shop');
                $this->tuMsg('评价成功', U('Wap/shop/detail', array('shop_id' => $shop_id)));
            }
            $this->tuMsg('操作失败');
        }else{
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function tuandianping($order_id){
        $order_id = (int) $order_id;
        if (!($detail = D('Tuanorder')->find($order_id))) {
            $this->tuMsg('没有该抢购');
        } else {
            if ($detail['user_id'] != $this->uid) {
                $this->tuMsg('不要评价别人的抢购');
                die;
            }
        }
        if (D('Tuandianping')->check($order_id, $this->uid)) {
            $this->tuMsg('已经评价过了');
        }
        if ($this->_Post()) {
            $data = $this->checkFields($this->_post('data', false), array('score', 'cost', 'contents'));
            $data['user_id'] = $this->uid;
            $data['shop_id'] = $detail['shop_id'];
            $data['tuan_id'] = $detail['tuan_id'];
            $data['order_id'] = $order_id;
            $data['score'] = (int) $data['score'];
            if (empty($data['score'])) {
                $this->tuMsg('评分不能为空');
            }
            if ($data['score'] > 5 || $data['score'] < 1) {
                $this->tuMsg('评分为1-5之间的数字');
            }
            $data['cost'] = (int) $data['cost'];
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg('评价内容不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->tuMsg('评价内容含有敏感词：' . $words);
            }
            $data['show_date'] = date('Y-m-d', NOW_TIME + 15 * 86400);
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if (D('Tuandianping')->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D('Tuandianpingpics')->upload($order_id, $local);
                }
                D('Tuanorder')->save(array('order_id' => $order_id, 'is_dainping' => 1));
                D('Users')->prestige($this->uid, 'dianping');
                D('Users')->updateCount($this->uid, 'ping_num');
                $this->tuMsg('恭喜您点评成功!', U('mcenter/tuan/index'));
            }
            $this->tuMsg('点评失败');
        } else {
            $tuandetails = D('Tuan')->find($detail['tuan_id']);
            $this->assign('tuandetails', $tuandetails);
            $this->assign('order_id', $order_id);
            $this->display();
        }
    }
    public function tuandpedit($order_id){
        $order_id = (int) $order_id;
        $obj = D('Tuandianping');
        if ($this->_Post()) {
            if (!($detail = $obj->find($order_id))) {
                $this->tuMsg('请选择要编辑的抢购点评');
            }
            if (!($detail = $obj->find($order_id))) {
                $this->tuMsg('没有该抢购点评');
            } else {
                if ($detail['user_id'] != $this->uid) {
                    $this->tuMsg('不要编辑别人的抢购');
                }
                if ($detail['show_date'] < '$today 00:00:00') {
                    $this->tuMsg('点评已过期');
                }
            }
            $data = $this->checkFields($this->_post('data', false), array('score', 'cost', 'contents'));
            $data['user_id'] = $this->uid;
            $data['tuan_id'] = $detail['tuan_id'];
            $data['shop_id'] = $detail['shop_id'];
            $data['order_id'] = $order_id;
            $data['score'] = (int) $data['score'];
            if (empty($data['score'])) {
                $this->tuMsg('评分不能为空');
            }
            if ($data['score'] > 5 || $data['score'] < 1) {
                $this->tuMsg('评分为1-5之间的数字');
            }
            $data['cost'] = (int) $data['cost'];
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg('评价内容不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->tuMsg('评价内容含有敏感词：' . $words);
            }
            $data['show_date'] = $detail['show_date'];
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if (false !== $obj->save($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D('Tuandianpingpics')->upload($order_id, $local);
                }
                $this->tuMsg('恭喜您编辑点评成功!', U('members/order'));
            }
            $this->tuMsg('点评编辑失败');
        } else {
            $this->assign('detail', $obj->find($order_id));
            $this->assign('tuandetails', D('Tuan')->find($detail['tuan_id']));
            $this->assign('photos', D('Tuandianpingpics')->getPics($order_id));
            $this->display();
        }
    }

    public function dplist(){
        if (empty($this->uid)) {
            $this->error("非法操作！");
        }
        $sgda = D('Shopgradeauth')->field("grade_id,grade_name")->select();
        foreach($sgda as $key=>$value)
        {
            if (array_key_exists($value['grade_name'], $this->item))
            {
                $sgda[$key]['count'] = D($this->item[$value['grade_name']])->where(array('user_id'=>$this->uid))->count();
            }
        }
        //$sgda[count($sgda)+1]['grade_id']='999';
        //$sgda[count($sgda)]['grade_name']='配送员';
        //$sgda[count($sgda)]['count']=D('DeliveryComment')->where(array('user_id'=>$this->uid))->count();
        $this->assign('list', $sgda);
        $this->display();
    }
    //显示
    public function dpshow(){
        $grade_id = (int) $this->_get('grade_id');
        if (empty($this->uid)) {
            $this->error("非法操作！");
        }
        $grade_id = $_GET['grade_id'];
        if(empty($grade_id)){
            $this->error("非法操作！");
        }
        if($grade_id){
            $where = array('grade_id'=>$grade_id);
        }
        $sgda = D('Shopgradeauth')->where($where)->field("grade_id,grade_name")->select();
        if(!$sgda)
        {
            $sgda =array(array('grade_id'=>999,'grade_name'=>'配送员'));
        }
        $this->assign('show', $sgda);
        $this->display();
    }
    //分页
    public function dpshowloading(){
        $grade_id = (int) $this->_get('grade_id');
        if($grade_id){
            $where = array('grade_id'=>$grade_id);
        }
        $sgda = D('Shopgradeauth')->where($where)->field("grade_id,grade_name")->select();
        //判断存在
        if($sgda)
        {
            if (array_key_exists($sgda[0]['grade_name'], $this->item))
            {
                import('ORG.Util.Page');
                $map['user_id'] = $this->uid;
                $count = D($this->item[$sgda[0]['grade_name']])->where($map)->count();
                $Page = new Page($count, 5);
                $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
                $p = $_GET[$var];
                if ($Page->totalPages < $p) {
                    die('0');
                }
                $show = $Page->show();
                $list = D($this->item[$sgda[0]['grade_name']])->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
                $user_ids = $order_ids = $comment_id =array();
                foreach ($list as $k => $val) {
                    $list[$k] = $val;
                    $user_ids[$val['user_id']] = $val['user_id'];
                    $order_ids[$val['order_id']] = $val['order_id'];
                    $comment_id[$val['comment_id']] = $val['comment_id'];
                }
                if (!empty($user_ids)) {
                    $this->assign('users', D('Users')->itemsByIds($user_ids));
                }
                if (!empty($order_ids)) {
                    if(strpos($this->item[$sgda[0]['grade_name']],'Dianping') !== false){
                        $this->assign('pics', D($this->item[$sgda[0]['grade_name']].'Pics')->where(array('order_id' => array('IN', $order_ids)))->select());
                    }else{
                        if($this->item[$sgda[0]['grade_name']]=='KtvComment') {
                            $this->assign('pics', D($this->item[$sgda[0]['grade_name']] . 'Pics')->where(array('comment_id' => array('IN', $order_ids)))->select());
                        }else{
                            $this->assign('pics', D($this->item[$sgda[0]['grade_name']].'Pics')->where(array('comment_id' => array('IN', $comment_id)))->field(array('photo'=>'pic'))->select());
                        }
                    }
                }
                $this->assign('contrl',$this->item[$sgda[0]['grade_name']]);
            }
        }else{
            import('ORG.Util.Page');
            $map['user_id'] = $this->uid;
            $count = D('DeliveryComment')->where($map)->count();
            $Page = new Page($count, 5);
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
            $show = $Page->show();
            $list = D('DeliveryComment')->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = $comment_id =array();
            foreach ($list as $k => $val) {
                $list[$k] = $val;
                $user_ids[$val['user_id']] = $val['user_id'];
                $order_ids[$val['order_id']] = $val['order_id'];
                $comment_id[$val['comment_id']] = $val['comment_id'];
            }
            if (!empty($user_ids)) {
                $this->assign('users', D('Users')->itemsByIds($user_ids));
            }
            if (!empty($order_ids)) {
                $this->assign('pics', D('DeliveryCommentPics')->where(array('comment_id' => array('IN', $comment_id)))->field(array('photo'=>'pic'))->select());
            }
            $this->assign('contrl',"DeliveryComment");
        }
        $this->assign('list', $list);
        $this->assign('totalnum', $count);
        $this->assign('page', $show);
        $this->display();
    }
    //修改点评
    public function dpedit(){
        $order_id = (int) $this->_get('order_id');
        $contrl = $this->_get('contrl');
        $where =array('order_id' => $order_id, 'user_id' => $this->uid);
        $sgda = D('Shopgradeauth')->where($where)->field("grade_id,grade_name")->select();
        $detail = D($contrl)->where($where)->find();
        if($detail)
        {
            if(strpos($contrl,'dianping') !== false){
                if($contrl=='AppointDianping') {
                    $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                }elseif ($contrl=='GoodsDianping'){
                    $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                }else{
                    $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                }
            }else{
                if($contrl=='KtvComment'){
                    $pic = D($contrl.'Pics')->where(array('room_id'=>$detail['room_id'],'order_id'=>$detail['order_id']))->select();
                }else{
                    $pic = D($contrl.'Pics')->where(array('comment_id'=>$detail['comment_id']))->field(array('photo'=>'pic'))->select();
                }
            }
            $this->assign('pics', $pic);
        }
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('score', 'content'));
            $photos = $this->_post('photos', false);
            if(strpos($contrl,'dianping') !== false){
                if($resule = D($contrl)->where($where)->save($data))
                {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        if($contrl=='AppointDianping') {
                            $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                            if($pic){
                                foreach ($pic as $key=>$value)
                                {
                                    unlink('.'.$value['photo']);
                                }
                                D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                            }
                            foreach ($local as $k => $val) {
                                D($contrl . 'Pics')->add(array('dianping_id' => $detail['dianping_id'], 'order_id' => $detail['order_id'], 'pic' => $val));
                            }
                        }elseif ($contrl=='GoodsDianping'){
                            $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                            if($pic){
                                foreach ($pic as $key=>$value)
                                {
                                    unlink('.'.$value['photo']);
                                }
                                D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                            }
                            foreach($local as $k=>$val){
                                D($contrl.'Pics')->add(array('goods_id' => $detail['goods_id'], 'order_id'=>$detail['order_id'],'pic'=>$val));
                            }
                        }else{
                            $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                            if($pic){
                                foreach ($pic as $key=>$value)
                                {
                                    unlink('.'.$value['photo']);
                                }
                                D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                            }
                            foreach($local as $k=>$val){
                                D($contrl.'Pics')->add(array('order_id'=>$detail['order_id'],'pic'=>$val));
                            }
                        }
                    }
                    $this->tuMsg('恭喜您修改点评成功!', U('dianping/dplist'));
                }else{
                    $this->tuMsg('无操作返回点评列表!', U('dianping/dplist'));
                }
            }else{
                if($resule = D($contrl)->where($where)->save($data))
                {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        if($contrl=='KtvComment'){
                            $pic = D($contrl.'Pics')->where(array('room_id'=>$detail['room_id'],'order_id'=>$detail['order_id']))->select();
                            if($pic){
                                foreach ($pic as $key=>$value)
                                {
                                    unlink('.'.$value['photo']);
                                }
                                D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                            }
                            foreach($local as $k=>$val){
                                D($contrl.'Pics')->add(array('room_id'=>$detail['room_id'],'order_id'=>$detail['order_id'],'pic'=>$val));
                            }
                        }else{
                            $pic = D($contrl.'Pics')->where(array('comment_id'=>$detail['comment_id']))->select();
                            if($pic){
                                foreach ($pic as $key=>$value)
                                {
                                    unlink('.'.$value['photo']);
                                }
                                D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                            }
                            foreach($local as $k=>$val){
                                D($contrl.'Pics')->add(array('comment_id'=>$detail['comment_id'],'photo'=>$val));
                            }
                        }
                    }
                    $this->tuMsg('恭喜您修改点评成功!', U('dianping/dplist'));
                }else{
                    $this->tuMsg('无操作返回点评列表!', U('dianping/dplist'));
                }
            }
        }
        $this->assign('order_id', $order_id);
        $this->assign('contrl', $contrl);
        $this->assign('detail', $detail);
        $this->assign('show', array_search($contrl,$this->item));
        $this->display();
    }
    //删除点评并删除图片
    public function del(){
        $order_id = (int) $this->_get('order_id');
        $contrl = $this->_get('contrl');
        $where = array('order_id' => $order_id, 'user_id' => $this->uid);
        if ($order_id){
            $obj = D($contrl);
            $detail = $obj->where($where)->find();
            if (!$detail){
                $this->tuMsg('点评记录不存在');
            }
            foreach($detail as $key=>$value)
            {

                if(strpos($contrl,'dianping') !== false){
                    if($contrl=='AppointDianping') {
                        $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                        if($pic){
                            foreach ($pic as $key=>$value)
                            {
                                unlink('.'.$value['photo']);
                            }
                            D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                        }
                    }elseif ($contrl=='GoodsDianping'){
                        $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                        if($pic){
                            foreach ($pic as $key=>$value)
                            {
                                unlink('.'.$value['photo']);
                            }
                            D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                        }
                    }else{
                        $pic = D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->select();
                        if($pic){
                            foreach ($pic as $key=>$value)
                            {
                                unlink('.'.$value['photo']);
                            }
                            D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                        }
                    }
                }else{
                    if($contrl=='KtvComment'){
                        $pic = D($contrl.'Pics')->where(array('room_id'=>$detail['room_id'],'order_id'=>$detail['order_id']))->select();
                        if($pic){
                            foreach ($pic as $key=>$value)
                            {
                                unlink('.'.$value['photo']);
                            }
                            D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                        }
                    }else{
                        $pic = D($contrl.'Pics')->where(array('comment_id'=>$detail['comment_id']))->select();
                        if($pic){
                            foreach ($pic as $key=>$value)
                            {
                                unlink('.'.$value['photo']);
                            }
                            D($contrl.'Pics')->where(array('order_id'=>$detail['order_id']))->delete();
                        }
                    }
                }

            }
            if($obj->where($where)->delete()){
                $this->success('删除成功', U('dianping/dplist'));
            }
            $this->error('操作失败');
        } else {
            $this->error('请选择要删除的点评');
        }
    }
}