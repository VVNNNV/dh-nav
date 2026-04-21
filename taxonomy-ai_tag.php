<?php
/**
 * 标签页面模板 - 网站标签
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;
$term_count = $term->count;

// 获取所有标签用于标签云
$all_tags = get_terms(array(
    'taxonomy' => 'ai_tag',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
));

// 获取所有分类
$all_categories = get_terms(array(
    'taxonomy' => 'ai_category',
    'hide_empty' => false,
    'parent' => 0,
));

// 获取当前标签下的所有网站
$tools = new WP_Query(array(
    'post_type' => 'ai_tool',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'ai_tag',
            'field' => 'term_id',
            'terms' => $term_id,
        ),
    ),
));

// 获取使用该标签的网站所属的分类
$tag_categories = array();
if ($tools->have_posts()) {
    $tag_tool_ids = wp_list_pluck($tools->posts, 'ID');
    foreach ($tag_tool_ids as $tool_id) {
        $cats = get_the_terms($tool_id, 'ai_category');
        if ($cats && !is_wp_error($cats)) {
            foreach ($cats as $cat) {
                $tag_categories[$cat->term_id] = $cat;
            }
        }
    }
}

// 准备网站数据用于弹窗
$click_action = get_option('ai_navigator_click_action', 'modal');
$tools_data = array();
if ($tools->have_posts()) {
    while ($tools->have_posts()) {
        $tools->the_post();
        $tool_id = get_the_ID();
        $tool_url = get_post_meta($tool_id, 'tool_url', true);
        $tool_icon = get_post_meta($tool_id, 'tool_icon', true);
        $tool_hot = get_post_meta($tool_id, 'tool_hot', true);
        $tool_cats = get_the_terms($tool_id, 'ai_category');
        $tool_tags = get_the_terms($tool_id, 'ai_tag');
        
        $primary_cat = !empty($tool_cats) && !is_wp_error($tool_cats) ? $tool_cats[0] : null;
        
        $tags_data = array();
        if ($tool_tags && !is_wp_error($tool_tags)) {
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
            'cat_slug' => $primary_cat ? $primary_cat->slug : '',
            'cat_name' => $primary_cat ? $primary_cat->name : '',
            'cat_link' => $primary_cat ? get_term_link($primary_cat) : '',
            'tags' => $tags_data,
        );
    }
    wp_reset_postdata();
}
?>

<style>
/* =========================
   浅色背景主题 - 与首页统一
   ========================= */
:root {
    --primary: #3b82f6;
    --primary-dark: #2563eb;
    --accent: #06b6d4;
    --background: #f4f5f7;
    --card: #ffffff;
    --text: #1e293b;
    --text-secondary: #64748b;
    --border: #e2e8f0;
    --gradient-primary: linear-gradient(135deg, #3b82f6, #06b6d4);
    --shadow-card: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
    --shadow-card-hover: 0 4px 16px rgba(59,130,246,0.12), 0 8px 24px rgba(0,0,0,0.06);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", sans-serif;
    background: var(--background);
    color: var(--text);
    line-height: 1.6;
}

/* 统一头部 */
.site-header {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 16px 0;
    position: sticky;
    top: 0;
    z-index: 100;
}
.site-header-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.site-logo {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
}
.site-nav {
    flex: 1;
    display: flex;
    justify-content: center;
}
.site-nav .nav-menu {
    display: flex;
    gap: 8px;
    list-style: none;
    margin: 0;
    padding: 0;
}
.site-nav .nav-menu li { position: relative; }
.site-nav .nav-menu a {
    display: block;
    padding: 8px 16px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s;
}
.site-nav .nav-menu a:hover {
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary);
}
.site-header-count {
    font-size: 14px;
    color: var(--text-secondary);
}
.site-header-count strong { color: var(--primary); }

/* 页面容器 */
.taxonomy-page {
    min-height: 100vh;
    padding: 0 0 60px 0;
}

/* 页面头部 */
.page-header {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 40px 20px;
    margin-bottom: 40px;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: var(--primary-dark);
}

.page-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
}

.page-title {
    font-size: 36px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 12px 0;
    font-family: 'Space Grotesk', sans-serif;
}

.page-description {
    font-size: 16px;
    color: var(--text-secondary);
    margin: 0 0 20px 0;
}

.page-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    font-size: 14px;
    color: var(--text-secondary);
}

.stat-value {
    font-weight: 600;
    color: var(--primary);
}

/* 导航容器 */
.nav-section {
    max-width: 1200px;
    margin: 0 auto 40px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
}

.nav-card {
    background: var(--card);
    border-radius: 16px;
    padding: 24px;
    border: 1px solid var(--border);
}

.nav-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* 分类导航 */
.category-nav-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.category-nav-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--background);
    color: var(--text);
    text-decoration: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.category-nav-item:hover {
    background: var(--primary);
    color: #fff;
}

/* 标签云 */
.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-cloud-item {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    background: var(--background);
    color: var(--text);
    text-decoration: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.tag-cloud-item:hover {
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff;
}

.tag-cloud-item.active {
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff;
}

/* 网站列表 */
.tools-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 24px 0;
    font-family: 'Space Grotesk', sans-serif;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

/* 网站卡片 */
.tool-card {
    background: var(--card);
    border-radius: 16px;
    padding: 24px;
    position: relative;
    transition: all 0.3s;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.tool-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-card-hover);
    border-color: rgba(139, 92, 246, 0.2);
}

.card-hot {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #f97316, #ef4444);
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.card-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    line-height: 1;
    color: #fff;
}

.card-body {
    flex: 1;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 8px 0;
    font-family: 'Space Grotesk', sans-serif;
}

.card-description {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0 0 12px 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.card-category {
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

.card-category:hover {
    background: var(--primary);
    color: #fff;
}

.card-tag {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(139, 92, 246, 0.08);
    color: #8b5cf6;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.card-tag:hover {
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff;
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.card-arrow {
    display: none;
    flex-shrink: 0;
    color: var(--text-secondary);
    font-size: 20px;
}

.visit-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.visit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--card);
    border-radius: 16px;
    border: 1px solid var(--border);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 18px;
    color: var(--text);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: var(--text-secondary);
}

/* 响应式 */
@media (max-width: 768px) {
    .site-nav { display: none; }
    .nav-section {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 30px 16px;
    }
    
    .page-title {
        font-size: 28px;
    }
    
    .category-nav,
    .tools-section {
        padding: 0 16px;
    }
    
    .tools-grid {
        grid-template-columns: 1fr;
    }
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
    
    .card-icon {
        width: 44px;
        height: 44px;
        font-size: 20px;
        border-radius: 10px;
        flex-shrink: 0;
    }
    
    .card-content {
        flex: 1;
        min-width: 0;
    }
    
    .card-title {
        font-size: 15px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .card-description {
        font-size: 12px;
        -webkit-line-clamp: 1;
        margin-bottom: 0;
    }
    
    .card-meta {
        display: none;
    }
    
    .card-arrow {
        flex-shrink: 0;
        color: var(--text-secondary);
        font-size: 18px;
    }
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
    color: var(--text-secondary);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
.modal-close:hover { background: var(--background); }
.modal-icon {
    width: 72px;
    height: 72px;
    background: var(--gradient-primary);
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
    background: linear-gradient(135deg, #f97316, #ef4444);
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.modal-desc {
    font-size: 15px;
    color: var(--text-secondary);
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
    background: var(--gradient-primary);
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
    background: var(--background);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
}
.btn-copy:hover { border-color: var(--primary); color: var(--primary); }
</style>

<div class="taxonomy-page">
    <!-- 统一头部 -->
    <header class="site-header">
        <div class="site-header-inner">
            <a href="<?php echo home_url(); ?>" class="site-logo">🤖 大海导航</a>
            <nav class="site-nav">
                <?php wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'nav-menu',
                    'fallback_cb' => function() {
                        echo '<span style="color: var(--text-secondary); font-size: 14px;">请在后台设置菜单</span>';
                    },
                )); ?>
            </nav>
            <span class="site-header-count">共收录 <strong><?php echo wp_count_posts('ai_tool')->publish; ?></strong> 个网站</span>
        </div>
    </header>
    
    <!-- 页面头部 -->
    <div class="page-header">
        <div class="header-content">
            <!-- 面包屑 -->
            <div class="breadcrumb">
                <a href="<?php echo home_url('/'); ?>">首页</a>
                <span>›</span>
                <a href="<?php echo home_url('/'); ?>">网站</a>
                <span>›</span>
                <span><?php echo esc_html($term_name); ?></span>
            </div>
            
            <!-- 标签图标 -->
            <div class="page-icon">🏷️</div>
            
            <!-- 标题 -->
            <h1 class="page-title"><?php echo esc_html($term_name); ?></h1>
            
            <!-- 描述 -->
            <p class="page-description">标签下共有 <?php echo $term_count; ?> 个网站</p>
            
            <!-- 统计 -->
            <div class="page-stats">
                <div class="stat-item">
                    <span>🔧</span>
                    <span class="stat-value"><?php echo $term_count; ?></span>
                    <span>个网站</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 导航区域 -->
    <div class="nav-section">
        <!-- 分类导航 -->
        <div class="nav-card">
            <h3 class="nav-title">📂 分类导航</h3>
            <div class="category-nav-list">
                <a href="<?php echo home_url('/'); ?>" class="category-nav-item">
                    📋 全部网站
                </a>
                <?php foreach ($all_categories as $cat) : ?>
                    <a href="<?php echo get_term_link($cat); ?>" class="category-nav-item">
                        <?php echo esc_html($cat->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 标签云 -->
        <div class="nav-card">
            <h3 class="nav-title">🏷️ 相关标签</h3>
            <div class="tag-cloud">
                <?php foreach ($all_tags as $tag) : ?>
                    <a href="<?php echo get_term_link($tag); ?>" 
                       class="tag-cloud-item <?php echo $tag->term_id == $term_id ? 'active' : ''; ?>">
                        <?php echo esc_html($tag->name); ?>
                        <span style="opacity: 0.7; margin-left: 4px;">(<?php echo $tag->count; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 网站列表 -->
    <section class="tools-section">
        <h2 class="section-title">🔧 带有「<?php echo esc_html($term_name); ?>」标签的网站</h2>

        <?php if ($tools->have_posts()) : ?>
            <div class="tools-grid">
                <?php while ($tools->have_posts()) : $tools->the_post(); ?>
                    <?php
                    $tool_url = get_post_meta(get_the_ID(), 'tool_url', true);
                    $tool_icon = get_post_meta(get_the_ID(), 'tool_icon', true);
                    $tool_hot = get_post_meta(get_the_ID(), 'tool_hot', true);
                    $categories = get_the_terms(get_the_ID(), 'ai_category');
                    $tags = get_the_terms(get_the_ID(), 'ai_tag');
                    ?>
                    <article class="tool-card" <?php if ($click_action === 'detail') : ?>onclick="window.location.href='<?php echo get_permalink(); ?>'"<?php else : ?>onclick="openModal(<?php echo get_the_ID(); ?>)"<?php endif; ?>>
                        <?php if ($tool_hot) : ?>
                            <span class="card-hot">🔥</span>
                        <?php endif; ?>
                        
                        <div class="card-icon">
                            <?php echo $tool_icon ? esc_html($tool_icon) : mb_substr(get_the_title(), 0, 1); ?>
                        </div>
                        
                        <div class="card-body">
                            <h3 class="card-title"><?php the_title(); ?></h3>
                            <p class="card-description"><?php echo esc_html(get_the_excerpt() ?: '暂无描述'); ?></p>
                        </div>
                        
                        <span class="card-arrow">›</span>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3 class="empty-title">暂无网站</h3>
                <p class="empty-text">该标签下还没有添加任何网站</p>
            </div>
        <?php endif; ?>
    </section>
    
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
    const toolsData = <?php echo json_encode($tools_data); ?>;
    let currentUrl = '';
    
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
    
    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
    
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
</div>

<?php
wp_reset_postdata();
get_footer();
