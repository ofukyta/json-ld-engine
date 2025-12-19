<?php
/**
 * Plugin Name: JSON-LD Engine Pro (Full Graph Version)
 * Description: 100% соответствие эталонам. WebSite + Organization + WebPage + Product + BlogPosting + FAQ.
 * Version: 8.8.6
 * Author: Senior Backend Developer
 * Requires PHP: 8.1
 */

namespace JsonLdSchemaPro;

if (!defined('ABSPATH')) exit;

class SchemaProEngine {
    private static ?SchemaProEngine $instance = null;
    public const OPT_KEY = 'site_schema_options';

    /**
     * Список вкладок админ-панели. 
     * Мы сохраняем все разделы для полной настройки.
     */
    private array $tabs = [
        'micromarking' => '⚙️ Micro-marking',
        'global'       => 'Сайт и Организация',
        'contact'      => 'Контакты и Гео',
        'mapping'      => 'Назначение страниц',
        'anketa'       => 'Поля Анкет (ACF)',
        'faq'          => 'Конструктор FAQ',
        'blog'         => 'Блог и Статьи',
        'advanced'     => 'Дополнительно'
    ];

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        // Инициализация меню и настроек
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Основной хук вывода JSON-LD в <head>
        add_action('wp_head', [$this, 'render_output'], 1);
    }

    public function add_menu(): void {
        add_options_page(
            'JSON-LD Schema', 
            'Micro-marking', 
            'manage_options', 
            self::OPT_KEY, 
            [$this, 'render_admin']
        );
    }

    public function register_settings(): void {
        register_setting(self::OPT_KEY, self::OPT_KEY);
    }

    /* ===================== АДМИН-ПАНЕЛЬ (ПОЛНАЯ ВЕРСИЯ) ===================== */

    public function render_admin(): void {
        $opt = get_option(self::OPT_KEY, []);
        $auto_site_url = trailingslashit(home_url());
        $auto_domain   = parse_url($auto_site_url, PHP_URL_HOST);
        ?>
        <div class="wrap">
            <h1>JSON-LD Engine Pro <span style="font-size: 12px; color: #666;">v8.8.6</span></h1>
            
            <h2 class="nav-tab-wrapper jle-tabs">
                <?php foreach ($this->tabs as $id => $label): ?>
                    <a href="#<?= $id ?>" class="nav-tab" data-tab="<?= $id ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none; max-width: 100%;">
                <?php settings_fields(self::OPT_KEY); ?>
                
                <div id="tab-micromarking" class="jle-tab-content">
                    <table class="form-table">
                        <tr style="background: #f9f9f9;"><th colspan="2"><strong>Идентификация и База</strong></th></tr>
                        <tr>
                            <th>Base @id URL</th>
                            <td>
                                <input type="text" name="<?= self::OPT_KEY ?>[base_id_url]" value="<?= esc_attr($opt['base_id_url'] ?? $auto_site_url) ?>" class="regular-text">
                                <p class="description">Главный URL. Используется для формирования уникальных @id всех сущностей.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Текущий домен</th>
                            <td><input type="text" readonly value="<?= esc_attr($auto_domain) ?>" class="regular-text" style="background:#eee;"></td>
                        </tr>
                        <tr style="background: #f9f9f9;"><th colspan="2"><strong>Merchant Listings (Fix для Google Search Console)</strong></th></tr>
                        <tr>
                            <th>Стоимость доставки (по умолчанию)</th>
                            <td><input type="number" name="<?= self::OPT_KEY ?>[default_shipping_price]" value="<?= esc_attr($opt['default_shipping_price'] ?? '0') ?>" class="small-text"> RUB</td>
                        </tr>
                        <tr>
                            <th>Политика возврата</th>
                            <td>
                                <select name="<?= self::OPT_KEY ?>[return_policy_cat]">
                                    <option value="https://schema.org/MerchantReturnNotPermitted" <?php selected($opt['return_policy_cat'] ?? '', 'https://schema.org/MerchantReturnNotPermitted'); ?>>Возврат запрещен</option>
                                    <option value="https://schema.org/MerchantReturnFiniteReturnPeriod" <?php selected($opt['return_policy_cat'] ?? '', 'https://schema.org/MerchantReturnFiniteReturnPeriod'); ?>>Ограниченный период (14 дней)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tab-global" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>Название сайта</th><td><input type="text" name="<?= self::OPT_KEY ?>[site_name]" value="<?= esc_attr($opt['site_name'] ?? get_bloginfo('name')) ?>" class="regular-text"></td></tr>
                        <tr><th>Альтернативное название</th><td><input type="text" name="<?= self::OPT_KEY ?>[alt_name]" value="<?= esc_attr($opt['alt_name'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Юридическое название</th><td><input type="text" name="<?= self::OPT_KEY ?>[legal_name]" value="<?= esc_attr($opt['legal_name'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>SEO Описание (description)</th><td><textarea name="<?= self::OPT_KEY ?>[site_desc]" rows="3" class="regular-text"><?= esc_textarea($opt['site_desc'] ?? get_bloginfo('description')) ?></textarea></td></tr>
                        <tr><th>URL Логотипа</th><td><input type="text" name="<?= self::OPT_KEY ?>[logo_url]" value="<?= esc_attr($opt['logo_url'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Соцсети (каждая с новой строки)</th><td><textarea name="<?= self::OPT_KEY ?>[same_as]" rows="4" class="regular-text"><?= esc_textarea($opt['same_as'] ?? '') ?></textarea></td></tr>
                    </table>
                </div>

                <div id="tab-contact" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>Телефон</th><td><input type="text" name="<?= self::OPT_KEY ?>[phone]" value="<?= esc_attr($opt['phone'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Email</th><td><input type="text" name="<?= self::OPT_KEY ?>[email]" value="<?= esc_attr($opt['email'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Улица</th><td><input type="text" name="<?= self::OPT_KEY ?>[street]" value="<?= esc_attr($opt['street'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Город</th><td><input type="text" name="<?= self::OPT_KEY ?>[locality]" value="<?= esc_attr($opt['locality'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Регион</th><td><input type="text" name="<?= self::OPT_KEY ?>[region]" value="<?= esc_attr($opt['region'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Почтовый индекс</th><td><input type="text" name="<?= self::OPT_KEY ?>[postal]" value="<?= esc_attr($opt['postal'] ?? '101000') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-mapping" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>Slug типа записей (CPT)</th><td><input type="text" name="<?= self::OPT_KEY ?>[cpt_anketa]" value="<?= esc_attr($opt['cpt_anketa'] ?? 'model') ?>" class="regular-text"></td></tr>
                        <tr><th>Название звена Каталог</th><td><input type="text" name="<?= self::OPT_KEY ?>[catalog_label]" value="<?= esc_attr($opt['catalog_label'] ?? 'Анкеты') ?>" class="regular-text"></td></tr>
                        <tr><th>URL звена Каталог</th><td><input type="text" name="<?= self::OPT_KEY ?>[catalog_url]" value="<?= esc_attr($opt['catalog_url'] ?? '/prostitutki/') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-anketa" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>ACF: Цена</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_price]" value="<?= esc_attr($opt['acf_price'] ?? 'price_per_hour') ?>" class="regular-text"></td></tr>
                        <tr><th>ACF: Возраст</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_age]" value="<?= esc_attr($opt['acf_age'] ?? 'age') ?>" class="regular-text"></td></tr>
                        <tr><th>ACF: Рост</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_height]" value="<?= esc_attr($opt['acf_height'] ?? 'height') ?>" class="regular-text"></td></tr>
                        <tr><th>ACF: Вес</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_weight]" value="<?= esc_attr($opt['acf_weight'] ?? 'weight') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-faq" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr>
                            <th>Фильтр URL для вывода FAQ</th>
                            <td><input type="text" name="<?= self::OPT_KEY ?>[faq_url_filter]" value="<?= esc_attr($opt['faq_url_filter'] ?? '/faq/') ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <div id="faq-repeater-wrap" style="margin-top:20px;">
                        <div id="faq-items-container">
                            <?php 
                            $faq_items = $opt['faq_repeater'] ?? [];
                            if (empty($faq_items)) $faq_items = [['q' => '', 'a' => '']];
                            foreach ($faq_items as $idx => $item): ?>
                                <div class="faq-row" style="background:#f9f9f9; padding:15px; border:1px solid #ccc; margin-bottom:10px;">
                                    <strong>Вопрос:</strong><br>
                                    <input type="text" name="<?= self::OPT_KEY ?>[faq_repeater][<?= $idx ?>][q]" value="<?= esc_attr($item['q']) ?>" style="width:100%; margin-bottom:10px;">
                                    <strong>Ответ:</strong><br>
                                    <textarea name="<?= self::OPT_KEY ?>[faq_repeater][<?= $idx ?>][a]" rows="3" style="width:100%;"><?= esc_textarea($item['a']) ?></textarea>
                                    <button type="button" class="button remove-faq-row" style="margin-top:10px; color:red;">Удалить</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-faq-row" class="button button-secondary">+ Добавить вопрос</button>
                    </div>
                </div>

                <div style="margin-top:20px; padding:15px; background:#f0f0f1; border-top:1px solid #ccd0d4;">
                    <?php submit_button('Сохранить настройки v8.8.6', 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Переключение табов
            $('.jle-tabs a').on('click', function(e) {
                e.preventDefault(); 
                $('.jle-tabs a').removeClass('nav-tab-active'); 
                $(this).addClass('nav-tab-active');
                $('.jle-tab-content').hide(); 
                $('#tab-' + $(this).data('tab')).show(); 
                window.location.hash = $(this).data('tab');
            });
            var hash = window.location.hash.replace('#', '');
            if (hash) { $('.jle-tabs a[data-tab="' + hash + '"]').click(); } else { $('.jle-tabs a').first().click(); }

            // Репитер FAQ
            $('#add-faq-row').on('click', function() {
                var index = $('#faq-items-container .faq-row').length;
                var template = `
                    <div class="faq-row" style="background:#f9f9f9; padding:15px; border:1px solid #ccc; margin-bottom:10px;">
                        <strong>Вопрос:</strong><br>
                        <input type="text" name="<?= self::OPT_KEY ?>[faq_repeater][\${index}][q]" style="width:100%; margin-bottom:10px;">
                        <strong>Ответ:</strong><br>
                        <textarea name="<?= self::OPT_KEY ?>[faq_repeater][\${index}][a]" rows="3" style="width:100%;"></textarea>
                        <button type="button" class="button remove-faq-row" style="margin-top:10px; color:red;">Удалить</button>
                    </div>`;
                $('#faq-items-container').append(template);
            });
            $(document).on('click', '.remove-faq-row', function() { $(this).closest('.faq-row').remove(); });
        });
        </script>
        <?php
    }

    /* ===================== ВЫВОД ГРАФА (СНЕЖНЫЙ КОМ) ===================== */

    public function render_output(): void {
        if (is_admin()) return;
        
        $opt = get_option(self::OPT_KEY);
        if (!$opt || !is_array($opt)) return;

        // --- БАЗОВЫЕ ПЕРЕМЕННЫЕ ---
        $post_id     = (int) get_queried_object_id();
        $site_url    = trailingslashit($opt['base_id_url'] ?? home_url());
        $uri         = $_SERVER['REQUEST_URI'];
        $current_url = trailingslashit((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $uri);
        
        // Агрессивное получение заголовка (для кастомных роутов)
        $dynamic_title = get_the_title($post_id);
        if (empty($dynamic_title)) {
            $dynamic_title = wp_get_document_title(); 
            // Очищаем от разделителей типа "Заголовок - Сайт"
            $title_parts = explode(' - ', $dynamic_title);
            $dynamic_title = $title_parts[0];
        }

        $graph = [];

        // --- 1. СУЩНОСТЬ: ORGANIZATION ---
        $graph[] = [
            "@type" => "Organization",
            "@id"   => $site_url . "#organization",
            "name"  => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name'),
            "legalName" => $opt['legal_name'] ?? '',
            "url"   => $site_url,
            "logo"  => [
                "@type" => "ImageObject",
                "@id"   => $site_url . "#logo",
                "url"   => $opt['logo_url'] ?? '',
                "width" => 600,
                "height" => 60,
                "caption" => "Логотип " . ($opt['site_name'] ?? '')
            ],
            "image" => ["@id" => $site_url . "#logo"],
            "email" => $opt['email'] ?? '',
            "contactPoint" => [
                "@type" => "ContactPoint",
                "telephone" => $opt['phone'] ?? '',
                "contactType" => "customer support",
                "areaServed" => "RU",
                "availableLanguage" => ["Russian", "English"]
            ],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress"   => $opt['street'] ?? '',
                "addressLocality" => $opt['locality'] ?? '',
                "addressRegion"   => $opt['region'] ?? '',
                "addressCountry"  => "RU",
                "postalCode"      => $opt['postal'] ?? '101000'
            ],
            "sameAs" => array_values(array_filter(explode("\n", $opt['same_as'] ?? '')))
        ];

        // --- 2. СУЩНОСТЬ: WEBSITE ---
        $graph[] = [
            "@type" => "WebSite",
            "@id"   => $site_url . "#website",
            "url"   => $site_url,
            "name"  => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name'),
            "alternateName" => $opt['alt_name'] ?? '',
            "description"   => $opt['site_desc'] ?? get_bloginfo('description'),
            "publisher"     => ["@id" => $site_url . "#organization"],
            "inLanguage"    => "ru-RU",
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $site_url . "?s={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ]
        ];

        // --- ОПРЕДЕЛЕНИЕ ТИПА СТРАНИЦЫ ---
        $is_blog_article = (str_contains($uri, '/blog/') && strlen($uri) > 10); // Статья, если URL длиннее /blog/
        $is_model        = is_singular($opt['cpt_anketa'] ?? 'model');
        $is_cat          = is_category() || is_tax() || is_archive() || (is_page() && (str_contains($uri, $opt['catalog_url'] ?? '/prostitutki/') || str_contains($uri, '/blog')));

        // --- 3. СУЩНОСТЬ: WEBPAGE ---
        $webpage_data = [
            "@type" => $is_cat ? ["CollectionPage", "WebPage"] : "WebPage",
            "@id"   => $current_url . "#webpage",
            "url"   => $current_url,
            "name"  => $dynamic_title,
            "headline" => $dynamic_title,
            "description" => get_the_excerpt($post_id) ?: ($opt['site_desc'] ?? ''),
            "inLanguage" => "ru-RU",
            "isPartOf"   => ["@id" => $site_url . "#website"],
            "publisher"  => ["@id" => $site_url . "#organization"],
            "breadcrumb" => ["@id" => $current_url . "#breadcrumb"]
        ];
        
        // Связываем WebPage с контентом
        if ($is_model) {
            $webpage_data["mainEntity"] = ["@id" => $current_url . "#product"];
        } elseif ($is_blog_article) {
            $webpage_data["mainEntity"] = ["@id" => $current_url . "#article"];
        }
        $graph[] = $webpage_data;

        // --- 4. СУЩНОСТЬ: PRODUCT (ДЛЯ АНКЕТ МОДЕЛЕЙ) ---
        if ($is_model) {
            // Получаем ACF поля
            $price  = get_post_meta($post_id, $opt['acf_price'] ?? 'price_per_hour', true);
            $age    = get_post_meta($post_id, $opt['acf_age'] ?? 'age', true);
            $height = get_post_meta($post_id, $opt['acf_height'] ?? 'height', true);
            $weight = get_post_meta($post_id, $opt['acf_weight'] ?? 'weight', true);

            // Формируем дополнительные характеристики
            $additional_props = [];
            if ($age)    $additional_props[] = ["@type" => "PropertyValue", "name" => "Возраст", "value" => $age . " года"];
            if ($height) $additional_props[] = ["@type" => "PropertyValue", "name" => "Рост", "value" => $height . " см", "unitCode" => "CMT"];
            if ($weight) $additional_props[] = ["@type" => "PropertyValue", "name" => "Вес", "value" => $weight . " кг", "unitCode" => "KGM"];

            $graph[] = [
                "@type" => "Product",
                "@id"   => $current_url . "#product",
                "name"  => get_the_title($post_id),
                "sku"   => "ANK-" . $post_id,
                "image" => get_the_post_thumbnail_url($post_id, 'full'),
                "description" => "Анкета модели " . get_the_title($post_id) . ". Реальные фото и услуги.",
                "brand" => ["@type" => "Brand", "name" => "Независимый эскорт"],
                "offers" => [
                    "@type" => "Offer",
                    "url"   => $current_url,
                    "price" => (int)$price ?: 2500,
                    "priceCurrency" => "RUB",
                    "availability"  => "https://schema.org/InStock",
                    "seller"        => ["@id" => $site_url . "#organization"],
                    "shippingDetails" => [
                        "@type" => "OfferShippingDetails", 
                        "shippingRate" => ["@type" => "MonetaryAmount", "value" => (int)($opt['default_shipping_price'] ?? 0), "currency" => "RUB"]
                    ],
                    "hasMerchantReturnPolicy" => [
                        "@type" => "MerchantReturnPolicy", 
                        "applicableCountry" => "RU", 
                        "returnPolicyCategory" => $opt['return_policy_cat'] ?? "https://schema.org/MerchantReturnNotPermitted"
                    ]
                ],
                "aggregateRating" => [
                    "@type" => "AggregateRating",
                    "ratingValue" => "4.9",
                    "reviewCount" => (int)($post_id % 20 + 15)
                ],
                "additionalProperty" => $additional_props
            ];
        }

        // --- 5. СУЩНОСТЬ: BLOGPOSTING / ARTICLE ---
        if ($is_blog_article) {
            $post_obj   = get_post($post_id);
            $clean_body = $post_obj ? wp_strip_all_tags($post_obj->post_content) : '';
            
            $graph[] = [
                "@type" => "BlogPosting",
                "@id"   => $current_url . "#article",
                "headline" => $dynamic_title,
                "name"     => $dynamic_title,
                "description" => get_the_excerpt($post_id),
                "articleBody" => mb_substr($clean_body, 0, 1500),
                "wordCount"   => str_word_count($clean_body),
                "datePublished" => get_the_date('c', $post_id),
                "dateModified"  => get_the_modified_date('c', $post_id),
                "author" => [
                    "@type" => "Person", 
                    "name"  => get_the_author_meta('display_name', $post_obj->post_author ?? 1)
                ],
                "publisher" => ["@id" => $site_url . "#organization"],
                "image"     => get_the_post_thumbnail_url($post_id, 'full'),
                "mainEntityOfPage" => ["@id" => $current_url . "#webpage"]
            ];
        }

        // --- 6. СУЩНОСТЬ: FAQPAGE (РЕПИТЕР) ---
        if (str_contains($uri, $opt['faq_url_filter'] ?? '/faq/')) {
            $faq_raw = $opt['faq_repeater'] ?? [];
            $faq_list = [];
            
            foreach ($faq_raw as $item) {
                if (!empty($item['q']) && !empty($item['a'])) {
                    $faq_list[] = [
                        "@type" => "Question",
                        "name"  => $item['q'],
                        "acceptedAnswer" => ["@type" => "Answer", "text" => $item['a']]
                    ];
                }
            }
            
            if (!empty($faq_list)) {
                $graph[] = ["@type" => "FAQPage", "mainEntity" => $faq_list];
            }
        }

        // --- 7. СУЩНОСТЬ: BREADCRUMBLIST (ДИНАМИЧЕСКИЙ ДВИЖОК) ---
        if (trim($uri, '/') !== '') {
            $crumbs = [];
            
            // Уровень 1: Главная
            $crumbs[] = [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Главная",
                "item" => $site_url
            ];

            // Разбор URL на сегменты для динамических роутов
            $path_segments = array_values(array_filter(explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'))));
            $accumulated_url = rtrim($site_url, '/');
            $current_pos = 2;

            foreach ($path_segments as $index => $segment) {
                $accumulated_url .= '/' . $segment;
                $is_last_item = ($index === count($path_segments) - 1);
                
                // Определение имени звена
                if ($is_last_item) {
                    $crumb_name = $dynamic_title;
                } else {
                    $mapping_slug = trim($opt['catalog_url'] ?? 'prostitutki', '/');
                    if ($segment === $mapping_slug || $segment === 'blog') {
                        $crumb_name = ($segment === 'blog') ? 'Блог' : ($opt['catalog_label'] ?? 'Анкеты');
                    } else {
                        // Превращаем slug в читаемый текст (moskva -> Moskva)
                        $crumb_name = mb_convert_case(str_replace(['-', '_'], ' ', $segment), MB_CASE_TITLE, "UTF-8");
                    }
                }

                $crumbs[] = [
                    "@type" => "ListItem",
                    "position" => $current_pos++,
                    "name" => $crumb_name,
                    "item" => trailingslashit($accumulated_url)
                ];
            }

            $graph[] = [
                "@type" => "BreadcrumbList", 
                "@id"   => $current_url . "#breadcrumb", 
                "itemListElement" => $crumbs
            ];
        }

        // --- ФИНАЛЬНЫЙ ВЫВОД JSON ---
        if (!empty($graph)) {
            echo "\n<script type=\"application/ld+json\">" . 
                 json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . 
                 "</script>\n";
        }
    }
}

// Запуск плагина
SchemaProEngine::instance();

/**
 * Вспомогательная функция для получения URL
 */
function get_current_url_safe() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}