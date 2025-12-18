<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$home_url = home_url();
$parsed = parse_url($home_url);
$port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
$scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
?>
<div class="wrap qr-analytics-wrap">
    <h1><?php _e('QR Analytics Settings', 'qr-analytics'); ?></h1>

    <?php if ($is_localhost) : ?>
        <div class="notice notice-info">
            <p>
                <span class="dashicons dashicons-info"></span>
                <?php _e('Localhost detected! You can choose a LAN IP address so QR codes can be scanned from mobile devices on the same network.', 'qr-analytics'); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="qr-card">
        <h2><?php _e('QR Code Base URL', 'qr-analytics'); ?></h2>
        <p class="description">
            <?php _e('Select the base URL that will be encoded in QR codes. This is useful when testing locally - choose your LAN IP so mobile devices can reach your development server.', 'qr-analytics'); ?>
        </p>

        <form id="qr-settings-form" class="qr-settings-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Base URL for QR Codes', 'qr-analytics'); ?></label>
                    </th>
                    <td>
                        <fieldset id="qr-url-options">
                            <!-- Default option - always shown -->
                            <label class="qr-url-option current">
                                <input type="radio" name="base_url" value="<?php echo esc_attr($home_url); ?>"
                                    <?php checked(empty($current_base_url) || $current_base_url === $home_url); ?>>
                                <span class="qr-url-label">
                                    <strong><?php echo esc_html($parsed['host'] . $port); ?></strong>
                                    <span class="qr-badge qr-badge-current"><?php _e('Current', 'qr-analytics'); ?></span>
                                </span>
                                <code class="qr-url-preview"><?php echo esc_html($home_url); ?>/qr/example/</code>
                            </label>

                            <!-- LAN options container - loaded via AJAX -->
                            <div id="qr-lan-options">
                                <?php if ($is_localhost) : ?>
                                    <div class="qr-loading-lan">
                                        <span class="dashicons dashicons-update qr-spin"></span>
                                        <?php _e('Detecting LAN addresses...', 'qr-analytics'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Custom URL option -->
                            <label class="qr-url-option custom">
                                <input type="radio" name="base_url" value="custom"
                                    <?php checked(!empty($current_base_url) && $current_base_url !== $home_url); ?>>
                                <span class="qr-url-label">
                                    <strong><?php _e('Custom URL', 'qr-analytics'); ?></strong>
                                </span>
                                <input type="url" id="custom_base_url" name="custom_base_url" class="regular-text"
                                    value="<?php echo (!empty($current_base_url) && $current_base_url !== $home_url) ? esc_attr($current_base_url) : ''; ?>"
                                    placeholder="https://your-domain.com">
                            </label>
                        </fieldset>

                        <p class="description" style="margin-top: 15px;">
                            <?php _e('Note: Changing this will affect newly generated QR codes. Existing QR code images will need to be re-downloaded.', 'qr-analytics'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Settings', 'qr-analytics'); ?>
                </button>
            </p>
        </form>
    </div>

    <div class="qr-card">
        <h2><?php _e('Data Tools', 'qr-analytics'); ?></h2>
        <p class="description">
            <?php _e('Tools for managing QR code data integrity and cleanup.', 'qr-analytics'); ?>
        </p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Verify Data Integrity', 'qr-analytics'); ?></th>
                <td>
                    <button type="button" id="qr-verify-data" class="button">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Run Verification', 'qr-analytics'); ?>
                    </button>
                    <p class="description"><?php _e('Check all QR codes for missing or invalid data (name, slug, destination URL).', 'qr-analytics'); ?></p>
                    <div id="qr-verify-results" style="margin-top: 10px; display: none;"></div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Delete All Data', 'qr-analytics'); ?></th>
                <td>
                    <button type="button" id="qr-delete-all" class="button button-link-delete">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete All QR Codes', 'qr-analytics'); ?>
                    </button>
                    <p class="description"><?php _e('Permanently delete all QR codes and their click statistics. This action cannot be undone!', 'qr-analytics'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="qr-card">
        <h2><?php _e('Current Configuration', 'qr-analytics'); ?></h2>
        <table class="qr-info-table">
            <tr>
                <th><?php _e('WordPress Home URL', 'qr-analytics'); ?></th>
                <td><code><?php echo esc_html($home_url); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('QR Base URL (active)', 'qr-analytics'); ?></th>
                <td><code><?php echo esc_html(QR_Admin::get_qr_base_url()); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('Example QR URL', 'qr-analytics'); ?></th>
                <td><code><?php echo esc_html(QR_Router::get_qr_url('example_slug')); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('Environment', 'qr-analytics'); ?></th>
                <td>
                    <?php if ($is_localhost) : ?>
                        <span class="qr-badge qr-badge-warning"><?php _e('Localhost / Development', 'qr-analytics'); ?></span>
                    <?php else : ?>
                        <span class="qr-badge qr-badge-success"><?php _e('Production', 'qr-analytics'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<style>
.qr-settings-form fieldset {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.qr-url-option {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f9f9f9;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.qr-url-option:hover {
    border-color: #2271b1;
    background: #f0f6fc;
}

.qr-url-option input[type="radio"] {
    margin: 0;
}

.qr-url-option input[type="radio"]:checked + .qr-url-label {
    color: #2271b1;
}

.qr-url-option:has(input[type="radio"]:checked) {
    border-color: #2271b1;
    background: #f0f6fc;
}

.qr-url-label {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 200px;
}

.qr-url-preview {
    font-size: 12px;
    color: #666;
    background: #fff;
    padding: 4px 8px;
    border-radius: 3px;
}

.qr-url-option.custom {
    flex-direction: row;
    flex-wrap: wrap;
}

.qr-url-option.custom input[type="url"] {
    flex: 1;
    min-width: 250px;
    margin-left: auto;
}

.qr-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.qr-badge-lan {
    background: #e7f3ff;
    color: #2271b1;
}

.qr-badge-current {
    background: #f0f0f1;
    color: #50575e;
}

.qr-badge-warning {
    background: #fcf0e3;
    color: #9a6700;
}

.qr-badge-success {
    background: #edfaef;
    color: #00a32a;
}

.qr-info-table {
    width: 100%;
    border-collapse: collapse;
}

.qr-info-table th,
.qr-info-table td {
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f1;
}

.qr-info-table th {
    width: 200px;
    font-weight: 600;
    color: #1d2327;
}

.qr-info-table code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
}

.qr-loading-lan {
    padding: 12px 15px;
    color: #666;
    font-style: italic;
}

.qr-spin {
    animation: qr-spin 1s linear infinite;
}

@keyframes qr-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    var homeUrl = '<?php echo esc_js($home_url); ?>';
    var scheme = '<?php echo esc_js($scheme); ?>';
    var port = '<?php echo esc_js($port); ?>';
    var currentBaseUrl = '<?php echo esc_js($current_base_url); ?>';
    var isLocalhost = <?php echo $is_localhost ? 'true' : 'false'; ?>;

    // Load LAN IPs asynchronously
    if (isLocalhost) {
        $.post(qrAnalytics.ajaxUrl, {
            action: 'qr_get_lan_ips',
            nonce: qrAnalytics.nonce
        })
        .done(function(response) {
            var $container = $('#qr-lan-options');
            $container.empty();

            if (response.success && response.data.ips && response.data.ips.length > 0) {
                response.data.ips.forEach(function(ip) {
                    var url = scheme + '://' + ip + port;
                    var isChecked = (currentBaseUrl === url) ? 'checked' : '';
                    var html = '<label class="qr-url-option lan">' +
                        '<input type="radio" name="base_url" value="' + url + '" ' + isChecked + '>' +
                        '<span class="qr-url-label">' +
                            '<strong>' + ip + port + '</strong>' +
                            '<span class="qr-badge qr-badge-lan">LAN</span>' +
                        '</span>' +
                        '<code class="qr-url-preview">' + url + '/qr/example/</code>' +
                    '</label>';
                    $container.append(html);
                });
            } else {
                $container.html('<p class="description"><?php _e('No LAN IPs detected. Use Custom URL to enter your network IP manually.', 'qr-analytics'); ?></p>');
            }
        })
        .fail(function() {
            $('#qr-lan-options').html('<p class="description"><?php _e('Could not detect LAN IPs. Use Custom URL instead.', 'qr-analytics'); ?></p>');
        });
    }

    // Form submit
    $('#qr-settings-form').on('submit', function(e) {
        e.preventDefault();

        var selectedValue = $('input[name="base_url"]:checked').val();
        var baseUrl = selectedValue === 'custom' ? $('#custom_base_url').val() : selectedValue;

        // If using default home URL, save empty string
        if (baseUrl === homeUrl) {
            baseUrl = '';
        }

        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update qr-spin"></span> <?php _e('Saving...', 'qr-analytics'); ?>');

        $.post(qrAnalytics.ajaxUrl, {
            action: 'qr_save_base_url',
            nonce: qrAnalytics.nonce,
            base_url: baseUrl
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || '<?php _e('Error saving settings', 'qr-analytics'); ?>');
            }
        })
        .fail(function() {
            alert('<?php _e('Error saving settings', 'qr-analytics'); ?>');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Enable custom URL input when custom radio is selected
    $(document).on('change', 'input[name="base_url"]', function() {
        var isCustom = $(this).val() === 'custom';
        $('#custom_base_url').prop('disabled', !isCustom);
        if (isCustom) $('#custom_base_url').focus();
    });

    // Initial state
    $('#custom_base_url').prop('disabled', $('input[name="base_url"]:checked').val() !== 'custom');

    // Verify data integrity
    $('#qr-verify-data').on('click', function() {
        var $btn = $(this);
        var $results = $('#qr-verify-results');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update qr-spin"></span> <?php _e('Verifying...', 'qr-analytics'); ?>');
        $results.hide();

        $.post(qrAnalytics.ajaxUrl, {
            action: 'qr_verify_data',
            nonce: qrAnalytics.nonce
        })
        .done(function(response) {
            $results.show();
            if (response.success) {
                var data = response.data;
                var hasIssues = data.issues && data.issues.length > 0;
                var html = '<div class="notice notice-' + (hasIssues ? 'warning' : 'success') + ' inline" style="margin: 0;">';
                html += '<p><strong>' + data.message + '</strong></p>';
                html += '<p><?php _e('Total QR codes:', 'qr-analytics'); ?> ' + data.stats.total_codes + '</p>';
                html += '<p><?php _e('Total clicks:', 'qr-analytics'); ?> ' + data.stats.total_clicks + '</p>';

                if (hasIssues) {
                    html += '<p><strong><?php _e('Issues found:', 'qr-analytics'); ?></strong></p><ul style="margin-left: 20px;">';
                    data.issues.forEach(function(item) {
                        html += '<li><strong>' + item.name + ':</strong> ' + item.problems.join(', ') + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<p style="color: #00a32a;"><span class="dashicons dashicons-yes"></span> <?php _e('All data integrity checks passed!', 'qr-analytics'); ?></p>';
                }
                html += '</div>';
                $results.html(html);
            } else {
                $results.html('<div class="notice notice-error inline" style="margin: 0;"><p>' + (response.data.message || '<?php _e('Verification failed', 'qr-analytics'); ?>') + '</p></div>');
            }
        })
        .fail(function() {
            $results.show().html('<div class="notice notice-error inline" style="margin: 0;"><p><?php _e('Verification failed', 'qr-analytics'); ?></p></div>');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Delete all data
    $('#qr-delete-all').on('click', function() {
        var confirmMsg = '<?php _e('Are you sure you want to delete ALL QR codes and their statistics?', 'qr-analytics'); ?>\n\n<?php _e('This action CANNOT be undone!', 'qr-analytics'); ?>';

        if (!confirm(confirmMsg)) {
            return;
        }

        // Double confirmation
        var confirmMsg2 = '<?php _e('Type DELETE to confirm:', 'qr-analytics'); ?>';
        var userInput = prompt(confirmMsg2);

        if (userInput !== 'DELETE') {
            alert('<?php _e('Deletion cancelled.', 'qr-analytics'); ?>');
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update qr-spin"></span> <?php _e('Deleting...', 'qr-analytics'); ?>');

        $.post(qrAnalytics.ajaxUrl, {
            action: 'qr_delete_all_data',
            nonce: qrAnalytics.nonce,
            confirm: 'DELETE'
        })
        .done(function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || '<?php _e('Deletion failed', 'qr-analytics'); ?>');
            }
        })
        .fail(function() {
            alert('<?php _e('Deletion failed', 'qr-analytics'); ?>');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
});
</script>

<?php include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/partials/footer.php'; ?>
