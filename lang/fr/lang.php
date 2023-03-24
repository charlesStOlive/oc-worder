<?php

return [
    'menu' => [
        'title' => 'Contenu',
        'documents' => 'Words',
        'documents_description' => 'Gestion des modèles words',
        'settings_category' => 'Wakaari Modèle',
    ],
    'bloc' => [
        'name' => 'Intitulé',
        'name_ex' => 'UNiquement utilisé dans le BO',
        'bloc_name' => 'Nom',
        'bloc_name_ex' => 'Liaison avec le document',
        'bloc_type' => 'Type de contenu',
        'code' => 'Code',
        'version' => 'Les versions',
        'nb_content' => 'Variante',
        'opt_section' => 'Liste des options',
        'opt_section_com' => "Si vide : pas d'options",
    ],
    'bloc_name' => [
        'name' => 'Intitulé',
        'name_ex' => 'Uniquement utilisé dans le BO',
        'bloc' => 'Bloc contenu de références',
        'bloc_name' => 'Nom',
        'bloc_name_ex' => 'Liaison avec le document',
        'bloc_type' => 'Type de contenu',
    ],
    'bloc_type' => [
        'name' => 'Intitulé',
        'type' => 'Type de bloc',
        'type_bloc' => "Le contenu sera de type : 'bloc'",
        'type_row' => "Le contenu sera de type : 'row'",
        'code' => "Code d'itentification du bloc",
        'model' => 'Model associé',
        'ajax_method' => 'Méthode Ajax',
        'use_icon' => 'Utiliser une icone October',
        'icon_png' => 'Utiliser une icone PNG',
        'scr_explication' => 'Fichier Word d explication du bloc',
        'datasource_accepted' => 'Model reservé pour les sources : ',
        'datasource_accepted_comment' => 'Vide si fonctionne avec tous les modèles',
    ],
    'document' => [
        'title' => "Créer un document Word",
        'name' => 'Nom',
        'path' => 'Fichier source',
        'data_source' => ' Sources des données',
        'download' => 'Télécharger un exemple',
        'check' => 'Vérifier',
        "name_construction" => "Construction du nom du fichier",
        "test_id" => "ID de test",
        'scopes' => [
            'title' => "limiter le document pour une cible",
            'prompt' => 'Ajouter une nouvelle limites',
            'com' => "Vous pouvez décider de n'afficher ce modèle que sous certains critères Attention seul les valeurs id sont accepté",
            'self' => "Fonction de restriction liée à l'id de ce modèle ?",
            'target' => 'Relation de la cible',
            'target_com' => "Ecrire le nom de la relation les relations parentes ne sont pas disponible",
            'id' => 'ID recherché',
            'id_com' => "Vous pouvez ajouter plusieurs ID",
            'conditions' => "Conditions",
        ],
    ],
    'objtext' => [
        'data' => 'Paragraphes',
        'data_prompt' => "Cliquez ici pour ajouter un paragraphe",
        'value' => 'Paragraphe',
        'jump' => 'Saut de ligne entre les deux paragraphes',
    ],
    'content' => [
        'name' => 'Contenu',
        'sector' => "Secteur",
        'sector_placeholder' => 'Choisissez un secteur',
        'versions' => 'Les versions',
        'add_version' => 'Nouvelle version',
        'add_base' => 'Créer le contenu de base',
        'create_content' => "Création d'une version : ",
        'update_content' => "Mise à jour d'une version ",
        'version_for_sector' => 'Version pour le secteur : ',
        'sector' => 'Secteur de cette version',
        'reminder_content' => "Choisisir ou créer une version dans le tableau du dessus. Mettre à jour avant de quitter",
    ],
    'word' => [
        'processor' => [
            'bad_format' => 'Fromat du tag incorrect',
            'bad_tag' => 'Le type de tag est incorrect',
            'type_not_exist' => "Ce type de tag n'existe pas",
            'field_not_existe' => "Le champs n'existe pas",
            'id_not_exist' => "L'id n'existe pas",
            'document_not_exist' => " La source du document n'a pas été trouvé",
            'errors' => "Ce document à des erreurs, veuillez les corriger.",
            'success' => "Le systhème n'a pas trouvé d'erreurs. Pensez à verifier votre document après édition",

        ],
        'error' => [
            'no_image' => "L'image ou le montage n'existe pas",
        ],
    ],
];
