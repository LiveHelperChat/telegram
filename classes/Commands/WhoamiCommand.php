<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Marco Boretto <marco.bore@gmail.com>
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Entities\UserProfilePhotos;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * User "/whoami" command
 *
 * Simple command that returns info about the current user.
 */
class WhoamiCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'whoami';

    /**
     * @var string
     */
    protected $description = 'Show your id, name and username';

    /**
     * @var string
     */
    protected $usage = '/whoami';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $from       = $message->getFrom();
        $user_id    = $from->getId();
        $chat_id    = $message->getChat()->getId();
        $message_id = $message->getMessageId();

        $data = [
            'chat_id'             => $chat_id,
            'reply_to_message_id' => $message_id,
        ];

        //Send chat action
        Request::sendChatAction([
            'chat_id' => $chat_id,
            'action'  => 'typing',
        ]);

        $caption = sprintf(
            'Your Id: %d' . PHP_EOL .
            'Name: %s %s' . PHP_EOL .
            'Username: %s',
            $user_id,
            $from->getFirstName(),
            $from->getLastName(),
            $from->getUsername()
        );

        //Fetch user profile photo
        $limit    = 10;
        $offset   = null;
        $response = Request::getUserProfilePhotos(
            [
                'user_id' => $user_id,
                'limit'   => $limit,
                'offset'  => $offset,
            ]
        );

        if ($response->isOk()) {
            /** @var UserProfilePhotos $user_profile_photos */
            $user_profile_photos = $response->getResult();

            if ($user_profile_photos->getTotalCount() > 0) {
                $photos = $user_profile_photos->getPhotos();

                /** @var PhotoSize $photo */
                $photo   = $photos[0][2];
                $file_id = $photo->getFileId();

                $data['photo']   = $file_id;
                $data['caption'] = $caption;

                $result = Request::sendPhoto($data);

                //Download the photo after send message response to speedup response
                $response2 = Request::getFile(['file_id' => $file_id]);
                if ($response2->isOk()) {
                    /** @var File $photo_file */
                    $photo_file = $response2->getResult();
                    Request::downloadFile($photo_file);
                }

                return $result;
            }
        }

        //No Photo just send text
        $data['text'] = $caption;

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tchat_id' => $chat_id, 'confirmed' => 1, 'bot_id' => $tBot->id)));

        if ($operator instanceof \erLhcoreClassModelTelegramOperator) {
            if ($operator->user->hide_online == 0) {
                $inlineKeyboards[] = ['text' => "Go offline", 'callback_data' => 'go_offline'];
            } else {
                $inlineKeyboards[] = ['text' => "Go online", 'callback_data' => 'go_online'];
            }

            $inlineKeyboard = new InlineKeyboard($inlineKeyboards);
            $data['reply_markup'] = $inlineKeyboard;
            $data['text'] .=  PHP_EOL . 'Status: ' . ($operator->user->hide_online == 0 ? 'Online' : 'Offline');
            $data['text'] .=  PHP_EOL . 'Associated operator: [' . $operator->user_id . '] Official name - ' . ($operator->user->name_official) .' Support name - '. ($operator->user->name_support);
            $data['text'] .=  PHP_EOL . 'Bot as client: ' . ($tBot->bot_client == 1 ? 'Yes (you will receive new chat requests)' : 'No (you will NOT receive new chat requests)');

        } else {
            $data['text'] .=  PHP_EOL . "You are not registered within Live Helper Chat. Please register. See /register";
        }

        return Request::sendMessage($data);
    }
}
