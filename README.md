# Telegeram bot integration

This extension allow have support for telegram bot support directly in Live Helper Chat. It support sound messages, images, files.

## Install instructions

1. Clone github repository
2. Rename cloned folder of telegram to lhctelegram and put it in extension/ directory
3. Activate extension in settings/settings.ini.php file
``` 
'extensions' => 
    array (          
        'lhctelegram'
    ),
```
4. Install composer requirements with. You have to download composer or just have it installed already.
``` 
composer.phar update
``` 
5. Clean cache. Just click clean cache in Live Helper Chat back office.
6. Execute doc/install.sql on database manager or just run
    ```
    php cron.php -s site_admin -e lhctelegram -c cron/update_structure
    ```
7. Register your bot with https://core.telegram.org/bots#6-botfather
8. Create bot in LHC back office.
9. After creating bot, go back and just click. "Set webhook"
10. That's it.
