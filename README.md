[![Packagist](https://img.shields.io/packagist/v/typhonius/acquia_cli.svg)](https://packagist.org/packages/typhonius/acquia_cli)
# Acquia Cli

## Pre-installation
1. Ensure that you have downloaded the Drush site aliases for Acquia.
1. Copy the `example.acquiacli.yml` file to your project root and name it `acquiacli.yml`.
1. Add your Acquia email address to the `acquiacli.yml` file.
1. Add either your [CloudAPI private key](https://accounts.acquia.com/account/security) (preferred) or Acquia password to the `acquiacli.yml` file.

Alternatively, run `./bin/acquiacli setup` (or `./vendor/bin/acquiacli setup` when used as a dependency in another project) which will ask for your credentials and automatically create this file.

## Installation
1. Run `composer install`

## Usage/Examples
````
# Show which sites you have access to.
./bin/acquiacli site:list

# Show detailed information about servers in the prod environment (assuming sitename of prod:acquia obtained from site:list command)
./bin/acquiacli environment:info prod:acquia prod

# Copy the files and db from alpha to dev for testing new code
./bin/acquiacli preprod:prepare prod:acquia alpha dev

# Deploy the develop-build branch to the test environment and run all config update steps
./bin/acquiacli preprod:deploy prod:acquia test develop-build
````

## Creating a Phar
A phar archive can be created to run Acquia Cli instead of utilising the entire codebase. Because some of Acquia Cli relies on user configuration of email/password, it is currently most appropriate to allow users to generate their own phar files inclusive of their own configuration.

1. Download and install the [box project tool](https://github.com/box-project/box2) for creating phars.
2. Follow the Getting Started section above to download and configure Acquia Cli.
3. Run `box build` in the directory that Acquia Cli has been cloned and configured in. This will use the packaged `box.json` file to create a phar specifically for Acquia Cli.
4. Move acquiacli.phar to a where it will be used. acquiacli.phar contains your secret email and password information as well as the code required to run Acquia Cli. The phar is now a customised and standalone app.
