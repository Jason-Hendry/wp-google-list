<?php
/*
Plugin Name: WP Google List
Plugin URI: http://rain.systems/junk
Description: Filter Sheets in JS
Version: 0.1.0
Author: JasonHendry
Author URI: http://rain.systems
*/


defined('ABSPATH') or die('No script kiddies please!');


function wpGoogleListToClass(&$str, $idx = null)
{
    $str = preg_replace('/ +/', '-', preg_replace('/[^A-Za-z\ 0-9]/', '', trim($str)));
    if ($idx === null) {
        return $str;
    }
}

function wpGoogleListGenerateHTML($key)
{
    file_put_contents('/tmp/flavours.csv', file_get_contents("https://docs.google.com/spreadsheet/ccc?key=$key&output=csv"));
    $fp = fopen('/tmp/flavours.csv', 'r');

    $first = true;

    $allAwards = [];
    $products = [];

    while (!feof($fp)) {
        list($flavor, $type, $award, $dietaryTags) = fgetcsv($fp);
        if ($first) {
            $first = false;
            continue;
        }
        $classes = explode(",", $dietaryTags);
        array_walk($classes, 'wpGoogleListToClass');

        $awards = explode(",", $award);
        $awardLabels = explode(",", $award);
        array_walk($awards, 'wpGoogleListToClass');

        foreach ($awards as $i => $v) {
            $allAwards[$v] = $awardLabels[$i];
        }

        $classes = array_merge($classes, $awards);
        $classes[] = wpGoogleListToClass($type);
        $products[] = '<li class="' . trim(implode(' ', $classes)) . '">' . $flavor . '</li>';
    }

// print_r($allAwards);
    $productHTML = implode("\n            ", $products);

    ksort($allAwards);

    $awardHTML = "";
    foreach ($allAwards as $class => $name) {
        if (!$class) {
            continue;
        }
        $awardHTML .= "        <label><input type=\"checkbox\" name=\"$class\" value=\"$class\">$name</label><br>\n";

    }

    ob_start();
    ?>
    <div class="product-finder-left">
        <strong>Type</strong><br>
        <label><input type="checkbox" name="Sorbet" value="Sorbet">Sorbet</label><br>
        <label><input type="checkbox" name="Gelato" value="Gelato">Gelato</label><br>
        <label><input type="checkbox" name="Yoghurt" value="Yoghurt">Yoghurt</label><br>
        <strong>Dietary</strong><br>
        <label><input type="checkbox" name="Vegan" value="Vegan">Vegan</label><br>
        <label><input type="checkbox" name="Dairy-Free" value="Dairy-Free">Dairy Free</label><br>
        <label><input type="checkbox" name="Gluten-Free" value="Gluten-Free">Gluten Free</label><br>
        <label><input type="checkbox" name="Fat-Free" value="Fat-Free">Fat Free</label><br>
        <label><input type="checkbox" name="Contains-Egg" value="Contains-Egg">Contains Egg</label><br>
        <strong>Awards</strong><br>
        <?= $awardHTML ?>
    </div>
    <div class="product-finder-right">
        <ul>
            <?= $productHTML ?>

        </ul>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <script type="application/javascript">
        function counts() {
            $('.product-finder-left input').each(function () {

                var $count = $(this).siblings('.count');
                if (!$count.length) {
                    $count = $('<span>').addClass('count');
                    $(this).closest('label').append($count);
                }
                var flavours = $('.product-finder-right li:visible').filter('.' + $(this).attr('value')).length
                $count.text(' (' + flavours + ')');
                if (flavours == 0) {
                    $(this).closest('label').addClass('disabled');
                    $(this).attr("disabled", true);
                } else {
                    $(this).removeAttr("disabled");
                    $(this).closest('label').removeClass('disabled');
                }
            });
        }

        $(document).ready(function () {
            counts();
            $('.product-finder-left input').click(function () {
                var list = [];
                $('.product-finder-left input:checked').each(function () {
                    list.push('.' + $(this).val());
                });
                $('.product-finder-right li').hide();
                if (list.length == 0) {
                    $('.product-finder-right li').show();
                } else {
                    var filter = list.join('');
                    $('.product-finder-right li').filter(filter).show();
                }
                counts();
            })
        })
    </script>
    <?php
    return "<!-- Generate by WP-Google-List Plugin -->" . ob_get_clean();
}


add_action('wp_ajax_wpGoogleListAjax', 'wpGoogleListAjax');
add_action('wp_ajax_wpGoogleListAjaxReload', 'wpGoogleListAjaxReload');
add_action('wp_ajax_wpGoogleListAjaxDelete', 'wpGoogleListAjaxDelete');

function wpGoogleListAjax()
{
    global $wpdb; // this is how you get access to the database
    $key = $_POST['documentKey'];
    $table_name = $wpdb->prefix . "googlelist";
    $text = wpGoogleListGenerateHTML($key);
    $wpdb->insert(
        $table_name,
        array(
            'created' => current_time('mysql'),
            'name' => $_POST['name'],
            'key' => $key,
            'text' => $text,
        )
    );
    echo $text;
    wp_die(); // this is required to terminate immediately and return a proper response
}

function wpGoogleListAjaxReload()
{
    global $wpdb; // this is how you get access to the database
    $key = $_POST['documentKey'];
    $table_name = $wpdb->prefix . "googlelist";
    $text = wpGoogleListGenerateHTML($key);
    $wpdb->update(
        $table_name,
        array(
            'created' => current_time('mysql'),
            'key' => $key,
            'text' => $text,
        ), [
            'id' => $_POST['id']
        ]
    );
    echo $text;
    wp_die(); // this is required to terminate immediately and return a proper response
}

function wpGoogleListAjaxDelete()
{
    global $wpdb; // this is how you get access to the database
    $table_name = $wpdb->prefix . "googlelist";
    $wpdb->delete(
        $table_name,
        [
            'id' => $_POST['id']
        ], array( '%d' )
    );
    echo "Deleted";
    wp_die(); // this is required to terminate immediately and return a proper response
}


function wpGoogleListGenerateAdminPage()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    global $wpdb; // this is how you get access to the database
    $table_name = $wpdb->prefix . "googlelist";

    $rows = $wpdb->get_results( "SELECT * FROM `$table_name`", ARRAY_A );

    ?>
    change4
    <table style="width: 100%">
        <tr>
            <th>Name</th>
            <th>Key</th>
            <th>Code</th>
            <th>Action</th>
        </tr>
        <?php foreach($rows as $r) { ?>
        <tr>
            <td><?= $r['name'] ?></td>
            <td><?= $r['key'] ?></td>
            <td>[google_list name="<?= $r['name'] ?>"]</td>
            <td>
                <input type="button" class="wp-google-list-reload" data-text="<?= $r['id'] ?>|<?= $r['key'] ?>" value="Reload">
                <input type="button" class="wp-google-list-delete" data-text="<?= $r['id'] ?>" value="Delete">
            </td>
        </tr>
        <?php } ?>
    </table>
    <h2>Add New</h2>
    <form id="wpGoogleListForm">
        <input type="text" name="documentKey" id="documentKey" placeholder="Google Sheets Document Key">
        <input type="text" name="name" id="name" placeholder="List Name">
        <input type="submit">
    </form>
    <pre id="wpGoogleListHTML"></pre>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {

            $('#documentKey').val(localStorage.getItem('documentKey'));

            $('#wpGoogleListForm').submit(function (e) {
                var data = {
                    'action': 'wpGoogleListAjax',
                    'documentKey': $('#documentKey').val(),
                    'name': $('#name').val()
                };
                localStorage.setItem('documentKey', $('#documentKey').val());
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    $('#wpGoogleListHTML').text(response);
                });
                e.preventDefault();
                return false;
            });

            $('.wp-google-list-reload').click(function (e) {
                var idKey = $(this).data('text').split('|');
                var data = {
                    'action': 'wpGoogleListAjaxReload',
                    'documentKey': idKey[1],
                    'id': idKey[0]
                };
                jQuery.post(ajaxurl, data, function (response) {
                    $('#wpGoogleListHTML').text(response);
                });
                e.preventDefault();
                return false;
            });

            $('.wp-google-list-delete').click(function (e) {
                if(!confirm("Are you sure you want to delete this?")) {
                    return;
                }
                var data = {
                    'action': 'wpGoogleListAjaxDelete',
                    'id': $(this).data('text')
                };
                jQuery.post(ajaxurl, data, function (response) {
                    $('#wpGoogleListHTML').text(response);
                });
                e.preventDefault();
                return false;
            });


        });
    </script>

    <?php
}

add_action('admin_menu', 'wpGoogleListGenerateAdminMenu');

function wpGoogleListGenerateAdminMenu()
{
    add_options_page('Sheet to HTML', 'Sheet to HTML', 'manage_options', 'wp-google-list', 'wpGoogleListGenerateAdminPage');
}

global $wpGoogleList_db_version;
$wpGoogleList_db_version = '1.1';

function wpGoogleListInstallNotice() {
    global $wpGoogleList_db_version;
    ?>
    <div class="updated">
        <p>DB Version: <?= get_site_option('wpGoogleList_db_version') ?> > <?= $wpGoogleList_db_version ?></p>
    </div>
    <?php
}

function wpGoogleListUpgradeNotice() {
    global $wpdb, $wpGoogleList_db_version;
    ?>
    <div class="updated">
        <p>"<?= $wpdb->prefix ?>" Upgraded to version: <?= get_site_option('wpGoogleList_db_version') ?> > <?= $wpGoogleList_db_version ?></p>
    </div>
    <?php
}

/**
CREATE TABLE wp_googlelist (
id mediumint(9) NOT NULL AUTO_INCREMENT,
created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
`name` tinytext NOT NULL,
`key` tinytext NOT NULL,
text text NOT NULL,
PRIMARY KEY id (id)
)
 */


/**
 * Install Section
 */
function wpGoogleListInstall()
{
    global $wpdb, $wpGoogleList_db_version;


    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "googlelist";
    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      `name` tinytext NOT NULL,
      `key` tinytext NOT NULL,
      text text NOT NULL,
      PRIMARY KEY id (id)
) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_action( 'admin_notices', 'wpGoogleListUpgradeNotice' );
    update_option("wpGoogleList_db_version", $wpGoogleList_db_version);

}



function wpGoogleListDBcheck()
{
    global $wpGoogleList_db_version;
    if (get_site_option('wpGoogleList_db_version') != $wpGoogleList_db_version) {
        add_action( 'admin_notices', 'wpGoogleListInstallNotice' );
        wpGoogleListInstall();
    }
}

register_activation_hook(__FILE__, 'wpGoogleListInstall');
add_action('plugins_loaded', 'wpGoogleListDBcheck');


function wpGoogleListShortTag( $atts ) {
    $a = shortcode_atts( array(
        'name' => '',
    ), $atts );
    global $wpdb; // this is how you get access to the database
    $table_name = $wpdb->prefix . "googlelist";
    $rows = $wpdb->get_results($wpdb->prepare(  "SELECT * FROM `$table_name` WHERE `name` = %s limit 1",$a['name']), ARRAY_A );
    return $rows[0]['text'];
}
add_shortcode( 'google_list', 'wpGoogleListShortTag' );