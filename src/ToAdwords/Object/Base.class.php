<?php

namespace ToAdwords\Object;

use ToAdwords\Exceptions\DataCheckException;

abstract class Base{
	public $name;

	protected $id;
	
	public function __construct($id){
		if((int)$id != 0){
			$this->id = (int)$id;
		} else {
			throw new DataCheckException('$id只能为数字，实例化'.get_class($this).'失败');
		}
	}
	
	public function getId(){
		return $this->id;
	}
}