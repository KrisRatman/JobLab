<?php
/**
 * Главная страница: живой поиск, категории и вакансии из базы.
 */

get_header();

$jl_categories = jl_all_categories();
$jl_total      = (int) wp_count_posts( JL_CPT_VACANCY )->publish;
$jl_fresh      = get_posts(
	array(
		'post_type'      => JL_CPT_VACANCY,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'author__not_in' => jl_banned_user_ids(),
	)
);
?>

  <!-- Hero -->
  <section class="max-w-7xl mx-auto px-6 pt-16 pb-14">
    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight max-w-2xl">
      Найдите работу своей мечты
    </h1>
    <p class="mt-6 text-lg text-slate-600 max-w-2xl">
      <?php if ( $jl_total ) : ?>
        Актуальных предложений на платформе: <?php echo esc_html( number_format_i18n( $jl_total ) ); ?>. Все компании проходят модерацию.
      <?php else : ?>
        Платформа для поиска работы и найма персонала. Первые вакансии появятся здесь совсем скоро.
      <?php endif; ?>
    </p>

    <!-- Search bar -->
    <form method="get" action="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
          class="mt-8 flex flex-col md:flex-row gap-3 md:gap-0 md:bg-white">
      <div class="flex-1 flex items-center gap-3 border border-slate-200 md:border-r-0 rounded-xl md:rounded-r-none px-4 py-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>
        <input type="text" name="s" placeholder="Должность, компания или навык" class="w-full outline-none placeholder:text-slate-400 text-sm">
      </div>
      <div class="flex-1 flex items-center gap-3 border border-slate-200 md:border-x-0 rounded-xl md:rounded-none px-4 py-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <input type="text" name="jl_city" placeholder="Город или регион" class="w-full outline-none placeholder:text-slate-400 text-sm">
      </div>
      <button class="bg-brand hover:bg-brand-dark transition text-white font-medium px-8 py-4 rounded-xl md:rounded-l-none text-sm whitespace-nowrap">
        Найти работу
      </button>
    </form>

    <!-- Popular tags -->
    <?php if ( $jl_categories ) : ?>
      <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <span class="text-brand font-medium">Популярные категории:</span>
        <?php foreach ( array_slice( $jl_categories, 0, 5 ) as $jl_term ) : ?>
          <a href="<?php echo esc_url( get_term_link( $jl_term ) ); ?>" class="text-slate-600 hover:text-brand">
            <?php echo esc_html( $jl_term->name ); ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Popular categories -->
  <section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold">Популярные категории</h2>
        <a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
           class="text-sm font-medium text-brand hover:text-brand-dark">Все категории</a>
      </div>

      <?php if ( $jl_categories ) : ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <?php foreach ( array_slice( $jl_categories, 0, 10 ) as $jl_term ) : ?>
            <?php $jl_count = jl_category_vacancy_count( $jl_term->term_id ); ?>
            <a href="<?php echo esc_url( get_term_link( $jl_term ) ); ?>"
               class="block bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md transition">
              <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand flex items-center justify-center mb-4">
                <?php jl_icon( jl_category_icon_path( $jl_term->name ), 'w-5 h-5' ); ?>
              </div>
              <h3 class="font-semibold"><?php echo esc_html( $jl_term->name ); ?></h3>
              <p class="text-sm text-slate-500 mt-1">
                <?php echo esc_html( $jl_count . ' ' . jl_plural( $jl_count, 'вакансия', 'вакансии', 'вакансий' ) ); ?>
              </p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-slate-500">Категории ещё не созданы. Добавьте их в консоли WordPress.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Fresh vacancies -->
  <section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold">Свежие вакансии</h2>
        <a href="<?php echo esc_url( get_post_type_archive_link( JL_CPT_VACANCY ) ); ?>"
           class="flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-dark">
          Все вакансии
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
      </div>

      <div class="space-y-4">
        <?php if ( $jl_fresh ) : ?>
          <?php foreach ( $jl_fresh as $jl_vacancy_post ) : ?>
            <?php jl_the_vacancy_card( $jl_vacancy_post ); ?>
          <?php endforeach; ?>
        <?php else : ?>
          <div class="border border-slate-200 rounded-2xl p-10 text-center text-slate-500">
            <p>Вакансий пока нет.</p>
            <a href="<?php echo esc_url( jl_page_url( 'register', array( 'role' => JL_ROLE_EMPLOYER ) ) ); ?>"
               class="inline-block mt-3 text-sm font-medium text-brand hover:text-brand-dark">Разместить первую вакансию</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CTA banner with carousel -->
  <section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-6">
      <div class="bg-brand rounded-3xl px-8 py-14 md:px-14 flex flex-col md:flex-row items-center gap-10">
        <div class="flex-1 text-white">
          <h2 class="text-3xl md:text-4xl font-extrabold leading-tight">
            Найдите лучших специалистов для вашей команды
          </h2>
          <p class="mt-5 text-blue-100 max-w-lg">
            Публикуйте вакансии, собирайте резюме и управляйте процессом найма в одном удобном интерфейсе.
          </p>
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="<?php echo esc_url( jl_is_employer() ? jl_page_url( 'vacancy-form' ) : jl_page_url( 'register', array( 'role' => JL_ROLE_EMPLOYER ) ) ); ?>"
               class="bg-white text-brand font-medium px-6 py-3 rounded-xl hover:bg-blue-50 transition text-sm">
              Разместить вакансию
            </a>
            <a href="<?php echo esc_url( jl_is_employer() ? jl_page_url( 'candidates' ) : jl_page_url( 'login' ) ); ?>"
               class="border border-white/60 text-white font-medium px-6 py-3 rounded-xl hover:bg-white/10 transition text-sm">
              <?php echo jl_is_employer() ? 'Подобрать кандидатов' : 'Войти в кабинет'; ?>
            </a>
          </div>
        </div>

        <!-- Carousel -->
        <div class="w-full md:w-[380px] shrink-0">
          <div id="carousel" class="relative aspect-[4/3.6] rounded-2xl overflow-hidden bg-white/20">
            <img src="<?php echo esc_url( get_template_directory_uri() . '/img/team-slide-1.png' ); ?>" alt="Специалист за работой" class="carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-700 opacity-100">
            <img src="<?php echo esc_url( get_template_directory_uri() . '/img/team-slide-2.png' ); ?>" alt="Специалист на объекте" class="carousel-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-700 opacity-0">

            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
              <button class="carousel-dot w-2.5 h-2.5 rounded-full bg-white" data-index="0" aria-label="Слайд 1"></button>
              <button class="carousel-dot w-2.5 h-2.5 rounded-full bg-white/50" data-index="1" aria-label="Слайд 2"></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Career resources -->
  <section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
      <h2 class="text-2xl font-bold mb-8">Карьерные ресурсы</h2>

      <div class="grid md:grid-cols-3 gap-6">
        <article class="border border-slate-200 rounded-2xl p-6">
          <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <h3 class="font-semibold text-lg">Готовимся к собеседованию</h3>
          <p class="text-sm text-slate-500 mt-2">Полное руководство по подготовке к техническому интервью.</p>
          <a href="#" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-dark mt-4">
            Читать статью
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </article>

        <article class="border border-slate-200 rounded-2xl p-6">
          <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7M3 7l2-4h14l2 4M9 11h6"/>
            </svg>
          </div>
          <h3 class="font-semibold text-lg">Как написать резюме</h3>
          <p class="text-sm text-slate-500 mt-2">Советы по составлению резюме, которое точно попадёт в шорт-лист.</p>
          <a href="#" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-dark mt-4">
            Читать статью
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </article>

        <article class="border border-slate-200 rounded-2xl p-6">
          <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10H5a2 2 0 01-2-2V9a2 2 0 012-2h4m0 10h6m-6-10h6m0 0h4a2 2 0 012 2v6a2 2 0 01-2 2h-4m0-10v10"/>
            </svg>
          </div>
          <h3 class="font-semibold text-lg">Рынок труда в 2024</h3>
          <p class="text-sm text-slate-500 mt-2">Аналитический отчёт о самых востребованных профессиях.</p>
          <a href="#" class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-dark mt-4">
            Читать статью
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </article>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
