<?php
/**
 * 搜索结果页面
 *
 * @package AI_Navigator_Hub
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取搜索参数
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$category_slug = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// 页脚设置
$_site_name = ai_navigator_get_site_name();
$_icp_number = get_option('ai_navigator_icp_number', '');
$_icp_url = get_option('ai_navigator_icp_url', 'https://beian.miit.gov.cn/');
$_copyright = get_option('ai_navigator_copyright', '');
if (empty($_copyright)) $_copyright = '&copy; ' . date('Y') . ' ' . $_site_name . ' All Rights Reserved.';
$_contact_info = get_option('ai_navigator_contact_info', '');
$_friend_links = get_option('ai_navigator_friend_links', '');

$_friend_links_html = '';
if (!empty($_friend_links)) {
    $_fl = array_filter(array_map('trim', explode("\n", $_friend_links)));
    $_fls = array();
    foreach ($_fl as $l) {
        if (strpos($l, '|') !== false) { $p = explode('|', $l, 2); $_fls[] = '<a href="' . esc_url(trim($p[1])) . '" target="_blank" rel="noopener noreferrer" style="color:rgba(255,255,255,0.7);text-decoration:none;">' . esc_html(trim($p[0])) . '</a>'; }
        else { $_fls[] = $l; }
    }
    $_friend_links_html = implode('<span style="margin:0 10px;color:rgba(255,255,255,0.25);">|</span>', $_fls);
}
$_contact_html = '';
if (!empty($_contact_info)) {
    $_cl = array_filter(array_map('trim', explode("\n", $_contact_info)));
    $_cts = array();
    foreach ($_cl as $l) {
        if ($l !== strip_tags($l)) $_cts[] = $l;
        else $_cts[] = '<span style="color:rgba(255,255,255,0.6);">' . esc_html($l) . '</span>';
    }
    $_contact_html = implode('<span style="margin:0 8px;color:rgba(255,255,255,0.25);">·</span>', $_cts);
}

// 获取所有分类
$categories = get_terms(array(
    'taxonomy' => 'ai_category',
    'hide_empty' => false,
    'parent' => 0,
    'orderby' => 'slug',
    'order' => 'ASC',
));

// 构建查询参数
$args = array(
    'post_type' => 'ai_tool',
    'posts_per_page' => -1,
    'post_status' => 'publish',
);

// 如果有搜索关键词
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

// 如果有分类筛选
if (!empty($category_slug)) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'ai_category',
            'field' => 'slug',
            'terms' => $category_slug,
        ),
    );
}

$search_results = new WP_Query($args);

// 准备网站数据
$click_action = get_option('ai_navigator_click_action', 'modal');
$tools_data = array();
if ($search_results->have_posts()) {
    while ($search_results->have_posts()) : $search_results->the_post();
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
}

$total_count = wp_count_posts('ai_tool')->publish;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        $page_title = '搜索结果';
        if (!empty($search_query)) $page_title .= ' - ' . esc_html($search_query);
        echo $page_title . ' - ' . get_bloginfo('name');
    ?></title>
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
        .nav-menu li { position: relative; }
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
        .header-count {
            font-size: 14px;
            color: var(--text-muted);
        }
        .header-count strong { color: var(--primary); }
        
        /* 搜索区域 */
        .search-hero {
            background: var(--gradient);
            padding: 40px 20px;
            text-align: center;
            color: #fff;
        }
        .search-hero-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .search-form {
            max-width: 560px;
            margin: 20px auto 0;
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
        
        /* 分类导航 */
        .category-nav {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 0 20px;
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
        
        /* 主内容 */
        .main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .results-title {
            font-size: 20px;
            font-weight: 600;
        }
        .results-count {
            color: var(--text-muted);
            font-size: 14px;
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
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card);
            border-radius: 16px;
            color: var(--text-muted);
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .empty-state-text {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--gradient);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
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
        .modal-overlay.active { display: flex; }
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
        }
        .modal-close:hover { background: var(--bg); }
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
        }
        .btn-copy:hover { border-color: var(--primary); color: var(--primary); }
        
        /* 面包屑 */
        .breadcrumb {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 0 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
        }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb span { margin: 0 8px; }
        
        /* 响应式 */
        @media (max-width: 768px) {
            .header-nav { display: none; }
            .search-hero-title { font-size: 22px; }
            .tools-grid { grid-template-columns: 1fr; }
            .modal-actions { flex-direction: column; }
        }
        
        /* 移动端紧凑列表布局 */
        @media (max-width: 480px) {
            .category-nav { display: none; }
            
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
        }
    </style>
</head>
<body>
    <!-- 头部 -->
    <header class="header">
        <div class="header-inner">
            <a href="<?php echo home_url(); ?>" class="logo">🤖 <?php echo esc_html($_site_name); ?></a>
            <nav class="header-nav">
                <?php wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'nav-menu',
                    'fallback_cb' => function() {
                        echo '<span style="color: var(--text-muted); font-size: 14px;">请在后台设置菜单</span>';
                    },
                )); ?>
            </nav>
            <span class="header-count">共收录 <strong><?php echo $total_count; ?></strong> 个网站</span>
        </div>
    </header>
    
    <!-- 面包屑 -->
    <div class="breadcrumb">
        <a href="<?php echo home_url(); ?>">首页</a>
        <span>/</span>
        <?php if (!empty($search_query)) : ?>
            <span>搜索: <?php echo esc_html($search_query); ?></span>
        <?php else : ?>
            <span>浏览全部</span>
        <?php endif; ?>
    </div>
    
    <!-- 搜索区域 -->
    <section class="search-hero">
        <h1 class="search-hero-title"><?php 
            if (!empty($search_query)) {
                echo '搜索 "' . esc_html($search_query) . '" 的结果';
            } else {
                echo '浏览网站';
            }
        ?></h1>
        <form class="search-form" action="<?php echo home_url('/search'); ?>" method="GET">
            <input type="text" name="s" class="search-input" placeholder="搜索网站..." value="<?php echo esc_attr($search_query); ?>">
            <button type="submit" class="search-btn">🔍</button>
        </form>
    </section>
    
    <!-- 分类筛选 -->
    <nav class="category-nav">
        <div class="category-list">
            <a href="<?php echo home_url('/search'); ?><?php echo !empty($search_query) ? '?s=' . urlencode($search_query) : ''; ?>" 
               class="category-btn <?php echo empty($category_slug) ? 'active' : ''; ?>">
                📋 全部
            </a>
            <?php foreach ($categories as $cat) : ?>
                <a href="<?php echo esc_url(add_query_arg(array_filter([
                    's' => $search_query,
                    'category' => $cat->slug,
                ]), home_url('/search'))); ?>" 
                   class="category-btn <?php echo $category_slug === $cat->slug ? 'active' : ''; ?>">
                    <?php echo esc_html($cat->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <!-- 主内容 -->
    <main class="main">
        <div class="results-header">
            <h2 class="results-title"><?php 
                if (!empty($search_query)) {
                    echo '🔍 搜索结果';
                } else {
                    echo '🔧 全部网站';
                }
            ?></h2>
            <span class="results-count">共 <?php echo $search_results->found_posts; ?> 个</span>
        </div>
        
        <?php if ($search_results->have_posts()) : ?>
        <div class="tools-grid" id="toolsGrid">
            <?php while ($search_results->have_posts()) : $search_results->the_post(); ?>
            <?php 
                $tool_id = get_the_ID();
                $tool_url = get_post_meta($tool_id, 'tool_url', true);
                $tool_icon = get_post_meta($tool_id, 'tool_icon', true);
                $tool_hot = get_post_meta($tool_id, 'tool_hot', true);
                $tool_categories = get_the_terms($tool_id, 'ai_category');
                
                $primary_cat = !empty($tool_categories) && !is_wp_error($tool_categories) ? $tool_categories[0] : null;
                $cat_slug = $primary_cat ? $primary_cat->slug : '';
            ?>
                <div class="tool-card" data-category="<?php echo esc_attr($cat_slug); ?>" <?php if ($click_action === 'detail') : ?>onclick="window.location.href='<?php echo get_permalink($tool_id); ?>'"<?php else : ?>onclick="openModal(<?php echo $tool_id; ?>)"<?php endif; ?>>
                    <div class="tool-card-header">
                        <div class="tool-icon"><?php echo esc_html($tool_icon ?: mb_substr(get_the_title(), 0, 1)); ?></div>
                        <?php if ($tool_hot) : ?>
                            <span class="tool-hot">🔥 热门</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="tool-title"><?php the_title(); ?></h3>
                    <p class="tool-desc"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt() ?: '暂无描述')); ?></p>
                    <div class="tool-tags">
                        <?php 
                        $tool_tags = get_the_terms($tool_id, 'ai_tag');
                        if (!empty($tool_tags) && !is_wp_error($tool_tags)) : 
                            foreach (array_slice($tool_tags, 0, 3) as $tag) : 
                        ?>
                            <a href="<?php echo get_term_link($tag); ?>" class="tool-tag" onclick="event.stopPropagation()">
                                <?php echo esc_html($tag->name); ?>
                            </a>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else : ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <p class="empty-state-text">
                <?php if (!empty($search_query)) : ?>
                    未找到与 "<?php echo esc_html($search_query); ?>" 相关的网站
                <?php else : ?>
                    暂无相关网站
                <?php endif; ?>
            </p>
            <a href="<?php echo home_url(); ?>" class="back-link">← 返回首页</a>
        </div>
        <?php endif; ?>
    </main>
    
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
                <button class="btn-copy" data-url="" onclick="copyLink(this)">📋 复制链接</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    <?php if ($click_action !== 'detail') : ?>
    // 网站数据
    const toolsData = <?php echo json_encode($tools_data); ?>;
    let currentUrl = '';
    
    // 打开弹窗
    function openModal(id) {
        const tool = toolsData.find(t => t.id === id);
        if (!tool) return;
        
        currentUrl = tool.url || '';
        
        document.getElementById('modalIcon').textContent = tool.icon;
        document.getElementById('modalTitle').textContent = tool.title;
        document.getElementById('modalDesc').textContent = tool.desc;
        
        document.getElementById('modalHot').style.display = tool.hot ? '' : 'none';
        
        const catEl = document.getElementById('modalCategory');
        if (tool.cat_name) {
            catEl.style.display = '';
            catEl.textContent = '📂 ' + tool.cat_name;
            catEl.href = tool.cat_link;
        } else {
            catEl.style.display = 'none';
        }
        
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
    </script>

    <!-- 页脚 -->
    <footer class="site-footer" style="background:#1e293b;color:rgba(255,255,255,0.6);padding:40px 20px;margin-top:60px;">
        <div style="max-width:1200px;margin:0 auto;text-align:center;">
            <?php if (!empty($_friend_links_html)) : ?>
            <div style="margin-bottom:16px;font-size:13px;line-height:2;">
                <span style="color:rgba(255,255,255,0.4);margin-right:4px;">友情链接：</span>
                <?php echo $_friend_links_html; ?>
            </div>
            <?php endif; ?>
            <div style="margin-bottom:12px;">
                <a href="<?php echo home_url('/submit'); ?>" style="color:rgba(255,255,255,0.8);text-decoration:none;font-size:14px;">申请收录</a>
                <span style="margin:0 10px;color:rgba(255,255,255,0.25);">|</span>
                <a href="<?php echo home_url(); ?>" style="color:rgba(255,255,255,0.8);text-decoration:none;font-size:14px;"><?php echo esc_html($_site_name); ?></a>
            </div>
            <?php if (!empty($_contact_html)) : ?>
            <div style="margin-bottom:12px;font-size:13px;line-height:2;">
                <span style="color:rgba(255,255,255,0.4);margin-right:4px;">联系方式：</span>
                <?php echo $_contact_html; ?>
            </div>
            <?php endif; ?>
            <div style="font-size:13px;margin-bottom:8px;"><?php echo $_copyright; ?></div>
            <?php if (!empty($_icp_number)) : ?>
            <div><a href="<?php echo esc_url($_icp_url); ?>" target="_blank" rel="noopener noreferrer" style="color:rgba(255,255,255,0.5);text-decoration:none;font-size:12px;"><?php echo esc_html($_icp_number); ?></a></div>
            <?php endif; ?>
        </div>
    </footer>
</body>
</html>
<?php
wp_reset_postdata();
?>
