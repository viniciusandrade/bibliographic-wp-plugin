<?php
/*
Plugin Name: Bibliographic
Plugin URI: http://reddes.bvsalud.org/projects/fi-admin/
Description: Search bibliographic records from FI-ADMIN.
Author: BIREME/OPAS/OMS
Version: 0.1
Author URI: http://reddes.bvsalud.org/
*/

define('BIBLIOGRAPHIC_PLUGIN_VERSION', '0.1' );

define('BIBLIOGRAPHIC_SYMBOLIC_LINK', false );
define('BIBLIOGRAPHIC_PLUGIN_DIRNAME', 'bibliographic' );

if(BIBLIOGRAPHIC_PLUGIN_SYMBOLIC_LINK == true) {
    define('BIBLIOGRAPHIC_PLUGIN_PATH',  ABSPATH . 'wp-content/plugins/' . BIBLIOGRAPHIC_PLUGIN_DIRNAME );
} else {
    define('BIBLIOGRAPHIC_PLUGIN_PATH',  plugin_dir_path(__FILE__) );
}

define('BIBLIOGRAPHIC_PLUGIN_DIR',   plugin_basename( BIBLIOGRAPHIC_PLUGIN_PATH ) );
define('BIBLIOGRAPHIC_PLUGIN_URL',   plugin_dir_url(__FILE__) );


require_once(BIBLIOGRAPHIC_PLUGIN_PATH . '/settings.php');
require_once(BIBLIOGRAPHIC_PLUGIN_PATH . '/template-functions.php');

if(!class_exists('Bibliographic_Plugin')) {
    class Bibliographic_Plugin {

        private $plugin_slug = 'biblio';
        private $service_url = 'http://fi-admin.teste.bvsalud.org/';

        /**
         * Construct the plugin object
         */
        public function __construct() {
            // register actions

            add_action( 'init', array(&$this, 'load_translation'));
            add_action( 'admin_menu', array(&$this, 'admin_menu'));
            add_action( 'plugins_loaded', array(&$this, 'plugin_init'));
            add_action( 'wp_head', array(&$this, 'google_analytics_code'));
            add_action( 'template_redirect', array(&$this, 'template_redirect'));
            add_action( 'widgets_init', array(&$this, 'register_sidebars'));
            add_filter( 'wp_title', array(&$this, 'page_title'), 20, 2);
            add_filter( 'get_search_form', array(&$this, 'search_form'));


        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate


        function load_translation(){
            // Translations
            load_plugin_textdomain( 'biblio', false,  BIBLIOGRAPHIC_PLUGIN_DIR . '/languages' );
        }

        function plugin_init() {
            $biblio_config = get_option('biblio_config');

            if ($biblio_config && $biblio_config['plugin_slug'] != ''){
                $this->plugin_slug = $biblio_config['plugin_slug'];
            }

        }

        function admin_menu() {
            add_options_page(__('Bibliographic record settings', 'biblio'), __('Bibliographic records', 'biblio'),
                'manage_options', 'biblio', 'biblio_page_admin');
            //call register settings function
            add_action( 'admin_init', array(&$this, 'register_settings'));
        }

        function template_redirect() {
            global $wp, $biblio_service_url, $biblio_plugin_slug;
            $pagename = $wp->query_vars["pagename"];

            $biblio_service_url = $this->service_url;
            $biblio_plugin_slug = $this->plugin_slug;

            if ($pagename == $this->plugin_slug || $pagename == $this->plugin_slug . '/resource'
                || $pagename == $this->plugin_slug . '/bibliographic-feed'
                ) {

                add_action( 'wp_enqueue_scripts', array(&$this, 'page_template_styles_scripts'));

                if ($pagename == $this->plugin_slug){
                    $template = BIBLIOGRAPHIC_PLUGIN_PATH . '/template/home.php';
                }elseif ($pagename == $this->plugin_slug . '/bibliographic-feed'){
                    header("Content-Type: text/xml; charset=UTF-8");
                    $template = BIBLIOGRAPHIC_PLUGIN_PATH . '/template/rss.php';
                }else{
                    $template = BIBLIOGRAPHIC_PLUGIN_PATH . '/template/resource.php';
                }
                // force status to 200 - OK
                status_header(200);

                // redirect to page and finish execution
                include($template);
                die();
            }
        }

        function register_sidebars(){
            $args = array(
                'name' => __('Bibliographic sidebar', 'biblio'),
                'id'   => 'biblio-home',
                'description' => 'Bibligraphic Area',
                'before_widget' => '<section id="%1$s" class="row-fluid marginbottom25 widget_categories">',
                'after_widget'  => '</section>',
                'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
                'after_title'   => '</h1></header>',
            );
            register_sidebar( $args );
        }

        function page_title($title, $sep){
            global $wp;
            $pagename = $wp->query_vars["pagename"];
            $title = $title ? $title : get_bloginfo();

            if ( strpos($pagename, $this->plugin_slug) === 0 ) { //pagename starts with plugin slug
                return __('Bibliographic records')  . ' | ' . $title;
            }

        }

        function search_form( $form ) {
            global $wp;
            $pagename = $wp->query_vars["pagename"];

            if ($pagename == $this->plugin_slug || $pagename == $this->plugin_slug .'/resource') {
                $form = preg_replace('/action="([^"]*)"(.*)/','action="' . home_url($this->plugin_slug) . '"',$form);
            }

            return $form;
        }

        function page_template_styles_scripts(){
            wp_enqueue_script('biblio-tooltipster',  BIBLIOGRAPHIC_PLUGIN_URL . 'template/js/jquery.tooltipster.min.js');
            wp_enqueue_script('biblio',  BIBLIOGRAPHIC_PLUGIN_URL . 'template/js/functions.js');
            wp_enqueue_style ('font-awesome', BIBLIOGRAPHIC_PLUGIN_URL . 'template/css/font-awesome/css/font-awesome.min.css');
            wp_enqueue_style ('biblio-tooltipster',  BIBLIOGRAPHIC_PLUGIN_URL . 'template/css/tooltipster.css');
            wp_enqueue_style ('biblio',  BIBLIOGRAPHIC_PLUGIN_URL . 'template/css/style.css');
        }


        function register_settings(){
            register_setting('biblio-settings-group', 'biblio_config');
        }

        function google_analytics_code(){
            global $wp;

            $pagename = $wp->query_vars["pagename"];
            $biblio_config = get_option('biblio_config');

            // check if is defined GA code and pagename starts with plugin slug
            if ($biblio_config['google_analytics_code'] != ''
                && strpos($pagename, $this->plugin_slug) === 0){
        ?>

        <script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '<?php echo $biblio_config['google_analytics_code'] ?>']);
          _gaq.push(['_trackPageview']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();

        </script>

        <?php
            } //endif
        }


    } // END class Bibliographic_Plugin
} // END if(!class_exists('Bibliographic_Plugin'))

if(class_exists('Bibliographic_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('Bibliographic_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('Bibliographic_Plugin', 'deactivate'));

    // instantiate the plugin class
    $wp_plugin_template = new Bibliographic_Plugin();
}

?>