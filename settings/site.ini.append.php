<?php /*

[RoleSettings]
PolicyOmitList[]=_fos_user_context_hash

[ContentSettings]
StaticCache=enabled
StaticCacheHandler=Opencontent\FosHttpCache\StaticCache

[Event]
Listeners[]=request/preinput@Opencontent\FosHttpCache\CacheListener::onRequestPreinput
Listeners[]=content/view@Opencontent\FosHttpCache\CacheListener::onContentView
Listeners[]=response/output@Opencontent\FosHttpCache\CacheListener::onResponseOutput
Listeners[]=content/cache/all@Opencontent\FosHttpCache\CacheListener::onContentCacheAll
Listeners[]=content/cache@Opencontent\FosHttpCache\CacheListener::onContentCache

[VarnishSettings]
VarnishHostName=
VarnishPort=
VarnishServers[]

[UserContextHash]
#IncludeCurrentUserId=enabled

*/ ?>