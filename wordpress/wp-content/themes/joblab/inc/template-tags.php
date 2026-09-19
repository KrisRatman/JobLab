<?php
/**
 * Помощники вывода: уведомления, карточки вакансий, поля форм.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Показывает уведомление, переданное через ?jl_notice=.
 */
function jl_the_notice() {
	$notice = jl_get_notice();
	if ( ! $notice ) {
		return;
	}

	$styles = 'error' === $notice['type']
		? 'bg-red-50 border-red-200 text-red-800'
		: 'bg-green-50 border-green-200 text-green-800';

	printf(
		'<div class="border rounded-xl px-5 py-4 text-sm mb-6 %s">%s</div>',
		esc_attr( $styles ),
		esc_html( $notice['message'] )
	);
}

/**
 * Иконка категории по названию — тот же набор путей, что в вёрстке.
 */
function jl_category_icon_path( $name ) {
	$icons = array(
		'IT'        => 'M10 6L4 12l6 6M14 6l6 6-6 6',
		'Менеджмент'=> 'M3 7h18M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7M3 7l2-4h14l2 4M9 11h6',
		'Финансы'   => 'M9 17V7m0 10H5a2 2 0 01-2-2V9a2 2 0 012-2h4m0 10h6m-6-10h6m0 0h4a2 2 0 012 2v6a2 2 0 01-2 2h-4m0-10v10',
		'Логистика' => 'M3 13h4l3 8 4-16 3 8h4',
		'Медицина'  => 'M12 21c-4.418-3.04-8-6.36-8-10.5A5.5 5.5 0 0112 5.5a5.5 5.5 0 018 5c0 4.14-3.582 7.46-8 10.5z',
		'Маркетинг' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
		'Продажи'   => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
		'Дизайн'    => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
	);

	foreach ( $icons as $needle => $path ) {
		if ( false !== mb_stripos( $name, $needle ) ) {
			return $path;
		}
	}

	return 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v1m4 6h.01';
}

function jl_icon( $path, $classes = 'w-4 h-4' ) {
	printf(
		'<svg xmlns="http://www.w3.org/2000/svg" class="%s" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="%s"/></svg>',
		esc_attr( $classes ),
		esc_attr( $path )
	);
}

/**
 * Плашки навыков.
 */
function jl_the_skill_pills( $terms, $highlight = array() ) {
	if ( ! $terms ) {
		return;
	}

	$highlight = array_map( 'intval', $highlight );

	echo '<div class="flex flex-wrap gap-2 mt-3">';
	foreach ( $terms as $term ) {
		$is_match = in_array( (int) $term->term_id, $highlight, true );
		printf(
			'<span class="text-xs px-2.5 py-1 rounded-full %s">%s</span>',
			$is_match ? 'bg-brand text-white' : 'bg-slate-100 text-slate-600',
			esc_html( $term->name )
		);
	}
	echo '</div>';
}

/**
 * Карточка вакансии в списке.
 */
function jl_the_vacancy_card( $post, array $options = array() ) {
	$vacancy = jl_get_vacancy( $post );
	if ( ! $vacancy ) {
		return;
	}

	$options   = wp_parse_args( $options, array( 'highlight' => array() ) );
	$permalink = get_permalink( $vacancy['post'] );
	?>
	<article class="border border-slate-200 rounded-2xl p-6 hover:shadow-md transition">
		<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
			<div class="min-w-0">
				<h3 class="font-semibold text-lg">
					<a href="<?php echo esc_url( $permalink ); ?>" class="hover:text-brand">
						<?php echo esc_html( get_the_title( $vacancy['post'] ) ); ?>
					</a>
				</h3>
				<p class="text-sm text-slate-500 mt-1"><?php echo esc_html( $vacancy['company'] ?: '—' ); ?></p>

				<div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-slate-500">
					<?php if ( $vacancy['city'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' ); ?>
							<?php echo esc_html( $vacancy['city'] ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $vacancy['employment'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M12 7v5l3 3' ); ?>
							<?php echo esc_html( jl_label( jl_employment_types(), $vacancy['employment'] ) ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $vacancy['experience'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M13 10V3L4 14h7v7l9-11h-7z' ); ?>
							<?php echo esc_html( jl_label( jl_experience_levels(), $vacancy['experience'] ) ); ?>
						</span>
					<?php endif; ?>
				</div>

				<?php jl_the_skill_pills( $vacancy['skills'], $options['highlight'] ); ?>
			</div>

			<div class="sm:text-right shrink-0">
				<p class="font-bold text-lg"><?php echo esc_html( jl_format_salary( $vacancy['salary_min'], $vacancy['salary_max'] ) ); ?></p>
				<p class="text-sm text-slate-400">в месяц</p>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Список навыков в виде чекбоксов + поле для своих вариантов.
 */
function jl_the_skill_picker( array $selected = array() ) {
	$skills   = jl_all_skills();
	$selected = array_map( 'intval', $selected );
	?>
	<div class="border border-slate-200 rounded-xl p-4 max-h-64 overflow-y-auto">
		<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
			<?php foreach ( $skills as $skill ) : ?>
				<label class="flex items-center gap-2 text-sm text-slate-700">
					<input type="checkbox" name="skills[]" value="<?php echo (int) $skill->term_id; ?>"
						class="rounded border-slate-300"
						<?php checked( in_array( (int) $skill->term_id, $selected, true ) ); ?>>
					<span><?php echo esc_html( $skill->name ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php if ( ! $skills ) : ?>
			<p class="text-sm text-slate-500">Справочник навыков пока пуст — добавьте свои через поле ниже.</p>
		<?php endif; ?>
	</div>
	<input type="text" name="skills_custom" placeholder="Свои навыки через запятую"
		class="mt-3 w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand">
	<?php
}

/**
 * Кнопка выхода.
 */
function jl_the_logout_button( $classes = '' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline">
		<?php wp_nonce_field( 'jl_logout' ); ?>
		<input type="hidden" name="action" value="jl_logout">
		<button class="<?php echo esc_attr( $classes ); ?>">Выйти</button>
	</form>
	<?php
}

/**
 * Русские формы множественного числа: 1 вакансия, 2 вакансии, 5 вакансий.
 */
function jl_plural( $number, $one, $few, $many ) {
	$number = abs( (int) $number );
	$mod10  = $number % 10;
	$mod100 = $number % 100;

	if ( 1 === $mod10 && 11 !== $mod100 ) {
		return $one;
	}
	if ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
		return $few;
	}

	return $many;
}

/**
 * Общие классы полей формы.
 */
function jl_input_class() {
	return 'w-full border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand';
}
