# Producer Carousel — decyzje projektowe

## Zakres

- Moduł ma nazwę techniczną `producercarousel` i nazwę widoczną „Karuzela producentów i dostawców”.
- Wspierane wersje: PrestaShop 8.x i 9.x oraz PHP zgodne z tymi wersjami.
- Moduł wyświetla dwie niezależne karuzele: producentów i dostawców.
- Domyślnym miejscem instalacji jest `displayHome`.
- Moduł implementuje `PrestaShop\PrestaShop\Core\Module\WidgetInterface`, dzięki czemu można go wywołać w dowolnym hooku.
- Przykładowe wywołania widgetów są wyświetlane na stronie konfiguracji modułu.

## Dane i zachowanie front office

- Nazwa, logo oraz adres strony są pobierane z natywnych danych producenta/dostawcy w PrestaShop.
- Kliknięcie logo prowadzi do natywnej strony producenta lub dostawcy.
- Atrybut `alt` obrazu zawiera nazwę encji.
- Encja bez logo pozostaje na liście i zajmuje puste miejsce. Administrator może ją wyłączyć w konfiguracji.
- Na froncie nie pokazujemy encji nieaktywnych, nawet jeśli nie zostały wykluczone w konfiguracji.
- Parametr `type` widgetu przyjmuje `all`, `manufacturers` albo `suppliers` i filtruje, która karuzela (lub obie) się renderuje — to jest mechanizm „osobnych widgetów” dla producentów i dostawców, wywoływanych np. `{widget name='producercarousel' type='manufacturers'}`.
- Hook `displayHome` bez jawnego parametru `type` używa domyślnie wartości z ustawienia „Co wyświetlać w głównym hooku” (`PC_DISPLAY_MODE`, patrz niżej).

## Konfiguracja back office

- Administrator wybiera w konfiguracji modułu, co ma się wyświetlać w domyślnym hooku `displayHome`: obie karuzele, tylko producenci albo tylko dostawcy (`PC_DISPLAY_MODE`, domyślnie `all`).
- Producent i dostawca mają osobne, pełne zestawy ustawień karuzeli (formularz ma trzy sekcje: ogólne, producenci, dostawcy). Klucze mają postać `PC_MFR_<KLUCZ>` / `PC_SUP_<KLUCZ>`, a definicje z dozwolonymi wartościami są w jednej stałej `CAROUSEL_SETTINGS`.
- Liczba elementów oznacza liczbę logo widocznych równocześnie na dużym ekranie. Widok responsywny zmniejsza ją na mniejszych ekranach.
- Szybkość oznacza odstęp pomiędzy automatycznymi przesunięciami w milisekundach.
- Tryb przewijania (`MODE`): `slide` (zatrzymuje się na końcu, autoplay wraca na początek przez `rewind`), `loop` (nieskończona pętla, domyślny — zachowuje dotychczasowe zachowanie) oraz `marquee` (ciągły, liniowy przesuw „infinity ticker”). W trybie `marquee` ustawienie szybkości oznacza czas przejazdu jednego logo, a przeciąganie palcem jest wyłączone.
- Styl strzałek (`ARROWS`): `none`, `minimal`, `circle`, `square`. Styl paginacji (`DOTS`): `none` (domyślnie), `dots`, `lines`, `dynamic`. Kolory ustawia się w motywie przez zmienne CSS `--producer-carousel-accent` i `--producer-carousel-accent-contrast`.
- Wymiary podawane ręcznie w px: szerokość elementu (`WIDTH`, 40–600, 0 = wg liczby elementów; podana szerokość przełącza Swiper na `slidesPerView: 'auto'` i zastępuje liczbę elementów), wysokość elementu (`HEIGHT`, 30–400, 0 = domyślna) i odstęp (`GAP`, 0–100, domyślnie 16).
- Dostępne wartości są ograniczone do wartości podanych w formularzu; pola liczbowe są walidowane zakresem.
- Nowe klucze konfiguracji tworzy `installDefaults()` wywoływane przy instalacji i w `upgrade/upgrade-1.1.0.php`; istniejące wartości nie są nadpisywane. Brakujące lub błędne wartości są na froncie zastępowane domyślnymi.
- Wszystkie encje są domyślnie zaznaczone. W bazie zapisujemy listę wykluczeń, więc nowo utworzone encje będą automatycznie widoczne.
- Konfiguracja respektuje kontekst sklepu w trybie multistore przez użycie `Configuration`.

## Slider i zasoby

- Classic nie udostępnia stabilnego API wieloelementowej karuzeli przeznaczonego dla modułów.
- Używamy Swiper 12.2.0 na licencji MIT. Jest to wersja po poprawce bezpieczeństwa dotyczącej prototype pollution i zachowująca szerszą zgodność przeglądarek niż seria 14.
- Pliki Swiper są dostarczane lokalnie z modułem, bez CDN. Moduł nie zależy od dostępności zewnętrznego serwera i nie wysyła do niego danych odwiedzających.
- Skrypt inicjalizujący jest izolowany w kontenerze modułu i obsługuje wiele instancji widgetu na jednej stronie.
- Tryby `loop` i `marquee` działają zawsze, także gdy wszystkie logo mieszczą się na ekranie (wybór administratora jest jawny). Tylko tryb `slide` używa `watchOverflow` i ukrywa strzałki/kropki, gdy nie ma czego przewijać.
- Pętla Swipera wymaga więcej slajdów niż widać naraz. Gdy logo jest za mało, JS powiela cały zestaw; kopie mają `aria-hidden` i `tabindex="-1"`, więc czytnik ekranu i klawiatura widzą każde logo raz. Przy powielonych slajdach paginację renderuje moduł (`type: 'custom'`) — jedna kropka na prawdziwe logo; styl `dynamic` wygląda wtedy jak zwykłe kropki.
- Przy `prefers-reduced-motion: reduce` autoplay i przesuw ciągły są wyłączone.
- Swiper 12 wstrzykuje strzałkę jako SVG (`.swiper-navigation-icon`), więc style strzałek stylują przycisk i SVG, a nie ikonę z fontu.
