<?php
/**
 * Кабинет соискателя: профиль, навыки, образование, свои отклики.
 */

defined( 'ABSPATH' ) || exit;

$jl_user    = $args['user'] ?? wp_get_current_user();
$jl_profile = jl_get_profile( $jl_user->ID );
$jl_replies = jl_get_applications_for_seeker( $jl_user->ID );
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
	<div>
		<h1 class="text-3xl font-extrabold">Мой профиль</h1>
		<p class="mt-2 text-slate-600 text-sm">
			Чем подробнее заполнен профиль, тем чаще вас находят работодатели по навыкам.
		</p>
	</div>
	<div class="flex items-center gap-3">
		<a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
			class="text-sm font-medium text-brand hover:text-brand-dark">Смотреть вакансии</a>
		<?php jl_the_logout_button( 'text-sm font-medium text-slate-500 hover:text-slate-900' ); ?>
	</div>
</div>

<div class="grid lg:grid-cols-3 gap-8 mt-8">
	<!-- Форма профиля -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lg:col-span-2 space-y-6">
		<?php wp_nonce_field( 'jl_save_profile' ); ?>
		<input type="hidden" name="action" value="jl_save_profile">

		<div class="border border-slate-200 rounded-2xl p-6">
			<h2 class="font-semibold text-lg">Основное</h2>

			<div class="grid sm:grid-cols-2 gap-4 mt-4">
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-first">Имя</label>
					<input id="jl-first" name="first_name" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_first_name'] ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-last">Фамилия</label>
					<input id="jl-last" name="last_name" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_last_name'] ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-phone">Телефон</label>
					<input id="jl-phone" name="phone" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_phone'] ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-city">Город</label>
					<input id="jl-city" name="city" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_city'] ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-position">Желаемая должность</label>
					<input id="jl-position" name="position" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_position'] ); ?>">
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-salary">Желаемая зарплата, ₽</label>
					<input id="jl-salary" name="salary" type="number" min="0" step="1000" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_salary'] ); ?>">
				</div>
			</div>
		</div>

		<div class="border border-slate-200 rounded-2xl p-6">
			<h2 class="font-semibold text-lg">Опыт и образование</h2>

			<div class="grid sm:grid-cols-2 gap-4 mt-4">
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-exp">Опыт работы</label>
					<select id="jl-exp" name="experience" class="<?php echo esc_attr( jl_input_class() ); ?>">
						<option value="">Не указан</option>
						<?php foreach ( jl_experience_levels() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $jl_profile['jl_experience'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-edu">Образование</label>
					<select id="jl-edu" name="education_level" class="<?php echo esc_attr( jl_input_class() ); ?>">
						<option value="">Не указано</option>
						<?php foreach ( jl_education_levels() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $jl_profile['jl_education_level'], $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="sm:col-span-2">
					<label class="block text-sm font-medium mb-1.5" for="jl-edu-place">Учебное заведение и специальность</label>
					<input id="jl-edu-place" name="education_place" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
						value="<?php echo esc_attr( $jl_profile['jl_education_place'] ); ?>">
				</div>
			</div>
		</div>

		<div class="border border-slate-200 rounded-2xl p-6">
			<h2 class="font-semibold text-lg">Навыки</h2>
			<p class="text-sm text-slate-500 mt-1">По этим тегам работодатели подбирают кандидатов.</p>

			<div class="mt-4">
				<?php jl_the_skill_picker( $jl_profile['skills'] ); ?>
			</div>
		</div>

		<div class="border border-slate-200 rounded-2xl p-6">
			<h2 class="font-semibold text-lg">О себе</h2>
			<textarea name="about" rows="6" class="mt-4 <?php echo esc_attr( jl_input_class() ); ?>"
				placeholder="Расскажите работодателю о своём опыте, проектах и том, какую работу ищете."><?php echo esc_textarea( $jl_profile['jl_about'] ); ?></textarea>

			<label class="flex items-center gap-2 text-sm text-slate-700 mt-4">
				<input type="checkbox" name="open_to_work" value="1" class="rounded border-slate-300"
					<?php checked( '' === $jl_profile['jl_open_to_work'] ? 1 : (int) $jl_profile['jl_open_to_work'], 1 ); ?>>
				Показывать мой профиль работодателям в подборе кандидатов
			</label>
		</div>

		<button class="bg-brand hover:bg-brand-dark transition text-white font-medium px-8 py-3 rounded-xl text-sm">
			Сохранить профиль
		</button>
	</form>

	<!-- Отклики -->
	<aside class="space-y-4">
		<h2 class="font-semibold text-lg">Мои отклики</h2>

		<?php if ( ! $jl_replies ) : ?>
			<div class="border border-slate-200 rounded-2xl p-6 text-sm text-slate-500">
				Вы пока никуда не откликнулись.
				<a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>" class="text-brand hover:text-brand-dark">Найти вакансию</a>.
			</div>
		<?php else : ?>
			<?php foreach ( $jl_replies as $jl_reply ) : ?>
				<?php
				$jl_vacancy_id = (int) get_post_meta( $jl_reply->ID, '_jl_vacancy_id', true );
				$jl_vacancy    = get_post( $jl_vacancy_id );
				$jl_state      = get_post_meta( $jl_reply->ID, '_jl_state', true );
				$jl_invite     = $jl_vacancy && ! jl_vacancy_is_banned( $jl_vacancy ) ? jl_get_invitation( $jl_reply ) : null;
				?>
				<div class="border rounded-2xl p-5 <?php echo $jl_invite ? 'border-green-300 bg-green-50' : 'border-slate-200'; ?>">
					<?php if ( $jl_vacancy && ! jl_vacancy_is_banned( $jl_vacancy ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $jl_vacancy ) ); ?>" class="font-medium hover:text-brand">
							<?php echo esc_html( $jl_vacancy->post_title ); ?>
						</a>
					<?php else : ?>
						<span class="font-medium text-slate-400">Вакансия снята с публикации</span>
					<?php endif; ?>

					<p class="text-xs mt-2 <?php echo $jl_invite ? 'text-green-800' : 'text-slate-500'; ?>">
						<?php echo esc_html( get_the_date( 'd.m.Y', $jl_reply ) ); ?> ·
						<?php echo esc_html( jl_label( jl_application_statuses(), $jl_state ) ?: 'Новый отклик' ); ?>
					</p>

					<?php if ( $jl_invite ) : ?>
						<div class="mt-3 pt-3 border-t border-green-200 text-green-900">
							<p class="font-bold"><?php echo esc_html( $jl_invite['headline'] ); ?></p>
							<?php foreach ( $jl_invite['details'] as $jl_detail ) : ?>
								<p class="font-bold mt-1"><?php echo esc_html( $jl_detail ); ?></p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</aside>
</div>
