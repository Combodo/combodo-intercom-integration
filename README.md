# Intercom chat integration

_Note: Prototype to show how to integrate a third-party chat(bot) in iTop through the existing APIs._

## Description
[Intercom](https://www.intercom.com/) is a paid, hosted, third-party, live support chat that can be easily integrated within iTop to offer a new contact channel. It's a perfect way to enable live chat in iTop with a minimal effort but needs a subscription.

## Features
It brings a small bubble in the bottom-right corner of the screen. Users can click on it to start a conversation with support agents.

_Note: Chat can be enabled in any end-user portals or the backoffice through its configuration._

_TODO: Make a screenshot of the closed widget in the portal_

_TODO: Make a screenshot of the closed widget in the portal_

**Important:** Unfortunately, embedding the agents dashboard in iTop through an iFrame dashlet is no longer possible as they have shutdown this feature.

## Compatibility
Compatible with iTop 2.7+

## Configuration
### Get Intercom account
Go to [intercom.com](https://www.intercom.com/), create a free account and that's it!

### Set widget configuration
First, go to the intercom.com backoffice and retrieve the `API Key`. Once you got it, open the iTop configuration file and fill the module settings as follow:
- `api_key` Put the site ID retrieve in the previous step.
- `enabled_portals` An array of the "portals" you want the chat to be enabled on. Can be `backoffice` for the admin. console or any end-user portal ID (eg. `itop-portal` for the standard portal).
- `allowed_profiles` An array of iTop profiles to define which users will be able to use the chat. If not defined, all users will be able t use it.

### Configuration example
```
'combodo-intercom-integration' => array (
    'api_key' => 'someapikeyforyourcopany',
    'enabled_portals' => array (
      'itop-portal',
    ),
    'allowed_profiles' => array('Portal user'),
),
```
