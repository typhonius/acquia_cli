[![Build Status](https://travis-ci.org/typhonius/acquia_cli.svg?branch=master)](https://travis-ci.org/typhonius/acquia_cli)
[![Total Downloads](https://poser.pugx.org/typhonius/acquia_cli/downloads.png)](https://packagist.org/packages/typhonius/acquia_cli)
[![Coverage Status](https://coveralls.io/repos/github/typhonius/acquia_cli/badge.svg?branch=master)](https://coveralls.io/repos/github/typhonius/acquia_cli/badge.svg?branch=master)

[![License](https://poser.pugx.org/typhonius/acquia_cli/license.png)]()
[![Latest Stable Version](https://poser.pugx.org/typhonius/acquia_cli/v/stable.png)](https://packagist.org/packages/typhonius/acquia_cli)
[![Latest Unstable Version](https://poser.pugx.org/typhonius/acquia_cli/v/unstable.png)](https://packagist.org/packages/typhonius/acquia_cli)

# Acquia Cli
The Acquia Cli tool provides developers, sysops, release managers and website administrators with a simple to use tool for obtaining information and running tasks on the Acquia Cloud.

Acquia Cli is simple to install and aligns to the permissions constructs already built into Acquia. The tool can be used for any task that can be completed using the Acquia web interface, and contains in-built instructions for each task.

If Acquia Cli doesn't have the task built in, simply create a ticket or a pull request for it to be included.

## Pre-installation

### Generating an API access token
To generate an API access token, login to [https://cloud.acquia.com](https://cloud.acquia.com), then visit [https://cloud.acquia.com/#/profile/tokens](https://cloud.acquia.com/#/profile/tokens), and click ***Create Token***.

* Provide a label for the access token, so it can be easily identified. Click ***Create Token***.
* The token has been generated, copy the api key and api secret to a secure place. Make sure you record it now: you will not be able to retrieve this access token's secret again.

## Downloading a version of the application
Select either the packaged application or the source application below before continuing with setup steps.

### Installing the packaged application (easiest)
To install the packaged application quickly and easily, the following three steps may be executed.
```
wget https://github.com/typhonius/acquia_cli/releases/latest/download/acquiacli.phar
mv acquiacli.phar /usr/local/bin/acquiacli
chmod +x /usr/local/bin/acquiacli
```

*Automatic updates*

If the packaged application is used, it can be updated simply and easily whenever a new release is out by running the following command from anywhere on the computer:

```
acquiacli self:update
```


### Installing from source (advanced)
To install from source, clone the repository into a location on your computer with the following commands:
```
git clone https://github.com/typhonius/acquia_cli.git
cd acquia_cli
composer install
```

## Setup
Choose one of the following methods to finish setting up your AcquiaCli installation.

### Automatic setup
Once you have downloaded the application using one of the above steps, run the following commands to enter add your Acquia credentials.
1. Run `acquiacli setup` (or `./vendor/bin/acquiacli setup` when used as a dependency in another project) which will ask for your credentials and automatically create this file.
1. Run `acquiacli drush:aliases` to download your available Drush aliases. Follow the instructions output by the command to install them.

### Manual installation/setup
Alternatively, follow the below steps for a manual installation.
1. Copy the `default.acquiacli.yml` file to your project root and name it `acquiacli.yml`.
1. Add your Acquia key and secret to the `acquiacli.yml` file.

### Environment Variables
Environmment variables can be used to store and provide the API key and secret, removing the need for configuration files.
* `ACQUIACLI_KEY` The environment variable for the API key
* `ACQUIACLI_SECRET` The environment variable for the API secret

If environment variables are to be used, these will need to be placed in the relevant bash file on Linux/Mac e.g. `$HOME/.bashrc` or `$HOME/.bash_profile` in the following format.
```
export ACQUIACLI_KEY=15fd1cde-1e66-b113-8e98-5ff9d444d54f
export ACQUIACLI_SECRET=Sdtg0o83TrZm5gVckpaZynCxpikMqcht9u3fexWIHm7
```
Windows users will need to add the environment variables within their system settings.

## Configuration
The Acquia Cli tool uses cascading configuration on the user's own machine to allow both global and per project credentials and overrides as needed.

Acquia Cli will load configuration in the following order with each step overriding matching array keys in the step prior:

1. Firstly, default configuration from `default.acquiacli.yml` in the project root/packaged with the Phar is loaded.
1. Next, if it exists, global configuration from `$HOME/.acquiacli/acquiacli.yml` is loaded.
1. Finally, if it exists, an `acquiacli.yml` file in the project root will be loaded. (Not applicable if using the Phar)
1. Environment variables take overall precedence for the key and secret, however other config won't be overridden.

The global and per project files may be deleted (manually) and recreated with `acquiacli setup` whenever a user wishes to do so.

Options may be manually set within the relevant `acquiacli.yml` file to change the following parameters under the `extraconfig` key:

Key | Default | Description
--- | :---: | ---
timezone | Australia/Sydney | Use [a supported PHP timezone](https://secure.php.net/manual/en/timezones.php) to see times in your locale.
format | Y-m-d H:i:s | Use [a supported PHP date string](https://secure.php.net/manual/en/function.date.php) to show times in an alternate format.
taskwait | 5 | A number in seconds to wait before hitting the API to check the status of a task.
timeout | 300 | A number in seconds before a task is considered to have timed out.


## Usage/Examples
Some of the following commands have aliases for simplicity e.g. `environment:info` has the alias of `e:i`.
````
# Show which applications you have access to.
acquiacli application:list

# Show detailed information about servers in the prod environment (assuming sitename of prod:acquia obtained from site:list command)
acquiacli environment:info prod:myacquiasite prod

# Copy the files and db from alpha to dev in preparation for a deployment
acquiacli deploy:prepare prod:myacquiasite dev alpha

# Copy the files and db from prod to test to prepare for a deployment.
# N.B. if the last argument is omitted in deploy:prepare, prod will be used
acquiacli deploy:prepare prod:myacquiasite test

# Deploy the develop-build branch to the test environment.
acquiacli code:switch prod:myacquiasite test develop-build

# Deploy the release-1.2.3 branch/tag to the production environment without prompting the user to confirm.
acquiacli code:switch prod:myacquiasite prod release-1.2.3 --yes

# Promote the code in preprod to prod.
acquiacli code:deploy prod:myacquiasite preprod prod

# Get a list of organizations you have access to and display organization UUIDs.
acquiacli organization:list

# Add a new team to an organization.
acquiacli team:create 'My Team Name' 'External Contractors'

# Add a new role to an organization (Use permissions:list to get available permissions for the new role).
acquiacli role:add 'My Team Name' 'Contractors' 'move file to non-prod,move file from prod,download db backup non-prod,download logs non-prod,deploy to non-prod'

# Associate a team with an application within the organization (Use organization:teams to get team UUIDs).
acquiacli team:addapp prod:myacquiasite d2693c6e-58e7-47e5-8867-e2db88c71b8c

# Add a user to a team and assign roles (Use role:list to obtain the role UUIDs).
acquiacli team:invite d2693c6e-58e7-47e5-8867-e2db88c71b8c 'username@example.com' f0b89594-0fc5-4609-935f-1f18c313c6c7

````

### See it in action
[![asciicast](https://asciinema.org/a/178427.png)](https://asciinema.org/a/178427)

### Command Parameters
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

Both the UUID and the hosting ID (with realm) for your applications may be discovered by running `acquiacli application:list`

*Other parameters*

Environment parameters take the label name of the environment e.g. dev

Organization parameters take the label name of the organization e.g. mycompany

All other parameters are currently provided in the UUID form, including but not limited to:
* Role ID
* Team ID

Commands using the following parameters will be automatically converted by the Acquia Cli tool using the SDK. This is achieved in the `validateUuidHook` method in the `AcquiaCommand` class using a `@hook validate` [annotation](https://github.com/consolidation/annotated-command).
* `$uuid` is converted to the UUID of the application

Helper functions exist in `CloudApi.php` to convert user supplied parameters into more useful objects.
* Environments may be converted into an EnvironmentResponse object by using the `getEnvironment` method.
* Organizations may be converted into an OrganizationResponse object by using the `getOrganization` method.

## Usage on Windows
The Phar file has been tested minimally on Windows. It may be executed using PHP from a tool such as [Chocolatey](https://chocolatey.org/). You may run into cURL issues with SSL in some instances. The recommended approach to remediating SSL issues is to follow the [basic instructions here](https://stackoverflow.com/a/34883260). You may need to use alternate paths based on your PHP installation.
