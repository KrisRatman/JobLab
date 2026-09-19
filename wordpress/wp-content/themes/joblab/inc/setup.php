<?php
/**
 * Ядро бэкенда JobLab: типы записей, таксономии, роли, статусы, страницы.
 */

defined( 'ABSPATH' ) || exit;

const JL_VERSION = '1.0.0';

const JL_CPT_VACANCY     = 'jl_vacancy';
const JL_CPT_COMPANY     = 'jl_company';
const JL_CPT_APPLICATION = 'jl_application';

const JL_TAX_CATEGORY = 'jl_category';
const JL_TAX_SKILL    = 'jl_skill';

const JL_STATUS_BANNED = 'jl_banned';

const JL_ROLE_SEEKER   = 'jl_seeker';
const JL_ROLE_EMPLOYER = 'jl_employer';

/** Через столько дней забаненные вакансии и работники удаляются из базы. */
const JL_BAN_TTL_DAYS = 14;

/**
 * Справочники значений. Ключ хранится в базе, значение выводится пользователю.
 */
function jl_employment_types() {
	return array(
		'full'   => 'Полный день',
		'part'   => 'Частичная занятость',
		'remote' => 'Удалённо',
		'flex'   => 'Гибкий график',
		'shift'  => 'Сменный график',
	);
}

function jl_experience_levels() {
	return array(
		'none' => 'Без опыта',
		'1-3'  => 'От 1 года до 3 лет',
		'3-6'  => 'От 3 до 6 лет',
		'6+'   => 'Более 6 лет',
	);
}

function jl_education_levels() {
	return array(
		'secondary'  => 'Среднее',
		'vocational' => 'Среднее специальное',
		'unfinished' => 'Неоконченное высшее',
		'higher'     => 'Высшее',
		'master'     => 'Магистратура',
		'phd'        => 'Учёная степень',
	);
}

function jl_application_statuses() {
	return array(
		'new'      => 'Новый отклик',
		'viewed'   => 'Просмотрен',
		'invited'  => 'Приглашение',
		'rejected' => 'Отказ',
	);
}

/**
 * Где работодатель ждёт соискателя после одобрения отклика.
 */
function jl_meeting_formats() {
	return array(
		'call'   => 'Созвон',
		'office' => 'Очная встреча в офисе',
	);
}

/**
 * Возвращает подпись из справочника либо пустую строку.
 */
function jl_label( array $dictionary, $key ) {
	return isset( $dictionary[ $key ] ) ? $dictionary[ $key ] : '';
}

/* -------------------------------------------------------------------------
 * Типы записей и таксономии
 * ---------------------------------------------------------------------- */

function jl_register_post_types() {
	register_post_type(
		JL_CPT_COMPANY,
		array(
			'labels'        => array(
				'name'               => 'Компании',
				'singular_name'      => 'Компания',
				'add_new_item'       => 'Добавить компанию',
				'edit_item'          => 'Редактировать компанию',
				'search_items'       => 'Искать компании',
				'not_found'          => 'Компании не найдены',
			),
			'public'        => true,
			'has_archive'   => 'companies',
			'rewrite'       => array( 'slug' => 'company', 'with_front' => false ),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'author' ),
			'show_in_rest'  => false,
		)
	);

	register_post_type(
		JL_CPT_VACANCY,
		array(
			'labels'          => array(
				'name'          => 'Вакансии',
				'singular_name' => 'Вакансия',
				'add_new_item'  => 'Добавить вакансию',
				'edit_item'     => 'Редактировать вакансию',
				'search_items'  => 'Искать вакансии',
				'not_found'     => 'Вакансии не найдены',
				'all_items'     => 'Все вакансии',
			),
			'public'          => true,
			'has_archive'     => 'vacancies',
			'rewrite'         => array( 'slug' => 'vacancy', 'with_front' => false ),
			'menu_icon'       => 'dashicons-portfolio',
			'menu_position'   => 25,
			'supports'        => array( 'title', 'editor', 'author' ),
			'taxonomies'      => array( JL_TAX_CATEGORY, JL_TAX_SKILL ),
			'show_in_rest'    => false,
			// Собственные права: работодатель управляет только своими вакансиями
			// и может открыть собственную заблокированную заявку.
			'capability_type' => array( 'jl_vacancy', 'jl_vacancies' ),
			'map_meta_cap'    => true,
		)
	);

	register_post_type(
		JL_CPT_APPLICATION,
		array(
			'labels'             => array(
				'name'          => 'Отклики',
				'singular_name' => 'Отклик',
				'edit_item'     => 'Отклик',
				'not_found'     => 'Откликов нет',
				'all_items'     => 'Все отклики',
			),
			'public'             => false,
			'show_ui'            => true,
			'publicly_queryable' => false,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-email-alt',
			'menu_position'      => 27,
			'supports'           => array( 'title', 'author' ),
			'show_in_rest'       => false,
		)
	);
}
add_action( 'init', 'jl_register_post_types', 5 );

function jl_register_taxonomies() {
	register_taxonomy(
		JL_TAX_CATEGORY,
		array( JL_CPT_VACANCY ),
		array(
			'labels'            => array(
				'name'          => 'Категории вакансий',
				'singular_name' => 'Категория',
				'add_new_item'  => 'Добавить категорию',
				'search_items'  => 'Искать категории',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'jobs', 'with_front' => false ),
			'show_in_rest'      => false,
		)
	);

	// Навыки одновременно висят на вакансиях и (через мета-поля) на работниках.
	register_taxonomy(
		JL_TAX_SKILL,
		array( JL_CPT_VACANCY ),
		array(
			'labels'            => array(
				'name'          => 'Навыки',
				'singular_name' => 'Навык',
				'add_new_item'  => 'Добавить навык',
				'search_items'  => 'Искать навыки',
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'skill', 'with_front' => false ),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'jl_register_taxonomies', 4 );

/**
 * Статус «заблокировано». Защищённый статус: автор видит свою запись,
 * остальные — нет, из публичных выборок она выпадает.
 */
function jl_register_post_status() {
	register_post_status(
		JL_STATUS_BANNED,
		array(
			'label'                     => 'Заблокировано',
			'public'                    => false,
			'internal'                  => false,
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'Заблокировано <span class="count">(%s)</span>',
				'Заблокировано <span class="count">(%s)</span>'
			),
		)
	);
}
add_action( 'init', 'jl_register_post_status', 6 );

/* -------------------------------------------------------------------------
 * Роли и права
 * ---------------------------------------------------------------------- */

/**
 * Полный набор прав на вакансии — для администратора.
 */
function jl_vacancy_caps_admin() {
	return array(
		'edit_jl_vacancy',
		'read_jl_vacancy',
		'delete_jl_vacancy',
		'edit_jl_vacancies',
		'edit_others_jl_vacancies',
		'publish_jl_vacancies',
		'read_private_jl_vacancies',
		'delete_jl_vacancies',
		'delete_private_jl_vacancies',
		'delete_published_jl_vacancies',
		'delete_others_jl_vacancies',
		'edit_private_jl_vacancies',
		'edit_published_jl_vacancies',
	);
}

/**
 * Урезанный набор — работодатель трогает только свои вакансии.
 */
function jl_vacancy_caps_employer() {
	return array(
		'edit_jl_vacancies',
		'edit_published_jl_vacancies',
		'publish_jl_vacancies',
		'delete_jl_vacancies',
		'delete_published_jl_vacancies',
	);
}

function jl_install_roles() {
	add_role( JL_ROLE_SEEKER, 'Соискатель', array( 'read' => true ) );
	add_role( JL_ROLE_EMPLOYER, 'Работодатель', array( 'read' => true ) );

	$employer = get_role( JL_ROLE_EMPLOYER );
	if ( $employer ) {
		$employer->add_cap( 'read' );
		foreach ( jl_vacancy_caps_employer() as $cap ) {
			$employer->add_cap( $cap );
		}
	}

	$seeker = get_role( JL_ROLE_SEEKER );
	if ( $seeker ) {
		$seeker->add_cap( 'read' );
	}

	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}
		foreach ( jl_vacancy_caps_admin() as $cap ) {
			$role->add_cap( $cap );
		}
		$role->add_cap( 'jl_moderate' );
	}
}

/* -------------------------------------------------------------------------
 * Служебные страницы
 * ---------------------------------------------------------------------- */

/**
 * ключ => [заголовок, слаг, шаблон]
 */
function jl_pages_map() {
	return array(
		'login'      => array( 'Вход', 'login', 'templates/page-login.php' ),
		'register'   => array( 'Регистрация', 'register', 'templates/page-register.php' ),
		'cabinet'    => array( 'Личный кабинет', 'cabinet', 'templates/page-cabinet.php' ),
		'vacancy-form' => array( 'Разместить вакансию', 'vacancy-form', 'templates/page-vacancy-form.php' ),
		'candidates' => array( 'Подбор кандидатов', 'candidates', 'templates/page-candidates.php' ),
		'blocked'    => array( 'Доступ ограничен', 'blocked', 'templates/page-blocked.php' ),
	);
}

function jl_install_pages() {
	$ids = get_option( 'jl_pages', array() );

	foreach ( jl_pages_map() as $key => $page ) {
		list( $title, $slug, $template ) = $page;

		$existing = isset( $ids[ $key ] ) ? get_post( $ids[ $key ] ) : null;
		if ( ! $existing || 'trash' === $existing->post_status ) {
			$existing = get_page_by_path( $slug );
		}

		if ( $existing ) {
			$id = $existing->ID;
			if ( 'publish' !== $existing->post_status ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			}
		} else {
			$id = wp_insert_post(
				array(
					'post_title'     => $title,
					'post_name'      => $slug,
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_content'   => '',
					'comment_status' => 'closed',
				)
			);
		}

		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}

		update_post_meta( $id, '_wp_page_template', $template );
		update_post_meta( $id, '_jl_page', $key );
		$ids[ $key ] = (int) $id;
	}

	update_option( 'jl_pages', $ids );
}

/**
 * URL служебной страницы по ключу.
 */
function jl_page_url( $key, array $args = array() ) {
	$ids = get_option( 'jl_pages', array() );
	$url = isset( $ids[ $key ] ) ? get_permalink( $ids[ $key ] ) : home_url( '/' );

	if ( ! $url ) {
		$url = home_url( '/' );
	}

	return $args ? add_query_arg( $args, $url ) : $url;
}

function jl_is_page( $key ) {
	$ids = get_option( 'jl_pages', array() );

	return isset( $ids[ $key ] ) && is_page( $ids[ $key ] );
}

/* -------------------------------------------------------------------------
 * Установка
 * ---------------------------------------------------------------------- */

function jl_install() {
	jl_register_post_types();
	jl_register_taxonomies();
	jl_install_roles();
	jl_install_pages();

	// Без ЧПУ архивы и одиночные страницы вакансий недоступны.
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	flush_rewrite_rules();

	if ( ! wp_next_scheduled( 'jl_cleanup_banned' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'jl_cleanup_banned' );
	}

	update_option( 'jl_version', JL_VERSION );
}
add_action( 'after_switch_theme', 'jl_install' );

/**
 * Догоняющая установка — чтобы не переключать тему вручную после обновления кода.
 */
function jl_maybe_install() {
	if ( get_option( 'jl_version' ) !== JL_VERSION ) {
		jl_install();
	}
}
add_action( 'admin_init', 'jl_maybe_install' );
