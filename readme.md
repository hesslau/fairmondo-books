Documentation for FairmondoBooks
==============================

Stack: PHP7, Laravel5, MySQL, Bash

# Installation

## Install Dependencies:
To install the required dependencies run

    $ apt-get install composer php7.0 mysql-server php-mbstring php-dom php-zip php-intl php-fpm php7.0-mysql

on the command line.

## Configuration:
Edit `config/ftp.php` for FTP connection details and `config/database.php` for database credentials.


## Installation:
    $ composer install
    $ php artisan migrate

# Usage
## Commands
### Import
To start the import run `$ ./import` on the command line and it will import the catalog updates and KTEXT and CBILD annotations. Ideally you would prepare a cronjob that runs this command for you (at least once a day).

### Export
To generate the CSV files run `$ ./export` on the command line.
If the export has finished successfully, the CSV files will be zipped in the folder `storage/app/export`.

## Logs & Bugs
### laravel.log
Fatal errors are written into `storage/logs/laravel.log`. Depending on how much memory is available to your PHP installation the script will die because of memory exhaustion. This is simply because PHP is not meant to do long operations, there is [no real fix for this](https://software-gunslinger.tumblr.com/post/47131406821/php-is-meant-to-die). If this happens during an import it's not much of an issue, as the PHP script will be restarted by the bash script. If it happens during an export the export will have to be repeated. So far this hasn't happend during the testing phase.


# What does the code do?

The two significant tables are `libri_products` and `fairmondo_products`. `libri_products` is the up-to-date table which contains all the significant data for each product that had a record in the catalog updates. `fairmondo_products` contains all the products that have already been exported, so it should contain the same information as the marketplace. When running an export, all the products in `libri_products` are converted into `fairmondo_products` and written into CSV files which are ready to be imported into the Fairmondo Marketplace.

## Implemented Rules for import

From the Libri Catalog all products will be offered that meet the following conditions:

* ProductReference is not Null
* DistinctiveTitle is not Null
* ProductForm is either 'BA', 'BB', 'BC', 'BG', 'BH', 'BI', 'BP', 'BZ', 'AC', 'AI', 'VI', 'VO', 'ZE', 'DA', 'DG' or 'PC'
* AvailabilityStatus is either 20,21 or 23
* AudienceCodeValue is not 16, 17 or 18


