<?php

namespace ToAdwords;

require_once ('Adapter.php');

use ToAdwords\Adapter;

class CampaignAdapter extends Adapter {
	
}

$campaign = new CampaignAdapter();
echo $campaign::$moduleName;

?>