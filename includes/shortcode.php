<?php
defined('ABSPATH') || exit;
add_shortcode('mc_asic_compare', function () {
    if (!function_exists('wc_get_product')) return '<p role="status">Сравнение временно недоступно: требуется WooCommerce.</p>';
    wp_enqueue_style('mc-asic-compare', MCAC_URL . 'assets/compare.css', array(), filemtime(MCAC_DIR . 'assets/compare.css'));
    wp_enqueue_script('mc-asic-compare', MCAC_URL . 'assets/compare.js', array(), filemtime(MCAC_DIR . 'assets/compare.js'), true);
    $title_id = wp_unique_id('mcac-title-');
    ob_start(); ?>
    <section class="mcac" aria-labelledby="<?php echo esc_attr($title_id); ?>" data-endpoint="<?php echo esc_url(rest_url('mc-asic-compare/v1/products')); ?>">
        <div class="mcac-heading"><div><span class="mcac-eyebrow">ОБОРУДОВАНИЕ ДЛЯ BITCOIN</span><h2 id="<?php echo esc_attr($title_id); ?>">Сравнение ASIC</h2><p>Выберите до трёх моделей и сравните основные характеристики.</p></div></div>
        <div class="mcac-toolbar"><span class="mcac-count">Выбрано 0 из 3</span><div class="mcac-actions"><button type="button" class="mcac-open" disabled>+ Добавить устройство</button><button type="button" class="mcac-clear">Очистить выбор</button></div></div>
        <p class="mcac-notice" role="status" aria-live="polite">Загружаем актуальные данные…</p>
        <button class="mcac-retry" type="button" hidden>Повторить загрузку</button>
        <div class="mcac-catalog" aria-label="Модели для сравнения"></div>
        <div class="mcac-results" aria-label="Характеристики выбранных моделей"></div>
        <dialog class="mcac-dialog" aria-labelledby="<?php echo esc_attr($title_id . '-picker'); ?>">
            <div class="mcac-picker-head"><h3 id="<?php echo esc_attr($title_id . '-picker'); ?>">Выберите устройства</h3><button class="mcac-close" type="button" aria-label="Закрыть выбор устройств">✕</button></div>
            <label for="<?php echo esc_attr($title_id . '-search'); ?>">Поиск по названию модели</label>
            <input class="mcac-search" id="<?php echo esc_attr($title_id . '-search'); ?>" type="search" placeholder="Например, S21 или Whatsminer" autocomplete="off">
            <p class="mcac-picker-notice" role="status" aria-live="polite"></p>
            <div class="mcac-picker-list"></div>
            <div class="mcac-picker-footer"><button class="mcac-done" type="button">Показать сравнение</button></div>
        </dialog>
        <p class="mcac-footnote">Потребление — мощность устройства в ваттах. Доходность не рассчитывается.</p>
        <noscript>Для выбора и сравнения моделей включите JavaScript в браузере.</noscript>
    </section>
    <?php return ob_get_clean();
});
