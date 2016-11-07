TIMEDUDE
http://labs.earthpeople.se/2016/10/time-tracking-via-slack/

simple project time reporting for slack.
because time reporting sucks. and this is slightly less sucky.

get this up and running in a few simple steps.
follow along.

1. open remind.php
 - change slack token, follow this: https://api.slack.com/docs/oauth
 - change db credentials
 - change csv filepath + baseurl
 - change the buddy token, if you’re using buddy.works

2. the sql import in a new db

3. if you want to enable daily reminders
 - call remind.php via cron every minute, like this:
 * * * * * /usr/bin/php "/PATH/TO/TIMEDUDE/timedude/remind.php"

4. if you want to ping users who committed stuff to a repo but didn’t report this time to time dude, add this cron too:
 0 19 * * * /usr/bin/php "/PATH/TO/TIMEDUDE/timedude/remind.php"

5. optionally password protect /gui with htpasswd.

license:
do what you want with this. no warranties. 
if you like it and find a bug or come up with a feature - make a pull request.

note:
i know this php looks like it's from 1999 but hey it works and it's fast. plus, it's (almost) readable.
