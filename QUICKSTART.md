# 🚀 Szybki Start - AI Content Publisher

Ten przewodnik pomoże Ci uruchomić wtyczkę w **15 minut**.

## ✅ Lista kontrolna

- [ ] WordPress 5.8+ zainstalowany
- [ ] Utworzone kategorie w WordPress
- [ ] Konto Perplexity AI (z kluczem API)
- [ ] Konto OpenAI (z kluczem API i środkami)
- [ ] Strona Facebook (z tokenem dostępu)

## 📝 Krok po kroku (15 minut)

### 1️⃣ Instalacja wtyczki (2 minuty)

```bash
# Skopiuj wtyczkę do WordPress
cp -r ai-content-publisher /path/to/wordpress/wp-content/plugins/

# LUB spakuj i wgraj przez panel WordPress
zip -r ai-content-publisher.zip ai-content-publisher/
```

Następnie w WordPress:
- **Wtyczki → Dodaj nową → Wgraj wtyczkę**
- Wybierz plik ZIP
- Kliknij **Zainstaluj → Aktywuj**

✅ Gotowe! Wtyczka jest zainstalowana.

---

### 2️⃣ Uzyskaj klucze API (6-10 minut)

**Uwaga:** Facebook API jest **opcjonalne**! Możesz pominąć punkt C jeśli nie chcesz publikować na Facebooku.

#### A. Perplexity API (~3 min) ✅ WYMAGANE

1. Idź na: https://www.perplexity.ai/settings/api
2. Zaloguj się / Zarejestruj
3. Kliknij **Generate API Key**
4. Skopiuj klucz (zaczyna się od `pplx-`)

**Koszt:** ~$20/miesiąc za plan API

---

#### B. OpenAI API (~3 min) ✅ WYMAGANE

1. Idź na: https://platform.openai.com/api-keys
2. Zaloguj się / Zarejestruj
3. Dodaj płatność: **Billing → Add payment method**
4. Kliknij **Create new secret key**
5. Skopiuj klucz (zaczyna się od `sk-`)

**Koszt:** Pay-as-you-go, ~$0.20-0.35 per artykuł

---

#### C. Facebook Token (~4 min) 🔵 OPCJONALNE

**Możesz pominąć ten krok!** Jeśli nie chcesz publikować na Facebooku, po prostu zostaw opcję wyłączoną w ustawieniach wtyczki.

**Opcja łatwa (jeśli chcesz Facebook):**

1. Idź na: https://developers.facebook.com/tools/explorer/
2. Zaloguj się
3. W prawym górnym rogu:
   - Wybierz aplikację (lub utwórz nową)
   - Wybierz swoją stronę (Page)
4. Kliknij **Permissions**:
   - Dodaj: `pages_manage_posts`
   - Dodaj: `pages_read_engagement`  
   - Dodaj: `pages_show_list`
5. Kliknij **Generate Access Token**
6. Skopiuj token

**ID Strony znajdziesz:**
- W ustawieniach strony Facebook
- Lub na: https://findmyfbid.com/

---

### 3️⃣ Konfiguracja wtyczki (3 minuty)

1. W WordPress idź do: **AI Publisher → Ustawienia**

2. Wklej klucze API:
   ```
   Klucz API Perplexity: pplx-xxxxxxxxxxxxx
   Klucz API OpenAI: sk-xxxxxxxxxxxxxxxx
   ID Strony Facebook: 1234567890
   Token Facebook: EAAxxxxxxxxxxxxxxxxx
   ```

3. **🌍 NOWOŚĆ v1.2.0:** Wybierz język treści:
   ```
   Język Treści: Polski 🇵🇱 / Deutsch 🇩🇪 / English 🇬🇧 / Українська 🇺🇦
   ```

4. Ustaw województwo/region (automatycznie wykryte):
   ```
   Polski: mazowieckie, śląskie, etc.
   Niemiecki: Bayern, Nordrhein-Westfalen, etc.
   Angielski: California, Texas, etc.
   Ukraiński: Київська область, etc.
   ```

5. Dodaj słowa kluczowe (w wybranym języku):
   ```
   Polski: samorząd, inwestycje, wydarzenia lokalne, kultura, sport, biznes
   Niemiecki: Gemeinde, Investitionen, lokale Veranstaltungen, Kultur, Sport
   Angielski: local government, investments, local events, culture, sports
   Ukraiński: самоврядування, інвестиції, місцеві події, культура, спорт
   ```

6. Włącz automatyzację (opcjonalne):
   ```
   ☑ Automatyczne Generowanie
   Godzina: 08:00
   ```

7. Kliknij **Zapisz Ustawienia**

---

### 4️⃣ Test połączeń (1 minuta)

1. Idź do: **AI Publisher → Panel Główny**
2. Kliknij **Testuj wszystkie połączenia**
3. Sprawdź zielone checkmarki ✓:
   - ✓ Perplexity API: Połączenie udane
   - ✓ OpenAI API: Połączenie udane
   - ✓ Facebook API: Połączenie udane

⚠️ **Jeśli coś nie działa:**
- Sprawdź klucze API (czy są poprawne?)
- Sprawdź środki na koncie OpenAI
- Sprawdź uprawnienia tokenu Facebook

---

### 5️⃣ Pierwszy artykuł! (2-3 minuty)

1. W **AI Publisher → Panel Główny** znajdź tabelę kategorii
2. Wybierz kategorię (np. "Aktualności")
3. Kliknij **Generuj artykuł**
4. Obserwuj progress w logach:
   ```
   [10:30:15] Rozpoczęto generowanie artykułu...
   [10:30:18] Wyszukiwanie aktualności przez Perplexity...
   [10:30:45] Generowanie artykułu przez OpenAI...
   [10:31:30] Generowanie obrazu przez DALL-E...
   [10:31:50] Tworzenie wpisu w WordPress...
   [10:32:10] Generowanie wpisu na Facebook...
   [10:32:25] Publikacja na Facebook...
   [10:32:30] ✓ Ukończono!
   ```

5. Kliknij **Zobacz wpis** aby zobaczyć rezultat!

🎉 **Gratulacje!** Właśnie wygenerowałeś pierwszy artykuł AI!

**🤖 Nowe w v1.6.0 - AI Search Optimization:**
Każdy wygenerowany artykuł zawiera teraz:
- ✅ Schema.org JSON-LD (dla ChatGPT, Gemini, Perplexity)
- ✅ Zoptymalizowane opisy ALT dla obrazków (SEO + AI Search)
- ✅ Meta descriptions (150-160 znaków, gotowe pod Google i AI)
- ✅ Lokalne słowa kluczowe (politycy, celebryci, miasta, wydarzenia)
- ✅ Strukturyzowane dane dla AI fact-checking
- ✅ Wsparcie dla Yoast SEO i Rank Math

---

## 🔄 Częstotliwość publikacji (ważne!)

**Nowa funkcja v1.1.0:** Możesz ustawić częstotliwość dla każdej kategorii osobno!

1. Idź do: **AI Publisher → Panel Główny**
2. W tabeli kategorii znajdź kolumnę **"Częstotliwość"**
3. Dla każdej kategorii wybierz z listy:
   - Codziennie
   - Co 2 dni
   - Co 3 dni
   - Raz w tygodniu
   - Raz na 2 tygodnie
   - Raz w miesiącu
   - Wyłączone
4. Ustawienie zapisuje się automatycznie

**Przykład:**
- "Aktualności" → Codziennie
- "Sport" → Co 2 dni
- "Kultura" → Raz w tygodniu

## 🤖 Automatyzacja

Chcesz, aby artykuły generowały się automatycznie?

1. Idź do: **AI Publisher → Ustawienia → Automatyzacja**
2. Zaznacz: **☑ Automatyczne Generowanie**
3. Ustaw godzinę sprawdzania: `08:00`
4. Kliknij **Zapisz Ustawienia**

Od teraz wtyczka będzie **codziennie o 8:00** sprawdzać wszystkie kategorie i generować artykuły według ustawionej częstotliwości!

---

## 📊 Monitorowanie

### Historia publikacji

**AI Publisher → Historia**

Zobacz:
- Wszystkie wygenerowane artykuły
- Statystyki (łącznie, udane, nieudane, skuteczność)
- Filtry (po kategorii, statusie)
- Linki do postów (WordPress + Facebook)

### Sprawdź koszty API

**OpenAI:**
- https://platform.openai.com/usage

**Perplexity:**
- https://www.perplexity.ai/settings/api

---

## ⚠️ Najczęstsze problemy

### Problem: "Timeout" podczas generowania

**Rozwiązanie:** Zwiększ limity PHP:

```php
// W wp-config.php dodaj:
set_time_limit(300);
ini_set('max_execution_time', 300);
```

Lub w `.htaccess`:
```apache
php_value max_execution_time 300
```

---

### Problem: Token Facebook wygasa po 60 dniach

**Rozwiązanie:** Co 2 miesiące musisz odnowić token:

1. Idź na: https://developers.facebook.com/tools/explorer/
2. Wygeneruj nowy token (te same kroki co wcześniej)
3. Wklej w: **AI Publisher → Ustawienia**

**Tip:** Ustaw sobie przypomnienie w kalendarzu! 📅

---

### Problem: Brak środków na OpenAI

**Rozwiązanie:** Doładuj konto:

1. https://platform.openai.com/settings/organization/billing
2. **Add credit** → Dodaj $20-50

**Ostrzeżenie:** Ustaw limity wydatków, aby uniknąć niespodzianek!

---

## 💡 Wskazówki pro

### 1. Optymalizuj koszty

W pliku `/includes/class-openai-api.php` zmień model:

```php
// Zamień GPT-4o na tańszy GPT-4o-mini
'model' => 'gpt-4o-mini'  // Zamiast 'gpt-4o'
```

**Oszczędność:** ~70% kosztów! (ale niższa jakość)

---

### 2. Dostosuj długość artykułów

**AI Publisher → Ustawienia → Długość Artykułu**

- Krótsze (800 słów) = tańsze
- Dłuższe (2000 słów) = lepsze SEO, ale droższe

---

### 3. Lepsze słowa kluczowe

Dodaj specyficzne słowa dla swojego regionu:

```
nazwa-miasta, lokalne firmy, rada miasta, 
wydarzenia w województwie, inwestycje regionalne
```

---

### 4. Monitoruj Facebook

Sprawdzaj regularnie:
- Czy posty są publikowane poprawnie?
- Czy linki działają?
- Jak ludzie reagują?

**Facebook Insights:** https://www.facebook.com/your-page/insights

---

## 📚 Następne kroki

✅ Wtyczka działa? Świetnie! Teraz:

1. **Przeczytaj pełną dokumentację:** `README.md`
2. **Dostosuj prompty:** Edytuj pliki w `/includes/` dla lepszych wyników
3. **Eksperymentuj:** Testuj różne ustawienia i słowa kluczowe
4. **Monitoruj koszty:** Sprawdzaj zużycie API co tydzień
5. **Backup:** Rób kopie zapasowe bazy danych

---

## 🆘 Pomoc

**Potrzebujesz pomocy?**

1. Sprawdź: `README.md` (pełna dokumentacja)
2. Sprawdź: Sekcja "Rozwiązywanie problemów" w README
3. Sprawdź logi: `/wp-content/debug.log`

---

## ✨ To wszystko!

Miłego korzystania z AI Content Publisher! 🚀

**Tip:** Udostępnij wtyczkę znajomym, którzy mają portale informacyjne! 😊

---

**Wersja:** 1.2.0  
**Data:** Październik 2025  
**Nowości:**
- 🌍 Wsparcie dla 4 języków: Polski, Niemiecki, Angielski, Ukraiński
- Konfigurowalna częstotliwość dla każdej kategorii
- Wszystkie prompty AI dostosowane do wybranego języka
