<?php
class LlifeclassModel extends CommonModel{
    protected $pk = 'cate_id';
    protected $tableName = 'life_class';

	protected $type="D('Lifetype')->select()";
	
	 public function getChannelM()
    {
        return $this->type;
    }
	
	}