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

# Explanation of LibriCodes

A title may be ordered when:
    1. ProductAvailability = 20
    2. ProductAvailability = 22 and AvailabilityCode = TO
    3. ProductAvailability = 23 and AvailabilityCode = MD
otherwise the title needs to be deleted if it exists in the database.



## Explanation of ProductAvailability

Note: in the database "ProductAvailability" is stored in the column "AvailabilityStatus"
+--------------------+----------+
| AvailabilityStatus | count(*) |
+--------------------+----------+
| 0                  |        5 |
| 1                  |      384 |
| 10                 |    49080 |
| 11                 |     1144 |
| 20                 |   567786 |   Available
| 22                 |  1482714 |   Available as Special Order
| 23                 |   796776 |   Available as Print on Demand
| 30                 |     2618 |
| 31                 |     8282 |
| 32                 |     2565 |
| 33                 |    39349 |
| 41                 |     1618 |
| 42                 |       26 |
| 43                 |    47905 |
| 44                 |      263 |
| 45                 |      952 |
| 46                 |       17 |
| 47                 |     1044 |
| 50                 |       21 |
| 51                 |   101334 |
| 99                 |    26720 |
+--------------------+----------+



## Explanation of AvailabilityCode (Tag <j141>)
+------------------+----------+
| AvailabilityCode | count(*) | Description                             Notes
+------------------+----------+
| NULL             |   567791 | 
| AB               |      373 | Cancelled 	                            Publication abandoned after having been announced
| AD               |      269 | Available direct from publisher only 	Apply direct to publisher, item not available to trade
| CS               |        2 | Availability uncertain 	                Check with customer service
| EX               |    48953 | No longer stocked by us 	            Wholesaler or vendor only
| MD               |   796776 | Manufactured on demand 	                May be accompanied by an estimated average time to supply
| NP               |    49080 | Not yet published 	                    MUST be accompanied by an expected availability date
| NY               |     1144 | Newly catalogued, not yet in stock 	    Wholesaler or vendor only: MUST be accompanied by expected availability date
| OF               |      999 | Other format available 	                This format is out of print, but another format is available: should be accompanied by an identifier for the alternative product
| OI               |    39381 | Out of stock indefinitely 	            No current plan to reprint
| OP               |   101302 | Out of print 	                        Discontinued, deleted from catalogue
| OR               |     1620 | Replaced by new edition 	            This edition is out of print, but a new edition has been or will soon be published: should be accompanied by an identifier for the new edition
| RF               |    26719 | Refer to another supplier 	            Supply of this item has been transferred to another publisher or distributor: should be accompanied by an identifier for the new supplier
| RP               |     2563 | Reprinting 	                            MUST be accompanied by an expected availability date
| TO               |  1482714 | Special order 	                        This item is not stocked but has to be specially ordered from a supplier (eg import item not stocked locally): may be accompanied by an estimated average time to supply
| TP               |     1557 | Temporarily out of stock because publisher cannot supply 	Wholesaler or vendor only
| TU               |     9343 | Temporarily unavailable 	            MUST be accompanied by an expected availability date
| WS               |       17 | Withdrawn from sale 	                Typically, withdrawn indefinitely for legal reasons
+------------------+----------+
Note: Code (IP 	Available 	In-print and in stock) isn't represented in libri_products


