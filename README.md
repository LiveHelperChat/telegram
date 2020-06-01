# Telegeram bot integration

This extension allow have support for telegram bot support directly in Live Helper Chat. It support sound messages, images, files.

## Requirements

Min 3.36v Live Helper Chat version.

## Install instructions

1. Clone github repository
2. Rename cloned folder of telegram to lhctelegram and put it in extension/ directory
3. Activate extension in main Live Helper Chat settings file `lhc_web/settings/settings.ini.php` file
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
6. Execute doc/install.sql on database manager or just run. You will have to wait 10 seconds for queries to be executed.
    ```
    php cron.php -s site_admin -e lhctelegram -c cron/update_structure
    ```
7. Register your bot with https://core.telegram.org/bots#6-botfather
8. Create bot in LHC back office under Modules => Telegram Settings.
9. After creating bot, go back and just click. "Set webhook"
10. That's it.

## Telegram as Support Client
This allows to use bot as gateway between normal chats on your page and bot chatting with you as a operator. In other words you can use Telegram mobile and desktop clients as Live Helper Chat support clients.

1. After you have completed the above steps, you have to do the following changes: In LHC back office, go back to the bot editing page and choose the departments tab. Check the departments you want to receive new chats notifications for. Don't save yet.
2. In same tab, check the "This bot acts as a client" box. Now save.
3. go to LHC back office main telegram page and choose "Telegram operators". Choose Operator and Bot, or create one.
4. Now start a conversation in Telegram and register yourself within bot by typing `/register <id>`. The `<id>` should be registered operator id from the very first column in the operators list in LHC Modules => Telegram Settings => Telegram operators.
5. If you get an error message saying it can't find an operator with that ID, and the operator was just created, clean the cache and start a chat as that operator using just the website (not Telegram). Then try registering the <id> again. It should work now.
6. That's all. Just type /help to see what available commands are supported.
    
## Tip
If you are planning only using telegram to support your site visitors. It makes sense to setup department online hours, so widget will remain always online even if you are not using default web back office.

Since 3.36v you can just set your online condition to "Always online" and you won't have to do anything else.

https://livehelperchat.com/how-to-use-telegram-if-you-are-automated-hosting-client-489a.html
