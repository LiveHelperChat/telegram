<?php

namespace LiveHelperChatExtension\lhctelegram\providers;
#[\AllowDynamicProperties]
class TelegramLiveHelperChatActivator {

    public static function remove()
    {
        if ($incomingWebhook = \erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'TelegramIntegration']])) {
            $incomingWebhook->removeThis();
        }

        if ($restAPI = \erLhcoreClassModelGenericBotRestAPI::findOne(['filter' => ['name' => 'TelegramIntegration']])) {
            $restAPI->removeThis();
        }

        if ($botPrevious = \erLhcoreClassModelGenericBotBot::findOne(['filter' => ['name' => 'TelegramIntegration']])) {
            $botPrevious->removeThis();

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.desktop_client_admin_msg', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.web_add_msg_admin', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.before_auto_responder_msg_saved', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            self::removeCallbackEvents($botPrevious->id);
        }
    }

    // Events which answer callback queries and optionally hide inline buttons after a click
    private static $callbackEvents = ['chat.webhook_incoming_chat_continue', 'chat.webhook_incoming_chat_started'];

    private static function removeCallbackEvents($botId)
    {
        foreach (self::$callbackEvents as $eventName) {
            foreach (\erLhcoreClassModelChatWebhook::getList(['filter' => ['event' => $eventName, 'bot_id' => $botId]]) as $event) {
                $event->removeThis();
            }
        }
    }

    /**
     * Attributes like access_token or hide_buttons_on_click are edited by the administrator.
     * Keep their values on update and only add attributes which are missing.
     */
    private static function keepAttributeValues($currentConfiguration, $templateConfiguration)
    {
        $current = json_decode((string)$currentConfiguration, true);
        $template = json_decode((string)$templateConfiguration, true);

        if (!is_array($current) || !is_array($template) || !isset($current['attr']) || !is_array($current['attr'])) {
            return $templateConfiguration;
        }

        $currentValues = [];
        foreach ($current['attr'] as $attr) {
            if (isset($attr['key'])) {
                $currentValues[$attr['key']] = $attr['value'];
            }
        }

        foreach ($template['attr'] as & $attr) {
            if (isset($attr['key']) && array_key_exists($attr['key'], $currentValues)) {
                $attr['value'] = $currentValues[$attr['key']];
            }
        }
        unset($attr);

        return json_encode($template);
    }

    public static function installOrUpdate()
    {
        $incomingWebhook = \erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'TelegramIntegration']]);

        $incomingWebhookContent = file_get_contents('extension/lhctelegram/doc/telegram/incoming-webhook.json');
        $content = json_decode($incomingWebhookContent,true);

        if (!$incomingWebhook) {
            $incomingWebhook = new \erLhcoreClassModelChatIncomingWebhook();
            $incomingWebhook->setState($content);
            $incomingWebhook->dep_id = 1;
            $incomingWebhook->name = 'TelegramIntegration';
            $incomingWebhook->identifier = \erLhcoreClassModelForgotPassword::randomPassword(20);
        } else {
            $dep_id = $incomingWebhook->dep_id;
            $identifier = $incomingWebhook->identifier;
            $content['configuration'] = self::keepAttributeValues($incomingWebhook->configuration, $content['configuration']);
            $incomingWebhook->setState($content);
            $incomingWebhook->dep_id = $dep_id;
            $incomingWebhook->identifier = $identifier;
            $incomingWebhook->name = 'TelegramIntegration';
        }
        $incomingWebhook->saveThis();

        // RestAPI
        $restAPI = \erLhcoreClassModelGenericBotRestAPI::findOne(['filter' => ['name' => 'TelegramIntegration']]);
        $content = json_decode(file_get_contents('extension/lhctelegram/doc/telegram/rest-api.json'),true);

        if (!$restAPI) {
            $restAPI = new \erLhcoreClassModelGenericBotRestAPI();
        }

        $restAPI->setState($content);
        $restAPI->name = 'TelegramIntegration';
        $restAPI->saveThis();

        if ($botPrevious = \erLhcoreClassModelGenericBotBot::findOne(['filter' => ['name' => 'TelegramIntegration']])) {
            $botPrevious->removeThis();
        }

        $botData = \erLhcoreClassGenericBotValidator::importBot(json_decode(file_get_contents('extension/lhctelegram/doc/telegram/bot-data.json'),true));
        $botData['bot']->name = 'TelegramIntegration';
        $botData['bot']->updateThis(['update' => ['name']]);

        foreach ($botData['triggers'] as $triggerItem) {
            $actions = $triggerItem->actions_front;
            $updatedActions = false;

            foreach ($actions as & $action) {
                if (isset($action['type']) && $action['type'] === 'restapi' && isset($action['content']['rest_api'])) {
                    $action['content']['rest_api'] = $restAPI->id;
                    $updatedActions = true;
                }
            }
            unset($action);

            if ($updatedActions === true) {
                $triggerItem->actions_front = $actions;
                $triggerItem->actions = json_encode($actions);
                $triggerItem->updateThis(['update' => ['actions']]);
            }
        }

        $trigger = $botData['triggers'][0];

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.desktop_client_admin_msg', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/lhctelegram/doc/telegram/chat.desktop_client_admin_msg.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.web_add_msg_admin', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/lhctelegram/doc/telegram/chat.web_add_msg_admin.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();
        
        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.before_auto_responder_msg_saved', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/lhctelegram/doc/telegram/chat.before_auto_responder_msg_saved.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();

        // Answer callback queries so Telegram stops the button spinner and hide buttons after a click
        if ($botPrevious) {
            self::removeCallbackEvents($botPrevious->id);
        }

        $callbackTrigger = null;
        foreach ($botData['triggers'] as $triggerItem) {
            if ($triggerItem->name == 'Answer callback query') {
                $callbackTrigger = $triggerItem;
                break;
            }
        }

        if ($callbackTrigger !== null) {
            foreach (self::$callbackEvents as $eventName) {
                $event = new \erLhcoreClassModelChatWebhook();
                $event->setState(json_decode(file_get_contents('extension/lhctelegram/doc/telegram/' . $eventName . '.json'),true));
                $event->bot_id = $botData['bot']->id;
                $event->trigger_id = $callbackTrigger->id;
                $event->saveThis();
            }
        }
    }
}

?>
