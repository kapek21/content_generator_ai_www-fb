# 📁 Struktura Projektu - AI Content Publisher

## 📂 Organizacja katalogów

```
ai-content-publisher/
│
├── ai-content-publisher.php    # Główny plik wtyczki (Entry point)
│
├── includes/                    # Klasy PHP (logika biznesowa)
│   ├── class-perplexity-api.php       # Komunikacja z Perplexity API
│   ├── class-openai-api.php           # Komunikacja z OpenAI API
│   ├── class-facebook-api.php         # Komunikacja z Facebook Graph API
│   └── class-content-generator.php    # Główna logika generowania treści
│
├── admin/                       # Strony panelu administracyjnego
│   ├── dashboard.php                  # Panel główny
│   ├── settings.php                   # Strona ustawień
│   └── history.php                    # Historia publikacji
│
├── assets/                      # Zasoby frontend
│   ├── css/
│   │   └── admin-style.css            # Style panelu admin
│   └── js/
│       └── admin-script.js            # JavaScript panelu admin
│
├── LICENSE.txt                  # Licencja GPL v2
├── README.md                    # Główna dokumentacja
├── QUICKSTART.md                # Szybki start (15 min)
├── CHANGELOG.md                 # Historia zmian
├── STRUCTURE.md                 # Ten plik (struktura projektu)
└── .gitignore                   # Pliki ignorowane przez Git
```

---

## 🔧 Opis komponentów

### 1. `ai-content-publisher.php` (Główny plik)

**Odpowiedzialność:**
- Inicjalizacja wtyczki
- Rejestracja hooks i actions
- Tworzenie menu administracyjnego
- Zarządzanie cron jobs
- Tworzenie tabeli w bazie danych

**Główne klasy:**
- `AI_Content_Publisher` - singleton class zarządzająca wtyczką

**Hooks:**
- `plugins_loaded` - inicjalizacja
- `admin_menu` - dodawanie menu
- `admin_enqueue_scripts` - ładowanie assets
- `wp_ajax_aicp_generate_content` - AJAX generowanie
- `wp_ajax_aicp_test_connection` - AJAX test API
- `aicp_auto_generate_event` - cron event

---

### 2. `includes/class-perplexity-api.php`

**Odpowiedzialność:**
- Komunikacja z Perplexity API
- Wyszukiwanie aktualnych newsów
- Budowanie zapytań wyszukiwania

**Publiczne metody:**
```php
test_connection()                          // Test połączenia
search_news($category, $province, $keywords) // Wyszukaj newsy
```

**Prywatne metody:**
```php
build_search_query()  // Buduje zapytanie
make_request()        // Wykonuje request HTTP
```

**API Endpoint:**
- `https://api.perplexity.ai/chat/completions`

**Modele używane:**
- Test: `sonar` (SearchModels - szybkie zapytania)
- Wyszukiwanie newsów: `sonar-pro` (ResearchModels - szczegółowe analizy)

**Dostępne modele Perplexity:**
- `sonar` - SearchModels (szybkie zapytania faktyczne i podsumowania)
- `sonar-reasoning` - ReasoningModels (złożone rozumowanie)
- `sonar-pro` - ResearchModels (szczegółowe analizy i raporty)

---

### 3. `includes/class-openai-api.php`

**Odpowiedzialność:**
- Komunikacja z OpenAI API
- Generowanie artykułów (GPT-4o)
- Generowanie obrazów (DALL-E 3)
- Generowanie postów Facebook

**Publiczne metody:**
```php
test_connection()                                      // Test połączenia
generate_article($news, $category, $province, $keywords) // Generuj artykuł
generate_facebook_post($title, $excerpt, $province)    // Generuj post FB
generate_image($title, $category)                      // Generuj obraz
```

**Prywatne metody:**
```php
chat($message, $model, $system, $max_tokens)  // Uniwersalna funkcja chat
build_article_system_prompt()                  // System prompt dla artykułu
build_article_user_prompt()                    // User prompt dla artykułu
build_image_prompt()                           // Prompt dla obrazu
```

**API Endpoints:**
- Chat: `https://api.openai.com/v1/chat/completions`
- Images: `https://api.openai.com/v1/images/generations`

**Modele używane:**
- `gpt-4o` - generowanie artykułów
- `gpt-4o-mini` - generowanie postów FB
- `dall-e-3` - generowanie obrazów

---

### 4. `includes/class-facebook-api.php`

**Odpowiedzialność:**
- Komunikacja z Facebook Graph API
- Publikowanie postów na stronie Facebook
- Publikowanie zdjęć

**Publiczne metody:**
```php
test_connection()                             // Test połączenia
publish_post($message, $link, $image_url)    // Publikuj post
get_page_info()                              // Pobierz info o stronie
```

**Prywatne metody:**
```php
publish_photo_post($message, $link, $image)  // Publikuj ze zdjęciem
```

**API Endpoint:**
- `https://graph.facebook.com/v18.0`

**Endpoints używane:**
- `/{page_id}/feed` - posty tekstowe
- `/{page_id}/photos` - posty ze zdjęciami

---

### 5. `includes/class-content-generator.php`

**Odpowiedzialność:**
- Orkiestracja całego procesu generowania
- Łączenie wszystkich API
- Tworzenie wpisów w WordPress
- Pobieranie i zapisywanie obrazów
- Zapisywanie historii

**Główna metoda:**
```php
generate_and_publish($category_id)  // Cały proces generowania
```

**Pomocnicze metody:**
```php
extract_title($html)                           // Wyodrębnia tytuł z HTML
create_excerpt($html)                          // Tworzy excerpt
download_and_save_image($url, $title)         // Pobiera i zapisuje obraz
create_wordpress_post($title, $content, ...)  // Tworzy wpis WP
save_to_history($category_id, $post_id, ...)  // Zapisuje historię
```

**Proces generowania (krok po kroku):**
1. Walidacja kategorii
2. Wyszukiwanie newsów (Perplexity)
3. Generowanie artykułu (OpenAI GPT-4o)
4. Generowanie obrazu (OpenAI DALL-E 3)
5. Pobieranie i zapisywanie obrazu w WP
6. Tworzenie wpisu WordPress
7. Generowanie posta Facebook (OpenAI GPT-4o-mini)
8. Publikacja na Facebook
9. Zapisanie w historii

---

### 6. `admin/dashboard.php`

**Odpowiedzialność:**
- Wyświetlanie panelu głównego
- Lista kategorii z przyciskami generowania
- Test połączeń API
- Ostatnie generacje

**Sekcje:**
- Informacje o systemie (województwo, liczba kategorii)
- Test połączeń API
- Tabela kategorii z akcjami
- Ostatnie 10 generacji

---

### 7. `admin/settings.php`

**Odpowiedzialność:**
- Formularz konfiguracji
- Zapisywanie ustawień
- Instrukcje konfiguracji API

**Sekcje ustawień:**
- Klucze API (Perplexity, OpenAI)
- Konfiguracja Facebook (Page ID, Token)
- Ustawienia treści (województwo, długość, słowa kluczowe)
- Automatyzacja (włącz/wyłącz, godzina)
- Instrukcje konfiguracji

---

### 8. `admin/history.php`

**Odpowiedzialność:**
- Wyświetlanie historii publikacji
- Filtry (kategoria, status)
- Paginacja
- Statystyki

**Funkcje:**
- Wyświetlanie 50 rekordów na stronę
- Filtry po kategorii i statusie
- Statystyki (łącznie, udane, nieudane, skuteczność)
- Linki do wpisów (WordPress, Facebook)

---

### 9. `assets/css/admin-style.css`

**Odpowiedzialność:**
- Style panelu administracyjnego
- Responsive design
- Animacje i transitions

**Główne klasy:**
- `.aicp-dashboard`, `.aicp-settings`, `.aicp-history` - główne kontenery
- `.aicp-section` - sekcje białe boxy
- `.aicp-info-box` - gradient info box
- `.api-test-result` - wyniki testów API
- `.aicp-status-success/error` - statusy
- `.aicp-progress` - progress bar
- `.aicp-stats` - grid statystyk

---

### 10. `assets/js/admin-script.js`

**Odpowiedzialność:**
- Interakcje użytkownika
- AJAX requests
- Progress tracking
- Real-time logs

**Główne funkcje:**
- Test połączeń API
- Generowanie dla pojedynczej kategorii (z logami)
- Generowanie dla wszystkich kategorii (z progress bar)
- Potwierdzenia przed opuszczeniem strony
- Auto-save warning

---

## 🗄️ Baza danych

### Tabela: `wp_aicp_history`

```sql
CREATE TABLE wp_aicp_history (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  category_id bigint(20) NOT NULL,
  post_id bigint(20) DEFAULT NULL,
  facebook_post_id varchar(255) DEFAULT NULL,
  status varchar(50) NOT NULL,
  message text,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY category_id (category_id),
  KEY post_id (post_id)
);
```

**Kolumny:**
- `id` - unikalny identyfikator
- `category_id` - ID kategorii WordPress
- `post_id` - ID wygenerowanego wpisu
- `facebook_post_id` - ID posta na Facebook
- `status` - 'success' lub 'error'
- `message` - komunikat/błąd
- `created_at` - timestamp generowania

---

## ⚙️ Opcje WordPress

### Zapisane w `wp_options`:

```php
'aicp_perplexity_api_key'       // Klucz Perplexity
'aicp_openai_api_key'           // Klucz OpenAI
'aicp_facebook_page_id'         // ID strony FB
'aicp_facebook_access_token'    // Token FB
'aicp_keywords'                 // Słowa kluczowe (CSV)
'aicp_auto_generate_enabled'    // Automatyzacja (0/1)
'aicp_auto_generate_time'       // Godzina automatyzacji
'aicp_article_length'           // Długość artykułu (słowa)
'aicp_province_name'            // Nazwa województwa
```

---

## 🔄 Cron Jobs

### Event: `aicp_auto_generate_event`

**Częstotliwość:** Codziennie (daily)

**Domyślna godzina:** 08:00

**Akcja:** Generuje artykuły dla wszystkich kategorii (z wyjątkiem "Bez kategorii")

**Hook:** `add_action('aicp_auto_generate_event', [$this, 'auto_generate_content'])`

---

## 📡 AJAX Endpoints

### 1. `wp_ajax_aicp_generate_content`

**Dane wejściowe:**
```javascript
{
  action: 'aicp_generate_content',
  category_id: 123,
  nonce: 'xxx'
}
```

**Odpowiedź (sukces):**
```json
{
  "success": true,
  "data": {
    "category": "Aktualności",
    "province": "mazowieckie",
    "steps": ["Krok 1...", "Krok 2..."],
    "post_id": 456,
    "post_url": "https://...",
    "facebook_post_id": "789"
  }
}
```

---

### 2. `wp_ajax_aicp_test_connection`

**Dane wejściowe:**
```javascript
{
  action: 'aicp_test_connection',
  service: 'all', // lub 'perplexity', 'openai', 'facebook'
  nonce: 'xxx'
}
```

**Odpowiedź (sukces):**
```json
{
  "success": true,
  "data": {
    "perplexity": true,
    "openai": true,
    "facebook": true
  }
}
```

---

## 🔐 Bezpieczeństwo

### Mechanizmy zabezpieczeń:

1. **Nonce verification** - wszystkie AJAX requests
2. **Capability checks** - tylko `manage_options`
3. **Input sanitization** - `sanitize_text_field()`, `intval()`, etc.
4. **Output escaping** - `esc_html()`, `esc_attr()`, `esc_url()`
5. **Direct access prevention** - `if (!defined('ABSPATH')) exit;`
6. **SQL prepared statements** - `$wpdb->prepare()`

---

## 📊 Przepływ danych

```
┌─────────────────┐
│   WordPress     │
│   Dashboard     │
└────────┬────────┘
         │
         │ Klik "Generuj artykuł"
         ▼
┌─────────────────┐
│   AJAX Request  │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────────┐
│   AICP_Content_Generator::generate_and_publish  │
└────────┬────────────────────────────────────────┘
         │
         ├──► 1. Perplexity API (wyszukaj newsy)
         │         │
         │         └──► Zwraca: lista newsów
         │
         ├──► 2. OpenAI GPT-4o (wygeneruj artykuł)
         │         │
         │         └──► Zwraca: HTML artykułu
         │
         ├──► 3. OpenAI DALL-E 3 (wygeneruj obraz)
         │         │
         │         └──► Zwraca: URL obrazu
         │
         ├──► 4. Download image → WP Media Library
         │         │
         │         └──► Zwraca: attachment_id
         │
         ├──► 5. wp_insert_post() (utwórz wpis)
         │         │
         │         └──► Zwraca: post_id
         │
         ├──► 6. OpenAI GPT-4o-mini (post na FB)
         │         │
         │         └──► Zwraca: tekst posta
         │
         ├──► 7. Facebook Graph API (publikuj)
         │         │
         │         └──► Zwraca: facebook_post_id
         │
         └──► 8. Zapisz w historii (wp_aicp_history)
                  │
                  └──► Koniec procesu
```

---

## 🧪 Testowanie

### Test ręczny:

1. **Test jednostkowy API:**
   ```php
   $api = new AICP_Perplexity_API();
   $result = $api->test_connection();
   ```

2. **Test pełnego procesu:**
   - Użyj przycisku "Generuj artykuł" w dashboard

3. **Test crona:**
   ```bash
   wp cron event run aicp_auto_generate_event
   ```
   (wymaga WP-CLI)

---

## 🔧 Rozszerzanie wtyczki

### Dodanie nowego API:

1. Utwórz klasę w `/includes/`:
   ```php
   class AICP_NewService_API {
       public function test_connection() { }
       // ...
   }
   ```

2. Załaduj w głównym pliku:
   ```php
   require_once AICP_PLUGIN_DIR . 'includes/class-newservice-api.php';
   ```

3. Użyj w `class-content-generator.php`

---

### Dodanie nowej strony admin:

1. Utwórz plik w `/admin/new-page.php`

2. Zarejestruj submenu w głównym pliku:
   ```php
   add_submenu_page(
       'ai-content-publisher',
       'New Page',
       'New Page',
       'manage_options',
       'aicp-new-page',
       [$this, 'render_new_page']
   );
   ```

---

## 📈 Performance

### Optymalizacje:

1. **Lazy loading** - klasy API ładowane tylko gdy potrzebne
2. **Caching** - brak, bo treść zawsze fresh
3. **Database indexes** - na `category_id` i `post_id`
4. **AJAX** - asynchroniczne requests
5. **Timeouts** - 60s dla API, 120s dla DALL-E

### Bottlenecks:

- Generowanie obrazu (DALL-E): ~20-30s
- Generowanie artykułu (GPT-4o): ~15-30s
- Wyszukiwanie (Perplexity): ~5-10s

**Łączny czas:** ~2-3 minuty na artykuł

---

## 📝 Konwencje kodowania

### Nazewnictwo:

- **Klasy:** `AICP_Class_Name` (PascalCase z prefiksem)
- **Funkcje:** `function_name()` (snake_case)
- **Zmienne:** `$variable_name` (snake_case)
- **Stałe:** `AICP_CONSTANT_NAME` (UPPER_CASE)
- **Hooks:** `aicp_hook_name` (snake_case z prefiksem)

### Prefixes:

- `AICP_` - klasy, stałe
- `aicp_` - funkcje, hooks, opcje, tabele
- `.aicp-` - klasy CSS
- `aicpAjax` - JS variables

---

## 🎯 Design Patterns

1. **Singleton** - `AI_Content_Publisher` (tylko jedna instancja)
2. **Factory** - Tworzenie API clients
3. **Strategy** - Różne API dla różnych zadań
4. **Observer** - WordPress hooks system

---

**Wersja:** 1.0.0  
**Ostatnia aktualizacja:** Październik 2025
