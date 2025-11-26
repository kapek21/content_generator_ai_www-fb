# Changelog

Wszystkie istotne zmiany w projekcie AI Content Publisher będą dokumentowane w tym pliku.

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/),
projekt stosuje [Semantic Versioning](https://semver.org/lang/pl/).

## [1.5.0] - 2025-11-01

### Dodane
- **🔧 Panel diagnostyczny WP-Cron** - sprawdź status automatycznego generowania
  - Pokazuje czy automatyczne generowanie jest włączone
  - Pokazuje ostatnie uruchomienie crona
  - Pokazuje następne zaplanowane uruchomienie
  - Sprawdza status WP-Cron (włączony/wyłączony w wp-config.php)
  - **Przycisk do ręcznego uruchomienia crona** - testuj automatyczne generowanie natychmiast!
  
- **📊 Szczegółowe logowanie aktywności cron**
  - Logowanie każdego uruchomienia do error_log
  - Informacja o liczbie sprawdzonych kategorii
  - Informacja o liczbie wygenerowanych artykułów
  - Informacja o liczbie pominiętych kategorii (nie nadszedł czas)
  - Szczegółowe logi błędów dla każdej kategorii
  
- **Zapisywanie czasu ostatniego uruchomienia crona** w opcjach WordPress

### Zmienione
- Funkcja `auto_generate_content()` teraz loguje szczegółowo każdy krok
- Dodano liczniki wygenerowanych i pominiętych kategorii
- Ulepszona diagnostyka problemów z automatycznym generowaniem

### Naprawione
- **Problem z automatycznym generowaniem** - dodano narzędzia diagnostyczne do debugowania

### Jak używać:
1. Przejdź do **AI Publisher → Panel Główny**
2. Zobacz sekcję "🤖 Status Automatycznego Generowania"
3. Sprawdź czy WP-Cron jest włączony
4. Kliknij "**▶️ Uruchom sprawdzenie TERAZ**" aby przetestować automatyczne generowanie
5. Sprawdź **Historia** aby zobaczyć czy artykuły zostały wygenerowane
6. Sprawdź logi serwera (`wp-content/debug.log`) aby zobaczyć szczegóły działania crona

### Troubleshooting:
- **Jeśli WP-Cron jest wyłączony:** Musisz skonfigurować prawdziwy cron serwera
- **Jeśli "Ostatnie uruchomienie" = "Nigdy":** WP-Cron się nie uruchamia - sprawdź czy odwiedzasz stronę regularnie
- **Jeśli "Brak zaplanowanego uruchomienia":** Dezaktywuj i ponownie aktywuj wtyczkę

---

## [1.4.0] - 2025-10-31

### Dodane
- **🎯 Fokus na województwo - Artykuły WYŁĄCZNIE o wybranym regionie**
  - Dropdown z listą wszystkich 16 polskich województw
  - Opcja "Własna nazwa" dla języków obcych (niemiecki, angielski, ukraiński)
  - Walidacja przed generowaniem - wymusza wybór województwa
  - Komunikat błędu gdy województwo nie jest ustawione
  
### Zmienione
- **Znacząco wzmocnione prompty AI** - artykuły MUSZĄ dotyczyć WYŁĄCZNIE wybranego województwa:
  - Perplexity API: Szuka newsów TYLKO z wybranego województwa
  - OpenAI API: Generuje artykuły WYŁĄCZNIE o wybranym województwie
  - Wymóg 5-7 wzmianek o województwie (było 3)
  - Zakaz pisania o innych województwach jako głównym temacie
  - Wszystkie przykłady, dane, cytaty MUSZĄ być z wybranego województwa
  - Jeśli informacje nie dotyczą wybranego województwa, AI ich NIE użyje
- Zaktualizowano wszystkie prompty dla 4 języków (PL, DE, EN, UK)
- Zmiana pola tekstowego na dropdown dla lepszej kontroli
- Automatyczna obsługa custom regionu dla języków obcych

### Cel aktualizacji
- **100% trafność regionalną** - artykuły zawsze o właściwym województwie
- Eliminacja treści o innych regionach
- Lepsze pozycjonowanie lokalne w Google
- Większe zaangażowanie lokalnej społeczności
- Wyższa wartość dla lokalnych reklamodawców w AdSense

---

## [1.3.0] - 2025-10-31

### Dodane
- **💎 Premium Content - Artykuły wysokiej jakości dla Google AdSense**
  - Znacząco ulepszone prompty AI dla generowania wartościowych treści
  - System prompt: Nagradzany dziennikarz analityczny, ekspert od premium content
  - Zwiększona długość artykułów: domyślnie 1600 słów (rekomendacja: 1500-1800)
  - 9 szczegółowych wymagań jakościowych w każdym języku:
    1. Długość i głębia (1500-1800 słów)
    2. Lokalizacja (5-7 wzmianek o regionie)
    3. Organiczne wplecenie słów kluczowych
    4. Premium struktura (H1, lead, 4-6 sekcji H2, podsekcje H3)
    5. Wartość merytoryczna (konkretne dane, cytaty ekspertów, analiza przyczyn-skutków)
    6. Jakość językowa (bogate słownictwo, precyzja, zero powtórzeń)
    7. Storytelling (mikrohistorie, case studies, humanizacja)
    8. Optymalizacja SEO i AdSense (naturalne słowa kluczowe, pytania retoryczne)
    9. Unikalne elementy (własna analiza, porównania, prognozy)

### Zmienione
- Zaktualizowano wszystkie prompty dla 4 języków (PL, DE, EN, UK)
- Zwiększono domyślną długość artykułów z 1200 do 1600 słów
- Zaktualizowano minimum długości artykułów z 500 do 800 słów
- Dodano wskazówki w ustawieniach o rekomendowanej długości dla premium content
- Artykuły teraz zawierają znacznie więcej:
  - Konkretnych danych i statystyk
  - Cytatów ekspertów
  - Analiz przyczyn i skutków
  - Kontekstu historycznego
  - Różnych perspektyw
  - Elementów storytellingu
  - Prognoz i rekomendacji

### Cel aktualizacji
- Maksymalizacja wartości treści dla Google AdSense
- Generowanie artykułów klasy premium zamiast standardowych
- Zwiększenie zaangażowania czytelników
- Lepsze pozycjonowanie w Google (SEO)
- Wyższa stawka CPC/CPM w AdSense dzięki jakości treści

---

## [1.2.0] - 2025-10-07

### Dodane
- **🌍 Wsparcie dla wielu języków (Multi-language support)**
  - Obsługa 4 języków: Polski, Niemiecki, Angielski, Ukraiński
  - Wybór języka treści w ustawieniach
  - Wszystkie prompty AI dostosowane do wybranego języka:
    - Perplexity API - wyszukiwanie newsów w wybranym języku
    - OpenAI GPT-4o - generowanie artykułów w wybranym języku
    - OpenAI GPT-4o-mini - generowanie postów Facebook w wybranym języku
  - Automatyczne dostosowanie tonu i stylu do kultury językowej
  - Wsparcie dla regionalnych nazw (województwo/Bundesland/state/регіон)
  
### Zmienione
- Zaktualizowano wszystkie API calls o parametr języka
- Rozszerzono system promptów o wielojęzyczne szablony
- Dodano funkcje pomocnicze do mapowania języków w głównej klasie

---

## [1.1.0] - 2025-10-04

### Dodane
- **Zarządzanie częstotliwością dla każdej kategorii osobno**
  - Panel wyboru częstotliwości w Dashboard (kolumna w tabeli kategorii)
  - Panel zarządzania częstotliwością w Ustawieniach
  - Opcje częstotliwości: Codziennie, Co 2 dni, Co 3 dni, Raz w tygodniu, Raz na 2 tygodnie, Raz w miesiącu, Wyłączone
- Wyświetlanie daty ostatniego generowania dla każdej kategorii
- Wyświetlanie przewidywanej następnej daty generowania
- AJAX zapisywanie częstotliwości (bez przeładowania strony)
- Inteligentne sprawdzanie czy kategoria powinna być generowana (w oparciu o częstotliwość)
- Śledzenie dat ostatnich generacji dla każdej kategorii
- **Opcjonalna publikacja na Facebook**
  - Checkbox włączania/wyłączania publikacji na Facebook
  - Automatyczne ukrywanie konfiguracji Facebook gdy wyłączone
  - Brak wymogu konfiguracji Facebook API jeśli opcja wyłączona
  - Test połączeń pomija Facebook gdy wyłączony

### Zmienione
- **WAŻNE: Zaktualizowano modele Perplexity API do aktualnych, dozwolonych modeli**
  - Model testowy: `sonar` (SearchModels)
  - Model wyszukiwania newsów: `sonar-pro` (ResearchModels - do szczegółowych analiz)
  - Usunięto przestarzałe modele `llama-3.1-sonar-*-online`
- Cron job teraz uwzględnia indywidualną częstotliwość każdej kategorii
- Dashboard pokazuje więcej informacji o statusie kategorii
- Ustawienia zawierają dedykowaną sekcję zarządzania częstotliwością
- Generator treści pomija generowanie i publikację postów na Facebook jeśli opcja wyłączona
- Konfiguracja Facebook oznaczona jako "Opcjonalna"

## [1.0.0] - 2025-10-04

### Dodane
- Integracja z Perplexity API do wyszukiwania aktualnych newsów
- Integracja z OpenAI API (GPT-4o i DALL-E 3)
- Integracja z Facebook Graph API
- Automatyczne generowanie artykułów dla kategorii WordPress
- Automatyczne generowanie obrazów przez DALL-E 3
- Automatyczna publikacja postów na Facebook
- Panel administracyjny z trzema sekcjami:
  - Panel główny (Dashboard)
  - Ustawienia
  - Historia publikacji
- System harmonogramu (WP-Cron) do codziennego automatycznego generowania
- Wykrywanie województwa z nazwy domeny
- Konfiguracja słów kluczowych
- Historia publikacji z filtrowaniem i statystykami
- Test połączeń API
- Tabela w bazie danych dla historii generacji
- Interfejs AJAX dla generowania treści
- System logowania w czasie rzeczywistym
- Responsive design panelu administracyjnego
- Dokumentacja (README.md)

### Funkcje techniczne
- Weryfikacja kluczy API
- Obsługa błędów i timeoutów
- Progress bar dla generowania wielu artykułów
- Automatyczne dodawanie obrazów do media library
- Optymalizacja SEO artykułów
- Meta dane dla wygenerowanych postów
- Zabezpieczenia (nonce, user capabilities)
- Paginacja w historii publikacji

### Bezpieczeństwo
- Walidacja wszystkich danych wejściowych
- Escape wszystkich danych wyjściowych
- Nonce verification dla AJAX requests
- Capability checks dla działań administracyjnych
- Bezpieczne przechowywanie kluczy API w opcjach WordPress

## [Planowane] - Przyszłe wersje

### Do rozważenia w v1.1.0
- [ ] Podgląd artykułu przed publikacją
- [ ] Możliwość edycji artykułu przed publikacją
- [ ] Wsparcie dla custom post types
- [ ] Export/import ustawień
- [ ] Backup i restore historii

### Do rozważenia w v1.2.0
- [ ] Integracja z Instagram
- [ ] Integracja z Twitter/X
- [ ] Zaplanowane publikacje (custom schedule)
- [ ] Kolejka publikacji

### Do rozważenia w v2.0.0
- [x] Multi-language support (zrealizowane w v1.2.0)
- [ ] Custom prompts przez interfejs
- [ ] A/B testing tytułów
- [ ] Analytics i reporting
- [ ] Webhook notifications
- [ ] REST API endpoints

---

**Legenda:**
- `Dodane` - nowe funkcje
- `Zmienione` - zmiany w istniejącej funkcjonalności
- `Przestarzałe` - funkcje, które wkrótce zostaną usunięte
- `Usunięte` - usunięte funkcje
- `Naprawione` - poprawki błędów
- `Bezpieczeństwo` - zmiany związane z bezpieczeństwem
