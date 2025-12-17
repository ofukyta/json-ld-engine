<?php
/**
 * Plugin Name: json-ld-engine
 * Description: Автоматизированная система управления микроразметкой для Sage 10 / Bedrock. Поддержка 8 типов сущностей и графовой структуры.
 * Version: 1.0.0
 * Author: Senior Dev
 */

namespace JsonLdEngine;

if (!defined('ABSPATH')) exit;

/**
 * Основной класс плагина
 */
final class JsonLdEngine {
    private $options;

    public function __construct() {
        // Создаем меню в админке
        add_action('admin_menu', [$this, 'create_menu']);
        // Регистрируем настройки
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Создание пункта меню "Micro-marking"
     */
    public function create_menu() {
        add_menu_page(
            'Micro-marking', 
            'Micro-marking', 
            'manage_options', 
            'jle-settings', 
            [$this, 'render_admin'], 
            'dashicons-rest-api', 
            90
        );
    }

    /**
     * Регистрация группы настроек в БД
     */
    public function register_settings() {
        register_setting('jle_settings_group', 'jle_options');
    }

    /**
     * Интерфейс админ-панели (Global Data + Template Manager)
     */
    public function render_admin() {
        $this->options = get_option('jle_options');
        ?>
        <div class="wrap">
            <h1>json-ld-engine — Управление микроразметкой</h1>
            <form method="post" action="options.php">
                <?php settings_fields('jle_settings_group'); ?>
                
                <h2>1. Глобальные данные (Global Data)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Название организации</th>
                        <td><input type="text" name="jle_options[org_name]" value="<?= esc_attr($this->options['org_name'] ?? '') ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Город</th>
                        <td><input type="text" name="jle_options[city]" value="<?= esc_attr($this->options['city'] ?? '') ?>" class="regular-text"></td>
                    </tr>
                </table>

                <hr>

                <h2>2. Менеджер шаблонов (Template Manager)</h2>
                <p>Вставьте JSON для каждого типа страницы. Используйте плейсхолдеры типа {title} или {url}.</p>

                <?php
                $types = [
                    'website' => 'WebSite', 'org' => 'Organization', 'breadcrumbs' => 'Breadcrumbs',
                    'category' => 'CollectionPage', 'product' => 'Product', 'blog' => 'BlogPosting',
                    'faq' => 'FAQPage', 'about' => 'AboutPage'
                ];
                foreach ($types as $key => $label): ?>
                    <h3><?= $label ?></h3>
                    <textarea name="jle_options[tpl_<?= $key ?>]" rows="8" class="large-text code" style="background:#1d2327; color:#00ff00; font-family:monospace;"><?= esc_textarea($this->options['tpl_' . $key] ?? '') ?></textarea>
                <?php endforeach; ?>

                <?php submit_button('Сохранить настройки'); ?>
            </form>
        </div>
        <?php
    }
}

// Запуск плагина
new JsonLdEngine();