<?php

declare(strict_types=1);

namespace Forms;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Submit;
use Phalcon\Validation\Validator;

class ChangeEmailForm extends BaseForm
{

    /**
     * Initialize the Form
     *
     * @return void  Handled by Phalcon
     */
    public function initialize(): void
    {
        $email = new Text('email', [
            'placeholder' => 'New Email',
            'class'       => 'form-control input-lg',
        ]);

        $email->addValidators([
            new Validator\PresenceOf([
                'message' => 'Your email is required.',
            ]),
            new Validator\Email([
                'message' => 'Your email is not valid.',
            ]),
        ]);
        $email->setFilters(['email', 'trim',]);


        $confirm_email = new Text('confirm_email', [
            'placeholder' => 'Confirm New Email',
            'class'       => 'form-control input-lg',
        ]);

        $confirm_email->addValidators([
            new Validator\PresenceOf([
                'message' => 'Your email is required.',
            ]),
            new Validator\Email([
                'message' => 'Your email is not valid.',
            ]),
            new Validator\Identical([
                'accepted' => $this->getUserOption('email'),
                'message'  => 'Your confirmation email address must match.',
            ]),
        ]);
        $confirm_email->setFilters(['email', 'trim',]);

        $this->add($email);
        $this->add($confirm_email);

        $this->add(new Submit('submit', [
            'value' => 'Submit',
            'class' => 'btn btn-lg btn-primary btn-block',
        ]));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
}
