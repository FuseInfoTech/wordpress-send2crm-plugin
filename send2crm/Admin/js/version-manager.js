
jQuery(document).ready(function($) {
    var versionElement = $('#send2crm_js_version');

    $('#fetch-releases').on('click', function() {
        var fetchButton = $(this);
        fetchButton.prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: send2crmReleases.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_send2crm_releases',
                nonce: send2crmReleases.nonce
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
                fetchButton.prop('disabled', false).text('Refresh Releases');
            }
        });
    });
  
    function displayReleases(releases) {
        var html = '<h2>Available Releases</h2>';

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
                    html += '<button class="button button-small select-version" data-tag="' + release.tag_name + '">Select Version</button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        
        $('#releases-container').html(html);
    }
        
    // Handle notice dismiss button
    $(document).on('click', '#releases-container .notice-dismiss', function() {
        $(this).closest('.notice').fadeOut(400, function() {
            $(this).remove();
        });
    });
    
    // Handle download button clicks
    $(document).on('click', '.select-version', function() {
        let selectVersionButton = $(this);
        let tagName = selectVersionButton.data('tag');
        versionElement.val(tagName);
    });
});