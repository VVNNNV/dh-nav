<?php
/**
 * Template Name: 网站导航
 * Description: 网站导航页面，支持卡片和页面两种展示模式
 *
 * @package AI_Navigator_Hub
 */

if (!defined('ABSPATH')) exit;

// 获取当前展示模式
$display_mode = get_option('ai_navigator_display_mode', 'grid');

// 获取所有网站
$args = array(
    'post_type' => 'ai_tool',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'order' => 'ASC',
);

// 按分类分组获取
$tools_by_category = array();
$all_tools = get_posts($args);

foreach ($all_tools as $tool) {
    $categories = get_the_terms($tool->ID, 'ai_category');
    $tool_data = array(
        'id' => $tool->ID,
        'title' => get_the_title($tool->ID),
        'url' => get_post_meta($tool->ID, 'tool_url', true),
        'icon' => get_post_meta($tool->ID, 'tool_icon', true) ?: '🔧',
        'description' => get_the_excerpt($tool->ID) ?: wp_strip_all_tags(get_post_field('post_content', $tool->ID)),
        'hot' => get_post_meta($tool->ID, 'tool_hot', true),
        'tags' => array(),
    );
    
    $tags = get_the_terms($tool->ID, 'ai_tag');
    if ($tags && !is_wp_error($tags)) {
        foreach ($tags as $tag) {
            $tool_data['tags'][] = $tag->name;
        }
    }
    
    if ($categories && !is_wp_error($categories)) {
        foreach ($categories as $cat) {
            if (!isset($tools_by_category[$cat->term_id])) {
                $tools_by_category[$cat->term_id] = array(
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'tools' => array(),
                );
            }
            $tools_by_category[$cat->term_id]['tools'][] = $tool_data;
        }
    } else {
        // 未分类的网站
        if (!isset($tools_by_category['uncategorized'])) {
            $tools_by_category['uncategorized'] = array(
                'name' => '未分类',
                'slug' => 'uncategorized',
                'tools' => array(),
            );
        }
        $tools_by_category['uncategorized']['tools'][] = $tool_data;
    }
}

// 获取所有分类（带网站数量）
$all_categories = get_terms(array('taxonomy' => 'ai_category', 'hide_empty' => false));
$category_counts = array();
foreach ($all_categories as $cat) {
    $category_counts[$cat->term_id] = $cat->count;
}

get_header();
?>

<main id="primary" class="site-main tools-page">
    <?php if ($display_mode === 'page') : ?>
    <!-- 页面模式：按分类分组的详细列表 -->
    <div class="tools-page-mode">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="page-header">
            <h1 class="page-title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
            <div class="page-description"><?php the_excerpt(); ?></div>
            <?php endif; ?>
        </div>
        <?php endwhile; endif; ?>
        
        <div class="tools-category-list">
            <?php foreach ($tools_by_category as $cat_id => $category) : ?>
            <section class="tools-category-section" id="category-<?php echo esc_attr($category['slug']); ?>">
                <div class="category-header">
                    <h2 class="category-title"><?php echo esc_html($category['name']); ?></h2>
                    <span class="category-count">(<?php echo count($category['tools']); ?>个网站)</span>
                </div>
                
                <div class="category-tools-list">
                    <?php foreach ($category['tools'] as $tool) : ?>
                    <div class="tool-list-item">
                        <div class="tool-list-icon"><?php echo esc_html($tool['icon']); ?></div>
                        <div class="tool-list-content">
                            <h3 class="tool-list-title">
                                <a href="<?php echo esc_url($tool['url'] ?: '#'); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($tool['title']); ?>
                                    <?php if ($tool['hot']) : ?><span class="hot-badge">🔥</span><?php endif; ?>
                                </a>
                            </h3>
                            <p class="tool-list-desc"><?php echo esc_html(wp_trim_words($tool['description'], 50)); ?></p>
                            <?php if (!empty($tool['tags'])) : ?>
                            <div class="tool-list-tags">
                                <?php foreach (array_slice($tool['tags'], 0, 3) as $tag) : ?>
                                <span class="tag-item"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="tool-list-action">
                            <a href="<?php echo esc_url($tool['url'] ?: '#'); ?>" class="visit-btn" target="_blank" rel="noopener noreferrer">
                                访问 →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php else : ?>
    <!-- 卡片模式：网格布局 -->
    <div class="tools-grid-mode">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="page-header-inline">
            <h1 class="page-title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
            <div class="page-description"><?php the_excerpt(); ?></div>
            <?php endif; ?>
        </div>
        <?php endwhile; endif; ?>
        
        <!-- 分类导航 -->
        <nav class="tools-category-nav">
            <a href="#all" class="category-nav-item active" data-category="all">全部</a>
            <?php foreach ($all_categories as $cat) : ?>
            <a href="#category-<?php echo esc_attr($cat->slug); ?>" class="category-nav-item" data-category="<?php echo esc_attr($cat->slug); ?>">
                <?php echo esc_html($cat->name); ?>
                <span class="nav-count"><?php echo $category_counts[$cat->term_id] ?? 0; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        
        <!-- 网站卡片网格 -->
        <div class="tools-grid">
            <?php foreach ($tools_by_category as $cat_id => $category) : ?>
            <?php foreach ($category['tools'] as $tool) : ?>
            <div class="tool-card" data-category="<?php echo esc_attr($category['slug']); ?>">
                <?php if ($tool['hot']) : ?>
                <span class="card-hot">🔥 热门</span>
                <?php endif; ?>
                <div class="card-icon"><?php echo esc_html($tool['icon']); ?></div>
                <h3 class="card-title"><?php echo esc_html($tool['title']); ?></h3>
                <p class="card-desc"><?php echo esc_html(wp_trim_words($tool['description'], 30)); ?></p>
                <?php if (!empty($tool['tags'])) : ?>
                <div class="card-tags">
                    <?php foreach (array_slice($tool['tags'], 0, 2) as $tag) : ?>
                    <span class="card-tag"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <a href="<?php echo esc_url($tool['url'] ?: '#'); ?>" class="card-link" target="_blank" rel="noopener noreferrer">
                    访问网站
                </a>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <style>
    .tools-page { min-height: 100vh; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 40px 20px; }
    .tools-page .container { max-width: 1200px; margin: 0 auto; }
    
    /* 页面头部 */
    .page-header { text-align: center; margin-bottom: 50px; }
    .page-title { font-size: 36px; color: #fff; margin-bottom: 15px; }
    .page-description { font-size: 18px; color: #a0a0a0; max-width: 600px; margin: 0 auto; }
    .page-header-inline { text-align: center; margin-bottom: 30px; }
    
    /* 分类导航 */
    .tools-category-nav { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 40px; }
    .category-nav-item { background: rgba(255,255,255,0.1); color: #fff; padding: 10px 20px; border-radius: 25px; text-decoration: none; transition: all 0.3s; display: flex; align-items: center; gap: 8px; }
    .category-nav-item:hover, .category-nav-item.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .nav-count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 12px; }
    
    /* 卡片模式 */
    .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
    .tool-card { background: #fff; border-radius: 16px; padding: 24px; position: relative; transition: all 0.3s; }
    .tool-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
    .card-hot { position: absolute; top: 16px; right: 16px; background: linear-gradient(135deg, #ff6b6b, #ff8e53); color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .card-icon { font-size: 48px; margin-bottom: 16px; }
    .card-title { font-size: 20px; color: #1a1a2e; margin-bottom: 10px; }
    .card-desc { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 16px; }
    .card-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .card-tag { background: #f0f2f5; color: #666; padding: 4px 10px; border-radius: 12px; font-size: 12px; }
    .card-link { display: block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-align: center; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: opacity 0.3s; }
    .card-link:hover { opacity: 0.9; }
    
    /* 页面模式 */
    .tools-category-list { display: flex; flex-direction: column; gap: 50px; }
    .tools-category-section { background: #fff; border-radius: 16px; padding: 30px; }
    .category-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #eee; }
    .category-title { font-size: 24px; color: #1a1a2e; margin: 0; }
    .category-count { color: #999; font-size: 14px; }
    .category-tools-list { display: flex; flex-direction: column; gap: 20px; }
    .tool-list-item { display: flex; align-items: center; gap: 20px; padding: 20px; background: #f8f9fa; border-radius: 12px; transition: all 0.3s; }
    .tool-list-item:hover { background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .tool-list-icon { font-size: 40px; width: 60px; text-align: center; }
    .tool-list-content { flex: 1; }
    .tool-list-title { font-size: 18px; margin: 0 0 8px 0; }
    .tool-list-title a { color: #1a1a2e; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    .tool-list-title a:hover { color: #667eea; }
    .tool-list-desc { font-size: 14px; color: #666; margin: 0 0 10px 0; line-height: 1.5; }
    .tool-list-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .tool-list-action { flex-shrink: 0; }
    .visit-btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap; }
    .visit-btn:hover { opacity: 0.9; }
    
    @media (max-width: 768px) {
        .tools-grid { grid-template-columns: 1fr; }
        .tool-list-item { flex-direction: column; text-align: center; }
        .tool-list-action { width: 100%; }
        .visit-btn { display: block; text-align: center; }
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 分类筛选
        const navItems = document.querySelectorAll('.category-nav-item');
        const cards = document.querySelectorAll('.tool-card');
        
        navItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const category = this.dataset.category;
                
                // 更新导航状态
                navItems.forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                
                // 筛选卡片
                cards.forEach(function(card) {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
    </script>
</main>

<?php get_footer(); ?>
