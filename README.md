# Telegram bot integration

This extension allow have support for telegram bot support directly in Live Helper Chat. It support sound messages, images, files.

## Requirements

Min 4.27v Live Helper Chat version.

## Upgrading

1. Run the command `php cron.php -s site_admin -e lhctelegram -c cron/update_structure`
2. Navigate to the Telegram options and activate the configuration.

## Tips

1. By default, HTML markdown is used for messages.
2. You can enable debug mode by unchecking the `Skip` option for the `TelegramIntegration` bot.
3. If you don't want HTML or Markdown support, you can edit the Rest API call by removing `"parse_mode":"HTML",` and change `{{msg_html_nobr}}` to `{{msg_url}}`.

## Installation instructions

1. Clone the GitHub repository.
2. Rename the cloned folder to "lhctelegram" and place it in the "extension/" directory.
3. Activate the extension by adding `'lhctelegram'` to the `'extensions'` array in the `lhc_web/settings/settings.ini.php` file.
``` 
'extensions' => 
    array (          
        'lhctelegram'
    ),
```
4. Install the composer requirements by running:
``` 
cd extension/lhctelegram && composer.phar update
``` 
5. Clean the cache in the Live Helper Chat back office.
6. Execute the `doc/install.sql` on the database manager or run the command:
    ```
    php cron.php -s site_admin -e lhctelegram -c cron/update_structure
    ```
7. Register your bot with BotFather: https://core.telegram.org/bots#6-botfather
8. In the Telegram options, activate webhook configurations.
9. Create a bot in the LHC back office under Modules => Telegram Settings.
10. After creating the bot, click "Set webhook".
11. That's it! The integration should be set up.

## Using Telegram as a Support Client
This feature allows you to use the Telegram bot as a gateway between chats on your website and your operators. Here's an example setup:

Example of final setup. Each customer get's it's own topic. and you can use telegram to chat with your customers directly. Files are also supported!

![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/topic-chats.png)

1. After you have completed the above steps, you have to do the following changes: In LHC back office, go back to the bot editing page and choose the departments tab. Check the departments you want to receive new chats notifications for. Don't save yet.
2. Create a group chat in telegram and add your bot as an admin in the group chat.
3. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/bot-as-admin.png) 
4. Modify group chat settings and enable topics. Each customer will get it's own topic.
   1. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/manage-group.png)
   2. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/enable-topics.png)
5. Create a dummy topic and send a dummy message and copy url.
   1. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/copylink.png)
6. Your link will look like `https://t.me/c/1634340846/3/4` and your group id will be middle number `1634340846` with appended `-100` so it will be like `-1001634340846` as concat string `-100` + `1634340846`
7. LHC back office main telegram page > Telegram bots > Edit bot departments : Copy-paste the telegram group id to the field 'Group Chat ID'. In same tab, check the "This bot acts as a client" box. Now save.
8. Go to LHC back office main telegram page and choose "Telegram operators". Choose Operator and Bot, or create one.
9. Now start a conversation in Telegram and register yourself within bot by typing `/register <id>`. The `<id>` should be registered operator id from the very first column in the operators list in LHC Modules => Telegram Settings => Telegram operators.
10. If you get an error message saying it can't find an operator with that ID, and the operator was just created, clean the cache (https://onlinehelpguide.com/delete-telegram-cache-files/) and start a chat as that operator using just the website (not Telegram). Then try registering the <id> again. It should work now.
11. That's all. Just type /help to see what available commands are supported.

## Tip
If you are planning only using telegram to support your site visitors. It makes sense to setup department online hours, so widget will remain always online even if you are not using default web back office.

Since 3.36v you can just set your online condition to "Always online" and you won't have to do anything else.

https://livehelperchat.com/how-to-use-telegram-if-you-are-automated-hosting-client-489a.html
