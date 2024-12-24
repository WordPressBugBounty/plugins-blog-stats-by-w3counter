<?php

/***************************************************************************

Plugin Name:  W3Counter Free Web Stats
Plugin URI:   http://www.w3counter.com
Description:  Adds real-time stats to your dashboard including visitor activity, top posts, top referrers, searches and locations of your visitors. To get started: 1) <a href="http://www.w3counter.com/signup">Sign up for a free W3Counter account</a>, 2) Go to your <a href="admin.php?page=w3counter-config">W3Counter Settings</a> page to set your login details.
Version:      4.1
Author:       W3Counter
Author URI:   http://www.w3counter.com

**************************************************************************/

function w3counter_init() {
    add_action('admin_menu', 'w3counter_config_page');
    add_action('admin_menu', 'w3counter_stats_page');
}
add_action('init', 'w3counter_init');
register_activation_hook(__FILE__, 'w3counter_activate');

function w3counter_warning() {
    echo "
    <div id='w3counter-warning' class='updated fade'><p><strong>".__('W3Counter is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your W3Counter login details</a> for it to work.'), "admin.php?page=w3counter-config")."</p></div>
    ";
}

function w3counter_admin_init() {
    if ( function_exists( 'get_plugin_page_hook' ) )
        $hook = get_plugin_page_hook( 'w3counter-stats-display', 'index.php' );
    else
        $hook = 'dashboard_page_w3counter-stats-display';
    add_action('admin_head-'.$hook, 'w3counter_stats_script');

    $w3counter_token = get_option('w3counter_token');
    if (empty($w3counter_token))
        add_action('admin_notices', 'w3counter_warning');
}
add_action('admin_init', 'w3counter_admin_init');

function w3counter_config_page() {
    if ( function_exists('add_submenu_page') )
        add_submenu_page('options-general.php', __('W3Counter'), __('W3Counter'), 'manage_options', 'w3counter-config', 'w3counter_conf');
}

function w3counter_stats_page() {
    if ( function_exists('add_submenu_page') )
        add_submenu_page('index.php', __('W3Counter Stats'), __('W3Counter Stats'), 'manage_options', 'w3counter-stats-display', 'w3counter_stats_display');
}

function w3counter_stats_script() { ?>
    <script type="text/javascript">
        function resizew3Iframe() {
            var height = document.documentElement.clientHeight;
            height -= document.getElementById('w3counter-stats-frame').offsetTop;
            height += 100; // magic padding
            document.getElementById('w3counter-stats-frame').style.height = height +"px";
        };
        function resizew3IframeInit() {
            document.getElementById('w3counter-stats-frame').onload = resizew3Iframe;
            window.onresize = resizew3Iframe;
        }
        addLoadEvent(resizew3IframeInit);
    </script>
    <?php
}

function w3counter_stats_display() {
    
    $w3counter_id = get_option('w3counter_id');
    $w3counter_token = get_option('w3counter_token');

    if (!empty($w3counter_token)) {

        $url = 'https://www.w3counter.com/stats/' . $w3counter_id . '?token=' . $w3counter_token;
        ?>
        <div class="wrap">
            <iframe src="<?php echo $url; ?>" width="100%" height="100%" frameborder="0" id="w3counter-stats-frame"></iframe>
        </div>
        
        <?php

    }
}

function w3counter_activate() {
    $w3counter_pass = get_option('w3counter_pass');
    if (!empty($w3counter_pass)) {
        delete_option('w3counter_user');
        delete_option('w3counter_pass');
        delete_option('w3counter_sites');
    }
}

function w3counter_conf() {

    //for debugging
    if (isset($_GET['clear'])) {
        delete_option('w3counter_user');
        delete_option('w3counter_pass');
        delete_option('w3counter_id');
        delete_option('w3counter_sites');
        delete_option('w3counter_token');
    }

    if (!empty($_POST['w3counter_id']))
        update_option('w3counter_id', $_POST['w3counter_id']);
    $w3counter_id = get_option('w3counter_id');

    if (!empty($_POST['w3counter_user'])) {
        $w3counter_sites = array();
        $list = @file('https://www.w3counter.com/stats/wpinstall?v3=true&username=' . urlencode($_POST['w3counter_user']) . '&password=' . urlencode(md5($_POST['w3counter_pass'])));
        if (!empty($list)) {
            $i = 0;
            foreach ($list as $line) {
                if ($i == 0) {
                    update_option('w3counter_token', trim($line));
                    $i++;
                } else {
                    $line = trim($line);
                    $parts = explode(',', $line);
                    $w3counter_sites[] = array('id' => $parts[0], 'url' => $parts[1]);
                }
            }
            if (count($w3counter_sites) == 1) {
                $w3counter_id = $w3counter_sites[0]['id'];
                update_option('w3counter_id', $w3counter_id);
            }
        } else {
            $manual = true;
        }
    }

    $w3counter_token = get_option('w3counter_token');

?>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>

    <h2>W3Counter Settings</h2>

    <form method="post" action="admin.php?page=w3counter-config">

    <?php if (!empty($w3counter_token) && !empty($w3counter_id)): ?>
        <div id="message" class="updated fade"><p><strong><?php _e('All set! You can view your blog\'s stats from your <b>Dashboard</b> menu. If you\'ve chosen a hit counter or badge to display on your site, you can do so by adding the W3Counter widget to one of your sidebars using the <b>Appearance</b> menu.') ?></strong></p></div>
    <?php endif; ?>

    <?php if (isset($manual)): ?>
        <div id="message" class="updated fade"><p><strong>Error:</strong> We couldn't retrieve the websites from 
            your account. You may have entered your username or password incorrectly, or your web host blocked the 
            connection to W3Counter. You can finish setting up the plugin manually by entering the ID of your 
            website below. The ID is the number you see in your browser address bar when viewing 
            your stats on the W3Counter website (ex: http://www.w3counter.com/stats/123 <-- 123).
        </p></div>
    <?php endif; ?>

    <table class="form-table">
        <?php if (!isset($manual) && empty($w3counter_sites)): ?>
            <tr valign="top">
                <th scope="row"><label>Your W3Counter Username:</label></th>
                <td>
                    <input type="text" name="w3counter_user" value="<?php echo $w3counter_user; ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Your W3Counter Password:</label></th>
                <td>
                    <input type="password" name="w3counter_pass" value="" />
                </td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($w3counter_sites)): ?>
            <tr>
                <th scope="row" style="width: 400px"><label>What website in W3Counter should this blog be linked to?</label></th>
                <td>
                    <select name="w3counter_id">
                    <?php foreach ($w3counter_sites as $site): ?>
                        <option value="<?php echo $site['id']; ?>"<?php if (!empty($w3counter_id) && $w3counter_id == $site['id']) echo ' selected="selected"'; ?>><?php echo str_replace('http://', '', $site['url']); ?></option>
                    <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php elseif ($manual): ?>
        <tr>
            <th scope="row"><label>Website ID:</label></th>
            <td>
                <input type="text" name="w3counter_id" value="" />
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <br /><br />

    <input type="submit" value="Save Changes" class="button" />

    </form>
</div>

<?php 
}

// Tracker Widget

class WP_Widget_W3Counter extends WP_Widget {

    function __construct() {
        $widget_ops = array('classname' => 'WP_Widget_W3Counter', 'description' => __( 'Adds the W3Counter tracking code to your theme, which will display the badge/counter style you chose when you signed up') );
        $this->WP_Widget('w3counter', __('W3Counter Widget (Badge/Counter)'), $widget_ops);
        add_action('wp_head', array(&$this, 'add_js'));
    }

    function add_js() {
        if (!is_active_widget(false, false, $this->id_base, true)) {
            $w3counter_id = get_option('w3counter_id');
            echo '<script type="text/javascript" src="https://www.w3counter.com/tracker.js?id=' . $w3counter_id . '&wphead=true"></script>' . "\n";
        }
    }

    function widget( $args, $instance ) {
        extract($args);

        $w3counter_id = get_option('w3counter_id');
        echo $before_widget;
        echo '<div id="w3counter_wrap">';
?>
<script type="text/javascript" src="https://www.w3counter.com/tracker.js?id=<?php echo $w3counter_id; ?>"></script>
<?php
        echo '</div>';
        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        return $instance;
    }

    function form($instance) { }

}

function w3counter_widget_init() {
    register_widget('WP_Widget_W3Counter');
}
add_action('widgets_init', 'w3counter_widget_init');

?>