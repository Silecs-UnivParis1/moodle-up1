# Syntaxe pour le widget coursesearch

Pour utiliser le widget, il faut charger le fichier JS
et appliquer le widget à un élément HTML.

Dans les exemples qui suivent, on suppose que la page HTML contient
un block ("div" ou autre) d'identifiant "widget-coursesearch".

Par exemple, dans Moodle :

    $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js')); // facultatif
    $PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js')); // facultatif
    $PAGE->requires->js(new moodle_url('/local/widget_coursesearch/coursesearch.js'));
    $PAGE->requires->js_init_code('jQuery("#widget-coursesearch").coursesearch();');

Autre exemple, hors Moodle :

    <script src="/js/coursesearch.js" />
    <script>
      jQuery("#widget-coursesearch").coursesearch();
    </script>

Dans le code ci-dessus, la première ligne charge la bibliothèque JS
et ses dépendances. La seconde balise "script" insère le formulaire
de recherche dans l'élément HTML d'id "widget-coursesearch".


La fonction jQuery **coursesearch()** accepte en paramètre optionnel
une structure de données qui configure le formulaire :

    {
        // legend of the first fieldset
        fieldset: "Recherche principale",

        // limit the search to courses under this category ID (recursively)
        topcategory: 22,

        // limit the search to courses under this ROF path (recursively)
        node: "/02/UP1-PROG39308/UP1-PROG24870",

        // search on a part of the name of persons enrolled in the course 
        enrolled: "Dupond",

        // for the previous criteria (enrolled), only consider the following roles
        // (defaults to "3", AKA "teacher")
        enrolledroles: [3],

        // hierarchy of fields
        // "fields": "*" // default: every category and every field
        "fields": {
            "Identification": ["up1code", "up1name"],
            "Diplome": "*"
        }
    }

Exemple complet (HTML + JS, avec configuration du formulaire) :

    <div id="widget-coursesearch"></div>

    <script src="/js/coursesearch.js" />
    <script>
      jQuery("#widget-coursesearch").coursesearch({
        fieldset: "Recherche principale",
        "fields": { "Identification": "*" }
      });
    </script>

