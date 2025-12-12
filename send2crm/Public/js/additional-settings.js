//TODO Should we check if Send2CRM.js is working before adding the event listenter?
//TODO Only add listener and settings/paths if the jsObjects are populated
window.addEventListener('send2crmLoading', (evt) => {
	// All values are optional, defaults will be used if not present.
	send2crm.applySettings(
        additionalSettings 
    );
    send2crm.services.setPaths(servicePaths.formPath, servicePaths.visitorPath);
});