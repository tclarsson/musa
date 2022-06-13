<?php 
require_once 'environment.php';
require_once 'music.php';
require_once 'table.php';
require_once 'crud_simple.php';
// ------------------------------------------------------
$user->admit([]);
// ------------------------------------------------------
// 
    // list of supported classes
    $list_classes=['person','category','theme','language','instrument','holiday','solovoice','choirvoice','storage'];
    if(isset($_REQUEST['list'])&&in_array($_REQUEST['list'],$list_classes)) {
        $cn=ucfirst(strtolower($_REQUEST['list']));
        $ci=$cn::classinfo();
        $crud=New Crud($ci['CLASS_TITLE'],$cn);
        // ------------------------------------------------------
        $crud->controller();
        // ------------------------------------------------------
        $card=New Card("$crud->page_title");
        $card->helpmodal=New Modal("helppage".__LINE__);
        $card->helpmodal->body="
        <p>Här kan du </p>
        <ul>
        <li>Lägga till, editera och ta bort {$crud->page_title}</li>
        </ul>";
        // ------------------------------------------------------
        $crud->controller();
        // ------------------------------------------------------
        // display page
        // ------------------------------------------------------
        require_once 'header.php';
        $card->render();
        $crud->render();
        require_once 'footer.php';
        exit;
    }
    else {
        $card=New Card("Administrera Listor");
        $card->helpmodal=New Modal("helppage".__LINE__);
        $card->helpmodal->body="
        <p>Här kan du välja en lista att administrera</p>
        <ul>
        <li>Lägga till, editera och ta bort list-element</li>
        </ul>";
        $card->body="<h4>Välj lista att administrera:</h4><ul>";
        foreach($list_classes as $class){
            $cn=ucfirst(strtolower($class));
            $ci=$cn::classinfo();
            //pa($ci);
            $card->body.="<a href='?list=$class'><li>$ci[CLASS_TITLE]</li></a>";
        }
        $card->body.="</ul>";
        require_once 'header.php';
        $card->render();
        require_once 'footer.php';
    }

?>
