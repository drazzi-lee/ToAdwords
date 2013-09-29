<?php

namespace ToAdwords;

use ToAdwords\Object\Base;

interface Adapter{

	public function getAdapteInfo(Base $object);
	
	public function getAdaptedId(Base $object);
	
}