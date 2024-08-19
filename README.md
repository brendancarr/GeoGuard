
![GeoGuard IP2Location Firewall](https://raw.githubusercontent.com/brendancarr/GeoGuard/main/GeoGuard.jpg)

# GeoGuard

[![GitHub release (latest by date including pre-releases)](https://img.shields.io/github/v/release/navendu-pottekkat/awesome-readme?include_prereleases)](https://img.shields.io/github/v/release/navendu-pottekkat/awesome-readme?include_prereleases)
[![GitHub last commit](https://img.shields.io/github/last-commit/brendancarr/GeoGuard)](https://img.shields.io/github/last-commit/brendancarr/GeoGuard)

GeoGuard is a php-based class to be used as a firewall as part of your custom project. It will log connection attempts and block them at the .htaccess level.

It utilizes two methods - one of them is IP tracking, the other is IP Geolocation using IP2Location's API. https://www.ip2location.io/

You can drop this into place in your custom project, and once an IP address is blocked, either because of country code or because of too many failed login attempts, it will be added to the .htaccess file, offloading the need to even load up your script and offering blocking at the Apache level.


# Installation
[(Back to top)](#table-of-contents)

Simply download the repo, unzip to a folder on your server, and you're ready to go. You will need to be able to access the settings screen in order to add your IP2Location API key and tweak your settings. To do this:

```shell
include 'geoguard_settings.php';
```

This will let you view all of the areas you can manage, including
- Number of allowed attempts
- The number of seconds it waits after each failed login attempt
- The Admin IP, which is automatically whitelisted
- The directory which contains the .htaccess file
- A list of blocked countries
- A list of whitelisted and blacklisted IPs
- Login attempts



# Quick Start Demo

![Settings Screen](https://raw.githubusercontent.com/brendancarr/GeoGuard/main/GeoGuardSettings.jpg)

Here's the settings screen that you will use to manage the class.

# Usage
[(Back to top)](#table-of-contents)

To start using GeoGuard, just call this file at the top of your project. 
```shell
require_once 'GeoGuard.php';
$geoguard = new GeoGuard();
```
When you run the following after a login attempt, whether it's a POST or some other method, it will check the IP address against the country code. If the IP is already blacklisted, it will be located in the .htaccess file, so they won't even hit the login page in the first place.

```shell
$geoguard->performIPCheck();
```
It will log the IP attempt unless it is a successful attempt, in which case you're going to clear the login attempts. You can pass an IP into this, or just leave it blank and it will check the IP when it runs the function.

```shell
$geoguard->clearAttempts();
```

To manually unblock:
```shell
$geoguard->unblockIP('123.456.789.000');
```
To manually whitelist: 
```shell
$geoguard->addToWhitelist('123.456.789.000');
$geoguard->removeFromWhitelist('123.456.789.000');
```


# Development
[(Back to top)](#table-of-contents)

Feel free to add or update to the code, or point out if you see anything that needs to be changed.


# Contribute
[(Back to top)](#table-of-contents)

I would love to add a few different options here - one of them being an email notification when an IP has been blocked.

