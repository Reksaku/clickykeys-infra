# Privacy policy — analytics section

---

## Polski

**Analityka i statystyki odwiedzin**

Nasza strona zbiera anonimowe dane statystyczne we własnym zakresie (bez korzystania z
zewnętrznych narzędzi typu Google Analytics) wyłącznie w celu zrozumienia, jak strona
jest używana, i jej ulepszania. Nie używamy plików cookie do celów analitycznych ani nie
tworzymy trwałych profili użytkowników.

Dla każdej wizyty zapisujemy: odwiedzony adres strony, adres strony odsyłającej
(referrer), typ urządzenia, przeglądarkę i system operacyjny (na podstawie nagłówka
User-Agent), rozmiar okna przeglądarki, czas ładowania strony oraz **kraj** ustalony na
podstawie adresu IP. Adres IP jest **anonimizowany** (usuwamy jego końcową część) zanim
zostanie zapisany — nie przechowujemy pełnego adresu IP. Identyfikator sesji jest
tymczasowy, przechowywany wyłącznie w pamięci karty przeglądarki (sessionStorage) i
znika po jej zamknięciu.

Zebrane dane mają charakter statystyczny i zbiorczy, nie pozwalają na identyfikację
konkretnej osoby. Podstawą prawną przetwarzania jest nasz uzasadniony interes
(art. 6 ust. 1 lit. f RODO) polegający na prowadzeniu statystyki oglądalności i
zapewnieniu prawidłowego działania strony.

---

## English

**Analytics and visit statistics**

This website collects anonymous usage statistics using our own first-party system (we do
**not** use third-party tools such as Google Analytics), solely to understand how the site
is used and to improve it. We do **not** use cookies for analytics and we do **not** build
persistent user profiles.

For each visit we record: the page visited, the referring page, the device type, the
browser and operating system (from the User-Agent header), the browser window size, the
page load time, and the **country** derived from the IP address. The IP address is
**anonymized** (its final part is removed) before it is stored — we never keep the full IP
address. The session identifier is temporary, kept only in the browser tab's memory
(sessionStorage), and is discarded when the tab is closed.

The collected data is statistical and aggregated and does not allow identification of an
individual. The legal basis for processing is our legitimate interest (Art. 6(1)(f) GDPR)
in maintaining audience statistics and ensuring the site functions correctly.

---

# Privacy policy — desktop app telemetry section

Ready-to-paste paragraphs describing the **opt-in** telemetry sent by the ClickyKeys
desktop application (a separate mechanism from the website analytics above). This is a
**separate section** on purpose: unlike the website page-view analytics, which relies on
legitimate interest and no cookies, the app telemetry is **consent-based** (Art. 6(1)(1)(a)
GDPR) and must be described on its own so the two are never conflated. Keep this section on
the same `/privacy` page the app's consent dialog links to.

The app offers three levels the user chooses from, with no pre-selected default: **None**
(nothing is ever sent), **Basic**, and **Full**. The choice can be changed at any time in
the app's settings, which makes withdrawing consent as easy as giving it. A single event is
sent once per app launch, and only when the level is not None.

---

## Polski

**Telemetria aplikacji desktopowej (dobrowolna, za zgodą)**

Aplikacja ClickyKeys może — **wyłącznie za Twoją zgodą** — wysyłać anonimowe dane o użyciu.
Jest to mechanizm odrębny od statystyk odwiedzin strony opisanych powyżej. Wybierasz jeden z
trzech poziomów, bez domyślnie zaznaczonej opcji: **Brak** (nic nie jest wysyłane),
**Podstawowy** lub **Pełny**. Wybór możesz zmienić w każdej chwili w ustawieniach aplikacji —
wycofanie zgody jest tak samo proste jak jej udzielenie. Dane wysyłane są raz przy każdym
uruchomieniu aplikacji i tylko wtedy, gdy poziom jest inny niż Brak.

Do Twoich danych przypisany jest losowy identyfikator (`user_id`) generowany raz na Twoim
urządzeniu **dopiero po wyrażeniu zgody**. Nie jest to identyfikator sprzętowy ani powiązany
z kontem Windows; znika po usunięciu pliku konfiguracyjnego aplikacji. Podstawą prawną
przetwarzania jest **Twoja zgoda** (art. 6 ust. 1 lit. a RODO).

Poziom **Podstawowy** wysyła dane identyfikujące wersję i środowisko:
identyfikator `user_id`, wersję aplikacji, kanał dystrybucji, wersję systemu operacyjnego,
język interfejsu, typ zdarzenia (uruchomienie aplikacji) oraz znacznik czasu. Wysyła również
dwie liczby zbiorcze: jedną łączną liczbę wszystkich zdarzeń wejścia (suma naciśnięć klawiszy,
kliknięć myszy, ruchów kółka i naciśnięć gamepada) oraz łączny czas działania aplikacji w
sekundach. Na poziomie Podstawowym są to wyłącznie te dwie sumy — bez podziału na
poszczególne kategorie (ten podział istnieje tylko na poziomie Pełnym).

Poziom **Pełny** wysyła wszystko to, co Podstawowy, oraz zestaw liczników i przełączników
opisujących sposób skonfigurowania aplikacji. Są to wyłącznie liczby i wartości
logiczne — **nigdy** nie wysyłamy etykiet ani opisów paneli, dokładnych kolorów, przypisanych
skrótów klawiszowych ani ścieżek plików. Konkretnie:

- układ siatki: liczba wierszy i kolumn;
- czy dostosowano kolory; czy dostosowano czcionki;
- czy włączono animowane tęczowe tło i jego prędkość (w sekundach);
- liczba skonfigurowanych paneli oraz ile z nich używa wejścia klawiatury, myszy, kółka lub
  gamepada;
- próg wyzwalania gamepada;
- czy włączono autostart; czy uruchamianie zminimalizowane; czy dostosowano skróty; czy
  ukończono samouczek;
- łączne liczniki: naciśnięcia klawiszy, kliknięcia myszy, ruchy kółka, naciśnięcia gamepada
  oraz łączny czas działania (w sekundach).

Zakres pola Pełnego został celowo ograniczony do liczników i wartości logicznych zgodnie z
zasadą minimalizacji danych (art. 5 ust. 1 lit. c RODO). **Prawo do usunięcia danych:**
ponieważ identyfikator `user_id` nie jest przez nas powiązany z osobą, aby usunąć swoje dane,
podaj nam swój `user_id`, a usuniemy powiązane z nim wiersze.

---

## English

**Desktop app telemetry (opt-in, consent-based)**

The ClickyKeys desktop application can — **only with your consent** — send anonymous usage
data. This is a mechanism separate from the website visit statistics described above. You
choose one of three levels, with no option pre-selected: **None** (nothing is ever sent),
**Basic**, or **Full**. You can change the choice at any time in the app's settings, which
makes withdrawing consent as easy as giving it. Data is sent once per app launch, and only
when the level is not None.

Your data is associated with a random identifier (`user_id`) generated once on your device
**only after you consent**. It is not a hardware ID and is not tied to a Windows account; it
disappears if the app's configuration file is deleted. The legal basis for processing is
**your consent** (Art. 6(1)(a) GDPR).

The **Basic** level sends fields that identify the version and environment: the
`user_id`, the app version, the distribution channel, the operating-system version, the
interface language, the event type (app launch), and a timestamp. It also sends two
aggregate numbers: a single combined count of all input events (key presses, mouse clicks,
wheel ticks and gamepad presses added together) and the app's total uptime in seconds. At
Basic these are only these two totals — there is no per-category breakdown (that split
exists only at the Full level).

The **Full** level sends everything Basic does, plus a set of counters and flags describing
how the app is configured. These are only numbers and booleans — we **never** send panel
labels or descriptions, exact colors, assigned shortcut keys, or file paths. Specifically:

- grid layout: number of rows and columns;
- whether colors were customized; whether fonts were customized;
- whether the animated rainbow background is enabled, and its speed (in seconds);
- the number of configured panels, and how many of them use keyboard, mouse, wheel, or
  gamepad input;
- the gamepad trigger threshold;
- whether autostart is enabled; whether it starts minimized; whether shortcuts were
  customized; whether the tutorial was completed;
- lifetime totals: key presses, mouse clicks, wheel ticks, gamepad presses, and total uptime
  (in seconds).

The Full-tier field list was deliberately scoped to counters and booleans in line with data
minimization (Art. 5(1)(c) GDPR). **Right to erasure:** because the `user_id` is not linked
to a person on our side, to have your data removed you supply us your `user_id` and we delete
the rows associated with it.
