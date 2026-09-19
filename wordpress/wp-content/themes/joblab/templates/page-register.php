<?php
/**
 * Template Name: JobLab — регистрация
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() && ! jl_user_is_banned( get_current_user_id() ) ) {
	wp_safe_redirect( jl_page_url( 'cabinet' ) );
	exit;
}

$jl_role = isset( $_GET['role'] ) && JL_ROLE_EMPLOYER === $_GET['role'] ? JL_ROLE_EMPLOYER : JL_ROLE_SEEKER;

get_header();
?>

<section class="max-w-md mx-auto px-6 py-16">
	<h1 class="text-3xl font-extrabold">Регистрация</h1>
	<p class="mt-2 text-slate-600 text-sm">Один аккаунт — либо для поиска работы, либо для найма.</p>

	<div class="mt-8">
		<?php jl_the_notice(); ?>

		<div class="grid grid-cols-2 gap-2 mb-6 p-1 bg-slate-100 rounded-xl text-sm font-medium">
			<a href="<?php echo esc_url( jl_page_url( 'register', array( 'role' => JL_ROLE_SEEKER ) ) ); ?>"
				class="text-center py-2.5 rounded-lg <?php echo JL_ROLE_SEEKER === $jl_role ? 'bg-white text-brand shadow-sm' : 'text-slate-600'; ?>">
				Ищу работу
			</a>
			<a href="<?php echo esc_url( jl_page_url( 'register', array( 'role' => JL_ROLE_EMPLOYER ) ) ); ?>"
				class="text-center py-2.5 rounded-lg <?php echo JL_ROLE_EMPLOYER === $jl_role ? 'bg-white text-brand shadow-sm' : 'text-slate-600'; ?>">
				Нанимаю
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
			<?php wp_nonce_field( 'jl_register' ); ?>
			<input type="hidden" name="action" value="jl_register">
			<input type="hidden" name="role" value="<?php echo esc_attr( $jl_role ); ?>">

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-name">
					<?php echo JL_ROLE_EMPLOYER === $jl_role ? 'Контактное лицо' : 'Имя и фамилия'; ?>
				</label>
				<input id="jl-name" name="name" type="text" required class="<?php echo esc_attr( jl_input_class() ); ?>">
			</div>

			<?php if ( JL_ROLE_EMPLOYER === $jl_role ) : ?>
				<div>
					<label class="block text-sm font-medium mb-1.5" for="jl-company">Название компании</label>
					<input id="jl-company" name="company" type="text" required class="<?php echo esc_attr( jl_input_class() ); ?>">
				</div>
			<?php endif; ?>

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-email">E-mail</label>
				<input id="jl-email" name="email" type="email" required autocomplete="email"
					class="<?php echo esc_attr( jl_input_class() ); ?>">
			</div>

			<div>
				<label class="block text-sm font-medium mb-1.5" for="jl-password">Пароль</label>
				<input id="jl-password" name="password" type="password" required minlength="6" autocomplete="new-password"
					class="<?php echo esc_attr( jl_input_class() ); ?>">
				<p class="text-xs text-slate-500 mt-1.5">Минимум 6 символов.</p>
			</div>

			<button class="w-full bg-brand hover:bg-brand-dark transition text-white font-medium px-6 py-3 rounded-xl text-sm">
				Создать аккаунт
			</button>
		</form>

		<p class="mt-6 text-sm text-slate-600">
			Уже зарегистрированы?
			<a href="<?php echo esc_url( jl_page_url( 'login' ) ); ?>" class="text-brand font-medium hover:text-brand-dark">Войти</a>
		</p>
	</div>
</section>

<?php get_footer(); ?>
