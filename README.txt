=== WP Easy Updates ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://ayecode.io/
Tags: EDD, github, updates, external updates, development
Requires at least: 3.1
Tested up to: 4.7
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Update plugins provided by EDD software licencing or via github with ease.

== Description ==

This plugin will let people update any plugins or themes that are provided through Easy Digital Downloads (EDD) or via github so long as the developer has added 1-2 lines to their plugin/theme.

If you are running a multisite network then this plugin should be network activated.

= For Developers =

To make your plugin compatible all you have to do is add 1-2 lines to your plugins main PHP file DocBlock or your theme style.css.

EDD Software Licencing:
To make your plugin/theme compatible with EDD Software Licencing just add the URL of your website and the product id such as
`Update URL: https://wpgeodirectory/`
and
`Update ID: 54321`
The Update ID is the post ID of the product on your site.

GitHub:
To make your plugin/theme compatible with GitHub you just have to add URL of the repo to your plugins main PHP file DocBlock.
`Update URL: https://github.com/AyeCode/test-product/`
The system will check for Releases and check the release tag as the version number to compare to.

At the moment this plugin will run a check for each plugin/theme update, when EDD SL v3.6 is release we will be able to send the request as and array so if you have 20 plugins from the same vendor, your site will only send one request instead of 20.

== Installation ==

= Minimum Requirements =

* WordPress 3.1 or greater
* PHP version 5.2.4 or greater

= Automatic installation =

Automatic installation is the easiest option. To do an automatic install log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type Easy External Updates and click Search Plugins. Once you've found the plugin you install it by simply clicking Install Now.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex will tell you more [here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should seamlessly work. We always suggest you backup up your website before performing any automated update to avoid unforeseen problems.

== Frequently Asked Questions ==

= Will EDD users be able to enter and activate their licence key? =

Yes, checkout the Screenshots section.

= Will users be able to see update details? =

Yes, this will work as normal, for github the info will be limited to the release description.

== Screenshots ==


== Changelog ==

= 1.1.3 =
Banner warning not defined if updating from github - FIXED

= 1.1.2 =
If deactivating licence fails the licence is not removed - FIXED
Added ability to show Upgrade Notice messages - ADDED

= 1.1.1 =
Sometimes on update/install theme info is not defined and causes it to freeze - FIXED

= 1.1.0
Theme update support added for both github and EDD - ADDED
Updates from github can change the folder name of plugins - FIXED
Theme licences can be added via Themes > Theme Details page - ADDED

= 1.0.4 =

Initial release