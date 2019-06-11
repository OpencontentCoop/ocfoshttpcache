{if $message}
    <div class="message-feedback"><h2>{$message|wash()}</h2></div>
{/if}

<div class="context-block">
    <div class="box-header"><div class="box-ml">
        <h1 class="context-title">Purge Varnish Cache</h1>
        <div class="header-mainline"></div>
    </div></div>
</div>

<div class="context-block">    
    <div class="box-bc"><div class="box-ml"><div class="box-content">

        <h2 class="context-title">Purge all</h2>
        <form method="post" action={'/varnish/dashboard'|ezurl()}>
            <div class="controlbar">
                <div class="block">
                    <input class="defaultbutton" type="submit" name="PurgeAll" value="Purge all cache" />
                </div>
            </div>
        </form>

    </div></div></div>
</div>

<div class="context-block">    
    <div class="box-bc"><div class="box-ml"><div class="box-content">

        <h2 class="context-title">Purge url</h2>
        <form method="post" action={'/varnish/dashboard'|ezurl()}>
            <input type="text" class="halfbox form-control" name="PurgeUrl" placeholder="http://www.example.org/my/url" value="" />
            <input class="defaultbutton" type="submit" name="Purge" value="Purge" />
        </form>

    </div></div></div>
</div>

<div class="context-block">        
    <div class="box-bc"><div class="box-ml"><div class="box-content">

        <h2 class="context-title">Purge node list</h2>
        <form method="post" action={'/varnish/dashboard'|ezurl()}>
            <input type="text" class="halfbox form-control" name="PurgeNodeList" placeholder="123,258,355,2" value="" />
            <input class="defaultbutton" type="submit" name="Purge" value="Purge" />
        </form>

    </div></div></div>

</div>