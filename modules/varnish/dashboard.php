<?php
use Opencontent\FosHttpCache\StaticCache;

$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();

$staticCache = new StaticCache();
if ($http->hasPostVariable('PurgeAll')){
    $staticCache->generateCache(true, true);
    
    $tpl->setVariable('message', "All cache purged");
}

if ($http->hasPostVariable('Purge')){
	if ($http->hasPostVariable('PurgeUrl')){
		$url = $http->postVariable('PurgeUrl');
		$staticCache->removeURL($url);

    	$tpl->setVariable('message', "Purged url $url");
	}
	if ($http->hasPostVariable('PurgeNodeList')){
		$stringList = $http->postVariable('PurgeNodeList');
		$nodeList = explode(',', $stringList);
		$nodeList = array_map('trim', $nodeList);
		$staticCache->generateNodeListCache($nodeList);

    	$tpl->setVariable('message', "Purged nodes " . implode(', ', $nodeList));
	}
}

$Result = array();
$Result['content'] = $tpl->fetch('design:modules/varnish/dashboard.tpl');
$Result['left_menu'] = false;
$Result['path'] = array(array('url' => false, 'text' => 'Varnish dashboard'));
