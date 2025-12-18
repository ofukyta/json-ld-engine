<?php
/**
 * Plugin Name: JSON-LD Engine Pro (Full Graph Version)
 * Description: Профессиональный генератор микроразметки. WebSite + Organization + WebPage + Product + CollectionPage + ItemList + Article + FAQ.
 * Version: 8.7.0
 * Author: Senior Backend Developer
 * Requires PHP: 8.1
 */

namespace JsonLdSchemaPro;

if (!defined('ABSPATH')) exit;

class SchemaProEngine {
    private static ?SchemaProEngine $instance = null;
    public const OPT_KEY = 'site_schema_options';

    private array $tabs = [
        'global'    => 'Сайт и Организация',
        'contact'   => 'Контакты и Гео',
        'mapping'   => 'Назначение страниц',
        'anketa'    => 'Поля Анкет (ACF)',
        'faq'       => 'Конструктор FAQ',
        'blog'      => 'Блог и Статьи',
        'advanced'  => 'Дополнительно'
    ];

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'render_output'], 1);
    }

    public function add_menu(): void {
        add_options_page('JSON-LD Schema', 'Micro-marking', 'manage_options', self::OPT_KEY, [$this, 'render_admin']);
    }

    public function register_settings(): void {
        register_setting(self::OPT_KEY, self::OPT_KEY);
    }

    /* ===================== АДМИНКА (БЕЗ ИЗМЕНЕНИЙ) ===================== */

    public function render_admin(): void {
        $opt = get_option(self::OPT_KEY, []);
        ?>
        <div class="wrap">
            <h1>JSON-LD Schema Pro <span style="font-size: 12px; color: #666;">v8.7</span></h1>
            
            <h2 class="nav-tab-wrapper jle-tabs">
                <?php foreach ($this->tabs as $id => $label): ?>
                    <a href="#<?= $id ?>" class="nav-tab" data-tab="<?= $id ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none; max-width: 1000px;">
                <?php settings_fields(self::OPT_KEY); ?>
                
                <div id="tab-global" class="jle-tab-content">
                    <table class="form-table">
                        <tr><th>Название сайта</th><td><input type="text" name="<?= self::OPT_KEY ?>[site_name]" value="<?= esc_attr($opt['site_name'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Alt Название</th><td><input type="text" name="<?= self::OPT_KEY ?>[alt_name]" value="<?= esc_attr($opt['alt_name'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Юр. Название (Legal)</th><td><input type="text" name="<?= self::OPT_KEY ?>[legal_name]" value="<?= esc_attr($opt['legal_name'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Описание сайта</th><td><textarea name="<?= self::OPT_KEY ?>[site_desc]" rows="3" class="regular-text"><?= esc_textarea($opt['site_desc'] ?? '') ?></textarea></td></tr>
                        <tr><th>Logo URL</th><td><input type="text" name="<?= self::OPT_KEY ?>[logo_url]" value="<?= esc_attr($opt['logo_url'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Размеры Logo (WxH)</th><td>
                            W: <input type="number" name="<?= self::OPT_KEY ?>[logo_w]" value="<?= esc_attr($opt['logo_w'] ?? '600') ?>" style="width:70px;"> 
                            H: <input type="number" name="<?= self::OPT_KEY ?>[logo_h]" value="<?= esc_attr($opt['logo_h'] ?? '60') ?>" style="width:70px;">
                        </td></tr>
                        <tr><th>isFamilyFriendly</th><td><input type="checkbox" name="<?= self::OPT_KEY ?>[is_family_friendly]" value="1" <?php checked(1, $opt['is_family_friendly'] ?? 0); ?>></td></tr>
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
                        <tr><th>Индекс</th><td><input type="text" name="<?= self::OPT_KEY ?>[postal]" value="<?= esc_attr($opt['postal'] ?? '') ?>" class="regular-text"></td></tr>
                        <tr><th>Языки</th><td><input type="text" name="<?= self::OPT_KEY ?>[langs]" value="<?= esc_attr($opt['langs'] ?? 'Russian, English') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-mapping" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>Тип записи для Анкет</th><td><input type="text" name="<?= self::OPT_KEY ?>[cpt_anketa]" value="<?= esc_attr($opt['cpt_anketa'] ?? 'model') ?>" class="regular-text"></td></tr>
                        <tr><th>Текст звена "Каталог"</th><td><input type="text" name="<?= self::OPT_KEY ?>[catalog_label]" value="<?= esc_attr($opt['catalog_label'] ?? 'Анкеты') ?>" class="regular-text"></td></tr>
                        <tr><th>URL звена "Каталог"</th><td><input type="text" name="<?= self::OPT_KEY ?>[catalog_url]" value="<?= esc_attr($opt['catalog_url'] ?? '/ankety/') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-anketa" class="jle-tab-content" style="display:none;">
                    <table class="form-table">
                        <tr><th>Поле "Цена"</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_price]" value="<?= esc_attr($opt['acf_price'] ?? 'price_per_hour') ?>" class="regular-text"></td></tr>
                        <tr><th>Поле "Возраст"</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_age]" value="<?= esc_attr($opt['acf_age'] ?? 'age') ?>" class="regular-text"></td></tr>
                        <tr><th>Поле "Рост"</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_height]" value="<?= esc_attr($opt['acf_height'] ?? 'height') ?>" class="regular-text"></td></tr>
                        <tr><th>Поле "Вес"</th><td><input type="text" name="<?= self::OPT_KEY ?>[acf_weight]" value="<?= esc_attr($opt['acf_weight'] ?? 'weight') ?>" class="regular-text"></td></tr>
                    </table>
                </div>

                <div id="tab-faq" class="jle-tab-content" style="display:none;">
                   <h3>FAQ для страниц (Мульти-вопросы)</h3>
                   <table class="form-table">
                       <?php for($i=1; $i<=3; $i++): ?>
                       <tr style="border-top: 1px solid #ccc;"><th>Вопрос <?= $i ?></th><td><input type="text" name="<?= self::OPT_KEY ?>[q<?= $i ?>]" value="<?= esc_attr($opt['q'.$i] ?? '') ?>" class="regular-text"></td></tr>
                       <tr><th>Ответ <?= $i ?></th><td><textarea name="<?= self::OPT_KEY ?>[a<?= $i ?>]" rows="2" class="regular-text"><?= esc_textarea($opt['a'.$i] ?? '') ?></textarea></td></tr>
                       <?php endfor; ?>
                   </table>
                </div>

                <div id="tab-blog" class="jle-tab-content" style="display:none;">
                    <p>Автоматическая разметка Article для записей блога (поддержка blog_article).</p>
                </div>

                <div style="margin-top:20px; padding:15px; background:#f0f0f1; border-top:1px solid #ccd0d4;">
                    <?php submit_button('Сохранить ВСЕ настройки', 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.jle-tabs a').on('click', function(e) {
                e.preventDefault(); 
                $('.jle-tabs a').removeClass('nav-tab-active'); 
                $(this).addClass('nav-tab-active');
                $('.jle-tab-content').hide(); 
                $('#tab-' + $(this).data('tab')).show(); 
                window.location.hash = $(this).data('tab');
            });
            var hash = window.location.hash.replace('#', '');
            if (hash && $('.jle-tabs a[data-tab="' + hash + '"]').length) { 
                $('.jle-tabs a[data-tab="' + hash + '"]').click(); 
            } else { 
                $('.jle-tabs a').first().click(); 
            }
        });
        </script>
        <?php
    }

    /* ===================== ВЫВОД (ПОЛНЫЙ ГРАФ БЕЗ УДАЛЕНИЙ) ===================== */

    public function render_output(): void {
        if (is_admin()) return;
        $opt = get_option(self::OPT_KEY);
        if (!$opt || !is_array($opt)) return;

        $id = (int) get_queried_object_id();
        $site_url = trailingslashit(home_url());
        $current_url = trailingslashit(get_current_url_safe());
        $uri = $_SERVER['REQUEST_URI'];
        if (is_archive() || is_tax() || is_category()) { $current_url = trailingslashit(get_current_url_safe()); }

        $graph = [];

        // 1. Organization
        $graph[] = [
            "@type" => "Organization",
            "@id"   => $site_url . "#organization",
            "name"  => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name'),
            "legalName" => $opt['legal_name'] ?? '',
            "url"   => $site_url,
            "logo"  => [
                "@type" => "ImageObject",
                "@id"   => $site_url . "#logo",
                "url"   => !empty($opt['logo_url']) ? esc_url($opt['logo_url']) : '',
                "width" => (int)($opt['logo_w'] ?? 600),
                "height" => (int)($opt['logo_h'] ?? 60)
            ],
            "image" => ["@id" => $site_url . "#logo"],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress"   => $opt['street'] ?? '',
                "addressLocality" => $opt['locality'] ?? '',
                "addressRegion"   => $opt['region'] ?? '',
                "postalCode"      => $opt['postal'] ?? '',
                "addressCountry"  => "RU"
            ],
            "contactPoint" => [
                "@type" => "ContactPoint",
                "telephone" => $opt['phone'] ?? '',
                "contactType" => "customer support",
                "areaServed" => "RU",
                "availableLanguage" => !empty($opt['langs']) ? array_map('trim', explode(',', $opt['langs'])) : ["Russian", "English"]
            ],
            "sameAs" => array_values(array_filter(explode("\n", $opt['same_as'] ?? '')))
        ];

        // 2. WebSite
        if (is_front_page()) {
            $graph[] = [
                "@type" => "WebSite",
                "@id"   => $site_url . "#website",
                "url"   => $site_url,
                "name"  => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name'),
                "alternateName" => $opt['alt_name'] ?? '',
                "description"   => $opt['site_desc'] ?? '',
                "publisher"     => ["@id" => $site_url . "#organization"],
                "inLanguage"    => "ru-RU",
                "isFamilyFriendly" => !empty($opt['is_family_friendly']) ? "true" : "false",
                "potentialAction"  => [
                    "@type"  => "SearchAction",
                    "target" => ["@type" => "EntryPoint", "urlTemplate" => $site_url . "?s={search_term_string}"],
                    "query-input" => "required name=search_term_string"
                ]
            ];
        } else {
            $graph[] = ["@type" => "WebSite", "@id" => $site_url . "#website", "url" => $site_url, "name" => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name')];
        }

        // --- ЛОГИКА ТИПОВ СТРАНИЦ ---
        $is_blog_index = str_contains($uri, '/blog/') && !is_singular('blog_article');
        $is_cat = is_category() || is_tax() || is_archive() || (is_page() && (str_contains($uri, $opt['catalog_url'] ?? '/ankety/') || str_contains($uri, '/category/') || str_contains($uri, '/proverennye/') || str_contains($uri, '/populyarnye/') || $is_blog_index));
        $is_model = is_singular($opt['cpt_anketa'] ?? 'model');
        $is_post = is_singular(['post', 'blog_article']);

        // 3. WebPage + CollectionPage
        $page_node = [
            "@type" => $is_cat ? ["CollectionPage", "WebPage"] : "WebPage",
            "@id"   => $current_url . "#webpage",
            "url"   => $current_url,
            "name"  => is_front_page() ? "Главная" : (is_archive() ? get_the_archive_title() : get_the_title($id)),
            "isPartOf"  => ["@id" => $site_url . "#website"],
            "publisher" => ["@id" => $site_url . "#organization"],
            "inLanguage" => "ru-RU"
        ];
        if ($is_cat) {
            $page_node["headline"] = is_archive() ? get_the_archive_title() : get_the_title($id);
            $page_node["description"] = is_archive() ? get_the_archive_description() : ($opt['site_desc'] ?? '');
            $page_node["mainEntity"] = ["@id" => $current_url . "#itemlist"];
        }
        $graph[] = $page_node;

        // 4. ItemList (Авто-сбор анкет ИЛИ статей blog_article)
        if ($is_cat) {
            $items = []; $pos = 1; global $wp_query;
            $type = $is_blog_index ? 'blog_article' : ($opt['cpt_anketa'] ?? 'model');
            $posts_to_list = (is_archive() || is_tax() || is_home()) ? $wp_query->posts : get_posts(['post_type' => $type, 'posts_per_page' => 50]);
            if ($posts_to_list) {
                foreach ($posts_to_list as $post) {
                    if ($type === 'blog_article') {
                        $items[] = ["@type" => "ListItem", "position" => $pos++, "url" => get_permalink($post->ID), "name" => get_the_title($post->ID)];
                    } else {
                        $p_price = get_post_meta($post->ID, $opt['acf_price'] ?? 'price_per_hour', true);
                        $items[] = [
                            "@type" => "ListItem",
                            "position" => $pos++,
                            "url" => get_permalink($post->ID),
                            "item" => [
                                "@type" => "Product",
                                "name"  => get_the_title($post->ID),
                                "image" => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                                "description" => "Анкета модели " . get_the_title($post->ID),
                                "offers" => [
                                    "@type" => "Offer",
                                    "price" => (int)$p_price ?: "2500",
                                    "priceCurrency" => "RUB",
                                    "availability" => "https://schema.org/InStock",
                                    "shippingDetails" => ["@type" => "OfferShippingDetails", "shippingRate" => ["@type" => "MonetaryAmount", "value" => 0, "currency" => "RUB"]],
                                    "hasMerchantReturnPolicy" => ["@type" => "MerchantReturnPolicy", "applicableCountry" => "RU", "returnPolicyCategory" => "https://schema.org/MerchantReturnNotPermitted"]
                                ]
                            ]
                        ];
                    }
                }
                $graph[] = ["@type" => "ItemList", "@id" => $current_url . "#itemlist", "name" => "Список", "numberOfItems" => count($items), "itemListOrder" => "https://schema.org/ItemListOrderDescending", "itemListElement" => $items];
            }
        }

        // 5. Product (Анкеты)
        if ($is_model) {
            $price = get_post_meta($id, $opt['acf_price'] ?? 'price_per_hour', true); $age = get_post_meta($id, $opt['acf_age'] ?? 'age', true); $h = get_post_meta($id, $opt['acf_height'] ?? 'height', true); $w = get_post_meta($id, $opt['acf_weight'] ?? 'weight', true);
            $props = []; if ($age) $props[] = ["@type" => "PropertyValue", "name" => "Возраст", "value" => $age]; if ($h) $props[] = ["@type" => "PropertyValue", "name" => "Рост", "value" => $h]; if ($w) $props[] = ["@type" => "PropertyValue", "name" => "Вес", "value" => $w];
            $graph[] = ["@type" => "Product", "@id" => $current_url . "#product", "name" => get_the_title($id), "image" => [get_the_post_thumbnail_url($id, 'full')], "description" => "Анкета модели " . get_the_title($id) . ". Фото 100% реальные.", "sku" => "model-" . $id, "brand" => ["@type" => "Brand", "name" => !empty($opt['site_name']) ? $opt['site_name'] : get_bloginfo('name')], "mainEntityOfPage" => ["@id" => $current_url . "#webpage"], "offers" => ["@type" => "Offer", "url" => $current_url, "price" => (int)$price ?: "2500", "priceCurrency" => "RUB", "priceValidUntil" => date('Y-12-31'), "availability" => "https://schema.org/InStock", "shippingDetails" => ["@type" => "OfferShippingDetails", "shippingRate" => ["@type" => "MonetaryAmount", "value" => 0, "currency" => "RUB"], "shippingDestination" => [["@type" => "DefinedRegion", "addressCountry" => "RU"]]], "hasMerchantReturnPolicy" => ["@type" => "MerchantReturnPolicy", "applicableCountry" => "RU", "returnPolicyCategory" => "https://schema.org/MerchantReturnNotPermitted"], "seller" => ["@id" => $site_url . "#organization"]], "aggregateRating" => ["@type" => "AggregateRating", "ratingValue" => "4.9", "reviewCount" => (int)($id % 50 + 15)], "additionalProperty" => $props];
        }

        // 6. Article (Блог)
        if ($is_post) {
            $graph[] = ["@type" => "Article", "@id" => $current_url . "#article", "headline" => get_the_title($id), "datePublished" => get_the_date('c', $id), "dateModified" => get_the_modified_date('c', $id), "author" => ["@id" => $site_url . "#organization"], "publisher" => ["@id" => $site_url . "#organization"], "image" => [get_the_post_thumbnail_url($id, 'full')], "mainEntityOfPage" => ["@id" => $current_url . "#webpage"]];
        }

        // 7. FAQPage
        $faq_items = []; for($i=1; $i<=3; $i++) { if (!empty($opt['q'.$i])) $faq_items[] = ["@type" => "Question", "name" => $opt['q'.$i], "acceptedAnswer" => ["@type" => "Answer", "text" => $opt['a'.$i]]]; }
        if (!empty($faq_items) && !$is_blog_index) { $graph[] = ["@type" => "FAQPage", "mainEntity" => $faq_items]; }

        // 8. Breadcrumbs (3 УРОВНЯ)
        if (!is_front_page()) {
            $crumbs = [["@type" => "ListItem", "position" => 1, "name" => "Главная", "item" => $site_url]];
            $pos = 2;
            if ($is_model || is_tax() || is_category() || (is_page() && str_contains($uri, '/ankety/'))) {
                $crumbs[] = ["@type" => "ListItem", "position" => $pos++, "name" => !empty($opt['catalog_label']) ? $opt['catalog_label'] : 'Анкеты', "item" => home_url($opt['catalog_url'] ?? '/ankety/')];
            }
            $crumbs[] = ["@type" => "ListItem", "position" => $pos, "name" => (is_archive() ? get_the_archive_title() : get_the_title($id)) ?: "Блог", "item" => $current_url];
            $graph[] = ["@type" => "BreadcrumbList", "@id" => $current_url . "#breadcrumb", "itemListElement" => $crumbs];
        }

        if (!empty($graph)) { echo "\n<script type=\"application/ld+json\">" . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n"; }
    }
}
SchemaProEngine::instance();
function get_current_url_safe() { return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; }