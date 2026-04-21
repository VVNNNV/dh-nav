<?php
/**
 * 大海导航 Theme Functions
 *
 * @package AI_Navigator_Hub
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 引入自定义文章类型和分类法
require get_template_directory() . '/includes/class-ai-tool-post-type.php';

// 引入后台管理页面
require get_template_directory() . '/includes/class-admin-page.php';

/**
 * 设置主题支持的的功能
 */
function ai_navigator_hub_setup() {
    // 添加默认标题支持
    add_theme_support( 'title-tag' );

    // 添加 HTML5 支持
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ) );

    // 添加自定义 logo 支持
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // 注册导航菜单
    register_nav_menus( array(
        'primary'   => __( 'Primary Menu', 'dh-nav' ),
        'footer'    => __( 'Footer Menu', 'dh-nav' ),
    ) );

    // 添加 REST API 支持
    add_theme_support( 'rest-api' );

    // 添加站点图标支持
    add_theme_support( 'site-icon' );
}
add_action( 'after_setup_theme', 'ai_navigator_hub_setup' );

/**
 * 注册并加载前端资源
 */
function ai_navigator_hub_scripts() {
    // 获取主题目录 URI (使用 get_stylesheet_directory_uri 以支持子主题)
    $theme_uri = get_stylesheet_directory_uri();
    $version   = wp_get_theme( 'dh-nav' )->get( 'Version' );

    // 加载 React 应用的主样式
    wp_enqueue_style(
        'dh-nav-styles',
        $theme_uri . '/assets/index-Dxk-UkNV.css',
        array(),
        $version
    );

    // 加载 React 应用的主脚本
    wp_enqueue_script(
        'dh-nav-scripts',
        $theme_uri . '/assets/index-t75xRreW.js',
        array(),
        $version,
        true // 加载到 footer
    );

    // 加载标签链接增强脚本
    wp_enqueue_script(
        'ai-navigator-tag-links',
        $theme_uri . '/assets/tag-links.js',
        array(),
        $version,
        true
    );

    // 添加全局配置到脚本
    wp_localize_script( 'dh-nav-scripts', 'wpConfig', array(
        'apiUrl'      => rest_url(),
        'nonce'       => wp_create_nonce( 'wp_rest' ),
        'homeUrl'     => home_url(),
        'themeUri'    => $theme_uri,
        'nonceHeader' => 'X-WP-Nonce',
    ) );

    // 移除 Gutenberg 相关样式（如果有）
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' );
}
add_action( 'wp_enqueue_scripts', 'ai_navigator_hub_scripts' );

/**
 * 登录页面自定义
 */
add_filter( 'login_headerurl', function() {
    return home_url();
});

add_filter( 'login_headertext', function() {
    return get_bloginfo( 'name' );
});

/**
 * 移除 WordPress 默认块样式
 */
add_action( 'wp_enqueue_scripts', 'ai_navigator_hub_remove_block_library_css', 100 );
function ai_navigator_hub_remove_block_library_css() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
}

/**
 * 添加前台页面 body 类
 */
function ai_navigator_hub_body_classes( $classes ) {
    if ( is_front_page() ) {
        $classes[] = 'front-page';
    }
    return $classes;
}
add_filter( 'body_class', 'ai_navigator_hub_body_classes' );

/**
 * 注册网站设置选项
 */
function ai_navigator_register_settings() {
    // 网站基本信息
    register_setting('ai_navigator_site_settings', 'ai_navigator_site_name', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_icp_number', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_icp_url', array('type' => 'string', 'sanitize_callback' => 'esc_url_raw'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_copyright', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_contact_info', array('type' => 'string', 'sanitize_callback' => 'wp_kses_post'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_friend_links', array('type' => 'string', 'sanitize_callback' => 'wp_kses_post'));

    // 自定义代码
    register_setting('ai_navigator_site_settings', 'ai_navigator_head_js', array('type' => 'string', 'sanitize_callback' => 'wp_kses_post'));
    register_setting('ai_navigator_site_settings', 'ai_navigator_footer_js', array('type' => 'string', 'sanitize_callback' => 'wp_kses_post'));
}
add_action('admin_init', 'ai_navigator_register_settings');

/**
 * 获取网站名称（带默认值）
 */
function ai_navigator_get_site_name() {
    return get_option('ai_navigator_site_name', '大海导航');
}

/**
 * 在 wp_head 输出自定义 head 代码
 */
function ai_navigator_output_head_js() {
    $head_js = get_option('ai_navigator_head_js', '');
    if (!empty($head_js)) {
        echo "\n<!-- 大海导航 - 自定义 Head 代码 -->\n";
        echo $head_js;
        echo "\n<!-- / 大海导航 - 自定义 Head 代码 -->\n";
    }
}
add_action('wp_head', 'ai_navigator_output_head_js');

/**
 * 在 wp_footer 输出自定义 footer 代码
 */
function ai_navigator_output_footer_js() {
    $footer_js = get_option('ai_navigator_footer_js', '');
    if (!empty($footer_js)) {
        echo "\n<!-- 大海导航 - 自定义 Footer 代码 -->\n";
        echo $footer_js;
        echo "\n<!-- / 大海导航 - 自定义 Footer 代码 -->\n";
    }
}
add_action('wp_footer', 'ai_navigator_output_footer_js');

/**
 * 获取常用图标列表（100个分类整理）
 */
function ai_navigator_get_icons() {
    return array(
        // AI 与科技
        '🤖', '🧠', '🤓', '🧪', '🔬', '🧬', '💡', '⚡', '🖥️', '💻',
        // 工具与办公
        '🔧', '🔨', '⚙️', '🛠️', '📋', '📝', '📊', '📈', '📉', '🗂️',
        // 创意与设计
        '🎨', '🖌️', '🖼️', '🎭', '🎪', '💎', '🔮', '✨', '🌈', '🎯',
        // 通讯与社交
        '💬', '📱', '📞', '📧', '💌', '📡', '📣', '🔔', '🤝', '👥',
        // 媒体与娱乐
        '🎬', '🎵', '🎶', '🎤', '🎧', '📷', '📹', '🎮', '🕹️', '🎲',
        // 搜索与导航
        '🔍', '🔎', '🧭', '🗺️', '📍', '🚀', '✈️', '🚁', '🛸', '🌍',
        // 安全与隐私
        '🔒', '🔐', '🛡️', '🔑', '🗝️', '👁️', '🕵️', '⛔', '🚫', '✅',
        // 文件与数据
        '📁', '📂', '📄', '📑', '📜', '📰', '📚', '📖', '🔖', '🏷️',
        // 电子商务
        '🛒', '💰', '💳', '💸', '🪙', '📦', '🎁', '🏪', '🏷️', '💎',
        // 其他常用
        '⭐', '🌟', '❤️', '🔥', '💪', '🎉', '🎊', '🏆', '🥇', '🏅',
    );
}

/**
 * 添加 REST API CORS 支持
 */
function ai_navigator_add_cors_headers() {
    // 仅对 REST API 请求添加 CORS 头部
    if ( preg_match( '/^\/wp-json\//', $_SERVER['REQUEST_URI'] ?? '' ) ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
        header( 'Access-Control-Allow-Credentials: true' );
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
        header( 'Access-Control-Max-Age: 3600' );
    }
    
    // 处理预检请求
    if ( isset($_SERVER['REQUEST_METHOD']) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
        status_header( 204 );
        exit;
    }
}
add_action( 'init', 'ai_navigator_add_cors_headers', 1 );

// 禁用 Gutenberg 编辑器（使用经典编辑器）
add_filter('use_block_editor_for_post_type', '__return_false');
remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');

/**
 * 添加搜索页面重写规则
 */
function ai_navigator_add_rewrite_rules() {
    add_rewrite_rule('^search/?$', 'index.php?ai_search=1', 'top');
    add_rewrite_rule('^search/page/([0-9]+)/?$', 'index.php?ai_search=1&paged=$matches[1]', 'top');
    // 申请收录页面
    add_rewrite_rule('^submit/?$', 'index.php?submit_page=1', 'top');
}
add_action('init', 'ai_navigator_add_rewrite_rules');

function ai_navigator_register_query_vars($vars) {
    $vars[] = 'ai_search';
    $vars[] = 'submit_page';
    return $vars;
}
add_filter('query_vars', 'ai_navigator_register_query_vars');

function ai_navigator_template_redirect() {
    global $wp_query;
    if (isset($wp_query->query_vars['ai_search'])) {
        include get_template_directory() . '/search.php';
        exit;
    }
    if (isset($wp_query->query_vars['submit_page'])) {
        include get_template_directory() . '/page-submit.php';
        exit;
    }
}
add_action('template_redirect', 'ai_navigator_template_redirect');

/**
 * 注册提交来源元数据
 */
function ai_navigator_register_submit_meta() {
    register_post_meta('ai_tool', 'submit_contact', array('type' => 'string', 'single' => true, 'show_in_rest' => true));
    register_post_meta('ai_tool', 'submit_time', array('type' => 'string', 'single' => true, 'show_in_rest' => true));
    register_post_meta('ai_tool', 'submit_source', array('type' => 'string', 'single' => true, 'show_in_rest' => true));
}
add_action('init', 'ai_navigator_register_submit_meta');

/**
 * 注册分类图标 term meta
 */
function ai_navigator_register_term_meta() {
    register_term_meta('ai_category', 'category_icon', array('type' => 'string', 'single' => true, 'show_in_rest' => false));
}
add_action('init', 'ai_navigator_register_term_meta');

/**
 * 获取分类图标（未设置则根据 slug 匹配或随机分配）
 */
function ai_navigator_get_category_icon($term) {
    $icon = get_term_meta($term->term_id, 'category_icon', true);
    if (!empty($icon)) return $icon;

    // 根据 slug 匹配默认图标
    $slug_icon_map = array(
        'llm' => '🤖', 'image' => '🎨', 'video' => '🎬', 'audio' => '🎵',
        'code' => '💻', 'writing' => '✍️', 'search' => '🔍', 'tool' => '🛠️',
        'design' => '🎨', 'education' => '📚', 'business' => '💼', 'productivity' => '📊',
        'chat' => '💬', 'translate' => '🌐', 'data' => '📈', 'security' => '🔒',
        'proprietary' => '🏆', 'open_source' => '🔓', '3d' => '🧊',
        'community' => '🤗', 'marketing' => '🛒', 'local' => '🖥️',
    );
    if (isset($slug_icon_map[$term->slug])) return $slug_icon_map[$term->slug];

    // 根据名称关键词匹配
    $name_lower = strtolower($term->name);
    $keyword_map = array(
        '语言' => '🤖', '对话' => '💬', '绘画' => '🎨', '图片' => '🖼️',
        '视频' => '🎬', '音频' => '🎵', '音乐' => '🎶', '编程' => '💻',
        '写作' => '✍️', '搜索' => '🔍', '工具' => '🛠️', '设计' => '🎨',
        '教育' => '📚', '商业' => '💼', '效率' => '📊', '翻译' => '🌐',
        '安全' => '🔒', '数据' => '📈', '办公' => '📝', '开发' => '⚙️',
    );
    foreach ($keyword_map as $kw => $ic) {
        if (strpos($name_lower, $kw) !== false) return $ic;
    }

    // 随机图标（基于 slug 确定性分配）
    $random_icons = array('🤖', '🎨', '🎬', '💻', '🔍', '✨', '🧠', '🚀', '💡', '🎯', '🔧', '📊', '🎵', '🌐', '🔥', '⚡', '💎', '🌟', '🛠️', '📋');
    $idx = abs(crc32($term->slug)) % count($random_icons);
    return $random_icons[$idx];
}

/**
 * 后台分类编辑页面添加图标字段
 */
function ai_navigator_category_icon_field($term) {
    $icon = get_term_meta($term->term_id, 'category_icon', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="category_icon">分类图标</label></th>
        <td>
            <input type="text" id="category_icon" name="category_icon" value="<?php echo esc_attr($icon); ?>" placeholder="🤖" style="width:60px;" maxlength="10">
            <button type="button" class="button" onclick="toggleCatIconPicker()">📋 选择图标</button>
            <span id="catIconPreview" style="font-size:24px;vertical-align:middle;margin-left:8px;"><?php echo $icon ? esc_html($icon) : '📂'; ?></span>
            <div id="catIconPicker" style="display:none;margin-top:8px;border:1px solid #ccd0d4;border-radius:4px;background:#fff;max-width:420px;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;font-weight:500;background:#f8f8f8;">
                    <span>选择图标</span>
                    <button type="button" style="background:none;border:none;font-size:14px;cursor:pointer;color:#666;" onclick="toggleCatIconPicker()">✕</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(10,1fr);gap:1px;padding:8px;overflow-y:auto;max-height:220px;">
                    <?php foreach (ai_navigator_get_icons() as $ic) : ?>
                    <button type="button" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;font-size:18px;border:none;background:transparent;border-radius:4px;cursor:pointer;" onclick="selectCatIcon('<?php echo esc_js($ic); ?>')" title="<?php echo esc_attr($ic); ?>"><?php echo $ic; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="description">留空则根据分类名称自动匹配图标</p>
            <script>
            function toggleCatIconPicker() {
                var p = document.getElementById('catIconPicker');
                p.style.display = p.style.display === 'none' ? 'block' : 'none';
            }
            function selectCatIcon(icon) {
                document.getElementById('category_icon').value = icon;
                document.getElementById('catIconPreview').textContent = icon;
                document.getElementById('catIconPicker').style.display = 'none';
            }
            document.getElementById('category_icon').addEventListener('input', function() {
                document.getElementById('catIconPreview').textContent = this.value || '📂';
            });
            </script>
        </td>
    </tr>
    <?php
}
add_action('ai_category_edit_form_fields', 'ai_navigator_category_icon_field');

/**
 * 后台分类添加页面添加图标字段
 */
function ai_navigator_category_icon_field_add($taxonomy) {
    ?>
    <div class="form-field">
        <label for="category_icon">分类图标</label>
        <input type="text" id="category_icon" name="category_icon" placeholder="🤖" style="width:60px;" maxlength="10">
        <span id="catIconPreview" style="font-size:24px;vertical-align:middle;margin-left:8px;">📂</span>
        <p class="description">留空则自动匹配图标</p>
        <script>
        document.getElementById('category_icon').addEventListener('input', function() {
            document.getElementById('catIconPreview').textContent = this.value || '📂';
        });
        </script>
    </div>
    <?php
}
add_action('ai_category_add_form_fields', 'ai_navigator_category_icon_field_add');

/**
 * 保存分类图标
 */
function ai_navigator_save_category_icon($term_id) {
    if (isset($_POST['category_icon'])) {
        update_term_meta($term_id, 'category_icon', sanitize_text_field($_POST['category_icon']));
    }
}
add_action('edited_ai_category', 'ai_navigator_save_category_icon');
add_action('created_ai_category', 'ai_navigator_save_category_icon');

/**
 * 分类列表页显示图标列
 */
function ai_navigator_category_icon_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'name') {
            $new_columns['icon'] = '图标';
        }
    }
    return $new_columns;
}
add_filter('manage_edit-ai_category_columns', 'ai_navigator_category_icon_column');

function ai_navigator_category_icon_column_content($content, $column_name, $term_id) {
    if ($column_name === 'icon') {
        $icon = get_term_meta($term_id, 'category_icon', true);
        echo $icon ? '<span style="font-size:20px;">' . esc_html($icon) . '</span>' : '<span style="color:#999;">自动</span>';
    }
    return $content;
}
add_filter('manage_ai_category_custom_column', 'ai_navigator_category_icon_column_content', 10, 3);

/**
 * 导航菜单中分类项自动添加图标
 */
function ai_navigator_nav_menu_icon($title, $item, $args, $depth) {
    // 仅对分类法菜单项添加图标
    if ($item->type === 'taxonomy' && $item->object === 'ai_category') {
        $term = get_term($item->object_id, 'ai_category');
        if ($term && !is_wp_error($term)) {
            $icon = ai_navigator_get_category_icon($term);
            // 避免重复添加图标
            $icon_html = '<span class="nav-icon">' . esc_html($icon) . '</span> ';
            if (strpos($title, 'nav-icon') === false) {
                $title = $icon_html . $title;
            }
        }
    }
    return $title;
}
add_filter('nav_menu_item_title', 'ai_navigator_nav_menu_icon', 10, 4);
