/**
 * QR Analytics Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        QRAnalytics.init();
    });

    var QRAnalytics = {
        init: function() {
            this.bindEvents();
            this.initSlugGeneration();
        },

        bindEvents: function() {
            // Form submission
            $('#qr-code-form').on('submit', this.handleFormSubmit.bind(this));

            // Delete button
            $(document).on('click', '.qr-delete-btn', this.handleDelete.bind(this));

            // Copy URL button
            $(document).on('click', '.qr-copy-btn', this.handleCopyUrl.bind(this));

            // Download buttons
            $(document).on('click', '.qr-download-btn', this.handleDownload.bind(this));
            $(document).on('click', '.qr-download-size', this.handleDownloadSize.bind(this));

            // Slug input - auto-generate from name
            $('#qr-name').on('blur', this.autoGenerateSlug.bind(this));

            // Slug input - generate preview
            $('#qr-slug').on('blur', this.updatePreview.bind(this));
        },

        initSlugGeneration: function() {
            // Auto-generate slug from name if slug is empty
            var nameField = $('#qr-name');
            var slugField = $('#qr-slug');

            if (nameField.length && slugField.length && !slugField.val()) {
                nameField.on('input', function() {
                    var slug = QRAnalytics.generateSlug($(this).val());
                    slugField.val(slug);
                    QRAnalytics.updateTrackingUrl(slug);
                });
            }
        },

        generateSlug: function(text) {
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '_')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '')
                .substring(0, 50);
        },

        autoGenerateSlug: function(e) {
            var nameField = $(e.target);
            var slugField = $('#qr-slug');

            if (!slugField.val() && nameField.val()) {
                var slug = this.generateSlug(nameField.val());
                slugField.val(slug);
                this.updateTrackingUrl(slug);
            }
        },

        updateTrackingUrl: function(slug) {
            var urlDisplay = $('#qr-tracking-url');
            if (urlDisplay.length && slug) {
                urlDisplay.text(qrAnalytics.homeUrl + '/qr/' + slug + '/');
            }
        },

        updatePreview: function(e) {
            var slug = $(e.target).val();
            this.updateTrackingUrl(slug);
        },

        handleFormSubmit: function(e) {
            e.preventDefault();

            var form = $(e.target);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.html();

            submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update qr-loading"></span> Saving...');

            var formData = {
                action: 'qr_save_code',
                nonce: qrAnalytics.nonce,
                id: form.find('input[name="id"]').val() || 0,
                name: form.find('#qr-name').val(),
                slug: form.find('#qr-slug').val(),
                destination_url: form.find('#qr-destination').val(),
                description: form.find('#qr-description').val(),
                is_active: form.find('#qr-active').is(':checked') ? 1 : 0
            };

            $.post(qrAnalytics.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        QRAnalytics.showNotice('success', response.data.message);

                        // If new code, redirect to edit page
                        if (!formData.id && response.data.id) {
                            window.location.href = 'admin.php?page=qr-analytics-new&edit=' + response.data.id;
                        }
                    } else {
                        QRAnalytics.showNotice('error', response.data.message || qrAnalytics.strings.error);
                    }
                })
                .fail(function() {
                    QRAnalytics.showNotice('error', qrAnalytics.strings.error);
                })
                .always(function() {
                    submitBtn.prop('disabled', false).html(originalText);
                });
        },

        handleDelete: function(e) {
            e.preventDefault();

            if (!confirm(qrAnalytics.strings.confirmDelete)) {
                return;
            }

            var btn = $(e.currentTarget);
            var card = btn.closest('.qr-code-card');
            var id = btn.data('id');

            btn.prop('disabled', true);

            $.post(qrAnalytics.ajaxUrl, {
                action: 'qr_delete_code',
                nonce: qrAnalytics.nonce,
                id: id
            })
            .done(function(response) {
                if (response.success) {
                    card.fadeOut(300, function() {
                        $(this).remove();

                        // Check if grid is empty
                        if ($('.qr-codes-grid').children().length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    QRAnalytics.showNotice('error', response.data.message || qrAnalytics.strings.error);
                    btn.prop('disabled', false);
                }
            })
            .fail(function() {
                QRAnalytics.showNotice('error', qrAnalytics.strings.error);
                btn.prop('disabled', false);
            });
        },

        handleCopyUrl: function(e) {
            e.preventDefault();

            var btn = $(e.currentTarget);
            var url = btn.data('url');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    QRAnalytics.showCopyFeedback(btn);
                });
            } else {
                // Fallback for older browsers
                var textarea = document.createElement('textarea');
                textarea.value = url;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                QRAnalytics.showCopyFeedback(btn);
            }
        },

        showCopyFeedback: function(btn) {
            var originalIcon = btn.find('.dashicons');
            originalIcon.removeClass('dashicons-clipboard').addClass('dashicons-yes');

            setTimeout(function() {
                originalIcon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1500);
        },

        handleDownload: function(e) {
            e.preventDefault();
            var slug = $(e.currentTarget).data('slug');
            this.downloadSvg(slug, 1000);
        },

        handleDownloadSize: function(e) {
            e.preventDefault();
            var btn = $(e.currentTarget);
            var slug = btn.data('slug');
            var size = btn.data('size');
            this.downloadSvg(slug, size);
        },

        downloadSvg: function(slug, size) {
            var url = qrAnalytics.ajaxUrl + '?' + $.param({
                action: 'qr_download_svg',
                nonce: qrAnalytics.nonce,
                slug: slug,
                size: size
            });

            window.location.href = url;
        },

        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p></p></div>');
            notice.find('p').text(message);

            $('.qr-analytics-wrap h1').after(notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };

    // Expose to global scope
    window.QRAnalytics = QRAnalytics;

})(jQuery);
