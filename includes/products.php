<?php
defined('ABSPATH') || exit;

function mcac_config() {
    return require MCAC_DIR . 'config/catalog.php';
}

function mcac_attribute($product, $field) {
    $config = mcac_config();
    foreach ($config['attributes'][$field] as $key) {
        $value = trim(wp_strip_all_tags($product->get_attribute($key)));
        if ($value !== '') return $value;
    }
    // An exact variation can inherit a single shared characteristic, never a range.
    if ($product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        if ($parent) {
            foreach ($config['attributes'][$field] as $key) {
                foreach ($parent->get_attributes() as $attribute) {
                    if ($attribute->get_name() === $key && !$attribute->get_variation() && count($attribute->get_options()) === 1) {
                        return trim(wp_strip_all_tags($parent->get_attribute($key)));
                    }
                }
            }
        }
    }
    return '';
}

function mcac_ids() {
    $saved = get_option('mcac_product_ids', null);
    if (is_array($saved)) return array_slice(array_values(array_unique(array_filter(array_map('absint', $saved)))), 0, 5);
    $ids = array();
    foreach (mcac_config()['slugs'] as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'product');
        if ($post) $ids[] = $post->ID;
    }
    return $ids;
}

function mcac_plain($html) {
    return html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8');
}

function mcac_product($id) {
    $product = wc_get_product($id);
    if (!$product || $product->get_status() !== 'publish') return null;
    $parent = $product->is_type('variation') ? wc_get_product($product->get_parent_id()) : $product;
    if (!$parent || $parent->get_status() !== 'publish' || get_post_field('post_password', $parent->get_id()) !== '') return null;
    if (!$product->is_type(array('simple', 'variation'))) return null;
    $algorithm = strtoupper(str_replace(array(' ', '-'), '', mcac_attribute($product, 'algorithm')));
    if ($algorithm !== 'SHA256') return null;
    $status = $product->get_meta('_mcac_availability');
    $labels = array('instock' => 'В наличии', 'outofstock' => 'Нет в наличии', 'onbackorder' => 'Под заказ', 'preorder' => 'Предзаказ', 'unknown' => 'Наличие уточняется');
    if (!isset($labels[$status])) {
        $raw_status = get_post_meta($id, '_stock_status', true);
        $status = isset($labels[$raw_status]) ? $product->get_stock_status() : 'unknown';
        if ($product->is_on_backorder(1)) $status = 'onbackorder';
    }
    $price = $product->get_price();
    $price_text = $price === '' ? 'Цена по запросу' : mcac_plain(wc_price(wc_get_price_to_display($product)));
    if ($price !== '' && $product->get_meta('_mcac_price_from') === 'yes') $price_text = 'от ' . $price_text;
    $image_id = $product->get_image_id() ?: $parent->get_image_id();
    return array(
        'id' => $id,
        'name' => mcac_plain($product->get_name()),
        'url' => $product->get_permalink(),
        'image' => $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '',
        'price' => $price_text,
        'hashrate' => mcac_attribute($product, 'hashrate') ?: 'Не указано',
        'power' => mcac_attribute($product, 'power') ?: 'Не указано',
        'availability' => $labels[$status] ?? $labels['unknown'],
        'status' => $status,
    );
}

function mcac_catalog() {
    $products = array();
    $preferred = mcac_ids();
    $published = get_posts(array('post_type' => array('product', 'product_variation'), 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true));
    foreach (array_unique(array_merge($preferred, $published)) as $id) {
        $object = wc_get_product($id);
        if (!$object) continue;
        $parent = $object->is_type('variation') ? wc_get_product($object->get_parent_id()) : $object;
        if (!$parent || ($parent->get_catalog_visibility() === 'hidden' && !in_array($id, $preferred, true))) continue;
        $product = mcac_product($id);
        if ($product) $products[] = $product;
    }
    return array('products' => $products, 'updated' => current_time('c'));
}
