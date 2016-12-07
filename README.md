# Driver
### A database task-runner

Driver is the easy way to turn a production database into a staging or development-friendly database.
It is built with the ultimate aim of complete flexibility. Additionally, with the built-in capability
of working with RDS, Driver can run transformations on database host that is completely separate from
your production environment.

Because Driver is a task-runner, there is a good chance that you will need to create some tasks to run.
There are some additional modules out there to help you with this. You will also need to specify configuration.

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
```
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

It should run! Be prepared for it to take some time.
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



