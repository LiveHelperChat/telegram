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
cd extension/lhctelegram && composer.phar update
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

## Telegram as Support Client
This allows to use bot as gateway between normal chats on your page and bot chatting with you as a operator. In other words you can use Telegram mobile and desktop clients as Live Helper Chat support clients.

1. After you have done everything since 9 step. You have to do the following changes. Go to bot editing page and choose departments. For these departments you will receive new chats notifications.
2. Check in same window "This bot acts as a client"
3. Now go to back office main telegram page and choose "Telegram operators". Choose Operator and Bot.
4. Now start conversation and register yourself within bot by typing /register <id> id should be registered operator id. Very first column in operators list.
5. That's all. Just type /help to see what available commands are supported.
    
https://livehelperchat.com/how-to-use-telegram-if-you-are-automated-hosting-client-489a.html
