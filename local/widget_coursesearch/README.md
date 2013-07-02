# Syntaxe pour le widget coursesearch

Pour utiliser le widget, il faut charger le fichier JS
et appliquer le widget à un élément HTML.

Par exemple, dans Moodle :

    $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js')); // facultatif
    $PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js')); // facultatif
    $PAGE->requires->js(new moodle_url('/local/widget_coursesearch/coursesearch.js'));
    $PAGE->requires->js_init_code('jQuery("#widget-coursesearch").coursesearch();');

Autre exemple, hors Moodle :

    <srcipt src="/js/coursesearch.js" />
    <script>
      jQuery("#widget-coursesearch").coursesearch();
    </script>

La fonction jQuery **coursesearch()** accepte en paramètre optionnel
une structure de données :

    {
        // limit the search to courses under this category ID (recursively)
        topcategory: 22,

        // limit the search to courses under this ROF path (recursively)
        topnode: "/02/UP1-PROG39308/UP1-PROG24870",

        // search on a part of the name of persons enrolled in the course 
        enrolled: "Dupond",

        // for the previous criteria, only consider roles
        // (defaults to "3", AKA "teacher")
        enrolledroles: [3],

        // legend of the first fieldset
        fieldset: "Recherche principale",

        // hierarchy of fields
        // "fields": "*" // default: every category and every field
        "fields": {
            "Identification": ["up1code", "up1name"],
            "Diplome": "*"
        }
    }

