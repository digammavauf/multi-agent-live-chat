# Multi Agent Live Chat

A responsive WordPress contact launcher that lets visitors choose an agent and continue in WhatsApp, Viber, or Messenger. Each agent can be set to show on desktop, mobile, or both.

The plugin opens third-party chat links. It does not embed an inbox, send messages from WordPress, or detect agent presence.

## Install

Upload this directory as `wp-content/plugins/multi-agent-live-chat`, activate **Multi Agent Live Chat**, then configure agents under **Settings → Multi Agent Live Chat**. The release ZIP is available from the [GitHub Releases](https://github.com/digammavauf/multi-agent-live-chat/releases) page.

## GitHub updates

WordPress checks the public GitHub Releases API for a newer semantic version. Publish a release by pushing a tag such as `v1.0.1`; the release workflow packages the plugin ZIP that WordPress downloads. The tag version must be newer than the plugin's `Version` header.

## Development checks

The GitHub Actions workflow checks PHP syntax on PHP 7.4 and 8.3, and JavaScript syntax on Node 20 for pushes and pull requests.

## Privacy and support boundaries

The plugin stores administrator-configured agent details and widget settings in the WordPress options table. It does not collect visitor messages. When a visitor follows a link, the selected third-party service handles the conversation under its own policies.

See [readme.txt](readme.txt) for setup, supported contact formats, privacy notes, and FAQs.

