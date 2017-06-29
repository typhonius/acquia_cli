# Acquia Cli

## Pre-installation
1. Ensure that you have downloaded the Drush site aliases for Acquia.
1. Move the example.robo.yml file to robo.yml and add your Acquia email address and password.

## Installation
1. Run `composer install`

## Usage/Examples
````
# Show which sites you have access to.
./bin/cli.php acquia:site:list

# Show detailed information about servers in the prod environment (assuming sitename of prod:acquia obtained from acquia:site:list command)
./bin/cli.php acquia:environment:info prod:acquia prod

# Copy the files and db from alpha to dev for testing new code
./bin/cli.php acquia:prepare:preprod prod:acquia alpha dev

# Deploy the develop-build branch to the test environment and run all config update steps
./bin/cli.php acquia:deploy:preprod prod:acquia test develop-build

# Copy the database and files from production to all non-production environments.
./bin/cli.php acquia:prepare:preprod:all prod:acquia
````

## Creating a Phar
A phar archive can be created to run Acquia Cli instead of utilising the entire codebase. Because some of Acquia Cli relies on user configuration of email/password, it is currently most appropriate to allow users to generate their own phar files inclusive of their own configuration.

1. Download and install the [box project tool](https://github.com/box-project/box2) for creating phars.
2. Follow the Getting Started section above to download and configure DBT.
3. Run `box build` in the directory that Acquia Cli has been cloned and configured in. This will use the packaged `box.json` file to create a phar specifically for Acquia Cli.
4. Move dbt.phar to a where it will be used. acquiacli.phar contains your secret email and password information as well as the code required to run Acquia Cli. The phar is now a customised and standalone app.
