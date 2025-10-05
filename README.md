# AI Content Publisher - Wtyczka WordPress

🤖 Automatyczne generowanie i publikowanie artykułów wykorzystując sztuczną inteligencję (Perplexity, OpenAI) z integracją Facebook.

## 📋 Spis treści

- [Opis](#opis)
- [Funkcjonalności](#funkcjonalności)
- [Wymagania](#wymagania)
- [Instalacja](#instalacja)
- [Konfiguracja](#konfiguracja)
- [Użytkowanie](#użytkowanie)
- [API i koszty](#api-i-koszty)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [Bezpieczeństwo](#bezpieczeństwo)
- [FAQ](#faq)

## 📝 Opis

AI Content Publisher to zaawansowana wtyczka WordPress, która automatyzuje proces tworzenia i publikowania treści. Wtyczka:

1. **Wyszukuje aktualne informacje** przez Perplexity API (newsy, wydarzenia z ostatnich dni)
2. **Generuje artykuły** używając GPT-4 (OpenAI) - artykuły o długości ~1200 słów, zoptymalizowane pod SEO
3. **Tworzy obrazy** przez DALL-E 3 (OpenAI)
4. **Publikuje w WordPress** jako gotowe wpisy z obrazem wyróżniającym
5. **Generuje posty na Facebook** i publikuje je automatycznie na Twojej stronie

## ✨ Funkcjonalności

### Główne funkcje:

- ✅ **Automatyczne wyszukiwanie newsów** - Perplexity przeszukuje internet w poszukiwaniu najnowszych informacji
- ✅ **Inteligentne generowanie treści** - GPT-4 tworzy wysokiej jakości artykuły dziennikarskie
- ✅ **Generowanie obrazów AI** - DALL-E 3 tworzy unikalne obrazy dla każdego artykułu
- ✅ **Publikacja na Facebook** - automatyczne posty ze zdjęciami i linkami
- ✅ **Optymalizacja SEO** - naturalne zagęszczenie słów kluczowych
- ✅ **Wykrywanie województwa** - automatyczne rozpoznawanie regionu z nazwy domeny
- ✅ **Konfigurowana częstotliwość** - indywidualna częstotliwość publikacji dla każdej kategorii (codziennie, co 2 dni, co tydzień, etc.)
- ✅ **Harmonogram automatyczny** - inteligentne generowanie według ustawionej częstotliwości
- ✅ **Historia publikacji** - pełna historia z filtrami i statystykami
- ✅ **Panel administracyjny** - intuicyjny interfejs zarządzania

### Szczegóły techniczne:

- Artykuły zawierają nazwę województwa minimum 3x
- Długość artykułu: konfigurowalna (domyślnie 1200 słów)
- Struktura: tytuł (H1), wprowadzenie, sekcje z podtytułami (H2), podsumowanie
- Format HTML gotowy do publikacji
- Własne słowa kluczowe do wyszukiwania
- Pomijanie kategorii "Bez kategorii"

## 🔧 Wymagania

### Wymagania systemowe:

- WordPress 5.8 lub nowszy
- PHP 7.4 lub nowszy
- MySQL 5.7 lub nowszy
- Dostęp do WP-Cron lub zewnętrznego crona

### Wymagane konta i klucze API:

1. **Perplexity AI** ✅ **Wymagane**
   - Konto na [perplexity.ai](https://www.perplexity.ai/)
   - Klucz API (płatny plan)
   
2. **OpenAI** ✅ **Wymagane**
   - Konto na [platform.openai.com](https://platform.openai.com/)
   - Klucz API
   - Środki na koncie (API jest płatne)
   
3. **Facebook** 🔵 **Opcjonalne**
   - Strona Facebook (Page)
   - Aplikacja Facebook Developers
   - Long-lived Page Access Token
   - **Możesz pominąć jeśli nie chcesz publikować na Facebooku**

## 📦 Instalacja

### Metoda 1: Przez panel WordPress (zalecana)

1. Spakuj folder `ai-content-publisher` do archiwum ZIP
2. W panelu WordPress przejdź do **Wtyczki → Dodaj nową**
3. Kliknij **Wgraj wtyczkę**
4. Wybierz plik ZIP i kliknij **Zainstaluj**
5. Kliknij **Aktywuj wtyczkę**

### Metoda 2: Ręczna instalacja FTP

1. Skopiuj folder `ai-content-publisher` do `/wp-content/plugins/`
2. W panelu WordPress przejdź do **Wtyczki**
3. Znajdź "AI Content Publisher" i kliknij **Aktywuj**

### Po instalacji:

Wtyczka automatycznie:
- Utworzy tabelę w bazie danych dla historii publikacji
- Ustawi harmonogram cron na codzienne generowanie (8:00)
- Utworzy folder `/wp-content/uploads/ai-content-publisher/` dla obrazów

## ⚙️ Konfiguracja

### 1. Konfiguracja Perplexity API

1. Zarejestruj się na [perplexity.ai](https://www.perplexity.ai/)
2. Wykup plan API (Pro lub wyższy)
3. Przejdź do [Settings → API](https://www.perplexity.ai/settings/api)
4. Wygeneruj klucz API (zaczyna się od `pplx-`)
5. W WordPress: **AI Publisher → Ustawienia → Klucze API**
6. Wklej klucz w pole "Klucz API Perplexity"

### 2. Konfiguracja OpenAI API

1. Załóż konto na [platform.openai.com](https://platform.openai.com/)
2. Dodaj metodę płatności w **Billing**
3. Przejdź do [API Keys](https://platform.openai.com/api-keys)
4. Kliknij **Create new secret key**
5. Skopiuj klucz (zaczyna się od `sk-`)
6. W WordPress: **AI Publisher → Ustawienia → Klucze API**
7. Wklej klucz w pole "Klucz API OpenAI"

**Ważne:** OpenAI API jest płatne według użycia. Sprawdź [cennik](https://openai.com/pricing).

### 3. Konfiguracja Facebook API (Opcjonalna)

**Ważne:** Publikacja na Facebook jest opcjonalna! Jeśli nie chcesz publikować na Facebooku, pomiń ten krok i pozostaw opcję wyłączoną w ustawieniach.

#### Krok A: Włącz publikację na Facebook

1. W WordPress: **AI Publisher → Ustawienia → Konfiguracja Facebook**
2. Zaznacz checkbox **"Włącz automatyczną publikację na Facebook"**
3. Pola konfiguracji Facebook pojawią się automatycznie

#### Krok B: Uzyskaj ID strony

1. Przejdź do swojej strony Facebook
2. Kliknij **Ustawienia**
3. Znajdź ID strony lub użyj [findmyfbid.com](https://findmyfbid.com/)

#### Krok C: Utwórz aplikację Facebook

1. Przejdź do [developers.facebook.com](https://developers.facebook.com/)
2. Kliknij **Moje aplikacje → Utwórz aplikację**
3. Wybierz typ: **Business**
4. Podaj nazwę aplikacji i email kontaktowy
5. Po utworzeniu dodaj produkt: **Facebook Login**

#### Krok D: Wygeneruj token dostępu

1. Przejdź do [Graph API Explorer](https://developers.facebook.com/tools/explorer/)
2. W prawym górnym rogu wybierz swoją aplikację
3. W "User or Page" wybierz swoją stronę
4. Kliknij **Permissions** i dodaj uprawnienia:
   - `pages_manage_posts`
   - `pages_read_engagement`
   - `pages_show_list`
5. Kliknij **Generate Access Token**
6. Skopiuj token

#### Krok E: Zamień na długoterminowy token

1. Przejdź do [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/)
2. Wklej swój token
3. Kliknij **Extend Access Token**
4. Skopiuj nowy, długoterminowy token (ważny 60 dni)

#### Krok F: Wprowadź dane w WordPress

1. W WordPress: **AI Publisher → Ustawienia → Konfiguracja Facebook**
2. Wklej **ID Strony Facebook**
3. Wklej **Token Dostępu Facebook**
4. Kliknij **Zapisz Ustawienia**

### 4. Konfiguracja treści

W WordPress: **AI Publisher → Ustawienia → Ustawienia Treści**

- **Nazwa Województwa**: Wykrywana automatycznie z domeny (możesz zmienić)
- **Długość Artykułu**: Domyślnie 1200 słów (zakres: 500-3000)
- **Słowa Kluczowe**: Lista słów kluczowych oddzielonych przecinkami, np.:
  ```
  samorząd, inwestycje, wydarzenia lokalne, kultura, sport
  ```

### 5. Częstotliwość publikacji dla kategorii

W WordPress: **AI Publisher → Panel Główny** lub **AI Publisher → Ustawienia**

Dla każdej kategorii możesz ustawić indywidualną częstotliwość publikacji:

- **Codziennie** - nowy artykuł każdego dnia
- **Co 2 dni** - artykuł co 2 dni
- **Co 3 dni** - artykuł co 3 dni
- **Raz w tygodniu** - jeden artykuł tygodniowo
- **Raz na 2 tygodnie** - artykuł co 2 tygodnie
- **Raz w miesiącu** - jeden artykuł miesięcznie
- **Wyłączone** - brak automatycznego generowania

**Jak ustawić:**
1. W dashboardzie lub ustawieniach znajdź tabelę kategorii
2. W kolumnie "Częstotliwość" wybierz opcję z listy rozwijanej
3. Ustawienie zapisuje się automatycznie
4. Zobaczysz przewidywaną datę następnej publikacji

### 6. Automatyzacja

W WordPress: **AI Publisher → Ustawienia → Automatyzacja**

- **Automatyczne Generowanie**: Zaznacz checkbox, aby włączyć
- **Godzina**: Ustaw godzinę codziennego sprawdzania (domyślnie 08:00)

**Jak to działa:**
- Cron uruchamia się o wybranej godzinie
- Sprawdza każdą kategorię
- Generuje artykuł tylko jeśli upłynął czas według ustawionej częstotliwości
- Np. kategoria z częstotliwością "Co 3 dni" wygeneruje artykuł co 72 godziny

**Uwaga:** Wymaga działającego WP-Cron!

### 7. Test połączeń

1. Przejdź do **AI Publisher → Panel Główny**
2. Kliknij **Testuj wszystkie połączenia**
3. Sprawdź, czy wszystkie API działają poprawnie (zielone checkmarki ✓)

## 🚀 Użytkowanie

### Generowanie artykułu dla pojedynczej kategorii

1. Przejdź do **AI Publisher → Panel Główny**
2. W tabeli znajdź kategorię
3. Kliknij **Generuj artykuł**
4. Poczekaj 2-3 minuty (proces jest widoczny w logach)
5. Artykuł zostanie opublikowany automatycznie

### Generowanie dla wszystkich kategorii

1. Przejdź do **AI Publisher → Panel Główny**
2. Kliknij **Generuj dla wszystkich kategorii**
3. Potwierdź operację
4. Proces będzie wykonywał się sekwencyjnie dla każdej kategorii

**Czas trwania:** ~2-3 minuty na kategorię

### Automatyczne generowanie

Jeśli włączona jest automatyzacja:
- Wtyczka automatycznie wygeneruje artykuły dla wszystkich kategorii o wybranej godzinie
- Każda kategoria otrzyma nowy artykuł raz dziennie
- Logi są zapisywane w historii

### Przeglądanie historii

1. Przejdź do **AI Publisher → Historia**
2. Zobacz wszystkie wygenerowane artykuły
3. Filtruj po kategorii lub statusie
4. Sprawdź statystyki (łączna liczba, udane, nieudane, skuteczność)

## 💰 API i koszty

### Perplexity API

- **Model używany:** `sonar-pro` (ResearchModels - szczegółowe analizy)
- **Dostępne modele:**
  - `sonar` (SearchModels) - szybkie zapytania faktyczne
  - `sonar-reasoning` (ReasoningModels) - złożone rozumowanie
  - `sonar-pro` (ResearchModels) - szczegółowe analizy i raporty
- **Koszt:** ~$0.001-0.005 per request
- **Użycie:** 1 request na artykuł

### OpenAI API

#### GPT-4o (generowanie artykułu)
- **Koszt:** ~$0.15-0.30 per artykuł
- **Użycie:** 2 requesty na artykuł (artykuł + post FB)

#### DALL-E 3 (generowanie obrazu)
- **Koszt:** $0.040 per obraz (1024x1024, standard quality)
- **Użycie:** 1 obraz na artykuł

### Szacunkowy koszt całkowity

**Jeden artykuł:** ~$0.20-0.35
**10 kategorii dziennie:** ~$2.00-3.50
**Miesięcznie (10 kategorii × 30 dni):** ~$60-105

**Uwaga:** Ceny mogą się zmieniać. Sprawdzaj aktualne cenniki u dostawców API.

### Jak ograniczyć koszty?

1. Ogranicz liczbę kategorii
2. Używaj automatycznego generowania rzadziej
3. Zmniejsz długość artykułów
4. Użyj tańszych modeli (w kodzie można zmienić na GPT-4o-mini)

## 🔍 Rozwiązywanie problemów

### Błąd: "Brak klucza API"

**Rozwiązanie:** Sprawdź, czy wprowadziłeś wszystkie klucze API w ustawieniach.

### Błąd: "Błąd połączenia z Perplexity/OpenAI"

**Rozwiązanie:** 
- Sprawdź poprawność klucza API
- Sprawdź, czy masz środki na koncie (OpenAI)
- Sprawdź, czy serwer ma dostęp do internetu

### Błąd: "Błąd Facebook API: Invalid OAuth access token"

**Rozwiązanie:**
- Token może być wygasły (ważny 60 dni)
- Wygeneruj nowy token i wprowadź w ustawieniach
- Sprawdź uprawnienia tokenu

### Artykuły nie generują się automatycznie

**Rozwiązanie:**
- Sprawdź, czy WP-Cron działa: zainstaluj wtyczkę "WP Crontrol"
- Upewnij się, że automatyzacja jest włączona w ustawieniach
- Sprawdź logi PHP na serwerze

### Przekroczono limit czasu (timeout)

**Rozwiązanie:**
- Zwiększ `max_execution_time` w PHP (zalecane: 300 sekund)
- Zwiększ limity w `.htaccess`:
  ```apache
  php_value max_execution_time 300
  php_value max_input_time 300
  ```

### Obrazy nie są generowane

**Rozwiązanie:**
- Sprawdź uprawnienia do folderu `/wp-content/uploads/`
- Upewnij się, że masz środki na koncie OpenAI
- Sprawdź logi błędów PHP

## 🔒 Bezpieczeństwo

### Najlepsze praktyki:

1. **Klucze API:**
   - Nigdy nie udostępniaj kluczy API publicznie
   - Używaj środowiska z SSL (HTTPS)
   - Regularnie odnawiaj tokeny Facebook

2. **Uprawnienia:**
   - Tylko administratorzy mają dostęp do wtyczki
   - Token Facebook powinien mieć tylko niezbędne uprawnienia

3. **Backupy:**
   - Regularnie twórz kopie zapasowe bazy danych
   - Zachowaj kopię kluczy API w bezpiecznym miejscu

4. **Monitoring:**
   - Regularnie sprawdzaj historię publikacji
   - Monitoruj koszty API
   - Sprawdzaj logi błędów

## ❓ FAQ

### Czy wtyczka działa z każdym motywem WordPress?

Tak, wtyczka jest niezależna od motywu. Publikuje standardowe wpisy WordPress.

### Czy mogę edytować wygenerowane artykuły przed publikacją?

Obecnie artykuły są publikowane automatycznie. Możesz je edytować po publikacji w standardowym edytorze WordPress.

### Czy mogę używać wtyczki na wielu stronach?

Tak, ale każda strona wymaga własnych kluczy API i konfiguracji.

### Jak długo ważny jest token Facebook?

Token Page Access Token jest ważny 60 dni. Po wygaśnięciu musisz wygenerować nowy.

### Czy artykuły są unikalne?

Tak, GPT-4 generuje każdorazowo unikalne treści oparte na najnowszych informacjach z Perplexity.

### Czy mogę zmienić modele AI?

Tak, możesz edytować pliki w `/includes/` i zmienić modele (np. na GPT-4o-mini dla oszczędności).

### Co się stanie, jeśli zabraknie środków na koncie OpenAI?

Generowanie się nie powiedzie i zostanie zapisane jako błąd w historii. Wtyczka nie spowoduje awarii strony.

### Czy wtyczka wspiera języki inne niż polski?

Obecnie wtyczka jest zaprojektowana dla języka polskiego, ale można dostosować prompty w kodzie.

---

## 📞 Wsparcie

W przypadku problemów:
1. Sprawdź logi błędów WordPress (`/wp-content/debug.log`)
2. Sprawdź historię publikacji w panelu wtyczki
3. Przetestuj połączenia API w panelu głównym

## 📄 Licencja

GPL v2 lub nowsza

---

## 🎯 Roadmap (przyszłe funkcje)

- [ ] Podgląd artykułu przed publikacją
- [ ] Wsparcie dla Instagram i Twitter
- [ ] Możliwość edycji promptów przez panel
- [ ] Wsparcie dla wielu języków
- [ ] Integracja z Google Analytics
- [ ] Zaplanowane publikacje
- [ ] A/B testing tytułów

---

**Wersja:** 1.1.0  
**Ostatnia aktualizacja:** Październik 2025  
**Autor:** Twoja Nazwa

🚀 Miłej automatyzacji treści!
