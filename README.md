# Holidays Dates

This is test project, I implement it by using PHP Laravel 5.3 Framework and PHPUnit

## THE TASK:
- Convert https://bitbucket.org/croudtech/holidayapi into a database-driven Laravel 5.3 
- A command to import the JSON data into an appropriately structured database table.
- An additional flag in the table to indicate if a holiday is an official bank holiday or not.
- All the same API functionality plus the option to show only official bank holidays.
- Appropriate HTTP response codes.
- A suite of unit tests 


## Required
1. php >= 5.6 and if you used apache you must have RewriteEngine On
2. MySql Server 5.7

# Installation
It recommend you have Composer manager. 
Open terminal and run the following command
> composer update 

This will install Laravel 5.3 with all dependencies.

## prepare database
1. Create empty database in your MySql server
2. open file **.env** and set the Database Connection Configuration
3. open open terminal and run the following command.

``` 
		php artisan migrate
```
This will create table holidays in DB

# Import Data from JSON Files
- All jason files are in **./storage/data** as I download it from https://bitbucket.org/croudtech/holidayapi
- for the test scope please it must be import the  **./storage/data/US.json** So run the following command:
- 
``` 
		php artisan import:holidays --country=us
```
This will import the **./storage/data/US.json** into DB.

# Structure 
## Command
I have create command in class **App\Console\Commands\ImportHolidays** with option and choice
Every command read the country_code (ISO Alpha-2) from user input, search if there is file with [country_code].json "./storage/data" path with holidays rules,and then import data from json file into Database.

>  
**For the public holidays I used a general public holidays for all countries
	for example: January 1st, EASTER, December 25th**
    


At the end it rename the file into [country_code]_YYYY-mm-dd.json and move it into **./storage/data/backups**
for example the US.json will be **US_2018-03-01.json**

The command that you can run as the following:
``` 
		php artisan import:holidays --country=
        	OR
        import:holidays --country= all
```
These commands you can used it for single file or all.
Also you can run 
``` 
		php artisan import:holidays

```
and chose wich file or action to run.

## Libraries
In folder **app\Libraries** I have create class **HolidaysApi** wich is the implement of the functionalities as in https://bitbucket.org/croudtech/holidayapi with more change.
This class throw two type Exception wich are in **app\Libraries\Exception** folder
- **InvalidParameterException** and
- **MissingParametersException**

it has only one public function getHolidays() wich return an array with all holidays base in the arguments

## Routes
The routes for the appi are as 
>  
** 
/holidays
/holidays/countrcode/year
    **

Where:
	country_code: strin ISO Alpha-2
    year: number with 4 digits
All routes are used GET methods and dispatch to **HolidaysController** index method

the route response json
- 400: when url not found

## HolidaysController
This controller has one method **index**

### index method
It accept three arguments 
- **$request**
- **$country**: dafault= null
- **$year**: default= null

Also' it's accept other options paramers in the request like month, day, upcoming,public,...

This functin validate all arguments and paramers and then call the the  and the HolidaysApi libreries.

the function return Response http json
with the following status code
- 200: succeeded
- 403: Forbidden Request, when required argument or parametr is missed
- 406: Paremetr or argument is invalid
- 500: other exeption

every json response has the status filed with data or error message
### optional parametrs
the optional paramers are the foloowing:
```
     *    $args[
     *        "month"         : integer [optional]
     *        "day"           : integer [optional]
     *        "previous"      : [optional] if is set then true
     *        "upcoming"      : [optional] if is set then true
     *        "public"        : [optional] if is set then true, false: will show all
     *        "cache"         : [optional] if set then true
     *        "cache_minutes" : integer [optional]
     *        ]
```
# usage example

## get all list
>  
- **holidays/us/2018**: this return all holidays in 2018 for US
- **holidays/?country=us&year=2018**: same as above

## get all public list
>  
- **holidays/us/2018/?public=[anything]***: this return all public holidays in 2018 for US
- **holidays/?country=us&year=2018&public=[anything]**: same as above

## get all month
>  
- **holidays/us/2018/?montc=3**
- **holidays/?country=us&year=2018&montc=3**

## get all single day
>  
- **holidays/us/2018/?montc=3&day=20**:
- **holidays/?country=us&year=2018&montc=3&day=20**


## get all previous single day
>  
- **holidays/us/2018/?montc=3&day=20&previous=[anything]**:
- **holidays/?country=us&year=2018&montc=3&day=20&previous=[anything]**


## get all upcoming single day
>  
- **holidays/us/2018/?montc=3&day=20&upcoming=[anything]**:
- **holidays/?country=us&year=2018&montc=3&day=20&upcoming=[anything]**

## TEST
For test I use phpUnit. to run the test it's required to have import the us.json file data into DB
So make sure you have done it by run the following command
``` 
		php artisan import:holidays --country=us

```
The test include three files class
- **RouteTest**: for test page not found
- **HolidaysControllerTest**: for test the response json when we have status code 200
- **HolidaysControllerExeptionsTest**: for test the other status code when we have invalidate errors

all tests has 9 tests, 63 assertions




