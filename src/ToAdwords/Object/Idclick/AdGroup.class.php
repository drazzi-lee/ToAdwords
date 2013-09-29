<?php

namespace ToAdwords\Object\Idclick;
use ToAdwords\Object\Idclick\IdclickBase;
use ToAdwords\Exceptions\DataCheckException;

class AdGroup extends IdclickBase{
	
	public function __construct($id){
		if((int)$id != 0){
			$this->id = (int)$id;
		} else {
			throw new DataCheckException('$id只能为数字，实例化'.get_class($this).'失败');
		}
	}
}