<?php
/**
 * 大海导航 - Custom Post Types and Taxonomies
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 注册自定义文章类型
 */
function ai_navigator_register_post_types() {
    register_post_type( 'ai_tool', array(
        'labels' => array(
            'name' => __('网站', 'dh-nav'),
            'singular_name' => __('网站', 'dh-nav'),
            'menu_name' => __('网站', 'dh-nav'),
            'add_new' => __('添加网站', 'dh-nav'),
            'add_new_item' => __('添加新网站', 'dh-nav'),
            'edit_item' => __('编辑网站', 'dh-nav'),
            'new_item' => __('新网站', 'dh-nav'),
            'view_item' => __('查看网站', 'dh-nav'),
            'all_items' => __('所有网站', 'dh-nav'),
            'search_items' => __('搜索网站', 'dh-nav'),
            'not_found' => __('未找到网站', 'dh-nav'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'rest_base' => 'ai_tool',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'site'),
        'show_ui' => true,
        'show_in_menu' => false,
        'menu_icon' => 'dashicons-admin-tools',
    ));

    register_taxonomy('ai_category', 'ai_tool', array(
        'labels' => array('name' => __('网站分类', 'dh-nav'), 'singular_name' => __('网站分类', 'dh-nav')),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'query_var' => true,
        'show_in_rest' => true,
        'rest_base' => 'ai_category',
        'rewrite' => array('slug' => 'site-category'),
        'show_admin_filter' => true,
    ));

    register_taxonomy('ai_tag', 'ai_tool', array(
        'labels' => array('name' => __('网站标签', 'dh-nav'), 'singular_name' => __('网站标签', 'dh-nav')),
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'query_var' => true,
        'show_in_rest' => true,
        'rest_base' => 'ai_tag',
        'rewrite' => array('slug' => 'site-tag'),
    ));
}
add_action('init', 'ai_navigator_register_post_types');

/**
 * 自定义 ai_tool 文章类型的固定链接格式为 /site/{post_id}.html
 */
function ai_navigator_custom_post_type_link($post_link, $post) {
    if ($post->post_type === 'ai_tool') {
        return home_url('/site/' . $post->ID . '.html');
    }
    return $post_link;
}
add_filter('post_type_link', 'ai_navigator_custom_post_type_link', 10, 2);

/**
 * 添加 ai_tool 的自定义重写规则，支持 /site/{id}.html 格式
 */
function ai_navigator_add_custom_rewrite_rules() {
    add_rewrite_rule('^site/([0-9]+)\.html$', 'index.php?post_type=ai_tool&p=$matches[1]', 'top');
}
add_action('init', 'ai_navigator_add_custom_rewrite_rules');

/**
 * 注册元数据
 */
function ai_navigator_register_meta() {
    register_post_meta('ai_tool', 'tool_url', array('type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url'));
    register_post_meta('ai_tool', 'tool_icon', array('type' => 'string', 'single' => true, 'show_in_rest' => true));
    register_post_meta('ai_tool', 'tool_hot', array('type' => 'boolean', 'single' => true, 'show_in_rest' => true));
    register_post_meta('ai_tool', 'tool_order', array('type' => 'integer', 'single' => true, 'show_in_rest' => true));
}
add_action('init', 'ai_navigator_register_meta');

/**
 * REST API 响应添加字段
 */
function ai_navigator_rest_prepare_post($response, $post, $request) {
    if ($post->post_type === 'ai_tool') {
        $response->data['tool_url'] = get_post_meta($post->ID, 'tool_url', true);
        $response->data['tool_icon'] = get_post_meta($post->ID, 'tool_icon', true);
        $response->data['tool_hot'] = (bool)get_post_meta($post->ID, 'tool_hot', true);
        $response->data['tool_order'] = (int)get_post_meta($post->ID, 'tool_order', true);
        $categories = get_the_terms($post->ID, 'ai_category');
        $response->data['categories'] = $categories ? array_map(fn($t) => array('id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug), $categories) : array();
        $tags = get_the_terms($post->ID, 'ai_tag');
        $response->data['tags'] = $tags ? array_map(fn($t) => array('id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug), $tags) : array();
    }
    return $response;
}
add_filter('rest_prepare_ai_tool', 'ai_navigator_rest_prepare_post', 10, 3);

/**
 * 添加 Metabox
 */
function ai_navigator_add_meta_boxes() {
    add_meta_box('ai_tool_url_metabox', '网站信息', 'ai_navigator_tool_url_metabox_html', 'ai_tool', 'normal', 'high');
}
add_action('add_meta_boxes', 'ai_navigator_add_meta_boxes');

function ai_navigator_tool_url_metabox_html($post) {
    include get_template_directory() . '/includes/metabox-ui.php';
}

/**
 * 保存 Metabox 数据
 */
function ai_navigator_save_meta($post_id) {
    if (!isset($_POST['ai_navigator_meta_nonce']) || !wp_verify_nonce($_POST['ai_navigator_meta_nonce'], 'ai_navigator_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['tool_url'])) update_post_meta($post_id, 'tool_url', esc_url($_POST['tool_url']));
    if (isset($_POST['tool_icon'])) update_post_meta($post_id, 'tool_icon', sanitize_text_field($_POST['tool_icon']));
    update_post_meta($post_id, 'tool_hot', isset($_POST['tool_hot']) ? '1' : '');
    if (isset($_POST['tool_order'])) update_post_meta($post_id, 'tool_order', absint($_POST['tool_order']));
    
    $current_cats = wp_get_post_terms($post_id, 'ai_category', array('fields' => 'ids'));
    if (empty($current_cats) && isset($_POST['ai_default_category']) && $_POST['ai_default_category']) {
        wp_set_object_terms($post_id, absint($_POST['ai_default_category']), 'ai_category');
    }
    
    if (isset($_POST['ai_tags_input'])) {
        $tags = array_filter(array_map('trim', explode(',', $_POST['ai_tags_input'])));
        $tag_ids = array();
        foreach ($tags as $tag_name) {
            $tag = wp_insert_term($tag_name, 'ai_tag');
            if (!is_wp_error($tag)) {
                $tag_ids[] = $tag['term_id'];
            } else {
                $existing = get_term_by('name', $tag_name, 'ai_tag');
                if ($existing) $tag_ids[] = $existing->term_id;
            }
        }
        if (!empty($tag_ids)) wp_set_object_terms($post_id, $tag_ids, 'ai_tag');
    }
}
add_action('save_post_ai_tool', 'ai_navigator_save_meta');

/**
 * 注册自动获取 URL 信息的 API
 */
function ai_navigator_register_fetch_url_api() {
    register_rest_route('ai-navigator/v1', '/fetch-url', array(
        'methods' => 'GET',
        'callback' => 'ai_navigator_fetch_url_info',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'ai_navigator_register_fetch_url_api');

function ai_navigator_fetch_url_info($request) {
    $url = $request->get_param('url');
    if (empty($url)) return new WP_Error('empty_url', 'URL不能为空', array('status' => 400));
    if (!preg_match('/^https?:\/\//', $url)) $url = 'https://' . $url;
    
    $response = wp_remote_get($url, array('timeout' => 10, 'user-agent' => 'Mozilla/5.0'));
    if (is_wp_error($response)) return new WP_Error('fetch_error', '无法获取网页', array('status' => 500));
    
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) return new WP_Error('empty_content', '网页内容为空', array('status' => 500));
    
    $title = '';
    if (preg_match('/<title[^>]*>([^<]*)<\/title>/i', $html, $matches)) {
        $title = trim($matches[1]);
        $title = preg_replace('/\s*[-|_]\s*[^-|_]+$/i', '', $title);
    }
    
    $description = '';
    if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
        $description = trim($matches[1]);
    } elseif (preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']description["\'][^>]*>/i', $html, $matches)) {
        $description = trim($matches[1]);
    }
    
    $icon = ai_navigator_match_icon($title);
    $tags = ai_navigator_suggest_tags($title, $description);
    
    return array('title' => $title, 'description' => wp_strip_all_tags($description), 'icon' => $icon, 'tags' => $tags);
}

function ai_navigator_match_icon($name) {
    $name_lower = strtolower($name);
    $icon_map = array(
        'chatgpt' => '🤖', 'gpt' => '🤖', 'openai' => '🤖', 'claude' => '🧠', 'anthropic' => '🧠',
        'deepseek' => '🔍', 'midjourney' => '🎨', 'image' => '🎨', 'sora' => '🎬', 'video' => '🎬',
        'cursor' => '💻', 'code' => '💻', 'github' => '💻', 'perplexity' => '🔎', 'search' => '🔎',
        'bard' => '✨', 'gemini' => '✨', 'google' => '🔵', 'copilot' => '💡', 'microsoft' => '🔷',
        'notion' => '📝', 'canva' => '🖌️', 'jasper' => '✍️', 'write' => '✍️', 'eleven' => '🎵',
        'audio' => '🎵', 'voice' => '🎤', 'music' => '🎶', 'translate' => '🌐', 'pdf' => '📄',
        'spreadsheet' => '📊', 'presentation' => '📽️', 'ppt' => '📽️',
    );
    foreach ($icon_map as $keyword => $icon) {
        if (strpos($name_lower, $keyword) !== false) return $icon;
    }
    return '🔧';
}

function ai_navigator_suggest_tags($title, $description) {
    $text = strtolower($title . ' ' . $description);
    $tags = array();
    $keywords = array('对话' => 'AI对话', 'chat' => 'AI对话', '绘画' => 'AI绘画', 'image' => 'AI绘画',
        '视频' => 'AI视频', 'video' => 'AI视频', '编程' => 'AI编程', 'code' => 'AI编程',
        '写作' => 'AI写作', 'write' => 'AI写作', '搜索' => 'AI搜索', 'search' => 'AI搜索',
        '翻译' => 'AI翻译', 'translate' => 'AI翻译', '语音' => 'AI语音', 'audio' => 'AI音频',
        'ppt' => 'PPT', 'presentation' => 'PPT', '文档' => '文档处理', 'pdf' => 'PDF处理');
    foreach ($keywords as $keyword => $tag) {
        if (strpos($text, $keyword) !== false && !in_array($tag, $tags)) $tags[] = $tag;
    }
    return array_slice($tags, 0, 5);
}

/**
 * 创建示例数据
 */
function ai_navigator_create_sample_data() {
    $existing = get_posts(array('post_type' => 'ai_tool', 'posts_per_page' => 1, 'post_status' => 'publish'));
    if (!empty($existing)) return;
    
    // 创建分类
    $categories = array(
        'llm' => '大语言模型',
        'proprietary' => '顶尖闭源模型',
        'open_source' => '全能开源模型',
        'image' => 'AI绘画',
        'video' => 'AI视频',
        'audio' => 'AI音频',
        '3d' => 'AI 3D/建模',
        'code' => 'AI编程',
        'writing' => 'AI写作',
        'search' => 'AI搜索',
        'tool' => 'AI网站',
        'community' => '模型社区',
        'productivity' => '办公效率',
        'marketing' => '电商营销',
        'local' => '本地部署',
    );
    
    $cat_ids = array();
    foreach ($categories as $slug => $name) {
        $term = get_term_by('slug', $slug, 'ai_category');
        if (!$term) {
            $result = wp_insert_term($name, 'ai_category', array('slug' => $slug));
            if (!is_wp_error($result)) {
                $cat_ids[$slug] = $result['term_id'];
            }
        } else {
            $cat_ids[$slug] = $term->term_id;
        }
    }
    
    // 演示数据
    $sample_tools = array(
        // 大语言模型 (LLM)
        array('title' => 'ChatGPT', 'description' => 'OpenAI推出的强大对话式AI，支持多轮对话、代码生成、文案创作等多种任务。', 'url' => 'https://chat.openai.com', 'icon' => '🤖', 'category' => 'llm', 'tags' => array('AI对话', 'GPT-4', 'OpenAI'), 'hot' => true),
        array('title' => 'Claude', 'description' => 'Anthropic开发的安全可靠AI助手，擅长长文本分析、编程辅助和深度推理。', 'url' => 'https://claude.ai', 'icon' => '🧠', 'category' => 'llm', 'tags' => array('AI对话', 'Anthropic', '安全'), 'hot' => true),
        array('title' => 'Gemini', 'description' => 'Google推出的多模态AI模型，支持文本、图像、音频等多种输入方式。', 'url' => 'https://gemini.google.com', 'icon' => '✨', 'category' => 'llm', 'tags' => array('Google', '多模态'), 'hot' => true),
        array('title' => '豆包', 'description' => '字节跳动推出的智能对话助手，支持问答、创作、翻译等日常任务。', 'url' => 'https://www.doubao.com', 'icon' => '🫘', 'category' => 'llm', 'tags' => array('字节', '中文'), 'hot' => true),
        array('title' => 'Kimi', 'description' => '月之暗面推出的超长上下文AI助手，支持20万字长文档处理。', 'url' => 'https://kimi.moonshot.cn', 'icon' => '🌙', 'category' => 'llm', 'tags' => array('长文本', '月之暗面')),
        array('title' => '文心一言', 'description' => '百度推出的知识增强大语言模型，深度整合百度搜索与知识图谱。', 'url' => 'https://yiyan.baidu.com', 'icon' => '📝', 'category' => 'llm', 'tags' => array('百度', '中文')),
        array('title' => '通义千问', 'description' => '阿里云推出的大语言模型，具备多轮对话、文案创作和逻辑推理能力。', 'url' => 'https://tongyi.aliyun.com', 'icon' => '🔮', 'category' => 'llm', 'tags' => array('阿里', '中文')),
        array('title' => 'DeepSeek', 'description' => '深度求索推出的开源大模型，在代码和数学推理方面表现出色。', 'url' => 'https://chat.deepseek.com', 'icon' => '🔍', 'category' => 'llm', 'tags' => array('开源', '编程', '推理'), 'hot' => true),
        array('title' => 'Meta Llama', 'description' => 'Meta推出的开源大语言模型系列，被广泛用于研究和商业应用。', 'url' => 'https://llama.meta.com', 'icon' => '🦙', 'category' => 'llm', 'tags' => array('Meta', '开源')),
        array('title' => 'Groq', 'description' => '极速推理的AI平台，提供超低延迟的LLM推理服务。', 'url' => 'https://console.groq.com', 'icon' => '⚡', 'category' => 'llm', 'tags' => array('推理', '极速')),
        array('title' => 'Mistral', 'description' => '法国AI创业公司推出的开源大模型，高效且易于部署。', 'url' => 'https://mistral.ai', 'icon' => '🌬️', 'category' => 'llm', 'tags' => array('开源', '欧洲')),
        array('title' => '智谱清言', 'description' => '智谱AI推出的对话大模型，支持中英文对话和知识问答。', 'url' => 'https://www.zhipuai.cn', 'icon' => '💎', 'category' => 'llm', 'tags' => array('中文', '清华')),
        
        // 顶尖闭源模型 (Proprietary)
        array('title' => 'GPT-5.4 Thinking', 'description' => 'OpenAI 2026年旗舰模型，具备深度思考模式，在复杂逻辑推理和自我修正方面达到人类专家水平。', 'url' => 'https://chat.openai.com', 'icon' => '♾️', 'category' => 'proprietary', 'tags' => array('OpenAI', '深度推理', '多模态'), 'hot' => true),
        array('title' => 'Claude Opus 4.7', 'description' => 'Anthropic 2026年4月发布，目前全球代码生成与数据看板构建排名第一的工具。', 'url' => 'https://claude.ai', 'icon' => '🎭', 'category' => 'proprietary', 'tags' => array('Anthropic', '超强代码', '低幻觉'), 'hot' => true),
        array('title' => 'Claude Mythos Preview', 'description' => 'Anthropic最强性能预览版，专为处理超大规模科研任务和跨领域复杂问题设计。', 'url' => 'https://claude.ai', 'icon' => '🔱', 'category' => 'proprietary', 'tags' => array('超大模型', '科研级')),
        array('title' => 'Gemini 3.5 Ultra', 'description' => 'Google多模态原生模型，原生支持处理长达数小时的视频和百万行代码库，生态整合能力极强。', 'url' => 'https://gemini.google.com', 'icon' => '♊', 'category' => 'proprietary', 'tags' => array('Google', '原生多模态', '超长上下文')),
        array('title' => 'Grok-3', 'description' => 'xAI推出，深度集成X实时社交数据，语风犀利且在数学、物理竞赛题目中表现卓越。', 'url' => 'https://x.ai', 'icon' => '🐦', 'category' => 'proprietary', 'tags' => array('xAI', '实时数据', '硬核科学')),

        // 全能开源模型 (Open Source)
        array('title' => 'Llama 4 Maverick', 'description' => 'Meta 2025年发布的MoE架构旗舰开源模型，400B+参数，性能全面对标GPT-4.5。', 'url' => 'https://llama.com', 'icon' => '🦙', 'category' => 'open_source', 'tags' => array('Meta', '开源', 'MoE架构'), 'hot' => true),
        array('title' => 'Muse Spark', 'description' => 'Meta Superintelligence Labs 2026年4月发布的Llama系列继任者，专为通用人工智能雏形设计的开源架构。', 'url' => 'https://github.com/meta-llama', 'icon' => '⚡', 'category' => 'open_source', 'tags' => array('AGI', '次世代开源')),
        array('title' => 'DeepSeek V4', 'description' => '2026年2月发布，自研Engram记忆架构，支持100万token上下文，代码能力超越同代GPT系列。', 'url' => 'https://chat.deepseek.com', 'icon' => '🔍', 'category' => 'open_source', 'tags' => array('国产开源', 'Engram记忆', '代码之王'), 'hot' => true),
        array('title' => 'Qwen 3.6-Max', 'description' => '阿里2026年4月最新发布的开源MoE模型，中文理解与智能Agent编排能力极强。', 'url' => 'https://qwen.ai', 'icon' => '🔮', 'category' => 'open_source', 'tags' => array('阿里', 'Agentic', '中文最强')),
        array('title' => 'Mistral Large 3', 'description' => '欧洲最强开源模型，以极高的推理效率和优秀的多语言支持著称。', 'url' => 'https://mistral.ai', 'icon' => '🌬️', 'category' => 'open_source', 'tags' => array('Mistral', '高效率', '多语言')),
        array('title' => '01-Yi-Large-V2', 'description' => '零一万物出品，针对企业级场景优化的超长文本模型，在阅读理解和财务审计场景表现突出。', 'url' => 'https://www.lingyiwanwu.com', 'icon' => '壹', 'category' => 'open_source', 'tags' => array('李开复', '企业级', '长文本')),
        array('title' => 'CodeQwen 2.0', 'description' => '专注于编程的专项大模型，支持92种编程语言，在复杂的全栈开发中准确率极高。', 'url' => 'https://github.com/QwenLM/CodeQwen', 'icon' => '💻', 'category' => 'open_source', 'tags' => array('编程专用', '开源')),
        array('title' => 'InternLM-3', 'description' => '书生·浦语最新一代，专注于数学推理和科学研究，内置海量理科专业知识库。', 'url' => 'https://internlm.shlab.sz.cn', 'icon' => '🏫', 'category' => 'open_source', 'tags' => array('实验室', '科研推理')),

        // AI绘画
        array('title' => 'Midjourney', 'description' => '领先的AI图像生成工具，以精美的艺术风格和高质量图像著称。', 'url' => 'https://www.midjourney.com', 'icon' => '🎨', 'category' => 'image', 'tags' => array('绘画', '艺术'), 'hot' => true),
        array('title' => 'DALL·E', 'description' => 'OpenAI推出的文本到图像生成模型，可根据文字描述创建逼真图像。', 'url' => 'https://openai.com/dall-e-3', 'icon' => '🖼️', 'category' => 'image', 'tags' => array('OpenAI', '文生图')),
        array('title' => 'Stable Diffusion', 'description' => '开源的文本到图像生成模型，支持本地部署和高度自定义。', 'url' => 'https://stability.ai', 'icon' => '🎭', 'category' => 'image', 'tags' => array('开源', '文生图')),
        array('title' => '即梦AI', 'description' => '字节跳动推出的AI图像创作平台，支持文生图、图生图等多种创作方式。', 'url' => 'https://jimeng.jianying.com', 'icon' => '💭', 'category' => 'image', 'tags' => array('字节', '创作')),
        array('title' => 'Adobe Firefly', 'description' => 'Adobe推出的AI图像生成工具，深度集成Photoshop工作流。', 'url' => 'https://firefly.adobe.com', 'icon' => '🔥', 'category' => 'image', 'tags' => array('Adobe', '设计')),
        array('title' => 'Leonardo.ai', 'description' => '专业的AI游戏资产生成平台，提供高质量的图像和动画生成。', 'url' => 'https://leonardo.ai', 'icon' => '🎮', 'category' => 'image', 'tags' => array('游戏', '资产')),
        array('title' => 'Ideogram', 'description' => '专注于文字生成的AI图像工具，可以生成带有精美文字的图片。', 'url' => 'https://ideogram.ai', 'icon' => '✍️', 'category' => 'image', 'tags' => array('文字', '图像')),
        array('title' => 'Flux', 'description' => 'Black Forest Labs推出的开源图像生成模型，图像质量出众。', 'url' => 'https://flux.ai', 'icon' => '🌊', 'category' => 'image', 'tags' => array('开源', '高质量')),
        array('title' => 'Midjourney Niji', 'description' => '专门面向动漫风格创作的AI图像生成模型。', 'url' => 'https://docs.midjourney.com/docs/niji', 'icon' => '🌸', 'category' => 'image', 'tags' => array('动漫', '绘画')),
        array('title' => 'Canva图像生成', 'description' => 'Canva集成的AI图像生成功能，一键创建设计素材。', 'url' => 'https://www.canva.com', 'icon' => '🎯', 'category' => 'image', 'tags' => array('设计', '模板')),
        array('title' => '通义万相', 'description' => '阿里云推出的AI绘画工具，支持多种艺术风格。', 'url' => 'https://tongyi.aliyun.com/wanxiang', 'icon' => '🎪', 'category' => 'image', 'tags' => array('阿里', '绘画')),
        
        // AI视频
        array('title' => 'Sora', 'description' => 'OpenAI推出的文本到视频生成模型，可生成高质量的短视频内容。', 'url' => 'https://sora.com', 'icon' => '🎬', 'category' => 'video', 'tags' => array('OpenAI', '文生视频'), 'hot' => true),
        array('title' => 'Runway', 'description' => '领先的AI视频编辑和生成平台，提供多种AI驱动的创意工具。', 'url' => 'https://runwayml.com', 'icon' => '🎥', 'category' => 'video', 'tags' => array('视频编辑', '创意')),
        array('title' => 'Pika', 'description' => 'AI视频生成平台，支持文本/图像到视频的快速转换。', 'url' => 'https://pika.art', 'icon' => '⚡', 'category' => 'video', 'tags' => array('文生视频', '图生视频')),
        array('title' => '可灵AI', 'description' => '快手推出的AI视频生成工具，支持高质量文生视频和图生视频。', 'url' => 'https://klingai.kuaishou.com', 'icon' => '🎞️', 'category' => 'video', 'tags' => array('快手', '文生视频')),
        array('title' => 'Luma Dream Machine', 'description' => '高质量的AI视频生成工具，支持文本和图像转视频。', 'url' => 'https://dreammachine.lumalabs.ai', 'icon' => '💫', 'category' => 'video', 'tags' => array('视频生成', '高质量')),
        array('title' => 'HeyGen', 'description' => 'AI数字人视频生成平台，快速创建逼真的AI主播。', 'url' => 'https://heygen.com', 'icon' => '👤', 'category' => 'video', 'tags' => array('数字人', '主播')),
        array('title' => 'Synthesia', 'description' => '专业AI视频制作平台，支持多语言AI主播。', 'url' => 'https://synthesia.io', 'icon' => '🌐', 'category' => 'video', 'tags' => array('多语言', '视频制作')),
        array('title' => 'CapCut', 'description' => '字节跳动推出的AI视频编辑工具，功能强大且易用。', 'url' => 'https://www.capcut.com', 'icon' => '✂️', 'category' => 'video', 'tags' => array('字节', '视频编辑')),
        array('title' => '剪映', 'description' => '字节跳动推出的智能视频剪辑工具，集成多种AI功能。', 'url' => 'https://www.jianying.com', 'icon' => '🎚️', 'category' => 'video', 'tags' => array('字节', '剪辑')),
        array('title' => '腾讯智影', 'description' => '腾讯推出的AI视频创作平台，支持数字人和视频编辑。', 'url' => 'https://zenvideo.qq.com', 'icon' => '🎬', 'category' => 'video', 'tags' => array('腾讯', '视频')),
        
        // AI音频
        array('title' => 'Suno', 'description' => 'AI音乐生成平台，输入文字描述即可自动创作完整歌曲。', 'url' => 'https://suno.com', 'icon' => '🎵', 'category' => 'audio', 'tags' => array('音乐', '创作'), 'hot' => true),
        array('title' => 'Udio', 'description' => '被誉为"音乐界的Sora"，能生成极具情感共鸣的高质量音乐。', 'url' => 'https://www.udio.com', 'icon' => '🎶', 'category' => 'audio', 'tags' => array('音乐生成', '高保真'), 'hot' => true),
        array('title' => 'ElevenLabs', 'description' => '先进的AI语音合成平台，支持超逼真的语音克隆和多语言配音。', 'url' => 'https://elevenlabs.io', 'icon' => '🔊', 'category' => 'audio', 'tags' => array('语音', '配音')),
        array('title' => 'Mureka', 'description' => '国内的AI音乐生成平台，支持中英文歌曲创作。', 'url' => 'https://mureka.com', 'icon' => '🎶', 'category' => 'audio', 'tags' => array('音乐', '中文')),
        array('title' => 'Lovo AI', 'description' => '顶级的AI配音平台，拥有500多种仿真人声音。', 'url' => 'https://lovo.ai', 'icon' => '🎙️', 'category' => 'audio', 'tags' => array('配音', '多语言')),
        array('title' => '讯飞智作', 'description' => '科大讯飞推出的AI语音合成和配音平台。', 'url' => 'https://zuo.sheng.xunfei.com', 'icon' => '🗣️', 'category' => 'audio', 'tags' => array('讯飞', '语音')),
        array('title' => 'Azure语音服务', 'description' => '微软Azure提供的AI语音合成服务，支持多语言和自定义声音。', 'url' => 'https://azure.microsoft.com/services/cognitive-services/speech-services', 'icon' => '🔵', 'category' => 'audio', 'tags' => array('微软', '语音')),
        array('title' => 'NaturalReader', 'description' => 'AI文本转语音工具，支持文档和网页朗读。', 'url' => 'https://www.naturalreaders.com', 'icon' => '📖', 'category' => 'audio', 'tags' => array('朗读', '文档')),
        array('title' => 'Speechify', 'description' => 'AI驱动的文本朗读应用，支持多语言和倍速播放。', 'url' => 'https://speechify.com', 'icon' => '📚', 'category' => 'audio', 'tags' => array('朗读', '学习')),

        // AI 3D/建模
        array('title' => 'Luma Genie', 'description' => 'Luma AI推出的文字生成高品质3D模型工具，支持多种导出格式。', 'url' => 'https://lumalabs.ai/genie', 'icon' => '🧊', 'category' => '3d', 'tags' => array('3D建模', '文生3D'), 'hot' => true),
        array('title' => 'Meshy', 'description' => '新一代AI 3D生成器，支持从文字或图片快速生成3D网格。', 'url' => 'https://www.meshy.ai', 'icon' => '🕸️', 'category' => '3d', 'tags' => array('游戏开发', '建模')),
        array('title' => 'Tripo AI', 'description' => '高精度的AI 3D建模平台，支持秒级生成可用的3D素材。', 'url' => 'https://www.tripoai.com', 'icon' => '📐', 'category' => '3d', 'tags' => array('高精度', '工业建模')),
        array('title' => 'CSM AI', 'description' => '从2D图片或视频快速生成3D模型，支持动画绑定和导出。', 'url' => 'https://csm.ai', 'icon' => '🎬', 'category' => '3d', 'tags' => array('2D转3D', '动画')),
        array('title' => 'Kaedim', 'description' => 'AI驱动的2D转3D工具，专为游戏和影视行业设计。', 'url' => 'https://www.kaedim3d.com', 'icon' => '🎲', 'category' => '3d', 'tags' => array('游戏', '2D转3D')),
        array('title' => 'Sloyd', 'description' => 'AI 3D资产生成平台，专为游戏开发者提供即用型3D模型。', 'url' => 'https://sloyd.ai', 'icon' => '🎮', 'category' => '3d', 'tags' => array('游戏资产', '自动生成')),

        // AI编程
        array('title' => 'GitHub Copilot', 'description' => 'GitHub与OpenAI合作的AI编程助手，支持代码自动补全和生成。', 'url' => 'https://github.com/features/copilot', 'icon' => '👨‍💻', 'category' => 'code', 'tags' => array('编程', 'GitHub'), 'hot' => true),
        array('title' => 'Cursor', 'description' => 'AI驱动的代码编辑器，内置智能代码生成和编辑功能。', 'url' => 'https://cursor.sh', 'icon' => '⌨️', 'category' => 'code', 'tags' => array('编辑器', '编程'), 'hot' => true),
        array('title' => 'Lovable', 'description' => 'AI全栈开发平台，通过自然语言描述即可快速构建完整的Web应用。', 'url' => 'https://lovable.dev', 'icon' => '💜', 'category' => 'code', 'tags' => array('全栈', '低代码'), 'hot' => true),
        array('title' => 'v0.dev', 'description' => 'Vercel推出的AI UI生成器，通过文字描述快速生成React组件。', 'url' => 'https://v0.dev', 'icon' => '🔧', 'category' => 'code', 'tags' => array('前端', 'UI')),
        array('title' => 'Replit', 'description' => '在线AI编程平台，支持多种语言的在线编写、运行和部署。', 'url' => 'https://replit.com', 'icon' => '💻', 'category' => 'code', 'tags' => array('在线IDE', '部署')),
        array('title' => 'Codeium', 'description' => '免费的AI代码补全工具，支持多种主流编程语言。', 'url' => 'https://codeium.com', 'icon' => '⚡', 'category' => 'code', 'tags' => array('免费', '补全')),
        array('title' => 'Tabnine', 'description' => 'AI代码助手，支持代码补全和重构建议。', 'url' => 'https://tabnine.com', 'icon' => '🔮', 'category' => 'code', 'tags' => array('补全', '重构')),
        array('title' => 'Amazon CodeWhisperer', 'description' => '亚马逊推出的AI编程助手，支持代码生成和安全扫描。', 'url' => 'https://aws.amazon.com/codewhisperer', 'icon' => '📦', 'category' => 'code', 'tags' => array('AWS', '安全')),
        array('title' => 'Cody', 'description' => 'Sourcegraph推出的AI代码助手，支持代码搜索和理解。', 'url' => 'https://sourcegraph.com/cody', 'icon' => '🦄', 'category' => 'code', 'tags' => array('搜索', '理解')),
        array('title' => 'JetBrains AI', 'description' => 'JetBrains IDE集成的AI助手，提升编程效率。', 'url' => 'https://jb.gg/ai', 'icon' => '🚀', 'category' => 'code', 'tags' => array('IDE', 'JetBrains')),
        array('title' => 'Devin', 'description' => 'Cognition推出的AI软件工程师，能够独立完成复杂编程任务。', 'url' => 'https://cognition.ai', 'icon' => '🤖', 'category' => 'code', 'tags' => array('AI工程师', '自动化')),
        array('title' => 'Bolt.new', 'description' => 'StackBlitz推出的AI全栈开发工具，浏览器中即时运行和部署。', 'url' => 'https://bolt.new', 'icon' => '⚡', 'category' => 'code', 'tags' => array('全栈', '浏览器')),
        
        // AI写作
        array('title' => 'Notion AI', 'description' => 'Notion内置的AI写作助手，帮助快速生成、改写和总结文档内容。', 'url' => 'https://www.notion.so/product/ai', 'icon' => '📄', 'category' => 'writing', 'tags' => array('文档', '协作')),
        array('title' => 'Jasper', 'description' => '专业的AI营销文案工具，支持广告、博客、邮件等多种内容创作。', 'url' => 'https://www.jasper.ai', 'icon' => '✍️', 'category' => 'writing', 'tags' => array('营销', '文案')),
        array('title' => 'Copy.ai', 'description' => 'AI文案生成工具，快速创建营销文案、社交媒体内容和商业邮件。', 'url' => 'https://www.copy.ai', 'icon' => '📋', 'category' => 'writing', 'tags' => array('文案', '营销')),
        array('title' => 'Writesonic', 'description' => 'AI写作助手，支持文章创作、广告文案和SEO优化。', 'url' => 'https://writesonic.com', 'icon' => '✏️', 'category' => 'writing', 'tags' => array('SEO', '文章')),
        array('title' => 'Claude写作', 'description' => '使用Claude进行高质量写作辅助和内容创作。', 'url' => 'https://claude.ai', 'icon' => '🧠', 'category' => 'writing', 'tags' => array('AI对话', '写作')),
        array('title' => '秘塔写作猫', 'description' => '国内的AI写作辅助工具，支持文章纠错和润色。', 'url' => 'https://xiezuocat.com', 'icon' => '🐱', 'category' => 'writing', 'tags' => array('中文', '纠错')),
        array('title' => '讯飞写作', 'description' => '科大讯飞推出的AI写作平台，支持多种文体创作。', 'url' => 'https://writing.xunfei.cn', 'icon' => '✍️', 'category' => 'writing', 'tags' => array('讯飞', '创作')),
        array('title' => '笔灵AI', 'description' => '多场景AI写作助手，支持论文、报告、方案等多种文档。', 'url' => 'https://ibiling.cn', 'icon' => '📝', 'category' => 'writing', 'tags' => array('多场景', '文档')),
        array('title' => 'Grammarly', 'description' => '全球最流行的AI英文写作助手，实时语法检查和风格优化。', 'url' => 'https://www.grammarly.com', 'icon' => '🔤', 'category' => 'writing', 'tags' => array('英文', '语法')),
        array('title' => 'Jenni AI', 'description' => 'AI学术写作助手，帮助撰写论文、文献综述和研究报告。', 'url' => 'https://jenni.ai', 'icon' => '🎓', 'category' => 'writing', 'tags' => array('学术', '论文')),
        
        // AI搜索
        array('title' => 'Perplexity', 'description' => 'AI驱动的智能搜索引擎，提供带引用来源的精准回答。', 'url' => 'https://www.perplexity.ai', 'icon' => '🔎', 'category' => 'search', 'tags' => array('搜索', '问答'), 'hot' => true),
        array('title' => 'Phind', 'description' => '面向开发者的AI搜索引擎，专注于技术问题的精准解答。', 'url' => 'https://www.phind.com', 'icon' => '🔬', 'category' => 'search', 'tags' => array('技术', '开发者')),
        array('title' => '天工AI', 'description' => '昆仑万维推出的AI搜索引擎，支持智能问答和信息检索。', 'url' => 'https://www.tiangong.cn', 'icon' => '🌐', 'category' => 'search', 'tags' => array('搜索', '中文')),
        array('title' => 'Kimi探索版', 'description' => 'Kimi推出的深度搜索版本，支持多轮搜索和推理。', 'url' => 'https://kimi.moonshot.cn', 'icon' => '🔭', 'category' => 'search', 'tags' => array('深度搜索', '推理')),
        array('title' => '秘塔AI搜索', 'description' => '无广告的AI搜索引擎，提供结构化的搜索结果。', 'url' => 'https://metaso.cn', 'icon' => '🔍', 'category' => 'search', 'tags' => array('无广告', '中文')),
        array('title' => 'Genspark', 'description' => 'AI驱动的新型搜索引擎，直接生成高度结构化的百科式答案。', 'url' => 'https://www.genspark.ai', 'icon' => '🔥', 'category' => 'search', 'tags' => array('AI搜索', '新物种'), 'hot' => true),
        array('title' => '夸克AI搜索', 'description' => '阿里旗下夸克浏览器的AI搜索功能，智能总结搜索结果。', 'url' => 'https://www.quark.cn', 'icon' => '🌟', 'category' => 'search', 'tags' => array('阿里', '浏览器')),
        array('title' => '360AI搜索', 'description' => '360推出的AI搜索引擎，智能分析和总结网页内容。', 'url' => 'https://ai.so.com', 'icon' => '🔒', 'category' => 'search', 'tags' => array('360', '安全')),
        array('title' => 'Consensus', 'description' => 'AI学术搜索引擎，帮助快速找到相关学术论文。', 'url' => 'https://consensus.ai', 'icon' => '📚', 'category' => 'search', 'tags' => array('学术', '论文')),
        array('title' => 'You.com', 'description' => 'AI驱动的搜索引擎，提供个性化的搜索体验。', 'url' => 'https://you.com', 'icon' => '💬', 'category' => 'search', 'tags' => array('个性化', 'AI')),
        
        // AI网站/工具
        array('title' => 'Canva AI', 'description' => 'Canva集成的AI设计工具，一键生成海报、PPT、视频等设计作品。', 'url' => 'https://www.canva.com', 'icon' => '🎯', 'category' => 'tool', 'tags' => array('设计', '模板')),
        array('title' => 'Gamma', 'description' => 'AI驱动的演示文稿工具，快速生成精美的PPT和文档。', 'url' => 'https://gamma.app', 'icon' => '📊', 'category' => 'tool', 'tags' => array('PPT', '演示')),
        array('title' => 'Remove.bg', 'description' => 'AI自动抠图工具，一键去除图片背景，支持批量处理。', 'url' => 'https://www.remove.bg', 'icon' => '✂️', 'category' => 'tool', 'tags' => array('抠图', '图片处理')),
        array('title' => 'Dify', 'description' => '开源的LLM应用开发平台，快速构建和部署AI应用。', 'url' => 'https://dify.ai', 'icon' => '🛠️', 'category' => 'tool', 'tags' => array('开发', '开源')),
        array('title' => 'ChatPDF', 'description' => 'AI文档阅读助手，与PDF进行智能对话。', 'url' => 'https://chatpdf.com', 'icon' => '📑', 'category' => 'tool', 'tags' => array('PDF', '文档')),
        array('title' => 'Notion', 'description' => 'AI增强的协作文档工具，集成强大的笔记和知识管理功能。', 'url' => 'https://www.notion.so', 'icon' => '📝', 'category' => 'tool', 'tags' => array('协作', '笔记')),
        array('title' => 'Taskade', 'description' => 'AI驱动的任务管理和团队协作平台。', 'url' => 'https://www.taskade.com', 'icon' => '📋', 'category' => 'tool', 'tags' => array('任务', '协作')),
        array('title' => 'Beautiful.ai', 'description' => '智能PPT设计工具，自动应用专业设计原则。', 'url' => 'https://www.beautiful.ai', 'icon' => '✨', 'category' => 'tool', 'tags' => array('设计', '演示')),
        array('title' => 'Tome', 'description' => 'AI叙事演示工具，用AI创建富有故事性的演示文稿。', 'url' => 'https://tome.net', 'icon' => '📖', 'category' => 'tool', 'tags' => array('叙事', '故事')),
        array('title' => 'MindMeister', 'description' => 'AI驱动的在线思维导图工具，自动生成和扩展思维结构。', 'url' => 'https://www.mindmeister.com', 'icon' => '🧠', 'category' => 'tool', 'tags' => array('思维导图', '协作')),
        array('title' => 'Warp', 'description' => 'AI驱动的现代终端工具，智能命令补全和错误修复。', 'url' => 'https://www.warp.dev', 'icon' => '💻', 'category' => 'tool', 'tags' => array('终端', '开发者')),

        // 模型社区
        array('title' => 'Hugging Face', 'description' => '全球最大的开源AI模型库、数据集和演示平台，AI界的GitHub。', 'url' => 'https://huggingface.co', 'icon' => '🤗', 'category' => 'community', 'tags' => array('开源', '模型库', '数据集'), 'hot' => true),
        array('title' => 'Civitai', 'description' => '最流行的Stable Diffusion模型分享社区，拥有海量LoRA和Checkpoint。', 'url' => 'https://civitai.com', 'icon' => '🖼️', 'category' => 'community', 'tags' => array('SD模型', '社区', '开源'), 'hot' => true),
        array('title' => 'ModelScope', 'description' => '阿里推出的魔搭社区，汇集了国内主流的开源模型和数据集。', 'url' => 'https://modelscope.cn', 'icon' => '🧪', 'category' => 'community', 'tags' => array('阿里', '中文', '开源')),
        array('title' => 'Liblib AI', 'description' => '国内领先的AI艺术创作社区与原创模型库，支持在线生图。', 'url' => 'https://www.liblib.art', 'icon' => '🎨', 'category' => 'community', 'tags' => array('国内', '绘画模型')),
        array('title' => 'Kaggle', 'description' => 'Google旗下全球最大的数据科学竞赛平台，提供海量数据集和Notebook。', 'url' => 'https://www.kaggle.com', 'icon' => '📊', 'category' => 'community', 'tags' => array('竞赛', '数据集')),
        array('title' => 'Papers with Code', 'description' => '论文与代码对应平台，追踪AI领域最新研究及开源实现。', 'url' => 'https://paperswithcode.com', 'icon' => '📄', 'category' => 'community', 'tags' => array('论文', '开源代码')),
        array('title' => 'Replicate', 'description' => '一键运行开源AI模型的云平台，无需本地配置即可体验最新模型。', 'url' => 'https://replicate.com', 'icon' => '🔄', 'category' => 'community', 'tags' => array('云推理', '即用')),

        // 办公效率
        array('title' => 'Monica', 'description' => '全能型AI浏览器扩展，支持阅读摘要、侧边栏对话和网页翻译。', 'url' => 'https://monica.im', 'icon' => '🦋', 'category' => 'productivity', 'tags' => array('浏览器助手', '聚合模型'), 'hot' => true),
        array('title' => 'Otter.ai', 'description' => '智能会议记录助手，提供实时的语音转文字及会议摘要。', 'url' => 'https://otter.ai', 'icon' => '🦦', 'category' => 'productivity', 'tags' => array('会议', '效率')),
        array('title' => 'Harvey AI', 'description' => '专为律师和法官设计的AI助手，擅长法律文书分析与案例检索。', 'url' => 'https://www.harvey.ai', 'icon' => '⚖️', 'category' => 'productivity', 'tags' => array('法律', '专业办公')),
        array('title' => 'Gamma', 'description' => 'AI驱动的演示文稿和文档生成工具，一键创建专业级内容。', 'url' => 'https://gamma.app', 'icon' => '📊', 'category' => 'productivity', 'tags' => array('PPT', '文档')),
        array('title' => 'TLDV', 'description' => 'AI会议录制和摘要工具，自动提取会议要点和行动项。', 'url' => 'https://tldv.io', 'icon' => '📹', 'category' => 'productivity', 'tags' => array('会议', '录制')),
        array('title' => 'Mem', 'description' => 'AI驱动的笔记工具，自动组织和关联你的知识碎片。', 'url' => 'https://get.mem', 'icon' => '🧩', 'category' => 'productivity', 'tags' => array('笔记', '知识管理')),
        array('title' => 'Reclaim AI', 'description' => 'AI日程管理助手，智能安排会议和专注时间。', 'url' => 'https://reclaim.ai', 'icon' => '📅', 'category' => 'productivity', 'tags' => array('日程', '时间管理')),
        array('title' => 'Feishu AI', 'description' => '飞书集成的AI助手，支持智能总结、翻译和会议纪要生成。', 'url' => 'https://www.feishu.cn', 'icon' => '🐦', 'category' => 'productivity', 'tags' => array('飞书', '协作')),

        // 电商营销
        array('title' => 'Flair.ai', 'description' => '专为品牌设计的AI摄影棚，一键生成极具商业感的产品展示图。', 'url' => 'https://flair.ai', 'icon' => '🧴', 'category' => 'marketing', 'tags' => array('电商', '摄影'), 'hot' => true),
        array('title' => 'Namelix', 'description' => 'AI驱动的品牌取名工具，支持生成精美的Logo和品牌视觉。', 'url' => 'https://namelix.com', 'icon' => '🏷️', 'category' => 'marketing', 'tags' => array('起名', '品牌')),
        array('title' => 'AdCreative.ai', 'description' => '自动生成高点击率的广告图和社媒素材，提升营销效果。', 'url' => 'https://www.adcreative.ai', 'icon' => '📈', 'category' => 'marketing', 'tags' => array('广告', '素材')),
        array('title' => 'Copy.ai', 'description' => 'AI营销文案生成平台，快速产出广告、社媒和产品描述文案。', 'url' => 'https://www.copy.ai', 'icon' => '📋', 'category' => 'marketing', 'tags' => array('文案', '营销')),
        array('title' => 'Pebblely', 'description' => 'AI产品图生成工具，为电商商品自动生成精美场景图。', 'url' => 'https://pebblely.com', 'icon' => '🛍️', 'category' => 'marketing', 'tags' => array('产品图', '电商')),
        array('title' => 'Mokker AI', 'description' => 'AI商品摄影工具，一键替换产品背景和场景。', 'url' => 'https://mokker.ai', 'icon' => '📸', 'category' => 'marketing', 'tags' => array('商品图', '背景替换')),
        array('title' => 'Jasper Art', 'description' => 'AI品牌视觉生成工具，保持品牌一致性的同时快速产出营销图。', 'url' => 'https://www.jasper.ai/art', 'icon' => '🎨', 'category' => 'marketing', 'tags' => array('品牌', '视觉')),

        // 本地部署
        array('title' => '大海资源网', 'description' => '领先的 AI 技术资源分享平台，专注于本地大模型部署、AI 教程、精品软件及网站优化干货分享。', 'url' => 'https://www.dhzyw.com', 'icon' => '🌊', 'category' => 'local', 'tags' => array('本地部署', 'AI网站')),
        array('title' => 'Ollama', 'description' => '极简的本地大模型运行工具，支持一键部署Llama、Gemma等开源模型。', 'url' => 'https://ollama.com', 'icon' => '🦙', 'category' => 'local', 'tags' => array('本地化', '开源', 'Mac/Win'), 'hot' => true),
        array('title' => 'LM Studio', 'description' => '图形化本地模型管理与推理工具，非常适合硬件性能测试。', 'url' => 'https://lmstudio.ai', 'icon' => '💻', 'category' => 'local', 'tags' => array('本地推理', '图形界面')),
        array('title' => 'AnythingLLM', 'description' => '全能的私有化RAG解决方案，支持将本地文档转化为智能知识库。', 'url' => 'https://useanything.com', 'icon' => '📂', 'category' => 'local', 'tags' => array('RAG', '知识库', '私有化')),
        array('title' => 'OpenClaw', 'description' => '强大的开源AI终端助手，支持多模型切换与本地化高效部署。', 'url' => 'https://github.com/idootop/openclaw', 'icon' => '🦞', 'category' => 'local', 'tags' => array('开源', '本地化'), 'hot' => true),
        array('title' => 'Docker Ollama', 'description' => '使用Docker一键部署Ollama和WebUI，快速搭建本地AI服务。', 'url' => 'https://github.com/valiantlynx/ollama-docker', 'icon' => '🐳', 'category' => 'local', 'tags' => array('Docker', '容器化')),
        array('title' => 'LocalAI', 'description' => '兼容OpenAI API的本地推理服务，无缝替换云端API调用。', 'url' => 'https://localai.io', 'icon' => '🔌', 'category' => 'local', 'tags' => array('API兼容', '本地推理')),
        array('title' => 'text-generation-webui', 'description' => '开源的文本生成Web界面，支持多种模型格式和LoRA微调。', 'url' => 'https://github.com/oobabooga/text-generation-webui', 'icon' => '🖥️', 'category' => 'local', 'tags' => array('WebUI', '微调')),
    );
    
    $order = 1;
    foreach ($sample_tools as $tool) {
        $post_id = wp_insert_post(array(
            'post_type' => 'ai_tool',
            'post_title' => $tool['title'],
            'post_content' => $tool['description'],
            'post_status' => 'publish',
            'post_excerpt' => $tool['description']
        ));
        
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, 'tool_url', $tool['url']);
            update_post_meta($post_id, 'tool_icon', $tool['icon']);
            update_post_meta($post_id, 'tool_hot', !empty($tool['hot']));
            update_post_meta($post_id, 'tool_order', $order++);
            
            // 设置分类
            $cat_slug = $tool['category'];
            if (isset($cat_ids[$cat_slug])) {
                wp_set_object_terms($post_id, $cat_ids[$cat_slug], 'ai_category');
            }
            
            // 设置标签
            foreach ($tool['tags'] as $tag_name) {
                $tag = wp_insert_term($tag_name, 'ai_tag');
                if (!is_wp_error($tag)) {
                    wp_set_object_terms($post_id, $tag['term_id'], 'ai_tag', true);
                } else {
                    $existing = get_term_by('name', $tag_name, 'ai_tag');
                    if ($existing) {
                        wp_set_object_terms($post_id, $existing->term_id, 'ai_tag', true);
                    }
                }
            }
        }
    }
}

/**
 * 主题激活时创建示例数据并刷新重写规则
 */
function ai_navigator_activate() {
    ai_navigator_create_sample_data();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'ai_navigator_activate');

/**
 * 主题停用时刷新重写规则
 */
function ai_navigator_deactivate() {
    flush_rewrite_rules();
}
add_action('switch_theme', 'ai_navigator_deactivate');

/**
 * Admin init fallback: 确保示例数据已创建
 * 防止 after_switch_theme 未触发的情况（如通过 FTP 上传主题后直接选择）
 * 如果用户主动清空过数据，则不再自动导入
 */
function ai_navigator_ensure_sample_data() {
    // 仅在后台执行
    if (!is_admin()) return;
    
    // 如果用户主动清空过数据，不再自动导入
    if (get_option('dh_nav_data_cleared')) return;
    
    // 检查是否需要创建示例数据
    $existing = get_posts(array('post_type' => 'ai_tool', 'posts_per_page' => 1, 'post_status' => 'publish'));
    if (empty($existing)) {
        ai_navigator_create_sample_data();
        flush_rewrite_rules();
    }
}
add_action('admin_init', 'ai_navigator_ensure_sample_data');
