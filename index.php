<?php
/**
 * The main template file
 * This template is used to render the React SPA
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    <div id="root">
        <?php
        // React 应用挂载点
        // React 应用将接管整个页面内容
        ?>
    </div>
</main>

<?php
get_footer();
