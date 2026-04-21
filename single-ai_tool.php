<?php
/**
 * 单条网站详情页面模板
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    
    $tool_url = get_post_meta(get_the_ID(), 'tool_url', true);
    $tool_icon = get_post_meta(get_the_ID(), 'tool_icon', true);
    $tool_hot = get_post_meta(get_the_ID(), 'tool_hot', true);
    $categories = get_the_terms(get_the_ID(), 'ai_category');
    $tags = get_the_terms(get_the_ID(), 'ai_tag');
    
    // 获取当前分类下的其他网站
    $related_tools = array();
    if ($categories && !is_wp_error($categories)) {
        $first_category = $categories[0];
        $related_tools = new WP_Query(array(
            'post_type' => 'ai_tool',
            'posts_per_page' => 4,
            'post__not_in' => array(get_the_ID()),
            'tax_query' => array(
                array(
                    'taxonomy' => 'ai_category',
                    'field' => 'term_id',
                    'terms' => $first_category->term_id,
                ),
            ),
        ));
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

/* 页面容器 */
.single-page {
    min-height: 100vh;
    padding: 0 0 60px 0;
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

/* 页面头部 */
.page-header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 24px;
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

/* 网站详情卡片 */
.tool-detail-card {
    background: var(--card);
    border-radius: 20px;
    padding: 40px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-card);
}

.tool-header {
    display: flex;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border);
}

.tool-icon-large {
    width: 100px;
    height: 100px;
    border-radius: 24px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    line-height: 1;
    color: #fff;
    flex-shrink: 0;
}

.tool-info {
    flex: 1;
}

.tool-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.tool-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
    font-family: 'Space Grotesk', sans-serif;
}

.tool-hot-badge {
    background: linear-gradient(135deg, #f97316, #ef4444);
    color: #fff;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.tool-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.tool-category {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: rgba(59, 130, 246, 0.08);
    color: var(--primary);
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
}

.tool-category:hover {
    background: var(--primary);
    color: #fff;
}

.tool-tag {
    display: inline-flex;
    padding: 6px 12px;
    background: rgba(6, 182, 212, 0.08);
    color: var(--accent);
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.tool-tag:hover {
    background: var(--accent);
    color: #fff;
}

/* 网站描述 */
.tool-description {
    font-size: 16px;
    color: var(--text);
    line-height: 1.8;
    margin-bottom: 32px;
}

.tool-description p {
    margin: 0 0 16px 0;
}

.tool-description p:last-child {
    margin-bottom: 0;
}

/* 操作按钮 */
.tool-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.action-btn-primary {
    background: var(--gradient-primary);
    color: #fff;
}

.action-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.action-btn-secondary {
    background: var(--background);
    color: var(--text);
    border: 1px solid var(--border);
}

.action-btn-secondary:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* 相关网站 */
.related-section {
    max-width: 1200px;
    margin: 40px auto 0;
    padding: 0 20px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 24px 0;
    font-family: 'Space Grotesk', sans-serif;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.related-card {
    background: var(--card);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--border);
    text-decoration: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 16px;
}

.related-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-card-hover);
}

.related-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    flex-shrink: 0;
}

.related-info {
    flex: 1;
    min-width: 0;
}

.related-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.related-category {
    font-size: 12px;
    color: var(--text-secondary);
}

/* 响应式 */
@media (max-width: 768px) {
    .site-nav { display: none; }
    .tool-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .tool-icon-large {
        width: 80px;
        height: 80px;
        font-size: 40px;
    }
    
    .tool-title-row {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .tool-title {
        font-size: 24px;
    }
    
    .tool-meta {
        justify-content: center;
    }
    
    .tool-detail-card {
        padding: 24px;
    }
    
    .tool-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="single-page">
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
        <!-- 面包屑 -->
        <div class="breadcrumb">
            <a href="<?php echo home_url('/'); ?>">首页</a>
            <span>›</span>
            <a href="<?php echo home_url('/'); ?>">网站</a>
            <?php if ($categories && !is_wp_error($categories)) : ?>
                <span>›</span>
                <a href="<?php echo get_term_link($categories[0]); ?>"><?php echo esc_html($categories[0]->name); ?></a>
            <?php endif; ?>
            <span>›</span>
            <span><?php the_title(); ?></span>
        </div>

        <!-- 网站详情卡片 -->
        <div class="tool-detail-card">
            <div class="tool-header">
                <div class="tool-icon-large">
                    <?php echo $tool_icon ? esc_html($tool_icon) : mb_substr(get_the_title(), 0, 1); ?>
                </div>
                <div class="tool-info">
                    <div class="tool-title-row">
                        <h1 class="tool-title"><?php the_title(); ?></h1>
                        <?php if ($tool_hot) : ?>
                            <span class="tool-hot-badge">🔥 热门</span>
                        <?php endif; ?>
                    </div>
                    <div class="tool-meta">
                        <?php if ($categories && !is_wp_error($categories)) : ?>
                            <?php foreach ($categories as $cat) : ?>
                                <a href="<?php echo get_term_link($cat); ?>" class="tool-category">
                                    📂 <?php echo esc_html($cat->name); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($tags && !is_wp_error($tags)) : ?>
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="tool-tag">
                                    🏷️ <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 网站描述 -->
            <div class="tool-description">
                <?php the_content(); ?>
            </div>

            <!-- 操作按钮 -->
            <div class="tool-actions">
                <?php if ($tool_url) : ?>
                    <a href="<?php echo esc_url($tool_url); ?>" 
                       class="action-btn action-btn-primary" 
                       target="_blank" 
                       rel="noopener">
                        🚀 访问 <?php the_title(); ?>
                    </a>
                    <button class="action-btn action-btn-secondary" onclick="copyLink('<?php echo esc_url($tool_url); ?>')">
                        📋 复制链接
                    </button>
                <?php else : ?>
                    <span style="color: var(--text-secondary); font-size: 14px;">
                        暂无访问地址
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 相关网站 -->
    <?php if ($related_tools->have_posts()) : ?>
        <section class="related-section">
            <h2 class="section-title">📂 同分类的其他网站</h2>
            <div class="related-grid">
                <?php while ($related_tools->have_posts()) : $related_tools->the_post(); ?>
                    <?php
                    $rel_tool_icon = get_post_meta(get_the_ID(), 'tool_icon', true);
                    $rel_categories = get_the_terms(get_the_ID(), 'ai_category');
                    ?>
                    <a href="<?php the_permalink(); ?>" class="related-card">
                        <div class="related-icon">
                            <?php echo $rel_tool_icon ? esc_html($rel_tool_icon) : mb_substr(get_the_title(), 0, 1); ?>
                        </div>
                        <div class="related-info">
                            <h3 class="related-title"><?php the_title(); ?></h3>
                            <?php if ($rel_categories && !is_wp_error($rel_categories)) : ?>
                                <span class="related-category"><?php echo esc_html($rel_categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
function copyLink(url) {
    var textarea = document.createElement('textarea');
    textarea.value = url;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        alert('链接已复制到剪贴板');
    } catch(e) {
        prompt('请手动复制链接:', url);
    }
    document.body.removeChild(textarea);
}
</script>

<?php
endwhile;
wp_reset_postdata();
get_footer();
