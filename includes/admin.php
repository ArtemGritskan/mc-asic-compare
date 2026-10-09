<?php
defined('ABSPATH') || exit;
add_action('admin_menu', function () {
    add_options_page('Сравнение ASIC', 'Сравнение ASIC', 'manage_options', 'mc-asic-compare', 'mcac_settings_page');
});
add_action('admin_init', function () {
    register_setting('mcac_settings', 'mcac_product_ids', array('type' => 'array', 'sanitize_callback' => function ($ids) {
        $valid = array();
        foreach (array_slice((array) $ids, 0, 5) as $id) {
            $id = absint($id);
            if (!$id) continue;
            if (function_exists('wc_get_product') && mcac_product($id)) $valid[] = $id;
            else add_settings_error('mcac_product_ids', 'invalid-' . $id, 'Товар ' . $id . ': нужен опубликованный товар SHA-256 или конкретная вариация. Проверьте атрибут «Алгоритм».');
        }
        return array_values(array_unique($valid));
    }));
});
function mcac_settings_page() {
    if (!current_user_can('manage_options')) return;
    $ids = mcac_ids();
    ?>
    <div class="wrap"><h1>Сравнение ASIC</h1>
    <p>Шорткод: <code>[mc_asic_compare]</code>. Здесь задаётся приоритетный порядок товаров, первые два выбраны при открытии. Посетитель выбирает устройства сам через поиск на сайте. Остальные опубликованные товары SHA-256 добавляются в список автоматически. Укажите до пяти приоритетных ID товаров или конкретных вариаций.</p>
    <?php settings_errors(); ?>
    <form action="options.php" method="post">
    <?php settings_fields('mcac_settings'); ?>
    <table class="form-table"><tbody>
    <?php for ($i = 0; $i < 5; $i++) : ?>
    <tr><th><label for="mcac-product-<?php echo esc_attr($i); ?>">Модель <?php echo esc_html($i + 1); ?></label></th><td>
    <input type="number" min="1" id="mcac-product-<?php echo esc_attr($i); ?>" name="mcac_product_ids[]" value="<?php echo esc_attr($ids[$i] ?? ''); ?>">
    <?php $p = function_exists('wc_get_product') && isset($ids[$i]) ? wc_get_product($ids[$i]) : false; if ($p) echo esc_html(' — ' . $p->get_name()); ?>
    </td></tr><?php endfor; ?>
    </tbody></table><?php submit_button(); ?></form>
    <p>Характеристики читаются из атрибутов WooCommerce. Сопоставление названий находится в <code>config/catalog.php</code>. Товары без цены или характеристик допускаются; пропуски отображаются явно.</p>
    </div>
    <?php
}
add_action('woocommerce_product_options_inventory_product_data', function () {
    woocommerce_wp_select(array('id' => '_mcac_availability', 'label' => 'Наличие в сравнении', 'options' => array('' => 'Из WooCommerce', 'preorder' => 'Предзаказ', 'unknown' => 'Наличие уточняется')));
    woocommerce_wp_checkbox(array('id' => '_mcac_price_from', 'label' => 'Цена «от» в сравнении'));
});
add_action('woocommerce_admin_process_product_object', function ($product) {
    $status = isset($_POST['_mcac_availability']) ? sanitize_key(wp_unslash($_POST['_mcac_availability'])) : '';
    $product->update_meta_data('_mcac_availability', in_array($status, array('preorder', 'unknown'), true) ? $status : '');
    $product->update_meta_data('_mcac_price_from', isset($_POST['_mcac_price_from']) ? 'yes' : 'no');
});
