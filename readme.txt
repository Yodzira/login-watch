=== Login Watch ===
Contributors: yodzira
Tags: login, security, alerts, telegram, admin
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Know who enters your admin the second they do: admin login alerts, failed-attempt bursts, new-admin creation. Telegram and email. No firewall, no lockouts.

== Description ==

Login Watch does one thing: it tells you what happens at your login screen — instantly.

* admin logins: who, when, masked IP
* failed-attempt bursts: one calm alert after 10 tries from one source, not a flood
* new administrators and role changes to administrator — the classic backdoor move
* plugin activations journaled
* Telegram (optional) + admin email
* 30-day event journal, clean uninstall

Login Watch never blocks anyone. It is lightweight on purpose and fully compatible with Wordfence, Solid Security and firewalls.

== Pro Version ==

Pro adds automation, reports and integrations on top of the free version
(one license = one site, 12 months of updates):

https://yodsira.com/buy/login-watch

== Installation ==

1. Install and activate.
2. Optionally add a Telegram bot for instant alerts.
3. The event journal fills automatically.

== Frequently Asked Questions ==

= Does it replace Wordfence? =
No — it complements it. Login Watch only notifies; it never blocks, never slows the login screen.

= Can it lock me out? =
No. Nothing is blocked, ever.

== Changelog ==

= 0.1.1 =
* Added: extension filters for the Pro companion (routing and custom checks). Nothing changes for existing setups.

= 0.1.0 =
* First release: admin login alerts, failed-burst detection, new-admin/role-change alerts, journal, clean uninstall.
