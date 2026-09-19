<?php
/**
 * Кабинет работодателя: свои заявки, отклики, доступ к подбору кандидатов.
 */

defined( 'ABSPATH' ) || exit;

$jl_user    = $args['user'] ?? wp_get_current_user();
$jl_company = jl_get_employer_company( $jl_user->ID );

$jl_vacancies = get_posts(
	array(
		'post_type'      => JL_CPT_VACANCY,
		'author'         => $jl_user->ID,
		'post_status'    => array( 'publish', 'draft', 'pending', JL_STATUS_BANNED ),
		'posts_per_page' => 50,
	)
);

$jl_applications = jl_get_applications_for_employer( $jl_user->ID );

// Группируем отклики по вакансиям, чтобы показывать их под карточкой.
$jl_by_vacancy = array();
foreach ( $jl_applications as $jl_application ) {
	$jl_by_vacancy[ (int) get_post_meta( $jl_application->ID, '_jl_vacancy_id', true ) ][] = $jl_application;
}
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
	<div>
		<h1 class="text-3xl font-extrabold">Кабинет работодателя</h1>
		<p class="mt-2 text-slate-600 text-sm">
			<?php echo $jl_company ? esc_html( $jl_company->post_title ) : 'Компания не заполнена'; ?> ·
			вакансий: <?php echo count( $jl_vacancies ); ?> ·
			откликов: <?php echo count( $jl_applications ); ?>
		</p>
	</div>
	<div class="flex flex-wrap items-center gap-3">
		<a href="<?php echo esc_url( jl_page_url( 'vacancy-form' ) ); ?>"
			class="bg-brand hover:bg-brand-dark transition text-white text-sm font-medium px-5 py-2.5 rounded-xl">
			Разместить вакансию
		</a>
		<a href="<?php echo esc_url( jl_page_url( 'candidates' ) ); ?>"
			class="border border-slate-200 text-slate-700 text-sm font-medium px-5 py-2.5 rounded-xl hover:bg-slate-50 transition">
			Подбор кандидатов
		</a>
		<?php jl_the_logout_button( 'text-sm font-medium text-slate-500 hover:text-slate-900' ); ?>
	</div>
</div>

<div class="mt-10 space-y-6">
	<h2 class="font-semibold text-lg">Мои заявки</h2>

	<?php if ( ! $jl_vacancies ) : ?>
		<div class="border border-slate-200 rounded-2xl p-6 text-sm text-slate-500">
			Вы ещё не размещали вакансий.
			<a href="<?php echo esc_url( jl_page_url( 'vacancy-form' ) ); ?>" class="text-brand hover:text-brand-dark">Создать первую</a>.
		</div>
	<?php endif; ?>

	<?php foreach ( $jl_vacancies as $jl_vacancy_post ) : ?>
		<?php
		$jl_vacancy  = jl_get_vacancy( $jl_vacancy_post );
		$jl_replies  = $jl_by_vacancy[ $jl_vacancy_post->ID ] ?? array();
		$jl_is_banned = $jl_vacancy['banned'];
		?>

		<article class="border rounded-2xl p-6 <?php echo $jl_is_banned ? 'border-red-200 bg-red-50/40' : 'border-slate-200'; ?>">
			<?php if ( $jl_is_banned ) : ?>
				<!-- Модератор заблокировал заявку: содержимое работодателю не показываем -->
				<div class="flex items-start gap-3">
					<span class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0">
						<?php jl_icon( 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z', 'w-5 h-5' ); ?>
					</span>
					<div>
						<p class="font-semibold text-red-800"><?php echo esc_html( jl_banned_vacancy_notice() ); ?></p>
						<?php $jl_reason = get_post_meta( $jl_vacancy_post->ID, '_jl_ban_reason', true ); ?>
						<?php if ( $jl_reason ) : ?>
							<p class="text-sm text-red-700 mt-1"><?php echo esc_html( $jl_reason ); ?></p>
						<?php endif; ?>
						<p class="text-xs text-red-600/80 mt-2">
							Заявка скрыта от соискателей и будет удалена через
							<?php echo (int) jl_days_until_purge( $jl_vacancy['banned_at'] ); ?> дн.
						</p>
					</div>
				</div>
			<?php else : ?>
				<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
					<div class="min-w-0">
						<h3 class="font-semibold text-lg">
							<a href="<?php echo esc_url( get_permalink( $jl_vacancy_post ) ); ?>" class="hover:text-brand">
								<?php echo esc_html( $jl_vacancy_post->post_title ); ?>
							</a>
						</h3>
						<p class="text-sm text-slate-500 mt-1">
							<?php echo esc_html( $jl_vacancy['city'] ?: 'Город не указан' ); ?> ·
							<?php echo esc_html( jl_format_salary( $jl_vacancy['salary_min'], $jl_vacancy['salary_max'] ) ); ?> ·
							откликов: <?php echo count( $jl_replies ); ?>
						</p>
						<?php jl_the_skill_pills( $jl_vacancy['skills'] ); ?>
					</div>

					<div class="flex items-center gap-3 shrink-0">
						<a href="<?php echo esc_url( jl_page_url( 'vacancy-form', array( 'id' => $jl_vacancy_post->ID ) ) ); ?>"
							class="text-sm font-medium text-brand hover:text-brand-dark">Изменить</a>

						<?php if ( $jl_vacancy['skills'] ) : ?>
							<a href="<?php echo esc_url( jl_page_url( 'candidates', array( 'vacancy' => $jl_vacancy_post->ID ) ) ); ?>"
								class="text-sm font-medium text-slate-600 hover:text-slate-900">Кандидаты</a>
						<?php endif; ?>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							onsubmit="return confirm('Снять вакансию с публикации?')">
							<?php wp_nonce_field( 'jl_delete_vacancy' ); ?>
							<input type="hidden" name="action" value="jl_delete_vacancy">
							<input type="hidden" name="vacancy_id" value="<?php echo (int) $jl_vacancy_post->ID; ?>">
							<button class="text-sm font-medium text-slate-400 hover:text-red-600">Удалить</button>
						</form>
					</div>
				</div>

				<?php if ( $jl_replies ) : ?>
					<div class="mt-5 border-t border-slate-100 pt-5 space-y-4">
						<?php foreach ( $jl_replies as $jl_reply ) : ?>
							<?php
							$jl_candidate = get_userdata( $jl_reply->post_author );
							if ( ! $jl_candidate || jl_user_is_banned( $jl_candidate->ID ) ) {
								continue; // Заблокированные соискатели из списка выпадают.
							}
							$jl_state         = get_post_meta( $jl_reply->ID, '_jl_state', true );
							$jl_reply_skills  = jl_get_user_skills( $jl_candidate->ID );
							$jl_vacancy_skill = wp_list_pluck( $jl_vacancy['skills'], 'term_id' );
							?>
							<div class="bg-slate-50 rounded-xl p-4">
								<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
									<div>
										<p class="font-medium"><?php echo esc_html( $jl_candidate->display_name ); ?></p>
										<p class="text-sm text-slate-500">
											<?php echo esc_html( get_user_meta( $jl_candidate->ID, 'jl_position', true ) ); ?>
											<?php $jl_city = get_user_meta( $jl_candidate->ID, 'jl_city', true ); ?>
											<?php echo $jl_city ? ' · ' . esc_html( $jl_city ) : ''; ?>
										</p>
										<p class="text-sm text-slate-600 mt-2"><?php echo esc_html( get_post_meta( $jl_reply->ID, '_jl_message', true ) ); ?></p>

										<?php
										$jl_terms = array_filter( array_map(
											static function ( $id ) {
												$term = get_term( $id, JL_TAX_SKILL );

												return $term && ! is_wp_error( $term ) ? $term : null;
											},
											$jl_reply_skills
										) );
										jl_the_skill_pills( $jl_terms, $jl_vacancy_skill );
										?>
									</div>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="shrink-0">
										<?php wp_nonce_field( 'jl_application_state' ); ?>
										<input type="hidden" name="action" value="jl_application_state">
										<input type="hidden" name="application_id" value="<?php echo (int) $jl_reply->ID; ?>">
										<select name="state" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
											<?php foreach ( jl_application_statuses() as $jl_key => $jl_label ) : ?>
												<option value="<?php echo esc_attr( $jl_key ); ?>" <?php selected( $jl_state, $jl_key ); ?>>
													<?php echo esc_html( $jl_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button class="ml-2 text-sm font-medium text-brand hover:text-brand-dark">ОК</button>
									</form>
								</div>

								<p class="text-xs text-slate-400 mt-3">
									Контакты: <?php echo esc_html( $jl_candidate->user_email ); ?>
									<?php $jl_phone = get_user_meta( $jl_candidate->ID, 'jl_phone', true ); ?>
									<?php echo $jl_phone ? ' · ' . esc_html( $jl_phone ) : ''; ?>
								</p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
