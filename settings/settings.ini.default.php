<?php

return array(
    'convert_ogg' => false,
    'convert_to' => 'wav',
    'convert_command' => 'avconv -i {file_orig} {file_dest}',
    'elastic_search' => array(
        'search_attr' => 'attr_int_1'
    )
);

?>