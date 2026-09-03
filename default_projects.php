<?php
/**
 * default_projects.php
 * The 3 built-in placeholder projects shown when projects.json has no
 * admin-added projects yet.
 *
 * Shared by:
 *   - get_projects.php   : public fallback display
 *   - save_project.php   : so editing one of these defaults turns it into
 *                           a real, persisted project (instead of failing
 *                           with "not_found", since it doesn't exist yet
 *                           in projects.json) - and its two siblings are
 *                           carried over too, instead of disappearing.
 *   - delete_project.php : same reasoning, for deleting one of them.
 *
 * Keeping this in one place means the 3 sample projects only need to be
 * edited/updated in a single spot.
 */

return [
    [
        'id'              => 'p-fash',
        'title'           => 'E-Commerce Mode',
        'desc'            => 'Boutique en ligne de vêtements moderne avec catalogue produits, recherche par catégories, gestion des tailles et paiement sécurisé.',
        'link'            => '',
        'image'           => null,
        'icon'            => 'fas fa-shirt',
        'placeholderClass'=> 'placeholder-fashion',
        'tech'            => ['HTML','CSS','JavaScript','PHP','MySQL']
    ],
    [
        'id'              => 'p-shoes',
        'title'           => 'E-Commerce Chaussures',
        'desc'            => 'Site de vente de chaussures professionnel avec filtres par pointure et marque, gestion de stock et options de commande rapide.',
        'link'            => '',
        'image'           => null,
        'icon'            => 'fas fa-shoe-prints',
        'placeholderClass'=> 'placeholder-shoes',
        'tech'            => ['HTML','CSS','JavaScript','PHP','MySQL']
    ],
    [
        'id'              => 'p-tech',
        'title'           => 'E-Commerce Tech',
        'desc'            => "Boutique en ligne d'électronique et accessoires avec fiche produit détaillée, comparaison de prix et paiement sécurisé en ligne.",
        'link'            => '',
        'image'           => null,
        'icon'            => 'fas fa-headphones',
        'placeholderClass'=> 'placeholder-tech',
        'tech'            => ['HTML','CSS','JavaScript','PHP','MySQL']
    ]
];
