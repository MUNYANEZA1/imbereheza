<?php
// Language translation helper
class LanguageManager {
    private static $instance = null;
    private $lang = 'en';
    private $translations = [];

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Load language from session or default
        $this->lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
        $this->loadTranslations();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadTranslations() {
        $file = __DIR__ . '/lang/' . $this->lang . '.json';
        if (file_exists($file)) {
            $this->translations = json_decode(file_get_contents($file), true) ?? [];
        } else {
            // Fallback to English
            $this->lang = 'en';
            $file = __DIR__ . '/lang/en.json';
            $this->translations = json_decode(file_get_contents($file), true) ?? [];
        }
    }

    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default ?? $key;
            }
        }
        
        return $value;
    }

    public function setLanguage($lang) {
        if (in_array($lang, ['en', 'kin'])) {
            $_SESSION['language'] = $lang;
            $this->lang = $lang;
            $this->loadTranslations();
        }
    }

    public function getLanguage() {
        return $this->lang;
    }

    public function getAvailableLanguages() {
        return [
            'en' => 'English',
            'kin' => 'Kinyarwanda'
        ];
    }
}

// Global function for easy access
function __($key, $default = null) {
    return LanguageManager::getInstance()->get($key, $default);
}

// Short alias t() for translate
function t($key, $default = null) {
    return __($key, $default);
}
?>
