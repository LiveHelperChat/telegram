<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

class BanCommand extends UserCommand
{
    protected $name = 'ban';
    protected $description = 'Ban the user by IP for 30 days and close the active chat';
    protected $usage = 'Type /ban';
    protected $version = '1.0.2';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $thread_id = $message->getMessageThreadId();

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        if ($tBot->bot_client == 0) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => "This command is available only when the bot acts as a client!",
            ]);
        }

        if (!$thread_id) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => "This command must be used inside a sub-topic (thread)!",
            ]);
        }

        $operator = \erLhcoreClassModelTelegramOperator::findOne([
            'filter' => [
                'tuser_id' => $message->getFrom()->getId(),
                'confirmed' => 1,
                'bot_id' => $tBot->id
            ]
        ]);

        if (!($operator instanceof \erLhcoreClassModelTelegramOperator)) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Operator not found or not confirmed.',
            ]);
        }

        $tChats = \erLhcoreClassModelTelegramChat::getList([
            'filter' => [
                'bot_id' => $tBot->id,
                'tchat_id' => $thread_id,
                'type' => 1
            ]
        ]);

        foreach ($tChats as $tChat) {
            $chat = $tChat->chat;

            if ($chat instanceof \erLhcoreClassModelChat) { 
                $user = \erLhcoreClassModelUser::fetch($operator->user_id);

               // Make sure the user exists
               if (!($user instanceof \erLhcoreClassModelUser)) {
                   return Request::sendMessage([
                       'chat_id' => $chat_id,
                       'message_thread_id' => $tChat->tchat_id,
                       'text' => 'Operator user could not be found.',
                   ]);
               }

               if (!empty($chat->ip)) {
                 // Force set the user context
                  // ✅ Set current user for LHC context
                 \erLhcoreClassUser::instance()->data = $user;


                 // Block by IP
                 $blocked = new \erLhcoreClassModelChatBlockedUser();
                 $blocked->ip = $chat->ip;
                 $blocked->user_id = $user->id;
                 $blocked->chat_id = $chat->id;
                 $blocked->dep_id = $chat->dep_id;
                 $blocked->nick = $chat->nick;
                 $blocked->btype = \erLhcoreClassModelChatBlockedUser::BLOCK_IP;
                 $blocked->expires = time() + (30 * 24 * 3600); // 30 days ban
                 $blocked->online_user_id = 0;
                 $blocked->saveThis();

                  // Now close the chat safely
                  \erLhcoreClassChatHelper::closeChat([
                     'user' => $user,
                     'chat' => $chat,
                  ]);

                // Clear operator state
                  $operator->chat_id = 0;
                  $operator->saveThis();
                }
            } else {
                Request::sendMessage([
                    'chat_id' => $chat_id,
                    'message_thread_id' => $tChat->tchat_id,
                    'text' => 'No active chat found!',
                ]);
            }
        }
        return Request::emptyResponse();
    }
}
