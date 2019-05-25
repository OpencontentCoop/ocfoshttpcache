vcl 4.0;

import std;

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl invalidators {
    "localhost";
    # Add any other IP addresses that your application runs on and that you
    # want to allow invalidation requests from. For instance:
    # "192.168.1.0"/24;
}

sub vcl_recv {

	// Add a Surrogate-Capability header to announce ESI support.
    set req.http.Surrogate-Capability = "abc=ESI/1.0";

    // Ensure that the Symfony Router generates URLs correctly with Varnish
    if (req.http.X-Forwarded-Proto == "https" ) {
        set req.http.X-Forwarded-Port = "443";
    } else {
        set req.http.X-Forwarded-Port = "80";
    }

    call fos_purge_recv;    
    call fos_ban_recv;

    // Don't cache requests other than GET and HEAD.
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    // Normalize the Accept-Encoding headers
    if (req.http.Accept-Encoding) {
        if (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            unset req.http.Accept-Encoding;
        }
    }

    // Don't cache Authenticate & Authorization
    // You may remove this when using REST API with basic auth.
    if (req.http.Authenticate || req.http.Authorization) {
        if (req.http.X-Cache-Debug) {
            set req.http.X-Debug = "Not Cached according to configuration (Authorization)";
        }
        return (hash);
    }
    
    // Remove all cookies besides Session ID, as JS tracker cookies and so will make the responses effectively un-cached
    if (req.http.cookie) {
        set req.http.cookie = ";" + req.http.cookie;
        set req.http.cookie = regsuball(req.http.cookie, "; +", ";");
        set req.http.cookie = regsuball(req.http.cookie, ";(is_logged_in|eZSESSID[^=]*)=", "; \1=");
        set req.http.cookie = regsuball(req.http.cookie, ";[^ ][^;]*", "");
        set req.http.cookie = regsuball(req.http.cookie, "^[; ]+|[; ]+$", "");
		
        if (req.http.cookie == "") {
            // If there are no more cookies, remove the header to get page cached.
            unset req.http.cookie;
        }
    }

    // Do a standard lookup on assets (these don't vary by user context hash)
    // Note that file extension list below is not extensive, so consider completing it to fit your needs.
    if (req.url ~ "\.(css|js|gif|jpe?g|bmp|png|tiff?|ico|img|tga|wmf|svg|swf|ico|mp3|mp4|m4a|ogg|mov|avi|wmv|zip|gz|pdf|ttf|eot|wof)$") {
        return (hash);
    }

    // Sort the query string for cache normalization.
    set req.url = std.querysort(req.url);

    call fos_user_context_recv;

    // If it passes all these tests, do a lookup anyway.
    return (hash);
}

sub vcl_backend_response {
    
	set beresp.http.X-Debug = bereq.http.X-Debug;
	set beresp.http.X-Ban-Url = bereq.url;
    set beresp.http.X-Ban-Host = bereq.http.host;

	if (bereq.url ~ "^(/var/([^/]+/)?cache/public/|/var/cache/textoimage/|/design/|/share/icons/|/extension/[^/]+/design/|/var/([^/]+/)?storage/images/).+\.(gif|jpeg|jpg|swf|css|js|png|zip|gz|pdf|ico|bmp|tiff?|tga|svg|mp3|mp4|m4a|ogg|mov|avi|wmv)$") {
        unset beresp.http.Set-Cookie;
        if (beresp.status == 200 ) {
            set beresp.ttl = 31d;
            set beresp.http.X-Ttl = "31d";
        }  else {            
            set beresp.ttl = 10s;
            set beresp.http.X-Ttl = "10s";
        }
        set beresp.http.Expires = "" + (now + beresp.ttl);
   	}

    call fos_ban_backend_response;
    call fos_user_context_backend_response;    

    // Check for ESI acknowledgement and remove Surrogate-Control header
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    // Make Varnish keep all objects for up to 1 hour beyond their TTL, see vcl_hit for Request logic on this
    set beresp.grace = 1h;
    set beresp.keep  = 3m;
}

sub vcl_deliver {
    call fos_ban_deliver;
    call fos_user_context_deliver;

    if (resp.http.Vary ~ "X-User-Hash") {
        // If we vary by user hash, we'll also adjust the cache control headers going out by default to avoid sending
        // large ttl meant for Varnish to shared proxies and such. We assume only session cookie is left after vcl_recv.
        if (req.http.cookie) {
            // When in session where we vary by user hash we by default avoid caching this in shared proxies & browsers
            // For browser cache with it revalidating against varnish, use for instance "private, no-cache" instead
            set resp.http.cache-control = "private, no-cache, no-store, must-revalidate";
        } else if (resp.http.cache-control ~ "public") {
            // For non logged in users we allow caching on shared proxies (mobile network accelerators, planes, ...)
            // But only for a short while, as there is no way to purge them
            set resp.http.cache-control = "public, s-maxage=600, stale-while-revalidate=300, stale-if-error=300";
        }
    }

    #unset resp.http.X-Ban-Url;
    #unset resp.http.X-Ban-Host;

    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hits = obj.hits;
    } else {
        set resp.http.X-Cache = "MISS";
    }
    set resp.http.X-Served-By = server.hostname;
}

sub fos_purge_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }
        return (purge);
    }
}

sub fos_ban_recv {

    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Not allowed"));
        }

        if (req.http.X-Cache-Tags) {
            ban("obj.http.X-Host ~ " + req.http.X-Host
                + " && obj.http.X-Url ~ " + req.http.X-Url
                + " && obj.http.content-type ~ " + req.http.X-Content-Type
                // the left side is the response header, the right side the invalidation header
                + " && obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags
            );
        } else {
            ban("obj.http.X-Host ~ " + req.http.X-Host
                + " && obj.http.X-Url ~ " + req.http.X-Url
                + " && obj.http.content-type ~ " + req.http.X-Content-Type
            );
        }

        return (synth(200, "Banned"));
    }
}

sub fos_ban_backend_response {

    # Set ban-lurker friendly custom headers
    set beresp.http.X-Url = bereq.url;
    set beresp.http.X-Host = bereq.http.host;
}

sub fos_ban_deliver {

    # Keep ban-lurker headers only if debugging is enabled
    if (!resp.http.X-Cache-Debug) {
        # Remove ban-lurker friendly custom headers when delivering to client
        unset resp.http.X-Url;
        unset resp.http.X-Host;

        # Unset the tagged cache headers
        unset resp.http.X-Cache-Tags;
    }
}

sub fos_user_context_recv {

    # Prevent tampering attacks on the hash mechanism
    if (req.restarts == 0
        && (req.http.accept ~ "application/vnd.fos.user-context-hash"
            || req.http.X-User-Context-Hash
        )
    ) {
        return (synth(400));
    }

    # Lookup the context hash if there are credentials on the request
    # Note that the hash lookup discards the request body.
    # https://www.varnish-cache.org/trac/ticket/652
    if (req.restarts == 0
        && (req.method == "GET" || req.method == "HEAD")
    ) {
        # Backup accept header, if set
        if (req.http.accept) {
            set req.http.X-Fos-Original-Accept = req.http.accept;
        }
        set req.http.accept = "application/vnd.fos.user-context-hash";

        # Backup original URL.
        #
        # We do not use X-Original-Url here, as the header will be sent to the
        # backend and X-Original-Url has semantical meaning for some applications.
        # For example, the Microsoft IIS rewriting module uses it, and thus
        # frameworks like Symfony also have to handle that header to integrate with IIS.

        set req.http.X-Fos-Original-Url = req.url;

        set req.url = "/_fos_user_context_hash";

        # Force the lookup, the backend must tell not to cache or vary on all
        # headers that are used to build the hash.
        return (hash);
    }

    # Rebuild the original request which now has the hash.
    if (req.restarts > 0
        && req.http.accept == "application/vnd.fos.user-context-hash"
    ) {
        set req.url = req.http.X-Fos-Original-Url;
        unset req.http.X-Fos-Original-Url;
        if (req.http.X-Fos-Original-Accept) {
            set req.http.accept = req.http.X-Fos-Original-Accept;
            unset req.http.X-Fos-Original-Accept;
        } else {
            # If accept header was not set in original request, remove the header here.
            unset req.http.accept;
        }

        # Force the lookup, the backend must tell not to cache or vary on the
        # user hash to properly separate cached data.

        return (hash);
    }
}

sub fos_user_context_backend_response {
    if (bereq.http.accept ~ "application/vnd.fos.user-context-hash"
        && beresp.status >= 500
    ) {
        return (abandon);
    }
}

sub fos_user_context_deliver {
    # On receiving the hash response, copy the hash header to the original
    # request and restart.
    if (req.restarts == 0
        && resp.http.content-type ~ "application/vnd.fos.user-context-hash"
    ) {
        set req.http.X-User-Context-Hash = resp.http.X-User-Context-Hash;

        return (restart);
    }

    # If we get here, this is a real response that gets sent to the client and we do some cleanup if not in debug.

    if (!resp.http.X-Cache-Debug) {
        # Remove the vary on context user hash, this is nothing public. Keep all
        # other vary headers.
        set resp.http.Vary = regsub(resp.http.Vary, "(?i),? *X-User-Context-Hash *", "");
        set resp.http.Vary = regsub(resp.http.Vary, "^, *", "");
        if (resp.http.Vary == "") {
            unset resp.http.Vary;
        }

        # Sanity check to prevent ever exposing the hash to a client.
        unset resp.http.X-User-Context-Hash;
    }
}