<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Super group chat created command
 *
 * Gets executed when a super group chat is created.
 */
class SupergroupchatcreatedCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'supergroupchatcreated';

    /**
     * @var string
     */
    protected $description = 'Super group chat created';

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
    public function execute(): ServerResponse
    {
        //$message = $this->getMessage();
        //$supergroup_chat_created = $message->getSuperGroupChatCreated();

        return parent::execute();
    }
}
