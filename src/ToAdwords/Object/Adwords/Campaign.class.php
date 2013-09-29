<?php

namespace ToAdwords\Object\Adwords;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Exceptions\DataCheckException;

class Campaign extends AdwordsBase{
	public function __construct($id){
		if((int)$id != 0){
			$this->id = (int)$id;
		} else {
			throw new DataCheckException('$id只能为数字，实例化'.get_class($this).'失败');
		}
	}
}