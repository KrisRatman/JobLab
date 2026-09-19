<?php
/**
 * Страница вакансии с формой отклика.
 */

defined( 'ABSPATH' ) || exit;

get_header();

the_post();

$jl_post    = get_post();
$jl_vacancy = jl_get_vacancy( $jl_post );
$jl_company = $jl_vacancy['company_id'] ? get_post( $jl_vacancy['company_id'] ) : null;
$jl_is_mine = (int) $jl_post->post_author === get_current_user_id();
$jl_applied = is_user_logged_in() && jl_has_applied( get_current_user_id(), $jl_post->ID );
?>

<section class="max-w-4xl mx-auto px-6 py-12">
	<?php jl_the_notice(); ?>

	<?php if ( $jl_vacancy['banned'] ) : ?>
		<!-- Заблокированную заявку видит только её автор (и модератор). -->
		<div class="border border-red-200 bg-red-50 rounded-2xl p-8">
			<h1 class="text-2xl font-bold text-red-800"><?php echo esc_html( jl_banned_vacancy_notice() ); ?></h1>
			<?php $jl_reason = get_post_meta( $jl_post->ID, '_jl_ban_reason', true ); ?>
			<?php if ( $jl_reason ) : ?>
				<p class="mt-3 text-red-700"><?php echo esc_html( $jl_reason ); ?></p>
			<?php endif; ?>
			<p class="mt-3 text-sm text-red-600/80">
				Заявка скрыта от соискателей и будет удалена через
				<?php echo (int) jl_days_until_purge( $jl_vacancy['banned_at'] ); ?> дн.
			</p>
			<a href="<?php echo esc_url( jl_page_url( 'cabinet' ) ); ?>"
				class="inline-block mt-6 text-sm font-medium text-red-800 underline">Вернуться в кабинет</a>
		</div>

	<?php else : ?>

		<nav class="text-sm text-slate-500">
			<a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>" class="hover:text-brand">Вакансии</a>
			<?php foreach ( $jl_vacancy['categories'] as $jl_term ) : ?>
				<span class="mx-2">/</span>
				<a href="<?php echo esc_url( get_term_link( $jl_term ) ); ?>" class="hover:text-brand"><?php echo esc_html( $jl_term->name ); ?></a>
			<?php endforeach; ?>
		</nav>

		<header class="mt-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6">
			<div>
				<h1 class="text-3xl md:text-4xl font-extrabold leading-tight"><?php the_title(); ?></h1>
				<p class="mt-3 text-slate-600">
					<?php if ( $jl_company ) : ?>
						<a href="<?php echo esc_url( get_permalink( $jl_company ) ); ?>" class="hover:text-brand font-medium">
							<?php echo esc_html( $jl_company->post_title ); ?>
						</a>
					<?php endif; ?>
				</p>

				<div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-slate-500">
					<?php if ( $jl_vacancy['city'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' ); ?>
							<?php echo esc_html( $jl_vacancy['city'] ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $jl_vacancy['employment'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M12 7v5l3 3' ); ?>
							<?php echo esc_html( jl_label( jl_employment_types(), $jl_vacancy['employment'] ) ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $jl_vacancy['experience'] ) : ?>
						<span class="flex items-center gap-1">
							<?php jl_icon( 'M13 10V3L4 14h7v7l9-11h-7z' ); ?>
							<?php echo esc_html( jl_label( jl_experience_levels(), $jl_vacancy['experience'] ) ); ?>
						</span>
					<?php endif; ?>
					<span>Опубликовано <?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
				</div>
			</div>

			<div class="sm:text-right shrink-0">
				<p class="text-2xl font-bold"><?php echo esc_html( jl_format_salary( $jl_vacancy['salary_min'], $jl_vacancy['salary_max'] ) ); ?></p>
				<p class="text-sm text-slate-400">в месяц</p>
			</div>
		</header>

		<?php if ( $jl_vacancy['skills'] ) : ?>
			<div class="mt-8">
				<h2 class="font-semibold">Требуемые навыки</h2>
				<div class="flex flex-wrap gap-2 mt-3">
					<?php foreach ( $jl_vacancy['skills'] as $jl_skill ) : ?>
						<a href="<?php echo esc_url( get_term_link( $jl_skill ) ); ?>"
							class="text-sm px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 hover:bg-brand hover:text-white transition">
							<?php echo esc_html( $jl_skill->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="mt-8 prose prose-slate max-w-none text-slate-700 leading-relaxed">
			<?php the_content(); ?>
		</div>

		<?php if ( $jl_company && $jl_company->post_content ) : ?>
			<div class="mt-10 border border-slate-200 rounded-2xl p-6">
				<h2 class="font-semibold text-lg">О компании</h2>
				<p class="mt-2 text-slate-600 text-sm"><?php echo esc_html( $jl_company->post_content ); ?></p>
				<?php $jl_site = get_post_meta( $jl_company->ID, '_jl_website', true ); ?>
				<?php if ( $jl_site ) : ?>
					<a href="<?php echo esc_url( $jl_site ); ?>" rel="nofollow noopener" target="_blank"
						class="inline-block mt-3 text-sm text-brand hover:text-brand-dark"><?php echo esc_html( $jl_site ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Отклик -->
		<div class="mt-10 border border-slate-200 rounded-2xl p-6" id="apply">
			<?php if ( $jl_is_mine ) : ?>
				<h2 class="font-semibold text-lg">Это ваша вакансия</h2>
				<p class="mt-2 text-sm text-slate-600">Отклики приходят в личный кабинет.</p>
				<div class="mt-4 flex flex-wrap gap-3">
					<a href="<?php echo esc_url( jl_page_url( 'cabinet' ) ); ?>"
						class="bg-brand hover:bg-brand-dark transition text-white text-sm font-medium px-5 py-2.5 rounded-xl">Смотреть отклики</a>
					<a href="<?php echo esc_url( jl_page_url( 'candidates', array( 'vacancy' => $jl_post->ID ) ) ); ?>"
						class="border border-slate-200 text-slate-700 text-sm font-medium px-5 py-2.5 rounded-xl hover:bg-slate-50 transition">Подобрать кандидатов</a>
				</div>

			<?php elseif ( ! is_user_logged_in() ) : ?>
				<h2 class="font-semibold text-lg">Откликнуться на вакансию</h2>
				<p class="mt-2 text-sm text-slate-600">Войдите или зарегистрируйтесь как соискатель, чтобы отправить отклик.</p>
				<div class="mt-4 flex flex-wrap gap-3">
					<a href="<?php echo esc_url( jl_page_url( 'login' ) ); ?>"
						class="bg-brand hover:bg-brand-dark transition text-white text-sm font-medium px-5 py-2.5 rounded-xl">Войти</a>
					<a href="<?php echo esc_url( jl_page_url( 'register' ) ); ?>"
						class="border border-slate-200 text-slate-700 text-sm font-medium px-5 py-2.5 rounded-xl hover:bg-slate-50 transition">Зарегистрироваться</a>
				</div>

			<?php elseif ( ! jl_is_seeker() ) : ?>
				<p class="text-sm text-slate-600">Откликаться на вакансии могут только соискатели.</p>

			<?php elseif ( $jl_applied ) : ?>
				<h2 class="font-semibold text-lg">Отклик отправлен</h2>
				<p class="mt-2 text-sm text-slate-600">
					Работодатель увидит ваш профиль и навыки.
					<a href="<?php echo esc_url( jl_page_url( 'cabinet' ) ); ?>" class="text-brand hover:text-brand-dark">Статус — в кабинете</a>.
				</p>

			<?php else : ?>
				<h2 class="font-semibold text-lg">Откликнуться на вакансию</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-4 space-y-4">
					<?php wp_nonce_field( 'jl_apply' ); ?>
					<input type="hidden" name="action" value="jl_apply">
					<input type="hidden" name="vacancy_id" value="<?php echo (int) $jl_post->ID; ?>">

					<textarea name="message" rows="4" class="<?php echo esc_attr( jl_input_class() ); ?>"
						placeholder="Коротко о том, почему вы подходите."></textarea>

					<button class="bg-brand hover:bg-brand-dark transition text-white font-medium px-8 py-3 rounded-xl text-sm">
						Отправить отклик
					</button>
				</form>
			<?php endif; ?>
		</div>

	<?php endif; ?>
</section>

<?php get_footer(); ?>
