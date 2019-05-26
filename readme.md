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

E' possibile configurare l'estensione perché utilizzi la `StaticCache` (configurazione di default) in `site.ini`:

```
[ContentSettings]
StaticCache=enabled
StaticCacheHandler=Opencontent\FosHttpCache\StaticCache
``` 
 
Per siti che producono molta cache dei contenuti (`viewcache`), è possibile configurare l'estensione in modo
che i file di cache dei contenuti **non** siano salvati nel filesystem (locale o nfs) inserendo in `site.ini`:

```
[Event]
Listeners[]=content/pre_rendering@Opencontent\FosHttpCache\CacheListener::onContentPreRendering
 
```

Con questa configurazione è possibile utilizzare i response tag per un controllo più accurato delle dinamiche di invalidamento  


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