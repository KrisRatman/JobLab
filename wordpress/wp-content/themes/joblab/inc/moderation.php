<?php
/**
 * Модерация: блокировка вакансий и работников из админки,
 * автоудаление заблокированного через JL_BAN_TTL_DAYS дней.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Вакансии: колонка, действия, массовые операции
 * ---------------------------------------------------------------------- */

function jl_vacancy_columns( $columns ) {
	$insert = array(
		'jl_company' => 'Компания',
		'jl_state'   => 'Модерация',
	);

	// Вставляем перед колонкой даты.
	$offset = array_search( 'date', array_keys( $columns ), true );
	if ( false === $offset ) {
		return array_merge( $columns, $insert );
	}

	return array_merge(
		array_slice( $columns, 0, $offset ),
		$insert,
		array_slice( $columns, $offset )
	);
}
add_filter( 'manage_' . JL_CPT_VACANCY . '_posts_columns', 'jl_vacancy_columns' );

function jl_vacancy_column_content( $column, $post_id ) {
	if ( 'jl_company' === $column ) {
		$company_id = (int) get_post_meta( $post_id, '_jl_company_id', true );
		echo $company_id ? esc_html( get_the_title( $company_id ) ) : '—';

		return;
	}

	if ( 'jl_state' !== $column ) {
		return;
	}

	if ( jl_vacancy_is_banned( $post_id ) ) {
		$left   = jl_days_until_purge( get_post_meta( $post_id, '_jl_banned_at', true ) );
		$reason = get_post_meta( $post_id, '_jl_ban_reason', true );

		printf(
			'<span style="color:#b32d2e;font-weight:600">Заблокировано</span><br><small>Удаление через %d дн.</small>',
			(int) $left
		);
		if ( $reason ) {
			echo '<br><small>' . esc_html( $reason ) . '</small>';
		}

		return;
	}

	echo '<span style="color:#008a20">Активна</span>';
}
add_action( 'manage_' . JL_CPT_VACANCY . '_posts_custom_column', 'jl_vacancy_column_content', 10, 2 );

/**
 * Ссылки «Забанить» / «Разбанить» в списке вакансий.
 */
function jl_vacancy_row_actions( $actions, $post ) {
	if ( JL_CPT_VACANCY !== $post->post_type || ! current_user_can( 'jl_moderate' ) ) {
		return $actions;
	}

	if ( jl_vacancy_is_banned( $post ) ) {
		$actions['jl_unban'] = sprintf(
			'<a href="%s">Снять блокировку</a>',
			esc_url( jl_moderation_link( 'jl_unban_vacancy', $post->ID ) )
		);
	} else {
		$actions['jl_ban'] = sprintf(
			'<a href="%s" style="color:#b32d2e">Заблокировать</a>',
			esc_url( jl_moderation_link( 'jl_ban_vacancy', $post->ID ) )
		);
	}

	return $actions;
}
add_filter( 'post_row_actions', 'jl_vacancy_row_actions', 10, 2 );

function jl_moderation_link( $action, $object_id ) {
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=' . $action . '&id=' . (int) $object_id ),
		$action . '_' . (int) $object_id
	);
}

/**
 * Обработчики ссылок модерации.
 */
function jl_moderation_route() {
	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
	$id     = absint( $_GET['id'] ?? 0 );

	if ( ! current_user_can( 'jl_moderate' ) ) {
		wp_die( 'Недостаточно прав.', 'JobLab', array( 'response' => 403 ) );
	}
	check_admin_referer( $action . '_' . $id );

	switch ( $action ) {
		case 'jl_ban_vacancy':
			jl_ban_vacancy( $id, 'Заблокировано модератором' );
			$back = admin_url( 'edit.php?post_type=' . JL_CPT_VACANCY );
			break;
		case 'jl_unban_vacancy':
			jl_unban_vacancy( $id );
			$back = admin_url( 'edit.php?post_type=' . JL_CPT_VACANCY );
			break;
		case 'jl_ban_user':
			jl_ban_user( $id, 'Заблокирован модератором' );
			$back = admin_url( 'users.php' );
			break;
		case 'jl_unban_user':
			jl_unban_user( $id );
			$back = admin_url( 'users.php' );
			break;
		default:
			$back = admin_url();
	}

	wp_safe_redirect( $back );
	exit;
}
foreach ( array( 'jl_ban_vacancy', 'jl_unban_vacancy', 'jl_ban_user', 'jl_unban_user' ) as $jl_action ) {
	add_action( 'admin_post_' . $jl_action, 'jl_moderation_route' );
}

/**
 * Массовые действия в списке вакансий.
 */
function jl_vacancy_bulk_actions( $actions ) {
	$actions['jl_ban']   = 'Заблокировать';
	$actions['jl_unban'] = 'Снять блокировку';

	return $actions;
}
add_filter( 'bulk_actions-edit-' . JL_CPT_VACANCY, 'jl_vacancy_bulk_actions' );

function jl_vacancy_handle_bulk( $redirect, $action, $ids ) {
	if ( ! in_array( $action, array( 'jl_ban', 'jl_unban' ), true ) || ! current_user_can( 'jl_moderate' ) ) {
		return $redirect;
	}

	foreach ( $ids as $id ) {
		'jl_ban' === $action ? jl_ban_vacancy( $id, 'Заблокировано модератором' ) : jl_unban_vacancy( $id );
	}

	return add_query_arg( 'jl_done', count( $ids ), $redirect );
}
add_filter( 'handle_bulk_actions-edit-' . JL_CPT_VACANCY, 'jl_vacancy_handle_bulk', 10, 3 );

/* -------------------------------------------------------------------------
 * Пользователи: колонки и блокировка
 * ---------------------------------------------------------------------- */

function jl_user_columns( $columns ) {
	$columns['jl_profile'] = 'Профиль JobLab';
	$columns['jl_state']   = 'Модерация';

	return $columns;
}
add_filter( 'manage_users_columns', 'jl_user_columns' );

function jl_user_column_content( $output, $column, $user_id ) {
	if ( 'jl_profile' === $column ) {
		if ( jl_is_employer( get_userdata( $user_id ) ) ) {
			$company = jl_get_employer_company( $user_id );

			return 'Работодатель' . ( $company ? '<br><small>' . esc_html( $company->post_title ) . '</small>' : '' );
		}

		if ( ! jl_is_seeker( get_userdata( $user_id ) ) ) {
			return $output;
		}

		$skills = array_filter( array_map(
			static function ( $id ) {
				$term = get_term( $id, JL_TAX_SKILL );

				return $term && ! is_wp_error( $term ) ? $term->name : null;
			},
			jl_get_user_skills( $user_id )
		) );

		$position = get_user_meta( $user_id, 'jl_position', true );

		return esc_html( $position ?: 'Соискатель' )
			. ( $skills ? '<br><small>' . esc_html( implode( ', ', $skills ) ) . '</small>' : '' );
	}

	if ( 'jl_state' !== $column ) {
		return $output;
	}

	if ( ! jl_is_seeker( get_userdata( $user_id ) ) && ! jl_is_employer( get_userdata( $user_id ) ) ) {
		return '—';
	}

	if ( jl_user_is_banned( $user_id ) ) {
		$left = jl_days_until_purge( get_user_meta( $user_id, 'jl_banned_at', true ) );

		return sprintf(
			'<span style="color:#b32d2e;font-weight:600">Заблокирован</span><br><small>Удаление через %d дн.</small><br><a href="%s">Снять блокировку</a>',
			(int) $left,
			esc_url( jl_moderation_link( 'jl_unban_user', $user_id ) )
		);
	}

	return sprintf(
		'<span style="color:#008a20">Активен</span><br><a href="%s" style="color:#b32d2e">Заблокировать</a>',
		esc_url( jl_moderation_link( 'jl_ban_user', $user_id ) )
	);
}
add_filter( 'manage_users_custom_column', 'jl_user_column_content', 10, 3 );

/* -------------------------------------------------------------------------
 * Видимость заблокированных вакансий
 * ---------------------------------------------------------------------- */

/**
 * Чужую заблокированную вакансию не должен открыть никто, кроме модератора.
 * Своя — открывается, но с заглушкой вместо содержимого.
 */
function jl_guard_banned_vacancy() {
	if ( ! is_singular( JL_CPT_VACANCY ) ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post || ! jl_vacancy_is_banned( $post ) ) {
		return;
	}

	if ( current_user_can( 'jl_moderate' ) || (int) $post->post_author === get_current_user_id() ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'jl_guard_banned_vacancy', 5 );

/**
 * Забаненные работодатели не должны светиться вакансиями в публичных выборках.
 */
function jl_exclude_banned_authors( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( JL_CPT_VACANCY ) && ! $query->is_tax( array( JL_TAX_CATEGORY, JL_TAX_SKILL ) ) ) {
		return;
	}

	$banned = jl_banned_user_ids();
	if ( $banned ) {
		$query->set( 'author__not_in', $banned );
	}

	$query->set( 'posts_per_page', 20 );
}
add_action( 'pre_get_posts', 'jl_exclude_banned_authors' );

function jl_banned_user_ids() {
	$ids = get_users(
		array(
			'meta_key'   => 'jl_banned',
			'meta_value' => 1,
			'fields'     => 'ID',
			'number'     => 500,
		)
	);

	return array_map( 'intval', $ids );
}

/* -------------------------------------------------------------------------
 * Автоудаление через две недели
 * ---------------------------------------------------------------------- */

function jl_cleanup_banned() {
	$threshold = time() - JL_BAN_TTL_DAYS * DAY_IN_SECONDS;

	// Вакансии.
	$vacancies = get_posts(
		array(
			'post_type'      => JL_CPT_VACANCY,
			'post_status'    => JL_STATUS_BANNED,
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_jl_banned_at',
					'value'   => $threshold,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $vacancies as $vacancy_id ) {
		jl_delete_vacancy_cascade( $vacancy_id );
	}

	// Работники и работодатели.
	$users = get_users(
		array(
			'fields'     => 'ID',
			'number'     => 200,
			'meta_query' => array(
				array(
					'key'     => 'jl_banned_at',
					'value'   => $threshold,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	require_once ABSPATH . 'wp-admin/includes/user.php';

	foreach ( $users as $user_id ) {
		if ( user_can( $user_id, 'jl_moderate' ) ) {
			continue;
		}
		jl_delete_user_cascade( (int) $user_id );
	}
}
add_action( 'jl_cleanup_banned', 'jl_cleanup_banned' );

/**
 * Удаляет вакансию вместе с её откликами.
 */
function jl_delete_vacancy_cascade( $vacancy_id ) {
	$applications = get_posts(
		array(
			'post_type'      => JL_CPT_APPLICATION,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_jl_vacancy_id',
			'meta_value'     => (int) $vacancy_id,
		)
	);

	foreach ( $applications as $application_id ) {
		wp_delete_post( $application_id, true );
	}

	wp_delete_post( (int) $vacancy_id, true );
}

/**
 * Удаляет пользователя вместе с его вакансиями, компанией и откликами.
 */
function jl_delete_user_cascade( $user_id ) {
	$user_id = (int) $user_id;

	$owned = get_posts(
		array(
			'post_type'      => array( JL_CPT_VACANCY, JL_CPT_COMPANY, JL_CPT_APPLICATION ),
			'post_status'    => 'any',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $owned as $post_id ) {
		if ( JL_CPT_VACANCY === get_post_type( $post_id ) ) {
			jl_delete_vacancy_cascade( $post_id );
			continue;
		}
		wp_delete_post( $post_id, true );
	}

	// Отклики, адресованные этому работодателю.
	$received = get_posts(
		array(
			'post_type'      => JL_CPT_APPLICATION,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_jl_employer_id',
			'meta_value'     => $user_id,
		)
	);

	foreach ( $received as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	wp_delete_user( $user_id );
}

/**
 * Подстраховка: если cron не отрабатывал, чистим не чаще раза в сутки при заходе в админку.
 */
function jl_cleanup_fallback() {
	if ( ! current_user_can( 'jl_moderate' ) ) {
		return;
	}

	$last = (int) get_option( 'jl_last_cleanup', 0 );
	if ( time() - $last < DAY_IN_SECONDS ) {
		return;
	}

	update_option( 'jl_last_cleanup', time() );
	jl_cleanup_banned();
}
add_action( 'admin_init', 'jl_cleanup_fallback', 20 );
