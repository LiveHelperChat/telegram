<?php

return array(
    'disable_op_flow' => false,
    'disable_leads' => false,
    'convert_ogg' => false,
    'convert_to' => 'wav',
    'convert_command' => 'avconv -i {file_orig} {file_dest}',
    // Voice messages recorded in the widget (record.mp3) go to operator topics as Telegram voice messages.
    // convert_command makes an OGG/Opus copy for Telegram ({file_orig}, {file_dest}), e.g.
    // 'ffmpeg -nostdin -loglevel error -y -i {file_orig} -c:a libopus -b:a 64k -ac 1 -application voip {file_dest}'
    // Empty = the MP3 is sent as a voice message as it is.
    'voice_record' => array(
        'send_as_voice' => true,
        'convert_command' => ''
    ),
    'elastic_search' => array(
        'search_attr' => 'attr_int_1'
    )
);

?>