# OpenContent FosHttpCache for eZPublish Legacy

L'estensione integra la libreria [FosHttpCache](https://foshttpcache.readthedocs.io/en/latest/index.html) in eZ Publish 4.x (Legacy)

Il principale vantaggio è la possibilità di cacheare pagine in base allo User Context utilizzando [il metodo
usato da FosHttpCache](https://foshttpcache.readthedocs.io/en/latest/user-context.html)

## Installazione

 - Configurare Varnish (vedi doc/default.vcl)
 
 - Impostare in `site.ini` i `HTTPHeaderSettings`, ad esempio:
 ``` 
 [HTTPHeaderSettings]
 CustomHeader=enabled
 OnlyForAnonymous=disabled
 OnlyForContent=enabled
 Cache-Control[]
 Cache-Control[/]=public, must-revalidate, max-age=259200, s-maxage=259200
 HeaderList[]=Vary
 Vary[/]=X-User-Context-Hash
 ```
 
 - Installare e configurare l'estensione
 
 - Rigenerare gli autoloads e svuotare cache degli ini
 

## Configurazione

Configurare l'endpont Varnish e la porta oppure gli ip in `site.ini`:
``` 
[VarnishSettings]
VarnishHostName=varnish.example.com
VarnishPort=80
VarnishServers[]=10.0.0.1:1234
VarnishServers[]=127.0.0.1:80
```
oppure usando un `[Event]Listeners[]=ocfoshttpcache/varnish_server@MyVarnishServerCallable`


E' possibile configurare l'estensione perché utilizzi la `StaticCache` (configurazione di default) in `site.ini`:

```
[ContentSettings]
StaticCache=enabled
StaticCacheHandler=Opencontent\FosHttpCache\StaticCache
```  


## Invalidamento della cache di Varnish

Per l'invalidamento della cache è previsto un header custom `X-Instance` configurabile in `site.ini`
```
[HTTPHeaderSettings]
HeaderList[]=X-Instance
X-Instance[/]=example
```
oppure usando un `[Event]Listeners[]=ocfoshttpcache/instance_identifier@MyIdentifierCallable`

E' consigliato impostare l'header anche a livello di VirtualHost in modo da taggare anche gli asset e le immagini.
 
## Note

L'handler di `StaticCache` considera i valori di `site.ini [ContentSettings]CacheThreshold` per calcolare se svuotare o meno tutta la cache.

L'accesso al modulo `_fos_user_context_hash` è garantito dal blocco in `site.ini`:
```
[RoleSettings]
PolicyOmitList[]=_fos_user_context_hash 
```

## Todo

Per invalidare selettivamente la cache di Varnish è disponibile il modulo `varnish/main`

Sono disponibili operatori di template per inserire tag custom 
```
{fos_httpcache_tag('mytag')}
{fos_httpcache_tag(['tag-one', 'tag-two'])} 
```