<?php
/**
 * The header for our theme
 *
 * @package AI_Navigator_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#1a1a2e">

    <?php if ( is_front_page() ) : ?>
    <title><?php bloginfo( 'name' ); ?> | <?php bloginfo( 'description' ); ?></title>
    <meta name="description" content="<?php bloginfo( 'description' ); ?>">
    <?php else : ?>
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <?php endif; ?>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <div id="page" class="site">
