TIMEDUDE
simple project time reporting for slack


get this up and running in 3 simple steps.
follow along.

1. open remind.php
 - change slack token, follow this: https://api.slack.com/docs/oauth
 - change db credentials
 - change csv filepath + baseurl

2. run this sql:
CREATE TABLE `reminders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `time` varchar(5) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `times` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `channel` varchar(255) DEFAULT NULL,
  `hours` float DEFAULT NULL,
  `description` text,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

3. if you want to enable daily reminders
 - call remind.php via cron every minute, like this:
 * * * * * /usr/bin/php "/var/www/miscbox.earthpeople.se/htdocs/timedude/remind.php"

license:
do what you want with this. no warranties. 
if you like it and find a bug or come up with a feature - make a pull request.

note:
i know this php looks like it's from 1999 but hey it works and it's fast. plus, it's readable.
