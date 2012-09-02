/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

jQuery(function () {
    var rootUrl = $('script[src$="/coursesearch.js"]').attr('src').replace('/coursesearch.js', '/');
    $('.widget-coursesearch').load(rootUrl + 'ajax.php');
    $('.widget-coursesearch').on('submit', 'form', searchMoodleCourses);
    $('.widget-coursesearch').on('click', 'input[name="submitbutton"]', searchMoodleCourses);
    function searchMoodleCourses (event) {
        var data = $(this).closest('form').serialize();
        $('.widget-coursesearch').load(rootUrl + 'ajax.php?', data);
        return false;
    }
});
