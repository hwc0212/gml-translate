<?php
/**
 * Basic functionality tests
 *
 * @package GML_Translate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Basic tests class
 */
class GML_Basic_Tests {
    
    /**
     * Run all tests
     */
    public static function run_all() {
        echo "<h2>GML Basic Tests</h2>";
        
        self::test_database_tables();
        self::test_autoloader();
        self::test_hash_deduplication();
        self::test_brand_protection();
        self::test_utf8_handling();
        
        echo "<p><strong>All tests completed!</strong></p>";
    }
    
    /**
     * Test database tables
     */
    private static function test_database_tables() {
        global $wpdb;
        
        echo "<h3>1. Database Tables Test</h3>";
        
        $tables = [
            'gml_index',
            'gml_queue',
            'gml_plans',
            'gml_plan_items',
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($exists) {
                echo "<p style='color: green;'>✓ Table $table_name exists</p>";
            } else {
                echo "<p style='color: red;'>✗ Table $table_name does not exist</p>";
            }
        }
    }
    
    /**
     * Test autoloader
     */
    private static function test_autoloader() {
        echo "<h3>2. Autoloader Test</h3>";
        
        $classes = [
            'GML_Output_Buffer',
            'GML_HTML_Parser',
            'GML_Translator',
            'GML_Gemini_API',
            'GML_SEO_Router',
            'GML_Queue_Processor',
            'GML_Language_Switcher',
        ];
        
        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "<p style='color: green;'>✓ Class $class loaded</p>";
            } else {
                echo "<p style='color: red;'>✗ Class $class not found</p>";
            }
        }
    }
    
    /**
     * Test hash deduplication
     */
    private static function test_hash_deduplication() {
        echo "<h3>3. Hash Deduplication Test</h3>";
        
        $text1 = "Home";
        $text2 = "Home";
        $text3 = "About";
        
        $hash1 = md5($text1);
        $hash2 = md5($text2);
        $hash3 = md5($text3);
        
        if ($hash1 === $hash2) {
            echo "<p style='color: green;'>✓ Same text produces same hash</p>";
            echo "<p>Text: '$text1' → Hash: $hash1</p>";
        } else {
            echo "<p style='color: red;'>✗ Hash mismatch</p>";
        }
        
        if ($hash1 !== $hash3) {
            echo "<p style='color: green;'>✓ Different text produces different hash</p>";
            echo "<p>Text: '$text3' → Hash: $hash3</p>";
        } else {
            echo "<p style='color: red;'>✗ Hash collision</p>";
        }
    }
    
    /**
     * Test brand protection
     */
    private static function test_brand_protection() {
        echo "<h3>4. Brand Protection Test</h3>";
        
        if (!class_exists('GML_HTML_Parser')) {
            echo "<p style='color: red;'>✗ GML_HTML_Parser class not found</p>";
            return;
        }
        
        $parser = new GML_HTML_Parser();
        
        $original = "Welcome to GML for WordPress";
        $translated_good = "欢迎使用 GML for WordPress";
        $translated_bad = "欢迎使用 GML 为 WordPress";
        
        $result_good = $parser->verify_brand_protection($original, $translated_good);
        $result_bad = $parser->verify_brand_protection($original, $translated_bad);
        
        if ($result_good) {
            echo "<p style='color: green;'>✓ Brand terms preserved correctly</p>";
        } else {
            echo "<p style='color: red;'>✗ Brand protection failed (false negative)</p>";
        }
        
        if (!$result_bad) {
            echo "<p style='color: green;'>✓ Brand term modification detected</p>";
        } else {
            echo "<p style='color: red;'>✗ Brand protection failed (false positive)</p>";
        }
    }
    
    /**
     * Test UTF-8 handling
     */
    private static function test_utf8_handling() {
        echo "<h3>5. UTF-8 Handling Test</h3>";
        
        $texts = [
            '中文测试',
            '日本語テスト',
            '한국어 테스트',
            '🎉 Emoji test',
        ];
        
        foreach ($texts as $text) {
            $hash = md5($text);
            $length = mb_strlen($text);
            
            echo "<p style='color: green;'>✓ Text: $text (Length: $length, Hash: " . substr($hash, 0, 8) . "...)</p>";
        }
    }
}

// Run tests if accessed directly
if (isset($_GET['gml_run_tests']) && current_user_can('manage_options')) {
    GML_Basic_Tests::run_all();
}
