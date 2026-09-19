<?php
/**
 * Template Name: JobLab — личный кабинет
 *
 * Разводит соискателя и работодателя по своим экранам.
 */

defined( 'ABSPATH' ) || exit;

jl_require_login();

get_header();

$jl_user = wp_get_current_user();
?>

<section class="max-w-7xl mx-auto px-6 py-12">
	<?php jl_the_notice(); ?>

	<?php if ( jl_is_seeker( $jl_user ) ) : ?>
		<?php get_template_part( 'templates/parts/cabinet', 'seeker', array( 'user' => $jl_user ) ); ?>
	<?php elseif ( jl_is_employer( $jl_user ) ) : ?>
		<?php get_template_part( 'templates/parts/cabinet', 'employer', array( 'user' => $jl_user ) ); ?>
	<?php else : ?>
		<h1 class="text-3xl font-extrabold">Личный кабинет</h1>
		<p class="mt-4 text-slate-600">
			Вы вошли как администратор. Модерация заявок и работников — в
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=joblab' ) ); ?>" class="text-brand hover:text-brand-dark">консоли JobLab</a>.
		</p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
