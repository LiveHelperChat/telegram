<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/register" command
 *
 * Registers sender as operator in Live Helper Chat back office
 */
class RegisterCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'register';

    /**
     * @var string
     */
    protected $description = 'Registers operator within LHC so this operator will receive a chat messages.';

    /**
     * @var string
     */
    protected $usage = 'Type /register <id> to register operator within LHC';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $text    = trim($message->getText(true));

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        if ($tBot->bot_client == 0) {
            $data = [
                'chat_id' => $chat_id,
                'text'    => "This command is available only if bot is set to act as a client!",
            ];
            return Request::sendMessage($data);
        }

        if ($text === '') {
            $text = 'Command usage: ' . $this->getUsage();
        } else {
            $operator = \erLhcoreClassModelTelegramOperator::fetch((int)$text);

            if ($operator instanceof \erLhcoreClassModelTelegramOperator) {
                if ($operator->confirmed == 0) {
                    $text = 'Operator was confirmed!';
                    $operator->tchat_id = $chat_id;
                    $operator->tuser_id = $user_id;
                    $operator->confirmed = 1;
                    $operator->saveThis();

                    if ($operator->user->hide_online == 1) {
                        $text .= PHP_EOL . 'You are offline. Type /whoami to switch your status';
                    }

                } else {
                    $text = 'Operator is already confirmed! Please un-confirm operator first in back office';
                }

            } else {
                $text = 'Operator could not be found! Please create operator in back office first!';
            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        return Request::sendMessage($data);
    }
}
