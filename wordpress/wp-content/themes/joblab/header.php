<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-white text-slate-900 antialiased' ); ?>>
<?php wp_body_open(); ?>

  <!-- Header -->
  <header class="border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
        <span class="w-9 h-9 rounded-lg bg-brand text-white flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>
          </svg>
        </span>
        <span class="text-xl font-bold"><?php bloginfo( 'name' ); ?></span>
      </a>

      <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
        <a href="#" class="hover:text-brand">Вакансии</a>
        <a href="#" class="hover:text-brand">Резюме</a>
        <a href="#" class="hover:text-brand">Компании</a>
        <a href="#" class="hover:text-brand">Карьера</a>
      </nav>

      <a href="#" class="inline-flex items-center gap-2 bg-slate-900 text-white text-xs md:text-sm font-medium px-3 py-2 md:px-5 md:py-2.5 rounded-full hover:bg-slate-800 transition whitespace-nowrap">
        Для работодателей
        <svg xmlns="http://www.w3.org/2000/svg" class="hidden md:block w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>
  </header>
