<?php
/**
 * Каталог вакансий: поиск, фильтры, список.
 * Используется и для архива, и для таксономий, и для результатов поиска.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$jl_search     = get_search_query();
$jl_city       = isset( $_GET['jl_city'] ) ? sanitize_text_field( wp_unslash( $_GET['jl_city'] ) ) : '';
$jl_cat        = absint( $_GET['jl_cat'] ?? 0 );
$jl_employment = isset( $_GET['jl_employment'] ) ? sanitize_key( $_GET['jl_employment'] ) : '';

$jl_heading = 'Вакансии';
if ( is_tax( JL_TAX_CATEGORY ) || is_tax( JL_TAX_SKILL ) ) {
	$jl_heading = single_term_title( '', false );
} elseif ( is_search() ) {
	$jl_heading = 'Результаты поиска';
}
?>

<section class="max-w-7xl mx-auto px-6 py-12">
	<h1 class="text-3xl font-extrabold"><?php echo esc_html( $jl_heading ); ?></h1>
	<p class="mt-2 text-slate-600 text-sm">Найдено вакансий: <?php echo (int) $GLOBALS['wp_query']->found_posts; ?></p>

	<!-- Фильтры -->
	<form method="get" action="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
		class="mt-8 grid md:grid-cols-4 gap-3">
		<input type="text" name="s" value="<?php echo esc_attr( $jl_search ); ?>"
			placeholder="Должность, компания или навык" class="<?php echo esc_attr( jl_input_class() ); ?>">

		<input type="text" name="jl_city" value="<?php echo esc_attr( $jl_city ); ?>"
			placeholder="Город или регион" class="<?php echo esc_attr( jl_input_class() ); ?>">

		<select name="jl_cat" class="<?php echo esc_attr( jl_input_class() ); ?>">
			<option value="">Все категории</option>
			<?php foreach ( jl_all_categories() as $jl_term ) : ?>
				<option value="<?php echo (int) $jl_term->term_id; ?>" <?php selected( $jl_cat, $jl_term->term_id ); ?>>
					<?php echo esc_html( $jl_term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<div class="flex gap-3">
			<select name="jl_employment" class="<?php echo esc_attr( jl_input_class() ); ?>">
				<option value="">Любой график</option>
				<?php foreach ( jl_employment_types() as $jl_key => $jl_label ) : ?>
					<option value="<?php echo esc_attr( $jl_key ); ?>" <?php selected( $jl_employment, $jl_key ); ?>>
						<?php echo esc_html( $jl_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button class="bg-brand hover:bg-brand-dark transition text-white font-medium px-6 rounded-xl text-sm whitespace-nowrap">
				Найти
			</button>
		</div>
	</form>

	<!-- Список -->
	<div class="mt-8 space-y-4">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php jl_the_vacancy_card( get_post() ); ?>
			<?php endwhile; ?>
		<?php else : ?>
			<div class="border border-slate-200 rounded-2xl p-10 text-center">
				<p class="text-slate-600">По вашему запросу ничего не нашлось.</p>
				<a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
					class="inline-block mt-4 text-sm font-medium text-brand hover:text-brand-dark">Показать все вакансии</a>
			</div>
		<?php endif; ?>
	</div>

	<?php
	$jl_pagination = paginate_links(
		array(
			'type'      => 'array',
			'prev_text' => '←',
			'next_text' => '→',
		)
	);
	?>
	<?php if ( $jl_pagination ) : ?>
		<nav class="mt-10 flex flex-wrap justify-center gap-2 text-sm">
			<?php foreach ( $jl_pagination as $jl_link ) : ?>
				<span class="[&>a]:px-4 [&>a]:py-2 [&>a]:border [&>a]:border-slate-200 [&>a]:rounded-lg [&>a]:inline-block [&>a:hover]:border-brand [&>span]:px-4 [&>span]:py-2 [&>span]:bg-brand [&>span]:text-white [&>span]:rounded-lg [&>span]:inline-block">
					<?php echo wp_kses_post( $jl_link ); ?>
				</span>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
