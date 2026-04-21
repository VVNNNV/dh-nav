<?php
/**
 * The footer for our theme
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$site_name = ai_navigator_get_site_name();
$icp_number = get_option('ai_navigator_icp_number', '');
$icp_url = get_option('ai_navigator_icp_url', 'https://beian.miit.gov.cn/');
$copyright = get_option('ai_navigator_copyright', '');
$contact_info = get_option('ai_navigator_contact_info', '');
$friend_links = get_option('ai_navigator_friend_links', '');

// 默认版权信息
if (empty($copyright)) {
    $copyright = '&copy; ' . date('Y') . ' ' . $site_name . ' All Rights Reserved.';
}

// 解析友情链接
$friend_links_html = '';
if (!empty($friend_links)) {
    $lines = array_filter(array_map('trim', explode("\n", $friend_links)));
    $links = array();
    foreach ($lines as $line) {
        // 支持 名称|URL 格式
        if (strpos($line, '|') !== false) {
            $parts = explode('|', $line, 2);
            $name = trim($parts[0]);
            $url = trim($parts[1]);
            $links[] = '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($name) . '</a>';
        } else {
            // 直接使用HTML
            $links[] = $line;
        }
    }
    $friend_links_html = implode('<span class="footer-divider">|</span>', $links);
}

// 解析联系方式
$contact_html = '';
if (!empty($contact_info)) {
    $lines = array_filter(array_map('trim', explode("\n", $contact_info)));
    $contacts = array();
    foreach ($lines as $line) {
        // 如果包含HTML标签，直接使用
        if ($line !== strip_tags($line)) {
            $contacts[] = $line;
        } else {
            $contacts[] = '<span>' . esc_html($line) . '</span>';
        }
    }
    $contact_html = implode('<span class="footer-divider">·</span>', $contacts);
}
?>

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

<style>
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
</style>

    </div><!-- #page -->

    <?php wp_footer(); ?>
</body>
</html>