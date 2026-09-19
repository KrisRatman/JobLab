<?php
/**
 * Template Name: JobLab — доступ ограничен
 */

defined( 'ABSPATH' ) || exit;

$jl_reason = is_user_logged_in() ? get_user_meta( get_current_user_id(), 'jl_ban_reason', true ) : '';

get_header();
?>

<section class="max-w-xl mx-auto px-6 py-24 text-center">
	<div class="w-16 h-16 mx-auto rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
		<?php jl_icon( 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z', 'w-8 h-8' ); ?>
	</div>

	<h1 class="mt-6 text-3xl font-extrabold"><?php echo esc_html( jl_banned_user_notice() ); ?></h1>

	<?php if ( $jl_reason ) : ?>
		<p class="mt-4 text-slate-600">Причина: <?php echo esc_html( $jl_reason ); ?></p>
	<?php endif; ?>

	<p class="mt-4 text-slate-600 text-sm">
		Если вы считаете, что блокировка ошибочна, напишите в поддержку — модератор пересмотрит решение.
		Данные заблокированного аккаунта удаляются из базы через <?php echo (int) JL_BAN_TTL_DAYS; ?> дней.
	</p>

	<div class="mt-8 flex items-center justify-center gap-3">
		<?php if ( is_user_logged_in() ) : ?>
			<?php jl_the_logout_button( 'bg-slate-900 text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-slate-800 transition' ); ?>
		<?php endif; ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"
			class="border border-slate-200 text-slate-700 text-sm font-medium px-6 py-3 rounded-xl hover:bg-slate-50 transition">
			На главную
		</a>
	</div>
</section>

<?php get_footer(); ?>
