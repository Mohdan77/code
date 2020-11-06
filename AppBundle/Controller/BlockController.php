<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AppBundle\Form\NotFoundForm;

class BlockController extends AbstractController
{
    public function notFoundAction()
    {
        $form = $this->createForm(NotFoundForm::class);

        $data['form'] = $form->createView();

        return $this->render('@PostroykaApp/Block/not_found.html.twig', $data);
    }
}