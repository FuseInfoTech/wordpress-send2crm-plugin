//TODO Should we check if Send2CRM.js is working before adding the event listenter?
window.addEventListener('send2crmLoading', (evt) => {
	
    console.log('Adding Settings: ' + JSON.stringify(additionalSettings));
	// Specify send2crm settings.
	// All values are optional, defaults will be used if not present.
	send2crm.applySettings(
        additionalSettings 
        /*{

		// Enable log message output to the console (default: false).
		debug: true,
		// The prefix to display for console log messages.
		//logPrefix: 'Send2CRM: ',
		
		// Enable the 'send2crm' Personalization cookie (default: false).
		//personalizationCookie: true,
		
        // Timeout before ending an inactive Session, in minutes.
        //sessionTimeout: 20,
        // Frequency of identified Visitor sync, in minutes.
        //syncFrequency: 4,
        // Sync frequency for secondary services where unknown, if applicable.
        //syncFrequencySecondary: 10,

        // Selector for auto-processing forms.
        //formSelector: 'form.send2crm-form',
        // Maximum form file size in bytes (1MB).
        //maxFileSize: 1 * 1024 * 1024,
        // Validation message shown on form submission failure.
        //formFailMessage: 'send2crm form submission failed.',
        // Specify the <form> attributes that may be used as identifiers.
        //formIdAttributes: ['name', 'action'],
        
        // Form flood control restrictions - set to falsey value to disable.
        // Minimum time, in seconds, that a standard form should be attached before allowing send.
        //formMinTime: 4,
        // Limit number of form submissions allowed.
        //formRateCount: 3,
        // Allow one additional form submission every n seconds.
        //formRateTime: 120,
        // Listen to form button click for submissions.
        //formListenOnButton: false,
    
        // Maximum storage space to occupy in bytes (4MB).
        //maxStorage: 4 * 1024 * 1024,

        // Set true to automatically collect UTM parameters into a cookie.
        //utmCookie: false,
        
        // Set top-level domain to allow sharing Send2CRM identification with subdomains.
        //idCookieDomain: '',
        
        // Determine how Send2CRM will handle visitor ignore flag.
        //ignoreBehavior: 'R',

        // Set true to disable automatic, e.g. page view, event recording.
        //disableAutoEvents: false,

        // Required for operation outside of web browser environment.
        //originHost: '',
        
	}*/
    );
});