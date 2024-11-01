=== Total Diagram ===
Contributors: dariuszdawidowski
Donate link: https://www.patreon.com/dariuszdawidowski
Tags: diagram, management, mindmap
Requires at least: 5.0
Tested up to: 5.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Diagramming web app.

== Description ==

Total Diagram is a diagramming web application. Useful for systematization and prototyping.
Write down your ideas on sticky notes, visualize your programming API as a tree structure.

To begin:

Put [total-diagram] shortcode on page.

Important note:

For the best experience you'll need a full-screen theme or at least use custom css to make diagram window wide enough.
See Installation section for more details.

For safety reasons remember to use latest stable versions of browser, WordPress and other plugins.

Usage:

Add new node: right click on background (two fingers on modern touchpad) -> context menu -> Add node

Quick add node: doubleclick (last used type)

Select node: click on node

Drag node: click on node and drag

Pan view: click on background adn drag or scroll with two fingers on modern touchpad

Zoom view: pinch gesture on modern touchpad (requires Chrome or Firefox) or scroll with CTRL key

Context menu: right click (two fingers on modern touchpad)

Connect nodes with link: select two nodes and right click for context menu -> Add link

Smart zoom view: + - keys

Center view: 0 key

== Frequently Asked Questions ==
 
= What browsers are supported? =
Chrome 68, Firefox 61, Safari 11

= Does it work on mobile devices? =
Not yet

= What technologies are used? =
Javascript/ES6, Html5, Css3, WebGL, Three.js library

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/total-diagram` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Create any page (e.g. 'Total') and put shortcode [total] or [total-diagram] there.
1. Instead of using shortcode you can use widget if your theme supports it.
1. Plugin works only for logged-in users.
1. Probably you preffer to use plugin in the full-screen mode, so you can:
   * choose clean full-screen theme for your site
   * or you can tweak div id '#total-diagram' with surroundings using css (Appearance -> Customize -> Additional CSS)
   * or customize only one page for this plugin [read about Page Templates here](https://developer.wordpress.org/themes/template-files-section/page-template-files/)
   * additionally you may hide WordPress toolbar in 'Users -> Your Profile: Show Toolbar when viewing site'
1. Other tweaks:
   * Preffered resolution for image miniatures is 256x256 (or 512x512 when you need quality). You can set it in WordPress Admin -> Settings -> Media: Thumbnail Size.

== Changelog ==

= 1.4.0 =
* Fixed the most unacceptable omission: pink color for sticky notes. Now it's supported!
* Introducing Node Clipart
* Introducing 3d layers
* Improved Node Point with new functionality
* Fixed Node Image
* Switched from .svg to .obj 3d model format
* Preloader
* Minor fixes and optmizations
* Updated third-party library Three.js to r100
* Bumped WordPress version requirement to 5.0+

= 1.3.1 =
* Bugfix release
* Fixed issues with keys in Firefox
* Fixed text in Node Image

= 1.3.0 =
* Introducing new nodes: Image and File
* Drag&drop external files, images and text directly onto diagram
* Updated third-party library Three.js to r97
* Welcome tutorial
* Fixed some minor issues

= 1.2.0 =
* Fixed linking nodes
* Spinner during loading
* New functionality for Node Point: move with children, show/hide children
* Updated third-party library Three.js to r96
* Fixed menu position
* Fixed message about number of deleted nodes

= 1.1.0 =
* Speed optimizations
* Fixed saving '+' and '&' characters in database
* Fixed current data folder
* Added both [total] and [total-diagram] shortcodes
* Minimal window size increased to 800x600
* Protect widget for logged-in users only
* Redirect non-admins to main page after login
* Prevent caching
* Simple word dividing
* Remove default values in database
* Prevent event flood while dragging node

= 1.0.0 =
* Initial release
