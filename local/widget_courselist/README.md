# Syntaxe pour le widget courselist

Pour utiliser le widget, il faut charger le fichier JS
et appliquer le widget à un ou plusieurs éléments HTML.

Dans les exemples qui suivent, on suppose que la page HTML contient
un block ("div" ou autre) d'identifiant "widget-courselist".

Par exemple, dans Moodle :

    $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js')); // facultatif
    $PAGE->requires->js(new moodle_url('/local/widget_courselist/courselist.js'));
    $PAGE->requires->js_init_code(
        'jQuery("#widget-courselist").courselist({format: "table", topcategory: 5});'
    );

Autre exemple, hors Moodle :

    <script src="/js/courselist.js" />
    <script>
      jQuery("#widget-courselist").courselist({format: "table", topcategory: 5});
    </script>

Dans le code ci-dessus, la première ligne charge la bibliothèque JS
et ses dépendances. La seconde balise "script" insère le tableau de cours
dans l'élément HTML d'id "widget-courselist".


La fonction jQuery **courselist()** accepte en paramètre optionnel
une structure de données qui configure le formulaire :

    {
        // Display courses in a "table" or in a "list"
        format: "table",

        // search terms applied to the fullname and the description of each course
        // use -something to exclude words
        search: "",

        // Date as YYYY-MM-DD
        startdateafter: "",
        startdatebefore: "",

        // limit the search to courses under this category ID (recursively)
        topcategory: 22,

        // limit the search to courses under this ROF path (recursively)
        node: "/02/UP1-PROG39308/UP1-PROG24870",

        // search on a part of the name of persons enrolled in the course 
        enrolled: "Dupond",

        // for the previous criteria (enrolled), only consider the following roles
        // (defaults to "3", AKA "teacher")
        enrolledroles: [3],

        // criteria on custom course fields
        "custom": {
            up1code: "xyz",
            demandeurid: 4
        }
    }

Exemple complet (HTML + JS, avec configuration du formulaire) :

	<h2>Cours de la catégorie 5</h2>
    <div id="widget-courselist-cat"></div>
	<h2>Cours intéressants</h2>
    <div id="widget-courselist-misc"></div>

    <script src="/js/courselist.js" />
    <script>
      jQuery("#widget-courselist-cat").courselist({
        format: "table",
        topcategory: 4
      });
      jQuery("#widget-courselist-misc").courselist({
        format: "list",
        enrolled: "Dupond",
        "custom": {
            demandeurid: 4
        }
      });
    </script>

