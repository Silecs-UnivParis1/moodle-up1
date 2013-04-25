<?php

$sample = array(
    0 => array(
        'id' => '/45/05/UP1-PROG33939/UP1-PROG33941/UP1-C12345',
        'label' => 'Tutorat Méthodologie',
        'courselink' => 'http://localhost/moodle-paris1/course/view.php?id=26',
        'courses' => 12,
        'load_on_demand' => true,               //has children to unfold
        'teachers' => array('Maxime Dupuis', 'Patricia Cavallo', 'Benoit Roques'),
        'synopsis' => 'http://localhost/moodle-paris1/course/report/synopsis/index.php?id=20',
        'access' => array('self-enrolment'),
        'label' => '... Tutorat Méthodologie ...'
    ),
    1 => array(
        'id' => '/45/05/UP1-PROG33939/UP1-PROG33941/UP1-C33945',
        'label' => '0210305 - Mathématique1',
        'courselink' => null,
        'courses' => 7,
        'load_on_demand' => true,
        'teachers' => array(),
        'synopsis' => null,
        'access' => array(),
        'label' => '... Tutorat Méthodologie ...'
    )
);

header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($sample);
