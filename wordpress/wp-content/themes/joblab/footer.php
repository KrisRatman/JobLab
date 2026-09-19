  <!-- Footer -->
  <footer class="bg-slate-950 text-slate-300">
    <div class="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-4 gap-10">
      <div>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 mb-4">
          <span class="w-9 h-9 rounded-lg bg-brand text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>
            </svg>
          </span>
          <span class="text-xl font-bold text-white"><?php bloginfo( 'name' ); ?></span>
        </a>
        <p class="text-sm text-slate-400 max-w-xs">Платформа для поиска работы и найма персонала. Более 10 миллионов пользователей.</p>
      </div>

      <div>
        <h4 class="text-white font-semibold mb-4">Соискателям</h4>
        <ul class="space-y-3 text-sm">
          <li><a href="#" class="hover:text-white">Поиск работы</a></li>
          <li><a href="#" class="hover:text-white">Резюме</a></li>
          <li><a href="#" class="hover:text-white">Карьерные советы</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-semibold mb-4">Работодателям</h4>
        <ul class="space-y-3 text-sm">
          <li><a href="#" class="hover:text-white">Разместить вакансию</a></li>
          <li><a href="#" class="hover:text-white">База резюме</a></li>
          <li><a href="#" class="hover:text-white">Тарифы</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-semibold mb-4">Компания</h4>
        <ul class="space-y-3 text-sm">
          <li><a href="#" class="hover:text-white">О нас</a></li>
          <li><a href="#" class="hover:text-white">Контакты</a></li>
          <li><a href="#" class="hover:text-white">Партнёрам</a></li>
        </ul>
      </div>
    </div>

    <div class="border-t border-white/10">
      <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-3 text-sm text-slate-400">
        <p>© <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. Все права защищены.</p>
        <div class="flex gap-6">
          <a href="#" class="hover:text-white">Политика конфиденциальности</a>
          <a href="#" class="hover:text-white">Пользовательское соглашение</a>
        </div>
      </div>
    </div>
  </footer>

<?php wp_footer(); ?>
</body>
</html>
