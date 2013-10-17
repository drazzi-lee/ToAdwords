<?php

namespace ToAdwords\Definitions;

final class SyncStatus{
	const RECEIVE = 'RECEIVE';
	const QUEUE   = 'QUEUE';
	const RETRY   = 'RETRY';
	const SENDING = 'SENDING';
	const SYNCED  = 'SYNCED';
	const ERROR   = 'ERROR';

	public static function isValid($status){
		$ref = new ReflectionClass(get_class(new SyncStatus()));
		return in_array($status, array_values($ref->getConstants()));
	}
}
