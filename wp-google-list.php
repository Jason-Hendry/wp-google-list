<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


function wpGoogleListToClass(&$str, $idx = null)
{
    $str = preg_replace('/ +/', '-', preg_replace('/[^A-Za-z\ 0-9]/', '', trim($str)));
    if ($idx === null) {
        return $str;
    }
}

function wpGoogleListGenerateHTML()
{
    file_put_contents('/tmp/flavours.csv', file_get_contents("https://docs.google.com/spreadsheet/ccc?key=1PhFb6F_XSZL1C5U8gOUz2P5LtlPfvjLfM2OlK_2BmqU&output=csv"));

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
    $productHTML = implode("\n", $products);

    $awardHTML = "";
    foreach ($allAwards as $class => $name) {
        if (!$class) {
            continue;
        }
        $awardHTML .= "<label><input type=\"checkbox\" name=\"$class\" value=\"$class\">$name</label><br>\n";

    }

    ob_start();
    ?>
    <div class="product-finder-left">
        <strong>Type</strong><br>
        <label><input type="checkbox" name="Sorbet" value="Sorbet">Sorbet</label><br>
        <label><input type="checkbox" name="Gelato" value="Gelato">Gelato</label><br>
        <label><input type="checkbox" name="Yoghurt" value="Yoghurt">Yoghurt</label><br>
        <strong>Awards</strong><br>
        <?= $awardHTML ?>
        <strong>Dietary</strong><br>
        <label><input type="checkbox" name="Vegan" value="Vegan">Vegan</label><br>
        <label><input type="checkbox" name="Dairy-Free" value="Dairy-Free">Dairy Free</label><br>
        <label><input type="checkbox" name="Gluten-Free" value="Gluten-Free">Gluten Free</label><br>
        <label><input type="checkbox" name="Fat-Free" value="Fat-Free">Fat Free</label><br>
        <label><input type="checkbox" name="Contains-Egg" value="Contains-Egg">Contains Egg</label><br>
    </div>
    <div class="product-finder-right">
        <ul>
            <?= $productHTML ?>
        </ul>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <script type="application/javascript">
        $(document).ready(function () {
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
            })
        })
    </script>
    <?php
    $pageData = ob_get_clean();
}

function wpGoogleListGenerateAdminPage() {
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    ?>
    <form>
        <input type="text" name="documentKey">
        <input type="submit">

    </form>
    <?php
}

add_action('admin_menu', 'wpGoogleListGenerateAdminMenu');

function wpGoogleListGenerateAdminMenu() {
    add_options_page('Sheet to HTML', 'Sheet to HTML', 'manage_options', 'wp-google-list', 'wpGoogleListGenerateAdminPage');
}