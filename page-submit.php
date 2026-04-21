<?php
/**
 * Template Name: 申请收录
 * 申请收录页面模板
 *
 * @package AI_Navigator_Hub
 */

if (!defined('ABSPATH')) {
    exit;
}

// 获取分类用于下拉选择
$categories = get_terms(array(
    'taxonomy' => 'ai_category',
    'hide_empty' => false,
    'parent' => 0,
));

// 页脚设置
$footer_site_name = ai_navigator_get_site_name();
$footer_icp_number = get_option('ai_navigator_icp_number', '');
$footer_icp_url = get_option('ai_navigator_icp_url', 'https://beian.miit.gov.cn/');
$footer_copyright = get_option('ai_navigator_copyright', '');
if (empty($footer_copyright)) $footer_copyright = '&copy; ' . date('Y') . ' ' . $footer_site_name . ' All Rights Reserved.';
$footer_contact = get_option('ai_navigator_contact_info', '');
$footer_friends = get_option('ai_navigator_friend_links', '');

$footer_friends_html = '';
if (!empty($footer_friends)) {
    $_fl = array_filter(array_map('trim', explode("\n", $footer_friends)));
    $_fls = array();
    foreach ($_fl as $l) {
        if (strpos($l, '|') !== false) { $p = explode('|', $l, 2); $_fls[] = '<a href="' . esc_url(trim($p[1])) . '" target="_blank" rel="noopener noreferrer">' . esc_html(trim($p[0])) . '</a>'; }
        else { $_fls[] = $l; }
    }
    $footer_friends_html = implode('<span class="footer-divider">|</span>', $_fls);
}
$footer_contact_html = '';
if (!empty($footer_contact)) {
    $_cl = array_filter(array_map('trim', explode("\n", $footer_contact)));
    $_cts = array();
    foreach ($_cl as $l) {
        if ($l !== strip_tags($l)) $_cts[] = $l;
        else $_cts[] = '<span>' . esc_html($l) . '</span>';
    }
    $footer_contact_html = implode('<span class="footer-divider">·</span>', $_cts);
}

$message = '';
$message_type = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_site_nonce'])) {
    if (!wp_verify_nonce($_POST['submit_site_nonce'], 'submit_site_action')) {
        $message = '安全验证失败，请重试。';
        $message_type = 'error';
    } else {
        $site_name = sanitize_text_field($_POST['site_name'] ?? '');
        $site_url = esc_url($_POST['site_url'] ?? '');
        $site_desc = sanitize_textarea_field($_POST['site_desc'] ?? '');
        $site_category = absint($_POST['site_category'] ?? 0);
        $site_tags = sanitize_text_field($_POST['site_tags'] ?? '');
        $site_contact = sanitize_text_field($_POST['site_contact'] ?? '');
        $site_icon = sanitize_text_field($_POST['site_icon'] ?? '');

        // 验证必填字段
        if (empty($site_name) || empty($site_url) || empty($site_desc)) {
            $message = '请填写所有必填项。';
            $message_type = 'error';
        } elseif (!filter_var($site_url, FILTER_VALIDATE_URL)) {
            $message = '请输入有效的网址。';
            $message_type = 'error';
        } else {
            // 创建待审核的网站文章（草稿状态）
            $post_id = wp_insert_post(array(
                'post_type' => 'ai_tool',
                'post_title' => $site_name,
                'post_content' => $site_desc,
                'post_excerpt' => $site_desc,
                'post_status' => 'pending', // 待审核
            ));

            if (is_wp_error($post_id)) {
                $message = '提交失败，请稍后重试。';
                $message_type = 'error';
            } else {
                // 保存元数据
                update_post_meta($post_id, 'tool_url', $site_url);
                update_post_meta($post_id, 'tool_icon', $site_icon ?: '🔧');
                update_post_meta($post_id, 'submit_contact', $site_contact);
                update_post_meta($post_id, 'submit_time', current_time('mysql'));
                update_post_meta($post_id, 'submit_source', 'frontend');

                // 设置分类
                if ($site_category) {
                    wp_set_object_terms($post_id, $site_category, 'ai_category');
                }

                // 设置标签
                if (!empty($site_tags)) {
                    $tags = array_filter(array_map('trim', explode(',', $site_tags)));
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
                    if (!empty($tag_ids)) {
                        wp_set_object_terms($post_id, $tag_ids, 'ai_tag');
                    }
                }

                $message = '提交成功！您的网站已进入审核队列，审核通过后将自动上线。';
                $message_type = 'success';

                // 清空表单
                $site_name = $site_url = $site_desc = $site_tags = $site_contact = $site_icon = '';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请收录 - <?php bloginfo('name'); ?></title>
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
        .header-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .header-back:hover {
            color: var(--primary);
        }

        /* 表单区域 */
        .submit-page {
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }
        .page-desc {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 32px;
            font-size: 15px;
        }

        /* 消息提示 */
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }
        .message.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* 表单卡片 */
        .form-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .form-label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        .form-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text);
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            font-family: inherit;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-icon-preview {
            font-size: 32px;
            margin-left: 12px;
            vertical-align: middle;
        }
        .form-icon-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-icon-group .form-input {
            flex: 1;
        }
        .icon-picker-btn {
            padding: 8px 14px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .icon-picker-btn:hover {
            background: #e2e8f0;
            border-color: var(--primary);
            color: var(--primary);
        }
        .icon-picker {
            margin-top: 8px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            max-height: 280px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .icon-picker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            font-weight: 500;
            background: #f8fafc;
        }
        .icon-picker-close {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: var(--text-muted);
            padding: 2px 6px;
            border-radius: 4px;
        }
        .icon-picker-close:hover {
            background: #e2e8f0;
            color: var(--text);
        }
        .icon-picker-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 2px;
            padding: 10px;
            overflow-y: auto;
            max-height: 230px;
        }
        .icon-picker-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .icon-picker-item:hover {
            background: #e0f2fe;
            transform: scale(1.2);
        }
        .icon-picker-item.selected {
            background: #dbeafe;
            box-shadow: 0 0 0 2px var(--primary);
        }
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: var(--gradient);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-tips {
            margin-top: 24px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .submit-tips h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text);
        }
        .submit-tips ul {
            list-style: none;
            padding: 0;
        }
        .submit-tips li {
            font-size: 13px;
            color: var(--text-muted);
            padding: 4px 0;
            padding-left: 18px;
            position: relative;
        }
        .submit-tips li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary);
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

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .page-title {
                font-size: 22px;
            }
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- 头部 -->
    <header class="header">
        <div class="header-inner">
            <a href="<?php echo home_url(); ?>" class="logo">🤖 <?php echo esc_html($footer_site_name); ?></a>
            <a href="<?php echo home_url(); ?>" class="header-back">← 返回首页</a>
        </div>
    </header>

    <!-- 表单区域 -->
    <main class="submit-page">
        <h1 class="page-title">📝 申请收录</h1>
        <p class="page-desc">提交您的网站信息，审核通过后将展示在<?php echo esc_html($footer_site_name); ?>中</p>

        <?php if ($message) : ?>
        <div class="message <?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post" action="">
                <?php wp_nonce_field('submit_site_action', 'submit_site_nonce'); ?>

                <div class="form-group">
                    <label class="form-label">网站名称 <span class="required">*</span></label>
                    <input type="text" name="site_name" class="form-input" placeholder="例如：ChatGPT" value="<?php echo esc_attr($site_name ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">网站网址 <span class="required">*</span></label>
                    <input type="url" name="site_url" class="form-input" placeholder="https://example.com" value="<?php echo esc_attr($site_url ?? ''); ?>" required>
                    <p class="form-hint">请输入完整的网址，包含 https://</p>
                </div>

                <div class="form-group">
                    <label class="form-label">网站简介 <span class="required">*</span></label>
                    <textarea name="site_desc" class="form-textarea" placeholder="简要描述网站的功能和特色..." required><?php echo esc_textarea($site_desc ?? ''); ?></textarea>
                    <p class="form-hint">建议 20-100 字，简明扼要地描述网站用途</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">网站分类</label>
                        <select name="site_category" class="form-select">
                            <option value="">选择分类</option>
                            <?php if (!is_wp_error($categories)) : ?>
                                <?php foreach ($categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(isset($site_category) && $site_category == $cat->term_id); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">图标</label>
                        <div class="form-icon-group">
                            <input type="text" name="site_icon" class="form-input" placeholder="🔧" value="<?php echo esc_attr($site_icon ?? ''); ?>" maxlength="10" id="iconInput" oninput="updateIconPreview()">
                            <span class="form-icon-preview" id="iconPreview">🔧</span>
                            <button type="button" class="icon-picker-btn" onclick="toggleIconPicker()">📋 选择</button>
                        </div>
                        <div class="icon-picker" id="iconPicker" style="display:none;">
                            <div class="icon-picker-header">
                                <span>选择图标</span>
                                <button type="button" class="icon-picker-close" onclick="toggleIconPicker()">✕</button>
                            </div>
                            <div class="icon-picker-grid">
                                <?php foreach (ai_navigator_get_icons() as $icon) : ?>
                                <button type="button" class="icon-picker-item" onclick="selectIcon('<?php echo esc_js($icon); ?>')" title="<?php echo esc_attr($icon); ?>"><?php echo $icon; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="form-hint">点击"选择"从列表中选取，或直接输入 Emoji</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">标签</label>
                    <input type="text" name="site_tags" class="form-input" placeholder="多个标签用逗号分隔，例如：AI对话, 编程, 写作" value="<?php echo esc_attr($site_tags ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">联系方式</label>
                    <input type="text" name="site_contact" class="form-input" placeholder="邮箱或其他联系方式（选填，方便我们联系您）" value="<?php echo esc_attr($site_contact ?? ''); ?>">
                </div>

                <button type="submit" class="submit-btn">🚀 提交申请</button>
            </form>
        </div>

        <div class="submit-tips">
            <h3>📌 收录须知</h3>
            <ul>
                <li>提交后需人工审核，通常 1-3 个工作日内完成</li>
                <li>网站内容需合法合规，无不良信息</li>
                <li>网站需能正常访问，且具有一定的实用价值</li>
                <li>审核通过后网站将自动上线展示</li>
                <li>如需修改信息，可通过联系方式与我们沟通</li>
            </ul>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="site-footer">
        <div class="footer-inner">
            <?php if (!empty($footer_friends_html)) : ?>
            <div class="footer-friend-links">
                <span class="footer-label">友情链接：</span>
                <?php echo $footer_friends_html; ?>
            </div>
            <?php endif; ?>
            <div class="footer-links">
                <a href="<?php echo home_url('/submit'); ?>">申请收录</a>
                <span class="footer-divider">|</span>
                <a href="<?php echo home_url(); ?>"><?php echo esc_html($footer_site_name); ?></a>
            </div>
            <?php if (!empty($footer_contact_html)) : ?>
            <div class="footer-contact">
                <span class="footer-label">联系方式：</span>
                <?php echo $footer_contact_html; ?>
            </div>
            <?php endif; ?>
            <div class="footer-copyright">
                <?php echo $footer_copyright; ?>
            </div>
            <?php if (!empty($footer_icp_number)) : ?>
            <div class="footer-icp">
                <a href="<?php echo esc_url($footer_icp_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($footer_icp_number); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <script>
    function updateIconPreview() {
        var input = document.getElementById('iconInput');
        var preview = document.getElementById('iconPreview');
        preview.textContent = input.value || '🔧';
        // 更新选中状态
        document.querySelectorAll('.icon-picker-item').forEach(function(el) {
            el.classList.toggle('selected', el.textContent.trim() === input.value.trim());
        });
    }
    function toggleIconPicker() {
        var picker = document.getElementById('iconPicker');
        picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
        if (picker.style.display === 'flex') {
            updateIconPreview();
        }
    }
    function selectIcon(icon) {
        document.getElementById('iconInput').value = icon;
        updateIconPreview();
    }
    // 初始化预览
    updateIconPreview();
    </script>
</body>
</html>
