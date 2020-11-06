<?php

namespace Postroyka\AccountBundle\Controller;

use Postroyka\AppBundle\Controller\AbstractController;
use Submarine\ConfirmationBundle\Entity\CodeConfirm;
use Submarine\ConfirmationBundle\Service\ConfirmationService;

class ConfirmationController extends AbstractController
{
    /**
     * @return ConfirmationService
     */
    private function confirmation()
    {
        return $this->get('submarine.confirmation');
    }

    /**
     * Использовать код активации
     */
    public function codeExecuteAction($code)
    {
        $codeConfirm = $this->confirmation()->getCodeConfirm($code);
        if ($codeConfirm instanceof CodeConfirm) {
            if ($this->confirmation()->executeCodeConfirm($codeConfirm)) {
                $this->notifier()->success('message.success.code_confirm_success');

                return $this->redirect($this->generateUrl('login'));
            }
        }

        $this->notifier()->error('message.error.code_confirm_not_found');

        return $this->redirect($this->generateUrl('login'));
    }
}