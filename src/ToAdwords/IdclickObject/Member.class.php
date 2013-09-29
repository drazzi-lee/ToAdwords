<?php

namespace ToAdwords\IdclickObject;
use ToAdwords\IdclickObject\IdclickBase;
use ToAdwords\Exceptions\DataCheckException;

class Member extends IdclickBase{	

	public function __construct($id){
		if((int)$id != 0){
			$this->id = (int)$id;
		} else {
			throw new DataCheckException('$id只能为数字，实例化Member失败');
		}
	}
}