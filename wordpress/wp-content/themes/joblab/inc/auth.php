<?php
/**
 * Регистрация, вход и обработка блокировок на фронте.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Уведомления между редиректами передаём через короткоживущий transient.
 */
function jl_set_notice( $type, $message ) {
	$key = 'jl_n_' . wp_generate_password( 12, false );
	set_transient( $key, array( 'type' => $type, 'message' => $message ), 5 * MINUTE_IN_SECONDS );

	return $key;
}

function jl_get_notice() {
	$key = isset( $_GET['jl_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['jl_notice'] ) ) : '';
	if ( ! $key ) {
		return null;
	}

	$notice = get_transient( $key );
	if ( $notice ) {
		delete_transient( $key );
	}

	return $notice ?: null;
}

function jl_redirect_with_notice( $url, $type, $message ) {
	wp_safe_redirect( add_query_arg( 'jl_notice', jl_set_notice( $type, $message ), $url ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Регистрация
 * ---------------------------------------------------------------------- */

function jl_handle_register() {
	check_admin_referer( 'jl_register' );

	$redirect = jl_page_url( 'register' );

	$role  = isset( $_POST['role'] ) && JL_ROLE_EMPLOYER === $_POST['role'] ? JL_ROLE_EMPLOYER : JL_ROLE_SEEKER;
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$pass  = (string) ( $_POST['password'] ?? '' );
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

	if ( ! is_email( $email ) ) {
		jl_redirect_with_notice( $redirect, 'error', 'Укажите корректный e-mail.' );
	}
	if ( email_exists( $email ) ) {
		jl_redirect_with_notice( $redirect, 'error', 'Пользователь с таким e-mail уже зарегистрирован.' );
	}
	if ( strlen( $pass ) < 6 ) {
		jl_redirect_with_notice( $redirect, 'error', 'Пароль должен быть не короче 6 символов.' );
	}
	if ( '' === $name ) {
		jl_redirect_with_notice( $redirect, 'error', 'Укажите имя.' );
	}

	$login = jl_unique_login( $email );

	$user_id = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $name,
			'role'         => $role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		jl_redirect_with_notice( $redirect, 'error', $user_id->get_error_message() );
	}

	if ( JL_ROLE_SEEKER === $role ) {
		$parts = preg_split( '/\s+/u', $name, 2 );
		jl_save_profile(
			$user_id,
			array(
				'jl_first_name'   => $parts[0] ?? '',
				'jl_last_name'    => $parts[1] ?? '',
				'jl_open_to_work' => 1,
			)
		);
	} else {
		jl_save_company( $user_id, array( 'name' => sanitize_text_field( wp_unslash( $_POST['company'] ?? $name ) ) ) );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'success', 'Добро пожаловать в JobLab! Заполните профиль, чтобы начать.' );
}
add_action( 'admin_post_nopriv_jl_register', 'jl_handle_register' );
add_action( 'admin_post_jl_register', 'jl_handle_register' );

/**
 * Логин на основе e-mail, но гарантированно уникальный.
 */
function jl_unique_login( $email ) {
	$base  = sanitize_user( current( explode( '@', $email ) ), true );
	$base  = $base ?: 'user';
	$login = $base;
	$i     = 1;

	while ( username_exists( $login ) ) {
		$login = $base . ++$i;
	}

	return $login;
}

/* -------------------------------------------------------------------------
 * Вход и выход
 * ---------------------------------------------------------------------- */

function jl_handle_login() {
	check_admin_referer( 'jl_login' );

	$redirect = jl_page_url( 'login' );

	$user = wp_signon(
		array(
			'user_login'    => sanitize_text_field( wp_unslash( $_POST['login'] ?? '' ) ),
			'user_password' => (string) ( $_POST['password'] ?? '' ),
			'remember'      => ! empty( $_POST['remember'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		if ( 'jl_banned' === $user->get_error_code() ) {
			wp_safe_redirect( jl_page_url( 'blocked' ) );
			exit;
		}
		jl_redirect_with_notice( $redirect, 'error', 'Неверный e-mail или пароль.' );
	}

	wp_set_current_user( $user->ID );

	wp_safe_redirect( jl_page_url( 'cabinet' ) );
	exit;
}
add_action( 'admin_post_nopriv_jl_login', 'jl_handle_login' );
add_action( 'admin_post_jl_login', 'jl_handle_login' );

function jl_handle_logout() {
	check_admin_referer( 'jl_logout' );
	wp_logout();
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
add_action( 'admin_post_jl_logout', 'jl_handle_logout' );
add_action( 'admin_post_nopriv_jl_logout', 'jl_handle_logout' );

/**
 * Вход по e-mail наравне с логином.
 */
function jl_allow_email_login( $user, $username, $password ) {
	if ( $user instanceof WP_User || ! is_email( $username ) ) {
		return $user;
	}

	$by_email = get_user_by( 'email', $username );

	return $by_email ? wp_authenticate_username_password( null, $by_email->user_login, $password ) : $user;
}
add_filter( 'authenticate', 'jl_allow_email_login', 20, 3 );

/**
 * Заблокированного не пускаем внутрь.
 */
function jl_block_banned_login( $user ) {
	if ( $user instanceof WP_User && jl_user_is_banned( $user->ID ) ) {
		return new WP_Error( 'jl_banned', jl_banned_user_notice() );
	}

	return $user;
}
add_filter( 'wp_authenticate_user', 'jl_block_banned_login', 10 );

/* -------------------------------------------------------------------------
 * Поведение заблокированного пользователя на сайте
 * ---------------------------------------------------------------------- */

/**
 * Если человека забанили в течение сессии — уводим его на страницу-заглушку.
 */
function jl_redirect_banned_user() {
	if ( ! is_user_logged_in() || is_admin() ) {
		return;
	}
	if ( ! jl_user_is_banned( get_current_user_id() ) ) {
		return;
	}
	if ( jl_is_page( 'blocked' ) ) {
		return;
	}

	wp_safe_redirect( jl_page_url( 'blocked' ) );
	exit;
}
add_action( 'template_redirect', 'jl_redirect_banned_user', 1 );

/**
 * Соискателям и работодателям в консоли делать нечего.
 */
function jl_block_admin_access() {
	if ( ! is_user_logged_in() || wp_doing_ajax() ) {
		return;
	}

	// admin-post.php и admin-ajax.php тоже вызывают admin_init — через них
	// работают формы фронтенда, их перехватывать нельзя.
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
	if ( in_array( $script, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
		return;
	}
	if ( isset( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
		return;
	}

	if ( current_user_can( 'jl_moderate' ) ) {
		return;
	}
	if ( jl_is_seeker() || jl_is_employer() ) {
		wp_safe_redirect( jl_page_url( 'cabinet' ) );
		exit;
	}
}
add_action( 'admin_init', 'jl_block_admin_access' );

function jl_hide_admin_bar( $show ) {
	return current_user_can( 'jl_moderate' ) ? $show : false;
}
add_filter( 'show_admin_bar', 'jl_hide_admin_bar' );

/**
 * Собственный экран входа вместо wp-login.php для обычных пользователей.
 */
function jl_redirect_wp_login() {
	if ( isset( $_GET['jl_wp'] ) || ! empty( $_POST ) ) {
		return; // Админ всегда может зайти через wp-login.php?jl_wp=1
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'login';
	if ( 'login' !== $action ) {
		return;
	}

	wp_safe_redirect( jl_page_url( 'login' ) );
	exit;
}
add_action( 'login_form_login', 'jl_redirect_wp_login' );

/**
 * Куда отправлять после входа через штатную форму WordPress.
 */
function jl_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect_to;
	}
	if ( jl_is_seeker( $user ) || jl_is_employer( $user ) ) {
		return jl_page_url( 'cabinet' );
	}

	return $redirect_to;
}
add_filter( 'login_redirect', 'jl_login_redirect', 10, 3 );

/**
 * Текущий пользователь или null — короткая форма для шаблонов.
 */
function jl_current_user() {
	$user = wp_get_current_user();

	return $user && $user->ID ? $user : null;
}

/**
 * Требует роль на странице кабинета, иначе отправляет на вход.
 */
function jl_require_login() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( jl_page_url( 'login' ) );
		exit;
	}
}
