# OpenContent FosHttpCache for eZPublish Legacy

L'estensione integra la libreria [FosHttpCache](https://foshttpcache.readthedocs.io/en/latest/index.html) in eZ Publish 4.x (Legacy)

Il principale vantaggio è la possibilità di cacheare pagine in base allo User Context utilizzando [il metodo
usato da FosHttpCache](https://foshttpcache.readthedocs.io/en/latest/user-context.html)

## Installazione

 - Configurare Varnish (vedi doc/default.vcl)
 
 - Installare e configurare l'estensione
 
 - Rigenerare gli autoloads e svuotare cache degli ini
 

## Configurazione

L'accesso al modulo `_fos_user_context_hash` è garantito dal blocco in `site.ini`:
```
[RoleSettings]
PolicyOmitList[]=_fos_user_context_hash 
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

Con questa configurazione è opportuno disattivare la `StaticCache` 
ed è possibile utilizzare i response tag per un controllo più accurato delle dinamiche di invalidamento  


## Invalidamento della cache di Varnish

L'handler di `StaticCache` considera i valori di `site.ini [ContentSettings]CacheThreshold` per calcolare se svuotare o meno tutta la cache.

Per invalidare selettivamente la cache di Varnish è disponibile il modulo `varnish/main` (todo)

Per l'invalidamento globale è previsto un header custom `X-Instance` da configurare a livello di VirtualHost (todo documentare)
