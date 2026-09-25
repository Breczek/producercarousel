{foreach from=$producer_carousels item=carousel name=carouselIteration}
  {if !empty($carousel.items)}
    {assign var=settings value=$carousel.settings}
    <section
      class="producer-carousel js-producer-carousel producer-carousel--mode-{$settings.mode|escape:'htmlall':'UTF-8'} producer-carousel--arrows-{$settings.arrows|escape:'htmlall':'UTF-8'} producer-carousel--dots-{$settings.dots|escape:'htmlall':'UTF-8'}{if $settings.width} producer-carousel--fixed-width{/if}"
      data-items="{$settings.count|intval}"
      data-speed="{$settings.speed|intval}"
      data-mode="{$settings.mode|escape:'htmlall':'UTF-8'}"
      data-dots="{$settings.dots|escape:'htmlall':'UTF-8'}"
      data-width="{$settings.width|intval}"
      data-gap="{$settings.gap|intval}"
      style="{if $settings.width}--producer-carousel-item-width: {$settings.width|intval}px;{/if}{if $settings.height}--producer-carousel-height: {$settings.height|intval}px;{/if}"
      aria-labelledby="{$carousel.id|escape:'htmlall':'UTF-8'}"
    >
      <h2 class="producer-carousel__title" id="{$carousel.id|escape:'htmlall':'UTF-8'}">
        {$carousel.title|escape:'htmlall':'UTF-8'}
      </h2>

      <div class="producer-carousel__stage">
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

        {if $settings.arrows != 'none'}
          <button class="producer-carousel__arrow swiper-button-prev js-producer-carousel-prev" type="button" aria-label="{l s='Poprzedni slajd' d='Modules.Producercarousel.Shop'}"></button>
          <button class="producer-carousel__arrow swiper-button-next js-producer-carousel-next" type="button" aria-label="{l s='Następny slajd' d='Modules.Producercarousel.Shop'}"></button>
        {/if}
      </div>

      {if $settings.dots != 'none'}
        <div class="producer-carousel__pagination swiper-pagination js-producer-carousel-pagination"></div>
      {/if}
    </section>
  {/if}
{/foreach}
