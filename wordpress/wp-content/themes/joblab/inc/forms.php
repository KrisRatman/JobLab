<?php
/**
 * Обработчики форм фронтенда. Все точки входа — через admin-post.php с nonce.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Общая проверка: залогинен, не забанен, нужной роли.
 */
function jl_guard( $role = '' ) {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( jl_page_url( 'login' ) );
		exit;
	}

	if ( jl_user_is_banned( get_current_user_id() ) ) {
		wp_safe_redirect( jl_page_url( 'blocked' ) );
		exit;
	}

	if ( $role && ! jl_user_has_role( wp_get_current_user(), $role ) ) {
		wp_die( 'Недостаточно прав для этого действия.', 'JobLab', array( 'response' => 403 ) );
	}
}

/* -------------------------------------------------------------------------
 * Профиль соискателя
 * ---------------------------------------------------------------------- */

function jl_handle_save_profile() {
	check_admin_referer( 'jl_save_profile' );
	jl_guard( JL_ROLE_SEEKER );

	$user_id = get_current_user_id();

	jl_save_profile(
		$user_id,
		array(
			'jl_first_name'      => wp_unslash( $_POST['first_name'] ?? '' ),
			'jl_last_name'       => wp_unslash( $_POST['last_name'] ?? '' ),
			'jl_phone'           => wp_unslash( $_POST['phone'] ?? '' ),
			'jl_city'            => wp_unslash( $_POST['city'] ?? '' ),
			'jl_position'        => wp_unslash( $_POST['position'] ?? '' ),
			'jl_salary'          => wp_unslash( $_POST['salary'] ?? 0 ),
			'jl_experience'      => wp_unslash( $_POST['experience'] ?? '' ),
			'jl_education_level' => wp_unslash( $_POST['education_level'] ?? '' ),
			'jl_education_place' => wp_unslash( $_POST['education_place'] ?? '' ),
			'jl_about'           => wp_unslash( $_POST['about'] ?? '' ),
			'jl_open_to_work'    => empty( $_POST['open_to_work'] ) ? 0 : 1,
		)
	);

	// Чекбоксы известных навыков + свободный ввод «через запятую».
	$picked = array_map( 'intval', (array) ( $_POST['skills'] ?? array() ) );
	$custom = jl_resolve_skills( wp_unslash( $_POST['skills_custom'] ?? '' ) );

	jl_set_user_skills( $user_id, array_merge( $picked, $custom ) );

	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'success', 'Профиль сохранён.' );
}
add_action( 'admin_post_jl_save_profile', 'jl_handle_save_profile' );

/* -------------------------------------------------------------------------
 * Публикация и редактирование вакансии
 * ---------------------------------------------------------------------- */

function jl_handle_save_vacancy() {
	check_admin_referer( 'jl_save_vacancy' );
	jl_guard( JL_ROLE_EMPLOYER );

	$user_id    = get_current_user_id();
	$vacancy_id = absint( $_POST['vacancy_id'] ?? 0 );
	$back       = $vacancy_id ? jl_page_url( 'vacancy-form', array( 'id' => $vacancy_id ) ) : jl_page_url( 'vacancy-form' );

	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	if ( '' === $title ) {
		jl_redirect_with_notice( $back, 'error', 'Укажите название вакансии.' );
	}

	// Компания работодателя обновляется вместе с вакансией.
	$company_id = jl_save_company(
		$user_id,
		array(
			'name'    => wp_unslash( $_POST['company'] ?? '' ),
			'city'    => wp_unslash( $_POST['city'] ?? '' ),
			'website' => wp_unslash( $_POST['company_website'] ?? '' ),
			'address' => wp_unslash( $_POST['company_address'] ?? '' ),
			'about'   => wp_unslash( $_POST['company_about'] ?? '' ),
		)
	);

	$data = array(
		'post_type'    => JL_CPT_VACANCY,
		'post_title'   => $title,
		'post_content' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
		'post_author'  => $user_id,
		'post_status'  => 'publish',
	);

	if ( $vacancy_id ) {
		$existing = get_post( $vacancy_id );
		if ( ! $existing || JL_CPT_VACANCY !== $existing->post_type || (int) $existing->post_author !== $user_id ) {
			wp_die( 'Вакансия не найдена.', 'JobLab', array( 'response' => 403 ) );
		}
		if ( jl_vacancy_is_banned( $existing ) ) {
			jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'error', jl_banned_vacancy_notice() );
		}

		$data['ID'] = $vacancy_id;
		$result     = wp_update_post( $data, true );
	} else {
		$result = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $result ) ) {
		jl_redirect_with_notice( $back, 'error', $result->get_error_message() );
	}

	$vacancy_id = (int) $result;

	$meta = array(
		'_jl_company_id' => $company_id,
		'_jl_city'       => wp_unslash( $_POST['city'] ?? '' ),
		'_jl_salary_min' => wp_unslash( $_POST['salary_min'] ?? 0 ),
		'_jl_salary_max' => wp_unslash( $_POST['salary_max'] ?? 0 ),
		'_jl_employment' => wp_unslash( $_POST['employment'] ?? '' ),
		'_jl_experience' => wp_unslash( $_POST['experience'] ?? '' ),
		'_jl_contact'    => wp_unslash( $_POST['contact'] ?? '' ),
		'_jl_meeting'    => wp_unslash( $_POST['meeting'] ?? '' ),
	);

	foreach ( jl_vacancy_meta_fields() as $key => $sanitizer ) {
		update_post_meta( $vacancy_id, $key, call_user_func( $sanitizer, $meta[ $key ] ) );
	}

	$category = absint( $_POST['category'] ?? 0 );
	wp_set_object_terms( $vacancy_id, $category ? array( $category ) : array(), JL_TAX_CATEGORY, false );

	$skills = array_merge(
		array_map( 'intval', (array) ( $_POST['skills'] ?? array() ) ),
		jl_resolve_skills( wp_unslash( $_POST['skills_custom'] ?? '' ) )
	);
	wp_set_object_terms( $vacancy_id, array_values( array_unique( $skills ) ), JL_TAX_SKILL, false );

	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'success', 'Вакансия опубликована.' );
}
add_action( 'admin_post_jl_save_vacancy', 'jl_handle_save_vacancy' );

function jl_handle_delete_vacancy() {
	check_admin_referer( 'jl_delete_vacancy' );
	jl_guard( JL_ROLE_EMPLOYER );

	$vacancy_id = absint( $_POST['vacancy_id'] ?? 0 );
	$vacancy    = get_post( $vacancy_id );

	if ( ! $vacancy || JL_CPT_VACANCY !== $vacancy->post_type || (int) $vacancy->post_author !== get_current_user_id() ) {
		wp_die( 'Вакансия не найдена.', 'JobLab', array( 'response' => 403 ) );
	}

	wp_trash_post( $vacancy_id );

	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'success', 'Вакансия снята с публикации.' );
}
add_action( 'admin_post_jl_delete_vacancy', 'jl_handle_delete_vacancy' );

/* -------------------------------------------------------------------------
 * Отклик соискателя
 * ---------------------------------------------------------------------- */

function jl_handle_apply() {
	check_admin_referer( 'jl_apply' );
	jl_guard( JL_ROLE_SEEKER );

	$vacancy_id = absint( $_POST['vacancy_id'] ?? 0 );
	$back       = get_permalink( $vacancy_id ) ?: jl_page_url( 'cabinet' );

	$result = jl_create_application(
		get_current_user_id(),
		$vacancy_id,
		wp_unslash( $_POST['message'] ?? '' )
	);

	if ( is_wp_error( $result ) ) {
		jl_redirect_with_notice( $back, 'error', $result->get_error_message() );
	}

	jl_redirect_with_notice( $back, 'success', 'Отклик отправлен работодателю.' );
}
add_action( 'admin_post_jl_apply', 'jl_handle_apply' );

/* -------------------------------------------------------------------------
 * Работодатель меняет статус отклика
 * ---------------------------------------------------------------------- */

function jl_handle_application_state() {
	check_admin_referer( 'jl_application_state' );
	jl_guard( JL_ROLE_EMPLOYER );

	$application_id = absint( $_POST['application_id'] ?? 0 );
	$state          = sanitize_key( $_POST['state'] ?? '' );
	$application    = get_post( $application_id );

	if ( ! $application || JL_CPT_APPLICATION !== $application->post_type ) {
		wp_die( 'Отклик не найден.', 'JobLab', array( 'response' => 404 ) );
	}
	if ( (int) get_post_meta( $application_id, '_jl_employer_id', true ) !== get_current_user_id() ) {
		wp_die( 'Недостаточно прав.', 'JobLab', array( 'response' => 403 ) );
	}
	if ( ! array_key_exists( $state, jl_application_statuses() ) ) {
		$state = 'viewed';
	}

	update_post_meta( $application_id, '_jl_state', $state );

	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'success', 'Статус отклика обновлён.' );
}
add_action( 'admin_post_jl_application_state', 'jl_handle_application_state' );
