(function () {
  'use strict';

  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /*
   * Swiper loop needs noticeably more slides than are visible at once. When there are
   * too few logos, the whole set is duplicated; copies are hidden from assistive tech
   * and keyboard focus so every logo is announced only once.
   */
  function fillSlidesForLoop(wrapper, minSlides) {
    var originals = Array.prototype.slice.call(wrapper.children);
    var total = originals.length;

    while (total < minSlides) {
      originals.forEach(function (slide) {
        var copy = slide.cloneNode(true);
        copy.classList.add('producer-carousel__item--copy');
        copy.setAttribute('aria-hidden', 'true');
        copy.querySelectorAll('a, button').forEach(function (focusable) {
          focusable.setAttribute('tabindex', '-1');
        });
        wrapper.appendChild(copy);
      });
      total += originals.length;
    }
  }

  function initProducerCarousels(root) {
    if (typeof window.Swiper !== 'function') {
      return;
    }

    (root || document).querySelectorAll('.js-producer-carousel:not([data-initialized])').forEach(function (element) {
      element.setAttribute('data-initialized', 'true');

      var viewport = element.querySelector('.js-producer-carousel-viewport');
      var wrapper = viewport.querySelector('.swiper-wrapper');
      var previous = element.querySelector('.js-producer-carousel-prev');
      var next = element.querySelector('.js-producer-carousel-next');
      var pagination = element.querySelector('.js-producer-carousel-pagination');
      var count = Math.max(1, parseInt(element.dataset.items, 10) || 6);
      var speed = Math.max(0, parseInt(element.dataset.speed, 10) || 0);
      var width = Math.max(0, parseInt(element.dataset.width, 10) || 0);
      var gap = Math.max(0, parseInt(element.dataset.gap, 10) || 0);
      var mode = element.dataset.mode || 'loop';
      var slideCount = wrapper.children.length;

      // With a fixed item width the number of visible logos depends on the viewport.
      var viewportStyle = window.getComputedStyle(viewport);
      var viewportWidth = viewport.clientWidth
        - (parseFloat(viewportStyle.paddingLeft) || 0)
        - (parseFloat(viewportStyle.paddingRight) || 0);
      var visible = width > 0
        ? Math.max(1, Math.ceil(viewportWidth / (width + gap)))
        : count;
      // Loop and marquee were chosen explicitly, so they run even when all logos fit on screen
      // (the set is duplicated below). Only the standard mode stops when there is nothing to scroll.
      var loop = mode === 'marquee' || mode === 'loop';
      var autoplay = speed > 0 && !reducedMotion && (loop || slideCount > visible);

      var options = {
        a11y: true,
        spaceBetween: gap,
        watchOverflow: !loop,
        loop: loop
      };

      if (width > 0) {
        options.slidesPerView = 'auto';
      } else {
        options.slidesPerView = 1;
        options.breakpoints = {
          576: { slidesPerView: Math.min(2, count) },
          768: { slidesPerView: Math.min(3, count) },
          992: { slidesPerView: count }
        };
      }

      if (previous && next) {
        options.navigation = { prevEl: previous, nextEl: next };
      }

      if (loop) {
        fillSlidesForLoop(wrapper, visible * 2 + 2);
      }

      if (pagination && wrapper.children.length > slideCount) {
        // Native bullets would also count the duplicated slides, so render one bullet per real logo.
        options.pagination = {
          el: pagination,
          type: 'custom',
          renderCustom: function (swiper) {
            var active = swiper.realIndex % slideCount;
            var label = element.dataset.bulletLabel || '';
            var html = '';
            for (var i = 0; i < slideCount; i++) {
              html += '<button type="button" class="swiper-pagination-bullet'
                + (i === active ? ' swiper-pagination-bullet-active' : '')
                + '" data-index="' + i + '" aria-label="' + label + ' ' + (i + 1) + '"'
                + (i === active ? ' aria-current="true"' : '') + '></button>';
            }
            return html;
          }
        };
      } else if (pagination) {
        options.pagination = {
          el: pagination,
          clickable: true,
          dynamicBullets: element.dataset.dots === 'dynamic'
        };
      }

      if (mode === 'marquee') {
        // Continuous ticker: no pause between slides, linear transition lasting "speed" ms per logo.
        options.speed = speed || 4000;
        options.allowTouchMove = false;
        if (autoplay) {
          options.autoplay = { delay: 0, disableOnInteraction: false };
        }
      } else if (autoplay) {
        options.autoplay = {
          delay: speed,
          disableOnInteraction: false,
          pauseOnMouseEnter: true
        };
        options.rewind = !loop;
      }

      var swiper = new window.Swiper(viewport, options);

      if (pagination && options.pagination.type === 'custom') {
        pagination.addEventListener('click', function (event) {
          var bullet = event.target.closest('[data-index]');
          if (bullet) {
            swiper.slideToLoop(parseInt(bullet.dataset.index, 10));
          }
        });
      }
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
