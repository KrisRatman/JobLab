<?php
/**
 * Тема JobLab: вёрстка + бэкенд платформы поиска работы.
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/data.php';
require_once get_template_directory() . '/inc/auth.php';
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/moderation.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/seed.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin.php';
}

function joblab_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	register_nav_menus( array(
		'primary' => 'Главное меню',
	) );
}
add_action( 'after_setup_theme', 'joblab_setup' );

function joblab_scripts() {
	wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', array(), null, false );

	wp_add_inline_script( 'tailwindcss', "
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						brand: {
							DEFAULT: '#2563eb',
							dark: '#1d4ed8',
						},
					},
				},
			},
		}
	" );

	wp_enqueue_script( 'joblab-carousel', get_template_directory_uri() . '/js/carousel.js', array(), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'joblab_scripts' );
