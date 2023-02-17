# Development

If you're a Driver developer, here you will find some handy information that will help you with your work.

## Terminology

* **Pipeline** (specified in `.driver/pipelines.yaml`): a series of stages. Representative of the work to take a production database and transform it. These are found under the `pipelines` node. You can execute a specific pipeline with the `--pipeline` argument in the command line.
* **Stage**: groups of actions inside a pipeline. Stages are run sequentially. Right now, Driver does work this way, but actions could run in parallel.
* **Action**: Specific command to run.

## Modifying an existing pipeline

`build` is the "default" pipeline. This may be all you need. The next section will talk about the syntax for creating a new pipeline.

Here is an example of adding a custom action to the `build` pipeline:

```yaml
commands:
  reset-admin-password:
    class: \YourApplication\Driver\Transformations\ResetAdminPassword
    # ensure that:
    #  1) the composer autoloader can find this class
    #  2) your class implements \Driver\Commands\CommandInterface
    #  3) it preferably extends \Symfony\Component\Console\Command\Command

pipelines:
  build:
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

## Creating a new pipeline

The following is taken from `.driver/pipelines.yaml`. You can put this code in any of the `yaml` files that Driver reads.
Just ensure that the `pipelines` root node has no space in front of it (exactly as shown below).

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

## Debug Mode

Working with AWS RDS is nice but super slow. For development purposes you can configure local database that will act
as RDS. All you need to do is to add the following to your `.driver/connections.yaml` config file:

```yaml
connections:
  mysql_debug:
    engine: mysql
    host: localhost
    database: local_db
    username: some_user
    password: some_password
```

and then run `build` pipeline with `--debug` option, e.g.:

```shell
./vendor/bin/driver run build --environment=local --debug
```

**Watchout!** Debug database should NOT be your local project's database.

## Increasing Verbosity of Messages

By default, Driver doesn't output much information to the user.
During development, we recommend to add `-vvv` to all commands you're running, e.g.:

```shell
./vendor/bin/driver run build --environment=local --debug -vvv
```

This will show error and debug information.
