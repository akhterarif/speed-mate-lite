<?php
namespace Speed_Mate\Includes;

class Speed_Mate_Page_Speed {
    public function __construct() {
        add_action('wp_footer', [$this, 'calculate_metrics']);
    }

    /**
     * Collect and display page speed metrics.
     */
    public function calculate_metrics() {
        if (!is_admin()) {
            return;
        }

        global $wp_scripts, $wp_styles;

        $metrics = [
            'total_scripts' => count($wp_scripts->queue),
            'total_styles'  => count($wp_styles->queue),
            'total_requests' => count($wp_scripts->queue) + count($wp_styles->queue), // Simplified
            'page_load_time' => $this->get_load_time(),
            'page_size'      => $this->get_page_size(),
        ];
    }

    /**
     * Calculate the page load time.
     *
     * @return float
     */
    private function get_load_time() {
        return timer_stop(); // WordPress timer for page generation
    }

    /**
     * Estimate total page size by analyzing enqueued assets.
     *
     * @return float
     */
    private function get_page_size() {
        global $wp_scripts, $wp_styles;

        $total_size = 0;

        // Include sizes for JS files
        foreach ($wp_scripts->queue as $handle) {
            $src = $wp_scripts->registered[$handle]->src ?? '';
            $total_size += $this->get_file_size($src);
        }

        // Include sizes for CSS files
        foreach ($wp_styles->queue as $handle) {
            $src = $wp_styles->registered[$handle]->src ?? '';
            $total_size += $this->get_file_size($src);
        }

        return round($total_size / 1024, 2); // Convert to KB
    }

    /**
     * Get the size of a file from a URL.
     *
     * @param string $url
     * @return int
     */
    private function get_file_size($url) {
        $file_path = ABSPATH . ltrim(wp_parse_url($url, PHP_URL_PATH), '/'); // Use wp_parse_url instead of parse_url
        if (file_exists($file_path)) {
            return filesize($file_path);
        }

        return 0;
    }

    public function get_speed_metrics() {
        $metrics = [
            'Total Scripts' => count(wp_scripts()->queue),
            'Total Styles' => count(wp_styles()->queue),
            'Total Requests' => count(wp_scripts()->queue) + count(wp_styles()->queue),
            'Page Load Time (seconds)' => timer_stop(),
            'Estimated Page Size' => round(ob_get_length() / 1024, 2) . ' KB',
        ];

        wp_send_json($metrics);
    }
}
