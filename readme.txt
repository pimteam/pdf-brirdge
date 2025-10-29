=== PDF-Bridge ===
Contributors: prasunsen
Tags: pdf, html, html2fpdf, MPDF
Requires at least: 3.3
Tested up to: 3.7.1
Stable tag: trunk
License: GPL2

Use WP filter "pdf-bridge-convert" to convert HTML to PDF inside your plugin or theme 

== License ==

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

== Description ==

Use WP filter "pdf-bridge-convert" to convert HTML to PDF inside your plugin or theme. Uses the <a href="http://blog.calendarscripts.info/wp-content/uploads/2014/03/pdf-bridge.zip" target="_blank">MPDF library</a>.

== Installation ==

1. Unzip the contents and upload the entire `namaste` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. In your plugins or theme functions call `$content = apply_filters('pdf-bridge-convert', $content);` to get your content converted. Then output it with the appropriate headers, save to a file, mail, or do whatever you want with it.

== Frequently Asked Questions ==

None yet, please ask in the forum

== Screenshots ==


== Changelog ==

= Version 0.2 =

First public release