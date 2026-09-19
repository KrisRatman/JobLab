<?php
/**
 * Экраны администратора: сводка модератора, поля вакансии, профиль работника.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Меню модератора
 * ---------------------------------------------------------------------- */

function jl_admin_menu() {
	add_menu_page(
		'JobLab',
		'JobLab',
		'jl_moderate',
		'joblab',
		'jl_render_dashboard',
		'dashicons-shield-alt',
		24
	);

	add_submenu_page( 'joblab', 'Сводка', 'Сводка', 'jl_moderate', 'joblab', 'jl_render_dashboard' );
	add_submenu_page( 'joblab', 'Тестовые данные', 'Тестовые данные', 'manage_options', 'joblab-seed', 'jl_render_seed_page' );
}
add_action( 'admin_menu', 'jl_admin_menu' );

function jl_render_dashboard() {
	$vacancies = wp_count_posts( JL_CPT_VACANCY );
	$active    = isset( $vacancies->publish ) ? (int) $vacancies->publish : 0;
	$banned    = isset( $vacancies->{JL_STATUS_BANNED} ) ? (int) $vacancies->{JL_STATUS_BANNED} : 0;

	$seekers   = count( get_users( array( 'role' => JL_ROLE_SEEKER, 'fields' => 'ID' ) ) );
	$employers = count( get_users( array( 'role' => JL_ROLE_EMPLOYER, 'fields' => 'ID' ) ) );
	$blocked   = count( jl_banned_user_ids() );

	$applications = wp_count_posts( JL_CPT_APPLICATION );
	$next_cron    = wp_next_scheduled( 'jl_cleanup_banned' );

	$cards = array(
		'Активные вакансии'          => $active,
		'Заблокированные вакансии'   => $banned,
		'Соискатели'                 => $seekers,
		'Работодатели'               => $employers,
		'Заблокированные аккаунты'   => $blocked,
		'Отклики'                    => isset( $applications->publish ) ? (int) $applications->publish : 0,
	);
	?>
	<div class="wrap">
		<h1>JobLab — модерация</h1>

		<div style="display:flex;flex-wrap:wrap;gap:16px;margin:24px 0">
			<?php foreach ( $cards as $label => $value ) : ?>
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:16px 20px;min-width:180px">
					<div style="font-size:28px;font-weight:700"><?php echo (int) $value; ?></div>
					<div style="color:#50575e"><?php echo esc_html( $label ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . JL_CPT_VACANCY ) ); ?>">Заявки на вакансии</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">Зарегистрированные работники</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . JL_CPT_APPLICATION ) ); ?>">Отклики</a>
		</p>

		<h2>Правила автоочистки</h2>
		<p>
			Заблокированные вакансии и аккаунты удаляются из базы через
			<strong><?php echo (int) JL_BAN_TTL_DAYS; ?> дней</strong> после блокировки.
			<?php if ( $next_cron ) : ?>
				Ближайшая проверка: <?php echo esc_html( wp_date( 'd.m.Y H:i', $next_cron ) ); ?>.
			<?php else : ?>
				<em>Задание cron не запланировано.</em>
			<?php endif; ?>
		</p>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Поля вакансии в админке
 * ---------------------------------------------------------------------- */

function jl_add_meta_boxes() {
	add_meta_box( 'jl_vacancy_details', 'Параметры вакансии', 'jl_render_vacancy_box', JL_CPT_VACANCY, 'normal', 'high' );
	add_meta_box( 'jl_application_details', 'Отклик', 'jl_render_application_box', JL_CPT_APPLICATION, 'normal', 'high' );
	add_meta_box( 'jl_company_details', 'Адрес офиса', 'jl_render_company_box', JL_CPT_COMPANY, 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'jl_add_meta_boxes' );

function jl_render_company_box( $post ) {
	wp_nonce_field( 'jl_company_box', 'jl_company_box_nonce' );
	?>
	<p>
		<input type="text" class="large-text" name="_jl_address"
			value="<?php echo esc_attr( get_post_meta( $post->ID, '_jl_address', true ) ); ?>"
			placeholder="Город, улица, дом, этаж">
	</p>
	<p class="description">Показывается соискателю, когда работодатель одобрил отклик и ждёт на очной встрече.</p>
	<?php
}

function jl_save_company_box( $post_id ) {
	if ( ! isset( $_POST['jl_company_box_nonce'] ) || ! wp_verify_nonce( $_POST['jl_company_box_nonce'], 'jl_company_box' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_jl_address', sanitize_text_field( wp_unslash( $_POST['_jl_address'] ?? '' ) ) );
}
add_action( 'save_post_' . JL_CPT_COMPANY, 'jl_save_company_box' );

function jl_render_vacancy_box( $post ) {
	wp_nonce_field( 'jl_vacancy_box', 'jl_vacancy_box_nonce' );

	$vacancy = jl_get_vacancy( $post );
	$fields  = array(
		'_jl_city'       => array( 'Город', 'text' ),
		'_jl_salary_min' => array( 'Зарплата от', 'number' ),
		'_jl_salary_max' => array( 'Зарплата до', 'number' ),
		'_jl_contact'    => array( 'Контакт', 'text' ),
	);
	?>
	<table class="form-table">
		<?php foreach ( $fields as $key => $field ) : ?>
			<tr>
				<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
				<td>
					<input type="<?php echo esc_attr( $field[1] ); ?>" class="regular-text"
						id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>">
				</td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th><label for="_jl_employment">Тип занятости</label></th>
			<td>
				<select id="_jl_employment" name="_jl_employment">
					<option value="">—</option>
					<?php foreach ( jl_employment_types() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $vacancy['employment'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="_jl_experience">Опыт</label></th>
			<td>
				<select id="_jl_experience" name="_jl_experience">
					<option value="">—</option>
					<?php foreach ( jl_experience_levels() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $vacancy['experience'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Компания</th>
			<td><?php echo $vacancy['company'] ? esc_html( $vacancy['company'] ) : '—'; ?></td>
		</tr>
		<tr>
			<th><label for="_jl_meeting">Где ждём после одобрения отклика</label></th>
			<td>
				<select id="_jl_meeting" name="_jl_meeting">
					<option value="">Не указано</option>
					<?php foreach ( jl_meeting_formats() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $vacancy['meeting'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>

	<?php if ( $vacancy['banned'] ) : ?>
		<p style="background:#fcf0f1;border-left:4px solid #b32d2e;padding:12px">
			Заявка заблокирована. Работодатель видит сообщение:
			«<?php echo esc_html( jl_banned_vacancy_notice() ); ?>».
			Удаление из базы через <?php echo (int) jl_days_until_purge( $vacancy['banned_at'] ); ?> дн.
			<a class="button" href="<?php echo esc_url( jl_moderation_link( 'jl_unban_vacancy', $post->ID ) ); ?>">Снять блокировку</a>
		</p>
	<?php else : ?>
		<p>
			<a class="button" href="<?php echo esc_url( jl_moderation_link( 'jl_ban_vacancy', $post->ID ) ); ?>">Заблокировать заявку</a>
		</p>
	<?php endif; ?>
	<?php
}

function jl_save_vacancy_box( $post_id ) {
	if ( ! isset( $_POST['jl_vacancy_box_nonce'] ) || ! wp_verify_nonce( $_POST['jl_vacancy_box_nonce'], 'jl_vacancy_box' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( jl_vacancy_meta_fields() as $key => $sanitizer ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		update_post_meta( $post_id, $key, call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) ) );
	}
}
add_action( 'save_post_' . JL_CPT_VACANCY, 'jl_save_vacancy_box' );

function jl_render_application_box( $post ) {
	$vacancy_id = (int) get_post_meta( $post->ID, '_jl_vacancy_id', true );
	$state      = get_post_meta( $post->ID, '_jl_state', true );
	?>
	<table class="form-table">
		<tr>
			<th>Вакансия</th>
			<td>
				<?php if ( $vacancy_id ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $vacancy_id ) ); ?>"><?php echo esc_html( get_the_title( $vacancy_id ) ); ?></a>
				<?php else : ?>
					—
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th>Соискатель</th>
			<td><?php echo esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); ?></td>
		</tr>
		<tr>
			<th>Статус</th>
			<td><?php echo esc_html( jl_label( jl_application_statuses(), $state ) ?: '—' ); ?></td>
		</tr>
		<tr>
			<th>Сообщение</th>
			<td><?php echo wp_kses_post( wpautop( get_post_meta( $post->ID, '_jl_message', true ) ) ); ?></td>
		</tr>
	</table>
	<?php
}

/* -------------------------------------------------------------------------
 * Профиль работника в карточке пользователя
 * ---------------------------------------------------------------------- */

function jl_render_user_profile( $user ) {
	if ( ! jl_is_seeker( $user ) && ! jl_is_employer( $user ) ) {
		return;
	}

	$profile = jl_get_profile( $user->ID );
	$skills  = array_filter( array_map(
		static function ( $id ) {
			$term = get_term( $id, JL_TAX_SKILL );

			return $term && ! is_wp_error( $term ) ? $term->name : null;
		},
		$profile['skills']
	) );
	?>
	<h2>Профиль JobLab</h2>
	<table class="form-table">
		<tr><th>Город</th><td><?php echo esc_html( $profile['jl_city'] ?: '—' ); ?></td></tr>
		<tr><th>Желаемая должность</th><td><?php echo esc_html( $profile['jl_position'] ?: '—' ); ?></td></tr>
		<tr><th>Опыт</th><td><?php echo esc_html( jl_label( jl_experience_levels(), $profile['jl_experience'] ) ?: '—' ); ?></td></tr>
		<tr><th>Образование</th><td><?php echo esc_html( trim( jl_label( jl_education_levels(), $profile['jl_education_level'] ) . ' ' . $profile['jl_education_place'] ) ?: '—' ); ?></td></tr>
		<tr><th>Навыки</th><td><?php echo $skills ? esc_html( implode( ', ', $skills ) ) : '—'; ?></td></tr>
		<tr><th>О себе</th><td><?php echo wp_kses_post( wpautop( $profile['jl_about'] ) ); ?></td></tr>
		<tr>
			<th>Модерация</th>
			<td>
				<?php if ( jl_user_is_banned( $user->ID ) ) : ?>
					<p style="color:#b32d2e"><strong>Заблокирован.</strong>
						Удаление через <?php echo (int) jl_days_until_purge( get_user_meta( $user->ID, 'jl_banned_at', true ) ); ?> дн.</p>
					<a class="button" href="<?php echo esc_url( jl_moderation_link( 'jl_unban_user', $user->ID ) ); ?>">Снять блокировку</a>
				<?php else : ?>
					<a class="button" href="<?php echo esc_url( jl_moderation_link( 'jl_ban_user', $user->ID ) ); ?>">Заблокировать</a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'jl_render_user_profile' );
add_action( 'edit_user_profile', 'jl_render_user_profile' );

/* -------------------------------------------------------------------------
 * Тестовые данные
 * ---------------------------------------------------------------------- */

function jl_render_seed_page() {
	?>
	<div class="wrap">
		<h1>Тестовые данные JobLab</h1>
		<p>Создаёт категории, навыки, компании, вакансии, работников и отклики для проверки платформы.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'jl_seed' ); ?>
			<input type="hidden" name="action" value="jl_seed">
			<p>
				<button class="button button-primary" name="mode" value="seed">Создать тестовые данные</button>
				<button class="button" name="mode" value="reset" onclick="return confirm('Удалить все вакансии, компании, отклики и тестовых пользователей?')">
					Удалить все данные JobLab
				</button>
			</p>
		</form>
	</div>
	<?php
}

function jl_handle_seed() {
	check_admin_referer( 'jl_seed' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	$mode = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'seed';

	if ( 'reset' === $mode ) {
		jl_seed_reset();
	} else {
		jl_seed_run();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=joblab' ) );
	exit;
}
add_action( 'admin_post_jl_seed', 'jl_handle_seed' );
