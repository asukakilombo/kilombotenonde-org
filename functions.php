<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

/**
 * 1. TRACKING LOGIC
 * Runs on every single post view to update statistics.
 */
function custom_track_post_views() {
    // Only run on single posts
    if ( ! is_single() ) return;

    global $post;
    $post_id = $post->ID;

    // Get current stats from meta
    $stats = get_post_meta($post_id, '_custom_pv_stats', true);
    
    // Initialize if empty
    if ( ! is_array($stats) ) {
        $stats = array(
            'daily'   => array('period' => '', 'count' => 0),
            'weekly'  => array('period' => '', 'count' => 0),
            'monthly' => array('period' => '', 'count' => 0),
            'yearly'  => array('period' => '', 'count' => 0),
            'total'   => 0
        );
    }

    // Get current time periods
    // We use 'current_time' to respect WordPress timezone settings
    $today       = current_time('Y-m-d');
    $this_week   = current_time('oW'); // ISO Year + Week Number
    $this_month  = current_time('Y-m');
    $this_year   = current_time('Y');

    // Update Daily
    if ( isset($stats['daily']['period']) && $stats['daily']['period'] === $today ) {
        $stats['daily']['count']++;
    } else {
        $stats['daily'] = array('period' => $today, 'count' => 1);
    }

    // Update Weekly
    if ( isset($stats['weekly']['period']) && $stats['weekly']['period'] === $this_week ) {
        $stats['weekly']['count']++;
    } else {
        $stats['weekly'] = array('period' => $this_week, 'count' => 1);
    }

    // Update Monthly
    if ( isset($stats['monthly']['period']) && $stats['monthly']['period'] === $this_month ) {
        $stats['monthly']['count']++;
    } else {
        $stats['monthly'] = array('period' => $this_month, 'count' => 1);
    }

    // Update Yearly
    if ( isset($stats['yearly']['period']) && $stats['yearly']['period'] === $this_year ) {
        $stats['yearly']['count']++;
    } else {
        $stats['yearly'] = array('period' => $this_year, 'count' => 1);
    }

    // Update Total
    if ( isset($stats['total']) ) {
        $stats['total']++;
    } else {
        $stats['total'] = 1;
    }

    // Save back to database
    update_post_meta($post_id, '_custom_pv_stats', $stats);
}
add_action('wp_head', 'custom_track_post_views');

/**
 * 2. ADMIN COLUMN REGISTRATION
 * Adds the "Page Views" column to the All Posts screen.
 */
function custom_add_pv_column($columns) {
    // Add the column at the end
    $columns['pv_stats'] = 'Page Views';
    return $columns;
}
add_filter('manage_posts_columns', 'custom_add_pv_column');

/**
 * 3. ADMIN COLUMN CONTENT
 * Displays the stats inside the column for each post.
 */
function custom_display_pv_column($column, $post_id) {
    if ( $column === 'pv_stats' ) {
        $stats = get_post_meta($post_id, '_custom_pv_stats', true);

        // Current periods to validate data (don't show last week's data as "current")
        $today       = current_time('Y-m-d');
        $this_week   = current_time('oW');
        $this_month  = current_time('Y-m');
        $this_year   = current_time('Y');

        // Extract counts, defaulting to 0 if the period stored doesn't match today/now
        $d_count = (is_array($stats) && isset($stats['daily']) && $stats['daily']['period'] === $today) ? $stats['daily']['count'] : 0;
        $w_count = (is_array($stats) && isset($stats['weekly']) && $stats['weekly']['period'] === $this_week) ? $stats['weekly']['count'] : 0;
        $m_count = (is_array($stats) && isset($stats['monthly']) && $stats['monthly']['period'] === $this_month) ? $stats['monthly']['count'] : 0;
        $y_count = (is_array($stats) && isset($stats['yearly']) && $stats['yearly']['period'] === $this_year) ? $stats['yearly']['count'] : 0;
        $t_count = (is_array($stats) && isset($stats['total'])) ? $stats['total'] : 0;

        // HTML Output
        echo '<div class="pv-stats-grid">';
        echo '<span><strong>D:</strong> ' . number_format($d_count) . '</span>';
        echo '<span><strong>W:</strong> ' . number_format($w_count) . '</span>';
        echo '<span><strong>M:</strong> ' . number_format($m_count) . '</span>';
        echo '<span><strong>Y:</strong> ' . number_format($y_count) . '</span>';
        echo '<span class="pv-total"><strong>All:</strong> ' . number_format($t_count) . '</span>';
        echo '</div>';
    }
}
add_action('manage_posts_custom_column', 'custom_display_pv_column', 10, 2);

/**
 * 4. ADMIN STYLING
 * Makes the column look neat and compact.
 */
function custom_pv_column_css() {
    echo '<style>
        .column-pv_stats { 
            width: 140px; 
        }
        .pv-stats-grid {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 11px;
            line-height: 1.4;
        }
        .pv-stats-grid span {
            background: #f0f0f1;
            padding: 2px 5px;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
        }
        .pv-stats-grid .pv-total {
            background: #dcdcde;
            font-weight: bold;
            border-top: 1px solid #ccc;
        }
    </style>';
}
add_action('admin_head', 'custom_pv_column_css');

/**
 * 1. 【完成版】STICKY HEADER CSS
 * 全体の枠を固定し、スクロールで背景を80%から100%不透明にします。
 */
function astra_child_sticky_header_css() {
    ?>
    <style>
        /* --- 初期状態：全体の枠（ラッパー）を固定し、80%透過にする --- */
        .ast-main-header-wrap {
            position: fixed !important; 
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            
            /* 初期状態の背景色：#0F2540 の 80% 透過 */
            background-color: rgba(15, 37, 64, 0.8) !important;
            box-shadow: none !important;
            
            /* 色の変化を滑らかにするアニメーション */
            transition: background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out; 
        }

        /* 内部のヘッダー要素の背景を透明にして、外側の色を通す */
        .site-header {
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
        }

        /* ヘッダーの後ろにコンテンツを潜り込ませる設定 */
        body {
            padding-top: 0 !important;
        }

        /* --- スクロールダウン時：100%不透明にする --- */
        .ast-main-header-wrap.ast-sticky-active {
            /* スクロール後の背景色：#0F2540 の 100% 不透明 */
            background-color: rgba(15, 37, 64, 1.0) !important;
            
            /* スクロール時に軽い影をつけてコンテンツと分離 */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important; 
        }
        
        /* 管理画面ログイン時のズレ防止 */
        body.admin-bar .ast-main-header-wrap {
            top: 32px;
        }
        @media screen and (max-width: 782px) {
            body.admin-bar .ast-main-header-wrap {
                top: 46px;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'astra_child_sticky_header_css');

/**
 * 2. 【完成版】SCROLL DETECTION JS
 * クラスを付与する対象を「.ast-main-header-wrap」に変更します。
 */
function astra_child_sticky_header_js() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // クラスをつける対象を外側のラッパーに変更
        var header = document.querySelector('.ast-main-header-wrap');
        
        if (header) {
            window.addEventListener('scroll', function() {
                // 50px以上スクロールしたらアクティブクラスを付与
                if (window.scrollY > 50) { 
                    header.classList.add('ast-sticky-active');
                } else {
                    header.classList.remove('ast-sticky-active');
                }
            });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'astra_child_sticky_header_js');

/**
 * PMPro: ログイン後に各言語のDashboardへリダイレクト
 */
function my_custom_login_redirect( $redirect_to, $request, $user ) {
    // ログインに失敗した場合などはそのまま
    if ( ! isset( $user->ID ) ) {
        return $redirect_to;
    }

    // Polylangの現在の言語を取得
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'pt';

    // 言語ごとに飛ばす先のURL（固定ページのパーマリンクに合わせて変更してください）
    switch ( $lang ) {
        case 'en':
            return home_url( '/dashboard-en/' ); // 英語
        case 'ja':
            return home_url( '/dashboard-jp/' ); // 日本語
        case 'pt':
        default:
            return home_url( '/pt-dashboard/' ); // ポルトガル語（メイン）
    }
}
add_filter( 'login_redirect', 'my_custom_login_redirect', 10, 3 );