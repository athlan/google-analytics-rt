# google-analytics-rt

Google Analytics Realtime thin client.

**Author**

Piotr 'Athlan' Pelczar

Created with ❤ for majsterkowo.pl to support the [Analogue Counter of Active Users on the website](https://majsterkowo.pl/analogowy-licznik-osob-przebywajacych-na-stronie-internetowej/).

## Installation

1. Clone repository.
2. `composer install`
3. Go to configuration section.
4. (optional) Upload to the server where you want to host the script.
5. Make `/var/cache` writable for web server process (chmod 777 or proper write access)

## Configuration

### Create Google API Console application

1. Create **Project** in Google Console (skip if you have one) and enable **Google Analytics API**. ([link to the wizard](https://console.developers.google.com/flows/enableapi?apiid=analytics&credential=client_key)) 
2. Create the **Service Account** and select the Project when prompted. ([link to the wizard](https://console.developers.google.com/projectselector/iam-admin/serviceaccounts))
   * Type the name of the account and select *Furnish a new private key*.
   * Download the .json credentials file. Remember the client email like *your-client-name@your-project-id.iam.gserviceaccount.com*

### Configure Google Analytics account

3. Login to the Google Analytics
   * Go to the Administration and add the new read-only user which email is as the Service Account email you just created (email from step 2).
   * Go to the your webpage administration, and copy the View ID from View Settings. Write it down.
   
### Configure the script

4. Copy the `/config/config.php.diff` to the `/config/config.php`
   * Copy the downloaded .json credentials file to the `/config/credentials` and point this file in `config.php`.
5. Your service is configured. You can access the stats by invoking address:
    ```
    http://localhost/your-path-of-this-project/rt-visitors.php?profileId=XYZ`
    ```
    Where `profileId` is your View ID from Google Analytics.

### Additional configuration

#### Caching

In configuration file you can configure caching:

* Change TTL of cache (0 to disable).
* Change cache driver (default is Filesystem) options like a directory to store cache.
* Change cache driver to another one ([list of drivers](http://www.stashphp.com/Drivers.html)).


### Known issues

1. **cURL error 60: SSL certificate problem: unable to get local issuer certificate**

    You are not accepting the Google certificate.
    
    Possible solutions:
    * Update your certificates on the on the operating system.
    * Override your [`curl.cainfo` property](http://php.net/manual/en/curl.configuration.php#ini.curl.cainfo) on the runtime or in php.ini file and point to the downloaded [cacert.pem](https://curl.haxx.se/ca/cacert.pem).
    * Just uncomment `cacert` settings in the config file and. Point to the downloaded [cacert.pem](https://curl.haxx.se/ca/cacert.pem) file.This file is bundled in `/config/cacert.pem`.
    
    Read more:
    https://github.com/google/google-api-php-client/issues/1011