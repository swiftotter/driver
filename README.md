# Driver
### A database task-runner

Driver is the easy way to turn a production database into a staging or development-friendly database.
It is built with the aim of complete flexibility. Additionally, with the built-in capability
of working with RDS, Driver can run transformations on database host that is completely separate from
your production environment.

Because Driver is a task-runner, there is a good chance that you will need to create some tasks to run.
There are some additional modules out there to help you with this. You will also need to specify configuration.

## TL;DR

Driver resides on your production machine, preferrably with your website's codebase (via composer). You setup a cron job to run Driver.

* **Driver connects to your production database ONCE via `mysqldump`.**
* Driver dumps that to a file on your system.
* Driver creates a RDS instance and pushes the database dump up to RDS (via **https**).
* Driver runs whatever actions you would like (configured globally or per environment).
* Driver dumps the transformed data, zips it and pushes it up to S3.

For a 3-5GB database, this process could take 2 hours or more. The downtime (associated with `mysqldump`'s table locking) is only a couple of minutes. It take a while to run, but it also uses very few resources and is a background process so you won't be waiting for it.

## Quickstart

Installing Driver is easy:
```
composer require swiftotter/driver
```

Configuring Driver is easy. In the folder that contains your `vendor/` folder, create a folder called `config`.
First, you need to create an Amazon AWS account. We will be using RDS to perform the data manipulations. It is
recommended to create a new IAM user with appropriate permissions to access EC2, RDS and S3 (exact permissions
will be coming).

Place inside it a file with the following information (replacing all of the brackets and their content):
```yaml
connections:
  database: mysql
  mysql:
    engine: mysql
    database: [DATABASE_NAME]
    user: [DATABASE_USER]
    password: [DATABASE_PASSWORD]
  s3:
    engine: s3
    key: [IAM_KEY]
    secret: [IAM_KEY]
    bucket: [YOUR_BUCKET]
    compressed-file-key: sample.gz
    uncompressed-file-key: sample.sql
    region: us-east-1
  rds:
    key: [IAM_KEY]
    secret: [IAM_KEY]
    region: us-east-1
  ec2:
    key: [IAM_KEY]
    secret: [IAM_KEY]
    region: us-east-1
```

It should run! Be prepared for it to take some time (on the order of hours).
```
./vendor/bin/driver run
```

## Connection Information

Connection information goes into a folder named `config` or `config.d`. The files that are recognized
inside these folders are:
* `pipelines.yaml`
* `commands.yaml`
* `engines.yaml`
* `connections.yaml`
* `config.yaml`

The filenames of these files serve no purpose other than a namespace. The delineation of the configuration
happens inside each file. For example, in `pipelines.yaml`, there is a `pipelines` node as the root element.
In this way, the YAML itself is providing namespaces. This also has the benefit for you of being able to put
all of your updates in one file (for example, `config.yaml`).

Driver looks in quite a few places for configuration files. As an example, let's say your application is
stored in `/var/www/`. Your vendor directory is `/var/www/vendor/` and, of course, Driver's home is
`/var/www/vendor/swiftotter/driver`. As such, Driver will look in the following locations for configuration
files:

* `/var/www/config/`
* `/var/www/config.d/`
* `/var/www/vendor/*/*/config/`
* `/var/www/vendor/*/*/config.d/`

You can symlink any file you want here. Keep in mind that these files do contain sensitive information and
it is necessary to include a `.htaccess` into that folder:
`Deny from all`

## AWS Setup

Driver's default implementation is to use AWS for the database transformation (RDS) and the storage of the transformed
databases (S3).

You will need to do two things in your AWS control panel:
1. Create a new policy.
2. Assign that policy to a new user.

### Policy Creation

Open your control panel and go to IAM. Click on the Policies tab on the sidebar. Choose to Create New Policy.
Select Create Your Own Policy (if you want to use the one below) and enter the following code.

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ec2:AuthorizeSecurityGroupIngress",
                "ec2:CreateSecurityGroup",
                "s3:GetObject",
                "s3:PutObject",
                "rds:CreateDBInstance",
                "rds:DeleteDBInstance",
                "rds:DescribeDBInstances"
            ],
            "Resource": [
                "*"
            ]
        }
    ]
}
```

### User Creation

In the IAM control panel, click on the Users tab. Select Add user. Choose a username. This will only be seen by you
in the control panel. Check the Programmatic access as Driver will be needing a access key ID and a secret access key.
Select Add existing policies directly and choose your newly-created policy. Review it and then create the user.

Place the Access key ID and Secret access key in your configuration.


### Connection Reference

```yaml
configuration:
  compress-output: true # if set, the output will be compressed and the compressed-file-key will be used.

connections:
  database: mysql # Currently, this is the only supported engine.
  webhooks:
    post-url: https://whatever-your-site-is.com # When the process is complete, Driver will ping this url.
    transform-url: # During the transformation process, Driver will ping this url with connection information.
                   # You could write your own scripts to be executed at this url.
    auth:
      user: # for HTTP basic authentication
      password: # for HTTP basic authentication
  mysql: # Source database connection information
    database: your_database_name # REQUIRED
    charset: # defaults to utf8
    engine: mysql
    port: # defaults to 3306
    host: # defaults to 127.0.0.1
    user: # REQUIRED: database username
    password: # REQUIRED: database password
    dump-path: /tmp # Where to put the dumps while they are transitioning between the server and RDS
  s3:
    engine: s3
    key: # REQUIRED: your S3 login key (can be the same as RDS if both access policies are allowed)
    secret: # REQUIRED: your S3 login secret
    bucket: # REQUIRED: which bucket would like this dumped into?
    region: # defaults to us-east-1
    compressed-file-key: # name in the bucket for a compressed file. 
    uncompressed-file-key: # name for an uncompressed file.
    # It is recommended to include {{environment}} in the filename to avoid multiple environments overwriting the file.
  rds:
    key: # REQUIRED: your RDS login key
    secret: # REQUIRED: your RDS login secret
    region: #defaults to us-east-1
    ## BEGIN NEW RDS INSTANCE:
    instance-type: # REQUIRED: choose from left column in https://aws.amazon.com/rds/details/#DB_Instance_Classes
    engine: MySQL
    storage-type: gp2
    ## END NEW RDS INSTANCE
    ## BEGIN EXISTING RDS INSTANCE:
    instance-identifier:
    instance-username:
    instance-password:
    instance-db-name:
    security-group-name:
    ## END EXISTING RDS INSTANCE
```

### Terminology

* **Pipeline** (specified in `pipelines.yaml`): a series of stages. Representative of the work to take a production database and transform it. These are found under the `pipelines` node. You can execute a specific pipeline with the `--pipe-line` argument in the command line.
* **Stage**: groups of actions inside of a pipeline. Stages are run sequentially Right now, Driver does work this way, but actions could run in parallel. 
* **Action**: Specific command to run.

### Modifying a existing pipeline

`default` is the "default" pipeline. This may be all you need. The next section will talk about the syntax for creating a new pipeline.

Here is an example of adding a custom action to the `default` pipeline:

```yaml
commands:
  reset-admin-password:
    class: \YourApplication\Driver\Transformations\ResetAdminPassword
    # ensure that:
    #  1) the composer autoloader can find this class
    #  2) your class implements \Driver\Commands\CommandInterface
    #  3) it preferably extends \Symfony\Component\Console\Command\Command

pipelines:
    default:
      - name: global-commands
      # you can add stages or use an existing one. global-commands runs
      # after the data has been pushed into RDS and before any transformations
      # run. Keep in mind, you can create a new stage prefixed with "repeat-"
      # and it will run it once per environment.
        actions:
          - name: reset-admin-password
            # notice this name matches the name we added in commands
            sort: 100
```

### Creating a new pipeline

The following is taken from `config/pipelines.yaml`. You can put this code in any of the `yaml` files that Driver reads. Just ensure that the
`pipelines` root node has no space in front of it (exactly as shown below).

```yaml
pipelines: # root node
  YOUR_PIPELINE_NAME: # pipeline span name
    - name: setup # pipeline stage name
      sort: 100 # sort order
      actions: # stages / actions to run
        - name: connect
          sort: 100
        - name: check-filesystem
          sort: 200
        - name: start-sandbox
          sort: 300

    - name: import
      sort: 200
      actions:
        - name: export-data-from-system-primary
          sort: 100
        - name: import-data-into-sandbox
          sort: 200

    - name: global-commands
      sort: 300
      actions:
        - name: empty
          sort: 1

    - name: repeat-commands
      sort: 400
      actions:
        - name: run-transformations
          sort: 1000

    - name: repeat-export
      sort: 400
      actions:
        - name: export-data-from-sandbox
          sort: 100
        - name: upload-data-to-s3
          sort: 200

    - name: shutdown
      sort: 500
      actions:
        - name: shutdown-sandbox
          sort: 100

```


### Environments

Ok, so Driver sounds really cool to create you a staging database. But, what about a database for the devs? There is a few changes for that: we want to clear out all other admin users (and reset the remaining admin user's password) as well as set unique urls for the website and possibly other things.

There is a solution for that, too. Environments allow you to easily make modifications to the database, per-site.

**Note:** you can execute specific commands per environment. These changes do not revert for each environment, but rather they are applied according to their sort order. If one environment uses the data in the `admin_users` table, and another environment clears out all data in the `admin_users` table, you set the sort order for the first table lower (ex. `100`) and the sort order for the second table higher (ex. `200`).


#### Environment Reference:

```yaml
environments:
  ENVIRONMENT_NAME:
    sort: # lower runs sooner, higher runs later
    transformations:
      TABLE_NAME:
        - "UPDATE {{table_name}} SET value = 'test-value' WHERE path = 'id';"
    ignored_tables:
        # These are ignored in the final dump: mysqldump ... --ignored-tables=DATABASE.table_1
        - table_1
        - table_2
```

**Notes:**
* The `{{table_name}}` is substituted for the `TABLE_NAME` reference above. Driver will look for a table that **ends** with `TABLE_NAME`. For example, if your `TABLE_NAME` is `core_config_data`, Driver will search for a table in the database that ends with `core_config_data`. Thus, `mage_core_config_data`, `sample_core_config_data` and `core_config_data` would all match.
