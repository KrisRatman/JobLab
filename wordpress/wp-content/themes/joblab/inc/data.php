<?php
/**
 * Работа с данными: профили соискателей, вакансии, отклики, подбор по навыкам.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Роли пользователя
 * ---------------------------------------------------------------------- */

function jl_user_has_role( $user, $role ) {
	$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );

	return $user && in_array( $role, (array) $user->roles, true );
}

function jl_is_seeker( $user = null ) {
	$user = $user ?: wp_get_current_user();

	return $user && $user->ID && jl_user_has_role( $user, JL_ROLE_SEEKER );
}

function jl_is_employer( $user = null ) {
	$user = $user ?: wp_get_current_user();

	return $user && $user->ID && jl_user_has_role( $user, JL_ROLE_EMPLOYER );
}

function jl_is_moderator( $user = null ) {
	$user = $user ?: wp_get_current_user();

	return $user && $user->ID && user_can( $user, 'jl_moderate' );
}

/* -------------------------------------------------------------------------
 * Блокировки
 * ---------------------------------------------------------------------- */

function jl_user_is_banned( $user_id ) {
	return (bool) get_user_meta( (int) $user_id, 'jl_banned', true );
}

function jl_ban_user( $user_id, $reason = '' ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || user_can( $user_id, 'jl_moderate' ) ) {
		return false;
	}

	update_user_meta( $user_id, 'jl_banned', 1 );
	update_user_meta( $user_id, 'jl_banned_at', time() );
	update_user_meta( $user_id, 'jl_ban_reason', sanitize_text_field( $reason ) );

	// Сессию намеренно не рвём: иначе человека выкинет на форму входа,
	// а он должен сразу увидеть страницу с причиной блокировки.
	// Действовать он всё равно не может — jl_guard() и template_redirect
	// перехватывают и запросы, и отправку форм.

	return true;
}

function jl_unban_user( $user_id ) {
	$user_id = (int) $user_id;
	delete_user_meta( $user_id, 'jl_banned' );
	delete_user_meta( $user_id, 'jl_banned_at' );
	delete_user_meta( $user_id, 'jl_ban_reason' );

	return true;
}

function jl_vacancy_is_banned( $post ) {
	$post = get_post( $post );

	return $post && JL_STATUS_BANNED === $post->post_status;
}

function jl_ban_vacancy( $post_id, $reason = '' ) {
	$post = get_post( $post_id );
	if ( ! $post || JL_CPT_VACANCY !== $post->post_type ) {
		return false;
	}

	update_post_meta( $post->ID, '_jl_status_before_ban', $post->post_status );
	update_post_meta( $post->ID, '_jl_banned_at', time() );
	update_post_meta( $post->ID, '_jl_ban_reason', sanitize_text_field( $reason ) );

	wp_update_post( array( 'ID' => $post->ID, 'post_status' => JL_STATUS_BANNED ) );

	return true;
}

function jl_unban_vacancy( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || JL_CPT_VACANCY !== $post->post_type ) {
		return false;
	}

	$previous = get_post_meta( $post->ID, '_jl_status_before_ban', true );

	wp_update_post(
		array(
			'ID'          => $post->ID,
			'post_status' => $previous && JL_STATUS_BANNED !== $previous ? $previous : 'publish',
		)
	);

	delete_post_meta( $post->ID, '_jl_banned_at' );
	delete_post_meta( $post->ID, '_jl_ban_reason' );
	delete_post_meta( $post->ID, '_jl_status_before_ban' );

	return true;
}

/** Текст, который видит работодатель вместо своей заблокированной заявки. */
function jl_banned_vacancy_notice() {
	return 'Ваша заявка не удовлетворяет требования платформы';
}

/** Текст, который видит заблокированный работник. */
function jl_banned_user_notice() {
	return 'Вы были заблокированы за неудовлетворение требований платформы';
}

/**
 * Сколько дней осталось до автоудаления заблокированной записи.
 */
function jl_days_until_purge( $banned_at ) {
	$banned_at = (int) $banned_at;
	if ( ! $banned_at ) {
		return JL_BAN_TTL_DAYS;
	}

	$left = JL_BAN_TTL_DAYS - (int) floor( ( time() - $banned_at ) / DAY_IN_SECONDS );

	return max( 0, $left );
}

/* -------------------------------------------------------------------------
 * Профиль соискателя
 * ---------------------------------------------------------------------- */

/**
 * Поля профиля: ключ мета => санитайзер.
 */
function jl_profile_fields() {
	return array(
		'jl_first_name'      => 'sanitize_text_field',
		'jl_last_name'       => 'sanitize_text_field',
		'jl_phone'           => 'sanitize_text_field',
		'jl_city'            => 'sanitize_text_field',
		'jl_position'        => 'sanitize_text_field',
		'jl_salary'          => 'absint',
		'jl_experience'      => 'sanitize_key',
		'jl_education_level' => 'sanitize_key',
		'jl_education_place' => 'sanitize_text_field',
		'jl_about'           => 'wp_kses_post',
		'jl_open_to_work'    => 'absint',
	);
}

function jl_get_profile( $user_id ) {
	$user_id = (int) $user_id;
	$profile = array();

	foreach ( array_keys( jl_profile_fields() ) as $key ) {
		$profile[ $key ] = get_user_meta( $user_id, $key, true );
	}

	$profile['skills'] = jl_get_user_skills( $user_id );

	return $profile;
}

function jl_save_profile( $user_id, array $input ) {
	$user_id = (int) $user_id;

	foreach ( jl_profile_fields() as $key => $sanitizer ) {
		if ( ! array_key_exists( $key, $input ) ) {
			continue;
		}
		update_user_meta( $user_id, $key, call_user_func( $sanitizer, $input[ $key ] ) );
	}

	$display = trim( (string) get_user_meta( $user_id, 'jl_first_name', true ) . ' ' . (string) get_user_meta( $user_id, 'jl_last_name', true ) );
	if ( $display ) {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $display,
				'first_name'   => get_user_meta( $user_id, 'jl_first_name', true ),
				'last_name'    => get_user_meta( $user_id, 'jl_last_name', true ),
			)
		);
	}
}

/**
 * Навыки работника хранятся отдельными строками usermeta — по ним идёт подбор.
 *
 * @return int[] ID термов таксономии навыков.
 */
function jl_get_user_skills( $user_id ) {
	$skills = get_user_meta( (int) $user_id, 'jl_skill', false );

	return array_values( array_unique( array_map( 'intval', (array) $skills ) ) );
}

function jl_set_user_skills( $user_id, array $term_ids ) {
	$user_id = (int) $user_id;

	delete_user_meta( $user_id, 'jl_skill' );

	foreach ( array_unique( array_map( 'intval', $term_ids ) ) as $term_id ) {
		if ( $term_id > 0 && get_term( $term_id, JL_TAX_SKILL ) ) {
			add_user_meta( $user_id, 'jl_skill', $term_id );
		}
	}
}

/**
 * Превращает пользовательский ввод («PHP, Docker, новый навык») в ID термов.
 * Существующие навыки переиспользуются, незнакомые создаются.
 */
function jl_resolve_skills( $input, $create_missing = true ) {
	$names = is_array( $input ) ? $input : preg_split( '/\s*,\s*/u', (string) $input );
	$ids   = array();

	foreach ( (array) $names as $name ) {
		$name = trim( wp_strip_all_tags( (string) $name ) );
		if ( '' === $name ) {
			continue;
		}

		// Числовой ввод — уже готовый ID терма (чекбоксы в форме).
		if ( ctype_digit( $name ) && get_term( (int) $name, JL_TAX_SKILL ) ) {
			$ids[] = (int) $name;
			continue;
		}

		$term = get_term_by( 'name', $name, JL_TAX_SKILL );
		if ( ! $term && $create_missing ) {
			$created = wp_insert_term( $name, JL_TAX_SKILL );
			if ( ! is_wp_error( $created ) ) {
				$ids[] = (int) $created['term_id'];
			}
			continue;
		}

		if ( $term ) {
			$ids[] = (int) $term->term_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/* -------------------------------------------------------------------------
 * Компании
 * ---------------------------------------------------------------------- */

function jl_get_employer_company( $user_id ) {
	$posts = get_posts(
		array(
			'post_type'      => JL_CPT_COMPANY,
			'author'         => (int) $user_id,
			'posts_per_page' => 1,
			'post_status'    => array( 'publish', 'draft' ),
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * Создаёт или обновляет карточку компании работодателя.
 */
function jl_save_company( $user_id, array $input ) {
	$user_id = (int) $user_id;
	$company = jl_get_employer_company( $user_id );

	$data = array(
		'post_type'    => JL_CPT_COMPANY,
		'post_title'   => sanitize_text_field( $input['name'] ?? '' ),
		'post_content' => wp_kses_post( $input['about'] ?? '' ),
		'post_status'  => 'publish',
		'post_author'  => $user_id,
	);

	if ( '' === $data['post_title'] ) {
		return $company ? $company->ID : 0;
	}

	if ( $company ) {
		$data['ID'] = $company->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	update_post_meta( $id, '_jl_website', esc_url_raw( $input['website'] ?? '' ) );
	update_post_meta( $id, '_jl_city', sanitize_text_field( $input['city'] ?? '' ) );

	// Адрес офиса пишем только если он пришёл: регистрация создаёт компанию без него.
	if ( array_key_exists( 'address', $input ) ) {
		update_post_meta( $id, '_jl_address', sanitize_text_field( $input['address'] ) );
	}

	return (int) $id;
}

/* -------------------------------------------------------------------------
 * Вакансии
 * ---------------------------------------------------------------------- */

function jl_vacancy_meta_fields() {
	return array(
		'_jl_company_id' => 'absint',
		'_jl_city'       => 'sanitize_text_field',
		'_jl_salary_min' => 'absint',
		'_jl_salary_max' => 'absint',
		'_jl_employment' => 'sanitize_key',
		'_jl_experience' => 'sanitize_key',
		'_jl_contact'    => 'sanitize_text_field',
		'_jl_meeting'    => 'jl_sanitize_meeting_format',
	);
}

/**
 * Оставляет только известный формат встречи, иначе пустую строку.
 */
function jl_sanitize_meeting_format( $value ) {
	$value = sanitize_key( $value );

	return array_key_exists( $value, jl_meeting_formats() ) ? $value : '';
}

function jl_get_vacancy( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$company_id = (int) get_post_meta( $post->ID, '_jl_company_id', true );

	return array(
		'post'       => $post,
		'company_id' => $company_id,
		'company'    => $company_id ? get_the_title( $company_id ) : '',
		'city'       => get_post_meta( $post->ID, '_jl_city', true ),
		'salary_min' => (int) get_post_meta( $post->ID, '_jl_salary_min', true ),
		'salary_max' => (int) get_post_meta( $post->ID, '_jl_salary_max', true ),
		'employment' => get_post_meta( $post->ID, '_jl_employment', true ),
		'experience' => get_post_meta( $post->ID, '_jl_experience', true ),
		'contact'    => get_post_meta( $post->ID, '_jl_contact', true ),
		'meeting'    => get_post_meta( $post->ID, '_jl_meeting', true ),
		'skills'     => wp_get_post_terms( $post->ID, JL_TAX_SKILL ),
		'categories' => wp_get_post_terms( $post->ID, JL_TAX_CATEGORY ),
		'banned'     => jl_vacancy_is_banned( $post ),
		'banned_at'  => (int) get_post_meta( $post->ID, '_jl_banned_at', true ),
	);
}

/**
 * Человекочитаемая зарплата.
 */
function jl_format_salary( $min, $max ) {
	$min = (int) $min;
	$max = (int) $max;

	if ( ! $min && ! $max ) {
		return 'з/п не указана';
	}
	if ( $min && $max && $min !== $max ) {
		return number_format_i18n( $min ) . ' – ' . number_format_i18n( $max ) . ' ₽';
	}

	$value = $max ?: $min;

	return ( $min && ! $max ? 'от ' : ( ! $min && $max ? 'до ' : '' ) ) . number_format_i18n( $value ) . ' ₽';
}

/* -------------------------------------------------------------------------
 * Отклики
 * ---------------------------------------------------------------------- */

function jl_has_applied( $user_id, $vacancy_id ) {
	return (bool) jl_find_application( $user_id, $vacancy_id );
}

function jl_find_application( $user_id, $vacancy_id ) {
	$posts = get_posts(
		array(
			'post_type'      => JL_CPT_APPLICATION,
			'author'         => (int) $user_id,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_key'       => '_jl_vacancy_id',
			'meta_value'     => (int) $vacancy_id,
		)
	);

	return $posts ? $posts[0] : null;
}

function jl_create_application( $user_id, $vacancy_id, $message = '' ) {
	$user_id    = (int) $user_id;
	$vacancy_id = (int) $vacancy_id;
	$vacancy    = get_post( $vacancy_id );

	if ( ! $vacancy || JL_CPT_VACANCY !== $vacancy->post_type || 'publish' !== $vacancy->post_status ) {
		return new WP_Error( 'jl_vacancy', 'Вакансия недоступна.' );
	}
	if ( jl_has_applied( $user_id, $vacancy_id ) ) {
		return new WP_Error( 'jl_duplicate', 'Вы уже откликались на эту вакансию.' );
	}

	$user = get_userdata( $user_id );
	$id   = wp_insert_post(
		array(
			'post_type'   => JL_CPT_APPLICATION,
			'post_title'  => sprintf( '%s → %s', $user ? $user->display_name : '#' . $user_id, $vacancy->post_title ),
			'post_status' => 'publish',
			'post_author' => $user_id,
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		return $id;
	}

	update_post_meta( $id, '_jl_vacancy_id', $vacancy_id );
	update_post_meta( $id, '_jl_employer_id', (int) $vacancy->post_author );
	update_post_meta( $id, '_jl_message', wp_kses_post( $message ) );
	update_post_meta( $id, '_jl_state', 'new' );

	return (int) $id;
}

/**
 * Что показать соискателю после одобрения отклика: где и как его ждут.
 *
 * @return array|null null, если отклик ещё не одобрен; иначе
 *                    ['format' => call|office|'', 'headline' => string, 'details' => string[]].
 */
function jl_get_invitation( $application ) {
	$application = get_post( $application );
	if ( ! $application || 'invited' !== get_post_meta( $application->ID, '_jl_state', true ) ) {
		return null;
	}

	$vacancy_id = (int) get_post_meta( $application->ID, '_jl_vacancy_id', true );
	$format     = $vacancy_id ? (string) get_post_meta( $vacancy_id, '_jl_meeting', true ) : '';
	$contact    = $vacancy_id ? (string) get_post_meta( $vacancy_id, '_jl_contact', true ) : '';
	$company_id = $vacancy_id ? (int) get_post_meta( $vacancy_id, '_jl_company_id', true ) : 0;
	$address    = $company_id ? (string) get_post_meta( $company_id, '_jl_address', true ) : '';

	$details = array();

	if ( 'office' === $format ) {
		$headline = 'Вас ждут на очной встрече в офисе';
		if ( $address ) {
			$details[] = 'Адрес: ' . $address;
		}
	} elseif ( 'call' === $format ) {
		$headline = 'Вас ждут на созвоне';
		if ( $contact ) {
			$details[] = 'Работодатель свяжется с вами. Контакт: ' . $contact;
		}
	} else {
		// Работодатель не указал формат: не обещаем то, чего не знаем.
		$headline = 'Работодатель одобрил ваш отклик';
		if ( $contact ) {
			$details[] = 'Формат встречи уточните по контакту: ' . $contact;
		}
	}

	return array(
		'format'   => $format,
		'headline' => $headline,
		'details'  => $details,
	);
}

function jl_get_applications_for_employer( $employer_id, $vacancy_id = 0 ) {
	$args = array(
		'post_type'      => JL_CPT_APPLICATION,
		'posts_per_page' => 100,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'   => '_jl_employer_id',
				'value' => (int) $employer_id,
			),
		),
	);

	if ( $vacancy_id ) {
		$args['meta_query'][] = array(
			'key'   => '_jl_vacancy_id',
			'value' => (int) $vacancy_id,
		);
	}

	return get_posts( $args );
}

function jl_get_applications_for_seeker( $user_id ) {
	return get_posts(
		array(
			'post_type'      => JL_CPT_APPLICATION,
			'author'         => (int) $user_id,
			'posts_per_page' => 100,
			'post_status'    => 'publish',
		)
	);
}

/* -------------------------------------------------------------------------
 * Подбор кандидатов по навыкам
 * ---------------------------------------------------------------------- */

/**
 * Ищет соискателей, чьи навыки пересекаются с переданными.
 * Сортировка — по числу совпавших навыков.
 *
 * @param int[] $skill_ids ID термов навыков; пустой массив — все соискатели.
 * @param array $args      city, limit.
 *
 * @return array[] Список ['user' => WP_User, 'matched' => int, 'skills' => int[]].
 */
function jl_find_candidates( array $skill_ids, array $args = array() ) {
	global $wpdb;

	$args      = wp_parse_args( $args, array( 'city' => '', 'limit' => 50 ) );
	$skill_ids = array_values( array_filter( array_map( 'intval', $skill_ids ) ) );
	$cap_key   = $wpdb->get_blog_prefix() . 'capabilities';
	$limit     = max( 1, min( 200, (int) $args['limit'] ) );

	$select = "SELECT u.ID, 0 AS matched";
	$join   = '';

	if ( $skill_ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $skill_ids ), '%d' ) );
		$select       = 'SELECT u.ID, COUNT(DISTINCT sk.meta_value) AS matched';
		$join         = $wpdb->prepare(
			"INNER JOIN {$wpdb->usermeta} sk
				ON sk.user_id = u.ID AND sk.meta_key = 'jl_skill' AND sk.meta_value IN ($placeholders)",
			$skill_ids
		);
	}

	$where  = array();
	$params = array();

	$where[]  = 'caps.meta_value LIKE %s';
	$params[] = '%' . $wpdb->esc_like( JL_ROLE_SEEKER ) . '%';

	$where[] = "(ban.meta_value IS NULL OR ban.meta_value = '' OR ban.meta_value = '0')";
	$where[] = "(vis.meta_value IS NULL OR vis.meta_value <> '0')";

	if ( $args['city'] ) {
		$where[]  = 'city.meta_value = %s';
		$params[] = $args['city'];
	}

	$sql = "
		$select
		FROM {$wpdb->users} u
		INNER JOIN {$wpdb->usermeta} caps ON caps.user_id = u.ID AND caps.meta_key = %s
		LEFT JOIN {$wpdb->usermeta} ban  ON ban.user_id  = u.ID AND ban.meta_key  = 'jl_banned'
		LEFT JOIN {$wpdb->usermeta} vis  ON vis.user_id  = u.ID AND vis.meta_key  = 'jl_open_to_work'
		LEFT JOIN {$wpdb->usermeta} city ON city.user_id = u.ID AND city.meta_key = 'jl_city'
		$join
		WHERE " . implode( ' AND ', $where ) . '
		GROUP BY u.ID
		ORDER BY matched DESC, u.display_name ASC
		LIMIT %d';

	$rows = $wpdb->get_results(
		$wpdb->prepare( $sql, array_merge( array( $cap_key ), $params, array( $limit ) ) )
	);

	$candidates = array();
	foreach ( (array) $rows as $row ) {
		$user = get_userdata( (int) $row->ID );
		if ( ! $user ) {
			continue;
		}
		$candidates[] = array(
			'user'    => $user,
			'matched' => (int) $row->matched,
			'skills'  => jl_get_user_skills( $user->ID ),
		);
	}

	return $candidates;
}

/**
 * Все навыки словаря, отсортированные по популярности.
 */
function jl_all_skills() {
	$terms = get_terms(
		array(
			'taxonomy'   => JL_TAX_SKILL,
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

function jl_all_categories() {
	$terms = get_terms(
		array(
			'taxonomy'   => JL_TAX_CATEGORY,
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/* -------------------------------------------------------------------------
 * Фильтры каталога вакансий
 * ---------------------------------------------------------------------- */

/**
 * Применяет к архиву вакансий фильтры из строки запроса:
 * s (текст), jl_city, jl_cat, jl_skill, jl_employment.
 */
function jl_apply_archive_filters( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_vacancy_archive = $query->is_post_type_archive( JL_CPT_VACANCY )
		|| $query->is_tax( array( JL_TAX_CATEGORY, JL_TAX_SKILL ) )
		|| ( $query->is_search() && JL_CPT_VACANCY === $query->get( 'post_type' ) );

	if ( ! $is_vacancy_archive ) {
		return;
	}

	$meta = (array) $query->get( 'meta_query' );
	$tax  = (array) $query->get( 'tax_query' );

	$city = isset( $_GET['jl_city'] ) ? sanitize_text_field( wp_unslash( $_GET['jl_city'] ) ) : '';
	if ( $city ) {
		$meta[] = array(
			'key'     => '_jl_city',
			'value'   => $city,
			'compare' => 'LIKE',
		);
	}

	$employment = isset( $_GET['jl_employment'] ) ? sanitize_key( $_GET['jl_employment'] ) : '';
	if ( $employment && array_key_exists( $employment, jl_employment_types() ) ) {
		$meta[] = array(
			'key'   => '_jl_employment',
			'value' => $employment,
		);
	}

	$category = absint( $_GET['jl_cat'] ?? 0 );
	if ( $category ) {
		$tax[] = array(
			'taxonomy' => JL_TAX_CATEGORY,
			'field'    => 'term_id',
			'terms'    => $category,
		);
	}

	$skill = absint( $_GET['jl_skill'] ?? 0 );
	if ( $skill ) {
		$tax[] = array(
			'taxonomy' => JL_TAX_SKILL,
			'field'    => 'term_id',
			'terms'    => $skill,
		);
	}

	if ( $meta ) {
		$query->set( 'meta_query', $meta );
	}
	if ( $tax ) {
		$query->set( 'tax_query', $tax );
	}
}
add_action( 'pre_get_posts', 'jl_apply_archive_filters' );

/**
 * Поиск с главной страницы ищет именно по вакансиям.
 */
function jl_search_only_vacancies( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	if ( isset( $_GET['post_type'] ) && JL_CPT_VACANCY !== $_GET['post_type'] ) {
		return;
	}

	$query->set( 'post_type', JL_CPT_VACANCY );
}
add_action( 'pre_get_posts', 'jl_search_only_vacancies', 9 );

/**
 * Число опубликованных вакансий в категории (с учётом блокировок).
 */
function jl_category_vacancy_count( $term_id ) {
	$query = new WP_Query(
		array(
			'post_type'      => JL_CPT_VACANCY,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'tax_query'      => array(
				array(
					'taxonomy' => JL_TAX_CATEGORY,
					'field'    => 'term_id',
					'terms'    => (int) $term_id,
				),
			),
		)
	);

	return (int) $query->found_posts;
}
