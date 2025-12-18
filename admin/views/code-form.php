<?php
/**
 * QR Code Form View
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($qr_code);
$page_title = $is_edit ? __('Edit QR Code', 'qr-analytics') : __('Add New QR Code', 'qr-analytics');
?>
<div class="wrap qr-analytics-wrap">
    <h1><?php echo esc_html($page_title); ?></h1>

    <div class="qr-form-layout">
        <div class="qr-form-main">
            <form id="qr-code-form" class="qr-card" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo $is_edit ? esc_attr($qr_code->id) : '0'; ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="qr-name"><?php _e('Name', 'qr-analytics'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="qr-name" name="name" class="regular-text"
                                   value="<?php echo $is_edit ? esc_attr($qr_code->name) : ''; ?>"
                                   placeholder="<?php _e('e.g., Lavazza Promo Stand', 'qr-analytics'); ?>" required>
                            <p class="description"><?php _e('Internal name to identify this QR code.', 'qr-analytics'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="qr-slug"><?php _e('Slug', 'qr-analytics'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <div class="qr-slug-input">
                                <span class="qr-slug-prefix"><?php echo esc_html(home_url('/qr/')); ?></span>
                                <input type="text" id="qr-slug" name="slug" class="regular-text"
                                       value="<?php echo $is_edit ? esc_attr($qr_code->slug) : ''; ?>"
                                       placeholder="<?php _e('lavazza_promo_stand', 'qr-analytics'); ?>" required>
                            </div>
                            <p class="description"><?php _e('URL-friendly identifier. Use lowercase letters, numbers, underscores, and hyphens.', 'qr-analytics'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="qr-destination"><?php _e('Destination URL', 'qr-analytics'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="url" id="qr-destination" name="destination_url" class="large-text"
                                   value="<?php echo $is_edit ? esc_attr($qr_code->destination_url) : ''; ?>"
                                   placeholder="https://example.com/landing-page" required>
                            <p class="description"><?php _e('Where users will be redirected when they scan this QR code.', 'qr-analytics'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="qr-description"><?php _e('Description', 'qr-analytics'); ?></label>
                        </th>
                        <td>
                            <textarea id="qr-description" name="description" class="large-text" rows="3"
                                      placeholder="<?php _e('Optional notes about this QR code...', 'qr-analytics'); ?>"><?php echo $is_edit ? esc_textarea($qr_code->description) : ''; ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="qr-active"><?php _e('Status', 'qr-analytics'); ?></label>
                        </th>
                        <td>
                            <label class="qr-toggle">
                                <input type="checkbox" id="qr-active" name="is_active" value="1"
                                    <?php checked($is_edit ? $qr_code->is_active : 1, 1); ?>>
                                <span class="qr-toggle-slider"></span>
                                <span class="qr-toggle-label"><?php _e('Active', 'qr-analytics'); ?></span>
                            </label>
                            <p class="description"><?php _e('Inactive QR codes will redirect to homepage.', 'qr-analytics'); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="qr-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? __('Update QR Code', 'qr-analytics') : __('Create QR Code', 'qr-analytics'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-codes')); ?>" class="button button-large">
                        <?php _e('Cancel', 'qr-analytics'); ?>
                    </a>
                </div>
            </form>
        </div>

        <div class="qr-form-sidebar">
            <div class="qr-card qr-preview-card">
                <h3><?php _e('QR Code Preview', 'qr-analytics'); ?></h3>
                <div id="qr-preview-container">
                    <?php if ($is_edit) : ?>
                        <?php
                        $preview_url = QR_Router::get_qr_url($qr_code->slug);
                        $preview_svg = QR_Generator::generate($preview_url, array('size' => 280, 'margin' => 4));
                        echo $preview_svg;
                        ?>
                    <?php else : ?>
                        <div class="qr-preview-placeholder-text">
                            <span class="dashicons dashicons-editor-code"></span>
                            <p><?php _e('Enter a slug to preview QR code', 'qr-analytics'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="qr-preview-url">
                    <strong><?php _e('Tracking URL:', 'qr-analytics'); ?></strong>
                    <code id="qr-tracking-url"><?php echo $is_edit ? esc_html(QR_Router::get_qr_url($qr_code->slug)) : '-'; ?></code>
                </div>

                <?php if ($is_edit) : ?>
                    <div class="qr-download-options">
                        <h4><?php _e('Download for Print', 'qr-analytics'); ?></h4>
                        <div class="qr-size-options">
                            <button type="button" class="button qr-download-size" data-slug="<?php echo esc_attr($qr_code->slug); ?>" data-size="500">
                                500px
                            </button>
                            <button type="button" class="button qr-download-size" data-slug="<?php echo esc_attr($qr_code->slug); ?>" data-size="1000">
                                1000px
                            </button>
                            <button type="button" class="button qr-download-size" data-slug="<?php echo esc_attr($qr_code->slug); ?>" data-size="2000">
                                2000px
                            </button>
                        </div>
                        <p class="description"><?php _e('SVG format - scales infinitely for print.', 'qr-analytics'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($is_edit) : ?>
                <div class="qr-card">
                    <h3><?php _e('Quick Stats', 'qr-analytics'); ?></h3>
                    <?php
                    $click_count = QR_Database::get_click_count($qr_code->id);
                    ?>
                    <div class="qr-quick-stats">
                        <div class="qr-quick-stat">
                            <span class="qr-quick-stat-number"><?php echo esc_html($click_count); ?></span>
                            <span class="qr-quick-stat-label"><?php _e('Total Scans', 'qr-analytics'); ?></span>
                        </div>
                        <div class="qr-quick-stat">
                            <span class="qr-quick-stat-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($qr_code->created_at))); ?></span>
                            <span class="qr-quick-stat-label"><?php _e('Created', 'qr-analytics'); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=qr-analytics-reports&qr_id=' . $qr_code->id)); ?>" class="button button-block">
                        <?php _e('View Detailed Reports', 'qr-analytics'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/partials/footer.php'; ?>
</div>
