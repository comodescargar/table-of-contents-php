<?php

/**
 * Created by PhpStorm.
 * User: hector.gamez
 * Date: 20/09/2016
 * Time: 10:40
 */
define('TOC_POSITION_BEFORE_FIRST_HEADING', 1);
define('TOC_POSITION_TOP', 2);
define('TOC_POSITION_BOTTOM', 3);
define('TOC_POSITION_AFTER_FIRST_HEADING', 4);
define('TOC_MIN_START', 2);
define('TOC_MAX_START', 10);
define('TOC_SMOOTH_SCROLL_OFFSET', 30);
define('TOC_WRAPPING_NONE', 0);
define('TOC_WRAPPING_LEFT', 1);
define('TOC_WRAPPING_RIGHT', 2);
define('TOC_THEME_GREY', 1);
define('TOC_THEME_LIGHT_BLUE', 2);
define('TOC_THEME_WHITE', 3);
define('TOC_THEME_BLACK', 4);
define('TOC_THEME_TRANSPARENT', 99);
define('TOC_THEME_CUSTOM', 100);
define('TOC_DEFAULT_BACKGROUND_COLOUR', '#f9f9f9');
define('TOC_DEFAULT_BORDER_COLOUR', '#aaaaaa');
define('TOC_DEFAULT_TITLE_COLOUR', '#');
define('TOC_DEFAULT_LINKS_COLOUR', '#');
define('TOC_DEFAULT_LINKS_HOVER_COLOUR', '#');
define('TOC_DEFAULT_LINKS_VISITED_COLOUR', '#');

class Toc {
  private $options;
  private $collision_collector;    // keeps a track of used anchors for collision detecting
  public $content;

  function __construct($content) {
    $this->path = __FILE__;
    $this->show_toc = TRUE;
    $this->exclude_post_types = array('attachment', 'revision', 'nav_menu_item', 'safecss');
    $this->collision_collector = array();

    // get options
    $defaults = array(        // default options
      'fragment_prefix' => 'i',
      'position' => TOC_POSITION_BEFORE_FIRST_HEADING,
      'start' => 4,
      'show_heading_text' => TRUE,
      'heading_text' => 'Tabla de contenidos',
      'show_heirarchy' => TRUE,
      'ordered_list' => TRUE,
      'smooth_scroll' => FALSE,
      'smooth_scroll_offset' => TOC_SMOOTH_SCROLL_OFFSET,
      'visibility' => TRUE,
      'visibility_show' => 'show',
      'visibility_hide' => 'hide',
      'visibility_hide_by_default' => FALSE,
      'width' => 'Auto',
      'width_custom' => '275',
      'width_custom_units' => 'px',
      'wrapping' => TOC_WRAPPING_NONE,
      'font_size' => '95',
      'font_size_units' => '%',
      'theme' => TOC_THEME_GREY,
      'custom_background_colour' => TOC_DEFAULT_BACKGROUND_COLOUR,
      'custom_border_colour' => TOC_DEFAULT_BORDER_COLOUR,
      'custom_title_colour' => TOC_DEFAULT_TITLE_COLOUR,
      'custom_links_colour' => TOC_DEFAULT_LINKS_COLOUR,
      'custom_links_hover_colour' => TOC_DEFAULT_LINKS_HOVER_COLOUR,
      'custom_links_visited_colour' => TOC_DEFAULT_LINKS_VISITED_COLOUR,
      'lowercase' => FALSE,
      'hyphenate' => FALSE,
      'bullet_spacing' => FALSE,
      'include_homepage' => FALSE,
      'exclude_css' => FALSE,
      'exclude' => '',
      'heading_levels' => array('1', '2', '3', '4', '5', '6'),
      'restrict_path' => '',
      'css_container_class' => ''
    );

    $options = $defaults;
    $this->options = $options;
    $this->content = $this->get_toc($content);
  }

  private function build_hierarchy(&$matches) {
    $current_depth = 100;    // headings can't be larger than h6 but 100 as a default to be sure
    $html = '';
    $numbered_items = array();
    $numbered_items_min = NULL;

    // reset the internal collision collection
    $this->collision_collector = array();

    // find the minimum heading to establish our baseline
    for ($i = 0; $i < count($matches); $i++) {
      if ($current_depth > $matches[$i][2]) {
        $current_depth = (int) $matches[$i][2];
      }
    }

    $numbered_items[$current_depth] = 0;
    $numbered_items_min = $current_depth;

    for ($i = 0; $i < count($matches); $i++) {

      if ($current_depth == (int) $matches[$i][2]) {
        $html .= '<li>';
      }

      // start lists
      if ($current_depth != (int) $matches[$i][2]) {
        for ($current_depth; $current_depth < (int) $matches[$i][2]; $current_depth++) {
          $numbered_items[$current_depth + 1] = 0;
          $html .= '<ul><li>';
        }
      }

      // list item
      if (in_array($matches[$i][2], $this->options['heading_levels'])) {
        $html .= '<a href="#' . $this->url_anchor_target($matches[$i][0]) . '"  title="ir a '.strip_tags($matches[$i][0]).'">';
        if ($this->options['ordered_list']) {
          // attach leading numbers when lower in hierarchy
          $html .= '<span class="toc_number toc_depth_' . ($current_depth - $numbered_items_min + 1) . '">';
          for ($j = $numbered_items_min; $j < $current_depth; $j++) {
            $number = ($numbered_items[$j]) ? $numbered_items[$j] : 0;
            $html .= $number . '.';
          }

          $html .= ($numbered_items[$current_depth] + 1) . '</span> ';
          $numbered_items[$current_depth]++;
        }
        $html .= strip_tags($matches[$i][0]) . '</a>';
      }


      // end lists
      if ($i != count($matches) - 1) {
        if ($current_depth > (int) $matches[$i + 1][2]) {
          for ($current_depth; $current_depth > (int) $matches[$i + 1][2]; $current_depth--) {
            $html .= '</li></ul>';
            $numbered_items[$current_depth] = 0;
          }
        }

        if ($current_depth == (int) @$matches[$i + 1][2]) {
          $html .= '</li>';
        }
      }
      else {
        // this is the last item, make sure we close off all tags
        for ($current_depth; $current_depth >= $numbered_items_min; $current_depth--) {
          $html .= '</li>';
          if ($current_depth != $numbered_items_min) {
            $html .= '</ul>';
          }
        }
      }
    }

    return $html;
  }

  /**
   * Returns a clean url to be used as the destination anchor target
   */
  private function url_anchor_target($title) {
    $return = FALSE;

    if ($title) {
      $return = trim(strip_tags($title));

      // convert accented characters to ASCII
      $return = StringUtils::spanish_stemmer($return);

      // replace newlines with spaces (eg when headings are split over multiple lines)
      $return = str_replace(array("\r", "\n", "\n\r", "\r\n"), ' ', $return);

      // remove &amp;
      $return = str_replace('&amp;', '', $return);

      // remove non alphanumeric chars
      $return = preg_replace('/[^a-zA-Z0-9 \-_]*/', '', $return);

      // convert spaces to _
      $return = str_replace(
        array('  ', ' '),
        '_',
        $return
      );

      // remove trailing - and _
      $return = rtrim($return, '-_');

      // lowercase everything?
      $return = strtolower($return);

      // hyphenate?
      $return = str_replace('_', '-', $return);
      $return = str_replace('--', '-', $return);
    }

    if (array_key_exists($return, $this->collision_collector)) {
      $this->collision_collector[$return]++;
      $return .= '-' . $this->collision_collector[$return];
    }
    else {
      $this->collision_collector[$return] = 1;
    }

    return $return;
  }

  /**
   * Returns a string with all items from the $find array replaced with their matching
   * items in the $replace array.  This does a one to one replacement (rather than
   * globally).
   *
   * This function is multibyte safe.
   *
   * $find and $replace are arrays, $string is the haystack.  All variables are
   * passed by reference.
   */
  private function mb_find_replace(&$find = FALSE, &$replace = FALSE, &$string = '') {
    if (is_array($find) && is_array($replace) && $string) {
      // check if multibyte strings are supported
      if (function_exists('mb_strpos')) {
        for ($i = 0; $i < count($find); $i++) {
          $string =
            mb_substr($string, 0, mb_strpos($string, $find[$i])) .    // everything befor $find
            $replace[$i] .                                                // its replacement
            mb_substr($string, mb_strpos($string, $find[$i]) + mb_strlen($find[$i]))    // everything after $find
          ;
        }
      }
      else {
        for ($i = 0; $i < count($find); $i++) {
          $string = substr_replace(
            $string,
            $replace[$i],
            strpos($string, $find[$i]),
            strlen($find[$i])
          );
        }
      }
    }

    return $string;
  }

  /**
   * This function extracts headings from the html formatted $content.  It will pull out
   * only the required headings as specified in the options.  For all qualifying headings,
   * this function populates the $find and $replace arrays (both passed by reference)
   * with what to search and replace with.
   *
   * Returns a html formatted string of list items for each qualifying heading.  This
   * is everything between and NOT including <ul> and </ul>
   */
  public function extract_headings(&$find, &$replace, $content = '') {
    $matches = array();
    $anchor = '';
    $items = FALSE;

    // reset the internal collision collection as the_content may have been triggered elsewhere
    // eg by themes or other plugins that need to read in content such as metadata fields in
    // the head html tag, or to provide descriptions to twitter/facebook
    $this->collision_collector = array();

    if (is_array($find) && is_array($replace) && $content) {
      // get all headings
      // the html spec allows for a maximum of 6 heading depths
      if (preg_match_all('/(<h([1-6]{1})[^>]*>).*<\/h\2>/msuU', $content, $matches, PREG_SET_ORDER)) {

        // remove undesired headings (if any) as defined by heading_levels

        $new_matches = array();
        for ($i = 0; $i < count($matches); $i++) {
          if (in_array($matches[$i][2], $this->options["heading_levels"])) {
            $new_matches[] = $matches[$i];
          }
        }
        $matches = $new_matches;

        // remove empty headings
        $new_matches = array();
        for ($i = 0; $i < count($matches); $i++) {
          if (trim(strip_tags($matches[$i][0])) != FALSE) {
            $new_matches[] = $matches[$i];
          }
        }
        if (count($matches) != count($new_matches)) {
          $matches = $new_matches;
        }

        // check minimum number of headings
        if (count($matches) >= $this->options['start']) {

          for ($i = 0; $i < count($matches); $i++) {
            // get anchor and add to find and replace arrays
            $anchor = $this->url_anchor_target($matches[$i][0]);
            $find[] = $matches[$i][0];
            $replace[] = str_replace(
              array(
                $matches[$i][1],                // start of heading
                '</h' . $matches[$i][2] . '>'    // end of heading
              ),
              array(
                $matches[$i][1] . '<span id="' . $anchor . '">',
                '</span></h' . $matches[$i][2] . '>'
              ),
              $matches[$i][0]
            );

          }

          // build a hierarchical toc?
          // we could have tested for $items but that var can be quite large in some cases
          $items = $this->build_hierarchy($matches);

        }
      }
    }

    return $items;
  }

  /**
   * Tries to convert $string into a valid hex colour.
   * Returns $default if $string is not a hex value, otherwise returns verified hex.
   */
  private function hex_value($string = '', $default = '#') {
    $return = $default;

    if ($string) {
      // strip out non hex chars
      $return = preg_replace('/[^a-fA-F0-9]*/', '', $string);

      switch (strlen($return)) {
        case 3:    // do next
        case 6:
          $return = '#' . $return;
          break;

        default:
          if (strlen($return) > 6) {
            $return = '#' . substr($return, 0, 6);
          }    // if > 6 chars, then take the first 6
          elseif (strlen($return) > 3 && strlen($return) < 6) {
            $return = '#' . substr($return, 0, 3);
          }    // if between 3 and 6, then take first 3
          else {
            $return = $default;
          }                        // not valid, return $default
      }
    }

    return $return;
  }

  function get_toc($content) {
    $custom_toc_position = strpos($content, '<!--TOC-->');
    $find = $replace = array();
    $items = $this->extract_headings($find, $replace, $content);

    if ($items) {
      // do we display the toc within the content or has the user opted
      // to only show it in the widget?  if so, then we still need to
      // make the find/replace call to insert the anchors
      if ($this->options['show_toc_in_widget_only']) {
        $content = $this->mb_find_replace($find, $replace, $content);
      }
      else {

        // wrapping css classes
        switch ($this->options['wrapping']) {
          case TOC_WRAPPING_LEFT:
            $css_classes .= ' toc_wrap_left';
            break;

          case TOC_WRAPPING_RIGHT:
            $css_classes .= ' toc_wrap_right';
            break;

          case TOC_WRAPPING_NONE:
          default:
            // do nothing
        }

        // colour themes
        switch ($this->options['theme']) {
          case TOC_THEME_LIGHT_BLUE:
            $css_classes .= ' toc_light_blue';
            break;

          case TOC_THEME_WHITE:
            $css_classes .= ' toc_white';
            break;

          case TOC_THEME_BLACK:
            $css_classes .= ' toc_black';
            break;

          case TOC_THEME_TRANSPARENT:
            $css_classes .= ' toc_transparent';
            break;

          case TOC_THEME_GREY:
          default:
            // do nothing
        }

        // bullets?
        if ($this->options['bullet_spacing']) {
          $css_classes .= ' have_bullets';
        }
        else {
          $css_classes .= ' no_bullets';
        }

        if ($this->options['css_container_class']) {
          $css_classes .= ' ' . $this->options['css_container_class'];
        }

        $css_classes = trim($css_classes);

        // an empty class="" is invalid markup!
        if (!$css_classes) {
          $css_classes = ' ';
        }

        // add container, toc title and list items
        $html = '<div id="toc_container" class="' . $css_classes . '">';
        if ($this->options['show_heading_text']) {
          $toc_title = $this->options['heading_text'];
          $html .= '<p class="toc_title">' . htmlentities($toc_title, ENT_COMPAT, 'UTF-8') . '</p>';
        }
        $html .= '<ul class="toc_list">' . $items . '</ul></div>' . "\n";

        if ($custom_toc_position !== FALSE) {
          $find[] = '<!--TOC-->';
          $replace[] = $html;
          $content = $this->mb_find_replace($find, $replace, $content);
        }
        else {
          if (count($find) > 0) {
            switch ($this->options['position']) {
              case TOC_POSITION_TOP:
                $content = $html . $this->mb_find_replace($find, $replace, $content);
                break;

              case TOC_POSITION_BOTTOM:
                $content = $this->mb_find_replace($find, $replace, $content) . $html;
                break;

              case TOC_POSITION_AFTER_FIRST_HEADING:
                $replace[0] = $replace[0] . $html;
                $content = $this->mb_find_replace($find, $replace, $content);
                break;

              case TOC_POSITION_BEFORE_FIRST_HEADING:
              default:
                $replace[0] = $html . $replace[0];
                $content = $this->mb_find_replace($find, $replace, $content);
            }
          }
        }
      }
    }
    else {
      // remove <!--TOC--> (inserted from shortcode) from content
      $content = str_replace('<!--TOC-->', '', $content);
    }

    return $content;
  }
}