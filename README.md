# Driver

Driver is a database task-runner that helps you to turn production database into a staging/development database sandbox.

## Description

Driver is the easy way to turn a production database into a staging or development-friendly database.
It is built with the aim of complete flexibility. Additionally, with the built-in capability
of working with RDS, Driver can run transformations on database host that is completely separate from
your production environment.

Driver resides on your production machine, with your website's codebase (via Composer).
You set up a CRON job to run Driver.

* Driver connects to your production database ONCE via `mysqldump`.
* Driver dumps that to a file on your system.
* Driver creates an RDS instance and pushes the database dump up to RDS (via https).
* Driver runs whatever actions you would like (configured globally or per environment).
* Driver dumps the transformed data, zips it and pushes it up to S3.
* You can then download transformed dump from S3 to your staging/development machine, also using Driver.

For a 3-5GB database, this process could take an hour or two.
This depends on how many environments you are creating and what type of RDS instance you are using.
This causes no downtime to the frontend thanks to the `--single-transaction` flag on `mysqldump`.
Yes, it does take a while to run, but there is little-to-no impact on the frontend.

## Before We Start

At Swift Otter we mainly work with Magento 2. We created separate repo for Magento 2 Driver transformations.
It includes config for anonymization of all database tables of core Magento 2 system. If you are Magento 2 developer,
we recommend you to install that repo, instead of this one (Driver would be installed anyway, as it is a dependency
of that Magento 2 repo). If you're not a Magento 2 developer, we still recommend you to look at that repo to get
a better idea on how things can be configured for your project.

Magento 2 Driver transformations repo can be found [here](https://github.com/swiftotter/Driver-Magento2).

## Getting Started

### Dependencies

- PHP 7.4 or higher

### Prerequisites

You need to create an Amazon AWS account. We will be using RDS to perform the data manipulations.
It is recommended to create a new IAM user with appropriate permissions to access EC2, RDS and S3
(exact permissions will be coming). This is explained in details [here](docs/aws-setup.md).

### Installation

```bash
composer require swiftotter/driver
```

### Configuration

#### Overview of Config Structure 

Driver configuration goes into a folder named `.driver`.
The files that are recognized inside this folder are:

* `anonymize.yaml`
* `commands.yaml`
* `config.yaml`
* `connections.yaml`
* `engines.yaml`
* `environments.yaml`
* `pipelines.yaml`
* `reduce.yaml`
* `update_values.yaml`

The filenames of these files serve no purpose other than a namespace. The delineation of the configuration
happens inside each file. For example, in `pipelines.yaml`, there is a `pipelines` node as the root element.
In this way, the YAML itself is providing namespaces. This also has the benefit for you of being able to put
all of your updates in one file (for example, `config.yaml`).

Driver looks in quite a few places for configuration files. As an example, let's say your application is
stored in `/var/www/`. Your vendor directory is `/var/www/vendor/` and, of course, Driver's home is
`/var/www/vendor/swiftotter/driver`. As such, Driver will look in the following locations for configuration
files:

* `/var/www/.driver/`
* `/var/www/vendor/*/*/.driver/`

You can symlink any file you want here. Keep in mind that these files do contain sensitive information, and
it is necessary to include a `.htaccess` into that folder: `Deny from all`

#### Connections Configuration

You have to configure connections information for your project.
In the folder that contains your `vendor` folder, create a folder called `.driver`.
Next, copy the file `vendor/swiftotter/driver/.driver/connections.yaml.dist` to `.driver/connections.yaml`
and fill it in with your source MySQL information, as well as destination EC2, RDS and S3 data.

Exemplary config can be found in `vendor/swiftotter/driver/.driver/connections.yaml.example`.

#### Environments Configuration

Environments allow to make modifications to the database, per-site. You can configure different set of actions for every
environment. For example, for local environment you may want to clear out all admin users as well as set unique URLs
for the website and possibly other things.

**Note:** You can execute specific commands per environment. These changes do not revert for each environment,
but rather they are applied according to their sort order. If one environment uses the data in the `admin_users` table,
and another environment clears out all data in the `admin_users` table, you set the sort order for the first environment
lower (ex. `100`) and the sort order for the second environment higher (ex. `200`).

To configure your environments copy the file `vendor/swiftotter/driver/.driver/environments.yaml.dist`
to `.driver/environments.yaml` and fill it in with your environments data.

Exemplary config can be found in `vendor/swiftotter/driver/.driver/environments.yaml.example`.

#### Anonymization Configuration

Tables can be anonymized by creating `anonymize.yaml` file in `.driver/`. The following type of anonymization entities
are available in order to provide realistic data and types:

* `email`
* `company`
* `firstname`
* `lastname`
* `phone`
* `postcode`
* `street`
* `city`
* `ip`
* `general`
* `empty`

**Example File**
```
anonymize:
  tables:
    quote:
      customer_email:
        type: email
      remote_ip:
        type: ip
```

#### Post-Anonymization Values Updates Configuration

Sometimes in your database you can store in a single field some value that was originally built by concatenating
fields from different tables, eg. `full_name` or `shipping_address`. Driver allows to run some specific values updates
after anonymization of simple fields is finished. Thanks to this feature you can anonymize such complex fields too.

To create post-anonymization values updates, create `update_values.yaml` file in `.driver/`.

**Example File**
```
update-values:
  tables:
    customer_grid_flat:
      joins:
        - table: customer_entity
          alias: c
          on: c.entity_id = customer_grid_flat.entity_id
        - table: customer_address_entity
          alias: ba
          on: ba.entity_id = c.default_billing
        - table: customer_address_entity
          alias: sa
          on: sa.entity_id = c.default_shipping
      values:
        - field: name
          value: CONCAT_WS(' ', c.firstname, c.lastname)
        - field: shipping_full
          value: CONCAT(sa.street, ', ', sa.city, ', ', IFNULL(sa.region, '-'), ' ', sa.postcode, ', ', sa.country_id)
        - field: billing_full
          value: CONCAT(ba.street, ', ', ba.city, ', ', IFNULL(ba.region, '-'), ' ', ba.postcode, ', ', ba.country_id)
```

## Usage 

### Building Database Sandboxes For All Environments

Be prepared for it to take some time (on the order of hours).

```
./vendor/bin/driver run
```

### Building Database Sandbox For Single Environment

```bash
./vendor/bin/driver run build --environment=envname
```

where `envname` is one of the environments defined in `environments.yaml` config file.

### Tagging Database Sandbox

Sandboxes built with a tag will include the tag in the file name.

```bash
./vendor/bin/driver run build --tag=tagname
```

where `tagname` is an issue number (or whatever is desired).

### Download and Import Sandbox Database from S3 to staging/local environment

The below command run the export/import pipeline which download the database into your project `var/` directory,
create new database in MySQL, and import the downloaded database in it for your environment.

```
./vendor/bin/driver run import-s3 --environment=envname --tag=tagname
```

`tag` is optional.

## Contribution

We welcome you to contribute in Driver development. You can find some handy information for developers
[here](docs/development.md).
