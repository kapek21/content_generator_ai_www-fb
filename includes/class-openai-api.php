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
                'req5' => "5. WARTOŚĆ MERYTORYCZNA:\n   - Używaj KONKRETNYCH liczb, danych, statystyk (np. kwoty, procenty, daty)\n   - Cytuj ekspertów lub przedstawicieli instytucji (prawdziwe imiona i stanowiska)\n   - Analizuj PRZYCZYNY i SKUTKI wydarzeń\n   - Dodaj KONTEKST historyczny lub porównania z innymi regionami\n   - Przedstaw różne PERSPEKTYWY (władze, mieszkańcy, eksperci)\n\n",
                'req6' => "6. JAKOŚĆ JĘZYKOWA:\n   - Używaj BOGATEGO słownictwa (synonimy, profesjonalne terminy)\n   - Unikaj powtórzeń - każdy akapit wnosi nową wartość\n   - Stosuj precyzyjne określenia zamiast ogólników\n   - Łącz fakty z narracją (storytelling z danymi)\n\n",
                'req7' => "7. STORYTELLING:\n   - Rozpocznij od konkretnego przykładu, sytuacji lub wydarzenia\n   - Pokaż wpływ na życie mieszkańców (humanizuj treść)\n   - Dodaj mikrohistorie lub case studies\n   - Zakończ wizją przyszłości lub wnioskami\n\n",
                'req8' => "8. OPTYMALIZACJA SEO I ADSENSE:\n   - Naturalne rozmieszczenie słów kluczowych (nie forsuj)\n   - Długie akapity (3-5 zdań) dla lepszej czytelności\n   - Używaj pytań retorycznych angażujących czytelnika\n   - Dodaj wewnętrzne linkowanie (gdzie stosowne)\n   - ZERO clickbaitu, sensacji, fake newsów\n\n",
                'req9' => "9. UNIKALNE ELEMENTY:\n   - Twoja własna analiza i interpretacja faktów\n   - Porównania międzyregionalne lub międzynarodowe\n   - Prognozy lub rekomendacje oparte na danych\n   - Wskazanie implikacji dla różnych grup (mieszkańcy, firmy, samorząd)\n\n",
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
                'req5' => "5. INHALTLICHER WERT:\n   - Verwende KONKRETE Zahlen, Daten, Statistiken (z.B. Beträge, Prozente, Termine)\n   - Zitiere Experten oder Institutionsvertreter (echte Namen und Positionen)\n   - Analysiere URSACHEN und FOLGEN von Ereignissen\n   - Füge historischen KONTEXT oder Vergleiche mit anderen Regionen hinzu\n   - Präsentiere verschiedene PERSPEKTIVEN (Behörden, Bürger, Experten)\n\n",
                'req6' => "6. SPRACHLICHE QUALITÄT:\n   - Verwende REICHHALTIGES Vokabular (Synonyme, Fachbegriffe)\n   - Vermeide Wiederholungen - jeder Absatz bietet neuen Wert\n   - Nutze präzise Formulierungen statt Allgemeinplätze\n   - Verbinde Fakten mit Erzählung (Storytelling mit Daten)\n\n",
                'req7' => "7. STORYTELLING:\n   - Beginne mit konkretem Beispiel, Situation oder Ereignis\n   - Zeige Auswirkungen auf das Leben der Bürger (humanisiere den Inhalt)\n   - Füge Mikrogeschichten oder Fallstudien hinzu\n   - Schließe mit Zukunftsvision oder Schlussfolgerungen\n\n",
                'req8' => "8. SEO- UND ADSENSE-OPTIMIERUNG:\n   - Natürliche Verteilung der Schlüsselwörter (nicht erzwingen)\n   - Längere Absätze (3-5 Sätze) für bessere Lesbarkeit\n   - Verwende rhetorische Fragen für Lesereinbindung\n   - Füge interne Verlinkungen hinzu (wo angemessen)\n   - KEIN Clickbait, keine Sensationen, keine Fake News\n\n",
                'req9' => "9. EINZIGARTIGE ELEMENTE:\n   - Deine eigene Analyse und Interpretation der Fakten\n   - Interregionale oder internationale Vergleiche\n   - Prognosen oder datenbasierte Empfehlungen\n   - Aufzeigen von Implikationen für verschiedene Gruppen (Bürger, Unternehmen, Verwaltung)\n\n",
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
                'req5' => "5. SUBSTANTIVE VALUE:\n   - Use SPECIFIC numbers, data, statistics (e.g., amounts, percentages, dates)\n   - Quote experts or institutional representatives (real names and positions)\n   - Analyze CAUSES and EFFECTS of events\n   - Add historical CONTEXT or comparisons with other regions\n   - Present different PERSPECTIVES (authorities, residents, experts)\n\n",
                'req6' => "6. LANGUAGE QUALITY:\n   - Use RICH vocabulary (synonyms, professional terms)\n   - Avoid repetition - each paragraph adds new value\n   - Use precise expressions instead of generalities\n   - Combine facts with narrative (storytelling with data)\n\n",
                'req7' => "7. STORYTELLING:\n   - Start with a concrete example, situation, or event\n   - Show impact on residents' lives (humanize content)\n   - Add micro-stories or case studies\n   - End with a vision of the future or conclusions\n\n",
                'req8' => "8. SEO AND ADSENSE OPTIMIZATION:\n   - Natural distribution of keywords (don't force it)\n   - Longer paragraphs (3-5 sentences) for better readability\n   - Use rhetorical questions to engage readers\n   - Add internal linking (where appropriate)\n   - ZERO clickbait, sensationalism, fake news\n\n",
                'req9' => "9. UNIQUE ELEMENTS:\n   - Your own analysis and interpretation of facts\n   - Inter-regional or international comparisons\n   - Forecasts or data-driven recommendations\n   - Indication of implications for different groups (residents, businesses, government)\n\n",
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
                'req5' => "5. ЗМІСТОВНА ЦІННІСТЬ:\n   - Використовуй КОНКРЕТНІ цифри, дані, статистику (напр., суми, відсотки, дати)\n   - Цитуй експертів або представників установ (справжні імена та посади)\n   - Аналізуй ПРИЧИНИ та НАСЛІДКИ подій\n   - Додай історичний КОНТЕКСТ або порівняння з іншими регіонами\n   - Представ різні ПЕРСПЕКТИВИ (влада, мешканці, експерти)\n\n",
                'req6' => "6. МОВНА ЯКІСТЬ:\n   - Використовуй БАГАТИЙ словниковий запас (синоніми, професійні терміни)\n   - Уникай повторень - кожен абзац додає нову цінність\n   - Застосовуй точні формулювання замість загальних фраз\n   - Поєднуй факти з наративом (сторітелінг з даними)\n\n",
                'req7' => "7. СТОРІТЕЛІНГ:\n   - Почни з конкретного прикладу, ситуації або події\n   - Покажи вплив на життя мешканців (гуманізуй контент)\n   - Додай мікроісторії або кейс-стаді\n   - Завершуй баченням майбутнього або висновками\n\n",
                'req8' => "8. SEO ТА ADSENSE ОПТИМІЗАЦІЯ:\n   - Природний розподіл ключових слів (не форсуй)\n   - Довші абзаци (3-5 речень) для кращої читабельності\n   - Використовуй риторичні питання для залучення читачів\n   - Додай внутрішні посилання (де доречно)\n   - НУЛЬ клікбейту, сенсацій, фейкових новин\n\n",
                'req9' => "9. УНІКАЛЬНІ ЕЛЕМЕНТИ:\n   - Твій власний аналіз та інтерпретація фактів\n   - Міжрегіональні або міжнародні порівняння\n   - Прогнози або рекомендації на основі даних\n   - Вказівка на наслідки для різних груп (мешканці, бізнес, влада)\n\n",
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
