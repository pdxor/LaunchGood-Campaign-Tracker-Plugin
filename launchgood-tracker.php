<?php
/**
 * Plugin Name: LaunchGood Campaign Tracker
 * Description: Track and display LaunchGood campaign progress
 * Version: 1.0.2
 * Author: Your Name
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

class LaunchGood_Tracker {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function init() {
        add_shortcode('launchgood_campaign', array($this, 'campaign_shortcode'));
    }
    
    public function register_widgets() {
        register_widget('LaunchGood_Widget');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('launchgood-tracker', plugins_url('css/style.css', __FILE__));
    }
    
    public function find_element_by_multiple_selectors($xpath, $selectors) {
        foreach ($selectors as $selector) {
            $element = $xpath->evaluate($selector);
            if ($element && !empty($element)) {
                return $element;
            }
        }
        return null;
    }

    public function get_campaign_data($url, $goal = 25000) {
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ));
        
        if (is_wp_error($response)) {
            error_log('LaunchGood Tracker: Error fetching URL - ' . $response->get_error_message());
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            error_log('LaunchGood Tracker: Empty response from URL');
            return false;
        }
        
        // Create a DOMDocument instance
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        @$doc->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($doc);

        // Updated selectors based on the new HTML structure
        $amount_selectors = array(
            'string(//div[contains(@class, "text-2xl") or contains(@class, "text-5xl")]//span[2])',
            'string(//div[contains(@class, "text-rebuild-primary")]//span[2])',
            'string(//*[contains(text(), "$")]/following-sibling::span[1])'
        );

        $supporters_selectors = array(
            'string(//span[contains(@class, "text-rebuild-dark")])',
            'string(//div[contains(@class, "text-rebuild-gray-500")]/span[1])',
            'string(//div[contains(@class, "stats")]//span[1])'
        );

        $days_left_selectors = array(
            'string(//span[contains(@class, "text-black-400")])',
            'string(//div[contains(@class, "text-rebuild-gray-500")]//span[contains(@class, "text-black-400")])',
            'string(//div[contains(@class, "stats")]//span[contains(text(), "days")])'
        );

        // Extract data using multiple selectors
        $amount_raised = $this->extract_number($this->find_element_by_multiple_selectors($xpath, $amount_selectors));
        $supporters = $this->extract_number($this->find_element_by_multiple_selectors($xpath, $supporters_selectors));
        $days_left = $this->extract_number($this->find_element_by_multiple_selectors($xpath, $days_left_selectors));

        // More detailed debug logging
        error_log("LaunchGood Debug - Raw values:");
        error_log("Amount: " . $amount_raised);
        error_log("Goal: " . $goal);
        error_log("Supporters: " . $supporters);
        error_log("Days left: " . $days_left);
        
        // Validate the amount raised
        if ($amount_raised === 0) {
            error_log('LaunchGood Tracker: Failed to extract amount raised');
            return false;
        }
        
        // Calculate percentage
        $percentage = 0;
        if ($goal > 0) {
            $percentage = min(($amount_raised / $goal) * 100, 100); // Cap at 100%
        }
        
        return array(
            'amount_raised' => $amount_raised,
            'goal' => $goal,
            'supporters' => $supporters ?: 0,
            'days_left' => $days_left ?: 0,
            'percentage' => round($percentage, 1)
        );
    }
    
    public function extract_number($string) {
        if (empty($string)) {
            return 0;
        }
        
        // Remove any currency symbols and commas
        $clean = preg_replace('/[^0-9.]/', '', $string);
        
        // Check if we have a valid number
        if (is_numeric($clean)) {
            return floatval($clean);
        }
        
        return 0;
    }
    
    public function campaign_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'refresh' => '3600', // Refresh every hour by default
            'goal' => '25000' // Default goal amount
        ), $atts);
        
        if (empty($atts['url'])) {
            return '<p class="launchgood-error">Please provide a LaunchGood campaign URL</p>';
        }
        
        // Get campaign data with the goal parameter
        $data = $this->get_campaign_data($atts['url'], floatval($atts['goal']));
        
        if (!$data) {
            return '<p class="launchgood-error">Unable to fetch campaign data. Please verify the URL and try again.</p>';
        }
        
        ob_start();
        ?>
        <div class="launchgood-campaign" data-refresh="<?php echo esc_attr($atts['refresh']); ?>" data-url="<?php echo esc_url($atts['url']); ?>">
            <div class="campaign-progress">
                <div class="amount-raised">$<?php echo number_format($data['amount_raised'], 0); ?></div>
                <div class="goal">raised of $<?php echo number_format($data['goal'], 0); ?> goal</div>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($data['percentage']); ?>%"></div>
                </div>
                <div class="stats">
                    <span class="supporters"><?php echo esc_html($data['supporters']); ?> supporters</span>
                    <span class="days-left"><?php echo esc_html($data['days_left']); ?> days left</span>
                </div>
            </div>
        </div>
        <style>
            .progress-bar {
                background-color: #E5E5E5;
                height: 4px;
                border-radius: 2px;
                margin: 10px 0;
                overflow: hidden;
            }
            .progress {
                background-color: #4AA567;
                height: 100%;
                transition: width 0.3s ease;
            }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Rest of the code remains the same...

class LaunchGood_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'launchgood_widget',
            'LaunchGood Campaign Tracker',
            array('description' => 'Display LaunchGood campaign progress')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        echo do_shortcode('[launchgood_campaign url="' . esc_attr($instance['url']) . '" refresh="' . esc_attr($instance['refresh']) . '"]');
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $url = !empty($instance['url']) ? $instance['url'] : '';
        $refresh = !empty($instance['refresh']) ? $instance['refresh'] : '3600';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('url')); ?>">Campaign URL:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('url')); ?>" name="<?php echo esc_attr($this->get_field_name('url')); ?>" type="url" value="<?php echo esc_attr($url); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('refresh')); ?>">Refresh Interval (seconds):</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('refresh')); ?>" name="<?php echo esc_attr($this->get_field_name('refresh')); ?>" type="number" value="<?php echo esc_attr($refresh); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['url'] = (!empty($new_instance['url'])) ? strip_tags($new_instance['url']) : '';
        $instance['refresh'] = (!empty($new_instance['refresh'])) ? absint($new_instance['refresh']) : 3600;
        return $instance;
    }
}

// Initialize the plugin
LaunchGood_Tracker::get_instance();

// Add AJAX endpoint for refreshing campaign data
add_action('wp_ajax_refresh_campaign', 'refresh_campaign_data');
add_action('wp_ajax_nopriv_refresh_campaign', 'refresh_campaign_data');

function refresh_campaign_data() {
    if (!isset($_POST['url'])) {
        wp_send_json_error('No URL provided');
    }
    
    $tracker = LaunchGood_Tracker::get_instance();
    $data = $tracker->get_campaign_data($_POST['url']);
    
    if (!$data) {
        wp_send_json_error('Unable to fetch campaign data');
    }
    
    wp_send_json_success($data);
}