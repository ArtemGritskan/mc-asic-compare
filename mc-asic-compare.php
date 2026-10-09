<?php
/**
 * Plugin Name: Mining Cluster — Сравнение ASIC
 * Description:  Шорткод [mc_asic_compare].
 * Version: 1.1.0
 * Author: Артём 
 * Requires Plugins: woocommerce
 * Text Domain: mc-asic-compare
 */
defined('ABSPATH') || exit;
define('MCAC_DIR', plugin_dir_path(__FILE__));
define('MCAC_URL', plugin_dir_url(__FILE__));
require_once MCAC_DIR . 'includes/products.php';
require_once MCAC_DIR . 'includes/admin.php';
require_once MCAC_DIR . 'includes/shortcode.php';
add_filter('wp_robots', function ($robots) {
    if (is_page() && get_post_meta(get_queried_object_id(), '_mcac_test_page', true) === 'yes') {
        $robots['noindex'] = true;
        unset($robots['index']);
    }
    return $robots;
});

add_action('rest_api_init', function () {
    register_rest_route('mc-asic-compare/v1', '/products', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            if (!function_exists('wc_get_product')) {
                return new WP_Error('mcac_unavailable', 'WooCommerce временно недоступен.', array('status' => 503));
            }
            $response = new WP_REST_Response(mcac_catalog());
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            return $response;
        },
    ));
});
