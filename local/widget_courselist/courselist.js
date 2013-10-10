(function($){
    var rootUrl = $('script[src$="/courselist.js"]').attr('src').replace('/courselist.js', '/');

    var scriptTag = document.createElement('script');
    scriptTag.setAttribute("type","text/javascript");
    scriptTag.setAttribute("src", rootUrl + '../jquery/jquery.dataTables.min.js');
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(scriptTag);
    var linkTag = document.createElement('link');
    linkTag.setAttribute("type","text/css");
    linkTag.setAttribute("rel","stylesheet");
    linkTag.setAttribute("href", rootUrl + '../jquery/css/jquery.dataTables.css');
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(linkTag);

    var defaultConfig = {
        "oLanguage": {
            "sProcessing":     "Traitement en cours...",
            "sSearch":         "Rechercher&nbsp;:",
            "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
            "sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
            "sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
            "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
            "sInfoPostFix":    "",
            "sLoadingRecords": "Chargement en cours...",
            "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
            "sEmptyTable":     "Aucune donnée disponible dans le tableau",
            "oPaginate": {
                "sFirst":      "Premier",
                "sPrevious":   "Pr&eacute;c&eacute;dent",
                "sNext":       "Suivant",
                "sLast":       "Dernier"
            },
            "oAria": {
                "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
            }
        }
    };
    jQuery.fn.courselist = function(criteria) {
        var $elem = this;
        var config;
        if ('table' in criteria) {
            config = $.extend(true, defaultConfig, criteria.table);
            delete criteria.table;
        } else {
            config = defaultConfig;
        }
        $.ajax({
            'url': rootUrl + 'list.php',
            'type': 'GET',
            data: criteria
        }).done(function(html) {
            $elem.html(html).find('table').dataTable(config);
        });
    }
})(jQuery);
