<?php

/*
  Plugin Name: WP-Phabricator-login
  Plugin URI: http://github.com/tomaluca95/wp-phabricator-login
  Description: A WordPress plugin that allows users to login or register by authenticating with Phabricator login
  Version: 0.0.1
  Author: Luca Toma
 */

session_start();

include __DIR__ . '/OAuth2-Phabricator/PhabricatorProvider.php';

Class WP_phab {

    private $phab;

    const PLUGIN_VERSION = "0.0.1";

    // singleton stuff
    protected static $instance = NULL;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new WP_phab();
        }
        return self::$instance;
    }

    private $settings = array(
        'wpphab_auto_register' => 1,
        'wpphab_phabricator_url' => '',
        'wpphab_api_id' => '',
        'wpphab_api_secret' => '',
        'wpphab_label' => '',
        'wpphab_new_user_role' => 'contributor',
        'wpphab_delete_settings_on_uninstall' => 0,
    );

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array($this, 'uninstall'));
        add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));
        add_filter('login_message', array($this, 'login_message'));
        add_action('login_head', array($this, 'login_head'));
        add_action('login_init', array($this, 'login_init'));
        add_action('admin_menu', array($this, 'add_glue_menu_option'));

        $this->phab = new PhabricatorProvider(get_option("wpphab_phabricator_url"), [
            'clientId' => get_option('wpphab_api_id'),
            'clientSecret' => get_option('wpphab_api_secret'),
            'redirectUri' => get_bloginfo('url') . "/wp-login.php",
        ]);
    }

    function add_glue_menu_option() {
        add_options_page('Phabricator login', 'Phabricator login', 'read', 'phabricator-login', array($this, 'menu_page'));
    }

    public function menu_page() {
        if (filter_input(INPUT_POST, 'phab_cmd') == "unlink") {
            delete_user_meta(get_current_user_id(), 'phab_id');
            echo "Link removed";
        } else {
            if (get_user_meta(get_current_user_id(), 'phab_id')) {
                require __DIR__ . '/html/menu_page_with.php';
            } else {
                require __DIR__ . '/html/menu_page_without.php';
            }
        }
    }

    // do something during plugin activation:
    function activate() {
        foreach ($this->settings as $key => $value) {
            if (get_option($key) === FALSE) {
                add_option($key, $value);
            }
        }
    }

    // do something during plugin deactivation:
    function deactivate() {
        
    }

    function uninstall() {
        // delete all plugin settings ONLY if the user requested it:
        global $wpdb;
        $delete_settings = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'wpphab_delete_settings_on_uninstall'");
        if ($delete_settings) {
            $wpdb->query("DELEwordprTE FROM $wpdb->options WHERE option_name LIKE 'wpphab_%';");
            $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'wpphab_%';");
        }
    }

    function login_head() {
        if ((filter_input(INPUT_POST, 'phab_cmd') == "login") || (filter_input(INPUT_POST, 'phab_cmd') == "link")) {
            if (filter_input(INPUT_POST, 'phab_cmd') == "link") {
                $_SESSION['phab_link'] = true;
            }
            $options = array(
                'scopes' => array('whoami', 'offline_access')
            );
            $authorizationUrl = $this->phab->getAuthorizationUrl($options);
            header('Location: ' . $authorizationUrl);
        }
    }

    function login_message() {
        include 'html/login.php';
    }

    function login_enqueue_scripts() {
        wp_enqueue_style('phab', plugin_dir_url(__FILE__) . 'css/login.css', false);
    }

    public function login_init() {
        if (filter_input(INPUT_GET, 'code')) {
            $phab_data = null;
            try {
                $accessToken = $this->phab->getAccessToken('authorization_code', [
                    'code' => filter_input(INPUT_GET, 'code')
                ]);

                $resourceOwner = $this->phab->getResourceOwner($accessToken);
                $phab_data = $resourceOwner->toArray();
            } catch (Exception $e) {
                
            }

            if (!is_null($phab_data)) {
                $phab_user_data = $phab_data["result"];

                $users = get_users();
                $matched_user = null;

                if (!isset($_SESSION['phab_link'])) {
                    foreach ($users as $user) {
                        if (get_user_meta($user->ID, "phab_id", true) == $phab_user_data['phid']) {
                            $matched_user = $user;
                        }
                    }
                    if (get_option('wpphab_auto_register') == 1) {
                        if (is_null($matched_user)) { // register user
                            $userdata = array(
                                'user_login' => $phab_user_data['userName'],
                                'user_email' => $phab_user_data['primaryEmail'],
                                'user_pass' => wp_generate_password(),
                                'role' => 'author',
                                'display_name' => $phab_user_data['realName'],
                            );
                            $user_id = wp_insert_user($userdata);

                            add_user_meta($user_id, 'phab_id', $phab_user_data["phid"]);
                            wp_new_user_notification($user_id, null, 'both');

                            if ($user_id) {
                                $matched_user = get_user_by('id', $user_id);
                            }
                        }
                    }
                } else {
                    unset($_SESSION['phab_link']);
                    add_user_meta(get_current_user_id(), 'phab_id', $phab_user_data["phid"]);
                    $matched_user = get_current_user();
                }

                if (is_null($matched_user)) {
                    // missing matching user inside wordpress
                    wp_safe_redirect(admin_url() . "#missing");
                } else {
                    // login now
                    if (get_current_user_id() == 0) {
                        $user_id = $matched_user->ID;
                        $user_login = $matched_user->user_login;
                        wp_set_current_user($user_id, $user_login);
                        wp_set_auth_cookie($user_id);
                        do_action('wp_login', $user_login, $matched_user);
                    }
                    wp_safe_redirect(get_dashboard_url() . "#login");
                }
            } else {
                // missing data from phabricator
                wp_safe_redirect(admin_url() . "#fetch");
            }
        }
    }

}

WP_phab::get_instance();
