<?php

namespace ToAdwords;

interface Adapter{

	/**
	 * 转换Idclick对象为Adwords对象
	 */
	public function getAdapter(IdclickObject $idclickObject);
}