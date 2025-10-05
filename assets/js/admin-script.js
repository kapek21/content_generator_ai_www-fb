/**
 * JavaScript dla panelu administracyjnego AI Content Publisher
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Zmiana częstotliwości kategorii
        $('.aicp-frequency-select').on('change', function() {
            var $select = $(this);
            var categoryId = $select.data('category-id');
            var frequency = $select.val();
            var $status = $select.siblings('.frequency-save-status');
            var $row = $select.closest('tr');
            
            $status.hide();
            
            $.ajax({
                url: aicpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aicp_save_category_frequency',
                    category_id: categoryId,
                    frequency: frequency,
                    nonce: aicpAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('✓ Zapisano').fadeIn().delay(2000).fadeOut();
                        
                        // Odśwież stronę po 1 sekundzie, aby zaktualizować daty
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Błąd: ' + response.data);
                    }
                },
                error: function() {
                    alert('Błąd połączenia z serwerem');
                }
            });
        });
        
        // Test wszystkich połączeń API
        $('#test-all-apis').on('click', function() {
            var $button = $(this);
            var $results = $('#api-test-results');
            
            $button.prop('disabled', true).text('Testowanie...');
            $results.html('<div class="api-test-result loading"><span class="dashicons dashicons-update"></span> Testowanie połączeń...</div>');
            
            $.ajax({
                url: aicpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aicp_test_connection',
                    service: 'all',
                    nonce: aicpAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        var results = response.data;
                        
                        $.each(results, function(service, success) {
                            var serviceNames = {
                                'perplexity': 'Perplexity API',
                                'openai': 'OpenAI API',
                                'facebook': 'Facebook API'
                            };
                            
                            if (success === 'disabled') {
                                html += '<div class="api-test-result" style="background: #f0f0f0; color: #666; border: 1px solid #ddd;">';
                                html += '<span class="dashicons dashicons-minus"></span>';
                                html += '<strong>' + serviceNames[service] + ':</strong> Wyłączone';
                                html += '</div>';
                            } else if (success) {
                                html += '<div class="api-test-result success">';
                                html += '<span class="dashicons dashicons-yes-alt"></span>';
                                html += '<strong>' + serviceNames[service] + ':</strong> Połączenie udane ✓';
                                html += '</div>';
                            } else {
                                html += '<div class="api-test-result error">';
                                html += '<span class="dashicons dashicons-dismiss"></span>';
                                html += '<strong>' + serviceNames[service] + ':</strong> Błąd połączenia ✗';
                                html += '</div>';
                            }
                        });
                        
                        $results.html(html);
                    } else {
                        $results.html('<div class="api-test-result error"><span class="dashicons dashicons-dismiss"></span> Błąd: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    $results.html('<div class="api-test-result error"><span class="dashicons dashicons-dismiss"></span> Błąd połączenia z serwerem</div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Testuj wszystkie połączenia');
                }
            });
        });
        
        // Generowanie treści dla pojedynczej kategorii
        $('.generate-content-btn').on('click', function() {
            var $button = $(this);
            var categoryId = $button.data('category-id');
            var categoryName = $button.data('category-name');
            var $row = $button.closest('tr');
            var $spinner = $row.find('.spinner');
            var $status = $row.find('.generation-status');
            
            if (!confirm('Czy na pewno chcesz wygenerować artykuł dla kategorii: ' + categoryName + '?\n\nProces może zająć 2-3 minuty.')) {
                return;
            }
            
            // Zablokuj przycisk i pokaż spinner
            $button.prop('disabled', true).addClass('generating');
            $spinner.addClass('is-active');
            $status.removeClass('success error').addClass('processing').text('Generowanie...');
            
            // Utwórz log viewer
            var $logViewer = $('<div class="aicp-log-viewer"></div>');
            $status.after($logViewer);
            
            function addLog(message, type) {
                type = type || 'info';
                var timestamp = new Date().toLocaleTimeString();
                var $entry = $('<div class="aicp-log-entry"></div>');
                $entry.html(
                    '<span class="aicp-log-timestamp">[' + timestamp + ']</span>' +
                    '<span class="aicp-log-' + type + '">' + message + '</span>'
                );
                $logViewer.append($entry);
                $logViewer.scrollTop($logViewer[0].scrollHeight);
            }
            
            addLog('Rozpoczęto generowanie artykułu dla kategorii: ' + categoryName);
            
            $.ajax({
                url: aicpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aicp_generate_content',
                    category_id: categoryId,
                    nonce: aicpAjax.nonce
                },
                timeout: 300000, // 5 minut
                success: function(response) {
                    if (response.success) {
                        var result = response.data;
                        
                        // Dodaj logi kroków
                        if (result.steps) {
                            $.each(result.steps, function(i, step) {
                                addLog(step, 'success');
                            });
                        }
                        
                        $status.removeClass('processing').addClass('success').text('✓ Ukończono');
                        
                        if (result.post_url) {
                            addLog('Artykuł opublikowany: ' + result.post_url, 'success');
                            var $viewButton = $('<a href="' + result.post_url + '" target="_blank" class="button button-small" style="margin-left: 10px;">Zobacz wpis</a>');
                            $status.after($viewButton);
                        }
                        
                        // Odśwież stronę po 3 sekundach
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                        
                    } else {
                        addLog('Błąd: ' + response.data, 'error');
                        $status.removeClass('processing').addClass('error').text('✗ Błąd');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Błąd połączenia: ' + error;
                    if (status === 'timeout') {
                        errorMsg = 'Przekroczono limit czasu. Spróbuj ponownie.';
                    }
                    addLog(errorMsg, 'error');
                    $status.removeClass('processing').addClass('error').text('✗ Błąd');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('generating');
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Generowanie dla wszystkich kategorii
        $('#generate-all-categories').on('click', function() {
            var $button = $(this);
            var $buttons = $('.generate-content-btn');
            var totalCategories = $buttons.length;
            
            if (totalCategories === 0) {
                alert('Brak kategorii do przetworzenia.');
                return;
            }
            
            if (!confirm('Czy na pewno chcesz wygenerować artykuły dla WSZYSTKICH ' + totalCategories + ' kategorii?\n\nProces może zająć długi czas (około ' + (totalCategories * 2) + '-' + (totalCategories * 3) + ' minut).\n\nUpewnij się, że masz wystarczające limity API.')) {
                return;
            }
            
            $button.prop('disabled', true).text('Generowanie w toku...');
            
            // Utwórz progress bar
            var $progressContainer = $('<div class="aicp-progress"></div>');
            var $progressBar = $('<div class="aicp-progress-bar">0%</div>');
            $progressContainer.append($progressBar);
            $button.after($progressContainer);
            
            var currentIndex = 0;
            
            function generateNext() {
                if (currentIndex >= totalCategories) {
                    $progressBar.css('width', '100%').text('100% - Ukończono!');
                    alert('Ukończono generowanie artykułów dla wszystkich kategorii!');
                    location.reload();
                    return;
                }
                
                var $currentButton = $buttons.eq(currentIndex);
                var progress = Math.round(((currentIndex + 1) / totalCategories) * 100);
                $progressBar.css('width', progress + '%').text(progress + '% - ' + $currentButton.data('category-name'));
                
                // Kliknij przycisk
                $currentButton.trigger('click');
                
                // Czekaj na zakończenie (sprawdzaj co sekundę)
                var checkInterval = setInterval(function() {
                    if (!$currentButton.prop('disabled')) {
                        clearInterval(checkInterval);
                        currentIndex++;
                        // Odczekaj 5 sekund między generowaniami
                        setTimeout(generateNext, 5000);
                    }
                }, 1000);
            }
            
            generateNext();
        });
        
        // Potwierdzenie przed opuszczeniem strony podczas generowania
        var isGenerating = false;
        
        $(document).on('ajaxSend', function(event, jqxhr, settings) {
            if (settings.data && settings.data.indexOf('aicp_generate_content') !== -1) {
                isGenerating = true;
            }
        });
        
        $(document).on('ajaxComplete', function(event, jqxhr, settings) {
            if (settings.data && settings.data.indexOf('aicp_generate_content') !== -1) {
                isGenerating = false;
            }
        });
        
        $(window).on('beforeunload', function() {
            if (isGenerating) {
                return 'Generowanie treści jest w toku. Czy na pewno chcesz opuścić stronę?';
            }
        });
        
        // Auto-save w formularzu ustawień
        var settingsChanged = false;
        
        $('.aicp-settings input, .aicp-settings textarea, .aicp-settings select').on('change', function() {
            settingsChanged = true;
        });
        
        $(window).on('beforeunload', function() {
            if (settingsChanged) {
                return 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
            }
        });
        
        $('.aicp-settings form').on('submit', function() {
            settingsChanged = false;
        });
        
        // Tooltip initialization (jeśli potrzebne)
        $('.aicp-tooltip').hover(
            function() {
                $(this).find('.tooltiptext').fadeIn(200);
            },
            function() {
                $(this).find('.tooltiptext').fadeOut(200);
            }
        );
        
    });
    
})(jQuery);
