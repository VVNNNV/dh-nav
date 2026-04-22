<?php
/**
 * 首页 - 纯 PHP/HTML 实现
 *
 * @package AI_Navigator_Hub
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取所有分类
$categories = get_terms(array(
    'taxonomy' => 'ai_category',
    'hide_empty' => false,
    'parent' => 0,
    'orderby' => 'slug',
    'order' => 'ASC',
));

// 分页和分类筛选
$current_page = isset($_GET['page']) ? max(1, absint($_GET['page'])) : 1;
$current_cat = isset($_GET['ai_cat']) ? sanitize_text_field($_GET['ai_cat']) : '';
$per_page = 15;

// 构建查询参数
$tools_args = array(
    'post_type' => 'ai_tool',
    'posts_per_page' => $per_page,
    'paged' => $current_page,
    'post_status' => 'publish',
);

// 使用 posts_clauses filter 实现 LEFT JOIN 排序（包含没有 tool_order 的文章）
function ai_navigator_order_by_tool_order($clauses) {
    global $wpdb;
    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ai_order_meta ON ({$wpdb->posts}.ID = ai_order_meta.post_id AND ai_order_meta.meta_key = 'tool_order') ";
    $clauses['orderby'] = "COALESCE(ai_order_meta.meta_value+0, 999999) ASC, {$wpdb->posts}.post_date DESC";
    $clauses['groupby'] = "{$wpdb->posts}.ID";
    return $clauses;
}
add_filter('posts_clauses', 'ai_navigator_order_by_tool_order');

if (!empty($current_cat)) {
    $tools_args['tax_query'] = array(
        array(
            'taxonomy' => 'ai_category',
            'field' => 'slug',
            'terms' => $current_cat,
        ),
    );
}

$tools = new WP_Query($tools_args);

// 移除排序 filter，避免影响后续查询
remove_filter('posts_clauses', 'ai_navigator_order_by_tool_order');

// 获取热门网站
$hot_tools = new WP_Query(array(
    'post_type' => 'ai_tool',
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'tool_hot',
            'value' => '1',
            'compare' => '=',
        ),
    ),
));

// 获取点击行为设置
$click_action = get_option('ai_navigator_click_action', 'modal');

// 准备网站数据
$tools_data = array();
while ($tools->have_posts()) : $tools->the_post();
    $tool_id = get_the_ID();
    $tool_url = get_post_meta($tool_id, 'tool_url', true);
    $tool_icon = get_post_meta($tool_id, 'tool_icon', true);
    $tool_hot = get_post_meta($tool_id, 'tool_hot', true);
    $tool_categories = get_the_terms($tool_id, 'ai_category');
    $tool_tags = get_the_terms($tool_id, 'ai_tag');
    
    $primary_cat = !empty($tool_categories) && !is_wp_error($tool_categories) ? $tool_categories[0] : null;
    $cat_slug = $primary_cat ? $primary_cat->slug : '';
    
    $tags_data = array();
    if (!empty($tool_tags) && !is_wp_error($tool_tags)) {
        foreach ($tool_tags as $tag) {
            $tags_data[] = array(
                'name' => $tag->name,
                'slug' => $tag->slug,
                'link' => get_term_link($tag),
            );
        }
    }
    
    $tools_data[] = array(
        'id' => $tool_id,
        'title' => get_the_title(),
        'url' => $tool_url,
        'icon' => $tool_icon ?: mb_substr(get_the_title(), 0, 1),
        'hot' => $tool_hot,
        'desc' => get_the_excerpt() ?: get_the_content() ?: '暂无描述',
        'cat_slug' => $cat_slug,
        'cat_name' => $primary_cat ? $primary_cat->name : '',
        'cat_link' => $primary_cat ? get_term_link($primary_cat) : '',
        'tags' => $tags_data,
    );
endwhile;

// 准备热门网站数据
$hot_tools_data = array();
while ($hot_tools->have_posts()) : $hot_tools->the_post();
    $tool_id = get_the_ID();
    $tool_url = get_post_meta($tool_id, 'tool_url', true);
    $tool_icon = get_post_meta($tool_id, 'tool_icon', true);
    $tool_hot = get_post_meta($tool_id, 'tool_hot', true);
    $tool_categories = get_the_terms($tool_id, 'ai_category');
    $tool_tags = get_the_terms($tool_id, 'ai_tag');
    
    $primary_cat = !empty($tool_categories) && !is_wp_error($tool_categories) ? $tool_categories[0] : null;
    
    $tags_data = array();
    if (!empty($tool_tags) && !is_wp_error($tool_tags)) {
        foreach ($tool_tags as $tag) {
            $tags_data[] = array(
                'name' => $tag->name,
                'slug' => $tag->slug,
                'link' => get_term_link($tag),
            );
        }
    }
    
    $hot_tools_data[] = array(
        'id' => $tool_id,
        'title' => get_the_title(),
        'url' => $tool_url,
        'icon' => $tool_icon ?: mb_substr(get_the_title(), 0, 1),
        'hot' => $tool_hot,
        'desc' => get_the_excerpt() ?: get_the_content() ?: '暂无描述',
        'cat_slug' => $primary_cat ? $primary_cat->slug : '',
        'cat_name' => $primary_cat ? $primary_cat->name : '',
        'cat_link' => $primary_cat ? get_term_link($primary_cat) : '',
        'tags' => $tags_data,
    );
endwhile;

// 页脚设置
$site_name = ai_navigator_get_site_name();
$icp_number = get_option('ai_navigator_icp_number', '');
$icp_url = get_option('ai_navigator_icp_url', 'https://beian.miit.gov.cn/');
$copyright = get_option('ai_navigator_copyright', '');
if (empty($copyright)) {
    $copyright = '&copy; ' . date('Y') . ' ' . $site_name . ' All Rights Reserved.';
}
$contact_info = get_option('ai_navigator_contact_info', '');
$friend_links = get_option('ai_navigator_friend_links', '');

// 解析友情链接
$friend_links_html = '';
if (!empty($friend_links)) {
    $fl_lines = array_filter(array_map('trim', explode("\n", $friend_links)));
    $fl_links = array();
    foreach ($fl_lines as $fl_line) {
        if (strpos($fl_line, '|') !== false) {
            $fl_parts = explode('|', $fl_line, 2);
            $fl_links[] = '<a href="' . esc_url(trim($fl_parts[1])) . '" target="_blank" rel="noopener noreferrer">' . esc_html(trim($fl_parts[0])) . '</a>';
        } else {
            $fl_links[] = $fl_line;
        }
    }
    $friend_links_html = implode('<span class="footer-divider">|</span>', $fl_links);
}
// 解析联系方式
$contact_html = '';
if (!empty($contact_info)) {
    $ct_lines = array_filter(array_map('trim', explode("\n", $contact_info)));
    $ct_items = array();
    foreach ($ct_lines as $ct_line) {
        if ($ct_line !== strip_tags($ct_line)) {
            $ct_items[] = $ct_line;
        } else {
            $ct_items[] = '<span>' . esc_html($ct_line) . '</span>';
        }
    }
    $contact_html = implode('<span class="footer-divider">·</span>', $ct_items);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #06b6d4;
            --bg: #f4f5f7;
            --card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --gradient: linear-gradient(135deg, #3b82f6, #06b6d4);
            --gradient-hot: linear-gradient(135deg, #f97316, #ef4444);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        /* 头部 */
        .header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .header-nav {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        .nav-menu {
            display: flex;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-menu li {
            position: relative;
        }
        .nav-menu a {
            display: block;
            padding: 8px 16px;
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-menu a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }
        .nav-menu .sub-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 160px;
            padding: 8px 0;
            list-style: none;
        }
        .nav-menu li:hover > .sub-menu {
            display: block;
        }
        .nav-menu .sub-menu a {
            padding: 8px 16px;
        }
        .header-count {
            font-size: 14px;
            color: var(--text-muted);
        }
        .header-count strong {
            color: var(--primary);
        }
        
        /* 移动端菜单按钮 */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            color: var(--text);
        }
        
        /* 移动端菜单 */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--card);
            z-index: 200;
            padding: 60px 20px 20px;
            overflow-y: auto;
        }
        .mobile-menu.active {
            display: block;
        }
        .mobile-menu-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        .mobile-menu-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text);
        }
        .mobile-nav-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .mobile-nav-menu li {
            border-bottom: 1px solid var(--border);
        }
        .mobile-nav-menu a {
            display: block;
            padding: 16px 0;
            color: var(--text);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
        }
        .mobile-nav-menu a:hover {
            color: var(--primary);
        }
        .mobile-nav-menu .sub-menu {
            list-style: none;
            padding-left: 20px;
        }
        .mobile-nav-menu .sub-menu a {
            font-size: 14px;
            font-weight: 400;
            color: var(--text-muted);
        }
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 150;
        }
        .mobile-menu-overlay.active {
            display: block;
        }
        
        /* 响应式 */
        @media (max-width: 768px) {
            .hero-title { font-size: 28px; }
            .hero-subtitle { font-size: 16px; }
            .hot-grid { grid-template-columns: repeat(2, 1fr); }
            .header-nav { display: none; }
            .header-count { display: none; }
            .menu-toggle { display: block; }
        }
        @media (max-width: 480px) {
            .hot-grid { grid-template-columns: 1fr; }
            .tools-grid { grid-template-columns: 1fr; }
            .modal-actions { flex-direction: column; }
        }
        /* Hero 区域 */
        .hero {
            background: var(--gradient);
            padding: 60px 20px;
            text-align: center;
            color: #fff;
        }
        .hero-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .hero-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 32px;
        }
        
        /* 搜索框 */
        .search-box {
            max-width: 560px;
            margin: 0 auto;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 16px 56px 16px 24px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            background: #fff;
            color: var(--text);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            outline: none;
            transition: box-shadow 0.2s;
        }
        .search-input:focus {
            box-shadow: 0 6px 24px rgba(0,0,0,0.2);
        }
        .search-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 50%;
            background: var(--gradient);
            color: #fff;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* 热门推荐 */
        .hot-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .section-icon {
            font-size: 24px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
        }
        .hot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .hot-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .hot-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            border-color: var(--primary);
        }
        .hot-card-icon {
            width: 48px;
            height: 48px;
            background: var(--gradient-hot);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            margin-bottom: 12px;
        }
        .hot-card-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text);
        }
        .hot-card-desc {
            font-size: 12px;
            color: var(--text-muted);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .hot-card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .hot-tag {
            font-size: 11px;
            padding: 2px 8px;
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
            border-radius: 8px;
        }
        
        /* 分类导航 */
        .category-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            scroll-margin-top: 80px; /* 为 sticky header 留出空间 */
        }
        .category-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .category-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .category-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .category-btn.active {
            background: var(--gradient);
            color: #fff;
            border-color: transparent;
        }
        .category-btn-icon {
            font-size: 16px;
            line-height: 1;
        }
        .category-toggle {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 10px 18px;
            background: transparent;
            color: var(--primary);
            border: 1px dashed var(--primary);
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .category-toggle:hover {
            background: rgba(59, 130, 246, 0.08);
        }
        .hot-card-hidden { display: none !important; }
        
        /* 主内容 */
        .main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* 网站网格 */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        /* 网站卡片 */
        .tool-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tool-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .tool-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .tool-icon {
            width: 52px;
            height: 52px;
            background: var(--gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
        }
        .tool-hot {
            background: var(--gradient-hot);
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .tool-title {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        .tool-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .tool-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .tool-tag {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(59, 130, 246, 0.08);
            color: var(--primary);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .tool-tag:hover {
            background: var(--primary);
            color: #fff;
        }
        
        /* 弹窗 */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background: var(--card);
            border-radius: 20px;
            padding: 32px;
            max-width: 480px;
            width: 100%;
            position: relative;
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-muted);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: var(--bg);
            color: var(--text);
        }
        .modal-icon {
            width: 72px;
            height: 72px;
            background: var(--gradient);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #fff;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-hot {
            background: var(--gradient-hot);
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .modal-desc {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .modal-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }
        .modal-tag {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(59, 130, 246, 0.08);
            color: var(--primary);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }
        .modal-tag:hover {
            background: var(--primary);
            color: #fff;
        }
        .modal-category {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(6, 182, 212, 0.08);
            color: var(--accent);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 24px;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
        }
        .btn-visit {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            background: var(--gradient);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-visit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        .btn-copy {
            padding: 14px 20px;
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-copy:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card);
            border-radius: 16px;
            color: var(--text-muted);
        }
        
        /* 分页 */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 14px;
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .page-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }
        .page-btn.active {
            background: var(--gradient);
            color: #fff;
            border-color: transparent;
        }
        .page-btn.page-prev,
        .page-btn.page-next {
            padding: 0 18px;
        }
        .page-dots {
            padding: 0 8px;
            color: var(--text-muted);
        }

        /* 页脚 */
        .site-footer {
            background: #1e293b;
            color: rgba(255,255,255,0.6);
            padding: 40px 20px;
            margin-top: 60px;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        .footer-label {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            margin-right: 4px;
        }
        .footer-links {
            margin-bottom: 12px;
        }
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .footer-links a:hover {
            color: #fff;
        }
        .footer-divider {
            margin: 0 10px;
            color: rgba(255,255,255,0.25);
        }
        .footer-friend-links {
            margin-bottom: 16px;
            font-size: 13px;
            line-height: 2;
        }
        .footer-friend-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-friend-links a:hover {
            color: #fff;
        }
        .footer-contact {
            margin-bottom: 12px;
            font-size: 13px;
            line-height: 2;
        }
        .footer-contact a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
        }
        .footer-contact a:hover {
            color: #fff;
        }
        .footer-contact span {
            color: rgba(255,255,255,0.6);
        }
        .footer-copyright {
            font-size: 13px;
            margin-bottom: 8px;
        }
        .footer-icp a {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 12px;
            transition: color 0.2s;
        }
        .footer-icp a:hover {
            color: rgba(255,255,255,0.8);
        }

        /* 响应式 */
        @media (max-width: 768px) {
            .hero-title { font-size: 28px; }
            .hero-subtitle { font-size: 16px; }
            .hot-grid { grid-template-columns: repeat(2, 1fr); }
            .header-nav { display: none; }
        }
        
        /* 移动端紧凑列表布局 */
        @media (max-width: 480px) {
            .hot-grid { display: none; }
            
            /* 紧凑卡片列表 */
            .tools-grid {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .tool-card {
                display: flex;
                align-items: center;
                padding: 12px 16px;
                border-radius: 12px;
                gap: 12px;
            }
            
            .tool-card:hover {
                transform: none;
            }
            
            .tool-card-header {
                margin-bottom: 0;
                flex-shrink: 0;
            }
            
            .tool-icon {
                width: 44px;
                height: 44px;
                font-size: 20px;
                border-radius: 10px;
            }
            
            .tool-hot {
                font-size: 10px;
                padding: 2px 6px;
            }
            
            .tool-card-content {
                flex: 1;
                min-width: 0;
            }
            
            .tool-title {
                font-size: 15px;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .tool-desc {
                font-size: 12px;
                -webkit-line-clamp: 1;
                margin-bottom: 0;
            }
            
            .tool-tags {
                display: none;
            }
            
            .tool-card-arrow {
                flex-shrink: 0;
                color: var(--text-muted);
                font-size: 18px;
            }
            
            .modal-actions { flex-direction: column; }
        }
    </style>
    <?php
    // 阻止 React 应用的资源加载到 front-page
    add_action('wp_enqueue_scripts', function() {
        wp_dequeue_style('ai-navigator-hub-styles');
        wp_dequeue_script('ai-navigator-hub-scripts');
        wp_dequeue_script('ai-navigator-tag-links');
    }, 100);
    wp_head();
    ?>
</head>
<body>
    <!-- 头部 -->
    <header class="header">
        <div class="header-inner">
            <a href="<?php echo home_url(); ?>" class="logo">🤖 <?php echo esc_html($site_name); ?></a>
            <nav class="header-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'nav-menu',
                    'fallback_cb' => function() {
                        echo '<span style="color: var(--text-muted); font-size: 14px;">请在后台设置菜单</span>';
                    },
                ));
                ?>
            </nav>
            <button class="menu-toggle" onclick="openMobileMenu()">☰</button>
            <span class="header-count">共收录 <strong><?php echo $tools->found_posts; ?></strong> 个网站</span>
        </div>
    </header>
    
    <!-- 移动端菜单遮罩 -->
    <div class="mobile-menu-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>
    
    <!-- 移动端菜单 -->
    <nav class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <span style="font-size: 18px; font-weight: 600;">菜单</span>
            <button class="mobile-menu-close" onclick="closeMobileMenu()">×</button>
        </div>
        <?php
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'container' => false,
            'menu_class' => 'mobile-nav-menu',
            'fallback_cb' => function() {
                echo '<ul class="mobile-nav-menu"><li><a href="' . home_url() . '">首页</a></li></ul>';
            },
        ));
        ?>
    </nav>
    
    <!-- Hero 区域 -->
    <section class="hero">
        <h1 class="hero-title">🚀 发现最优质的网站</h1>
        <p class="hero-subtitle">收录全球领先的网站，让你轻松找到最合适的在线工具和服务</p>
        
        <!-- 搜索框 -->
        <form class="search-box" action="<?php echo home_url('/search'); ?>" method="GET">
            <input type="text" name="s" class="search-input" placeholder="搜索网站..." value="">
            <button type="submit" class="search-btn">🔍</button>
        </form>
    </section>
    
    <!-- 热门推荐 -->
    <?php if (!empty($hot_tools_data)) : ?>
    <section class="hot-section">
        <div class="section-header">
            <span class="section-icon">🔥</span>
            <h2 class="section-title">热门推荐</h2>
        </div>
        <div class="hot-grid" id="hotGrid">
            <?php foreach ($hot_tools_data as $hot) : ?>
            <?php if ($click_action === 'detail') : ?>
            <a href="<?php echo get_permalink($hot['id']); ?>" class="hot-card">
            <?php else : ?>
            <a href="#" class="hot-card" onclick="openModal(<?php echo $hot['id']; ?>); return false;">
            <?php endif; ?>
                <div class="hot-card-icon"><?php echo esc_html($hot['icon']); ?></div>
                <div class="hot-card-title"><?php echo esc_html($hot['title']); ?></div>
                <div class="hot-card-desc"><?php echo esc_html(wp_strip_all_tags($hot['desc'])); ?></div>
                <div class="hot-card-tags">
                    <?php foreach (array_slice($hot['tags'], 0, 2) as $tag) : ?>
                        <span class="hot-tag"><?php echo esc_html($tag['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 分类导航 -->
    <nav class="category-nav" id="category-nav" style="margin-top: 30px;">
        <div class="category-list" id="categoryList">
            <a href="<?php echo home_url('/') . '#category-nav'; ?>" class="category-btn <?php echo empty($current_cat) ? 'active' : ''; ?>" data-cat-slug=""><span class="category-btn-icon">📋</span> 全部</a>
            <?php foreach ($categories as $cat) : ?>
                <a href="<?php echo add_query_arg('ai_cat', $cat->slug, home_url('/') . '#category-nav'); ?>" class="category-btn <?php echo $current_cat === $cat->slug ? 'active' : ''; ?>" data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                    <span class="category-btn-icon"><?php echo esc_html(ai_navigator_get_category_icon($cat)); ?></span>
                    <?php echo esc_html($cat->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <!-- 主内容 -->
    <main class="main">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h2 id="sectionTitle" style="font-size:20px;font-weight:600;"><?php
            if (empty($current_cat)) {
                echo '📋 全部网站';
            } else {
                $current_term = get_term_by('slug', $current_cat, 'ai_category');
                echo esc_html(ai_navigator_get_category_icon($current_term)) . ' ' . esc_html($current_term->name);
            }
            ?></h2>
            <span id="toolCount" style="color:var(--text-muted);font-size:14px;">共 <?php echo $tools->found_posts; ?> 个</span>
        </div>
        <div class="tools-grid" id="toolsGrid">
            <?php while ($tools->have_posts()) : $tools->the_post(); ?>
            <?php 
                $tool_id = get_the_ID();
                $tool_url = get_post_meta($tool_id, 'tool_url', true);
                $tool_icon = get_post_meta($tool_id, 'tool_icon', true);
                $tool_hot = get_post_meta($tool_id, 'tool_hot', true);
                $tool_categories = get_the_terms($tool_id, 'ai_category');
                $tool_tags = get_the_terms($tool_id, 'ai_tag');
                
                $primary_cat = !empty($tool_categories) && !is_wp_error($tool_categories) ? $tool_categories[0] : null;
                $cat_slug = $primary_cat ? $primary_cat->slug : '';
            ?>
                <div class="tool-card" <?php if ($click_action === 'detail') : ?>onclick="window.location.href='<?php echo get_permalink($tool_id); ?>'"<?php else : ?>onclick="openModal(<?php echo $tool_id; ?>)"<?php endif; ?>>
                    <div class="tool-card-header">
                        <div class="tool-icon"><?php echo esc_html($tool_icon ?: mb_substr(get_the_title(), 0, 1)); ?></div>
                        <?php if ($tool_hot) : ?>
                            <span class="tool-hot">🔥</span>
                        <?php endif; ?>
                    </div>
                    <div class="tool-card-content">
                        <h3 class="tool-title"><?php the_title(); ?></h3>
                        <p class="tool-desc"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt() ?: '暂无描述')); ?></p>
                    </div>
                    <div class="tool-tags">
                        <?php if (!empty($tool_tags) && !is_wp_error($tool_tags)) : ?>
                            <?php foreach (array_slice($tool_tags, 0, 3) as $tag) : ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="tool-tag" onclick="event.stopPropagation()">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <span class="tool-card-arrow">›</span>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- 分页 -->
        <?php if ($tools->max_num_pages > 1) : ?>
        <nav class="pagination">
            <?php
            $base_url = home_url('/');
            if (!empty($current_cat)) {
                $base_url = add_query_arg('ai_cat', $current_cat, $base_url);
            }

            $total_pages = $tools->max_num_pages;

            // 上一页
            if ($current_page > 1) {
                $prev_url = $current_page === 2 ? $base_url : add_query_arg('page', $current_page - 1, $base_url);
                echo '<a href="' . esc_url($prev_url) . '" class="page-btn page-prev">&laquo; 上一页</a>';
            }

            // 页码
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);

            if ($start > 1) {
                echo '<a href="' . esc_url($base_url) . '" class="page-btn">1</a>';
                if ($start > 2) echo '<span class="page-dots">...</span>';
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($i === 1) {
                    $page_url = $base_url;
                } else {
                    $page_url = add_query_arg('page', $i, $base_url);
                }
                $active = $i === $current_page ? ' active' : '';
                echo '<a href="' . esc_url($page_url) . '" class="page-btn' . $active . '">' . $i . '</a>';
            }

            if ($end < $total_pages) {
                if ($end < $total_pages - 1) echo '<span class="page-dots">...</span>';
                $last_url = add_query_arg('page', $total_pages, $base_url);
                echo '<a href="' . esc_url($last_url) . '" class="page-btn">' . $total_pages . '</a>';
            }

            // 下一页
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('page', $current_page + 1, $base_url);
                echo '<a href="' . esc_url($next_url) . '" class="page-btn page-next">下一页 &raquo;</a>';
            }
            ?>
        </nav>
        <?php endif; ?>
    </main>
    
    <!-- 页脚 -->
    <footer class="site-footer">
        <div class="footer-inner">
            <?php if (!empty($friend_links_html)) : ?>
            <div class="footer-friend-links">
                <span class="footer-label">友情链接：</span>
                <?php echo $friend_links_html; ?>
            </div>
            <?php endif; ?>
            <div class="footer-links">
                <a href="<?php echo home_url('/submit'); ?>">申请收录</a>
                <span class="footer-divider">|</span>
                <a href="<?php echo home_url(); ?>"><?php echo esc_html($site_name); ?></a>
            </div>
            <?php if (!empty($contact_html)) : ?>
            <div class="footer-contact">
                <span class="footer-label">联系方式：</span>
                <?php echo $contact_html; ?>
            </div>
            <?php endif; ?>
            <div class="footer-copyright">
                <?php echo $copyright; ?>
            </div>
            <?php if (!empty($icp_number)) : ?>
            <div class="footer-icp">
                <a href="<?php echo esc_url($icp_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($icp_number); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- 弹窗 -->
    <?php if ($click_action !== 'detail') : ?>
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
        <div class="modal" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div class="modal-icon" id="modalIcon">🤖</div>
            <h2 class="modal-title">
                <span id="modalTitle">网站名称</span>
                <span class="modal-hot" id="modalHot" style="display:none">🔥 热门</span>
            </h2>
            <a href="#" class="modal-category" id="modalCategory" onclick="event.stopPropagation()">分类</a>
            <p class="modal-desc" id="modalDesc">网站描述...</p>
            <div class="modal-tags" id="modalTags"></div>
            <div class="modal-actions">
                <a href="#" class="btn-visit" id="modalVisitBtn" target="_blank" onclick="event.stopPropagation()">
                    🚀 访问网站
                </a>
                <button class="btn-copy" data-url="" onclick="copyLink(this)">
                    📋 复制链接
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    <?php if ($click_action !== 'detail') : ?>
    // 网站数据
    const toolsData = <?php echo json_encode(array_merge($tools_data, $hot_tools_data)); ?>;
    let currentUrl = '';
    
    // 打开弹窗
    function openModal(id) {
        const tool = toolsData.find(t => t.id === id);
        if (!tool) return;
        
        currentUrl = tool.url || '';
        
        document.getElementById('modalIcon').textContent = tool.icon;
        document.getElementById('modalTitle').textContent = tool.title;
        document.getElementById('modalDesc').textContent = tool.desc;
        
        // 热门标记
        document.getElementById('modalHot').style.display = tool.hot ? '' : 'none';
        
        // 分类
        const catEl = document.getElementById('modalCategory');
        if (tool.cat_name) {
            catEl.style.display = '';
            catEl.textContent = '📂 ' + tool.cat_name;
            catEl.href = tool.cat_link;
        } else {
            catEl.style.display = 'none';
        }
        
        // 标签
        const tagsEl = document.getElementById('modalTags');
        tagsEl.innerHTML = '';
        if (tool.tags && tool.tags.length) {
            tool.tags.forEach(tag => {
                const a = document.createElement('a');
                a.href = tag.link;
                a.className = 'modal-tag';
                a.textContent = tag.name;
                a.onclick = function(e) { e.stopPropagation(); };
                tagsEl.appendChild(a);
            });
        }
        
        // 访问按钮
        const visitBtn = document.getElementById('modalVisitBtn');
        const copyBtn = document.querySelector('.modal .btn-copy');
        if (currentUrl) {
            visitBtn.style.display = '';
            visitBtn.href = currentUrl;
            if (copyBtn) copyBtn.setAttribute('data-url', currentUrl);
        } else {
            visitBtn.style.display = 'none';
            if (copyBtn) copyBtn.setAttribute('data-url', '');
        }
        
        document.getElementById('modalOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // 关闭弹窗
    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // ESC 关闭弹窗
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
    
    // 复制链接
    function copyLink(btn) {
        const url = btn.getAttribute('data-url');
        if (!url || url === '') {
            alert('网站链接未设置');
            return;
        }
        // HTTP 下 navigator.clipboard 不可用，使用 execCommand 方案
        const textarea = document.createElement('textarea');
        textarea.value = url;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            btn.textContent = '✅ 已复制';
        } catch(e) {
            btn.textContent = '复制失败';
        }
        document.body.removeChild(textarea);
        setTimeout(() => { btn.textContent = '📋 复制链接'; }, 2000);
    }
    <?php endif; ?>
    
    // 移动端菜单
    function openMobileMenu() {
        document.getElementById('mobileMenu').classList.add('active');
        document.getElementById('mobileOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        document.getElementById('mobileMenu').classList.remove('active');
        document.getElementById('mobileOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    // 热门分类两行自适应
    (function() {
        var list = document.getElementById('categoryList');
        if (!list) return;
        var btns = list.querySelectorAll('.category-btn');
        if (btns.length === 0) return;

        var toggleBtn = null;
        var expanded = false;

        function calcVisible() {
            var listWidth = list.offsetWidth;
            var gap = 8;
            var currentRowWidth = 0;
            var perRow = 0;
            var row = 1;
            var twoRowCount = 0;
            for (var i = 0; i < btns.length; i++) {
                var w = btns[i].offsetWidth + gap;
                if (currentRowWidth + w > listWidth) {
                    row++;
                    currentRowWidth = w;
                } else {
                    currentRowWidth += w;
                }
                if (row === 1) perRow++;
                if (row <= 2) twoRowCount = i + 1;
            }
            return { perRow: perRow, twoRowCount: twoRowCount };
        }

        function applyCategoryLayout() {
            var info = calcVisible();
            expanded = false;
            if (toggleBtn) { toggleBtn.remove(); toggleBtn = null; }

            if (btns.length <= info.twoRowCount) {
                for (var i = 0; i < btns.length; i++) btns[i].style.display = '';
                return;
            }

            for (var i = 0; i < btns.length; i++) {
                btns[i].style.display = i < info.twoRowCount ? '' : 'none';
            }

            toggleBtn = document.createElement('button');
            toggleBtn.className = 'category-toggle';
            toggleBtn.textContent = '更多 ▼';
            toggleBtn.onclick = function() {
                expanded = !expanded;
                for (var i = 0; i < btns.length; i++) {
                    btns[i].style.display = (expanded || i < info.twoRowCount) ? '' : 'none';
                }
                toggleBtn.textContent = expanded ? '收起 ▲' : '更多 ▼';
            };
            list.appendChild(toggleBtn);
        }

        applyCategoryLayout();
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(applyCategoryLayout, 200);
        });
    })();

    // 热门推荐最多两行，超出隐藏
    (function() {
        var grid = document.getElementById('hotGrid');
        if (!grid) return;
        var cards = grid.querySelectorAll('.hot-card');
        if (cards.length === 0) return;

        function calcTwoRows() {
            var style = getComputedStyle(grid);
            var cols = style.gridTemplateColumns.split(' ').length;
            if (cols < 1) cols = Math.floor(grid.offsetWidth / (200 + 16));
            if (cols < 1) cols = 1;
            return cols * 2;
        }

        function applyHotLayout() {
            var count = calcTwoRows();
            for (var i = 0; i < cards.length; i++) {
                if (i < count) {
                    cards[i].classList.remove('hot-card-hidden');
                } else {
                    cards[i].classList.add('hot-card-hidden');
                }
            }
        }

        applyHotLayout();
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(applyHotLayout, 200);
        });
    })();
    </script>
    <?php wp_footer(); ?>
</body>
</html>
<?php
wp_reset_postdata();
?>
