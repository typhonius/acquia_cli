[![Packagist](https://img.shields.io/packagist/v/typhonius/acquia_cli.svg)](https://packagist.org/packages/typhonius/acquia_cli)
# Acquia Cli

## Pre-installation
1. Ensure that you have downloaded the Drush site aliases for Acquia.
1. Run `composer install`

## Installation/setup
Run `./bin/acquiacli setup` (or `./vendor/bin/acquiacli setup` when used as a dependency in another project) which will ask for your credentials and automatically create this file.

Alternatively, follow the below steps for a manual installation.
1. Copy the `default.acquiacli.yml` file to your project root and name it `acquiacli.yml`.
1. Add your Acquia key and secret to the `acquiacli.yml` file.
1. Optionally add your Cloudflare email address and API key to the `acquiacli.yml` file.


## Configuration
The Acquia Cli tool users cascading configuration on the users own machine to allow both global and per project credentials and overrides as needed.

Acquia Cli will load configuration in the following order with each step overriding matching array keys in the step prior:

1. Firstly, default configuration from `default.acquiacli.yml` in the project root is loaded.
1. Next, if it exists, global configuration from `~/.acquiacli/acquiacli.yml` is loaded.
1. Finally, if it exists, an `acquiacli.yml` file in the project root will be loaded.

The global and per project files may be deleted (manually) and recreated with `./bin/acquiacli setup` whenever a user wishes to do so.

## Usage/Examples
````
# Show which applications you have access to.
./bin/acquiacli application:list

# Show detailed information about servers in the prod environment (assuming sitename of prod:acquia obtained from site:list command)
./bin/acquiacli environment:info prod:acquia prod

# Copy the files and db from alpha to dev for testing new code
./bin/acquiacli preprod:prepare 6fb6adfd-5827-4f88-8e7a-3f577fe08ace alpha dev

# Deploy the develop-build branch to the test environment and run all config update steps
./bin/acquiacli preprod:deploy 6fb6adfd-5827-4f88-8e7a-3f577fe08ace test develop-build

# Get a list of DNS records for the foobar.com domain in Cloudflare
./bin/acquiacli cf:list foobar.com

# Add a record for www.foobar.com to point to 127.0.0.1 in Cloudflare.
./bin/acquiacli cf:add foobar.com A www 127.0.0.1
````

## Creating a Phar
A phar archive can be created to run Acquia Cli instead of utilising the entire codebase. Because some of Acquia Cli relies on user configuration of email/password, it is currently most appropriate to allow users to generate their own phar files inclusive of their own configuration.

1. Download and install the [box project tool](https://github.com/box-project/box2) for creating phars.
2. Follow the Getting Started section above to download and configure Acquia Cli.
3. Run `box build` in the directory that Acquia Cli has been cloned and configured in. This will use the packaged `box.json` file to create a phar specifically for Acquia Cli.
4. Move acquiacli.phar to a where it will be used. acquiacli.phar contains your secret email and password information as well as the code required to run Acquia Cli. The phar is now a customised and standalone app.
