# Driver
### A database task-runner

Driver is the easy way to turn a production database into a staging or development-friendly database.
It is built with the ultimate aim of complete flexibility. Additionally, with the built-in capability
of working with RDS, Driver can run transformations on database host that is completely separate from
your production environment.

Because Driver is a task-runner, there is a good chance that you will need to create some tasks to run.
There are some additional modules out there to help you with this. You will also need to specify configuration.

Installing Driver is easy:
```
composer require swiftotter/driver
```

## Connection Information

Connection information goes into a folder named `config` or `config.d`. The files that are recognized
inside the aforementioned folders are: `pipes.yaml`, `commands.yaml`, `engines.yaml`, `connections.yaml`
and `config.yaml`.

