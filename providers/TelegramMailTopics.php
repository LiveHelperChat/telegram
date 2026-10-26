<?php

namespace LiveHelperChatExtension\lhctelegram\providers;

/**
 * Mail conversations in forum topics (bot option "Create topics for mail conversations").
 *
 * A new e-mail in a department the operator bot serves opens a topic with a card (who wrote,
 * subject, text, attachments, link to the back office); further e-mails of that conversation
 * are posted into the same topic. Text an operator writes in the topic is sent to the customer
 * as a reply e-mail through erLhcoreClassMailconvValidator::sendReply — the same call the back
 * office uses, so the reply is threaded, sent from the mailbox and copied to its Sent folder.
 *
 * Topics are lhc_telegram_chat rows with type = 2 and chat_id = mail conversation id
 * (chats use type = 1). last_msg_id is the last mail message posted into the topic.
 */
class TelegramMailTopics
{
    const TYPE_MAIL = 2;

    // Longer e-mails are cut; the full text is one click away in the back office.
    const BODY_LIMIT = 3000;

    public static function registerListeners($dispatcher)
    {
        $dispatcher->listen('mail.conversation_started', self::class . '::mailReceived');
        $dispatcher->listen('mail.conversation_reply', self::class . '::mailReceived');
    }

    public static function mailReceived($params)
    {
        $mail = $params['mail'] ?? null;
        $conversation = $params['conversation'] ?? null;

        if (!($mail instanceof \erLhcoreClassModelMailconvMessage) || !($conversation instanceof \erLhcoreClassModelMailconvConversation)) {
            return;
        }

        foreach (\erLhcoreClassModelTelegramBotDep::getList(array('filter' => array('dep_id' => $conversation->dep_id))) as $botDep) {
            $bot = $botDep->bot;
            if (!($bot instanceof \erLhcoreClassModelTelegramBot) || $bot->bot_client != 1 || $bot->mail_topics != 1 || (int)$bot->group_chat_id === 0) {
                continue;
            }

            try {
                self::postMail($bot, $conversation, $mail);
            } catch (\Throwable $e) {
                self::log('Mail topic: ' . $e->getMessage(), $conversation->id);
            }
        }
    }

    public static function postMail($bot, $conversation, $mail)
    {
        new \Longman\TelegramBot\Telegram($bot->bot_api, $bot->bot_username);

        $tChat = \erLhcoreClassModelTelegramChat::findOne(array('filter' => array('bot_id' => $bot->id, 'chat_id' => $conversation->id, 'type' => self::TYPE_MAIL)));

        if (!($tChat instanceof \erLhcoreClassModelTelegramChat)) {
            $tChat = new \erLhcoreClassModelTelegramChat();
            $tChat->bot_id = $bot->id;
            $tChat->chat_id = $conversation->id;
            $tChat->chat_id_internal = $conversation->id;
            $tChat->type = self::TYPE_MAIL;
            $tChat->tchat_id = 0;
            $tChat->ctime = time();
            $tChat->utime = time();
            $tChat->saveThis();
        }

        // A mailbox sync can hand over the same message again
        if ($tChat->last_msg_id >= $mail->id) {
            return;
        }

        $isNewTopic = $tChat->tchat_id == 0;
        if ($isNewTopic && !self::createTopic($bot, $tChat, $conversation, $mail)) {
            return;
        }

        $data = array(
            'chat_id' => $bot->group_chat_id,
            'message_thread_id' => $tChat->tchat_id,
            'parse_mode' => 'HTML',
            'link_preview_options' => json_encode(array('is_disabled' => true)),
            'text' => $isNewTopic ? self::buildCard($conversation, $mail) : self::buildMessage($conversation, $mail)
        );

        $sendData = \Longman\TelegramBot\Request::sendMessage($data);

        // The topic was deleted in Telegram: open a new one with the full card
        if (!$sendData->isOk() && $sendData->getErrorCode() == 400 && (str_contains($sendData->getDescription(), 'message thread not found') || str_contains($sendData->getDescription(), 'TOPIC_DELETED'))) {
            if (!self::createTopic($bot, $tChat, $conversation, $mail)) {
                return;
            }
            $data['message_thread_id'] = $tChat->tchat_id;
            $data['text'] = self::buildCard($conversation, $mail);
            $sendData = \Longman\TelegramBot\Request::sendMessage($data);
        }

        if (!$sendData->isOk()) {
            self::log('Mail topic sendMessage [' . $sendData->getErrorCode() . '] ' . $sendData->getDescription(), $conversation->id);
            return;
        }

        $tChat->last_msg_id = $mail->id;
        $tChat->utime = time();
        $tChat->updateThis(array('update' => array('last_msg_id', 'utime')));
    }

    public static function createTopic($bot, $tChat, $conversation, $mail)
    {
        $customer = self::getCustomer($mail);

        $sendData = \Longman\TelegramBot\Request::send('createForumTopic', array(
            'chat_id' => $bot->group_chat_id,
            'name' => mb_substr('✉️ [' . (string)$conversation->department . '] ' . ($customer['name'] !== '' ? $customer['name'] : $customer['email']) . ' #' . $conversation->id . ' | ' . (string)$mail->subject, 0, 128)
        ));

        if (!$sendData->isOk()) {
            self::log('Mail topic createForumTopic [' . $sendData->getErrorCode() . '] ' . $sendData->getDescription(), $conversation->id);
            return false;
        }

        $tChat->tchat_id = $sendData->getResult()->getMessageThreadId();
        $tChat->updateThis(array('update' => array('tchat_id')));

        return true;
    }

    // First message of a mail topic: who, subject, text, attachments, link — like the chat card
    public static function buildCard($conversation, $mail)
    {
        $customer = self::getCustomer($mail);

        $lines = array('✉️ <b>#' . (int)$conversation->id . '</b> · ' . self::html((string)$conversation->department));

        $who = array();
        if ($customer['name'] !== '') {
            $who[] = '👤 ' . self::html($customer['name']);
        }
        $who[] = '<code>' . self::html($customer['email']) . '</code>';
        $lines[] = implode(' · ', $who);

        if (trim((string)$mail->subject) !== '') {
            $lines[] = '📝 <b>' . self::html((string)$mail->subject) . '</b>';
        }

        $lines[] = '─────────────';

        return implode("\n", $lines) . "\n" . self::buildBody($conversation, $mail);
    }

    // Next e-mails of the conversation
    public static function buildMessage($conversation, $mail)
    {
        $customer = self::getCustomer($mail);

        return '👤 [' . self::html($customer['name'] !== '' ? $customer['name'] : $customer['email']) . ']:' . "\n" . self::buildBody($conversation, $mail);
    }

    public static function buildBody($conversation, $mail)
    {
        $lines = array();

        $text = self::getMailText($mail);
        if ($text !== '') {
            $lines[] = self::html($text);
        }

        $attachments = array();
        foreach ($mail->attachments as $file) {
            $attachments[] = self::html((string)$file->name);
        }
        if (!empty($attachments)) {
            $lines[] = '📎 ' . implode(', ', $attachments);
        }

        $url = self::getConversationUrl($conversation);
        if ($url !== '') {
            $lines[] = '🔗 <a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Live Helper Chat</a>';
        }

        return implode("\n\n", $lines);
    }

    /**
     * Who to answer: the first Reply-To address that is not the mailbox itself, else the sender.
     * Forms and shops usually send from their own address with the customer in Reply-To.
     */
    public static function getCustomer($mail)
    {
        $mailboxAddress = is_object($mail->mailbox) ? mb_strtolower((string)$mail->mailbox->mail) : '';

        foreach ((array)$mail->reply_to_data_keyed as $email => $name) {
            if (mb_strtolower((string)$email) !== $mailboxAddress && mb_strtolower((string)$email) !== mb_strtolower((string)$mail->from_address)) {
                return array('email' => (string)$email, 'name' => trim((string)$name) !== '' ? trim((string)$name) : trim((string)$mail->from_name));
            }
        }

        return array('email' => (string)$mail->from_address, 'name' => trim((string)$mail->from_name));
    }

    // Plain text of the e-mail without the quoted history of previous messages
    public static function getMailText($mail)
    {
        $text = trim((string)$mail->alt_body);

        if ($text === '') {
            $html = preg_replace('#<(style|script|head)\b[^>]*>.*?</\1>#is', '', (string)$mail->body);
            $html = preg_replace('#<(br|/p|/div|/tr|/li|/h[1-6])\b[^>]*>#i', "\n", $html);
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $lines = array();
        foreach (preg_split('/\R/u', $text) as $line) {
            $trimmed = trim($line);
            if (
                strpos($trimmed, '>') === 0 ||
                preg_match('/^-{2,}\s*(Original Message|Forwarded message|Исходное сообщение|Пересылаемое сообщение)/iu', $trimmed) ||
                (preg_match('/:$/u', $trimmed) && strpos($trimmed, '@') !== false && count($lines) > 0)
            ) {
                break;
            }
            $lines[] = rtrim($line);
        }

        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));

        if (mb_strlen($text) > self::BODY_LIMIT) {
            $text = rtrim(mb_substr($text, 0, self::BODY_LIMIT - 1)) . '…';
        }

        return $text;
    }

    public static function getConversationUrl($conversation)
    {
        $siteAddress = rtrim((string)\erConfigClassLhConfig::getInstance()->getSetting('site', 'site_address', false), '/');
        if ($siteAddress === '') {
            return '';
        }

        return $siteAddress . \erLhcoreClassDesign::baseurl('mailconv/single') . '/' . (int)$conversation->id;
    }

    /**
     * Operator wrote in a mail topic. Returns null when the topic is not a mail topic of this bot,
     * so the caller goes on with its own handling.
     */
    public static function operatorReply($tBot, $message, $operator, $text, $type)
    {
        if ($tBot->mail_topics != 1) {
            return null;
        }

        $chatId = $message->getChat()->getId();

        $tChat = \erLhcoreClassModelTelegramChat::findOne(array('filter' => array('bot_id' => $tBot->id, 'tchat_id' => $message->getMessageThreadId(), 'type' => self::TYPE_MAIL)));
        // Telegram thread ids are unique only within one group
        if (!($tChat instanceof \erLhcoreClassModelTelegramChat) || (int)$tBot->group_chat_id !== (int)$chatId) {
            return null;
        }

        // Commands are for the bot, not for the customer
        if (strpos($text, '/') === 0 || strpos($text, '!') === 0) {
            return \Longman\TelegramBot\Request::emptyResponse();
        }

        if ($text === '' || !in_array($type, array('message', 'text'), true)) {
            return self::answer($message, '⚠️ ' . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram', 'Only text is sent by e-mail. Attach files in Live Helper Chat.'));
        }

        $conversation = \erLhcoreClassModelMailconvConversation::fetch($tChat->chat_id);
        if (!($conversation instanceof \erLhcoreClassModelMailconvConversation)) {
            return self::answer($message, '⚠️ ' . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram', 'Mail conversation could not be found!'));
        }

        // Reply to the last e-mail of the customer, as the back office does
        $mail = \erLhcoreClassModelMailconvMessage::findOne(array(
            'filter' => array('conversation_id' => $conversation->id),
            'filternot' => array('response_type' => \erLhcoreClassModelMailconvMessage::RESPONSE_INTERNAL),
            'sort' => 'id DESC'
        ));
        if (!($mail instanceof \erLhcoreClassModelMailconvMessage)) {
            return self::answer($message, '⚠️ ' . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram', 'Mail conversation could not be found!'));
        }

        $customer = self::getCustomer($mail);

        $content = nl2br(htmlspecialchars($text, ENT_QUOTES));
        $mailbox = $conversation->mailbox;
        if (is_object($mailbox) && trim((string)$mailbox->signature) !== '') {
            $content .= '<br /><br />' . $mailbox->signature;
        }

        $response = array();
        \erLhcoreClassMailconvValidator::sendReply(array(
            'content' => $content,
            'recipients' => array('reply' => array(array('email' => $customer['email'], 'name' => $customer['name'])), 'cc' => array(), 'bcc' => array()),
            'attatchements' => array()
        ), $response, $mail, $operator->user_id);

        if (empty($response['send'])) {
            $error = !empty($response['errors']) ? implode('; ', array_map('strval', $response['errors'])) : 'unknown error';
            self::log('Mail topic reply: ' . $error, $conversation->id);
            return self::answer($message, '⚠️ ' . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram', 'E-mail was not sent') . ': ' . self::html($error));
        }

        $conversation->lr_time = time();
        $update = array('lr_time');
        if ($conversation->status == \erLhcoreClassModelMailconvConversation::STATUS_PENDING) {
            $conversation->status = \erLhcoreClassModelMailconvConversation::STATUS_ACTIVE;
            $update[] = 'status';
        }
        if ($conversation->user_id == 0) {
            $conversation->user_id = $operator->user_id;
            $update[] = 'user_id';
        }
        $conversation->updateThis(array('update' => $update));

        \erLhcoreClassMailconvWorkflow::logInteraction('[Telegram] ' . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/mailconv', 'Operator replied from Telegram topic'), is_object($operator->user) ? (string)$operator->user->name_support : '', $conversation->id);

        return self::answer($message, '✅ ✉️ <code>' . self::html($customer['email']) . '</code>');
    }

    public static function answer($message, $text)
    {
        return \Longman\TelegramBot\Request::sendMessage(array(
            'chat_id' => $message->getChat()->getId(),
            'message_thread_id' => $message->getMessageThreadId(),
            'reply_to_message_id' => $message->getMessageId(),
            'parse_mode' => 'HTML',
            'text' => $text
        ));
    }

    public static function html($text)
    {
        return htmlspecialchars((string)$text, ENT_NOQUOTES);
    }

    public static function log($text, $objectId = 0)
    {
        \erLhcoreClassLog::write($text, \ezcLog::SUCCESS_AUDIT, array(
            'source' => 'lhc',
            'category' => 'telegram_exception',
            'line' => __LINE__,
            'file' => __FILE__,
            'object_id' => $objectId
        ));
    }
}
