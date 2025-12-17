<?php
/**
 * Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap qr-analytics-wrap">
    <h1><?php _e('QR Analytics Dashboard', 'qr-analytics'); ?></h1>

    <div class="qr-stats-grid">
        <div class="qr-stat-card">
            <div class="qr-stat-icon">
                <span class="dashicons dashicons-editor-code"></span>
            </div>
            <div class="qr-stat-content">
                <span class="qr-stat-number"><?php echo esc_html($stats['total_codes']); ?></span>
                <span class="qr-stat-label"><?php _e('Total QR Codes', 'qr-analytics'); ?></span>
            </div>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-icon qr-stat-active">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="qr-stat-content">
                <span class="qr-stat-number"><?php echo esc_html($stats['active_codes']); ?></span>
                <span class="qr-stat-label"><?php _e('Active Codes', 'qr-analytics'); ?></span>
            </div>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-icon qr-stat-clicks">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="qr-stat-content">
                <span class="qr-stat-number"><?php echo esc_html($stats['total_clicks']); ?></span>
                <span class="qr-stat-label"><?php _e('Total Scans', 'qr-analytics'); ?></span>
            </div>
        </div>
    </div>

    <div class="qr-dashboard-grid">
        <div class="qr-card qr-chart-card">
            <h2><?php _e('Scans Over Time (Last 30 Days)', 'qr-analytics'); ?></h2>
            <canvas id="qr-clicks-chart"></canvas>
        </div>

        <div class="qr-card qr-chart-card-small">
            <h2><?php _e('Device Distribution', 'qr-analytics'); ?></h2>
            <canvas id="qr-device-chart"></canvas>
        </div>
    </div>

    <div class="qr-card">
        <h2><?php _e('Top Performing QR Codes', 'qr-analytics'); ?></h2>
        <?php if (!empty($top_codes)) : ?>
            <table class="qr-table">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'qr-analytics'); ?></th>
                        <th><?php _e('Slug', 'qr-analytics'); ?></th>
                        <th><?php _e('Destination', 'qr-analytics'); ?></th>
                        <th><?php _e('Scans', 'qr-analytics'); ?></th>
                        <th><?php _e('Status', 'qr-analytics'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_codes as $code) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-new&edit=' . $code->id)); ?>">
                                    <?php echo esc_html($code->name); ?>
                                </a>
                            </td>
                            <td><code>/qr/<?php echo esc_html($code->slug); ?></code></td>
                            <td>
                                <a href="<?php echo esc_url($code->destination_url); ?>" target="_blank" class="qr-url-link">
                                    <?php echo esc_html(wp_trim_words($code->destination_url, 5, '...')); ?>
                                </a>
                            </td>
                            <td><strong><?php echo esc_html($code->click_count); ?></strong></td>
                            <td>
                                <span class="qr-status <?php echo $code->is_active ? 'qr-status-active' : 'qr-status-inactive'; ?>">
                                    <?php echo $code->is_active ? __('Active', 'qr-analytics') : __('Inactive', 'qr-analytics'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="qr-empty-state">
                <?php _e('No QR codes yet.', 'qr-analytics'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-new')); ?>">
                    <?php _e('Create your first QR code', 'qr-analytics'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clicks over time chart
    const clicksData = <?php echo json_encode($clicks_by_date); ?>;
    const clicksCtx = document.getElementById('qr-clicks-chart');

    if (clicksCtx && clicksData.length > 0) {
        new Chart(clicksCtx, {
            type: 'line',
            data: {
                labels: clicksData.map(item => item.date),
                datasets: [{
                    label: '<?php _e('Scans', 'qr-analytics'); ?>',
                    data: clicksData.map(item => parseInt(item.clicks)),
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Device distribution chart
    const deviceData = <?php echo json_encode($clicks_by_device); ?>;
    const deviceCtx = document.getElementById('qr-device-chart');

    if (deviceCtx && deviceData.length > 0) {
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(item => item.device_type || 'Unknown'),
                datasets: [{
                    data: deviceData.map(item => parseInt(item.clicks)),
                    backgroundColor: ['#2271b1', '#72aee6', '#c3c4c7', '#d63638', '#dba617']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
