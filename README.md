# monocle-migrator
A tool which helps migrate your data between Postgres <-> MySQL

## Requirements
1. [PHP](https://secure.php.net/) >= 7.1
2. [Composer](https://getcomposer.org/)

## Installation
1. Run `composer install`
2. Copy `credentials.sample.yml` to `credentials.yml` and edit accordingly

## Running
```
$ ./migrator
[2018-05-28 17:37:01] INFO Connecting to pgsql...
[2018-05-28 17:37:01] INFO Connecting to mysql...
[2018-05-28 17:37:01] INFO Querying forts...
[2018-05-28 17:37:01] INFO Migrating forts...
[2018-05-28 17:37:49] 8292/8292 [============================] 100% 48 secs
[2018-05-28 17:37:49] INFO Querying pokestops...
[2018-05-28 17:37:49] INFO Migrating pokestops...
[2018-05-28 17:40:53] 27444/27444 [============================] 100% 3 mins
[OK] [2018-05-28 17:40:53] Done.
```

## Coverage
Right now, only the following tables are being migrated:

- [ ] accounts
- [ ] common
- [x] forts
- [ ] fort_sightings
- [ ] gym_defenders
- [ ] mystery_sightings
- [ ] parks
- [x] pokestops
- [ ] raids
- [ ] sightings
- [ ] spawnpoints
- [ ] weather
