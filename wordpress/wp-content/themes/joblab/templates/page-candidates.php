<?php
/**
 * Template Name: JobLab — подбор кандидатов
 *
 * Работодатель выбирает навыки (или берёт их из своей вакансии)
 * и получает зарегистрированных соискателей, отсортированных по совпадениям.
 */

defined( 'ABSPATH' ) || exit;

jl_require_login();

if ( ! jl_is_employer() ) {
	wp_safe_redirect( jl_page_url( 'cabinet' ) );
	exit;
}

$jl_user = wp_get_current_user();

$jl_vacancy_id = absint( $_GET['vacancy'] ?? 0 );
$jl_vacancy    = null;
$jl_selected   = array_map( 'absint', (array) ( $_GET['skills'] ?? array() ) );

// Если пришли из карточки вакансии — подставляем её навыки.
if ( $jl_vacancy_id ) {
	$jl_post = get_post( $jl_vacancy_id );
	if ( $jl_post && JL_CPT_VACANCY === $jl_post->post_type && (int) $jl_post->post_author === $jl_user->ID ) {
		$jl_vacancy = jl_get_vacancy( $jl_post );
		if ( ! $jl_selected ) {
			$jl_selected = array_map( 'intval', wp_list_pluck( $jl_vacancy['skills'], 'term_id' ) );
		}
	}
}

$jl_city       = sanitize_text_field( wp_unslash( $_GET['city'] ?? '' ) );
$jl_candidates = jl_find_candidates( $jl_selected, array( 'city' => $jl_city, 'limit' => 50 ) );

$jl_own_vacancies = get_posts(
	array(
		'post_type'      => JL_CPT_VACANCY,
		'author'         => $jl_user->ID,
		'post_status'    => 'publish',
		'posts_per_page' => 50,
	)
);

get_header();
?>

<section class="max-w-7xl mx-auto px-6 py-12">
	<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
		<div>
			<h1 class="text-3xl font-extrabold">Подбор кандидатов</h1>
			<p class="mt-2 text-slate-600 text-sm">
				<?php if ( $jl_vacancy ) : ?>
					Навыки из вакансии «<?php echo esc_html( get_the_title( $jl_vacancy['post'] ) ); ?>».
				<?php else : ?>
					Отметьте нужные навыки — система найдёт работников с такими тегами.
				<?php endif; ?>
			</p>
		</div>
		<a href="<?php echo esc_url( jl_page_url( 'cabinet' ) ); ?>" class="text-sm font-medium text-brand hover:text-brand-dark">
			← В кабинет
		</a>
	</div>

	<div class="grid lg:grid-cols-4 gap-8 mt-8">
		<!-- Фильтр -->
		<form method="get" action="<?php echo esc_url( jl_page_url( 'candidates' ) ); ?>" class="lg:col-span-1 space-y-4">
			<?php if ( $jl_own_vacancies ) : ?>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-vacancy">Взять навыки из вакансии</label>
					<select id="jl-vacancy" name="vacancy" class="<?php echo esc_attr( jl_input_class() ); ?>"
						onchange="this.form.querySelectorAll('input[name=\'skills[]\']').forEach(function(i){i.checked=false});this.form.submit()">
						<option value="">— не выбрано —</option>
						<?php foreach ( $jl_own_vacancies as $jl_item ) : ?>
							<option value="<?php echo (int) $jl_item->ID; ?>" <?php selected( $jl_vacancy_id, $jl_item->ID ); ?>>
								<?php echo esc_html( $jl_item->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-city">Город</label>
				<input id="jl-city" name="city" type="text" class="<?php echo esc_attr( jl_input_class() ); ?>"
					value="<?php echo esc_attr( $jl_city ); ?>" placeholder="Любой">
			</div>

			<div>
				<span class="block text-sm font-medium mb-1.5">Навыки</span>
				<div class="border border-slate-200 rounded-xl p-4 max-h-[420px] overflow-y-auto space-y-2">
					<?php foreach ( jl_all_skills() as $jl_skill ) : ?>
						<label class="flex items-center gap-2 text-sm text-slate-700">
							<input type="checkbox" name="skills[]" value="<?php echo (int) $jl_skill->term_id; ?>"
								class="rounded border-slate-300"
								<?php checked( in_array( (int) $jl_skill->term_id, $jl_selected, true ) ); ?>>
							<span><?php echo esc_html( $jl_skill->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<button class="w-full bg-brand hover:bg-brand-dark transition text-white font-medium px-6 py-3 rounded-xl text-sm">
				Найти кандидатов
			</button>
			<a href="<?php echo esc_url( jl_page_url( 'candidates' ) ); ?>"
				class="block text-center text-sm text-slate-500 hover:text-slate-900">Сбросить фильтр</a>
		</form>

		<!-- Результаты -->
		<div class="lg:col-span-3 space-y-4">
			<p class="text-sm text-slate-500">
				Найдено: <?php echo count( $jl_candidates ); ?>
				<?php if ( $jl_selected ) : ?>
					· сортировка по числу совпавших навыков
				<?php endif; ?>
			</p>

			<?php if ( ! $jl_candidates ) : ?>
				<div class="border border-slate-200 rounded-2xl p-8 text-center text-slate-500 text-sm">
					Никто не подошёл под выбранные навыки. Попробуйте снять часть фильтров.
				</div>
			<?php endif; ?>

			<?php foreach ( $jl_candidates as $jl_row ) : ?>
				<?php
				$jl_candidate = $jl_row['user'];
				$jl_terms     = array_filter( array_map(
					static function ( $id ) {
						$term = get_term( $id, JL_TAX_SKILL );

						return $term && ! is_wp_error( $term ) ? $term : null;
					},
					$jl_row['skills']
				) );
				$jl_profile   = jl_get_profile( $jl_candidate->ID );
				?>
				<article class="border border-slate-200 rounded-2xl p-6 hover:shadow-md transition">
					<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
						<div class="min-w-0">
							<h3 class="font-semibold text-lg"><?php echo esc_html( $jl_candidate->display_name ); ?></h3>
							<p class="text-sm text-slate-500 mt-1">
								<?php echo esc_html( $jl_profile['jl_position'] ?: 'Должность не указана' ); ?>
								<?php echo $jl_profile['jl_city'] ? ' · ' . esc_html( $jl_profile['jl_city'] ) : ''; ?>
								<?php if ( $jl_profile['jl_experience'] ) : ?>
									· <?php echo esc_html( jl_label( jl_experience_levels(), $jl_profile['jl_experience'] ) ); ?>
								<?php endif; ?>
							</p>

							<?php if ( $jl_profile['jl_education_level'] ) : ?>
								<p class="text-sm text-slate-500 mt-1">
									<?php echo esc_html( jl_label( jl_education_levels(), $jl_profile['jl_education_level'] ) ); ?>
									<?php echo $jl_profile['jl_education_place'] ? ' · ' . esc_html( $jl_profile['jl_education_place'] ) : ''; ?>
								</p>
							<?php endif; ?>

							<?php if ( $jl_profile['jl_about'] ) : ?>
								<p class="text-sm text-slate-600 mt-3"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $jl_profile['jl_about'] ), 35 ) ); ?></p>
							<?php endif; ?>

							<?php jl_the_skill_pills( $jl_terms, $jl_selected ); ?>

							<p class="text-xs text-slate-400 mt-3">
								<?php echo esc_html( $jl_candidate->user_email ); ?>
								<?php echo $jl_profile['jl_phone'] ? ' · ' . esc_html( $jl_profile['jl_phone'] ) : ''; ?>
							</p>
						</div>

						<div class="sm:text-right shrink-0">
							<?php if ( $jl_selected ) : ?>
								<p class="text-2xl font-bold text-brand"><?php echo (int) $jl_row['matched']; ?>/<?php echo count( $jl_selected ); ?></p>
								<p class="text-xs text-slate-400">совпадений</p>
							<?php endif; ?>
							<?php if ( $jl_profile['jl_salary'] ) : ?>
								<p class="text-sm text-slate-600 mt-2">от <?php echo esc_html( number_format_i18n( $jl_profile['jl_salary'] ) ); ?> ₽</p>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
