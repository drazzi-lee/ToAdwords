<?php

namespace ToAdwords\Object;

abstract class Base{
	public $name;

	protected $id;
	
	public function getId(){
		return $this->id;
	}
}