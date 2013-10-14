<?php

namespace ToAdwords\Definitions;

final class SyncStatus{
	const RECEIVE = 'RECEIVE';
	const QUEUE   = 'QUEUE';
	const RETRY   = 'RETRY';
	const SENDING = 'SENDING';
	const SYNCED  = 'SYNCED';
	const ERROR   = 'ERROR';
}
