<?php
/**
 * Template Name: JobLab — форма вакансии
 */

defined( 'ABSPATH' ) || exit;

jl_require_login();

if ( ! jl_is_employer() ) {
	wp_safe_redirect( jl_page_url( 'cabinet' ) );
	exit;
}

$jl_user    = wp_get_current_user();
$jl_company = jl_get_employer_company( $jl_user->ID );

$jl_id      = absint( $_GET['id'] ?? 0 );
$jl_post    = $jl_id ? get_post( $jl_id ) : null;
$jl_editing = $jl_post && JL_CPT_VACANCY === $jl_post->post_type && (int) $jl_post->post_author === $jl_user->ID;

if ( $jl_id && ! $jl_editing ) {
	wp_die( 'Вакансия не найдена.', 'JobLab', array( 'response' => 404 ) );
}
if ( $jl_editing && jl_vacancy_is_banned( $jl_post ) ) {
	jl_redirect_with_notice( jl_page_url( 'cabinet' ), 'error', jl_banned_vacancy_notice() );
}

$jl_vacancy = $jl_editing ? jl_get_vacancy( $jl_post ) : null;
$jl_cat_id  = $jl_vacancy && $jl_vacancy['categories'] ? (int) $jl_vacancy['categories'][0]->term_id : 0;
$jl_skills  = $jl_vacancy ? wp_list_pluck( $jl_vacancy['skills'], 'term_id' ) : array();

get_header();
?>

<section class="max-w-3xl mx-auto px-6 py-12">
	<h1 class="text-3xl font-extrabold"><?php echo $jl_editing ? 'Редактирование вакансии' : 'Разместить вакансию'; ?></h1>
	<p class="mt-2 text-slate-600 text-sm">
		Заявка публикуется сразу. Модератор может заблокировать её, если она нарушает правила площадки.
	</p>

	<div class="mt-8">
		<?php jl_the_notice(); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6">
			<?php wp_nonce_field( 'jl_save_vacancy' ); ?>
			<input type="hidden" name="action" value="jl_save_vacancy">
			<input type="hidden" name="vacancy_id" value="<?php echo (int) ( $jl_editing ? $jl_post->ID : 0 ); ?>">

			<div class="border border-slate-200 rounded-2xl p-6 space-y-4">
				<h2 class="font-semibold text-lg">О вакансии</h2>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-title">Название должности</label>
					<input id="jl-title" name="title" type="text" required class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_editing ? $jl_post->post_title : '' ); ?>"
						placeholder="Например: Senior Frontend Developer">
				</div>

				<div class="grid sm:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-category">Категория</label>
						<select id="jl-category" name="category" class="<?php echo esc_attr( jl_input_class() ); ?>">
							<option value="">Не выбрана</option>
							<?php foreach ( jl_all_categories() as $jl_term ) : ?>
								<option value="<?php echo (int) $jl_term->term_id; ?>" <?php selected( $jl_cat_id, $jl_term->term_id ); ?>>
									<?php echo esc_html( $jl_term->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-city">Город</label>
						<input id="jl-city" name="city" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
							value="<?php echo esc_attr( $jl_vacancy ? $jl_vacancy['city'] : ( $jl_company ? get_post_meta( $jl_company->ID, '_jl_city', true ) : '' ) ); ?>">
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-salary-min">Зарплата от, ₽</label>
						<input id="jl-salary-min" name="salary_min" type="number" min="0" step="1000"
							class="<?php echo esc_attr( jl_input_class() ); ?>"
							value="<?php echo esc_attr( $jl_vacancy ? $jl_vacancy['salary_min'] : '' ); ?>">
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-salary-max">Зарплата до, ₽</label>
						<input id="jl-salary-max" name="salary_max" type="number" min="0" step="1000"
							class="<?php echo esc_attr( jl_input_class() ); ?>"
							value="<?php echo esc_attr( $jl_vacancy ? $jl_vacancy['salary_max'] : '' ); ?>">
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-employment">Тип занятости</label>
						<select id="jl-employment" name="employment" class="<?php echo esc_attr( jl_input_class() ); ?>">
							<?php foreach ( jl_employment_types() as $jl_key => $jl_label ) : ?>
								<option value="<?php echo esc_attr( $jl_key ); ?>" <?php selected( $jl_vacancy ? $jl_vacancy['employment'] : 'full', $jl_key ); ?>>
									<?php echo esc_html( $jl_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-experience">Требуемый опыт</label>
						<select id="jl-experience" name="experience" class="<?php echo esc_attr( jl_input_class() ); ?>">
							<?php foreach ( jl_experience_levels() as $jl_key => $jl_label ) : ?>
								<option value="<?php echo esc_attr( $jl_key ); ?>" <?php selected( $jl_vacancy ? $jl_vacancy['experience'] : 'none', $jl_key ); ?>>
									<?php echo esc_html( $jl_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-description">Описание</label>
					<textarea id="jl-description" name="description" rows="8" class="<?php echo esc_attr( jl_input_class() ); ?>"
						placeholder="Задачи, условия, что важно для вас в кандидате."><?php echo esc_textarea( $jl_editing ? $jl_post->post_content : '' ); ?></textarea>
				</div>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-contact">Контакт для связи</label>
					<input id="jl-contact" name="contact" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_vacancy ? $jl_vacancy['contact'] : $jl_user->user_email ); ?>">
				</div>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-meeting">Где вы ждёте кандидата после одобрения отклика</label>
					<select id="jl-meeting" name="meeting" class="<?php echo esc_attr( jl_input_class() ); ?>">
						<option value="">Не указано</option>
						<?php foreach ( jl_meeting_formats() as $jl_key => $jl_label ) : ?>
							<option value="<?php echo esc_attr( $jl_key ); ?>" <?php selected( $jl_vacancy ? $jl_vacancy['meeting'] : '', $jl_key ); ?>>
								<?php echo esc_html( $jl_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="text-xs text-slate-500 mt-1.5">
						Соискатель увидит это в кабинете, когда вы одобрите его отклик. Для очной встречи покажем адрес офиса из карточки компании.
					</p>
				</div>
			</div>

			<div class="border border-slate-200 rounded-2xl p-6">
				<h2 class="font-semibold text-lg">Требуемые навыки</h2>
				<p class="text-sm text-slate-500 mt-1">
					По этим же тегам система подберёт вам зарегистрированных кандидатов.
				</p>
				<div class="mt-4">
					<?php jl_the_skill_picker( $jl_skills ); ?>
				</div>
			</div>

			<div class="border border-slate-200 rounded-2xl p-6 space-y-4">
				<h2 class="font-semibold text-lg">Компания</h2>

				<div class="grid sm:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-company">Название</label>
						<input id="jl-company" name="company" type="text" required class="<?php echo esc_attr( jl_input_class() ); ?>"
							value="<?php echo esc_attr( $jl_company ? $jl_company->post_title : '' ); ?>">
					</div>
					<div>
						<label class="block text-sm font-medium mb-1.5" for="jl-website">Сайт</label>
						<input id="jl-website" name="company_website" type="url" class="<?php echo esc_attr( jl_input_class() ); ?>"
							value="<?php echo esc_attr( $jl_company ? get_post_meta( $jl_company->ID, '_jl_website', true ) : '' ); ?>">
					</div>
				</div>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-address">Адрес офиса</label>
					<input id="jl-address" name="company_address" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_company ? get_post_meta( $jl_company->ID, '_jl_address', true ) : '' ); ?>"
						placeholder="Город, улица, дом, этаж">
				</div>

				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-company-about">О компании</label>
					<textarea id="jl-company-about" name="company_about" rows="4" class="<?php echo esc_attr( jl_input_class() ); ?>"><?php echo esc_textarea( $jl_company ? $jl_company->post_content : '' ); ?></textarea>
				</div>
			</div>

			<div class="flex items-center gap-4">
				<button class="bg-brand hover:bg-brand-dark transition text-white font-medium px-8 py-3 rounded-xl text-sm">
					<?php echo $jl_editing ? 'Сохранить изменения' : 'Опубликовать вакансию'; ?>
				</button>
				<a href="<?php echo esc_url( jl_page_url( 'cabinet' ) ); ?>" class="text-sm text-slate-500 hover:text-slate-900">Отмена</a>
			</div>
		</form>
	</div>
</section>

<?php get_footer(); ?>
