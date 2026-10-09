<?php
defined('WP_UNINSTALL_PLUGIN') || exit;
delete_option('mcac_product_ids');
// Product data and the test page are intentionally preserved.
