# db-cinema [WIP]
Simple movie MySQL database with data taken from TMDB API  
Credit to:  

![alt text](https://www.themoviedb.org/assets/2/v4/logos/v2/blue_long_1-8ba2ac31f354005783fab473602c34c3f4fd207150182061e425d366e4f34596.svg "TMDB logo")

## Installation  
Requires  
- `php`  
- `php-mysql`  
- `php-curl`  
- A `mysql` DMBS  

```
$ sudo apt install php php-mysql php-curl
```  
Clone the repository:  
```
$ git clone https://github.com/filippo-viti/db-cinema
```  
Install Composer and pull the dependencies:
```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
$ cd db-cinema
$ composer install
```  
Add your TMDB API key:
```
$ echo -n <key here> > api_key.txt
```
Import the databes in your system using the file `cinema.sql`

## Usage
To populate the database:  
```
$ php populate.php
```