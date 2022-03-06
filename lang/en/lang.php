<?php
return [
    'menu' => [
        'title' => 'Contents',
        'documents' => 'Words',
        'documents_description' => 'Word models management ',
        'settings_category' => 'Wakaari Model'
    ],
    'bloc' => [
        'name' => 'Entitled',
        'name_ex' => 'Only used in the soundtrack',
        'bloc_name' => 'name',
        'bloc_name_ex' => 'Link to document',
        'bloc_type' => 'Content type',
        'code' => 'Code',
        'version' => 'Versions',
        'nb_content' => 'variant',
        'opt_section' => 'List of options',
        'opt_section_com' => 'If empty: no options'
    ],
    'bloc_name' => [
        'name' => 'Entitled',
        'name_ex' => 'Only used in the soundtrack',
        'bloc' => 'Reference content block',
        'bloc_name' => 'name',
        'bloc_name_ex' => 'Link to document',
        'bloc_type' => 'Content type'
    ],
    'bloc_type' => [
        'name' => 'Entitled',
        'type' => 'block type',
        'type_bloc' => 'The content will be of type: \'block\'',
        'type_row' => 'The content will be of type: \'row\'',
        'code' => 'Block identification code',
        'model' => 'Associated model',
        'ajax_method' => 'Ajax method',
        'use_icon' => 'Use an October icon',
        'icon_png' => 'Use a PNG icon',
        'scr_explication' => 'Block Explanation Word File',
        'datasource_accepted' => 'Model reserved for sources:',
        'datasource_accepted_comment' => 'Blank if works with all models'
    ],
    'document' => [
        'title' => 'Create a Word document',
        'name' => 'name',
        'path' => 'Source file',
        'data_source' => ' Data sources',
        'download' => 'Download an example',
        'check' => 'To verify',
        'name_construction' => 'File name construction',
        'test_id' => 'Test ID',
        'scopes' => [
            'title' => 'limit the document for a target',
            'prompt' => 'Add a new limit',
            'com' => 'You can decide to display this model only under certain criteria Attention only id values are accepted',
            'self' => 'Restriction function linked to the id of this model?',
            'target' => 'Target relationship',
            'target_com' => 'Write the name of the relation the parent relations are not available',
            'id' => 'ID sought',
            'id_com' => 'You can add multiple IDs',
            'conditions' => 'Conditions'
        ]
    ],
    'objtext' => [
        'data' => 'Paragraphs',
        'data_prompt' => 'Click here to add a paragraph',
        'value' => 'Paragraph',
        'jump' => 'Line break between two paragraphs'
    ],
    'content' => [
        'name' => 'Contents',
        'sector' => 'Sector of this version',
        'sector_placeholder' => 'Choose a sector',
        'versions' => 'Versions',
        'add_version' => 'New version',
        'add_base' => 'Create basic content',
        'create_content' => 'Creating a release:',
        'update_content' => 'Version update',
        'version_for_sector' => 'Version for the sector:',
        'reminder_content' => 'Choose or create a version in the table above. Update before leaving'
    ],
    'word' => [
        'processor' => [
            'bad_format' => 'Fromat du tag incorrect',
            'bad_tag' => 'The tag type is incorrect',
            'type_not_exist' => 'This type of tag does not exist',
            'field_not_existe' => 'The field does not exist',
            'id_not_exist' => 'ID does not exist',
            'document_not_exist' => ' The source of the document was not found',
            'errors' => 'This document has errors, please correct them.',
            'success' => 'The system did not find any errors. Remember to check your document after editing'
        ],
        'error' => [
            'no_image' => 'Image or mount does not exist'
        ]
    ],
    'contents' => [
        'linkedphoto' => [
            'image' => 'Choose a picture',
            'image_placeholder' => '--Choose an image--',
            'width' => 'Width (mm)',
            'height' => 'Height (mm)',
            'width_explication' => '165mm is the default content width',
            'keep_ratio' => 'Keep the ration',
            'unit' => 'Unity'
        ],
        'mediastextes' => [
            'data_prompt' => 'Create a text + photo initial',
            'value' => 'Text',
            'path' => 'Choose a picture',
            'Jump' => 'Add a second paragraph break'
        ],
        'textes' => [
            'data_prompt' => 'Create a text paraph',
            'value' => 'Text',
            'Jump' => 'Add a second paragraph break'
        ]
    ]
];