<?php

namespace ToAdwords\Definition;

use \ReflectionClass;

final class Operation{
	const CREATE = 'CREATE';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';

	public static function isValid($action){
		$ref = new ReflectionClass(get_class(new Operation()));
		return in_array($action, array_values($ref->getConstants()));
	}
}
