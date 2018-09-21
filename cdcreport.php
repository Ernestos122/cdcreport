<?php
require_once (dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
### Este php lo tengo ubicado en /local/uaio/reports/cdcreport.php
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/lib/tests/other/cdcreport.php');
$PAGE->set_heading('Test CDC Charts');
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();

$sales = new \core\chart_series('Sales', [1000, 1170, 660, 1030]);
$expenses = new \core\chart_series('Expenses', [400, 460, 1120, 540]);
$labels = ['1', '2', '3', '4'];

$sql="select r.survey_id, userid, group_concat(question_id separator '#') qids, group_concat(concat(rq.name, ' ', rq.content)  separator '#') qcontents, group_concat(response separator '#') qresponses
from mdl_questionnaire_response r
left join mdl_questionnaire_response_text rt on (rt.response_id = r.id)
left join mdl_questionnaire_question rq on (rq.id = rt.question_id)
where 1=1
group by r.userid
union all
select r.survey_id, userid, group_concat(rr.question_id separator '#') qids, group_concat(concat(rq.name, ' ', rq.content, ' ', qc.content) separator '#') qcontents, group_concat(rank separator '#') qresponses
from mdl_questionnaire_response r
left join mdl_questionnaire_response_rank rr on (rr.response_id = r.id)
left join mdl_questionnaire_quest_choice qc on (rr.choice_id = qc.id)
left join mdl_questionnaire_question rq on (rq.id = rr.question_id)
 
group by r.userid

/*left join mdl_questionnaire_response_bool rb on (rb.response_id = r.id)
left join mdl_questionnaire_response_date rd on (rd.response_id = r.id)
left join mdl_questionnaire_response_other ro on (ro.response_id = r.id)
left join mdl_questionnaire_response_rank rr on (rr.response_id = r.id)*/
;";



## Obtengo datos de la tremenda query
$serietest=$DB->get_recordset_sql($sql);


##Brutamente las coloco en una array
$i=0;  
foreach($serietest as $respuesta)
{
      
    $respuesta1[$i]=$respuesta->qresponses; 
    $i=$i+1;
}


##Brutamente hago un array bidimensional, considerando que
## X es la persona única e Y son las respuestas a las preguntas
## (cada índice siendo pregunta única):

$i=0;
foreach($respuesta1 as $array)
{
    $respuesta2[$i]=explode("#",$array);
    $i=$i+1;
}


##Ahora crearé un "Horizontal Line Chart" por cada pregunta realizada (FALTA AGREGAR TITULOS)
##(FALTA FORMATEAR BIEN GRÁFICOS)

for($j=0; $j<count($respuesta2[3]);$j++)
{
### $j--->Pregunta en cuestión


    for($i=3; $i<count($respuesta1);$i++)### OJO: empieza desde 3 por que los primeros 3 usuarios son
                                         ### los usuarios parte del sistema (al menos en mi caso, podríamos
                                         ### modificar Query para sólo respuestas no nulas)
    {
        ### $---> Respuestas de la pregunta en cuestión
        
        $ranks[$i]=$respuesta2[$i][$j];
    }

    $ranks=array_count_values($ranks);
    ### Con esto cuento frecuencias fácilmente
    $values= array_values($ranks);
    $keys= array_keys($ranks);
    ### Preparo data para pasárselo al chart
    $chartSeries = new \core\chart_series('Respuestas', $values);
    ### Creo una serie

    $chart = new \core\chart_bar();
    $chart->set_title('HORIZONTAL BAR CHART'); ### (CAMBIAR POR TÍTULO DE LA PREGUNTA)
    $chart->set_horizontal(true); ### Según lo visto por el PPT, eran horizontales
    $chart->add_series($chartSeries); ### Añado la serie (Es posible añadir varias)
    $chart->set_labels($keys); ### Labels dependiendo de las respuestas capturadas

    echo $OUTPUT->render($chart); ### Se proyecta Chart

}
## JS PDF
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js"></script>
<?php 
echo $OUTPUT->footer();
?>