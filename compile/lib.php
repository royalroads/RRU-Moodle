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

/**
 * A library of helper functions used by Course Compile and its modules
 *
 * 2014-04-25
 * @package      local_compile
 * @author       Gerald Albion, Carlos Chiarella, Steve Beaudry
 * @copyright    2014 Royal Roads University
 * @license      http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('HTTP_STATUS_OK', 200);

define('CHARACTERS_TO_SKIP', 12); // Number of characters to skip before finding the next double quotation.
/**
 * Add a "Compile" menu link to the Course Admin block as the top link.
 *
 * @author Gerald Albion
 * date 2014-10-31
 * @copyright 2014 Royal Roads University
 * @param object $settingsnav Main navigation object.
 * @param object $context Course context.
 */
function local_compile_extends_settings_navigation($settingsnav, $context) {

    // Context must be course.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return;
    }

    // Must be in a valid course: Cannot be course id 0.
    if ($context->instanceid == 0) {
        return;
    }

    // Must be in a valid course: Course must be retrievable.
    if (!($course = get_course($context->instanceid))) {
        return;
    }

    // Must be enrolled or otherwise allowed to view the course.
    if (!(is_enrolled($context) || is_viewing($context))) {
        return;
    }

    // Must have a course admin menu in which to add link.
    if (!($coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE))) {
        return;
    }

    // Good to go.  Build the menu item.
    $url = new moodle_url('/local/compile/list_modules.php', array('id' => $course->id));
    $newnode = navigation_node::create(
        get_string('menucaption', 'local_compile'),
        $url,
        navigation_node::NODETYPE_LEAF,
        'compile',
        'compile',
        new pix_icon('i/settings', '')
    );

    // We want to put this link at the top: find the existing top (first) node.
    $firstnode = $coursenode->get_children_key_list()[0];

    // Add the menu item to the menu, before the first node.
    $coursenode->add_node($newnode, $firstnode);
}

/**
 * Read the html file to find images tags.
 * If they have been uploaded to the server a symboic link to the image is created and
 * replace the old src code
 *
 * @author Carlos Chiarella, Gerald Albion
 * date 2014-04-09
 * @global object $CFG Moodle object
 * @param string $htmpage contains the html code for module
 * @param string $modmodname module name
 * @param int $modid module id
 * @param string $scriptname name of the moodle php file that normally serves this image (typ. pluginfile.php or file.php)
 * @return string $newhtmlpage contains new html code for the module
 */
function compile_update_image_source($htmlpage, $modmodname, $modid, $scriptname='pluginfile.php' ) {
    global $CFG;

    $context = context_module::instance($modid);
    $newhtmlpage = '';

    // String to find.
    $findme = ' src="'.$CFG->wwwroot.'/'.$scriptname.'/';
    $pos = 1;

    // Only do the search if there is html content.
    if (strlen($htmlpage) > 0) {
        $pos = strpos($htmlpage, $findme, $pos + 1);

        // If the string to find is not there return the original string.
        if ($pos === false) {
            $newhtmlpage = $htmlpage;
            return $newhtmlpage;
        }
        $pos = 1;
        $newhtmlpage = $htmlpage;
        do {
            $pos = strpos($htmlpage, $findme, $pos + 1);
            $pos2 = strpos($htmlpage, '"', $pos + CHARACTERS_TO_SKIP);

            if ($pos > 0 && $pos2 > 0) {
                $contextid = $context->id;
                $component = 'mod_'. $modmodname;

                // Handle exceptions.
                if ($modmodname == 'book') {
                    $filearea = 'chapter';
                } else if ($modmodname == 'forum') {
                    $filearea = 'intro';
                } else {
                    $filearea = 'content';
                }

                // Extract the file name from the img src URL.
                $scratch = substr($htmlpage, $pos, $pos2 - $pos);
                $pos3 = strrpos($scratch, '/') + 1;
                $filename = urldecode(substr($scratch, $pos3));
                $imageinfo = compile_get_image_info($contextid, $component, $filearea, $filename);
                // If we couldn't get image info from the current context, try and extract it from the context in the current URL.
                if (false === $imageinfo) {
                    $oldvalue = substr($htmlpage, $pos, ($pos2 - $pos) + 1);
                    $pos0 = strpos($oldvalue, '"') + 1; // Start of text after first quote.
                    $posq = strpos($oldvalue, '?');
                    if (false === $posq) {
                        $relativepath = substr($oldvalue, $pos0, -1); // End at second last char, which is the last quote.
                    } else {
                        $relativepath = substr($oldvalue, $pos0, ($posq - $pos0)); // End just before the questionmark if present.
                    }

                    // Extract path components.
                    $args = explode('/', ltrim($relativepath, '/'));
                    do {
                        $junk = array_shift($args);
                        if ($junk == $scriptname) {
                            break;
                        }
                    } while (count($args) > 0);
                    $contextid = $args[0];
                    $component = $args[1];
                    $filearea  = $args[2];
                    $filename  = $args[count($args) - 1];

                    // We have what might be the real image info, try retrieving it again.
                    $imageinfo = compile_get_image_info($contextid, $component, $filearea, $filename);
                }

                // If we have a valid image info, make the symbolic link and use that in the HTML.
                if (false !== $imageinfo) {
                    // Find folders for the image from the contenthash field.
                    $folder1 = substr($imageinfo->contenthash, 0, 2);
                    $folder2 = substr($imageinfo->contenthash, 2, 2);

                    // Create the symbolic link if it does not exist.
                    $target = $CFG->dataroot. '/filedir/'. $folder1. '/'. $folder2. '/'. $imageinfo->contenthash;
                    $link   = $CFG->dirroot.'/local/compile/srcimages/'.  $imageinfo->filename;
                    if (!file_exists($link)) { // if the symlink is not already there ...
                        if (file_exists($target)) { // If the link target exists ...
                            $linkresult = symlink($target, $link); // Create a symlink.
                        }
                    }

                    // Replace the path for the image with the symbolic links.
                    $oldvalue = substr($htmlpage, $pos, ($pos2 - $pos) + 1);
                    $newvalue = '<img src="'.$CFG->wwwroot.'/local/compile/srcimages/'.$imageinfo->filename.'" ';
                    $newhtmlpage = str_replace($oldvalue, $newvalue, $newhtmlpage);
                }

            }
        } while ($pos > 0);
    } else {
        $newhtmlpage = $htmlpage;
    }
    return $newhtmlpage;
}

/**
 * Read the files table to retrieve information about a specific image.
 * You may or may not have a filename but have other information about the file.
 * The parameters that are required are: context id, component and the filearea.
 *
 * @author Carlos Chiarella, Gerald Albion
 * date 2014-03-21
 * @global object $DB Moodle database object
 * @param int $contextid context id
 * @param string $component component
 * @param string $filearea filearea
 * @param string $filename optional partial filename in case of more than one result
 * @return object $result for success, false for failure
 */
function compile_get_image_info($contextid, $component, $filearea, $filename="") {
    global $DB;
    $params   = array ("contextid" => $contextid,
                    "component" => $component,
                    "filearea"  => $filearea);

    $criteria = "    contextid = :contextid
                 AND component = :component
                 AND filearea  = :filearea
                 AND mimetype IS NOT NULL ";

    if (strlen($filename) > 0 ) {
        $criteria .= " AND filename LIKE '".$filename."%'";
    }

    try {
        $result = $DB->get_record_select("files", $criteria, $params);
    } catch (dml_exception $e) {
        die("Course Compile: DML Exception {$e->getMessage()} reading files table");
    }
    return $result;
}

/**
 * Produces an HTML header for compiled output.
 *
 * @author Gerald Albion
 * date 2014-11-12
 * @return string The header.
 */
function compile_create_pdf_header() {
    $pdfheader  = '<!DOCTYPE html PUBLIC "-// W3C// DTD XHTML 1.0 Strict// EN"'
        . ' "http:// www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
        . "\n";
    $pdfheader .= "<HTML><BODY>\n";
    $pdfheader .= '<link rel="stylesheet" type="text/css" href="pdf.css" media="screen" />' . "\n";
    return $pdfheader;
}

/**
 * Remove the first instance (within a limited number of characters) of a given html element
 * A helper function
 *
 * @author Gerald Albion
 * date 2014-02-25
 * @param string $haystack the string from which to remove the element
 * @param string $opentag the opening tag of the element
 * @param string $closetag the closing tag of the element
 * @param int $limit the element must start at this character offset or less to be considered
 * @return string the modified string
 */
function compile_strip_first_tag($haystack, $opentag, $closetag, $limit=0) {
    $hpos = strpos($haystack, $opentag);
    if ($hpos !== false) {
        if ($limit == 0 || $hpos < $limit) {
            $hend = strpos($haystack, $closetag);
            if ($hend !== false) {
                return(substr_replace($haystack, '', $hpos, $hend - $hpos + strlen($closetag)));
            }
        }
    }
    return $haystack;
}

/**
 * Close unclosed <div> elements in $html
 * will not attempt to balance divs if there are more closes than opens
 *
 * @author Gerald Albion
 * date 2014-02-25
 * @param string $html The HTML string to work on
 * @return string the (hopefully) <div>-balanced HTML result
 */
function compile_fix_unbalanced_divs($html) {
    $divopencount = substr_count($html, '<div');
    $divclosecount = substr_count($html, '</div>');
    for ($i = $divclosecount; $i < $divopencount; $i++) {
        $html .= "</div>\n";
    }
    return $html;
}

/**
 * Sanitize $html by removing links to admin pages
 * currently supports: <a>submissions.php, <form>/admin/, <div class="alert">, <form>Group selector
 *
 * @author Gerald Albion
 * date 2014-03-25
 * @global $CFG The Moodle configuration object
 * @param string $html The HTML string to work on
 * @return string the sanitized HTML result
 */
function compile_remove_admin_links($html) {
    global $CFG;

    // Remove link to instructor view of submissions.
    $html = compile_remove_tag($html, '<a href="'.$CFG->wwwroot.'/mod/assignment/submissions.php', '</a>');

    // Targets the "View the Assignment Upgrade Tool" button.
    $html = compile_remove_tag($html, '<form method="post" action="'.$CFG->wwwroot.'/admin/', '</form>');

    // Alerts.
    $html = compile_remove_tag($html, '<div class="alert', '</div>');

    // Group selector.
    $html = compile_remove_tag($html, '<form method="get" action="'.$CFG->wwwroot.'/mod/assignment/view.php"', '</form>');

    // Remove all form <input> elements as these won't be useful in PDF.
    $html = compile_remove_tag($html, '<input', '>'); // Use > for self closing tags.

    // Remove Skype Click-To-Call Image.
    $html = compile_remove_tag($html, '<img class="skype_c2c_logo_img" src="resource:// ', '>');

    // Remove TeX filter output; PDF can't handle it.
    $html = compile_remove_tag($html, '<img class="texrender"', '>');

    return $html;
}

/**
 * Remove all instances of an HTML tag starting with $starttag and closing with $closetag from $html
 * Helper function for compile_remove_admin_links()
 *
 * Note that this does not do the same thing as compile_strip_first_tag()
 * Note that if the tag has attributes, $starttag should NOT include a ">"
 *
 * @author Gerald Albion
 * date 2014-03-25
 * @param string $html The input HTML to sanitize
 * @param string $starttag The starting tag (or partial tag) to remove
 * @param string $closetag The closing tag which delimits then end of text to remove
 * @return string the sanitized HTML result
 */
function compile_remove_tag($html, $starttag, $closetag) {
    $pos = strpos($html, $starttag);
    while (false !== $pos) {
        $pos2   = strpos($html, $closetag, $pos + 1);
        $doomed = substr($html, $pos, $pos2 - $pos + strlen($closetag));
        $html   = str_replace($doomed, '', $html);
        $pos    = strpos($html, $starttag, $pos + 1);
    }
    return $html;
}

/**
 * Helper function for compile_anchors.  Does the provided file extension imply
 * a file that should be downloaded rather than viewed inline?
 *
 * @author Gerald Albion
 * date 2014-04-14
 * @param string $ext
 * @return boolean TRUE if the extension is likely an attachable or downloadable file resource
 */
function compile_ext_indicates_file($ext) {
    $ext = trim(strtolower($ext));

    if ('' == $ext) { // Empty extension unlikely to be a file.
        return false;
    }

    // Extensions that are typically preprocessed by the webserver for browser output
    // are highly unlikely to be downloads.
    $notlikely = array('php', 'html', 'htm', 'asp', 'aspx', 'jsp', 'shtml', 'shtm', 'sht');
    if (in_array($ext, $notlikely)) { // Extension indicates a web page resource rather than a file.
        return false;
    }

    // Not known, probably a download.
    return true;
}

/**
 * Unlink Anchors
 *
 * @author Gerald Albion
 * date 2014-08-06
 * @param string $html the HTML to convert
 * @return string The converted HTML
 */
function compile_unlink_anchors($html) {
    $starttag = '<a ';
    $endtag = '>';
    $pos = strpos($html, $starttag);
    while (false !== $pos) {
        // Isolate <a> tag.
        $pos2   = strpos($html, $endtag, $pos + 1);
        $doomed = substr($html, $pos, $pos2 - $pos + strlen($endtag)); // To be replaced.

        // Figure out file type.
        $s    = explode('href="', $doomed);
        $href = $s[1];
        $href = trim($href, '">');

        if (count($s) < 2) { // Bad anchor - skip it.
            $pos    = strpos($html, $starttag, $pos + 1);
            continue;
        }

        $urlparts = parse_url($href);

        if (array_key_exists('path', $urlparts)) {
            $path     = pathinfo($urlparts['path']);
        } else {
            $pos    = strpos($html, $starttag, $pos + 1);
            continue;
        }
        $filename = $path['filename'];
        if (array_key_exists('extension', $path)) {
            $ext  = $path['extension'];
            $filename .= '.' . $path['extension'];
        } else {
            $ext  = '';
        }

        // Skip non-downloads.
        if (!compile_ext_indicates_file($ext)) {
            $pos    = strpos($html, $starttag, $pos + 1);
            continue;
        }

        // Update the HTML.
        $html = str_replace($doomed, '', $html);
        $pos  = strpos($html, $starttag, $pos + 1);
    }
    return $html;
}


/**
 * Checks if the URL has the same domain as the local Moodle installation
 * or if the URL domain is absent. Helper for compile_remove_blacklisted_links()
 *
 * @author Gerald Albion
 * @global $CFG The Moodle configuration object
 * @param string $url URL to check
 * @return boolean TRUE if the URL domain is local or absent
 */
function compile_domain_is_local($url) {
    global $CFG;
    $thisdomain = parse_url($url, PHP_URL_HOST);

    // Check for no host.
    if ('' == $thisdomain) {
        return false;
    }

    // Check $CFG->wwwroot (current domain).
    $rootdomain = parse_url($CFG->wwwroot, PHP_URL_HOST);
    if ($rootdomain == $thisdomain) {
        return true;
    }
    return false;
}

/**
 * Checks a qualified URL against a list of blacklisted domains.
 * Helper for compile_remove_blacklisted_links()
 *
 * @author Gerald Albion
 * @param string $url URL to check
 * @return boolean TRUE if the URL domain is blacklisted or absent.
 */
function compile_domain_is_blacklisted($url) {
    $thisdomain = parse_url($url, PHP_URL_HOST);

    // Check for no host.
    if ('' == $thisdomain) {
        return false;
    }

    $blacklist = get_config('local_compile', 'blacklistresources');

    // Check blacklist.
    if (!$blacklist) {
        return false;
    }

    // Check the supplied URL domain against each blacklisted domain.
    $domains = explode(',', $blacklist);
    foreach ($domains as $domain) {
        if ($domain == $thisdomain) {
            return true;
        }
    }
    return false;
}

/**
 * Check if an image URL "exists" (i.e. is retrievable)
 *
 * @author Gerald Albion
 * @param string $url the URL to check
 * @return boolean true if the URL is okay
 */
function compile_image_is_retrievable($url) {
    $hdrs = @get_headers($url);
    if (false === $hdrs) {
        return false;
    }
    $laststatus = 0;
    $lastcontent = '';
    // Loop through all headers to find the LAST occurrences of status and content-type.
    foreach ($hdrs as $hdr) {
        $parts = explode(' ', $hdr);
        if (($parts[0] == 'HTTP/1.0') || ($parts[0] == 'HTTP/1.1')) {
            $laststatus = $parts[1];
        }
        if ($parts[0] == 'Content-Type:') {
            $lastcontent = $parts[1];
        }
    }

    if ($laststatus != HTTP_STATUS_OK) { // Last status was not success.
        return false;
    }

    if (substr($lastcontent, 0, 6) != 'image/') { // Last content was not an image (probably an error).
        return false;
    }

    return true;
}

/**
 * Remove images that cannot be retrieved remotely, so that they are not compiled.
 *
 * @author Gerald Albion
 * @param string $html the HTML to examine
 * @return string the modified html
 */
function compile_remove_missing_images($html) {
    $starttag = '<img ';
    $endtag = '>';
    $pos = strpos($html, $starttag);
    while (false !== $pos) {
        // Isolate <img> tag.
        $pos2     = strpos($html, $endtag, $pos + 1);
        $doomed   = trim(substr($html, $pos, $pos2 - $pos + strlen($endtag))); // What is to be removed.

        $linkdoc  = new DOMDocument();
        $linkdoc->loadHTML($doomed);
        $img = $linkdoc->getElementsByTagName('img');
        foreach ($img as $a) { // Should only be one element but this is the easiest way to access it.
            $href = $a->attributes->getNamedItem('src')->value;
            break;
        }

        if (!compile_image_is_retrievable($href)) { // If the source image is gone, remove the link.
            $html = str_replace($doomed, get_string('imagemissing', 'local_compile', $href), $html);
            $pos  = strpos($html, $starttag, $pos + 1);
            continue;
        }

        $pos    = strpos($html, $starttag, $pos + 1);
    }
    return $html;
}

/**
 * Remove blacklisted or no-domain image links in HTML
 *
 * @author Gerald Albion
 * @param string $html the HTML to examine
 * @return string the modified HTML
 */
function compile_remove_blacklisted_images($html) {
    $starttag = '<img ';
    $endtag = '>';
    $pos = strpos($html, $starttag);
    while (false !== $pos) {
        // Isolate <img> tag.
        $pos2     = strpos($html, $endtag, $pos + 1);
        $doomed   = trim(substr($html, $pos, $pos2 - $pos + strlen($endtag))); // What is to be removed.

        $linkdoc  = new DOMDocument();
        $linkdoc->loadHTML($doomed);
        $anchor = $linkdoc->getElementsByTagName('img');

        // Should only be one element but this is the easiest way to access it.
        foreach ($anchor as $a) {
            $href = $a->attributes->getNamedItem('src')->value;
            $anchorcontents = '';
            // Build innerHTML.
            foreach ($a->childNodes as $cn) {
                $anchorcontents .= $linkdoc->saveHTML($cn);
            }
            break; // There should only be one <img> in $doomed. Only consider the first.
        }

        $urlparts = parse_url($href);

        // If invalid path, skip.
        if (!array_key_exists('path', $urlparts)) {
            $pos = strpos($html, $starttag, $pos + 1);
            continue;
        }

        // If blacklisted, replace the full anchor with its inner text.
        if (compile_domain_is_blacklisted($href)) {
            $html = str_replace($doomed, get_string('imageblacklisted', 'local_compile', $anchorcontents), $html);
        }

        $pos    = strpos($html, $starttag, $pos + 1);
    }
    return $html;
}

/**
 * Remove blacklisted, local (if PDF), or no-domain links in HTML
 *
 * @author Gerald Albion
 * @param string $html the HTML to examine
 * @param boolean $compilepdf TRUE if we are compiling a PDF.
 * @return string the modified HTML
 */
function compile_remove_blacklisted_links($html, $compilepdf) {
    $starttag = '<a ';
    $endtag = '</a>';
    $pos = strpos($html, $starttag);
    while (false !== $pos) {
        // Isolate <a> tag.
        $pos2     = strpos($html, $endtag, $pos + 1);
        $doomed   = trim(substr($html, $pos, $pos2 - $pos + strlen($endtag))); // To be removed.

        $linkdoc  = new DOMDocument();
        $linkdoc->loadHTML($doomed);
        $anchor = $linkdoc->getElementsByTagName('a');

        // Should only be one element but this is the easiest way to access it.
        foreach ($anchor as $a) {
            $atts = $a->attributes;
            $hrefitem = $atts->getNamedItem('href');
            if (isset($hrefitem)) {
                $href = $hrefitem->value;
            } else {
                $href = false;
                continue; // No href found - probably an internal anchor.
            }

            $anchorcontents = '';
            foreach ($a->childNodes as $cn) { // Build innerHTML.
                $anchorcontents .= $linkdoc->saveHTML($cn);
            }
            break; // There should only be one element. Only consider the first.
        }

        if (false === $href) {
            $pos = strpos($html, $starttag, $pos + 1);
            continue; // Invalid href, continue.
        }

        $urlparts = parse_url($href);

        // Invalid path?  Skip.
        if (!array_key_exists('path', $urlparts)) {
            $pos = strpos($html, $starttag, $pos + 1);
            continue;
        }

        if ($compilepdf) {
            if (compile_domain_is_local($href)) {
                $html = str_replace($doomed, $anchorcontents, $html); // Replace the full anchor with its contents.
            } else {
                if (compile_domain_is_blacklisted($href)) { // If blacklisted, replace the full anchor with a not-included message.
                    $html = str_replace($doomed, get_string('linkblacklisted', 'local_compile', $anchorcontents), $html);
                }
            }
        } else {
            if (compile_domain_is_blacklisted($href)) { // If blacklisted, replace the full anchor with a not-included message.
                $html = str_replace($doomed, get_string('linkblacklisted', 'local_compile', $anchorcontents), $html);
            }
        }

        $pos    = strpos($html, $starttag, $pos + 1);
    }
    return $html;
}

/**
 * Clean up some common issues in Moodle-rendered module output HTML.
 *
 * @author Gerald Albion
 * @param string $htmlpage The HTML to be cleaned up
 * @param boolean $compilepdf TRUE if the compile target is PDF
 * @return string The cleaned-up HTML.
 */
function compile_cleanup_rendered($htmlpage, $compilepdf) {
    define('HTAG_LIMIT', 120); // How far into the rendered module to look for redundant headers.

    // We're only interested in the content of the page, so we use a regular expression to select it.
    preg_match('/<span id="maincontent">.*?<\/span>(.*)<\/div>.*?<\/div>.*?<\/section>/ims', $htmlpage, $content);

    // Initialize regex matches array.
    $cleaned = array();

    // Using a second regex to get rid of the final </div> tag.
    $tempcontent = array_key_exists(1, $content) ? $content[1] : '';
    preg_match('/(.*)\t*<\/div>\t*/ims', $tempcontent, $cleaned);
    $tempcleaned = array_key_exists(1, $cleaned) ? $cleaned[1] : '';

    // Added by ga 2014-02-25.
    // Remove redundant headers:
    // If there is an <h2> or <h3> in the first 120 chars, strip it.
    // Allow for the possibility of up to two of each.
    $tempcleaned = compile_strip_first_tag($tempcleaned, '<h2>', '</h2>', HTAG_LIMIT);
    $tempcleaned = compile_strip_first_tag($tempcleaned, '<h3>', '</h3>', HTAG_LIMIT);
    $tempcleaned = compile_strip_first_tag($tempcleaned, '<h2>', '</h2>', HTAG_LIMIT);
    $tempcleaned = compile_strip_first_tag($tempcleaned, '<h3>', '</h3>', HTAG_LIMIT);

    // Remove spurious <doctype>.
    $tempcleaned = compile_strip_first_tag($tempcleaned, '<doctype', '</doctype>');

    // Fix unbalanced <div> elements.
    $tempcleaned = compile_fix_unbalanced_divs($tempcleaned);

    // Remove admin links.
    $tempcleaned = compile_remove_admin_links($tempcleaned);
    $tempcleaned = compile_remove_blacklisted_links($tempcleaned, $compilepdf); // Remove blacklisted links completely.

    return $tempcleaned;
}

/**
 * Convert HTML to PDF using third party library, then output PDF to user-agent.
 *
 * @author Gerald Albion
 * @param string $html The HTML to convert
 */
function compile_output_as_pdf($html) {
    // Set the path to the external PDF engine.
    define('PDF_ENGINE', 'pdf/wkhtmltopdf');

    // This is a rudimentary PHP wrapper for wkhtmltopdf, a compiled OpenSuse binary.
    $basicfilename = sha1(time().random_string(10));
    $infilename    = "/tmp/".$basicfilename.".html";
    $pdffilename   = "/tmp/".$basicfilename.".pdf";

    file_put_contents($infilename, $html); // This is the input file.

    // Check that external PDF engine exists.
    if (!file_exists(PDF_ENGINE)) {
        compile_errorpage(get_string('cantloadpdfengine', 'local_compile'), get_string('pdfenginemissing', 'local_compile'));
    }

    // Check that external PDF engine is executable.
    if (!is_executable(PDF_ENGINE)) {
        compile_errorpage(get_string('cantloadpdfengine', 'local_compile'), get_string('pdfenginebadpermissions', 'local_compile'));
    }

    // This exec is safe because no user input to it is possible.
    exec(PDF_ENGINE . " -q --user-style-sheet pdf.css --encoding UTF-8 $infilename $pdffilename");

    unlink($infilename); // Done with the input file.
    if (file_exists($pdffilename)) {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($pdffilename));
        header("Content-Disposition: inline; filename=\"CompiledCourse.pdf\"");
        readfile($pdffilename);
        unlink($pdffilename); // Done with the output file.
    } else {
        compile_errorpage(get_string('cantcompilepdf', 'local_compile'), get_string('cantcompilepdfunknown', 'local_compile'));
    }

}

/**
 * Get the "intro" field value for a course (activity) module
 * Unlike other fields in the course module object, we need to look in the module's main table to get this.
 *
 * @author Gerald Albion
 * @param object $cm a course module object
 * @return mixed a string containing the intro or FALSE if unable to retrieve
 * @global object $DB the Moodle database object
 */
function compile_activity_intro($cm) {
    global $DB;
    $id = $cm->instance;
    $modname = $cm->modname;
    try {
        $result = $DB->get_records($modname, array('id' => $id));
    } catch (dml_exception $e) {
        print "<p>".$e->getMessage()."</p>";
        return false;
    }
    $intro = $result[$id]->intro;
    $intro = resolvepluginfile($cm, $intro, 'intro');
    return $intro;
}

/**
 * Convert @@PLUGINFILE@@ to context/component/filearea in supplied string.
 *
 * @param  object $cm       The course module containing the string to be transformed
 * @param  string $intro    The string to be transformed
 * @param  string $filearea The Filearea component of the file (field of the instance record).  Normally "intro"
 * @return string           The transformed string
 * @global object $CFG      The Moodle config object.
 *
 */
function resolvepluginfile($cm, $intro, $filearea) {
    global $CFG;
    $context = context_module::instance($cm->id);
    $urlfragment = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/mod_{$cm->modname}/{$filearea}";
    $newintro = str_replace('@@PLUGINFILE@@', $urlfragment, $intro);
    return $newintro;
}

/**
 * Uses curl to execute a module's display and return the resulting HTML.
 *
 * @author Gerald Albion
 * date 2014-11-01
 * @global object $CFG the Moodle configuration object
 * @param string $url The module's URL
 * @return string the module's HTML output.
 */
function compile_get_mod_html($url) {
    global $CFG;
    $ch = curl_init();
    $cookie = "MoodleSession{$CFG->sessioncookie} = " . $_COOKIE["MoodleSession" . $CFG->sessioncookie];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    session_write_close();
    return curl_exec($ch);
}

/**
 * Outputs an error page if the compile failed.
 *
 * @author Gerald Albion
 * date 2014-11-06
 * @global object $PAGE the Moodle page object
 * @global object $OUTPUT the Moodle output object
 * @global object $COURSE the Moodle current course object
 * @param string $title The title to use in the page header.
 * @param string $message Detailed error message.
 */
function compile_errorpage($title, $message) {
    global $PAGE, $OUTPUT, $COURSE;

    // Set up a basic error page.
    $PAGE->set_url('/local/compile/compiled_course.php', array('id' => $COURSE->id));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('plugin_title', 'local_compile'));
    $PAGE->set_heading(get_string('plugin_title', 'local_compile') . ' - ' . $title);
    $PAGE->set_pagelayout('base'); // No blocks wanted here.

    // Output the error.
    print $OUTPUT->header();
    print("<h3 class=\"error\">{$message}</h3>");
    print $OUTPUT->footer();

    die(); // If we don't die() here, we'll fall through to another error which will break the output of this one.
}

/**
 * Prints a section full of activity modules
 *
 * @author Steve Beaudry
 * date    2011-06-03
 * @global object $CFG The Moodle configuration object
 * @param object course information
 * @param array section information
 * @param array mod modules of the course
 * @return string section table
 */
function print_course_section($course, $section, $mods) {
    global $CFG;

    $sectiontable = '<div>';

    $modinfo = get_fast_modinfo($course);
    filter_preload_activities($modinfo);

    if (!empty($section->sequence)) {
        $sectionmods = explode(",", $section->sequence);
        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }
            $mod = $mods[$modnumber];

            // Is this course-module available to the user?
            if (!$mod->uservisible && empty($mod->availableinfo)) {
                continue; // Not available, do not list.
            }

            if ($mod->visible) {

                $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
                $instancename = $mod->get_formatted_name(array('overflowdiv' => true, 'noclean' => true));

                if (strlen($content) > 0) {
                    $instancename = strip_tags($content);
                }

                $fullinstancename = urldecode($instancename);
                $cm = $modinfo->get_cm($modnumber);
                if (!empty($cm->extra)) {
                    $extra = urldecode($cm->extra);
                } else {
                    $extra = '';
                }

                // Normal activity.
                $compilable = get_config("local_compile", $mod->modname);
                if (($compilable) && ($mod->visible)) {
                    if (!strlen(trim($instancename))) {
                        $instancename = $mod->modfullname;
                    }
                    if ($mod->modname != 'resource') {
                        $sectiontable .= '<tr>';
                        $sectiontable .= '<td class="compile-select-spacer"></td>';
                        $sectiontable .= '<td class="compile-select-checkcell">'
                            . "<input type=checkbox checked onclick=\"setparent("
                            . $modnumber
                            . ","
                            . $section->id
                            . ")\"  class=\"section-{$section->id}\" id=\"{$modnumber}\" name=checkboxlist[] value=\"{$modnumber}\" />"
                            . '</td>';
                        $sectiontable .= '<td class="compile-select-desccell">'
                            . "<a href=\"{$CFG->wwwroot}/mod/{$mod->modname}/view.php?id={$mod->id}\">"
                            . "<img src=\"{$CFG->wwwroot}/mod/{$mod->modname}/pix/icon.png\">{$fullinstancename}</a>"
                            . '</td>';
                        $sectiontable .= '</tr>';
                    } else {
                        require_once($CFG->dirroot.'/mod/resource/lib.php');
                        $info = resource_get_coursemodule_info($mod);
                        if ($info->icon) {
                            $sectiontable .= '<tr>';
                            $sectiontable .= '<td class="compile-select-spacer"></td>';
                            $sectiontable .= '<td class="compile-select-checkcell">'
                                . "<input type=checkbox onclick=\"setparent("
                                . $modnumber
                                . ","
                                . $section->id
                                . ")\" checked class=\"section-{$section->id}\" "
                                ."id={$modnumber} name=checkboxlist[] value={$modnumber} />"
                                . '</td>';
                            $sectiontable .= '<td class="compile-select-desccell">'
                                . "<a href=\"{$CFG->wwwroot}/mod/{$mod->modname}/view.php?id={$mod->id}\">"
                                . "<img src=\"{$CFG->wwwroot}/pix/{$info->icon}.png\">{$fullinstancename}</a>"
                                . '</td>';
                            $sectiontable .= '</tr>';
                        } else if (!$info->icon) {
                            $sectiontable .= '<tr>';
                            $sectiontable .= '<td class="compile-select-spacer"></td>';
                            $sectiontable .= '<td class="compile-select-checkcell">'
                                . "<input type=checkbox onclick=\"setparent("
                                . $modnumber
                                . ","
                                . $section->id
                                . ")\" checked class=\"section-{$section->id}\" "
                                . "id=\"{$modnumber}\" name=checkboxlist[] value=\"{$modnumber}\" />"
                                . '</td>';
                            $sectiontable .= '<td class="compile-select-desccell">'
                                . "<a href=\"{$CFG->wwwroot}/mod/{$mod->modname}/view.php?id={$mod->id}\">"
                                . "<img src=\"{$CFG->modpixpath}/{$mod->modname}/icon.gif\">{$fullinstancename}</a>"
                                . '</td>';
                            $sectiontable .= '</tr>';
                        }
                    }
                }
            }
        }
    }
    $sectiontable .= '</div>';
    if ($sectiontable == "<div></div>") {
        $sectiontable = '';
    }
    return $sectiontable;
}