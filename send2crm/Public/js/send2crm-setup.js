(function(s,e,n,d2,c,r,m){n[e]=n[e]||{};m=document.createElement('script');m.onload=function(){n[e].init(d2,c);};m.src=s;m.integrity='sha384-'+r;m.crossOrigin='anonymous';document.head.appendChild(m);})(snippetData.js_location, 'send2crm', window, snippetData.api_domain, snippetData.api_key, snippetData.hash);


function additionalSetup() {
    if ( (!additionalSettings || !Object.keys(additionalSettings).length)
        && ( (!servicePaths || !Object.keys(servicePaths).length) 
        || (!servicePaths.formPath && !servicePaths.visitorPath ) ) )
    {
        return;
    }

    if (Object.hasOwn(additionalSettings, 'formIdAttributes')) {
      // Convert comma-separated list to array.
      additionalSettings.formIdAttributes = additionalSettings.formIdAttributes.split(',');
    }

    window.addEventListener('send2crmLoading', (evt) => {
        // All values are optional, defaults will be used if not present.
        if (additionalSettings && Object.keys(additionalSettings).length > 0) {
            send2crm.applySettings(
                additionalSettings 
            );
        }
        if ( (servicePaths && Object.keys(servicePaths).length > 0) 
        && (servicePaths.formPath || servicePaths.visitorPath ) ) {
            send2crm.services.setPaths(servicePaths.formPath, servicePaths.visitorPath);
        }
    });
}

additionalSetup();
