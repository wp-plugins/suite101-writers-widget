<?php

/**
 * Plugin Name: Suite101 Writer's Widget
 * Description: Include a feed of writer's articles on your blog.
 * Version: 1.1
 * Author: Suite101
 * Author URI: http://www.suite101.com
**/



if (!class_exists('WriterWidgetPlugin'))
{
  
  class WriterWidgetPlugin
  {
    
    const shortcode_name = 'ww';
    // Test URL
    //const url_base = 'http://writerwidget.m7tech.net/';
    const url_base = 'http://widget.suite101.com/';
        
    protected $plugin_name,
    $plugin_url;
    
    // Current option values, loaded when plugin starts.
    protected $current_options;
    
    // Option names.
    const rss_uri_opt_name = 'ww_rss_uri';
    const section_id_opt_name = 'ww_section_id';
    const topic_id_opt_name = 'ww_topic_id';
    const category_id_opt_name = 'ww_category_id';
    const author_account_opt_name = 'ww_author_account';
    const security_code_opt_name = 'ww_security_code';
    const widget_config_id_opt_name = 'ww_widget_config_id';
    const widget_theme_opt_name = 'ww_theme';

    private $options = array(self::rss_uri_opt_name,
                             self::section_id_opt_name,
                             self::topic_id_opt_name,
                             self::category_id_opt_name,
                             self::author_account_opt_name,
                             self::security_code_opt_name,
                             self::widget_config_id_opt_name,
                             self::widget_theme_opt_name);
    
    public function __construct()
    {
      $this->plugin_name = plugin_basename(__FILE__);
      $this->plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__)));
      define('WW_BASE_URL', $this->plugin_url);
      register_activation_hook($this->plugin_name, array(&$this, 'install'));
      register_deactivation_hook($this->plugin_name, array(&$this, 'uninstall'));
      wp_register_script('ww-admin', $this->plugin_url . 'admin.js', array('jquery'), '1.0.0');      
      $this->load_options();
      $this->register_hooks();      
    }
    
    public function install()
    {
      // Show message by default on install.
      update_option('ww_display_message', 1);
    }
    
    public function uninstall()
    {
      remove_shortcode(self::shortcode_name);
    }
    
    public function register_hooks()
    {
      // Register with shortcode API to do content replacement.
      add_shortcode(self::shortcode_name, array(&$this, 'replace_content'));
      
       // Add admin/options menu
      add_action('admin_menu', array(&$this, 'add_menu'));
      // Add any javascript for admin
      add_action('admin_print_scripts', array(&$this, 'load_admin_scripts') );
      
      // Load CSS
      add_action('wp_print_styles', array(&$this, 'load_styles'));
      
      // Add JavaScript
      add_action('init', array(&$this, 'load_scripts'));
      
      // Add action for admin notices
      add_action('admin_notices', array(&$this, 'print_notice'));
    }
    
    public function print_notice()
    {
      // Display a message stating the plugin has been installed,
      // and what to do next.
      if (get_option('ww_display_message'))
      {
        $option_link = admin_url('options-general.php?page=suite101-writers-widget/suite101-writerwidget.php');
        echo('<div class="error fade"><p>'.
             __('You must now configure your widget by clicking <a href="'.$option_link.'">here</a>.', 'ww_domain').
             '</p></div>');
      }
    }
    
    public function register_widget()
    {
      register_widget('WriterWidget');
    }
    
    /**
     * Load options into the plugin, setting defaults and creating options
     * themselves, if required.
     **/
    
    public function load_options()
    {
      // Set option defaults.
      $default_options = array(self::rss_uri_opt_name => '',
                               self::section_id_opt_name => '',
                               self::topic_id_opt_name => '',
                               self::category_id_opt_name => '',
                               self::author_account_opt_name => '',
                               self::security_code_opt_name => '',
                               self::widget_config_id_opt_name => '',
                               self::widget_theme_opt_name => 'transparent');
      // Load options into the current_options array, and creating if needed.
      foreach($this->options as $option_name)
      {
        $opt = get_option($option_name);
        if ($opt == false || $opt == '')
        {
          $this->current_options[$option_name] = $default_options[$option_name];
          add_option($option_name, $default_options[$option_name]);
        }
        else
        {
          $this->current_options[$option_name] = $opt;
        }
      }
    }
    
    public function load_styles()
    {
      // Load style according to theme picked by user.
      $widget_theme = get_option(self::widget_theme_opt_name, 'transparent');
      $css_uri = 'css/widget-' . $widget_theme . '.css';
      wp_enqueue_style('suite101widget', $this->plugin_url . $css_uri, false, '1.0.0', 'screen');
    }
    
    public function add_menu()
    {
      add_options_page(__('Suite101 Writer\'s Widget'), __('Suite101 Writer\'s Widget'), 8, __FILE__, array(&$this, 'options_page'));
    }

    /**
     * Replaces shortcodes in posts with whatever content.
     **/

    public function replace_content($attrs, $content='')
    {
      return '';
    }
    
    public function load_admin_scripts()
    {
      // Only if we're in the options page.
      if (stristr($_SERVER['REQUEST_URI'], $this->plugin_name))
      {
        wp_enqueue_script('ww-admin');
      }
    }
    
    public function load_scripts()
    {
      if (!is_admin()) {
        wp_enqueue_script('jquery');
      }
    }
    
    public function options_page()
    {
      $hidden_field_name = 'ww_submit_hidden';
      
      // Update?
      if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] =='Y')
      {
        foreach($this->options as $option)
        {
          update_option($option, $_POST[$option]);
        }
        // Turn off message.
        update_option('ww_display_message', 0);
        // Reload
        $this->load_options();
        // Clear cached RSS feed.
        $this->clear_rss_cache();
      }
      
      // Hidden field for flushing all options
      if (isset($_GET['yodarocks']))
      {
        foreach($this->options as $option)
        {
          delete_option($option);
        }
      }
      
      // Flush cache
      if (isset($_GET['flush_rss']))
      {
        $this->clear_rss_cache();
      }
      
      // Output JavaScript variables.
      echo($this->generate_js_vars());
      
      // Ouput. Hate how WP combines this.
      echo( '<div class="wrap">' );
      echo('<h2>'.__('Suite101 Writer\'s Widget Options', 'ww_domain').'</h2>');
      echo(__('<p>Generate links from your blog to your Suite101 articles! First, enter in your profile URL. '.
      	   'Then, select the section, topic and category you contribute to most often.</p>', 'ww_domain'));
      echo('<br />');
      echo('<form name="ww_options" id="widget-create" method="POST" action="'.
          str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">');
      // Author Account
      echo( '<p class="step-1">'.__( 'Profile URL : ', 'ww_domain' ) );
      echo( '<input type="text" id="author-account" name="'.self::author_account_opt_name.'" value="'.
           $this->current_options[self::author_account_opt_name].'" /><span id="profile-url-errors"></span></p>');
      // Section ID
      echo( '<p class="step-2">'.__( 'Section : ', 'ww_domain' ) );
      echo( '<select id="section_id" name="'.self::section_id_opt_name.'"></select></p>');
      // Topic ID
      echo( '<p class="step-3">'.__( 'Topic : ', 'ww_domain' ) );
      echo( '<select id="topic_id" name="'.self::topic_id_opt_name.'"></select></p>');
      // Category ID
      echo( '<p class="step-4">'.__( 'Category : ', 'ww_domain' ) );
      echo( '<select id="category_id" name="'.self::category_id_opt_name.'"></select></p>');
      // Theme
      echo('<p class="step-4">'.__('Theme : ', 'ww_domain'));
      $selected_theme = get_option(self::widget_theme_opt_name);
      $theme_options = '';
      $option_choices = array('dark-dark' => __('Dark on Dark', 'ww_domain'),
                              'transparent' => __('Transparent', 'ww_domain'),
                              'dark-light' => __('Dark on Light', 'ww_domain'));
      foreach($option_choices as $theme_name => $theme_display)
      {
        if ($theme_name == $selected_theme)
        {
          $theme_options .= "<option value=\"$theme_name\" selected=\"selected\">$theme_display</option>";
        }
        else
        {
          $theme_options .= "<option value=\"$theme_name\">$theme_display</option>";
        }
      }
      echo('<select id="theme" name="'.self::widget_theme_opt_name.'">'.$theme_options.'</select>&nbsp;<a href="#" id="preview-theme">Preview</a></p>');
     
      // Hidden fields
      // Security Code
      echo( '<input type="hidden" id="security_code" name="'.self::security_code_opt_name.'" value="'.
           $this->current_options[self::security_code_opt_name].'" />');
      // Widget Config ID
      echo( '<input type="hidden" id="widget_config_id" name="'.self::widget_config_id_opt_name.'" value="'.
           $this->current_options[self::widget_config_id_opt_name].'" />');
      echo( '<input type="hidden" name="'.$hidden_field_name.'" value="Y">' );
      // RSS URI
      echo( '<input type="hidden" id="rss_uri" name="'.self::rss_uri_opt_name.'" value="'.
           $this->current_options[self::rss_uri_opt_name].'" />');
      echo('<input class="step-5" type="submit" name="submit" id="widget-create" value="Submit" />');
      echo('<br />');
      echo('<a href="'.$_SERVER['REQUEST_URI'].'&flush_cache=1">Flush RSS Cache</a>');
      echo('<p id="ww-message"></p>');
      echo('</div>');
      echo('<style>#preview-pane { margin: -250px 0 0 300px; } </style>');
      echo('<div id="preview-pane"></div>');      
    }
    
    /**
     * Make the options available to JS.
     **/
    
    public function generate_js_vars()
    {
      $ret = '<script type="text/javascript">';
      $ret .= 'var section_id_selected = \'' . $this->current_options[self::section_id_opt_name] . "';\n";
      $ret .= 'var topic_id_selected = \'' . $this->current_options[self::topic_id_opt_name] . "';\n";
      $ret .= 'var category_id_selected = \'' . $this->current_options[self::category_id_opt_name] . "';\n";
      $ret .= 'var jsonp_base_url = \'' . self::url_base . "';\n";
      $ret .= 'var plugin_base_url = \'' . $this->plugin_url . "';\n";
      $ret .= '</script>';
      return $ret;
    }
    
    /**
     * Clear WP's internal RSS cache - used for when user changes aspects of the
     * feed.
     **/
    
    public function clear_rss_cache()
    {
      $rss_key = md5($this->current_options[self::rss_uri_opt_name]);
      error_log('RSS Cache clear - URI:'. $this->current_options[self::rss_uri_opt_name].' Key: ' . $rss_key);
      delete_option( "_transient_timeout_feed_mod_$rss_key" );
      delete_option( "_transient_timeout_feed_$rss_key" );
      delete_option( "_transient_feed_mod_$rss_key" );
      delete_option( "_transient_feed_$rss_key" );
      delete_option( "_transient_rss_$rss_key" );
      delete_option( "_transient_timeout_rss_$rss_key" );      
    }
    
  }
  
  $ww = new WriterWidgetPlugin();
}

class WriterWidget extends WP_Widget
{
  function WriterWidget()
  {
    $widget_ops = array('description' => __( "Includes Suite101 writer feed on your site") );
    $this->WP_Widget('suite101widget', __('Suite101 Writer\'s Widget'), $widget_ops);
  }

  function widget($args, $instance)
  {
    extract($args);
    $rss_url = get_option(WriterWidgetPlugin::rss_uri_opt_name);
    $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
    $template_name = get_option(WriterWidgetPlugin::widget_theme_opt_name, 'transparent');
    
    echo $before_widget;
    if ($title)
    {
      echo $before_title . $title . $after_title;
    }
    if ( !defined('MAGPIE_CACHE_AGE') ) {
        define('MAGPIE_CACHE_AGE', 3*60);
      }
    
    $html = '';
    // If no RSS URL, don't do anything.
    if ($rss_url != '')
    {
      // Fetch it
      require_once(ABSPATH . WPINC . '/rss.php');
      $rss = fetch_rss($rss_url);
      $image_base_url = WW_BASE_URL . 'images/';
      $template_base_path = '';
    
      if (is_array($rss->items ) && !empty($rss->items))
      {
        // Parse feed and build the HTML
        $parsed_feed = $this->parse_feed($rss);
        // Blurb for STC - putting this logic here since it's the same for all
        // templates.
        $home_link = $this->make_anchor($parsed_feed['home_uri'], $parsed_feed['home_anchor']);
        $section_link = $this->make_anchor($parsed_feed['section_uri'], $parsed_feed['section_anchor']);        
        //$stc_blurb = "<p>Find more $section_link at $home_link</p>";
        $stc_blurb = "<p>Find more $section_link</p>";
        if ($parsed_feed['topic_uri'] != '' && $parsed_feed['category_uri'] != '')
        {
          // We've got all three.
          $topic_link = $this->make_anchor($parsed_feed['topic_uri'], $parsed_feed['topic_anchor']);
          $category_link = $this->make_anchor($parsed_feed['category_uri'], $parsed_feed['category_anchor']);
          //$stc_blurb = "<p>Find more $category_link and $topic_link articles in the $section_link section at $home_link";
          $stc_blurb = "<p>Find more $category_link and $topic_link articles in the $section_link section";
        }
        elseif ($parsed_feed['topic_uri'] != '')
        {
        	$topic_link = $this->make_anchor($parsed_feed['topic_uri'], $parsed_feed['topic_anchor']);
          //$stc_blurb = "<p>Find more $topic_link articles in the $section_link section at $home_link";
          $stc_blurb = "<p>Find more $topic_link articles in the $section_link section";
        }

        $html = $this->outputTemplate($template_name, array('parsed_feed' => $parsed_feed,
                                                            'stc_blurb' => $stc_blurb,
                                                            'image_base_url' => $image_base_url));
      }
      else
      {
        $html = "<!-- RSS Link not working? -->\n";
      }
      
      $parsed_req = parse_url($_SERVER['REQUEST_URI']);
      $req_url = str_replace('.', '%2E', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
      $parsed_ref = parse_url($_REQUEST['REFERER_URL']);
      $ref_url = str_replace('.', '%2E', $parsed_ref['host'] . $parsed_ref['path']);
      $widget_config_id = get_option(WriterWidgetPlugin::widget_config_id_opt_name);

      // Dynamic font-resizing.
      $js = "<script type=\"text/javascript\">
      jQuery(document).ready(function(){
        var e = jQuery('#author-name');
        if (e)
        {
          var start_font_size = e.css('font-size');
          var height = e.height();
          var line_height = e.css('line-height').replace('px', '');
          
          if (height > line_height)
          {
            var font_size = start_font_size.replace('px', '');
            while(height > line_height - 0.5 && font_size > 8)
            {
              font_size--;
              e.css('font-size', font_size + 'px');
              height = e.height();
            }
          }
        }
      });
      </script>";
      $html .= $js;
      echo $html;
      echo $after_widget;
    }
  }

  function make_anchor($href, $anchor)
  {
  	return "<a href=\"$href\">$anchor</a>";
  }
  
  function parse_feed($rss)
  {
    $ret = array('home_uri' => '',
                 'home_anchor' => '',
                 'author_uri' => '',
                 'author_anchor' => '',
                 'section_uri' => '',
                 'section_anchor' => '',
                 'topic_uri' => '',
                 'topic_anchor' => '',
                 'category_uri' => '',
                 'category_anchor' => '',
                 'writer_uri' => '',
                 'writer_anchor' => '',
                 'image_alt' => '');
    $articles = array();
    // Home URI, anchor and image alt are set in the feed itself.
    $ret['home_uri'] = strip_tags($rss->channel['link']);
    $ret['home_anchor'] = attribute_escape(strip_tags($rss->channel['title']));
    $ret['image_alt'] = attribute_escape(strip_tags($rss->image['title']));
    if (is_array($rss->items ) && !empty($rss->items))
    {
      foreach( $rss->items as $item )
      {
        $link = clean_url(strip_tags($item['link']));
        $title = attribute_escape(strip_tags($item['title']));
        $category = strtolower(attribute_escape(strip_tags($item['category'])));
        if ($category != 'article')
        {
          $ret[$category.'_uri'] = $link;
          $ret[$category.'_anchor'] = $title;
        }
        else
        {
          $articles []= array('link' => $link,
                              'title' => $title,
                              'published' => $this->getPublished($item['pubdate']));
        }
      }
    }
    $ret['articles'] = $articles;
    return $ret;
  }
  
  /**
   * Template handler.
   **/
  
  function outputTemplate($template_name, $args=array())
  {
    $filename = dirname(__FILE__) . "/templates/$template_name-template.php";
    error_log('Template being loaded: ' . $filename);
    // Standard PHP pattern to capture output and print.
    ob_start();
    $this->renderTemplate($filename, $args);
    $output = ob_get_contents();
    ob_end_clean();
                
    return $output;
  }
  
  /**
   * Renders a template 
   **/
  
  function renderTemplate($filename, $args=array())
  {
    // Unpack arguments so they can be used as variables in the template.
    extract($args);
    if ($filename != false && file_exists($filename))
    {
      include($filename);
    }
    else
    {
      echo('<!-- Rendering ' . $filename .' failed! -->');
    }
  }
  
  /**
   * Converts a published date into a string like "today", "yesterday", "N days ago"
   **/
  
  function getPublished($pubDate)
  {
    $ret = '';
    if ($pubDate != '')
    {
      $time_diff = time() - strtotime($pubDate);
      $in_days = round($time_diff/86400);
      $in_weeks = round($time_diff/(86400*7));
      $in_months = round($time_diff/(86400*30));
      $in_years = round($time_diff/(86400*365));
      if ($in_days <= 1)
      {
        $ret = 'today';
      }
      elseif ($in_days == 2)
      {
        $ret = 'yesterday';
      }
      elseif ($in_days < 7)
      {
        $ret = "$in_days days ago";
      }
      elseif ($in_weeks < 4)
      {
        if ($in_weeks == 1)
        {
          $ret = "last week";
        }
        else
        {
          $ret = "$in_weeks weeks ago";
        }
      }
      elseif ($in_months < 12)
      {
        if ($in_months == 1)
        {
          $ret = 'last month';
        }
        else
        {
          $ret = "$in_months months ago";
        }
      }
      else
      {
        if ($in_years == 1)
        {
          $ret = 'last year';
        }
        else
        {
          $ret = "$in_years years ago";
        }
      }
    }
    return $ret;
  }

  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '') );
    $title = $instance['title'];
?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
<?php
  }

  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
    $instance['title'] = strip_tags($new_instance['title']);
    return $instance;
  }
}

add_action('widgets_init', create_function('', 'return register_widget("WriterWidget");'));

?>
