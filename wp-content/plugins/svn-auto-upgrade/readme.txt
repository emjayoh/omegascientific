=== SVN Auto Upgrade ===
Contributors: Modern Tribe, Inc.,peterchester,moderntribe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7GVFAADMQ96WA
Tags: svn, subversion, upgrade, update, autoupdate, plugin, core, cache bust, cache burst, version
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.2
Author URI: http://tri.be

== Description ==

Description: Hook into plugin and core upgrader to support SVN driven sites. Now you can freely use the WordPress auto upgrade features without worrying about the Subversion impact. Just upgrade in WordPress and then commit the changes in SVN.

Additionally, this plugin displays the current SVN version in the footer of your admin panel and uses the SVN version to append to your CSS and Javascript embeds so that every time you update from SVN, your media files are cleared form cache.

Requires that your system permissions allow shell_exec() to execute svn commands.

Donate - if this is generating enough revenue to support our time it makes all the difference in the world
https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7GVFAADMQ96WA

= To Do =

* Account for SVN externals
* Test to ensure the plugin is compatible with your server (that you can run exec svn commands)
* Build an auto commit option? Not sure if this is a good idea.

== Installation ==

= Install =

Install just like any other plugin.  That's it.  Done.

= Requirements =

* PHP 5.1 or above
* WordPress 3.0 or above
* System permissions allow shell_exec() to execute svn commands

== Documentation ==

It's pretty straight forward. When you auto upgrade a plugin or the core, this plugin automatically svn adds and removes files appropriately. That way you can keep your entire WordPress install in SVN.

== Changelog ==

= 1.2 =

* Add theme upgrade support.
* Convert all functions to static functions.

= 1.1 =

* Add support for displaying current SVN version in admin footer.
* Added bloginfo version override so that CSS / JS is cache busted upon SVN incrementation.

= 1.0 =

Initial Launch.