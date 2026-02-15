/**
 * @file This file contains all of the javascript for updated the Additional Settings Page UI
 * @author Chris Tyler
 * @version 1.0.1
 */
jQuery( document ).ready( function($) {
    let ipLookupCheckbox = $('#ip_lookup-checkbox');
    let ipLookupInput = $('#ip_lookup');

    ipLookupCheckbox.change(function() {
        if (ipLookupCheckbox.is(':checked')) {
            //set input type to hidden instead of setting display to none. Do not used hide() as that doesnt change the type
            ipLookupInput.attr('type', 'hidden');
            ipLookupInput.val('false');
        } else {
            ipLookupInput.attr('type', 'text');
            if (ipLookupInput.val() === 'false') {
                ipLookupInput.val('');
            }
        }
    });
})