[![Build Status](https://travis-ci.org/typhonius/acquia_cli.svg?branch=master)](https://travis-ci.org/typhonius/acquia_cli)
[![Total Downloads](https://poser.pugx.org/typhonius/acquia_cli/downloads.png)](https://packagist.org/packages/typhonius/acquia_cli)
[![Coverage Status](https://coveralls.io/repos/github/typhonius/acquia_cli/badge.svg?branch=master)](https://coveralls.io/repos/github/typhonius/acquia_cli/badge.svg?branch=master)

[![License](https://poser.pugx.org/typhonius/acquia_cli/license.png)]()
[![Latest Stable Version](https://poser.pugx.org/typhonius/acquia_cli/v/stable.png)](https://packagist.org/packages/typhonius/acquia-php-sdk-v2)
[![Latest Unstable Version](https://poser.pugx.org/typhonius/acquia_cli/v/unstable.png)](https://packagist.org/packages/typhonius/acquia-php-sdk-v2)

# Acquia Cli

## Pre-installation
1. Run `composer install`
1. Obtain your API key and secret

## Generating an API access token
To generate an API access token, login to [https://cloud.acquia.com](https://cloud.acquia.com), then visit [https://cloud.acquia.com/#/profile/tokens](https://cloud.acquia.com/#/profile/tokens), and click ***Create Token***.

* Provide a label for the access token, so it can be easily identified. Click ***Create Token***.
* The token has been generated, copy the api key and api secret to a secure place. Make sure you record it now: you will not be able to retrieve this access token's secret again.

## Automatic installation/setup
1. Run `./bin/acquiacli setup` (or `./vendor/bin/acquiacli setup` when used as a dependency in another project) which will ask for your credentials and automatically create this file.
1. Run `./bin/acquiacli drush:aliases` to download your available Drush aliases. Follow the instructions output by the command to install them.

## Manual installation/setup
Alternatively, follow the below steps for a manual installation.
1. Copy the `default.acquiacli.yml` file to your project root and name it `acquiacli.yml`.
1. Add your Acquia key and secret to the `acquiacli.yml` file.


## Configuration
The Acquia Cli tool uses cascading configuration on the user's own machine to allow both global and per project credentials and overrides as needed.

Acquia Cli will load configuration in the following order with each step overriding matching array keys in the step prior:

1. Firstly, default configuration from `default.acquiacli.yml` in the project root is loaded.
1. Next, if it exists, global configuration from `~/.acquiacli/acquiacli.yml` is loaded.
1. Finally, if it exists, an `acquiacli.yml` file in the project root will be loaded.
1. Environment variables take overall precedence for the key and secret, however other config won't be overridden.

The global and per project files may be deleted (manually) and recreated with `./bin/acquiacli setup` whenever a user wishes to do so.

Options may be manually set within the relevant `acquiacli.yml` file to change the following parameters under the `extraconfig` key:

Key | Default | Description
--- | :---: | ---
timezone | Australia/Sydney | Use [a supported PHP timezone](https://secure.php.net/manual/en/timezones.php) to see times in your locale.
format | Y-m-d H:i:s | Use [a supported PHP date string](https://secure.php.net/manual/en/function.date.php) to show times in an alternate format.
taskwait | 5 | A number in seconds to wait before hitting the API to check the status of a task.
timeout | 300 | A number in seconds before a task is considered to have timed out.
configsyncdir | sync | A directory to be passed to the config-import command for syncing config.

## Environment Variables
Environmment variables can be used to store and provide the API key and secret.
* `ACQUIACLI_KEY` The environment variables for the API key
* `ACQUIACLI_SECRET` The environment variables for the API secret


## Usage/Examples
Some of the following commands have aliases for simplicity e.g. `environment:info` has the alias of `e:i`.
````
# Show which applications you have access to.
./bin/acquiacli application:list

# Show detailed information about servers in the prod environment (assuming sitename of prod:acquia obtained from site:list command)
./bin/acquiacli environment:info prod:acquia prod

# Copy the files and db from alpha to dev for testing new code
./bin/acquiacli preprod:prepare prod:acquia alpha dev

# Deploy the develop-build branch to the test environment and run all config update steps.
./bin/acquiacli preprod:deploy prod:acquia test develop-build

# Deploy the release-1.2.3 branch/tag to the production environment and run all config update steps without prompting the user to confirm.
./bin/acquiacli prod:deploy prod:acquia release-1.2.3 --yes

# Get a list of organizations you have access to and display organization UUIDs.
./bin/acquiacli organization:list

# Add a new team to an organization.
./bin/acquiacli team:create f5626a0a-5ed8-4868-b7aa-c5e91de666b5 'External Contractors'

# Add a new role to an organization (Use permissions:list to get available permissions for the new role).
./bin/acquiacli role:add f5626a0a-5ed8-4868-b7aa-c5e91de666b5 'Contractors' 'move file to non-prod,move file from prod,download db backup non-prod,download logs non-prod,deploy to non-prod'

# Associate a team with an application within the organization (Use organization:teams to get team UUIDs).
./bin/acquiacli team:addapp prod:acquia d2693c6e-58e7-47e5-8867-e2db88c71b8c

# Add a user to a team and assign roles (Use role:list to obtain the role UUIDs).
./bin/acquiacli team:invite d2693c6e-58e7-47e5-8867-e2db88c71b8c 'username@example.com' f0b89594-0fc5-4609-935f-1f18c313c6c7

````

## See it in action
[![asciicast](https://asciinema.org/a/178427.png)](https://asciinema.org/a/178427)

## Command Parameters
*Application UUID*
If a command takes an application UUID as a parameter, it can be provided in one of three ways - see below for a description of hosting realm:
* The Acquia hosting ID on its own e.g. myacquiasite
* The full Acquia hosting realm and ID e.g. prod:myacquiasite
* The application UUID e.g. 8ff6c046-ec64-4ce4-bea6-27845ec18600

*Hosting Realms*
Acquia uses the concept of a 'realm' to differentiate between customers on the two tiers of hosting offered:
* prod: The 'prod' realm is exclusively for Acquia Cloud Enterprise (ACE) customers.
* devcloud: The 'devcloud' realm is exclusively for Acquia Cloud Professional (ACP) customers.

If no hosting realm is provided, prod is used by default. This can be overridden in the command by specifying a realm e.g. `--realm=devcloud`

Both the UUID and the hosting ID (with realm) for your applications may be discovered by running `./bin/acquiacli application:list`

*Other parameters*
Environment parameters take the label name of the environment e.g. dev
Organization parameters take the label name of the organization e.g. mycompany

All other parameters are currently provided in the UUID form, including but not limited to:
* Role ID
* Team ID

Commands using the following parameters will be automatically converted by the Acquia Cli tool using the SDK. This is achieved in the `validateUuidHook` method in the `AcquiaCommand` class using a `@hook validate` [annotation](https://github.com/consolidation/annotated-command).
* `$uuid` is converted to the UUID of the application
* `$environment` is converted into an EnvironmentResponse object
* `$environmentFrom` is converted into an EnvironmentResponse object
* `$environmentTo` is converted into an EnvironmentResponse object
* `$organization` is converted into an OrganizationResponse object

## Creating a Phar
A phar archive can be created to run Acquia Cli instead of utilising the entire codebase. Because some of Acquia Cli relies on user configuration of email/password, it is currently most appropriate to allow users to generate their own phar files inclusive of their own configuration.

1. Download and install the [box project tool](https://github.com/box-project/box2) for creating phars.
2. Follow the Getting Started section above to download and configure Acquia Cli.
3. Run `box build` in the directory that Acquia Cli has been cloned and configured in. This will use the packaged `box.json` file to create a phar specifically for Acquia Cli.
4. Move acquiacli.phar to a where it will be used. acquiacli.phar contains your secret email and password information as well as the code required to run Acquia Cli. The phar is now a customised and standalone app.
