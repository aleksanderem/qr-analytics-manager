<?php
/**
 * QR Codes List View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap qr-analytics-wrap">
    <h1 class="wp-heading-inline"><?php _e('QR Codes', 'qr-analytics'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-new')); ?>" class="page-title-action">
        <?php _e('Add New', 'qr-analytics'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (!empty($qr_codes)) : ?>
        <div class="qr-codes-grid">
            <?php foreach ($qr_codes as $code) : ?>
                <?php
                $qr_url = QR_Router::get_qr_url($code->slug);
                $svg = QR_Generator::generate($qr_url, array('size' => 200, 'margin' => 2));
                ?>
                <div class="qr-code-card" data-id="<?php echo esc_attr($code->id); ?>">
                    <div class="qr-code-preview">
                        <?php echo $svg; ?>
                    </div>

                    <div class="qr-code-info">
                        <h3><?php echo esc_html($code->name); ?></h3>
                        <p class="qr-code-slug">
                            <code><?php echo esc_html($code->qr_url); ?></code>
                            <button type="button" class="qr-copy-btn" data-url="<?php echo esc_attr($code->qr_url); ?>" title="<?php _e('Copy URL', 'qr-analytics'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </p>
                        <p class="qr-code-destination">
                            <span class="dashicons dashicons-admin-links"></span>
                            <a href="<?php echo esc_url($code->destination_url); ?>" target="_blank">
                                <?php echo esc_html(wp_trim_words($code->destination_url, 6, '...')); ?>
                            </a>
                        </p>

                        <div class="qr-code-stats">
                            <span class="qr-stat">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php echo esc_html($code->click_count); ?> <?php _e('scans', 'qr-analytics'); ?>
                            </span>
                            <span class="qr-status <?php echo $code->is_active ? 'qr-status-active' : 'qr-status-inactive'; ?>">
                                <?php echo $code->is_active ? __('Active', 'qr-analytics') : __('Inactive', 'qr-analytics'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="qr-code-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-new&edit=' . $code->id)); ?>" class="button">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'qr-analytics'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-reports&qr_id=' . $code->id)); ?>" class="button">
                            <span class="dashicons dashicons-chart-area"></span> <?php _e('Stats', 'qr-analytics'); ?>
                        </a>
                        <button type="button" class="button qr-download-btn" data-slug="<?php echo esc_attr($code->slug); ?>">
                            <span class="dashicons dashicons-download"></span> <?php _e('SVG', 'qr-analytics'); ?>
                        </button>
                        <button type="button" class="button button-link-delete qr-delete-btn" data-id="<?php echo esc_attr($code->id); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="qr-empty-state-box">
            <span class="dashicons dashicons-editor-code"></span>
            <h2><?php _e('No QR Codes Yet', 'qr-analytics'); ?></h2>
            <p><?php _e('Create your first QR code to start tracking scans and redirects.', 'qr-analytics'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-new')); ?>" class="button button-primary button-hero">
                <?php _e('Create QR Code', 'qr-analytics'); ?>
            </a>
        </div>
    <?php endif; ?>

    <?php include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/partials/footer.php'; ?>
</div>
