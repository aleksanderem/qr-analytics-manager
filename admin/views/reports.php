<?php
/**
 * Reports View
 */

if (!defined('ABSPATH')) {
    exit;
}

$selected_code = $selected_id ? QR_Database::get_qr_code($selected_id) : null;
?>
<div class="wrap qr-analytics-wrap">
    <h1><?php _e('QR Analytics Reports', 'qr-analytics'); ?></h1>

    <div class="qr-card qr-filters-card">
        <form method="get" class="qr-filters-form">
            <input type="hidden" name="page" value="qr-analytics-reports">

            <div class="qr-filter-group">
                <label for="qr-filter-code"><?php _e('QR Code', 'qr-analytics'); ?></label>
                <select id="qr-filter-code" name="qr_id">
                    <option value=""><?php _e('All QR Codes', 'qr-analytics'); ?></option>
                    <?php foreach ($qr_codes as $code) : ?>
                        <option value="<?php echo esc_attr($code->id); ?>" <?php selected($selected_id, $code->id); ?>>
                            <?php echo esc_html($code->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="qr-filter-group">
                <label for="qr-filter-start"><?php _e('Start Date', 'qr-analytics'); ?></label>
                <input type="date" id="qr-filter-start" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            </div>

            <div class="qr-filter-group">
                <label for="qr-filter-end"><?php _e('End Date', 'qr-analytics'); ?></label>
                <input type="date" id="qr-filter-end" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </div>

            <div class="qr-filter-group qr-filter-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Apply Filters', 'qr-analytics'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-reports')); ?>" class="button">
                    <?php _e('Reset', 'qr-analytics'); ?>
                </a>
            </div>
        </form>
    </div>

    <?php if ($selected_code) : ?>
        <div class="qr-report-header qr-card">
            <div class="qr-report-code-info">
                <h2><?php echo esc_html($selected_code->name); ?></h2>
                <p>
                    <code><?php echo esc_html(QR_Router::get_qr_url($selected_code->slug)); ?></code>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                    <a href="<?php echo esc_url($selected_code->destination_url); ?>" target="_blank">
                        <?php echo esc_html($selected_code->destination_url); ?>
                    </a>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="qr-stats-grid">
        <div class="qr-stat-card">
            <div class="qr-stat-icon qr-stat-clicks">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="qr-stat-content">
                <?php
                $total_clicks = 0;
                foreach ($clicks_by_date as $day) {
                    $total_clicks += (int) $day->clicks;
                }
                ?>
                <span class="qr-stat-number"><?php echo esc_html($total_clicks); ?></span>
                <span class="qr-stat-label"><?php _e('Total Scans (Period)', 'qr-analytics'); ?></span>
            </div>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="qr-stat-content">
                <?php
                $days_count = count($clicks_by_date);
                $avg_clicks = $days_count > 0 ? round($total_clicks / $days_count, 1) : 0;
                ?>
                <span class="qr-stat-number"><?php echo esc_html($avg_clicks); ?></span>
                <span class="qr-stat-label"><?php _e('Daily Average', 'qr-analytics'); ?></span>
            </div>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-icon qr-stat-active">
                <span class="dashicons dashicons-smartphone"></span>
            </div>
            <div class="qr-stat-content">
                <?php
                $mobile_clicks = 0;
                foreach ($clicks_by_device as $device) {
                    if ($device->device_type === 'mobile') {
                        $mobile_clicks = (int) $device->clicks;
                        break;
                    }
                }
                $mobile_percent = $total_clicks > 0 ? round(($mobile_clicks / $total_clicks) * 100) : 0;
                ?>
                <span class="qr-stat-number"><?php echo esc_html($mobile_percent); ?>%</span>
                <span class="qr-stat-label"><?php _e('Mobile Scans', 'qr-analytics'); ?></span>
            </div>
        </div>
    </div>

    <div class="qr-dashboard-grid">
        <div class="qr-card qr-chart-card">
            <h2><?php _e('Scans Over Time', 'qr-analytics'); ?></h2>
            <?php if (!empty($clicks_by_date)) : ?>
                <canvas id="qr-timeline-chart"></canvas>
            <?php else : ?>
                <p class="qr-empty-chart"><?php _e('No data available for the selected period.', 'qr-analytics'); ?></p>
            <?php endif; ?>
        </div>

        <div class="qr-card qr-chart-card-small">
            <h2><?php _e('Device Types', 'qr-analytics'); ?></h2>
            <?php if (!empty($clicks_by_device)) : ?>
                <canvas id="qr-device-chart"></canvas>
            <?php else : ?>
                <p class="qr-empty-chart"><?php _e('No data available.', 'qr-analytics'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($clicks_by_country)) : ?>
        <div class="qr-card">
            <h2><?php _e('Scans by Country', 'qr-analytics'); ?></h2>
            <table class="qr-table">
                <thead>
                    <tr>
                        <th><?php _e('Country', 'qr-analytics'); ?></th>
                        <th><?php _e('Scans', 'qr-analytics'); ?></th>
                        <th><?php _e('Percentage', 'qr-analytics'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clicks_by_country as $country) : ?>
                        <tr>
                            <td><?php echo esc_html($country->country ?: __('Unknown', 'qr-analytics')); ?></td>
                            <td><?php echo esc_html($country->clicks); ?></td>
                            <td>
                                <?php
                                $percent = $total_clicks > 0 ? round(($country->clicks / $total_clicks) * 100, 1) : 0;
                                ?>
                                <div class="qr-progress-bar">
                                    <div class="qr-progress-fill" style="width: <?php echo esc_attr($percent); ?>%"></div>
                                    <span><?php echo esc_html($percent); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($selected_id === 0 && !empty($qr_codes)) : ?>
        <div class="qr-card">
            <h2><?php _e('Comparison by QR Code', 'qr-analytics'); ?></h2>
            <table class="qr-table">
                <thead>
                    <tr>
                        <th><?php _e('QR Code', 'qr-analytics'); ?></th>
                        <th><?php _e('Scans (Period)', 'qr-analytics'); ?></th>
                        <th><?php _e('Total Scans', 'qr-analytics'); ?></th>
                        <th><?php _e('Status', 'qr-analytics'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($qr_codes as $code) :
                        $period_clicks = QR_Database::get_clicks_by_date($code->id, $start_date, $end_date . ' 23:59:59');
                        $period_total = 0;
                        foreach ($period_clicks as $day) {
                            $period_total += (int) $day->clicks;
                        }
                        $all_time_total = QR_Database::get_click_count($code->id);
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-reports&qr_id=' . $code->id . '&start_date=' . $start_date . '&end_date=' . $end_date)); ?>">
                                    <?php echo esc_html($code->name); ?>
                                </a>
                            </td>
                            <td><strong><?php echo esc_html($period_total); ?></strong></td>
                            <td><?php echo esc_html($all_time_total); ?></td>
                            <td>
                                <span class="qr-status <?php echo $code->is_active ? 'qr-status-active' : 'qr-status-inactive'; ?>">
                                    <?php echo $code->is_active ? __('Active', 'qr-analytics') : __('Inactive', 'qr-analytics'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Timeline chart
    const timelineData = <?php echo wp_json_encode($clicks_by_date); ?>;
    const timelineCtx = document.getElementById('qr-timeline-chart');

    if (timelineCtx && timelineData.length > 0) {
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: timelineData.map(function(item) { return item.date; }),
                datasets: [{
                    label: '<?php echo esc_js(__('Scans', 'qr-analytics')); ?>',
                    data: timelineData.map(function(item) { return parseInt(item.clicks); }),
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

    // Device chart
    const deviceData = <?php echo wp_json_encode($clicks_by_device); ?>;
    const deviceCtx = document.getElementById('qr-device-chart');

    if (deviceCtx && deviceData.length > 0) {
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(function(item) { return item.device_type || 'Unknown'; }),
                datasets: [{
                    data: deviceData.map(function(item) { return parseInt(item.clicks); }),
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
