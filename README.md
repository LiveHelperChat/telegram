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
4. Clean cache. Just click clean cache in Live Helper Chat back office.
5. Execute doc/install.sql on database manager or just run
    ```
    php cron.php -s site_admin -e lhctelegram -c cron/update_structure
    ```
6. Register your bot with https://core.telegram.org/bots#6-botfather
7. Create bot in LHC back office.
8. After creating bot, go back and just click. "Set webhook"
9. That's it.
