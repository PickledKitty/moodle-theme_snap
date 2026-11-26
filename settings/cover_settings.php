<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;// Main settings.
use theme_snap\admin_setting_configradiobuttons;

$snapsettings = new admin_settingpage('themesnapcoverdisplay', get_string('coverdisplay', 'theme_snap'));

$name = 'theme_snap/cover_image';
$heading = new \core\lang_string('poster', 'theme_snap');
$description = '';
$setting = new admin_setting_heading($name, $heading, $description);
$snapsettings->add($setting);

// Cover image file setting.
$name = 'theme_snap/poster';
$title = new \core\lang_string('poster', 'theme_snap');
$description = new \core\lang_string('posterdesc', 'theme_snap');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'poster', 0, $opts);
$setting->set_updatedcallback('theme_snap_process_site_coverimage');
$snapsettings->add($setting);

// The page header band's gradient, for pages without a cover image. Empty: the brand colour to a lighter shade of it.
$name = 'theme_snap/header_gradient_heading';
$heading = new \core\lang_string('headergradient', 'theme_snap');
$description = new \core\lang_string('headergradientdesc', 'theme_snap');
$snapsettings->add(new admin_setting_heading($name, $heading, $description));

foreach (['start', 'end'] as $end) {
    $name = 'theme_snap/headergradient' . $end;
    $title = new \core\lang_string('headergradient' . $end, 'theme_snap');
    $description = new \core\lang_string('headergradient' . $end . 'desc', 'theme_snap');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '', null, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);
}

// Cover carousel.
$name = 'theme_snap/cover_carousel_heading';
$heading = new \core\lang_string('covercarousel', 'theme_snap');
$description = new \core\lang_string('covercarouseldescription', 'theme_snap');
$setting = new admin_setting_heading($name, $heading, $description);
$snapsettings->add($setting);

$name = 'theme_snap/cover_carousel';
$title = new \core\lang_string('covercarouselon', 'theme_snap');
$description = '';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Hero: tall photos with the title over them. Banner: each slide the shape of its image, for images that carry
// their own text.
$snapsettings->add(new admin_setting_configselect('theme_snap/cover_carousel_style',
    new \core\lang_string('covercarouselstyle', 'theme_snap'),
    new \core\lang_string('covercarouselstyle_desc', 'theme_snap'), 'hero', [
        'hero' => new \core\lang_string('covercarouselstylehero', 'theme_snap'),
        'banner' => new \core\lang_string('covercarouselstylebanner', 'theme_snap'),
    ]));

$name = 'theme_snap/slide_one_image';
$title = new \core\lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_one_image', 0, $opts);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_image';
$title = new \core\lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_two_image', 0, $opts);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_image';
$title = new \core\lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_three_image', 0, $opts);
$snapsettings->add($setting);

// Optional images for small screens (below 768px), shown there instead: a narrower banner, or a portrait crop.
$snapsettings->add(new admin_setting_configstoredfile('theme_snap/slide_one_image_small',
    new \core\lang_string('coverimagesmall', 'theme_snap'), new \core\lang_string('coverimagesmall_desc', 'theme_snap'),
    'slide_one_image_small', 0, $opts));

$snapsettings->add(new admin_setting_configstoredfile('theme_snap/slide_two_image_small',
    new \core\lang_string('coverimagesmall', 'theme_snap'), new \core\lang_string('coverimagesmall_desc', 'theme_snap'),
    'slide_two_image_small', 0, $opts));

$snapsettings->add(new admin_setting_configstoredfile('theme_snap/slide_three_image_small',
    new \core\lang_string('coverimagesmall', 'theme_snap'), new \core\lang_string('coverimagesmall_desc', 'theme_snap'),
    'slide_three_image_small', 0, $opts));

$name = 'theme_snap/slide_one_title';
$title = new \core\lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 60);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_title';
$title = new \core\lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 60);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_title';
$title = new \core\lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 60);
$snapsettings->add($setting);

$name = 'theme_snap/slide_one_subtitle';
$title = new \core\lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 130);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_subtitle';
$title = new \core\lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 130);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_subtitle';
$title = new \core\lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, $default, PARAM_RAW_TRIMMED, null, 130);
$snapsettings->add($setting);

$settings->add($snapsettings);
