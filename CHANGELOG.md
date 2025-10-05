# Changelog

Wszystkie istotne zmiany w projekcie AI Content Publisher będą dokumentowane w tym pliku.

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/),
projekt stosuje [Semantic Versioning](https://semver.org/lang/pl/).

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
- [ ] Multi-language support
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
