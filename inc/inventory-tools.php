<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If loose inventory ever gets off for whatever reason, run /wp-admin/?fr_run_nonwoo_backfill=1
// after inputing desired date things went screwy to see what they should be if orders were not taking Components
// out of inventory. Then - if that looks correct - run /wp-admin/?fr_run_nonwoo_backfill=1&live=1 to actually
// put those changes into affect. Uncomment the temp runner below though

// ===============================
// 1. HISTORICAL FUNCTION
// ===============================
/*function fr_cbompro_backfill_nonwoo_bom_components_since(
    string $start_date = '2026-03-10 00:00:00',
    array $statuses = ['wc-processing', 'wc-completed'],
    int $limit = 500,
    bool $dry_run = true
): array {

    $results = [
        'start_date'     => $start_date,
        'orders_checked' => 0,
        'orders_updated' => 0,
        'loose_changes'  => [],
    ];

    $orders = wc_get_orders([
        'status'        => $statuses,
        'limit'         => $limit,
        'type'          => 'shop_order',
        'orderby'       => 'date',
        'order'         => 'ASC',
        'return'        => 'objects',
        'date_created'  => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        $results['orders_checked']++;

        if ($order->get_meta(\FR\ComponentsBOMPro\OrderBOMStock::META_REDUCED) !== 'yes') continue;
        if ($order->get_meta('_fr_cbompro_nonwoo_backfill_done') === 'yes') continue;

        $order_loose_changes = [];

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $qty = (int) $item->get_quantity();
            if ($qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;
            $ordered_sku        = strtoupper(trim((string) $product->get_sku()));

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($bom_product_id);
            if (empty($rows)) continue;

            foreach ($rows as $row) {
                $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
                if ($sku === '') continue;

                $row_qty = max(1, (int) ($row['qty'] ?? 1));
                $needed  = $row_qty * $qty;

                if ($needed <= 0) continue;

                $pid = (int) wc_get_product_id_by_sku($sku);

                if ($pid > 0) continue; // skip Woo products
                if ($ordered_sku !== '' && $sku === $ordered_sku) continue;

                if (!isset($order_loose_changes[$sku])) {
                    $order_loose_changes[$sku] = 0;
                }

                $order_loose_changes[$sku] += $needed;
            }
        }

        if (empty($order_loose_changes)) {
            if (!$dry_run) {
                $order->update_meta_data('_fr_cbompro_nonwoo_backfill_done', 'yes');
                $order->save();
            }
            continue;
        }

        foreach ($order_loose_changes as $sku => $qty_to_reduce) {
            if (!isset($results['loose_changes'][$sku])) {
                $results['loose_changes'][$sku] = 0;
            }

            $results['loose_changes'][$sku] += (int) $qty_to_reduce;

            if (!$dry_run) {
                \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                    $sku,
                    -1 * (int) $qty_to_reduce,
                    "Historical backfill for order #" . $order->get_id()
                );
            }
        }

        if (!$dry_run) {
            $order->update_meta_data('_fr_cbompro_nonwoo_backfill_done', 'yes');
            $order->save();
        }

        $results['orders_updated']++;
    }

    return $results;
}*/


// ===============================
// 2. TEMP RUNNER (REMOVE AFTER)
// ===============================
/*add_action('admin_init', function () {

    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_run_nonwoo_backfill'])) return;

    $dry_run = !isset($_GET['live']);

    $result = fr_cbompro_backfill_nonwoo_bom_components_since(
        '2026-03-10 00:00:00',
        ['wc-processing', 'wc-completed'],
        500,
        $dry_run
    );

    echo '<pre>';
    echo $dry_run ? "DRY RUN\n\n" : "LIVE RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});*/

function fr_cbompro_collect_component_usage_recursive(
    int $product_id,
    int $multiplier = 1,
    array &$accum = [],
    array $visited = []
): array {
    if ($product_id <= 0) {
        return $accum;
    }

    if (in_array($product_id, $visited, true)) {
        return $accum;
    }

    $visited[] = $product_id;

    $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
    if (empty($rows)) {
        return $accum;
    }

    foreach ($rows as $row) {
        $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if ($row_sku === '') {
            continue;
        }

        $row_qty = max(1, (int) ($row['qty'] ?? 1));
        $needed  = $row_qty * $multiplier;

        // Recurse through subassemblies only
        if (str_ends_with($row_sku, '-SA')) {
            $sa_product_id = (int) wc_get_product_id_by_sku($row_sku);
            if ($sa_product_id > 0) {
                fr_cbompro_collect_component_usage_recursive($sa_product_id, $needed, $accum, $visited);
                continue;
            }
        }

        if (!isset($accum[$row_sku])) {
            $accum[$row_sku] = 0;
        }

        $accum[$row_sku] += $needed;
    }

    return $accum;
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_component'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $baseline_qty = isset($_GET['baseline'])
        ? (int) $_GET['baseline']
        : null;

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 2000;

    $live = isset($_GET['live']);

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    if ($baseline_qty === null) {
        wp_die('Missing baseline param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $current_loose_qty = $wpdb->get_var(
        $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
    );
    $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

    // ------------------------------------------------------------
    // 1) Pull all logged rows after baseline
    // ------------------------------------------------------------
    $logged_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE sku = %s
               AND created_at >= %s
             ORDER BY created_at ASC, id ASC",
            $sku,
            $start_date
        ),
        ARRAY_A
    );

    // Ignore the exact-stock row that established the chosen baseline
    $filtered_logged_rows = [];
    foreach ($logged_rows as $row) {
        $note    = (string) ($row['note'] ?? '');
        $new_qty = isset($row['new_qty']) && $row['new_qty'] !== '' ? (int) $row['new_qty'] : null;

        if (
            stripos($note, 'Exact stock set') !== false &&
            $new_qty !== null &&
            $new_qty === (int) $baseline_qty
        ) {
            continue;
        }

        $filtered_logged_rows[] = $row;
    }
    $logged_rows = $filtered_logged_rows;

    $logged_total_delta        = 0;
    $logged_already_consumed   = 0;
    $logged_location_only_rows = [];
    $logged_other_rows         = [];

    foreach ($logged_rows as $row) {
        $delta = (int) ($row['delta'] ?? 0);
        $note  = (string) ($row['note'] ?? '');

        $logged_total_delta += $delta;

        if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
            $logged_location_only_rows[] = $row;
            continue;
        }

        if (
            $delta < 0 && (
                stripos($note, 'BOM component auto-reduced for order #') !== false ||
                stripos($note, 'Historical backfill for order #') !== false ||
                stripos($note, 'Subassembly build for ') !== false
            )
        ) {
            $logged_already_consumed += abs($delta);
            continue;
        }

        $logged_other_rows[] = $row;
    }

    // ------------------------------------------------------------
    // 2) Reconstruct "should have consumed" from orders recursively
    // ------------------------------------------------------------
    $projected_consumption = 0;
    $projected_rows = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            fr_cbompro_collect_component_usage_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$sku])) {
                continue;
            }

            $needed = (int) $expanded[$sku];
            $projected_consumption += $needed;

            $projected_rows[] = [
                'order_id'      => $order->get_id(),
                'order_date'    => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'ordered_sku'   => $product->get_sku(),
                'ordered_qty'   => $order_qty,
                'needed'        => $needed,
            ];
        }
    }

    // ------------------------------------------------------------
    // 3) Missing consumption still not reflected in the log
    // ------------------------------------------------------------
    $missing_consumption = max(0, $projected_consumption - $logged_already_consumed);

    // ------------------------------------------------------------
    // 4) Shadow expected qty today
    // ------------------------------------------------------------
    // Start at trusted baseline
    // Add all logged deltas after baseline
    // Then subtract ONLY the missing projected consumption
    // (because already-consumed reductions are already inside logged_total_delta)
    $expected_qty_today = $baseline_qty + $logged_total_delta - $missing_consumption;
    $correction_needed  = $expected_qty_today - $current_loose_qty;

    $result = [
        'sku'                    => $sku,
        'start_date'             => $start_date,
        'baseline_qty'           => $baseline_qty,
        'current_loose_qty'      => $current_loose_qty,

        'logged_total_delta'     => $logged_total_delta,
        'logged_already_consumed'=> $logged_already_consumed,
        'projected_consumption'  => $projected_consumption,
        'missing_consumption'    => $missing_consumption,

        'expected_qty_today'     => $expected_qty_today,
        'correction_needed'      => $correction_needed,

        'location_only_rows'     => $logged_location_only_rows,
        'logged_other_rows'      => $logged_other_rows,
        'projected_rows'         => $projected_rows,
    ];

    // ------------------------------------------------------------
    // 5) Optional live correction
    // ------------------------------------------------------------
    if ($live && $correction_needed !== 0) {
        \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
            $sku,
            $correction_needed,
            sprintf(
                'Shadow replay reconcile from %s. Baseline=%d, expected=%d, current=%d',
                $start_date,
                $baseline_qty,
                $expected_qty_today,
                $current_loose_qty
            )
        );

        $result['live_applied'] = true;
    } else {
        $result['live_applied'] = false;
    }

    echo '<pre>';
    echo $live ? "SHADOW REPLAY LIVE RUN\n\n" : "SHADOW REPLAY DRY RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_debug_sku_snapshot'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 10;

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $loose_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT sku, qty, location
             FROM {$loose_table}
             WHERE sku = %s
             LIMIT 1",
            $sku
        ),
        ARRAY_A
    );

    $recent_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT created_at, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE sku = %s
             ORDER BY created_at DESC, id DESC
             LIMIT %d",
            $sku,
            $limit
        ),
        ARRAY_A
    );

    $result = [
        'sku' => $sku,
        'loose_snapshot' => $loose_row ?: [
            'sku' => $sku,
            'qty' => null,
            'location' => null,
        ],
        'recent_adjustments' => $recent_rows,
    ];

    echo '<pre>';
    echo "SKU SNAPSHOT DEBUG\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

function fr_cbompro_collect_component_usage_recursive_multi(
    int $product_id,
    int $multiplier = 1,
    array &$accum = [],
    array $visited = []
): array {
    if ($product_id <= 0) {
        return $accum;
    }

    if (in_array($product_id, $visited, true)) {
        return $accum;
    }

    $visited[] = $product_id;

    $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
    if (empty($rows)) {
        return $accum;
    }

    foreach ($rows as $row) {
        $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if ($row_sku === '') continue;

        $row_qty = max(1, (int) ($row['qty'] ?? 1));
        $needed  = $row_qty * $multiplier;

        if (str_ends_with($row_sku, '-SA')) {
            $sa_product_id = (int) wc_get_product_id_by_sku($row_sku);
            if ($sa_product_id > 0) {
                fr_cbompro_collect_component_usage_recursive_multi($sa_product_id, $needed, $accum, $visited);
                continue;
            }
        }

        if (!isset($accum[$row_sku])) {
            $accum[$row_sku] = 0;
        }

        $accum[$row_sku] += $needed;
    }

    return $accum;
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_audit'])) return;

    $skus = isset($_GET['sku'])
        ? array_values(array_filter(array_map(
            fn($s) => strtoupper(trim($s)),
            explode(',', sanitize_text_field(wp_unslash($_GET['sku'])))
        )))
        : [];

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 2000;

    $live = isset($_GET['live']);

    if (empty($skus)) {
        wp_die('Missing sku param');
    }

    // Baselines passed like: baseline=HDW-80011:33,HDW-80012:41
    $baseline_map = [];
    $baseline_raw = isset($_GET['baseline'])
        ? sanitize_text_field(wp_unslash($_GET['baseline']))
        : '';

    if ($baseline_raw !== '') {
        foreach (explode(',', $baseline_raw) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || strpos($chunk, ':') === false) continue;
            [$bsku, $bqty] = array_map('trim', explode(':', $chunk, 2));
            $baseline_map[strtoupper($bsku)] = (int) $bqty;
        }
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    // ------------------------------------------------------------
    // 1) Precompute projected consumption from orders for ALL SKUs
    // ------------------------------------------------------------
    $projected_by_sku = [];
    $projected_rows_by_sku = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            fr_cbompro_collect_component_usage_recursive_multi($bom_product_id, $order_qty, $expanded);

            foreach ($expanded as $component_sku => $needed) {
                if (!in_array($component_sku, $skus, true)) continue;

                if (!isset($projected_by_sku[$component_sku])) {
                    $projected_by_sku[$component_sku] = 0;
                }
                $projected_by_sku[$component_sku] += (int) $needed;

                $projected_rows_by_sku[$component_sku][] = [
                    'order_id'    => $order->get_id(),
                    'order_date'  => $order->get_date_created()
                        ? $order->get_date_created()->date('Y-m-d H:i:s')
                        : '',
                    'ordered_sku' => $product->get_sku(),
                    'ordered_qty' => $order_qty,
                    'needed'      => (int) $needed,
                ];
            }
        }
    }

    // ------------------------------------------------------------
    // 2) Audit each SKU
    // ------------------------------------------------------------
    $results = [];

    foreach ($skus as $sku) {
        if (!isset($baseline_map[$sku])) {
            $results[$sku] = ['error' => 'Missing baseline for SKU'];
            continue;
        }

        $baseline_qty = (int) $baseline_map[$sku];

        $current_loose_qty = $wpdb->get_var(
            $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
        );
        $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

        $logged_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
                 FROM {$log_table}
                 WHERE sku = %s
                   AND created_at >= %s
                 ORDER BY created_at ASC, id ASC",
                $sku,
                $start_date
            ),
            ARRAY_A
        );

        $filtered_logged_rows = [];
        foreach ($logged_rows as $row) {
            $note    = (string) ($row['note'] ?? '');
            $new_qty = isset($row['new_qty']) && $row['new_qty'] !== '' ? (int) $row['new_qty'] : null;

            if (
                stripos($note, 'Exact stock set') !== false &&
                $new_qty !== null &&
                $new_qty === $baseline_qty
            ) {
                continue;
            }

            $filtered_logged_rows[] = $row;
        }
        $logged_rows = $filtered_logged_rows;

        $logged_total_delta      = 0;
        $logged_already_consumed = 0;
        $location_only_rows      = [];
        $logged_other_rows       = [];

        foreach ($logged_rows as $row) {
            $delta = (int) ($row['delta'] ?? 0);
            $note  = (string) ($row['note'] ?? '');

            $logged_total_delta += $delta;

            if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
                $location_only_rows[] = $row;
                continue;
            }

            if (
                $delta < 0 && (
                    stripos($note, 'BOM component auto-reduced for order #') !== false ||
                    stripos($note, 'Historical backfill for order #') !== false ||
                    stripos($note, 'Subassembly build for ') !== false
                )
            ) {
                $logged_already_consumed += abs($delta);
                continue;
            }

            $logged_other_rows[] = $row;
        }

        $projected_consumption = (int) ($projected_by_sku[$sku] ?? 0);
        $missing_consumption   = max(0, $projected_consumption - $logged_already_consumed);

        $expected_qty_today = $baseline_qty + $logged_total_delta - $missing_consumption;
        $correction_needed  = $expected_qty_today - $current_loose_qty;

        $results[$sku] = [
            'baseline_qty'            => $baseline_qty,
            'current_loose_qty'       => $current_loose_qty,
            'logged_total_delta'      => $logged_total_delta,
            'logged_already_consumed' => $logged_already_consumed,
            'projected_consumption'   => $projected_consumption,
            'missing_consumption'     => $missing_consumption,
            'expected_qty_today'      => $expected_qty_today,
            'correction_needed'       => $correction_needed,
            'location_only_rows'      => $location_only_rows,
            'logged_other_rows'       => $logged_other_rows,
            'projected_rows'          => $projected_rows_by_sku[$sku] ?? [],
        ];

        if ($live && $correction_needed !== 0) {
            \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                $sku,
                $correction_needed,
                sprintf(
                    'Shadow replay audit reconcile from %s. Baseline=%d, expected=%d, current=%d',
                    $start_date,
                    $baseline_qty,
                    $expected_qty_today,
                    $current_loose_qty
                )
            );
            $results[$sku]['live_applied'] = true;
        } else {
            $results[$sku]['live_applied'] = false;
        }
    }

    echo '<pre>';
    echo $live ? "SHADOW REPLAY AUDIT LIVE RUN\n\n" : "SHADOW REPLAY AUDIT DRY RUN\n\n";
    print_r($results);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_prove_component_orders'])) return;

    $target_sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $statuses = isset($_GET['statuses'])
        ? array_values(array_filter(array_map('trim', explode(',', sanitize_text_field(wp_unslash($_GET['statuses']))))))
        : ['wc-processing', 'wc-completed'];

    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 5000;

    if ($target_sku === '') {
        wp_die('Missing sku param');
    }

    $expand_recursive = function(int $product_id, int $multiplier = 1, array &$accum = [], array $visited = []) use (&$expand_recursive) {
        if ($product_id <= 0) return $accum;
        if (in_array($product_id, $visited, true)) return $accum;

        $visited[] = $product_id;

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (empty($rows)) return $accum;

        foreach ($rows as $row) {
            $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($row_sku === '') continue;

            $row_qty = max(1, (int) ($row['qty'] ?? 1));
            $needed  = $row_qty * $multiplier;

            if (str_ends_with($row_sku, '-SA')) {
                $child_id = (int) wc_get_product_id_by_sku($row_sku);
                if ($child_id > 0) {
                    $expand_recursive($child_id, $needed, $accum, $visited);
                    continue;
                }
            }

            if (!isset($accum[$row_sku])) {
                $accum[$row_sku] = 0;
            }
            $accum[$row_sku] += $needed;
        }

        return $accum;
    };

    $matches = [];
    $orders = wc_get_orders([
        'status'       => $statuses,
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            $expand_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$target_sku])) {
                continue;
            }

            $matches[] = [
                'order_id'      => $order->get_id(),
                'order_date'    => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'status'        => $order->get_status(),
                'ordered_sku'   => $product->get_sku(),
                'ordered_qty'   => $order_qty,
                'component_sku' => $target_sku,
                'needed'        => (int) $expanded[$target_sku],
            ];
        }
    }

    $total = 0;
    foreach ($matches as $m) {
        $total += (int) $m['needed'];
    }

    echo '<pre>';
    echo "PROVE COMPONENT ORDERS\n\n";
    echo "Target SKU: {$target_sku}\n";
    echo "Start date: {$start_date}\n";
    echo "Statuses: " . implode(', ', $statuses) . "\n";
    echo "Total projected usage: {$total}\n\n";
    print_r($matches);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_absolute'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-01-01 00:00:00';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 5000;

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $current_loose_qty = $wpdb->get_var(
        $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
    );
    $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

    // ------------------------------------------------------------
    // 1) Pull all log rows in date range
    // ------------------------------------------------------------
    $logged_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, delta, prev_qty, new_qty, note, source, user_id
             FROM {$log_table}
             WHERE sku = %s
               AND created_at >= %s
             ORDER BY created_at ASC, id ASC",
            $sku,
            $start_date
        ),
        ARRAY_A
    );

    // ------------------------------------------------------------
    // 2) Determine starting qty from first row that has prev_qty
    // ------------------------------------------------------------
    $starting_qty = null;
    foreach ($logged_rows as $row) {
        if (isset($row['prev_qty']) && $row['prev_qty'] !== '' && $row['prev_qty'] !== null) {
            $starting_qty = (int) $row['prev_qty'];
            break;
        }
    }

    if ($starting_qty === null) {
        $starting_qty = $current_loose_qty;
    }

    // ------------------------------------------------------------
    // 3) Sum NON-reset logged deltas
    // ------------------------------------------------------------
    $logged_non_reset_delta = 0;
    $location_rows = [];
    $reset_rows = [];
    $other_logged_rows = [];

    foreach ($logged_rows as $row) {
        $delta = (int) ($row['delta'] ?? 0);
        $note  = (string) ($row['note'] ?? '');

        if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
            $location_rows[] = $row;
            continue;
        }

        if (stripos($note, 'Exact stock set') !== false) {
            $reset_rows[] = $row;
            continue; // skip resets entirely
        }

        $logged_non_reset_delta += $delta;
        $other_logged_rows[] = $row;
    }

    // ------------------------------------------------------------
    // 4) Recursively project order consumption
    // ------------------------------------------------------------
    $expand_recursive = function(int $product_id, int $multiplier = 1, array &$accum = [], array $visited = []) use (&$expand_recursive) {
        if ($product_id <= 0) return $accum;
        if (in_array($product_id, $visited, true)) return $accum;

        $visited[] = $product_id;

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (empty($rows)) return $accum;

        foreach ($rows as $row) {
            $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($row_sku === '') continue;

            $row_qty = max(1, (int) ($row['qty'] ?? 1));
            $needed  = $row_qty * $multiplier;

            if (str_ends_with($row_sku, '-SA')) {
                $sa_id = (int) wc_get_product_id_by_sku($row_sku);
                if ($sa_id > 0) {
                    $expand_recursive($sa_id, $needed, $accum, $visited);
                    continue;
                }
            }

            if (!isset($accum[$row_sku])) {
                $accum[$row_sku] = 0;
            }
            $accum[$row_sku] += $needed;
        }

        return $accum;
    };

    $projected_consumption = 0;
    $projected_rows = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed', 'wc-on-hold'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $bom_product_id = (int) $product->get_id();
            $parent_id      = (int) $product->get_parent_id();

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            $expand_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$sku])) {
                continue;
            }

            $needed = (int) $expanded[$sku];
            $projected_consumption += $needed;

            $projected_rows[] = [
                'order_id'    => $order->get_id(),
                'order_date'  => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'status'      => $order->get_status(),
                'ordered_sku' => $product->get_sku(),
                'ordered_qty' => $order_qty,
                'needed'      => $needed,
            ];
        }
    }

    // ------------------------------------------------------------
    // 5) Compute reset-blind shadow qty
    // starting qty
    // + non-reset logged deltas
    // - projected order consumption
    // ------------------------------------------------------------
    $shadow_qty = $starting_qty + $logged_non_reset_delta - $projected_consumption;
    $difference_vs_current = $shadow_qty - $current_loose_qty;

    $result = [
        'sku'                    => $sku,
        'start_date'             => $start_date,
        'starting_qty'           => $starting_qty,
        'current_loose_qty'      => $current_loose_qty,
        'logged_non_reset_delta' => $logged_non_reset_delta,
        'projected_consumption'  => $projected_consumption,
        'shadow_qty'             => $shadow_qty,
        'difference_vs_current'  => $difference_vs_current,
        'location_rows'          => $location_rows,
        'reset_rows'             => $reset_rows,
        'other_logged_rows'      => $other_logged_rows,
        'projected_rows'         => $projected_rows,
    ];

    echo '<pre>';
    echo "SHADOW REPLAY ABSOLUTE DRY RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

/**
 * Replay missed BOM consumption from MANUAL parent stock increases
 * logged through the inventory shortcode page.
 *
 * Safe by design:
 * - uses a unique GET key
 * - uses a unique option key
 * - skips audit/reset rows containing "Exact stock set"
 * - skips components you already corrected manually
 * - dry run first, live run second
 *
 * DRY RUN:
 * /wp-admin/?fr_bom_manual_build_replay=1&start=2026-03-10%2000:00:00
 *
 * LIVE RUN:
 * /wp-admin/?fr_bom_manual_build_replay=1&live=1&start=2026-03-10%2000:00:00
 *
 * RESET processed IDs:
 * /wp-admin/?fr_bom_manual_build_replay=1&reset_processed=1
 */

if (!function_exists('fr_bom_manual_build_replay_collect_flat_bom')) {
    function fr_bom_manual_build_replay_collect_flat_bom(int $product_id): array {
        if ($product_id <= 0) {
            return [];
        }

        if (!class_exists('\FR\ComponentsBOMPro\BOMManager')) {
            return [];
        }

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            $qty = (int) ($row['qty'] ?? 0);

            if ($sku === '' || $qty <= 0) {
                continue;
            }

            if (!isset($normalized[$sku])) {
                $normalized[$sku] = 0;
            }

            $normalized[$sku] += $qty;
        }

        return $normalized;
    }
}

add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['fr_bom_manual_build_replay'])) {
        return;
    }

    if (
        !function_exists('wc_get_product_id_by_sku') ||
        !function_exists('wc_get_product') ||
        !class_exists('\FR\ComponentsBOMPro\BOMManager') ||
        !class_exists('\FR\ComponentsBOMPro\FR_BOM_Inventory_API')
    ) {
        wp_die('Required WooCommerce / FR BOM classes are not available.');
    }

    global $wpdb;

    $start = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $is_live = isset($_GET['live']) && $_GET['live'] === '1';
    $reset_processed = isset($_GET['reset_processed']) && $_GET['reset_processed'] === '1';

    $log_table = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    // Components you already fixed manually and do NOT want touched here.
    $skip_components = [
        'HDW-80011',
        'HDW-80012',
    ];

    // Optional parent SKUs to skip completely, if needed later.
    $skip_parents = [
        // 'CP-40001-SA',
    ];

    $processed_option_key = 'fr_bom_manual_build_replay_processed_ids';

    if ($reset_processed) {
        delete_option($processed_option_key);
    }

    $processed_ids = get_option($processed_option_key, []);
    if (!is_array($processed_ids)) {
        $processed_ids = [];
    }

    /**
     * Pull positive stock increases from the adjustment log.
     *
     * We intentionally exclude audit/reset rows here:
     * note LIKE '%Exact stock set%'
     */
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE created_at >= %s
               AND delta > 0
               AND (note IS NULL OR note NOT LIKE %s)
             ORDER BY id ASC",
            $start,
            '%Exact stock set%'
        ),
        ARRAY_A
    );

    $report_lines = [];
    $parent_totals = [];
    $component_totals = [];

    $applied_rows = 0;
    $skipped_rows = 0;
    $error_count = 0;

    foreach ((array) $rows as $row) {
        $log_id = (int) ($row['id'] ?? 0);
        $parent_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        $delta = (int) ($row['delta'] ?? 0);
        $note = (string) ($row['note'] ?? '');

        if ($log_id <= 0 || $parent_sku === '' || $delta <= 0) {
            $skipped_rows++;
            continue;
        }

        // Double safety: also skip in PHP if note contains Exact stock set.
        if (stripos($note, 'Exact stock set') !== false) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: audit/reset row detected by note";
            $skipped_rows++;
            continue;
        }

        if (in_array($parent_sku, $skip_parents, true)) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: parent SKU in skip list";
            $skipped_rows++;
            continue;
        }

        if ($is_live && isset($processed_ids[$log_id])) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: already processed";
            $skipped_rows++;
            continue;
        }

        $parent_product_id = (int) wc_get_product_id_by_sku($parent_sku);
        if ($parent_product_id <= 0) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: not found as Woo product";
            $skipped_rows++;
            continue;
        }

        $build_flag = (string) get_post_meta($parent_product_id, '_fr_build_from_components', true);
        if ($build_flag !== 'yes') {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: _fr_build_from_components is not yes";
            $skipped_rows++;
            continue;
        }

        $flat_bom = fr_bom_manual_build_replay_collect_flat_bom($parent_product_id);
        if (empty($flat_bom)) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: no flattened BOM found";
            $skipped_rows++;
            continue;
        }

        if (!isset($parent_totals[$parent_sku])) {
            $parent_totals[$parent_sku] = 0;
        }
        $parent_totals[$parent_sku] += $delta;

        $report_lines[] = "";
        $report_lines[] = "PARENT {$parent_sku} | +{$delta} | log #{$log_id} | {$row['created_at']}";

        $row_ok = true;

        foreach ($flat_bom as $component_sku => $qty_per_unit) {
            $qty_per_unit = (int) $qty_per_unit;
            if ($qty_per_unit <= 0) {
                continue;
            }

            if ($component_sku === $parent_sku) {
                continue;
            }

            $needed = $qty_per_unit * $delta;
            if ($needed <= 0) {
                continue;
            }

            if (in_array($component_sku, $skip_components, true)) {
                $report_lines[] = "  SKIP COMPONENT {$component_sku} | would consume {$needed} | skipped by config";
                continue;
            }

            if (!isset($component_totals[$component_sku])) {
                $component_totals[$component_sku] = 0;
            }
            $component_totals[$component_sku] += $needed;

            if (!$is_live) {
                $report_lines[] = "  WOULD CONSUME {$component_sku} x {$needed}";
                continue;
            }

            $component_pid = (int) wc_get_product_id_by_sku($component_sku);
            $apply_note = sprintf(
                'Manual build replay from parent %s x %d (adjust log #%d, %s)',
                $parent_sku,
                $delta,
                $log_id,
                $row['created_at']
            );

            if ($component_pid > 0) {
                $component_product = wc_get_product($component_pid);

                if ($component_product && $component_product->managing_stock()) {
                    $current_qty = $component_product->get_stock_quantity();
                    $current_qty = ($current_qty === null) ? 0 : (int) $current_qty;

                    wc_update_product_stock($component_pid, $needed, 'decrease');

                    $report_lines[] = "  APPLIED WOO {$component_sku} | -{$needed} | {$current_qty} -> " . ($current_qty - $needed);
                    continue;
                }
            }

            $ok = \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                $component_sku,
                -1 * $needed,
                $apply_note
            );

            if ($ok) {
                $report_lines[] = "  APPLIED LOOSE {$component_sku} | -{$needed}";
            } else {
                $report_lines[] = "  ERROR {$component_sku} | failed applying -{$needed}";
                $row_ok = false;
                $error_count++;
            }
        }

        if ($is_live && $row_ok) {
            $processed_ids[$log_id] = current_time('mysql');
            $applied_rows++;
        }
    }

    if ($is_live) {
        update_option($processed_option_key, $processed_ids, false);
    }

    ksort($parent_totals);
    ksort($component_totals);

    echo '<div class="wrap">';
    echo '<h1>Manual Build Replay</h1>';
    echo '<p><strong>Mode:</strong> ' . ($is_live ? 'LIVE APPLY' : 'DRY RUN') . '</p>';
    echo '<p><strong>Start date:</strong> ' . esc_html($start) . '</p>';
    echo '<p><strong>Processed option key:</strong> ' . esc_html($processed_option_key) . '</p>';
    echo '<p><strong>Applied rows:</strong> ' . (int) $applied_rows . ' | <strong>Skipped rows:</strong> ' . (int) $skipped_rows . ' | <strong>Errors:</strong> ' . (int) $error_count . '</p>';

    echo '<hr>';
    echo '<h2>Parent additions found</h2>';

    if (empty($parent_totals)) {
        echo '<p>No qualifying parent additions found.</p>';
    } else {
        echo '<table class="widefat striped" style="max-width:760px;">';
        echo '<thead><tr><th>Parent SKU</th><th>Total Added</th></tr></thead><tbody>';

        foreach ($parent_totals as $sku => $qty) {
            echo '<tr><td>' . esc_html($sku) . '</td><td>' . (int) $qty . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    echo '<hr>';
    echo '<h2>Component totals ' . ($is_live ? 'applied' : 'that would be applied') . '</h2>';

    if (empty($component_totals)) {
        echo '<p>No component consumption calculated.</p>';
    } else {
        echo '<table class="widefat striped" style="max-width:760px;">';
        echo '<thead><tr><th>Component SKU</th><th>Total Consumption</th></tr></thead><tbody>';

        foreach ($component_totals as $sku => $qty) {
            echo '<tr><td>' . esc_html($sku) . '</td><td>' . (int) $qty . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    echo '<hr>';
    echo '<h2>Detailed log</h2>';
    echo '<div style="font-family:monospace; white-space:pre-wrap; background:#fff; border:1px solid #ccd0d4; padding:12px;">';
    echo esc_html(implode("\n", $report_lines));
    echo '</div>';

    echo '</div>';
    exit;
});