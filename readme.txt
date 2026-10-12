=== Multi Agent Live Chat ===
Contributors: johnphiliptena
Tags: live chat, whatsapp, viber, messenger, contact button
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a responsive, floating contact launcher so visitors can choose an agent on WhatsApp, Viber, or Messenger.

== Description ==

Multi Agent Live Chat adds a lightweight contact button to the bottom corner of your public pages. Visitors can choose from up to 25 agents, each with their own platform, contact details, and desktop or mobile visibility.

= Features =

* Configure multiple agents and assign WhatsApp, Viber, or Messenger to each one.
* Show each agent on desktop, mobile, or both.
* Place the launcher in the bottom-left or bottom-right corner.
* Customize the button and prefilled message text.
* Responsive layout, keyboard controls, visible focus styles, and reduced-motion support.
* No third-party scripts, external fonts, or message collection are added to your site.
* Optionally remove this plugin's settings when uninstalling. Cleanup is off by default.
* Check GitHub for updates when a new version release is published.

This plugin launches the selected third-party service. It does not provide an embedded inbox, send messages from WordPress, or detect whether an agent is online. Availability and whether a conversation can be started depend on the service, the agent's account settings, and the visitor's device.

Updates are delivered from this plugin's GitHub releases. A release is created when a version tag such as `v1.0.1` is pushed.

= Contact formats =

* WhatsApp: full international phone number, digits only or formatted. Include the country code.
* Messenger: account or Page username, without the full URL. The plugin opens an `https://m.me/username` link.
* Viber: preferably paste the active Business chat link copied from Viber's Share Business screen. Personal Viber numbers may not have a public chat link; entering a number only works where Viber supports it. The plugin accepts `https://viber.me/<number>` links or an international number.

== Installation ==

1. Upload the `multi-agent-live-chat` folder to `/wp-content/plugins/`, or upload the ZIP from Plugins > Add New > Upload Plugin.
2. Activate **Multi Agent Live Chat** from the Plugins screen.
3. Open **Settings > Multi Agent Live Chat**.
4. Choose a button position and startup text, then add and configure agents.
5. Save changes and visit a public page to check the launcher on desktop and mobile.

== Frequently Asked Questions ==

= Does this embed a chatbox or inbox on my site? =

No. The plugin displays an agent picker and opens that agent's WhatsApp, Viber, or Messenger contact destination. These services do not provide one universal, unauthenticated embeddable inbox for this use case. Use the service's own supported integration if you need an embedded chat product.

= Can it tell if an agent is online? =

No. The plugin does not query agent presence. Presence APIs and account permissions vary by service and are not exposed by these public contact links. The visitor can still try to contact the agent through the service.

= Why is a Viber contact missing or not opening? =

Viber public business links depend on a Business account and a valid share link. Copy the current link from the account's Share Business screen. A personal phone number alone may not be reachable as a public chat destination. Check that the Viber app is available on the visitor's device.

= Why is an agent hidden on a device? =

Each agent has a desktop, mobile, or both visibility setting. The agent is omitted when the visitor's current screen width does not match that setting. If no configured agents are available at that width, the launcher is hidden.

= Does the plugin collect or store visitor messages? =

No. Messages are handled by the selected external service. The plugin stores the agent contact information and display settings in the WordPress options table. When a visitor follows a contact link, that service may receive the visitor's information under its own privacy policy.

= How do I remove the saved settings? =

By default, settings remain in WordPress if the plugin is deleted, so they are available if you reinstall it. To delete the saved settings during uninstall, enable **Delete this plugin's saved settings when the plugin is deleted** on the settings page before uninstalling.

== Privacy ==

This plugin does not create visitor accounts, track visitors, or store chat messages. It stores administrator-configured agent names, contact identifiers, visibility choices, button position, startup text, and the uninstall preference in the site's WordPress options. Contacting an agent takes the visitor to a third-party service, which may process data according to its own terms and privacy policy.

== Changelog ==

= 1.0.0 =
* First public release of Multi Agent Live Chat.
* Support up to 25 agents across WhatsApp, Viber, and Messenger with device-specific visibility.
* Add responsive visitor controls, safer settings handling, and opt-in uninstall cleanup.

= 1.7.0 =
* Support multiple agents across WhatsApp, Viber, and Messenger with per-agent device visibility.
