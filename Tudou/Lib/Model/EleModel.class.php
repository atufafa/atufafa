<?php
class EleModel extends CommonModel{
    protected $pk = 'shop_id';
    protected $tableName = 'ele';
    public function updateMonth($shop_id){
        $shop_id = (int) $shop_id;
        $month = date('Ym', NOW_TIME);
        $num = (int) D('Eleorder')->where(array('shop_id' => $shop_id, 'month' => $month))->count();
        return $this->execute("update " . $this->getTableName() . " set  month_num={$num} where shop_id={$shop_id}");
    }
    public function getEleCate(){
        $result = M('Elecate')->where(['is_look'=>1,'status'=>0,'parent_id'=>0])->select();
        foreach ($result as $key => $value) {
            $data[$value['cate_id']] = $value;
        }
        // print_r($result);die;
        return $data;
  //       return array(
		// 	'1' => '中餐外送', 
		// 	'2' => '面点汤粉', 
		// 	'3' => '馋嘴小吃', 
		// 	'4' => '甜点饮料', 
		// 	'5' => '夜宵夜宵', 
		// 	'6' => '水果鲜花',
		// 	'7' => '日用超市',
		// 	'8' => '健康保健',
		// );
    }
	
	public function getEleCates()
    {
        $where = " parent_id > 0";
        $result =M('Elecate')->where($where)->select();
		$data2 = array();
        foreach ($result as $key => $value) {
            $data[$value['parent_id']] = $value;
			array_push($data2,$value);
        }
	//	print_r($data2);
        return $data2;
    }
	
    public function getEleCateIds()
    {
        $where = " parent_id >0";
        $result =M('Elecate')->where($where)->select();
        foreach ($result as $key => $value) {
            $data[$value['parent_id']] = $value;
        }
        return $data;
    }
    public function getAllEleCate()
    {
        $result =M('Elecate')->select();
        foreach ($result as $key => $value) {
            $data[$value['cate_id']] = $value;
        }
        return $data;
    }
	public function get_file_Code($shop_id,$size){
        $url = U('wap/ele/shop', array('shop_id' => $shop_id, 't' => NOW_TIME, 'sign' => md5($shop_id . C('AUTH_KEY') . NOW_TIME)));
        $token = 'shop_id_' . $shop_id;
        $file = ToQrCode($token, $url,$size);
        return $file;
    }
    public function CallDataForMat($items){
        if (empty($items)) {
            return array();
        }
        $obj = D('Shop');
        $shop_ids = array();
        foreach ($items as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shops = $obj->itemsByIds($shop_ids);
        foreach ($items as $k => $val) {
            $val['shop'] = $shops[$val['shop_id']];
            $items[$k] = $val;
        }
        return $items;
    }
}