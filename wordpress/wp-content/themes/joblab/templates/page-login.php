<?php
/**
 * Template Name: JobLab — вход
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() && ! jl_user_is_banned( get_current_user_id() ) ) {
	wp_safe_redirect( jl_page_url( 'cabinet' ) );
	exit;
}

get_header();
?>

<section class="max-w-md mx-auto px-6 py-16">
	<h1 class="text-3xl font-extrabold">Вход в JobLab</h1>
	<p class="mt-2 text-slate-600 text-sm">Войдите, чтобы откликаться на вакансии или управлять заявками.</p>

	<div class="mt-8">
		<?php jl_the_notice(); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
			<?php wp_nonce_field( 'jl_login' ); ?>
			<input type="hidden" name="action" value="jl_login">

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-login">E-mail</label>
				<input id="jl-login" name="login" type="text" required autocomplete="username"
					class="<?php echo esc_attr( jl_input_class() ); ?>">
			</div>

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-password">Пароль</label>
				<input id="jl-password" name="password" type="password" required autocomplete="current-password"
					class="<?php echo esc_attr( jl_input_class() ); ?>">
			</div>

			<label class="flex items-center gap-2 text-sm text-slate-600">
				<input type="checkbox" name="remember" value="1" checked class="rounded border-slate-300">
				Запомнить меня
			</label>

			<button class="w-full bg-brand hover:bg-brand-dark transition text-white font-medium px-6 py-3 rounded-xl text-sm">
				Войти
			</button>
		</form>

		<p class="mt-6 text-sm text-slate-600">
			Ещё нет аккаунта?
			<a href="<?php echo esc_url( jl_page_url( 'register' ) ); ?>" class="text-brand font-medium hover:text-brand-dark">Зарегистрироваться</a>
		</p>
	</div>
</section>

<?php get_footer(); ?>
