<?php
/**
 * Klasa do komunikacji z OpenAI API (ChatGPT i DALL-E)
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICP_OpenAI_API {
    
    private $api_key;
    private $chat_api_url = 'https://api.openai.com/v1/chat/completions';
    private $image_api_url = 'https://api.openai.com/v1/images/generations';
    
    public function __construct() {
        $this->api_key = get_option('aicp_openai_api_key');
    }
    
    /**
     * Testuje połączenie z API
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $response = $this->chat('Odpowiedz jednym słowem: OK', 'gpt-4o-mini');
        return !empty($response);
    }
    
    /**
     * Generuje artykuł na podstawie zebranych informacji
     */
    public function generate_article($news_data, $category_name, $province, $keywords, $target_length = 1200, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $system_prompt = $this->build_article_system_prompt($target_length, $language);
        $user_prompt = $this->build_article_user_prompt($news_data, $category_name, $province, $keywords, $language);
        
        $response = $this->chat($user_prompt, 'gpt-4o', $system_prompt, 4000);
        
        return $response;
    }
    
    /**
     * Generuje krótki wpis na Facebooka
     */
    public function generate_facebook_post($article_title, $article_excerpt, $province, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $prompt = $this->build_facebook_prompt($article_title, $article_excerpt, $province, $language);
        $system_prompt = $this->build_facebook_system_prompt($language);
        
        $response = $this->chat($prompt, 'gpt-4o-mini', $system_prompt, 500);
        
        return $response;
    }
    
    /**
     * Generuje obraz dla artykułu
     */
    public function generate_image($article_title, $category_name) {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        // Przygotuj prompt dla DALL-E
        $image_prompt = $this->build_image_prompt($article_title, $category_name);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'dall-e-3',
                'prompt' => $image_prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard'
            )),
            'timeout' => 120
        );
        
        $response = wp_remote_request($this->image_api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z OpenAI (obrazy): ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['data'][0]['url'])) {
            return $decoded['data'][0]['url'];
        }
        
        throw new Exception('Błąd generowania obrazu: ' . $body);
    }
    
    /**
     * Generuje opis ALT dla obrazka (SEO + AI Search optimized)
     */
    public function generate_image_alt_text($article_title, $category_name, $province, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $templates = array(
            'pl' => "Napisz SZCZEGÓŁOWY opis ALT dla obrazka artykułu o tytule: '{$article_title}' (kategoria: {$category_name}, województwo: {$province}).\n\n" .
                   "WYMAGANIA dla ALT:\n" .
                   "- Długość: 100-150 znaków\n" .
                   "- Zawrzyj: województwo '{$province}', kategorię '{$category_name}'\n" .
                   "- Użyj lokalnych słów kluczowych (miasto, gmina, region)\n" .
                   "- Opisz CO jest na obrazku w kontekście lokalnym\n" .
                   "- Optymalizuj pod Google Images i AI Search\n" .
                   "- Bez słowa 'obrazek', 'zdjęcie', 'ilustracja'\n\n" .
                   "Zwróć TYLKO tekst ALT, bez cudzysłowów.",
            'de' => "Schreibe eine DETAILLIERTE ALT-Beschreibung für ein Bild zum Artikel: '{$article_title}' (Kategorie: {$category_name}, Bundesland: {$province}).\n\n" .
                   "ANFORDERUNGEN für ALT:\n" .
                   "- Länge: 100-150 Zeichen\n" .
                   "- Enthalte: Bundesland '{$province}', Kategorie '{$category_name}'\n" .
                   "- Verwende lokale Schlüsselwörter (Stadt, Gemeinde, Region)\n" .
                   "- Beschreibe WAS auf dem Bild im lokalen Kontext ist\n" .
                   "- Optimiere für Google Images und AI Search\n" .
                   "- Ohne Wort 'Bild', 'Foto', 'Illustration'\n\n" .
                   "Gib NUR den ALT-Text zurück, ohne Anführungszeichen.",
            'en' => "Write a DETAILED ALT description for an article image titled: '{$article_title}' (category: {$category_name}, region: {$province}).\n\n" .
                   "ALT REQUIREMENTS:\n" .
                   "- Length: 100-150 characters\n" .
                   "- Include: region '{$province}', category '{$category_name}'\n" .
                   "- Use local keywords (city, municipality, region)\n" .
                   "- Describe WHAT is in the image in local context\n" .
                   "- Optimize for Google Images and AI Search\n" .
                   "- Without words 'image', 'photo', 'illustration'\n\n" .
                   "Return ONLY the ALT text, no quotes.",
            'uk' => "Напиши ДЕТАЛЬНИЙ опис ALT для зображення статті: '{$article_title}' (категорія: {$category_name}, регіон: {$province}).\n\n" .
                   "ВИМОГИ до ALT:\n" .
                   "- Довжина: 100-150 символів\n" .
                   "- Включи: регіон '{$province}', категорію '{$category_name}'\n" .
                   "- Використай локальні ключові слова (місто, громада, регіон)\n" .
                   "- Опиши ЩО на зображенні в локальному контексті\n" .
                   "- Оптимізуй під Google Images та AI Search\n" .
                   "- Без слів 'зображення', 'фото', 'ілюстрація'\n\n" .
                   "Поверни ТІЛЬКИ текст ALT, без лапок."
        );
        
        $prompt = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        $alt_text = $this->chat($prompt, 'gpt-4o-mini', null, 200);
        
        // Oczyść z ewentualnych cudzysłowów
        $alt_text = trim($alt_text, '"\'');
        
        return $alt_text;
    }
    
    /**
     * Generuje meta description (SEO + AI Search optimized)
     */
    public function generate_meta_description($article_excerpt, $province, $category_name, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $templates = array(
            'pl' => "Na podstawie fragmentu artykułu napisz IDEALNĄ meta description dla SEO i AI Search:\n\n" .
                   "Fragment: {$article_excerpt}\n\n" .
                   "WYMAGANIA:\n" .
                   "- Długość: 150-160 znaków (ściśle!)\n" .
                   "- MUSISZ zawrzeć: województwo '{$province}'\n" .
                   "- Zawrzyj: kategorię '{$category_name}' lub synonimy\n" .
                   "- Użyj call-to-action (Dowiedz się, Zobacz, Sprawdź)\n" .
                   "- Optymalizuj pod Google i ChatGPT/Gemini\n" .
                   "- Angażująca, zachęcająca do kliknięcia\n\n" .
                   "Zwróć TYLKO meta description, bez cudzysłowów.",
            'de' => "Schreibe basierend auf dem Artikelauszug die PERFEKTE Meta-Description für SEO und AI Search:\n\n" .
                   "Auszug: {$article_excerpt}\n\n" .
                   "ANFORDERUNGEN:\n" .
                   "- Länge: 150-160 Zeichen (streng!)\n" .
                   "- MUSS enthalten: Bundesland '{$province}'\n" .
                   "- Enthalte: Kategorie '{$category_name}' oder Synonyme\n" .
                   "- Verwende Call-to-Action (Erfahren Sie, Sehen Sie, Prüfen Sie)\n" .
                   "- Optimiere für Google und ChatGPT/Gemini\n" .
                   "- Ansprechend, zum Klicken anregend\n\n" .
                   "Gib NUR die Meta-Description zurück, ohne Anführungszeichen.",
            'en' => "Based on the article excerpt, write the PERFECT meta description for SEO and AI Search:\n\n" .
                   "Excerpt: {$article_excerpt}\n\n" .
                   "REQUIREMENTS:\n" .
                   "- Length: 150-160 characters (strictly!)\n" .
                   "- MUST include: region '{$province}'\n" .
                   "- Include: category '{$category_name}' or synonyms\n" .
                   "- Use call-to-action (Learn, See, Check, Discover)\n" .
                   "- Optimize for Google and ChatGPT/Gemini\n" .
                   "- Engaging, encouraging clicks\n\n" .
                   "Return ONLY the meta description, no quotes.",
            'uk' => "На основі фрагмента статті напиши ІДЕАЛЬНИЙ мета-опис для SEO та AI Search:\n\n" .
                   "Фрагмент: {$article_excerpt}\n\n" .
                   "ВИМОГИ:\n" .
                   "- Довжина: 150-160 символів (строго!)\n" .
                   "- ПОВИНЕН містити: регіон '{$province}'\n" .
                   "- Включи: категорію '{$category_name}' або синоніми\n" .
                   "- Використай заклик до дії (Дізнайся, Подивись, Перевір)\n" .
                   "- Оптимізуй під Google та ChatGPT/Gemini\n" .
                   "- Захоплююча, що заохочує клікнути\n\n" .
                   "Поверни ТІЛЬКИ мета-опис, без лапок."
        );
        
        $prompt = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        $meta_desc = $this->chat($prompt, 'gpt-4o-mini', null, 200);
        
        // Oczyść z ewentualnych cudzysłowów
        $meta_desc = trim($meta_desc, '"\'');
        
        // Ogranicz do 160 znaków jeśli za długie
        if (mb_strlen($meta_desc) > 160) {
            $meta_desc = mb_substr($meta_desc, 0, 157) . '...';
        }
        
        return $meta_desc;
    }
    
    /**
     * Generuje FAQ dla artykułu (SEO + AI Search + Featured Snippets)
     */
    public function generate_faq($article_title, $article_excerpt, $province, $category_name, $language = 'pl') {
        if (empty($this->api_key)) {
            throw new Exception('Brak klucza API OpenAI');
        }
        
        $templates = array(
            'pl' => "Na podstawie artykułu o tytule '{$article_title}' (kategoria: {$category_name}, województwo: {$province}) napisz FAQ (często zadawane pytania).\n\n" .
                   "Fragment artykułu:\n{$article_excerpt}\n\n" .
                   "WYMAGANIA dla FAQ:\n" .
                   "- 5-7 pytań z odpowiedziami\n" .
                   "- Pytania MUSZĄ dotyczyć województwa '{$province}'\n" .
                   "- Pytania w stylu: 'Co...?', 'Jak...?', 'Kiedy...?', 'Dlaczego...?', 'Gdzie w {$province}...?'\n" .
                   "- Pytania lokalne: wspominaj miasta, gminy z '{$province}'\n" .
                   "- Odpowiedzi: 50-100 słów, konkretne, z danymi\n" .
                   "- Optymalizuj pod Google Featured Snippets\n" .
                   "- Używaj pełnych nazw (nie skrótów)\n\n" .
                   "Zwróć w formacie JSON:\n" .
                   '{"faq": [{"question": "Pytanie 1?", "answer": "Odpowiedź 1..."}, {"question": "Pytanie 2?", "answer": "Odpowiedź 2..."}]}',
            'de' => "Basierend auf dem Artikel mit dem Titel '{$article_title}' (Kategorie: {$category_name}, Bundesland: {$province}) schreibe FAQ (häufig gestellte Fragen).\n\n" .
                   "Artikelauszug:\n{$article_excerpt}\n\n" .
                   "FAQ-ANFORDERUNGEN:\n" .
                   "- 5-7 Fragen mit Antworten\n" .
                   "- Fragen MÜSSEN über '{$province}' sein\n" .
                   "- Fragen im Stil: 'Was...?', 'Wie...?', 'Wann...?', 'Warum...?', 'Wo in {$province}...?'\n" .
                   "- Lokale Fragen: erwähne Städte, Gemeinden aus '{$province}'\n" .
                   "- Antworten: 50-100 Wörter, konkret, mit Daten\n" .
                   "- Optimiere für Google Featured Snippets\n" .
                   "- Verwende vollständige Namen (keine Abkürzungen)\n\n" .
                   "Gib im JSON-Format zurück:\n" .
                   '{"faq": [{"question": "Frage 1?", "answer": "Antwort 1..."}, {"question": "Frage 2?", "answer": "Antwort 2..."}]}',
            'en' => "Based on the article titled '{$article_title}' (category: {$category_name}, region: {$province}) write FAQ (frequently asked questions).\n\n" .
                   "Article excerpt:\n{$article_excerpt}\n\n" .
                   "FAQ REQUIREMENTS:\n" .
                   "- 5-7 questions with answers\n" .
                   "- Questions MUST be about '{$province}'\n" .
                   "- Questions in style: 'What...?', 'How...?', 'When...?', 'Why...?', 'Where in {$province}...?'\n" .
                   "- Local questions: mention cities, municipalities from '{$province}'\n" .
                   "- Answers: 50-100 words, specific, with data\n" .
                   "- Optimize for Google Featured Snippets\n" .
                   "- Use full names (no abbreviations)\n\n" .
                   "Return in JSON format:\n" .
                   '{"faq": [{"question": "Question 1?", "answer": "Answer 1..."}, {"question": "Question 2?", "answer": "Answer 2..."}]}',
            'uk' => "На основі статті з назвою '{$article_title}' (категорія: {$category_name}, регіон: {$province}) напиши FAQ (часті питання).\n\n" .
                   "Фрагмент статті:\n{$article_excerpt}\n\n" .
                   "ВИМОГИ до FAQ:\n" .
                   "- 5-7 питань з відповідями\n" .
                   "- Питання ПОВИННІ стосуватися '{$province}'\n" .
                   "- Питання в стилі: 'Що...?', 'Як...?', 'Коли...?', 'Чому...?', 'Де в {$province}...?'\n" .
                   "- Локальні питання: згадуй міста, громади з '{$province}'\n" .
                   "- Відповіді: 50-100 слів, конкретні, з даними\n" .
                   "- Оптимізуй під Google Featured Snippets\n" .
                   "- Використовуй повні назви (не скорочення)\n\n" .
                   "Поверни у форматі JSON:\n" .
                   '{"faq": [{"question": "Питання 1?", "answer": "Відповідь 1..."}, {"question": "Питання 2?", "answer": "Відповідь 2..."}]}'
        );
        
        $prompt = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        $faq_json = $this->chat($prompt, 'gpt-4o-mini', null, 800);
        
        // Wyczyść markdown code blocks jeśli są
        $faq_json = preg_replace('/```json\s*|\s*```/', '', $faq_json);
        $faq_json = trim($faq_json);
        
        // Parsuj JSON
        $faq_data = json_decode($faq_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($faq_data['faq'])) {
            // Jeśli parsing się nie powiódł, zwróć pustą tablicę
            error_log('AICP: Błąd parsowania FAQ JSON: ' . json_last_error_msg());
            return array();
        }
        
        return $faq_data['faq'];
    }
    
    /**
     * Uniwersalna funkcja chat
     */
    private function chat($message, $model = 'gpt-4o-mini', $system_prompt = null, $max_tokens = 2000) {
        $messages = array();
        
        if ($system_prompt) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }
        
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => 0.7
            )),
            'timeout' => 60
        );
        
        $response = wp_remote_request($this->chat_api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z OpenAI: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            throw new Exception('Błąd API OpenAI (kod: ' . $status_code . '): ' . $body);
        }
        
        $decoded = json_decode($body, true);
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }
        
        throw new Exception('Brak odpowiedzi z OpenAI');
    }
    
    /**
     * Buduje system prompt dla generowania artykułu
     */
    private function build_article_system_prompt($target_length, $language = 'pl') {
        $language_name = AI_Content_Publisher::get_language_full_name($language);
        
        $prompts = array(
            'pl' => "Jesteś nagradzanym dziennikarzem analitycznym i ekspertem od premium content, specjalizującym się w tworzeniu WARTOŚCIOWYCH treści wysokiej jakości dla Google AdSense. " .
                   "Twoje artykuły to dogłębne analizy, które wyróżniają się profesjonalizmem, unikalnymi perspektywami i eksperckim podejściem. " .
                   "Piszesz w języku polskim, używając bogatego słownictwa, precyzyjnego języka i wciągającego storytellingu. " .
                   "Twoje teksty są długie, szczegółowe i zawierają około {$target_length} słów, pełne konkretnych danych, statystyk i analizy przyczyn-skutków. " .
                   "Unikasz clickbaitu i powierzchowności - każde zdanie wnosi wartość merytoryczną.",
            'de' => "Du bist ein preisgekrönter analytischer Journalist und Premium-Content-Experte, spezialisiert auf die Erstellung WERTVOLLER hochwertiger Inhalte für Google AdSense. " .
                   "Deine Artikel sind tiefgehende Analysen, die sich durch Professionalität, einzigartige Perspektiven und Expertenwissen auszeichnen. " .
                   "Du schreibst auf Deutsch mit reichhaltigem Vokabular, präziser Sprache und fesselndem Storytelling. " .
                   "Deine Texte sind lang, detailliert und enthalten etwa {$target_length} Wörter, voller konkreter Daten, Statistiken und Ursache-Wirkungs-Analysen. " .
                   "Du vermeidest Clickbait und Oberflächlichkeit - jeder Satz bietet inhaltlichen Mehrwert.",
            'en' => "You are an award-winning analytical journalist and premium content expert, specializing in creating VALUABLE high-quality content for Google AdSense. " .
                   "Your articles are in-depth analyses that stand out through professionalism, unique perspectives, and expert approach. " .
                   "You write in English using rich vocabulary, precise language, and engaging storytelling. " .
                   "Your texts are long, detailed and contain approximately {$target_length} words, full of concrete data, statistics, and cause-effect analysis. " .
                   "You avoid clickbait and superficiality - every sentence adds substantial value.",
            'uk' => "Ти нагороджений аналітичний журналіст та експерт з преміум-контенту, який спеціалізується на створенні ЦІННОГО високоякісного контенту для Google AdSense. " .
                   "Твої статті - це глибокі аналізи, які виділяються професіоналізмом, унікальними перспективами та експертним підходом. " .
                   "Ти пишеш українською мовою, використовуючи багатий словниковий запас, точну мову та захоплюючий сторітелінг. " .
                   "Твої тексти довгі, детальні та містять приблизно {$target_length} слів, повні конкретних даних, статистики та аналізу причин-наслідків. " .
                   "Ти уникаєш клікбейту та поверхневості - кожне речення додає змістовну цінність."
        );
        
        return isset($prompts[$language]) ? $prompts[$language] : $prompts['pl'];
    }
    
    /**
     * Buduje user prompt dla generowania artykułu
     */
    private function build_article_user_prompt($news_data, $category_name, $province, $keywords, $language = 'pl') {
        $templates = array(
            'pl' => array(
                'intro' => "Napisz WARTOŚCIOWY, dogłębny artykuł analityczny WYŁĄCZNIE o województwie {$province} na temat: {$category_name}.\n\n",
                'base' => "Bazuj na poniższych aktualnych informacjach:\n{$news_data}\n\n",
                'requirements' => "WYMAGANIA PREMIUM CONTENT (dla maksymalnej wartości w Google AdSense):\n\n",
                'req1' => "1. DŁUGOŚĆ I GŁĘBIA: Artykuł musi mieć MINIMUM 1500-1800 słów (nie oszczędzaj na szczegółach)\n",
                'req2' => "2. LOKALIZACJA - NAJWAŻNIEJSZE!!:\n   - Artykuł MUSI dotyczyć WYŁĄCZNIE województwa '{$province}'\n   - Nazwa '{$province}' musi pojawić się 5-7 razy w tekście\n   - Wszystkie przykłady, wydarzenia, dane MUSZĄ być z województwa '{$province}'\n   - Cytuj osoby/firmy/instytucje z województwa '{$province}'\n   - Porównuj z innymi województwami, ale koncentruj się TYLKO na '{$province}'\n   - NIE pisz o innych województwach jako głównym temacie\n   - Jeśli informacje nie dotyczą '{$province}', NIE używaj ich!\n\n",
                'req3' => "3. SŁOWA KLUCZOWE: Wpleć organicznie następujące frazy: %s (używaj synonimów i pokrewnych terminów)\n",
                'req4' => "4. STRUKTURA PREMIUM:\n   - Tytuł (H1): Konkretny, angażujący, bez clickbaitu\n   - Lead (2-3 akapity): Wprowadzenie z kluczowymi informacjami\n   - 4-6 sekcji merytorycznych (H2): Każda z analizą przyczyn-skutków\n   - Podsekcje (H3) tam gdzie potrzebne: Dla większej szczegółowości\n   - Podsumowanie z przewidywaniami lub rekomendacjami\n\n",
                'req5' => "5. WARTOŚĆ MERYTORYCZNA I LOKALNE ELEMENTY:\n   - Używaj KONKRETNYCH liczb, danych, statystyk (np. kwoty, procenty, daty)\n   - Cytuj LOKALNYCH ekspertów, polityków, celebrytów z województwa '{$province}' (prawdziwe imiona i stanowiska)\n   - Wymień konkretne MIASTA, GMINY z województwa '{$province}'\n   - Wspomniej LOKALNYCH POLITYKÓW (marszałek, wojewoda, burmistrzowie, radni)\n   - Wspomniej LOKALNE WYDARZENIA, festiwale, uroczystości w '{$province}'\n   - Wspomniej LOKALNE FIRMY, inwestycje, projekty w '{$province}'\n   - Analizuj PRZYCZYNY i SKUTKI wydarzeń dla mieszkańców '{$province}'\n   - Dodaj KONTEKST historyczny lub porównania z innymi regionami\n   - Przedstaw różne PERSPEKTYWY (władze lokalne, mieszkańcy, eksperci regionalni)\n\n",
                'req6' => "6. JAKOŚĆ JĘZYKOWA:\n   - Używaj BOGATEGO słownictwa (synonimy, profesjonalne terminy)\n   - Unikaj powtórzeń - każdy akapit wnosi nową wartość\n   - Stosuj precyzyjne określenia zamiast ogólników\n   - Łącz fakty z narracją (storytelling z danymi)\n\n",
                'req7' => "7. STORYTELLING:\n   - Rozpocznij od konkretnego przykładu, sytuacji lub wydarzenia\n   - Pokaż wpływ na życie mieszkańców (humanizuj treść)\n   - Dodaj mikrohistorie lub case studies\n   - Zakończ wizją przyszłości lub wnioskami\n\n",
                'req8' => "8. OPTYMALIZACJA SEO I AI SEARCH (ChatGPT, Gemini, Perplexity):\n   - Naturalne rozmieszczenie słów kluczowych (nie forsuj)\n   - Długie akapity (3-5 zdań) dla lepszej czytelności\n   - Używaj pytań retorycznych angażujących czytelnika\n   - Strukturyzuj informacje - używaj list (ul/ol) tam gdzie możliwe\n   - Dodaj KONKRETNE FAKTY które AI będzie mogła zacytować\n   - Używaj jasnych, jednoznacznych stwierdzeń (dla AI fact-checking)\n   - Dodaj pełne nazwy (nie skróty) - np. 'województwo mazowieckie' zamiast 'woj. maz.'\n   - ZERO clickbaitu, sensacji, fake newsów\n\n",
                'req9' => "9. LOKALNE SŁOWA KLUCZOWE (dla pozycjonowania lokalnego):\n   - Używaj nazw: miasta, gminy, powiaty z '{$province}'\n   - Wspominaj: 'aktualności {$province}', 'wiadomości {$province}', 'wydarzenia {$province}'\n   - Dodaj: 'samorząd {$province}', 'inwestycje w {$province}'\n   - Jeśli są plotki/celebryci lokalni - wspomniej ich\n   - Używaj lokalnych określeń geograficznych (rzeki, góry, dzielnice)\n   - Dla lepszego AI Search: używaj pełnych fraz pytających (Kto? Co? Gdzie? Kiedy? Dlaczego?)\n\n",
                'req10' => "10. UNIKALNE ELEMENTY:\n   - Twoja własna analiza i interpretacja faktów\n   - Porównania międzyregionalne lub międzynarodowe\n   - Prognozy lub rekomendacje oparte na danych\n   - Wskazanie implikacji dla różnych grup (mieszkańcy, firmy, samorząd lokalny)\n\n",
                'format' => "Zwróć artykuł w formacie HTML z odpowiednimi tagami (h1, h2, h3, p, strong, em). Użyj <strong> dla kluczowych informacji i <em> dla akcentów."
            ),
            'de' => array(
                'intro' => "Schreibe einen WERTVOLLEN, tiefgehenden analytischen Artikel AUSSCHLIESSLICH über {$province} zum Thema: {$category_name}.\n\n",
                'base' => "Basiere auf folgenden aktuellen Informationen:\n{$news_data}\n\n",
                'requirements' => "PREMIUM-CONTENT-ANFORDERUNGEN (für maximalen Wert bei Google AdSense):\n\n",
                'req1' => "1. LÄNGE UND TIEFE: Der Artikel muss MINDESTENS 1500-1800 Wörter umfassen (spare nicht an Details)\n",
                'req2' => "2. LOKALISIERUNG - AM WICHTIGSTEN!!:\n   - Der Artikel MUSS AUSSCHLIESSLICH über '{$province}' handeln\n   - Der Name '{$province}' muss 5-7 Mal im Text erscheinen\n   - Alle Beispiele, Ereignisse, Daten MÜSSEN aus '{$province}' sein\n   - Zitiere Personen/Firmen/Institutionen aus '{$province}'\n   - Vergleiche mit anderen Regionen, aber konzentriere dich NUR auf '{$province}'\n   - Schreibe NICHT über andere Regionen als Hauptthema\n   - Wenn Informationen nicht '{$province}' betreffen, verwende sie NICHT!\n\n",
                'req3' => "3. SCHLÜSSELWÖRTER: Füge organisch folgende Begriffe ein: %s (verwende Synonyme und verwandte Begriffe)\n",
                'req4' => "4. PREMIUM-STRUKTUR:\n   - Titel (H1): Konkret, ansprechend, kein Clickbait\n   - Lead (2-3 Absätze): Einleitung mit Schlüsselinformationen\n   - 4-6 inhaltliche Abschnitte (H2): Jeder mit Ursache-Wirkungs-Analyse\n   - Unterabschnitte (H3) wo nötig: Für mehr Details\n   - Zusammenfassung mit Prognosen oder Empfehlungen\n\n",
                'req5' => "5. INHALTLICHER WERT UND LOKALE ELEMENTE:\n   - Verwende KONKRETE Zahlen, Daten, Statistiken (z.B. Beträge, Prozente, Termine)\n   - Zitiere LOKALE Experten, Politiker, Prominente aus '{$province}' (echte Namen und Positionen)\n   - Nenne konkrete STÄDTE, GEMEINDEN aus '{$province}'\n   - Erwähne LOKALE POLITIKER (Ministerpräsident, Landrat, Bürgermeister, Räte)\n   - Erwähne LOKALE VERANSTALTUNGEN, Festivals, Feierlichkeiten in '{$province}'\n   - Erwähne LOKALE UNTERNEHMEN, Investitionen, Projekte in '{$province}'\n   - Analysiere URSACHEN und FOLGEN von Ereignissen für Bürger von '{$province}'\n   - Füge historischen KONTEXT oder Vergleiche mit anderen Regionen hinzu\n   - Präsentiere verschiedene PERSPEKTIVEN (lokale Behörden, Bürger, regionale Experten)\n\n",
                'req6' => "6. SPRACHLICHE QUALITÄT:\n   - Verwende REICHHALTIGES Vokabular (Synonyme, Fachbegriffe)\n   - Vermeide Wiederholungen - jeder Absatz bietet neuen Wert\n   - Nutze präzise Formulierungen statt Allgemeinplätze\n   - Verbinde Fakten mit Erzählung (Storytelling mit Daten)\n\n",
                'req7' => "7. STORYTELLING:\n   - Beginne mit konkretem Beispiel, Situation oder Ereignis\n   - Zeige Auswirkungen auf das Leben der Bürger (humanisiere den Inhalt)\n   - Füge Mikrogeschichten oder Fallstudien hinzu\n   - Schließe mit Zukunftsvision oder Schlussfolgerungen\n\n",
                'req8' => "8. SEO- UND AI-SEARCH-OPTIMIERUNG (ChatGPT, Gemini, Perplexity):\n   - Natürliche Verteilung der Schlüsselwörter (nicht erzwingen)\n   - Längere Absätze (3-5 Sätze) für bessere Lesbarkeit\n   - Verwende rhetorische Fragen für Lesereinbindung\n   - Strukturiere Informationen - verwende Listen (ul/ol) wo möglich\n   - Füge KONKRETE FAKTEN hinzu, die AI zitieren kann\n   - Verwende klare, eindeutige Aussagen (für AI-Faktenprüfung)\n   - Füge vollständige Namen hinzu (keine Abkürzungen) - z.B. '{$province}' vollständig\n   - KEIN Clickbait, keine Sensationen, keine Fake News\n\n",
                'req9' => "9. LOKALE SCHLÜSSELWÖRTER (für lokale Positionierung):\n   - Verwende Namen: Städte, Gemeinden, Landkreise aus '{$province}'\n   - Erwähne: 'Nachrichten {$province}', 'Ereignisse {$province}'\n   - Füge hinzu: 'Kommunalverwaltung {$province}', 'Investitionen in {$province}'\n   - Falls es lokale Klatsch/Prominente gibt - erwähne sie\n   - Verwende lokale geografische Bezeichnungen (Flüsse, Berge, Bezirke)\n   - Für besseres AI Search: verwende vollständige Fragephrasen (Wer? Was? Wo? Wann? Warum?)\n\n",
                'req10' => "10. EINZIGARTIGE ELEMENTE:\n   - Deine eigene Analyse und Interpretation der Fakten\n   - Interregionale oder internationale Vergleiche\n   - Prognosen oder datenbasierte Empfehlungen\n   - Aufzeigen von Implikationen für verschiedene Gruppen (Bürger, Unternehmen, lokale Verwaltung)\n\n",
                'format' => "Gib den Artikel im HTML-Format mit entsprechenden Tags zurück (h1, h2, h3, p, strong, em). Verwende <strong> für Schlüsselinformationen und <em> für Akzente."
            ),
            'en' => array(
                'intro' => "Write a VALUABLE, in-depth analytical article EXCLUSIVELY about {$province} on: {$category_name}.\n\n",
                'base' => "Base it on the following current information:\n{$news_data}\n\n",
                'requirements' => "PREMIUM CONTENT REQUIREMENTS (for maximum value in Google AdSense):\n\n",
                'req1' => "1. LENGTH AND DEPTH: The article must be AT LEAST 1500-1800 words (don't skimp on details)\n",
                'req2' => "2. LOCALIZATION - MOST IMPORTANT!!:\n   - The article MUST be EXCLUSIVELY about '{$province}'\n   - The name '{$province}' must appear 5-7 times in the text\n   - All examples, events, data MUST be from '{$province}'\n   - Quote people/companies/institutions from '{$province}'\n   - Compare with other regions, but focus ONLY on '{$province}'\n   - Do NOT write about other regions as the main topic\n   - If information doesn't concern '{$province}', do NOT use it!\n\n",
                'req3' => "3. KEYWORDS: Organically incorporate the following phrases: %s (use synonyms and related terms)\n",
                'req4' => "4. PREMIUM STRUCTURE:\n   - Title (H1): Specific, engaging, no clickbait\n   - Lead (2-3 paragraphs): Introduction with key information\n   - 4-6 substantive sections (H2): Each with cause-effect analysis\n   - Subsections (H3) where needed: For greater detail\n   - Summary with predictions or recommendations\n\n",
                'req5' => "5. SUBSTANTIVE VALUE AND LOCAL ELEMENTS:\n   - Use SPECIFIC numbers, data, statistics (e.g., amounts, percentages, dates)\n   - Quote LOCAL experts, politicians, celebrities from '{$province}' (real names and positions)\n   - Name specific CITIES, MUNICIPALITIES from '{$province}'\n   - Mention LOCAL POLITICIANS (marshal, voivode, mayors, councilors)\n   - Mention LOCAL EVENTS, festivals, ceremonies in '{$province}'\n   - Mention LOCAL COMPANIES, investments, projects in '{$province}'\n   - Analyze CAUSES and EFFECTS of events for residents of '{$province}'\n   - Add historical CONTEXT or comparisons with other regions\n   - Present different PERSPECTIVES (local authorities, residents, regional experts)\n\n",
                'req6' => "6. LANGUAGE QUALITY:\n   - Use RICH vocabulary (synonyms, professional terms)\n   - Avoid repetition - each paragraph adds new value\n   - Use precise expressions instead of generalities\n   - Combine facts with narrative (storytelling with data)\n\n",
                'req7' => "7. STORYTELLING:\n   - Start with a concrete example, situation, or event\n   - Show impact on residents' lives (humanize content)\n   - Add micro-stories or case studies\n   - End with a vision of the future or conclusions\n\n",
                'req8' => "8. SEO AND AI SEARCH OPTIMIZATION (ChatGPT, Gemini, Perplexity):\n   - Natural distribution of keywords (don't force it)\n   - Longer paragraphs (3-5 sentences) for better readability\n   - Use rhetorical questions to engage readers\n   - Structure information - use lists (ul/ol) where possible\n   - Add SPECIFIC FACTS that AI can quote\n   - Use clear, unambiguous statements (for AI fact-checking)\n   - Add full names (not abbreviations) - e.g., 'Masovian Voivodeship' instead of 'Masov. Voiv.'\n   - ZERO clickbait, sensationalism, fake news\n\n",
                'req9' => "9. LOCAL KEYWORDS (for local positioning):\n   - Use names: cities, municipalities, counties from '{$province}'\n   - Mention: 'news {$province}', 'events {$province}'\n   - Add: 'local government {$province}', 'investments in {$province}'\n   - If there are local gossip/celebrities - mention them\n   - Use local geographical terms (rivers, mountains, districts)\n   - For better AI Search: use full question phrases (Who? What? Where? When? Why?)\n\n",
                'req10' => "10. UNIQUE ELEMENTS:\n   - Your own analysis and interpretation of facts\n   - Inter-regional or international comparisons\n   - Forecasts or data-driven recommendations\n   - Indication of implications for different groups (residents, businesses, local government)\n\n",
                'format' => "Return the article in HTML format with appropriate tags (h1, h2, h3, p, strong, em). Use <strong> for key information and <em> for emphasis."
            ),
            'uk' => array(
                'intro' => "Напиши ЦІННУ, глибоку аналітичну статтю ВИКЛЮЧНО про {$province} на тему: {$category_name}.\n\n",
                'base' => "Базуйся на наступній актуальній інформації:\n{$news_data}\n\n",
                'requirements' => "ВИМОГИ ДО ПРЕМІУМ-КОНТЕНТУ (для максимальної цінності в Google AdSense):\n\n",
                'req1' => "1. ОБСЯГ І ГЛИБИНА: Стаття повинна містити МІНІМУМ 1500-1800 слів (не економ на деталях)\n",
                'req2' => "2. ЛОКАЛІЗАЦІЯ - НАЙВАЖЛИВІШЕ!!:\n   - Стаття ПОВИННА стосуватися ВИКЛЮЧНО '{$province}'\n   - Назва '{$province}' повинна з'явитися 5-7 разів у тексті\n   - Всі приклади, події, дані ПОВИННІ бути з '{$province}'\n   - Цитуй людей/компанії/установи з '{$province}'\n   - Порівнюй з іншими регіонами, але зосереджуйся ТІЛЬКИ на '{$province}'\n   - НЕ пиши про інші регіони як основну тему\n   - Якщо інформація не стосується '{$province}', НЕ використовуй її!\n\n",
                'req3' => "3. КЛЮЧОВІ СЛОВА: Органічно вплети наступні фрази: %s (використовуй синоніми та споріднені терміни)\n",
                'req4' => "4. ПРЕМІУМ-СТРУКТУРА:\n   - Заголовок (H1): Конкретний, цікавий, без клікбейту\n   - Лід (2-3 абзаци): Вступ з ключовою інформацією\n   - 4-6 змістовних розділів (H2): Кожен з аналізом причин-наслідків\n   - Підрозділи (H3) де потрібно: Для більшої деталізації\n   - Підсумок з прогнозами або рекомендаціями\n\n",
                'req5' => "5. ЗМІСТОВНА ЦІННІСТЬ ТА ЛОКАЛЬНІ ЕЛЕМЕНТИ:\n   - Використовуй КОНКРЕТНІ цифри, дані, статистику (напр., суми, відсотки, дати)\n   - Цитуй ЛОКАЛЬНИХ експертів, політиків, селебріті з '{$province}' (справжні імена та посади)\n   - Називай конкретні МІСТА, ГРОМАДИ з '{$province}'\n   - Згадуй ЛОКАЛЬНИХ ПОЛІТИКІВ (голова області, губернатор, мери, депутати)\n   - Згадуй ЛОКАЛЬНІ ПОДІЇ, фестивалі, урочистості в '{$province}'\n   - Згадуй ЛОКАЛЬНІ КОМПАНІЇ, інвестиції, проєкти в '{$province}'\n   - Аналізуй ПРИЧИНИ та НАСЛІДКИ подій для мешканців '{$province}'\n   - Додай історичний КОНТЕКСТ або порівняння з іншими регіонами\n   - Представ різні ПЕРСПЕКТИВИ (місцева влада, мешканці, регіональні експерти)\n\n",
                'req6' => "6. МОВНА ЯКІСТЬ:\n   - Використовуй БАГАТИЙ словниковий запас (синоніми, професійні терміни)\n   - Уникай повторень - кожен абзац додає нову цінність\n   - Застосовуй точні формулювання замість загальних фраз\n   - Поєднуй факти з наративом (сторітелінг з даними)\n\n",
                'req7' => "7. СТОРІТЕЛІНГ:\n   - Почни з конкретного прикладу, ситуації або події\n   - Покажи вплив на життя мешканців (гуманізуй контент)\n   - Додай мікроісторії або кейс-стаді\n   - Завершуй баченням майбутнього або висновками\n\n",
                'req8' => "8. SEO ТА AI SEARCH ОПТИМІЗАЦІЯ (ChatGPT, Gemini, Perplexity):\n   - Природний розподіл ключових слів (не форсуй)\n   - Довші абзаци (3-5 речень) для кращої читабельності\n   - Використовуй риторичні питання для залучення читачів\n   - Структуруй інформацію - використовуй списки (ul/ol) де можливо\n   - Додай КОНКРЕТНІ ФАКТИ які AI зможе процитувати\n   - Використовуй чіткі, однозначні твердження (для AI перевірки фактів)\n   - Додай повні назви (не скорочення) - напр., '{$province}' повністю\n   - НУЛЬ клікбейту, сенсацій, фейкових новин\n\n",
                'req9' => "9. ЛОКАЛЬНІ КЛЮЧОВІ СЛОВА (для локального позиціонування):\n   - Використовуй назви: міста, громади, райони з '{$province}'\n   - Згадуй: 'новини {$province}', 'події {$province}'\n   - Додай: 'місцева влада {$province}', 'інвестиції в {$province}'\n   - Якщо є місцеві плітки/селебріті - згадай їх\n   - Використовуй локальні географічні назви (річки, гори, райони)\n   - Для кращого AI Search: використовуй повні питальні фрази (Хто? Що? Де? Коли? Чому?)\n\n",
                'req10' => "10. УНІКАЛЬНІ ЕЛЕМЕНТИ:\n   - Твій власний аналіз та інтерпретація фактів\n   - Міжрегіональні або міжнародні порівняння\n   - Прогнози або рекомендації на основі даних\n   - Вказівка на наслідки для різних груп (мешканці, бізнес, місцева влада)\n\n",
                'format' => "Поверни статтю у форматі HTML з відповідними тегами (h1, h2, h3, p, strong, em). Використовуй <strong> для ключової інформації та <em> для акцентів."
            )
        );
        
        $template = isset($templates[$language]) ? $templates[$language] : $templates['pl'];
        
        $prompt = $template['intro'];
        $prompt .= $template['base'];
        $prompt .= $template['requirements'];
        $prompt .= $template['req1'];
        $prompt .= $template['req2'];
        
        if (!empty($keywords)) {
            $keywords_str = implode(', ', $keywords);
            $prompt .= sprintf($template['req3'], $keywords_str);
        }
        
        $prompt .= $template['req4'];
        $prompt .= $template['req5'];
        $prompt .= $template['req6'];
        $prompt .= $template['req7'];
        $prompt .= $template['req8'];
        $prompt .= $template['req9'];
        
        if (isset($template['req10'])) {
            $prompt .= $template['req10'];
        }
        
        $prompt .= $template['format'];
        
        return $prompt;
    }
    
    /**
     * Buduje prompt dla generowania obrazu
     */
    private function build_image_prompt($article_title, $category_name) {
        // DALL-E preferuje opisy po angielsku
        $prompt = "A professional, high-quality editorial image representing: {$article_title}. ";
        $prompt .= "Category: {$category_name}. ";
        $prompt .= "Style: modern, clean, photorealistic, suitable for news article. ";
        $prompt .= "No text or watermarks in the image.";
        
        return $prompt;
    }
    
    /**
     * Buduje prompt dla wpisu na Facebooku
     */
    private function build_facebook_prompt($article_title, $article_excerpt, $province, $language = 'pl') {
        $templates = array(
            'pl' => "Na podstawie poniższego artykułu napisz krótki, angażujący wpis na Facebooka (maksymalnie 250 znaków).\n\n" .
                   "Tytuł artykułu: {$article_title}\n\n" .
                   "Fragment artykułu: {$article_excerpt}\n\n" .
                   "Wpis powinien być zachęcający do przeczytania całości, zawierać emocje i hashtagi. " .
                   "Uwzględnij województwo: {$province}",
            'de' => "Schreibe basierend auf dem folgenden Artikel einen kurzen, ansprechenden Facebook-Post (maximal 250 Zeichen).\n\n" .
                   "Artikeltitel: {$article_title}\n\n" .
                   "Artikelauszug: {$article_excerpt}\n\n" .
                   "Der Beitrag sollte zum Lesen des gesamten Artikels anregen, Emotionen enthalten und Hashtags verwenden. " .
                   "Beziehe das Bundesland ein: {$province}",
            'en' => "Based on the article below, write a short, engaging Facebook post (maximum 250 characters).\n\n" .
                   "Article title: {$article_title}\n\n" .
                   "Article excerpt: {$article_excerpt}\n\n" .
                   "The post should encourage reading the full article, contain emotions and use hashtags. " .
                   "Include the state/region: {$province}",
            'uk' => "На основі наступної статті напиши короткий, захоплюючий пост для Facebook (максимум 250 символів).\n\n" .
                   "Заголовок статті: {$article_title}\n\n" .
                   "Фрагмент статті: {$article_excerpt}\n\n" .
                   "Пост повинен спонукати до прочитання повної статті, містити емоції та використовувати хештеги. " .
                   "Включи регіон: {$province}"
        );
        
        return isset($templates[$language]) ? $templates[$language] : $templates['pl'];
    }
    
    /**
     * Buduje system prompt dla wpisu na Facebooku
     */
    private function build_facebook_system_prompt($language = 'pl') {
        $prompts = array(
            'pl' => 'Jesteś ekspertem od mediów społecznościowych. Tworzysz krótkie, angażujące wpisy na Facebooka.',
            'de' => 'Du bist ein Social-Media-Experte. Du erstellst kurze, ansprechende Facebook-Posts.',
            'en' => 'You are a social media expert. You create short, engaging Facebook posts.',
            'uk' => 'Ти експерт з соціальних мереж. Ти створюєш короткі, захоплюючі пости для Facebook.'
        );
        
        return isset($prompts[$language]) ? $prompts[$language] : $prompts['pl'];
    }
}
