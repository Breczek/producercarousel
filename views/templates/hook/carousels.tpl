{foreach from=$producer_carousels item=carousel name=carouselIteration}
  {if !empty($carousel.items)}
    <section
      class="producer-carousel js-producer-carousel"
      data-items="{$carousel.count|intval}"
      data-speed="{$carousel.speed|intval}"
      aria-labelledby="{$carousel.id|escape:'htmlall':'UTF-8'}"
    >
      <h2 class="producer-carousel__title" id="{$carousel.id|escape:'htmlall':'UTF-8'}">
        {$carousel.title|escape:'htmlall':'UTF-8'}
      </h2>

      <div class="producer-carousel__viewport swiper js-producer-carousel-viewport">
        <div class="swiper-wrapper">
          {foreach from=$carousel.items item=entity}
            <div class="producer-carousel__item swiper-slide">
              <a class="producer-carousel__link" href="{$entity.url|escape:'htmlall':'UTF-8'}" aria-label="{$entity.name|escape:'htmlall':'UTF-8'}">
                {if $entity.image}
                  <img
                    class="producer-carousel__image"
                    src="{$entity.image|escape:'htmlall':'UTF-8'}"
                    alt="{$entity.name|escape:'htmlall':'UTF-8'}"
                    loading="lazy"
                  >
                {else}
                  <span class="producer-carousel__empty" aria-hidden="true"></span>
                {/if}
              </a>
            </div>
          {/foreach}
        </div>
      </div>

      <button class="swiper-button-prev js-producer-carousel-prev" type="button" aria-label="{l s='Poprzedni slajd' d='Modules.Producercarousel.Shop'}"></button>
      <button class="swiper-button-next js-producer-carousel-next" type="button" aria-label="{l s='Następny slajd' d='Modules.Producercarousel.Shop'}"></button>
    </section>
  {/if}
{/foreach}
