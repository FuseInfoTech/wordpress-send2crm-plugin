
jQuery(document).ready(function($) {
    let fetchIcon = $('#fetch-icon');
    const style = document.createElement('style');
    style.innerHTML = `
        .dashicons.spin {
        animation: dashicons-spin 1s infinite;
        animation-timing-function: linear;
        }

        @keyframes dashicons-spin {
        0% {
            transform: rotate( 0deg );
        }
        100% {
            transform: rotate( 360deg );
        }
        }
    `;

    document.head.appendChild(style);

    displayReleases();
    $('#fetch-releases').on('click', function() {
        var fetchButton = $(this);
        fetchButton.prop('disabled', true);
        fetchIcon.addClass('spin');
        
        $.ajax({
            url: send2crmReleases.ajax_url,
            type: 'POST',
            data: {
                action: 'send2crm_fetch_releases',
                nonce: send2crmReleases.nonce
            },
            success: function(response) {
                if (response.success && response.releases) {
                    localStorage.setItem('send2crm_releases', JSON.stringify(response.releases));
                    displayReleases();
                } else {
                    addError(response.message);
                }
                fetchButton.prop('disabled', false);
                fetchIcon.removeClass('spin');
            },
            error: function() {
                addError();
            },
            complete: function() {
                fetchButton.prop('disabled', false);
                fetchIcon.removeClass('spin');
            }
        });
    });


    function addError(message) {
        let errorHtml = $('<div id="settings-error-fetch_failed" class="notice notice-error settings-error is-dismissible"><p><strong>' + 
            (message || 'Something went wrong while fetching releases.') + 
            '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');

        errorHtml.on('click', 'button.notice-dismiss', function() {

            $(this).closest('.notice').fadeTo(100, 0, function() {
                $(this).slideUp(100, function() {
                    $(this).remove();
                });
            });
        });
        $('div.wrap').prepend(errorHtml);
    }
  
    function displayReleases() {
        let versionElement = $('#' + send2crmReleases.version_element_id);
        let currentVersion = versionElement.data('current-version');
        let releases = null;
        const storedReleases = localStorage.getItem('send2crm_releases');

        if (storedReleases) {
            try {
                const parsedReleases = JSON.parse(storedReleases);
                releases = Object.values(parsedReleases);
                if (!Array.isArray(releases) || releases.length === 0) {
                    releases = null;
                }
            } catch (error) {
                console.error('Error parsing releases from localStorage. Clearing releases so we can try again:', error);
                localStorage.removeItem('send2crm_releases');
                //TODO display an error to the user so it's clear fetching releases failed.
                releases = null;
            }
        }
        // Options stores releases as an options array to be added to the select element.
        let options = [];
        if (releases) {
            //TODO Add an option to say 'select a version tha tis selected if current version is empty.
            //TODO handle cases where current version is not in releases array
            releases.forEach((release) => {
            options.push(
                $('<option>', {
                    value: release.tag_name,
                    text: release.tag_name + ' - ( Published ' + (new Date(release.published_at)).toLocaleDateString()  +  ' )',
                    selected: currentVersion === release.tag_name
                })
            );
        });
        } else if (currentVersion) {
            options.push(
                $('<option>', {
                    value: currentVersion,
                    text: currentVersion + ' (Fetch Version for Details)',
                    selected: true
                })
            );
        } else {
           options.push(
                $('<option>', {
                    value: '',
                    text: 'Click "Fetch Versions" to load available versions',
                    selected: true,
                    disabled: true
                })
            );
        }
        //Clear Version Select Element and add options
        versionElement.empty().append(options);

    }
});