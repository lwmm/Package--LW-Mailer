<?php
namespace LwMailer\Views;

class SignatureView
{

    public function render($array)
    {
        $view = new \lw_view(dirname(__FILE__) . '/Templates/Signature.phtml');

        $view->contactData = $array;

        return $view->render();
    }
    
}