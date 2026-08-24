(function () {
  'use strict';

  function initProducerCarousels(root) {
    if (typeof window.Swiper !== 'function') {
      return;
    }

    (root || document).querySelectorAll('.js-producer-carousel:not([data-initialized])').forEach(function (element) {
      element.setAttribute('data-initialized', 'true');

      var viewport = element.querySelector('.js-producer-carousel-viewport');
      var previous = element.querySelector('.js-producer-carousel-prev');
      var next = element.querySelector('.js-producer-carousel-next');
      var count = Math.max(1, parseInt(element.dataset.items, 10) || 6);
      var speed = Math.max(0, parseInt(element.dataset.speed, 10) || 0);
      var slideCount = viewport.querySelectorAll('.swiper-slide').length;

      var options = {
        a11y: true,
        slidesPerView: 1,
        spaceBetween: 16,
        watchOverflow: true,
        navigation: {
          prevEl: previous,
          nextEl: next
        },
        breakpoints: {
          576: { slidesPerView: Math.min(2, count) },
          768: { slidesPerView: Math.min(3, count) },
          992: { slidesPerView: count }
        }
      };

      if (speed > 0 && slideCount > count) {
        options.autoplay = {
          delay: speed,
          disableOnInteraction: false,
          pauseOnMouseEnter: true
        };
        options.loop = slideCount > count;
      }

      new window.Swiper(viewport, options);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initProducerCarousels(document);
    });
  } else {
    initProducerCarousels(document);
  }

  if (window.prestashop && typeof window.prestashop.on === 'function') {
    window.prestashop.on('updatedProduct', function () {
      initProducerCarousels(document);
    });
  }
})();
