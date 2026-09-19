(function () {
  const slides = document.querySelectorAll('.carousel-slide');
  const dots = document.querySelectorAll('.carousel-dot');
  let current = 0;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('opacity-100', i === index);
      slide.classList.toggle('opacity-0', i !== index);
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('bg-white', i === index);
      dot.classList.toggle('bg-white/50', i !== index);
    });
    current = index;
  }

  dots.forEach((dot) => {
    dot.addEventListener('click', () => showSlide(parseInt(dot.dataset.index, 10)));
  });

  setInterval(() => {
    showSlide((current + 1) % slides.length);
  }, 4000);
})();
