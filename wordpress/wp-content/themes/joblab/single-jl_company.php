<?php
/**
 * Страница компании с её открытыми вакансиями.
 */

defined( 'ABSPATH' ) || exit;

get_header();

the_post();

$jl_company   = get_post();
$jl_site      = get_post_meta( $jl_company->ID, '_jl_website', true );
$jl_city      = get_post_meta( $jl_company->ID, '_jl_city', true );
$jl_address   = get_post_meta( $jl_company->ID, '_jl_address', true );
$jl_vacancies = get_posts(
	array(
		'post_type'      => JL_CPT_VACANCY,
		'post_status'    => 'publish',
		'author'         => (int) $jl_company->post_author,
		'posts_per_page' => 30,
	)
);
?>

<section class="max-w-4xl mx-auto px-6 py-12">
	<h1 class="text-3xl md:text-4xl font-extrabold"><?php the_title(); ?></h1>

	<div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-slate-500">
		<?php if ( $jl_city ) : ?>
			<span class="flex items-center gap-1">
				<?php jl_icon( 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' ); ?>
				<?php echo esc_html( $jl_city ); ?>
			</span>
		<?php endif; ?>
		<?php if ( $jl_address ) : ?>
			<span class="flex items-center gap-1">
				<?php jl_icon( 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m6-14h2m-2 4h2m4-4h2m-2 4h2' ); ?>
				<?php echo esc_html( $jl_address ); ?>
			</span>
		<?php endif; ?>
		<?php if ( $jl_site ) : ?>
			<a href="<?php echo esc_url( $jl_site ); ?>" rel="nofollow noopener" target="_blank" class="text-brand hover:text-brand-dark">
				<?php echo esc_html( $jl_site ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $jl_company->post_content ) : ?>
		<div class="mt-6 text-slate-700 leading-relaxed"><?php the_content(); ?></div>
	<?php endif; ?>

	<h2 class="mt-12 text-2xl font-bold">
		Открытые вакансии
		<span class="text-slate-400 font-normal text-lg">(<?php echo count( $jl_vacancies ); ?>)</span>
	</h2>

	<div class="mt-6 space-y-4">
		<?php if ( ! $jl_vacancies ) : ?>
			<p class="text-slate-500 text-sm">Сейчас у компании нет открытых вакансий.</p>
		<?php endif; ?>

		<?php foreach ( $jl_vacancies as $jl_vacancy_post ) : ?>
			<?php jl_the_vacancy_card( $jl_vacancy_post ); ?>
		<?php endforeach; ?>
	</div>
</section>

<?php get_footer(); ?>
