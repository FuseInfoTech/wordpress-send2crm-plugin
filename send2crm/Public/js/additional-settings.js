//TODO Should we check if Send2CRM.js is working before adding the event listenter?
window.addEventListener('send2crmLoading', (evt) => { //TODO Only add listener and settings/paths if the jsObjects are populated
	
    console.log('Adding Settings: ' + JSON.stringify(additionalSettings)); //TODO Remove Debug statements
	// Specify send2crm settings.
	// All values are optional, defaults will be used if not present.
	send2crm.applySettings(
        additionalSettings 
    );
    console.log('Adding Paths: ' + JSON.stringify(servicePaths));
    send2crm.services.setPaths(servicePaths.formPath, servicePaths.visitorPath);
});