# git.engine-alpha.org

This repository contains the default build bot for Engine Alpha.

## installation

Clone the repository, run `composer install`, and add the services in `conf` to your SupervisorD's `conf.d`. If you want to run the application behind Nginx as reverse proxy, you can find a sample configuration in `conf` as well.