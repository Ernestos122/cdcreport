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

##Forma simple y actual de guardar cómo PDF e imprimir.

echo'<form>
<input type=button name=print value="Imprimir/Guardar PDF" onClick="window.print()">
</form>';

echo '';

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

$pregunta=$serietest->qcontents;




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


for($i=0; $i<count($respuesta2[3]);$i++)
{
### $j--->Pregunta en cuestión


    
    for($j=3; $j < count($respuesta1);$j++){
             
        $rank[$j]= $respuesta2[$j][$i];
        
    }
   
    $rank1=array_count_values($rank);
    ### Con esto saco frecuencias fácilmente
    $values= array_values($rank1);
    $keys= array_keys($rank1);
    ### Preparo data para pasárselo al chart
    $chartSeries = new \core\chart_series('Respuestas', $values);
    ### Creo una serie
    $chart = new \core\chart_bar();
    $chart->set_title("Pregunta ".($i+1)); ### (CAMBIAR POR TÍTULO DE LA PREGUNTA)
    $chart->set_horizontal(true); ### Según lo visto por el PPT, eran horizontales
    $chart->add_series($chartSeries); ### Añado la serie (Es posible añadir varias)
    $chart->set_labels($keys); ### Labels dependiendo de las respuestas capturadas
    $xaxis= new \core\chart_axis();
    ### Frecuencias se miden sólo en enteros (duh)
    $xaxis->set_stepsize(1);
    $chart->set_xaxis($xaxis);
    echo $OUTPUT->render($chart); ### Se proyecta Chart

}

echo $OUTPUT->footer();
?>