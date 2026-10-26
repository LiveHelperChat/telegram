<?php

namespace LiveHelperChatExtension\lhctelegram\providers;

/**
 * Forum topic icon per department (Telegram bots → Edit bot departments → icon next to each department).
 *
 * Stored in lhc_telegram_bot_dep.topic_icon: a custom emoji id from Telegram's free set of topic icons
 * (getForumTopicIconStickers — bots may use only these, no Premium needed) or "color:<value>" for the
 * default icon in one of the six colours createForumTopic allows. Empty — the default icon.
 * Mobile clients otherwise draw the first letter of the topic name, which is "[" for every chat topic.
 *
 * The icon set is cached per bot for a day in cache/telegram_topic_icons_<bot id>.json.
 */
class TelegramTopicIcons
{
    const CACHE_TTL = 86400;

    // icon_color values allowed by createForumTopic
    const COLORS = array(
        7322096 => 'Blue',
        16766590 => 'Yellow',
        13338331 => 'Violet',
        9367192 => 'Green',
        16749490 => 'Pink',
        16478047 => 'Red',
    );

    // Adds icon_custom_emoji_id or icon_color to createForumTopic data from the department setting
    public static function addIcon($bot, $department, array $data)
    {
        $depId = is_object($department) ? (int)$department->id : (int)$department;
        if (!is_object($bot) || $depId <= 0) {
            return $data;
        }

        $botDep = \erLhcoreClassModelTelegramBotDep::findOne(array('filter' => array('bot_id' => $bot->id, 'dep_id' => $depId)));
        if (!($botDep instanceof \erLhcoreClassModelTelegramBotDep)) {
            return $data;
        }

        $icon = (string)$botDep->topic_icon;
        if (strpos($icon, 'color:') === 0) {
            $color = (int)substr($icon, 6);
            if (isset(self::COLORS[$color])) {
                $data['icon_color'] = $color;
            }
        } elseif ($icon !== '' && ctype_digit($icon)) {
            $data['icon_custom_emoji_id'] = $icon;
        }

        return $data;
    }

    // Value from the form: an icon id of the set, an allowed colour or nothing
    public static function normalizeSetting($value, array $icons)
    {
        $value = trim((string)$value);

        if (strpos($value, 'color:') === 0) {
            return isset(self::COLORS[(int)substr($value, 6)]) ? 'color:' . (int)substr($value, 6) : '';
        }

        return ($value !== '' && in_array($value, $icons, true)) ? $value : '';
    }

    // emoji => custom_emoji_id
    public static function getIcons($bot)
    {
        $cacheFile = 'cache/telegram_topic_icons_' . (int)$bot->id . '.json';

        if (is_file($cacheFile) && filemtime($cacheFile) > time() - self::CACHE_TTL) {
            $icons = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($icons)) {
                return $icons;
            }
        }

        $icons = array();

        try {
            new \Longman\TelegramBot\Telegram($bot->bot_api, $bot->bot_username);
            // The library refuses empty data; Telegram ignores the extra field
            $response = \Longman\TelegramBot\Request::send('getForumTopicIconStickers', array('chat_id' => $bot->group_chat_id));
            if ($response->isOk() && is_array($response->getResult())) {
                foreach ($response->getResult() as $sticker) {
                    $emoji = is_object($sticker) ? (string)$sticker->getEmoji() : (string)($sticker['emoji'] ?? '');
                    $id = is_object($sticker) ? (string)$sticker->getCustomEmojiId() : (string)($sticker['custom_emoji_id'] ?? '');
                    if ($emoji !== '' && $id !== '') {
                        $icons[$emoji] = $id;
                    }
                }
            }
        } catch (\Throwable $e) {
            return array();
        }

        if (!empty($icons)) {
            @file_put_contents($cacheFile, json_encode($icons));
        }

        return $icons;
    }
}
