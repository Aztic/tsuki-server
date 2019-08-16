# Tsuki server

Server implementation of "Tsuki" project, written in PHP, using [Codeigniter](https://www.codeigniter.com). Here's an example of the [client](https://github.com/Aztic/tsuki-client)

## Requirements
- PHP >= 7.2
- PostgreSQL
- Composer


## Install
- Clone the repository and install dependencies
```sh
$ git clone https://github.com/Aztic/tsuki-server && cd tsuki-server && composer install
```

- Create the config files, you can copy them from the example ones if you want
```
$ cd application/config
$ cp jwt_config.example.php jwt_config.php
$ cp database.example.php database.php
```

- Fill them

- Run the migrations inside `migrations` folder inside PostgreSQL

- Configure Nginx / Apache as necessary. Forbid access to the migrations folder too


## Disable register

You can disable / enable user registration changing the `'enable_register'` value inside `config.php`.
