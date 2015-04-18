=== Formidable to Slack ===
Contributors: ninnypants
Donate link: http://ninnypants.com/plugins/
Tags: formidible, slack, invite
Requires at least: 3.3.0
Tested up to: 4.2.0
Stable tag: 1.0.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows site owners to automatically send Slack invites to anyone that fills out a specified form.

== Description ==

Automatically invite users that have filled out a specified form to your Slack team instead of having to manually invite them yourself.

== Installation ==
1. Upload folder to `wp-content/plugins`.
1. Activate plugin.
1. Create a new form with Email, First Name, and Last Name fields.
1. Got to Formidable > Global Settings select the form you created, and copy the labels from your form into the Email Address Label, First Name Label, and Last Name Label fields.
1. Go to [https://api.slack.com/web](https://api.slack.com/web) and generate a token for your team, and add that to your settings.

== Changelog ==
= 1.0.2 =
* Add better validation to settings.
* Fix bug where after initially saving settings all of the settings fields were empty until the page was reloaded again.

= 1.0.1 =
* Fixed bug where instantiation access was causing errors in versions of php older than 5.4

= 1.0 =
* Initial plugin release

