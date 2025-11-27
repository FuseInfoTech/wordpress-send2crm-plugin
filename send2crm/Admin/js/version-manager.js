jQuery(document).ready(function($) {
    let jsLocationInput = $('#send2crm_js_location');
    let jsVersionInput = $('#send2crm_js_version');
    let useCDNCheckbox = $('#use_cdn');

    $('#fetch-releases').on('click', function() {
        var fetchButton = $(this);
        fetchButton.prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: githubReleases.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_send2crm_releases',
                nonce: githubReleases.nonce
            },
            success: function(response) {
                if (response.success && response.releases) {
                    displayReleases(response.releases);
                } else {
                    $('#releases-container').html(
                        '<div class="notice notice-error"><p>' + 
                        (response.message || 'Failed to fetch releases') + 
                        '</p></div>'
                    );
                }
            },
            error: function() {
                $('#releases-container').html(
                    '<div class="notice notice-error"><p>Failed to fetch releases</p></div>'
                );
            },
            complete: function() {
                fetchButton.prop('disabled', false).text('Fetch Releases');
            }
        });
    });

    //when the "Use CDN" checkbox is checked, change the location of the JS file use the cdn url prefix stored in githubReleases.cdn_prefix. 
    $('#use_cdn').on('change', function() {
        if ($(this).is(':checked')) {
            updateReleaseSettings("",githubReleases.cdn_prefix + "@" + jsVersionInput.val() + "/"); ;
        } 
        else {
            //get the current page url prefix
            updateReleaseSettings("", githubReleases.local_prefix + jsVersionInput.val() + "/");
        }
    });

    function updateReleaseSettings(version, location) {
        if (version) {
            jsVersionInput.val(version);
        }
        if (location) {
            jsLocationInput.val(location);
        }
    }

    
    function displayReleases(releases) {
        var html = '<h2>Available Releases</h2>';
        var versionElement = $('#send2crm_js_version');
        var version = versionElement.val();

        if (releases.length === 0) {
            html += '<p>No releases found matching the criteria.</p>';
        } else {
            html += '<table id="releases-header" class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Version</th><th>Name</th><th>Published</th><th>Actions</th></tr></thead>';
            html += '</table>';
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += '<table id="releases-body" class="wp-list-table widefat fixed striped">';
            html += '<tbody>';
            
            releases.forEach(function(release) {
                html += '<tr>';
                html += '<td><strong>' + release.tag_name + '</strong></td>';
                html += '<td>' + release.name + '</td>';
                html += '<td>' + new Date(release.published_at).toLocaleDateString() + '</td>';
                html += '<td>';
                html += '<a href="' + release.html_url + '" target="_blank" class="button button-small">View</a> ';
                if (release.tag_name === version) {
                    html += '<span class="button button-small button-primary"><span class="dashicons dashicons-saved" style="font-size: 13px; width: 13px; height: 13px; margin-top: 5px;"></span> Current Version</span>';
                } else {
                    html += '<button class="button button-small download-zip" data-tag="' + release.tag_name + '">Select Version</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        
        $('#releases-container').html(html);
    }
    
    function showNotice(type, message, autoDismiss) {
        // Remove any existing notices
        $('#releases-container .notice-dismissible').remove();
        
        var noticeClass = 'notice notice-' + type;
        if (autoDismiss) {
            noticeClass += ' notice-dismissible';
        }
        
        var icon = type === 'success' ? 'yes-alt' : 'warning';
        
        var noticeHtml = '<div class="' + noticeClass + '" style="position: relative;">';
        noticeHtml += '<p><span class="dashicons dashicons-' + icon + '" style="color: ' + (type === 'success' ? '#46b450' : '#dc3232') + '; margin-right: 5px;"></span>' + message + '</p>';
        if (autoDismiss) {
            noticeHtml += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        }
        noticeHtml += '</div>';
        
        $('#releases-container').prepend(noticeHtml);
        
        // Auto-dismiss after 5 seconds if requested
        if (autoDismiss) {
            setTimeout(function() {
                $('#releases-container .notice-dismissible').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
    
    // Handle notice dismiss button
    $(document).on('click', '#releases-container .notice-dismiss', function() {
        $(this).closest('.notice').fadeOut(400, function() {
            $(this).remove();
        });
    });
    
    // Handle download button clicks
    $(document).on('click', '.download-zip', function() {

        let downloadButton = $(this);
        let tagName = downloadButton.data('tag');
        if (useCDNCheckbox.is(':checked')) {
            updateReleaseSettings($(this).data('tag'), githubReleases.cdn_prefix + "@" + tagName + "/");
            showNotice('success', 'Version ' + tagName + ' will be used from the CDN.', true); 
            return;
        }
        
        // Show loading state with spinner
        downloadButton.prop('disabled', true)
            .html('<span class="dashicons dashicons-update dashicons-spin" style="font-size: 13px; width: 13px; height: 13px; margin-top: 5px;"></span> Downloading...');
        
        $.ajax({
            url: githubReleases.ajax_url,
            type: 'POST',
            data: {
                action: 'download_github_release',
                nonce: githubReleases.nonce,
                tag_name: tagName
            },
            success: function(response) {
                if (response.success) {
                    // Update the input fields
                    updateReleaseSettings(tagName, upload_url)
                    
                    // Show success notice
                    showNotice('success', 'Version ' + tagName + ' has been successfully installed!', true);
                                       
                    // Update any other "Current Version" buttons back to "Select Version"
                    $('.download-zip').not(downloadButton).each(function() {
                        var btn = $(this);
                        if (btn.hasClass('button-primary')) {
                            btn.removeClass('button-primary')
                                .prop('disabled', false)
                                .text('Select Version');
                        }
                    });
                    // Update button to success state
                    downloadButton
                        .addClass('button-primary')
                        .html('<span class="dashicons dashicons-yes" style="font-size: 13px; width: 13px; height: 13px; margin-top: 5px;"></span> Installed Version')
                        .prop('disabled', true);
                    
                } else {
                    // Show error notice
                    var errorMsg = response.message || 'Download failed';
                    showNotice('error', errorMsg, true);
                    
                    // Reset button
                    downloadButton.prop('disabled', false).text('Select Version');
                }
            },
            error: function() {
                showNotice('error', 'Failed to download release files. Please try again.', true);
                downloadButton.prop('disabled', false).text('Select Version');
            }
        });
    });
});