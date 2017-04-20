    //<!-- BEGIN SAVUSHKIN CODE {literal} -->
    jQuery.loadScript = function (url, callback) {
        jQuery.ajax({
            url: url,
            dataType: 'script',
            success: callback,
            async: true
        });
    }
    $.loadScript('https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js', function(){
        //Stuff to do after someScript has loaded
        // Parse the URL
        function getParameterByName(name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&;]" + name + "=([^&;#]*)"),
                results = regex.exec(location.search);
            return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
        }

        ga(function(tracker) {
            var clientId = tracker.get('clientId');
            if($.cookie('snmga') == null || $.cookie('snmga') == "") {
                $.cookie('snmga', clientId);
            }
        });
        // Give the URL parameters variable names
        var source = getParameterByName('utm_source');
        var medium = getParameterByName('utm_medium');
        var campaign = getParameterByName('utm_campaign');
        var term = getParameterByName('utm_term');
        var content = getParameterByName('utm_content');
        var referer = document.referrer;
        // var snmga = ga.getAll()[0].get('clientId');

        // Set the cookies
        if($.cookie('utm_source') == null || $.cookie('utm_source') == "") {
            $.cookie('utm_source', source);
        }
        if($.cookie('utm_medium') == null || $.cookie('utm_medium') == "") {
            $.cookie('utm_medium', medium);
        }
        if($.cookie('utm_campaign') == null || $.cookie('utm_campaign') == "") {
            $.cookie('utm_campaign', campaign);
        }
        if($.cookie('utm_term') == null || $.cookie('utm_term') == "") {
            $.cookie('utm_term', term);
        }
        if($.cookie('utm_content') == null || $.cookie('utm_content') == "") {
            $.cookie('utm_content', content);
        }
        if($.cookie('ref') == null || $.cookie('ref') == "") {
            $.cookie('ref', referer);
        }
    });
    //<!-- {/literal} END SAVUSHKIN CODE -->