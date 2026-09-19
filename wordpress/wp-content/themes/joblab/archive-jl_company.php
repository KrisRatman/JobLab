<?php
/**
 * Список компаний-работодателей.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="max-w-7xl mx-auto px-6 py-12">
	<h1 class="text-3xl font-extrabold">Компании</h1>
	<p class="mt-2 text-slate-600 text-sm">Работодатели, размещающие вакансии на платформе.</p>

	<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-8">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$jl_open = new WP_Query(
					array(
						'post_type'      => JL_CPT_VACANCY,
						'post_status'    => 'publish',
						'author'         => get_the_author_meta( 'ID' ),
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				$jl_count = (int) $jl_open->found_posts;
				?>
				<a href="<?php the_permalink(); ?>" class="block border border-slate-200 rounded-2xl p-6 hover:shadow-md transition">
					<h2 class="font-semibold text-lg"><?php the_title(); ?></h2>
					<p class="text-sm text-slate-500 mt-1">
						<?php echo esc_html( get_post_meta( get_the_ID(), '_jl_city', true ) ?: 'Город не указан' ); ?>
					</p>
					<p class="text-sm text-brand mt-3">
						<?php echo esc_html( $jl_count . ' ' . jl_plural( $jl_count, 'вакансия', 'вакансии', 'вакансий' ) ); ?>
					</p>
				</a>
			<?php endwhile; ?>
		<?php else : ?>
			<p class="text-slate-500">Компаний пока нет.</p>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
