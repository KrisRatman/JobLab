<?php get_header(); ?>

  <section class="max-w-7xl mx-auto px-6 py-16">
    <?php if ( have_posts() ) : ?>
      <?php while ( have_posts() ) : the_post(); ?>
        <article class="border border-slate-200 rounded-2xl p-6 mb-4">
          <h2 class="font-semibold text-lg"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="text-sm text-slate-500 mt-2"><?php the_excerpt(); ?></div>
        </article>
      <?php endwhile; ?>
    <?php else : ?>
      <p>Ничего не найдено.</p>
    <?php endif; ?>
  </section>

<?php get_footer(); ?>
