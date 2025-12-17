<?php
/**
 * QR Code Generator using phpqrcode library
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include phpqrcode library (all-in-one file)
require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/phpqrcode/phpqrcode.php';

class QR_Generator {

    private $data;
    private $size;
    private $margin;
    private $error_correction;

    public function __construct($data, $options = array()) {
        $this->data = $data;
        $this->size = isset($options['size']) ? (int) $options['size'] : 400;
        $this->margin = isset($options['margin']) ? (int) $options['margin'] : 2;
        $this->error_correction = isset($options['error_correction']) ? $options['error_correction'] : QR_ECLEVEL_M;
    }

    public function generate_svg() {
        // Generate QR matrix using phpqrcode
        $qr = QRcode::text($this->data, false, $this->error_correction, 1, $this->margin);

        if (!$qr || !is_array($qr)) {
            return $this->generate_error_svg();
        }

        return $this->render_svg($qr);
    }

    private function render_svg($matrix) {
        $matrixSize = count($matrix);
        $totalSize = $this->size;

        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ';
        $svg .= 'width="' . $totalSize . '" height="' . $totalSize . '" ';
        $svg .= 'viewBox="0 0 ' . $matrixSize . ' ' . $matrixSize . '" ';
        $svg .= 'shape-rendering="crispEdges">' . "\n";

        // White background
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>' . "\n";

        // Build path for all black modules (optimized single path)
        $path = '';
        for ($row = 0; $row < $matrixSize; $row++) {
            $rowData = $matrix[$row];
            $rowLen = strlen($rowData);
            for ($col = 0; $col < $rowLen; $col++) {
                // phpqrcode returns string with '1' for black modules
                if ($rowData[$col] === '1') {
                    $path .= 'M' . $col . ',' . $row . 'h1v1h-1z';
                }
            }
        }

        if ($path) {
            $svg .= '<path d="' . $path . '" fill="#000000"/>' . "\n";
        }

        $svg .= '</svg>';

        return $svg;
    }

    private function generate_error_svg() {
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->size . '" height="' . $this->size . '">' . "\n";
        $svg .= '<rect width="100%" height="100%" fill="#f0f0f0"/>' . "\n";
        $svg .= '<text x="50%" y="50%" text-anchor="middle" fill="#666" font-size="14">QR Generation Error</text>' . "\n";
        $svg .= '</svg>';
        return $svg;
    }

    public static function generate($url, $options = array()) {
        $generator = new self($url, $options);
        return $generator->generate_svg();
    }
}
