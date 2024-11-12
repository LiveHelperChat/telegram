# Telegram bot integration

This extension allows for direct support of Telegram bots in Live Helper Chat. It supports sound messages, images, and files.

## Requirements

 * Minimum version required: Live Helper Chat 4.27v.
 * Webhooks has to be enabled - https://github.com/LiveHelperChat/livehelperchat/blob/master/lhc_web/settings/settings.ini.default.php#L86

## Upgrading

1. Run the command `php cron.php -s site_admin -e lhctelegram -c cron/update_structure`
2. Navigate to the Telegram options and activate the configuration.

## Integration tips

1. By default, HTML markdown is used for messages.
2. You can enable debug mode by unchecking the `Skip` option for the `TelegramIntegration` bot.
3. If you don't want HTML or Markdown support, you can edit the Rest API call by removing `"parse_mode":"HTML",` and change `{{msg_html_nobr}}` to `{{msg_url}}`.
4. In parse mode `"HTML"` not all tags are supported so don't use bbcode which translates to HTML which is not supported by telegram. You can always debug in Rest API enabling debug trigger. So you should have different bot or messages depending whom you are sending message. Telegram client or web widget.

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

### Watch YouTube video

[![Watch the video](https://img.youtube.com/vi/wObbEaeopRU/default.jpg)](https://youtu.be/wObbEaeopRU)

### Instructions

![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/topic-chats.png)

1. After you have completed the above steps, you have to do the following changes: In LHC back office, go back to the bot editing page and choose the departments tab. Check the departments you want to receive new chats notifications for but don't save yet.
2. Create a group chat in telegram and add your bot as an admin in the group chat.
3. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/bot-as-admin.png) 
4. Modify group chat settings and enable topics. Each customer will get their own topic.
   1. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/manage-group.png)
   2. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/enable-topics.png)
5. Create a dummy topic, send a dummy message, and copy the URL.
   1. ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/copylink.png)
6. Your link will look like `https://t.me/c/1634340846/3/4`, and your group ID will be the middle number `1634340846` with the appended `-100`, so it will be like `-1001634340846` as a concatenated string of `-100` + `1634340846`.
7. In the LHC back office, go to the main `Telegram page`, then to `Telegram bots > Edit bot departments`. Copy-paste the Telegram group ID into the `Group Chat ID` field. In the same tab, check the `This bot acts as a client` box. Now save.
8. Go to the LHC back office main Telegram page and choose `Telegram operators`. Choose Operator and Bot, or create one.
9. Start a conversation in Telegram and register yourself within the bot by typing `/register <id>`. The `<id>` should be the registered operator id from the very first column in the operators list in LHC Modules => Telegram Settings => Telegram operators.
10. If you get an error message saying it can't find an operator with that ID, and the operator was just created, clean the cache (https://onlinehelpguide.com/delete-telegram-cache-files/) and start a chat as that operator using just the website (not Telegram). Then try registering the <id> again. It should work now.
11. That's all. Just type /help to see what available commands are supported.

## General tips

* If you are planning only to use Telegram to support your site visitors, it makes sense to set up department online hours so the widget will remain always online even if you are not using the default web back office.
* For a chat to be accepted by the first message from Telegram, you have to be in `Visible` status.
* You can listen for a `/start` command by defining event listener with keyword `/start`. The Same way you can listen to any other command.
  * ![](https://raw.githubusercontent.com/LiveHelperChat/telegram/master/doc/img/start-command.png)
* Same visitor chats are written to the same topic as long Online Visitor record exists. You can extend it to be valid for one year in `Settings -> Chat configuration -> Online tracking -> How many days keep records of online users.`
* Integration supports quick reply buttons. Using these, you can make a quick navigation.
* Telegram API has limit of 20MB per file size limit. Please make sure you set the appropriate limit in lhc files sections.
* You now can set bot option to delete topic on chat close/delete to keep it clean.

Since version 3.36v, you can set your online condition to `Always online`, and you won't have to do anything else.

https://livehelperchat.com/how-to-use-telegram-if-you-are-automated-hosting-client-489a.html
