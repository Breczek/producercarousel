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
- Producent i dostawca mają osobne ustawienia liczby widocznych elementów oraz szybkości automatycznego przewijania.
- Liczba elementów oznacza liczbę logo widocznych równocześnie na dużym ekranie. Widok responsywny zmniejsza ją na mniejszych ekranach.
- Szybkość oznacza odstęp pomiędzy automatycznymi przesunięciami w milisekundach.
- Dostępne wartości są ograniczone do wartości podanych w formularzu.
- Wszystkie encje są domyślnie zaznaczone. W bazie zapisujemy listę wykluczeń, więc nowo utworzone encje będą automatycznie widoczne.
- Konfiguracja respektuje kontekst sklepu w trybie multistore przez użycie `Configuration`.

## Slider i zasoby

- Classic nie udostępnia stabilnego API wieloelementowej karuzeli przeznaczonego dla modułów.
- Używamy Swiper 12.2.0 na licencji MIT. Jest to wersja po poprawce bezpieczeństwa dotyczącej prototype pollution i zachowująca szerszą zgodność przeglądarek niż seria 14.
- Pliki Swiper są dostarczane lokalnie z modułem, bez CDN. Moduł nie zależy od dostępności zewnętrznego serwera i nie wysyła do niego danych odwiedzających.
- Skrypt inicjalizujący jest izolowany w kontenerze modułu i obsługuje wiele instancji widgetu na jednej stronie.
