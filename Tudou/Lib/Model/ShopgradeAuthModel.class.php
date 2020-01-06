<?php
class ShopgradeAuthModel extends CommonModel {
    protected $pk = 'grade_id';
    protected $tableName = 'Shopgradeauth';
    protected $token = 'tu_shopgradeauth';
    public function fetchAll()
    {
        $cache = cache(array('type' => 'File', 'expire' => $this->cacheTime));
        // if (!($data = $cache->get($this->token))) {
        	$result = D('ShopgradeAuth')->select();
            $data = array();
            foreach ($result as $row => $val) {
                $data[$val[$this->pk]] = $val;
            }
            $cache->set($this->token, $data);

        return  $data;
    }
}
