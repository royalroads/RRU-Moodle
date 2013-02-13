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
 * disable link for Compile
 *
 * 2011-06-03
 * @package      plug-in
 * @subpackage   RRU_Compile
 * @copyright    2011 Steve Beaudry, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create an option in the navigation menu
 *
 * @author Steve Beaudry
 * date    2011-05-03
 * @param global navigation 
 */
function compile_extends_navigation(global_navigation $navigation) {
    if (isset($_GET['id'])) {
        $nodeCompile = $navigation->add('Compile Course', new moodle_url('/local/compile/list_modules.php?id=' . $_GET['id']));
    }
}
