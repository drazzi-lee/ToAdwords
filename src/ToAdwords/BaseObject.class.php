<?php

namespace ToAdwords;

abstract class BaseObject{
	public $name;

	protected $id;
	
	public function getId(){
		return $this->id;
	}
}