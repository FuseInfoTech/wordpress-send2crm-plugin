jQuery(document).ready(function($) {
    $('#fetch-releases').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('Fetching...');
        
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
                button.prop('disabled', false).text('Fetch Releases');
            }
        });
    });
    
    function displayReleases(releases) {
        var html = '<h2>Available Releases</h2>';
        
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
                html += '<button class="button button-small download-zip" data-tag="' + release.tag_name + '">Download Files</button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        
        $('#releases-container').html(html);
    }
    
    // Handle download button clicks
    $(document).on('click', '.download-zip', function() {
        var button = $(this);
        var tagName = button.data('tag');
        
        button.prop('disabled', true).text('Downloading...');
        
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
                    var message = 'Success! ' + response.message + '\n\nFiles downloaded:\n';
                    
                    $.each(response.files, function(filename, fileInfo) {
                        if (fileInfo.success) {
                            message += '\n✓ ' + filename;
                            if (fileInfo.skipped) {
                                message += ' (already exists)';
                            } else if (fileInfo.file_size) {
                                message += ' (' + fileInfo.file_size + ')';
                            }
                        } else {
                            message += '\n✗ ' + filename + ' - ' + fileInfo.message;
                        }
                    });
                    
                    message += '\n\nLocation: ' + response.download_dir;
                    alert(message);
                } else {
                    var errorMsg = 'Error: ' + (response.message || 'Download failed');
                    
                    if (response.files) {
                        errorMsg += '\n\nDetails:\n';
                        $.each(response.files, function(filename, fileInfo) {
                            if (!fileInfo.success) {
                                errorMsg += '\n✗ ' + filename + ' - ' + fileInfo.message;
                            }
                        });
                    }
                    
                    alert(errorMsg);
                }
            },
            error: function() {
                alert('Failed to download release files');
            },
            complete: function() {
                button.prop('disabled', false).text('Download Files');
            }
        });
    });
});